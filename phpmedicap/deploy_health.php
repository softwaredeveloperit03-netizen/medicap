<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$out = array('ok' => false);
try {
    require_once __DIR__ . '/db.config.php';
    $cfg = cyclone_get_db_config();
    $out['profile'] = $cfg['profile'];
    $out['dbname'] = $cfg['dbname'];
    $out['username'] = $cfg['username'];
    $out['servername'] = $cfg['servername'];

    $conn = @new mysqli($cfg['servername'], $cfg['username'], $cfg['password'], $cfg['dbname']);
    if ($conn->connect_error) {
        $out['db_error'] = $conn->connect_error;
        echo json_encode($out, JSON_PRETTY_PRINT);
        exit;
    }
    $out['db_ok'] = true;
    $out['db_host_info'] = $conn->host_info;

    require_once __DIR__ . '/schema_tables.php';
    $out['schema'] = medicap_ensure_schema($conn);

    $tables = array();
    foreach (array('chemical', 'glassware', 'others_material', 'chem_manufaturer') as $t) {
        $r = $conn->query("SHOW TABLES LIKE '".$t."'");
        $tables[$t] = ($r && $r->num_rows > 0) ? 'exists' : 'missing';
    }
    $out['tables'] = $tables;
    $out['ok'] = true;
    $conn->close();
} catch (Throwable $e) {
    $out['exception'] = $e->getMessage();
}
echo json_encode($out, JSON_PRETTY_PRINT);
