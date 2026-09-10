<?php
/**
 * "Change Forecast Plan" — re-forecast the CHANGEABLE remainder of an already
 * processed plan month, before indents are raised / before production.
 *
 * Forecast plans for future months get split (split_planning_qty) and processed
 * into work orders (Work_order_materials). Marketing sometimes needs to revise the
 * forecast AFTER processing/proceeding but BEFORE indents are raised. This module
 * lets a planner:
 *   - see each processed plan month with a breakdown of locked vs changeable qty,
 *   - free the unlocked (not-in-production, no-indent) work-order qty,
 *   - club in newly added marketing forecast for the same product + month,
 *   - and drop a fresh estimated qty back into the Pending/split state so the
 *     normal split → process → proceed flow re-runs from scratch.
 *
 * SAFETY MODEL (never corrupts real output):
 *   - LOCKED qty is NEVER touched. A WO is locked if it is in the production
 *     pipeline (Pending Verification / Verified - Ready for Batch Allocation /
 *     Sent for Batch Allocation) OR has an indent raised/confirmed
 *     (WO_deductions.indent_status IN ('Raised','Indent Sent') and not cancelled,
 *     or a non-cancelled mrp_indents_confirmation row).
 *   - Only UNLOCKED work orders are cancelled (status='Cancel'); their un-raised
 *     deductions are cancelled too. This is reversible-by-record and fully logged.
 *   - The freed + clubbed qty is written to a NEW split_planning_qty row (Pending),
 *     so the original split + its locked WOs stay intact for history and continue
 *     downstream. No existing committed quantity is ever double-counted.
 *
 * Self-provisioning schema; the authoritative lock math is recomputed server-side
 * on every change (client numbers are never trusted).
 */

// Soft-load audit helpers (avoid fatal if sibling path missing; entry scripts also load it).
if (!function_exists('gw_get_mrp_audit_log')) {
    $__cfp_audit = __DIR__ . '/mrp_audit_log_helpers.php';
    if (is_file($__cfp_audit) && filesize($__cfp_audit) > 50) {
        require_once $__cfp_audit;
    }
}

if (!function_exists('gw_cfp_table_exists')) {
    function gw_cfp_table_exists($conn, $tableName) {
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tableName);
        if ($tableName === '') {
            return false;
        }
        $res = $conn->query("SHOW TABLES LIKE '$tableName'");
        return ($res && $res->num_rows > 0);
    }
}

if (!function_exists('gw_cfp_ensure_split_columns')) {
    function gw_cfp_ensure_split_columns($conn) {
        static $done = false;
        if ($done || !gw_cfp_table_exists($conn, 'split_planning_qty')) {
            return;
        }
        $done = true;
        $need = array(
            'plant_id' => 'TEXT NULL',
            'unit' => 'TEXT NULL',
            'planUnit' => 'TEXT NULL',
            'status' => 'TEXT NULL',
            'work_order_planned_qty' => 'TEXT NULL',
        );
        foreach ($need as $col => $def) {
            $check = @$conn->query("SHOW COLUMNS FROM `split_planning_qty` LIKE '" . $conn->real_escape_string($col) . "'");
            if ($check && $check->num_rows === 0) {
                @$conn->query("ALTER TABLE `split_planning_qty` ADD COLUMN `{$col}` {$def}");
            }
        }
    }
}

