<?php

function po_processing_escape($conn, $value)
{
    return $conn->real_escape_string((string)($value ?? ''));
}

function po_processing_split_id($input)
{
    if (!empty($input['split_id'])) {
        return (string)$input['split_id'];
    }
    if (!empty($input['id'])) {
        return (string)$input['id'];
    }
    return '';
}

function po_processing_plan_qty($input)
{
    foreach (array('plan_qty', 'planQty', 'wo_qty', 'oder_qty') as $key) {
        if (isset($input[$key]) && $input[$key] !== '' && $input[$key] !== null) {
            return floatval($input[$key]);
        }
    }
    return 0.0;
}

/**
 * Batch sizes for Processing entry — product_code first, then unitformula/mfr fallback.
 *
 * @param array $options  fresh_from_bom — refresh popup: all BOM sizes, any unit, incl. pending BFR
 *                        all_units      — skip planUnit filter
 */
function po_processing_batch_formula_for_product($conn, $productCode, $planUnit = '', $options = array())
{
    $formulas = array();
    $productCode = trim((string)$productCode);
    if ($productCode === '') {
        return $formulas;
    }

    $freshFromBom = !empty($options['fresh_from_bom']);
    $ignorePlanUnit = $freshFromBom || !empty($options['all_units']);

    $appendRows = function ($result) use (&$formulas) {
        if (!$result) {
            return;
        }
        $seen = array();
        foreach ($formulas as $f) {
            $seen[(string)($f['batch_formula_weight'] ?? '')] = true;
        }
        while ($row = $result->fetch_assoc()) {
            $weight = $row['batch_formula_weight'] ?? '';
            if ($weight === '' || $weight === null || floatval($weight) <= 0) {
                continue;
            }
            $key = (string)$weight;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $formulas[] = array(
                'batch_formula_weight' => $weight,
                'batch_formula_weight_unit' => $row['batch_formula_weight_unit'] ?? $row['rm_batch_size_unit'] ?? '',
                'bfr_no' => $row['bfr_no'] ?? '',
                'bfr_status' => $row['status'] ?? '',
            );
        }
    };

    $tryProductCodes = array($productCode);
    $productCodeEsc = po_processing_escape($conn, $productCode);
    $altRes = $conn->query(
        "SELECT TRIM(IFNULL(product_code,'')) AS product_code,
                TRIM(IFNULL(product_code1,'')) AS alt_code
         FROM product
         WHERE TRIM(product_code) = '$productCodeEsc'
            OR TRIM(product_code1) = '$productCodeEsc'
         LIMIT 1"
    );
    if ($altRes && ($altRow = $altRes->fetch_assoc())) {
        $main = trim((string)($altRow['product_code'] ?? ''));
        $alt = trim((string)($altRow['alt_code'] ?? ''));
        if ($main !== '' && !in_array($main, $tryProductCodes, true)) {
            $tryProductCodes[] = $main;
        }
        if ($alt !== '' && !in_array($alt, $tryProductCodes, true)) {
            $tryProductCodes[] = $alt;
        }
    }

    $planUnitEsc = po_processing_escape($conn, trim((string)$planUnit));
    // Unit formula is approved when status column or approve_by is set.
    $approvedMfr = "(LOWER(IFNULL(u.status,'')) IN ('approve', 'approved') OR (u.approve_by IS NOT NULL AND TRIM(u.approve_by) <> ''))";
    // BOM batch sizes: include pending (newly prepared BOM) and Approve/approved; exclude cancelled/rejected only.
    $usableBfr = "(b.status IS NULL OR TRIM(b.status) = '' OR LOWER(TRIM(b.status)) NOT IN ('cancel', 'cancelled', 'reject', 'rejected'))";

    foreach ($tryProductCodes as $code) {
        $codeEsc = po_processing_escape($conn, $code);
        if ($codeEsc === '') {
            continue;
        }

        if (!$ignorePlanUnit && $planUnitEsc !== '') {
            $sqlUnit = "SELECT DISTINCT b.bfr_no, b.status, b.batch_formula_weight, b.rm_batch_size_unit AS batch_formula_weight_unit
                        FROM batch_formula_info b
                        WHERE LOWER(TRIM(IFNULL(b.product_code,''))) = LOWER('$codeEsc')
                          AND CAST(b.batch_formula_weight AS DECIMAL(15,4)) > 0
                          AND LOWER(TRIM(IFNULL(b.rm_batch_size_unit,''))) = LOWER('$planUnitEsc')
                          AND $usableBfr
                        ORDER BY CAST(b.batch_formula_weight AS DECIMAL(15,4)) DESC";
            $appendRows($conn->query($sqlUnit));
        }

        // All BOM batch sizes for this product code (any unit).
        $sqlAll = "SELECT DISTINCT b.bfr_no, b.status, b.batch_formula_weight, b.rm_batch_size_unit AS batch_formula_weight_unit
                   FROM batch_formula_info b
                   WHERE LOWER(TRIM(IFNULL(b.product_code,''))) = LOWER('$codeEsc')
                     AND CAST(b.batch_formula_weight AS DECIMAL(15,4)) > 0
                     AND $usableBfr
                   ORDER BY CAST(b.batch_formula_weight AS DECIMAL(15,4)) DESC";
        $appendRows($conn->query($sqlAll));

        // BOM batch sizes linked via approved unit formula (mfr_no).
        $sqlMfr = "SELECT DISTINCT b.bfr_no, b.status, b.batch_formula_weight, b.rm_batch_size_unit AS batch_formula_weight_unit
                   FROM unitformula u
                   INNER JOIN batch_formula_info b ON b.mfr_no = u.mfr_no
                   WHERE LOWER(TRIM(u.product_code)) = LOWER('$codeEsc')
                     AND $approvedMfr
                     AND CAST(b.batch_formula_weight AS DECIMAL(15,4)) > 0
                     AND $usableBfr
                   ORDER BY u.id DESC, CAST(b.batch_formula_weight AS DECIMAL(15,4)) DESC";
        $appendRows($conn->query($sqlMfr));

        if (!empty($formulas)) {
            break;
        }
    }

    if (empty($formulas)) {
        $codeEsc = po_processing_escape($conn, $productCode);
        $sqlUfBatch = "SELECT DISTINCT u.batch_size AS batch_formula_weight,
                              COALESCE(NULLIF(TRIM(u.unit), ''), NULLIF(TRIM('$planUnitEsc'), ''), 'NOS') AS batch_formula_weight_unit
                       FROM unitformula u
                       WHERE LOWER(TRIM(u.product_code)) = LOWER('$codeEsc')
                         AND $approvedMfr
                         AND CAST(u.batch_size AS DECIMAL(15,4)) > 0
                       ORDER BY u.id DESC";
        $appendRows($conn->query($sqlUfBatch));
    }

    return $formulas;
}

