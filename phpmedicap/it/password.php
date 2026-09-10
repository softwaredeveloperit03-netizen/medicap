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
 
    if ($_GET['type'] == 'getResetRequests'){
      
        $output = array();
        $sql = "SELECT id,emp_id,tempPassword,CONCAT(firstname ,' ', lastname) as empName,passStatus,lastModifiedOn,passApproveBy,
        passApproveOn,department FROM employee  where passStatus = 'Inprocess' AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { 
            
                $output[] = $row;
            }
        }
        echo json_encode($output);
     
     
    }
    else if ($_GET['type'] == 'resetPassword'){
     
     
        $sql= "Update employee set tempPassword = '".$input["new_password"]."'  where emp_id = '".$input["emp_id"]."'";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";    
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
     
    }
    else if ($_GET['type'] == 'updatePassword'){
         $date = new DateTime();
    $formattedDate = $date->format('Y-m-d H:i:s'); 
     
     
     if($_GET["status"] == 'Approved'){
         
                 $sql= "Update employee set password = '".$input["tempPassword"]."' , passStatus = '".$_GET["status"]."' ,
        passApproveOn = '$formattedDate' , passApproveBy = '".$_GET["emp_id"]."'  where emp_id = '".$input["emp_id"]."'";
    
     }else{
         
        $sql= "Update employee set passStatus = '".$_GET["status"]."' ,
        passApproveOn = '$formattedDate' , passApproveBy = '".$_GET["emp_id"]."'  where emp_id = '".$input["emp_id"]."'";
        
     }
      
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
     
    }


}

$conn->close();
?>