if (!function_exists('gw_cfp_ensure_log_table')) {
    function gw_cfp_ensure_log_table($conn) {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $sql = "CREATE TABLE IF NOT EXISTS mrp_forecast_change_log (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            event_datetime DATETIME NOT NULL,
            order_no VARCHAR(100) DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            plan_month VARCHAR(50) DEFAULT NULL,
            plan_year VARCHAR(20) DEFAULT NULL,
            source_split_id INT DEFAULT NULL,
            new_split_id INT DEFAULT NULL,
            total_planned_qty DECIMAL(18,4) DEFAULT NULL,
            qty_in_production DECIMAL(18,4) DEFAULT NULL,
            qty_with_indent DECIMAL(18,4) DEFAULT NULL,
            qty_locked_total DECIMAL(18,4) DEFAULT NULL,
            freed_qty DECIMAL(18,4) DEFAULT NULL,
            clubbed_qty DECIMAL(18,4) DEFAULT NULL,
            new_estimated_qty DECIMAL(18,4) DEFAULT NULL,
            cancelled_wo_ids TEXT,
            clubbed_split_ids TEXT,
            qty_unit VARCHAR(30) DEFAULT NULL,
            changed_by VARCHAR(50) DEFAULT NULL,
            changed_by_name VARCHAR(120) DEFAULT NULL,
            plant_id VARCHAR(50) DEFAULT NULL,
            remark TEXT,
            detail LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cfp_datetime (event_datetime),
            INDEX idx_cfp_order (order_no),
            INDEX idx_cfp_product (product_code),
            INDEX idx_cfp_plant (plant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        @$conn->query($sql);
    }
}

if (!function_exists('gw_cfp_plant_filter')) {
    function gw_cfp_plant_filter($conn, $plantId, $alias) {
        $plantId = trim((string)$plantId);
        if ($plantId === '') {
            return '';
        }
        return " AND $alias.plant_id = '" . $conn->real_escape_string($plantId) . "'";
    }
}

if (!function_exists('gw_cfp_production_lock_statuses')) {
    /** WO statuses that mean the qty is committed to the production pipeline. */
    function gw_cfp_production_lock_statuses() {
        return array(
            'Pending Verification',
            'Verified - Ready for Batch Allocation',
            'Sent for Batch Allocation',
        );
    }
}

if (!function_exists('gw_cfp_compute_plan_locks')) {
    /**
     * Authoritative, server-side lock math for one plan month.
     * Returns total / production-locked / indent-locked / changeable quantities and
     * the concrete unlocked vs locked work-order id lists.
     */
    function gw_cfp_compute_plan_locks($conn, $orderNo, $productCode, $month, $year, $plantId = '') {
        $orderEsc = $conn->real_escape_string((string)$orderNo);
        $prodEsc = $conn->real_escape_string((string)$productCode);
        $monthEsc = $conn->real_escape_string((string)$month);
        $yearEsc = $conn->real_escape_string((string)$year);

        $out = array(
            'split_id' => 0,
            'split_planned_qty' => 0.0,
            'total_wo_qty' => 0.0,
            'qty_in_production' => 0.0,
            'qty_with_indent' => 0.0,
            'qty_locked_total' => 0.0,
            'qty_changeable' => 0.0,
            'unlocked_wo_ids' => array(),
            'locked_wo_ids' => array(),
            'unlocked_wo_count' => 0,
            'locked_wo_count' => 0,
            'qty_unit' => '',
            'product_name' => '',
        );

        // Anchor split row (the processed plan month).
        $splitSql = "SELECT id, COALESCE(NULLIF(work_order_planned_qty,0), oder_qty, 0) AS planned_qty,
                            oder_qty, work_order_planned_qty, product_name,
                            COALESCE(NULLIF(unit,''), planUnit, '') AS qty_unit
                     FROM split_planning_qty
                     WHERE order_no = '$orderEsc' AND product_code = '$prodEsc'
                       AND month = '$monthEsc' AND year = '$yearEsc'";
        $splitSql .= gw_cfp_plant_filter($conn, $plantId, 'split_planning_qty');
        // Prefer the split row that actually owns the work orders (has WOs).
        $splitSql .= " ORDER BY (SELECT COUNT(*) FROM Work_order_materials w WHERE CAST(w.doc_no AS CHAR)=CAST(split_planning_qty.id AS CHAR)) DESC, id ASC LIMIT 1";
        $sres = $conn->query($splitSql);
        if (!$sres || $sres->num_rows === 0) {
            return $out;
        }
        $srow = $sres->fetch_assoc();
        $splitId = (int)$srow['id'];
        $out['split_id'] = $splitId;
        $out['split_planned_qty'] = (float)$srow['planned_qty'];
        $out['qty_unit'] = (string)$srow['qty_unit'];
        $out['product_name'] = (string)$srow['product_name'];

        $prodStatuses = gw_cfp_production_lock_statuses();
        $prodInList = "'" . implode("','", array_map(function ($s) use ($conn) {
            return $conn->real_escape_string($s);
        }, $prodStatuses)) . "'";

        // Each non-cancelled WO for this plan month, with its lock signals.
        $woSql = "SELECT wom.id, wom.workorder_no, COALESCE(wom.batch_size,0) AS qty, wom.status,
                    (CASE WHEN wom.status IN ($prodInList) THEN 1 ELSE 0 END) AS prod_locked,
                    (CASE WHEN EXISTS (
                        SELECT 1 FROM WO_deductions wd
                        WHERE wd.work_order_id = wom.id
                          AND wd.indent_status IN ('Raised','Indent Sent')
                          AND wd.status <> 'Cancel'
                    ) OR EXISTS (
                        SELECT 1 FROM mrp_indents_confirmation c
                        WHERE c.workorder_no = wom.workorder_no
                          AND c.confirmation_status IN ('PENDING','SENT_FOR_PURCHASE','ON_HOLD')
                    ) THEN 1 ELSE 0 END) AS indent_locked
                  FROM Work_order_materials wom
                  WHERE CAST(wom.doc_no AS CHAR) = CAST($splitId AS CHAR)
                    AND wom.order_no = '$orderEsc'
                    AND wom.product_code = '$prodEsc'
                    AND wom.status <> 'Cancel'";
        // mrp_indents_confirmation may not exist on older installs; guard the EXISTS.
        if (!gw_cfp_table_exists($conn, 'mrp_indents_confirmation')) {
            $woSql = str_replace(
                "OR EXISTS (
                        SELECT 1 FROM mrp_indents_confirmation c
                        WHERE c.workorder_no = wom.workorder_no
                          AND c.confirmation_status IN ('PENDING','SENT_FOR_PURCHASE','ON_HOLD')
                    ) ",
                '',
                $woSql
            );
        }

        $wres = $conn->query($woSql);
        if ($wres && $wres->num_rows > 0) {
            while ($w = $wres->fetch_assoc()) {
                $qty = (float)$w['qty'];
                $isProd = (int)$w['prod_locked'] === 1;
                $isIndent = (int)$w['indent_locked'] === 1;
                $out['total_wo_qty'] += $qty;
                if ($isProd) {
                    $out['qty_in_production'] += $qty;
                }
                if ($isIndent) {
                    $out['qty_with_indent'] += $qty;
                }
                if ($isProd || $isIndent) {
                    $out['qty_locked_total'] += $qty;
                    $out['locked_wo_ids'][] = (int)$w['id'];
                } else {
                    $out['qty_changeable'] += $qty;
                    $out['unlocked_wo_ids'][] = (int)$w['id'];
                }
            }
        }
        $out['unlocked_wo_count'] = count($out['unlocked_wo_ids']);
        $out['locked_wo_count'] = count($out['locked_wo_ids']);

        // Round for display sanity.
        foreach (array('split_planned_qty', 'total_wo_qty', 'qty_in_production', 'qty_with_indent', 'qty_locked_total', 'qty_changeable') as $k) {
            $out[$k] = round((float)$out[$k], 4);
        }
        return $out;
    }
}

