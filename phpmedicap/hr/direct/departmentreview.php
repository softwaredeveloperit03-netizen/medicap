<?php
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    if ($_GET["type"] == "saveDeptreview") {
        $sql = "INSERT INTO department_review (department, emp_name, emp_id, joining_date, year_service, designation, last_date, year_appraisal, result, special_appraisal, request_appraisal, head_recomded, assign, entry_by, entry_date) VALUES ('".$input["department"]."', '".$input["emp_name"]."', '".$input["emp_id"]."', '".$input["joining_date"]."', '".$input["year_service"]."','".$input["designation"]."','".$input["last_date"]."','".$input["year_appraisal"]."','".$input["result"]."','".$input["special_appraisal"]."','".$input["request_appraisal"]."','".$input["head_recomded"]."','".$input["assign"]."','".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) { 
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "getDeptreview") {
        $output = array();
        $sql = "SELECT * FROM department_review ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    }

$conn->close();
?>