<?php
// ini_set('display_errors', 1);
//  error_reporting(E_ALL);
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    // header("Access-Control-Allow-Origin: *"); // Allow all origins. Replace '*' with a specific domain for security.
header("Access-Control-Allow-Methods: POST, GET"); // Specify allowed methods.
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Specify allowed headers.
header("Access-Control-Allow-Credentials: true"); // If credentials (e.g., cookies) are involved.

    $token = $_GET["token"];
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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "saveCAPA1") {
        $input = $_POST;
        
        $target_dir = "../../../upload/capa/";

        $id = date("YmdHis", $timestamp);
    
        $file_name = "";
    	if(isset($_FILES["document"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['document']['name'])));
        	$file_name = $_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["document"]["tmp_name"], $target_dir.$file_name);
    	}
    	if(isset($_FILES["document1"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['document1']['name'])));
        	$file_name1 = $_GET['plant_id'].'2'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["document1"]["tmp_name"], $target_dir.$file_name1);
    	}
    	if(isset($_FILES["document2"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['document2']['name'])));
        	$file_name2 = $_GET['plant_id'].'3'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["document2"]["tmp_name"], $target_dir.$file_name2);
    	}
    	
  		$sql = "INSERT INTO capa (capa_ref_no,target_date,ref_qms_doc_no,first_actd,second_actd,categories,capa_deatils,capa_deatils_document,
		status_corrective_action,status_corrective_document,status_preventive_action,status_preventive_document,root_cause,
		entry_date,entry_by,capa_from) VALUES 
		( '".$input["capa_ref_no"]."','".$input["target_date"]."','".$input["ref_qms_doc_no"]."','".$input["first_actd"]."',
		'".$input["second_actd"]."','".json_encode($input['origin'])."','".$input["capa_deatils"]."','$file_name',
		'".$input["status_corrective_action"]."','$file_name1','".$input["status_preventive_action"]."',
		'$file_name2','".$input["root_cause"]."',
		'".$entry_date."','".$_GET["emp_id"]."','".$_GET["capaFrom"]."')";
		
		if ($conn->query($sql)) {
		    $CAPA_ID = $conn->insert_id; 
		    if($_GET["capaFrom"]=='Incident'){
		        $sql1="update new_incident set capa_id='$CAPA_ID' where id='".$_GET["incident_id"]."'";
		        $conn->query($sql1);
		    }
		
		    
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	}
 
   else if ($_GET["type"] == "saveMehaCAPA") {
        $input = $_POST;
 
    	
//   		$sql = "INSERT INTO capa (capa_ref_no,CapaOccuredDept,Initiate_date,Source_doc,DesNonConformity,AssessmentImpact,
// 		entry_date,entry_by) VALUES 
// 		( '".$input["capa_ref_no"]."','".$input["CapaOccuredDept"]."','".$input["Initiate_date"]."','".$input["Source_doc"]."','".$input["DesNonConformity"]."',
// 		'".$input["AssessmentImpact"]."',
// 		'".$entry_date."','".$_GET["emp_id"]."')";
		
            		
            	$sql = "INSERT INTO capa (
                CapaOccuredDept, Initiate_date, Source_doc, DesNonConformity, AssessmentImpact,
                entry_date, entry_by, CustomerComplaint, InternalAudits, TrendingData, OutOfSpecification,
                RegulatoryInspection, RiskAssessment, DeviationIncident, ManagementReview, StaffObservation,
                ProcessPerformanceMonitoring, Other
            ) VALUES (
                 '".$input["CapaOccuredDept"]."', '".$input["Initiate_date"]."', '".$input["Source_doc"]."', 
                '".$input["DesNonConformity"]."', '".$input["AssessmentImpact"]."', '".$entry_date."', '".$_GET["emp_id"]."', 
                '".($input["Customer Complaint"] ? "Yes" : "No")."', '".($input["Internal Audits"] ? "Yes" : "No")."', 
                '".($input["Trending the Data"] ? "Yes" : "No")."', '".($input["Out of Specification"] ? "Yes" : "No")."', 
                '".($input["Regulatory inspection"] ? "Yes" : "No")."', '".($input["Risk Assessment"] ? "Yes" : "No")."', 
                '".($input["Deviation/ Incident"] ? "Yes" : "No")."', '".($input["Management Review"] ? "Yes" : "No")."', 
                '".($input["Staff Observation"] ? "Yes" : "No")."', '".($input["Process Performance Monitoring"] ? "Yes" : "No")."', 
                '".($input["Other"] ? "Yes" : "No")."'
            )";



		if ($conn->query($sql)) {
		    $CAPA_ID = $conn->insert_id; 
		    if($_GET["capaFrom"]=='Incident'){
		        $sql1="update new_incident set capa_id='$CAPA_ID' where id='".$_GET["incident_id"]."'";
		        $conn->query($sql1);
		    }
		
		    
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	}
	
	  else if ($_GET["type"] == "saveCAPAextention") {
	      // Allow CORS
header("Access-Control-Allow-Origin: *"); // Replace * with specific domains if needed
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Specify allowed methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Specify allowed headers

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Return 200 OK for the preflight request
    http_response_code(200);
    exit();
}



