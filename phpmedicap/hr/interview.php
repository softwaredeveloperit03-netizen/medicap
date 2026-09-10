<?php


    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);









    require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
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
    
    if ($_GET["type"] == "getPendingInterviews") {
        $output = Array();
       // $sql = "SELECT * FROM candidate WHERE id IN (SELECT candidate_id FROM interviewers WHERE employee='".$_GET["emp_id"]."' AND emp_status='pending')";
    // 	 $sql = "SELECT * FROM candidate WHERE id IN (SELECT candidate_id FROM interviewers WHERE emp_status='pending') 
    // 	 AND isInterviewCompleted='No' order by 1 desc";
   $sql=" SELECT DISTINCT c.email_id, c. *,c.id,i.candidate_id,c.isInterviewCompleted,i.emp_status,c.primary_int_comp,c.interviewer_int_comp,c.final_int_comp FROM candidate c LEFT join interviewers i ON c.id=i.candidate_id WHERE c.isInterviewCompleted='no' and c.primary_int_comp='yes' AND c.interviewer_int_comp='no' ORDER BY id desc";
   //$sql=" SELECT DISTINCT c.email_id, c. *,c.id,i.candidate_id,c.isInterviewCompleted,i.emp_status,c.primary_int_comp,c.interviewer_int_comp,c.final_int_comp FROM candidate c LEFT join interviewers i ON c.id=i.candidate_id WHERE c.status='pending' AND c.isInterviewCompleted='no' and c.primary_int_comp='yes' AND c.interviewer_int_comp='no' ORDER BY id desc";

//   $sql=" SELECT c. *,c.id,i.candidate_id,c.isInterviewCompleted,i.emp_status FROM candidate c LEFT join interviewers i ON c.id=i.candidate_id WHERE c.status='pending' AND c.isInterviewCompleted='no' ORDER BY id desc";
    
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $row["primary_hr_checklist"] = json_decode($row["primary_hr_checklist"]);  
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    } 
    if ($_GET["type"] == "getPendingInterviewsMeha") {
        $output = Array();
    $sql=" SELECT DISTINCT c.email_id, c. *,c.id,i.candidate_id,c.isInterviewCompleted,i.emp_status,c.primary_int_comp,c.interviewer_int_comp,c.final_int_comp FROM candidate c LEFT join interviewers i ON c.id=i.candidate_id WHERE c.isInterviewCompleted='no' and c.primary_int_comp='yes' AND c.interviewer_int_comp='no' and i.employee='".$_GET["emp_id"]."'  ORDER BY id desc";
    
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $row["primary_hr_checklist"] = json_decode($row["primary_hr_checklist"]);  
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveInterview") {
        $sql = "INSERT INTO interviewers  (candidate_id,department,designation) 
        VALUES ('".$input["candidate_id"]."','".$input["department"]."','".$input["designation"]."')";
        
    	if($conn->query($sql)){
    	    
    	 
           
    	    
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if ($_GET["type"] == "Interviewer_Remarks") {
        $sql = "UPDATE candidate SET  interviewer_remarks ='".$input["remarks"]."',interviewer_checklist='".json_encode($input["checklist"])."',
        interviewed_by= '".$_GET["emp_id"]."',interviwed_date='$entry_date',
        interviewer_int_comp= 'yes' WHERE id='".$input["id"]."' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
          } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "savePrimaryRoundRemarks") {
        $sql = "UPDATE candidate SET  primary_hr_comments ='".$input["remarks"]."',primary_hr_checklist='".json_encode($input["checklist"])."',
        primary_hr= '".$_GET["emp_id"]."',primaryHr_date='$entry_date',
        primary_int_comp= 'yes' WHERE id='".$input["id"]."' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
          } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveFinalRoundRemarks") {
        $sql = "UPDATE candidate SET  final_hr_comments ='".$input["decision"]."',is_interview_completed='".$input["interview_status"]."',
        final_hr_checklist='".json_encode($input["checklist"])."',final_hr= '".$_GET["emp_id"]."',finalHr_date='$entry_date',
         expected_salary='".$input["expected_salary"]."',  recommended_salary='".$input["recommended_salary"]."',  offered_salary='".$input["offered_salary"]."',
         final_hr= '".$_GET["emp_id"]."',finalHr_date='$entry_date',
         final_int_comp='yes',rep_ctc_annual ='".$input["rep_ctc_annual"]."',criteria_sal ='".$input["criteria_sal"]."',replacement ='".$input["replacement"]."',
         privious_ctc1 ='".$input["privious_ctc1"]."',experinced ='".$input["experinced"]."',rep_for ='".$input["rep_for"]."',
         status= 'Approved'   WHERE id='".$input["id"]."' ";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
          } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveFinalRoundRemarksMeha") {
        $sql = "UPDATE candidate SET  final_hr_comments ='".$input["decision"]."',is_interview_completed='".$input["interview_status"]."',
        final_hr_checklist='".json_encode($input["checklist"])."',final_hr= '".$_GET["emp_id"]."',finalHr_date='$entry_date',
         expected_salary='".$input["expected_salary"]."',  recommended_salary='".$input["recommended_salary"]."',  offered_salary='".$input["offered_salary"]."',
         final_hr= '".$_GET["emp_id"]."',finalHr_date='$entry_date',
         final_int_comp='yes',rep_ctc_annual ='".$input["rep_ctc_annual"]."',criteria_sal ='".$input["criteria_sal"]."',replacement ='".$input["replacement"]."',
         privious_ctc1 ='".$input["privious_ctc1"]."',experinced ='".$input["experinced"]."',rep_for ='".$input["rep_for"]."',
         status= 'Sent To Director'   WHERE id='".$input["id"]."' ";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
          } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "updateStatus") {
        $sql = "UPDATE candidate SET    status= '".$input["status"]."'  WHERE id='".$input["id"]."' ";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
          } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveFinalRoundRemarks1") {
        $sql = "UPDATE candidate SET  final_hr_comments ='".$input["decision"]."',is_interview_completed='".$input["interview_status"]."',
        final_hr_checklist='".json_encode($input["checklist"])."',final_hr= '".$_GET["emp_id"]."',finalHr_date='$entry_date',
         expected_salary='".$input["expected_salary"]."',  recommended_salary='".$input["recommended_salary"]."',  offered_salary='".$input["offered_salary"]."',
         final_hr= '".$_GET["emp_id"]."',manage_approval = 'Inprocess',
         final_int_comp='yes',rep_ctc_annual ='".$input["rep_ctc_annual"]."',criteria_sal ='".$input["criteria_sal"]."',replacement ='".$input["replacement"]."',
         privious_ctc1 ='".$input["privious_ctc1"]."',experinced ='".$input["experinced"]."',rep_for ='".$input["rep_for"]."',
         status= 'Approved'   WHERE id='".$input["id"]."' ";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
          } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "changeStatusByManagement") {
         $sql = "UPDATE candidate SET manage_approval = '".$_GET["status1"]."' WHERE id = '".$_GET["cid"]."' ";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
          } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "saveFinalDecisionRemarks") {
        $sql = "UPDATE candidate SET  final_decision_comments ='".$_GET["remarks"]."'   WHERE id='".$_GET["id"]."' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
          } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getInterviewsLog") {
        $output = Array();
        $sql = "SELECT i.*, c.candidate_name, c.qualification, c.mobile_no, c.email_id FROM interviewers i LEFT JOIN candidate c ON i.candidate_id=c.id WHERE ";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $row = array_map('utf8_encode', $row);
    		    $row["data"] = json_decode($row["data"]);
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
     else if ($_GET["type"] == "getInterviewerSign") {
        $output = Array();
        $sql = "SELECT * from interviewers where candidate_id='".$_GET["id"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    } 
    else if ($_GET["type"] == "getInterviewerLog") {
        $output = Array();
        // $sql = "SELECT i.*, c.candidate_name, c.qualification, c.mobile_no, c.email_id FROM interviewers i LEFT JOIN candidate c 
        // ON i.candidate_id=c.id";
        $sql = "SELECT i.*, c.interviewer_remarks,c.candidate_name, c.qualification, c.mobile_no, c.email_id,c.interviewer_checklist FROM interviewers i LEFT JOIN candidate c ON i.candidate_id=c.id";
        
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $row["interviewer_checklist"] = json_decode($row["interviewer_checklist"]);
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }

}

$conn->close();
?>