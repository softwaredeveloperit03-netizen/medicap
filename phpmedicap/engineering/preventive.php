<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();


// ini_set('display_errors', 1);
// error_reporting(E_ALL);



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
    
    if($_GET["type"] == "getCriticalFilter"){
        $output = Array();
        $sql ="SELECT * FROM ventfilter WHERE category='Critical'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"] == "getNonCriticalFilter"){
        $output = Array();
        $sql ="SELECT * FROM ventfilter WHERE category='Non Critical'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"] =="saveReplacementRecord"){
        $sql="INSERT INTO ventfilter_replacement(filter_id, remark , entry_by , entry_date ,next_date , check_by,check_date)VALUES('".$input["filter_id"]."' ,'".$input["remark"]."' ,'".$_GET["emp_id"]."' ,'$entry_date' ,'".$input["next_date"]."','','')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        }else{
             echo "{\"status\":\"failed\"}";
        }
    }
       else if($_GET["type"] == "getEquipments") {
        $output = Array();
         $sql = "SELECT * FROM equipment where preventive_maintenance  = 'Applicable' and plant_id='".$_GET["plant_id"]."' AND  status = 'Active' ORDER BY 1 DESC"; 
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row['inpection_freequency'] = json_decode($row['inpection_freequency']);
                $row['prev_maint_frequency'] = json_decode($row['prev_maint_frequency']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
       else if($_GET["type"]=="update_equipment_schedule") {
        $sql="SELECT * FROM equipment_maintenance WHERE equipment_id= '".$input["id"]."' ";
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Schedule Already Prepared for this Equipment.\"}";
        }
        else {
        $sql="UPDATE equipment SET inpection_freequency='".json_encode($input["inspection"])."',
        prev_maint_frequency='".json_encode($input["preventive"])."'
        WHERE id='".$input["id"]."' ";
         
        if($conn->query($sql)){
            $insp_array = $input["inspection"];
            $sql1='';
            $sql1 = "insert into equipment_maintenance(equipment_id,due_date,due_type,frequency,entry_by,entry_date) values";
            foreach ($insp_array as $value) {
             //   echo $value['particular'];
                if(trim($value['particular']) =='Daily'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<730;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 1 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Daily','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }else if(trim($value['particular']) =='Weekly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<104;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 7 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Weekly','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }else if(trim($value['particular']) =='FortNightly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<52;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 15 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Daily','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }else if(trim($value['particular']) =='Monthly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<24;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 30 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Monthly','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }else if(trim($value['particular']) =='Quarterly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<8;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 90 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Quarterly','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }else if(trim($value['particular']) =='Half-Yearly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<4;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 180 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Half-Yearly','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }else if(trim($value['particular']) =='Annually'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<2;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 364 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Annually','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }
            }
             $prevent_array = $input["preventive"];
            foreach ($prevent_array as $value) {
              //  echo $value['particular'];
                if(trim($value['particular']) =='Daily'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<730;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 1 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Daily','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }else if(trim($value['particular']) =='Weekly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<104;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 7 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Weekly','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }else if(trim($value['particular']) =='FortNightly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<52;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 15 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Daily','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }else if(trim($value['particular']) =='Monthly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<24;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 30 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Monthly','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }else if(trim($value['particular']) =='Quarterly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<8;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 90 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Quarterly','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }else if(trim($value['particular']) =='Half-Yearly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<4;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 180 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Half-Yearly','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }else if(trim($value['particular']) =='Annually'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<2;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 364 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Annually','".$_GET["emp_id"]."','$entry_date'),";
                    }
                }
            }
            $sql1= rtrim($sql1, ',');
           // echo $sql1;
            $conn->query($sql1);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
            
        }
    }
     else if($_GET["type"]=="update_equipment_frequency") {
        $sql="SELECT * FROM equipment_maintenance WHERE equipment_id= '".$input["id"]."' ";
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Frequency Already Set and Schedule prepared for this Equipment.\"}";
        }
        else {
        
            $sql="UPDATE equipment SET inpection_freequency='".json_encode($input["inspection"])."',preventive_department = '".$input["preventive_department"]."',inspection_department = '".$input["inspection_department"]."',
            prev_maint_frequency='".json_encode($input["preventive"])."'
            WHERE id='".$input["id"]."' ";
                if($conn->query($sql)){  
                     echo "{\"status\":\"success\"}";
                } else {
                     echo "{\"status\":\"".$conn->error."\"}";
                }
        }
    }
    else if($_GET["type"] == "acceptIntimation") {
 
        $sql = "Update equipment_maintenance SET  intimation_status = '".$_GET["intimation_status"]."'  WHERE   id =   '".$_GET["id"]."' "; 
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
 
    }
     else if ($_GET["type"] == "saveChecklist_data") {
     
    
        
          $sql = "update equipment_maintenance set checklist =  '".json_encode($input["checklist"])."' ,remark = '".$input["remark"]."' ,
        preventive_date = '".$input["preventive_date"]."' ,status = 'Checking',entry_by = '".$_GET["emp_id"]."' ,entry_date = '$entry_date' where id = '".$_GET["id"]."' ";
        
        
 
        
         if($conn->query($sql)){
    
         echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
        else if($_GET["type"] == "getIntimations") {  
        $output = Array();
           $sql = "SELECT a.*,b.equipment_code ,b.equipment_name ,b.description,b.department,b.location,
                         DATEDIFF(a.due_date, CURDATE()) AS remaining_days

         FROM equipment_maintenance a JOIN equipment b on a.equipment_id= b.id WHERE intimation_status !='Pending' and  b.department ='".$_GET["Inti_department"]."'
         and a.due_type =   '".$_GET["due_type"]."'  order by due_date"; 
        $result = $conn->query($sql);
    
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row['checklist'] = json_decode($row['checklist']);
                $row['intimation_data'] = json_decode($row['intimation_data']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "saveIntimation") {
 
        $sql = "Update equipment_maintenance SET intimation_data='".json_encode($input)."' ,intimation_status = '".$_GET["intimation_status"]."'  WHERE   id =   '".$_GET["id"]."' "; 
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
 
    }
      else if($_GET["type"] == "get_monthly_schedule") {
        $output = Array();
          $sql = "SELECT a.*,b.equipment_code ,b.equipment_name ,b.description,b.department,b.location,
                         DATEDIFF(a.due_date, CURDATE()) AS remaining_days

         FROM equipment_maintenance a JOIN equipment b on a.equipment_id= b.id WHERE month(due_date)='".$_GET["month"]."' and year(due_date)='".$_GET["year"]."'
         and a.due_type =   '".$_GET["due_type"]."'  order by due_date"; 
        $result = $conn->query($sql);
    
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 
                $row['checklist'] = json_decode($row['checklist']);
                
                if (isset($row['intimation_data'])) {
                    $row['intimation_data'] = json_decode($row['intimation_data'], true);
                } else {
                    $row['intimation_data'] = [];
                }
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
          else if ($_GET["type"] ==  "getchecklistData") {
       
           $output = Array();
         $sql = "select b.*,b.checkpoint as check_point from euipment_prevent_checklist_hdr a left join euipment_prevent_checklist_dtl b ON a.id = b.hdr_id where a.plant_id = '".$_GET["plant_id"]."' AND a.frequency_type = '".$_GET["frequency_type"]."'
        AND a.equipment_id = '".$_GET["equipment_id"]."' AND a.frequency = '".$_GET["frequency"]."' ";
         $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
      
            $output[] = $row;  
        }
        }
        echo json_encode($output);
        
        
    }
     else if($_GET["type"] == "getPmForChecking") {
        $output = Array();
          $sql = "SELECT a.*,b.equipment_code ,b.equipment_name ,b.description,b.department,b.location
         FROM equipment_maintenance a JOIN equipment b on a.equipment_id= b.id WHERE a.status='".$_GET["status"]."'
         and a.due_type =   '".$_GET["due_type"]."'  order by due_date"; 
        $result = $conn->query($sql);
    
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row['checklist'] = json_decode($row['checklist']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
       else if($_GET["type"] == "ChangeStatusOfPMChecking") {
 
        $sql = "Update equipment_maintenance SET status='".$_GET["status"]."' ,check_by ='".$_GET["emp_id"]."'  ,check_on ='$entry_date'  WHERE   id =   '".$_GET["id"]."' "; 
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
 
    }
     else if($_GET["type"] == "ChangeStatusOfPMApproval") {
 
        $sql = "Update equipment_maintenance SET status='".$_GET["status"]."' ,approve_by ='".$_GET["emp_id"]."'  ,approve_on ='$entry_date'  WHERE   id =   '".$_GET["id"]."' "; 
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
 
    }
    else if($_GET["type"] =="getReplacementRecord"){
        $output=Array();
        $sql ="SELECT * FROM ventfilter_replacement WHERE status='pending' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    }
    else if ($_GET["type"] ==  "savePreventChecklist1") {
      
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
       
         $ch="CH-";
        $sql = "Select id  from euipment_prevent_checklist_hdr where  plant_id = '".$_GET["plant_id"]."'  order by id desc  limit 0 , 1"; 
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $ch_id = $ch."0". ++$row['id']; 
      
        
        
         $sql = "INSERT INTO euipment_prevent_checklist_hdr (plant_id,department,equipment_name,equipment_id,frequency, entry_by,ch_id) 
                                             VALUES ('".$_GET["plant_id"]."','".$input["department"]."','".$input["equipment_name"]."','".$input["equipment_id"]."',
                                                        '".$input["frequency"]."', '".$_GET["emp_id"]."','".$ch_id."')";
        
         if($conn->query($sql)){
    	
        $last_id = $conn->insert_id;

        foreach($input["checklist_details"] as $detail){
    		
     	$sql="INSERT INTO euipment_prevent_checklist_dtl (hdr_id ,`checkpoint` ,evaluation_parameter)
                                value(".$last_id.",
                                      '".$detail["check_point"]."',
                                       '".$detail["evaluation_parameter"]."')";
    	        $conn->query($sql);
        }
    
    
         echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    	
    }
    else if ($_GET["type"] ==  "save_maintanance") {
      
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
       
        
        
         $sql = "INSERT INTO equp_priventiv_data (plant_id, department, equipment_name, equipment_id, maintance_date, checklist)
                 VALUES ('".$_GET["plant_id"]."','".$input["department"]."','".$input["equipment_name"]."','".$input["equipment_id"]."',
                        '".$input["maintance_date"]."', '".json_encode($input["checklist_details"])."')";
        
        if($conn->query($sql)){
         echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    	
    }
     else  if ($_GET["type"] == "getequipment_history") {
        $sql = "Select * from equp_priventiv_data where equipment_id = '".$_GET["equipment_id"]."' AND plant_id = '".$_GET["plant_id"]."' ";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['checklist'] = json_decode($row['checklist']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
         else  if ($_GET["type"] == "getDepartmentsEqupment") {
        $sql = "Select * from equipment where preventive_maintenance = 'Applicable' ";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
        else if($_GET["type"] == "getPreventiveHistory")    {
        $output = Array();
          $sql = "SELECT a.*,b.equipment_code ,b.equipment_name ,b.description,b.department,b.location
         FROM equipment_maintenance a JOIN equipment b on a.equipment_id= b.id WHERE a.status='".$_GET["status"]."'
         and a.due_type =   '".$_GET["due_type"]."'  order by due_date"; 
        $result = $conn->query($sql);
    
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row['checklist'] = json_decode($row['checklist']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    //  else  if ($_GET["type"] == "getDepartmentsEqupment") {
    //     $sql = "Select * from equipment where department = '".$_GET["department_name"]."' ";
    //      $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }
     else  if ($_GET["type"] == "getDepartmentsEqupmentCode") {
        $sql = "Select * from equipment where equipment_name = '".$_GET["equipment_name"]."' ";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else  if ($_GET["type"] == "get_department") {
        $sql = "SELECT * FROM department";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "savePreventChecklist") {
      
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
       
        /******* Get CH-ID for  enter in new record  */
        $ch="CH-";
        $sql = "Select id  from euipment_prevent_checklist_hdr where  plant_id = '".$_GET["plant_id"]."'  order by id desc  limit 0 , 1"; 
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $ch_id = $ch."0". ++$row['id']; 
      
        
        
        $sql = "INSERT INTO euipment_prevent_checklist_hdr (plant_id,checktype,department,equipment_name,equipment_id,frequency_type,frequency, partname, entry_by,ch_id) 
                                             VALUES ('".$_GET["plant_id"]."','".$input["checktype"]."','".$input["department"]."','".$input["equipment_name"]."','".$input["equipment_id"]."',
                                                      '".$input["frequency_type"]."', '".$input["frequency"]."', '".$input["partname"]."','".$_GET["emp_id"]."','".$ch_id."')";
        
         if($conn->query($sql)){
    	
        $last_id = $conn->insert_id;

        foreach($input["checklist_details"] as $detail){
    		
    	$sql="INSERT INTO euipment_prevent_checklist_dtl (hdr_id ,checkpoint ,description,evaluation_parameter)
                                value(".$last_id.",
                                      '".$detail["check_point"]."',
                                      '".$detail["description"]."',
                                      '".$detail["evaluation_parameter"]."')";
    	 $conn->query($sql);
        }
    
    
         echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    
      else if ($_GET["type"] ==  "getPreventChecklist") {
       
           $output = Array();
        $sql = "select * from euipment_prevent_checklist_hdr where plant_id = '".$_GET["plant_id"]."' AND checktype = '".$_GET["checktype"]."' order by 1 desc";
         $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
            $output1 = Array();
            $sql1 = "select * , checkpoint as check_point from euipment_prevent_checklist_dtl  where hdr_id = '".$row["id"]."'";
            $result1 = $conn->query($sql1);
            if($result1->num_rows > 0){
                while($row1 = $result1->fetch_assoc()){
                 $output1[]=$row1;
                    
                }
                $row['checkList'] = $output1;
            }
            $output[] = $row;  
        }
        }
        echo json_encode($output);
        
        
    }
      else if ($_GET["type"] ==  "get_checklist") {
       
        
            $output1 = Array();
            $sql1 = "select * from euipment_prevent_checklist_dtl a left join  euipment_prevent_checklist_hdr b on a.hdr_id = b.id  where
             b.plant_id = '".$_GET["plant_id"]."' AND b.equipment_name = '".$_GET["equipment_name"]."'";
            $result1 = $conn->query($sql1);
            if($result1->num_rows > 0){
                while($row1 = $result1->fetch_assoc()){
                    $row1['check_point'] = $row1['checkpoint'];
                    $row1['remark'] = '';
                    $row1['action_taken'] =  '';
                 $output1[]=$row1;
                    
                }
                
             }
        
        echo json_encode($output1);
        
        
    }
    
      else if ($_GET["type"] ==  "getPendingPreventives") {
       
           $output = Array();
        $sql = "select *,e.id as eid from euipment_prevent_checklist_hdr e left join equipment t ON t.equipment_code = e.equipment_id where
    e.plant_id = '".$_GET["plant_id"]."' order by 1 desc";
         $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
            $output1 = Array();
            $sql1 = "select * from euipment_prevent_checklist_dtl  where hdr_id = '".$row["eid"]."'";
            $result1 = $conn->query($sql1);
            if($result1->num_rows > 0){
                while($row1 = $result1->fetch_assoc()){
                 $output1[]=$row1;
                    
                }
                $row['checkList'] = $output1;
            }
            $output[] = $row;  
        }
        }
        echo json_encode($output);
        
        
    }
    else if ($_GET["type"] == "downloadPreventiveSchedule") {
        $_GET['filename'] = 'Preventive Maintanance Calendar'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                       <td  style="width: 10%;">Equip. ID</td>
                       <td  style="width: 7%;">Status</td>
                       <td  style="width: 7%;">JAN	</td>
                       <td  style="width: 7%;"> FEB</td>
                       <td style="width:  7%;">MAR</td>
                       <td style="width:  7%;">APR</td>
                       <td style="width:  7%;">MAY</td>
                       <td style="width:  7%;">JUN</td>
                       <td style="width:  7%;">JUL</td>
                       <td style="width:  7%;">AUG</td>
                       <td style="width:  7%;">SEP</td>
                       <td style="width:  7%;">OCT</td>
                       <td style="width:  7%;">NOV</td>
                       <td style="width:  7%;">DEC</td>
                    </tr>
                   
                </thead>';
            $sql="SELECT * FROM gaussmeter_test WHERE status='APPROVE' ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 7%;">'.$row[''].'</td>
                        <td style="width: 7%;">'.$row[''].'</td>
                        <td style="width: 7%;">'.$row[''].'</td>
                        <td style="width: 7%;">'.$row[''].'</td>
                        <td style="width: 7%;">'.$row[''].'</td>
                        <td style="width: 7%;">'.$row[''].'</td>
                        <td style="width: 7%;">'.$row[''].'</td>
                        <td style="width: 7%;">'.$row[''].'</td>
                        <td style="width: 7%;">'.$row[''].'</td>
                        <td style="width: 7%;">'.$row[''].'</td>
                        <td style="width: 7%;">'.$row[''].'</td>
                        <td style="width: 7%;">'.$row[''].'</td>
                        <td style="width: 7%;">'.$row[''].'</td>
                       
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vent Filter Replacement Record', 'I');
    }
     else if ($_GET["type"] == "downloadFrequency") {
        $_GET['filename'] = 'ANNEXURE-I: Frequency of Equipment PM'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                     
                       <td  style="width: 20%;"> Equipment Name</td>
                       <td  style="width: 20%;">Format No</td>
                       <td  style="width: 20%;">Monthly(On or before 3 Days of last P.M.)</td>
                       <td style="width:  20%;">Quarterly (On or before 7 days of last P.M.)</td>
                       <td  style="width: 20%;">Yearly (On or before 30 days of last P.M.)</td>
                      
                      
                    </tr>
                   
                </thead>';
            $sql="SELECT * FROM pressure_plenum WHERE type='PRESSURE2' AND entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                       
                       
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vent Filter Replacement Record', 'I');
     }
     else if ($_GET["type"] == "downloadInspections") {
        $_GET['filename'] = 'ANNEXURE-II: Frequency of Equipment Inspection'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                     
                       <td  style="width: 20%;">Equipment Name</td>
                       <td  style="width: 20%;">Format No</td>
                       <td  style="width: 40%;">Quarterly (On or before 7 days of last inspection)</td>
                       <td style="width:  20%;">Checklist</td>
                      
                    </tr>
                   
                </thead>';
            $sql="SELECT * FROM gaussmeter_test WHERE status='APPROVE' ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
            $html.='<tr nobr="true">
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 40%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                       
                       
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vent Filter Replacement Record', 'I');
     }
    else if ($_GET["type"] == "downloadPreventiveHistory") {
        $_GET['filename'] = 'Preventive Maintanance History'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                       <td  style="width: 10%;">Sr.</td>
                       <td  style="width: 20%;">Equipment Name</td>
                       <td  style="width: 20%;">Equipment Code	</td>
                       <td  style="width: 20%;"> Frequency</td>
                       <td style="width:  20%;">Entry By	</td>
                       <td style="width:  10%;">Entry Date</td>
                    </tr>
                   
                </thead>';
            $sql="SELECT * FROM gaussmeter_test WHERE status='APPROVE' ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                       
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vent Filter Replacement Record', 'I');
    }
     else if ($_GET["type"] == "downloadNonCriticalFilter") {
        $_GET['filename'] = 'Vent Filter Replacement Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width: 10%;">Sr.</td>
                        <td rowspan="2" style="width: 10%;">Plant Name.</td>
                        <td rowspan="2" style="width: 10%;">Location</td>
                         <td rowspan="2" style="width: 10%;">Filter Id</td>
                        <td style="width: 30%;text-align:center;">MOC</td>
                       <td style="width: 30%;text-align:center;">Filter Cartridge Details</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Housing</td>
                        <td style="width:15%;">Cartridge</td>
                         <td style="width:10%;">Micron</td>
                          <td style="width:10%;">Diameter</td>
                           <td style="width:10%;">Length</td>
                    </tr>
                </thead>';
            $sql ="SELECT * FROM ventfilter_replacement WHERE status='pending' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vent Filter Replacement Record', 'I');
     }
    else if ($_GET["type"] == "downloadReplacementRecord") {
        $_GET['filename'] = 'Vent Filter Replacement Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td rowspan="2" style="width: 20%;">Filter Id No.</td>
                        <td rowspan="2" style="width: 25%;">Date Of Filter Replacement</td>
                        <td style="width: 30%;text-align:center;">Department</td>
                        <td rowspan="2" style="width: 25%;">Remark (If Any)</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Done By</td>
                        <td style="width:15%;">Check By</td>
                    </tr>
                </thead>';
            $sql ="SELECT * FROM ventfilter_replacement WHERE status='pending' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 20%;">'.$row['filter_id'].'</td>
                        <td style="width: 25%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 15%;">'.$row['entry_by'].'</td>
                        <td style="width: 15%;">'.$row['check_by'].'</td>
                        <td style="width: 25%;">'.$row['remark'].'</td>
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vent Filter Replacement Record', 'I');
    
    }
    
}

$conn->close();
?>