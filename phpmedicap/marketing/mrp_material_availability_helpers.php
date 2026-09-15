<?php
/**
 * Shared MRP material availability detail (read-only).
 * Used by Planning Shortages popup and reusable across MRP tabs.
 * Does NOT change shortage calculation rules — display / drill-down only.
 */

if (!function_exists('gw_mrp_mat_avail_num')) {
    function gw_mrp_mat_avail_num($v) {
        return round(max(0, floatval($v)), 4);
    }
}

if (!function_exists('gw_mrp_mat_avail_open_po_and_transit')) {
    /**
     * Open PO = PO lines not received and not yet at security gate.
     * Transit PO = security received but store/GRN not complete.
     */
    function gw_mrp_mat_avail_open_po_and_transit($conn, $material_code) {
        $out = array('open_po' => 0.0, 'transit_po' => 0.0);
        $code = trim((string)$material_code);
        if ($code === '' || !($conn instanceof mysqli)) {
            return $out;
        }
        $esc = $conn->real_escape_string($code);
        $sql = "SELECT
                    IFNULL(SUM(CASE
                        WHEN LOWER(TRIM(IFNULL(p.is_security_receive,''))) IN ('yes','y','1')
                        THEN CAST(pm.qty AS DECIMAL(15,4)) ELSE 0 END), 0) AS transit_po,
                    IFNULL(SUM(CASE
                        WHEN LOWER(TRIM(IFNULL(p.is_security_receive,''))) NOT IN ('yes','y','1')
                        THEN CAST(pm.qty AS DECIMAL(15,4)) ELSE 0 END), 0) AS open_po
                FROM po_material pm
                LEFT JOIN purchaseorder p ON p.po_no = pm.po_no
                WHERE pm.material_code = '$esc'
                  AND (pm.isreceive = 'No' OR pm.isreceive IS NULL OR TRIM(IFNULL(pm.isreceive,'')) = '')
                  AND (
                        p.po_no IS NULL
                        OR LOWER(TRIM(IFNULL(p.status,''))) NOT IN ('cancel','cancelled','rejected','reject')
                      )";
        $res = @$conn->query($sql);
        if ($res && ($row = $res->fetch_assoc())) {
            $out['open_po'] = gw_mrp_mat_avail_num($row['open_po'] ?? 0);
            $out['transit_po'] = gw_mrp_mat_avail_num($row['transit_po'] ?? 0);
        }
        return $out;
    }
}

if (!function_exists('gw_mrp_mat_avail_undertest')) {
    function gw_mrp_mat_avail_undertest($conn, $material_code) {
        $code = trim((string)$material_code);
        if ($code === '' || !($conn instanceof mysqli)) {
            return 0.0;
        }
        $esc = $conn->real_escape_string($code);
        $qty = 0.0;

        $res = @$conn->query(
            "SELECT IFNULL(SUM(CAST(qty AS DECIMAL(15,4))), 0) AS qty
             FROM stock_book
             WHERE material_code = '$esc' AND status = 'Under Test'"
        );
        if ($res && ($row = $res->fetch_assoc())) {
            $qty += floatval($row['qty']);
        }

        static $hasUndertestQty = null;
        if ($hasUndertestQty === null) {
            $colCheck = @$conn->query("SHOW COLUMNS FROM stock_book LIKE 'undertest_qty'");
            $hasUndertestQty = ($colCheck && $colCheck->num_rows > 0);
        }
        if ($hasUndertestQty) {
            $res2 = @$conn->query(
                "SELECT IFNULL(SUM(CAST(undertest_qty AS DECIMAL(15,4))), 0) AS qty
                 FROM stock_book
                 WHERE material_code = '$esc' AND status = 'Approved'"
            );
            if ($res2 && ($row2 = $res2->fetch_assoc())) {
                $qty += floatval($row2['qty']);
            }
        }
        return gw_mrp_mat_avail_num($qty);
    }
}

