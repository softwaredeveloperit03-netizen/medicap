<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "save_holidays") { 
            $sql = "Select * from mst_holidays where holiday_name = '".$input["holiday_name"]."' and plant_id =   '".$_GET["plant_id"]."'";
        
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Holiday Already Exists. Duplicate Values are not allowed\"}";
        }else{
            $date = DateTime::createFromFormat("Y-m-d", $input["holiday_date"]);
            $cal_year = $date->format("Y");
            $sql = 'INSERT INTO mst_holidays (plant_id,cal_year, holiday_date, holiday_name, entry_by, entry_date)
            VALUES  ("'.$_GET["plant_id"].'", "'.$cal_year.'", "'.$input["holiday_date"].'", 
            "'.$input["holiday_name"]. '", "'.$_GET["id"].'", "'.$entry_date.'")';
        	if($conn->query($sql)){
        		echo "{\"status\":\"success\"}";
        	} else {
        		echo "{\"status\":\"".$conn->error."\"}";
        	}
        }	
    	
    }else if ($_GET["type"] == "get_holidays_list") {
        $output = Array();
        $sql = "SELECT * FROM mst_holidays WHERE plant_id='".$_GET["plant_id"]."' order by holiday_date desc "; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    } 
}
$conn->close();
?>