if (!function_exists('gw_cfp_lock_defaults')) {
    function gw_cfp_lock_defaults($splitId = 0) {
        return array(
            'split_id' => (int)$splitId,
            'split_planned_qty' => 0.0,
            'total_wo_qty' => 0.0,
            'qty_in_production' => 0.0,
            'qty_with_indent' => 0.0,
            'qty_locked_total' => 0.0,
            'qty_changeable' => 0.0,
            'unlocked_wo_ids' => array(),
            'locked_wo_ids' => array(),
            'unlocked_wo_count' => 0,
            'locked_wo_count' => 0,
            'qty_unit' => '',
            'product_name' => '',
        );
    }
}

if (!function_exists('gw_cfp_batch_compute_plan_locks')) {
    /**
     * Batch lock math for many split rows (one WO query instead of N).
     */
    function gw_cfp_batch_compute_plan_locks($conn, array $splitRows, $plantId = '') {
        $map = array();
        if (empty($splitRows)) {
            return $map;
        }

        foreach ($splitRows as $r) {
            $sid = (int)($r['id'] ?? 0);
            if ($sid <= 0) {
                continue;
            }
            $map[$sid] = gw_cfp_lock_defaults($sid);
            $map[$sid]['split_planned_qty'] = round((float)($r['planned_qty'] ?? 0), 4);
            $map[$sid]['qty_unit'] = (string)($r['qty_unit'] ?? '');
            $map[$sid]['product_name'] = (string)($r['product_name'] ?? '');
        }

        if (empty($map)) {
            return $map;
        }

        $idList = implode(',', array_map('intval', array_keys($map)));
        $prodStatuses = gw_cfp_production_lock_statuses();
        $prodInList = "'" . implode("','", array_map(function ($s) use ($conn) {
            return $conn->real_escape_string($s);
        }, $prodStatuses)) . "'";

        $indentConfirmSql = '';
        if (gw_cfp_table_exists($conn, 'mrp_indents_confirmation')) {
            $indentConfirmSql = "OR EXISTS (
                        SELECT 1 FROM mrp_indents_confirmation c
                        WHERE c.workorder_no = wom.workorder_no
                          AND c.confirmation_status IN ('PENDING','SENT_FOR_PURCHASE','ON_HOLD')
                    )";
        }

        $woSql = "SELECT wom.id, CAST(wom.doc_no AS UNSIGNED) AS split_id,
                    wom.workorder_no, COALESCE(wom.batch_size,0) AS qty, wom.status,
                    (CASE WHEN wom.status IN ($prodInList) THEN 1 ELSE 0 END) AS prod_locked,
                    (CASE WHEN EXISTS (
                        SELECT 1 FROM WO_deductions wd
                        WHERE wd.work_order_id = wom.id
                          AND wd.indent_status IN ('Raised','Indent Sent')
                          AND wd.status <> 'Cancel'
                    ) $indentConfirmSql THEN 1 ELSE 0 END) AS indent_locked
                  FROM Work_order_materials wom
                  WHERE CAST(wom.doc_no AS UNSIGNED) IN ($idList)
                    AND wom.status <> 'Cancel'";

        $wres = $conn->query($woSql);
        if ($wres && $wres->num_rows > 0) {
            while ($w = $wres->fetch_assoc()) {
                $sid = (int)($w['split_id'] ?? 0);
                if (!isset($map[$sid])) {
                    continue;
                }
                $qty = (float)$w['qty'];
                $isProd = (int)$w['prod_locked'] === 1;
                $isIndent = (int)$w['indent_locked'] === 1;
                $map[$sid]['total_wo_qty'] += $qty;
                if ($isProd) {
                    $map[$sid]['qty_in_production'] += $qty;
                }
                if ($isIndent) {
                    $map[$sid]['qty_with_indent'] += $qty;
                }
                if ($isProd || $isIndent) {
                    $map[$sid]['qty_locked_total'] += $qty;
                    $map[$sid]['locked_wo_ids'][] = (int)$w['id'];
                } else {
                    $map[$sid]['qty_changeable'] += $qty;
                    $map[$sid]['unlocked_wo_ids'][] = (int)$w['id'];
                }
            }
        }

        foreach ($map as $sid => &$out) {
            $out['unlocked_wo_count'] = count($out['unlocked_wo_ids']);
            $out['locked_wo_count'] = count($out['locked_wo_ids']);
            foreach (array('split_planned_qty', 'total_wo_qty', 'qty_in_production', 'qty_with_indent', 'qty_locked_total', 'qty_changeable') as $k) {
                $out[$k] = round((float)$out[$k], 4);
            }
        }
        unset($out);

        return $map;
    }
}

