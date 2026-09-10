<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];

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
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "getActivities") {
        $output = array();
        
        $sql = "SELECT * FROM laf WHERE equipment_code='M-02' AND entry_date=CURDATE()";
         
        $result = $conn->query($sql);
        if ($result->num_rows == 0) {
            $temp = array();
            $temp["equipment_code"] = "M-02";
            $temp["entry_date"] = $entry_date;
            $temp["status"] = "PENDING";
            $output[] = $temp;
        }
        
        $sql = "SELECT * FROM laf WHERE equipment_code='M-02' AND entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "startUVLight") {
        $sql = "INSERT INTO laf (equipment_code, before_uv_on, beforeUVstart_by, status, entry_date) VALUES ('M-02', '".$entry_time."', '".$_GET["emp_id"]."', 'BEFORE ACTIVITY UV ON', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "stopUVLight") {
        $sql = "UPDATE laf SET before_uv_off='".$entry_time."', beforeUVstop_by='".$_GET["emp_id"]."', status='BEFORE ACTIVITY UV OFF' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveActivity") {
        $sql = "UPDATE laf SET pressure='".$_GET["pressure"]."', activity_start='".$_GET["activity_start"]."', details='".$_GET["details"]."', activity_end='$entry_time', beforeUVstop_by='".$_GET["emp_id"]."', status='ACTIVTY COMPLETE' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "startAfterUVLight") {
        $sql = "UPDATE laf SET after_uv_on='".$entry_time."', afterUVstart_by='".$_GET["emp_id"]."', status='AFTER ACTIVITY UV ON' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "stopAfterUVLight") {
        $sql = "UPDATE laf SET after_uv_off='".$entry_time."', afterUVstop_by='".$_GET["emp_id"]."', status='AFTER ACTIVITY UV OFF' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>