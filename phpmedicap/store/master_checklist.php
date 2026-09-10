<?php
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set("Asia/Kolkata");
$input = json_decode(file_get_contents('php://input'), true);

$output = array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='" . $_GET["token"] . "'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }

    $txt = '{"process": "FRONTEND", "token": "' . $token . '", "action": "' . $_GET["type"] . '", "actiontime": "' . $entry_date . '", "department": "' . $_GET["department"] . '", "emp_id": "' . $_GET["emp_id"] . '", "method": "' . $_SERVER['REQUEST_METHOD'] . '", "REMOTE_ADDR": "' . $_SERVER['REMOTE_ADDR'] . '"}';
    $myfile = file_put_contents('../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

     
    
      if ($_GET["type"] == "savechecklist") {
  $sql = "INSERT INTO checklistKPI  ( plant_id,evualation_parameter,description,checklist_particulars,
  goal_type,version_no,checklist_heading ) VALUES 
  ( '".$_GET["plant_id"]."','".$input["evualation_parameter"]."','".$input["description"]."', '".$input["checklist_particulars"]."', '".$input["goal_type"]."', '".$input["version_no"]."', '".$input["checklist_heading"]."')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
       else if ($_GET["type"] == "gatemaster_checklist") {
        $output = array();
        $sql = "SELECT * from checklistKPI  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     
 
    
    
     
    
      else if ($_GET["type"] == "update_status") {
        $sql = "UPDATE new_team SET status='".$_GET["status"]."' WHERE department ='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
 
 
 

    
    
    
     else if ($_GET["type"] == "get_dept_approve") {
        $output = array();
        $sql = "SELECT DISTINCT a.id, a.*, a.designation AS desig, b.designation, b.emp_id AS b_id,d.firstname,d.lastname FROM apprisal a LEFT JOIN employee b 
        ON a.emp_id = b.emp_id WHERE  a.dept_head='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "save_apprisal") {
         
        $sql = "INSERT INTO apprisal( department,entry_date,emp_id,plant_id, achivement, apprisal_type, designation, promotion, Request_reason, 
                rise) VALUES ('".$_GET["department"]."','$entry_date','".$_GET["emp_id"]."','".$_GET["plant_id"]."','".$input["achivement"]."','".$input["apprisal_type"]."',
                '".$input["designation"]."',' ".$input["promotion"]."','".$input["Request_reason"]."',
                '".$input["rise"]."') ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    
         
     }
     else if ($_GET["type"] == "save_apprisal_dept") {
         
        $sql = "INSERT INTO apprisal( dept_head,department,entry_date,emp_id,plant_id, achivement, apprisal_type, designation, promotion, Request_reason, 
                rise) VALUES ('Approve','".$_GET["department"]."','$entry_date','".$input["employee"]."','".$_GET["plant_id"]."','".$input["achivement"]."','".$input["apprisal_type"]."',
                '".$input["designation"]."',' ".$input["promotion"]."','".$input["Request_reason"]."',
                '".$input["rise"]."') ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    
         
     }
     else if ($_GET["type"] == "update_dept_status") {
         
         $sql = "UPDATE apprisal SET dept_head='" . $_GET["status"] . "' where id='". $_GET["id"] ."'";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    
         
     }
}

$conn->close();
