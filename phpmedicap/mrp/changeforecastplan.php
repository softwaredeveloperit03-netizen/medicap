<?php
/**
 * Medicap Change Forecast Plan API — standalone (no marketing/po.php).
 * Frontend: mrp/changeforecastplan.php?type=...
 *
 * Must upload WITH full helpers (not empty files):
 *   marketing/change_forecast_plan_helpers.php
 *   marketing/mrp_audit_log_helpers.php
 */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/changeforecastplan-error.log');

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

function mrp_cfp_helper_candidates($relativeName) {
    return array(
        __DIR__ . '/../marketing/' . $relativeName,
        dirname(__DIR__) . '/../phpzuma/marketing/' . $relativeName,
        dirname(dirname(__DIR__)) . '/phpzuma/marketing/' . $relativeName,
    );
}

function mrp_cfp_require_helper($relativeName) {
    foreach (mrp_cfp_helper_candidates($relativeName) as $path) {
        if (is_file($path) && @filesize($path) > 50) {
            require_once $path;
            return $path;
        }
    }
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Missing or empty helper: marketing/' . $relativeName
            . ' — upload the FULL file to phpmedicap/marketing/.',
        'looked_in' => mrp_cfp_helper_candidates($relativeName),
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

$type = $_GET['type'] ?? '';

if ($type === 'diag') {
    $files = array('change_forecast_plan_helpers.php', 'mrp_audit_log_helpers.php');
    $report = array();
    foreach ($files as $f) {
        $entry = array('name' => $f, 'candidates' => array());
        foreach (mrp_cfp_helper_candidates($f) as $path) {
            $entry['candidates'][] = array(
                'path' => $path,
                'exists' => is_file($path),
                'bytes' => is_file($path) ? (int)@filesize($path) : 0,
            );
        }
        $report[] = $entry;
    }
    // Try load for function check
    foreach ($files as $f) {
        foreach (mrp_cfp_helper_candidates($f) as $path) {
            if (is_file($path) && @filesize($path) > 50) {
                @include_once $path;
                break;
            }
        }
    }
    echo json_encode(array(
        'status' => 'success',
        'php' => PHP_VERSION,
        'helpers' => $report,
        'functions' => array(
            'gw_cfp_get_processed_plans' => function_exists('gw_cfp_get_processed_plans'),
            'gw_get_mrp_audit_log' => function_exists('gw_get_mrp_audit_log'),
        ),
    ));
    exit;
}

try {
    mrp_cfp_require_helper('mrp_audit_log_helpers.php');
    $cfpLoaded = mrp_cfp_require_helper('change_forecast_plan_helpers.php');

    if (!function_exists('gw_cfp_get_processed_plans')) {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'Helper loaded but gw_cfp_get_processed_plans() missing. Re-upload full change_forecast_plan_helpers.php',
            'loaded' => $cfpLoaded,
        ));
        exit;
    }

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
        $rows = function_exists('gw_get_mrp_audit_log')
            ? gw_get_mrp_audit_log($conn, $filters)
            : array();
        echo json_encode(array(
            'status' => 'success',
            'rows' => $rows,
            'total' => count($rows),
        ));
        exit;
    }

    if ($type === 'getChangeForecastPlans') {
        $plantId = $_GET['plant_id'] ?? '';
        $filters = array(
            'order_no' => $_GET['order_no'] ?? '',
            'product_code' => $_GET['product_code'] ?? '',
            'search' => $_GET['search'] ?? '',
            'page' => $_GET['page'] ?? 1,
            'pageSize' => $_GET['pageSize'] ?? 25,
        );
        $resultPlans = gw_cfp_get_processed_plans($conn, $plantId, $filters);
        echo json_encode(array(
            'status' => 'success',
            'plans' => isset($resultPlans['plans']) ? $resultPlans['plans'] : array(),
            'total' => isset($resultPlans['total']) ? $resultPlans['total'] : 0,
            'page' => isset($resultPlans['page']) ? $resultPlans['page'] : 1,
            'pageSize' => isset($resultPlans['pageSize']) ? $resultPlans['pageSize'] : 25,
            'totalPages' => isset($resultPlans['totalPages']) ? $resultPlans['totalPages'] : 0,
            'changeable_on_page' => isset($resultPlans['changeable_on_page']) ? $resultPlans['changeable_on_page'] : 0,
        ));
        exit;
    }

    if ($type === 'getClubbableForecast') {
        $plantId = $_GET['plant_id'] ?? '';
        $locks = gw_cfp_compute_plan_locks(
            $conn,
            $_GET['order_no'] ?? '',
            $_GET['product_code'] ?? '',
            $_GET['month'] ?? '',
            $_GET['year'] ?? '',
            $plantId
        );
        $club = gw_cfp_get_clubbable_forecast(
            $conn,
            $_GET['order_no'] ?? '',
            $_GET['product_code'] ?? '',
            $_GET['month'] ?? '',
            $_GET['year'] ?? '',
            $plantId,
            (int)$locks['split_id']
        );
        echo json_encode(array(
            'status' => 'success',
            'locks' => $locks,
            'clubbable' => $club['rows'],
            'clubbable_total' => $club['total'],
        ));
        exit;
    }

    if ($type === 'changeForecastPlan') {
        $payload = $input;
        if (empty($payload['plant_id']) && !empty($_GET['plant_id'])) {
            $payload['plant_id'] = $_GET['plant_id'];
        }
        echo json_encode(gw_cfp_change_plan($conn, $payload, array(
            'emp_id' => $_GET['emp_id'] ?? '',
            'department' => $_GET['department'] ?? '',
        )));
        exit;
    }

    if ($type === 'getForecastChangeLog') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
        $rows = gw_cfp_get_change_log($conn, $limit, $_GET['plant_id'] ?? '');
        echo json_encode(array('status' => 'success', 'rows' => $rows, 'total' => count($rows)));
        exit;
    }

    echo json_encode(array(
        'status' => 'error',
        'message' => 'Unsupported type for changeforecastplan.php: ' . $type,
    ));
} catch (Throwable $e) {
    @file_put_contents(
        __DIR__ . '/changeforecastplan-error.log',
        date('c') . ' ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL,
        FILE_APPEND
    );
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
    ));
}
