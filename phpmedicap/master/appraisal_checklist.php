<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
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
    
    if ($_GET["type"] == "SaveCheckList") {
        
        $sql = "INSERT INTO check_list (review_heading,review_point,grade,marks,alert_grade,entry_by,entry_date) VALUES 
        ('".$input["review_heading"]."','".$input["review_point"]."','".$input["grade"]."','".$input["marks"]."',
      '".json_encode($input["CheckList"])."','".$_GET["id"]."' ,'$entry_date')";
        
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }else if ($_GET["type"] == "deleteCheck_List") {
               
               $sql =" Delete from check_list where id='".$_GET['id']."' "; 
               $conn->query($sql);
      
    	    if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    
    	
    	
    }  else if ($_GET["type"] == "getCheckList") {
        $output = array();
        $sql = "SELECT * FROM check_list where id='".$_GET['id']."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["checkList"] = json_decode($row["checkList"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

}

$conn->close();
?>