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
mrp_ic_require_helper('mrp_material_availability_helpers.php');
mrp_ic_require_helper('mrp_cancel_recalc_helpers.php');
mrp_ic_require_helper('mrp_planning_horizon_helpers.php');
mrp_ic_require_helper('mrp_traceability_helpers.php');
mrp_ic_require_helper('mrp_phase_smoke_helpers.php');

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

if ($type === 'getMrpTraceability') {
    echo json_encode(gw_get_mrp_traceability($conn, array(
        'workorder_no' => $_GET['workorder_no'] ?? '',
        'material_code' => $_GET['material_code'] ?? '',
        'order_no' => $_GET['order_no'] ?? '',
        'plant_id' => $_GET['plant_id'] ?? '',
        'write_audit' => $_GET['write_audit'] ?? '1',
    )));
    exit;
}

if ($type === 'runMrpPhaseSmoke') {
    // line booking history helper used by smoke
    mrp_ic_require_helper('mrp_line_booking_history_helpers.php');
    echo json_encode(gw_run_mrp_phase_smoke($conn, array(
        'plant_id' => $_GET['plant_id'] ?? '',
        'workorder_no' => $_GET['workorder_no'] ?? 'BO002',
        'material_code' => $_GET['material_code'] ?? 'RM0129',
    )));
    exit;
}

if ($type === 'createIndentConfirmationSnapshot') {
    gw_ensure_mrp_indents_confirmation_tables($conn);
    $materialCode = trim((string)($input['material_code'] ?? ''));
    if ($materialCode === '') {
        echo json_encode(array('status' => 'error', 'message' => 'material_code is required'));
        exit;
    }
    $esc = function ($v) use ($conn) {
        return $conn->real_escape_string((string)($v ?? ''));
    };
    $qty = (float)($input['raised_indent_qty'] ?? $input['shortage_qty'] ?? $input['required_qty'] ?? 0);
    if ($qty <= 0) {
        $qty = 1;
    }
    $empId = $esc($_GET['emp_id'] ?? '');
    $empName = '';
    if ($empId !== '') {
        $er = @$conn->query(
            "SELECT CASE
                WHEN TRIM(IFNULL(firstname,'')) <> '' THEN CONCAT(TRIM(firstname), ' (', emp_id, ')')
                ELSE emp_id
             END AS n FROM employee WHERE emp_id='$empId' LIMIT 1"
        );
        if ($er && $er->num_rows > 0) {
            $empName = $esc($er->fetch_assoc()['n'] ?? '');
        }
    }
    $remark = $esc($input['remark'] ?? 'Indent confirmation snapshot');
    $sql = "INSERT INTO mrp_indents_confirmation (
                confirmation_status, material_code, material_name, material_type,
                workorder_no, order_no, product_code, product_name,
                required_qty, shortage_qty, raised_indent_qty, qty_unit,
                indent_raised_date, indent_raised_by, indent_raised_by_name, remark,
                is_locked, lock_revision_no
            ) VALUES (
                'PENDING',
                '".$esc($materialCode)."',
                '".$esc($input['material_name'] ?? '')."',
                '".$esc($input['material_type'] ?? '')."',
                '".$esc($input['workorder_no'] ?? '')."',
                '".$esc($input['order_no'] ?? '')."',
                '".$esc($input['product_code'] ?? '')."',
                '".$esc($input['product_name'] ?? '')."',
                $qty, $qty, $qty,
                '".$esc($input['qty_unit'] ?? '')."',
                NOW(), '$empId', '$empName', '$remark',
                0, 0
            )";
    if (!$conn->query($sql)) {
        echo json_encode(array('status' => 'error', 'message' => $conn->error));
        exit;
    }
    $id = (int)$conn->insert_id;
    gw_write_mrp_indent_confirmation_log($conn, array(
        'confirmation_id' => $id,
        'action_type' => 'CREATE',
        'action_status' => 'PENDING',
        'material_code' => $materialCode,
        'material_name' => $input['material_name'] ?? '',
        'workorder_no' => $input['workorder_no'] ?? '',
        'order_no' => $input['order_no'] ?? '',
        'qty' => $qty,
        'remark' => $input['remark'] ?? 'Indent confirmation snapshot',
    ), array(
        'emp_id' => $_GET['emp_id'] ?? '',
        'department' => $_GET['department'] ?? '',
        'raised_by_name' => $empName,
    ));
    echo json_encode(array(
        'status' => 'success',
        'confirmation_id' => $id,
        'confirmation_status' => 'PENDING',
        'message' => 'Indent confirmation snapshot created',
    ));
    exit;
}

if ($type === 'applyPlanningIndentConfirmationAction') {
    $confirmationId = (int)($input['confirmation_id'] ?? 0);
    $action = $input['action'] ?? '';
    $remark = $input['remark'] ?? '';
    $raisedByName = '';
    if (!empty($_GET['emp_id'])) {
        $eid = mysqli_real_escape_string($conn, $_GET['emp_id']);
        $empRes = @$conn->query(
            "SELECT CASE
                WHEN TRIM(IFNULL(firstname,'')) <> '' THEN CONCAT(TRIM(firstname), ' (', emp_id, ')')
                ELSE emp_id
             END AS n FROM employee WHERE emp_id = '$eid' LIMIT 1"
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

if ($type === 'getMrpCancelRecalcLog') {
    echo json_encode(gw_mrp_get_cancel_recalc_log($conn, $_GET));
    exit;
}

if ($type === 'recalcAfterIndentCancel') {
    $confirmationId = (int)($input['confirmation_id'] ?? ($_GET['confirmation_id'] ?? 0));
    echo json_encode(gw_mrp_recalc_after_indent_cancel($conn, $confirmationId, array(
        'emp_id' => $_GET['emp_id'] ?? '',
        'department' => $_GET['department'] ?? '',
        'plant_id' => $_GET['plant_id'] ?? '',
        'remark' => $input['remark'] ?? 'Manual cancel recalculation',
    )));
    exit;
}

echo json_encode(array(
    'status' => 'error',
    'message' => 'Unsupported type for indentsconfirmation.php: ' . $type,
));
