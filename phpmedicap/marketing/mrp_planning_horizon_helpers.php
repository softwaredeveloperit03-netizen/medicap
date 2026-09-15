<?php
/**
 * MRP Phase 5 — Immediate (≤30 days) vs Future planning classification.
 * Additive columns on Work_order_materials; does not change WO status flows.
 */

if (!function_exists('gw_mrp_planning_horizon_window_days')) {
    function gw_mrp_planning_horizon_window_days() {
        return 30;
    }
}

if (!function_exists('gw_ensure_mrp_planning_horizon_columns')) {
    function gw_ensure_mrp_planning_horizon_columns($conn) {
        static $done = false;
        if ($done || !($conn instanceof mysqli)) {
            return;
        }
        $cols = array(
            'planning_horizon' => "VARCHAR(30) NULL DEFAULT NULL",
            'planning_horizon_source' => "VARCHAR(20) NULL DEFAULT NULL",
            'planning_horizon_set_by' => "VARCHAR(50) NULL DEFAULT NULL",
            'planning_horizon_set_by_name' => "VARCHAR(120) NULL DEFAULT NULL",
            'planning_horizon_set_on' => "DATETIME NULL DEFAULT NULL",
            'planning_horizon_remark' => "TEXT NULL",
            'planning_horizon_ref_date' => "DATE NULL DEFAULT NULL",
            'planning_horizon_days' => "INT NULL DEFAULT NULL",
        );
        foreach ($cols as $colName => $colDef) {
            $exists = false;
            try {
                $col = $conn->query("SHOW COLUMNS FROM Work_order_materials LIKE '" . $conn->real_escape_string($colName) . "'");
                $exists = ($col && $col->num_rows > 0);
            } catch (Throwable $e) {
                $exists = false;
            }
            if (!$exists) {
                try {
                    $conn->query("ALTER TABLE Work_order_materials ADD COLUMN `$colName` $colDef");
                } catch (Throwable $e) {
                    // ignore duplicate / race
                }
            }
        }
        $done = true;
    }
}

if (!function_exists('gw_mrp_parse_horizon_date')) {
    function gw_mrp_parse_horizon_date($raw) {
        $raw = trim((string)$raw);
        if ($raw === '' || $raw === '0000-00-00' || $raw === '0000-00-00 00:00:00') {
            return null;
        }
        // dd/mm/yyyy or dd-mm-yyyy
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $raw, $m)) {
            $ts = strtotime(sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]));
            return $ts ? date('Y-m-d', $ts) : null;
        }
        $ts = strtotime($raw);
        if ($ts === false) {
            return null;
        }
        return date('Y-m-d', $ts);
    }
}

if (!function_exists('gw_mrp_classify_planning_horizon')) {
    /**
     * Classify Immediate (≤30 days) / Future / Undecided from a reference date.
     * @return array{planning_horizon:string,planning_horizon_days:?int,planning_horizon_ref_date:?string,planning_horizon_label:string}
     */
    function gw_mrp_classify_planning_horizon($refDateRaw, $windowDays = null) {
        $window = $windowDays !== null ? (int)$windowDays : gw_mrp_planning_horizon_window_days();
        if ($window <= 0) {
            $window = 30;
        }
        $ref = gw_mrp_parse_horizon_date($refDateRaw);
        if ($ref === null) {
            return array(
                'planning_horizon' => 'UNDECIDED',
                'planning_horizon_days' => null,
                'planning_horizon_ref_date' => null,
                'planning_horizon_label' => 'Not Decided',
            );
        }
        date_default_timezone_set('Asia/Kolkata');
        $today = new DateTimeImmutable(date('Y-m-d'));
        $target = DateTimeImmutable::createFromFormat('Y-m-d', $ref);
        if (!$target) {
            return array(
                'planning_horizon' => 'UNDECIDED',
                'planning_horizon_days' => null,
                'planning_horizon_ref_date' => null,
                'planning_horizon_label' => 'Not Decided',
            );
        }
        $days = (int)$today->diff($target)->format('%r%a');
        // Past or within window → Immediate planning
        if ($days <= $window) {
            $horizon = 'IMMEDIATE';
            $label = 'Immediate (≤' . $window . ' days)';
        } else {
            $horizon = 'FUTURE';
            $label = 'Future Plan';
        }
        return array(
            'planning_horizon' => $horizon,
            'planning_horizon_days' => $days,
            'planning_horizon_ref_date' => $ref,
            'planning_horizon_label' => $label,
        );
    }
}

