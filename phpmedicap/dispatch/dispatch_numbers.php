<?php
require '../db.php';
require '../token.php';
require 'dispatch_serial.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');

$token = isset($_GET['token']) ? $_GET['token'] : '';
$entry_date = date('Y-m-d H:i:s');

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

if ($type === 'getNextDispatchNumbers') {
    dispatch_ensure_sales_challan_column($conn);
    $plantId = isset($_GET['plant_id']) ? $_GET['plant_id'] : '';
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
