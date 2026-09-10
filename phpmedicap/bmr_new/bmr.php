<?php 
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
  $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
$token = $_GET["token"];
  $currentUrl =$_GET["description"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
 if ($_GET["type"] == "bmr_stages") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        
       	   $sql = "select * from bmr_stages where stage='".$_GET["Stage_id"]."' and step_id='".$_GET["stpe_id"]."' and product_code='".$input["product_code"]."' and work_order_no='".$input["work_order_no"]."' and stages_id='".$input["stages_step_id"]."'";
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
    	echo "{\"status\":\"success\"}";
    }else 
         {
         $sql = "INSERT INTO bmr_stages (stage,step_id,product_code,work_order_no,stages_id) VALUES ('".$_GET["Stage_id"]."','".$_GET["stpe_id"]."','".$input["product_code"]."','".$input["work_order_no"]."','".$input["stages_step_id"]."')";
    	if($conn->query($sql)){
    	    $product_id = $conn->insert_id;    
    	            $json_obj = json_encode($input["bmr_logbook"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
              $sql = "INSERT INTO `bmr_logbook`(  equipment_cleaning_id,`bmr_stages_id`, `activity_type`,   `equipment_code`, `status`, 
            `equipment_name`, `room_name`, `room_code`, `form_no`, `title`, `entry_by`, `entry_date`,`plant_id`, 
            `sequence`,stage,step_id,stages_id) VALUES ( '".$values["id"]."','$product_id','".$values["activity_type"]."','".$values
            ["equipment_code"]."','".$values["status"]."','".$values["equipment_name"]."','".$values
            ["room_name"]."','".$values["room_code"]."','".$values["form_no"]."','".$values["title"]."',
            '".$_GET["emp_id"]."','$entry_date','".$_GET["plant_id"]."','".$values["sequence"]."','".$_GET["Stage_id"]."','".$_GET["stpe_id"]."','".$input["stages_step_id"]."')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
    	    
    	    
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        }
    }
    
      else if ($_GET["type"] == "masterBMR") {
                
                $_GET['filename'] = 'Products';
$_GET['pdftype'] = 'onlyheader';
include("../pdfimp2.php");

$html = "";
$sql = "SELECT * FROM bmr_process WHERE id = '".$_GET["id"]."'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sql2 = "SELECT * FROM bmr_process_stage WHERE bmr_process_id = '".$row["id"]."' ORDER BY id ASC";
        $result2 = $conn->query($sql2);

        $stageCount = 1;
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $html .= "<h3>$stageCount.   " . $row2['stages'] . "</h3>";

                $sql3 = "SELECT * FROM bmr_process_stages_step WHERE bmr_process_stage_id = '".$row2["id"]."'";
                $result3 = $conn->query($sql3);

                $stepCount = 1;
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        $html .= "<h4 style='margin-left:20px;'>$stageCount.$stepCount   " . $row3['step'] . "</h4>";

                        $sql4 = "SELECT * FROM bmr_process_stages_step_substep WHERE bmr_process_stages_step_id = '".$row3["id"]."' AND bmr_process_stage_id = '".$row2["id"]."'";
                        $result4 = $conn->query($sql4);

                        $substepCount = 1;
                        if ($result4->num_rows > 0) {
                            while ($row4 = $result4->fetch_assoc()) {
                                $html .= "<p style='margin-left:40px;'>$stageCount.$stepCount.$substepCount   " . $row4['substep'] . "</p>";
                                $substepCount++;
                            }
                        }
                        $stepCount++;
                    }
                }
                $stageCount++;
            }
        }
    }
}

