<?php
/**
 * Medicap Indent Status Log API — standalone (no marketing/po.php).
 * Frontend: mrp/indentstatuslog.php?type=getPlanningIndentStatusLog
 *
 * Upload together with:
 *   marketing/mrp_indent_status_log_helpers.php
 *   marketing/mrp_shortages_log_helpers.php
 *   marketing/mrp_indents_confirmation_helpers.php
 */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/indentstatuslog-error.log');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../token.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');

$token = $_GET['token'] ?? '';
$entry_date = date('Y-m-d H:i:s');

function mrp_isl_require_helper($relativeName) {
    $candidates = array(
        __DIR__ . '/../marketing/' . $relativeName,
        dirname(__DIR__) . '/../phpzuma/marketing/' . $relativeName,
        dirname(dirname(__DIR__)) . '/phpzuma/marketing/' . $relativeName,
    );
    foreach ($candidates as $path) {
        if (is_file($path)) {
            require_once $path;
            return true;
        }
    }
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Missing helper: marketing/' . $relativeName,
    ));
    exit;
}

$tokenEsc = $conn->real_escape_string((string)$token);
$result = $conn->query("SELECT * FROM token WHERE token='" . $tokenEsc . "'");
$_GET['emp_id'] = $_GET['emp_id'] ?? '';
$_GET['department'] = '';

$auth_ok = false;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET['token'], $row['key1'], $row['key2']);
        $parts = explode('$', $string);
        $_GET['emp_id'] = $parts[0] ?? '';
        $_GET['department'] = $parts[1] ?? '';
        break;
    }
    $auth_ok = true;
}

if (!$auth_ok) {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid or missing token'));
    exit;
}

mrp_isl_require_helper('mrp_shortages_log_helpers.php');
mrp_isl_require_helper('mrp_indents_confirmation_helpers.php');
mrp_isl_require_helper('mrp_indent_status_log_helpers.php');

$type = $_GET['type'] ?? '';
if ($type === 'getPlanningIndentStatusLog') {
    $output = gw_get_planning_indent_status_log($conn, array(
        'search' => $_GET['search'] ?? '',
        'plant_id' => $_GET['plant_id'] ?? '',
        'page' => $_GET['page'] ?? 0,
        'pageSize' => $_GET['pageSize'] ?? 100,
    ));
    echo json_encode($output);
    exit;
}

echo json_encode(array(
    'status' => 'error',
    'message' => 'Unsupported type for indentstatuslog.php: ' . $type,
));
