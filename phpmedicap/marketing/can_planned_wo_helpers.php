<?php

if (!function_exists('gw_json_response')) {
    function gw_json_response($data, $httpCode = 200)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $json = json_encode($data, $flags);
        if ($json === false) {
            $json = json_encode([
                'status' => 'error',
                'message' => 'JSON encode failed: ' . json_last_error_msg(),
            ]);
        }
        echo $json;
        exit;
    }
}

if (!function_exists('gw_material_name_map')) {
    function gw_material_name_map($conn, array $materialCodes)
    {
        $map = [];
        $codes = array_values(array_unique(array_filter($materialCodes, function ($c) {
            return $c !== null && trim((string)$c) !== '';
        })));
        if (count($codes) === 0) {
            return $map;
        }

        $inList = implode(',', array_map(function ($code) use ($conn) {
            return "'" . $conn->real_escape_string($code) . "'";
        }, $codes));

        $queries = [
            "SELECT material_code, material_name FROM material WHERE material_code IN ($inList)",
            "SELECT material_code, material_name FROM others_material WHERE material_code IN ($inList)",
            "SELECT bulkCode AS material_code, bulkName AS material_name FROM bulkMaster WHERE bulkCode IN ($inList)",
        ];

        foreach ($queries as $sql) {
            $result = $conn->query($sql);
            if (!$result) {
                continue;
            }
            while ($row = $result->fetch_assoc()) {
                $code = $row['material_code'] ?? '';
                if ($code !== '' && !isset($map[$code]) && !empty($row['material_name'])) {
                    $map[$code] = $row['material_name'];
                }
            }
        }

        return $map;
    }
}

if (!function_exists('gw_fetch_wo_deductions_grouped')) {
    function gw_fetch_wo_deductions_grouped($conn, array $woIds, $plant_id = '', $shortageOnly = false)
    {
        $grouped = [];
        if (count($woIds) === 0) {
            return $grouped;
        }

        $idList = implode(',', array_map('intval', $woIds));
        $dedPlantFilter = '';
        if ($plant_id !== '') {
            $plantEsc = $conn->real_escape_string($plant_id);
            $dedPlantFilter = " AND (wd.plant_id = '$plantEsc' OR wd.plant_id IS NULL OR TRIM(wd.plant_id) = '')";
        }
        $shortageFilter = $shortageOnly
            ? " AND CAST(COALESCE(wd.shortage, '0') AS DECIMAL(15,4)) > 0"
            : '';

        $dedSql = "SELECT wd.*
            FROM WO_deductions wd
            WHERE wd.work_order_id IN ($idList)
            $shortageFilter
            $dedPlantFilter
            ORDER BY wd.work_order_id ASC, wd.id ASC";

        $dedResult = $conn->query($dedSql);
        if (!$dedResult) {
            return $grouped;
        }

        $materialCodes = [];
        $rows = [];
        while ($ded = $dedResult->fetch_assoc()) {
            $rows[] = $ded;
            if (!empty($ded['material_code'])) {
                $materialCodes[] = $ded['material_code'];
            }
        }

        $nameMap = gw_material_name_map($conn, $materialCodes);

        foreach ($rows as $ded) {
            $woId = intval($ded['work_order_id'] ?? 0);
            if ($woId <= 0) {
                continue;
            }
            $code = $ded['material_code'] ?? '';
            $grouped[$woId][] = [
                'material_code' => $code,
                'material_name' => $nameMap[$code] ?? $code,
                'mat_type' => $ded['mat_type'] ?? '',
                'requiredQty' => floatval($ded['plan_qty'] ?? 0),
                'plan_qty' => floatval($ded['plan_qty'] ?? 0),
                'deducted_from_RM' => floatval($ded['deducted_from_RM'] ?? 0),
                'deducted_from_MC' => floatval($ded['deducted_from_MC'] ?? 0),
                'shortage' => floatval($ded['shortage'] ?? 0),
                'unit' => $ded['unit'] ?? '',
            ];
        }

        return $grouped;
    }
}

