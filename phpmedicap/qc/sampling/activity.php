<?php 
require '../../db.php';
require '../../token.php';
require '../../tcpdf/tcpdf.php';

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

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
    
    if ($_GET["type"] == "saveActivity") {
        $sql = "INSERT INTO activity (humidity,temperature,pressure,equipment_code,location,labour_no,start_date,from_time,end_date,to_time,entry_by,entry_date,plant_id) 
        VALUES ('".$input["humidity"]."','".$input["temperature"]."','".$input["pressure"]."','".$input["equipment_code"]."','".$input["location"]."','".$input["labour_no"]."',
        '".$input["start_date"]."','".$input["from_time"]."','".$input["end_date"]."','".$input["to_time"]."','".$_GET["emp_id"]."','$entry_date','".$_GET["plant_id"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    
    
    
    else if ($_GET["type"] == "getOperatorsWorker") {
        
          $output = array();
        $sql = "select id,plant_id,emp_id,lastname,firstname,department,operator_category FROM employee where department = 'Quality Control' AND
        operator_category = 'Worker / Operator' AND plant_id = '".$_GET['plant_id']."' ";
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
          
                $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    	
    }
    
    else if ($_GET["type"] == "saveActivityCleaning") {
        
        
        $sql = "UPDATE cleanActivity SET C_date = '".$input['C_date']."' , cleanBy = '".$input['cleanBy']."' , Status = 'Done' WHERE id = '".$input['id']."' ";
         
     	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    	
    }
    
    else if ($_GET["type"] == "getPendingEquipmentForCleaning") {
        
        $output = array();
        $sql = "SELECT a.*,b.equipment_name FROM cleanActivity a left join  equipment b ON a.equipmentClean = b.equipment_code where a.status = 'Pending' AND a.plant_id = '".$_GET['plant_id']."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['cleanBy'] = 'NA';
                $row['C_date'] = 'NA';
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getcleaningLog") {
        
        $output = array();
        $sql = "SELECT a.*,b.equipment_name FROM cleanActivity a left join  equipment b ON a.equipmentClean = b.equipment_code where a.status != 'Pending' AND a.plant_id = '".$_GET['plant_id']."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
          
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    
    else if ($_GET["type"] == "updateActivity") {
        $sql = "update activity set temperature='".$input['temperature']."',
        humidity='".$input['humidity']."',
        to_time='".$input['to_time']."',
        end_date='".$input['end_date']."',
        pressure='".$input['pressure']."' WHERE id='".$_GET["id"]."' ";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if ($_GET["type"] == "getActivity") {
        $output = array();
        //  $sql = "SELECT * FROM activity";
        $sql = "SELECT a.*,l.labour_name FROM activity a  LEFT JOIN  labour l ON  l.labour_no=a.labour_no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveCleaningActivity") {
        $sql = "UPDATE activity SET clean_by='".$input["labour_no"]."',clean_date='".$input["start_date"]."',clean_start_time='".$input["from_time"]."'
        ,clean_end_time='".$input["to_time"]."'
             WHERE id='".$_GET["activity_id"]."'";
               if ($conn->query($sql)) {
               echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if ($_GET["type"] == "getActivity_inprocess") {
        $output = array();
        //  $sql = "SELECT * FROM activity";
        $sql = "SELECT a.*,l.labour_name FROM activity a LEFT JOIN labour l ON l.labour_no=a.labour_no WHERE a.end_date=''";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getActivity_Cleaning") {
        $output = array();
        //  $sql = "SELECT * FROM activity";
        $sql = "SELECT a.*,l.labour_name FROM activity a LEFT JOIN labour l ON l.labour_no=a.labour_no WHERE a.end_date !=''";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getcleaning") {
        $output = array();
        //  $sql = "SELECT * FROM activity";
        $sql = "SELECT * FROM employee WHERE operator_category ='Worker / Operator' AND department='Quality Control'";
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