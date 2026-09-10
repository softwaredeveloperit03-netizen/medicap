<?php

header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);
$entry_time = date("H:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

$_GET['logo'] = 'https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/user/wastcost.png';

$servername = "localhost";
$username = "aurenyxgmp_admin";
$password = "Cppl@1979";
$dbname = "aurenyxgmp_admin";

$conn = new MySQLi($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>