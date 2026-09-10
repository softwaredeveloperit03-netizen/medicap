<?php 
require '../../db.php';
require '../../token.php';
require '../../tcpdf/tcpdf.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
$input = json_decode(file_get_contents("php://input"), true);
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
    
    if ($_GET["type"] == "saveLAF_CLEAN_Activity") {

    // Ensure input exists
    if(!$input){
        $input = [];
    }

    // Encode arrays safely
    $previousBatchDetails = isset($input['previousBatchDetails']) 
        ? json_encode($input['previousBatchDetails']) 
        : '[]';

    $checkPointData = isset($input['checkPointData']) 
        ? json_encode($input['checkPointData']) 
        : '[]';

    $sql = "INSERT INTO `sampling_LAF_Clean_record`
    (`plant_id`,`cleaning_date`,`equipment_id`,`equipment_name`,
    `startTime`,`endTime`,`Roompressure`,`Prepressure`,
    `Mediatepressure`,`Heappressure`,`doneBy`,`doneOn`,
    `previousBatchDetails`,`checkPointData`,
    `entry_by`,`entry_on`,`status`,`equipment_type`)
    
    VALUES (
    '".$_GET['plant_id']."',
    '".($input['date'] ?? '')."',
    '".($input['equipment_code'] ?? '')."',
    '".($input['equipment_name'] ?? '')."',
    '".($input['startTime'] ?? '')."',
    '".($input['endTime'] ?? '')."',
    '".($input['Roompressure'] ?? '')."',
    '".($input['Prepressure'] ?? '')."',
    '".($input['Mediatepressure'] ?? '')."',
    '".($input['Heappressure'] ?? '')."',
    '".($input['doneBy'] ?? '')."',
    '".($input['doneOn'] ?? '')."',
    '".$previousBatchDetails."',
    '".$checkPointData."',
    '".($input['entry_by'] ?? '')."',
    '".($input['entry_on'] ?? '')."',
    '".($input['status'] ?? '')."',
    '".($input['equipment_type'] ?? '')."'
    )";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => $conn->error]);
    }
}
    if ($_GET["type"] == "save_Weighing_CLEAN_Activity") {
        
        // Encode arrays properly
$previousBatchDetails =  json_encode($input['previousBatchDetails']);
$checkPointData      = json_encode($input['checkPointData']);

         $sql = "INSERT INTO `sampling_Weighing_BAL_Clean_record`
        (`plant_id`, `cleaning_date`, `equipment_id`,`equipment_name`, `startTime`, `endTime`, 
         `doneBy`, `doneOn`, `previousBatchDetails`, `checkPointData`,
         `entry_by`, `entry_on`, `status`)
        VALUES (
            '".$_GET['plant_id']."',
            '".$input['date']."',
            '".$input['equipment_code']."',
            '".$input['equipment_name']."',
            '".$input['startTime']."',
            '".$input['endTime']."',
            '".$input['doneBy']."',
            '".$input['doneOn']."',
            '".$previousBatchDetails."',
            '".$checkPointData."',
            '".$input['entry_by']."',
            '".$input['entry_on']."',
            '".$input['status']."'
        )";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }

    }
    if ($_GET["type"] == "save_Area_CLEAN_Activity") {
        
        // Encode arrays properly
$previousBatchDetails =  json_encode($input['previousBatchDetails']);
$checkPointData      = json_encode($input['checkPointData']);

         $sql = "INSERT INTO `sampling_Area_Clean_record`
        (`plant_id`, `cleaning_date`,  `startTime`, `endTime`, 
         `doneBy`, `doneOn`, `previousBatchDetails`, `checkPointData`,
         `entry_by`, `entry_on`, `status`)
        VALUES (
            '".$_GET['plant_id']."',
            '".$input['date']."',
            
            '".$input['timeinhr']."',
            '".$input['endTime']."',
            '".$input['done_by']."',
            '".$input['donebyDate']."',
            '".$previousBatchDetails."',
            '".$checkPointData."',
            '".$input['entry_by']."',
            '".$input['entry_on']."',
            '".$input['status']."'
        )";
        
        if ($conn->query($sql)) {
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
 

}

$conn->close();
?>