if (!function_exists('gw_cfp_batch_clubbable_totals')) {
    /**
     * Batch clubbable forecast totals keyed by product|month|year.
     */
    function gw_cfp_batch_clubbable_totals($conn, array $splitRows, $plantId = '') {
        $totals = array();
        if (empty($splitRows)) {
            return $totals;
        }

        $combos = array();
        foreach ($splitRows as $r) {
            $key = ($r['product_code'] ?? '') . '|' . ($r['month'] ?? '') . '|' . ($r['year'] ?? '');
            $combos[$key] = array(
                'product_code' => $r['product_code'] ?? '',
                'month' => $r['month'] ?? '',
                'year' => $r['year'] ?? '',
            );
        }

        foreach ($combos as $key => $combo) {
            $prodEsc = $conn->real_escape_string((string)$combo['product_code']);
            $monthEsc = $conn->real_escape_string((string)$combo['month']);
            $yearEsc = $conn->real_escape_string((string)$combo['year']);

            $sql = "SELECT sp.id, COALESCE(NULLIF(sp.oder_qty,0), 0) AS oder_qty
                    FROM split_planning_qty sp
                    WHERE sp.product_code = '$prodEsc'
                      AND sp.month = '$monthEsc'
                      AND sp.year = '$yearEsc'
                      AND (sp.status IS NULL OR TRIM(IFNULL(sp.status,'')) = '' OR LOWER(TRIM(sp.status)) = 'pending')
                      AND sp.id NOT IN (
                          SELECT DISTINCT CAST(w.doc_no AS UNSIGNED) FROM Work_order_materials w
                          WHERE w.doc_no IS NOT NULL AND TRIM(w.doc_no) <> '' AND w.doc_no <> '0'
                      )
                      AND COALESCE(NULLIF(sp.oder_qty,0),0) > 0";
            $sql .= gw_cfp_plant_filter($conn, $plantId, 'sp');

            $total = 0.0;
            $count = 0;
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $total += (float)$row['oder_qty'];
                    $count++;
                }
            }
            $totals[$key] = array('total' => round($total, 4), 'count' => $count);
        }

        return $totals;
    }
}

if (!function_exists('gw_cfp_clubbable_for_plan')) {
    function gw_cfp_clubbable_for_plan($clubTotals, $productCode, $month, $year, $excludeSplitId = 0) {
        $key = $productCode . '|' . $month . '|' . $year;
        $base = isset($clubTotals[$key]) ? $clubTotals[$key] : array('total' => 0.0, 'count' => 0);
        if ($excludeSplitId <= 0) {
            return $base;
        }
        return $base;
    }
}

