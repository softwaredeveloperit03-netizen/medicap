<?php
/**
 * Apply SQL patches from backend/database/ (local development helper).
 * URL: setup_apply_patch.php?type=applyPatch&patch=store_raw_receiving_checklist_patch
 */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

$host = isset($_SERVER['SERVER_NAME']) ? strtolower($_SERVER['SERVER_NAME']) : 'localhost';
$isLocal = ($host === 'localhost' || $host === '127.0.0.1' || strpos($host, '192.168.') === 0);

if (!$isLocal && (!isset($_GET['allow_server']) || $_GET['allow_server'] !== 'yes')) {
    echo json_encode(array(
        'status' => 'failed',
        'error'  => 'Patch runner is restricted to local PHP. Add allow_server=yes on server only if intended.',
    ));
    exit;
}

require_once __DIR__ . '/db.php';
$dbCfg = cyclone_get_db_config();

$type = isset($_GET['type']) ? $_GET['type'] : '';
if ($type !== 'applyPatch') {
    echo json_encode(array(
        'status' => 'failed',
        'error'  => 'Use type=applyPatch&patch=<filename_without_sql>',
        'db_profile' => isset($dbCfg['profile']) ? $dbCfg['profile'] : 'unknown',
    ));
    exit;
}

$patchName = isset($_GET['patch']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['patch']) : '';
if ($patchName === '') {
    echo json_encode(array('status' => 'failed', 'error' => 'patch parameter is required'));
    exit;
}

$sqlFile = realpath(__DIR__ . '/../../database/' . $patchName . '.sql');
$dbRoot = realpath(__DIR__ . '/../../database');
if ($sqlFile === false || strpos($sqlFile, $dbRoot) !== 0 || !is_file($sqlFile)) {
    echo json_encode(array('status' => 'failed', 'error' => 'Patch file not found: ' . $patchName . '.sql'));
    exit;
}

$sqlContent = file_get_contents($sqlFile);
if ($sqlContent === false || trim($sqlContent) === '') {
    echo json_encode(array('status' => 'failed', 'error' => 'Patch file is empty'));
    exit;
}

$statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sqlContent)));
$applied = 0;
$errors = array();

foreach ($statements as $statement) {
    if ($statement === '' || strpos($statement, '--') === 0) {
        continue;
    }
    if (!$conn->query($statement)) {
        $errors[] = $conn->error;
    } else {
        $applied++;
    }
}

if (count($errors) > 0) {
    echo json_encode(array(
        'status' => 'failed',
        'applied' => $applied,
        'errors' => $errors,
        'db_profile' => $dbCfg['profile'],
        'patch' => $patchName,
    ));
    exit;
}

echo json_encode(array(
    'status' => 'success',
    'applied' => $applied,
    'db_profile' => $dbCfg['profile'],
    'patch' => $patchName,
    'message' => 'Database patch applied successfully',
));
