<?php 
    require '../db.php';
    
 


    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);
    
    
    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);

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

    
    
    if ($_GET["type"] == "SaveMastercheckList") {
      
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
     
        $sections = array();
        if (isset($input["sections"]) && is_array($input["sections"]) && count($input["sections"]) > 0) {
            $sections = $input["sections"];
        } else if (isset($input["checklistList"]) && is_array($input["checklistList"]) && count($input["checklistList"]) > 0) {
            $sections[] = array(
                "heading" => isset($input["checklist_heading"]) ? $input["checklist_heading"] : "Checklist",
                "checkpoints" => $input["checklistList"]
            );
        } else {
            echo "{\"status\":\"invalid\"}";
            exit;
        }

        $saved = 0;
        foreach ($sections as $section) {
            $heading = trim((string)$section["heading"]);
            if ($heading == "" || !isset($section["checkpoints"]) || !is_array($section["checkpoints"]) || count($section["checkpoints"]) == 0) {
                continue;
            }

            $sql = "Select id from master_checklist where plant_id = '".$_GET["plant_id"]."' ORDER BY id DESC LIMIT 1";
            $result = $conn->query($sql);
            $row = $result ? $result->fetch_assoc() : null;
            $nextId = ($row && isset($row['id'])) ? intval($row['id']) + 1 : 1;
            $ch_id = "CH-0".$nextId;

            $sql = "INSERT INTO master_checklist (plant_id,department,heading,module,form_name,ch_id,entry_by,entry_date,status) 
                    VALUES ('".$_GET["plant_id"]."','".$conn->real_escape_string($input["department"])."','".$conn->real_escape_string($heading)."',
                    '".$conn->real_escape_string($input["module"])."','".$conn->real_escape_string($input["form_name"])."','".$ch_id."',
                    '".$_GET["emp_id"]."','$entry_date','pending')";

            if ($conn->query($sql)) {
                $last_id = $conn->insert_id;
                foreach ($section["checkpoints"] as $checkDtlData) {
                    $check_point = $conn->real_escape_string($checkDtlData["check_point"]);
                    $description = isset($checkDtlData["description"]) ? $conn->real_escape_string($checkDtlData["description"]) : " ";
                    $evl_pr = $conn->real_escape_string($checkDtlData["evaluation_parameter"]);
                    $evl_type = isset($checkDtlData["evaluation_type"]) ? $conn->real_escape_string($checkDtlData["evaluation_type"]) : "";

                    $sql="INSERT INTO mst_chlist_dtl (chklist_id ,check_point ,ch_description,evl_pr,evl_type,entry_by,update_date,date_peparation)
                          VALUES (".$last_id.",
                                  '".$check_point."',
                                  '".$description."',
                                  '".$evl_pr."',
                                  '".$evl_type."',
                                  '".$_GET["emp_id"]."','$entry_date','$entry_date')";
                    $conn->query($sql);
                }
                $saved++;
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
                exit;
            }
        }

        if ($saved > 0) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"invalid\"}";
        }
    }
        else if($_GET["type"] == "getMethodDocuments") {
        $output = array();
        $sql = "SELECT * FROM method_document  where plant_id =  '".$_GET["plant_id"]."' "; //AND doc_type = 'AssociateDoc'
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
        else if ($_GET["type"] == "saveMethodDocuments") {
       
        $sql = "INSERT INTO method_document (plant_id,doc_name,doc_type) VALUES
        ('".$_GET["plant_id"]."','".$_GET["doc_name"]."','".$_GET["docType"]."')";
        
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    }


    else if ($_GET["type"] == "savChecHeadingg"){
         
      
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
     
       
        $sql = "INSERT INTO checkpoint_list (plant_id,checklistpoints,entry_by,entry_date)  VALUES ('".$_GET["plant_id"]."',
        '".$input["checkHeading"]."','".$_GET["emp_id"]."','$entry_date')";
        
         if($conn->query($sql)){
    	 
         echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}

     }
     else if ($_GET["type"] == "saveCheckpoints"){
         
      
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
     
        $jadu = 0;

        foreach($input["CheckList"] as $checkDtlData){
    		
       	$sql="INSERT INTO checkpoint_master ( chklist_id,checklistpoints ,evl_pr,entry_by,entry_date)value('".$_GET["chklist_id"]."',
    	'".$checkDtlData["check_point"]."','".$checkDtlData["evaluation_parameter"]."','".$_GET["emp_id"]."','$entry_date')";
    	 
            	if($conn->query($sql)){
                 $jadu = 0;
            	} else {
            	$jadu = 1;
            	}
    	 
        }
        if($jadu == 0){
    
         echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}

     }
     
    else if ($_GET["type"] == "saveExitChecklist") {
      
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
     
    
        
    	 $flag = 0;
 

        foreach($input["checklistList"] as $checkDtlData){
    		
    	$sql="INSERT INTO exit_checklist (check_point ,description ,evaluation_parameter,entry_by,entry_date)
        values( '".$checkDtlData["check_point"]."', '".$checkDtlData["description"]."','".$checkDtlData["evaluation_parameter"]."','".$_GET["emp_id"]."','$entry_date')";
                                      
            	if($conn->query($sql)){
                   $flag = 1;
            	} else {
            	    $flag = 2;
            	}
    	
        }
    
    
        if( $flag == 1){
            echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    	
    }
    else if ($_GET["type"] == "SaveMastercheckListBMR") {
      
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
       
              $prasad = 0;

        foreach($input["checklistList"] as $checkDtlData){
    		
    	$sql="INSERT INTO  bmr_checklist (checklist_heading ,description ,evaluation_parameter,check_point,plant_id)
                                value('".$checkDtlData["checklist_heading"]."',
                                      '".$checkDtlData["description"]."',
                                      '".$checkDtlData["evaluation_parameter"]."',
                                      '".$checkDtlData["check_point"]."',
                                      '".$_GET["plant_id"]."')";
 
                	 if( $conn->query($sql)){
                	     
                	     $prasad++;
                	     
                	 } 
 
        }
    
                if($prasad > 0){
                       echo "{\"status\":\"success\"}";	
                       
                 }else {
                		echo "{\"status\":\"".$conn->error."\"}";
                }

    }
    
       else if ($_GET["type"] == "getooschecklistmaster") { 

       
        $output = array();
        $sql = "SELECT id,check_index,plant_id,table_no,heading_no,checklist_heading FROM oos_heading_check where plant_id ='".$_GET["plant_id"]."' ORDER BY check_index asc";// ORDER BY id asc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                    $output1 = array();
                    $sql1 = "SELECT * FROM oos_parameter_dtl where heading_no ='".$row["id"]."' "; 
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1['anaylyst'] = ' ';
                            $row1['riviewer'] = ' ';
                            $row1['complete_by'] = '';
                            $row1['result'] = '';
                            $output1[] = $row1;
                        }
                    }
                
                
                $row['check_points'] = $output1;
                $output[] = $row;
            }
        }
        
        
        echo json_encode($output);
    }  
    
        else if ($_GET["type"] == "getooschecklist_forchecking") { 

       
        $output = array();
        $sql = "SELECT id,check_index,plant_id,table_no,heading_no,checklist_heading FROM oos_heading_check where 
        ( check_index = '10' OR check_index = '11') AND plant_id ='".$_GET["plant_id"]."' ORDER BY check_index asc";// ORDER BY id asc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                    $output1 = array();
                    $sql1 = "SELECT * FROM oos_parameter_dtl where heading_no ='".$row["id"]."' "; 
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1['anaylyst'] = '';
                            $row1['riviewer'] = '';
                            $row1['complete_by'] = '';
                            $row1['result'] = '';
                            $output1[] = $row1;
                        }
                    }
                
                
                $row['check_points'] = $output1;
                $output[] = $row;
            }
        }
        
        
        echo json_encode($output);
    }
    else if ($_GET["type"] == "gevendorAssesmentChecklist") {
        $output = array();
        $sql = "SELECT  * from checkpoint_list WHERE plant_id ='".$_GET["plant_id"]."' "; // ORDER BY id asc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $output1 = array();
                    $sql1 = "SELECT * FROM checkpoint_master where chklist_id ='".$row["id"]."' "; 
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                $row['check_points'] = $output1;
                $output[] = $row;
            }
        }
        
        
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getchecklistHeading") {
        $output = array();
        $sql = "SELECT  * from checkpoint_list WHERE plant_id ='".$_GET["plant_id"]."' "; // ORDER BY id asc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    
        else if ($_GET["type"] == "getooschecklis_for_investing") { 

       
        $output = array();
        $sql = "SELECT id,check_index,plant_id,table_no,heading_no,checklist_heading FROM oos_heading_check where 
        ( check_index = '8' OR check_index = '9') AND plant_id ='".$_GET["plant_id"]."' ORDER BY check_index asc";// ORDER BY id asc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                    $output1 = array();
                    $sql1 = "SELECT * FROM oos_parameter_dtl where heading_no ='".$row["id"]."' "; 
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1['anaylyst'] = '';
                            $row1['riviewer'] = '';
                            $row1['complete_by'] = '';
                            $row1['result'] = '';
                            $output1[] = $row1;
                        }
                    }
                
                
                $row['check_points'] = $output1;
                $output[] = $row;
            }
        }
        
        
        echo json_encode($output);
    }

    
        else if ($_GET["type"] == "getooschecklis_for_review") { 

       
        $output = array();
        $sql = "SELECT id,check_index,plant_id,table_no,heading_no,checklist_heading FROM oos_heading_check where 
        ( check_index = '12' OR check_index = '13' OR check_index = '14' ) AND plant_id ='".$_GET["plant_id"]."' ORDER BY check_index asc";// ORDER BY id asc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                    $output1 = array();
                    $sql1 = "SELECT * FROM oos_parameter_dtl where heading_no ='".$row["id"]."' "; 
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1['anaylyst'] = '';
                            $row1['riviewer'] = '';
                            $row1['complete_by'] = '';
                            $row1['result'] = '';
                            $output1[] = $row1;
                        }
                    }
                
                
                $row['check_points'] = $output1;
                $output[] = $row;
            }
        }
        
        
        echo json_encode($output);
    }

    
        else if ($_GET["type"] == "getooschecklist") { 

       
        $output = array();
        $sql = "SELECT id,check_index,plant_id,table_no,heading_no,checklist_heading FROM oos_heading_check where 
        check_index != '8' AND check_index != '9' AND check_index != '10' AND check_index != '11' AND check_index != '12'
        AND check_index != '13' AND check_index != '14' AND plant_id ='".$_GET["plant_id"]."' ORDER BY check_index asc";// ORDER BY id asc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                    $output1 = array();
                    $sql1 = "SELECT * FROM oos_parameter_dtl where heading_no ='".$row["id"]."' "; 
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1['anaylyst'] = '';
                            $row1['riviewer'] = '';
                            $row1['complete_by'] = '';
                            $row1['result'] = '';
                            $output1[] = $row1;
                        }
                    }
                
                
                $row['check_points'] = $output1;
                $output[] = $row;
            }
        }
        
        
        echo json_encode($output);
    }

        else if ($_GET["type"] == "getooschecklistmaster") { 

       
        $output = array();
        $sql = "SELECT id,check_index,plant_id,table_no,heading_no,checklist_heading FROM oos_heading_check where plant_id ='".$_GET["plant_id"]."' ORDER BY check_index asc";// ORDER BY id asc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                    $output1 = array();
                    $sql1 = "SELECT * FROM oos_parameter_dtl where heading_no ='".$row["id"]."' "; 
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1['anaylyst'] = ' ';
                            $row1['riviewer'] = ' ';
                            $row1['complete_by'] = '';
                            $row1['result'] = '';
                            $output1[] = $row1;
                        }
                    }
                
                
                $row['check_points'] = $output1;
                $output[] = $row;
            }
        }
        
        
        echo json_encode($output);
    }

    else if ($_GET["type"] == "getapprovedEmployee") { 

       
        $output = array();
         $sql = "SELECT id,firstname,lastname FROM employee where status = 'active' AND plant_id =  '".$_GET["plant_id"]."' ORDER BY id asc";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) {
                
                $row['emp_id'] = $row['firstname'].' '.$row['lastname'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
     if ($_GET["type"] == "getMastercheckListMethod2") { 

       
        $output = array();
        $sql = "SELECT * FROM method_document  ORDER BY doc_no asc";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "oos_checklist") {
      
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
       
  
        $ch="CH-";
        $sql = "Select id  from oos_heading_check where  plant_id = '".$_GET["plant_id"]."'   ORDER BY id DESC "; 
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $ch_id = $ch."0". ++$row['id']; 
      
      
                                              
        
         $sql = "INSERT INTO oos_heading_check (plant_id,heading_no,check_index,checklist_heading,parameters_checks,table_no) VALUES ('".$_GET["plant_id"]."',
        '$ch_id','".$input["check_index"]."','".$input["checklist_heading"]."','".json_encode($input["checklistList"])."','".$input["table_no"]."')";
        
        
         if($conn->query($sql)){
    	
        $last_id = $conn->insert_id;

        foreach($input["checklistList"] as $checkDtlData){
    		
    		
        	$sql="INSERT INTO oos_parameter_dtl (heading_no ,check_point ,evaluation_parameter)
                value(".$last_id.",'".$checkDtlData["check_point"]."',  '".$checkDtlData["evaluation_parameter"]."')";
        	 $conn->query($sql);
        	 
        }
    
    
         echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    
    if ($_GET["type"] == "SaveMastercheckList_inprocess") {
      
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
       
              $prasad = 0;
        
   
    	


        foreach($input["checklistList"] as $checkDtlData){
    		
    	$sql="INSERT INTO  inprocess_checks (stages ,steps ,check_point,Department,parameter,frequency)
                                value('".$input["stages"]."',
                                      '".$input["steps"]."',
                                      '".$checkDtlData["check_point"]."',
                                      '".$checkDtlData["Department"]."',
                                      '".$checkDtlData["parameter"]."',
                                      '".$checkDtlData["frequency"]."')";
    	
    	 
    	 
    	 
                	 if( $conn->query($sql)){
                	     
                	     $prasad++;
                	     
                	 } 
    	 
    	 
        }
    
                if($prasad > 0){
                       echo "{\"status\":\"success\"}";	
                       
                 }else {
                		echo "{\"status\":\"".$conn->error."\"}";
                }
                
                
                
                
                
  
    }
    
    

        else if ($_GET["type"] == "delCheck") {
            
            
         
         $sql= " Update mst_chlist_dtl set status = 'inactive' where id='".$_GET["id"]."' ";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    
            
        }
        
  
    else if ($_GET["type"] == "getMastercheckList") { 

       
        $output = array();
        $where = array();
        if (isset($_GET['status']) && $_GET['status'] != "") {
            $where[] = "status='".$conn->real_escape_string($_GET['status'])."'";
        }
        if (isset($_GET['plant_id']) && $_GET['plant_id'] != "") {
            $where[] = "(plant_id='".$conn->real_escape_string($_GET['plant_id'])."' OR plant_id IS NULL OR plant_id = '')";
        }
        $whereClause = count($where) ? " WHERE ".implode(" AND ", $where) : "";
        $sql = "SELECT * FROM master_checklist".$whereClause." ORDER BY id DESC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) {
                $row["checkList"]=[];
                $sqlChkDtl = "select * from mst_chlist_dtl where chklist_id=".$row["id"];
                $resultChkDtl = $conn->query($sqlChkDtl);
                while ($rowChkDtl = $resultChkDtl->fetch_assoc()) { 
                    $row["checkList"][] = $rowChkDtl;
                    
                }
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getMastercheckList1") { 
        $output = array();
        $sql = "SELECT * FROM master_checklist where status='".$_GET['status']."' ORDER BY id DESC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) {
                $row["checkList"]=[];
                $sqlChkDtl = "select * from checkpoint_list where chklist_id=".$row["id"];
                $resultChkDtl = $conn->query($sqlChkDtl);
                while ($rowChkDtl = $resultChkDtl->fetch_assoc()) { 
                    $row["checkList"][] = $rowChkDtl;
                    
                }
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getExitInterviewcheckList") {
        $output = array();
        $sql = "SELECT * FROM exit_checklist   ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
        else if ($_GET["type"] == "approve_chklist") {
            
         
         $sql= " Update master_checklist set status = 'approve', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' where id='".$_GET["id"]."' ";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    
            
        }
        else if ($_GET["type"] == "approve_chklist_group") {
            $input = json_decode(file_get_contents('php://input'), true);
            $ids = isset($input["ids"]) && is_array($input["ids"]) ? $input["ids"] : array();
            if (count($ids) == 0) {
                echo "{\"status\":\"invalid\"}";
                exit;
            }
            $approved = 0;
            foreach ($ids as $id) {
                if (!is_numeric($id)) {
                    continue;
                }
                $sql = "UPDATE master_checklist SET status='approve', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".intval($id)."'";
                if ($conn->query($sql)) {
                    $approved++;
                }
            }
            if ($approved > 0) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"invalid\"}";
            }
        }
    else if ($_GET["type"] == "getMastercheckList_bmr") { 

       
        $output = array();
        $sql = "SELECT * FROM bmr_checklist  ORDER BY id DESC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getMastercheckList_inprocess_checks") { 

       
        $output = array();
        $sql = "SELECT * FROM inprocess_checks  ORDER BY id DESC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getWarehouse_Dispensing") { 

       
        $output = array();
        $sql = "SELECT * FROM bmr_checklist where checklist_heading='".$_GET["checklist_heading"]."'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
        else  if ($_GET["type"] == "SaveDocument") {
      
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
       
        /******* Get CH-ID for  enter in new record  */
        $ch="Doc-";
        $sql = "Select id  from master_index where  plant_id = '".$_GET["plant_id"]."'  order by id desc  limit 0 , 1"; 
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $ch_id = $ch."0". ++$row['id']; 
      
        
        
        $sql = "INSERT INTO master_index (plant_id,dossier_dmf,country_name,particular ,index_id,document,multiple_upload,ch_id) 
                                             VALUES ('".$_GET["plant_id"]."','".$input["dossier_dmf"]."','".$input["country_name"]."','".$input["particular"]."'
                                             ,'".$input["index_id"]."','".$input["document"]."','".$input["multiple_upload"]."','".$ch_id."')";
        
         if($conn->query($sql)){
              $last_id = $conn->insert_id;
             
             
              $json_obj = json_encode($input["subpoint"]);
              $array = json_decode($json_obj, true);
                 $k=0;
                foreach ($array as $checkDtlData){
        $sql="  INSERT INTO mst_documentData (chklist_id ,A_particular ,A_index_id,A_document,A_multiple_upload)
                                value(".$last_id.",
                                      '".$checkDtlData["A_particular"]."',
                                      '".$checkDtlData["A_index_id"]."',
                                      '".$checkDtlData["A_document"]."',
                                      '".$checkDtlData["A_multiple_upload"]."')";
                                      
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
             
             
             
             
             
             
             
             
             
             
             
             
             
             
             
             
             
             
             
             
             
    	
    //     $last_id = $conn->insert_id;

    //     foreach($input["documentData"] as $checkDtlData){
    		
    // 	$sql1="INSERT INTO mst_documentData (chklist_id ,particular ,index,document,upload,multi_upload)
    //                             value(".$last_id.",
    //                                   '".$checkDtlData["particular"]."',
    //                                   '".$checkDtlData["index"]."',
    //                                   '".$checkDtlData["document"]."',
    //                                   '".$checkDtlData["upload"]."',
    //                                   '".$checkDtlData["multi_upload"]."')";
    // 	 $conn->query($sql1);
    //     }
    
    
    //      echo "{\"status\":\"success\"}";	
    // 	} else {
    // 		echo "{\"status\":\"".$conn->error."\"}";
    // 	}
    } 
    else if ($_GET["type"] == "deldocument") {
            
            
         
         $sql= " Update mst_documentData set status = 'inactive' where id='".$_GET["id"]."' ";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    
            
        }
       else  if ($_GET["type"] == "savedocument_data") {
        $sql="SELECT * FROM document WHERE document='".$_GET["document"]."' AND plant_id ='".$_GET["plant_id"]."' ";
        
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Unit Already Exists. Duplicate Values are not allowed\"}";
        }
        else {
        $sql = "INSERT INTO document (plant_id,document,entry_by,entry_date) VALUES ('".$_GET["plant_id"]."','".$_GET["document"]."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        }
    }
        else if ($_GET["type"] == "get_document") {
          $output = array();
        $sql = "SELECT * FROM document where plant_id='".$_GET["plant_id"]."' order by document";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                
            }
        }
    echo json_encode($output);
       
    }
        
    else if ($_GET["type"] == "getDocument") { 

       
        $output = array();
        $sql = "SELECT * FROM master_index";
      
       $result = $conn->query($sql);
        //$result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                       $sql1 = "select * from mst_documentData where chklist_id='".$row["id"]."' ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                          $row["documentData"] = $output1;
                        $output[] = $row;
                }
            }
            //  $data["material_types"] = $output;
        echo json_encode($output);
    }
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
    //     $result = $conn->query($sql);
        
    //     if ($result->num_rows > 0) {
            
    //         while ($row = $result->fetch_assoc()) { $row["documentData"]=[];
    //             $sqlChkDtl = "select * from mst_documentData where chklist_id=".$row["id"];
    //             $resultChkDtl = $conn->query($sqlChkDtl);
    //             while ($rowChkDtl = $resultChkDtl->fetch_assoc()) { 
    //                 $row["documentData"][] = $rowChkDtl;
                    
    //             }
               
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }

    

    else if ($_GET["type"] == "getCheckPointByForm") { 
  
        $module = $conn->real_escape_string($_GET["module"] ?? '');
        $form = $conn->real_escape_string($_GET["form"] ?? '');
        $plant_id = isset($_GET['plant_id']) ? $conn->real_escape_string($_GET['plant_id']) : '';
        $output = array();
        $plantFilter = "";
        if ($plant_id != "") {
            $plantFilter = " AND (mc.plant_id = '".$plant_id."' OR mc.plant_id IS NULL OR mc.plant_id = '') ";
        }
        $sql = "SELECT cd.*, mc.module, mc.form_name, mc.department, mc.heading FROM mst_chlist_dtl cd 
                LEFT JOIN master_checklist mc ON cd.chklist_id = mc.id 
                WHERE mc.module = '".$module."' AND mc.form_name = '".$form."'
                AND LOWER(TRIM(IFNULL(mc.status, ''))) IN ('approve', 'approved')
                ".$plantFilter."
                ORDER BY mc.id ASC, cd.id ASC";
                            
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { 
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if ($_GET["type"] == "getChkListByTranID") { 

      
        $output = array();
        $sql = "SELECT * from checklist_transaction where trans_id ='".$_GET["tranId"]."'";
                            
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { 
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getCheckList') {
         $output = array();
        // $sql = "SELECT * FROM sopinitiation WHERE department='".$_GET["department"]."'";
            $sql = "SELECT  a.*,b.department_name as department FROM appraisal_checklist_master a left join  department b on a.department=b.id";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
         $sql1 = "SELECT * from appraisal_checklist_details where checklist_master_id ='".$row["id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                          $row["checkList"] = $output1;
                          
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_rec_ChkListByTranID") { 


        $output = array();
         $sql = "SELECT ct.*, mc.heading, d.check_point as master_check_point
                 FROM checklist_transaction ct
                 LEFT JOIN mst_chlist_dtl d ON ct.chk_id = d.id
                 LEFT JOIN master_checklist mc ON d.chklist_id = mc.id
                 WHERE ct.trans_id ='".$conn->real_escape_string($_GET["rec_no"])."'
                 AND ct.correction = 'ReceivingChecklist'
                 ORDER BY mc.id ASC, d.id ASC";
                            
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { 
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_wgh_ChkListByTranID") { 


        $output = array();
        $sql = "SELECT * from checklist_transaction where trans_id ='".$_GET["tranId"]."'";
                            
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { 
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "SaveCheckList") {
        
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
       
        /******* Get CH-ID for  enter in new record  */
        $ch="CH-";
      $sql = "Select id  from appraisal_checklist_master where  plant_id = '".$_GET["plant_id"]."'  order by id desc  limit 0 , 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) { 
               
        $ch_id = $ch."0". ++$row['id'];
            }


        }else{
                    $ch_id = $ch."0". 1;

        }
 
      
         
        $sql = "INSERT INTO appraisal_checklist_master (plant_id,department,designation,check_point,ch_id) 
             VALUES ('".$_GET["plant_id"]."','".$input["department"]."','".$input["designation"]."','".$input["check_point"]."','".$ch_id."')";
        
        

         if($conn->query($sql)){
    	
        $last_id = $conn->insert_id;

        foreach($input["checkList"] as $checkDtlData) {
    		
    	$sql="INSERT INTO appraisal_checklist_details (checklist_master_id ,checkpoint_particular ,evualation_parameter)
                                value(".$last_id.",'".$checkDtlData["point"]."', '".$checkDtlData["result"]."')";
                                
    	 $conn->query($sql);
        }
    
    
         echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    }
    
}
    else {
        echo "{\"status\":\"invalid\"}";
    }
    $conn->close();
    ?>