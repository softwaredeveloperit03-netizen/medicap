<?php
    require 'db.php';
    require 'token.php';
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

    if($_GET["type"]=="saveChecklist"){
        $sql="INSERT INTO checklist(checklist_name ,format_no ,revision_no ,checkpoints ,prepared_by ,prepared_date)VALUES('".$input["checklist_name"]."','".$input["format_no"]."' ,'".$input["revision_no"]."' , '".json_encode($input["checklist"])."', '".$_GET["emp_id"]."' ,'$entry_date')";
         if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"]=="getChecklist"){
        $output=Array();
        $sql="SELECT * FROM checklist WHERE status='APPROVED' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["checkpoints"]= json_decode($row["checkpoints"]);
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