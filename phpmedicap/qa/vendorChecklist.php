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
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if($_GET["type"] ==  "saveChecklist"){
        $sql="INSERT INTO vendor_checklist(checklist_No,version_no ,checklist_type ,effective_date ,entry_by,entry_date,state_name,corporate)VALUES('".$input["checklist_No"]."' ,'".$input["version_no"]."','".$input["checklist_type"]."','".$input["effective_date"]."' ,'".$_GET["emp_id"]."','entry_date', '".$input["state_name"]."', '$json_corporate' )";
        if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
        } else {
                echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }else if ($_GET["type"] == "getChecklist") {
        $output = Array();
        $sql = "SELECT * FROM vendor_checklist WHERE status='pending' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"] == "savePrepareList"){
        $sql="UPDATE vendor_checklist SET preparechekclist='".json_encode($input["preparechekclist"])."', status='prepare' WHERE id='".$_GET["id"]."' ";
        if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
        } else {
                echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "getPrepareChecklist") {
        $output = Array(); 
        $sql = "SELECT * FROM vendor_checklist WHERE status='prepare'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["preparechekclist"] = json_decode($row["preparechekclist"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

}

$conn->close();
?>