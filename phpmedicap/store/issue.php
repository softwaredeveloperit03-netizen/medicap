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
    while($row = $result->fetch_assoc()) {
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveRawMaterialIssue") {
        $sql = "INSERT INTO material_issue (material_type, material_subtype, material_code, batch_no, issue_for, qty, unit, entry_by, entry_date) VALUES ('Raw Material', '".$input["material_type"]."', '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["issue_for"]."', '".$input["issue_qty"]."', '".$input["unit"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingRawIssue") {
        $output = Array();
        $sql = "SELECT * FROM material_issue WHERE status='pending' AND material_type='Raw Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_type"] = $row1["material_type"];
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getRawIssueLog") {
        $output = Array();
        $sql = "SELECT * FROM material_issue WHERE material_type='Raw Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_type"] = $row1["material_type"];
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateRawIssue") {
        $sql = "UPDATE material_issue SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "savePackingMaterialIssue") {
        $sql = "INSERT INTO material_issue (material_type, material_subtype, material_code, batch_no, issue_for, qty, unit, entry_by, entry_date) VALUES ('Packing Material', '".$input["material_type"]."', '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["issue_for"]."', '".$input["issue_qty"]."', '".$input["unit"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingPackingIssue") {
        $output = Array();
        $sql = "SELECT * FROM material_issue WHERE status='pending' AND material_type='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_type"] = $row1["material_type"];
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updatePackingIssue") {
        $sql = "UPDATE material_issue SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPackingIssueLog") {
        $output = Array();
        $sql = "SELECT * FROM material_issue WHERE material_type='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_type"] = $row1["material_type"];
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
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