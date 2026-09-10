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


if ($_GET["type"] == "saveTheroticalTest") {
    $sql = "INSERT INTO therotical_test (user_no, test_type, test_name, equipment_code, activity_description, questions, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["test_type"]."', '".$input["test_name"]."', '".$input["equipment_code"]."', '".$input["activity"]."', '".json_encode($input["questions"])."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingTheroticalTests") {
    $output = Array();
    $sql = "SELECT t.*, e.equipment_name, e.capacity, e.make, e.department FROM therotical_test t LEFT JOIN equipment e ON t.equipment_code=e.equipment_code WHERE t.user_no='".$_GET["user_no"]."' AND t.status='pending'";
    $result = $conn->query($sql);
    if ($conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $row["questions"] = json_decode($row["questions"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateTheroticalTest") {
    $sql = "UPDATE therotical_test SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getTheroticalTestLog") {
    $output = Array();
    $sql = "SELECT t.*, e.equipment_name, e.capacity, e.make, e.department FROM therotical_test t LEFT JOIN equipment e ON t.equipment_code=e.equipment_code WHERE t.user_no='".$_GET["user_no"]."'";
    $result = $conn->query($sql);
    if ($conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $row["questions"] = json_decode($row["questions"]);
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