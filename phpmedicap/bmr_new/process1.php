<?php 

        ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db.php';
require '../token.php';
$output = Array();
$token = $_GET["token"];
  $currentUrl =$_GET["description"];
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
    
    if ($_GET["type"] == "getProcesses") {

        $output = Array();
        $data = array();
            $output = Array();
            $sql = "SELECT * FROM manufacturing_process where  plant_id ='".$_GET["plant_id"]."' and id='".$_GET['id']."' order by id desc ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output2 = Array();
                    $sql2 = "SELECT * FROM manufacturing_process_stages where manufacturing_process_id =  '".$row["id"]."' and plant_id ='".$_GET["plant_id"]."' order by id asc";
                    $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                
                                $row2["forms_list"] = json_decode($row2["forms_list"]); 
                                $output3 = Array();
                                $sql3 = "SELECT * FROM manufacturing_process_step where manufacturing_process_stages_id =  '".$row2["id"]."'  and plant_id ='".$_GET["plant_id"]."' ";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                           $output4 = Array();
                                            $total_sum_c = 0;

// Query to get the sequence from stages
     $sql4 = "SELECT id,sequence FROM stages a WHERE a.stage='".$row2["id"]."' AND a.step_id='".$row3["id"]."'";
$result4 = $conn->query($sql4);

if ($result4->num_rows > 0) {
    while ($row4 = $result4->fetch_assoc()) {
    $sequence = json_decode($row4['sequence'], true);


        // Check if 'sequence' is an array and contains items
        if (is_array($sequence)) {
            foreach ($sequence as $seq_item) {
              
                if (isset($seq_item['list'])) {
                    $list = $seq_item['list'];

                    
                     $sql_list = "SELECT * FROM stages WHERE $list = '2' AND id='".$row4["id"]."'";
                    $result_list = $conn->query($sql_list);

                    // Initialize $c to 0 for this iteration
                    $c = 0;

                    // Process the result of the query
                    if ($result_list->num_rows > 0) {
                        // If rows are found, set $c to 1
                        $c = 1;
                    }

                    // Add $c to total sum
                    $total_sum_c += $c;
                }
            }
        }
    }
     $length = count($sequence);
    if($total_sum_c==2){
        $total_sum_c==1;
    }
    else if($total_sum_c==4){
        $total_sum_c==2;
    }else{
        if($length !==0 && $length==$total_sum_c){
            
         $total_sum_c=$total_sum_c;
        }else if ($length !==0 && $length < $total_sum_c){
            
            $total_sum_c=$total_sum_c-2;
        }
        
    }
    $length = count($sequence);
 $row3['$total_sum_c'] = $total_sum_c;
                $row3['sequence'] = $sequence;
                $row3['length'] = $length;
    if($length !==0 && $row3['length']==$row3['$total_sum_c']){
        $row3['View']='true';
    }else{
        $row3['View']='false';
        
    }
                                            }
                                            else{
                                                  $row3['View']='false';
                                            }
                                   
                                    $output3[] = $row3;
                                    }
                                }
                                $row2["Steps"] = $output3;
                                $output2[] = $row2;
                            }
                        }
                    $row["Stages"] = $output2;
                     
                    $output[] = $row;
                }
                
            }
        echo json_encode($output);
      
    } 
    if ($_GET["type"] == "getProcesses_for_product") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $output = Array();
        $data = array();
            $output = Array();
            $sql = "SELECT * FROM manufacturing_process where  plant_id ='".$_GET["plant_id"]."' and product_code='".$_GET["product_code"]."' order by id desc ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output2 = Array();
                    $sql2 = "SELECT * FROM manufacturing_process_stages where manufacturing_process_id =  '".$row["id"]."' and plant_id ='".$_GET["plant_id"]."'";
                    $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output3 = Array();
                                $sql3 = "SELECT * FROM manufacturing_process_step where manufacturing_process_stages_id =  '".$row2["id"]."'  and plant_id ='".$_GET["plant_id"]."' ";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                  
                                    $output3[] = $row3;
                                    }
                                }
                                $row2["Steps"] = $output3;
                                $output2[] = $row2;
                            }
                        }
                    $row["Stages"] = $output2;
                     
                    $output[] = $row;
                }
                
            }
        echo json_encode($output);
      
    } 
    else if ($_GET["type"] == "getProcessesBMR") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $output = Array();
        $data = array();
            $output = Array();
            $sql = "SELECT a.* from manufacturing_process a where a.product_code='".$_GET["product_code"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output2 = Array();
                     $sql2 = "SELECT *,(SELECT id  from bmr_stages b where a.id=b.stage and b.work_order_no='".$_GET["work_order_no"]."' limit 1) as bmr_stage_id 
                                ,(SELECT saved_by from bmr_stages b where a.id=b.stage and b.work_order_no='".$_GET["work_order_no"]."' limit 1) as bmr_saved_by 
                                ,(SELECT approve_by from bmr_stages b where a.id=b.stage and b.work_order_no='".$_GET["work_order_no"]."' limit 1) as bmr_approve_by
                                FROM manufacturing_process_stages a where a.for_department='".$_GET["dep_name"]."' and a.manufacturing_process_id =  '".$row["id"]."' and a.plant_id ='".$_GET["plant_id"]."' ORDER BY ID ASC";
                    $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output3 = Array();
                                $sql3 = "SELECT * FROM manufacturing_process_step where manufacturing_process_stages_id =  '".$row2["id"]."'  and plant_id ='".$_GET["plant_id"]."' ";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        
                                        
                                        
                                  
                                    $output3[] = $row3;
                                    }
                                }
                                $row2["Steps"] = $output3;
                                $output2[] = $row2;
                            }
                        }
                    $row["Stages"] = $output2;
                    $row["Forms1"] = json_decode($row["Forms"]);
                     
                     
                     
                    $output[] = $row;
                }
                
            }
        echo json_encode($output);
      
    } 
    else if ($_GET["type"] == "getProcessesBMRLog") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $output = Array();
        $data = array();
            $output = Array();
            $sql = "SELECT a.* from manufacturing_process a where a.product_code='".$_GET["product_code"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output2 = Array();
                    $sql2 = "SELECT * FROM manufacturing_process_stages where manufacturing_process_id =  '".$row["id"]."' and plant_id ='".$_GET["plant_id"]."'";
                    $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output3 = Array();
                                $sql3 = "SELECT * FROM manufacturing_process_step where manufacturing_process_stages_id =  '".$row2["id"]."'  and plant_id ='".$_GET["plant_id"]."' ";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        
                                        	$output4 = Array();
                                      	  $sql4 = "select a.*,b.table_status as bmr_table_status,b.procedure_status as bmr_procedure_status ,b.equipment_status as bmr_equipment_status,b.CleaningChecks_status as bmr_CleaningChecks_status
                                     	  ,b.room_status as bmr_room_status ,b.roomActions_status as bmr_roomActions_status,b.weighing_status as bmr_weighing_status,
                                     	  b.procedure as bmr_pocedure,b.equipment as bmr_equipment,b.CleaningChecks as bmr_CleaningChecks,b.room as bmr_room,b.weighing as bmr_weighing,b.roomActions as bmr_roomActions,
                                     	  procedure_entry_by,procedure_entry_date,procedure_approved_by,procedure_approved_date,room_entry_by,room_entry_date,room_approved_by,room_approved_date,
                                     	  equipment_entry_by,equipment_entry_date,equipment_approved_by,equipment_approved_date,CleaningChecks_entry_by,CleaningChecks_entry_date,CleaningChecks_approved_by,
                                     	  CleaningChecks_approved_date,roomActions_entry_by,roomActions_entry_date,roomActions_approved_by,roomActions_approved_date,weighing_entry_by,weighing_entry_date,
                                     	  weighing_approved_by,weighing_approved_date
                                     	  from stages a left join bmr_stages b on a.stage=b.stage and a.step_id=b.step_id and a.id=b.stages_id  where a.stage='".$row2["id"]."' and a.step_id='".$row3["id"]."'";
                                    	$result4 = $conn->query($sql4);
                                    	if($result4->num_rows > 0){
                                    		while($row4 = $result4->fetch_assoc()){
                                    		      $row4["sequence"] = json_decode($row4["sequence"]);
                                    		      $row4["procedures"] = json_decode($row4["procedure"]);
                                    		    
                                    		      $row4["bmr_pocedure"] = json_decode($row4["bmr_pocedure"]);
                                    		      
                                    		      $row4["equipment"] = json_decode($row4["equipment"]);
                                    		      $row4["bmr_equipment"] = json_decode($row4["bmr_equipment"]);
                                    		      
                                    		      $row4["CleaningChecks"] = json_decode($row4["CleaningChecks"]);
                                    		      $row4["bmr_CleaningChecks"] = json_decode($row4["bmr_CleaningChecks"]);
                                    		      
                                    		      $row4["room"] = json_decode($row4["room"]);
                                    		      $row4["bmr_room"] = json_decode($row4["bmr_room"]);
                                    		      
                                    		       $row4["table"] =  $row4["table"];
        		                                   $row4["bmr_table"] = $row4["bmr_table"];
                                    		      
                                    		      $row4["weighing"] = json_decode($row4["weighing"]);
                                    		      $row4["bmr_weighing"] = json_decode($row4["bmr_weighing"]);
                                    		      
                                    		      $row4["roomActions"] = json_decode($row4["roomActions"]);
                                    		      $row4["bmr_roomActions"] = json_decode($row4["bmr_roomActions"]);
                                    		      $row4["instructions"] = json_decode($row4["instructions"]);
                                    		      $row4["initial_checks"] = json_decode($row4["initial_checks"]);
                                    		      $row4["environments"] = json_decode($row4["environments"]);
                                    		      $row4["inprocess"] = json_decode($row4["inprocess"]);
                                    		      $row4["EquipmemntCleaning"] = json_decode($row4["EquipmemntCleaning"]);
                                    		      $row4["QcSample"] = json_decode($row4["QcSample"]);
                                    		      $row4["Logbook"] = json_decode($row4["Logbook"]);
                                    		      
                                    		       $array = $row4['Logbook'];


                                                  $logbook_length = count($array);
                                                  
                                                  
                      $output33 = Array();    
           for ($i = 0; $i < $logbook_length; $i++) {
     
        $values = $array[$i];
             $sql33 = "SELECT a.*,JSON_LENGTH(b.PrepareMaster) AS prepare_master_length,JSON_LENGTH(b.RoomLogin) AS RoomLogin_length,JSON_LENGTH(b.RoomLogbook) AS RoomLogbook_length
        ,JSON_LENGTH(b.RoomLogout) AS RoomLogout_length,JSON_LENGTH(b.EquipmentCleaning) AS EquipmentCleaning_length,JSON_LENGTH(b.majorClean)AS majorClean_length 
        ,b.PrepareMaster as bmr_PrepareMaster,b.RoomLogin as bmr_RoomLogin,b.RoomLogbook as bmr_RoomLogbook,b.RoomLogout as bmr_RoomLogout,
        b.EquipmentCleaning as bmr_EquipmentCleaning,b.majorClean as bmr_majorClean,
        b.majorClean_approved_by,b.EquipmentCleaning_approved_by,b.RoomLogout_approved_by,b.RoomLogbook_approved_by,b.RoomLogin_approved_by,b.PrepareMaster_approved_by,
        b.majorClean_approved_date,b.EquipmentCleaning_approved_date,b.RoomLogout_approved_date,b.RoomLogbook_approved_date,b.RoomLogin_approved_date,b.PrepareMaster_approved_date,
b.majorClean_entry_by,b.EquipmentCleaning_entry_by,b.RoomLogout_entry_by,b.RoomLogbook_entry_by,b.RoomLogin_entry_by,b.PrepareMaster_entry_by,
b.majorClean_entry_by,b.EquipmentCleaning_entry_by,b.RoomLogout_entry_by,b.RoomLogbook_entry_by,b.RoomLogin_entry_by,b.PrepareMaster_entry_by
        FROM equipment_cleaning a left join bmr_logbook b on a.id=b.equipment_cleaning_id  WHERE b.stage='".$row4["stage"]."'  and b.step_id='".$row4["step_id"]."' and  a.form_no='" . $values->form_no . "'";
        // FROM equipment_cleaning a left join bmr_logbook b on a.id=b.equipment_cleaning_id  WHERE  a.form_no='" . $values->form_no . "'";
        $result33 = $conn->query($sql33);
        if ($result33 && $result33->num_rows > 0) {
            
            // Loop through each row of the result set
            while ($row33 = $result33->fetch_assoc()) {
                    $row33["PrepareMaster"] = json_decode($row33["PrepareMaster"]);
                    $row33["bmr_PrepareMaster"] = json_decode($row33["bmr_PrepareMaster"]);
                    $row33["RoomLogin"] = json_decode($row33["RoomLogin"]);
                    $row33["bmr_RoomLogin"] = json_decode($row33["bmr_RoomLogin"]);
                    $row33["RoomLogbook"] = json_decode($row33["RoomLogbook"]);
                    $row33["bmr_RoomLogbook"] = json_decode($row33["bmr_RoomLogbook"]);
                    $row33["EquipmentCleaning"] = json_decode($row33["EquipmentCleaning"]);
                    $row33["bmr_EquipmentCleaning"] = json_decode($row33["bmr_EquipmentCleaning"]);
                    $row33["majorClean"] = json_decode($row33["majorClean"]);
                    $row33["bmr_majorClean"] = json_decode($row33["bmr_majorClean"]);
                    $row33["RoomLogout"] = json_decode($row33["RoomLogout"]);
                    $row33["bmr_RoomLogout"] = json_decode($row33["bmr_RoomLogout"]);
                $output33[] = $row33; // Append each row to the output array
                
              
            }
        }
    } 
        $row4["equipments_cleaning"] = $output33;
    		  
         
                                                                
                                                                $output4[] = $row4;        
                                    		}
                                    	    
                                    	}   
                                    	$row3["final_Steps"] = $output4;
                                    $output3[] = $row3;
                                    }
                                }
                                $row2["Steps"] = $output3;
                                $output2[] = $row2;
                            }
                        }
                    $row["Stages"] = $output2;
                     
                    $output[] = $row;
                }
                
            }
        echo json_encode($output);
      
    } 
    else if ($_GET["type"] == "get_stages") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $output = Array();
        $data = array();
            $output = Array();
            $sql = "SELECT * FROM manufacturing_process_stages where id ='".$_GET["stage_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output2 = Array();
                    $sql2 = "SELECT * FROM manufacturing_process_step where manufacturing_process_stages_id =  '".$row["id"]."'  and plant_id ='".$_GET["plant_id"]."'";
                    $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                         
                               
                                $output2[] = $row2;
                            }
                        }
                    $row["steps"] = $output2;
                     
                    $output[] = $row;
                }
                
            }
        echo json_encode($output);
      
    } 
    // if ($_GET["type"] == "getProcesses") {
    //     $output = Array();
    //     $data = array();
    //         $output = Array();
    //         $sql = "SELECT distinct dosage_form FROM manufacturing_process where  plant_id ='".$_GET["plant_id"]."'  order by dosage_form";
    //         $result = $conn->query($sql);
    //         if ($result->num_rows > 0) {
    //             while ($row = $result->fetch_assoc()) {
    //                 $output2 = Array();
    //                 $sql2 = "SELECT distinct process_type FROM manufacturing_process where dosage_form =  '".$row["dosage_form"]."' and plant_id ='".$_GET["plant_id"]."'  order by process_type";
    //                 $result2 = $conn->query($sql2);
    //                     if ($result2->num_rows > 0) {
    //                         while ($row2 = $result2->fetch_assoc()) {
    //                             $output3 = Array();
    //                             $sql3 = "SELECT distinct stage FROM manufacturing_process where dosage_form =  '".$row["dosage_form"]."' and process_type =  '".$row2["process_type"]."' and plant_id ='".$_GET["plant_id"]."'  order by stage";
    //                             $result3 = $conn->query($sql3);
    //                             if ($result3->num_rows > 0) {
    //                                 while ($row3 = $result3->fetch_assoc()) {
    //                                     $output4 = Array();
    //                                     $sql4 = "SELECT distinct step FROM manufacturing_process where dosage_form =  '".$row["dosage_form"]."' and process_type =  '".$row2["process_type"]."' and stage =  '".$row3["stage"]."'  and plant_id ='".$_GET["plant_id"]."'  order by step";
                                        
    //                                     $result4 = $conn->query($sql4);
    //                                     if ($result4->num_rows > 0) {
    //                                         while ($row4 = $result4->fetch_assoc()) {
    //                                             $output4[] = $row4;
    //                                         }
    //                                     }
    //                                 $row3["steps"] =    $output4;
    //                                 $output3[] = $row3;
    //                                 }
    //                             }
    //                             $row2["stages"] = $output3;
    //                             $output2[] = $row2;
    //                         }
    //                     }
    //                 $row["process_types"] = $output2;
                     
    //                 $output[] = $row;
    //             }
                
    //         }
    //     echo json_encode($output);
      
    // } 
    else if ($_GET["type"] == "savebmrproducts") {
        
        
         $sql="SELECT * FROM bmr_products WHERE product_code='".$input["product"]."'
          AND plant_id ='".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
       // echo $sql;
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"product Already Exists. Duplicate Values are not allowed\"}";
        }else{
          $sql = "INSERT INTO bmr_products (plant_id,product_code, dosage_form)values('".$_GET["plant_id"]."','".$input["product"]."','".$input["dosage_form"]."')";

            if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
        
    }
    else if ($_GET["type"] == "saveStageList") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $list=$input["stage_lists"];
 $flag=0;
           foreach ($list as $sublist) {
        // Iterate over each instruction in the sublist
        foreach ($sublist as $instruction) {
            // Insert the instruction into the database
            $instruction_sql = "INSERT INTO manufacturing_process_step_list ( stageName,step_id, instruction,plant_id)
            VALUES ('".$instruction["stageName"]."', '".$instruction["step_id"]."', '".$instruction["instruction"]."', '".$_GET["plant_id"]."')";

            if ($conn->query($instruction_sql)) {
                $flag=1;
                // echo "{\"status\":\"".$conn->error."\"}";
                // Rollback the transaction or handle error as needed
            }else{
                  $flag=0;
                // echo "{\"status\":\"success\"}";
            }
        }
    }
     if ($flag==1) {
                echo "{\"status\":\"success\"}";
                // Rollback the transaction or handle error as needed
            }else{
                echo "{\"status\":\"".$conn->error."\"}";
            }
 
        
    }
    else if ($_GET["type"] == "getSteps_table") {
	$output = Array();

     	      $sql="SELECT tables_DATA FROM manufacturing_process_step WHERE id='".$_GET["stpe_id"]."'";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);
 }
    else if ($_GET["type"] == "getProcess_types") {
	$output = Array();

     	      $sql="SELECT process_type FROM manufacturing_process WHERE dosage_form='".$_GET["dosage_form"]."'
          AND plant_id ='".$_GET["plant_id"]."' group by process_type ";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);
 }
    else if ($_GET["type"] == "getProcess_stage") {
	$output = Array();

     	      $sql="SELECT stage FROM manufacturing_process WHERE dosage_form='".$_GET["dosage_form"]."' and process_type='".$_GET["process_type"]."'
          AND plant_id ='".$_GET["plant_id"]."' group by stage ";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);
 }
    else if ($_GET["type"] == "getProcess_step") {
	$output = Array();

     	      $sql="SELECT step FROM manufacturing_process WHERE dosage_form='".$_GET["dosage_form"]."' and process_type='".$_GET["process_type"]."'
        and stage='".$_GET["stage"]."' AND plant_id ='".$_GET["plant_id"]."' group by step ";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);
 }
    // else if ($_GET["type"] == "savebmrproducts") {
        
    //      $sql = "INSERT INTO bmr_products (plant_id,product_code, dosage_form)values('".$_GET["plant_id"]."','".$input["product"]."','".$input["dosage_form"]."')";
    //           if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //      }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
   
    // }
         
    
    else if ($_GET["type"] == "saveProcess") {
 
        
         $sql="SELECT * FROM manufacturing_process WHERE DocumentTitle='".$input["ProcessTitle"]."'
          AND DocumentNo='".$input["DocumentNo"]."' AND plant_id ='".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
      // echo $sql;
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Process Type Already Exists. Duplicate Values are not allowed\"}";
        }else{
        $flag = 0;
         $sql = "INSERT INTO manufacturing_process (plant_id,DocumentTitle,DocumentNo,product_code,Forms) VALUES ('".$_GET["plant_id"]."','".$input["ProcessTitle"]."','".$input["DocumentNo"]."','".$input["product_code"]."','".json_encode($input["Forms"])."')";
            if ($conn->query($sql)) {
        $last_id = $conn->insert_id;
        $stagess=$input['stages'];
        for ($i = 0; $i < count($stagess); $i++) {
            $data = $stagess[$i];
             $sql = "INSERT INTO manufacturing_process_stages (forms_list,plant_id,manufacturing_process_id, stages,for_department)
                        VALUES ('".$data["forms_list"]."','".$_GET["plant_id"]."','$last_id','".$data["stage"]."','".$input["for_department"]."')";
            if ($conn->query($sql)) {
                $stage_id = $conn->insert_id; // Get the last inserted stage ID
        
        // Assuming $data["steps"] is an array of steps
        foreach ($data["steps"] as $step) {
             $sql = "INSERT INTO manufacturing_process_step (plant_id, manufacturing_process_stages_id, step,checking_in,split_lot) VALUES ('".$_GET["plant_id"]."', '$stage_id', '".$step["step"]."','".$step["Checking_in"]."', '".$step["split_lot"]."')";
            if (!$conn->query($sql)) {
                $flag = 0;
                break; // Exit the loop if there's an error
            }
        }
                
                $flag = 1;
            } else {
                $flag = 0;
                break;
            }
        }
            }
        if ($flag == 1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
            
        }
    
 
    }
  
    
 else if ($_GET["type"] == "saveMFGProcess") {
     
//      ini_set('display_errors', 1);
// error_reporting(E_ALL);
      $sql="SELECT * FROM manufacturing_process WHERE DocumentTitle='".$input["doc_name"]."'
          AND DocumentNo='".$input["doc_no"]."' AND plant_id ='".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
      // echo $sql;
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Process Type Already Exists. Duplicate Values are not allowed\"}";
        }else{
  
         $sql = "INSERT INTO manufacturing_process (DocumentTitle,DocumentNo,effective_date,product_code,for_department,plant_id) VALUES ('".$input["doc_name"]."','".$input["doc_no"]."','".$input["effective_date"]."','".$input["product_code"]."','".$input["doc_type"]."','".$_GET["plant_id"]."' )";
            if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>" Inserted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
     
 }
 }
    // else if ($_GET["type"] == "saveProcess") {
        
        
    //      $sql="SELECT * FROM manufacturing_process WHERE process_type='".$_GET["process_type"]."'
    //       AND dosage_form='".$_GET["dosage_form"]."' AND plant_id ='".$_GET["plant_id"]."' ";
    //     $result =$conn->query($sql);
    //   // echo $sql;
    //     if ($result->num_rows > 0) {
    //       	echo "{\"status\":\"Process Type Already Exists. Duplicate Values are not allowed\"}";
    //     }else{
    //     $flag = 0;
    //     for ($i = 0; $i < count($input); $i++) {
    //         $data = $input[$i];
    //         $sql = "INSERT INTO manufacturing_process (plant_id,user_no, dosage_form, process_type, stage, step, entry_by, entry_date,line_clearance,
    //                 inprocess_checks,ipqc_test) VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."', '".$data["dosage_form"]."', '".$data["process_type"]."',
    //                 '".$data["stage"]."', '".$data["step"]."', '".$_GET["emp_id"]."', '$entry_date', '".$data["line_clearance"]."', '".$data["inprocess_checks"]."', '".$data["ipqc_test"]."')";
    //         if ($conn->query($sql)) {
    //             $flag = 1;
    //         } else {
    //             $flag = 0;
    //             break;
    //         }
    //     }
    //     if ($flag == 1) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }}
    // }
    else if ($_GET["type"] == "get_stage_step") {
        
          $output = Array();
          $sql="SELECT stage FROM manufacturing_process WHERE  plant_id ='".$_GET["plant_id"]."' and inprocess_checks='Applicable'  group by stage";
        $result =$conn->query($sql);

        if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                        $sql1 = "SELECT step FROM manufacturing_process WHERE stage='".$row["stage"]."'  ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                       
                        $row["step"] = $output1;
                        $output[] = $row;
                }
            }   echo json_encode($output);
    }
    else if ($_GET["type"] == "GET_SAVEgEN_INSTRUCTION") {
        
	$output = Array();

     	  $sql = "select * from ebmr_genral_instruction";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    }
    else if ($_GET["type"] == "GET_adddisp_chek") {
        
	$output = Array();

     	  $sql = "select * from pm_disp_checklist";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    }
    else if ($_GET["type"] == "SAVEgEN_INSTRUCTION_demo") {      
        $json_obj = json_encode($input["instruction"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
   $sql = "INSERT INTO ebmr_genral_instruction( plant_id, instruction,product_code) VALUES ('".$_GET["plant_id"]."','".$values["check_point"]."','".$_GET["product_code"]."')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    else if ($_GET["type"] == "SAVEgEN_INSTRUCTION") {
    
            $sql = "INSERT INTO ebmr_genral_instruction( plant_id, instruction) VALUES ('".$_GET["plant_id"]."','".$input["Particular"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "adddisp_chek") {
    
            $sql = "INSERT INTO pm_disp_checklist( plant_id, checkpoint,remark) VALUES ('".$_GET["plant_id"]."','".$input["checkpoint"]."','".$input["Remark"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "deldisp_chek") {
    
            $sql = "DELETE FROM pm_disp_checklist where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "DelSAVEgEN_INSTRUCTION") {
    
            $sql = "DELETE FROM ebmr_genral_instruction where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "GET_SAVEequipment") {
        
	$output = Array();

     	  $sql = "select * from bmr_Equipment_data";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    }
    else if ($_GET["type"] == "SAVE_bmr_Equipment_data") {
    
            $sql = "INSERT INTO bmr_Equipment_data( plant_id, capacity,equipment_code,equipment_name) VALUES ('".$_GET["plant_id"]."','".$input["capacity"]."','".$input["equipment_code"]."','".$input["equipment_name"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
        else if ($_GET["type"] == "Delbmr_Equipment_data") {
    
            $sql = "DELETE FROM bmr_Equipment_data where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "GET_warehouse_dis") {
        
	$output = Array();

     	  $sql = "select * from bmr_warehouse_dispensing_checklist";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    }
    else if ($_GET["type"] == "SAVEwarehouse_dis") {
    
            $sql = "INSERT INTO bmr_warehouse_dispensing_checklist( plant_id, checkpoint	,remark) VALUES ('".$_GET["plant_id"]."','".$input["Description"]."','".$input["Remark"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
        else if ($_GET["type"] == "Delbmr_SAVEwarehouse_dis") {
    
            $sql = "DELETE FROM bmr_warehouse_dispensing_checklist where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "GET_disp_chek") {
        	$output = Array();
     	  $sql = "select * from bmr_dispensing_cheklist";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
    else if ($_GET["type"] == "SAVE_disp_chek") {
            $sql = "INSERT INTO bmr_dispensing_cheklist( plant_id, checkpoint	,remark) VALUES ('".$_GET["plant_id"]."','".$input["checkpoint"]."','".$input["Remark"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if ($_GET["type"] == "Del_disp_chek") {
            $sql = "DELETE FROM bmr_dispensing_cheklist where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "GET_line_chek") {
        	$output = Array();
     	  $sql = "select * from bmr_lineclearance_cheklist";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
    else if ($_GET["type"] == "SAVE_line_chek") {
            $sql = "INSERT INTO bmr_lineclearance_cheklist( plant_id, checkpoint	,remark) VALUES ('".$_GET["plant_id"]."','".$input["Description"]."','".$input["Remark"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if ($_GET["type"] == "Del_line_chek") {
            $sql = "DELETE FROM bmr_lineclearance_cheklist where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "GET_line_chek_process") {
        	$output = Array();
     	  $sql = "select * from bmr_lineclearance_process_cheklist";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
    else if ($_GET["type"] == "SAVE_line_chek_process") {
            $sql = "INSERT INTO bmr_lineclearance_process_cheklist( plant_id, checkpoint	,remark) VALUES ('".$_GET["plant_id"]."','".$input["Description"]."','".$input["Remark"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if ($_GET["type"] == "Del_line_chek_process") {
            $sql = "DELETE FROM bmr_lineclearance_process_cheklist where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "SAVE_oprp_chek") {
            $sql = "INSERT INTO oprp_room( plant_id, room) VALUES ('".$_GET["plant_id"]."','".$input["room"]."')";
         if ($conn->query($sql)) {
             
             $product_id = $conn->insert_id;
            $sql1 = "INSERT INTO oprp_room_details(oprp_room_id,temp,humidity) VALUES ('$product_id','".$input["temp"]."','".$input["humidity"]."')";
             $conn->query($sql1);
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if ($_GET["type"] == "GET_oprp_chek") {
        	$output = Array();
     	  $sql = "select * from oprp_room";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		     $output1 = Array();
                        $sql1 = "SELECT * FROM oprp_room_details WHERE oprp_room_id='".$row["id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                          while ($row1 = $result1->fetch_assoc()) {
                                $output1 []= $row1;
                          }
                        }
                         $row["oprp_room_details"] = $output1;
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
        else if ($_GET["type"] == "GET_oprp_chek2") {
        	$output = Array();
     	  $sql = "select * from oprpccp_equip2 where work_order_id='".$_GET["work_id"]."' and section='".$_GET["section"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		     $output1 = Array();
                        $sql1 = "SELECT * FROM oprpccp_equip_dtl2 WHERE oprpccp_equip2_id='".$row["id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                          while ($row1 = $result1->fetch_assoc()) {
                                $output1 []= $row1;
                          }
                        }
                         $row["oprpccp_details"] = $output1;
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
      else if ($_GET["type"] == "Del_room") {
            $sql = "DELETE FROM oprp_room where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
             $sql1 = "DELETE FROM oprp_room_details where oprp_room_id='".$_GET["id"]."'";
             $conn->query($sql1);
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
      else if ($_GET["type"] == "delete_oprp_ccp2") {
            $sql = "DELETE FROM oprpccp_equip2 where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
             $sql1 = "DELETE FROM oprpccp_equip_dtl2 where oprpccp_equip2_id='".$_GET["id"]."'";
             $conn->query($sql1);
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
      else if ($_GET["type"] == "Del_room11") {
            $sql = "DELETE FROM oprpccp_equip where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
             $sql1 = "DELETE FROM oprpccp_equip_dtl where oprpccp_equip_id='".$_GET["id"]."'";
             $conn->query($sql1);
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "getblender") {
        	$output = Array();
     	  $sql = "SELECT * FROM equipment WHERE equipment_name like '%blender%'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
     else if ($_GET["type"] == "getsifterr") {
        	$output = Array();
     	  $sql = "SELECT * FROM equipment WHERE equipment_name like '%sifter%'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
     else if ($_GET["type"] == "saveoprpccp2") {
                $sql = "INSERT INTO oprpccp_equip2( plant_id, equipment,work_order_id,section,sp_bmr_sifting_id	) VALUES ('".$_GET["plant_id"]."','".$input["equipment"]."','".$_GET["work_id"]."','".$_GET["section"]."','".$_GET["sift_id"]."')";
         if ($conn->query($sql)) {
             
             $product_id = $conn->insert_id;
 
                     $sql1 = "INSERT INTO oprpccp_equip_dtl2(oprpccp_equip2_id, airpressure, cleanliness_discharge_channel, cleanliness_hoper, film_folds, heater_working,
        humidity, seal_cleanliness, seal_strength, sensitivity, tmep, wad_film_folds, wad_heater_working, wad_seal_cleanliness, wad_seal_strength, time,
        obervation, remark, fe, non_fe, ss) VALUES ( '$product_id','".$input["airpressure"]."',
        '".$input["cleanliness_discharge_channel"]."','".$input["cleanliness_hoper"]."','".$input["film_folds"]."','".$input["heater_working"]."',
'".$input["humidity"]."','".$input["seal_cleanliness"]."','".$input["seal_strength"]."','".$input["sensitivity"]."','".$input["temp"]."',
'".$input["wad_film_folds"]."','".$input["wad_heater_working"]."','".$input["wad_seal_cleanliness"]."','".$input["wad_seal_strength"]."',
'".$input["time"]."','".$input["observation"]."','".$input["remark"]."','".$input["FE"]."','".$input["NON_FE"]."','".$input["SS"]."')";
             $conn->query($sql1);
                
           
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "saveoprpccp") {
            $sql = "INSERT INTO oprpccp_equip( plant_id, blender,sifter,checkpoint,cleanliness) VALUES ('".$_GET["plant_id"]."','".$input["blender"]."','".$input["sifter"]."','".$input["checkpoint"]."','".$input["Cleanliness"]."')";
         if ($conn->query($sql)) {
             
             $product_id = $conn->insert_id;
             $json_obj = json_encode($input["ccrp_Checklist"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                     $sql1 = "INSERT INTO oprpccp_equip_dtl(oprpccp_equip_id,sieves,mesh_size) VALUES ('$product_id','".$values["sieves"]."','".$values["mesh_size"]."')";
             $conn->query($sql1);
                }
           
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "get_saveoprpccp") {
        	$output = Array();
     	  $sql = "select * from oprpccp_equip";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		     $output1 = Array();
                        $sql1 = "SELECT * FROM oprpccp_equip_dtl WHERE oprpccp_equip_id='".$row["id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                          while ($row1 = $result1->fetch_assoc()) {
                                $output1 []= $row1;
                          }
                        }
                         $row["oprpccp_details"] = $output1;
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
      else if ($_GET["type"] == "SAVE_bmr_sifting") {
            $sql = "INSERT INTO  bmr_sifting( plant_id, sift_end_time	,sift_start_time,	siftter_equip) VALUES ('".$_GET["plant_id"]."','".$input["sift_end_time"]."','".$input["sift_start_time"]."','".$input["sifter_equip"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
      else if ($_GET["type"] == "SAVE_bmr_blending") {
            $sql = "INSERT INTO  bmr_blending( plant_id, blend_end_time	,blend_start_time,	blender,Processing) VALUES ('".$_GET["plant_id"]."','".$input["blend_end_time"]."','".$input["blend_start_time"]."','".$input["blender"]."','".$input["Processing"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    //   else if ($_GET["type"] == "SAVE_bmr_blending") {
    //         $sql = "INSERT INTO  bmr_blending( plant_id, blend_end_time	,blend_start_time,	blender,Processing) VALUES ('".$_GET["plant_id"]."','".$input["blend_end_time"]."','".$input["blend_start_time"]."','".$input["blender"]."','".$input["Processing"]."')";
    //      if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //      }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
      else if ($_GET["type"] == "get_savebmr_blend") {
        	$output = Array();
     	  $sql = "SELECT * FROM bmr_blending ORDER BY Processing ASC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
      else if ($_GET["type"] == "get_savebmr_sift") {
        	$output = Array();
     	  $sql = "SELECT * FROM bmr_sifting ";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
         else if ($_GET["type"] == "del_blend") {
            //  echo('hello');
            $sql = "DELETE FROM bmr_blending where id='".$_GET["id"]."' " ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
         else if ($_GET["type"] == "del_sift") {
            //  echo('hello');
            $sql = "DELETE FROM bmr_blending where id='".$_GET["id"]."' " ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    // else if ($_GET["type"] == "SAVEgEN_INSTRUCTION") {
        
        
    //      $sql="SELECT product_code FROM ebmr WHERE product_code='".$_GET["product_code"]."'";
    //     $result =$conn->query($sql);
    //   // echo $sql;
    //     if ($result->num_rows > 0) {
    //         $sql = "update ebmr set genral_instruction='".json_encode($input["instruction"])."' where product_code='".$_GET["product_code"]."'";
    //     }else{
    
    //         $sql = "INSERT INTO ebmr( plant_id, product_code,genral_instruction) VALUES ('".$_GET["plant_id"]."','".$_GET["product_code"]."','".json_encode($input["instruction"])."')";
    //      if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //      }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    // }
    else if ($_GET["type"] == "SAVEequipments") {
        
        
         $sql="SELECT product_code FROM ebmr WHERE product_code='".$_GET["product_code"]."'";
        $result =$conn->query($sql);
       // echo $sql;
        if ($result->num_rows > 0) {
            $sql = "update ebmr set equipments='".json_encode($input["equipments"])."' where product_code='".$_GET["product_code"]."'";
             if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
             }else {
            echo "{\"status\":\"".$conn->error."\"}";
            }
        }else{
    
            $sql = "INSERT INTO ebmr( plant_id, product_code,equipments) VALUES ('".$_GET["plant_id"]."','".$_GET["product_code"]."','".json_encode($input["equipments"])."')";
                 if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
                 }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    }
    else if ($_GET["type"] == "savewarehouse_dispensing") {      
        $json_obj = json_encode($input["warehouse_dispensing"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
   $sql = "INSERT INTO bmr_warehouse_dispensing_checklist( plant_id, checkpoint,product_code,remark) VALUES ('".$_GET["plant_id"]."','".$values["check_point"]."','".$_GET["product_code"]."','".$values["evaluation_parameter"]."')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    else if ($_GET["type"] == "savedispensing_checklist") {      
        $json_obj = json_encode($input["dispensing_checklist"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
   $sql = "INSERT INTO bmr_dispensing_checklist( plant_id, checkpoint,product_code,remark) VALUES ('".$_GET["plant_id"]."','".$values["check_point"]."','".$_GET["product_code"]."','".$values["evaluation_parameter"]."')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    else if ($_GET["type"] == "savedispensing_checklist_LC") {      
        $json_obj = json_encode($input["lineCleance_checklist"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
    $sql = "INSERT INTO bmr_lineclearance_cheklist( plant_id, checkpoint,product_code,remark) VALUES ('".$_GET["plant_id"]."','".$values["check_point"]."','".$_GET["product_code"]."','".$values["evaluation_parameter"]."')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    else if ($_GET["type"] == "savedispensing_checklist_LC_process") {      
        $json_obj = json_encode($input["lineCleance_Checklist_for_processing"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
    $sql = "INSERT INTO bmr_lineclearance_process_cheklist( plant_id, checkpoint,product_code,remark) VALUES ('".$_GET["plant_id"]."','".$values["check_point"]."','".$_GET["product_code"]."','".$values["evaluation_parameter"]."')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    // else if ($_GET["type"] == "savewarehouse_dispensing") {
        
        
    //      $sql="SELECT product_code FROM ebmr WHERE product_code='".$_GET["product_code"]."'";
    //     $result =$conn->query($sql);
    //   // echo $sql;
    //     if ($result->num_rows > 0) {
    //         $sql = "update ebmr set warehouse_dispensing='".json_encode($input["warehouse_dispensing"])."' where product_code='".$_GET["product_code"]."'";
    //          if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //          }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //         }
    //     }else{
    
    //         $sql = "INSERT INTO ebmr( plant_id, product_code,warehouse_dispensing) VALUES ('".$_GET["plant_id"]."','".$_GET["product_code"]."','".json_encode($input["warehouse_dispensing"])."')";
    //              if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //              }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    // }
    // else if ($_GET["type"] == "savedispensing_checklist") {
        
        
    //      $sql="SELECT product_code FROM ebmr WHERE product_code='".$_GET["product_code"]."'";
    //     $result =$conn->query($sql);
    //   // echo $sql;
    //     if ($result->num_rows > 0) {
    //         $sql = "update ebmr set dispensing_checklist='".json_encode($input["dispensing_checklist"])."' where product_code='".$_GET["product_code"]."'";
    //          if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //          }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //         }
    //     }else{
    
    //         $sql = "INSERT INTO ebmr( plant_id, product_code,dispensing_checklist) VALUES ('".$_GET["plant_id"]."','".$_GET["product_code"]."','".json_encode($input["dispensing_checklist"])."')";
    //              if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //              }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    // }
    else if ($_GET["type"] == "Saveline_chek") {
        
        
         $sql="SELECT product_code FROM ebmr WHERE product_code='".$_GET["product_code"]."'";
        $result =$conn->query($sql);
       // echo $sql;
        if ($result->num_rows > 0) {
            $sql = "update ebmr set lineCleance_checklist='".json_encode($input["lineCleance_checklist"])."' where product_code='".$_GET["product_code"]."'";
             if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
             }else {
            echo "{\"status\":\"".$conn->error."\"}";
            }
        }else{
    
            $sql = "INSERT INTO ebmr( plant_id, product_code,lineCleance_checklist) VALUES ('".$_GET["plant_id"]."','".$_GET["product_code"]."','".json_encode($input["lineCleance_checklist"])."')";
                 if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
                 }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    }
    else if ($_GET["type"] == "Savelinedisp_chek") {
        
        
         $sql="SELECT product_code FROM ebmr WHERE product_code='".$_GET["product_code"]."'";
        $result =$conn->query($sql);
       // echo $sql;
        if ($result->num_rows > 0) {
            $sql = "update ebmr set lineCleance_Checklist_for_processing='".json_encode($input["lineCleance_Checklist_for_processing"])."' where product_code='".$_GET["product_code"]."'";
             if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
             }else {
            echo "{\"status\":\"".$conn->error."\"}";
            }
        }else{
    
            $sql = "INSERT INTO ebmr( plant_id, product_code,lineCleance_Checklist_for_processing) VALUES ('".$_GET["plant_id"]."','".$_GET["product_code"]."','".json_encode($input["lineCleance_Checklist_for_processing"])."')";
                 if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
                 }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    }
    else if ($_GET["type"] == "saveEbmrProcess") {    
        $json_obj = json_encode($input["processes1"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
   $sql="INSERT INTO manufacturing_process(plant_id, user_no, dosage_form, process_type, stage, step,  entry_by,entry_date) VALUES (
   '".$_GET["plant_id"]."','".$_GET["user_no"]."','".$_GET["dosage_form"]."','".$values["process_type"]."','".$values["stage"]."',
   '".$values["step"]."','".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }

}

$conn->close();
?>