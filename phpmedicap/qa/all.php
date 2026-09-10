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
    
    if ($_GET["type"] == "obsoluteSOP") {
        
              $sql = "INSERT INTO  obsolete_sop  (plant_id , sop_id, obsolete_date, sop_title, version_no, reason_for_obsol, reviewing_officer,reviewing_date,approval_status,sop_doc,archiv_date ) 
     VALUES ('".$_GET["plant_id"]."','".$input["sop_id"]."', '".$input["obsolete_date"]."', '".$input["sop_title"]."', '".$input["version_no"]."', '".$input["reason_for_obsol"]."',
        '".$input["reviewing_officer"]."', '".$input["reviewing_date"]."', '".$input["approval_status"]."', '".$input["sop_doc"]."', '".$input["archiv_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
 else if ($_GET["type"] == "savechemistqualification") {
  $sql = "INSERT INTO  new_chemist  (plant_id , emp_name, emp_id, qualification_date, dob, contact_info, degrre,institute,
        degrre_year,total_exp,pharma_xperience,certficate_name,certificat_doc,skill1,skill2,skill3,skill4,skill5,skill6,skill7,skill8,previous_exp,responsibiliti,last_review_date,
        review_authority,review_outcome,acknowledgment_emp_name,emp_signature,acknowledgment_date) 
     VALUES ('".$_GET["plant_id"]."','".$input["emp_name"]."', '".$input["emp_id"]."', '".$input["qualification_date"]."', '".$input["dob"]."', '".$input["contact_info"]."',
        '".$input["degrre"]."', '".$input["institute"]."', '".$input["degrre_year"]."', '".$input["total_exp"]."', '".$input["pharma_xperience"]."', '".$input["certficate_name"]."',
        '".$input["certificat_doc"]."', '".$input["skill1"]."', '".$input["skill2"]."', '".$input["skill3"]."', '".$input["skill4"]."', '".$input["skill5"]."', '".$input["skill6"]."', 
        '".$input["skill7"]."', '".$input["skill8"]."', '".$input["previous_exp"]."', '".$input["responsibiliti"]."', '".$input["last_review_date"]."', '".$input["review_authority"]."',
        '".$input["review_outcome"]."', '".$input["acknowledgment_emp_name"]."', '".$input["emp_signature"]."', '".$input["acknowledgment_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
    
  }
 
  else if ($_GET["type"] == "saveReceivComplaintform") {
      $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["attachment1"])) {
            $file_tmp =$_FILES['attachment1']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachment1']['name'])));
            $file_name = $emp_id."attachment1.".$file_ext;
            $attachment1 = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
  $sql = "INSERT INTO   received_complaint   (plant_id , rec_date, complaint_no, name, contact, address, complaint_nature,inc_location,
         compl_description,security_level,priority_level,related_compl,attachment1,c_name,c_contact,c_email,c_phone,assigned_to,investigation,resolve_date,resolve_status,additional_action,follow_up_date,closed_date,
        follow_date,cause_analysis,preventive_measures,feedback,satisfaction_level) 
     VALUES ('".$_GET["plant_id"]."','".$input["rec_date"]."', '".$input["complaint_no"]."', '".$input["name"]."', '".$input["contact"]."', '".$input["address"]."',
     
        '".$input["complaint_nature"]."', '".$input["inc_location"]."', '".$input["compl_description"]."', '".$input["security_level"]."', '".$input["priority_level"]."', '".$input["related_compl"]."',
        '$attachment1', '".$input["c_name"]."', '".$input["c_contact"]."', '".$input["c_email"]."', '".$input["c_phone"]."', '".$input["assigned_to"]."', '".$input["investigation"]."', 
        
        '".$input["resolve_date"]."', '".$input["resolve_status"]."', '".$input["additional_action"]."', '".$input["follow_up_date"]."', '".$input["closed_date"]."', '".$input["follow_date"]."',
        '".$input["cause_analysis"]."', '".$input["preventive_measures"]."', '".$input["feedback"]."', '".$input["satisfaction_level"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   }
   else if ($_GET["type"] == "saveoostrendform") {
        $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["attachment"])) {
            $file_tmp =$_FILES['attachment']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachment']['name'])));
            $file_name = $emp_id."attachment.".$file_ext;
            $attachment = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/employee/".$file_name);
        }
        
  $sql = "INSERT INTO   oos_trend    (plant_id , report_id, report_date, product_name, batch_no, parameter,specification_limit,oss_date,
        oss_time,location,oss_description,cause_analysis,action_taken,previous_oos_no,trend_status,attachment,review_officer,review_date,approval_status) 
        VALUES ('".$_GET["plant_id"]."','".$input["report_id"]."', '".$input["report_date"]."', '".$input["product_name"]."', '".$input["batch_no"]."', '".$input["parameter"]."',
          '".$input["specification_limit"]."', '".$input["oss_date"]."', '".$input["oss_time"]."', '".$input["location"]."', '".$input["oss_description"]."', '".$input["cause_analysis"]."',
        '".$input["action_taken"]."', '".$input["previous_oos_no"]."', '".$input["trend_status"]."', '$attachment', '".$input["review_officer"]."', '".$input["review_date"]."', '".$input["approval_status"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   }
    else if ($_GET["type"] == "saveCleaningreportForm") {
  $sql = "INSERT INTO    cleaning_report     (plant_id , area_name, ipqc_location, ipqc_inspector, cle_start_time, cle_end_time,cle_procedure,visual_inspection,
        chemical_testing,microbil_test,cleaned_by,cle_date,inspect_by,inspecting_date,prepared_by,prepared_date,review_by,review_date) 
        VALUES ('".$_GET["plant_id"]."','".$input["area_name"]."', '".$input["ipqc_location"]."', '".$input["ipqc_inspector"]."', '".$input["cle_start_time"]."', '".$input["cle_end_time"]."',
          '".$input["cle_procedure"]."', '".$input["visual_inspection"]."', '".$input["chemical_testing"]."', '".$input["microbil_test"]."', '".$input["cleaned_by"]."', '".$input["cle_date"]."',
        '".$input["inspect_by"]."', '".$input["inspecting_date"]."', '".$input["prepared_by"]."', '".$input["prepared_date"]."', '".$input["review_by"]."', '".$input["review_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   }
   else if ($_GET["type"] == "saveStatusForm") {
  $sql = "INSERT INTO    analytical_material      (plant_id , m_name, m_code, supplier_name, supllier_id, batch_no,date_receipt,apperance,
        colour,odour,chemical_composition,impurities,microbial_load,testmethod_used,testing_method,test_equpment,test_result,ref_standard,comment,reviewed_by,reviewed_date,approval_status,head_name,author_date,sign) 
        VALUES ('".$_GET["plant_id"]."','".$input["m_name"]."', '".$input["m_code"]."', '".$input["supplier_name"]."', '".$input["supllier_id"]."', '".$input["batch_no"]."',
          '".$input["date_receipt"]."', '".$input["apperance"]."', '".$input["colour"]."', '".$input["odour"]."', '".$input["chemical_composition"]."', '".$input["impurities"]."',
        '".$input["microbial_load"]."', '".$input["testmethod_used"]."', '".$input["testing_method"]."', '".$input["test_equpment"]."', '".$input["test_result"]."', '".$input["ref_standard"]."', '".$input["comment"]."', '".$input["reviewed_by"]."', '".$input["reviewed_date"]."', '".$input["approval_status"]."', '".$input["head_name"]."', '".$input["author_date"]."', '".$input["sign"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   }
   else if ($_GET["type"] == "saveRequalificationform") {
       $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["refrences_doc"])) {
            $file_tmp =$_FILES['refrences_doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['refrences_doc']['name'])));
            $file_name = $emp_id."refrences_doc.".$file_ext;
            $refrences_doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/employee/".$file_name);
        }
        	if(isset($_FILES["support_doc"])) {
            $file_tmp =$_FILES['support_doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['support_doc']['name'])));
            $file_name = $emp_id."support_doc.".$file_ext;
            $support_doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/employee/".$file_name);
        }

  $sql = "INSERT INTO    requalification_eqp  (plant_id , equipment_name, equipment_id, location, requal_date, requal_purpose,pre_qual_detail,iq_result,
        iq_obs,oq_result,oq_obs,pq_result,pq_obs,details,action_tack,refrences,support_doc) 
        VALUES ('".$_GET["plant_id"]."','".$input["equipment_name"]."', '".$input["equipment_id"]."', '".$input["location"]."', '".$input["requal_date"]."', '".$input["requal_purpose"]."',
          '".$input["pre_qual_detail"]."', '".$input["iq_result"]."', '".$input["iq_obs"]."', '".$input["oq_result"]."', '".$input["oq_obs"]."', '".$input["pq_result"]."',
        '".$input["pq_obs"]."', '".$input["details"]."', '".$input["action_tack"]."', '$refrences_doc', '$support_doc')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   }
   else if ($_GET["type"] == "saveStanderdOpratingProcedure") {
       $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["signature"])) {
            $file_tmp =$_FILES['signature']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['signature']['name'])));
            $file_name = $emp_id."signature.".$file_ext;
            $signature = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
        	if(isset($_FILES["supporting_doc"])) {
            $file_tmp =$_FILES['supporting_doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['supporting_doc']['name'])));
            $file_name = $emp_id."supporting_doc.".$file_ext;
            $supporting_doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }

  $sql = "INSERT INTO     std_oprating_procedure   (plant_id , purpose, scope, equ_name, equ_id, man_name,model,temp,
        pressure,spped,call_date,call_status,sop_revision,document_reviev,user_name,signature,dept_head,review_date,approval_status,comments_head,dep_head,supporting_doc) 
        VALUES ('".$_GET["plant_id"]."','".$input["purpose"]."', '".$input["scope"]."', '".$input["equ_name"]."', '".$input["equ_id"]."', '".$input["man_name"]."',
          '".$input["model"]."', '".$input["temp"]."', '".$input["pressure"]."', '".$input["spped"]."', '".$input["call_date"]."', '".$input["call_status"]."',
        '".$input["sop_revision"]."', '".$input["document_reviev"]."', '".$input["user_name"]."', '$signature','".$input["dept_head"]."','".$input["review_date"]."','".$input["approval_status"]."','".$input["comments_head"]."','".$input["dep_head"]."', '$supporting_doc')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   
}
else if ($_GET["type"] == "savemanufacture_certificateform") {
       $input = $_POST;
       $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["uplode_doc"])) {
            $file_tmp =$_FILES['uplode_doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['uplode_doc']['name'])));
            $file_name = $emp_id."uplode_doc.".$file_ext;
            $uplode_doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
        	
   $sql = "INSERT INTO      qa_mfg_certificateform    (plant_id , product_name, product_code, batch_no, mfg_date, exp_date,production_line,comments, uplode_doc) 
        VALUES ('".$_GET["plant_id"]."','".$input["product_name"]."', '".$input["product_code"]."', '".$input["batch_no"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."',
          '".$input["production_line"]."', '".$input["comments"]."', '$uplode_doc')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   
}
  
  else if ($_GET["type"] == "saveFinalReportform") {
       $input = $_POST;
       $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["sign"])) {
            $file_tmp =$_FILES['sign']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['sign']['name'])));
            $file_name = $emp_id."sign.".$file_ext;
            $sign = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
        	
   $sql = "INSERT INTO       final_report     (plant_id , project_name, project_code, oq_complit_date, equpment_name, equpment_id,manufacturer,model,temp_setting,pressurre_setting,
                speed_setting,test_case_name,description,expexted_result,actual_result,pass_fail,dev_description,resolution,qulif_status,qa_head_comm,further_action,authorized_person,date_authorized) 
        VALUES ('".$_GET["plant_id"]."','".$input["project_name"]."', '".$input["project_code"]."', '".$input["oq_complit_date"]."', '".$input["equpment_name"]."', '".$input["equpment_id"]."',
          '".$input["manufacturer"]."', '".$input["model"]."', '".$input["temp_setting"]."', '".$input["pressurre_setting"]."', '".$input["speed_setting"]."', '".$input["test_case_name"]."',
          '".$input["description"]."', '".$input["expexted_result"]."', '".$input["actual_result"]."', '".$input["pass_fail"]."','".$input["dev_description"]."','".$input["qulif_status"]."',
          '".$input["qa_head_comm"]."','".$input["further_action"]."','".$input["authorized_person"]."','".$input["date_authorized"]."','$sign')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   
  }
  else if ($_GET["type"] == "saveDeviationOQDumyform") {
      $input = $_POST;
      $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["supporing_doc"])) {
            $file_tmp =$_FILES['supporing_doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['supporing_doc']['name'])));
            $file_name = $emp_id."supporing_doc.".$file_ext;
            $supporing_doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
        	if(isset($_FILES["sign_doc"])) {
            $file_tmp =$_FILES['sign_doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['sign_doc']['name'])));
            $file_name = $emp_id."sign_doc.".$file_ext;
            $sign_doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }

  $sql = "INSERT INTO     deviation_oq    (plant_id , p_name, p_code, date_deviation, eqp_name, eqp_id,description_eqp_,deviation_id,
        deviation_type,descrption_deviation,affected_system,severity_level,cause_analysis,investigation_team,action,verf_date,verf_by,approval_date,approved_by,closure_date,follo_action,supporing_doc,authorized_person,date_authoriz,sign_doc) 
        VALUES ('".$_GET["plant_id"]."','".$input["p_name"]."', '".$input["p_code"]."', '".$input["date_deviation"]."', '".$input["eqp_name"]."', '".$input["eqp_id"]."',
          '".$input["description_eqp_"]."', '".$input["deviation_id"]."', '".$input["deviation_type"]."', '".$input["descrption_deviation"]."', '".$input["affected_system"]."', '".$input["severity_level"]."',
        '".$input["cause_analysis"]."', '".$input["investigation_team"]."', '".$input["action"]."','".$input["verf_date"]."','".$input["verf_by"]."','".$input["approval_date"]."','".$input["approved_by"]."','".$input["closure_date"]."','".$input["follo_action"]."', '$supporing_doc','".$input["authorized_person"]."','".$input["date_authoriz"]."','$sign_doc')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   
}
else if ($_GET["type"] == "saverefrence_standard_bookform") {
      $input = $_POST;
      $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["doc"])) {
            $file_tmp =$_FILES['doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['doc']['name'])));
            $file_name = $emp_id."doc.".$file_ext;
            $doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
        if($conn->query($sql)){
        $last_id = $conn->insert_id;
        foreach($input["fromlevel1"] as $fromlevel1Data){
    	$sql="INSERT INTO  refrence_std_stock  (date ,qty_consumed ,reciving_qty,user)
                                value(".$last_id.",
                                      '".$fromlevel1Data["date"]."',
                                      '".$fromlevel1Data["qty_consumed"]."',
                                      '".$fromlevel1Data["reciving_qty"]."',
                                      '".$fromlevel1Data["user"]."')";
    	 $conn->query($sql);
        }
         
    	
    	
  $sql = "INSERT INTO     primary_standard_stock (plant_id , s_title, s_identifier,grade, batch_no, record_details,record_date,received_qty,available_qty,
        umo,storage_location,doc,date,qty_consumed,reciving_qty,user,prepared_by,prepared_date,review_by,review_date,stock_type) 
        VALUES ('".$_GET["plant_id"]."','".$input["s_title"]."', '".$input["s_identifier"]."','".$input["grade"]."', '".$input["batch_no"]."', '".$input["record_details"]."',
          '".$input["record_date"]."', '".$input["received_qty"]."', '".$input["available_qty"]."',
        '".$input["umo"]."', '".$input["storage_location"]."', '$doc','".$input["date"]."','".$input["qty_consumed"]."','".$input["reciving_qty"]."','".$input["user"]."',
        '".$input["prepared_by"]."','".$input["prepared_date"]."','".$input["review_by"]."','".$input["review_date"]."','Referance Standard')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
   
}
else if ($_GET["type"] == "saveworking_standard_bookform") {
     $input = $_POST;
      $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["doc"])) {
            $file_tmp =$_FILES['doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['doc']['name'])));
            $file_name = $emp_id."doc.".$file_ext;
            $doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
       	
      $sql = "INSERT INTO     primary_standard_stock (plant_id , s_title, s_identifier,grade, batch_no, record_details,record_date,received_qty,available_qty,
        umo,storage_location,doc,date,qty_consumed,reciving_qty,user,prepared_by,prepared_date,review_by,review_date,stock_type) 
        VALUES ('".$_GET["plant_id"]."','".$input["s_title"]."', '".$input["s_identifier"]."','".$input["grade"]."', '".$input["batch_no"]."', '".$input["record_details"]."',
          '".$input["record_date"]."', '".$input["received_qty"]."', '".$input["available_qty"]."',
        '".$input["umo"]."', '".$input["storage_location"]."', '$doc','".$input["date"]."','".$input["qty_consumed"]."','".$input["reciving_qty"]."','".$input["user"]."',
        '".$input["prepared_by"]."','".$input["prepared_date"]."','".$input["review_by"]."','".$input["review_date"]."','Working Standard')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   
}
else if ($_GET["type"] == "saveprimary_standard_stockform") {
      	
      	$input = $_POST;
      $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["doc"])) {
            $file_tmp =$_FILES['doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['doc']['name'])));
            $file_name = $emp_id."doc.".$file_ext;
            $doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
     $sql = "INSERT INTO     primary_standard_stock (plant_id , s_title, s_identifier,grade, batch_no, record_details,record_date,received_qty,available_qty,
        umo,storage_location,doc,date,qty_consumed,reciving_qty,user,prepared_by,prepared_date,review_by,review_date,stock_type) 
        VALUES ('".$_GET["plant_id"]."','".$input["s_title"]."', '".$input["s_identifier"]."','".$input["grade"]."', '".$input["batch_no"]."', '".$input["record_details"]."',
          '".$input["record_date"]."', '".$input["received_qty"]."', '".$input["available_qty"]."',
        '".$input["umo"]."', '".$input["storage_location"]."', '$doc','".$input["date"]."','".$input["qty_consumed"]."','".$input["reciving_qty"]."','".$input["user"]."',
        '".$input["prepared_by"]."','".$input["prepared_date"]."','".$input["review_by"]."','".$input["review_date"]."','Primary Standard')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   
}
else if ($_GET["type"] == "saveexpiry_standard_form") {
      	
   $sql = "INSERT INTO        expiry_standard_stock  (plant_id , e_name, e_std_num, e_revision, e_date_exp, e_reason_exp,e_impact,e_replacement,e_disposal,e_record_details,
                e_location,e_prepared_by,e_prepared_date,review_by,review_date) 
        VALUES ('".$_GET["plant_id"]."','".$input["e_name"]."', '".$input["e_std_num"]."', '".$input["e_revision"]."', '".$input["e_date_exp"]."', '".$input["e_reason_exp"]."',
          '".$input["e_impact"]."', '".$input["e_replacement"]."', '".$input["e_disposal"]."', '".$input["e_record_details"]."', '".$input["e_location"]."','".$input["e_prepared_by"]."' ,
          '".$input["e_prepared_date"]."', '".$input["review_by"]."', '".$input["review_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   
}
else if ($_GET["type"] == "savedistruction_record_form") {
      	
   $sql = "INSERT INTO  distruction_record (plant_id , destruction_date, department, material_name, material_type, description,resson_destruction,destruction_method,add_comments,confirming_person,
                witness) 
        VALUES ('".$_GET["plant_id"]."','".$input["destruction_date"]."', '".$input["department"]."', '".$input["material_name"]."', '".$input["material_type"]."', '".$input["description"]."',
          '".$input["resson_destruction"]."', '".$input["destruction_method"]."', '".$input["add_comments"]."', '".$input["confirming_person"]."', '".$input["witness"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   
}
else if ($_GET["type"] == "savedisinfectant_pre_recordform") {
      	
   $sql = "INSERT INTO  disinfectant_pre_record  (plant_id , date_preparation, department, disinfectan_name, batch_no, manufacture,ingredients,concentration,prepration_method,equipment,
                confirmingPerson,prepared_by,quality_check,storage_instruction,usage_instruction,expiration_date) 
        VALUES ('".$_GET["plant_id"]."','".$input["date_preparation"]."', '".$input["department"]."', '".$input["disinfectan_name"]."', '".$input["batch_no"]."', '".$input["manufacture"]."',
          '".$input["ingredients"]."', '".$input["concentration"]."', '".$input["prepration_method"]."', '".$input["equipment"]."', '".$input["confirmingPerson"]."','".$input["prepared_by"]."',
          '".$input["quality_check"]."','".$input["storage_instruction"]."','".$input["usage_instruction"]."','".$input["expiration_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   
}
    else if($_GET["type"]=="get_stock"){
            
    // $s_title = isset($_GET["s_title"]) ? $_GET["s_title"] : '';

	$sql = "SELECT * FROM primary_standard_stock  WHERE s_identifier='".$_GET["s_identifier"]."' ";
		$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);

}
}
$conn->close();
?>