if (!function_exists('gw_cfp_get_clubbable_forecast')) {
    /**
     * New, not-yet-planned marketing forecast for the SAME product + month + year
     * (across order numbers) that can be merged into the fresh estimated qty.
     * These are Pending split rows that have NO work orders yet.
     */
    function gw_cfp_get_clubbable_forecast($conn, $orderNo, $productCode, $month, $year, $plantId = '', $excludeSplitId = 0) {
        $prodEsc = $conn->real_escape_string((string)$productCode);
        $monthEsc = $conn->real_escape_string((string)$month);
        $yearEsc = $conn->real_escape_string((string)$year);
        $excludeSplitId = (int)$excludeSplitId;

        $sql = "SELECT sp.id, sp.order_no, sp.product_code, sp.product_name, sp.month, sp.year,
                       COALESCE(NULLIF(sp.oder_qty,0), 0) AS oder_qty,
                       COALESCE(NULLIF(sp.unit,''), sp.planUnit, '') AS qty_unit,
                       sp.entry_by, sp.date
                FROM split_planning_qty sp
                WHERE sp.product_code = '$prodEsc'
                  AND sp.month = '$monthEsc'
                  AND sp.year = '$yearEsc'
                  AND (sp.status IS NULL OR TRIM(IFNULL(sp.status,'')) = '' OR LOWER(TRIM(sp.status)) = 'pending')
                  AND sp.id NOT IN (
                      SELECT DISTINCT CAST(w.doc_no AS UNSIGNED) FROM Work_order_materials w
                      WHERE w.doc_no IS NOT NULL AND TRIM(w.doc_no) <> '' AND w.doc_no <> '0'
                  )
                  AND COALESCE(NULLIF(sp.oder_qty,0),0) > 0";
        if ($excludeSplitId > 0) {
            $sql .= " AND sp.id <> $excludeSplitId";
        }
        $sql .= gw_cfp_plant_filter($conn, $plantId, 'sp');
        $sql .= " ORDER BY sp.id ASC";

        $rows = array();
        $total = 0.0;
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($r = $res->fetch_assoc()) {
                $r['oder_qty'] = round((float)$r['oder_qty'], 4);
                $total += (float)$r['oder_qty'];
                $rows[] = $r;
            }
        }
        return array('rows' => $rows, 'total' => round($total, 4));
    }
}

