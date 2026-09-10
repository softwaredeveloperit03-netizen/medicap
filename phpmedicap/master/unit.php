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
    
    if ($_GET["type"] == "saveUnit") {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input["unit"])) {
            echo "{\"status\":\"Invalid request\"}";
            exit;
        }

        $sql="SELECT * FROM unit WHERE unit = '".$conn->real_escape_string($input["unit"])."' AND plant_id = '".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
        
        if ($result->num_rows > 0) {
            
          	echo "{\"status\":\"Unit Already Exists. Duplicate Values are not allowed\"}";
          	
        }else {
            
            $sql = "INSERT INTO unit (plant_id,unit,entry_by,entry_date) VALUES ('".$_GET["plant_id"]."','".$conn->real_escape_string($input["unit"])."','".$_GET["emp_id"]."','$entry_date')";
        	if($conn->query($sql)){
        		echo "{\"status\":\"success\"}";
        	} else {
        		echo "{\"status\":\"".$conn->error."\"}";
        	}
        	
        }
        
    } 
    else if ($_GET["type"] == "getUnit") {
          $output = array();
        $sql = "SELECT * FROM unit";
        $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
            
        }
    }
    echo json_encode($output);
       /* $output = Array();
        $sql = "SELECT * FROM unit";
        $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);*/
    }

}

$conn->close();
?>