// Write to PDF
$pdf->writeHTML($html, true, false, false, false, '');
$pdf->Output('BrandProductLog.pdf', 'I');

            }
       else if ($_GET["type"] == "complete_bmr_masterZuma") {
            
           if($input["status"]=='Complete'){
               
                 $sql = "UPDATE product SET `bmr_status`='".$input["status"]."' ,`complete_by`='".$_GET["emp_id"]."',`complete_on`='$entry_date'   where product_code='".$input["product_code"]."'";
           }
           if($input["status"]=='review'){
               
                  $sql = "UPDATE product SET `bmr_status`='".$input["status"]."' ,`review_by`='".$_GET["emp_id"]."' ,`review_on`='$entry_date'  where product_code='".$input["product_code"]."'";
           }
           if($input["status"]=='approved'){
               
                 $sql = "UPDATE product SET `bmr_status`='".$input["status"]."' ,`approved_by`='".$_GET["emp_id"]."' ,`approved_on`='$entry_date'  where product_code='".$input["product_code"]."'";
           }
           if($input["status"]=='pending'){
               
                 $sql = "UPDATE product SET `bmr_status`='".$input["status"]."' ,`approved_by`='' ,`approved_on`=''  where product_code='".$input["product_code"]."'";
           }
        if ($conn->query($sql)) {
            
            
$entery_date = date('Y-m-d H:i:s');

 if($input["status"]=='Complete'){
$sql = "INSERT INTO bmr_process (  product_code,dosage_form,DocumentNo,DocumentTitle, entry_by, entry_date) 
        VALUES ('".$input["product_code"]."','".$input["dosage_form"]."', '".$input["DocumentNo"]."', '".$input["DocumentTitle"]."', '".$_GET["emp_id"]."', '$entery_date')";

if ($conn->query($sql)) {
    $process_type_id = $conn->insert_id;    
    
    foreach ($input['stages'] as $stageData) {
        $sql1 = "INSERT INTO  bmr_process_stage(plant_id, bmr_process_id,stages, check_by, check_date) 
                 VALUES ('".$_GET["plant_id"]."', '$process_type_id', '".$stageData["stages"]."', '".$_GET["emp_id"]."', '$entery_date')";
        
        if ($conn->query($sql1)) {
            $process_stage_id = $conn->insert_id; 

            foreach ($stageData['steps'] as $stepData) { 
                $sql2 = "INSERT INTO  bmr_process_stages_step (bmr_process_stage_id, plant_id, step) 
                         VALUES ('$process_stage_id', '".$_GET['plant_id']."', '".$stepData['step']."')";

                if ($conn->query($sql2)) {
                    $process_stage_steps_id = $conn->insert_id;

                    foreach ($stepData['Substeps'] as $substepData) {
                        $sql3 = "INSERT INTO  bmr_process_stages_step_substep (bmr_process_stage_id, bmr_process_stages_step_id, plant_id, Substep) 
                                 VALUES ('$process_stage_id', '$process_stage_steps_id', '".$_GET['plant_id']."', '".$substepData['substep']."')";
                        $conn->query($sql3); // Optionally check success/failure here
                    }
                }
            }
        }
    }


    
    echo "{\"status\":\"success\"}";
} else {
    echo "{\"status\":\"".$conn->error."\"}";
}
}
else if($input["status"]=='review' ){
    
    $sql = "update bmr_process  set status='".$input["status"]."',`review_by`='".$_GET["emp_id"]."' ,`review_date`='$entry_date'  where id = '".$input["id"]."'";

            $conn->query($sql);
             
    echo "{\"status\":\"success\"}";
 }
else if($input["status"]=='approved' ){
    
    $sql = "update bmr_process  set status='".$input["status"]."',`approved_by`='".$_GET["emp_id"]."' ,`approved_date`='$entry_date'  where id = '".$input["id"]."'";

            $conn->query($sql);
             
    echo "{\"status\":\"success\"}";
 }
else {
    echo "{\"status\":\"".$conn->error."\"}";
 
}
 
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
        else if ($_GET["type"] == "saveProcedure2") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
            if($input["step"]=='procedure'){
                 $data = $input["procedures"];
                  $sql = "UPDATE bmr_stages SET `procedure`='".json_encode($data)."' ,procedure_status='Checked',procedure_entry_date='".$entry_date."',procedure_entry_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='LineClearance'){
                 $data = $input["LineClearance"];
                echo  $sql = "UPDATE bmr_stages SET `LineClearance`='".json_encode($data)."' ,LineClearance_status='Checked',LineClearance_entry_date='".$entry_date."',LineClearance_entry_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='LineClearance_check'){
                 $data = $input["LineClearance"];
                 $sql = "UPDATE bmr_stages SET LineClearance_status='Approved',LineClearance_approved_date='".$entry_date."',LineClearance_approved_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
           else if($input["step"]=='procedure_check'){
                 $data = $input["procedures"];
                 $sql = "UPDATE bmr_stages SET procedure_status='Approved',procedure_approved_date='".$entry_date."',procedure_approved_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='Equipments'){
                 $data = $input["equipmentsss"];
                 $sql = "UPDATE bmr_stages SET equipment='".json_encode($data)."' ,equipment_status='Checked',equipment_entry_date='".$entry_date."',equipment_entry_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='Equipments_check'){
                 $data = $input["equipmentsss"];
                 $sql = "UPDATE bmr_stages SET equipment_status='Approved',equipment_approved_date='".$entry_date."',equipment_approved_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='Cleaning_Checks'){
                 $data = $input["CleaningChecks"];
                 $sql = "UPDATE bmr_stages SET CleaningChecks='".json_encode($data)."' ,CleaningChecks_status='Checked',CleaningChecks_entry_date='".$entry_date."',CleaningChecks_entry_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='Cleaning_Checks_check'){
                 $data = $input["CleaningChecks"];
                 $sql = "UPDATE bmr_stages SET  CleaningChecks_status='Approved',CleaningChecks_approved_date='".$entry_date."',CleaningChecks_approved_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='Room'){
                 $data = $input["room"];
                 $sql = "UPDATE bmr_stages SET room='".json_encode($data)."' ,room_status='Checked',room_entry_date='".$entry_date."',room_entry_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='Room_check'){
                 $data = $input["room"];
                 $sql = "UPDATE bmr_stages SET room_status='Approved',room_approved_date='".$entry_date."',room_approved_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='roomActions'){
                 $data = $input["roomActions"];
                 $sql = "UPDATE bmr_stages SET roomActions='".json_encode($data)."' ,roomActions_status='Checked',roomActions_entry_date='".$entry_date."',roomActions_entry_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='roomActions_check'){
                 $data = $input["roomActions"];
                 $sql = "UPDATE bmr_stages SET  roomActions_status='Approved',roomActions_approved_date='".$entry_date."',roomActions_approved_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='weighinggg'){
                 $data = $input["weighings"];
                  $sql = "UPDATE bmr_stages SET weighing='".json_encode($data)."' ,weighing_status='Checked',weighing_entry_date='".$entry_date."',weighing_entry_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='weighinggg_check'){
                 $data = $input["weighings"];
                  $sql = "UPDATE bmr_stages SET  weighing_status='Approved',weighing_approved_date='".$entry_date."',weighing_approved_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='tabless'){
                 $data = $input["tables"];
                  $sql = "UPDATE bmr_stages SET tables='".$data."' ,table_status='Checked',tables_entry_date='".$entry_date."',tables_entry_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='tabless_check'){
                 $data = $input["weighings"];
                  $sql = "UPDATE bmr_stages SET  table_status='Approved',tables_approved_date='".$entry_date."',tables_approved_by='".$_GET["emp_id"]."' where stages_id='".$_GET["id"]."'";
            }
            else if($input["step"]=='logbook_prepare_master'){
                 $data = $input["PrepareMaster"];
                 $sql = "UPDATE bmr_logbook SET PrepareMaster='".json_encode($data)."' ,PrepareMaster_entry_date='".$entry_date."',PrepareMaster_entry_by='".$_GET["emp_id"]."'  where equipment_cleaning_id='".$input["id"]."'";
            }
            else if($input["step"]=='logbook_prepare_master_check'){
                 $data = $input["PrepareMaster"];
                 $sql = "UPDATE bmr_logbook SET  PrepareMaster_approved_date='".$entry_date."',PrepareMaster_approved_by='".$_GET["emp_id"]."'  where equipment_cleaning_id='".$input["id"]."'";
            }
            else if($input["step"]=='logbook_RoomLogin'){
                 $data = $input["RoomLogin"];
                 $sql = "UPDATE bmr_logbook SET RoomLogin='".json_encode($data)."' ,RoomLogin_entry_date='".$entry_date."',RoomLogin_entry_by='".$_GET["emp_id"]."' where equipment_cleaning_id='".$input["id"]."'";
            }
            else if($input["step"]=='logbook_RoomLogin_check'){
                 $data = $input["RoomLogin"];
                 $sql = "UPDATE bmr_logbook SET RoomLogin_approved_date='".$entry_date."',RoomLogin_approved_by='".$_GET["emp_id"]."' where equipment_cleaning_id='".$input["id"]."'";
            }
            else if($input["step"]=='logbook_RoomLogbook'){
                 $data = $input["RoomLogbook"];
                 $sql = "UPDATE bmr_logbook SET RoomLogbook='".json_encode($data)."' ,RoomLogbook_entry_date='".$entry_date."',RoomLogbook_entry_by='".$_GET["emp_id"]."' where equipment_cleaning_id='".$input["id"]."'";
            }
            else if($input["step"]=='logbook_RoomLogbook_check'){
                 $data = $input["RoomLogbook"];
                 $sql = "UPDATE bmr_logbook SET RoomLogbook_approved_date='".$entry_date."',RoomLogbook_approved_by='".$_GET["emp_id"]."' where equipment_cleaning_id='".$input["id"]."'";
            }
            else if($input["step"]=='logbook_RoomLogout'){
                 $data = $input["RoomLogout"];
                 $sql = "UPDATE bmr_logbook SET RoomLogout='".json_encode($data)."',RoomLogout_entry_date='".$entry_date."',RoomLogout_entry_by='".$_GET["emp_id"]."'  where equipment_cleaning_id='".$input["id"]."'";
            }
            else if($input["step"]=='logbook_RoomLogout_check'){
                 $data = $input["RoomLogout"];
                 $sql = "UPDATE bmr_logbook SET  RoomLogout_approved_date='".$entry_date."',RoomLogout_approved_by='".$_GET["emp_id"]."'  where equipment_cleaning_id='".$input["id"]."'";
            }
            else if($input["step"]=='logbook_EquipmentCleaning'){
                 $data = $input["EquipmentCleaning"];
                 $sql = "UPDATE bmr_logbook SET EquipmentCleaning='".json_encode($data)."' ,EquipmentCleaning_entry_date='".$entry_date."',EquipmentCleaning_entry_by='".$_GET["emp_id"]."' where equipment_cleaning_id='".$input["id"]."'";
            }
            else if($input["step"]=='logbook_EquipmentCleaning_check'){
                 $data = $input["EquipmentCleaning"];
                 $sql = "UPDATE bmr_logbook SET  EquipmentCleaning_approved_date='".$entry_date."',EquipmentCleaning_approved_by='".$_GET["emp_id"]."' where equipment_cleaning_id='".$input["id"]."'";
            }
            else if($input["step"]=='logbook_majorClean'){
                 $data = $input["majorClean"];
                 $sql = "UPDATE bmr_logbook SET majorClean='".json_encode($data)."',majorClean_entry_date='".$entry_date."',majorClean_entry_by='".$_GET["emp_id"]."'  where equipment_cleaning_id='".$input["id"]."'";
            }
            else if($input["step"]=='logbook_majorClean_check'){
                 $data = $input["majorClean"];
                 $sql = "UPDATE bmr_logbook SET majorClean_approved_date='".$entry_date."',majorClean_approved_by='".$_GET["emp_id"]."'  where equipment_cleaning_id='".$input["id"]."'";
            }

        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
        else if ($_GET["type"] == "saveallocarion") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
           
                 $sql = "UPDATE manufacturing_process_stages SET `Production_Office`='".$input["producton_ofc"]."' ,Alternate_Officer='".$input["alt_ofc"]."' ,Supervisor='".$input["supervisor"]."'  where id='".$input["id"]."'";
            

        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
        else if ($_GET["type"] == "complete_bmr_master") {
            
           if($input["status"]=='Complete'){
               
                 $sql = "UPDATE product SET `bmr_status`='".$input["status"]."' ,`complete_by`='".$_GET["emp_id"]."',`complete_on`='$entry_date'   where product_code='".$input["product_code"]."'";
           }
           if($input["status"]=='review'){
               
                  $sql = "UPDATE product SET `bmr_status`='".$input["status"]."' ,`review_by`='".$_GET["emp_id"]."' ,`review_on`='$entry_date'  where product_code='".$input["product_code"]."'";
           }
           if($input["status"]=='approved'){
               
                 $sql = "UPDATE product SET `bmr_status`='".$input["status"]."' ,`approved_by`='".$_GET["emp_id"]."' ,`approved_on`='$entry_date'  where product_code='".$input["product_code"]."'";
           }
           if($input["status"]=='pending'){
               
                 $sql = "UPDATE product SET `bmr_status`='".$input["status"]."' ,`approved_by`='' ,`approved_on`=''  where product_code='".$input["product_code"]."'";
           }
        if ($conn->query($sql)) {
            
            
            if($input["status"]=='pending'){
                            $json_obj = json_encode($input["stages"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                    
                      $sql = "SELECT * FROM `stages` WHERE stage='".$values["id"]."'";
     
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                if($row['isprocedure']=='2'){
                     $sql1="update stages set isprocedure='1' where isprocedure='2' and stage='".$values["id"]."'";
                 $result1 = $conn->query($sql1);
                }
                if($row['isroom']=='2'){
                     $sql1="update stages set isroom='1' where isroom='2' and stage='".$values["id"]."'";
                 $result1 = $conn->query($sql1);
                }
                if($row['isequipment']=='2'){
                     $sql1="update stages set isequipment='1' where isequipment='2' and stage='".$values["id"]."'";
                 $result1 = $conn->query($sql1);
                }
                if($row['isCleaningChecks']=='2'){
                     $sql1="update stages set isCleaningChecks='1' where isCleaningChecks='2' and stage='".$values["id"]."'";
                 $result1 = $conn->query($sql1);
                }
                if($row['isroomActions']=='2'){
                     $sql1="update stages set isroomActions='1' where isroomActions='2' and stage='".$values["id"]."'";
                 $result1 = $conn->query($sql1);
                }
                if($row['isEquipmemntCleaning']=='2'){
                     $sql1="update stages set isEquipmemntCleaning='1' where isEquipmemntCleaning='2' and stage='".$values["id"]."'";
                 $result1 = $conn->query($sql1);
                }
                if($row['istable']=='2'){
                     $sql1="update stages set istable='1' where istable='2' and stage='".$values["id"]."'";
                 $result1 = $conn->query($sql1);
                }
                if($row['isFraction']=='2'){
                     $sql1="update stages set isFraction='1' where isFraction='2' and stage='".$values["id"]."'";
                 $result1 = $conn->query($sql1);
                }
                if($row['isweighing']=='2'){
                     $sql1="update stages set isweighing='1' where isweighing='2' and stage='".$values["id"]."'";
                 $result1 = $conn->query($sql1);
                }
                if($row['isQcSample']=='2'){
                     $sql1="update stages set isQcSample='1' where isQcSample='2' and stage='".$values["id"]."'";
                 $result1 = $conn->query($sql1);
                }
                if($row['isFormats']=='2'){
                     $sql1="update stages set isFormats='1' where isFormats='2' and stage='".$values["id"]."'";
                 $result1 = $conn->query($sql1);
                }
                if($row['isQaReview']=='2'){
                     $sql1="update stages set isQaReview='1' where isQaReview='2' and stage='".$values["id"]."'";
                 $result1 = $conn->query($sql1);
                }
            
              
            }
        }
                  
           
                
            }
            }
            
            
            
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
         else if ($_GET["type"] == "getenapsulation_record") {
        $output = Array();
         $sql = "select * from Encapsulation where work_order_no='".$_GET['work_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                $output[] = $row;
            }
        }
        echo json_encode($output); 
    }
        else if ($_GET["type"] == "saveCleanEquipTag") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
           
                 $sql = "INSERT INTO `bmr_equipment_cleaning_tag`( `plant_id`, `work_order_no`, `product_code`, `batch_no`, `document_no`, `form_no`, `equipment_name`,
                 `equipment_code`, `effective_date`, `cleaning_status`, `entry_by`, `entry_date`) VALUES ( '".$input["plant_id"]."','".$input["work_order_no"]."',
                 '".$input["product_code"]."','".$input["batch_number"]."','".$input["DocumentNo"]."','".$input["form_no"]."','".$input["equipment_name"]."',
                 '".$input["equipment_code"]."','".$input["effective_date"]."','".json_encode($input["CleaStatusList"])."','".$_GET["emp_id"]."','$entry_date')";


        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
        else if ($_GET["type"] == "getCleanEquipTag") {
            
            
        $output = array();
         $sql = "SELECT * FROM bmr_equipment_cleaning_tag WHERE work_order_no='".$input["work_order_no"]."'  and  product_code='".$input["product_code"]."'  and  document_no='".$input["DocumentNo"]."'  and  batch_no='".$input["batch_number"]."'   "  ;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["cleaning_status"] = json_decode($row["cleaning_status"]); 
                                $output[] = $row;

            }
        }
        echo json_encode($output);
    
        }
 else if ($_GET["type"] == "saveroomClearanceForm") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
           
                 $sql = "INSERT INTO `bmr_Room_Clearance_Checks`(  `plant_id`, `work_order_no`, `product_code`, `batch_no`, `document_no`, `form_no`, `version_no`, 
                 `specification_no`, `RoomClearanceCheckList`, `effective_date`, `entry_by`, `entry_date`) VALUES ( '".$_GET["plant_id"]."','".$input["work_order_no"]."',
                 '".$input["product_code"]."','".$input["batch_number"]."','".$input["DocumentNo"]."','".$input["Form_no"]."','".$input["version_no"]."',
                 '".$input["specification_no"]."','".json_encode($input["CleaStatusList"])."','".$input["effective_date"]."','".$_GET["emp_id"]."','$entry_date')";


        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }

        else if ($_GET["type"] == "getroomClearanceForm") {
            
            
        $output = array();
         $sql = "SELECT * FROM bmr_Room_Clearance_Checks WHERE work_order_no='".$input["work_order_no"]."'  and  product_code='".$input["product_code"]."'  and  document_no='".$input["DocumentNo"]."'  and  batch_no='".$input["batch_number"]."'   "  ;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["RoomClearanceCheckList"] = json_decode($row["RoomClearanceCheckList"]); 
                                $output[] = $row;

            }
        }
        echo json_encode($output);
    
        }
 else if ($_GET["type"] == "bmr_empty_rm_capsule_bag_weight_record") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
           
                 $sql = "INSERT INTO `bmr_empty_rm_capsule_bag_weight_record`(  `plant_id`, `Form_no`, `version_no`, `specification_no`, `effective_date`,
                 `empy_rm_wt_list`, `entry_by`, `entry_date`, `product_code`, `batch_number`, `work_order_no`, `DocumentNo`) VALUES ('".$_GET["plant_id"]."','".$input["Form_no"]."','".$input["version_no"]."','".$input["specification_no"]."',
                 '".$input["effective_date"]."','".json_encode($input["empy_rm_wt_list"])."','".$_GET["enp_id"]."','$entry_date','".$input["product_code"]."','".$input["batch_number"]."','".$input["work_order_no"]."','".$input["DocumentNo"]."')";


        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
        else if ($_GET["type"] == "getbmr_empty_rm_capsule_bag_weight_record") {
            
            
        $output = array();
          $sql = "SELECT * FROM bmr_empty_rm_capsule_bag_weight_record WHERE work_order_no='".$input["work_order_no"]."'  and  product_code='".$input["product_code"]."'  and  DocumentNo='".$input["DocumentNo"]."'  and  batch_number='".$input["batch_number"]."'   "  ;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["empy_rm_wt_list"] = json_decode($row["empy_rm_wt_list"]); 
                                $output[] = $row;

            }
        }
        echo json_encode($output);
    
        }
 else if ($_GET["type"] == "save_qcSample_form") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
           
                 $sql = "INSERT INTO `bmr_qc_sample_weight_record`(  `plant_id`, `Form_no`, `version_no`, `specification_no`, `effective_date`,
                 `qcSample_form_list`, `entry_by`, `entry_date`, `product_code`, `batch_number`, `work_order_no`, `DocumentNo`) VALUES ('".$_GET["plant_id"]."','".$input["Form_no"]."','".$input["version_no"]."','".$input["specification_no"]."',
                 '".$input["effective_date"]."','".json_encode($input["qcSample_form_list"])."','".$_GET["enp_id"]."','$entry_date','".$input["product_code"]."','".$input["batch_number"]."','".$input["work_order_no"]."','".$input["DocumentNo"]."')";


        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
        else if ($_GET["type"] == "getqcSample_form_list") {
            
            
        $output = array();
          $sql = "SELECT * FROM bmr_qc_sample_weight_record WHERE work_order_no='".$input["work_order_no"]."'  and  product_code='".$input["product_code"]."'  and  DocumentNo='".$input["DocumentNo"]."'  and  batch_number='".$input["batch_number"]."'   "  ;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["qcSample_form_list"] = json_decode($row["qcSample_form_list"]); 
                                $output[] = $row;

            }
        }
        echo json_encode($output);
    
        }
 else if ($_GET["type"] == "savefilled_cap_list") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
           
                 $sql = "INSERT INTO `bmr_filled_capsule_weight_record`(  `plant_id`, `Form_no`, `version_no`, `specification_no`, `effective_date`,
                 `filled_cap_list`, `entry_by`, `entry_date`, `product_code`, `batch_number`, `work_order_no`, `DocumentNo`) VALUES ('".$_GET["plant_id"]."','".$input["Form_no"]."','".$input["version_no"]."','".$input["specification_no"]."',
                 '".$input["effective_date"]."','".json_encode($input["filled_cap_list"])."','".$_GET["enp_id"]."','$entry_date','".$input["product_code"]."','".$input["batch_number"]."','".$input["work_order_no"]."','".$input["DocumentNo"]."')";


        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
        else if ($_GET["type"] == "getfilled_cap_list") {
            
            
        $output = array();
          $sql = "SELECT * FROM bmr_filled_capsule_weight_record WHERE work_order_no='".$input["work_order_no"]."'  and  product_code='".$input["product_code"]."'  and  DocumentNo='".$input["DocumentNo"]."'  and  batch_number='".$input["batch_number"]."'   "  ;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["filled_cap_list"] = json_decode($row["filled_cap_list"]); 
                                $output[] = $row;

            }
        }
        echo json_encode($output);
     
        }
        else if ($_GET["type"] == "get_bmr_disp_dtl") {
            
            
        $output = array();
             $sql = "SELECT
                            a.*,
                            b.avbl_stock
                        FROM
                            (
                            SELECT
                                a.*,a.id as lot_id,
                               
                                b.category,
                                b.material_subtype,
                                b.grade AS m_grade,
                                COALESCE(b.material_name, p.product_name) AS material_name,
                                COALESCE(b.material_type, p.product_type) AS material_type,
                                wd.lod_status,
                                wd.assay_status,
                                wd.duispensing_in,
                                IFNULL(dd.id, 0) AS dispence_id,
                                dd.qa_status,
                                dd.prod_status,
                                dd.qa_checking,
                                dd.prod_checking,
                                (
                                SELECT
                                    SUM(mi.qty)
                                FROM
                                    material_issue mi
                                WHERE
                                    mi.dispensing_details_hdr_id = dd.id
                            ) AS disp_qty,
                            (
                            SELECT
                                entry_by
                            FROM
                                material_issue mi
                            WHERE
                                mi.dispensing_details_hdr_id = dd.id
                            GROUP BY
                                mi.entry_by
                        ) AS disp_entry_by,
                        (
                            SELECT
                                entry_date
                            FROM
                                material_issue mi
                            WHERE
                                mi.dispensing_details_hdr_id = dd.id
                            GROUP BY
                                mi.entry_date
                        ) AS disp_entry_date
                        FROM
                            work_order_batch_lots a
                        JOIN mfg_work_order_hdr c ON
                            a.work_order_id = c.id
                        LEFT JOIN mfg_work_order_dtl wd ON
                            wd.work_order_id = c.id AND wd.material_code = a.material_code
                        LEFT JOIN material b ON
                            a.material_code = b.material_code AND c.plant_id = b.plant_id
                        LEFT JOIN product p ON
                            a.material_code = p.product_code AND c.plant_id = p.plant_id
                        LEFT JOIN dispensing_details_hdr dd ON
                            a.id = dd.lot_id
                         
                 where a.work_order_id = '".$_GET["work_id"]."' and wd.duispensing_in='Collect In Production') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$_GET["work_id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code "  ;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                  $output2 = Array();
             $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no";
             $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                
               
                $output2[] = $row2;
            }
        }
                                $row['ar_data']=$output2;
                
                                $output[] = $row;

            }
        }
        echo json_encode($output);
    
        }

}

$conn->close();
?>