if (!function_exists('gw_cfp_get_processed_plans')) {
    /**
     * Paginated board of processed plans with batched lock / clubbable math.
     */
    function gw_cfp_get_processed_plans($conn, $plantId = '', $filters = array()) {
        gw_cfp_ensure_log_table($conn);
        gw_cfp_ensure_split_columns($conn);

        $empty = array(
            'plans' => array(),
            'total' => 0,
            'page' => max(1, (int)($filters['page'] ?? 1)),
            'pageSize' => max(10, min(100, (int)($filters['pageSize'] ?? 25))),
            'totalPages' => 0,
            'changeable_on_page' => 0,
        );

        if (!gw_cfp_table_exists($conn, 'split_planning_qty') || !gw_cfp_table_exists($conn, 'Work_order_materials')) {
            return $empty;
        }

        $where = array("sp.id IN (
            SELECT DISTINCT CAST(w.doc_no AS UNSIGNED) FROM Work_order_materials w
            WHERE w.doc_no IS NOT NULL AND TRIM(w.doc_no) <> '' AND w.doc_no <> '0' AND w.status <> 'Cancel'
        )");
        $where[] = "(sp.status IS NULL OR LOWER(TRIM(IFNULL(sp.status,''))) NOT IN ('cancel','clubbed','rejected'))";
        $plantClause = gw_cfp_plant_filter($conn, $plantId, 'sp');
        if ($plantClause !== '') {
            $where[] = ltrim($plantClause, ' AND');
        }
        if (!empty($filters['order_no'])) {
            $where[] = "sp.order_no = '" . $conn->real_escape_string((string)$filters['order_no']) . "'";
        }
        if (!empty($filters['product_code'])) {
            $where[] = "sp.product_code = '" . $conn->real_escape_string((string)$filters['product_code']) . "'";
        }
        if (!empty($filters['search'])) {
            $s = $conn->real_escape_string((string)$filters['search']);
            $where[] = "(sp.order_no LIKE '%$s%' OR sp.product_code LIKE '%$s%' OR sp.product_name LIKE '%$s%'
                         OR sp.month LIKE '%$s%' OR sp.year LIKE '%$s%' OR sp.status LIKE '%$s%')";
        }

        $whereSql = implode(' AND ', $where);
        $page = max(1, (int)($filters['page'] ?? 1));
        $pageSize = max(10, min(100, (int)($filters['pageSize'] ?? 25)));
        $offset = ($page - 1) * $pageSize;

        $total = 0;
        $countRes = @$conn->query("SELECT COUNT(*) AS cnt FROM split_planning_qty sp WHERE " . $whereSql);
        if ($countRes === false) {
            return $empty;
        }
        if ($countRes && $countRes->num_rows > 0) {
            $total = (int)$countRes->fetch_assoc()['cnt'];
        }

        $sql = "SELECT sp.id, sp.order_no, sp.product_code, sp.product_name, sp.month, sp.year, sp.status,
                       COALESCE(NULLIF(sp.work_order_planned_qty,0), sp.oder_qty, 0) AS planned_qty,
                       COALESCE(NULLIF(sp.unit,''), sp.planUnit, '') AS qty_unit
                FROM split_planning_qty sp
                WHERE " . $whereSql . "
                ORDER BY sp.year DESC, sp.id DESC
                LIMIT $pageSize OFFSET $offset";
        $res = @$conn->query($sql);
        if ($res === false) {
            return $empty;
        }
        $splitRows = array();
        if ($res && $res->num_rows > 0) {
            while ($r = $res->fetch_assoc()) {
                $splitRows[] = $r;
            }
        }

        $lockMap = gw_cfp_batch_compute_plan_locks($conn, $splitRows, $plantId);
        $clubTotals = gw_cfp_batch_clubbable_totals($conn, $splitRows, $plantId);

        $plans = array();
        $changeableOnPage = 0;
        foreach ($splitRows as $r) {
            $sid = (int)$r['id'];
            $locks = isset($lockMap[$sid]) ? $lockMap[$sid] : gw_cfp_compute_plan_locks(
                $conn,
                $r['order_no'],
                $r['product_code'],
                $r['month'],
                $r['year'],
                $plantId
            );
            $clubKey = ($r['product_code'] ?? '') . '|' . ($r['month'] ?? '') . '|' . ($r['year'] ?? '');
            $club = isset($clubTotals[$clubKey]) ? $clubTotals[$clubKey] : array('total' => 0.0, 'count' => 0);
            $canChange = ($locks['qty_changeable'] > 0 || $club['total'] > 0) ? 1 : 0;
            if ($canChange) {
                $changeableOnPage++;
            }
            $plans[] = array(
                'split_id' => $sid,
                'order_no' => $r['order_no'],
                'product_code' => $r['product_code'],
                'product_name' => $r['product_name'],
                'plan_month' => $r['month'],
                'plan_year' => $r['year'],
                'status' => $r['status'],
                'qty_unit' => $r['qty_unit'] ?: $locks['qty_unit'],
                'planned_qty' => round((float)$r['planned_qty'], 4),
                'total_wo_qty' => $locks['total_wo_qty'],
                'qty_in_production' => $locks['qty_in_production'],
                'qty_with_indent' => $locks['qty_with_indent'],
                'qty_locked_total' => $locks['qty_locked_total'],
                'qty_changeable' => $locks['qty_changeable'],
                'unlocked_wo_count' => $locks['unlocked_wo_count'],
                'locked_wo_count' => $locks['locked_wo_count'],
                'clubbable_qty' => $club['total'],
                'clubbable_count' => $club['count'],
                'can_change' => $canChange,
            );
        }

        return array(
            'plans' => $plans,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $pageSize > 0 ? (int)ceil($total / $pageSize) : 0,
            'changeable_on_page' => $changeableOnPage,
        );
    }
}

