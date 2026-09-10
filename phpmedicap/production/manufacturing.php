<?php
    require '../db.php';
    
//     ini_set('display_errors', 1);
// error_reporting(E_ALL);
    
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $entry_time = date("H:i:s", $timestamp);
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
    
    if ($_GET["type"] == "startStage") {
        $sql = "UPDATE bmr_stages SET status='INPROCESS', start_by='".$_GET["emp_id"]."', start_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>$_GET["bmr_no"].": ".$_GET["stage"]." has been started successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "callforclearance") {
        $sql = "INSERT INTO lineclearance (department, section, activity, bmr_no, batch_no, stage, checkpoints, request_by, request_date) VALUES ('Production', '', 'Under Production Stage Clearance', '".$_GET["bmr_no"]."', '".$_GET["batch_no"]."', '".$_GET["stage"]."', '".json_encode($input)."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>$_GET["bmr_no"].": Line Clearance Request has been send to QA"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveEnvironmentCheck") {
        $sql = "INSERT INTO environment_check (department, activity, bmr_no, batch_no, stage, checkpoints, entry_by, entry_date, entry_time) VALUES ('Production', 'Under Production Stage Clearance', '".$_GET["bmr_no"]."', '".$_GET["batch_no"]."', '".$_GET["stage"]."', '".json_encode($input)."', '".$_GET["emp_id"]."', '$entry_date', '$entry_time')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>$_GET["bmr_no"].": Environmental Checks saved successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveInprocessCheck") {
        $sql = "INSERT INTO inprocess_check (department, activity, bmr_no, batch_no, stage, checkpoints, entry_by, entry_date, entry_time) VALUES ('Production', 'Under Production Stage Clearance', '".$_GET["bmr_no"]."', '".$_GET["batch_no"]."', '".$_GET["stage"]."', '".json_encode($input)."', '".$_GET["emp_id"]."', '$entry_date', '$entry_time')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>$_GET["bmr_no"].": Inprocess Checks saved successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "startEquipmentUsage") {
        $sql = "INSERT INTO equipment_usages (bmr_no, batch_no, stage, equipment_code, usage_from, entry_by, entry_date) VALUES ('".$_GET["bmr_no"]."', '".$_GET["batch_no"]."', '".$_GET["stage"]."', '".$input["equipment_code"]."','$entry_date', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>$_GET["bmr_no"].": Equipment Usage Started Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "stopEquipmentUsage") {
        $sql = "UPDATE equipment_usages SET status='STOP', usage_to='$entry_date', stop_by='".$_GET["emp_id"]."', stop_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Equipment has been stoped Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveWeightCheck") {
        $sql = "INSERT INTO weight_check (department, activity, bmr_no, batch_no, stage, checkpoints, entry_by, entry_date, entry_time) VALUES ('Production', 'Under Production Stage Clearance', '".$_GET["bmr_no"]."', '".$_GET["batch_no"]."', '".$_GET["stage"]."', '".json_encode($input)."', '".$_GET["emp_id"]."', '$entry_date', '$entry_time')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>$_GET["bmr_no"].": Weight Verification saved successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    } 
    else if ($_GET["type"] == "completeStage") {
        //$sql = "UPDATE bmr_stages SET yields='".json_encode($input["yields"])."',yield_qty = '".$input["yield_qty"]."',yield_unit='".$input["yield_unit"]."', yield_per = '".$input["yield_per"]."' , theoretical_wt='".$input["theoretical_wt"]."', actual_wt='".$input["actual_wt"]."', yield_per='".$input["yield_per"]."', qty_destroyed='".$input["qty_destroyed"]."',status='COMPLETED', complete_by='".$_GET["emp_id"]."', complete_date='$entry_date' WHERE id='".$_GET["id"]."'";
        $sql="update mfg_work_order_hdr SET
        stage_completed_by = '".$_GET["emp_id"]."', stage_complete_date ='".$entry_date."' where id = '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Stage has been Completed Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "completeStage_pk") {
        //$sql = "UPDATE bmr_stages SET yields='".json_encode($input["yields"])."',yield_qty = '".$input["yield_qty"]."',yield_unit='".$input["yield_unit"]."', yield_per = '".$input["yield_per"]."' , theoretical_wt='".$input["theoretical_wt"]."', actual_wt='".$input["actual_wt"]."', yield_per='".$input["yield_per"]."', qty_destroyed='".$input["qty_destroyed"]."',status='COMPLETED', complete_by='".$_GET["emp_id"]."', complete_date='$entry_date' WHERE id='".$_GET["id"]."'";
        $sql="update mfg_work_order_hdr SET
        stage_completed_by = '".$_GET["emp_id"]."', stage_complete_date ='".$entry_date."' where id = '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Stage has been Completed Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "approveStage") {
        //$sql = "UPDATE bmr_stages SET yields='".json_encode($input["yields"])."',yield_qty = '".$input["yield_qty"]."',yield_unit='".$input["yield_unit"]."', yield_per = '".$input["yield_per"]."' , theoretical_wt='".$input["theoretical_wt"]."', actual_wt='".$input["actual_wt"]."', yield_per='".$input["yield_per"]."', qty_destroyed='".$input["qty_destroyed"]."',status='COMPLETED', complete_by='".$_GET["emp_id"]."', complete_date='$entry_date' WHERE id='".$_GET["id"]."'";
        $sql="update mfg_work_order_hdr SET
        stage_checked_remarks = '".$input["remarks"]."',
        stage_checked_by = '".$_GET["emp_id"]."', stage_checked_date ='".$entry_date."' where id = '".$_GET["id"]."'";
       
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Stage has been Completed Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    }
    
    
    else if($_GET["type"] == "save_test_results") {
             $sql="update batch_stages_ipqc_dtl set test_result = '".$input["result"]."',
             test_remarks = '".$input["remarks"]."',
             ti_recd_by = '".$_GET["emp_id"]."',
             test_result_status = '".$input["test_result_status"]."',
             ti_recd_date ='".$entry_date."' where id = '".$_GET["id"]."'";
        
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "save_test_results_pk") {
             $sql="update packing_batch_stages_ipqc_dtl set test_result = '".$input["result"]."',
             test_remarks = '".$input["remarks"]."',
             ti_recd_by = '".$_GET["emp_id"]."',
             test_result_status = '".$input["test_result_status"]."',
             ti_recd_date ='".$entry_date."' where id = '".$_GET["id"]."'";
        
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "save_test_remarks") {
        $sql="";
        
        
        if($_GET["batch_stage_id"]>0){
             $sql="update batch_stages_ipqc_dtl set remarks = '".$_GET["remarks"]."', remarks_entry_by = '".$_GET["emp_id"]."',
                   remarks_entry_date ='".$entry_date."', limit_type = '".$_GET["limit_type"]."'
                   , description = '".$_GET["description"]."', lower_limit = '".$_GET["lower_limit"]."'
                   , upper_limit = '".$_GET["upper_limit"]."'where id = '".$_GET["batch_stage_id"]."'";
        }else{
            
        $sql = "insert into batch_stages_ipqc_dtl (limit_type,description,lower_limit,upper_limit,product_code,plan_no,bfr_no, lot_no, 
        stage_hdr_id,stage_dtl_id,remarks,remarks_entry_by,remarks_entry_date) values('".$input["limit_type"]."','".$input["description"]."',
        '".$input["lower_limit"]."','".$input["upper_limit"]."','".$input["product_code"]."','".$input["plan_no"]."', '".$input["bfr_no"]."' ,
        '".$input["lot_no"]."','".$input["stage_hdr_id"]."' , '".$input["stage_dtl_id"]."','".$input["remarks"]."','".$_GET["emp_id"]."',
        '".$entry_date."')";
        
        }
             
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
            $last_id = $conn->insert_id;
            $tiNo = 'TI' . str_pad($last_id, 4, '0', STR_PAD_LEFT);
        
        if($_GET["batch_stage_id"]>0){
            
        }else{
            
                $update_sql = "UPDATE batch_stages_ipqc_dtl SET ti_no = '$tiNo' WHERE id = $last_id";
                $conn->query($update_sql);
            
            
                    $sql1 = "INSERT INTO technical_info (plant_id,ti_no,plan_no,bfr_no,product_code, batch_no,  batch_size, stage, step,
        entry_by, entry_date,status)VALUES ('".$_GET["plant_id"]."','$tiNo','".$input["plan_no"]."','".$input["bfr_no"]."','".$input["product_code"]."', '".$input["batch_no"]."','".$input["batch_size"]."',
        '".$input["stage_dtl_id"]."', '".$input["step"]."',  '".$_GET["emp_id"]."', '".$entry_date."','')";
        $conn->query($sql1);
            
        }
            

        
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "save_test_remarksPacking") {
        
        
        $sql="";
        
        
        if($_GET["batch_stage_id"]>0){
             $sql="update packing_batch_stages_ipqc_dtl set remarks = '".$_GET["remarks"]."', remarks_entry_by = '".$_GET["emp_id"]."',
                   remarks_entry_date ='".$entry_date."', limit_type = '".$_GET["limit_type"]."'
                   , description = '".$_GET["description"]."', lower_limit = '".$_GET["lower_limit"]."'
                   , upper_limit = '".$_GET["upper_limit"]."'where id = '".$_GET["batch_stage_id"]."'";
        }else{
            
        $sql = "insert into packing_batch_stages_ipqc_dtl (limit_type,description,lower_limit,upper_limit,product_code,plan_no,bfr_no, lot_no, 
        stage_hdr_id,stage_dtl_id,remarks,remarks_entry_by,remarks_entry_date) values('".$input["limit_type"]."','".$input["description"]."',
        '".$input["lower_limit"]."','".$input["upper_limit"]."','".$input["product_code"]."','".$input["plan_no"]."', '".$input["bfr_no"]."' ,
        '".$input["lot_no"]."','".$input["stage_hdr_id"]."' , '".$input["stage_dtl_id"]."','".$input["remarks"]."','".$_GET["emp_id"]."',
        '".$entry_date."')";
        
        }
             
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
            $last_id = $conn->insert_id;
            $tiNo = 'TI' . str_pad($last_id, 4, '0', STR_PAD_LEFT);
        
        if($_GET["batch_stage_id"]>0){
            
        }else{
            
                $update_sql = "UPDATE packing_batch_stages_ipqc_dtl SET ti_no = '$tiNo' WHERE id = $last_id";
                $conn->query($update_sql);
            
            
                    $sql1 = "INSERT INTO technical_info (plant_id,ti_no,plan_no,bfr_no,product_code, batch_no,  batch_size, stage, step,
        entry_by, entry_date,status,data_from)VALUES ('".$_GET["plant_id"]."','$tiNo','".$input["plan_no"]."','".$input["bfr_no"]."','".$input["product_code"]."', '".$input["batch_no"]."','".$input["batch_size"]."',
        '".$input["stage_dtl_id"]."', '".$input["step"]."',  '".$_GET["emp_id"]."', '".$entry_date."','','Packing')";
        $conn->query($sql1);
            
        }
            

        
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
    else if($_GET["type"] == "save_test_remarks1") {
        $sql="";
         
        if($_GET["batch_stage_id"]>0){
             $sql="update batch_stages_ipqc_dtl set remarks = '".$input["remarks"]."', remarks_entry_by = '".$_GET["emp_id"]."',
                   remarks_entry_date ='".$entry_date."', limit_type = '".$input["limit_type"]."',test_result_status = 'For_Allocation'
                   , description = '".$input["description"]."', lower_limit = '".$input["lower_limit"]."',samplingStatus = 'Complete'
    , sampleQty = '".$input["sample_qty"]."', unit = '".$input["unit"]."', withdraw_by = '".$_GET["emp_id"]."', withdraw_date = '$entry_date'
                   , upper_limit = '".$input["upper_limit"]."'where id = '".$input["batch_stage_id"]."'";
        }else{
            
        $sql = "insert into batch_stages_ipqc_dtl (limit_type,description,lower_limit,upper_limit,product_code,plan_no,bfr_no, lot_no, 
        stage_hdr_id,stage_dtl_id,remarks,remarks_entry_by,remarks_entry_date,sampleQty,unit,samplingStatus,withdraw_by,withdraw_date,test_result_status) values('".$input["limit_type"]."','".$input["description"]."',
        '".$input["lower_limit"]."','".$input["upper_limit"]."','".$input["product_code"]."','".$input["plan_no"]."', '".$input["bfr_no"]."' ,
        '".$input["lot_no"]."','".$input["stage_hdr_id"]."' , '".$input["stage_dtl_id"]."','".$input["remarks"]."','".$_GET["emp_id"]."',
        '".$entry_date."', '".$input["sample_qty"]."','".$input["unit"]."','Complete','".$_GET["emp_id"]."',
        '".$entry_date."','For_Allocation')";
        
        }
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
             
            $last_id = $conn->insert_id;
            $tiNo = 'TI' . str_pad($last_id, 4, '0', STR_PAD_LEFT); 
             
        if($_GET["batch_stage_id"]>0){
            
        }else{
            
                $update_sql = "UPDATE batch_stages_ipqc_dtl SET ti_no = '$tiNo' WHERE id = $last_id";
                $conn->query($update_sql);
            
                    $sql1 = "INSERT INTO technical_info (plant_id,ti_no,plan_no,bfr_no,product_code, batch_no,  batch_size, stage, step, sample_qty,
        unit,entry_by, entry_date,status)VALUES ('".$_GET["plant_id"]."','$tiNo','".$input["plan_no"]."','".$input["bfr_no"]."','".$input["product_code"]."', '".$input["batch_no"]."','".$input["batch_size"]."',
        '".$input["stage_dtl_id"]."', '".$input["step"]."', '".$input["sample_qty"]."',
        '".$input["unit"]."', '".$_GET["emp_id"]."', '".$entry_date."','For_Allocation')";
        $conn->query($sql1);
            
        }
             
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "save_test_remarks_pk") {
        $sql="";
        if($_GET["batch_stage_id"]>0){
             $sql="update packing_batch_stages_ipqc_dtl set remarks = '".$_GET["remarks"]."', remarks_entry_by = '".$_GET["emp_id"]."',
                   remarks_entry_date ='".$entry_date."', limit_type = '".$_GET["limit_type"]."'
                   , description = '".$_GET["description"]."', lower_limit = '".$_GET["lower_limit"]."'
                   , upper_limit = '".$_GET["upper_limit"]."'where id = '".$_GET["batch_stage_id"]."'";
        }else{
        $sql = "insert into packing_batch_stages_ipqc_dtl (limit_type,description,lower_limit,upper_limit,plan_no,bfr_no, lot_no, stage_hdr_id,stage_dtl_id,remarks,remarks_entry_by,remarks_entry_date)
        values('".$input["limit_type"]."','".$input["description"]."','".$input["lower_limit"]."','".$input["upper_limit"]."','".$input["plan_no"]."', '".$input["bfr_no"]."' ,'".$input["lot_no"]."','".$input["stage_hdr_id"]."' , '".$input["stage_dtl_id"]."',
        '".$input["remarks"]."','".$_GET["emp_id"]."','".$entry_date."')";
        }
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "save_process_data") {
       
        $sql="update batch_stages_ipqc_dtl set equipment_code = '".$input["equipment_code"]."',operator_code = '".$input["operator_code"]."',
        start_time = '".$input["start_time"]."', end_time = '".$input["end_time"]."',recording_date = '".$input["recording_date"]."',recording_time = '".$input["recording_time"]."',
        process_entry_by = '".$_GET["emp_id"]."',process_entry_date ='".$entry_date."'where id = '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "save_process_data_pk") {
       
        $sql="update packing_batch_stages_ipqc_dtl set equipment_code = '".$input["equipment_code"]."',operator_code = '".$input["operator_code"]."',
        start_time = '".$input["start_time"]."', end_time = '".$input["end_time"]."',recording_date = '".$input["recording_date"]."',recording_time = '".$input["recording_time"]."',
        process_entry_by = '".$_GET["emp_id"]."',process_entry_date ='".$entry_date."'where id = '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "save_process_data_pk") {
       
        $sql="update packing_batch_stages_ipqc_dtl set equipment_code = '".$input["equipment_code"]."',operator_code = '".$input["operator_code"]."',
        start_time = '".$input["start_time"]."', end_time = '".$input["end_time"]."',recording_date = '".$input["recording_date"]."',recording_time = '".$input["recording_time"]."',
        process_entry_by = '".$_GET["emp_id"]."',process_entry_date ='".$entry_date."'where id = '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "save_yeild_data") {
        $sql="update batch_stages_ipqc_dtl set stage_yield = '".$input["stage_yield"]."',stage_yield_unit = '".$input["stage_yield_unit"]."',
         inputQty = '".$input["inputQty"]."',  outputQty = '".$input["outputQty"]."', yieldDiffrence = '".$input["yieldDiffrence"]."',
        stage_yield_percent = '".$input["stage_yield_percent"]."' , expected_yield = '".$input["expected_yield"]."' ,
        yeild_entry_by = '".$_GET["emp_id"]."',yeild_entry_date ='".$entry_date."' where id = '".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "transfer_workorder_pk_dept") {
        $sql="update mfg_work_order_hdr set pm_actual_yeild = '".$input["actual_yeild"]."',pm_yeild_percentage = '".$input["yeild_percent"]."',
        pm_batch_commence_date = '".$input["bath_commencent_date"]."' ,  pm_batch_complete_date = '".$input["bath_complete_date"]."' ,  
        pm_no_of_days = '".$input["total_days"]."' ,pm_statusForCheckAndTranfer = 'For_Checking',
        pm_tr_to_packing_dept_by = '".$_GET["emp_id"]."',pm_tr_to_packing_dept_date ='".$entry_date."' , pm_receiving='bpr Start' where id = '".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            
             $sql1 = "  INSERT INTO fg_stock_book( plant_id, batch_no, stock_type,  material_code, qty, 
              mfg_date, exp_date, status, entry_by, entry_date)  VALUES ('".$_GET["plant_id"]."', 
             '".$input["batch_number"]."', 'Production',  '".$input["product_code"]."', 
             '".$input["actual_yeild"]."',  '".$input["bath_complete_date"]."', '".$input["exp_date"]."', 
             'Approved'  ,'".$_GET["emp_id"]."','$entry_date')";
             
             $conn->query($sql1);
             
             $lastInsertedId = $conn->insert_id;
             
             $ar = 'AR-'.$lastInsertedId;
             
             $sql2 = "update fg_stock_book set ar_no = '$ar'  where id ='$lastInsertedId' ";
             $conn->query($sql2);
            
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "transfer_workorder_pk_dept_FromChecking") {
        $input = $_POST;
        
        $target_dir = "../../../upload/bmr/";
    
        $file_name = "";
        
        $id = $_GET["id"];
        
        if(isset($_FILES["document"]["name"])){
            $target_file = $target_dir.$id."bmr".basename($_FILES["document"]["name"]);
            $file_name = $id."bmr".basename($_FILES["document"]["name"]);
            
        }
        
        
        $sql="update mfg_work_order_hdr set batchCheckRemark = '".$input["remark"]."',statusForCheckAndTranfer = 'Checked',
        bmrFile = '$file_name',batchCheckBy = '".$_GET["emp_id"]."',batchCheckOn ='".$entry_date."'where id = '".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            move_uploaded_file($_FILES["document"]["tmp_name"], $target_file);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "transfer_workorder_pk_dept_sampling") {
        $sql="update mfg_work_order_hdr set sampling_intimation = 'Yes' where id = '".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "downloadProductionReport") {
        require '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Production Report'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Production Report</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 15%;">MFG Date</td>
                    <td style="width: 15%;">Product Name</td>
                    <td style="width: 15%;">Batch Size</td>
                    <td style="width: 15%;">Batch Qty</td>
                    <td style="width: 15%;">No. of Batches</td>
                    <td style="width: 15%;">Qty</td>
                   
                </tr>
            </thead>
            <tbody>';
        $i=1;
        $sql = "SELECT b.*, DATE(b.entry_date) as entry_date, p.product_type, p.product_name, p.grade FROM bmr b LEFT JOIN product p 
         ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."'ORDER BY id DESC";
   	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
                $html.='<tr>
                        <td style="width: 10%;">'.$i.'.</td>
                        <td style="width: 15%;">'.$row['mfg_date'].'</td>
                        <td style="width: 15%;">'.$row['product_name'].'</td>
                        <td style="width: 15%;">'.$row['batch_size'].'</td>
                        <td style="width: 15%;">'.$row['batch_qty'].'</td>
                        <td style="width: 15%;">'.$row['no_of_batch'].'</td>
                        <td style="width: 15%;">'.$row['qty'].'</td>
                    </tr>
                    </tbody>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Workorder.pdf', 'I');
    }
    if ($_GET["type"] == "saveCompletedBMR") {
        $stages = $input["stages"];
        for ($i = 0; $i < count($stages); $i++) {
            $stage = $stages[$i];
            $stage["status"] = "pending";
            $stages[$i] = $stage;
        }
        $input["stages"] = $stages;
        
        $sql = "INSERT INTO bmr (user_no, company_unit, product_code, bom_no, raw_materials, packing_materials, stages, batch_size, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$input["company_unit"]."', '".$input["product_code"]."', '".$input["bom_no"]."', '".json_encode($input["raw_materials"])."', '".json_encode($input["packing_materials"])."', '".json_encode($input["stages"])."', '".$input["batch_size"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
     } else if ($_GET["type"] == "getCompletedBMR") {
         $output = array();
        //  $sql = "SELECT b.*, DATE(b.entry_date) as entry_date, p.product_type, p.product_name, p.grade FROM bmr b LEFT JOIN product p 
        //  ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."'ORDER BY id DESC";
           $sql = "SELECT b1. *,s2.product_code,s2.product_name ,b2.stage_hdr_id as stage_id,b2.stage_name,b1.lot_no as test_no,s.sample_qty,m.batch_number,m.batch_complete_date,m.batch_commence_date,b.batch_size FROM batch_stages_ipqc_dtl b1 left join stages_ipqc_dtl b2 ON b1.stage_hdr_id=b2.id LEFT JOIN spec_tests s ON b2.stage_name=s.stage LEFT JOIN stages_ipqc s2 on b2.stage_hdr_id=s2.id LEFT JOIN batch_planning b on s2.product_code =b.product_code left join mfg_work_order_hdr m on b.id=m.batch_plan_id";

   	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
    }else if ($_GET["type"] == "downloadProductionDetailsReport") {
        require '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Product Report'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Production Report</h2>
        <table cellpadding="5" border="1">
        <tr>
            <td style="width:5%; text-align:centre;"><b>Sr No.</b></td>
            <td style="width:15%; text-align:centre;"><b>Date of Commentmemt</b></td>
            <td style="width:15%; text-align:centre;"><b>Date of Completion</b></td>
            <td style="width:10%; text-align:centre;"><b>Batch No.</b></td>
            <td style="width:15%; text-align:centre;"><b>Standard Batch Size</b></td>
            <td style="width:10%; text-align:centre;"><b>Yield(%)</b></td>
            <td style="width:15%; text-align:centre;"><b>Yield(Kg)</b></td>
            <td style="width:15%; text-align:centre;"><b>Yield(Nos)</b></td>
        </tr>';
        $i=1;
        $sql = "SELECT b.*, DATE(b.start_date) as start_date, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim ,u.instructions ,u.abbreviation,u.raw_materials ,u.packing_materials ,u.bmr_checklist FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code LEFT JOIN unitformula u ON b.mfr_no = u.mfr_no WHERE b.status='COMPLETED'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['abbreviation'] = json_decode($row['abbreviation']);
                $row['instructions'] = json_decode($row['instructions']);
                $row['raw_materials'] = json_decode($row['raw_materials']);
                $row['packing_materials'] = json_decode($row['packing_materials']);
                $row["bmr_checklist"] = json_decode($row["bmr_checklist"]);
                
                $output1 = Array();
               $sql = "SELECT b.*, DATE(b.entry_date) as entry_date, p.product_type, p.product_name, p.grade FROM bmr b LEFT JOIN product p 
         ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."'ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
        $html.='<tr>
                    <td style="width:5%;">'.$i.'</td>
                    <td style="width:15%;">'.date('d-m-Y',strtotime($row['approve_date'])).'</td>
                    <td style="width:15%;">'.date('d-m-Y',strtotime($row['complete_date'])).'</td>
                    <td style="width:10%;">'.$row['batch_no'].'</td>
                    <td style="width:15%;">'.$row['batch_size'].'</td>
                    <td style="width:10%;">'.$row['yield_qty'].'</td>
                    <td style="width:15%;">'.$row['yield_qty'].'</td>
                    <td style="width:15%;">'.$row['yield_qty'].'</td>
                </tr>';
            $i++;
                    }
                }
        }
    }
        $html.='</table>';
        
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Workorder.pdf', 'I');
    }


}

$conn->close();
?>