<?php
require 'db.php';
require 'token.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if ($result->num_rows > 0) {
    
     while($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
        $string = explode("$",$string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }

    $sql = "INSERT INTO log(process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "saveOn_job") {
        $sql = "INSERT INTO onjob_training (plant_id,department,subject,training_on,equipment,equipment_id,description,proposed_trainer,proposed_date,employeeList,entry_by,entry_date)
        VALUES ('".$_GET["plant_id"]."','".$_GET["department"]."','".$input["subject"]."','".$input["training_on"]."','".$input["equipment"]."','".$input["equipment_id"]."',
        '".$input["description"]."','".$input["proposed_trainer"]."','".$input["proposed_date"]."','".json_encode($input["employeeList"])."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
            
    }
        else if ($_GET["type"] == "get_On_job") {
        $output = Array();
        $sql = "SELECT * FROM onjob_training";
        $result = $conn->query($sql);
         if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["employeeList"] = json_decode($row["employeeList"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "saveDocument") {
        $sql = "INSERT INTO document_training (plant_id,department,subject,training_on,equipment,equipment_id,description,proposed_trainer,proposed_date,employeeList,entry_by,entry_date)
        VALUES ('".$_GET["plant_id"]."','".$_GET["department"]."','".$input["subject"]."','".$input["training_on"]."','".$input["equipment"]."','".$input["equipment_id"]."',
        '".$input["description"]."','".$input["proposed_trainer"]."','".$input["proposed_date"]."','".json_encode($input["employeeList"])."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
            
    }
        else if ($_GET["type"] == "getDocument") {
        $output = Array();
        $sql = "SELECT * FROM document_training";
        $result = $conn->query($sql);
         if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["employees"] = json_decode($row["employees"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "saveNeed") {
        $sql = "INSERT INTO need_training (plant_id,department,subject,training_on,equipment,equipment_id,description,proposed_trainer,proposed_date,employeeList,entry_by,entry_date)
        VALUES ('".$_GET["plant_id"]."','".$_GET["department"]."','".$input["subject"]."','".$input["training_on"]."','".$input["equipment"]."','".$input["equipment_id"]."',
        '".$input["description"]."','".$input["proposed_trainer"]."','".$input["proposed_date"]."','".json_encode($input["employeeList"])."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
            
    }
        else if ($_GET["type"] == "getNeed") {
        $output = Array();
        $sql = "SELECT * FROM need_training";
        $result = $conn->query($sql);
         if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["employeeList"] = json_decode($row["employeeList"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "saveQms") {
        $sql = "INSERT INTO qms_training (plant_id,department,subject,training_on,equipment,equipment_id,description,proposed_trainer,proposed_date,employeeList,entry_by,entry_date)
        VALUES ('".$_GET["plant_id"]."','".$_GET["department"]."','".$input["subject"]."','".$input["training_on"]."','".$input["equipment"]."','".$input["equipment_id"]."',
        '".$input["description"]."','".$input["proposed_trainer"]."','".$input["proposed_date"]."','".json_encode($input["employeeList"])."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
            
    }
        else if ($_GET["type"] == "getQms") {
        $output = Array();
        $sql = "SELECT * FROM qms_training";
        $result = $conn->query($sql);
         if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["employeeList"] = json_decode($row["employeeList"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
}

$conn->close();
?>