if (!function_exists('gw_map_batch_mat_type')) {
    function gw_map_batch_mat_type($materialType)
    {
        $t = strtolower(trim((string)$materialType));
        if ($t === 'raw material' || $t === 'rm') {
            return 'RM';
        }
        if ($t === 'packing material' || $t === 'pm') {
            return 'PM';
        }
        if (strpos($t, 'primary') !== false) {
            return 'PRIMARY_PM';
        }
        if (strpos($t, 'consum') !== false) {
            return 'CONSUMABLE';
        }
        if (strpos($t, 'secondary') !== false || strpos($t, 'packaging') !== false) {
            return 'SECONDARY PACKAGING';
        }
        return strtoupper(trim((string)$materialType));
    }
}

if (!function_exists('gw_resolve_bfr_for_work_order')) {
    function gw_resolve_bfr_for_work_order($conn, $productCode, $batchSize, $planUnit)
    {
        $productCode = trim((string)$productCode);
        $batchSize = trim((string)$batchSize);
        $planUnit = trim((string)$planUnit);
        if ($productCode === '' || $batchSize === '' || floatval($batchSize) <= 0) {
            return '';
        }

        $pcEsc = $conn->real_escape_string($productCode);
        $bsEsc = $conn->real_escape_string($batchSize);
        $unitEsc = $conn->real_escape_string($planUnit);

        $queries = [];
        if ($unitEsc !== '') {
            $queries[] = "SELECT bfr_no FROM batch_formula_info
                WHERE product_code = '$pcEsc'
                  AND batch_formula_weight = '$bsEsc'
                  AND LOWER(TRIM(rm_batch_size_unit)) = LOWER('$unitEsc')
                ORDER BY bfr_no DESC LIMIT 1";
        }
        $queries[] = "SELECT bfr_no FROM batch_formula_info
            WHERE product_code = '$pcEsc'
              AND batch_formula_weight = '$bsEsc'
            ORDER BY bfr_no DESC LIMIT 1";
        $queries[] = "SELECT b.bfr_no FROM unitformula u
            INNER JOIN batch_formula_info b ON b.mfr_no = u.mfr_no
            WHERE u.product_code = '$pcEsc'
              AND b.batch_formula_weight = '$bsEsc'
            ORDER BY u.id DESC, b.bfr_no DESC LIMIT 1";
        $queries[] = "SELECT bfr_no FROM batch_formula_info
            WHERE LOWER(TRIM(product_code)) = LOWER('$pcEsc')
            ORDER BY CAST(batch_formula_weight AS DECIMAL(15,4)) DESC, bfr_no DESC LIMIT 1";

        foreach ($queries as $sql) {
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $bfr = trim((string)($res->fetch_assoc()['bfr_no'] ?? ''));
                if ($bfr !== '') {
                    return $bfr;
                }
            }
        }

        return '';
    }
}

