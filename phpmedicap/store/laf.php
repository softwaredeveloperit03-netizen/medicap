<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
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

    if ($_GET["type"] == "getRLAFRecords") {
        $output = array();
       
        $sql = "SELECT * FROM equipment WHERE equipment_name='Reverse Laminar Air Flow Unit' AND department='Store' AND equipment_code NOT IN (SELECT equipment_code FROM rlaf_usages WHERE entry_date=CURDATE() AND status !='STOP')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["status"] = 'PENDING';
                $row["entry_date"] = date("Y-m-d", $timestamp);
                $row["start_time"] = "";
                $row["stop_time"] = "";
                $row["start_by"] = "";
                $row["stop_by"] = "";
                $output[] = $row;
            }
        }
        $sql = "SELECT r.*,e.firstname,e1.firstname as stop_name FROM rlaf_usages r LEFT JOIN employee e ON r.start_by=e.emp_id LEFT JOIN employee e1 ON r.stop_by=e1.emp_id  WHERE r.department='Store' AND DATE(r.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "startRLAF") {
        $start_time = date("H:i:s", $timestamp);
        $sql = "INSERT INTO rlaf_usages (department, section, equipment_code, entry_date, status, start_time, start_by) VALUES ('".$input["department"]."', '".$input["location"]."', '".$input["equipment_code"]."', '".$entry_date."', 'START', '".$input["start_time"]."', '".$_GET["emp_id"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "stopRLAF") {
        $stop_time = date("H:i:s", $timestamp);
        $sql = "UPDATE rlaf_usages SET stop_time='".$_GET["stop_time"]."', stop_by='".$_GET["emp_id"]."', status='STOP' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

}

$conn->close();
?>