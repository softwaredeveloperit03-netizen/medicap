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
    
    if ($_GET["type"] == "saveShift") {
        $sql = "INSERT INTO shift (shift_name,night_shift,start_time,end_time,duration,date_change,half_day,full_day,default_shift,entry_by,entry_date) VALUES ('".$input["shift_name"]."','".$input["night_shift"]."','".$input["start_time"]."','".$input["end_time"]."','".$input["duration"]."','".$input["date_change"]."','".$input["half_day"]."','".$input["full_day"]."','".$input["default_shift"]."','".$_GET["emp_id"]."','$entry_date')";
      
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    	
    	
    }else if ($_GET["type"] == "getEmployees") {
        $output = Array();
      // $sql = "SELECT * FROM shift  WHERE status='active'";
       $sql = "SELECT s.*, e.emp_name, e.department, e.designation,e.emp_id FROM shift s 
       LEFT JOIN employee e ON s.emp_id=e.emp_id "; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    }else if ($_GET["type"] == "getShiftSchedule") {
        $output = Array();
        $sql = "SELECT * FROM shift ";
        //WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    	
    }else if($_GET["type"] == "editShift"){
        $sql = "UPDATE shift SET shift_name='".$input["shift_name"]."', night_shift='".$input["night_shift"]."' , start_time='".$input["start_time"]."',end_time='".$input["end_time"]."',duration='".$input["duration"]."',date_change='".$input["date_change"]."',half_day='".$input["half_day"]."',full_day='".$input["full_day"]."',default_shift='".$input["default_shift"]."' WHERE id ='".$_GET["id"]."' ";
        if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }else if ($_GET["type"] == "deleteShift") {
        //$sql = "UPDATE pricelist SET status='Deleted' WHERE id='".$_GET["id"]."'";
        $sql="DELETE FROM shift WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "getShiftLog") {
        $output = Array();
        $sql = "SELECT * FROM shift WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
      
    
}else if ($_GET["type"] == "allocateShift") {
        $output = Array();
        $sql = "SELECT * FROM shift  WHERE shift='allocate'";
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