if (!function_exists('gw_ensure_can_plan_deductions_for_wo')) {
    /**
     * Create WO_deductions from batch formula when missing (backfill + send flow).
     */
    function gw_ensure_can_plan_deductions_for_wo($conn, array $woRow, $plant_id = '', $emp_id = '')
    {
        $woId = intval($woRow['id'] ?? 0);
        if ($woId <= 0) {
            return 0;
        }

        $check = $conn->query("SELECT COUNT(*) AS c FROM WO_deductions WHERE work_order_id = '$woId'");
        if ($check) {
            $existing = intval(($check->fetch_assoc()['c'] ?? 0));
            if ($existing > 0) {
                return $existing;
            }
        }

        $productCode = trim((string)($woRow['product_code'] ?? ''));
        $batchSize = trim((string)($woRow['batch_size'] ?? ''));
        $planUnit = trim((string)($woRow['planUnit'] ?? ''));
        $workorderNo = trim((string)($woRow['workorder_no'] ?? ''));
        $woStatus = strtoupper(trim((string)($woRow['status'] ?? 'CAN_PLAN')));
        if (!in_array($woStatus, ['CAN_PLAN', 'CAN_PLAN_MC_QTY_USED'], true)) {
            $woStatus = 'CAN_PLAN';
        }

        $bfrNo = gw_resolve_bfr_for_work_order($conn, $productCode, $batchSize, $planUnit);
        if ($bfrNo === '') {
            return 0;
        }

        $bfrEsc = $conn->real_escape_string($bfrNo);
        $matSql = "SELECT a.material_code, a.material_type, a.batch_qty, a.qty, a.unit
                   FROM batch_materials a
                   WHERE a.bfr_no = '$bfrEsc'
                     AND TRIM(IFNULL(a.material_code,'')) <> ''";
        $matRes = $conn->query($matSql);
        $plantEsc = $conn->real_escape_string((string)($plant_id !== '' ? $plant_id : ($woRow['plant_id'] ?? '')));
        $empEsc = $conn->real_escape_string((string)$emp_id);
        $entryDate = date('Y-m-d H:i:s');
        $woNoEsc = $conn->real_escape_string($workorderNo);
        $statusEsc = $conn->real_escape_string($woStatus);
        $inserted = 0;

        if (!$matRes || $matRes->num_rows === 0) {
            if (function_exists('gw_gwo_collect_bfr_deduction_lines')) {
                foreach (gw_gwo_collect_bfr_deduction_lines($conn, $bfrNo, $woRow) as $line) {
                    $code = trim((string)($line['material_code'] ?? ''));
                    $planQty = floatval($line['plan_qty'] ?? 0);
                    if ($code === '' || $planQty <= 0) {
                        continue;
                    }
                    $unit = gw_convert_wo_unit($line['unit'] ?? $planUnit);
                    $matType = gw_map_batch_mat_type($line['mat_type'] ?? 'RM');
                    $codeEsc = $conn->real_escape_string($code);
                    $unitEsc = $conn->real_escape_string($unit);
                    $matTypeEsc = $conn->real_escape_string($matType);
                    $sql = "INSERT INTO WO_deductions
                        (plant_id, workorder_no, work_order_id, mat_type, status, plan_qty, unit,
                         entry_by, entry_date, material_code, deducted_from_RM, deducted_from_MC, shortage, qty_status)
                        VALUES(
                            '$plantEsc', '$woNoEsc', '$woId', '$matTypeEsc', '$statusEsc',
                            '$planQty', '$unitEsc', '$empEsc', '$entryDate', '$codeEsc',
                            '$planQty', '0', '0', 'Pending'
                        )";
                    if ($conn->query($sql)) {
                        $inserted++;
                    }
                }
            }
            return $inserted;
        }

        while ($mat = $matRes->fetch_assoc()) {
            $code = trim((string)($mat['material_code'] ?? ''));
            if ($code === '' || $code === '-' || strtoupper($code) === 'N/A') {
                continue;
            }

            $planQty = floatval($mat['batch_qty'] ?? 0);
            if ($planQty <= 0) {
                $planQty = floatval($mat['qty'] ?? 0);
            }
            if ($planQty <= 0) {
                continue;
            }

            $unit = gw_convert_wo_unit($mat['unit'] ?? $planUnit);
            $matType = gw_map_batch_mat_type($mat['material_type'] ?? '');
            $codeEsc = $conn->real_escape_string($code);
            $unitEsc = $conn->real_escape_string($unit);
            $matTypeEsc = $conn->real_escape_string($matType);

            $sql = "INSERT INTO WO_deductions
                (plant_id, workorder_no, work_order_id, mat_type, status, plan_qty, unit,
                 entry_by, entry_date, material_code, deducted_from_RM, deducted_from_MC, shortage, qty_status)
                VALUES(
                    '$plantEsc', '$woNoEsc', '$woId', '$matTypeEsc', '$statusEsc',
                    '$planQty', '$unitEsc', '$empEsc', '$entryDate', '$codeEsc',
                    '$planQty', '0', '0', 'Pending'
                )";
            if ($conn->query($sql)) {
                $inserted++;
            }
        }

        return $inserted;
    }
}