if (!function_exists('gw_cfp_change_plan')) {
    /**
     * Execute the forecast change for one plan month.
     * @param array $input { order_no, product_code, month, year, plant_id,
     *                       club (bool, default true), club_split_ids (optional int[]),
     *                       new_qty_override (optional), remark }
     * @param array $meta  { emp_id, department }
     */
    function gw_cfp_change_plan($conn, $input, $meta = array()) {
        gw_cfp_ensure_log_table($conn);

        $orderNo = trim((string)($input['order_no'] ?? ''));
        $productCode = trim((string)($input['product_code'] ?? ''));
        $month = trim((string)($input['month'] ?? ($input['plan_month'] ?? '')));
        $year = trim((string)($input['year'] ?? ($input['plan_year'] ?? '')));
        $plantId = trim((string)($input['plant_id'] ?? ($_GET['plant_id'] ?? '')));
        $doClub = !isset($input['club']) ? true : (bool)$input['club'];
        $remark = trim((string)($input['remark'] ?? ''));
        $empId = (string)($meta['emp_id'] ?? ($_GET['emp_id'] ?? ''));

        if ($orderNo === '' || $productCode === '' || $month === '' || $year === '') {
            return array('status' => 'error', 'message' => 'order_no, product_code, month and year are required.');
        }

        $locks = gw_cfp_compute_plan_locks($conn, $orderNo, $productCode, $month, $year, $plantId);
        if ((int)$locks['split_id'] <= 0) {
            return array('status' => 'error', 'message' => 'Plan not found for the given order/product/month.');
        }

        // Resolve clubbable rows.
        $clubData = gw_cfp_get_clubbable_forecast($conn, $orderNo, $productCode, $month, $year, $plantId, (int)$locks['split_id']);
        $clubRows = $clubData['rows'];
        $selectedClubIds = array();
        if (!empty($input['club_split_ids']) && is_array($input['club_split_ids'])) {
            $selectedClubIds = array_map('intval', $input['club_split_ids']);
        }
        $clubbedQty = 0.0;
        $clubbedIds = array();
        if ($doClub) {
            foreach ($clubRows as $cr) {
                $cid = (int)$cr['id'];
                if (!empty($selectedClubIds) && !in_array($cid, $selectedClubIds, true)) {
                    continue;
                }
                $clubbedQty += (float)$cr['oder_qty'];
                $clubbedIds[] = $cid;
            }
        }

        $freedQty = (float)$locks['qty_changeable'];
        $newEstimatedQty = round($freedQty + $clubbedQty, 4);

        if ($newEstimatedQty <= 0 && empty($locks['unlocked_wo_ids']) && empty($clubbedIds)) {
            return array('status' => 'error', 'message' => 'Nothing changeable: the entire plan is already in production or under indent, and no new forecast to club.');
        }

        // Fetch the source split row to clone metadata for the fresh split.
        $splitId = (int)$locks['split_id'];
        $srcRes = $conn->query("SELECT * FROM split_planning_qty WHERE id = $splitId LIMIT 1");
        if (!$srcRes || $srcRes->num_rows === 0) {
            return array('status' => 'error', 'message' => 'Source split row missing.');
        }
        $src = $srcRes->fetch_assoc();

        date_default_timezone_set('Asia/Kolkata');
        $now = date('Y-m-d H:i:s');
        $empEsc = $conn->real_escape_string($empId);

        $conn->begin_transaction();
        try {
            // 1) Cancel the UNLOCKED work orders (and their un-raised deductions only).
            $cancelledWoIds = array();
            if (!empty($locks['unlocked_wo_ids'])) {
                $idList = implode(',', array_map('intval', $locks['unlocked_wo_ids']));
                if (!$conn->query("UPDATE Work_order_materials SET status = 'Cancel' WHERE id IN ($idList)")) {
                    throw new Exception('Failed to cancel work orders: ' . $conn->error);
                }
                // Only cancel deductions that are NOT under a raised indent (safety).
                $conn->query("UPDATE WO_deductions SET status = 'Cancel'
                              WHERE work_order_id IN ($idList)
                                AND indent_status = 'Not Raised'");
                $cancelledWoIds = array_map('intval', $locks['unlocked_wo_ids']);
            }

            // 2) Mark clubbed source splits as consumed so their qty is not double-counted.
            if (!empty($clubbedIds)) {
                $clubIdList = implode(',', array_map('intval', $clubbedIds));
                $conn->query("UPDATE split_planning_qty SET status = 'Clubbed' WHERE id IN ($clubIdList)");
            }

            // 3) Create a fresh Pending split row for the freed + clubbed qty so the
            //    normal split → process flow re-runs. (Original split + locked WOs stay.)
            $newSplitId = 0;
            if ($newEstimatedQty > 0) {
                $g = function ($k) use ($conn, $src) {
                    return $conn->real_escape_string((string)($src[$k] ?? ''));
                };
                $unit = $g('unit') !== '' ? $g('unit') : $g('planUnit');
                $insSql = "INSERT INTO split_planning_qty
                    (order_no, product_name, product_code, date, month, year,
                     oder_qty, balance_qty, plant_id, entry_by, InQty, outQty, avbl_stock, status, unit, planUnit)
                    VALUES
                    ('" . $g('order_no') . "', '" . $g('product_name') . "', '" . $g('product_code') . "', '$now',
                     '" . $g('month') . "', '" . $g('year') . "',
                     '$newEstimatedQty', '$newEstimatedQty', '" . $g('plant_id') . "', '$empEsc',
                     '$newEstimatedQty', '0', '0', 'Pending', '$unit', '$unit')";
                if (!$conn->query($insSql)) {
                    throw new Exception('Failed to create fresh split: ' . $conn->error);
                }
                $newSplitId = (int)$conn->insert_id;
            }

            // 4) Keep the forecast line in the planning queue so the fresh split is processable.
            $conn->query("UPDATE order_materials SET reqStatus = 'Inprocess'
                          WHERE order_no = '" . $conn->real_escape_string($orderNo) . "'
                            AND product_code = '" . $conn->real_escape_string($productCode) . "'
                            AND reqStatus = 'Complete'");

            // 5) History: dedicated change log + unified audit trail.
            $detail = json_encode(array(
                'locks' => $locks,
                'clubbed_ids' => $clubbedIds,
                'cancelled_wo_ids' => $cancelledWoIds,
                'new_split_id' => $newSplitId,
            ), JSON_UNESCAPED_UNICODE);
            $logSql = "INSERT INTO mrp_forecast_change_log
                (event_datetime, order_no, product_code, product_name, plan_month, plan_year,
                 source_split_id, new_split_id, total_planned_qty, qty_in_production, qty_with_indent,
                 qty_locked_total, freed_qty, clubbed_qty, new_estimated_qty, cancelled_wo_ids,
                 clubbed_split_ids, qty_unit, changed_by, changed_by_name, plant_id, remark, detail)
                VALUES
                ('$now', '" . $conn->real_escape_string($orderNo) . "', '" . $conn->real_escape_string($productCode) . "',
                 '" . $conn->real_escape_string((string)$locks['product_name']) . "', '" . $conn->real_escape_string($month) . "',
                 '" . $conn->real_escape_string($year) . "', $splitId, " . ($newSplitId ?: 'NULL') . ",
                 '" . (float)$locks['total_wo_qty'] . "', '" . (float)$locks['qty_in_production'] . "',
                 '" . (float)$locks['qty_with_indent'] . "', '" . (float)$locks['qty_locked_total'] . "',
                 '" . (float)$freedQty . "', '" . (float)$clubbedQty . "', '" . (float)$newEstimatedQty . "',
                 '" . $conn->real_escape_string(implode(',', $cancelledWoIds)) . "',
                 '" . $conn->real_escape_string(implode(',', $clubbedIds)) . "',
                 '" . $conn->real_escape_string((string)$locks['qty_unit']) . "', '$empEsc',
                 '" . $conn->real_escape_string((string)($meta['emp_name'] ?? '')) . "',
                 '" . $conn->real_escape_string($plantId) . "',
                 '" . $conn->real_escape_string($remark) . "',
                 '" . $conn->real_escape_string($detail) . "')";
            $conn->query($logSql);

            if (function_exists('gw_mrp_audit_log')) {
                gw_mrp_audit_log($conn, array(
                    'stage' => 'FORECAST_CHANGED',
                    'event_type' => 'Forecast plan revised (re-split remainder)',
                    'source_screen' => 'Change Forecast Plan',
                    'order_no' => $orderNo,
                    'product_code' => $productCode,
                    'product_name' => $locks['product_name'],
                    'plan_month' => $month,
                    'qty_unit' => $locks['qty_unit'],
                    'required_qty' => $locks['total_wo_qty'],
                    'plan_qty' => $newEstimatedQty,
                    'deducted_qty' => $locks['qty_locked_total'],
                    'balance_qty' => $freedQty,
                    'status_from' => $src['status'] ?? '',
                    'status_to' => 'Pending (re-split)',
                    'plant_id' => $plantId,
                    'emp_id' => $empId,
                    'department' => $meta['department'] ?? '',
                    'remark' => trim('Freed ' . $freedQty . ' + clubbed ' . $clubbedQty . ' = ' . $newEstimatedQty
                        . ' ' . $locks['qty_unit'] . '. Locked kept: ' . $locks['qty_locked_total']
                        . '. ' . $remark),
                    'detail' => array(
                        'source_split_id' => $splitId,
                        'new_split_id' => $newSplitId,
                        'cancelled_wo_ids' => $cancelledWoIds,
                        'clubbed_split_ids' => $clubbedIds,
                    ),
                ));
            }

            $conn->commit();
            return array(
                'status' => 'success',
                'message' => 'Forecast plan changed. Fresh estimated qty ' . $newEstimatedQty . ' ' . $locks['qty_unit']
                    . ' is ready to split & process again.',
                'source_split_id' => $splitId,
                'new_split_id' => $newSplitId,
                'freed_qty' => round($freedQty, 4),
                'clubbed_qty' => round($clubbedQty, 4),
                'new_estimated_qty' => $newEstimatedQty,
                'qty_locked_total' => $locks['qty_locked_total'],
                'cancelled_wo_count' => count($cancelledWoIds),
                'clubbed_count' => count($clubbedIds),
            );
        } catch (Exception $e) {
            $conn->rollback();
            return array('status' => 'error', 'message' => $e->getMessage());
        }
    }
}

if (!function_exists('gw_cfp_get_change_log')) {
    function gw_cfp_get_change_log($conn, $limit = 500, $plantId = '') {
        gw_cfp_ensure_log_table($conn);
        $limit = max(1, min(2000, (int)$limit));
        $where = '';
        if (trim((string)$plantId) !== '') {
            $where = " WHERE plant_id = '" . $conn->real_escape_string((string)$plantId) . "'";
        }
        $out = array();
        $res = $conn->query("SELECT * FROM mrp_forecast_change_log$where ORDER BY event_datetime DESC, id DESC LIMIT $limit");
        if ($res && $res->num_rows > 0) {
            while ($r = $res->fetch_assoc()) {
                $out[] = $r;
            }
        }
        return $out;
    }
}
