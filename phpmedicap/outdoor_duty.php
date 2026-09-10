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
    
    
    if ($_GET["type"] == "get_Outdoor_duty") {
        $output = Array();
        $sql = "SELECT * FROM outdoor_duty";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveOutdoor_duty") {
        $sql = "INSERT INTO outdoor_duty(plant_id, emp_id, emp_name, department, designation, duty_details, checker, approver, from_date, to_date, total_days)VALUES('".$_GET['plant_id']."','".$input["emp_id"]."',
        '".$input["emp_name"]."','".$input["department"]."','".$input["designation"]."','".$input["duty_details"]."','".$input["checker"]."','".$input["approver"]."','".$input["from_date"]."','".$input["to_date"]."','".$input["total_days"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     
    
    else if ($_GET["type"] == "get_cabin") {
        $output = Array();
        $sql = "SELECT * FROM cabin_passage";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveCabin") {
        $sql = "INSERT INTO cabin_passage(plant_id, date, time, sweeping, table_chair_stool, cupboard, done_by, checked_by, remark)VALUES('".$_GET['plant_id']."','".$input["date"]."',
        '".$input["time"]."','".$input["sweeping"]."','".$input["table"]."','".$input["cupboard"]."','".$input["done_by"]."','".$input["checked_by"]."','".$input["remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    else if ($_GET["type"] == "get_bath") {
        $output = Array();
        $sql = "SELECT * FROM toilet_bathroom";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveBath") {
        $sql = "INSERT INTO toilet_bathroom(plant_id, date, time, staff, director, ladies, worker, done_by, checked_by, remark)VALUES('".$_GET['plant_id']."','".$input["date"]."',
        '".$input["time"]."','".$input["staff"]."','".$input["director"]."','".$input["ladies"]."','".$input["worker"]."','".$input["done_by"]."','".$input["checked_by"]."','".$input["remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    else if ($_GET["type"] == "get_factory") {
        $output = Array();
        $sql = "SELECT * FROM factory_primises";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveFactory") {
        $sql = "INSERT INTO factory_primises(plant_id, date, road, wall, drainage, done_by, checked_by, remark)VALUES('".$_GET['plant_id']."','".$input["date"]."',
        '".$input["road"]."','".$input["wall"]."','".$input["drainage"]."','".$input["done_by"]."','".$input["checked_by"]."','".$input["remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    
     else if ($_GET["type"] == "get_general") {
        $output = Array();
        $sql = "SELECT * FROM general_primises";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveGeneral") {
        $sql = "INSERT INTO general_primises(plant_id, date, ceilings, windows, doors, done_by, checked_by, remark)VALUES('".$_GET['plant_id']."','".$input["date"]."',
        '".$input["ceilings"]."','".$input["windows"]."','".$input["doors"]."','".$input["done_by"]."','".$input["checked_by"]."','".$input["remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    


}

$conn->close();
?>
    
