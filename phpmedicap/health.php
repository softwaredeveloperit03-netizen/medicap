<?php
/**
 * Medicap API + DB connectivity check.
 * GET https://aurenyxgmp.com/php/phpdevlop/phpmedicap/health.php?plant_id=1126
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.config.php';

$plantId = trim((string)($_GET['plant_id'] ?? '1126'));
$cfg = cyclone_get_db_config();
$dbName = cyclone_resolve_dbname($plantId);

$out = array(
    'status' => 'error',
    'profile' => $cfg['profile'] ?? 'unknown',
    'db' => $dbName,
    'plant_id' => $plantId,
    'server' => isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '',
    'api_base' => isset($cfg['public_base_url']) ? $cfg['public_base_url'] : '',
    'checks' => array(),
);

$conn = @new mysqli($cfg['servername'], $cfg['username'], $cfg['password'], $dbName);
if ($conn->connect_error) {
    $out['message'] = 'Database connection failed';
    $out['checks']['db'] = $conn->connect_error;
    echo json_encode($out);
    exit;
}

$out['checks']['db'] = 'ok';
$res = @$conn->query('SELECT 1 AS ok');
$out['checks']['query'] = ($res && $res->num_rows > 0) ? 'ok' : 'fail';

$plantRes = @$conn->query("SELECT plant_id, plant_name FROM plant WHERE plant_id='" . $conn->real_escape_string($plantId) . "' LIMIT 1");
if ($plantRes && $plantRes->num_rows > 0) {
    $row = $plantRes->fetch_assoc();
    $out['plant_name'] = $row['plant_name'] ?? '';
    $out['checks']['plant'] = 'ok';
} else {
    $out['checks']['plant'] = 'missing';
}

$out['checks']['checkLogin'] = file_exists(__DIR__ . '/checkLogin.php') ? 'ok' : 'missing';
$out['checks']['login_client'] = file_exists(__DIR__ . '/login_client.php') ? 'ok' : 'missing';
$out['checks']['auth_helper'] = file_exists(__DIR__ . '/shared/auth_helper.php') ? 'ok' : 'missing';

$out['status'] = ($out['checks']['db'] === 'ok' && $out['checks']['query'] === 'ok') ? 'ok' : 'error';
$conn->close();
echo json_encode($out);
