<?php
/**
 * Clear login lockout for Master (and alias) — Medicap plant 1126.
 * GET: ?key=MedicapSeed1126&plant_id=1126
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$key = isset($_GET['key']) ? $_GET['key'] : '';
if ($key !== 'MedicapSeed1126') {
    http_response_code(403);
    echo json_encode(array('status' => 'error', 'message' => 'Forbidden'));
    exit;
}

$plantId = isset($_GET['plant_id']) ? trim($_GET['plant_id']) : '1126';

require_once __DIR__ . '/db.config.php';
$cfg = cyclone_get_db_config();
$dbname = cyclone_resolve_dbname($plantId);
$conn = new mysqli($cfg['servername'], $cfg['username'], $cfg['password'], $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(array('status' => 'error', 'message' => $conn->connect_error));
    exit;
}

$cols = array();
$cr = $conn->query('SHOW COLUMNS FROM employee');
while ($cr && ($c = $cr->fetch_assoc())) {
    $cols[$c['Field']] = true;
}

$updates = array();
if (isset($cols['login_fail_count'])) {
    $updates[] = 'login_fail_count=0';
}
if (isset($cols['login_locked_until'])) {
    $updates[] = 'login_locked_until=NULL';
}

if (!$updates) {
    echo json_encode(array('status' => 'error', 'message' => 'No lockout columns on employee'));
    exit;
}

$sql = "UPDATE employee SET " . implode(', ', $updates) .
    " WHERE plant_id='" . $conn->real_escape_string($plantId) . "'" .
    " AND (emp_id='Master' OR emp_id='master' OR emp_id='M001' OR emp_id1='M001')";
$ok = $conn->query($sql);
$affected = $conn->affected_rows;

$verify = array();
$vr = $conn->query(
    "SELECT emp_id, status, login_fail_count, login_locked_until, mpin FROM employee " .
    "WHERE plant_id='" . $conn->real_escape_string($plantId) . "' " .
    "AND (emp_id='Master' OR emp_id='M001') ORDER BY emp_id"
);
while ($vr && ($row = $vr->fetch_assoc())) {
    $verify[] = $row;
}

echo json_encode(array(
    'status' => $ok ? 'success' : 'error',
    'affected' => $affected,
    'employees' => $verify,
), JSON_PRETTY_PRINT);
$conn->close();
