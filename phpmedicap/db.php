<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

date_default_timezone_set("Asia/Kolkata");
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);
$entry_time = date("H:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'), true);

require_once __DIR__ . '/db.config.php';
$dbCfg = cyclone_get_db_config();
$appSettings = cyclone_get_app_settings();

$_GET['company_name'] = $appSettings['company_name'];
$_GET['address'] = $appSettings['address'];
$_GET['logo'] = $appSettings['logo_url'];

$servername = $dbCfg['servername'];
$username = $dbCfg['username'];
$password = $dbCfg['password'];

$plantid = isset($_GET['plant_id']) ? $_GET['plant_id'] : '';
$dbname = cyclone_resolve_dbname($plantid);

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
