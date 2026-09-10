<?php


// ini_set('display_errors', 1);
// error_reporting(E_ALL);


require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
  $currentUrl =$_GET["description"];
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
    
$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);


if ($_GET["type"] == "getEquipmentForBreakdown") {
    $output = array();
    $sql="SELECT * FROM equipment where plant_id = '".$_GET["plant_id"]."'   AND status = 'Active'     ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 

else if ($_GET["type"] == "getEquipmentForBreakdownBydept") {
    $output = array();
    $sql="SELECT * FROM equipment where plant_id = '".$_GET["plant_id"]."'   AND status = 'Active'  AND  department = '".$_GET["deptName"]."'    ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getEquipmentForBreakdownByDepartment") {
    $output = array();
    $sql="SELECT * FROM equipment where plant_id = '".$_GET["plant_id"]."'   AND status = 'Active' AND  department = '".$_GET["department_name"]."'    ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getEmployee") {
    $output = array();
    $sql="SELECT * FROM employee where plant_id = '".$_GET["plant_id"]."'  AND status = 'Active'     ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
 } 
else if ($_GET["type"] == "getEmployeeProduction") {
    $output = array();
    $sql="SELECT * FROM employee where plant_id = '".$_GET["plant_id"]."' and department='Production'      ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
 } 
else if ($_GET["type"] == "getdepartment") {
    $output = array();
    $sql="SELECT * FROM department    ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getempbydept") {
    $output = array();
    $sql="SELECT * FROM employee where plant_id = '".$_GET["plant_id"]."'  AND status = 'Active'  AND  department = '".$_GET["department_name"]."'   ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "saveMaintainance") {
    
    $sql = "INSERT INTO maintenance (plant_id,department_name, equipment_id,equipment_code, date_of_incident, raised_by, Raised_on,status, descrip_breakd, removal_from_service, entry_by, entry_date) VALUES 
    ('".$_GET["plant_id"]."', '".$input["department_name"]."','".$input["equipment_id"]."','".$input["equipment_code"]."', '".$input["date_of_incident"]."', 
    '".$_GET["emp_id"]."', '$entry_date', 'Pending',
    '".$input["descrip_breakd"]."', '".$input["removal_from_service"]."', '".$_GET["emp_id"]."', '$entry_date')";
    
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
 }   
else if ($_GET["type"] == "saveMaintainanceMeha") {
    
    $sql = "INSERT INTO maintenance (plant_id,department_name, equipment_id,equipment_code, date_of_incident, raised_by, Raised_on,status, descrip_breakd, removal_from_service, entry_by, entry_date,remark,Priority,areaLocation) VALUES 
    ('".$_GET["plant_id"]."', '".$input["department_name"]."','".$input["equipment_id"]."','".$input["equipment_code"]."', '".$input["date_of_incident"]."', 
    '".$_GET["emp_id"]."', '$entry_date', 'Sent For Dept Head',
    '".$input["descrip_breakd"]."', '".$input["removal_from_service"]."', '".$_GET["emp_id"]."', '$entry_date', '".$input["remark"]."', '".$input["Priority"]."', '".$input["areaLocation"]."')";
    
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
 }   
else if ($_GET["type"] == "saveMaintainanceBYEngg") {
    
    $sql = "INSERT INTO maintenance (plant_id,department_name, equipment_id,equipment_code, date_of_incident, raised_by, Raised_on,status, descrip_breakd, removal_from_service, entry_by, entry_date) VALUES 
    ('".$_GET["plant_id"]."', '".$input["department_name"]."','".$input["equipment_id"]."','".$input["equipment_code"]."', '".$input["date_of_incident"]."', '".$input["raised_by"]."', '".$input["Raised_on"]."', 'Pending',
    '".$input["descrip_breakd"]."', '".$input["removal_from_service"]."', '".$_GET["emp_id"]."', '$entry_date')";
    
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} 

else if ($_GET["type"] == "getbreakdown_data") {
    $output = array();
     $sql="SELECT m.*,e.tag_no,e.location,e.equipment_name FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id  where m.status= 'Pending' AND
     m.plant_id = '".$_GET["plant_id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["steps_data"] = json_decode($row["steps_data"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getdescription_of_repair") {
    $output = array();
     $sql="SELECT m.*,e.tag_no,e.location,e.equipment_name FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id  where
     (m.prili_req = 'Done' OR m.prili_req = 'NA') AND m.repair_desc_status is null AND  m.plant_id = '".$_GET["plant_id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["steps_data"] = json_decode($row["steps_data"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getreplacementEqupiment") {
    $output = array();
     $sql="SELECT m.*,e.tag_no,e.location,e.serial_no,e.model,e.equipment_name FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id  where
     m.replacement_req = 'Required' AND m.replacement_status != 'Done' AND  m.plant_id = '".$_GET["plant_id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["steps_data"] = json_decode($row["steps_data"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getBreakdownForPreApproval") {
    $output = array();
     $sql="SELECT m.*,e.tag_no,e.location,e.equipment_name FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id  where m.plant_id = '".$_GET["plant_id"]."'
     AND preApproval = 'Pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["steps_data"] = json_decode($row["steps_data"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
 } 
else if ($_GET["type"] == "getBreakdownForPreApprovalMeha") {
    $output = array();
     $sql="SELECT m.*,e.tag_no,e.location,e.equipment_name FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id  where m.plant_id = '".$_GET["plant_id"]."'
     AND m.status = 'Repair Verified'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
                             $row["selectedPPE"] = json_decode($row["selectedPPE"]);

             $row["steps_data"] = json_decode($row["steps_data"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
 } 
else if ($_GET["type"] == "getbreakdownForPreliminary") {
    $output = array();
     $sql="SELECT m.*,e.tag_no,e.location,e.equipment_name FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id  where m.plant_id = '".$_GET["plant_id"]."'
     AND prili_req = 'Pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["steps_data"] = json_decode($row["steps_data"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getqaApproval") {
    $output = array();
     $sql="SELECT m.*,e.tag_no,e.location,e.model,e.serial_no,e.equipment_name,
(SELECT equipment_code from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_code ,
(SELECT location from equipment WHERE equipment.id=m.replacement_equipment) as r_location ,
(SELECT model from equipment WHERE equipment.id=m.replacement_equipment) as r_model ,
(SELECT serial_no from equipment WHERE equipment.id=m.replacement_equipment) as r_serial_no ,
(SELECT equipment_name from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_name 
FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id   where m.status='TO_QA_Approved' AND m.plant_id = '".$_GET["plant_id"]."' ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["steps_data"] = json_decode($row["steps_data"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getebmLog") {
    $output = array();
     $sql="SELECT m.*,e.tag_no,e.location,e.model,e.serial_no,e.equipment_name,
(SELECT tag_no from equipment WHERE equipment.id=m.replacement_equipment) as r_tag_no ,
(SELECT location from equipment WHERE equipment.id=m.replacement_equipment) as r_location ,
(SELECT model from equipment WHERE equipment.id=m.replacement_equipment) as r_model ,
(SELECT serial_no from equipment WHERE equipment.id=m.replacement_equipment) as r_serial_no ,
(SELECT equipment_name from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_name 
FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id   where m.plant_id = '".$_GET["plant_id"]."' ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["steps_data"] = json_decode($row["steps_data"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getForApproval") {
    $output = array();
     $sql="SELECT m.*,e.tag_no,e.location,e.model,e.serial_no,e.equipment_name,
(SELECT equipment_code from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_code ,
(SELECT location from equipment WHERE equipment.id=m.replacement_equipment) as r_location ,
(SELECT model from equipment WHERE equipment.id=m.replacement_equipment) as r_model ,
(SELECT serial_no from equipment WHERE equipment.id=m.replacement_equipment) as r_serial_no ,
(SELECT equipment_name from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_name 
FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id   where m.status='Done' AND m.plant_id = '".$_GET["plant_id"]."' ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["steps_data"] = json_decode($row["steps_data"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getebmlogbydepartment") {
    $output = array();
     $sql="SELECT m.*,e.tag_no,e.location,e.model,e.serial_no,e.equipment_name,
(SELECT equipment_code from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_code ,
(SELECT location from equipment WHERE equipment.id=m.replacement_equipment) as r_location ,
(SELECT model from equipment WHERE equipment.id=m.replacement_equipment) as r_model ,
(SELECT serial_no from equipment WHERE equipment.id=m.replacement_equipment) as r_serial_no ,
(SELECT equipment_name from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_name 
FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id   where m.department_name='".$_GET["department_name"]."' AND m.plant_id = '".$_GET["plant_id"]."' ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["steps_data"] = json_decode($row["steps_data"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
 } 
else if ($_GET["type"] == "getebmlogbydepartmentHead") {
    $output = array();
     $sql="SELECT m.*,e.tag_no,e.location,e.model,e.serial_no,e.equipment_name,
(SELECT equipment_code from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_code ,
(SELECT location from equipment WHERE equipment.id=m.replacement_equipment) as r_location ,
(SELECT model from equipment WHERE equipment.id=m.replacement_equipment) as r_model ,
(SELECT serial_no from equipment WHERE equipment.id=m.replacement_equipment) as r_serial_no ,
(SELECT equipment_name from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_name 
FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id   where m.department_name='".$_GET["department_name"]."' AND m.plant_id = '".$_GET["plant_id"]."' and m.status='Sent For Dept Head'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["steps_data"] = json_decode($row["steps_data"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
 } 
else if ($_GET["type"] == "getebmlogbydepartmentHeadVerifi") {
    $output = array();
     $sql="SELECT m.*,e.tag_no,e.location,e.model,e.serial_no,e.equipment_name,
(SELECT equipment_code from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_code ,
(SELECT location from equipment WHERE equipment.id=m.replacement_equipment) as r_location ,
(SELECT model from equipment WHERE equipment.id=m.replacement_equipment) as r_model ,
(SELECT serial_no from equipment WHERE equipment.id=m.replacement_equipment) as r_serial_no ,
(SELECT equipment_name from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_name 
FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id   where m.department_name='".$_GET["department_name"]."' AND m.plant_id = '".$_GET["plant_id"]."' and m.status='Inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["steps_data"] = json_decode($row["steps_data"]);
             $row["selectedPPE"] = json_decode($row["selectedPPE"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
 } 
else if ($_GET["type"] == "getebmlogbydepartmentHeadVerifiedClosure") {
    $output = array();
     $sql="SELECT m.*,e.tag_no,e.location,e.model,e.serial_no,e.equipment_name,
(SELECT equipment_code from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_code ,
(SELECT location from equipment WHERE equipment.id=m.replacement_equipment) as r_location ,
(SELECT model from equipment WHERE equipment.id=m.replacement_equipment) as r_model ,
(SELECT serial_no from equipment WHERE equipment.id=m.replacement_equipment) as r_serial_no ,
(SELECT equipment_name from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_name 
FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id   where   m.plant_id = '".$_GET["plant_id"]."' and m.status='Sent to engg for closure'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["steps_data"] = json_decode($row["steps_data"]);
             $row["selectedPPE"] = json_decode($row["selectedPPE"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
 } 

else if ($_GET["type"] == "updateStepsDeptHeadMeha") {
    
    $sql = "UPDATE maintenance SET status='Pending',  dept_head_by = '".$_GET["emp_id"]."', dept_head_on = '$entry_date'  WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
 } 
else if ($_GET["type"] == "updateStepsDeptHeadMehaVerify") {
    
    $sql = "UPDATE maintenance SET status='Verified By Dept Head',  vrification_by ='".$input["vrification_by"]."' , vrification_on = '".$input["vrification_on"]."' ,
    RemarksafterJobVerification ='".$input["RemarksafterJobVerification"]."' , equipAreaClen = '".$input["equipAreaClen"]."' ,
    vrification_byDeptHEad = '".$_GET["emp_id"]."', vrification_onDeptHEad = '$entry_date',status='Repair Verified'
    WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
 } 
 else if ($_GET["type"] == "CloseEbd") {
    
    $sql = "UPDATE maintenance SET status='Close',  MHCard ='".$input["MHCard"]."' ,
   close_by = '".$_GET["emp_id"]."', close_on = '$entry_date'
    WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
 } 
else if ($_GET["type"] == "updateSteps") {
    
    $sql = "UPDATE maintenance SET status='Inprocess', steps_data = '".json_encode($input["steps_data"])."',
    preApproval = '".$input["preApproval"]."', prili_req = '".$input["prili_req"]."'  WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
 } 
else if ($_GET["type"] == "updateStepsMehaENGG") {
    
    // $sql = "UPDATE maintenance SET status='Inprocess', steps_data = '".json_encode($input["steps_data"])."',
    // preApproval = '".$input["preApproval"]."', prili_req = '".$input["prili_req"]."'  WHERE id='".$_GET["id"]."'";
    $sql = "UPDATE `maintenance` 
            SET status='Inprocess',
                observationRemark ='".$input['observationRemark']."'
                ,ShutdownRequire  ='".$input['ShutdownRequire']."'
                ,selectedPPE  = '".json_encode($input["selectedPPE"])."'
                ,otherPpe  ='".$input['otherPpe']."'
                ,delayJustification  ='".$input['delayJustification']."'
                ,engineeringAction  ='".$input['engineeringAction']."'
                ,received_closeOut_by  ='".$_GET['emp_id']."'
                ,received_closeOut_on  ='$entry_date'
          WHERE id='".$_GET["id"]."'";
 
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
 } 
else if ($_GET["type"] == "ApprovefFinalEBM") {
    
    
    if($_GET["status"] == 'TO_QA_Approved'){
            $sql = "UPDATE maintenance SET status= '".$_GET["status"]."' , approve_by = '".$_GET["emp_id"]."',approve_on =  '$entry_date'  WHERE id='".$_GET["id"]."'";
        
    }else{
            $sql = "UPDATE maintenance SET status= '".$_GET["status"]."' , qa_approve_by = '".$_GET["emp_id"]."',qa_approve_on =  '$entry_date'  WHERE id='".$_GET["id"]."'";

    }
     
    
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
} 

else if ($_GET["type"] == "saveReplacement") {
    
    $sql = "UPDATE maintenance SET  replacement_equipment = '".$input["replacement_equipment"]."', assessed_by = '".$input["assessed_by"]."',
    repl_approved_by = '".$input["repl_approved_by"]."', update_details_elb = '".$input["update_details_elb"]."',update_details_calibration = '".$input["update_details_calibration"]."',
    implemented_by = '".$input["implemented_by"]."',
    replacement_comments = '".$input["replacement_comments"]."', replacement_status = 'Inprocess',replacementlikerequired = '".$input["replacementlikerequired"]."'
     WHERE id='".$_GET["id"]."'";
     
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
} 
else if ($_GET["type"] == "savereplacementReport") {
    
     $sql = "UPDATE maintenance SET  replacement_report = '".$input["replacement_report"]."', fit_for_use = '".$input["fit_for_use"]."',
    if_no_cali_main = '".$input["if_no_cali_main"]."', return_to_service = '".$input["return_to_service"]."',date_return = '".$input["date_return"]."',
    relsed_for_use = '".$input["relsed_for_use"]."',
    elb_update_eq_details = '".$input["elb_update_eq_details"]."',status='Done',replacement_status= 'Done', equp_disposed = '".$input["equp_disposed"]."',finance_notified = '".$input["finance_notified"]."'
     WHERE id='".$_GET["id"]."'";
     
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
} 
else if ($_GET["type"] == "updaterepairDescription") {
    
    
    $status = 'Inprocess';
    
    if($input["replacement_req"] != 'Required'){
        $status = 'Done';
    }
    
    
      $sql = "UPDATE maintenance SET status = '$status',  description_repair = '".$input["description_repair"]."',replacement_req = '".$input["replacement_req"]."',repairAdded_by='".$_GET["emp_id"]."',
    repairDesc_on='$entry_date' ,repair_desc_status = 'Done'  WHERE id='".$_GET["id"]."'";
    
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
} 
else if ($_GET["type"] == "approvePreApproval") {
    
    $sql = "UPDATE maintenance SET preApproval= 'Done',  prili_req = '".$input["prili_req"]."',ebm_no = '".$input["ebm_no"]."',preApprove_by='".$_GET["emp_id"]."',
    preApprove_on='$entry_date'  WHERE id='".$_GET["id"]."'";
    
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
    } 
else if ($_GET["type"] == "approvePreApprovalMehaQAFINAL") {
    
    $sql = "UPDATE maintenance SET capaRef='".$input['capaRef']."'
,equipAreaCleanQA='".$input['equipAreaCleanQA']."'
,REequipAreaClen='".$input['REequipAreaClen']."'
,equipAreaClenUsed='".$input['equipAreaClenUsed']."'
,vrification_byIPQA='".$input['vrification_byIPQA']."'
,IPQAvrification_on='".$input['IPQAvrification_on']."'
,ApprovedQAM='".$input['ApprovedQAM']."',
status='Sent to engg for closure',
QAMvrification_on='".$input['QAMvrification_on']."'  WHERE id='".$_GET["id"]."'";
    
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
    } 
else if ($_GET["type"] == "approvepreliminary") {
    
    $sql = "UPDATE maintenance SET prili_req= 'Done',prelim_comment = '".$input["prelim_comment"]."',  performed = '".$input["performed"]."',
    potential_impact = '".$input["potential_impact"]."',prelimApproved_by='".$_GET["emp_id"]."', prelim_Approved_on='$entry_date'  WHERE id='".$_GET["id"]."'";
    
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
} 


else if ($_GET["type"] == "getInprocessMaintenance") {
    $output = array();
    $sql = "SELECT m.*, s.section_name, e.department, e1.equipment_type, e1.equipment_name FROM maintenance m LEFT JOIN employee e 
    ON m.entry_by=e.emp_id LEFT JOIN section s ON m.section=s.section_code LEFT JOIN equipment e1 ON m.equipment_code=e1.equipment_code
    WHERE e.department='".$_GET["department"]."' AND m.status='checked' ORDER BY m.id DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "attendMaintenance") {
    $sql = "UPDATE maintenance SET status='attend', attend_by='".$_GET["emp_id"]."', attend_date='$entry_date', descriptions='".$input["descriptions"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} 
else if ($_GET["type"] == "getMaintenanceHistory") {
    $output = array();
    $sql = "SELECT m.*, s.section_name, e.department, e1.equipment_type, e1.equipment_name, 
    TIMESTAMPDIFF(SECOND, m.entry_date, m.attend_date) as breakdown_time FROM maintenance m 
    LEFT JOIN employee e ON m.entry_by=e.emp_id LEFT JOIN section s ON m.section=s.section_code LEFT JOIN equipment e1 ON m.equipment_code=e1.equipment_code WHERE e.department LIKE '%".$_GET["department_name"]."%' AND DATE(m.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY m.id DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $hours = floor($row["breakdown_time"] / 3600);
            $mins = floor($row["breakdown_time"] / 60 % 60);
            $secs = floor($row["breakdown_time"] % 60);
            $row["breakdown_time"] = sprintf('%02dH:%02dM', $hours, $mins, $secs);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

else if($_GET['type'] == 'downloadMaintenanceLog'){

        $_GET['filename'] = 'Questions'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
             $sql="SELECT m.*,e.tag_no,e.location,e.model,e.serial_no,e.equipment_name,
                (SELECT tag_no from equipment WHERE equipment.id=m.replacement_equipment) as r_tag_no ,
                (SELECT location from equipment WHERE equipment.id=m.replacement_equipment) as r_location ,
                (SELECT model from equipment WHERE equipment.id=m.replacement_equipment) as r_model ,
                (SELECT serial_no from equipment WHERE equipment.id=m.replacement_equipment) as r_serial_no ,
                (SELECT equipment_name from equipment WHERE equipment.id=m.replacement_equipment) as r_equipment_name 
                FROM maintenance m LEFT JOIN  equipment e ON e.id=m.equipment_id   where m.plant_id = '".$_GET["plant_id"]."' AND m.id= '".$_GET["id"]."'";
          $result = $conn->query($sql);
    $row = $result->fetch_assoc();{
        $html.='';
        $selectedPPE = json_decode($row['selectedPPE'], true);
        $html.='
        
        <h1 style="color:brown;text-align: center;">JOB CARD FOR MAINTENANCE WORK</h1>
                <table cellpadding="5" border="1">
                    
              <tr>
            <th  style="width:25%;font-size: 12px;color:green;"><b>1.0   Job Card No. :</b>   </th>
            <th style="width:75%;color:blue;"> '.$row['ebm_no'].' </th>
        </tr>
      
        <tr>
            <th colspan="2" style="font-size: 12px;color:green;"><b>2.0 Details of Job (  '.$row['department_name'].'  )</b></th>
        </tr>
        <tr>
            <td><b>Department Name:</b></td>
            <td>'.$row['department_name'].'
            </td>
        </tr>
        <tr>
            <td><b>Name & Code no. of Area/Location:</b></td>
            <td>'.$row['areaLocation'].'</td>
        </tr>
        <tr>
            <td><b>Equipment/ Instruments ID no.:</b></td>
            <td>'.$row['equipment_name'].'</td>
        </tr>
        <tr>
            <td><b>Details of Job/Activities:</b></td>
            <td>'.$row['descrip_breakd'].'</td>
        </tr>
        <tr>
            <td><b>Priority:</b></td>
            <td>'.$row['Priority'].'
            </td>
        </tr>
        <tr>
            <td><b>Remarks (If any):</b></td>
            <td>'.$row['remark'].'</td>
        </tr>
        <tr>
            <td><b>Job Card raised by:</b></td>
            <td><b>'.$row['raised_by'].'</b></td>
        </tr>
      
        <tr>
            <th colspan="2"  style="font-size: 12px;color:green;"><b>3.0 Execution (Engineering)</b></th>
        </tr>
        <tr>
            <td><b>Job Card received by:</b></td>
            <td><b>Name:</b>  '.$row['raised_by'].'        <b>Date/Time:</b> '.$row['Raised_on'].'</td>
        </tr>
        <tr>
            <td><b>Initial Observation/Remark:</b></td>
            <td>'.$row['observationRemark'].'</td>
        </tr>
        <tr>
            <td><b>Shutdown required:</b></td>
            <td>'.$row['ShutdownRequire'].'
            </td>
        </tr>
        
        
        <tr>
            <td><b>Personal protective equipment:</b></td>
            <td>'.$row['selectedPPE'].' , '.$row['otherPpe'].' 
            </td>
        </tr>
        <tr>
            <td><b>Engineering Action taken:</b></td>
            <td>'.$row['engineeringAction'].'</td>
        </tr>
        <tr>
            <td><b>Closed out by:</b></td>
             <td><b>Name:</b>  '.$row['raised_by'].'        <b>Date/Time:</b> '.$row['Raised_on'].'</td>
        </tr>
        <tr>
            <th colspan="2"  style="font-size: 12px;color:green;"><b>4.0 Verification of Job after completion  (  '.$row['department_name'].'  )</b></th>
        </tr>
        <tr>
            <td><b>Equipment/Area cleaned:</b></td>
            <td>'.$row['equipAreaClen'].'
            </td>
        </tr>
        <tr>
            <td><b>Job Verified By Officer:</b></td>
            <td><b>Name:</b>  '.$row['raised_by'].'        <b>Date/Time:</b> '.$row['Raised_on'].'</td>
        </tr>
        <tr>
            <th colspan="2"  style="font-size: 12px;color:green;"><b>5.0 Impact assessment by Quality Assurance department</b></th>
        </tr>
        <tr>
            <td><b>Reference number of CAPA/Change control:</b></td>
            <td>'.$row['capaRef'].'</td>
        </tr>
        <tr>
            <td><b>Equipment/Area cleaned:</b></td>
            <td>'.$row['areaLocation'].'
            </td>
        </tr>
        <tr>
            <td><b>Equipment/Area can be used for routine use:</b></td>
            <td>'.$row['equipAreaClenUsed'].'
            </td>
        </tr>
        <tr>
            <td><b>Verified by IPQA:</b></td>
            <td><b>Name:</b>  '.$row['vrification_byIPQA'].'        <b>Date/Time:</b> '.$row['Raised_on'].'</td>
        </tr>
        <tr>
         <br pagebreak="true"/> 
            <th colspan="2"  style="font-size: 12px;color:green;"><b>6.0 Closeout (Engineering)</b></th>
        </tr>
        <tr>
            <td><b>Machine History card update:</b></td>
            <td>'.$row['MHCard'].'
            </td>
        </tr>
        <tr>
            <td><b>Closed By:</b></td>
            <td><b>Name:</b>  '.$row['raised_by'].'        <b>Date/Time:</b> '.$row['Raised_on'].'</td>
        </tr>
        ';
            }
                $html.='</table>
                <div></div>';
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('trainingneeds.pdf', 'I');
        }
        
        
        
        else if($_GET['type'] == 'downloadMaintenanceHistory'){
                $_GET['filename'] = 'Questions'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp.php");
                $html.='
                <table cellpadding="5">
                    <tr style="background-color:#DCDCDC;">
                        <td style="text-align:center;">Training Questionaries Log</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                         <td style="width:10%;">Sr.</td>
                        <td style="width:10%;">Department</td>
                        <td style="width:10%;">Area	</td>
                        <td style="width:20%;">Maintenance Type	</td>
                        <td style="width:20%;">Urgency Level</td>
                        <td style="width:10%;">Date</td>
                        <td style="width:10%;">Initiate By</td>
                        <td style="width:10%;">Status</td>
                    </tr>
                ';
        
                $sql = "SELECT m.*, s.section_name, e.department, e1.equipment_type, e1.equipment_name, TIMESTAMPDIFF(SECOND, m.entry_date, m.attend_date) as breakdown_time FROM maintenance m LEFT JOIN employee e ON m.entry_by=e.emp_id LEFT JOIN section s ON m.section=s.section_code LEFT JOIN equipment e1 ON m.equipment_code=e1.equipment_code WHERE e.department LIKE '%".$_GET["department_name"]."%' AND DATE(m.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY m.id DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                        $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["trainer_name"];
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $row["trainer_name"] = $row1["trainer_name"];
                            }
                        }
                        $row["questions"] = json_decode($row["questions"]);
                        $html.='
                        <tr>
                            <td>'.$row[''].'</td>
                            <td>'.$row[''].'</td>
                            <td>'.$row[''].'</td>
                            <td>'.$row[''].'</td>
                            <td>'.$row[''].'</td>
                            <td>'.$row[''].'</td>
                            <td>'.$row[''].'</td>
                            <td>'.$row[''].'</td>
                            
                        </tr>';
                    }
                }
                $html.='</table>
                <div></div>';
                EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('trainingneeds.pdf', 'I');
}
else if ($_GET["type"] == "getMaintenanceLog") {
    $output = array();
    $sql = "SELECT m.*, s.section_name, e.department, e1.equipment_type, e1.equipment_name, TIMESTAMPDIFF(SECOND, m.entry_date, m.attend_date) as breakdown_time FROM maintenance m LEFT JOIN employee e ON m.entry_by=e.emp_id LEFT JOIN section s ON m.section=s.section_code LEFT JOIN equipment e1 ON m.equipment_code=e1.equipment_code WHERE e.department='".$_GET["department"]."' AND DATE(m.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY m.id DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $hours = floor($row["breakdown_time"] / 3600);
            $mins = floor($row["breakdown_time"] / 60 % 60);
            $secs = floor($row["breakdown_time"] % 60);
            $row["breakdown_time"] = sprintf('%02dH:%02dM', $hours, $mins, $secs);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getCheckedMaintenances") {
    $output = array();
    $sql = "SELECT m.*, s.section_name, e.department, e1.equipment_type, e1.equipment_name, TIMESTAMPDIFF(SECOND, m.entry_date, m.attend_date) as breakdown_time FROM maintenance m LEFT JOIN employee e ON m.entry_by=e.emp_id LEFT JOIN section s ON m.section=s.section_code LEFT JOIN equipment e1 ON m.equipment_code=e1.equipment_code WHERE e.department='".$_GET["department"]."' AND DATE(m.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY m.id DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $hours = floor($row["breakdown_time"] / 3600);
            $mins = floor($row["breakdown_time"] / 60 % 60);
            $secs = floor($row["breakdown_time"] % 60);
            $row["breakdown_time"] = sprintf('%02dH:%02dM', $hours, $mins, $secs);
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