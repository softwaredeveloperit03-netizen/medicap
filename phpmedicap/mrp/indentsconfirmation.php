<?php
/**
 * Medicap Indents Confirmation + MRP dashboard stats — standalone (no marketing/po.php).
 * Frontend: mrp/indentsconfirmation.php?type=...
 *
 * Upload together with:
 *   marketing/mrp_shortages_log_helpers.php
 *   marketing/mrp_indents_confirmation_helpers.php
 *   marketing/mrp_dashboard_helpers.php
 *   marketing/mrp_audit_log_helpers.php
 */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/indentsconfirmation-error.log');

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
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = array();
}

function mrp_ic_require_helper($relativeName) {
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
        'message' => 'Missing helper: marketing/' . $relativeName
            . ' — upload it under phpmedicap/marketing/ (or phpzuma/marketing/).',
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
    $txt = '{"process":"FRONTEND","token":"' . $token . '","action":"' . ($_GET['type'] ?? '') . '","actiontime":"' . $entry_date . '","department":"' . $_GET['department'] . '","emp_id":"' . $_GET['emp_id'] . '","method":"' . ($_SERVER['REQUEST_METHOD'] ?? '') . '","REMOTE_ADDR":"' . ($_SERVER['REMOTE_ADDR'] ?? '') . '"}';
    @file_put_contents(__DIR__ . '/../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);
}

if (!$auth_ok) {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid or missing token'));
    exit;
}

mrp_ic_require_helper('mrp_shortages_log_helpers.php');
mrp_ic_require_helper('mrp_indents_confirmation_helpers.php');
mrp_ic_require_helper('mrp_dashboard_helpers.php');
mrp_ic_require_helper('mrp_audit_log_helpers.php');

$type = $_GET['type'] ?? '';

if ($type === 'getMrpAuditLog') {
    $filters = array(
        'stage' => $_GET['stage'] ?? '',
        'workorder_no' => $_GET['workorder_no'] ?? '',
        'material_code' => $_GET['material_code'] ?? '',
        'order_no' => $_GET['order_no'] ?? '',
        'product_code' => $_GET['product_code'] ?? '',
        'indent_id' => $_GET['indent_id'] ?? '',
        'po_no' => $_GET['po_no'] ?? '',
        'source_screen' => $_GET['source_screen'] ?? '',
        'plant_id' => $_GET['plant_id'] ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'search' => $_GET['search'] ?? '',
        'limit' => $_GET['limit'] ?? 500,
    );
    $rows = gw_get_mrp_audit_log($conn, $filters);
    echo json_encode(array(
        'status' => 'success',
        'rows' => $rows,
        'total' => count($rows),
    ));
    exit;
}

if ($type === 'backfillPlanningMrpIndentsHistory') {
    @set_time_limit(180);
    @ini_set('memory_limit', '512M');
    try {
        $batch = isset($_GET['batch']) ? (int)$_GET['batch'] : 200;
        $max = isset($_GET['max']) ? (int)$_GET['max'] : 2000;
        $logInserted = gw_backfill_mrp_shortages_indent_log($conn, $batch, $max);
        $confirmationSynced = gw_backfill_mrp_indents_confirmation($conn, $batch, $max, false);
        echo json_encode(array(
            'status' => 'success',
            'shortages_log_inserted' => $logInserted,
            'confirmation_synced' => $confirmationSynced,
            'message' => 'Historical planning indents imported.',
        ));
    } catch (Throwable $e) {
        echo json_encode(array(
            'status' => 'error',
            'message' => $e->getMessage(),
            'shortages_log_inserted' => 0,
            'confirmation_synced' => 0,
        ));
    }
    exit;
}

if ($type === 'getPlanningIndentsConfirmation') {
    @set_time_limit(120);
    $output = gw_get_mrp_indents_confirmation_list($conn, array(
        'status' => $_GET['status'] ?? 'ALL',
        'search' => $_GET['search'] ?? '',
        'sync_missing' => true,
    ));
    echo json_encode($output);
    exit;
}

if ($type === 'getPlanningIndentsConfirmationPendingSummary') {
    echo json_encode(gw_get_mrp_indents_confirmation_pending_summary($conn));
    exit;
}

if ($type === 'getPlanningIndentsConfirmationLog') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 300;
    echo json_encode(gw_get_mrp_indents_confirmation_log($conn, $limit));
    exit;
}

if ($type === 'getMrpDashboardStats') {
    echo json_encode(gw_get_mrp_dashboard_stats($conn, array(
        'plant_id' => $_GET['plant_id'] ?? '',
    )));
    exit;
}

if ($type === 'applyPlanningIndentConfirmationAction') {
    $confirmationId = (int)($input['confirmation_id'] ?? 0);
    $action = $input['action'] ?? '';
    $remark = $input['remark'] ?? '';
    $raisedByName = '';
    if (!empty($_GET['emp_id'])) {
        $empRes = $conn->query(
            "SELECT COALESCE(emp_name, firstname, '') AS n FROM employee WHERE emp_id = '"
            . mysqli_real_escape_string($conn, $_GET['emp_id']) . "' LIMIT 1"
        );
        if ($empRes && $empRes->num_rows > 0) {
            $raisedByName = $empRes->fetch_assoc()['n'] ?? '';
        }
    }
    if ($raisedByName === '' && !empty($input['shortages_log']['raised_by_name'])) {
        $raisedByName = $input['shortages_log']['raised_by_name'];
    }
    $resultAction = gw_apply_mrp_indent_confirmation_action($conn, $confirmationId, $action, $remark, array(
        'emp_id' => $_GET['emp_id'] ?? '',
        'department' => $_GET['department'] ?? '',
        'raised_by_name' => $raisedByName,
    ));
    echo json_encode($resultAction);
    exit;
}

echo json_encode(array(
    'status' => 'error',
    'message' => 'Unsupported type for indentsconfirmation.php: ' . $type,
));
