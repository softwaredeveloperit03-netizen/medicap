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

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveTransporter") {
        $user_no = isset($_GET["user_no"]) ? $_GET["user_no"] : "";
        if ($user_no === "" && isset($_GET["plant_id"])) {
            $user_no = $_GET["plant_id"];
        }
        $company = isset($input["company"]) ? $input["company"] : (isset($input["LglNm"]) ? $input["LglNm"] : "");
        $sql = "INSERT INTO transporter_management (user_no, company, person, address, pincode, contact_no, email, transport_for, entry_by, entry_date, LglNm, state_code, gst_type, gst_no, Trdnm, type, country, status) VALUES ('".$user_no."','".$company."','".$input["person"]."', '".$input["address"]."', '".$input["pincode"]."', '".$input["contact_no"]."', '".$input["email"]."', '".$input["transport_for"]."', '".$_GET["emp_id"]."', '$entry_date','".$input["LglNm"]."','".$input["state_code"]."','".$input["gst_type"]."','".$input["gst_no"]."','".$input["Trdnm"]."','".$input["type"]."','".$input["country"]."','pending')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingTransporters") {
        $output = array();
        // Admin/marketing approval: show all pending (also records saved before status was set)
        $sql = "SELECT * FROM transporter_management WHERE (status='pending' OR status IS NULL OR status='') ORDER BY id DESC";

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['state_name'] = '';
                if (!empty($row['state_code'])) {
                    $sql1 = "SELECT state_name FROM state WHERE state_code='".$row['state_code']."' LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1 && $result1->num_rows > 0) {
                        $row1 = $result1->fetch_assoc();
                        $row['state_name'] = $row1['state_name'];
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateTransporter") {
        $status = isset($_GET["status"]) ? $_GET["status"] : '';
        if ($status === 'approved') {
            $status = 'approve';
        }
        $sql = "UPDATE transporter_management SET status='".$status."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getTransportersLog") {
        $output = array();
        // Log: only approved entries (new saves stay pending until approval)
        $sql = "SELECT * FROM transporter_management WHERE (status='approve' OR status='approved') ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['state_code'])) {
                    $sql1 = "SELECT state_name FROM state WHERE state_code='".$row['state_code']."' LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1 && $result1->num_rows > 0) {
                        $row1 = $result1->fetch_assoc();
                        $row['state_name'] = $row1['state_name'];
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
}

$conn->close();
?>