$sql="update capa set status='New Capa Initiated' where id ='".$_GET['capa_id']."' ";
if ($conn->query($sql)) {
$sql1 = "INSERT INTO capa_extention( capa_id, capa_no, ref_qms_doc_no, department, first_actd, second_actd,
        Extention_req_intiated_by, Justification_for_extention, extention_approval_status, remark,initiate_by,initiate_on) VALUES (
        '".$_GET['capa_id']."',
        '".$input['capa_no']."','".$input['ref_qms_doc_no']."','".$input['department']."',
        '".$input['first_actd']."','".$input['second_actd']."','".$input['Extention_req_intiated_by']."',
        '".$input['Justification_for_extention']."','".$input['extention_approval_status']."','".$input['remark']."','".$_GET['emp_id']."','$entry_date')";
        $conn->query($sql1);

    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => $conn->error]);
}
 

	  }
	  else if ($_GET["type"] == "save_plan") {
	      
	      $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["supporting_doc"])) {
            $file_tmp =$_FILES['supporting_doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['supporting_doc']['name'])));
            $file_name = $emp_id."supporting_doc.".$file_ext;
            $supporting_doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
	      
  $sql = "INSERT INTO plan1 ( plant_id,supporting_document,department_head_contains,approvel_status,review_date,department_head,prepared_by,resource_req,strategies_action,criteria_success,key_Performance,proposed_completion_date,initiation_date,responsible_department,plan_title,effectiveness_id) VALUES
  ( '".$_GET["plant_id"]."','$supporting_doc','".$input["department_head_contains"]."', '".$input["approvel_status"]."', '".$input["review_date"]."', '".$input["department_head"]."', 
        '".$input["prepared_by"]."', '".$input["resource_req"]."', '".$input["strategies_action"]."', '".$input["criteria_success"]."', '".$input["key_Performance"]."', '".$input["proposed_completion_date"]."', '".$input["initiation_date"]."', '".$input["responsible_department"]."', '".$input["plan_title"]."', '".$input["effectiveness_id"]."')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
	  else if ($_GET["type"] == "save_approvel") {
	      
	       $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["supporing_doc"])) {
            $file_tmp =$_FILES['supporing_doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['supporing_doc']['name'])));
            $file_name = $emp_id."supporing_doc.".$file_ext;
            $supporing_doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
	      
	             $sql="UPDATE plan1 SET status='".$_GET["status"]."',comments='".$_GET["comments"]."'approval_document='$supporing_doc' where effectiveness_id='".$_GET["capa_no"]."' ";
       if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
	  else if ($_GET["type"] == "save_extededCAPANEW") {
	      
	            $sql = "UPDATE capa_extention SET
                    doc_name = '".$input['doc_name']."',
                    doc_no = '".$input['doc_no']."',
                    capa_for = '".$input['capa_for']."',
                    productMaterial_code = '".$input['productMaterial_code']."',
                    batch_no = '".$input['batch_no']."',
                    sapCode = '".$input['sapCode']."',
                    capa_dtl_stated_report = '".$input['capa_dtl_stated_report']."',
                    Capa_Implimentation_Date = '".$input['Capa_Implimentation_Date']."',
                    Capa_Implimentation_From_Batch_No = '".$input['Capa_Implimentation_From_Batch_No']."',
                    Verification_Date = '".$input['Verification_Date']."',
                    category = '".$input['origin']."',
                    status = 'EXTENd_QA_FINAL_REVIEW',
                    comments = '".$input['comment']."'
                WHERE id='".$_GET['extend_capa_id']."'";
  if ($conn->query($sql)) {
         $sql = "UPDATE capa SET status='EXTENd_QA_FINAL_REVIEW'  WHERE id='".$_GET['capa_id']."'";
         $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

	
	
	
	else if ($_GET["type"] == "getPendingCAPA") {
	    $output = array();
	   // $sql = "SELECT * FROM capa WHERE department='".$_GET["department"]."' AND status='pending' ORDER BY id DESC";
	    $sql = "SELECT * FROM capa WHERE   status='Initiated' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	           // $row["document_file"] = "upload/capa/".$row["document_file"];
	            $row["origin"] = json_decode($row["categories"]);
 	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	else if ($_GET["type"] == "getcheckedCAPA") {
	    $output = array();
	   // $sql = "SELECT * FROM capa WHERE department='".$_GET["department"]."' AND status='pending' ORDER BY id DESC";
	    $sql = "SELECT * FROM capa WHERE   status='Checked' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	           // $row["document_file"] = "upload/capa/".$row["document_file"];
	            $row["origin"] = json_decode($row["categories"]);
	            $row["imidiatActions"] = json_decode($row["imidiatActions"]);
	            $row["needVerify"] = json_decode($row["needVerify"]);
 	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	else if ($_GET["type"] == "getExteentionCAPA") {
	    $output = array();
	   // $sql = "SELECT * FROM capa WHERE department='".$_GET["department"]."' AND status='pending' ORDER BY id DESC";
	    $sql = "SELECT * FROM capa WHERE   status='New CAPA Issue' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	           // $row["document_file"] = "upload/capa/".$row["document_file"];
	            $row["origin"] = json_decode($row["categories"]);
	            $row["imidiatActions"] = json_decode($row["imidiatActions"]);
	            $row["needVerify"] = json_decode($row["needVerify"]);
 	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	
	else if ($_GET["type"] == "getCAPAlog") {
	    $output = array();
	   // $sql = "SELECT * FROM capa WHERE department='".$_GET["department"]."' AND status='pending' ORDER BY id DESC";
	    $sql = "SELECT * FROM capa ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	           // $row["document_file"] = "upload/capa/".$row["document_file"];
	            $row["origin"] = json_decode($row["categories"]);
	            $row["imidiatActions"] = json_decode($row["imidiatActions"]);
	            $row["needVerify"] = json_decode($row["needVerify"]);
 	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	
	else if ($_GET["type"] == "getExtendedCAPA") {
	    $output = array();
	   // $sql = "SELECT * FROM capa WHERE department='".$_GET["department"]."' AND status='pending' ORDER BY id DESC";
	    $sql = "SELECT 
                        a.*, b.id as b_id,
                        b.capa_id AS b_capa_id, 
                        b.capa_no AS b_capa_no, 
                        b.ref_qms_doc_no AS b_ref_qms_doc_no, 
                        b.department AS b_department, 
                        b.first_actd AS b_first_actd, 
                        b.second_actd AS b_second_actd, 
                        b.Extention_req_intiated_by AS b_Extention_req_intiated_by, 
                        b.Justification_for_extention AS b_Justification_for_extention, 
                        b.extention_approval_status AS b_extention_approval_status, 
                        b.remark AS b_remark
                    FROM 
                        capa a
                    LEFT JOIN 
                        capa_extention b 
                    ON 
                        a.id = b.capa_id AND b.status = 'pending'
                    WHERE 
                        a.status = 'new capa Initiated'
                    ORDER BY 
                        a.id DESC;
";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	           // $row["document_file"] = "upload/capa/".$row["document_file"];
	            $row["origin"] = json_decode($row["categories"]);
	            $row["imidiatActions"] = json_decode($row["imidiatActions"]);
	            $row["needVerify"] = json_decode($row["needVerify"]);
 	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	else if ($_GET["type"] == "getExtendedCAPAReview") {
	    $output = array();
	   // $sql = "SELECT * FROM capa WHERE department='".$_GET["department"]."' AND status='pending' ORDER BY id DESC";
	    $sql = "SELECT 
                        a.*, b.id as b_id,
                        b.capa_id AS b_capa_id, 
                        b.capa_no AS b_capa_no, 
                        b.ref_qms_doc_no AS b_ref_qms_doc_no, 
                        b.department AS b_department, 
                        b.first_actd AS b_first_actd, 
                        b.second_actd AS b_second_actd, 
                        b.Extention_req_intiated_by AS b_Extention_req_intiated_by, 
                        b.Justification_for_extention AS b_Justification_for_extention, 
                        b.extention_approval_status AS b_extention_approval_status, 
                        b.remark AS b_remark,
                        b.doc_name as b_doc_name,
                        b.doc_no as b_doc_no,
                        b.capa_for as b_capa_for,
                        b.productMaterial_code as b_productMaterial_code,
                        b.batch_no as b_batch_no,
                        b.sapCode as b_sapCode,
                        b.capa_dtl_stated_report as b_capa_dtl_stated_report,
                        b.Capa_Implimentation_Date as b_Capa_Implimentation_Date,
                        b.Capa_Implimentation_From_Batch_No as b_Capa_Implimentation_From_Batch_No,
                        b.Verification_Date as b_Verification_Date,
                        b.comments as b_comment,
                        b.category as b_category
                    FROM 
                        capa a
                    LEFT JOIN 
                        capa_extention b 
                    ON 
                        a.id = b.capa_id AND b.status = 'EXTENd_QA_FINAL_REVIEW'
                    WHERE 
                        a.status = 'EXTENd_QA_FINAL_REVIEW'
                    ORDER BY 
                        a.id DESC;
";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	           // $row["document_file"] = "upload/capa/".$row["document_file"];
	            $row["origin"] = json_decode($row["categories"]);
	            $row["imidiatActions"] = json_decode($row["imidiatActions"]);
	            $row["needVerify"] = json_decode($row["needVerify"]);
	            $row["b_category"] = json_decode($row["b_category"]);
 	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
 
	else if ($_GET["type"] == "getcheckedCAPADeptHead") {
	    $output = array();
	   // $sql = "SELECT * FROM capa WHERE department='".$_GET["department"]."' AND status='pending' ORDER BY id DESC";
	    $sql = "SELECT * FROM capa WHERE   status='Close' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	           // $row["document_file"] = "upload/capa/".$row["document_file"];
	            $row["origin"] = json_decode($row["categories"]);
	            $row["imidiatActions"] = json_decode($row["imidiatActions"]);
	            $row["needVerify"] = json_decode($row["needVerify"]);
 	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	else if ($_GET["type"] == "checkCAPA") {
	   
	     $sql = "UPDATE capa SET check_by='".$_GET["emp_id"]."', check_on='$entry_date', status='".$_GET["status"]."',
	    imidiatActions='".json_encode($input['imidiatAction'])."',needVerify='".json_encode($input['needVerify'])."',
        desc_immidiateAction='".$input['desc_immidiateAction']."',reason_first_alt_tcd='".$input['reason_first_alt_tcd']."',
        reason_Second_alt_tcd='".$input['reason_Second_alt_tcd']."' WHERE capa_no='".$_GET["capa_no"]."'";
	    if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
 
	}
	else if ($_GET["type"] == "checkDeptHead") {
	   
	     $sql = "UPDATE capa SET dept_head='".$_GET["emp_id"]."', dept_head_date='$entry_date', status='".$_GET["status"]."',
        close_cmt_capa_hod='".$input['close_cmt_capa_hod']."',new_capa_dtl='".$input['new_capa_dtl']."',
        qa_head_remark='".$input['qa_head_remark']."' WHERE capa_no='".$_GET["capa_no"]."'";
	    if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
 
	}
	else if ($_GET["type"] == "checkQAHEAD") {
	   
	     $sql = "UPDATE capa SET qa_head='".$_GET["emp_id"]."', qa_head_review_date='$entry_date', status='".$_GET["status"]."',
        close_cmt_capa_QAHEAD='".$input['close_cmt_capa_QAHEAD']."' WHERE capa_no='".$_GET["capa_no"]."'";
	    if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
 
	}
	else if ($_GET["type"] == "getReview1CAPA") {
	    $output = array();
	    $sql = "SELECT * FROM capa WHERE user_no='".$_GET["user_no"]."' AND status='checked' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["origin"] = json_decode($row["origin"]);
	            $row["plan"] = json_decode($row["plan"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} else if ($_GET["type"] == "saveReview1") {
	    $sql = "UPDATE capa SET status='Review1', prev_occured='".$input["prev_occured"]."', pcapa_no='".$input["pcapa_no"]."', changecontrol='".$input["changecontrol"]."', comment='".$input["comment"]."', review1_by='".$_GET["emp_id"]."', review1_date='$entry_date' WHERE capa_no='".$_GET["capa_no"]."'";
	    if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	} else if ($_GET["type"] == "getReview2CAPA") {
	    $output = array();
	    $sql = "SELECT * FROM capa WHERE user_no='".$_GET["user_no"]."' AND status='Review1' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["origin"] = json_decode($row["origin"]);
	            $row["plan"] = json_decode($row["plan"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} else if ($_GET["type"] == "saveReview2") {
	    $sql = "UPDATE capa SET status='Review2',due_date='".$input["due_date"]."', review2_comment='".$_GET["review2_comment"]."',extension='".$input["extension"]."',capa_extension='".json_encode($input["capa_extension"])."',review2_by='".$_GET["emp_id"]."', review2_date='$entry_date' WHERE capa_no='".$_GET["capa_no"]."'";
	    if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	} else if ($_GET["type"] == "getReview3CAPA") {
	    $output = array();
	    $sql = "SELECT * FROM capa WHERE user_no='".$_GET["user_no"]."' AND status='Review2' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	         while ($row = $result->fetch_assoc()) {
	            $row["origin"] = json_decode($row["origin"]);
	            $row["plan"] = json_decode($row["plan"]);
	             $row["capa_extension"] = json_decode($row["capa_extension"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} else if ($_GET["type"] == "saveReview3") {
	    $sql = "UPDATE capa SET status='review3',review3_comment='".$_GET["review3_comment"]."', review3_by='".$_GET["emp_id"]."', review3_date='$entry_date' WHERE capa_no='".$_GET["capa_no"]."'";
	    if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	}else if ($_GET["type"] == "getApproval1") {
	    $output = array();
	    $sql = "SELECT * FROM capa WHERE user_no='".$_GET["user_no"]."' AND status='Review3' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	         while ($row = $result->fetch_assoc()) {
	            $row["origin"] = json_decode($row["origin"]);
	            $row["plan"] = json_decode($row["plan"]);
	             $row["capa_extension"] = json_decode($row["capa_extension"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}else if ($_GET["type"] == "updateApproval1") {
	    $sql = "UPDATE capa SET status='Approval1',approval1_by='".$_GET["emp_id"]."', approval1_date='$entry_date' WHERE capa_no='".$_GET["capa_no"]."'";
	    if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	}else if ($_GET["type"] == "getApproval2") {
	    $output = array();
	    $sql = "SELECT * FROM capa WHERE user_no='".$_GET["user_no"]."' AND status='Approval1' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	         while ($row = $result->fetch_assoc()) {
	            $row["origin"] = json_decode($row["origin"]);
	            $row["plan"] = json_decode($row["plan"]);
	             $row["capa_extension"] = json_decode($row["capa_extension"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}else if ($_GET["type"] == "updateApproval2") {
	    $sql = "UPDATE capa SET status='approval2',approval2_by='".$_GET["emp_id"]."', approval2_date='$entry_date' WHERE capa_no='".$_GET["capa_no"]."'";
	    if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	}else if ($_GET["type"] == "getCAPAclosing") {
	    $output = array();
	    $sql = "SELECT * FROM capa WHERE status='Review3' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	         while ($row = $result->fetch_assoc()) {
	            $row["origin"] = json_decode($row["origin"]);
	            $row["plan"] = json_decode($row["plan"]);
	            $row["capa_extension"] = json_decode($row["capa_extension"]);
	            
	            $plans = $row["plan"];
	            for ($i = 0; $i < count($plans); $i++) {
	                $plan = $plans[$i];
	                if ($plan->due_date < $entry_date) {
	                    $plan->isextension = 'YES';
	                } else {
	                    $plan->isextension = 'NO';
	                }
	                $plans[$i] = $plan;
	            }
	            $row["plan"] = $plans;
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
            move_uploaded_file($file_tmp,"../../upload/incident/".$file_name);
            
            $sql = "INSERT INTO capa_file (capa_no, particular, file) VALUES ('".$_GET["capa_no"]."', '".$_POST["particular"]."', '$attachment')";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } else {
            echo "{\"status\":\"failed\"}";
        }
	} else if ($_GET["type"] == "deleteAttachment") {
	    $sql = "UPDATE capa_file SET status='DELETED' WHERE id='".$_GET["id"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} else if ($_GET["type"] =="getCapaDetails") {
	    $output = array();
	    $sql = "SELECT * FROM capa WHERE user_no='".$_GET["user_no"]."' AND capa_no='".$_GET["capa_no"]."'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	           $row["origin"] = json_decode($row["origin"]);
	            $row["plan"] = json_decode($row["plan"]);
	             $row["capa_extension"] = json_decode($row["capa_extension"]);
	            $output1 = array();
	            $sql1 = "SELECT * FROM capa_file WHERE capa_no='".$row["capa_no"]."' AND status='ACTIVE'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $row["isextension"] = 'NO';
	            $plans = $row["plan"];
	            for ($i = 0; $i < count($plans); $i++) {
	                $plan = $plans[$i];
	                if ($plan->due_date < $entry_date) {
	                    $plan->isextension = 'YES';
	                    $row["isextension"] = "YES";
	                } else {
	                    $plan->isextension = 'NO';
	                }
	                $plans[$i] = $plan;
	            }
	            $row["plan"] = $plans;
	            echo json_encode($row);
	        }
	    } else {
	        echo "{}";
	    }
	} else if ($_GET["type"] == "saveClosing") {
	    $sql = "UPDATE capa SET close_by='".$_GET["emp_id"]."', close_date='$entry_date', status='CLOSE' WHERE capa_no='".$_GET["capa_no"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} else if ($_GET["type"] == "getCAPAclosingApproval") {
	    $output = array();
	    $sql = "SELECT * FROM capa WHERE status='CLOSE' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	         while ($row = $result->fetch_assoc()) {
	            $row["origin"] = json_decode($row["origin"]);
	            $row["plan"] = json_decode($row["plan"]);
	            $row["capa_extension"] = json_decode($row["capa_extension"]);
	            
	            $plans = $row["plan"];
	            for ($i = 0; $i < count($plans); $i++) {
	                $plan = $plans[$i];
	                if ($plan->due_date < $entry_date) {
	                    $plan->isextension = 'YES';
	                } else {
	                    $plan->isextension = 'NO';
	                }
	                $plans[$i] = $plan;
	            }
	            $row["plan"] = $plans;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM capa_file WHERE capa_no='".$row["capa_no"]."' AND status='ACTIVE'";
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
	} else if ($_GET["type"] == "capaClosingApproval") {
	    $sql = "UPDATE capa SET closing_approval_by='".$_GET["emp_id"]."', closing_approval_date='$entry_date', status='CAPA CLOSING APPROVAL' WHERE capa_no='".$_GET["capa_no"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} else if ($_GET["type"] == "getCAPAclosingVerification") {
	    $output = array();
	    $sql = "SELECT * FROM capa WHERE status='CAPA CLOSING APPROVAL' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	         while ($row = $result->fetch_assoc()) {
	            $row["origin"] = json_decode($row["origin"]);
	            $row["plan"] = json_decode($row["plan"]);
	            $row["capa_extension"] = json_decode($row["capa_extension"]);
	            
	            $plans = $row["plan"];
	            for ($i = 0; $i < count($plans); $i++) {
	                $plan = $plans[$i];
	                if ($plan->due_date < $entry_date) {
	                    $plan->isextension = 'YES';
	                } else {
	                    $plan->isextension = 'NO';
	                }
	                $plans[$i] = $plan;
	            }
	            $row["plan"] = $plans;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM capa_file WHERE capa_no='".$row["capa_no"]."' AND status='ACTIVE'";
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
	} else if ($_GET["type"] == "capaVerify") {
	    $sql = "UPDATE capa SET verify_comment='".$input["verify_comment"]."',effectiveness='".$input["effectiveness"]."', effectiveness_no='".$input["effectiveness_no"]."', close_verify_date='$entry_date', close_verify_by='".$_GET["emp_id"]."', status='CLOSE VERIFY' WHERE capa_no='".$_GET["capa_no"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} else if ($_GET["type"] == "getCAPAclosingVerificationApproval") {
	    $output = array();
	    $sql = "SELECT * FROM capa WHERE status='CLOSE VERIFY' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	         while ($row = $result->fetch_assoc()) {
	            $row["origin"] = json_decode($row["origin"]);
	            $row["plan"] = json_decode($row["plan"]);
	            $row["capa_extension"] = json_decode($row["capa_extension"]);
	            
	            $plans = $row["plan"];
	            for ($i = 0; $i < count($plans); $i++) {
	                $plan = $plans[$i];
	                if ($plan->due_date < $entry_date) {
	                    $plan->isextension = 'YES';
	                } else {
	                    $plan->isextension = 'NO';
	                }
	                $plans[$i] = $plan;
	            }
	            $row["plan"] = $plans;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM capa_file WHERE capa_no='".$row["capa_no"]."' AND status='ACTIVE'";
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
	} else if ($_GET["type"] == "capaVerifyApproval") {
	    $sql = "UPDATE capa SET close_verify_approve_date='$entry_date', close_verify_approve_by='".$_GET["emp_id"]."', status='Closed' WHERE capa_no='".$_GET["capa_no"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} else if ($_GET["type"] == "updateApproval2") {
	    $sql = "UPDATE capa SET status='approval2',approval2_by='".$_GET["emp_id"]."', approval2_date='$entry_date' WHERE capa_no='".$_GET["capa_no"]."'";
	    if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	}else if ($_GET["type"] == "getCapalog") {
	    $output = array();
	    //$sql = "SELECT *, DATE(initiate_date) as initiate_date, DATE(close_date) as close_date FROM capa WHERE status='Closed' ";
	    //AND DATE(initiate_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
	    $sql = "SELECT *, DATE(initiate_date) as initiate_date, DATE(close_date) as close_date FROM capa 
	    WHERE user_no='".$_GET["user_no"]."' AND DATE(initiate_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	         while ($row = $result->fetch_assoc()) {
	            $row["origin"] = json_decode($row["origin"]);
	            $row["plan"] = json_decode($row["plan"]);
	            $row["capa_extension"] = json_decode($row["capa_extension"]);
	            
	            $plans = $row["plan"];
	            for ($i = 0; $i < count($plans); $i++) {
	                $plan = $plans[$i];
	                if ($plan->due_date < $entry_date) {
	                    $plan->isextension = 'YES';
	                } else {
	                    $plan->isextension = 'NOT APPLICABLE';
	                }
	                $plans[$i] = $plan;
	            }
	            $row["plan"] = $plans;
	             
	            $output1 = array();
	            $sql1 = "SELECT * FROM capa_file WHERE capa_no='".$row["capa_no"]."' AND status='ACTIVE'";
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
	
		else if($_GET["type"]=="downloadLog"){
	     $_GET['filename'] = 'capalog '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
	     	    $sql = "SELECT * FROM `new_incident` LEFT JOIN `capa` ON `new_incident`.`id` = `capa`.`id`;";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        $row = $result->fetch_assoc();{
	      $html.='
	      <h1 style="text-align:center;color:brown">Incident Log</h1>
	      <table border="1" cellspacing="0" cellpadding="5" style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td>Date Of INR</td>
                        <td colspan="3">'.$row['Date_Of_INR'].'</td>
                    </tr>
                    <tr>
                        <td>Name of Department</td>
                        <td colspan="3">'.$row['Name_of_Department'].'</td>
                    </tr>
                    <tr>
                        <td>INR No</td>
                        <td>'.$row['INR_No'].'</td>
                        <td>CAPA Ref No.</td>
                        <td>'.$row['CAPA_Ref_No'].'</td>
                    </tr>
                    <tr>
                        <td>Ref QMS Document No.</td>
                        <td>'.$row['Ref_QMS_Document_No'].'</td>
                        <td>Target Date</td>
                        <td>' . date('d-m-Y', strtotime($row['Target_Date'])) .'</td> 
                    </tr>
                    <tr>
                        <td>Incident Related To</td>
                        <td colspan="3">'.$row['incident_relateds'].'</td>
                    </tr>
                    <tr>
                        <td>Classification of INR</td>
                        <td colspan="3">'.$row['classification_inr'].'</td>
                    </tr>
                    <tr>
                        <td>Potential Impact on</td>
                        <td colspan="3">'.$row['potential_impact'].'</td>
                    </tr>
                    <tr>
                        <td>INR Details</td>
                        <td colspan="3">'.$row['INR_Details'].'</td>
                    </tr>
                    <tr>
                        <td>Attach Supportive Evidence/Document</td>
                        <td colspan="3">'.$row['incident_supportive_document'].'</td>
                    </tr>
                    <tr>
                        <td>Probable Cause/Root Cause for Incidence</td>
                        <td colspan="3">'.$row['Probable_Cause_Root_Cause_for_Incidence'].'</td>
                    </tr>
                    <tr>
                        <td>Corrective Action</td>
                        <td colspan="3">'.$row['Corrective_Action'].'</td>
                    </tr>
                    <tr>
                        <td>Preventive Action</td>
                        <td colspan="3">'.$row['Preventive_Reference_Document'].'</td>
                    </tr>
                    <tr>
                        <td>Root Cause Identified</td>
                        <td colspan="3">'.$row['Root_Cause_Identified'].'</td>
                    </tr>
                    <tr>
                        <td>Immediate Actions</td>
                        <td colspan="3">'.$row['specify_details'].'</td>
                    </tr>
                    <tr>
                        <td>Need to Verify</td>
                        ';
                                $needs_verify = json_decode($row['needs_verify']); 
                                    foreach ($needs_verify as $item) {
                         $html.= '<td colspan="3"> ' . $item . '
                        </td> ';
                        }
	                   
                        
                  $html.='  </tr>
                    <tr>
                        <td>Description of Immediate Action</td>
                        <td colspan="3">'.$row['Description_of_Immediate_Action'].'</td>
                    </tr>
                    <tr>
                        <td>Reason/Justification of First Alternate TCD</td>
                        <td colspan="3">'.$row['Reason_Justification_of_First_Alternate_TCD'].'</td>
                    </tr>
                    <tr>
                        <td>Reason/Justification of Second Alternate TCD</td>
                        <td colspan="3">'.$row['Reason_Justification_of_Second_Alternate_tcd'].'</td>
                    </tr>
                    <tr>
                        <td colspan="2">Item</td>
                        <td>Recommendation</td>
                        <td>Remark</td>
                    </tr>';
                     $json_obj = $row['evaluation'];
                $array = json_decode($json_obj, true);
             
                foreach ($array as $values)
                {
                    $item = $values['item'];
                    $Recommendation = $values['Recommendation'];
                    $remark = $values['remark'];
                
                  	$html.='  
                    <tr>
                        <td  colspan="2">' . $item . '</td>
                        <td>' . $Recommendation . '</td>
                        <td>' . $remark . '</td>
                    </tr>';}
                $html.='  </table>
                <div></div>
                <table border="1"  cellpadding="5"  style="width: 100%;  text-align: left;">
                    <tr>
                        <td>CAPA Required</td>
                        <td>'.$row['CAPA'].'</td>
                        <td>Closure of Incidence/Non Conformance</td>
                        <td>'.$row['CAPA_Impliment'].'</td>
                    </tr>
                    <tr>
                        <td>Training to relevant Personnel imparted</td>
                        <td>'.$row['training'].'</td>
                        <td>Closing Date of INR</td>
                        <td>'.$row['Closing_date'].'</td>
                    </tr>
                    <tr>
                        <td>Justification if INR is Not Closed Within 15 Days</td>
                        <td>'.$row['closing_justification'].'</td>
                        <td>Closure Comments</td>
                        <td>'.$row['Closure_Comments'].'</td>
                    </tr>
                </table>

                <div></div> ';}}
	      $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('capalog .pdf', 'I');
	}

	else if($_GET["type"]=="downloadCapaLog"){
	     $_GET['filename'] = 'capalog '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
	      
	      $html.='
	      <h2 style="text-align:center;color:brown">Cap Log</h2>
	      <table border="1" cellpadding="5">
	              <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; ">Sr No</td>
                    <td style="width: 6%; ">Date</td>
                    <td style="width: 9%; ">Initiated Dept.	</td>
                    <td style="width: 10%; ">CAPA Reference No.	</td>
                    <td style="width: 20%; ">Origin/ Reference From / Ref. Document No.	</td>
                     <td style="width: 10%; ">Details of CAPA	</td>
                    <td style="width: 10%; ">Requisition By		</td>
                     <td style="width: 10%; ">Issued By		</td>
                      <td style="width: 10%; ">Closed on/by			</td>
                       <td style="width: 10%; ">Remarks	</td>
                 </tr> ';
         $output = array();
          //$sql = "SELECT *, DATE(initiate_date) as initiate_date, DATE(close_date) as close_date FROM capa WHERE status='Closed' ";
	    $sql = "SELECT *, DATE(initiate_date) as initiate_date, DATE(close_date) as close_date FROM capa 
	    WHERE user_no='".$_GET["user_no"]."' AND DATE(initiate_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
	    $result = $conn->query($sql);
	    $i=1;
 	    if ($result->num_rows > 0) {
	         while ($row = $result->fetch_assoc()) {
	            $row["origin"] = json_decode($row["origin"]);
	            $row["plan"] = json_decode($row["plan"]);
	            $row["capa_extension"] = json_decode($row["capa_extension"]);
	            $output[] = $row;       
                 
         $html.='<tr>
                    <td style="width: 5%; ">'.$i.'</td>
                    <td style="width: 6%; ">'.$row['initiate_date'].'</td>
                    <td style="width: 9%; ">'.$row['department'].'</td>
                    <td style="width: 10%; ">'.$row['capa_no'].'</td>
                    <td style="width: 20%; ">' . $row['origin'][0] . '</td>
                     <td style="width: 10%; ">'.$row['document_no'].'</td>
                    <td style="width: 10%; ">'.$row['initiate_by'].'</td>
                    <td style="width: 10%; ">'.$row[''].'</td>
                    <td style="width: 10%; ">'.$row['close_date'].'</td>
                    <td style="width: 10%; ">'.$row['status'].'</td>
                 </tr>';
                 $i++;
	         }
	    }
	      $html.='</table>';
	     
	      $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('capalog .pdf', 'I');
	}
	 else if ($_GET["type"] == "downloadCapa") {
	       $_GET['filename'] = 'capalog '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
      //  $_GET['filename'] = 'CORRECTIVE ACTION / PREVENTIVE ACTION FORM'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
       
        $output = array();
	    $sql = "SELECT *, DATE(initiate_date) as initiate_date, DATE(close_date) as close_date, DATE(review3_date) as review3_date, DATE(close_verify_approve_date) as close_verify_approve_date, DATE(close_verify_date) as close_verify_date FROM capa WHERE user_no='".$_GET["user_no"]."' AND id='".$_GET['id']."'  ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	         while ($row = $result->fetch_assoc()) {
	            $row["origin"] = json_decode($row["origin"]);
	            $row["plan"] = json_decode($row["plan"]);
	             //$row["capa_extension"] = json_decode($row["capa_extension"]);
	            $output[] = $row;
	           
        
        $html.='
        <h2 style="text-align:cenetr;color:brown">CORRECTIVE ACTION / PREVENTIVE ACTION FORM</h2>
        &nbsp;&nbsp;<b>Format No:</b>QA022/F/02-03 <br>
        
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:60%;"><b>CAPA Issued by(Sign/Date):</b>'.$row['initiate_by'].' '.date('d-m-Y',strtotime($row['initiate_date'])).'</td>
                    <td style="width:40%;"><b>CAPA No:</b>'.$row['capa_no'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Origin of CAPA: </b><br>
                ';
                 $origins = $row["origin"];
                        for ($i = 0; $i < count($origins); $i++) {
                            $origin = $origins[$i];            
    
         $html.=''.$origin.' &nbsp;&nbsp;&nbsp;';
                    }
         $html.='</td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Reference Supporting Document No.:</b>'.$row['document_no'].'</td>
                </tr>';
        
                        
               
                
                
                $html.='
                <tr>
                    <td style="width:100%;"><b>CAPA Plan:</b><br>
                                              
                                            <table border="1" cellpadding="5">
                                            
                                            <tr>
                                                <td style="width:10%;"><b>Sr.No.</b></td>
                                                <td style="width:40%;"><b>CAPA</b></td>
                                                <td style="width:25%;"><b>Responsible Person Name</b></td>
                                                <td style="width:25%;"><b>Target Due Date</b></td>
                                            </tr>';
                                             $j=1;
                  
                                            $plans = $row["plan"];
                                            for ($i = 0; $i < count($plans); $i++) {
                                            $plan = $plans[$i];
                                    $html.='<tr>
                                                <td style="width:10%;">'.$j++.'</td>
                                                <td style="width:40%;">'.$plan->capa.'</td>
                                                <td style="width:25%;">'.$plan->person.'</td>
                                                <td style="width:25%;">'.date('d-m-Y',strtotime($plan->due_date)).'</td>
                                            </tr>';
                                            }
                                    $html.='</table><br>
                                            

                                            <b>CAPA Initiated By- Name:</b>'.$row['initiate_by'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Sign/Date:</b>'.date('d-m-Y',strtotime($row['initiate_date'])).'<br>
                                            <b>CAPA Initiated Department Head- Name:</b>'.$row['department'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Sign/Date:</b>'.date('d-m-Y',strtotime($row['check_date'])).'<br>
                                            </td>
                </tr>
                ';
                
                        
        $html.='</table>
                <div></div>';
        $html.='<table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%;"><b>QA Assessment:</b></td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Has this type of CAPA occurred previously: </b>'.$row['prev_occured'].' 
                                            
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;">';
                    if ($row["prev_occured"] == 'Yes') {
                        $html.='&nbsp;<b>If Yes then Mention Ref CAPA No.:</b>'.$row['capa_no'].'<br>';
                    }
                    $html.='
                                            <b>Change Control required:</b>'.$row['changecontrol'].'<br>
                                            <b>Comments (if any):</b>'.$row['comment'].'<br>
                                            <div></div>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>QA Executive/Designee:</b>
                                            <div></div>
                                            <b>Name:</b>'.$row['review1_by'].'&nbsp;&nbsp;<b>sign/Date:</b>'.date('d-m-Y',strtotime($row['review1_date'])).'
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Reviewed By Head QA Head/Designee:</b>
                                            <div></div>
                                            <b>Comment:</b> '.$row["review2_comment"].'<br>
                                            <b>Name:</b>'.$row['review2_by'].'&nbsp;&nbsp;<b>Sign/Date:</b>'.date('d-m-Y',strtotime($row['review2_date'])).'
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Reviewed By CQA Head/Designee:</b>
                                            <div></div>
                                            <b>Comment:</b> '.$row["review3_comment"].'<br>
                                           <b>Name:</b>'.$row['review3_by'].'&nbsp;&nbsp;<b>Sign/Date:</b>'.date('d-m-Y',strtotime($row['review3_date'])).'
                    </td>
                </tr>
                 <br pagebreak="true"/>';
               
        
        $plans = $row["plan"];
        for ($i = 0; $i < count($plans); $i++) {
            $plan = $plans[$i];
            if ($plan->due_date < $entry_date) {
                $plan->isextension = 'YES';
                
                $html.='<tr>
                    <td style="width:100%;"><b>	CAPA Extension (Reason of Extension and Justification): (if required or N/A)	</b>'.$row['extension'].'</td>
                    </tr>
                    <tr>
                        <td style="width:10%;"><b>Sr.No.</b></td>
                        <td style="width:45%;"><b>CAPA</b></td>
                        <td style="width:45%;"><b>Justification</b></td>
                    </tr>';
                     $row["capa_extension"] = json_decode($row["capa_extension"]);
                     $capa_extensions = $row["capa_extension"];
                     $j=1;
                        for ($k = 0; $k < count($capa_extensions); $k++) {
                        $capa_extension = $capa_extensions[$k];    
                $html.='<tr>
                        <td style="width:10%;">'.$j++.'</td>
                        <td style="width:45%;">'.$capa_extension->capa.'</td>
                        <td style="width:45%;">'.$capa_extension->justifaction.'</td>
                    </tr>';
                }
            } else {
                $html.='<tr><td style="width:100%;"><b>	CAPA Extension (Reason of Extension and Justification):</b> Not Applicable</td></tr>
                    <tr><td style="width:100%;"><b>	New Target Due Date:</b> Not Applicable</td></tr>
                    <tr>
                        <td style="width:50%;"><b>Responsible person :</b> Not Applicable</td>
                        <td style="width:50%;"><b>Approved By (Head QA) :</b> Not Applicable</td>
                    </tr>
                ';
                break;
            }
            $plans[$i] = $plan;
        }
        $html.='
                <tr>
                    <td style="width:100%;"><b>CAPA Closure:</b>(Attached copy of completed document for verification):<br></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Attachment No.</b></td>
                    <td style="width:70%;"><b>Title of Attachment</b></td>
                </tr>';
                $output1 = array();
	            $sql1 = "SELECT * FROM capa_file WHERE capa_no='".$row["capa_no"]."'";
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
                                 <a href="https://'.$_SERVER['SERVER_NAME'].'/api/gmptotal/upload/capa/'.$row1['file'].'">'.$file.'</a>
                            </td>
                        </tr>';
                    }
                }
        $html.='<tr>
                    <td style="width:50%;"><b>Responsible person :</b><br>'.$row["close_by"].' / '.date('d-m-Y',strtotime($row['close_date'])).'<br></td>
                    <td style="width:50%;"><b>Approved By (Department Head) :</b><br>'.$row['closing_approval_by'].' / '.date('d-m-Y',strtotime($row['closing_approval_date'])).'<br></td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>CAPA Closure verification:</b></td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Comment (if any):</b><br>'.$row["verify_comment"].'
                                            <div></div>
                                            <b>CAPA Effectiveness Required:</b>'.$row['effectiveness'].'<br>'.$row['effectiveness_no'].'<div></div><b>CAPA Closing Date:</b>'.date('d-m-Y',strtotime($row['close_date'])).'<br></td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>QA Executive/Designee :</b>'.$row['close_verify_by'].'<br><b>Sign/Date:</b>'.date('d-m-Y',strtotime($row['close_verify_date'])).'<br>
                    </td>
                    <td style="width:50%;"><b>Approved By ( Head QA) :</b>'.$row['close_verify_approve_by'].'<br><b>Sign/Date:</b>'.date('d-m-Y',strtotime($row['close_verify_approve_date'])).'<br></td>
                </tr>
        ';      
        $html.='</table>';
                        
	         }
	    }
        

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('capa.pdf', 'I');

}

}

$conn->close();
?>