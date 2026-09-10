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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
   /* if ($_GET["type"] == "saveMasterStages") {
       $sql = "INSERT INTO stagemaster(stages, entry_by,entry_date)VALUES('".$input["stages"]."' , '".$_GET["emp_id"]."' ,'$entry_date')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }*/
    if ($_GET["type"] == "saveStages_ipqc") {
       $sql = "INSERT INTO stages_ipqc(stages, entry_by,entry_date)VALUES('".$input["stages"]."' , '".$_GET["emp_id"]."' ,'$entry_date')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    /*else if($_GET["type"] == "getStagesMaster"){
        $output =array();
        $sql = "SELECT * FROM stagemaster";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }*/


else if($_GET["type"] == "getStages_ipqc"){
        $output =array();
        $sql = "SELECT * FROM stages_ipqc";
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