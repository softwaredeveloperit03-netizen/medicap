<?php
require '../db.php';
require '../token.php';

header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Kolkata');

function roundSalesPoDecimal($value, $decimals = 4) {
    if ($value === null || $value === '') {
        return 0;
    }
    return round((float) $value, $decimals);
}

function salesPoFetchClientRow($clientCode, $conn) {
    if ($clientCode === null || trim((string) $clientCode) === '') {
        return null;
    }
    $codeEsc = $conn->real_escape_string(trim((string) $clientCode));
    $sql = "SELECT * FROM client WHERE client_code = '".$codeEsc."' LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $row['branch'] = json_decode($row['branch'] ?? '[]', true);
        if (!is_array($row['branch'])) {
            $row['branch'] = array();
        }
        return $row;
    }
    return null;
}

function salesPoBuildAddressOption($clientRow, $branchRow = null) {
    if ($branchRow !== null) {
        return array(
            'LglNm' => $branchRow['branch_name'] ?? ($clientRow['LglNm'] ?? ''),
            'c_address' => $branchRow['b_address'] ?? '',
            'gst_no' => $branchRow['b_gst_no'] ?? '',
            'c_city' => $branchRow['b_city'] ?? '',
            'c_permanent_state' => $branchRow['b_state'] ?? '',
            'c_pincode' => $branchRow['b_pincode'] ?? '',
            'dl_no' => $branchRow['dl_no'] ?? '-',
            'email' => $branchRow['email'] ?? '-',
            'c_scode' => $clientRow['c_scode'] ?? ($clientRow['scode'] ?? ''),
            'client_code' => $clientRow['client_code'] ?? '',
            'pan_no' => $clientRow['pan_no'] ?? '-'
        );
    }
    return array(
        'LglNm' => $clientRow['LglNm'] ?? ($clientRow['TrdNm'] ?? ''),
        'c_address' => $clientRow['c_address'] ?? ($clientRow['Addr1'] ?? ''),
        'gst_no' => $clientRow['c_gst_no'] ?? ($clientRow['gst_no'] ?? ''),
        'c_city' => $clientRow['c_city'] ?? ($clientRow['Loc'] ?? ''),
        'c_permanent_state' => $clientRow['state'] ?? ($clientRow['c_permanent_state'] ?? ''),
        'c_pincode' => $clientRow['c_pincode'] ?? ($clientRow['Pin'] ?? ''),
        'dl_no' => $clientRow['dl_no'] ?? '-',
        'email' => $clientRow['email'] ?? '-',
        'c_scode' => $clientRow['c_scode'] ?? ($clientRow['scode'] ?? ''),
        'client_code' => $clientRow['client_code'] ?? '',
        'pan_no' => $clientRow['pan_no'] ?? '-'
    );
}

function salesPoBuildBillToShipTos($clientRow) {
    $options = array();
    if (!$clientRow) {
        return $options;
    }
    foreach ($clientRow['branch'] as $branchRow) {
        if (!is_array($branchRow)) {
            continue;
        }
        $options[] = salesPoBuildAddressOption($clientRow, $branchRow);
    }
    $options[] = salesPoBuildAddressOption($clientRow, null);
    return $options;
}

function salesPoResolveGstType($poType, $billClient, $shipClient) {
    $poType = strtoupper(trim((string) $poType));
    if ($poType === 'EXPORT') {
        return 'IGST';
    }
    $billState = strtoupper(trim((string) ($billClient['state'] ?? $billClient['c_permanent_state'] ?? '')));
    $shipState = strtoupper(trim((string) ($shipClient['state'] ?? $shipClient['c_permanent_state'] ?? '')));
    $companyState = strtoupper(trim((string) ($billClient['company_state'] ?? 'GUJARAT')));
    if ($billState !== '' && ($billState === $companyState || $shipState === $companyState)) {
        return 'CGST/SGST';
    }
    return 'IGST';
}

