<?php
require '../db.php';
require '../token.php';
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
    
    if ($_GET["type"] == "saveSpareIndend") {
        $flag = 0;
        for($i = 0; $i < count($input); $i++) {
            $data = $input[$i];
            $sql = "INSERT INTO spare_indend (user_no, equipment_code, part_name, requirement, qty, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$data["equipment_code"]."', '".$data["part_name"]."', '".$data["requirement"]."', '".$data["qty"]."', '".$_GET["emp_id"]."', '$entry_date')";
            if ($conn->query($sql)) {
                $flag = 0;
            } else {
                $flag = 1;
                break;
            }
        }
        if ($flag == 0) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getPendingSpareIndends") {
        $output = array();
        $sql = "SELECT s.*, e.equipment_name FROM spare_indend s LEFT JOIN equipment e ON s.equipment_code=e.equipment_code WHERE s.user_no='".$_GET["user_no"]."' AND s.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "checkSpareIndend") {
        $sql = "UPDATE spare_indend SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getSpareIndendLog") {
        $sql = "SELECT s.*, e.equipment_type, e.equipment_name, DATE(s.entry_date) as entry_date FROM spare_indend s LEFT JOIN equipment e ON s.equipment_code=e.equipment_code WHERE s.user_no='".$_GET["user_no"]."' AND DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getSparesStock") {
        $output = array();
        $sql = "SELECT s.*, e.equipment_type, e.equipment_name, DATE(s.entry_date) as entry_date FROM spare_stock s LEFT JOIN equipment e ON s.equipment_code=e.equipment_code WHERE s.user_no='".$_GET["user_no"]."' AND DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>