function po_processing_next_workorder_no($conn)
{
    $next = 1;
    $res = $conn->query(
        "SELECT workorder_no FROM Work_order_materials
         WHERE workorder_no REGEXP '^BO[0-9]+$'
         ORDER BY CAST(SUBSTRING(workorder_no, 3) AS UNSIGNED) DESC, id DESC
         LIMIT 1"
    );
    if ($res && ($row = $res->fetch_assoc()) && !empty($row['workorder_no'])) {
        $next = intval(substr($row['workorder_no'], 2)) + 1;
    }
    return 'BO' . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

/**
 * Fill missing processing-entry fields from split_planning_qty + po_entry.
 */
function po_processing_enrich_input($conn, &$input, $plantIdFromRequest = '')
{
    $splitId = po_processing_split_id($input);
    if ($splitId === '') {
        return false;
    }

    $splitIdEsc = po_processing_escape($conn, $splitId);
    $sql = "SELECT sp.*,
                   pe.id AS po_entry_id,
                   pe.client_code,
                   pe.conisgnee,
                   p.category,
                   p.product_name AS product_name_from_product
            FROM split_planning_qty sp
            LEFT JOIN po_entry pe ON pe.order_no = sp.order_no
            LEFT JOIN product p ON p.product_code = sp.product_code
            WHERE sp.id = '$splitIdEsc'
            LIMIT 1";

    $res = $conn->query($sql);
    if (!$res || $res->num_rows === 0) {
        return false;
    }

    $row = $res->fetch_assoc();
    foreach ($row as $key => $value) {
        if (!isset($input[$key]) || $input[$key] === '' || $input[$key] === null) {
            $input[$key] = $value;
        }
    }

    $input['id'] = $splitId;
    $input['split_id'] = $splitId;

    if (empty($input['plant_id'])) {
        $input['plant_id'] = $plantIdFromRequest !== '' ? $plantIdFromRequest : ($row['plant_id'] ?? '');
    }
    if (empty($input['doc_no'])) {
        $input['doc_no'] = $splitId;
    }
    if (empty($input['planMonth']) && !empty($row['month']) && !empty($row['year'])) {
        $input['planMonth'] = $row['month'] . '-' . $row['year'];
    }
    if (!isset($input['plan_qty']) || $input['plan_qty'] === '' || $input['plan_qty'] === null) {
        $input['plan_qty'] = $row['oder_qty'] ?? 0;
    }
    if (!isset($input['planQty']) || $input['planQty'] === '' || $input['planQty'] === null) {
        $input['planQty'] = $input['plan_qty'];
    }
    if (empty($input['planUnit']) && !empty($row['planUnit'])) {
        $input['planUnit'] = $row['planUnit'];
    } elseif (empty($input['planUnit']) && !empty($row['unit'])) {
        $input['planUnit'] = $row['unit'];
    }
    if (empty($input['workorder_no'])) {
        $input['workorder_no'] = po_processing_next_workorder_no($conn);
    }
    if (empty($input['remark']) && !empty($row['remarkText'])) {
        $input['remark'] = $row['remarkText'];
    }
    if (empty($input['product_name']) && !empty($row['product_name_from_product'])) {
        $input['product_name'] = $row['product_name_from_product'];
    }

    return true;
}

function po_processing_json_response($payload)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function po_processing_update_split($conn, $input, $status, $empId, $entryDate)
{
    if (!po_processing_enrich_input($conn, $input, $_GET['plant_id'] ?? '')) {
        po_processing_json_response(array('status' => 'error', 'message' => 'Split plan not found'));
    }

    $splitId = po_processing_escape($conn, $input['id']);
    $statusEsc = po_processing_escape($conn, $status);
    $empEsc = po_processing_escape($conn, $empId);

    $planQty = po_processing_plan_qty($input);
    $excess = floatval($input['excess'] ?? 0);
    $leftover = floatval($input['leftover'] ?? 0);

    if ($excess > 0) {
        $planQty += $excess;
    } elseif ($leftover > 0) {
        $planQty -= $leftover;
    }
    if ($planQty < 0) {
        $planQty = 0;
    }

    $batchesRaw = $input['batches'] ?? array();
    if (is_string($batchesRaw)) {
        $decoded = json_decode($batchesRaw, true);
        $batchesRaw = is_array($decoded) ? $decoded : array();
    }
    $batchesJson = po_processing_escape($conn, json_encode($batchesRaw));

    $sql = "UPDATE split_planning_qty
            SET work_order_planned_qty='$planQty',
                leftover='$leftover',
                excess='$excess',
                batches='$batchesJson',
                status='$statusEsc',
                entryBy='$empEsc',
                entryOn='$entryDate'
            WHERE id='$splitId'";

    if (!$conn->query($sql)) {
        po_processing_json_response(array('status' => 'error', 'message' => $conn->error));
    }
    if ($conn->affected_rows === 0) {
        $checkRes = $conn->query("SELECT status FROM split_planning_qty WHERE id='$splitId' LIMIT 1");
        if ($checkRes && ($checkRow = $checkRes->fetch_assoc()) && (string)$checkRow['status'] === (string)$status) {
            po_processing_json_response(array('status' => 'success'));
        }
        po_processing_json_response(array('status' => 'error', 'message' => 'No split row updated. Invalid split id.'));
    }

    if ($status === 'Work Order Preparation Approved') {
        $conn->query("UPDATE split_planning_qty
                      SET proceed_approved_by='$empEsc', proceed_approved_date='$entryDate'
                      WHERE id='$splitId'");
    }

    if ($status === 'Work Order Processed' && !empty($batchesRaw) && is_array($batchesRaw)) {
        $docNoEsc = po_processing_escape($conn, $input['doc_no'] ?? $splitId);
        $existingWo = $conn->query("SELECT id FROM Work_order_materials WHERE doc_no='$docNoEsc' LIMIT 1");
        if (!$existingWo || $existingWo->num_rows === 0) {
            po_processing_insert_work_orders($conn, $input, $batchesRaw, $status, $empId, $entryDate, $planQty);
        }
    }

    if (!empty($input['order_no']) && !empty($input['product_code'])) {
        po_processing_complete_factory_order_if_done($conn, $input['order_no'], $input['product_code']);
    }

    po_processing_json_response(array('status' => 'success'));
}

function po_processing_complete_factory_order_if_done($conn, $orderNo, $productCode)
{
    $orderNoEsc = po_processing_escape($conn, $orderNo);
    $productCodeEsc = po_processing_escape($conn, $productCode);
    $sql = "SELECT COUNT(*) AS cnt FROM split_planning_qty sp
            WHERE sp.order_no = '$orderNoEsc'
              AND sp.product_code = '$productCodeEsc'
              AND (
                  sp.status IS NULL
                  OR TRIM(IFNULL(sp.status,'')) = ''
                  OR LOWER(TRIM(sp.status)) = 'pending'
              )
              AND sp.id NOT IN (
                  SELECT DISTINCT CAST(wom.doc_no AS UNSIGNED)
                  FROM Work_order_materials wom
                  WHERE wom.doc_no IS NOT NULL AND TRIM(wom.doc_no) != '' AND wom.doc_no != '0'
              )";
    $res = $conn->query($sql);
    $cnt = ($res && ($row = $res->fetch_assoc())) ? (int)$row['cnt'] : 0;
    if ($cnt === 0) {
        $conn->query("UPDATE order_materials
                      SET reqStatus = 'Complete'
                      WHERE order_no = '$orderNoEsc'
                        AND product_code = '$productCodeEsc'
                        AND reqStatus = 'Inprocess'");
    }
}

function po_processing_insert_work_orders($conn, $input, $batches, $status, $empId, $entryDate, $planQty)
{
    $fields = array(
        'po_entry_id', 'plant_id', 'workorder_no', 'mainGroupName', 'groupcode', 'subClient',
        'order_no', 'doc_no', 'Fo_code', 'planMonth', 'product_code', 'packingStyle',
        'packingUnit', 'planUnit', 'deliveryDate', 'remark', 'parent_product_code',
        'CombiMaster_dtl_qty', 'billing_type'
    );
    $values = array();
    foreach ($fields as $field) {
        $values[$field] = po_processing_escape($conn, $input[$field] ?? '');
    }
    // Live DB: mainGroupName is VARCHAR(30) — long client names must not fail WO insert.
    if (isset($values['mainGroupName']) && strlen($values['mainGroupName']) > 30) {
        $values['mainGroupName'] = substr($values['mainGroupName'], 0, 30);
    }

    $excess = po_processing_escape($conn, $input['excess'] ?? 0);
    $leftover = po_processing_escape($conn, $input['leftover'] ?? 0);
    $planQtyEsc = po_processing_escape($conn, $planQty);
    $planQtyField = po_processing_escape($conn, $input['plan_qty'] ?? $planQty);
    $planQtyCamel = po_processing_escape($conn, $input['planQty'] ?? $planQty);
    $statusEsc = po_processing_escape($conn, $status);
    $empEsc = po_processing_escape($conn, $empId);
    $entryEsc = po_processing_escape($conn, $entryDate);

    foreach ($batches as $batch) {
        $size = floatval($batch['size'] ?? 0);
        $count = intval($batch['count'] ?? 0);
        if ($size <= 0 || $count <= 0) {
            continue;
        }
        for ($i = 0; $i < $count; $i++) {
            // Each batch row must get its own BO number (reusing one caused duplicate BO001 rows).
            $woNo = po_processing_next_workorder_no($conn);
            $woNoEsc = po_processing_escape($conn, $woNo);
            $sqlInsert = "INSERT INTO Work_order_materials(
                po_entry_id, plant_id, workorder_no,
                batch_size, excess, leftover,
                mainGroupName, groupcode, subClient, order_no,
                doc_no, Fo_code, planMonth, product_code,
                packingStyle, packingUnit, planQty, planUnit,
                deliveryDate, remark, status, entryBy, entryOn,
                parent_product_code, plan_qty, CombiMaster_dtl_qty, billing_type
            ) VALUES (
                '{$values['po_entry_id']}',
                '{$values['plant_id']}',
                '{$woNoEsc}',
                '$size',
                '$excess',
                '$leftover',
                '{$values['mainGroupName']}',
                '{$values['groupcode']}',
                '{$values['subClient']}',
                '{$values['order_no']}',
                '{$values['doc_no']}',
                '{$values['Fo_code']}',
                '{$values['planMonth']}',
                '{$values['product_code']}',
                '{$values['packingStyle']}',
                '{$values['packingUnit']}',
                '$planQtyCamel',
                '{$values['planUnit']}',
                '{$values['deliveryDate']}',
                '{$values['remark']}',
                '$statusEsc',
                '$empEsc',
                '$entryEsc',
                '{$values['parent_product_code']}',
                '$planQtyField',
                '{$values['CombiMaster_dtl_qty']}',
                '{$values['billing_type']}'
            )";
            if (!$conn->query($sqlInsert)) {
                po_processing_json_response(array('status' => 'error', 'message' => 'Work order insert failed: ' . $conn->error));
            }
        }
    }
}