if (!function_exists('gw_mrp_horizon_ref_date_from_row')) {
    function gw_mrp_horizon_ref_date_from_row(array $row) {
        $candidates = array(
            $row['planning_horizon_ref_date'] ?? null,
            $row['delivery_date'] ?? null,
            $row['deliveryDate'] ?? null,
            $row['expected_production_start_date'] ?? null,
        );
        foreach ($candidates as $c) {
            $parsed = gw_mrp_parse_horizon_date($c);
            if ($parsed !== null) {
                return $parsed;
            }
        }
        return null;
    }
}

if (!function_exists('gw_mrp_attach_planning_horizon')) {
    /**
     * Attach horizon fields to a WO row. Manual override wins over auto classify.
     */
    function gw_mrp_attach_planning_horizon(array &$row, $windowDays = null) {
        $stored = strtoupper(trim((string)($row['planning_horizon'] ?? '')));
        $source = strtoupper(trim((string)($row['planning_horizon_source'] ?? '')));
        $ref = gw_mrp_horizon_ref_date_from_row($row);
        $auto = gw_mrp_classify_planning_horizon($ref, $windowDays);

        if ($source === 'MANUAL' && in_array($stored, array('IMMEDIATE', 'FUTURE', 'UNDECIDED'), true)) {
            $row['planning_horizon'] = $stored;
            $row['planning_horizon_source'] = 'MANUAL';
            $row['planning_horizon_days'] = $auto['planning_horizon_days'];
            $row['planning_horizon_ref_date'] = $auto['planning_horizon_ref_date'];
            if ($stored === 'IMMEDIATE') {
                $row['planning_horizon_label'] = 'Immediate (manual)';
            } elseif ($stored === 'FUTURE') {
                $row['planning_horizon_label'] = 'Future Plan (manual)';
            } else {
                $row['planning_horizon_label'] = 'Not Decided (manual)';
            }
            $row['planning_horizon_auto'] = $auto['planning_horizon'];
            return;
        }

        $row['planning_horizon'] = $auto['planning_horizon'];
        $row['planning_horizon_source'] = $source === 'AUTO' ? 'AUTO' : 'AUTO';
        $row['planning_horizon_days'] = $auto['planning_horizon_days'];
        $row['planning_horizon_ref_date'] = $auto['planning_horizon_ref_date'];
        $row['planning_horizon_label'] = $auto['planning_horizon_label'];
        $row['planning_horizon_auto'] = $auto['planning_horizon'];
    }
}

if (!function_exists('gw_mrp_set_wo_planning_horizon')) {
    function gw_mrp_set_wo_planning_horizon($conn, $workorderNo, $horizon, $remark = '', $meta = []) {
        gw_ensure_mrp_planning_horizon_columns($conn);
        $workorderNo = trim((string)$workorderNo);
        $horizon = strtoupper(trim((string)$horizon));
        $allowed = array('IMMEDIATE', 'FUTURE', 'UNDECIDED');
        if ($workorderNo === '') {
            return array('status' => 'error', 'message' => 'workorder_no is required');
        }
        if (!in_array($horizon, $allowed, true)) {
            return array('status' => 'error', 'message' => 'planning_horizon must be IMMEDIATE, FUTURE, or UNDECIDED');
        }

        $woEsc = $conn->real_escape_string($workorderNo);
        $res = $conn->query("SELECT * FROM Work_order_materials WHERE workorder_no = '$woEsc' LIMIT 1");
        if (!$res || $res->num_rows === 0) {
            return array('status' => 'error', 'message' => 'Work order not found');
        }
        $row = $res->fetch_assoc();
        $ref = gw_mrp_horizon_ref_date_from_row($row);
        $auto = gw_mrp_classify_planning_horizon($ref);

        date_default_timezone_set('Asia/Kolkata');
        $now = date('Y-m-d H:i:s');
        $empId = $conn->real_escape_string((string)($meta['emp_id'] ?? ($_GET['emp_id'] ?? '')));
        $empName = $conn->real_escape_string((string)($meta['emp_name'] ?? ''));
        $remarkEsc = $conn->real_escape_string((string)$remark);
        $refEsc = $auto['planning_horizon_ref_date'] !== null
            ? "'" . $conn->real_escape_string($auto['planning_horizon_ref_date']) . "'"
            : 'NULL';
        $daysSql = $auto['planning_horizon_days'] === null ? 'NULL' : (int)$auto['planning_horizon_days'];

        $sql = "UPDATE Work_order_materials SET
            planning_horizon = '$horizon',
            planning_horizon_source = 'MANUAL',
            planning_horizon_set_by = '$empId',
            planning_horizon_set_by_name = '$empName',
            planning_horizon_set_on = '$now',
            planning_horizon_remark = '$remarkEsc',
            planning_horizon_ref_date = $refEsc,
            planning_horizon_days = $daysSql
            WHERE workorder_no = '$woEsc'";
        try {
            if (!$conn->query($sql)) {
                return array('status' => 'error', 'message' => $conn->error ?: 'Update failed');
            }
        } catch (Throwable $e) {
            return array('status' => 'error', 'message' => $e->getMessage());
        }

        return array(
            'status' => 'success',
            'message' => 'Planning horizon saved',
            'workorder_no' => $workorderNo,
            'planning_horizon' => $horizon,
            'planning_horizon_source' => 'MANUAL',
            'planning_horizon_auto' => $auto['planning_horizon'],
            'planning_horizon_days' => $auto['planning_horizon_days'],
            'planning_horizon_ref_date' => $auto['planning_horizon_ref_date'],
            'planning_horizon_label' => $horizon === 'IMMEDIATE'
                ? 'Immediate (manual)'
                : ($horizon === 'FUTURE' ? 'Future Plan (manual)' : 'Not Decided (manual)'),
        );
    }
}

