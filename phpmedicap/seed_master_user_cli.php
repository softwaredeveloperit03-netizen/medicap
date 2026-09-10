<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}
$_GET['key'] = 'MedicapSeed1126';
$_GET['plant_id'] = isset($argv[1]) ? $argv[1] : '1126';
require __DIR__ . '/deploy_seed_master_user.php';