if (!function_exists('gw_mrp_mat_avail_commitments')) {
    /** Active WO / FO commitments for a material code (drill-down). */
    function gw_mrp_mat_avail_commitments($conn, $material_code, $plant_id = '') {
        $rows = array();
        $code = trim((string)$material_code);
        if ($code === '' || !($conn instanceof mysqli)) {
            return $rows;
        }
        $esc = $conn->real_escape_string($code);
        $plantClause = '';
        $plant = trim((string)$plant_id);
        if ($plant !== '') {
            $plantClause = " AND TRIM(IFNULL(wom.plant_id,'')) = '" . $conn->real_escape_string($plant) . "'";
        }

        $sql = "SELECT
                    wd.id AS wo_deduction_id,
                    wd.workorder_no,
                    wd.material_code,
                    wd.status AS deduction_status,
                    wd.indent_status,
                    CAST(COALESCE(wd.deducted_from_RM, 0) AS DECIMAL(15,4)) AS deducted_from_rm,
                    CAST(COALESCE(wd.deducted_from_MC, 0) AS DECIMAL(15,4)) AS deducted_from_mc,
                    CAST(COALESCE(wd.shortage, 0) AS DECIMAL(15,4)) AS shortage_qty,
                    wom.order_no,
                    wom.product_code,
                    wom.status AS wo_status,
                    wom.batch_size,
                    COALESCE(
                      (SELECT p.product_name FROM product p WHERE p.product_code = wom.product_code LIMIT 1),
                      ''
                    ) AS product_name,
                    COALESCE(
                      (SELECT pe.client_code FROM po_entry pe WHERE pe.order_no = wom.order_no ORDER BY pe.id DESC LIMIT 1),
                      ''
                    ) AS client_code,
                    COALESCE(
                      (SELECT c.LglNm FROM client c
                        INNER JOIN po_entry pe2 ON pe2.client_code = c.client_code
                        WHERE pe2.order_no = wom.order_no
                        ORDER BY pe2.id DESC LIMIT 1),
                      ''
                    ) AS client_name
                FROM WO_deductions wd
                LEFT JOIN Work_order_materials wom ON wom.workorder_no = wd.workorder_no
                WHERE wd.material_code = '$esc'
                  AND IFNULL(wd.status,'') NOT IN ('Cancel', 'Cancelled')
                  AND IFNULL(wom.status,'') NOT IN ('Cancel', 'Cancelled', 'Hold')
                  $plantClause
                ORDER BY wd.id DESC
                LIMIT 200";
        $res = @$conn->query($sql);
        if (!$res) {
            return $rows;
        }
        while ($row = $res->fetch_assoc()) {
            $dedRm = floatval($row['deducted_from_rm'] ?? 0);
            $dedMc = floatval($row['deducted_from_mc'] ?? 0);
            $shortage = floatval($row['shortage_qty'] ?? 0);
            $req = $dedRm + $dedMc + $shortage;
            $rows[] = array(
                'wo_deduction_id' => (int)($row['wo_deduction_id'] ?? 0),
                'workorder_no' => (string)($row['workorder_no'] ?? ''),
                'order_no' => (string)($row['order_no'] ?? ''),
                'factory_order_no' => (string)($row['order_no'] ?? ''),
                'product_code' => (string)($row['product_code'] ?? ''),
                'product_name' => (string)($row['product_name'] ?? ''),
                'client_code' => (string)($row['client_code'] ?? ''),
                'client_name' => (string)($row['client_name'] ?? ''),
                'required_qty' => gw_mrp_mat_avail_num($req),
                'shortage_qty' => gw_mrp_mat_avail_num($shortage),
                'reserved_qty' => gw_mrp_mat_avail_num($dedRm + $dedMc),
                'deduction_status' => (string)($row['deduction_status'] ?? ''),
                'indent_status' => (string)($row['indent_status'] ?? ''),
                'wo_status' => (string)($row['wo_status'] ?? ''),
                'batch_size' => (string)($row['batch_size'] ?? ''),
            );
        }
        return $rows;
    }
}

