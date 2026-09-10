<?php 

//   ini_set('display_errors', 1);
//  error_reporting(E_ALL);

require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
 $entry_date = date("Y-m-d h:i:s", $timestamp);
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
    
    if ($_GET["type"] == "sopRevHistory") {
        
         $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["sopDoc"])) {
            $file_tmp =$_FILES['sopDoc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['sopDoc']['name'])));
            $file_name = $emp_id."sopDoc.".$file_ext;
            $sopDoc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
        
        $sql = "INSERT INTO sopRevHistory (plant_id , sopId, sopTitle, revNumber, revDate, reasonForRev, SummaryOfChange,revOfficer,
        reviewDate,approval_status,sopDoc) 
        
     VALUES ('".$_GET["plant_id"]."','".$input["sopId"]."', '".$input["sopTitle"]."', '".$input["revNumber"]."', '".$input["revDate"]."', '".$input["reasonForRev"]."',
        '".$input["SummaryOfChange"]."', '".$input["revOfficer"]."', '".$input["reviewDate"]."', '".$input["approval_status"]."', '$sopDoc')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
}

else if ($_GET["type"] == "get_revision") {
    
        $sql = "select * from  sopRevHistory ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
   }
else if ($_GET["type"] == "saveExtension") {
    
     $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["changeControl"])) {
            $file_tmp =$_FILES['changeControl']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['changeControl']['name'])));
            $file_name = $emp_id."changeControl.".$file_ext;
            $changeControl = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
    
        $sql = "INSERT INTO extension (plant_id , changeId, changeTitle, currentEndDate, extReason, extEndDate, changeSummary,requestOfficer,
        reviewing_officer,reviewDate,approval_status,changeControl,reviewComments) 
     VALUES ('".$_GET["plant_id"]."','".$input["changeId"]."', '".$input["changeTitle"]."', '".$input["currentEndDate"]."', '".$input["extReason"]."', '".$input["extEndDate"]."',
        '".$input["changeSummary"]."', '".$input["requestOfficer"]."', '".$input["reviewing_officer"]."', '".$input["reviewDate"]."', '".$input["approval_status"]."', '$changeControl',
        '".$input["reviewComments"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }

else if ($_GET["type"] == "getExtension") {
    
        $sql = "select * from  extension where status='0'";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
   }
   
   else if ($_GET["type"] == "saveExtApproval") {
       $sql="UPDATE extension SET status='".$input["approval_status"]."' where id='".$_GET["changeControlID"]."' ";
    
       if ($conn->query($sql)) {
    
        $sql1 = "INSERT INTO extension_approval (plant_id , changeId, changeTitle, requestId, extEndDate, extReason, approval_officer,
        approve_date,approver_comments) 
        
     VALUES ('".$_GET["plant_id"]."','".$_GET["changeControlID"]."', '".$input["changeTitle"]."', '".$input["requestId"]."', 
     '".$input["extEndDate"]."', '".$input["extReason"]."', '".$input["approval_officer"]."', '".$input["approve_date"]."',
     '".$input["approver_comments"]."')";
     $conn->query($sql1);
     
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }

else if ($_GET["type"] == "getCapaNo") {
    
        $sql = "select capa_no from  capa where status='review3'";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
   }


else if ($_GET["type"] == "getPlan") {
    
        $sql = "select * from  plan1 ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}

else if ($_GET["type"] == "saveVerification") {
    
        $sql = "INSERT INTO capa_verification (plant_id , capaNo, planTitle, department, iniationDate, completionDate, 
        keyIndicators,successCriteria,action,resouce,dept_head,review_date,approval_status,comments,qaHead,qaDate,aqApprovalStatus,qaComments,
        verifyOfficer,verifyDate) 
     VALUES ('".$_GET["plant_id"]."','".$input["capaNo"]."', '".$input["planTitle"]."', '".$input["dept"]."', '".$input["iniationDate"]."', '".$input["completionDate"]."',
        '".$input["keyIndicators"]."', '".$input["successCriteria"]."', '".$input["action"]."', '".$input["resouce"]."', '".$input["dept_head"]."', '".$input["review_date"]."',
        '".$input["approval_status"]."','".$input["comments"]."','".$input["qaHead"]."','".$input["qaDate"]."','".$input["aqApprovalStatus"]."',
        '".$input["qaComments"]."','".$input["verifyOfficer"]."','".$input["verifyDate"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }

else if ($_GET["type"] == "getApprovalPlan") {
    
        $sql = "select * from  plan1 where qa_approval_status='approve'";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
   }
    else if ($_GET["type"] == "save_verify") {
	             $sql="UPDATE plan1 SET verification_status='".$_GET["status"]."',verifyOfficer='".$_GET["emp_id"]."',verifyDate='$entry_date',verifyComments='".$_GET["cmt"]."' where effectiveness_id='".$_GET["capa_no"]."' ";
       if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
	
else if ($_GET["type"] == "getverificationPlan") {
    
        $sql = "select * from  plan1 where verification_status='approve'";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
   }
   
   else if ($_GET["type"] == "save_plan_head") {
	             $sql="UPDATE plan1 SET plan_head_approval_status='".$_GET["status"]."',plan_head_officer='".$_GET["emp_id"]."',plan_headverifyDate='$entry_date',plan_headComments='".$_GET["plan_headComments"]."'
	             where effectiveness_id='".$_GET["capa_no"]."' ";
       if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getPlanHead") {
    
        $sql = "select * from  plan1 where plan_head_approval_status='approve'";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
   }
   else if ($_GET["type"] == "save_closure_plan") {
	             $sql="UPDATE plan1 SET closure_status='".$_GET["status"]."',closure_officer='".$_GET["emp_id"]."',closureDate='$entry_date',verify_closure_comments= '".$_GET["verify_closure_comments"]."',
	             closureComments='".$_GET["closure_comments"]."' where effectiveness_id='".$_GET["capa_no"]."' ";
       if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getClosure") {
    
        $sql = "select * from  plan1 where closure_status='approve'";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
   }
    else if ($_GET["type"] == "save_closure_approval") {
        
         $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["support_document"])) {
            $file_tmp =$_FILES['support_document']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['support_document']['name'])));
            $file_name = $emp_id."support_document.".$file_ext;
            $support_document = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
	             $sql="UPDATE plan1 SET closure_approval_status='".$_GET["status"]."',closure_approval_officer='".$_GET["emp_id"]."',closure_approval_date='$entry_date',verify_closure_approval_comments= '".$_GET["verify_closure_comments"]."',
	             closure_approval_comments='".$_GET["closure_comments"]."',approval_comments='".$_GET["approval_comments"]."' approval_final_doc='$support_document' where effectiveness_id='".$_GET["capa_no"]."' ";
       if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "save_standard_resp") {
    $input = $_POST;
     $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["supporting_doc"])) {
            $file_tmp =$_FILES['supporting_doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['supporting_doc']['name'])));
            $file_name = $emp_id."supporting_doc.".$file_ext;
            $supporting_doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
    
        $sql = "INSERT INTO responsibility (plant_id , dept, emp_name, emp_id, job_title, responsibility1, responsibility2,responsibility3,
        add_responsibility1,add_responsibility2,add_responsibility3,per_metrices1,per_metrices2,per_metrices3,review_by,review_date,approval_status,supporting_doc) 
     VALUES ('".$_GET["plant_id"]."','".$input["dept"]."', '".$input["emp_name"]."', '".$input["emp_id"]."', '".$input["job_title"]."', '".$input["responsibility1"]."',
        '".$input["responsibility2"]."', '".$input["responsibility3"]."', '".$input["add_responsibility1"]."', '".$input["add_responsibility2"]."', '".$input["add_responsibility3"]."', 
        '".$input["per_metrices1"]."','".$input["per_metrices2"]."','".$input["per_metrices3"]."','".$input["review_by"]."','".$input["review_date"]."','".$input["approval_status"]."','$supporting_doc')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
   
   else if ($_GET["type"] == "getVendors") {
    
            $sql = "SELECT * FROM vendor WHERE  plant_id= '".$_GET["plant_id"]."'";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
   }
   
   else if ($_GET["type"] == "save_rev_checklist") {
    $input = $_POST;
     $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["supporting_doc"])) {
            $file_tmp =$_FILES['supporting_doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['supporting_doc']['name'])));
            $file_name = $emp_id."supporting_doc.".$file_ext;
            $supporting_doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
    
        $sql = "INSERT INTO revision_checklist (plant_id , vendor_name, vendor_id, aprl_chck_id, approval_date, rev_id, rev_date,rev_reason,
        revised,review_by,review_date,approval_status,supporing_doc) 
     VALUES ('".$_GET["plant_id"]."','".$input["vendor_name"]."', '".$input["vendor_id"]."', '".$input["aprl_chck_id"]."', '".$input["approval_date"]."', '".$input["rev_id"]."',
        '".$input["rev_date"]."', '".$input["rev_reason"]."', '".$input["revised"]."', '".$input["review_by"]."', '".$input["review_date"]."', 
        '".$input["approval_status"]."','$supporting_doc')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
   
    else if ($_GET["type"] == "save_qualification") {
    $input = $_POST;
     $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["q_document"])) {
            $file_tmp =$_FILES['q_document']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['q_document']['name'])));
            $file_name = $emp_id."q_document.".$file_ext;
            $q_document = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
    
          $sql = "INSERT INTO qualification_details (plant_id , dept, emp_name, emp_id, dob, mo_no, email,q_title,
        c_number,date_quali,isue_authority,q_description,validity,renewal,skills,q_document,org_name,sign_authority,date_issuance,prepared_by,prepared_date,review_by,review_date) 
     VALUES ('".$_GET["plant_id"]."','".$input["dept"]."', '".$input["emp_name"]."', '".$input["emp_id"]."', '".$input["dob"]."', '".$input["mo_no"]."',
        '".$input["email"]."', '".$input["q_title"]."', '".$input["c_number"]."', '".$input["date_quali"]."', '".$input["isue_authority"]."', 
        '".$input["q_description"]."','".$input["validity"]."','".$input["renewal"]."','".$input["skills"]."','$q_document','".$input["org_name"]."',
        '".$input["sign_authority"]."','".$input["date_issuance"]."','".$input["prepared_by"]."','".$input["prepared_date"]."','".$input["review_by"]."','".$input["review_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
   
    else if ($_GET["type"] == "save_analyst") {
    $input = $_POST;
     $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["q_document"])) {
            $file_tmp =$_FILES['q_document']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['q_document']['name'])));
            $file_name = $emp_id."q_document.".$file_ext;
            $q_document = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
    
          $sql = "INSERT INTO analyst_details (plant_id , dept, emp_name, emp_id, dob, mo_no, email,degree,
        institute,study,work_exp,pre_emp,skills,courses,certificate,analytical_tools,q_document,prepared_by,prepared_date,review_by,review_date) 
     VALUES ('".$_GET["plant_id"]."','".$input["dept"]."', '".$input["emp_name"]."', '".$input["emp_id"]."', '".$input["dob"]."', '".$input["mo_no"]."',
        '".$input["email"]."', '".$input["degree"]."', '".$input["institute"]."', '".$input["study"]."', '".$input["work_exp"]."', 
        '".$input["pre_emp"]."','".$input["skills"]."','".$input["courses"]."','".$input["certificate"]."','".$input["analytical_tools"]."',
        '$q_document','".$input["prepared_by"]."','".$input["prepared_date"]."','".$input["review_by"]."','".$input["review_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
   
   else if ($_GET["type"] == "save_customer_info") {
    
        $sql = "INSERT INTO customer_details (plant_id , customer_id, reg_date, title, full_name, gender, 
        dob,email,mo_no,address,c_name,position,comn_pref,mark_pref,about_us,comments,term_condn) 
     VALUES ('".$_GET["plant_id"]."','".$input["customer_id"]."', '".$input["reg_date"]."', '".$input["title"]."', '".$input["full_name"]."', '".$input["gender"]."',
        '".$input["dob"]."', '".$input["email"]."', '".$input["mo_no"]."', '".$input["address"]."', '".$input["c_name"]."', '".$input["position"]."',
        '".$input["comn_pref"]."','".$input["mark_pref"]."','".$input["about_us"]."','".$input["comments"]."','".$input["term_condn"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
else if ($_GET["type"] == "getDetails") {
    
        $sql = "select * from  customer_details ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}

else if ($_GET["type"] == "save_order") {
    
    $input = $_POST;
     $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["attachments"])) {
            $file_tmp =$_FILES['attachments']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachments']['name'])));
            $file_name = $emp_id."attachments.".$file_ext;
            $attachments = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
    
        $sql = "INSERT INTO receiving_order (plant_id , ordernumber, orderdate, c_name, c_contact, c_email, 
        c_phone,p_name,p_id,quantity,unit_price,shipping_address,shipping_method,expected_delivery_date,payment_method,payment_status,payment_amount,order_status,attachments) 
     VALUES ('".$_GET["plant_id"]."','".$input["ordernumber"]."', '".$input["orderdate"]."', '".$input["c_name"]."', '".$input["c_contact"]."', '".$input["c_email"]."',
        '".$input["c_phone"]."', '".$input["p_name"]."', '".$input["p_id"]."', '".$input["quantity"]."', '".$input["unit_price"]."', '".$input["shipping_address"]."',
        '".$input["shipping_method"]."','".$input["expected_delivery_date"]."','".$input["payment_method"]."','".$input["payment_status"]."','".$input["payment_amount"]."','".$input["order_status"]."','$attachments')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
   
else if ($_GET["type"] == "getOrderDetails") {
    
        $sql = "select * from  receiving_order ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}


else if ($_GET["type"] == "save_review_order") {
    
    $input = $_POST;
     $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["attachments"])) {
            $file_tmp =$_FILES['attachments']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachments']['name'])));
            $file_name = $emp_id."attachments.".$file_ext;
            $attachments = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
    
        $sql = "INSERT INTO review_order (plant_id , ordernumber, orderdate, c_name, c_contact, c_email, 
        c_phone,p_name,p_id,quantity,unit_price,pym_method,paym_status,paym_amnt,shiping_add,shipping_method,exp_del_date,order_status,updated_status,attachments) 
     VALUES ('".$_GET["plant_id"]."','".$input["ordernumber"]."', '".$input["orderdate"]."', '".$input["c_name"]."', '".$input["c_contact"]."', '".$input["c_email"]."',
        '".$input["c_phone"]."', '".$input["p_name"]."', '".$input["p_id"]."', '".$input["quantity"]."', '".$input["unit_price"]."', '".$input["pym_method"]."',
        '".$input["paym_status"]."','".$input["paym_amnt"]."','".$input["shiping_add"]."','".$input["shipping_method"]."','".$input["exp_del_date"]."','".$input["order_status"]."','".$input["updated_status"]."','$attachments')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
   
   else if ($_GET["type"] == "getConfirmOrderDetails") {
    
        $sql = "select * from  review_order ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
else if ($_GET["type"] == "save_confirm_order") {
    
    $input = $_POST;
     $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["attachments"])) {
            $file_tmp =$_FILES['attachments']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachments']['name'])));
            $file_name = $emp_id."attachments.".$file_ext;
            $attachments = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
    
        $sql = "INSERT INTO confirm_order (plant_id , ordernumber, orderdate, c_name, c_contact, c_email, 
        c_phone,p_name,p_id,quantity,unit_price,pym_method,paym_status,paym_amnt,shiping_add,shipping_method,exp_del_date,order_status,updated_status,attachments,order_confirmation) 
     VALUES ('".$_GET["plant_id"]."','".$input["ordernumber"]."', '".$input["orderdate"]."', '".$input["c_name"]."', '".$input["c_contact"]."', '".$input["c_email"]."',
        '".$input["c_phone"]."', '".$input["p_name"]."', '".$input["p_id"]."', '".$input["quantity"]."', '".$input["unit_price"]."', '".$input["pym_method"]."',
        '".$input["paym_status"]."','".$input["paym_amnt"]."','".$input["shiping_add"]."','".$input["shipping_method"]."','".$input["exp_del_date"]."','".$input["order_status"]."','".$input["updated_status"]."','$attachments','".$input["order_confirmation"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
   
    else if ($_GET["type"] == "getLog") {
    
        $sql = "select * from  confirm_order ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
else if ($_GET["type"] == "getOrderStatus") {
    
        $sql = "select * from  confirm_order ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}

   
   else if ($_GET["type"] == "save_order_status") {
	             $sql="UPDATE confirm_order SET new_order_status='".$_GET["new_order_status"]."',update_note='".$_GET["update_note"]."'
	             where ordernumber='".$_GET["ordernumber"]."' ";
       if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

else if ($_GET["type"] == "save_rent") {
    
    $input = $_POST;
     $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["file_agreement"])) {
            $file_tmp =$_FILES['file_agreement']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['file_agreement']['name'])));
            $file_name = $emp_id."file_agreement.".$file_ext;
            $file_agreement = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
    
        $sql = "INSERT INTO rent_agreement (plant_id , agreement_title, agreement_id, effective_date, expiry_date, property_type, 
        property_name,property_address,landlord,tenant,administrator,lease_terms,
        rent_amount,security_deposit,payment,file_agreement) 
     VALUES ('".$_GET["plant_id"]."','".$input["agreement_title"]."', '".$input["agreement_id"]."', '".$input["effective_date"]."', '".$input["expiry_date"]."', '".$input["property_type"]."',
        '".$input["property_name"]."', '".$input["property_address"]."', '".$input["landlord"]."', '".$input["tenant"]."', '".$input["administrator"]."', '".$input["lease_terms"]."',
        '".$input["rent_amount"]."','".$input["security_deposit"]."','".$input["payment"]."','$file_agreement')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
   else if ($_GET["type"] == "getDepartments") {
    
            $sql = "SELECT * FROM department order by department_name";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}

else if ($_GET["type"] == "save_requesition") {
    
    // $input = $_POST;
     
        $sql = "INSERT INTO first_aid_requesition (plant_id , date, department, requisition_by, contact_info, manager, 
        item_desc,quantity,uom,priority,req_reason,usage_exp,deli_address,del_date,del_time,manager_approval,mngr_approval_date,
        review_by,review_date,submitted_by,submitted_date) 
     VALUES ('".$_GET["plant_id"]."','".$input["date"]."', '".$input["department"]."', '".$input["requisition_by"]."', '".$input["contact_info"]."', '".$input["manager"]."',
        '".$input["item_desc"]."', '".$input["quantity"]."', '".$input["uom"]."', '".$input["priority"]."', '".$input["req_reason"]."', '".$input["usage_exp"]."',
        '".$input["deli_address"]."','".$input["del_date"]."','".$input["del_time"]."','".$input["manager_approval"]."','".$input["mngr_approval_date"]."',
        '".$input["review_by"]."','".$input["review_date"]."','".$input["submitted_by"]."','".$input["submitted_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }

 else if ($_GET["type"] == "getfirst_aid_material") {
    
            $sql = "SELECT * FROM first_aid_requesition order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}

else if ($_GET["type"] == "save_registration") {
    
    // $input = $_POST;
     
        $sql = "INSERT INTO first_aid_stock_register (plant_id , date, department, Maintained_by, contact_info, item_desc, 
        item_id,uom,intial_stock,received,issued,closing_stock,replenishment_level,recorder_quantity,recorder_date,ex_date,
        ex_items,prepared_by,prepared_date,review_by,review_date) 
     VALUES ('".$_GET["plant_id"]."','".$input["date"]."', '".$input["department"]."', '".$input["Maintained_by"]."', '".$input["contact_info"]."', '".$input["item_desc"]."',
        '".$input["item_id"]."', '".$input["uom"]."', '".$input["intial_stock"]."', '".$input["received"]."', '".$input["issued"]."',
        '".$input["closing_stock"]."','".$input["replenishment_level"]."','".$input["recorder_quantity"]."','".$input["recorder_date"]."','".$input["ex_date"]."',
        '".$input["ex_items"]."','".$input["prepared_by"]."','".$input["prepared_date"]."','".$input["review_by"]."','".$input["review_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
   
   else if ($_GET["type"] == "save_training") {
    
    // $input = $_POST;
     
        $sql = "INSERT INTO first_aid_training (plant_id , date, department, training_by, training_duration, phone_no, 
        email,trainee_name,trainee_phone_no,trainee_email,trainne_training_duration,  topic_covered,materials_provided,written_test,practical_test,remarks,
        review_by,review_date,submitted_by,submitted_date) 
     VALUES ('".$_GET["plant_id"]."','".$input["training_date"]."', '".$input["department"]."', '".$input["training_by"]."', '".$input["training_duration"]."', '".$input["phone_no"]."',
        '".$input["email"]."', '".$input["trainee_name"]."', '".$input["trainee_phone_no"]."', '".$input["trainee_email"]."', '".$input["trainne_training_duration"]."',
        '".$input["topic_covered"]."','".$input["materials_provided"]."','".json_encode($input["written_test"])."','".json_encode($input["practical_test"])."','".$input["remarks"]."',
        '".$input["review_by"]."','".$input["review_date"]."','".$input["submitted_by"]."','".$input["submitted_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
   
   else if ($_GET["type"] == "getfirst_aid_training") {
    
            $sql = "SELECT * FROM first_aid_training order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}

   else if ($_GET["type"] == "save_procedure") {
    
    // $input = $_POST;
     
        $sql = "INSERT INTO first_aid_procedure (plant_id , injured_person_name, incident_date, incident_time, injury_type, incident_location, 
        detailed_desc,action,first_aid_type,witness_name,witness_phone_no,  emergency_phone_no,observation,full_name,contact_email,contact_phone_no)
     VALUES ('".$_GET["plant_id"]."','".$input["injured_person_name"]."', '".$input["incident_date"]."', '".$input["incident_time"]."', '".$input["injury_type"]."', '".$input["incident_location"]."',
        '".$input["detailed_desc"]."', '".$input["action"]."', '".$input["first_aid_type"]."', '".$input["witness_name"]."', '".$input["witness_phone_no"]."',
        '".$input["emergency_phone_no"]."','".$input["observation"]."','".$input["full_name"]."','".$input["contact_email"]."','".$input["contact_phone_no"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
   
     else if ($_GET["type"] == "get_procedure_log") {
    
            $sql = "SELECT * FROM first_aid_procedure order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}

else if ($_GET["type"] == "save_usage_record") {
    
    // $input = $_POST;
     
        $sql = "INSERT INTO first_aid_usage_record (plant_id , location, incident_desc, injured_person_name, incident_time, first_aid_time, 
        name_of_first_aid_provider,phone_no,email,first_aid_desc,first_aid_item,  other_action,medical_assistance,medical_assistance_contacted,outcome,
        prepared_by,prepared_date,review_by,review_date)
     VALUES ('".$_GET["plant_id"]."','".$input["location"]."', '".$input["incident_desc"]."', '".$input["injured_person_name"]."', '".$input["incident_time"]."', '".$input["first_aid_time"]."',
        '".$input["name_of_first_aid_provider"]."', '".$input["phone_no"]."', '".$input["email"]."', '".$input["first_aid_desc"]."', '".$input["first_aid_item"]."',
        '".$input["other_action"]."','".$input["medical_assistance"]."','".$input["medical_assistance_contacted"]."','".$input["outcome"]."',
        '".$input["prepared_by"]."', '".$input["prepared_date"]."', '".$input["review_by"]."', '".$input["review_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
   
   else if ($_GET["type"] == "saveScrapSales") {
    
    // $input = $_POST;
     
        $sql = "INSERT INTO scrap_sales (plant_id , scrap_items, scrap_date, quantity, scrap_reason, source, 
        source_location,responsible_emp,scrap_disposal,disposal_date,comments,  aproved_by,approved_date)
     VALUES ('".$_GET["plant_id"]."','".$input["scrap_items"]."', '".$input["scrap_date"]."', '".$input["quantity"]."', '".$input["scrap_reason"]."', '".$input["source"]."',
        '".$input["source_location"]."', '".$input["responsible_emp"]."', '".$input["scrap_disposal"]."', '".$input["disposal_date"]."', '".$input["comments"]."',
        '".$input["aproved_by"]."','".$input["approved_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }
   
    else if ($_GET["type"] == "getScrapReport") {
    
            $sql = "SELECT * FROM scrap_sales order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}

}

$conn->close();
?>