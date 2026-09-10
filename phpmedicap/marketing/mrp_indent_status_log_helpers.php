<?php

if (!function_exists('gw_indent_status_is_done')) {
    function gw_indent_status_is_done($value)
    {
        $v = strtolower(trim((string)$value));
        return in_array($v, ['approve', 'approved', 'yes', 'done', 'complete', 'completed', 'tested'], true);
    }
}

if (!function_exists('gw_indent_status_approval_pending')) {
    function gw_indent_status_approval_pending($irStatus)
    {
        $s = strtolower(trim((string)$irStatus));
        if ($s === '') {
            return false;
        }
        if (in_array($s, ['approve', 'approved'], true)) {
            return false;
        }
        if (in_array($s, ['rejected', 'reject', 'cancelled', 'cancel'], true)) {
            return false;
        }
        return true;
    }
}

if (!function_exists('gw_batch_fetch_indent_store_pipeline')) {
    function gw_batch_fetch_indent_store_pipeline($conn, array $pairs)
    {
        $map = [];
        if (count($pairs) === 0) {
            return $map;
        }

        $clauses = [];
        foreach ($pairs as $pair) {
            $po = $conn->real_escape_string($pair['po_no'] ?? '');
            $mat = $conn->real_escape_string($pair['material_code'] ?? '');
            if ($po === '' || $mat === '') {
                continue;
            }
            $clauses[] = "(c.po_no = '$po' AND cm.material_code = '$mat')";
        }
        if (count($clauses) === 0) {
            return $map;
        }

        $whereOr = implode(' OR ', $clauses);
        $sql = "SELECT c.po_no, cm.material_code,
            MAX(CASE WHEN LOWER(COALESCE(cm.receiving, '')) IN ('approve','approved','yes','done','complete','completed')
                OR LOWER(COALESCE(cm.weighing, '')) IN ('approve','approved','yes','done')
                OR TRIM(COALESCE(cm.grn_no, '')) <> '' THEN 1 ELSE 0 END) AS po_received,
            MAX(CASE WHEN TRIM(COALESCE(cm.grn_no, '')) <> ''
                OR LOWER(COALESCE(cm.grn, '')) IN ('approve','approved','yes','done') THEN 1 ELSE 0 END) AS grn_raised,
            MAX(CASE WHEN LOWER(COALESCE(sb.status, '')) = 'approved'
                OR gw_indent_status_is_done(t.status) THEN 1 ELSE 0 END) AS qc_released,
            MAX(TRIM(COALESCE(cm.grn_no, ''))) AS grn_no,
            MAX(COALESCE(sb.status, '')) AS stock_status,
            MAX(COALESCE(t.status, '')) AS testing_status
            FROM challan c
            INNER JOIN challan_materials cm ON cm.challan_no = c.challan_no
            LEFT JOIN sampling s ON s.grn_no = cm.grn_no AND s.material_code = cm.material_code
            LEFT JOIN testing t ON t.sampling_no = s.sampling_no AND t.material_code = s.material_code
            LEFT JOIN stock_book sb ON sb.grn_no = cm.grn_no AND sb.material_code = cm.material_code
            WHERE $whereOr
            GROUP BY c.po_no, cm.material_code";

        // MariaDB may not allow function in SELECT — inline testing check
        $sql = str_replace('gw_indent_status_is_done(t.status)', "LOWER(COALESCE(t.status,'')) IN ('approve','approved','yes','done','tested')", $sql);

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $key = ($row['po_no'] ?? '') . '|' . ($row['material_code'] ?? '');
                $map[$key] = $row;
            }
        }
        return $map;
    }
}

