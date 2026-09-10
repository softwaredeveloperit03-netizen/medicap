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
    
    if ($_GET["type"] == "getRequests") {
        $output = Array();
        $sql = "SELECT p.*, e.emp_name, e.department, e.designation FROM password p LEFT JOIN employee e ON p.emp_id=e.emp_id WHERE p.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getRequestsLog") {
        $output = Array();
        $sql = "SELECT p.*, e.emp_name, e.department, e.designation FROM password p LEFT JOIN employee e ON p.emp_id=e.emp_id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateRequest") {
        $sql = "UPDATE password SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            $new_password = "";
            $emp_id = "";
            $sql = "SELECT * FROM password WHERE id='".$_GET["id"]."' AND status='approve' ORDER BY id DESC LIMIT 1";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                $emp_id = $row["emp_id"];
                $new_password = $row["new_password"];
            }
            
            $password = encrypt('encrypt', $new_password);
            $sql = "UPDATE employee SET emp_password='$password' WHERE emp_id='$emp_id'";
            $conn->query($sql);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
}

$conn->close();
?>