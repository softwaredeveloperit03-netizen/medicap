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
    
    if ($_GET["type"] == "saveDoseUnit") {
        $sql="SELECT * FROM dose_units WHERE dose_unit_name='".$_GET["dose_unit"]."' AND plant_id ='".$_GET["plant_id"]."' ";
        
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Unit Already Exists. Duplicate Values are not allowed\"}";
        }
        else {
        $sql = "INSERT INTO dose_units (plant_id,dose_unit_name,entry_by,entry_date) VALUES ('".$_GET["plant_id"]."','".$_GET["dose_unit"]."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        }
    } else if ($_GET["type"] == "get_dose_units") {
          $output = array();
        $sql = "SELECT * FROM dose_units where plant_id='".$_GET["plant_id"]."' order by dose_unit_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                
            }
        }
    echo json_encode($output);
       
    }
    else if ($_GET["type"] == "saveDoseUnit1") {
        $sql="SELECT * FROM dose_units WHERE dose_unit_name='".$_GET["dose_unit"]."' AND plant_id ='".$_GET["plant_id"]."' ";
        
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Unit Already Exists. Duplicate Values are not allowed\"}";
        }
        else {
        $sql = "INSERT INTO dose_units1 (plant_id,dose_unit_name,entry_by,entry_date) VALUES ('".$_GET["plant_id"]."','".$_GET["dose_unit"]."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        }
    } else if ($_GET["type"] == "get_dose_units1") {
          $output = array();
        $sql = "SELECT * FROM dose_units where plant_id='".$_GET["plant_id"]."' order by dose_unit_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                
            }
        }
    echo json_encode($output);
       
    }

}

$conn->close();
?>