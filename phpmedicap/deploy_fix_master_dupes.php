<?php
/**
 * Deduplicate Master employee rows for plant 1126.
 * Keeps the lowest id Master row; deactivates others and clears their mpin.
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

$plant = $conn->real_escape_string($plantId);
$before = array();
$r = $conn->query("SELECT id, emp_id, emp_id1, status, mpin, login_fail_count, login_locked_until FROM employee WHERE plant_id='$plant' AND (emp_id='Master' OR emp_id='master' OR emp_id='M001') ORDER BY id ASC");
while ($r && ($row = $r->fetch_assoc())) {
    $before[] = $row;
}

$keepers = array();
$deactivated = array();
foreach ($before as $row) {
    $eid = strtolower(trim((string)$row['emp_id']));
    if ($eid === 'master') {
        if (!isset($keepers['master'])) {
            $keepers['master'] = $row;
            // Ensure primary Master is active with PIN + unlocked
            $id = (int)$row['id'];
            $conn->query("UPDATE employee SET status='active', mpin='111111', emp_id1='M001', login_fail_count=0, login_locked_until=NULL, password='Master@47#' WHERE id=$id");
        } else {
            $id = (int)$row['id'];
            $conn->query("UPDATE employee SET status='inactive', mpin='', login_fail_count=0, login_locked_until=NULL WHERE id=$id");
            $deactivated[] = $id;
        }
    } elseif ($eid === 'm001') {
        // Alias row must not compete for PIN login
        $id = (int)$row['id'];
        $conn->query("UPDATE employee SET status='inactive', mpin='', login_fail_count=0, login_locked_until=NULL WHERE id=$id");
        $deactivated[] = $id;
    }
}

$after = array();
$r2 = $conn->query("SELECT id, emp_id, emp_id1, status, mpin, login_fail_count, login_locked_until FROM employee WHERE plant_id='$plant' AND (emp_id='Master' OR emp_id='master' OR emp_id='M001' OR emp_id1='M001') ORDER BY id ASC");
while ($r2 && ($row = $r2->fetch_assoc())) {
    $after[] = $row;
}

$activePin = array();
$r3 = $conn->query("SELECT id, emp_id, status, mpin FROM employee WHERE plant_id='$plant' AND status='active' AND TRIM(IFNULL(mpin,''))='111111'");
while ($r3 && ($row = $r3->fetch_assoc())) {
    $activePin[] = $row;
}

echo json_encode(array(
    'status' => 'success',
    'before' => $before,
    'deactivated_ids' => $deactivated,
    'after' => $after,
    'active_with_pin_111111' => $activePin,
), JSON_PRETTY_PRINT);
$conn->close();