if (!function_exists('gw_get_can_planned_wo')) {
    /**
     * Load work orders already marked CAN_PLAN / CAN_PLAN_MC_QTY_USED from Generate WO,
     * with material deductions from WO_deductions (no heavy batch-formula recalculation).
     */
    function gw_get_can_planned_wo($conn, $params = [])
    {
        $output = [];
        $shortageInfo = [];
        $plant_id = !empty($params['plant_id']) ? $conn->real_escape_string($params['plant_id']) : '';

        $colors = [
            '#FFCCCB', '#CCFFCC', '#CCE5FF', '#FFFACD', '#D1C4E9',
            '#FFE0B2', '#F8BBD0', '#B2EBF2', '#E6EE9C', '#FFECB3',
            '#CFD8DC', '#F0F4C3', '#DCEDC8', '#F5F5F5', '#E1BEE7',
            '#BBDEFB', '#FFCDD2', '#D7CCC8', '#FFCC80', '#C8E6C9',
        ];
        $colorMap = [];
        $colorIndex = 0;

        $plantFilter = '';
        if ($plant_id !== '') {
            $plantFilter = " AND (
                a.plant_id = '$plant_id' OR a.plant_id IS NULL OR TRIM(a.plant_id) = ''
                OR EXISTS (
                    SELECT 1 FROM WO_deductions wd0
                    WHERE wd0.work_order_id = a.id
                      AND wd0.plant_id = '$plant_id'
                    LIMIT 1
                )
            )";
        }

        $sql = "SELECT a.id, a.workorder_no, a.order_no, a.batch_size, a.planUnit,
            a.status, a.plant_id, a.doc_no, a.groupcode, a.subClient, a.excess, a.leftover,
            COALESCE(a.product_code, '') AS product_code,
            COALESCE(a.entryOn, a.wo_generated_by_digi_sign_date) AS Wo_Generated_on,
            (SELECT deliveryDate FROM order_materials om
             WHERE om.order_no = a.order_no
               AND (om.product_code = a.product_code OR TRIM(IFNULL(a.product_code, '')) = '')
             ORDER BY om.id DESC LIMIT 1) AS delivery_date,
            (SELECT packingStyle FROM order_materials om
             WHERE om.order_no = a.order_no
               AND (om.product_code = a.product_code OR TRIM(IFNULL(a.product_code, '')) = '')
             ORDER BY om.id DESC LIMIT 1) AS packing_type,
            (SELECT LglNm FROM client cl
             LEFT JOIN po_entry po ON po.client_code = cl.client_code
             WHERE po.order_no = a.order_no LIMIT 1) AS mainGroupName,
            (SELECT product_name FROM product c
             WHERE c.product_code = a.product_code LIMIT 1) AS product_name,
            (SELECT CONCAT(sp.month, '-', sp.year)
             FROM split_planning_qty sp
             WHERE CAST(sp.id AS CHAR) = CAST(a.doc_no AS CHAR)
               AND sp.order_no = a.order_no
             ORDER BY sp.id DESC LIMIT 1) AS planMonth
            FROM Work_order_materials a
            WHERE a.status IN ('CAN_PLAN', 'CAN_PLAN_MC_QTY_USED')
            $plantFilter
            ORDER BY a.workorder_no ASC";

        $result = $conn->query($sql);
        if ($result === false) {
            return [
                'can_plan_work_orders' => [],
                'shortage_info' => [],
                'debug_info' => [
                    'error' => 'Query failed: ' . $conn->error,
                ],
            ];
        }

        $woRows = [];
        $woIds = [];
        while ($row = $result->fetch_assoc()) {
            $woId = intval($row['id'] ?? 0);
            if ($woId <= 0) {
                continue;
            }
            $woRows[$woId] = $row;
            $woIds[] = $woId;
        }

        $deductionsByWo = gw_fetch_wo_deductions_grouped($conn, $woIds, $plant_id, false);
        $emp_id = !empty($params['emp_id']) ? (string)$params['emp_id'] : '';
        $backfilled = 0;

        foreach ($woRows as $woId => $row) {
            if (count($deductionsByWo[$woId] ?? []) === 0) {
                $created = gw_ensure_can_plan_deductions_for_wo($conn, $row, $plant_id, $emp_id);
                if ($created > 0) {
                    $backfilled++;
                }
            }
        }

        if ($backfilled > 0) {
            $deductionsByWo = gw_fetch_wo_deductions_grouped($conn, $woIds, $plant_id, false);
        }

        $skippedNoDeductions = 0;
        foreach ($woRows as $woId => $row) {
            $deductions = $deductionsByWo[$woId] ?? [];
            if (count($deductions) === 0 && gw_work_order_formula_material_count($conn, $row) > 0) {
                $skippedNoDeductions++;
                continue;
            }

            $po = $row['order_no'] ?? '';
            if (!isset($colorMap[$po])) {
                $colorMap[$po] = $colors[$colorIndex % count($colors)];
                $colorIndex++;
            }
            $row['bg_color'] = $colorMap[$po];
            $row['Deductions'] = $deductions;
            $totalShortage = 0;
            foreach ($deductions as $ded) {
                $totalShortage += floatval($ded['shortage'] ?? 0);
            }
            $hasShortage = $totalShortage > 0;
            $row['total_shortage'] = $totalShortage;
            $row['has_shortage'] = $hasShortage;
            $row['can_plan'] = !$hasShortage;
            $row['ready_for_verification'] = !$hasShortage;
            $row['display_plan_status'] = $hasShortage ? 'Not To Be Plan' : 'Can Plan';
            $row['process_plan_source'] = 'generate_wo';
            $row['purchase_pipeline_status'] = $hasShortage ? 'Live stock shortage' : 'Stock in hand';
            $output[] = $row;
        }

        $readyCount = 0;
        $waitCount = 0;
        foreach ($output as $woOut) {
            if (!empty($woOut['can_plan'])) {
                $readyCount++;
            } else {
                $waitCount++;
            }
        }

        // CANNOT_PLAN shortages belong on Generate WO; omit here to keep payload small.
        return [
            'status' => 'success',
            'data' => $output,
            'can_plan_work_orders' => $output,
            'shortage_info' => $shortageInfo,
            'total' => count($output),
            'can_plan_ready_count' => $readyCount,
            'not_to_be_plan_count' => $waitCount,
            'shortage_count' => count($shortageInfo),
            'debug_info' => [
                'total_work_orders_found' => count($woRows),
                'work_orders_processed' => count($woRows),
                'work_orders_can_plan' => count($output),
                'work_orders_with_shortages' => count($shortageInfo),
                'work_orders_skipped' => max(0, count($woRows) - count($output)),
                'skipped_no_deductions' => $skippedNoDeductions,
                'deductions_backfilled' => $backfilled,
                'skip_reasons' => [],
                'source' => 'WO_deductions',
            ],
        ];
    }
}