if (!function_exists('gw_mrp_get_wo_planning_horizons')) {
    function gw_mrp_get_wo_planning_horizons($conn, $params = []) {
        gw_ensure_mrp_planning_horizon_columns($conn);
        $plantId = trim((string)($params['plant_id'] ?? ''));
        $horizonFilter = strtoupper(trim((string)($params['horizon'] ?? $params['planning_horizon'] ?? 'ALL')));
        $search = trim((string)($params['search'] ?? ''));
        $limit = max(1, min(500, (int)($params['limit'] ?? 200)));

        $where = "WHERE a.status NOT IN ('Cancel', 'Hold')";
        if ($plantId !== '') {
            $p = $conn->real_escape_string($plantId);
            $where .= " AND (a.plant_id = '$p' OR a.plant_id IS NULL OR TRIM(IFNULL(a.plant_id,'')) = '')";
        }
        if ($search !== '') {
            $s = $conn->real_escape_string($search);
            $where .= " AND (a.workorder_no LIKE '%$s%' OR a.order_no LIKE '%$s%' OR a.product_code LIKE '%$s%')";
        }

        $sql = "SELECT a.id, a.workorder_no, a.order_no, a.product_code, a.status, a.plant_id,
                       a.deliveryDate, a.expected_production_start_date, a.planMonth,
                       (SELECT deliveryDate FROM order_materials om
                        WHERE om.order_no = a.order_no
                          AND (om.product_code = a.product_code OR TRIM(IFNULL(a.product_code,'')) = '')
                        ORDER BY om.id DESC LIMIT 1) AS delivery_date,
                       (SELECT product_name FROM product p WHERE p.product_code = a.product_code LIMIT 1) AS product_name
                FROM Work_order_materials a
                $where
                ORDER BY a.id DESC
                LIMIT $limit";
        $result = null;
        try {
            $result = $conn->query($sql);
        } catch (Throwable $e) {
            return array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'rows' => array(),
                'total' => 0,
                'counts' => array('IMMEDIATE' => 0, 'FUTURE' => 0, 'UNDECIDED' => 0),
                'window_days' => gw_mrp_planning_horizon_window_days(),
            );
        }
        $rows = array();
        $counts = array('IMMEDIATE' => 0, 'FUTURE' => 0, 'UNDECIDED' => 0);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Merge stored horizon columns when present (after ensure).
                try {
                    $woEsc = $conn->real_escape_string((string)$row['workorder_no']);
                    $meta = $conn->query(
                        "SELECT planning_horizon, planning_horizon_source, planning_horizon_set_by,
                                planning_horizon_set_by_name, planning_horizon_set_on,
                                planning_horizon_remark, planning_horizon_ref_date, planning_horizon_days
                         FROM Work_order_materials WHERE workorder_no = '$woEsc' LIMIT 1"
                    );
                    if ($meta && $meta->num_rows > 0) {
                        $row = array_merge($row, $meta->fetch_assoc());
                    }
                } catch (Throwable $e) {
                    // columns may still be missing
                }
                gw_mrp_attach_planning_horizon($row);
                $h = $row['planning_horizon'] ?? 'UNDECIDED';
                if (isset($counts[$h])) {
                    $counts[$h]++;
                }
                if ($horizonFilter !== 'ALL' && $horizonFilter !== '' && $h !== $horizonFilter) {
                    continue;
                }
                $rows[] = $row;
            }
        }

        return array(
            'status' => 'success',
            'rows' => $rows,
            'total' => count($rows),
            'counts' => $counts,
            'window_days' => gw_mrp_planning_horizon_window_days(),
        );
    }
}
