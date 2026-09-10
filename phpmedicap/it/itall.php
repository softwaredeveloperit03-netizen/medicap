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
    
    if ($_GET["type"] == "savesoftware_query_form") {
         $input = $_POST;
       $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["attachment_doc"])) {
            $file_tmp =$_FILES['attachment_doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachment_doc']['name'])));
            $file_name = $emp_id."attachment_doc.".$file_ext;
            $attachment_doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
        
              $sql = "INSERT INTO   software_query   (plant_id , department, date, query_id, person_name, email, address,phone_no,software_name,software_type,description,query_level,category,other_category,query_details,action_description,prpared_restli,impact,comment,declaration,attachment_doc ) 
     VALUES ('".$_GET["plant_id"]."','".$input["department"]."', '".$input["date"]."', '".$input["query_id"]."', '".$input["person_name"]."', '".$input["email"]."',
        '".$input["address"]."', '".$input["phone_no"]."', '".$input["software_name"]."', '".$input["software_type"]."', '".$input["description"]."',
        '".$input["query_level"]."','".$input["category"]."','".$input["other_category"]."','".$input["query_details"]."','".$input["action_description"]."','".$input["prpared_restli"]."','".$input["impact"]."','".$input["comment"]."','".$input["declaration"]."','$attachment_doc')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
 
  
    else if ($_GET["type"] == "savemasterloginform") {
        $input = $_POST;
  $sql = "INSERT INTO    master_login     (plant_id , username, pass, designation, other_dept, access_level,roll,other_roll,
        emp_id,email_id,phon_no,location,permisstion,enabled,disabled,enforced,not_enforced,declaration_date) 
        VALUES ('".$_GET["plant_id"]."','".$input["username"]."', '".$input["pass"]."', '".$input["designation"]."', '".$input["other_dept"]."', '".$input["access_level"]."',
          '".$input["roll"]."', '".$input["other_roll"]."', '".$input["emp_id"]."', '".$input["email_id"]."', '".$input["phon_no"]."', '".$input["location"]."',
        '".$input["permisstion"]."', '".$input["enabled"]."', '".$input["disabled"]."', '".$input["enforced"]."', '".$input["not_enforced"]."', '".$input["declaration_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   }
   
   
  
else if ($_GET["type"] == "saveauditform") {
       
   $sql = "INSERT INTO       audit     (plant_id , audit_date, audit_system, feature, audit_by, audit_type,other_audit_type,observation,date_change, user_esponce,type_change,description_change) 
        VALUES ('".$_GET["plant_id"]."','".$input["audit_date"]."', '".$input["audit_system"]."', '".$input["feature"]."', '".$input["audit_by"]."', '".$input["audit_type"]."','".$input["other_audit_type"]."',
          '".$input["observation"]."', '".$input["date_change"]."','".$input["user_esponce"]."','".$input["type_change"]."','".$input["description_change"]."' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   
}
else if ($_GET["type"] == "savechangerequestform") {
         $input = $_POST;
       $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["attachment_doc1"])) {
            $file_tmp =$_FILES['attachment_doc1']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachment_doc1']['name'])));
            $file_name = $emp_id."attachment_doc1.".$file_ext;
            $attachment_doc1 = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
        
             $sql = "INSERT INTO sw_change_request (plant_id, request_name, request_email, request_date, sw_name, module, version, change, description, justification, urgency, attachment_doc1, test_plan, rebatch_plan, approvel_requird, approver_name, approval_date, comments, declaration_date ) 
     VALUES ('".$_GET["plant_id"]."', '".$input["request_name"]."', '".$input["request_email"]."', '".$input["request_date"]."', '".$input["sw_name"]."', '".$input["module"]."',
        '".$input["version"]."', '".$input["change"]."', '".$input["description"]."', '".$input["justification"]."', '".$input["urgency"]."','$attachment_doc1',
        '".$input["test_plan"]."', '".$input["rebatch_plan"]."','".$input["approvel_requird"]."','".$input["approver_name"]."','".$input["approval_date"]."','".$input["comments"]."','".$input["declaration_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
}
  
  else if ($_GET["type"] == "savedesktopapplicationform") {
        
             $sql = "INSERT INTO desktop_application  (plant_id, request_name, request_email, request_date, apl_title, apl_reviev, apl_vender, licence_type, other_lic, licence_key, 
             instol_path, requirement, user_id, user_name, acc_permisstion, integ_requirement, depandancy,approvel_requird,approver_name,approval_date,comments,declaration_date) 
     VALUES ('".$_GET["plant_id"]."', '".$input["request_name"]."', '".$input["request_email"]."', '".$input["request_date"]."', '".$input["apl_title"]."', '".$input["apl_reviev"]."',
        '".$input["apl_vender"]."', '".$input["licence_type"]."', '".$input["other_lic"]."', '".$input["licence_key"]."', 
        '".$input["instol_path"]."', '".$input["requirement"]."','".$input["user_id"]."','".$input["user_name"]."','".$input["acc_permisstion"]."','".$input["integ_requirement"]."',
            '".$input["depandancy"]."','".$input["approvel_requird"]."','".$input["approver_name"]."','".$input["approval_date"]."','".$input["comments"]."','".$input["declaration_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
}
else if ($_GET["type"] == "savemobileapplicationform") {
        
             $sql = "INSERT INTO mobile_application   (plant_id, request_name, request_email, request_date, apl_title, apl_platform, apl_version, apl_purpose, licence_type, other, 
             dist_method, target_dev, user_id, user_name, acc_permisstion, integration_sys, dependancies,security_requir,approvel_requird,approver_name,approval_date,comments,declaration_date) 
     VALUES ('".$_GET["plant_id"]."', '".$input["request_name"]."', '".$input["request_email"]."', '".$input["request_date"]."', '".$input["apl_title"]."', '".$input["apl_platform"]."',
        '".$input["apl_version"]."', '".$input["apl_purpose"]."', '".$input["licence_type"]."', '".$input["other"]."', 
        '".$input["dist_method"]."', '".$input["target_dev"]."','".$input["user_id"]."','".$input["user_name"]."','".$input["acc_permisstion"]."','".$input["integration_sys"]."',
            '".$input["dependancies"]."','".$input["security_requir"]."','".$input["approvel_requird"]."','".$input["approver_name"]."','".$input["approval_date"]."','".$input["comments"]."','".$input["declaration_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
} 
 
}
$conn->close();
?>