<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.config.php';
$cfg = cyclone_get_db_config();
$conn = new mysqli($cfg['servername'], $cfg['username'], $cfg['password'], 'aurenyxgmp_medicap');
if ($conn->connect_error) {
    echo json_encode(array('err' => $conn->connect_error));
    exit;
}

$cols = array();
$r = $conn->query('SHOW COLUMNS FROM test_methods');
while ($r && ($c = $r->fetch_assoc())) {
    $cols[strtolower($c['Field'])] = $c['Field'];
}
$tid = 59;
$mRes = $conn->query("SELECT * FROM test_methods WHERE test_id='{$tid}' AND (spec_test_id IS NULL OR spec_test_id=0 OR spec_test_id='') ORDER BY id DESC LIMIT 1");
if (!$mRes || $mRes->num_rows === 0) {
    $mRes = $conn->query("SELECT * FROM test_methods WHERE test_id='{$tid}' ORDER BY id DESC LIMIT 1");
}
$test = null;
$tr = $conn->query("SELECT id, test, test_method_no, status, plant_id FROM test WHERE id={$tid}");
if ($tr && $tr->num_rows) {
    $test = $tr->fetch_assoc();
}
$method = ($mRes && $mRes->num_rows) ? $mRes->fetch_assoc() : null;
$procKey = isset($cols['procedure']) ? $cols['procedure'] : null;

echo json_encode(array(
    'ok' => ($test && $method) ? true : false,
    'test' => $test,
    'method_id' => isset($method['id']) ? $method['id'] : null,
    'procedure_col' => $procKey,
    'has_Test_Solutions' => isset($cols['test_solutions']),
    'has_chromatographic_conditions' => isset($cols['chromatographic_conditions']),
    'method_php_has_create_blank' => (strpos((string)@file_get_contents(__DIR__ . '/method.php'), 'create_blank_test_method_row') !== false),
), JSON_PRETTY_PRINT);
$conn->close();