if (!function_exists('gw_build_indent_pipeline_row')) {
    function gw_build_indent_pipeline_row($row, $storeMap = [])
    {
        $irStatus = strtolower(trim($row['indent_raw_status'] ?? ''));
        $confStatus = strtoupper(trim($row['confirmation_status'] ?? ''));
        $poIndend = strtolower(trim($row['po_indend_status'] ?? 'pending'));
        $poStatus = strtoupper(str_replace(' ', '_', trim($row['po_status'] ?? '')));
        $poNo = trim($row['po_no'] ?? '');
        $matCode = trim($row['material_code'] ?? '');

        $storeKey = $poNo . '|' . $matCode;
        $store = $storeMap[$storeKey] ?? [];
        $poReceived = !empty($store['po_received']);
        $grnRaised = !empty($store['grn_raised']);
        $qcReleased = !empty($store['qc_released']);

        $underApproval = gw_indent_status_approval_pending($row['indent_raw_status'] ?? '');
        $underPurchase = ($confStatus === 'PENDING' || $confStatus === 'ON_HOLD')
            || ($confStatus === 'SENT_FOR_PURCHASE' && $poIndend === 'pending' && $poNo === '');
        $poRaised = ($poNo !== '') || ($poIndend === 'approve');
        $poCancelled = in_array($poStatus, ['CANCELLED', 'CANCEL', 'REJECTED'], true)
            || in_array($irStatus, ['rejected', 'reject'], true)
            || $confStatus === 'CANCELLED';

        if ($poCancelled) {
            $currentStatus = 'PO Cancelled';
        } elseif ($qcReleased) {
            $currentStatus = 'QC Released';
        } elseif ($grnRaised) {
            $currentStatus = 'GRN Raised';
        } elseif ($poReceived) {
            $currentStatus = 'PO Received';
        } elseif ($poRaised) {
            $currentStatus = 'PO Raised';
        } elseif ($underPurchase) {
            $currentStatus = 'Under Purchase Order';
        } elseif ($underApproval) {
            $currentStatus = 'Under Approval';
        } elseif ($confStatus === 'SENT_FOR_PURCHASE') {
            $currentStatus = 'Sent For Purchase';
        } else {
            $currentStatus = 'Indent Raised';
        }

        return [
            'forecast_no' => $row['forecast_no'] ?? $row['order_no'] ?? '',
            'order_no' => $row['order_no'] ?? '',
            'workorder_no' => $row['workorder_no'] ?? '',
            'product_code' => $row['product_code'] ?? '',
            'product_name' => $row['product_name'] ?? '',
            'material_code' => $matCode,
            'material_name' => $row['material_name'] ?? '',
            'indent_qty' => $row['indent_qty'] ?? null,
            'qty_unit' => $row['qty_unit'] ?? '',
            'indent_raised_by' => $row['indent_raised_by'] ?? '',
            'indent_raised_by_name' => $row['indent_raised_by_name'] ?? '',
            'indent_no' => $row['indent_no'] ?? $row['request_no'] ?? '',
            'indent_id' => $row['indent_id'] ?? null,
            'po_no' => $poNo,
            'current_status' => $currentStatus,
            'stage_under_approval' => $underApproval,
            'stage_under_purchase' => $underPurchase && !$poRaised,
            'stage_po_raised' => $poRaised && !$poCancelled,
            'stage_po_cancelled' => $poCancelled,
            'stage_po_received' => $poReceived && !$poCancelled,
            'stage_grn_raised' => $grnRaised && !$poCancelled,
            'stage_qc_released' => $qcReleased && !$poCancelled,
            'grn_no' => $store['grn_no'] ?? '',
            'indent_raised_date' => $row['indent_raised_date'] ?? $row['event_datetime'] ?? '',
            'confirmation_status' => $confStatus,
            'indent_raw_status' => $row['indent_raw_status'] ?? '',
        ];
    }
}

