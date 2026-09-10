<?php
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);
$entry_time = date("H:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

$_GET['company_name'] = 'GMP Software Pvt Ltd';
$_GET['address'] = 'Location: Pune';
$_GET['logo'] = 'https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/user/gmp.png';

$servername = "162.241.149.225";
$username = "gmpsoftwareindia_admin";
$password = "Cpplgmp@123@2424";
$dbname = "gmpsoftwareindia_mastergmp";

if($_GET["plant_id"]>0){
     switch($_GET["plant_id"]){
         case 26:
         case 27:
         case 28:
         case 29:
         case 41:
         $dbname = "gmpsoftwareindia_mastergmp";
         break;
         case 43:
         $dbname = "gmpsoftwareindia_novo";
         break;
         case 57:
         $dbname = "gmpsoftwareindia_hamax";
         break;
         case 58:
         case 59:
         $dbname = "amardeepgmp_live";
         $username = "amardeep_gmp";
         $password = "cppl@2424";
         break;
         case 60:
         $username = "captalopharma_captol";
         $password = "Cpplgmp@123@2424";
         $dbname = "captalopharma_live";
         break;
     }
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/*$dbname="gmpsoftwareindia_admin";
$adminconn = new mysqli($servername, $username, $password, $dbname);
if ($adminconn->connect_error) {
    die("Connection failed: " . $adminconn->connect_error);
}*/
?>