if (!function_exists('gw_formula_material_map')) {
    /**
     * Map material_code => std qty, batch qty, overages from batch formula for a product/batch size.
     */
    function gw_formula_material_map($conn, $product_code, $batch_size, $planUnit)
    {
        $map = [];
        $product_code = trim((string)$product_code);
        $batch_size = trim((string)$batch_size);
        $planUnit = trim((string)$planUnit);
        if ($product_code === '' || $batch_size === '' || $planUnit === '') {
            return $map;
        }

        $bfrSql = "SELECT bfr_no FROM batch_formula_info
            WHERE product_code = '" . $conn->real_escape_string($product_code) . "'
              AND batch_formula_weight = '" . $conn->real_escape_string($batch_size) . "'
              AND rm_batch_size_unit = '" . $conn->real_escape_string($planUnit) . "'
            ORDER BY bfr_no DESC LIMIT 1";
        $bfrResult = $conn->query($bfrSql);
        if (!$bfrResult || $bfrResult->num_rows === 0) {
            return $map;
        }
        $bfrRow = $bfrResult->fetch_assoc();
        $bfr_no = $bfrRow['bfr_no'] ?? '';
        if ($bfr_no === '') {
            return $map;
        }

        $matSql = "SELECT material_code, qty, batch_qty, overages, unit, unit_name
            FROM batch_materials
            WHERE bfr_no = '" . $conn->real_escape_string($bfr_no) . "'";
        $matResult = $conn->query($matSql);
        if (!$matResult) {
            return $map;
        }
        while ($mat = $matResult->fetch_assoc()) {
            $code = trim((string)($mat['material_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $map[$code] = $mat;
        }

        return $map;
    }
}

if (!function_exists('gw_get_proceed_production_plan')) {
    /**
     * CAN_PLAN work orders enriched for Proceed Production Plan (Micro Planning).
     */
    function gw_get_proceed_production_plan($conn, $params = [])
    {
        $data = gw_get_can_planned_wo($conn, $params);
        $wos = $data['can_plan_work_orders'] ?? [];
        if (!is_array($wos) || count($wos) === 0) {
            $data['debug_info']['source'] = 'proceed_production_plan';
            return $data;
        }

        $docIds = [];
        $woNos = [];
        foreach ($wos as $wo) {
            $docId = intval($wo['doc_no'] ?? 0);
            if ($docId > 0) {
                $docIds[] = $docId;
            }
            if (!empty($wo['workorder_no'])) {
                $woNos[] = $wo['workorder_no'];
            }
        }

        $splitMap = [];
        if (count($docIds) > 0) {
            $idList = implode(',', array_unique($docIds));
            $splitRes = $conn->query(
                "SELECT id, order_no, product_code, oder_qty, balance_qty, Qty
                 FROM split_planning_qty WHERE id IN ($idList)"
            );
            if ($splitRes) {
                while ($row = $splitRes->fetch_assoc()) {
                    $splitMap[intval($row['id'])] = $row;
                }
            }
        }

        $plannedByDoc = [];
        foreach ($wos as $wo) {
            $docId = intval($wo['doc_no'] ?? 0);
            if ($docId > 0) {
                $plannedByDoc[$docId] = ($plannedByDoc[$docId] ?? 0) + floatval($wo['batch_size'] ?? 0);
            }
        }

        $batchNoMap = [];
        if (count($woNos) > 0) {
            $woList = implode(',', array_map(function ($w) use ($conn) {
                return "'" . $conn->real_escape_string($w) . "'";
            }, array_unique($woNos)));
            $bnRes = $conn->query(
                "SELECT work_order_no, batch_number FROM mfg_work_order_hdr WHERE work_order_no IN ($woList)"
            );
            if ($bnRes) {
                while ($bn = $bnRes->fetch_assoc()) {
                    $batchNoMap[$bn['work_order_no']] = $bn['batch_number'] ?? '';
                }
            }
        }

        $formulaCache = [];
        foreach ($wos as &$wo) {
            $docId = intval($wo['doc_no'] ?? 0);
            $split = $splitMap[$docId] ?? null;
            $orderQty = floatval($split['oder_qty'] ?? $split['Qty'] ?? 0);
            $balanceQty = $split ? floatval($split['balance_qty'] ?? 0) : 0;

            $wo['forecast_no'] = $docId > 0 ? (string)$docId : (string)($wo['order_no'] ?? '');
            $wo['order_qty'] = $orderQty;
            $wo['remaining_qty'] = $balanceQty > 0
                ? $balanceQty
                : max(0, $orderQty - floatval($plannedByDoc[$docId] ?? 0));
            $wo['qty_to_produce'] = floatval($wo['batch_size'] ?? 0);
            $wo['batch_no'] = $batchNoMap[$wo['workorder_no'] ?? ''] ?? '';

            $formulaKey = ($wo['product_code'] ?? '') . '|' . ($wo['batch_size'] ?? '') . '|' . ($wo['planUnit'] ?? '');
            if (!isset($formulaCache[$formulaKey])) {
                $formulaCache[$formulaKey] = gw_formula_material_map(
                    $conn,
                    $wo['product_code'] ?? '',
                    $wo['batch_size'] ?? '',
                    $wo['planUnit'] ?? ''
                );
            }
            $formulaMap = $formulaCache[$formulaKey];

            if (!empty($wo['Deductions']) && is_array($wo['Deductions'])) {
                foreach ($wo['Deductions'] as &$ded) {
                    $code = $ded['material_code'] ?? '';
                    $fm = $formulaMap[$code] ?? null;
                    $ded['std_qty'] = $fm ? floatval($fm['qty'] ?? 0) : 0;
                    $ded['batch_qty'] = $fm
                        ? floatval($fm['batch_qty'] ?? ($ded['plan_qty'] ?? 0))
                        : floatval($ded['plan_qty'] ?? 0);
                    $ded['overages'] = $fm ? floatval($fm['overages'] ?? 0) : 0;
                    $ded['uom'] = $ded['unit'] ?? ($fm['unit_name'] ?? ($fm['unit'] ?? ''));
                }
                unset($ded);
            }
        }
        unset($wo);

        require_once __DIR__ . '/../planning/microplan/micro_production_plan_helpers.php';
        $wos = mpp_filter_proceed_wos($conn, $params, $wos);

        $data['can_plan_work_orders'] = $wos;
        $data['debug_info']['source'] = 'proceed_production_plan';
        $data['debug_info']['work_orders_can_plan'] = count($wos);
        return $data;
    }
}

if (!function_exists('gw_convert_wo_unit')) {
    function gw_convert_wo_unit($unit)
    {
        $u = strtolower(trim((string)$unit));
        $toKg = ['g', 'gm', 'gms', 'gram', 'grams', 'mg', 'milligram', 'milligrams'];
        if (in_array($u, $toKg, true)) {
            return 'KG';
        }
        $toLtr = ['ml', 'milli litre', 'millilitre', 'l', 'lt', 'ltr', 'litre', 'liter'];
        if (in_array($u, $toLtr, true)) {
            return 'LTR';
        }
        $toNos = ['nos', 'no', 'pcs', 'piece', 'pieces'];
        if (in_array($u, $toNos, true)) {
            return 'NOS';
        }
        $toMtr = ['m', 'meter', 'metre', 'meters', 'metres'];
        if (in_array($u, $toMtr, true)) {
            return 'MTR';
        }
        return strtoupper(trim((string)$unit));
    }
}

if (!function_exists('gw_work_order_formula_material_count')) {
    /**
     * Count batch formula lines for a work order (used when WO_deductions may legitimately be empty).
     */
    function gw_work_order_formula_material_count($conn, array $woRow)
    {
        $bfrNo = gw_resolve_bfr_for_work_order(
            $conn,
            $woRow['product_code'] ?? '',
            $woRow['batch_size'] ?? '',
            $woRow['planUnit'] ?? ''
        );
        if ($bfrNo === '') {
            return 0;
        }
        $bfrEsc = $conn->real_escape_string($bfrNo);
        $res = $conn->query(
            "SELECT COUNT(*) AS c FROM batch_materials
             WHERE bfr_no = '$bfrEsc'
               AND TRIM(IFNULL(material_code, '')) NOT IN ('', '-', 'N/A')"
        );
        $tableCount = ($res && ($row = $res->fetch_assoc())) ? intval($row['c']) : 0;
        if ($tableCount > 0) {
            return $tableCount;
        }
        if (function_exists('gw_gwo_collect_bfr_deduction_lines')) {
            return count(gw_gwo_collect_bfr_deduction_lines($conn, $bfrNo, $woRow));
        }
        return 0;
    }
}

if (!function_exists('gw_send_can_plan_batches')) {
    /**
     * Persist CAN_PLAN / CAN_PLAN_MC_QTY_USED work orders from Generate WO to Can Planned tab.
     */
    function gw_send_can_plan_batches($conn, array $workOrders, $emp_id = '', $plant_id = '')
    {
        $entry_date = date('Y-m-d H:i:s');
        $empEsc = $conn->real_escape_string((string)$emp_id);
        $plantEsc = $conn->real_escape_string((string)$plant_id);
        $sent = 0;
        $skipped = 0;
        $errors = [];

        if (function_exists('ensureWoDeductionShortageQueueColumns')) {
            ensureWoDeductionShortageQueueColumns($conn);
        }

        if (count($workOrders) === 0) {
            return [
                'status' => 'error',
                'message' => 'No work orders received. Refresh Generate WO and try again.',
                'sent' => 0,
                'skipped' => 0,
            ];
        }

        foreach ($workOrders as $values) {
            $status = strtoupper(trim((string)($values['status'] ?? '')));
            if (!in_array($status, ['CAN_PLAN', 'CAN_PLAN_MC_QTY_USED'], true)) {
                $skipped++;
                continue;
            }

            $woId = intval($values['id'] ?? 0);
            if ($woId <= 0 && !empty($values['workorder_no'])) {
                $woNoLookup = $conn->real_escape_string((string)$values['workorder_no']);
                $idRes = $conn->query("SELECT id FROM Work_order_materials WHERE workorder_no='$woNoLookup' LIMIT 1");
                if ($idRes && ($idRow = $idRes->fetch_assoc())) {
                    $woId = intval($idRow['id'] ?? 0);
                }
            }
            if ($woId <= 0) {
                $errors[] = 'Missing work order id for ' . ($values['workorder_no'] ?? 'unknown');
                continue;
            }

            $woRes = $conn->query("SELECT * FROM Work_order_materials WHERE id='$woId' LIMIT 1");
            $woRow = ($woRes && $woRes->num_rows > 0) ? $woRes->fetch_assoc() : null;
            if (!$woRow) {
                $errors[] = 'Work order not found: ' . $woId;
                continue;
            }

            $deductions = $values['deductions'] ?? [];
            if (is_array($deductions) && count($deductions) > 0) {
                $conn->query("DELETE FROM WO_deductions WHERE work_order_id = '$woId'");
                foreach ($deductions as $values1) {
                    if (empty($values1['material_code'])) {
                        continue;
                    }

                    $status1 = (string)($values1['status1'] ?? $status);
                    if (floatval($values1['deducted_from_MC'] ?? 0) != 0) {
                        $status1 = 'CAN_PLAN_MC_QTY_USED';
                    }

                    $finalUnit = gw_convert_wo_unit($values1['unit'] ?? '');
                    $planQty = round(
                        floatval($values1['deducted_from_MC'] ?? 0) +
                        floatval($values1['deducted_from_RM'] ?? 0) +
                        floatval($values1['deducted_from_Bulk'] ?? 0) +
                        floatval($values1['shortage'] ?? 0),
                        4
                    );

                    $indexDataArray = [];
                    if (!empty($values1['bulkShortage'])) {
                        $indexDataArray['bulkShortage'] = floatval($values1['bulkShortage']);
                    }
                    if (!empty($values1['deducted_from_Bulk'])) {
                        $indexDataArray['deducted_from_Bulk'] = floatval($values1['deducted_from_Bulk']);
                    }
                    if (!empty($values1['bulkComponents']) && is_array($values1['bulkComponents'])) {
                        $indexDataArray['bulkComponents'] = $values1['bulkComponents'];
                    }
                    if (!empty($values1['primixShortages']) && is_array($values1['primixShortages'])) {
                        $indexDataArray['primixShortages'] = $values1['primixShortages'];
                    }
                    $indexData = count($indexDataArray) > 0
                        ? "'" . $conn->real_escape_string(json_encode($indexDataArray)) . "'"
                        : 'NULL';

                    $matType = $conn->real_escape_string((string)($values1['type'] ?? ''));
                    $status1Esc = $conn->real_escape_string($status1);
                    $woNoEsc = $conn->real_escape_string((string)($values1['workorder_no'] ?? $values['workorder_no'] ?? ''));
                    $matCodeEsc = $conn->real_escape_string((string)$values1['material_code']);

                    $sql2 = "INSERT INTO WO_deductions
                        (plant_id, workorder_no, mat_type, status, plan_qty, unit, entry_by, entry_date,
                         material_code, deducted_from_RM, deducted_from_MC, shortage, indexData, work_order_id)
                     VALUES(
                        '$plantEsc',
                        '$woNoEsc',
                        '$matType',
                        '$status1Esc',
                        '$planQty',
                        '$finalUnit',
                        '$empEsc',
                        '$entry_date',
                        '$matCodeEsc',
                        '" . floatval($values1['deducted_from_RM'] ?? 0) . "',
                        '" . floatval($values1['deducted_from_MC'] ?? 0) . "',
                        '" . floatval($values1['shortage'] ?? 0) . "',
                        $indexData,
                        '$woId'
                     )";

                    if (!$conn->query($sql2)) {
                        $errors[] = $conn->error;
                        break 2;
                    }
                }
            } else {
                gw_ensure_can_plan_deductions_for_wo($conn, $woRow, $plantEsc, $empEsc);
            }

            $dedCheck = $conn->query("SELECT COUNT(*) AS c FROM WO_deductions WHERE work_order_id = '$woId'");
            $dedCount = ($dedCheck && ($dedRow = $dedCheck->fetch_assoc())) ? intval($dedRow['c']) : 0;
            if ($dedCount === 0) {
                $formulaMatCount = gw_work_order_formula_material_count($conn, $woRow);
                if ($formulaMatCount > 0) {
                    $errors[] = 'No material deductions for ' . ($values['workorder_no'] ?? $woId) . ' — check batch formula master';
                    continue;
                }
            }

            $woStatusEsc = $conn->real_escape_string($status);
            $sql = "UPDATE Work_order_materials
                    SET status='$woStatusEsc',
                        send_for_planning_by='$empEsc',
                        send_for_planning_on='$entry_date'
                    WHERE id='$woId'";
            if (!$conn->query($sql)) {
                $errors[] = $conn->error;
                continue;
            }

            if (!empty($values['doc_no'])) {
                $docNo = $conn->real_escape_string((string)$values['doc_no']);
                $conn->query("UPDATE split_planning_qty SET status='CAN_PLAN' WHERE id='$docNo'");
            }

            $sent++;
        }

        if (!empty($errors)) {
            return [
                'status' => 'error',
                'message' => implode('; ', array_unique($errors)),
                'sent' => $sent,
                'skipped' => $skipped,
            ];
        }

        if ($sent === 0) {
            return [
                'status' => 'error',
                'message' => 'No Can Plan batches were sent. Check batch status and material deductions.',
                'sent' => 0,
                'skipped' => $skipped,
            ];
        }

        return [
            'status' => 'success',
            'message' => $sent . ' batch(es) sent to Can Planned for further planning.',
            'sent' => $sent,
            'skipped' => $skipped,
        ];
    }
}