if (!function_exists('gw_get_planning_indent_status_log')) {
    function gw_get_planning_indent_status_log($conn, $filters = [])
    {
        if (function_exists('gw_ensure_mrp_shortages_indent_log_table')) {
            gw_ensure_mrp_shortages_indent_log_table($conn);
        }
        if (function_exists('gw_ensure_mrp_indents_confirmation_tables')) {
            gw_ensure_mrp_indents_confirmation_tables($conn);
        }

        $search = mysqli_real_escape_string($conn, $filters['search'] ?? '');
        $plantId = mysqli_real_escape_string($conn, $filters['plant_id'] ?? '');
        $page = max(0, (int)($filters['page'] ?? 0));
        $pageSize = min(2000, max(10, (int)($filters['pageSize'] ?? 100)));
        $offset = $page * $pageSize;

        $where = 'WHERE 1=1';
        if ($plantId !== '') {
            $where .= " AND (l.plant_id = '$plantId' OR l.plant_id IS NULL OR TRIM(l.plant_id) = '')";
        }
        if ($search !== '') {
            $where .= " AND (
                l.material_code LIKE '%$search%' OR l.material_name LIKE '%$search%' OR
                l.workorder_no LIKE '%$search%' OR l.order_no LIKE '%$search%' OR
                l.forecast_no LIKE '%$search%' OR l.indent_no LIKE '%$search%' OR
                l.product_code LIKE '%$search%' OR l.product_name LIKE '%$search%' OR
                c.indent_no LIKE '%$search%' OR c.request_no LIKE '%$search%'
            )";
        }

        $countSql = "SELECT COUNT(DISTINCT l.id) AS cnt
            FROM mrp_shortages_indent_log l
            LEFT JOIN mrp_indents_confirmation c ON (
                (l.indent_id > 0 AND c.indent_id = l.indent_id)
                OR (l.id > 0 AND c.shortages_log_id = l.id)
            )
            $where";
        $total = 0;
        $countRes = $conn->query($countSql);
        if ($countRes && $countRow = $countRes->fetch_assoc()) {
            $total = (int)($countRow['cnt'] ?? 0);
        }

        $sql = "SELECT l.id AS log_id,
            COALESCE(NULLIF(TRIM(l.forecast_no), ''), NULLIF(TRIM(l.order_no), ''), NULLIF(TRIM(c.forecast_no), ''), NULLIF(TRIM(c.order_no), '')) AS forecast_no,
            COALESCE(NULLIF(TRIM(l.order_no), ''), NULLIF(TRIM(c.order_no), '')) AS order_no,
            COALESCE(NULLIF(TRIM(l.workorder_no), ''), NULLIF(TRIM(c.workorder_no), '')) AS workorder_no,
            COALESCE(NULLIF(TRIM(l.product_code), ''), NULLIF(TRIM(c.product_code), '')) AS product_code,
            COALESCE(NULLIF(TRIM(l.product_name), ''), NULLIF(TRIM(c.product_name), '')) AS product_name,
            COALESCE(NULLIF(TRIM(l.material_code), ''), NULLIF(TRIM(c.material_code), '')) AS material_code,
            COALESCE(NULLIF(TRIM(l.material_name), ''), NULLIF(TRIM(c.material_name), '')) AS material_name,
            COALESCE(l.raised_indent_qty, c.raised_indent_qty, l.required_qty, c.required_qty) AS indent_qty,
            COALESCE(NULLIF(TRIM(l.qty_unit), ''), NULLIF(TRIM(c.qty_unit), ''), NULLIF(TRIM(ir.unit), '')) AS qty_unit,
            COALESCE(NULLIF(TRIM(l.raised_by_name), ''), NULLIF(TRIM(c.indent_raised_by_name), ''), NULLIF(TRIM(l.raised_by), ''), NULLIF(TRIM(c.indent_raised_by), ''), NULLIF(TRIM(ir.entry_by), '')) AS indent_raised_by_name,
            COALESCE(NULLIF(TRIM(l.raised_by), ''), NULLIF(TRIM(c.indent_raised_by), ''), NULLIF(TRIM(ir.entry_by), '')) AS indent_raised_by,
            COALESCE(NULLIF(TRIM(l.indent_no), ''), NULLIF(TRIM(c.indent_no), ''), NULLIF(TRIM(ir.no), ''), NULLIF(TRIM(c.request_no), ''), NULLIF(TRIM(ir.request_no), '')) AS indent_no,
            COALESCE(NULLIF(TRIM(c.request_no), ''), NULLIF(TRIM(ir.request_no), '')) AS request_no,
            COALESCE(l.indent_id, c.indent_id) AS indent_id,
            COALESCE(l.event_datetime, c.indent_raised_date) AS indent_raised_date,
            l.event_datetime,
            COALESCE(c.confirmation_status, '') AS confirmation_status,
            COALESCE(ir.status, '') AS indent_raw_status,
            COALESCE(ir.po_indend, 'pending') AS po_indend_status,
            rq.po_no,
            COALESCE(po.status, '') AS po_status
            FROM mrp_shortages_indent_log l
            LEFT JOIN mrp_indents_confirmation c ON (
                (l.indent_id > 0 AND CAST(c.indent_id AS CHAR) = CAST(l.indent_id AS CHAR))
                OR (l.id > 0 AND c.shortages_log_id = l.id)
            )
            LEFT JOIN indend_raw ir ON CAST(ir.id AS CHAR) = CAST(COALESCE(l.indent_id, c.indent_id) AS CHAR)
            LEFT JOIN mrp_raised_indnd_qty rq ON CAST(rq.indend_id AS CHAR) = CAST(COALESCE(l.indent_id, c.indent_id) AS CHAR)
                AND TRIM(COALESCE(rq.po_no, '')) <> ''
            LEFT JOIN purchaseorder po ON po.po_no = rq.po_no
            $where
            ORDER BY COALESCE(l.event_datetime, c.indent_raised_date) DESC, l.id DESC
            LIMIT $offset, $pageSize";

        $rawRows = [];
        $storePairs = [];
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rawRows[] = $row;
                $poNo = trim($row['po_no'] ?? '');
                $mat = trim($row['material_code'] ?? '');
                if ($poNo !== '' && $mat !== '') {
                    $storePairs[$poNo . '|' . $mat] = ['po_no' => $poNo, 'material_code' => $mat];
                }
            }
        }

        $storeMap = gw_batch_fetch_indent_store_pipeline($conn, array_values($storePairs));
        $rows = [];
        foreach ($rawRows as $row) {
            $rows[] = gw_build_indent_pipeline_row($row, $storeMap);
        }

        return [
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'rows' => $rows,
        ];
    }
}