if (!function_exists('gw_get_mrp_material_availability_detail')) {
    /**
     * Full material availability payload for MRP popup.
     * Physical vs expected quantities are separated (open PO / transit / indent
     * are NOT treated as free store stock).
     */
    function gw_get_mrp_material_availability_detail($conn, $material_code, $opts = array()) {
        $code = trim((string)$material_code);
        $plantId = trim((string)($opts['plant_id'] ?? ''));
        $requiredQty = isset($opts['required_qty']) ? floatval($opts['required_qty']) : null;

        $empty = array(
            'status' => 'success',
            'material_code' => $code,
            'material_name' => '',
            'material_type' => '',
            'mother_code' => '',
            'uom' => '',
            'total_stock_qty' => 0,
            'available_store_qty' => 0,
            'reserved_qty' => 0,
            'under_test_qty' => 0,
            'open_po_qty' => 0,
            'transit_po_qty' => 0,
            'open_indent_qty' => 0,
            'expected_receipt_qty' => 0,
            'effective_planning_qty' => 0,
            'required_qty' => $requiredQty,
            'net_shortage_qty' => null,
            'commitments' => array(),
            'calculation_basis' => array(),
        );

        if ($code === '' || !($conn instanceof mysqli)) {
            $empty['status'] = 'error';
            $empty['message'] = 'material_code is required';
            return $empty;
        }

        $display = array();
        if (function_exists('gw_prefetch_material_display_map')) {
            $map = gw_prefetch_material_display_map($conn, array($code));
            $display = $map[$code] ?? array();
        }
        if (empty($display)) {
            $esc = $conn->real_escape_string($code);
            $res = @$conn->query(
                "SELECT material_code, material_name, material_type, material_subtype, unit, mother_code
                 FROM material WHERE material_code = '$esc' LIMIT 1"
            );
            if ($res && ($row = $res->fetch_assoc())) {
                $display = $row;
            } else {
                $res2 = @$conn->query(
                    "SELECT material_code, material_name, material_type, material_subtype, unit, '' AS mother_code
                     FROM others_material WHERE material_code = '$esc' LIMIT 1"
                );
                if ($res2 && ($row2 = $res2->fetch_assoc())) {
                    $display = $row2;
                }
            }
        }

        $stock = function_exists('gw_get_shortages_stock_fields')
            ? gw_get_shortages_stock_fields($conn, $code, '')
            : array();

        $poSplit = gw_mrp_mat_avail_open_po_and_transit($conn, $code);
        // Prefer shortage-helper openPO when present (cancelled-plan linked open PO),
        // otherwise use general open PO qty.
        $openPo = isset($stock['openPO']) ? floatval($stock['openPO']) : 0.0;
        if ($openPo <= 0) {
            $openPo = floatval($poSplit['open_po']);
        }
        $transitPo = floatval($poSplit['transit_po']);
        $underTest = gw_mrp_mat_avail_undertest($conn, $code);
        $availableStore = floatval($stock['available_Stock_qty'] ?? $stock['available_qty'] ?? 0);
        $reserved = floatval($stock['booked_qty'] ?? 0);
        $totalStock = floatval($stock['total_stock_qty'] ?? $availableStore);
        $openIndent = floatval($stock['openIndent'] ?? 0);
        $expected = $openPo + $transitPo + $openIndent;
        // Planning effective = physical free + expected supply + released booked add-backs
        // (same spirit as shortages effective_available_rm, without treating UT as free stock).
        $effective = floatval($stock['effective_available_rm'] ?? 0);
        if ($effective <= 0) {
            $effective = $availableStore + $openPo + $openIndent + floatval($stock['releasedBookedQty'] ?? 0);
        }

        $commitments = gw_mrp_mat_avail_commitments($conn, $code, $plantId);
        $requiredFromCommitments = 0.0;
        foreach ($commitments as $c) {
            $requiredFromCommitments += floatval($c['required_qty'] ?? 0);
        }
        if ($requiredQty === null || $requiredQty < 0) {
            $requiredQty = $requiredFromCommitments > 0 ? $requiredFromCommitments : null;
        }

        $netShortage = null;
        if ($requiredQty !== null) {
            // Net shortage vs physical store only (expected qty shown separately).
            $netShortage = gw_mrp_mat_avail_num(floatval($requiredQty) - $availableStore);
        }

        return array(
            'status' => 'success',
            'material_code' => $code,
            'material_name' => (string)($display['material_name'] ?? ''),
            'material_type' => (string)($display['material_type'] ?? ($display['material_subtype'] ?? '')),
            'mother_code' => (string)($display['mother_code'] ?? ''),
            'uom' => (string)($display['unit'] ?? ''),
            'total_stock_qty' => gw_mrp_mat_avail_num($totalStock),
            'available_store_qty' => gw_mrp_mat_avail_num($availableStore),
            'reserved_qty' => gw_mrp_mat_avail_num($reserved),
            'under_test_qty' => gw_mrp_mat_avail_num($underTest),
            'open_po_qty' => gw_mrp_mat_avail_num($openPo),
            'transit_po_qty' => gw_mrp_mat_avail_num($transitPo),
            'open_indent_qty' => gw_mrp_mat_avail_num($openIndent),
            'expected_receipt_qty' => gw_mrp_mat_avail_num($expected),
            'effective_planning_qty' => gw_mrp_mat_avail_num($effective),
            'released_booked_qty' => gw_mrp_mat_avail_num($stock['releasedBookedQty'] ?? 0),
            'required_qty' => $requiredQty === null ? null : gw_mrp_mat_avail_num($requiredQty),
            'net_shortage_qty' => $netShortage,
            'commitments' => $commitments,
            'calculation_basis' => array(
                'available_store' => 'vw_total_available_stock.available_qty (physical free after issue/book)',
                'reserved' => 'vw_total_available_stock.booked_qty / WO bookings',
                'under_test' => 'stock_book status Under Test (+ undertest_qty on Approved if column exists)',
                'open_po' => 'po_material not received and not security-received (or cancelled-plan linked open PO)',
                'transit_po' => 'po_material not store-received but security-received',
                'open_indent' => 'approved/pending planning indents not yet converted to PO',
                'expected_receipt' => 'open_po + transit_po + open_indent (NOT physical stock)',
                'net_shortage' => 'max(0, required_qty - available_store_qty); expected supply shown separately',
            ),
        );
    }
}
