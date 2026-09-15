<?php
/**
 * MRP Phase 9 smoke runner — Phases 1–8 read checks.
 * Usage: mrp/mrp_phase_smoke.php?type=runMrpPhaseSmoke&plant_id=1126&token=...
 * Optional: workorder_no=BO002&material_code=RM0129
 */
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../token.php';

$token = $_GET['token'] ?? '';
$entry_date = date('Y-m-d H:i:s');

function mrp_smoke_require_helper($relativeName) {
    $candidates = array(
        __DIR__ . '/../marketing/' . $relativeName,
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

mrp_smoke_require_helper('mrp_material_availability_helpers.php');
mrp_smoke_require_helper('mrp_indents_confirmation_helpers.php');
mrp_smoke_require_helper('mrp_dashboard_helpers.php');
mrp_smoke_require_helper('mrp_audit_log_helpers.php');
mrp_smoke_require_helper('mrp_cancel_recalc_helpers.php');
mrp_smoke_require_helper('mrp_planning_horizon_helpers.php');
mrp_smoke_require_helper('mrp_line_booking_history_helpers.php');
mrp_smoke_require_helper('mrp_traceability_helpers.php');
mrp_smoke_require_helper('mrp_phase_smoke_helpers.php');

$type = $_GET['type'] ?? 'runMrpPhaseSmoke';
if ($type === 'runMrpPhaseSmoke' || $type === '') {
    echo json_encode(gw_run_mrp_phase_smoke($conn, array(
        'plant_id' => $_GET['plant_id'] ?? '1126',
        'workorder_no' => $_GET['workorder_no'] ?? 'BO002',
        'material_code' => $_GET['material_code'] ?? 'RM0129',
    )));
    exit;
}

echo json_encode(array(
    'status' => 'error',
    'message' => 'Unsupported type for mrp_phase_smoke.php: ' . $type,
));
