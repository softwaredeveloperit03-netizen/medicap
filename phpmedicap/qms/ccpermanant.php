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

    /*
        CC Status:
        New Form: `Initiated`
        Dept. Checking: `Initiated Dept Checking`
        Pre Approval QA Exec. : `PRE APPROVAL OF QA EXECUTIVE`
        Pre Approval QA Head: `PRE APPROVAL OF QA HEAD`
        Dept. Review: `CONCERNED DEPT REVIEW`
        Post Approval QA Head: `POST APPROVAL OF QA HEAD`
        Extension: `EXTENSION OF CC`
        If Extension Required: Extension Approval by QA Head: `EXTENSION CHECKING`
        Additional Imapct Evaluation: `ADDITIONAL EVALUATION`
        Post Implementation: `POST IMPLEMENTATION`
        CQA Recommendation: `COA RECOMMENDATION`
        Closure of CC: `CLOSED`
    */
    if ($_GET["type"] == "initiateCC") {
        $sql = "INSERT INTO ccpermanant (document_type, cc_related, change_title, document_no, revision_no, existing_procedure, proposed_change, change_reason, affected_for,initiate_dept, initiate_by, initiate_date,action_plan, requirements, final_target) VALUE ('".$input["document_type"]."', '".$input["cc_related"]."', '".$input["change_title"]."', '".$input["document_no"]."', '".$input["revision_no"]."', '".$input["existing_procedure"]."', '".$input["proposed_change"]."', '".$input["change_reason"]."', '".$input["affected_for"]."','".$_GET["department"]."', '".$_GET["emp_id"]."', '$entry_date', '".json_encode($input["actions"])."', '".json_encode($input["requirement"])."', '".$input["final_target"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if ($_GET["type"] == "OinitiateCC") {
             $input = $_POST;        
            $plant_id=$_GET["plant_id"];
           $data = json_decode($input["data"], true);
             $target_dir = "../../../upload/";
           if(isset($_FILES["photo"]["name"])) {
            	$target_file = $target_dir.$plant_id.$last_id."_".basename($_FILES["photo"]["name"]);
            	$photo = $plant_id.$last_id."_".basename($_FILES["photo"]["name"]);
        	    move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
        	   
           }
            $sql = "INSERT INTO ccpermanant (document,department,issue_code,reference,date_initiation,market,client_code,cc_related_product_code,document_type, cc_related, change_title, document_no, revision_no, existing_procedure, proposed_change, change_reason, affected_for,initiate_dept, initiate_by, initiate_date,action_plan, requirements, final_target) VALUE ('".$photo."','".$data["department"]."','".$data["issue_code"]."','".$data["reference"]."','".$data["date_initiation"]."','".$data["market"]."','".$data["client_code"]."','".$data["product_code"]."','".$data["document_type"]."', '".$data["cc_related"]."', '".$data["change_title"]."', '".$data["document_no"]."', '".$data["revision_no"]."', '".$data["existing_procedure"]."', '".$data["proposed_change"]."', '".$data["change_reason"]."', '".$data["affected_for"]."','".$_GET["department"]."', '".$_GET["emp_id"]."', '$entry_date', '".json_encode($data["actions"])."', '".json_encode($data["requirement"])."', '".$data["final_target"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    else if ($_GET["type"] == "getPendingAttachements") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE status !='CLOSED' AND initiate_dept IN ('".$_GET["department"]."', 'Master', 'Quality Assurance')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["action_plan"] = json_decode($row["action_plan"]);
                $row["requirements"] = json_decode($row["requirements"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "uploadAttachment") {
	    $attachment = "";
        if(isset($_FILES['attachment'])) {
            $id = date("YmdHis", $timestamp);
            $file_tmp =$_FILES['attachment']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachment']['name'])));
            $file_name = $id.".".$file_ext;
            $attachment = $file_name;
            move_uploaded_file($file_tmp,"../upload/ccpermanant/".$file_name);
            
            $sql = "INSERT INTO ccpermanant_files (cc_no, type, particular, file) VALUES ('".$_GET["cc_no"]."', '".$_POST["type"]."', '".$_POST["particular"]."', '$attachment')";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } else {
            echo "{\"status\":\"failed\"}";
        }
	} else if ($_GET["type"] == "deleteAttachment") {
	    $sql = "DELETE FROM ccpermanant_files WHERE id='".$_GET["id"]."'";
	    if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
	} else if ($_GET["type"] == "getCCDetails") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE cc_no='".$_GET["cc_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["action_plan"] = json_decode($row["action_plan"]);
                $row["requirements"] = json_decode($row["requirements"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='PROPOSED CHANGE'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
                echo json_encode($row);
                break;
            }
        } else {
            echo "{}";
        }
    } else if ($_GET["type"] == "getInitiatedCC") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE status ='Initiated' AND initiate_dept IN ('".$_GET["department"]."', 'Master') order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["action_plan"] = json_decode($row["action_plan"]);
                $row["requirements"] = json_decode($row["requirements"]);
                
                $actions = $row["action_plan"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->status == 'No') {
                        $action->description = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["action_plan"] = $actions;
                
                $actions = $row["requirements"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->department == '') {
                        $action->department = "Not Applicable";
                    }
                    if ($action->details == '') {
                        $action->details = "Not Applicable";
                    }
                    if ($action->target_date == '') {
                        $action->target_date = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["requirements"] = $actions;
                
                $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='PROPOSED CHANGE'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "checkInitiatedCC") {
        $sql = "UPDATE ccpermanant SET status='Initiated Dept Checking', dept_remark='".$input["remark"]."', dept_check_by='".$_GET["emp_id"]."', dept_check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingPreApproval") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE status ='Initiated Dept Checking'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["action_plan"] = json_decode($row["action_plan"]);
                $row["requirements"] = json_decode($row["requirements"]);
                
                $actions = $row["action_plan"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->status == 'No') {
                        $action->description = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["action_plan"] = $actions;
                
                $actions = $row["requirements"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->department == '') {
                        $action->department = "Not Applicable";
                    }
                    if ($action->details == '') {
                        $action->details = "Not Applicable";
                    }
                    if ($action->target_date == '') {
                        $action->target_date = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["requirements"] = $actions;
                
                $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='PROPOSED CHANGE'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "savePreApprovalCC") {
           $input = $_POST;        
            $plant_id=$_GET["plant_id"];
           $data = json_decode($input["data"], true);
           
         $target_dir = "../../../upload/";
           if(isset($_FILES["photo"]["name"])) {
            	$target_file = $target_dir.$plant_id.$last_id."_".basename($_FILES["photo"]["name"]);
            	$photo = $plant_id.$last_id."_".basename($_FILES["photo"]["name"]);
        	    move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
        	   
           }
        $sql = "UPDATE ccpermanant SET status='PRE APPROVAL OF QA EXECUTIVE',change_proposed='".$data["proposed_change"]."', change_status='".$data["change_status"]."', reject_reason='".$data["reject_reason"]."', preapproval_by='".$_GET["emp_id"]."', preapproval_date='$entry_date',cc_document='$photo' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            
             $departments = $data["departments"];
            for ($i = 0; $i < count($departments); $i++) {
                $department = $departments[$i];
                $sql1 = "INSERT INTO cc_department_review 
            (ccpermanant_id, department,plant_id) 
            VALUES ('".$_GET["id"]."', '$department','".$_GET["plant_id"]."')";
                $conn->query($sql1);
            }
            
                    $json_obj = json_encode($input["requirement"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
  $sql = "INSERT INTO change_action( ccpermanant_id,plant_id, action, department_name, employee, target_date, status) VALUES ('".$_GET["id"]."','".$_GET["plant_id"]."','".$values["attr1ibute"]."','".$values["department_name"]."','".$values["Employee"]."','".$values["target_date"]."','pending')";
        if ($conn->query($sql)) {
           
                
        
              $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        //  if ($status1) {
        //     echo "{\"status\":\"success\"}";
        // } else {
        //     echo "{\"status\":\"".$conn->error."\"}";
        // }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getPendingPreApprovalCustomer") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE status ='PRE APPROVAL OF QA EXECUTIVE' AND LOWER(change_status)='approved'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["action_plan"] = json_decode($row["action_plan"]);
                $row["requirements"] = json_decode($row["requirements"]);
                
                $actions = $row["action_plan"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->status == 'No') {
                        $action->description = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["action_plan"] = $actions;
                
                $actions = $row["requirements"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->department == '') {
                        $action->department = "Not Applicable";
                    }
                    if ($action->details == '') {
                        $action->details = "Not Applicable";
                    }
                    if ($action->target_date == '') {
                        $action->target_date = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["requirements"] = $actions;
                
                $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='PROPOSED CHANGE'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
      else if ($_GET["type"] == "getPendingPreApprovalOcustomer") {
          $output = Array();
      echo $sql = "SELECT * FROM ccpermanant WHERE status ='PRE APPROVAL OF QA EXECUTIVE' AND LOWER(change_status)='approved'";
        $result = $conn->query($sql);
        //$result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                        $sql1 = "SELECT * FROM change_action WHERE ccpermanant_id='".$row["id"]."'  ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                        $sql2 = "SELECT * FROM cc_department_review WHERE ccpermanant_id='".$row["id"]."' and status='Checked' and remark2='pending' ";
                        
                        $result2 = $conn->query($sql2);
                         $flaf=0;
                        if ($result2->num_rows > 0) {
                             $flaf=1;
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                       
                       
                        $row["review"] = $output2;
                        $row["action"] = $output1;
                        if($flaf==1){
                        $output[] = $row;
                        }
                }
            }
            //  $data["material_types"] = $output;
        echo json_encode($output);
          
      }
      else if ($_GET["type"] == "getPendingaction") {
          $output = Array();
       $sql = "SELECT * FROM ccpermanant";
        $result = $conn->query($sql);
        //$result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                        $sql1 = "SELECT * FROM change_action WHERE ccpermanant_id='".$row["id"]."' and department_name='".$_GET["department1"]."' ";
                        $flaf=0;
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            $flaf=1;
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                       
                       
                        $row["action"] = $output1;
                        if($flaf==1){
                        $output[] = $row;
                        }
                       
                }
            }
            //  $data["material_types"] = $output;
        echo json_encode($output);
          
      }
      else if ($_GET["type"] == "getPendingareview") {
          $output = Array();
       $sql = "SELECT * FROM ccpermanant";
        $result = $conn->query($sql);
        //$result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                        $sql1 = "SELECT * FROM cc_department_review WHERE ccpermanant_id='".$row["id"]."' and department='".$_GET["department1"]."' and status='pending' ";
                        $flaf=0;
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            $flaf=1;
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                       
                       
                        $row["action"] = $output1;
                        if($flaf==1){
                        $output[] = $row;
                        }
                       
                }
            }
            //  $data["material_types"] = $output;
        echo json_encode($output);
          
      }
    else if ($_GET["type"] == "savePreApprovalCustomer1") {
        $sql = "UPDATE ccpermanant SET status='PRE APPROVAL OF CUSTOMER', preapproval_customer='".$input["preapproval_customer"]."', customer_comment='".$input["customer_comment"]."', preapproval_customer_by='".$_GET["emp_id"]."', preapproval_customer_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            $departments = $input["departments"];
            for ($i = 0; $i < count($departments); $i++) {
                $department = $departments[$i];
                $sql1 = "INSERT INTO cc_department_review 
            (ccpermanant_id, department,plant_id,entry_from) 
            VALUES ('".$_GET["id"]."', '$department','".$_GET["plant_id"]."','QA review')";
                $conn->query($sql1);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "savedeptReview") {
        $sql = "UPDATE cc_department_review SET remark='".$input["remark"]."',status='Checked' WHERE ccpermanant_id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "savedeptReview1") {
        $sql = "UPDATE cc_department_review SET remark2='".$input["remark"]."',status='Checked' WHERE ccpermanant_id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "getPendingPreApprovalOChecking") {
          $output = Array();
      $sql = "SELECT * FROM ccpermanant WHERE status ='PRE APPROVAL OF CUSTOMER' AND LOWER(change_status)='approved'";
        $result = $conn->query($sql);
        //$result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                        $sql1 = "SELECT * FROM change_action WHERE ccpermanant_id='".$row["id"]."'  ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                        $sql2 = "SELECT * FROM cc_department_review WHERE ccpermanant_id='".$row["id"]."' and status='Checked' and remark2!='pending' ";
                        
                        $result2 = $conn->query($sql2);
                         $flaf=0;
                        if ($result2->num_rows > 0) {
                             $flaf=1;
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                       
                       
                        $row["review"] = $output2;
                        $row["action"] = $output1;
                        if($flaf==1){
                        $output[] = $row;
                        }
                }
            }
            //  $data["material_types"] = $output;
        echo json_encode($output);
          
      } else if ($_GET["type"] == "preApprovalChecking") {
        $sql = "UPDATE ccpermanant SET status='PRE APPROVAL OF QA HEAD', preapproval_remark='".$input["remark"]."', preapproval_check_by='".$_GET["emp_id"]."', preapproval_check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
          
            $departments = $input["departments"];
            for ($i = 0; $i < count($departments); $i++) {
                $department = $departments[$i];
            echo    $sql = "INSERT INTO ccp_departments (cc_no, department) VALUES ('".$input["cc_no"]."', '$department')";
                $conn->query($sql);
            }
              echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingConcernedDeptReview") { // code work fine cmt for olive flow
        $output = array();
        $sql = "SELECT * FROM  ccpermanant  WHERE status ='PRE APPROVAL OF QA HEAD'";
    //  echo   $sql = "SELECT c.*, d.id as dept_id, d.department FROM ccp_departments d LEFT JOIN ccpermanant c ON d.cc_no=c.cc_no WHERE d.status ='pending' AND LOWER(c.change_status)='approved' AND (department='".$_GET["department"]."' OR department LIKE '%%')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            //     $row["action_plan"] = json_decode($row["action_plan"]);
            //     $row["requirements"] = json_decode($row["requirements"]);
                
            //     $actions = $row["action_plan"];
            //     for ($i = 0; $i < count($actions); $i++) {
            //         $action = $actions[$i];
            //         if ($action->status == 'No') {
            //             $action->description = "Not Applicable";
            //         }
            //         $actions[$i] = $action;
            //     }
            //     $row["action_plan"] = $actions;
                
            //     $actions = $row["requirements"];
            //     for ($i = 0; $i < count($actions); $i++) {
            //         $action = $actions[$i];
            //         if ($action->department == '') {
            //             $action->department = "Not Applicable";
            //         }
            //         if ($action->details == '') {
            //             $action->details = "Not Applicable";
            //         }
            //         if ($action->target_date == '') {
            //             $action->target_date = "Not Applicable";
            //         }
            //         $actions[$i] = $action;
            //     }
            //     $row["requirements"] = $actions;
                
            //     $output1 = array();
	           // $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='PROPOSED CHANGE'";
	           // $result1 = $conn->query($sql1);
	           // if ($result1->num_rows > 0) {
	           //     while ($row1 = $result1->fetch_assoc()) {
	           //         $output1[] = $row1;
	           //     }
	           // }
	           // $row["attachments"] = $output1;
	            
	           // $output1 = array();
            //     $sql1 = "SELECT * FROM ccp_departments WHERE cc_no='".$row["cc_no"]."'";
            //     $result1 = $conn->query($sql1);
            //     if ($result1->num_rows > 0) {
            //         while ($row1 = $result1->fetch_assoc()) {
            //             $output1[] = $row1;
            //         }
            //     }
            //     $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveDeptReviews") {
        
        $sql = "UPDATE ccpermanant SET status='CONCERNED DEPT REVIEW' WHERE id='".$_GET["id"]."'";

        // $sql = "UPDATE ccp_departments SET remark='".$input["remark"]."', reg_impact='".$input["reg_impact"]."', status='done', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            $sql = "SELECT id FROM ccp_departments WHERE cc_no='".$input["cc_no"]."' AND status='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows == 0) {
                $sql = "UPDATE ccpermanant SET status='CONCERNED DEPT REVIEW' WHERE id='".$_GET["id"]."'";
                $conn->query($sql);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "update_cust_review") {
          $input = $_POST;        
            $plant_id=$_GET["plant_id"];
           $data = json_decode($input["data"], true);
             $target_dir = "../../../upload/";
           if(isset($_FILES["photo"]["name"])) {
            	$target_file = $target_dir.$plant_id.$last_id."_".basename($_FILES["photo"]["name"]);
            	$photo = $plant_id.$last_id."_".basename($_FILES["photo"]["name"]);
        	    move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
        	   
           }
        
     echo   $sql = "UPDATE ccpermanant SET customer_status='done',cust_comment='".$data["cust_comment"]."',decison_chnge_control='".$data["decison_chnge_control"]."',cust_atachment='$photo' WHERE id='".$_GET["id"]."'";

        // $sql = "UPDATE ccp_departments SET remark='".$input["remark"]."', reg_impact='".$input["reg_impact"]."', status='done', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";

        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "saveDeptReviews99") {
          $input = $_POST;        
            $plant_id=$_GET["plant_id"];
           $data = json_decode($input["data"], true);
           
        if(isset($_FILES["photo"])) {
            $file_tmp =$_FILES['photo']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['photo']['name'])));
            $file_name = $emp_id."photo.".$file_ext;
            $photo = $file_name;
            move_uploaded_file($file_tmp,"../upload/employee/".$file_name);
        }
        
       echo $sql = "UPDATE ccpermanant SET status='CONCERNED DEPT REVIEW' WHERE id='".$_GET["id"]."'";

        // $sql = "UPDATE ccp_departments SET remark='".$input["remark"]."', reg_impact='".$input["reg_impact"]."', status='done', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            
          echo  $sql="INSERT INTO ccpermanant_regulatoy( ccpermanant_id, comment, change_reg_doss, change_pharma, change_update, change_cust_req, change_regulatory_req, dic_req, reg_file, plant_id)
             VALUES ('".$_GET["id"]."','".$data["comment"]."','".$data["change_reg_doss"]."','".$data["change_pharma"]."','".$data["change_update"]."','".$data["change_cust_req"]."',
            '".$data["change_regulatory_req"]."','".$data["dic_req"]."','$photo','".$_GET["plant_id"]."')";
       $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getPendingPostApproval") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE status ='CONCERNED DEPT REVIEW' or status='done' AND LOWER(change_status)='approved'";
        // $sql = "SELECT * FROM ccpermanant WHERE status ='CONCERNED DEPT REVIEW' AND LOWER(change_status)='approved'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["action_plan"] = json_decode($row["action_plan"]);
                $row["requirements"] = json_decode($row["requirements"]);
                
                $actions = $row["action_plan"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->status == 'No') {
                        $action->description = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["action_plan"] = $actions;
                
                $actions = $row["requirements"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->department == '') {
                        $action->department = "Not Applicable";
                    }
                    if ($action->details == '') {
                        $action->details = "Not Applicable";
                    }
                    if ($action->target_date == '') {
                        $action->target_date = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["requirements"] = $actions;
                
                $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='PROPOSED CHANGE'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccp_departments WHERE cc_no='".$row["cc_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $output2 = array();
	            $sql2 = "SELECT * FROM ccpermanant_regulatoy WHERE ccpermanant_id='".$row["id"]."'";
	            $result2 = $conn->query($sql2);
	            if ($result2->num_rows > 0) {
	                while ($row2 = $result2->fetch_assoc()) {
	                    $output2[] = $row2;
	                }
	            }
	            $row["regulatory"] = $output2;
	            $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "savePostApproval") {
        $sql = "UPDATE ccpermanant SET status='POST APPROVAL OF QA HEAD', postapproval_comment='".$input["postapproval_comment"]."',postapproval_status='".$input["postapproval_status"]."', postapproval_by='".$_GET["emp_id"]."', postapproval_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingPostApprovalbyCQA") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE status ='POST APPROVAL OF QA HEAD' AND LOWER(change_status)='approved'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            //     $row["action_plan"] = json_decode($row["action_plan"]);
            //     $row["requirements"] = json_decode($row["requirements"]);
                
            //     $actions = $row["action_plan"];
            //     for ($i = 0; $i < count($actions); $i++) {
            //         $action = $actions[$i];
            //         if ($action->status == 'No') {
            //             $action->description = "Not Applicable";
            //         }
            //         $actions[$i] = $action;
            //     }
            //     $row["action_plan"] = $actions;
                
            //     $actions = $row["requirements"];
            //     for ($i = 0; $i < count($actions); $i++) {
            //         $action = $actions[$i];
            //         if ($action->department == '') {
            //             $action->department = "Not Applicable";
            //         }
            //         if ($action->details == '') {
            //             $action->details = "Not Applicable";
            //         }
            //         if ($action->target_date == '') {
            //             $action->target_date = "Not Applicable";
            //         }
            //         $actions[$i] = $action;
            //     }
            //     $row["requirements"] = $actions;
                
            //     $output1 = array();
	           // $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='PROPOSED CHANGE'";
	           // $result1 = $conn->query($sql1);
	           // if ($result1->num_rows > 0) {
	           //     while ($row1 = $result1->fetch_assoc()) {
	           //         $output1[] = $row1;
	           //     }
	           // }
	           // $row["attachments"] = $output1;
	            
	           // $output1 = array();
	           // $sql1 = "SELECT * FROM ccp_departments WHERE cc_no='".$row["cc_no"]."'";
	           // $result1 = $conn->query($sql1);
	           // if ($result1->num_rows > 0) {
	           //     while ($row1 = $result1->fetch_assoc()) {
	           //         $output1[] = $row1;
	           //     }
	           // }
	            $output2 = array();
	            $sql2 = "SELECT * FROM ccpermanant_regulatoy WHERE ccpermanant_id='".$row["id"]."'";
	            $result2 = $conn->query($sql2);
	            if ($result2->num_rows > 0) {
	                while ($row2 = $result2->fetch_assoc()) {
	                    $output2[] = $row2;
	                }
	            }
	            $row["regulatory"] = $output2;
	           // $row["departments"] = $output1;
                $output[] = $row;
            }  
             

        }
        echo json_encode($output);
    } 
     else if ($_GET["type"] == "cust_review") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE status ='POST APPROVAL OF CQA HEAD'  and customer_status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            //     $row["action_plan"] = json_decode($row["action_plan"]);
            //     $row["requirements"] = json_decode($row["requirements"]);
                
            //     $actions = $row["action_plan"];
            //     for ($i = 0; $i < count($actions); $i++) {
            //         $action = $actions[$i];
            //         if ($action->status == 'No') {
            //             $action->description = "Not Applicable";
            //         }
            //         $actions[$i] = $action;
            //     }
            //     $row["action_plan"] = $actions;
                
            //     $actions = $row["requirements"];
            //     for ($i = 0; $i < count($actions); $i++) {
            //         $action = $actions[$i];
            //         if ($action->department == '') {
            //             $action->department = "Not Applicable";
            //         }
            //         if ($action->details == '') {
            //             $action->details = "Not Applicable";
            //         }
            //         if ($action->target_date == '') {
            //             $action->target_date = "Not Applicable";
            //         }
            //         $actions[$i] = $action;
            //     }
            //     $row["requirements"] = $actions;
                
            //     $output1 = array();
	           // $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='PROPOSED CHANGE'";
	           // $result1 = $conn->query($sql1);
	           // if ($result1->num_rows > 0) {
	           //     while ($row1 = $result1->fetch_assoc()) {
	           //         $output1[] = $row1;
	           //     }
	           // }
	           // $row["attachments"] = $output1;
	            
	           // $output1 = array();
	           // $sql1 = "SELECT * FROM ccp_departments WHERE cc_no='".$row["cc_no"]."'";
	           // $result1 = $conn->query($sql1);
	           // if ($result1->num_rows > 0) {
	           //     while ($row1 = $result1->fetch_assoc()) {
	           //         $output1[] = $row1;
	           //     }
	           // }
	            $output2 = array();
	            $sql2 = "SELECT * FROM ccpermanant_regulatoy WHERE ccpermanant_id='".$row["id"]."'";
	            $result2 = $conn->query($sql2);
	            if ($result2->num_rows > 0) {
	                while ($row2 = $result2->fetch_assoc()) {
	                    $output2[] = $row2;
	                }
	            }
	            $row["regulatory"] = $output2;
	           // $row["departments"] = $output1;
                $output[] = $row;
            }  
             

        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "savePostApprovalChecking") {
        if($input["customer_approval"]=="Customer Approval"){
                 echo   $sql = "UPDATE ccpermanant SET status='POST APPROVAL OF CQA HEAD', postapproval1_comment='".$input["postapproval_comment"]."', postapproval1_by='".$_GET["emp_id"]."', postapproval1_date='".$entry_date."',final_qa_action='".$input["Action_plan"]."',customer_approval='".$input["customer_approval"]."',customer_qa_approval='".$input["customer_qa_approval"]."',customer_status='pending' WHERE id='".$_GET["id"]."'";
        }else
        $sql = "UPDATE ccpermanant SET status='POST APPROVAL OF CQA HEAD', postapproval1_comment='".$input["postapproval_comment"]."', postapproval1_by='".$_GET["emp_id"]."', postapproval1_date='".$entry_date."',final_qa_action='".$input["Action_plan"]."',customer_approval='".$input["customer_approval"]."',customer_qa_approval='".$input["customer_qa_approval"]."',customer_status='done' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            if($input["Action_plan"]==Yes){
                        $json_obj = json_encode($input["requirement"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
  $sql = "INSERT INTO ccpermanant_final_qa_change_action( ccpermanant_id,plant_id, action, department_name, employee, target_date, status) VALUES ('".$_GET["id"]."','".$_GET["plant_id"]."','".$values["attr1ibute"]."','".$values["department_name"]."','".$values["Employee"]."','".$values["target_date"]."','pending')";
        if ($conn->query($sql)) {
           
                
        
              $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
                
            }
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingExtension") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE initiate_dept IN ('".$_GET["department"]."', 'Master') AND status='POST APPROVAL OF CQA HEAD' AND UPPER(change_status)='APPROVED' AND UPPER(postapproval_status)='APPROVED'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            $row["action_plan"] = json_decode($row["action_plan"]);
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM cc_departments WHERE cc_no='".$row["cc_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveExtension") {
        $status = "";
        if (strtoupper($input["extension_status"]) == "REQUIRED") {
            $status = "EXTENSION OF CC";
        } else {
            $status = "ADDITIONAL EVALUATION";
        }
        $sql = "UPDATE cctemporary SET status='$status',extension_status='".$input["extension_status"]."', extension_plan='".$input["extension_plan"]."', extension_for='".$input["extension_for"]."', extension_justification='".$input["extension_justification"]."', extension_by='".$_GET["emp_id"]."', extension_date='$entry_date' WHERE cc_no='".$input["cc_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingExtensionChecking") {
        $output = array();
        $sql = "SELECT * FROM cctemporary WHERE UPPER(change_status)='APPROVED' AND UPPER(postapproval_status)='APPROVED' AND UPPER(extension_status)='REQUIRED' AND status='EXTENSION OF CC'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
	            $sql1 = "SELECT * FROM cctemporary_files WHERE cc_no='".$row["cc_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            $row["action_plan"] = json_decode($row["action_plan"]);
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM cc_departments WHERE cc_no='".$row["cc_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "checkExtension") {
        $sql = "UPDATE cctemporary SET status='ADDITIONAL EVALUATION', extension_check_by='".$_GET["emp_id"]."', extension_check_date='$entry_date' WHERE cc_no='".$_GET["cc_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingAdditionalEvaluation") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE status='POST APPROVAL OF CQA HEAD' and customer_status='done'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["action_plan"] = json_decode($row["action_plan"]);
                $row["requirements"] = json_decode($row["requirements"]);
                
                $actions = $row["action_plan"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->status == 'No') {
                        $action->description = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["action_plan"] = $actions;
                
                $actions = $row["requirements"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->department == '') {
                        $action->department = "Not Applicable";
                    }
                    if ($action->details == '') {
                        $action->details = "Not Applicable";
                    }
                    if ($action->target_date == '') {
                        $action->target_date = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["requirements"] = $actions;
                
                $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='PROPOSED CHANGE'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='POST IMPLEMENTATION REVIEW'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments1"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='Additional Evaluation Plan'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments2"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccp_departments WHERE cc_no='".$row["cc_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveAdditional") {
        $sql = "UPDATE ccpermanant SET status='POST IMPLEMENTATION', implementation_date='".$input["implementation_date"]."', implementation_review='".$input["implementation_review"]."', action_plan='".json_encode($input["action_plan"])."', evaluation_by='".$_GET["emp_id"]."', evaluation_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingPostImplementation") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE status='POST IMPLEMENTATION'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["action_plan"] = json_decode($row["action_plan"]);
                $row["requirements"] = json_decode($row["requirements"]);
                
                $actions = $row["action_plan"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->status == 'No') {
                        $action->description = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["action_plan"] = $actions;
                
                $actions = $row["requirements"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->department == '') {
                        $action->department = "Not Applicable";
                    }
                    if ($action->details == '') {
                        $action->details = "Not Applicable";
                    }
                    if ($action->target_date == '') {
                        $action->target_date = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["requirements"] = $actions;
                
                $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='PROPOSED CHANGE'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='POST IMPLEMENTATION REVIEW'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments1"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='Additional Evaluation Plan'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments2"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccp_departments WHERE cc_no='".$row["cc_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "savePostImplementation") {
        $sql = "UPDATE ccpermanant SET status='COA RECOMMENDATION', cqa_comment='".$input["cqa_comment"]."', implementation_comment='".$input["implementation_comment"]."', issatisfactory='".$input["issatisfactory"]."', implement_by='".$_GET["emp_id"]."', implement_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingClosing") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE status='COA RECOMMENDATION'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["action_plan"] = json_decode($row["action_plan"]);
                $row["requirements"] = json_decode($row["requirements"]);
                
                $actions = $row["action_plan"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->status == 'No') {
                        $action->description = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["action_plan"] = $actions;
                
                $actions = $row["requirements"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->department == '') {
                        $action->department = "Not Applicable";
                    }
                    if ($action->details == '') {
                        $action->details = "Not Applicable";
                    }
                    if ($action->target_date == '') {
                        $action->target_date = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["requirements"] = $actions;
                
                $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='PROPOSED CHANGE'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='POST IMPLEMENTATION REVIEW'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments1"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='Additional Evaluation Plan'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments2"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccp_departments WHERE cc_no='".$row["cc_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "closeCC") {
        $sql = "UPDATE ccpermanant SET status='CLOSED APPROVAL PENDING', close_comment='".$input["close_comment"]."', close_by='".$_GET["emp_id"]."', close_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingClosingApproval") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE status='CLOSED APPROVAL PENDING'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["action_plan"] = json_decode($row["action_plan"]);
                $row["requirements"] = json_decode($row["requirements"]);
                
                $actions = $row["action_plan"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->status == 'No') {
                        $action->description = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["action_plan"] = $actions;
                
                $actions = $row["requirements"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->department == '') {
                        $action->department = "Not Applicable";
                    }
                    if ($action->details == '') {
                        $action->details = "Not Applicable";
                    }
                    if ($action->target_date == '') {
                        $action->target_date = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["requirements"] = $actions;
                
                $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='PROPOSED CHANGE'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='POST IMPLEMENTATION REVIEW'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments1"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='Additional Evaluation Plan'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments2"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccp_departments WHERE cc_no='".$row["cc_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "closeCCApproval") {
        $sql = "UPDATE ccpermanant SET status='CLOSED', close1_comment='".$input["close_comment"]."', close1_by='".$_GET["emp_id"]."', close1_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getCCLog") {
        $output = array();
        $sql = "SELECT * FROM ccpermanant WHERE initiate_dept IN ('".$_GET["department"]."', 'Quality Assurance', 'Master')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["action_plan"] = json_decode($row["action_plan"]);
                $row["requirements"] = json_decode($row["requirements"]);
                
                $actions = $row["action_plan"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->status == 'No') {
                        $action->description = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["action_plan"] = $actions;
                
                $actions = $row["requirements"];
                for ($i = 0; $i < count($actions); $i++) {
                    $action = $actions[$i];
                    if ($action->department == '') {
                        $action->department = "Not Applicable";
                    }
                    if ($action->details == '') {
                        $action->details = "Not Applicable";
                    }
                    if ($action->target_date == '') {
                        $action->target_date = "Not Applicable";
                    }
                    $actions[$i] = $action;
                }
                $row["requirements"] = $actions;
                
                $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='PROPOSED CHANGE'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='POST IMPLEMENTATION REVIEW'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments1"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type='Additional Evaluation Plan'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments2"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM ccp_departments WHERE cc_no='".$row["cc_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "downloadCChangeLog") {
          $output = array();
        $sql = "SELECT * FROM cctemporary WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
	           // $row["action_plan"] = json_decode($row["action_plan"]);
	            
	            
                $output[] = $row;
        class MYPDF extends TCPDF {

            //Page header
            public function Header() {
           // if($_GET['user_no'] == 'GMP007'){
                 
                
                $table='

                 <style>td { border:solid 1px BCBBBA;}</style>
               <table>
                    <tr>
                         <td style="width:20%;">';
                           $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/api/gmptotal/upload/user/bajaj1.png'),20,7,17);
                        $table.='
                        </td>
                        <td style="width:80%;text-align:center;font-weight:bold;">
                            <span style="font-family:times;font-size:26px;">BAJAJ HEALTHCARE LTD.[UNIT-II]</span><br>
                            <span style="font-size:9px;">Location:Block No.588,Savli,karachia Road,At & Po:Gothada-391776 Tal.Savli<br>Dist:Vadodara,Gujarat,India.</span>
                        </td>
                    </tr>
                </table>
                <div></div>';
                
                $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
                $this->SetY(30); $this->Cell(($this->w -> $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                $this->SetY(37); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0,'', 0, false, 'L', 0, '', 0, false, 'M', 'M');
                $this->SetY(31); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                //$this->SetY(31); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0, 'Format No:QA005/F/01-08 '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'L', 0, '', 0, false, 'T', 'M');
            }
            public function Footer(){
               
            }
        }

            // create new PDF document
            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // set default header data
            $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
            
            // set header and footer fonts
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '30', PDF_FONT_SIZE_MAIN));
           //$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '30', PDF_FONT_SIZE_DATA));
            
            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            
            // set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, 40, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
           // $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            
            // set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, 60);
            
            // set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            
            // set some language-dependent strings (optional)
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf->setLanguageArray($l);
            }
            
            // ---------------------------------------------------------
            
            // set font
            $pdf->SetFont('times', '', 12);
        
            // add a page 
            $pdf->AddPage();
            
        $html.="";
        $html.='
                <div></div>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR TEMPORARY CHANGE</b></u></td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Initiating Department</b></td>
                    <td style="width:25%;">'.$row['initiate_dept'].'</td>
                    <td style="width:25%;"><b>CC No</b></td>
                    <td style="width:25%;">'.$row['cc_no'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Date of Initiating</b></td>
                    <td style="width:25%;">'.$row['initiate_date'].'</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>A. CHANGE APPLICABLE TO: </b></td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>'.$row['document_type'].' </b><br>'.$row['cc_related'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>A.1 Title of change:</b>'.$row['change_title'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>A.2 Batch no. / Equipment ID/ Instrument ID / Document no./ No. of days:</b><br>'.$row['document_no'].'</td>
                </tr>
                <tr>
                    <td style="width:33%;"><b>A.3 Current / Existing Procedure:</b><br>'.$row['existing_procedure'].'</td>
                    <td style="width:34%;"><b>A.4 Proposed Change* (s) (All changes being proposed should be listed here or attached herein)</b><br>'.$row['proposed_change'].'</td>
                    <td style="width:33%;"><b>A.5 Reason / Justification of each Proposed change*(s):(May be documented and attached herein)</b><br>'.$row['change_reason'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>A.6 Affected LMR/BMR version no./ Other document ( Specify ) ( Trial / Regular):</b>'.$row['affected_for'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>A.7 Attachment with proposed change* (s) e.g. Supporting document:</b></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Attachment No.</b></td>
                    <td style="width:70%;"><b>Title of Attachment</b></td>
                </tr>
                <tr>
                    <td style="width:30%;"></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>* Attach separate sheet is required</b><br></td>
                </tr>
                </table>
                <div></div> <div></div>
                <div></div>
                <div></div>
                
                ';
        $html.='<table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR TEMPORARY CHANGE</b></u></td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Initiating Department:</b></td>
                    <td style="width:25%;">'.$row['initiate_dept'].'</td>
                    <td style="width:25%;"><b>CC No.:</b></td>
                    <td style="width:25%;">'.$row['cc_no'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>B. IMPACT EVALUTION AND ACTION PLAN OF PROPOSED CHANGE:</b></td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>B.1 Attribute to be evaluated:(one or more of the following)</b></td>
                </tr>
                <tr>
                    <td style="width:5%;"><b>Sr.No.</b></td>
                    <td style="width:20%;"><b>Attribute</b></td>
                    <td style="width:7%;"><b>Yes/No</b></td>
                    <td style="width:48%;"><b>Work description</b></td>
                    <td style="width:20%;"><b>Responsibility for action</b></td>
                </tr>';
                $row["action_plan"] = json_decode($row["action_plan"]);
                $action_plans= $row["action_plan"];
                $j=1;
                for($k=0;$k<count($action_plans);$k++){
                    $action=$action_plans[$k];
                
        $html.='<tr>
                    <td style="width:5%;">'.$j++.'</td>
                    <td style="width:20%;">'.$action->attribute.'</td>
                    <td style="width:7%;">'.$action->status.'</td>
                    <td style="width:48%;">'.$action->description.'</td>
                    <td style="width:20%;">'.$action->responsibility.'</td>
                </tr>';
                }
        $html.='</table><br>
                <div></div>
                <div></div>
                <div></div>
                 ';
        $html.='<table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR TEMPORARY CHANGE</b></u></td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Initiating Department</b></td>
                    <td style="width:25%;">'.$row['initiate_dept'].'</td>
                    <td style="width:25%;"><b>CC No</b></td>
                    <td style="width:25%;">'.$row['cc_no'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Change Control Initiated by:</b><br>
                        <div></div>
                        <table>
                            <tr>
                                <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['initiate_by'].'</td>
                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row['initiate_date'].'</td>
                            </tr>
                        </table>
                    </td>                
                </tr>
                <tr>
                    <td style="width:100%;"><b>Remarks:</b>'.$row['dept_remark'].'<br>
                        <b>Initiating Department Head:</b><br>
                        <div></div>
                        <table>
                            <tr>
                                <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['dept_check_by'].'</td>
                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row['dept_check_date'].'</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>PRE-APPROVAL OF CHANGE CONTROL:</b><br>
                        <b>Tick the Classification of the proposed change:</b> &nbsp;&nbsp;'.$row['change_proposed'].'<br>
                        <b>Proposed Changes are:</b> &nbsp;&nbsp;'.$row['change_status'].'<br>
                        <b>Reason for Rejection / Cancellation:</b> &nbsp;&nbsp;'.$row['reject_reason'].'<br>
                        <b>QA Executive/ Designee:</b><br>
                        <table>
                            <tr>
                                 <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['preapproval_by'].'</td>
                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row['preapproval_date'].'</td>
                            </tr>
                        </table>
                    </td>
                                            
                </tr>
                <tr>
                    <td style="width:100%;"><b>QA Comment:</b>'.$row['preapproval_remark'].'<br>
                        <div></div>
                        <div></div>
                        <b>QA Head/ Designee:</b>
                        <table>
                            <tr>
                                <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['preapproval_check_by'].'</td>
                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row['preapproval_check_date'].'</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Head QA will fonvard the Change Control to following concerned Departments (Wherever applicable). Put ( ) marks to the applicable department.</b><div></div></td>
                </tr>
                <tr>
                    <td style="width:100%;">';
                
                    $output2 = array();
    	            $sql2 = "SELECT * FROM cc_departments WHERE cc_no='".$row["cc_no"]."'";
    	            $result2 = $conn->query($sql2);
    	            if ($result2->num_rows > 0) {
    	                while ($row2 = $result2->fetch_assoc()) {
    	                    $output2[] = $row2;
    	                
                        $html.=''.$row2['department'].',&nbsp;';
    	                }
    	            }
        $html.='    </td>
                </tr>
                </table>
                <div></div>
                <div></div>
                <div></div>';
                
        $html.='<table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR TEMPORARY CHANGE</b></u></td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Initiating Department</b></td>
                    <td style="width:25%;">'.$row['initiate_dept'].'</td>
                    <td style="width:25%;"><b>CC No</b></td>
                    <td style="width:25%;">'.$row['cc_no'].'</td>
                </tr>';
                
                $sql3 = "SELECT * FROM cc_departments WHERE department='Production' AND cc_no='".$row["cc_no"]."'";
	            $result3 = $conn->query($sql3);
	            if ($result3->num_rows > 0) {
	                while ($row3 = $result3->fetch_assoc()) {
                        $html.='<tr>
                            <td style="width:100%;"><b>Evaluation by production Department:</b><br>
                                <table>
                                    <tr>
                                        <td style="width:33.33%; border:none;"><b>Name</b>:'.$row3['entry_by'].'</td>
                                        <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                        <td style="width:33.33%; border:none;"><b>Date:</b>'.$row3['entry_date'].'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>';
	                }
	            } 
	            else{
	                $html.='<tr>
                        <td style="width:100%;"><b>Evaluation by production Department:</b><br>
                            <table>
                                <tr>
                                    <td style="width:33.33%; border:none;"><b>Name</b>:</td>
                                    <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                    <td style="width:33.33%; border:none;"><b>Date:</b></td>
                                </tr>
                            </table>
                        </td>
                    </tr>';
	            }
                
                $sql4 = "SELECT * FROM cc_departments WHERE department='Quality Control' AND cc_no='".$row["cc_no"]."'";
	            $result4 = $conn->query($sql4);
	            if ($result4->num_rows > 0) {
	                while ($row4 = $result4->fetch_assoc()) {
                        $html.='<tr>
                                    <td style="width:100%;"><b>Evaluation by Quality Control Department:</b><br>
                                        <table>
                                            <tr>
                                                <td style="width:33.33%; border:none;"><b>Name</b>:'.$row4['entry_by'].'</td>
                                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row4['entry_date'].'</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>';
                	}
	           }
	           else{
	                $html.='<tr>
                                <td style="width:100%;"><b>Evaluation by Quality Control Department:</b><br>
                                    <table>
                                        <tr>
                                            <td style="width:33.33%; border:none;"><b>Name</b>:/td>
                                            <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                            <td style="width:33.33%; border:none;"><b>Date:</b></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>';
	            }
	            $sql5 = "SELECT * FROM cc_departments WHERE department='Engineering' AND cc_no='".$row["cc_no"]."'";
	            $result5 = $conn->query($sql5);
	            if ($result5->num_rows > 0) {
	                while ($row5 = $result5->fetch_assoc()) {
                        $html.='<tr>
                                    <td style="width:100%;"><b>Evaluation by Engineering/Project:</b><br>
                                        <table>
                                            <tr>
                                                <td style="width:33.33%; border:none;"><b>Name</b>:'.$row5['entry_by'].'</td>
                                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row5['entry_date'].'</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>';
	                }
	            }
	            else{
	                 $html.='<tr>
                                <td style="width:100%;"><b>Evaluation by Engineering/Project:</b><br>
                                    <table>
                                        <tr>
                                            <td style="width:33.33%; border:none;"><b>Name</b>:</td>
                                            <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                            <td style="width:33.33%; border:none;"><b>Date:</b></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>';
	            }
                $sql6 = "SELECT * FROM cc_departments WHERE department='WareHouse' AND cc_no='".$row["cc_no"]."'";
	            $result6 = $conn->query($sql6);
	            if ($result6->num_rows > 0) {
	                while ($row6 = $result6->fetch_assoc()) {
                        $html.='<tr>
                                    <td style="width:100%;"><b>Evaluation by Warehouse:</b><br>  
                                       <table>
                                            <tr>
                                                <td style="width:33.33%; border:none;"><b>Name</b>:'.$row6['entry_by'].'</td>
                                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row6['entry_date'].'</td>
                                            </tr>
                                       </table>
                                    </td>
                                </tr>';
	                }
	            }
	            else{
	                 $html.='<tr>
                                <td style="width:100%;"><b>Evaluation by Warehouse:</b><br>  
                                    <table>
                                        <tr>
                                            <td style="width:33.33%; border:none;"><b>Name</b>:</td>
                                            <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                            <td style="width:33.33%; border:none;"><b>Date:</b></td>
                                        </tr>
                                   </table>
                                </td>
                            </tr>';
	            }
	            
	            $sql7 = "SELECT * FROM cc_departments WHERE department='EHS' AND cc_no='".$row["cc_no"]."'";
	            $result7 = $conn->query($sql7);
	            if ($result7->num_rows > 0) {
	                while ($row7 = $result7->fetch_assoc()) {
                        $html.='<tr>
                                    <td style="width:100%;"><b>Evaluation by EHS:</b><br>
                                        <table>
                                            <tr>
                                                <td style="width:33.33%; border:none;"><b>Name</b>:'.$row7['entry_by'].'</td>
                                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row7['entry_date'].'</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>';
	                }
	            }
	            else{
	                 $html.='<tr>
                                <td style="width:100%;"><b>Evaluation by EHS:</b><br>
                                    <table>
                                        <tr>
                                            <td style="width:33.33%; border:none;"><b>Name</b>:</td>
                                            <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                            <td style="width:33.33%; border:none;"><b>Date:</b></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>';
	            }
	            $sql8 = "SELECT * FROM cc_departments WHERE department='HR' AND cc_no='".$row["cc_no"]."'";
	            $result8 = $conn->query($sql8);
	            if ($result8->num_rows > 0) {
	                while ($row8 = $result8->fetch_assoc()) {
                        $html.='<tr>
                            <td style="width:100%;"><b>Evaluat ion by HR:</b><br>
                                <table>
                                    <tr>
                                        <td style="width:33.33%; border:none;"><b>Name</b>:'.$row8['entry_by'].'</td>
                                        <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                        <td style="width:33.33%; border:none;"><b>Date:</b>'.$row8['entry_date'].'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>';
	                }
	            }
	            else{
	                $html.='<tr>
                            <td style="width:100%;"><b>Evaluat ion by HR:</b><br>
                                <table>
                                    <tr>
                                        <td style="width:33.33%; border:none;"><b>Name</b>:</td>
                                        <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                        <td style="width:33.33%; border:none;"><b>Date:</b></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>';
	            }
	            
	            $sql9 = "SELECT * FROM cc_departments WHERE department='Regulatory Affairs' AND cc_no='".$row["cc_no"]."'";
	            $result9 = $conn->query($sql9);
	            if ($result9->num_rows > 0) {
	                while ($row9 = $result9->fetch_assoc()) {
                        $html.='<tr>
                            <td style="width:100%;"><b>Evaluation by Regulatory Affairs: </b><br>
                                <b>Impact on regulatory filling:</b><br>
                                <table>
                                    <tr>
                                        <td style="width:33.33%; border:none;"><b>Name</b>:'.$row9['entry_by'].'</td>
                                        <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                        <td style="width:33.33%; border:none;"><b>Date:</b>'.$row9['entry_date'].'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>';
	                }
	            }
	            else{
	                $html.='<tr>
                            <td style="width:100%;"><b>Evaluation by Regulatory Affairs: </b><br>
                                <b>Impact on regulatory filling:</b><br>
                                <table>
                                    <tr>
                                        <td style="width:33.33%; border:none;"><b>Name</b>:</td>
                                        <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                        <td style="width:33.33%; border:none;"><b>Date:</b></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>';
	            }
	            
                $sql10 = "SELECT * FROM cc_departments WHERE department='other' AND cc_no='".$row["cc_no"]."'";
	            $result10 = $conn->query($sql10);
	            if ($result10->num_rows > 0) {
	                while ($row10 = $result10->fetch_assoc()) {
                        $html.='<tr>
                                    <td style="width:100%;"><b>Evaluation by any other department:</b><br>
                                        <table>
                                            <tr>
                                                <td style="width:33.33%; border:none;"><b>Name</b>:'.$row10['entry_by'].'</td>
                                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row10['entry_date'].'</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>';
	                }
	            }
	            else{
	                $html.='<tr>
                                <td style="width:100%;"><b>Evaluation by any other department:</b><br>
                                    <table>
                                        <tr>
                                            <td style="width:33.33%; border:none;"><b>Name</b>:</td>
                                            <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                            <td style="width:33.33%; border:none;"><b>Date:</b></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>';
	            }
	            $sql11 = "SELECT * FROM cc_departments WHERE department='IT' AND cc_no='".$row["cc_no"]."'";
	            $result11 = $conn->query($sql11);
	            if ($result11->num_rows > 0) {
	                while ($row11 = $result11->fetch_assoc()) {
                        $html.='<tr>
                                    <td style="width:100%;"><b>Evaluation by IT department:</b><br>
                                        <table>
                                            <tr>
                                                <td style="width:33.33%; border:none;"><b>Name</b>:'.$row11['entry_by'].'</td>
                                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row11['entry_date'].'</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>';
	                }
	            }
	            else{
	                 $html.='<tr>
                                <td style="width:100%;"><b>Evaluation by IT department:</b><br>
                                    <table>
                                        <tr>
                                            <td style="width:33.33%; border:none;"><b>Name</b>:</td>
                                            <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                            <td style="width:33.33%; border:none;"><b>Date:</b></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>';
	            }
	            $sql12 = "SELECT * FROM cc_departments WHERE department='CQA' AND cc_no='".$row["cc_no"]."'";
	            $result12 = $conn->query($sql12);
	            if ($result12->num_rows > 0) {
	                while ($row12 = $result12->fetch_assoc()) {
                        $html.='<tr>
                                    <td style="width:100%;"><b>Evaluation by CQA department:</b><br>
                                        <table>
                                            <tr>
                                                <td style="width:33.33%; border:none;"><b>Name</b>:'.$row12['entry_by'].'</td>
                                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row12['entry_date'].'</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>';
	                }
	            }
	            else{
	                $html.='<tr>
                                <td style="width:100%;"><b>Evaluation by CQA department:</b><br>
                                    <table>
                                        <tr>
                                            <td style="width:33.33%; border:none;"><b>Name</b>:</td>
                                            <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                            <td style="width:33.33%; border:none;"><b>Date:</b></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>';
	            }
	            $sql13 = "SELECT * FROM cc_departments WHERE department='other' AND cc_no='".$row["cc_no"]."'";
	            $result13 = $conn->query($sql13);
	            if ($result13->num_rows > 0) {
	                while ($row13 = $result13->fetch_assoc()) {
                        $html.='<tr>
                                    <td style="width:100%;"><b>Evaluation by any other department:</b><br>
                                        <div></div>
                                        <table>
                                            <tr>
                                                <td style="width:33.33%; border:none;"><b>Name</b>:'.$row13['entry_by'].'</td>
                                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row13['entry_date'].'</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>';
	                }
	            }
	            else{
	                $html.='<tr>
                                <td style="width:100%;"><b>Evaluation by any other department:</b><br>
                                    <div></div>
                                    <table>
                                        <tr>
                                            <td style="width:33.33%; border:none;"><b>Name</b>:</td>
                                            <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                            <td style="width:33.33%; border:none;"><b>Date:</b></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>';
	            }
        $html.='</table>
                <div></div>
                <div></div>
                <div></div>
                ';
                
        $html.='<table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR TEMPORARY CHANGE</b></u></td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Initiating Department</b></td>
                    <td style="width:25%;">'.$row['initiate_dept'].'</td>
                    <td style="width:25%;"><b>CC No</b></td>
                    <td style="width:25%;">'.$row['cc_no'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>POST-APPROVAL OF CHANGE CONTROL:</b><br><b>QA Head/Designee comment:</b><br> '.$row['postapproval_comment'].'<br>
                        <b>Proposed Changes are:&nbsp;</b> '.$row['postapproval_status'].'<br>
                        <b>No. of batches / No. of days:</b> '.$row['batches_no'].'<br><br>
                        <table>
                            <tr>
                                <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['postapproval_by'].'</td>
                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row['postapproval_date'].'</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>C. Extension (If required):</b>'.$row['extension_status'].'</td>
                </tr>
                <tr>
                    <td style="width:5%;"><b>Sr.No</b></td>
                    <td style="width:30%;"><b>No. of days/Batches initially planned</b></td>
                    <td style="width:30%;"><b>No. of days/Batches required for extension</b></td>
                    <td style="width:35%;"><b>Justification for Extension</b></td>
                </tr>
                <tr>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:30%;"><b>'.$row['extension_plan'].'</b></td>
                    <td style="width:30%;"><b>'.$row['extension_for'].'</b></td>
                    <td style="width:35%;"><b>'.$row['extension_justification'].'</b></td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Extension Initiated by:</b><br>
                        <div></div>
                        <table>
                            <tr>
                                <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['extension_by'].'</td>
                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                <td style="width:33.33%; border:none;"><b>Date:</b>'.$row['extension_date'].'</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Approved by:</b><br>
                        <b>(QA Head Sign./Date)</b><br>
                        <div></div>
                        <table>
                         <tr>
                            <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['extension_check_by'].'</td>
                            <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                            <td style="width:33.33%; border:none;"><b>Date:</b>'.$row['extension_check_date'].'</td>
                        </tr>
                        </table>
                    </td>
                                        
                </tr>
                <tr>
                    <td style="width:100%;"><b>Additional Impact Evaluation or Action Plan Requirements:</b><br>
                    '.$row['additional_evaluation'].'<div></div></td>
                </tr>
                </table>
                <div></div>
                <div></div><div></div>
                <div></div>
                ';
                
        $html.='<table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR TEMPORARY CHANGE</b></u></td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Initiating Department</b></td>
                    <td style="width:25%;">'.$row['initiate_dept'].'</td>
                    <td style="width:25%;"><b>CC No</b></td>
                    <td style="width:25%;">'.$row['cc_no'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <b>D.1 E VALUATION  OF CHANGE (Post Implementation):</b><br>
                        <div></div>
                        <b>All Change Control Requirements have been met</b><br>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Comments:</b>'.$row['implementation_comment'].'<br></td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Temporary change to be made permanent:</b>'.$row['permanent'].'<br></td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <b>D.2 CHANGE CONTROL POST RECOMME NDATIONS: </b><br>
                        <b>CQA internal and external recommendations:</b>'.$row['close_comment'].'<br>
                        <div></div>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>E. CLOSURE OF CHANGE CO NTROL :</b><br>
                        <b>Comments by Quality Assura nce Head (or Designee):</b><br>
                        <table>
                         <tr>
                            <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['close_by'].'</td>
                            <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                            <td style="width:33.33%; border:none;"><b>Date:</b>'.$row['close_date'].'</td>
                        </tr>
                        </table>
                    </td>                  
                </tr>
                <tr>
                    <td style="width:100%;"><b>F. 1 List of Attachments</b></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Attachment No.</b></td>
                    <td style="width:70%;"><b>Title of Attachment</b></td>
                </tr>';
                
                $output1 = array();
	            $sql1 = "SELECT * FROM cctemporary_files WHERE cc_no='".$row["cc_no"]."'";
	            $result1 = $conn->query($sql1);
	            $i=1;
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                    $file=$row1['file'];
                        $html.='<tr>
                            <td style="width:30%;">'.$i++.'</td>
                            <td style="width:30%;">'.$row1['particular'].'</td>
                            <td style="width:40%;">
                                 <a href="https://'.$_SERVER['SERVER_NAME'].'/api/gmptotal/upload/cctemporary/'.$row1['file'].'">'.$file.'</a>
                            </td>
                        </tr>';
                    }
                }
        $html.='<tr>
                    <td style="width:100%;"><b>* Attach separate sheet is required</b></td>
                </tr>
                </table>
                <div></div>
                ';
	    $html.='<table>
	            ';
	    
	           // $sql14 = "SELECT * FROM cctemporary_files WHERE cc_no='".$row["cc_no"]."'";
	           // $result14 = $conn->query($sql14);
	           // $i=1;
	           // if ($result14->num_rows > 0) {
	           //     while ($row14 = $result14->fetch_assoc()) {
	           //         $filename=$row14['file'];
	                    
	                  
        	           // $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
                    //     $pdf = new FPDI();
                    //     $pdf->AddPage();
                    //     $pdf->AddFont('courier');
                    //     $pdf->Write(10, 'page 1 created by TCPDF');
                    //     $pages = $pdf->setSourceFile('https://'.$_SERVER['SERVER_NAME'].'/api/gmptotal/upload/cctemporary/'.$filename.'');
                    //     for($i=0; $i<$pages; $i++)
                    //     {
                    //          $pdf->AddPage();
                    //          $tplIdx = $pdf->importPage($i+1);
                    //          $pdf->useTemplate($tplIdx, 10, 10, 200);
                    //     }
                    //     $pdf->AddPage();
                    //     $pdf->Write(10, 'page 2 created by TCPDF');
                    //     $pdf->Output($filename, 'I');
                    
                    
	                   //$txt = ''.$row14['file'].'';
                    //     $pdf->SetFont('helvetica', '', 10);
                    //     $pdf->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);
                    //   // attach an external file
                    //     
                         //$pdf->Annotation(50, 50, 5, 5, 'pdf file', array('Subtype'=>'FileAttachment', 'Name' => 'PushPin', 'FS' => '../upload/cctemporary/'.$row14['file'].''));
	                     
        	  
	           //     }
	           // }
	    $html.='</table>';     
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ChangecontrolTemporary .pdf', 'I');
    }
    else if ($_GET["type"] == "downloadCCRecord") {
        $_GET['filename'] = 'CC Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    
                    <td style="width: 10%;">CC No.	</td>
                    <td style="width:10%;">Document No.	</td>
                     <td style="width:20%;">Document Type	</td>
                      <td style="width:10%;">CC Related	</td>
                       <td style="width:20%;">Initiate Dept	</td>
                        <td style="width:10%;">Initiate By	</td>
                         <td style="width:20%;">Initiate Date		</td>
                </tr>
            </thead>';
                $output = array();
                $sql = "SELECT * FROM cctemporary WHERE initiate_dept IN ('".$_GET["department"]."', 'Quality Assurance', 'Master')";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output1 = array();
        	            $sql1 = "SELECT * FROM cctemporary_files WHERE cc_no='".$row["cc_no"]."'";
        	            $result1 = $conn->query($sql1);
        	            if ($result1->num_rows > 0) {
        	                while ($row1 = $result1->fetch_assoc()) {
        	                    $output1[] = $row1;
        	                }
        	            }
        	            $row["attachments"] = $output1;
        	            $row["action_plan"] = json_decode($row["action_plan"]);
        	            
        	            $output1 = array();
        	            $sql1 = "SELECT * FROM cc_departments WHERE cc_no='".$row["cc_no"]."'";
        	            $result1 = $conn->query($sql1);
        	            if ($result1->num_rows > 0) {
        	                while ($row1 = $result1->fetch_assoc()) {
        	                    $output1[] = $row1;
        	                }
        	            }
                        $html.='<tr nobr="true">
                                <td style="width: 10%;">'.$row['cc_no'].'</td>
                                <td style="width: 10%;">'.$row['document_no'].'</td>
                                <td style="width: 20%;">'.$row['document_type'].'</td>
                                <td style="width: 10%;">'.$row['cc_related'].'</td>
                                <td style="width: 20%;">'.$row['initiate_dept'].'</td>
                                <td style="width: 10%;">'.$row['initiate_by'].'</td>
                                <td style="width: 20%;">'.date('d-m-Y',strtotime($row['initiate_date'])).'</td>
                                
                            </tr>';
                        $i++;
                    }
                }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('CC Record.pdf', 'I');
    }
    // else if ($_GET["type"] == "downloadCCLog") {
    //     class MYPDF extends TCPDF {

    //         //Page header
    //         public function Header() {
    //       // if($_GET['user_no'] == 'GMP007'){
                 
                
    //             $table='

    //              <style>td { border:solid 1px BCBBBA;}</style>
    //           <table>
    //                 <tr>
    //                      <td style="width:20%;">';
    //                       $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/api/gmptotal/upload/user/bajaj1.png'),20,7,17);
    //                     $table.='
    //                     </td>
    //                     <td style="width:80%;text-align:center;font-weight:bold;">
    //                         <span style="font-family:times;font-size:26px;">BAJAJ HEALTHCARE LTD.[UNIT-II]</span><br>
    //                         <span style="font-size:9px;">Location:Block No.588,Savli,karachia Road,At & Po:Gothada-391776 Tal.Savli<br>Dist:Vadodara,Gujarat,India.</span>
    //                     </td>
    //                 </tr>
    //             </table>
    //             <div></div>';
                
    //             $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
    //           $this->SetY(30); $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
    //             $this->SetY(32); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0,'Format No :QA005/F/01-08', 0, false, 'L', 0, '', 0, false, 'M', 'M');
    //             $this->SetY(31); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    //         }
    //         public function Footer(){
    //              $table='
    //         <style>td { border:solid 1px BCBBBA;}</style>
    //         <table border="1" cellpadding="5">
    //             <tr>
    //                 <td style="width:33%;"><b>PREPARED BY / DATE</b></td>
    //                 <td style="width:34%;"><b>REVIEWED BY / DATE</b></td>
    //                 <td style="width:33%;"><b>REVIEWED BY / DATE</b></td>
    //             </tr>
    //              <tr>
    //                 <td style="width:33%;"><div></div><div></div></td>
    //                 <td style="width:34%;"><div></div><div></div></td>
    //                 <td style="width:33%;"><div></div><div></div></td>
    //             </tr>
    //             </table>';
    //         $this->SetY(-50);
    //         $this->SetFont('Times', '', 10);
    //         $this->writeHTML($table, true, false, false, false, '');
    //         }
    //     }

    //         // create new PDF document
    //         $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    //         // set default header data
    //         $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
            
    //         // set header and footer fonts
    //         $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '30', PDF_FONT_SIZE_MAIN));
    //         $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '30', PDF_FONT_SIZE_DATA));
            
    //         // set default monospaced font
    //         $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            
    //         // set margins
    //         $pdf->SetMargins(PDF_MARGIN_LEFT, 40, PDF_MARGIN_RIGHT);
    //         $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    //         $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            
    //         // set auto page breaks
    //         $pdf->SetAutoPageBreak(TRUE, 60);
            
    //         // set image scale factor
    //         $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            
    //         // set some language-dependent strings (optional)
    //         if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    //             require_once(dirname(__FILE__).'/lang/eng.php');
    //             $pdf->setLanguageArray($l);
    //         }
            
    //         // ---------------------------------------------------------
            
    //         // set font
    //         $pdf->SetFont('times', '', 10);
        
    //         // add a page 
    //         $pdf->AddPage();
            
    //     $html.="";
    //     $sql = "SELECT * FROM ccpermanant WHERE id='".$_GET['id']."'";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //     $html.='<table border="1" cellpadding="5">
    //             <tr>
                    
    //                 <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR PERMANENT CHANGE</b></u></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:25%;"><b>Initiating Department</b></td>
    //                 <td style="width:25%;">'.$row['initiate_dept'].'</td>
    //                 <td style="width:25%;"><b>CC No</b></td>
    //                 <td style="width:25%;">'.$row['cc_no'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:25%;"><b>Date of Initiating</b></td>
    //                 <td style="width:75%;">'.date('d-m-Y',strtotime($row['initiate_date'])).'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>A. CHANGE INITIATION: (Part A: To be completed by change initiator)</b></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;">'.$row['cc_related'].' </td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>Title:</b>'.$row['change_title'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:25%;"><b>Document No:</b></td>
    //                 <td style="width:25%;">'.$row['document_no'].'</td>
    //                 <td style="width:25%;"><b>Revision No.:</b></td>
    //                 <td style="width:25%;">'.$row['revision_no'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:33%;"><b>Current / Existing Procedure</b><br>'.$row['existing_procedure'].'</td>
    //                 <td style="width:34%;"><b>	Proposed Changes" (s) (All changes being proposed should be listed here or attached herein)</b><br>'.$row['proposed_change'].'</td>
    //                 <td style="width:33%;"><b>	Reason / Justification of each Proposed change (s):(May be documented and attached herein)</b><br>'.$row['change_reason'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>	Attachment with proposed change* (s) e.g. Supporting document: </b><br></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:30%;"><b>Attachment No.</b></td>
    //                 <td style="width:70%;"><b>Title of Attachment</b></td>
    //             </tr>';
    //             $output1 = array();
	   //         $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type=''";
	   //         $result1 = $conn->query($sql1);
	   //         $i=1;
	   //         if ($result1->num_rows > 0) {
	   //             while ($row1 = $result1->fetch_assoc()) {
	   //                 $output1[] = $row1;
	   //              $file=$row1['file'];
    //     $html.='<tr>
    //                 <td style="width:30%;">'.$i++.'</td>
    //                 <td style="width:70%;">
    //                      <a href="https://'.$_SERVER['SERVER_NAME'].'/api/gmptotal/upload/ccpermanant/'.$row1['file'].'">'.$row1['particular'].'</a>
    //                 </td>
    //             </tr>';
    //                 }
    //             }
    //     $html.='<tr>
    //                 <td style="width:100%;"><b>* Attach Separate sheet if required</b></td>
    //             </tr>
    //             </table>
    //             <br pagebreak="true"/>';
                
    //     $html.='<table border="1" cellpadding="5">
    //             <tr>
    //                 <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR PERMANENT CHANGE</b></u></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:25%;"><b>Initiating Department</b></td>
    //                 <td style="width:25%;">'.$row['initiate_dept'].'</td>
    //                 <td style="width:25%;"><b>CC No</b></td>
    //                 <td style="width:25%;">'.$row['cc_no'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:25%;"><b>Date of Initiating</b></td>
    //                 <td style="width:75%;">'.date('d-m-Y',strtotime($row['initiate_date'])).'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>B. IMPACT EVALUATION AND ACTION PLAN OF PROPOSED CHANGE</b></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>B.1 Documents / system to be evaluated / revised / updated: (one or more of the following)</b></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:10%;"><b>Sr.No</b></td>
    //                 <td style="width:40%;"><b>Documents</b></td>
    //                 <td style="width:10%;"><b>Yes / No</b></td>
    //                 <td style="width:40%;"><b>List of proposed documents(Evaluate / Revise / Up-date, please specify)</b></td>
    //             </tr>';
    //             $row["action_plan"] = json_decode($row["action_plan"]);
    //             $action_plans= $row["action_plan"];
    //             $j=1;
    //             for($k=0;$k<count($action_plans);$k++){
    //                 $action=$action_plans[$k];
    //     $html.='<tr>
    //                 <td style="width:10%;">'.$j++.'</td>
    //                 <td style="width:40%;">'.$action->attribute.'</td>
    //                 <td style="width:10%;">'.$action->status.'</td>
    //                 <td style="width:40%;">'.$action->description.'</td>
    //             </tr>';
    //             }
    //     $html.='</table>
    //             <br pagebreak="true"/>';
                
    //     $html.='<table border="1" cellpadding="5">
    //             <tr>
    //                 <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR PERMANENT CHANGE</b></u></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:25%;"><b>Initiating Department</b></td>
    //                 <td style="width:25%;">'.$row['initiate_dept'].'</td>
    //                 <td style="width:25%;"><b>CC No</b></td>
    //                 <td style="width:25%;">'.$row['cc_no'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>Change Control Initiated By</b><br><div></div>
    //                     <table>
    //                         <tr>
    //                             <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['initiate_by'].'</td>
    //                             <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                             <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['initiate_date'])).'</td>
    //                         </tr>
    //                     </table>
    //                 </td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>Remarks:</b>'.$row['dept_remark'].'<br><b>Initiating Department Head</b><br>
    //                     <div></div>
    //                     <table>
    //                         <tr>
    //                             <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['dept_check_by'].'</td>
    //                             <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                             <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['dept_check_date'])).'</td>
    //                         </tr>
    //                     </table>
    //                 </td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>PRE-APPROVAL OF CHANGE CONTROL:</b><br>
    //                     <b>Tick the Classification of the proposed change:</b> &nbsp;&nbsp;'.$row['change_proposed'].'<br>
    //                     <b>Proposed Changes are:</b> &nbsp;&nbsp;'.$row['change_status'].'<br>';
    //                     if ($row['change_status'] !== 'APPROVED') {
    //                         $html.='<b>Reason for Rejection:</b> &nbsp;&nbsp;'.$row['reject_reason'].'<br>';
    //                     } else {
    //                         $html.='<b>Reason for Rejection:</b> &nbsp;&nbsp; Not Applicable<br>';
    //                     }
    //                     $html.='
    //                     <b>QA Executive/ Designee:</b><br>
    //                     <table>
    //                         <tr>
    //                              <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['preapproval_by'].'</td>
    //                             <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                             <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['preapproval_date'])).'</td>
    //                         </tr>
    //                     </table>
    //                 </td>
                                            
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>Pre-approval from customer</b><br>
    //                     <div></div>
    //                     <b>QA Head/Designee:</b><br>
    //                     <table>
    //                         <tr>
    //                             <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['preapproval_check_by'].'</td>
    //                             <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                             <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['preapproval_check_date'])).'</td>
    //                         </tr>
    //                     </table>
    //                 </td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>QA comment:</b>'.$row['preapproval_remark'].'<br>
    //                     <div></div>
    //                     <b>QA Head/Designee:</b>
    //                     <table>
    //                         <tr>
    //                             <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['preapproval_check_by'].'</td>
    //                             <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                             <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['preapproval_check_date'])).'</td>
    //                         </tr>
    //                     </table>
    //                 </td>
    //             </tr>
    //             </table>
    //             <br pagebreak="true"/>';
                
    //     $html.='<table border="1" cellpadding="5">
    //             <tr>
    //                 <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR PERMANENT CHANGE</b></u></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:25%;"><b>Initiating Department</b></td>
    //                 <td style="width:25%;">'.$row['initiate_dept'].'</td>
    //                 <td style="width:25%;"><b>CC No</b></td>
    //                 <td style="width:25%;">'.$row['cc_no'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;">QA will Forward the Change Control to the following concerned Departments(Wherever applicable).
    //                 put() marks to the applicable department</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;">';
    //                 $output2 = array();
    // 	            $sql2 = "SELECT * FROM ccp_departments WHERE cc_no='".$row["cc_no"]."'";
    // 	            $result2 = $conn->query($sql2);
    // 	            if ($result2->num_rows > 0) {
    // 	                while ($row2 = $result2->fetch_assoc()) {
    // 	                    $output2[] = $row2;
    	                
    //                     $html.=''.$row2['department'].' &nbsp;';
    // 	                }
    // 	            }
    //         $html.='</td>
    //             </tr>';
    //             $sql3 = "SELECT * FROM ccp_departments WHERE department='Production' AND cc_no='".$row["cc_no"]."'";
	   //         $result3 = $conn->query($sql3);
	   //         if ($result3->num_rows > 0) {
	   //             while ($row3 = $result3->fetch_assoc()) {
    //                     $html.='<tr>
    //                         <td style="width:100%;"><b>Evaluation by production Department:</b><br>
    //                             <table>
    //                                 <tr>
    //                                     <td style="width:33.33%; border:none;"><b>Name</b>:'.$row3['entry_by'].'</td>
    //                                     <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                     <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row[$row3['entry_date']])).'</td>
    //                                 </tr>
    //                             </table>
    //                         </td>
    //                     </tr>';
	   //             }
	   //         } 
	   //         else{
	   //             $html.='<tr>
    //                     <td style="width:100%;"><b>Evaluation by production Department:</b><br>
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>';
	   //         }
	   //         $sql4 = "SELECT * FROM ccp_departments WHERE department='Quality Control' AND cc_no='".$row["cc_no"]."'";
	   //         $result4 = $conn->query($sql4);
	   //         if ($result4->num_rows > 0) {
	   //             while ($row4 = $result4->fetch_assoc()) {
    //                     $html.='<tr>
    //                         <td style="width:100%;"><b>Evaluation by Quality Control Department:</b><br>
    //                             <table>
    //                                 <tr>
    //                                     <td style="width:33.33%; border:none;"><b>Name</b>:'.$row4['entry_by'].'</td>
    //                                     <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                     <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row4['entry_date'])).'</td>
    //                                 </tr>
    //                             </table>
    //                         </td>
    //                     </tr>';
    //             	}
	   //        }
	   //        else{
	   //             $html.='<tr>
    //                     <td style="width:100%;"><b>Evaluation by Quality Control Department:</b><br>
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:/td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>';
	   //         }
	   //         $sql5 = "SELECT * FROM ccp_departments WHERE department='Engineering' AND cc_no='".$row["cc_no"]."'";
	   //         $result5 = $conn->query($sql5);
	   //         if ($result5->num_rows > 0) {
	   //             while ($row5 = $result5->fetch_assoc()) {
    //                     $html.='<tr>
    //                         <td style="width:100%;"><b>Evaluation by Engineering/Project:</b><br>
    //                             <table>
    //                                 <tr>
    //                                     <td style="width:33.33%; border:none;"><b>Name</b>:'.$row5['entry_by'].'</td>
    //                                     <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                     <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row5['entry_date'])).'</td>
    //                                 </tr>
    //                             </table>
    //                         </td>
    //                     </tr>';
	   //             }
	   //         }
	   //         else{
	   //              $html.='<tr>
    //                     <td style="width:100%;"><b>Evaluation by Engineering/Project:</b><br>
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>';
	   //         }
	   //         $sql6 = "SELECT * FROM ccp_departments WHERE department='WareHouse' AND cc_no='".$row["cc_no"]."'";
	   //         $result6 = $conn->query($sql6);
	   //         if ($result6->num_rows > 0) {
	   //             while ($row6 = $result6->fetch_assoc()) {
    //                     $html.='<tr>
    //                         <td style="width:100%;"><b>Evaluation by Warehouse:</b><br>  
    //                           <table>
    //                                 <tr>
    //                                     <td style="width:33.33%; border:none;"><b>Name</b>:'.$row6['entry_by'].'</td>
    //                                     <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                     <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row6['entry_date'])).'</td>
    //                                 </tr>
    //                           </table>
    //                         </td>
    //                     </tr>';
	   //             }
	   //         }
	   //         else{
	   //              $html.='<tr>
    //                     <td style="width:100%;"><b>Evaluation by Warehouse:</b><br>  
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                       </table>
    //                     </td>
    //                 </tr>';
	   //         }
	   //         $sql7 = "SELECT * FROM ccp_departments WHERE department='EHS' AND cc_no='".$row["cc_no"]."'";
	   //         $result7 = $conn->query($sql7);
	   //         if ($result7->num_rows > 0) {
	   //             while ($row7 = $result7->fetch_assoc()) {
    //                     $html.='<tr>
    //                         <td style="width:100%;"><b>Evaluation by EHS:</b><br>
    //                             <table>
    //                                 <tr>
    //                                     <td style="width:33.33%; border:none;"><b>Name</b>:'.$row7['entry_by'].'</td>
    //                                     <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                     <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row7['entry_date'])).'</td>
    //                                 </tr>
    //                             </table>
    //                         </td>
    //                     </tr>';
	   //             }
	   //         }
	   //         else{
	   //              $html.='<tr>
    //                     <td style="width:100%;"><b>Evaluation by EHS:</b><br>
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>';
	   //         }
	   //         $sql8 = "SELECT * FROM ccp_departments WHERE department='HR' AND cc_no='".$row["cc_no"]."'";
	   //         $result8 = $conn->query($sql8);
	   //         if ($result8->num_rows > 0) {
	   //             while ($row8 = $result8->fetch_assoc()) {
    //                     $html.='<tr>
    //                         <td style="width:100%;"><b>Evaluat ion by HR:</b><br>
    //                             <table>
    //                                 <tr>
    //                                     <td style="width:33.33%; border:none;"><b>Name</b>:'.$row8['entry_by'].'</td>
    //                                     <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                     <td style="width:33.33%; border:none;"><b>Date:</b>'.$row8['entry_date'].'</td>
    //                                 </tr>
    //                             </table>
    //                         </td>
    //                     </tr>';
	   //             }
	   //         }
	   //         else{
	   //             $html.='<tr>
    //                     <td style="width:100%;"><b>Evaluat ion by HR:</b><br>
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>';
	   //         }
	   //         $sql9 = "SELECT * FROM ccp_departments WHERE department='Regulatory Affairs' AND cc_no='".$row["cc_no"]."'";
	   //         $result9 = $conn->query($sql9);
	   //         if ($result9->num_rows > 0) {
	   //             while ($row9 = $result9->fetch_assoc()) {
    //                     $html.='<tr>
    //                         <td style="width:100%;"><b>Evaluation by Regulatory Affairs: </b><br>
    //                             <b>Impact on regulatory filling:</b><br>
    //                             <table>
    //                                 <tr>
    //                                     <td style="width:33.33%; border:none;"><b>Name</b>:'.$row9['entry_by'].'</td>
    //                                     <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                     <td style="width:33.33%; border:none;"><b>Date:</b>'.$row9['entry_date'].'</td>
    //                                 </tr>
    //                             </table>
    //                         </td>
    //                     </tr>';
	   //             }
	   //         }
	   //         else{
	   //             $html.='<tr>
    //                     <td style="width:100%;"><b>Evaluation by Regulatory Affairs: </b><br>
    //                         <b>Impact on regulatory filling:</b><br>
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>
    //             </table>
    //             <br pagebreak="true"/>';
	   //         }
	            
    //     $html.='<table border="1" cellpadding="5">
    //             <tr>
    //                 <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR PERMANENT CHANGE</b></u></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:25%;"><b>Initiating Department</b></td>
    //                 <td style="width:25%;">'.$row['initiate_dept'].'</td>
    //                 <td style="width:25%;"><b>CC No</b></td>
    //                 <td style="width:25%;">'.$row['cc_no'].'</td>
    //             </tr>';
    //             $sql10 = "SELECT * FROM ccp_departments WHERE department='R & D' AND cc_no='".$row["cc_no"]."'";
	   //         $result10 = $conn->query($sql10);
	   //         if ($result10->num_rows > 0) {
	   //             while ($row10 = $result10->fetch_assoc()) {
    //             $html.='<tr>
    //                         <td style="width:100%;"><b>Evalution by R & D:</b><br>
    //                             <table>
    //                                 <tr>
    //                                     <td style="width:33.33%; border:none;"><b>Name</b>:'.$row10['entry_by'].'</td>
    //                                     <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                     <td style="width:33.33%; border:none;"><b>Date:</b>'.$row10['entry_date'].'</td>
    //                                 </tr>
    //                             </table>
    //                         </td>
    //                     </tr>';
	   //             }
	   //         }else{
    //                 $html.='<tr>
    //                     <td style="width:100%;"><b>Evalution by R & D:</b><br>
    //                       <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>';  
	   //        }
	   //        $sql14 = "SELECT * FROM ccp_departments WHERE department='warehouse' AND cc_no='".$row["cc_no"]."'";
	   //         $result14 = $conn->query($sql14);
	   //         if ($result14->num_rows > 0) {
	   //             while ($row14 = $result14->fetch_assoc()) {
    //                     $html.='<tr>
    //                         <td style="width:100%;"><b>Evalution by Warehouse:</b><br>
    //                             <div></div>
    //                             <table>
    //                                 <tr>
    //                                     <td style="width:33.33%; border:none;"><b>Name</b>:'.$row14['entry_by'].'</td>
    //                                     <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                     <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row14['entry_date'])).'</td>
    //                                 </tr>
    //                             </table>
    //                         </td>
    //                     </tr>';
	   //             }
	   //         }
	   //         else{
	   //             $html.='<tr>
    //                     <td style="width:100%;"><b>Evalution by Warehouse:</b><br>
    //                         <div></div>
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>';
	   //         }
	   //         $sql17 = "SELECT * FROM ccp_departments WHERE department='marketing' AND cc_no='".$row["cc_no"]."'";
	   //         $result17 = $conn->query($sql17);
	   //         if ($result17->num_rows > 0) {
	   //             while ($row17 = $result17->fetch_assoc()) {
    //                     $html.='<tr>
    //                         <td style="width:100%;"><b>Evalution by Marketing</b><br>
    //                             <div></div>
    //                             <table>
    //                                 <tr>
    //                                     <td style="width:33.33%; border:none;"><b>Name</b>:'.$row17['entry_by'].'</td>
    //                                     <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                     <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row17['entry_date'])).'</td>
    //                                 </tr>
    //                             </table>
    //                         </td>
    //                     </tr>';
	   //             }
	   //         }
	   //         else{
	   //             $html.='<tr>
    //                     <td style="width:100%;"><b>Evalution by Marketing</b><br>
    //                         <div></div>
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>';
	   //         }
    //             $sql15 = "SELECT * FROM ccp_departments WHERE department='purchase' AND cc_no='".$row["cc_no"]."'";
	   //         $result15 = $conn->query($sql15);
	   //         if ($result15->num_rows > 0) {
	   //             while ($row15 = $result15->fetch_assoc()) {
    //                     $html.='<tr>
    //                         <td style="width:100%;"><b>Evalution by Purchase</b><br>
    //                             <div></div>
    //                             <table>
    //                                 <tr>
    //                                     <td style="width:33.33%; border:none;"><b>Name</b>:'.$row15['entry_by'].'</td>
    //                                     <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                     <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row15['entry_date'])).'</td>
    //                                 </tr>
    //                             </table>
    //                         </td>
    //                     </tr>';
	   //             }
	   //         }
	   //         else{
	   //             $html.='<tr>
    //                     <td style="width:100%;"><b>Evalution by Purchase</b><br>
    //                         <div></div>
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>';
	   //         }
    //             $sql11 = "SELECT * FROM ccp_departments WHERE department='IT' AND cc_no='".$row["cc_no"]."'";
	   //         $result11 = $conn->query($sql11);
	   //         if ($result11->num_rows > 0) {
	   //             while ($row11 = $result11->fetch_assoc()) {
    //                     $html.='<tr>
    //                         <td style="width:100%;"><b>Evaluation by IT department:</b><br>
    //                             <table>
    //                                 <tr>
    //                                     <td style="width:33.33%; border:none;"><b>Name</b>:'.$row11['entry_by'].'</td>
    //                                     <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                     <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row11['entry_date'])).'</td>
    //                                 </tr>
    //                             </table>
    //                         </td>
    //                     </tr>';
	   //             }
	   //         }
	   //         else{
	   //              $html.='<tr>
    //                     <td style="width:100%;"><b>Evaluation by IT department:</b><br>
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>';
	   //         }
    //             $sql12 = "SELECT * FROM ccp_departments WHERE department='AQA' AND cc_no='".$row["cc_no"]."'";
	   //         $result12 = $conn->query($sql12);
	   //         if ($result12->num_rows > 0) {
	   //             while ($row12 = $result12->fetch_assoc()) {
    //                     $html.='<tr>
    //                     <td style="width:100%;"><b>Evaluation by AQA department:</b><br>
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:'.$row12['entry_by'].'</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row12['entry_date'])).'</td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>';
	   //             }
	   //         }
	   //         else{
	   //             $html.='<tr>
    //                     <td style="width:100%;"><b>Evaluation by AQA department:</b><br>
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>';
	   //         }
	   //         $sql13 = "SELECT * FROM ccp_departments WHERE department='other' AND cc_no='".$row["cc_no"]."'";
	   //         $result13 = $conn->query($sql13);
	   //         if ($result13->num_rows > 0) {
	   //             while ($row13 = $result13->fetch_assoc()) {
    //                     $html.='<tr>
    //                         <td style="width:100%;"><b>Evaluation by any other department:</b><br>
    //                             <div></div>
    //                             <table>
    //                                 <tr>
    //                                     <td style="width:33.33%; border:none;"><b>Name</b>:'.$row13['entry_by'].'</td>
    //                                     <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                     <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row13['entry_date'])).'</td>
    //                                 </tr>
    //                             </table>
    //                         </td>
    //                     </tr>';
	   //             }
	   //         }
	   //         else{
	   //             $html.='<tr>
    //                     <td style="width:100%;"><b>Evaluation by any other department:</b><br>
    //                         <div></div>
    //                         <table>
    //                             <tr>
    //                                 <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                                 <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                                 <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                             </tr>
    //                         </table>
    //                     </td>
    //                 </tr>';
	   //         }
	            
    //     $html.='<tr>
    //                 <td style="width:100%;"><b>Post Approvel Changes:</b><br>
    //                     <b>QA Head Comment</b><br>
    //                     <b>Proposed Changes are:&nbsp;</b><input type="checkbox" name="box" value="1"/>APPROVED &nbsp;&nbsp;<input type="checkbox" name="box" value="2"/>REJECTED<br>
    //                     <table>
    //                         <tr>
    //                             <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                             <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                             <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                         </tr>
    //                     </table>
    //                 </td>
    //             </tr>
    //             </table>
    //             <br pagebreak="true"/>';
                
    //     $html.='<table border="1" cellpadding="5">
    //             <tr>
    //                 <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR PERMANENT CHANGE</b></u></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:25%;"><b>Initiating Department</b></td>
    //                 <td style="width:25%;">'.$row['initiate_dept'].'</td>
    //                 <td style="width:25%;"><b>CC No</b></td>
    //                 <td style="width:25%;">'.$row['cc_no'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>Post Approvel Changes:</b><br><b>CQA Head/Designee Comment</b>'.$row['postapproval_comment'].'<br>
    //                     <table>
    //                         <tr>
    //                             <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['postapproval_by'].'</td>
    //                             <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                             <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['postapproval_date'])).'</td>
    //                         </tr>
    //                     </table>
    //                 </td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>Extension (if required):</b>'.$row['extension_status'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:10%;"><b>Sr.No</b></td>
    //                 <td style="width:45%;"><b>Action plan</b></td>
    //                 <td style="width:45%;"><b>Justification For Extension</b></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:10%;">1</td>
    //                 <td style="width:45%;">'.$row['extension_plan'].'</td>
    //                 <td style="width:45%;">'.$row['extension_justification'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:55%;"><b>Old Target Date:</b></td>
    //                 <td style="width:45%;"><b>New Target Date:</b></td>
    //             </tr>
    //           <tr>
    //                 <td style="width:100%;"><b>Extension Initiated by:</b><br>
    //                     <div></div>
    //                     <table>
    //                         <tr>
    //                             <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['extension_by'].'</td>
    //                             <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                             <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['extension_date'])).'</td>
    //                         </tr>
    //                     </table>
    //                 </td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>Approved by:</b><br>
    //                     <b>(QA Head Sign./Date)</b><br>
    //                     <div></div>
    //                     <table>
    //                      <tr>
    //                         <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['extension_check_by'].'</td>
    //                         <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                         <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['extension_check_date'])).'</td>
    //                     </tr>
    //                     </table>
    //                 </td>              
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>Implementation review:</b><br><b>Date of Implimation:</b><br>
    //                     <b>Remark</b>________________________
    //                 </td>
    //             </tr>
               
    //             </table>
    //             <br pagebreak="true"/>';
                
    //      $html.='<table border="1" cellpadding="3">
    //             <tr>
    //                 <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR PERMANENT CHANGE</b></u></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:25%;"><b>Initiating Department</b></td>
    //                 <td style="width:25%;">'.$row['initiate_dept'].'</td>
    //                 <td style="width:25%;"><b>CC No</b></td>
    //                 <td style="width:25%;">'.$row['cc_no'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>D.Post Implementation review</b></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>Document Revised/Updated(To be filled by QA)</b></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:10%;"><b>Sr.No</b></td>
    //                 <td style="width:40%;"><b>Document</b></td>
    //                 <td style="width:10%;"><b>Yes/No</b></td>
    //                 <td style="width:40%;"><b>Document Reference No</b></td>
    //             </tr>';
    //             $row["requirements"] = json_decode($row["requirements"]);
    //             $requirements= $row["requirements"];
    //             $j=1;
    //             for($k=0;$k<count($requirements);$k++){
    //                 $requirement=$requirements[$k];
                
    //     $html.='<tr>
    //                 <td style="width:10%;">'.$j++.'</td>
    //                 <td style="width:40%;">'.$requirement->requirement.'</td>
    //                 <td style="width:10%;"></td>
    //                 <td style="width:40%;"></td>
    //             </tr>';
    //             }
    //     $html.='<tr>
    //                 <td style="width:100%;"><b>Attached Separate sheet,if required</b></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;">
    //                     <table>
    //                         <tr>
    //                             <td style="width:33.33%; border:none;"><b>Name</b>:</td>
    //                             <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                             <td style="width:33.33%; border:none;"><b>Date:</b></td>
    //                         </tr>
    //                     </table>
    //                 </td>
    //             </tr>
    //              <tr>
    //                 <td style="width:100%;"><b>E.EVALUTION OF CHANGE(Post Implimentation)</b>:<br><b>E.1.All change control Requirements have been met</b></td>
    //              </tr>
    //              <tr>
    //                 <td style="width:100%;"><b>Comment:</b>'.$row['implementation_comment'].'</td>
    //              </tr>
    //              <tr>
    //                 <td style="width:100%;"><b>Change Implements as  per the Change Control Form are:</b>'.$row['ispermanent'].'</td>
    //              </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>F. CLOSURE OF CHANGE  CONTROL :</b><br>
    //                     <b>Comments by Quality Assurance Head (or Designee):</b><br><br>
    //                     <table>
    //                          <tr>
    //                             <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['close_by'].'</td>
    //                             <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                             <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['close_date'])).'</td>
    //                         </tr>
    //                     </table>
    //                 </td>                  
    //             </tr>
    //             </table>
    //             <br pagebreak="true"/>';
    //     $html.='<table border="1" cellpadding="5">
    //             <tr>
    //                 <td style="width:100%; text-align:center;"><u><b>CHANGE CONTROL FORM FOR PERMANENT CHANGE</b></u></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:25%;"><b>Initiating Department</b></td>
    //                 <td style="width:25%;">'.$row['initiate_dept'].'</td>
    //                 <td style="width:25%;"><b>CC No</b></td>
    //                 <td style="width:25%;">'.$row['cc_no'].'</td>
    //             </tr>
    //              <tr>
    //                 <td style="width:100%;"><b>G CLOSURE OF CHANGE CONTRTOL:</b><br>
    //                     <b>Comment by Corporate Quality Assurance Head (or Designee):</b><br><br>
    //                     <table>
    //                      <tr>
    //                         <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['close_by'].'</td>
    //                         <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
    //                         <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['close_date'])).'</td>
    //                     </tr>
    //                     </table>
    //                 </td>                  
    //             </tr>
    //              <tr>
    //                 <td style="width:100%;"><b>G1 List Of Attachments</b></td>
    //              </tr>
    //              <tr>
    //                 <td style="width:20%;"><b>Attachment No</b></td>
    //                 <td style="width:80%;"><b>Title of Attachment</b></td>
    //              </tr>';
    //             $output1 = array();
	   //         $sql1 = "SELECT * FROM ccpermanant_files WHERE cc_no='".$row["cc_no"]."' AND type !=''";
	   //         $result1 = $conn->query($sql1);
	   //         $i=1;
	   //         if ($result1->num_rows > 0) {
	   //             while ($row1 = $result1->fetch_assoc()) {
	   //                 $output1[] = $row1;
	   //                  $file=$row1['file'];
    //     $html.='<tr>
    //                 <td style="width:30%;">'.$i++.'</td>
    //                 <td style="width:70%;">
    //                      <a href="https://'.$_SERVER['SERVER_NAME'].'/api/gmptotal/upload/cctemporary/'.$row1['file'].'">'.$row1['particular'].'</a>
    //                 </td>
    //             </tr>';
    //                 }
    //             }
    //     $html.='<tr>
    //                 <td style="width:100%;"><b>*Attach Separate Sheet is required</b></td>
    //             </tr>
    //             </table>';
    //         }
    //     }
    //     $pdf->writeHTML($html, true, false, false, false, '');
    //     $pdf->Output('ChangecontrolPermaneant.pdf', 'I');
    // }
    else if ($_GET["type"] == "downloadCCLog") { // amardeep
	   
	    $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
		

            
			$html= "";
			$html.='
 <table border="1">
 <tr>
 <td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;"rowspan="2"></td>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">DEPARTMENT: QUALITY ASSURANCE</td>
<td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;" rowspan="2">page No:</td>
 </tr>
 <tr>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">Permanent Change Control Format</td>
 </tr>
 <br>
 
 </table>
 <br>
 <br>
 <table border="1">
 <tr>
 <td style="width: 150px; font-size: 15; font-weight:  text-align: left;"rowspan="2">Select Unique Item 
 Value</td>
 <td style="width: 110px; font-size: 15; font-weight:  text-align: left;">  location</td>
 <td style="width: 20px; font-size: 18; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15; font-weight: text-align: left;">Olive Healthcare Unit-II</td>
 </tr>
 <tr>
 <td style="width: 110px; font-size: 15; font-weight:  text-align: left;">  Department</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15; font-weight:  text-align: left;">  Issue Code</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 
 <tr>
 <td style="width: 260px; font-size: 15; font-weight:  text-align: left;">  Reference *</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15; font-weight: text-align: left;"> </td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15;height: 20px; font-weight:  text-align: left;">  Referred Issue(s)</td>
 <td style="width: 20px; font-size: 15;height: 20px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15;height: 20px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15;height: 20px; font-weight:  text-align: left;">  Initiating Department *</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15;height: 30px; font-weight:  text-align: left;">  Date of Initiation *</td>
 <td style="width: 20px; font-size: 15; height: 30px;font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15; height: 30px;font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15;height: 30px; font-weight:  text-align: left;">  Target Date of Closure *</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15; height: 30px;font-weight:  text-align: left;">  Market</td>
 <td style="width: 20px; font-size: 15; height: 30px;font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15;height: 30px; font-weight:  text-align: left;">  Customer</td>
 <td style="width: 20px; font-size: 15; height: 30px;font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15;height: 30px; font-weight:  text-align: left;">  Change Related To *</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15;height: 30px; font-weight:  text-align: left;">  Product</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15;height: 30px; font-weight:  text-align: left;">  Material</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15;height: 30px; font-weight:  text-align: left;">  Equipment</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15;height: 30px; font-weight:  text-align: left;">  Facility</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15;height: 30px; font-weight:  text-align: left;">  System</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 
 <tr>
 <td style="width: 260px; font-size: 15;height: 30px; font-weight:  text-align: left;"> Others</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 
 <tr>
 <td style="width: 260px; font-size: 15;height: 30px; font-weight:  text-align: left;"> Stage</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size:height: 30px; 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 260px; font-size: 15;height: 70px; font-weight:  text-align: left;"> Current Practice *</td>
 <td style="width: 20px; font-size: 15;height: 70px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15;height: 70px; font-weight: text-align: left;"></td>
 </tr>
 <tr> 
 <td style="width: 260px; font-size: 15;height: 70px; font-weight:  text-align: left;">  Proposed Change *</td>
 <td style="width: 20px; font-size: 15;height: 70px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 260px; font-size: 15;height: 70px; font-weight: text-align: left;"></td>
 </tr>
 
 

 </table>
 <div>
 </div>
 <div>
 </div>
 <div>
 </div>
 <div>
 </div>
 <table border="1">
 <tr>
 <td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;"rowspan="2"></td>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">DEPARTMENT: QUALITY ASSURANCE</td>
<td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;" rowspan="2">page No:</td>
 </tr>
 <tr>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">Permanent Change Control Format</td>
 </tr>
 <br>
 
 </table>
 <br>
 <br>
 <table border="1">
 <tr>
 <td style="width: 220px; font-size: 13;height: 60px; font-weight:  text-align: left;"> Justification For Proposed Change *</td>
 <td style="width: 20px; font-size: 15;height: 60px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 300px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 220px; font-size: 13;height: 30px; font-weight:  text-align: left;">  Supporting Data / References / List 
 of Attachments</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 300px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 220px; font-size: 13;height: 30px; font-weight:  text-align: left;">  Initiator - File Attachments (if any)</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 300px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 220px; font-size: 13;height: 30px; font-weight:  text-align: left;">  Initiator Name / Sign and Date</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 300px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 220px; font-size: 13;height: 50px; font-weight:  text-align: left;">  I-HOD Review Comments</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 300px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 220px; font-size: 13;height: 30px; font-weight:  text-align: left;"> Action Plan Proposal(s) Required *</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 300px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 540px; font-size: 15; font-weight: text-align: left;">   Proposed Action Plan(s)  </td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14; font-weight:bold; text-align: center;">SR No</td>
 <td style="width: 300px; font-size: 14; font-weight:bold; text-align: center;">Action Plan</td>
 <td style="width: 110px; font-size: 14; font-weight:bold; text-align: center;">Assigned To</td>
 <td style="width: 80px; font-size: 14; font-weight:bold; text-align: center;">TCD</td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   1.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   2.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   3.</td>
 <td style="width: 300px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14; height: 30px;font-weight: text-align: center;">   4.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   5.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   6.</td>
 <td style="width: 300px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   7.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   8.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   9.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">  10.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">  11.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">  12.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">  13.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">  14.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 </table> 
 <div>
 </div>
 <div>
 </div>
 <table border="1">
 <tr>
 <td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;"rowspan="2"></td>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">DEPARTMENT: QUALITY ASSURANCE</td>
<td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;" rowspan="2">page No:</td>
 </tr>
 <tr>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">Permanent Change Control Format</td>
 </tr>
 <br>
 
 </table>
 <br>
 <br>
 <table border="1">
 
 <tr>
 <td style="width: 230px; font-size: 15; font-weight:  text-align: center;"rowspan="6">  Department(s)   Required   To   Provide 
 Impact Assessment / Evaluation *</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;"rowspan="6"> </td>
     <td style="width: 135px; font-size: 15; font-weight: text-align: left;">Quality Control</td>
     <td style="width: 155px; font-size: 15; font-weight: text-align: left;">IT</td>
 </tr>
 <tr>
     <td style="width: 135px; font-size: 15; font-weight: text-align: center;">Warehouse</td>
     <td style="width: 155px; font-size: 15; font-weight: text-align: center;">Microbiology</td>
 </tr>
 <tr>
     <td style="width: 135px; font-size: 15; font-weight: text-align: center;">Production</td>
     <td style="width: 155px; font-size: 15; font-weight: text-align: center;">B Development</td>
 </tr>
 <tr>
     <td style="width: 135px; font-size: 15; font-weight: text-align: center;">Packing</td>
     <td style="width: 155px; font-size: 15; font-weight: text-align: center;">Supply Chain </td>
 </tr>

 <tr>
     <td style="width: 135px; font-size: 15; font-weight: text-align: center;">Engineering </td>
     <td style="width: 155px; font-size: 15; font-weight: text-align: center;">Regulatory Affairs</td>
 </tr>
 <tr>
     <td style="width: 135px; font-size: 15; font-weight: text-align: center;">A & P</td>
     <td style="width: 155px; font-size: 15; font-weight: text-align: center;"> Quality Assurance *</td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15;height: 40px; font-weight:  text-align: left;"> File Attachments (if any) (I-HOD)</td>
 <td style="width: 20px; font-size: 15;height: 40px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15; font-weight:  text-align: left;"> Decision</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15; font-weight:  text-align: left;"> Remark (s) / Reason (s)</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15; font-weight:  text-align: left;"> I-HOD Name / Sign and Date</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15;     font-weight:  text-align: left;"> QA Review Comments</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15; font-weight:  text-align: center;"rowspan="6">  Additional Review / Comments -
 Other Departments (if required)*</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;"rowspan="6"> </td>
     <td style="width: 135px; font-size: 15; font-weight: text-align: left;">Quality Control</td>
     <td style="width: 155px; font-size: 15; font-weight: text-align: left;">IT</td>
 </tr>
 <tr>
     <td style="width: 135px; font-size: 15; font-weight: text-align: center;">Warehouse</td>
     <td style="width: 155px; font-size: 15; font-weight: text-align: center;">Microbiology</td>
 </tr>
 <tr>
     <td style="width: 135px; font-size: 15; font-weight: text-align: center;">Production</td>
     <td style="width: 155px; font-size: 15; font-weight: text-align: center;">B Development</td>
 </tr>
 <tr>
     <td style="width: 135px; font-size: 15; font-weight: text-align: center;">Packing</td>
     <td style="width: 155px; font-size: 15; font-weight: text-align: center;">Supply Chain </td>
 </tr>

 <tr>
     <td style="width: 135px; font-size: 15; font-weight: text-align: center;">Engineering </td>
     <td style="width: 155px; font-size: 15; font-weight: text-align: center;">Regulatory Affairs</td>
 </tr>
 <tr>
     <td style="width: 135px; font-size: 15; font-weight: text-align: center;">A & P</td>
     <td style="width: 155px; font-size: 15; font-weight: text-align: center;"> Quality Assurance *</td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15; font-weight:  text-align: left;"> File Attachments (if any) (I-HOD)</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15; font-weight:  text-align: left;"> Decision</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15; font-weight:  text-align: left;"> Remark (s) / Reason (s)</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15; font-weight:  text-align: left;"> QA Review Name / Sign and Date</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 540px; font-size: 15; font-weight: text-align: left;">Other Department (s) Comments : </td>

 </tr>
 <tr>
 <td style="width: 115px; font-size: 15; font-weight:  text-align: left;"rowspan="3"> Quality Control</td>
 <td style="width: 115px; font-size: 15;height: 20px; font-weight:  text-align: left;"> Comments</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 115px; font-size: 15; font-weight:  text-align: left;">File Attachment 
 (If any)</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 115px; font-size: 15; font-weight:  text-align: left;">Name / Sign & 
 Date</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 115px; font-size: 15; font-weight:  text-align: left;"rowspan="3"> Warehouse</td>
 <td style="width: 115px; font-size: 15;height: 20px; font-weight:  text-align: left;"> Comments</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 115px; font-size: 15; font-weight:  text-align: left;">File Attachment 
 (If any)</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 115px; font-size: 15; font-weight:  text-align: left;">Name / Sign & 
 Date</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 </table>
 <div>
 </div>
 <div>
 </div>
 <table border="1">
 <tr>
 <td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;"rowspan="2"></td>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">DEPARTMENT: QUALITY ASSURANCE</td>
<td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;" rowspan="2">page No:</td>
 </tr>
 <tr>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">Permanent Change Control Format</td>
 </tr>
 <br>
 
 </table>
 <br>
 <br>
 
 <table border="1">

 <tr>
 <td style="width: 540px; font-size: 13; font-weight: bold; text-align: left;">Other Department (s) Comments (Continue)</td>
  </tr>
  <tr>
  <td style="width: 130px; font-size: 15; font-weight:  text-align: left;" rowspan="3"> Production</td>
  <td style="width: 130px; font-size: 15; font-weight:  text-align: left;">Comments </td>
  <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
      <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
  </tr>
  <tr>
  <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> File Attachment 
  </td>
  <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
      <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
  </tr>
  <tr>
  <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> Name / Sign & 
  Date</td>
  <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
      <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
  </tr>
  
   <tr>
   <td style="width: 130px; font-size: 15; font-weight:  text-align: left;" rowspan="3"> Packing</td>
   <td style="width: 130px; font-size: 15; font-weight:  text-align: left;">Comments </td>
   <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
       <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
   </tr>
   <tr>
   <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> File Attachment 
   </td>
   <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
       <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
   </tr>
   <tr>
   <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> Name / Sign & 
   Date</td>
   <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
       <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
   </tr>
   
    <tr>
    <td style="width: 130px; font-size: 15; font-weight:  text-align: left;" rowspan="3"> Engineering</td>
    <td style="width: 130px; font-size: 15; font-weight:  text-align: left;">Comments </td>
    <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
        <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
    </tr>
    <tr>
    <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> File Attachment 
    </td>
    <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
        <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
    </tr>
    <tr>
    <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> Name / Sign & 
    Date</td>
    <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
        <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
    </tr>
    
     <tr>
     <td style="width: 130px; font-size: 15; font-weight:  text-align: left;" rowspan="3"> A & P</td>
     <td style="width: 130px; font-size: 15; font-weight:  text-align: left;">Comments </td>
     <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
         <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
     </tr>
     <tr>
     <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> File Attachment 
     (If any)</td>
     <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
         <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
     </tr>
     <tr>
     <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> Name / Sign & 
     Date</td>
     <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
         <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
     </tr>
     
      <tr>
      <td style="width: 130px; font-size: 15; font-weight:  text-align: left;" rowspan="3"> IT</td>
      <td style="width: 130px; font-size: 15; font-weight:  text-align: left;">Comments </td>
      <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
          <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
      </tr>
      <tr>
      <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> File Attachment 
      </td>
      <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
          <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
      </tr>
      <tr>
      <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> Name / Sign & 
      Date</td>
      <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
          <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
      </tr>
     
       <tr>
       <td style="width: 130px; font-size: 15; font-weight:  text-align: left;" rowspan="3"> Microbiology</td>
       <td style="width: 130px; font-size: 15; font-weight:  text-align: left;">Comments </td>
       <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
           <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
       </tr>
       <tr>
       <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> File Attachment 
    </td>
       <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
           <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
       </tr>
       <tr>
       <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> Name / Sign & 
       Date</td>
       <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
           <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
       </tr>
       
        <tr>
        <td style="width: 130px; font-size: 15; font-weight:  text-align: left;" rowspan="3"> Business 
        Development</td>
        <td style="width: 130px; font-size: 15; font-weight:  text-align: left;">Comments </td>
        <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
            <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
        </tr>
        <tr>
        <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> File Attachment 
        </td>
        <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
            <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
        </tr>
        <tr>
        <td style="width: 130px; font-size: 15; font-weight:  text-align: left;"> Name / Sign & 
        Date</td>
        <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
            <td style="width: 260px; font-size: 15; font-weight: text-align: left;"></td>
        </tr>
        
          </table>
 <div>
 </div>
 <div>
 </div>
 <div>
 </div>
 <div>
 </div>
 <div>
 </div>
 <table border="1">
 <tr>
 <td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;"rowspan="2"></td>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">DEPARTMENT: QUALITY ASSURANCE</td>
<td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;" rowspan="2">page No:</td>
 </tr>
 <tr>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">Permanent Change Control Format</td>
 </tr>
 <br>
 
 </table>
 <br>
 <br>
 <table border="1">

 <tr>
 <td style="width: 540px; font-size: 13;height: 14px; font-weight: bold; text-align: center;"> Other Department (s) Comments (Continue)</td>
  </tr>
  <tr>
 <td style="width: 115px; font-size: 14; font-weight:  text-align: center;"rowspan="3"> Supply Chain</td>
 <td style="width: 115px; font-size: 14;height: 20px; font-weight:  text-align: center;"> Comments</td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 115px; font-size: 14; font-weight:  text-align: center;">File Attachment 
 (If any)</td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 115px; font-size: 14; font-weight:  text-align: center;"> Name / Sign & 
 Date</td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 540px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">  Regulatory Affairs Comments</td>
  </tr>
  <tr>
 <td style="width: 230px; font-size: 14;height: 60px; font-weight:  text-align: center;"> Comments *</td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
  <tr>
 <td style="width: 230px; font-size: 14; font-weight:  text-align: center;"> Change in Registration Dossier *</td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
  <tr>
 <td style="width: 230px; font-size: 14; font-weight:  text-align: center;"> Change in Pharmacopoeial status </td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
  <tr>
 <td style="width: 230px; font-size: 14; font-weight:  text-align: center;"> Change in Annual updates / 
 Supplements * </td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
  <tr>
 <td style="width: 230px; font-size: 14; font-weight:  text-align: center;"> Change in Customer`s Requirement * </td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
  <tr>
 <td style="width: 230px; font-size: 14; font-weight:  text-align: center;"> Change in Regulatory Requirement
 * </td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
  <tr>
 <td style="width: 230px; font-size: 14; font-weight:  text-align: center;"> Documents required for submission 
 to Authority / Contract Giver *</td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
  <tr>
 <td style="width: 230px; font-size: 14; font-weight:  text-align: center;"> File Attachment (If any) *</td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
  <tr>
 <td style="width: 230px; font-size: 14; font-weight:  text-align: center;"> Name / Sign and Date</td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 540px; font-size: 14; font-weight: text-align: center;"> QA Final Review</td>

 </tr>
 <tr>
 <td style="width: 230px; font-size: 14;height: 60px; font-weight:  text-align: center;"> Review Comments *</td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 14;height: 30px; font-weight:  text-align: center;"> Action Plan Proposal (s) Required *</td>
 <td style="width: 20px; font-size: 14; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 14; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 540px; font-size: 14; font-weight:bold; text-align: center;"> Proposed Action Plan (s)</td>

 </tr>
 </table>
 <table border="1">

 <tr>
 <td style="width: 50px; font-size: 14; font-weight:bold; text-align: center;">SR No</td>
 <td style="width: 300px; font-size: 14; font-weight:bold; text-align: center;">Action Plan</td>
 <td style="width: 110px; font-size: 14; font-weight:bold; text-align: center;">Assigned To</td>
 <td style="width: 80px; font-size: 14; font-weight:bold; text-align: center;">TCD</td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   1.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   2.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   3.</td>
 <td style="width: 300px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14; height: 30px;font-weight: text-align: center;">   4.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 25px; font-weight: text-align: center;">   5.</td>
 <td style="width: 300px; font-size: 14;height: 25px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 25px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 25px; font-weight: text-align: center;"></td>
 </tr>
 <br>
 <br>
 
 


 </table>
 <br>
 <br>

 <table border="1">
 <tr>
 <td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;"rowspan="2"></td>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">DEPARTMENT: QUALITY ASSURANCE</td>
<td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;" rowspan="2">page No:</td>
 </tr>
 <tr>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">Permanent Change Control Format</td>
 </tr>
 <br>
 
 </table>
 <br>
 <br>


 <table border="1">
 <tr>
 <td style="width: 50px; font-size: 14; font-weight:bold; text-align: center;">SR No</td>
 <td style="width: 300px; font-size: 14; font-weight:bold; text-align: center;">Action Plan</td>
 <td style="width: 110px; font-size: 14; font-weight:bold; text-align: center;">Assigned To</td>
 <td style="width: 80px; font-size: 14; font-weight:bold; text-align: center;">TCD</td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   6.</td>
 <td style="width: 300px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 


 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   7.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   8.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   9.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">  10.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15;height: 30px; font-weight:  text-align: left;"> Customer Approval Required *</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15;height: 30px; font-weight:  text-align: left;"> Customer Approval & QA Decision</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15;height: 30px; font-weight:  text-align: left;"> File Attachment (If any) *</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15;height: 30px; font-weight:  text-align: left;"> Name / Sign & Date</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 540px; font-size: 15;height: 10px; font-weight:bold; text-align: left;">   Customer Approval</td>

 </tr>
 <tr>
 <td style="width: 230px; font-size: 15;height: 60px; font-weight:  text-align: left;"> Comments</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15;height: 30px; font-weight:  text-align: left;"> Decision on Change Control * </td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15;height: 30px; font-weight:  text-align: left;"> File Attachment (If any) </td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 13;height: 30px; font-weight:bold;  text-align: left;"> Name / Sign & Date</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 540px; font-size: 15;height: 30px; font-weight:bold; text-align: left;">  QA Decision</td>

 </tr>
 <tr>
 <td style="width: 230px; font-size: 13;height: 70px; font-weight:bold;  text-align: left;"> Review Comments *</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15;height: 30px; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 13;height: 30px; font-weight:bold;  text-align: left;"> Category *</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15;height: 30px; font-weight: text-align: left;"> </td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 13;height: 30px; font-weight:bold;  text-align: left;"> Quality Risk Management</td>
 <td style="width: 20px; font-size: 15;height: 30px; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15;height: 30px; font-weight: text-align: left;"> </td>
 </tr>
 

 </table>
 <div>
 </div>
 <div>
 </div>
 <div>
 </div>
 <table border="1">
 <tr>
 <td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;"rowspan="2"></td>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">DEPARTMENT: QUALITY ASSURANCE</td>
<td style="width: 114px; font-size: 13;height: 14px; font-weight: bold; text-align: center;" rowspan="2">page No:</td>
 </tr>
 <tr>
<td style="width: 310px; font-size: 13;height: 14px; font-weight: bold; text-align: center;">Permanent Change Control Format</td>
 </tr>
 <br>
 
 
 </table>
 <br>
 <br>
 <table border="1">
 <tr>
 <td style="width: 230px; font-size: 13; font-weight:bold;  text-align: left;"> Additional Action Plan Proposal (s) 
 Required *</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 540px; font-size: 13;height: 25px; font-weight:bold; text-align: left;">   Proposed Action Plan (s)</td>
 </tr>


 <tr>
 <td style="width: 50px; font-size: 14; font-weight:bold; text-align: center;">SR No</td>
 <td style="width: 300px; font-size: 14; font-weight:bold; text-align: center;">Action Plan</td>
 <td style="width: 110px; font-size: 14; font-weight:bold; text-align: center;">Assigned To</td>
 <td style="width: 80px; font-size: 14; font-weight:bold; text-align: center;">TCD</td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   1.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   2.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   3.</td>
 <td style="width: 300px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14; height: 30px;font-weight: text-align: center;">   4.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   5.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   6.</td>
 <td style="width: 300px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   7.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14; height: 30px;font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   8.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">   9.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 50px; font-size: 14;height: 30px; font-weight: text-align: center;">  10.</td>
 <td style="width: 300px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 110px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 <td style="width: 80px; font-size: 14;height: 30px; font-weight: text-align: center;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15;height: 50px; font-weight:bold;  text-align: left;">  Decision on Change Control *</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15;height: 50px; font-weight:bold;  text-align: left;">  Remark (s) / Reason (s)</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15;height: 50px; font-weight:bold;  text-align: left;">  File Attachment (If any)</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>
 <tr>
 <td style="width: 230px; font-size: 15;height: 50px; font-weight:bold;  text-align: left;">  Name / Sign & Date</td>
 <td style="width: 20px; font-size: 15; font-weight: bold; text-align: center;">:</td>
     <td style="width: 290px; font-size: 15; font-weight: text-align: left;"></td>
 </tr>


 </table>
 ';
		$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	   
	
	   
        
    }

     

}else {
    echo "Invalid Token";
}

$conn->close();
?>