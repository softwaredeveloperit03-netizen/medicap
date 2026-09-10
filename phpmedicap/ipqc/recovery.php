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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    
    if ($_GET["type"] == "getTechnicalLog") {
        $output = array();
        $sql = "SELECT t.*, m.material_name, m.grade FROM recovery_inprocess t LEFT JOIN material m ON t.material_code=m.material_code  ORDER BY t.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["tests"] = json_decode($row["tests"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getInprocessTechnicalLog") {
        $output = array();
        $sql = "SELECT t.*, m.material_name, m.grade FROM recovery_inprocess t LEFT JOIN material m ON t.material_code=m.material_code WHERE t.status='pending' ORDER BY t.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no=(SELECT specification_no FROM specification WHERE spec_type='Raw Material Specification' AND material_code='".$row["material_code"]."')";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                    $row["tests"] = $output1;
                    $row["isspecification"] = "yes";
                } else {
                    $row["isspecification"] = "no";
                }
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveTestingReport") {
        $ar_no = "RI00".$input["id"];
        $sql = "UPDATE recovery_inprocess SET tests='".json_encode($input["tests"])."', ar_no='".$ar_no."', remark='".$input["remark"]."', status='done' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

}

$conn->close();
?>