<?php
require '../db.php';
require '../token.php';
require 'dispatch_serial.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');

$token = isset($_GET['token']) ? $_GET['token'] : '';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = array();
}

$sql = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$result = $conn->query($sql);
$_GET['emp_id'] = '';
$_GET['department'] = '';

if (!$result || $result->num_rows === 0) {
    echo json_encode(array('status' => 'failed', 'msg' => 'Invalid token'));
    $conn->close();
    exit;
}

while ($row = $result->fetch_assoc()) {
    $string = decrypt('decrypt', $_GET['token'], $row['key1'], $row['key2']);
    $string = explode('$', $string);
    $_GET['emp_id'] = $string[0];
    $_GET['department'] = isset($string[1]) ? $string[1] : '';
    break;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$plantId = isset($_GET['plant_id']) ? $_GET['plant_id'] : '';
$plantEsc = $conn->real_escape_string(trim((string)$plantId));

function dispatch_api_load_sales_products($conn, $plantEsc, $orderId) {
    $output2 = array();
    $orderEsc = $conn->real_escape_string(trim((string)$orderId));
    $sql1 = "SELECT * FROM sales_product WHERE plant_id = '$plantEsc' AND orderId = '$orderEsc'";
    $result1 = $conn->query($sql1);
    if ($result1 && $result1->num_rows > 0) {
        while ($row1 = $result1->fetch_assoc()) {
            $output2[] = $row1;
        }
    }
    return $output2;
}

if ($type === 'getPendingInvoices') {
    dispatch_ensure_sales_challan_column($conn);
    $output = array();
    $sql = "SELECT s.*, c.LglNm AS clientName
        FROM sales s
        LEFT JOIN client c ON s.client_code = c.client_code
        WHERE s.plant_id = '$plantEsc'
          AND s.status = 'Approved'
          AND IFNULL(s.invoice, 'Pending') = 'Pending'
        ORDER BY s.id DESC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['products'] = dispatch_api_load_sales_products($conn, $plantEsc, $row['id']);
            $row = dispatch_ensure_sales_dispatch_numbers($conn, $plantId, $row);
            $output[] = $row;
        }
    }
    echo json_encode($output);
    $conn->close();
    exit;
}

if ($type === 'getSalesOrderForInvoice') {
    dispatch_ensure_sales_challan_column($conn);
    $salesIdEsc = $conn->real_escape_string(trim((string)($_GET['id'] ?? '')));
    if ($salesIdEsc === '') {
        echo json_encode(array('status' => 'failed', 'msg' => 'Sales order id is required.'));
        $conn->close();
        exit;
    }

    $sql = "SELECT s.*, c.LglNm AS clientName
        FROM sales s
        LEFT JOIN client c ON s.client_code = c.client_code
        WHERE s.id = '$salesIdEsc' AND s.plant_id = '$plantEsc'
        LIMIT 1";
    $result = $conn->query($sql);
    if (!$result || $result->num_rows === 0) {
        echo json_encode(array('status' => 'failed', 'msg' => 'Sales order not found.'));
        $conn->close();
        exit;
    }

    $row = $result->fetch_assoc();
    $row['products'] = dispatch_api_load_sales_products($conn, $plantEsc, $row['id']);
    $row = dispatch_ensure_sales_dispatch_numbers($conn, $plantId, $row);
    echo json_encode($row);
    $conn->close();
    exit;
}

if ($type === 'getNextDispatchNumbers') {
    dispatch_ensure_sales_challan_column($conn);
    $nums = dispatch_find_next_available_numbers($conn, $plantId);
    echo json_encode(array(
        'status' => 'success',
        'invoice_serial' => $nums['invoice_serial'],
        'invoice_no' => $nums['invoice_no'],
        'challan_no' => $nums['challan_no'],
    ));
    $conn->close();
    exit;
}

echo json_encode(array('status' => 'failed', 'msg' => 'Unknown request type'));
$conn->close();