function salesPoRecalculateLine(&$line, $gstType) {
    $qty = roundSalesPoDecimal($line['requiredQty']);
    $rate = roundSalesPoDecimal($line['rate']);
    $gst = roundSalesPoDecimal($line['gst']);

    $line['requiredQty'] = $qty;
    $line['rate'] = $rate;
    $line['gst'] = $gst;
    $line['taxable'] = roundSalesPoDecimal($qty * $rate);
    $line['taxAmt'] = roundSalesPoDecimal(($line['taxable'] * $gst) / 100);

    $gstType = strtoupper(trim((string) $gstType));
    if ($gstType === 'IGST') {
        $line['igst'] = $line['taxAmt'];
        $line['cgst'] = 0;
        $line['sgst'] = 0;
    } else {
        $line['igst'] = 0;
        $line['cgst'] = roundSalesPoDecimal($line['taxAmt'] / 2);
        $line['sgst'] = roundSalesPoDecimal($line['taxAmt'] / 2);
    }
    $line['netAmt'] = roundSalesPoDecimal($line['taxable'] + $line['taxAmt']);
}

function salesPoMapPoProducts($orderNo, $plantId, $gstType, $conn) {
    $products = array();
    $orderEsc = $conn->real_escape_string($orderNo);
    $plantEsc = $conn->real_escape_string($plantId);
    $sql = "SELECT o.*, p.product_name, p.hsn, p.gst
            FROM order_materials o
            LEFT JOIN product p ON TRIM(o.product_code) = TRIM(p.product_code)
            WHERE o.plant_id = '".$plantEsc."' AND o.order_no = '".$orderEsc."'
            ORDER BY o.id ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $qty = (float) ($row['order_qty'] ?? 0);
            if ($qty <= 0) {
                $qty = (float) ($row['quantity'] ?? 0);
            }
            $line = array(
                'product_code' => $row['product_code'],
                'product_name' => $row['product_name'] ?? $row['product_code'],
                'hsn' => !empty($row['hsn']) ? $row['hsn'] : 'NA',
                'requiredQty' => $qty,
                'rate' => (float) ($row['rate'] ?? 0),
                'gst' => (float) (($row['gst'] ?? '') !== '' ? $row['gst'] : 18),
                'unit' => !empty($row['unit']) ? $row['unit'] : 'Kg',
                'pack_size' => $row['pack_size'] ?? ''
            );
            salesPoRecalculateLine($line, $gstType);
            $products[] = $line;
        }
    }
    return $products;
}

function salesPoBuildOrderPayload($poRow, $plantId, $conn) {
    $billClient = salesPoFetchClientRow($poRow['client_code'] ?? '', $conn);
    $shipClient = salesPoFetchClientRow($poRow['conisgnee'] ?? ($poRow['client_code'] ?? ''), $conn);
    if (!$shipClient) {
        $shipClient = $billClient;
    }

    $billToShipTos = salesPoBuildBillToShipTos($billClient);
    $billData = salesPoBuildAddressOption($billClient, null);
    $shipData = salesPoBuildAddressOption($shipClient, null);

    foreach ($billToShipTos as $option) {
        if (trim($option['client_code']) === trim($poRow['client_code'] ?? '')) {
            $billData = $option;
            break;
        }
    }

    $shipOptions = salesPoBuildBillToShipTos($shipClient);
    foreach ($shipOptions as $option) {
        if (trim($option['client_code']) === trim($poRow['conisgnee'] ?? ($poRow['client_code'] ?? ''))) {
            $shipData = $option;
            break;
        }
    }

    $gstType = salesPoResolveGstType($poRow['po_type'] ?? '', $billClient ?? array(), $shipClient ?? array());
    $products = salesPoMapPoProducts($poRow['order_no'], $plantId, $gstType, $conn);

    $dueDate = !empty($poRow['valid_till']) ? $poRow['valid_till'] : '';
    if ($dueDate === '' && !empty($poRow['required_date'])) {
        $dueDate = $poRow['required_date'];
    }
    if ($dueDate === '' && !empty($poRow['delivery_date'])) {
        $dueDate = $poRow['delivery_date'];
    }

    return array(
        'marketing_po_id' => $poRow['id'],
        'marketing_order_no' => $poRow['order_no'],
        'client_code' => $poRow['client_code'],
        'clientName' => $poRow['TrdNm'] ?? '',
        'gst_type' => $gstType,
        'orderNo' => $poRow['po_no'],
        'orderDate' => $poRow['po_date'],
        'dueDate' => $dueDate,
        'po_no' => $poRow['po_no'],
        'transporter' => !empty($poRow['freight_by']) ? $poRow['freight_by'] : '',
        'dispatch' => !empty($poRow['mode_ship']) ? $poRow['mode_ship'] : 'Road',
        'creditDays' => '',
        'BillTo' => $billData['LglNm'],
        'ShipTo' => $shipData['LglNm'],
        'billToShipTos' => $billToShipTos,
        'billData' => $billData,
        'shipData' => $shipData,
        'poProducts' => $products,
        'is_marketing_client' => $billClient !== null
    );
}

