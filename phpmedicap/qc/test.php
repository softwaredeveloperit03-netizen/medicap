<?php 
require '../db.php';
require '../token.php';
$output = Array();
$token = $_GET["token"];
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
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "saveTest") {
        $subtests = $input["subtest"];
        for ($i = 0; $i < count($subtests); $i++) {
            $subtest = $subtests[$i];
            $subtest['status'] = 'pending';
            $subtest['entry_by'] = $_GET["emp_id"];
            $subtest['entry_date'] = $entry_date;
            $subtests[$i] = $subtest;
        }
        $sql = "INSERT INTO tests (test, subtest, entry_by, entry_date) VALUES ('".$input["test"]."', '".json_encode($input["subtest"])."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingTests") {
        $output = Array();
        $sql = "SELECT * FROM tests WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["subtest"] = json_decode($row["subtest"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateTest") {
        $sql = "UPDATE tests SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    else if ($_GET["type"] == "approve_method") {
        
        $sql = "update test set method='".$_GET["status"]."' where test_method_no='".$_GET["method_no"]."' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
     else if ($_GET["type"] == "gethplc") {
        $output = Array();
        $sql = "SELECT * FROM hplc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["subtest"] = json_decode($row["subtest"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "savehplc") {
        
     $sql = "INSERT INTO hplc (, receipt_date, ColumnDescription, serial_no, make, column_id) 
            VALUES ('".$input["receipt_date"]."','".$input["ColumnDescription"]."','".$input["serial_no"]."'
            ,'".$input["make"]."', '".$input["column_id"]."')";
    	   
        if ($conn->query($sql))
        
        {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
    else if ($_GET["type"] == "getTestsLog") {
        $output = Array();
        $sql = "SELECT * FROM tests";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["subtest"] = json_decode($row["subtest"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

}

$conn->close();
?>