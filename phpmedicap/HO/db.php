<?php

header("Access-Control-Allow-Origin: *"); // Or restrict to Angular's origin
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");


date_default_timezone_set("Asia/Kolkata");
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);
$entry_time = date("H:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

$_GET['company_name'] = 'GMP Software Pvt Ltd';
$_GET['address'] = 'Location: Pune';
$_GET['logo'] = 'https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/user/gmp.png';


// $servername = "162.214.204.236";
 
$servername = "localhost";
$username = "paperlessgmp_admin";
$password = "Cyclone@1979";


    

$plantid = $_GET['plantId'];
 

switch ($plantid) {
    case 181:
        $dbname = "paperlessgmp_pritam";
        
        break;
    case 182:
        $dbname = "paperlessgmp_oneAsia";
        break;
    case 183:
        $dbname = "paperlessgmp_sai";
        break;
    case 180:
        $dbname = "paperlessgmp_wonderho";
        break;
    case 142:
        $dbname = "paperlessgmp_cyclone";
        break;
    default:
        $dbname = "NA";
        break;
}
  
$conn = new mysqli($servername, $username, $password, $dbname);
  
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



// function decrypt1($action, $string, $secret_key, $secret_iv) {
//     $output = false;
//     $encrypt_method = "AES-256-CBC";
//     $key = hash('sha256', $secret_key);
//     $iv = substr(hash('sha256', $secret_iv), 0, 16);
//     if( $action == 'decrypt' ) {
//         $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
//     }
//     return $output;
// }



//     $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
//     $result = $conn->query($sql);
//     $_GET["plant_id"] = "";
//     $_GET["user_no"] = "gmpdemo1";
//     if($result->num_rows > 0){
//         while($row = $result->fetch_assoc()){
//     	    $string = decrypt1('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
//     	    $string = explode("$",$string);
//     	    //   $_GET["plant_id"] = $string[3];
//     	    break;
//         }
//     }







?>