$token = $_GET['token'] ?? '';
$timestamp = time();
$entry_date = date('Y-m-d h:i:s', $timestamp);
$_GET['emp_id'] = '';
$_GET['department'] = '';
$input = json_decode(file_get_contents('php://input'), true);

$sql = "SELECT * FROM token WHERE token='".$conn->real_escape_string($token)."'";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(array('status' => 'failed', 'msg' => 'Unauthorized'));
    exit;
}

while ($row = $result->fetch_assoc()) {
    $string = decrypt('decrypt', $token, $row['key1'], $row['key2']);
    $string = explode('$', $string);
    $_GET['emp_id'] = $string[0];
    $_GET['department'] = $string[1];
    break;
}

$action = $_GET['type'] ?? '';
$plant_id = isset($_GET['plant_id']) ? $conn->real_escape_string($_GET['plant_id']) : '';
$txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$action.'", "actiontime": "'.$entry_date.'", "department": "'.$_GET['department'].'", "emp_id": "'.$_GET['emp_id'].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
file_put_contents('../logs.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);

if ($action === 'getApprovedMarketingPOs') {
    header('Content-Type: application/json');
    $output = array();
    $sqlPo = "SELECT p.id, p.order_no, p.po_no, p.po_date, p.client_code, p.conisgnee, p.po_type, p.valid_till,
              c.TrdNm AS clientName
              FROM po_entry p
              LEFT JOIN client c ON p.client_code = c.client_code
              WHERE p.plant_id = '".$plant_id."' AND p.status = 'approve'
              ORDER BY p.id DESC";
    $resPo = $conn->query($sqlPo);
    if ($resPo && $resPo->num_rows > 0) {
        while ($po = $resPo->fetch_assoc()) {
            $output[] = $po;
        }
    }
    echo json_encode($output);
    exit;
}

if ($action === 'getMarketingPOForSales') {
    header('Content-Type: application/json');
    $poId = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';
    if ($poId === '') {
        echo json_encode(array('status' => 'failed', 'msg' => 'PO id is required.'));
        exit;
    }

    $sqlPo = "SELECT p.*, c.TrdNm
              FROM po_entry p
              LEFT JOIN client c ON p.client_code = c.client_code
              WHERE p.id = '".$poId."' AND p.plant_id = '".$plant_id."' AND p.status = 'approve'
              LIMIT 1";
    $resPo = $conn->query($sqlPo);
    if (!$resPo || $resPo->num_rows === 0) {
        echo json_encode(array('status' => 'failed', 'msg' => 'Approved marketing PO not found.'));
        exit;
    }

    $poRow = $resPo->fetch_assoc();
    $payload = salesPoBuildOrderPayload($poRow, $plant_id, $conn);
    $payload['status'] = 'success';
    echo json_encode($payload);
    exit;
}

http_response_code(400);
echo json_encode(array('status' => 'failed', 'msg' => 'Invalid request type.'));
