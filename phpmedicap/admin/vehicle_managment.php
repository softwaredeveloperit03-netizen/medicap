<?php
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
 $currentUrl =$_GET["description"];



$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveVehiclManagement") {
        
         $sql = "INSERT INTO vehicle_management (user_no,agent_no,entry_date,end_date,email_id,vehicle_category,vehicle_type,vehicle_model,make_company,colour,engine_no,chassis_no,registration_no,purpose_vehicle,insurance_type)
         VALUES ('".$_GET["user_no"]."','".$input["agent_no"]."','".$input["entry_date"]."', '".$input["end_date"]."', '".$input["email"]."', '".$input["vehicle_category"]."', '".$input["vehicle_type"]."', '".$input["vehicle_model"]."', '".$input["make_company"]."','".$input["colour"]."','".$input["engine_no"]."','".$input["chassis_no"]."','".$input["registration_no"]."','".$input["purpose_vehicle"]."','".$input["insurance_type"]."')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
   else if($_GET['type'] == 'getVehiclManagementlog'){
        
        $sql = "SELECT * FROM vehicle_management";
    	$result = $conn->query($sql);
    	$data = array();
    	if($result-> num_rows > 0) {
    		while($row = $result-> fetch_assoc()) {
     
    		    $data[] = $row;
    		}
    	}
    	echo json_encode($data);
    }
}

$conn->close();
?>