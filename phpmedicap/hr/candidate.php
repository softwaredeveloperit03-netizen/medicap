<?php
    require '../db.php';
    require '../token.php';
     require '../tcpdf/tcpdf.php';
     
    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);

     
    require '../phpmailer/class.phpmailer.php';
   // require '../PHPExcel/Classes/PHPExcel.php';
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
    
    if ($_GET["type"] == "saveCandidate") {
        
        $input = $_POST;
        
        $id = $input["contact_no"];
        $rn = mt_rand(1000, 9999);
     
        if(isset($_FILES["resume"])) {
            $file_tmp =$_FILES['resume']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['resume']['name'])));
            $resume = $id.$rn."resume.".$file_ext;
            move_uploaded_file($file_tmp,"../../../upload/candidate/".$resume);
        }

    
      	$sql = "INSERT INTO `candidate`(`plant_id`, `firstname`, `lastname`, `contact_no`, `emp_email`, `qualification`, `department`, `gender`, `birthdate`, 
      	`nationality`, `marital_status`, `blood_group`, `resume`, `permanent_flat`, `permanent_country`, `permanent_state`, `permanent_city`, `permanent_pincode`, 
      	`tempflat_no`, `temp_country`, `temp_state`, `temp_city`, `temp_pincode`, `status`,`entry_by`,`entry_date`) VALUES  ('".$_GET["plant_id"]."','".$input["firstname"]."',
      	'".$input["lastname"]."','".$input["contact_no"]."','".$input["emp_email"]."','".$input["qualification"]."','".$input["department"]."','".$input["gender"]."',
      	'".$input["birthdate"]."','".$input["nationality"]."','".$input["marital_status"]."','".$input["blood_group"]."','$resume','".$input["permanent_flat"]."', 
      	'".$input["permanent_country"]."','".$input["permanent_state"]."','".$input["permanent_city"]."','".$input["permanent_pincode"]."','".$input["tempflat_no"]."',
      	'".$input["temp_country"]."','".$input["temp_state"]."','".$input["temp_city"]."','".$input["temp_pincode"]."', 'Pending','".$_GET["emp_id"]."','$entry_date')";
      	
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    
    }
    
    
    else if ($_GET["type"] == "joiningreport") {
       $sql = "INSERT INTO `joiningreport`(plant_id, candidate, letter_date, department, designation, join_date, emp_no, location, reporting) VALUES  ('".$_GET['plant_id']."', '".$input['candidate']."','".$input['letter_date']."',
                             '".$input['department']."','".$input['designation']."','".$input['join_date']."','".$input['emp_no']."',
                             '".$input['location']."','".$input['reporting']."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET['type'] == 'downloadJoinreportList') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
    		      $html.='
    		                 <h4 style="text-align:center">Joining Report</h4>

        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:20%;text-align:center"><b>Candidate Name</b></td>
           <td style="width:20%;text-align:center"><b>Employee Code</b></td>
            <td style="width:20%;text-align:center"><b>Department	</b></td>
             <td style="width:15%;text-align:center"><b>Designation</b></td>
             <td style="width:20%;text-align:center"><b>Date Of Offer Letter</b></td>
         </tr>';
         
            $sql = "SELECT * FROM joiningreport";
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
             $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:20%;text-align:center">'.$row['candidate'].'</td>
           <td style="width:20%;text-align:center">'.$row['emp_no'].'</td>
            <td style="width:20%;text-align:center">'.$row['department'].'</td>
             <td style="width:15%;text-align:center">'.$row['designation'].'</td>
             <td style="width:20%;text-align:center">'.$row['letter_date'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
   else if($_GET['type'] == 'downloadOfferLetter') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		             <h4  style="text-align:center">Selected Candidate List </h4>

        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:20%;text-align:center"><b>Candidate Name</b></td>
           <td style="width:20%;text-align:center"><b>Qualification</b></td>
            <td style="width:20%;text-align:center"><b>Department	</b></td>
             <td style="width:15%;text-align:center"><b>Designation</b></td>
             <td style="width:20%;text-align:center"><b>Interview date</b></td>
         </tr>';
         
        $sql = "SELECT * FROM candidate WHERE isSalary = 'Yes' AND dept_remark='selected' AND user_no='".$_GET['user_no']."' and dept_status='done' ORDER BY id DESC";
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
             $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:20%;text-align:center">'.$row['candidate_name'].'</td>
           <td style="width:20%;text-align:center">'.$row['qualification'].'</td>
            <td style="width:20%;text-align:center">'.$row['department'].'</td>
             <td style="width:15%;text-align:center">'.$row['designation'].'</td>
             <td style="width:20%;text-align:center">'.$row['interview_date'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
    
    else if ($_GET["type"] == "getreportingLog") {
        
        
        $output = Array();
        $sql = "SELECT * FROM joiningreport";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);

        
    }
    
    else if ($_GET["type"] == "getCandidate") {
        $output = Array();
     
        $sql = "SELECT * FROM candidate where  AND plant_id = '".$_GET["plant_id"]."'  order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['resume'] = '/upload/candidate/'.$row['resume'];
                   $output1 = array();
                $sql1 = "SELECT i.*, e.firstname FROM interviewers i LEFT JOIN employee e ON i.employee=e.emp_id 
                WHERE i.user_no='".$_GET["user_no"]."' AND i.candidate_id='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["data"] = json_decode($row1["data"]);
                        $output1[] = $row1;
                    }
                }
                $row["interviewers"] = $output1;
                   $row["interviewer_checklist"] = json_decode($row["interviewer_checklist"]); 
                      $row["primary_hr_checklist"] = json_decode($row["primary_hr_checklist"]); 
                         $row["final_hr_checklist"] = json_decode($row["final_hr_checklist"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getCandidateForInterviewScheduleAndAllocation") {
        
        $output = Array();
        $sql = "SELECT * FROM candidate where  status NOT IN ('Rejected_In_Technical','Technical_Interview_Complete','Rejected_In_Primary_Round','Send_To_Plant_Head_Approval','Final_Round_Complete' ) 
        AND  plant_id = '".$_GET["plant_id"]."'  order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT * FROM interviewers where  plant_id = '".$_GET["plant_id"]."' AND candidate_id = '".$row["id"]."'  order by id ASC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                
                $row['primaryRoundChecklist'] =  json_decode($row["primaryRoundChecklist"]); 
                $row['technicalRemarkData'] =  json_decode($row["technicalRemarkData"]); 
                $row['interviewers'] = $output1;
                $row['resume'] = '/upload/candidate/'.$row['resume'];
                $output[] = $row;
            }
        }
        echo json_encode($output); 
    }
    else if ($_GET["type"] == "getCandidateForInterviewFinalHrROund") {
        
        $output = Array();
        $sql = "SELECT * FROM candidate where  status = 'Technical_Interview_Complete' AND isFinalHrRound = 'NO' AND  plant_id = '".$_GET["plant_id"]."'  order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT * FROM interviewers where  plant_id = '".$_GET["plant_id"]."' AND candidate_id = '".$row["id"]."'  order by id ASC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                
                $row['primaryRoundChecklist'] =  json_decode($row["primaryRoundChecklist"]); 
                $row['technicalRemarkData'] =  json_decode($row["technicalRemarkData"]); 
                $row['interviewers'] = $output1;
                $row['resume'] = '/upload/candidate/'.$row['resume'];
                $output[] = $row;
            }
        }
        echo json_encode($output); 
    }
    else if ($_GET["type"] == "getCandidateForPlantHeadAPproval") {
        
        $output = Array();
        $sql = "SELECT * FROM candidate where  status = 'Send_To_Plant_Head_Approval' AND  plant_id = '".$_GET["plant_id"]."'  order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT * FROM interviewers where  plant_id = '".$_GET["plant_id"]."' AND candidate_id = '".$row["id"]."'  order by id ASC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                
                $row['primaryRoundChecklist'] =  json_decode($row["primaryRoundChecklist"]); 
                $row['technicalRemarkData'] =  json_decode($row["technicalRemarkData"]); 
                $row['hrFinalChecklist'] =  json_decode($row["hrFinalChecklist"]); 
                $row['interviewers'] = $output1;
                $row['resume'] = '/upload/candidate/'.$row['resume'];
                $output[] = $row;
            }
        }
        echo json_encode($output); 
    }
    else if ($_GET["type"] == "getRecommendedCandidatesForFurtherProcess") {
        
        $output = Array();
        $sql = "SELECT * FROM candidate where  ( status = 'Final_Round_Complete' OR isOfferLetter = 'YES' ) AND decision = 'Recommended'  AND  plant_id = '".$_GET["plant_id"]."'  order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT * FROM interviewers where  plant_id = '".$_GET["plant_id"]."' AND candidate_id = '".$row["id"]."'  order by id ASC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                
                $row['primaryRoundChecklist'] =  json_decode($row["primaryRoundChecklist"]); 
                $row['technicalRemarkData'] =  json_decode($row["technicalRemarkData"]); 
                $row['hrFinalChecklist'] =  json_decode($row["hrFinalChecklist"]); 
                $row['interviewers'] = $output1;
                $row['resume'] = '/upload/candidate/'.$row['resume'];
                $output[] = $row;
            }
        }
        echo json_encode($output); 
    }
    else if ($_GET["type"] == "getEmployeeByDeptWithCTC") {       
                $output = Array();
   
        $sql = "SELECT e.firstname ,e.emp_id,e.department,e.designation,
       (select ctcMonthly from salary_annexure where emp_id = e.emp_id order By id desc limit 1) as ctcMonthly,
       (select ctcAnuually from salary_annexure where emp_id = e.emp_id order By id desc limit 1) as ctcAnuually
       FROM employee e WHERE e.status='Active' AND 
       e.plant_id='".$_GET["plant_id"]."' and e.department='".$_GET["depart"]."' ORDER BY e.firstname asc";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getCandidateForFinal") {
        
        $output = Array();
        $sql = "SELECT * FROM candidate where status = 'Technical_Interview_Complete' AND  plant_id = '".$_GET["plant_id"]."'  order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT * FROM interviewers where  plant_id = '".$_GET["plant_id"]."' AND candidate_id = '".$row["id"]."'  order by id ASC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                
                $row['primaryRoundChecklist'] =  json_decode($row["primaryRoundChecklist"]); 
                $row['technicalRemarkData'] =  json_decode($row["technicalRemarkData"]); 
                $row['interviewers'] = $output1;
                $row['resume'] = '/upload/candidate/'.$row['resume'];
                $output[] = $row;
            }
        }
        echo json_encode($output); 
    }
    else if ($_GET["type"] == "getCandidatesForTechnicalROund") {
        
        $output = Array();
        $sql = "SELECT c.*,i.interviewerId FROM candidate c LEFT JOIN  interviewers i ON c.id = i.candidate_id where c.plant_id = '".$_GET["plant_id"]."' AND 
        i.interviewerId = '".$_GET["emp_id"]."' AND c.status = 'Primary_Round_Complete' order by c.id desc";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['resume'] = '/upload/candidate/'.$row['resume'];
                $output[] = $row;
            }
        }
        echo json_encode($output); 
        
    }
    else if ($_GET["type"] == "getCandidateForInterview") {
        
        $output = Array();
        $sql = "SELECT a.*,SELECT CONCAT(b.firstname, ' ', b.lastname) AS candidateName ,b.qualification,b.department,b.resume,b.venue,b.designation,b.interview_date,
        b.interview_time_from, b.interview_time_to FROM interviewers a 
        left join candidate b ON a.candidate_id = b.id where  a.plant_id = '".$_GET["plant_id"]."' AND a.status = 'Pending' AND  a.interviewerId = '".$_GET["emp_id"]."'  order by b.id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['resume'] = '/upload/candidate/'.$row['resume'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"]=="saveInterviewers"){
        
         
        $data = $input["interviewers"]; // Already an array
        $candidate_id    = mysqli_real_escape_string($conn, trim($input["candidate_id"]));

        $flag = 0;
        
        foreach ($data as $row) {
        
            $interviewerDept  = mysqli_real_escape_string($conn, trim($row["interviewerDept"]));
            $interviewerDesignation = mysqli_real_escape_string($conn, trim($row["interviewerDesignation"]));
            $interviewerId    = mysqli_real_escape_string($conn, trim($row["interviewerId"]));
            $interviewerName    = mysqli_real_escape_string($conn, trim($row["interviewerName"]));

            $sql = "INSERT INTO `interviewers`(`plant_id`, `candidate_id`, `interviewerName`, `interviewerId`, `interviewerDept`, `interviewerDesignation`) VALUES  
            ('".$_GET['plant_id']."', '$candidate_id', '$interviewerName', '$interviewerId','$interviewerDept','$interviewerDesignation')";
        
            if ($conn->query($sql) === TRUE) {
                $flag = 1; // At least one success
            } else {
                error_log("DB Insert Failed: " . $conn->error);
                $flag = 0; // Track failure
            }
        }
 
    	if($flag == 1) {
    	    $sql = "UPDATE candidate SET status = 'Interviewer_Allocated', isInterviewerAllocated = 'YES' WHERE id = '$candidate_id'";
    	    $conn->query($sql);
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if($_GET["type"]=="savePrimaryRoundInterView"){
        
        $candidate_id    = mysqli_real_escape_string($conn, trim($input["candidate_id"]));
        $status    = mysqli_real_escape_string($conn, trim($input["status"]));
 
       $sql = "UPDATE candidate SET isPrimaryRoundHr = 'YES' , primaryRoundChecklist = '".json_encode($input["primaryRoundChecklist"])."',
     	primaryROundRemark = '".$input["primaryROundRemark"]."' , primaryBy = '".$_GET["emp_id"]."' , status = '$status', primaryOn = '$entry_date' 
     	WHERE id = '$candidate_id'";
    	if($conn->query($sql) === TRUE) {
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if($_GET["type"]=="saveFinalHrRound"){
        
        $candidate_id    = mysqli_real_escape_string($conn, trim($input["candidate_id"]));
        $status    = mysqli_real_escape_string($conn, trim($input["status"]));
 
       $sql = "UPDATE candidate SET isFinalHrRound = 'YES' , hrFinalChecklist = '".json_encode($input["hrFinalChecklist"])."',
     	hrFinalRemark = '".$input["hrFinalRemark"]."',decision = '".$input["decision"]."',experinced = '".$input["experinced"]."',privious_ctc = '".$input["privious_ctc"]."',replacement = '".$input["replacement"]."',
     	replacementFor = '".$input["replacementFor"]."',criteria_sal = '".$input["criteria_sal"]."',repCtcAnuually = '".$input["repCtcAnuually"]."',repCtcMonthly = '".$input["repCtcMonthly"]."',
     	expected_salary = '".$input["expected_salary"]."',recommended_salary = '".$input["recommended_salary"]."', hrFinalRoundBy = '".$_GET["emp_id"]."' , status = '$status', hrFinalRoundOn = '$entry_date' 
     	WHERE id = '$candidate_id'";
     	
    	if($conn->query($sql) === TRUE) {
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if($_GET["type"]=="updateInterviewStatusFromPlantHead"){
        
        $candidate_id    = mysqli_real_escape_string($conn, trim($input["candidate_id"]));
        $status    = mysqli_real_escape_string($conn, trim($input["status"]));
 
       $sql = "UPDATE candidate SET plantHeadApprovalBy = '".$_GET["emp_id"]."' , status = '$status', plantHeadApprovalOn = '$entry_date' WHERE id = '$candidate_id'";
     	
    	if($conn->query($sql) === TRUE) {
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if($_GET["type"]=="saveGenerateOfferForm"){
        
       $candidate_id    = mysqli_real_escape_string($conn, trim($input["candidate_id"]));
  
       $sql = "UPDATE candidate SET Offer_letter = '".$input["Offer_letter"]."', joining_date = '".$input["joining_date"]."', isOfferLetter = 'YES', status = 'Offer_Letter_Generated', 
       offerLetBy = '".$_GET["emp_id"]."' , offerLetOn = '$entry_date' WHERE id = '$candidate_id'";
     	
    	if($conn->query($sql) === TRUE) {
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if ($_GET["type"] == "saveSalaryAnnexureCandidate") {
        
        $sql = "INSERT INTO `salary_annexure_candidate`(`plant_id`, `candidate_id`, `basicDAMonthly`, `basicDAAnnually`, `hraMonthly`, `hraAnnually`, 
        `conveyAllowanceMonthly`, `conveyAllowanceAnnually`, `eduAllowanceMonthly`, `eduAllowanceAnnually`, `foodAllowanceMonthly`, 
        `foodAllowanceAnnually`, `dressAllowanceMonthly`, `dressAllowanceAnnually`, `medicalAllowanceMonthly`, `medicalAllowanceAnnually`, 
        `othAllowanceMonthly`, `othAllowanceAnnually`, `monthlyBonusMonthly`, `monthlyBonusAnnually`, `grossSalAMonthly`, 
        `grossSalAAnnually`, `employerSharePfMonthly`, `employerSharePfAnnually`, `employerShareEsicMonthly`, `employerShareEsicAnnually`, 
        `gratuityMonthly`, `gratuityAnnually`, `exGratiaMonthly`, `exGratiaAnnually`, `bonusMonthly`, `bonusAnnually`, `latMonthly`, `latAnnually`, 
        `totalRetrialMonthly`, `totalRetrialAnnually`, `performanceBonusMonthly`, `performanceBonusAnnually`, `employeePfMonthly`, `employeePfAnnually`, 
        `empEsicMonthly`, `empEsicAnnually`, `netPayMonthly`, `netPayAnuually`, `ctcMonthly`, `ctcAnuually`, `entry_by`, `entry_date`, `status`) VALUES 
        ('".$_GET["plant_id"]."','".$input["candidate_id"]."','".$input["basicDAMonthly"]."','".$input["basicDAAnnually"]."','".$input["hraMonthly"]."',
        '".$input["hraAnnually"]."','".$input["conveyAllowanceMonthly"]."','".$input["conveyAllowanceAnnually"]."','".$input["eduAllowanceMonthly"]."',
        '".$input["eduAllowanceAnnually"]."','".$input["foodAllowanceMonthly"]."','".$input["foodAllowanceAnnually"]."', '".$input["dressAllowanceMonthly"]."',
        '".$input["dressAllowanceAnnually"]."','".$input["medicalAllowanceMonthly"]."','".$input["medicalAllowanceAnnually"]."','".$input["othAllowanceMonthly"]."',
        '".$input["othAllowanceAnnually"]."','".$input["monthlyBonusMonthly"]."','".$input["monthlyBonusAnnually"]."','".$input["grossSalAMonthly"]."',
        '".$input["grossSalAAnnually"]."','".$input["employerSharePfMonthly"]."','".$input["employerSharePfAnnually"]."',
        '".$input["employerShareEsicMonthly"]."','".$input["employerShareEsicAnnually"]."','".$input["gratuityMonthly"]."',
        '".$input["gratuityAnnually"]."','".$input["exGratiaMonthly"]."','".$input["exGratiaAnnually"]."','".$input["bonusMonthly"]."',
        '".$input["bonusAnnually"]."','".$input["latMonthly"]."','".$input["latAnnually"]."','".$input["totalRetrialMonthly"]."',
        '".$input["totalRetrialAnnually"]."','".$input["performanceBonusMonthly"]."','".$input["performanceBonusAnnually"]."',
        '".$input["employeePfMonthly"]."','".$input["employeePfAnnually"]."','".$input["empEsicMonthly"]."','".$input["empEsicAnnually"]."',
        '".$input["netPayMonthly"]."','".$input["netPayAnuually"]."','".$input["ctcMonthly"]."','".$input["ctcAnuually"]."',
        '".$_GET["emp_id"]."', '$entry_date' , 'Pending')";
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
             
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

    else if($_GET["type"]=="saveTechnicalRoundInterView"){
        
        $candidate_id    = mysqli_real_escape_string($conn, trim($input["candidate_id"]));
        $status    = mysqli_real_escape_string($conn, trim($input["status"]));
 
       $sql = "UPDATE candidate SET isTechnicalRound = 'YES' , technicalRemarkData = '".json_encode($input["technicalRemarkData"])."',
       technicalRoundBy = '".$_GET["emp_id"]."' , status = '$status', technicalRoundOn = '$entry_date' 
     	WHERE id = '$candidate_id'";
    	if($conn->query($sql) === TRUE) {
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }

    else if ($_GET["type"] == "getCandidateDirector") {
        $output = Array();
        $sql = "SELECT * FROM candidate where status ='Sent To Director' AND plant_id = '".$_GET["plant_id"]."'  order by 1 desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['resume'] = '/upload/candidate/'.$row['resume'];
                   $output1 = array();
                $sql1 = "SELECT i.*, e.firstname FROM interviewers i LEFT JOIN employee e ON i.employee=e.emp_id 
                WHERE i.user_no='".$_GET["user_no"]."' AND i.candidate_id='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["data"] = json_decode($row1["data"]);
                        $output1[] = $row1;
                    }
                }
                $row["interviewers"] = $output1;
                   $row["interviewer_checklist"] = json_decode($row["interviewer_checklist"]); 
                      $row["primary_hr_checklist"] = json_decode($row["primary_hr_checklist"]); 
                         $row["final_hr_checklist"] = json_decode($row["final_hr_checklist"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
  
    else if ($_GET["type"] == "getCandidateForManagement") {
        $output = Array();
        // $sql = "SELECT * FROM candidate WHERE status='pending' order by 1 desc";
        $sql = "SELECT * FROM candidate where manage_approval ='Inprocess'  order by 1 desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['resume'] = '/upload/candidate/'.$row['resume'];
                   $output1 = array();
                $sql1 = "SELECT i.*, e.firstname FROM interviewers i LEFT JOIN employee e ON i.employee=e.emp_id 
                WHERE i.user_no='".$_GET["user_no"]."' AND i.candidate_id='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["data"] = json_decode($row1["data"]);
                        $output1[] = $row1;
                    }
                }
                $row["interviewers"] = $output1;
                   $row["interviewer_checklist"] = json_decode($row["interviewer_checklist"]); 
                      $row["primary_hr_checklist"] = json_decode($row["primary_hr_checklist"]); 
                         $row["final_hr_checklist"] = json_decode($row["final_hr_checklist"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    else if ($_GET["type"] == "getPendingSchedules") {
        $output = Array();
        $sql = "SELECT * FROM candidate WHERE isScheduleInteview='No' order by 1 desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output); 
    } 
    else if($_GET["type"] == "saveScheduleInterview") {
        
        $sql = "UPDATE candidate SET interview_date = '".$input["interview_date"]."', interview_time_from = '".$input["interview_time_from"]."', 
        interview_time_to = '".$input["interview_time_to"]."' , venue = '".$input["venue"]."', isInterviewSchedule = 'YES', status = 'Interview_Scheduled',
        department = '".$input["department"]."',designation = '".$input["designation"]."' , schedule_by = '".$_GET["emp_id"]."' ,schedule_On = '$entry_date'
        WHERE id = '".$input["candidate_id"]."'";
        
    	if ($conn->query($sql)) {
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    } 

    else if($_GET["type"]=="saveInterviewChecklist") {
        
    	$sql = "INSERT INTO `interviewChecklists`(`plant_id`, `checklistType`, `checkPerticular`, `entryBy`, `entryOn`) VALUES
        ('".$_GET["plant_id"]."','".$input["checklistType"]."', '".$input["checkPerticular"]."', '".$_GET["emp_id"]."', '$entry_date' )";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    
    else if($_GET["type"]=="interviewChecklistByType"){
    	$sql = "SELECT * FROM interviewChecklists WHERE plant_id = '".$_GET["plant_id"]."'  AND checklistType = '".$_GET["checklistType"]."' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    } 
    
    else if($_GET["type"]=="getInterviewChecklist"){
    	$sql = "SELECT checklistType FROM interviewChecklists WHERE plant_id = '".$_GET["plant_id"]."' group by checklistType";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    
    		    $sql1 = "SELECT * FROM interviewChecklists WHERE plant_id = '".$_GET["plant_id"]."' AND checklistType = '".$row["checklistType"]."' ";
            	$result1 = $conn->query($sql1);
            	$output1 = Array();
            	if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            			$output1[] = $row1;
            		}
            	}
            	
            	$row['perticulars'] = $output1;
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    } 
    
    
    
    
    else if($_GET["type"]=="getPendingJoiningCandidates"){
        header('Content-Type: application/json');
        $output = array();
        $colSet = array();
        try {
            $colRes = $conn->query("SHOW COLUMNS FROM candidate");
            if ($colRes) {
                while ($c = $colRes->fetch_assoc()) {
                    $colSet[strtolower($c['Field'])] = true;
                }
            }
        } catch (Throwable $e) {
            $colSet = array();
        }
        $hasCol = function ($name) use ($colSet) {
            return isset($colSet[strtolower($name)]);
        };

        $where = array('1=1');
        $plantId = isset($_GET['plant_id']) ? $conn->real_escape_string($_GET['plant_id']) : '';
        if ($plantId !== '' && $hasCol('plant_id')) {
            $where[] = "plant_id='".$plantId."'";
        }
        if ($hasCol('isemployee')) {
            $where[] = "isemployee='No'";
        }
        if ($hasCol('dept_remark')) {
            $where[] = "dept_remark='selected'";
        }
        if ($hasCol('offer_status')) {
            $where[] = "offer_status='Accepted'";
        } else if ($hasCol('offeracceptance')) {
            $where[] = "(offeracceptance='yes' OR offeracceptance='Yes' OR offeracceptance='Accepted')";
        }

        $deptName = isset($_GET['department_name']) ? trim($_GET['department_name']) : '';
        if ($deptName !== '') {
            $deptEsc = $conn->real_escape_string($deptName);
            $deptParts = array();
            if ($hasCol('finaldepartment')) {
                $deptParts[] = "finaldepartment='".$deptEsc."'";
            }
            if ($hasCol('department')) {
                $deptParts[] = "department='".$deptEsc."'";
            }
            if ($deptParts) {
                $where[] = '('.implode(' OR ', $deptParts).')';
            }
        }

        $desig = isset($_GET['designation']) ? trim($_GET['designation']) : '';
        if ($desig !== '') {
            $desigEsc = $conn->real_escape_string($desig);
            $desigParts = array();
            if ($hasCol('finaldesignation')) {
                $desigParts[] = "finaldesignation='".$desigEsc."'";
            }
            if ($hasCol('designation')) {
                $desigParts[] = "designation='".$desigEsc."'";
            }
            if ($desigParts) {
                $where[] = '('.implode(' OR ', $desigParts).')';
            }
        }

        $order = $hasCol('id') ? ' ORDER BY id DESC' : '';
        $sql = "SELECT * FROM candidate WHERE ".implode(' AND ', $where).$order;
        $result = false;
        try {
            $result = $conn->query($sql);
        } catch (Throwable $e) {
            $result = false;
        }
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (empty($row['candidate_name'])) {
                    $row['candidate_name'] = trim(($row['firstname'] ?? '').' '.($row['lastname'] ?? ''));
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
        exit;
    } 
    
    
   else if($_GET["type"] == "viewCandidateOfferLetter"){
       
  
        include("../pdfimp2.php");
        
        $var = 'https://aurenyxgmp.com/php/phpdevlop/gmptotal/logos/'.$logo;
 
       $pdf = new TCPDF('P', 'mm', 'A4');
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Paper Less HR');
        $pdf->SetTitle('Offer Letter');
        
        // ---- ADD PROFESSIONAL FONT ----
        // (Option 1: Built-in font)
        $pdf->SetFont('dejavusans', '', 9);   // <–– smaller, clean font
        
        // Margins
        $pdf->SetMargins(6, 6, 6);
        $pdf->AddPage();
        
 
        $sql = "SELECT * FROM candidate where plant_id = '".$_GET["plant_id"]."' AND id = '".$_GET["id"]."'  "; 
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        
        $sql1 = "SELECT * FROM salary_annexure_candidate where plant_id = '".$_GET["plant_id"]."' AND candidate_id = '".$row["id"]."'  "; 
        $result1 = $conn->query($sql1);
        $row1 = $result1->fetch_assoc();
 
        $html = '
            <table style="width: 100%; " >
                <tr>
                    <td style="width:50%;padding-top: 20px; ">
                        <img src="'.$var.'" height="38">
                    </td>
                    <td style="width:50%;padding: 3px 4px;font-size: 8px; text-align:right;font-family: dejavusans;">
                        <p> <b>'.$plant_full_name.'<br>'.$plant_full_address.' </b></p>
                    </td>
                </tr>
            </table>
            <div></div>
            <table style="width: 100%;">
                <tr>
                    <td style="width:100%; font-family: dejavusans; font-size: 12px;text-align:center;text-decoration: underline; text-decoration-color: black;text-decoration-style: solid; text-decoration-thickness: 5px;"><b>OFFER LETTER</b></td>
                </tr>
            </table>
            <div></div>
            <table style="width: 100%;">
                <tr>
                    <td style="width:50%; font-family: dejavusans; text-align: left; "><b>TO</b></td>
                    <td style="width:50%; font-family: dejavusans; text-align: right; " rowspan="4">DATE : <b>'.date("m/d/Y").'</b></td>
                </tr>
                <tr>
                    <td style="width:50%; font-family: dejavusans; text-align: left; color:blue;">'.$row['firstname'].' '.$row['lastname'].'</td>
                </tr>
                <tr>
                    <td style="width:50%; font-family: dejavusans; text-align: left; color:blue;">'.$row['permanent_flat'].'</td>
                </tr>
                <tr>
                    <td style="width:50%; font-family: dejavusans; text-align: left; color:blue;">'.$row['contact_no'].' .</td>
                </tr>
            </table>
            <div></div>
            <table style="width: 100%;">
                <tr>
                    <td style="width:100%; font-family: dejavusans; font-size: 12px;text-align:center;text-decoration: underline; text-decoration-color: black;text-decoration-style: solid; text-decoration-thickness: 5px;"><b>Sub: Offer Letter</b></td>
                </tr>
                <tr>
                    <td style="width:100%; font-family: dejavusans; text-align: left; "></td>
                </tr>
                <tr>
                    <td style="width:100%; font-family: dejavusans; text-align: left; "><b>Dear '.$row['firstname'].',</b></td>
                </tr>
            </table>
            <div></div>
            <table style="width: 100%;">
              
                <tr>
                    <td style="width:100%; font-family: dejavusans; text-align: left; ">
                        <p>We are pleased to offer you the post of <b>'.$row['designation'].'</b> on <b>'.$row['joining_date'].'</b> form <b>'.$plant_full_name.'</b>.</p> 
    
                        <p>The compensation structure is enclosed for your reference as Annexure.</p>
                            
                        <p>Your employment with the Company will be subject to strict adherence to the policies and procedures of the Company.</p>
                            
                        <p>You will be designated as a trainee for a period of six months, after which the training period may be extended, or you may be placed on probation for an additional six months. The detailed terms will be outlined in your appointment letter.</p>
                            
                        <p>This offer is subjected to background verification and medical fitness.</p>
                               				             
                        <p>On acceptance of the terms of conditions as per this offer letter, you will be able to terminate your employment with the Company by giving <b>45 Days notice</b> to the Company.</p>
                            
                        <p><b>Termination and Resignation During Training or Probation:</b></p>
                            
                        <p>During the training or probationary period, the Company reserves the right to terminate your employment at its discretion without prior notice, by providing one days salary in lieu of notice.
                            If you choose to resign without notice, or if your employment is discontinued by the Company during the training or probationary period due to non-performance, misconduct, violation of Company policies, 
                            actions detrimental to the Company’s interest or reputation, or damage to Company property, you will forfeit any entitlement to unpaid salary, outstanding dues, or other financial clearances. In such cases, 
                            the Company also reserves the right to recover training-related expenses, up to a maximum of ₹15,000.
                            We are pleased to welcome you to the Company. Kindly sign and return the duplicate copy of this letter as a token of your acceptance of the terms of employment.</p>
                            
                        <p>If you have any question, please clarify from the undersigned.</p> <br><br>
                    </td>
                </tr>
            
            </table>
            <div></div>
            <table style="width: 100%;">
                <tr>
                    <td style="width:100%; font-family: dejavusans; font-size: 10px; text-align: left;"><b>HR - Head</b></td>
                </tr>
            </table>
            <br pagebreak="true"/>
     
            <table style="width: 100%; " >
                <tr>
                    <td style="width:50%;padding-top: 20px; ">
                        <img src="'.$var.'" height="38">
                    </td>
                    <td style="width:50%;padding: 3px 4px;font-size: 8px; text-align:right;font-family: dejavusans;">
                        <p> <b>'.$plant_full_name.'<br>'.$plant_full_address.' </b></p>
                    </td>
                </tr>
            </table>
            <div></div>
        
      
        
            <table style="width: 100%;">
              
                <tr>
                    <td style="width:100%; font-family: dejavusans; text-align: left; ">
                        <p>I accept the aforesaid terms & conditions and this offer of employment. I shall keep the contents of this document confidential.</p> 
                    </td>
                </tr>
            
            </table>
            <div></div>
            <table style="width: 100%;" cellpadding="4">
                <tr>
                    <td style="width:100%;  font-family: dejavusans; font-size: 10px; text-align: left;">I will join on : ______________</td>
                </tr>
                <tr>
                    <td style="width:100%;  font-family: dejavusans; font-size: 10px; text-align: left;">Name : <span style="color:blue;">'.$row['firstname'].' '.$row['lastname'].'</span></td>
                </tr>
                <tr>
                    <td style="width:100%; font-family: dejavusans; font-size: 10px; text-align: left;">Signature : ______________</td>
                </tr>
                <tr>
                    <td style="width:100%; font-family: dejavusans; font-size: 10px; text-align: left;">Date : ______________</td>
                </tr>
            </table>
            <div></div>
            <div></div>
            <table style="width: 100%;">
                <tr>
                    <td style="width:100%; font-family: dejavusans; font-size: 12px;text-align:center;text-decoration: underline; text-decoration-color: black;text-decoration-style: solid; text-decoration-thickness: 5px;"><b>Documents to be Submitted by Candidate while Joining</b></td>
                </tr>
            </table>
            <div></div>
            <table style="width: 100%;">
              
                <tr>
                    <td style="width:100%; font-family: dejavusans; text-align: left; ">
                        <p> 1. Original Mark sheet of 10th </p> 
                        <p> 2. Original Mark sheet of 12th </p> 
                        <p> 3. Original Finale Year Mark sheet (Note: Any 1 From 1,2,3)</p> 
                        <p> 4. Aadhar Card Zerox Copy</p> 
                        <p> 5. PAN Card Zerox Copy</p> 
                        <p> 6. Bank Details – Zerox</p> 
                        <p> 7. One Passport Photo</p> 
                    </td>
                </tr>
            
            </table>
            <div></div>
            <div></div>
            <table style="width: 100%;">
                <tr>
                    <td style="width:100%; font-family: dejavusans; font-size: 12px;text-align:center;text-decoration: underline; text-decoration-color: black;text-decoration-style: solid; text-decoration-thickness: 5px;"><b>Contact Details of the Family</b></td>
                </tr>
            </table>
            <div></div>
            <table style="width: 100%;"   cellpadding="4">
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center; "><b>Name</b></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center; "><b>Relation</b></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center; "><b>Contact No</b></td>
                </tr>
                <tr>
                    <td style="border: 0.5px solid #000; "></td>
                    <td style="border: 0.5px solid #000; "></td>
                    <td style="border: 0.5px solid #000; "></td>
                </tr>
                <tr>
                    <td style="border: 0.5px solid #000; "></td>
                    <td style="border: 0.5px solid #000; "></td>
                    <td style="border: 0.5px solid #000; "></td>
                </tr>
            </table>';
        
        if($row['Offer_letter']=='With Annexure'){
            
        $html.='<br pagebreak="true"/>
            
            
            
            <table style="width: 100%; " >
                <tr>
                    <td style="width:50%;padding-top: 20px; ">
                        <img src="'.$var.'" height="38">
                    </td>
                    <td style="width:50%;padding: 3px 4px;font-size: 8px; text-align:right;font-family: dejavusans;">
                        <p> <b>'.$plant_full_name.'<br>'.$plant_full_address.' </b></p>
                    </td>
                </tr>
            </table>
            <div></div>
            <table style="width: 100%;">
                <tr>
                    <td style="width:100%; font-family: dejavusans; font-size: 12px;text-align:center;text-decoration: underline; text-decoration-color: black;text-decoration-style: solid; text-decoration-thickness: 5px;"><b>Annexure</b></td>
                </tr>
            </table>
            <div></div>
            
            <table  style="width: 100%;" cellpadding="4">
                                           
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>1.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Basic + D.A.</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['basicDAMonthly'].' </td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['basicDAAnnually'].'</td>
                </tr>                        
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>2.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> HRA</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['hraMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['hraAnnually'].'</td>
                </tr>                        
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>3.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Conveyance Allowance</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['conveyAllowanceMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['conveyAllowanceAnnually'].'</td>
                </tr>                        
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>4.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Education Allowance</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['eduAllowanceMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['eduAllowanceAnnually'].'</td>
                </tr>                        
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>5.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Food Allowance</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['foodAllowanceMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['foodAllowanceAnnually'].'</td>
                </tr>                        
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>6.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Dress Allowance</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['dressAllowanceMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['dressAllowanceAnnually'].'</td>
                </tr>                        
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>7.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Medical Allowance</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['medicalAllowanceMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['medicalAllowanceAnnually'].'</td>
                </tr>                        
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>8.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Other Allowance</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['othAllowanceMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['othAllowanceAnnually'].'</td>
                </tr>                        
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>9.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Monthly Bonus</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['monthlyBonusMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['monthlyBonusAnnually'].'</td>
                </tr>                        
                <tr>
                    <td colspan="2"> <strong>Gross Salary (A)</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>'.$row1['grossSalAMonthly'].'</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>'.$row1['grossSalAAnnually'].'</strong></td>
                </tr>                        
                <tr>
                    <td colspan="2" style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> <strong> Retrial / Statutory  Benefits Employer</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong></strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong></strong></td>
                </tr>      
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>10.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Employer Share to EPF</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['employerSharePfMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['employerSharePfAnnually'].'</td>
                </tr>                     
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>11.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Employer Share to ESIC</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['employerShareEsicMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['employerShareEsicAnnually'].'</td>
                </tr>                     
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>12.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Gratuity @ 4.81%**</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['gratuityMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['gratuityAnnually'].'</td>
                </tr>                     
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>13.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Ex-Gratia (Payable Annually)</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['exGratiaMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['exGratiaAnnually'].'</td>
                </tr>                     
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>14.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Bonus (Payable Annually)</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['bonusMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['bonusAnnually'].'</td>
                </tr>                     
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>15.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> LTA (Payable Annually)</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['latMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['latAnnually'].'</td>
                </tr>     
                <tr>
                    <td colspan="2"> <strong>Total Retrial (B)</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>'.$row1['totalRetrialMonthly'].'</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>'.$row1['totalRetrialAnnually'].'</strong></td>
                </tr>                        
                <tr>
                    <td colspan="2" style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> <strong>Employee Benefits ( C )</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong></strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong></strong></td>
                </tr>        
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>16.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Performance Bonus Payable Monthly <br>(Depend upon performance)</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['performanceBonusMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['performanceBonusAnnually'].'</td>
                </tr>      
                <tr>
                    <td colspan="2" style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> <strong>Retrial / Statutory  Benefits Employee (D)</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong></strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong></strong></td>
                </tr>       
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>17.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Employee PF</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['employeePfMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['employeePfAnnually'].'</td>
                </tr>   
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>17.</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> Employee ESIC</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['empEsicMonthly'].'</td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;">'.$row1['empEsicAnnually'].'</td>
                </tr>   
                <tr>
                    <td colspan="2" style="border: 0.5px solid #000; font-family: dejavusans; text-align: right;"> <strong>Net Payable Monthly (A+C-D)</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>'.$row1['netPayMonthly'].'</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>'.$row1['netPayAnuually'].'</strong></td>
                </tr> 
                <tr>
                    <td colspan="2" style="border: 0.5px solid #000; font-family: dejavusans; text-align: right;"> <strong>CTC (Cost to Company) (A+B+C)</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>'.$row1['ctcMonthly'].'</strong></td>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"><strong>'.$row1['ctcAnuually'].'</strong></td>
                </tr> 
                <tr>
                    <td style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> <strong>Note :-</strong></td>
                    <td colspan="3" style="border: 0.5px solid #000; font-family: dejavusans; text-align: center;"> <strong>Gratuity will only be payable after successful completion of 5 Years of continue service. At the time of leaving the services.</strong></td>
                </tr> 
            </table>
            <div></div>
            <div></div>
            <div></div>
   
            <table style="width: 100%;">
                <tr>
                    <td style="width:50%; font-family: dejavusans; font-size: 10px; text-align: left;"><b>HR Sign.</b></td>
                    <td style="width:50%; font-family: dejavusans; font-size: 10px; text-align: right;"><b>Employee Sign</b></td>
                </tr>
            </table>
            
 
       ';
            
            
        }
        

        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('grn.pdf', 'I');
    
       
   } 

    
    else if ($_GET["type"] == "viewCandidateOfferLetter") {
            
         $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $sql = "SELECT * FROM candidate WHERE plant_id = '".$_GET['plant_id']."'  AND id = '".$_GET['id']."' ";
     
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		      $sql1="SELECT * From salary_annexure WHERE emp_id='".$row["id"]."'";
    		      
    		    $salary_details;
    		   $result1 = $conn->query($sql1);
                if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            		   $salary_details = $row1;
            		}}
    		
    		
           $html.='
                <table cellpadding="1">
                    <tr>
                        <td style="width:25%;"><b>File No</b>:</td>
                         <td style="width:25%;"></td>
                        <td style="width:25%; text-align:right;"><b>Date:</b></td>
                        <td style="width:25%;"><b>'.date('d/m/Y',strtotime($row['entry_date'])).'</b></td>
                    </tr><br>
                    <tr>
                        <td style="width:100%;">To,<br>';
                        if($row['gender']=='Female'){$html.='Miss';}else{$html.='Mr';}
                        $html.='&nbsp;
                        '.$row['candidate_name'].'<br>
                        '.$row['address'].'</td>
                         </tr>
                        <tr>
                        <td style="width:100%;text-align:center;color:brown"><h2><strong><u>Offer Letter</u></strong></h2></td>
                        </tr>
                    <tr>
                    <td style="width:100%; font-weight:bold;">Dear &nbsp;'; if($row['gender']=='Female'){$html.='Miss';}
                    else{$html.='Mr';}
                    $html.='&nbsp;'.$row['candidate_name'].'</td>
                    </tr>
                    <tr>
                    <td style="width:100%;">This has reference to your application for employment in our company and the subsequent 
                    interview you had with us on <b>'.$row['interview_date'].'</b> . We are pleased to offer you an employment with our 
                    organization as <b>'.$row['designation'].'</b> - <b>'.$row['department'].'</b> at <b>'.$row['location'].',</b> 
                    on the following conditions:</td>
                    </tr>
                    <ul>
                            <li>Please note that this is an offer letter only. The company’s standard appointment letter 
                                  containing detailed CTC components   and terms & conditions of your employment will be issued to 
                                  you upon you joining the company which shall be binding on you.</li>
                                <li>You would be on probation period of 6 (Six Months) from the date of your joining and your services would be confirmed subsequent to a satisfactory performance and assessment.</li>
                               <li>You are requested to join us on or before '.$row['entry_date'].' failing which this offer will stand automatically withdrawn. Kindly also confirm your exact date of joining within seven days from the receipt of this offer.</li>
                                <li>You are requested to bring the following documents in original at the time of reporting for duty:</li>
                                </ul>
                               <ul>
                               <ul>
                             
                              <li>  Education Certificates – SSC, Inter, Degree, PG and others, if any.</li>
                                    <li>  Relieving letter from the Previous Employer & Experience Certificates.</li>
                                    <li>  Pay slips for last three months.</li>
                                    <li>  Proof of Date of Birth / SSLC / HSC certificate stating Date of Birth.</li>
                                    <li>  Photocopy of Bank A/c Details, PAN & Aadhar card.</li>
                                    <li>  Photocopy of Address Proof. </li>
                                    <li>  Passport Size Photos – 03 Nos. </li>
                                    
                              </ul>
                            </ul>
                      <tr>
                      <td style="width:100%">Your commencement of employment shall be subject to you fulfilling the following conditions:</td>
                      </tr>
                   <ul>
                    <ol type="a">
                      <li>An appropriate relieving letter from your immediately previous employer is required, if employed previousl;and</li>
                     <li>By signing this offer you hereby consent to any background investigations and/or reference checks that may be carried out in relation to you by the Company.</li>
                      </ol>
                     </ul>
                     <tr>
                     <td style="width:100%">Please indicate your acceptance of this position by signing below and returning a signed copy of this letter and the attached addendum. We look forward to a mutually rewarding relationship.</td>
                     </tr>
                  
                </table>
                <br pagebreak="true"/>';
                
        
              $html.='<table cellpadding="1" border="0.1">
        
        <tr>
        <th style="width:95%;" align="center"><b>Salary Authorization Form (SAF):</b></th></tr>
        <tr>
         <th style="width:95%;"align="cenetr"><b>Annexure A to Appointment Letter dated:</b></th> 
        </tr>
         </table>
          <table style="width:100%;"cellpadding="3" border="0.1">
         <tr>
         <td style="width:55%;"><b>Name</b></td>
          <td style="width:40%;">'.$row['candidate_name'].' </td>
         </tr>
         
         <tr>
         <td style="width:55%;"><b>Designation</b></td>
          <td style="width:40%;">'.$row['designation'].'</td>
         </tr>
         <tr>
         <td style="width:55%;"><b>Department</b></td>
          <td style="width:40%;">'.$row['department'].'</td>
         </tr>
         <tr>
         <td style="width:55%;"><b>Location</b></td>
          <td style="width:40%;">'.$row['location'].'</td>
         </tr>
         <tr>
         <td style="width:55%;"><b>Probation Period </b></td>
          <td style="width:40%;"><b>'.$row[''].'</b></td>
         </tr>
         <tr>
         <td style="width:55%;"><b>Monthly CTC Rs.</b></td>
          <td style="width:20%;"><b>'.$salary_details['total_ctc'].'</b></td>
          <td style="width:20%;"><b></b></td>
          </tr>
          <tr>
         <td style="width:55%;"align="center"><b>Cost To Company (CTC)</b></td>
         </tr>
         <tr>
         <th style="width:55%;"><b>Salary Heads </b></th>
          <th style="width:20%;"><b>INR Per Month </b></th>
          <th style="width:20%;"><b>INR Per Annum</b></th>
         </tr>';
         
$sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= '" .$salary_details["id"]."' AND  salary_group ='Earnings' ";
 
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){	
                
      
        $html.=' <tr>
         <td style="width:55%;">'.$row2['description'].'</td>
          <td style="width:20%; text-align:right;">'.$row2['per_month'].'</td>
          <td style="width:20%; text-align:right;">'.$row2['per_annum'].'</td>
         </tr>';
		}
			$html.=' 	<tr>
          <td style="width:55%;"><strong>Gross Salary</strong></td>
          <td style="width:20%; text-align:right;"><strong>'.$salary_details['total_earnings'].'</strong></td>
          <td style="width:20%; text-align:right;"><strong>'.($salary_details['total_earnings'] * 12).'</strong></td>
         </tr>
         <tr>
          <td style="width:55%;"><strong>Employer Benefits </strong></td>
          <td style="width:20%; text-align:right;"></td>
          <td style="width:20%; text-align:right;"></td>
         </tr>
         ';
            		
                }
                $sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= '" .$salary_details["id"]."' AND  salary_group ='CTC Calculations' ";
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){	
                
      
        $html.=' <tr>
         <td style="width:55%;">'.$row2['description'].'</td>
          <td style="width:20%; text-align:right;">'.$row2['per_month'].'</td>
          <td style="width:20%; text-align:right;">'.$row2['per_annum'].'</td>
         </tr>';
            		}
            		
            	$html.=' 	<tr>
          <td style="width:55%;"><strong>Fixed CTC</strong></td>
          <td style="width:20%; text-align:right;"><strong>'.$salary_details['total_ctc'].'</strong></td>
          <td style="width:20%; text-align:right;"><strong>'.($salary_details['total_ctc'] * 12).'</strong></td>
         </tr>
          
         	<tr>
          <td style="width:55%;"><strong>Employee Deduction  </strong></td>
          <td style="width:20%; text-align:right;"></td>
          <td style="width:20%; text-align:right;"></td>
         </tr>
         ';
            		
                }
        
         $sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= '" .$salary_details["id"]."' AND  salary_group ='Deductions' ";
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){	
                
      
        $html.=' <tr>
         <td style="width:55%;">'.$row2['description'].'</td>
          <td style="width:20%; text-align:right;">'.$row2['per_month'].'</td>
          <td style="width:20%; text-align:right;">'.$row2['per_annum'].'</td>
         </tr>';
            		}
            		
            	$html.=' 	<tr>
          <td style="width:55%;"><strong>Total Deductions</strong></td>
          <td style="width:20%; text-align:right;"><strong>'.$salary_details['ctc_deductions'].'</strong></td>
          <td style="width:20%; text-align:right;"><strong>'.($salary_details['ctc_deductions'] * 12).'</strong></td>
         </tr>';
            		
                }
                
        	$html.=' 	<tr>
          <td style="width:55%;"><strong>Net Take Home Salary after PF & Tax deduction</strong></td>
          <td style="width:20%; text-align:right;"><strong>'.$salary_details['take_home_salary'].'</strong></td>
          <td style="width:20%; text-align:right;"><strong>'.($salary_details['take_home_salary'] * 12).'</strong></td>
         </tr>';
        
        
        
        
        
       
          $html.='</table>
        <div></div>
          <tr>
                   <td style="width:50%"><b>With Best Wishes,</b></td>
                   </tr><br>
                    <tr>
                   <td style="width:50%"><b>For,GMP Software PVT LTD Pvt Ltd</b></td>
                   </tr><div></div>
                   <tr>
                   <td style="width:100%"><b>Authorized Signatory</b></td>
                   </tr><div></div>
                  <hr style="color:blue"></hr>
                  <tr>
                  <td style="width:100%;text-align:center"><b><u>Acceptance</u></b></td>
                  </tr><div></div>
                  <tr>
                  <td style="width:70%;">I have read and understood the above Terms & Conditions and hereby signify my acceptance. I hereby confirm my date of joining as: _________________.</td>
                  </tr><div></div><div></div>
                  <tr>
                  <td style="width:35%;">Name:</td>
                  <td style="width:30%;">Signature:</td>
                   <td style="width:35%;">Date:</td>
                  </tr>';
    		
    	
    		}
    	}
         $html.='  </table>';
              	
           $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('generateHrOffer.pdf', 'I');
    
    }
    else if($_GET['type'] == 'downloadCandidatesList'){
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:30%;text-align:center"><b>Candidate Name</b></td>
           <td style="width:30%;text-align:center"><b>Qualification</b></td>
            <td style="width:20%;text-align:center"><b>Contact No	</b></td>
             <td style="width:15%;text-align:center"><b>Interviewer Allocated</b></td>
         </tr>';
         
          $sql = "SELECT * FROM candidate WHERE user_no='".$_GET["user_no"]."' AND status LIKE '%".$_GET["status"]."%' order by 1 desc";
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
             $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:30%;text-align:center">'.$row['candidate_name'].'</td>
           <td style="width:30%;text-align:center">'.$row['qualification'].'</td>
            <td style="width:20%;text-align:center">'.$row['mobile_no'].'</td>
             <td style="width:15%;text-align:center">'.$row['interview_allocated'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
   
    else if ($_GET["type"] == "print_all_emp_tds") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
                //  $sql = "SELECT * FROM employee ORDER BY id ASC";
                //      $result = $conn->query($sql);
                //   $result->num_rows > 0;
              //          $row = $result->fetch_assoc();
      

 $html .= '

         <table border="1">
        <tr>
         <td style="line-height:30px;text-align:center;"><h1>TDS Statement</h1></td>
        </tr>
        
        <table border="1">
        <tr>
            <th style="width:5.26%; text-align:center">Sr No.</th>
            <th style="width:5.26%; text-align:center">Emp ID</th>
            <th style="width:5.26%; text-align:center">Name</th>
            <th style="width:5.26%; text-align:center">Department</th>
            <th style="width:5.26%; text-align:center">Designation</th>
            <th style="width:5.26%; text-align:center">CTC</th>
            <th style="width:5.26%; text-align:center">Investment 80 CC </th>
            <th style="width:5.26%; text-align:center">Investment 8D</th>
            <th style="width:5.26%; text-align:center">Investment HRA</th>
            <th style="width:5.26%; text-align:center">Standard Deduction</th>
            <th style="width:5.26%; text-align:center">Total Investment</th>
            <th style="width:5.26%; text-align:center">Total Income</th>
            <th style="width:5.26%; text-align:center">Income Tax</th>
            <th style="width:5.26%; text-align:center">Health and Education Cess </th>
            <th style="width:5.26%; text-align:center">Surcharge</th>
            <th style="width:5.26%; text-align:center">Balence and Tax Deduction</th>
            <th style="width:5.26%; text-align:center">Total Tax</th>
            <th style="width:5.26%; text-align:center">Monthly TDS</th>
            <th style="width:5.26%; text-align:center">Deduction Till</th>
       
        </tr>';
    // $sql = "SELECT DISTINCT a.emp_id, a.*,b.take_home_salary,b.employee_type, b.id AS annexure_id,b.ctc_annual FROM employee a LEFT JOIN ( SELECT sa.* FROM salary_annexure sa JOIN 
    //     ( SELECT emp_id, MAX(id) AS max_id FROM salary_annexure WHERE increament = '' GROUP BY emp_id ) latest ON sa.emp_id = latest.emp_id 
    //     AND sa.id = latest.max_id ) b ON a.emp_id = b.emp_id AND a.plant_id = b.plant_id 
    //     WHERE a.department LIKE '%".$_GET["department_name"]."%' AND a.designation LIKE '%".$_GET["designation"]."%' 
    //   AND a.status LIKE '%".$_GET["status"]."%' and a.plant_id LIKE '%".$_GET["plant_id"]."%' order by a.id desc";
    
   $sql = "SELECT a.*, b.department, b.designation,b.firstname,b.lastname FROM employee_tds a LEFT JOIN employee b ON a.emp_id = b.emp_id ORDER BY `id` DESC";
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
          $html.='  <tr>
             <td style="width:5.26%;text-align:center">'.$i.'</td>
             <td style="width:5.26%;text-align:center">'.$row['emp_id'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['firstname'].' '.$row['lastname'].' </td>
             <td style="width:5.26%;text-align:center">'.$row['department'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['designation'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['CTC'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['invest_80cc'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['invest_80D'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['HRA'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['standard_deduction'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['total_invest_new'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['total_income_new'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['income_tax_new'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['HAECess_new'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['surcharge'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['balence_tax_deduction'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['total_tax_new'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['monthly_tds_new'].'</td>
             <td style="width:5.26%;text-align:center">'.$row['paidAmount'].'</td>
         </tr>';
          $i++;
            }
        }
        
    $html.='</table>';    



 

$html.='';
     
          
       
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadTDSList.pdf', 'I');
     
    }
    else if ($_GET["type"] == "print_emp_tds") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
                 $sql = "SELECT * FROM employee where emp_id='".$_GET['empid']."' ORDER BY id ASC";
                     $result = $conn->query($sql);
                   $result->num_rows > 0;
                        $row = $result->fetch_assoc();
      

 $html .= '

 <table border="1">
<tr>
 <td style="line-height:30px;width: 540px;text-align:center;"><b>FORM NO. 16</b></td>
</tr>
<tr>
 <td style="line-height:20px;width: 540px;text-align:center;">[See rule 31(1)(a)]</td>
</tr>
<tr>
 <td style="line-height:20px;width: 540px;text-align:center;"><b>PART A</b></td>
</tr>
<tr>
 <td style="line-height:20px;width: 540px;border-bottom:none;text-align:center;"><b>Certificate under Section 203 of the Income-tax Act, 1961 for tax deducted at source on salary paid to an employee under section 192 or pension/interest income of specified senior citizen under section 194P</b></td>
</tr>
<tr>
 <td style="line-height:20px;width: 270px;border-bottom:none;text-align:center;"><b>Certificate No.	xxxxxxx</b></td>
 <td style="line-height:20px;width: 270px;text-align:center;"><b>Last updated on 01106/2022</b></td>
</tr>
<tr>
<td style="line-height:20px;width: 270px;border-bottom:none;text-align:center;"><b>Name and address of the Employer/Specified Bank</b></td>
 <td style="line-height:20px;width: 270px;text-align:center;"><b>Name and address of the Employee/Specified senior citizen</b></td>
</tr>
<tr>
<td style="line-height:20px;width: 270px;border-bottom:none;text-align:center;"></td>
 <td style="line-height:20px;width: 270px;text-align:center;"></td>
</tr>
<tr>
<td style="line-height:20px;width: 135px;text-align:center;"><b>PAN of the Deductor</b></td>
 <td style="line-height:20px;width: 135px;text-align:center;"><b>TAN of the Deductor</b></td>
 <td style="line-height:20px;width: 135px;text-align:center;"><b>PAN of theEmployee/Specified senior citizen</b></td>
 <td style="line-height:20px;width: 135px;text-align:center;"><b>Employee Reference No. provided by the Employer/Pension Payment order no. provided by the Employer (If available)</b></td>
 </tr>
 <tr>
<td style="line-height:20px;width: 135px;text-align:center;">xxxxxxxxx</td>
 <td style="line-height:20px;width: 135px;text-align:center;">xxxxxxxxx</td>
 <td style="line-height:20px;width: 135px;text-align:center;">xxxxxxxxx</td>
 <td style="line-height:20px;width: 135px;text-align:center;"></td>
 </tr>
 <tr>
<td style="line-height:20px;width: 270px;border-bottom:none;text-align:center;"><b>CIT (TDS)</b></td>
 <td style="line-height:20px;width: 135px;text-align:center;"><b>Assessment Year</b></td>
 <td style="line-height:20px;width: 135px;text-align:center;"><b>Period with the Employer</b></td>
</tr>
<tr>
<td style="line-height:20px;width: 270px;text-align:center;">Sahkar Apt 84 S V Road Malad, Bengaluru,400064,India</td>
 <td style="line-height:20px;width: 135px;text-align:center;">2022-23</td>
 <td style="line-height:20px;width: 67.5px;text-align:center;">From 01-Apr-2021</td>
 <td style="line-height:20px;width: 67.5px;text-align:center;">To 31-Mar-2022</td>
 </tr>
<tr>
<td style="line-height:20px;width: 540px;border-bottom:none;text-align:center;"><b>Summary of amount paid/credited and tax deducted at source thereon in respect of the employee</b></td>
</tr>
<tr>
<td style="line-height:20px;width: 90px;text-align:center;"><b>Quarter(s)</b></td>
<td style="line-height:20px;width: 120px;text-align:center;"><b>Receipt Numbers of original quarterly statements of TDS under sub-section (3) of Section 200</b></td>
<td style="line-height:20px;width: 90px;text-align:center;"><b>Amount paid/credited</b></td>
<td style="line-height:20px;width: 120px;text-align:center;"><b>Amount of tax deducted(Rs.)</b></td>
<td style="line-height:20px;width: 120px;text-align:center;"><b>Amount of tax deposited / remitted (Rs.)</b></td>
</tr>
<tr>
<td style="line-height:20px;width: 90px;text-align:center;"><b>Total (Rs.)</b></td>
<td style="line-height:20px;width: 120px;text-align:center;"></td>
<td style="line-height:20px;width: 90px;text-align:center;"></td>
<td style="line-height:20px;width: 120px;text-align:center;"></td>
<td style="line-height:20px;width: 120px;text-align:center;"></td>
</tr>


<tr style="page-break-after: always;">
<td style="line-height:20px;width: 540px;border-bottom:none;text-align:cemter;"><b>I. DETAILS OF TAX DEDUCTED AND DEPOSITED IN THE CENTRAL GOVERNMENT ACCOUNT THROUGH BOOK ADJUSTMENT</b><br>
(The deductor to provide payment wise details of tax deducted and deposited with respect to the deductee)</td>
</tr>
<tr>
<td style="line-height:20px;width: 60px;text-align:center;" rowspan="2"><b>Sl. No.</b></td>
<td style="line-height:20px;width: 110px;text-align:center;" rowspan="2"><b>Tax Deposited in respect of the deductee (Rs.)</b></td>
<td style="line-height:20px;width: 370px;text-align:center;"><b>Book Identification Number (BIN)</b></td>
</tr>

<tr>
<td style="line-height:20px;width: 85px;text-align:center;"><b>Receipt Numbers of Form No. 24G</b></td>
<td style="line-height:20px;width: 105px;text-align:center;"><b>DDO serial number in Form no. 24G</b></td>
<td style="line-height:20px;width: 95px;text-align:center;"><b>Date of transfer voucher (dd/mm/yyyy)</b></td>
<td style="line-height:20px;width: 85px;text-align:center;"><b>Status of matching with Form no. 24G</b></td>
</tr>
<tr>
<td style="line-height:20px;width: 60px;text-align:center;"><b>Total (Rs.)</b></td>
<td style="line-height:20px;width: 110px;text-align:center;"></td>
<td style="line-height:20px;width: 370px;text-align:center;"></td>
</tr>

<tr>
    <td style="line-height:20px;width: 540px;text-align:center;"><b>II. DETAILS OF TAX DEDUCTED AND DEPOSITED IN THE CENTRAL GOVERNMENT ACCOUNT THROUGH CHALLAN</b> <br>(The deductor to provide payment wise details of tax deducted and deposited with respect to the deductee)</td>
</tr>

<tr>
<td style="line-height:20px;width: 60px;text-align:center;" rowspan="2"><b>Sl. No.</b></td>
<td style="line-height:20px;width: 110px;text-align:center;" rowspan="2"><b>Tax Deposited in respect of the deductee (Rs.)</b></td>
<td style="line-height:20px;width: 370px;text-align:center;"><b>Challan Identification Number (CIN)</b></td>
</tr>

<tr>
<td style="line-height:20px;width: 85px;text-align:center;"><b>BSR Code of the Bank Branch</b></td>
<td style="line-height:20px;width: 115px;text-align:center;"><b>Date on which Tax deposited (dd/mm/yyyy)</b></td>
<td style="line-height:20px;width: 85px;text-align:center;"><b>Challan Serial Number</b></td>
<td style="line-height:20px;width: 85px;text-align:center;"><b>Status of matching with OLTAS*</b></td>
</tr>
<tr>
 <td style="line-height:20px;width: 60px;text-align:left;"></td>
 <td style="line-height:20px;width: 110px;text-align:left;"></td>
 <td style="line-height:20px;width: 85px;text-align:left;"></td>
 <td style="line-height:20px;width: 115px;text-align:left;"></td>
 <td style="line-height:20px;width: 85px;text-align:left;"></td>
 <td style="line-height:20px;width: 85px;text-align:left;"></td>
</tr>
<tr>
<td style="line-height:20px;width: 60px;text-align:center;"><b>Total (Rs.)</b></td>
<td style="line-height:20px;width: 110px;text-align:center;"></td>
<td style="line-height:20px;width: 85px;text-align:left;"></td>
 <td style="line-height:20px;width: 115px;text-align:left;"></td>
 <td style="line-height:20px;width: 85px;text-align:left;"></td>
 <td style="line-height:20px;width: 85px;text-align:left;"></td></tr>
<tr>
<td style="line-height:20px;width: 540px;text-align:center;"><b>Verification</b></td>
</tr>
<tr>
<td style="line-height:20px;width: 540px;text-align:left;"><b> I, PANKAJ VASHIST, son / daughter of RAMA SHANKAR SHARMA working in the capacity of AUTHORISED SIGNATORY (designation) do hereby certify that a sum
of Rs. 483740.00 [Rs. Four Lakh Eighty Three Thousand Seven Hundred and Fourty Only (in words)] has been deducted and a sum of Rs. 483740.00 [Rs. Four Lakh
Eighty Three Thousand Seven Hundred and Fourty Only] has been deposited to the credit of the Central Government. I further certify that the information given
above is true, complete and correct and is based on the books of account, documents, TDS statements, TDS deposited and other available records.
</b></td>
</tr>
<tr>
<td style="line-height:20px;width: 60px;text-align:left;"><b> Place</b></td>
<td style="line-height:20px;width: 110px;text-align:center;"></td>
<td style="line-height:20px;width: 370px;text-align:center;" rowspan="2"><b>(Signature of person responsible for deduction of Tax)</b></td>
</tr>
<tr>
<td style="line-height:20px;width: 60px;text-align:left;"><b> Date</b></td>
<td style="line-height:20px;width: 110px;text-align:center;"></td>
</tr>
<tr>
<td style="line-height:20px;width: 270px;text-align:left;"><b> Designation: </b></td>
<td style="line-height:20px;width: 270px;text-align:left;"><b> Full Name:PANKA
J V</b></td>
</tr>

</table>
<div></div>
<table>
<tr>
<td style="width: 540;"><b>Notes:</b></td>
</tr><br>
<tr>
<td style="width: 20;">1.</td>
<td style="width:520;">Part B (Annexure) of the certificate in Form No.16 shall be issued by the employer</td>
</tr>
<br>

<tr>
<td style="width: 20;">2.</td>
<td style="width:520;">. If an assessee is employed under one employer during the year, Part A of the certificate in Form No.16 issued for the quarter ending on 31st March of the financial year shall contain the details
of tax deducted and deposited for all the quarters of the financial year.</td>
</tr>    <br>

<tr>

<td style="width: 20;">3.</td>
<td style="width:520;"> If an assessee is employed under more than one employer during the year, each of the employers shall issue Part A of the certificate in Form No.16 pertaining to the period for which such
assessee was employed with each of the employers. Part B (Annexure) of the certificate in Form No. 16 may be issued by each of the employers or the last employer at the option of the assessee</td>
</tr>    <br>

<tr>
<td style="width: 20;">4.</td>
<td style="width:520;">To update PAN details in Income Tax Department database, apply for PAN change request through NSDL or UTITSL.</td>
</tr>    <br>
<br>



<tr>
<td style="width: 540;"><b>Legend used in Form 16</b></td>
</tr>
<br>


<tr>
<td style="width: 540;"><b>* Status of matching with OLTAS</b></td>
</tr>
<br>
<br>
</table>
<div></div>
<table border="1">

<tr>
<td style="line-height:20px;width: 60px;text-align:center;"><b>Legend</b></td>
<td style="line-height:20px;width: 90px;text-align:center;"><b>Description</b></td>
<td style="line-height:20px;width: 390px;text-align:center;"><b>Definition</b></td>
</tr>
<tr>
<td style="line-height:20px;width: 60px;text-align:center;"><b>U</b></td>
<td style="line-height:20px;width: 90px;text-align:center;">Unmatched</td>
<td style="line-height:20px;width: 390px;text-align:center;">Deductors have not deposited taxes or have furnished incorrect particulars of tax payment. Final credit will be reflected only when payment
details in bank match with details of deposit in TDS / TCS statement</td>
</tr>
<tr>
<td style="line-height:20px;width: 60px;text-align:center;">P</td>
<td style="line-height:20px;width: 90px;text-align:center;">Provisional</td>
<td style="line-height:20px;width: 390px;text-align:center;">Provisional tax credit is effected only for TDS / TCS Statements filed by Government deductors."P" status will be changed to Final (F) on
verification of payment details submitted by Pay and Accounts Officer (PAO)
</td>
</tr>
<tr>
<td style="line-height:20px;width: 60px;text-align:center;"><b>F</b></td>
<td style="line-height:20px;width: 90px;text-align:center;">Final</td>
<td style="line-height:20px;width: 390px;text-align:center;">In case of non-government deductors, payment details of TDS / TCS deposited in bank by deductor have matched with the payment details
mentioned in the TDS / TCS statement filed by the deductors. In case of government deductors, details of TDS / TCS booked in Government
account have been verified by Pay & Accounts Officer (PAO)</td>
</tr>
<tr>
<td style="line-height:20px;width: 60px;text-align:center;"><b>O</b></td>
<td style="line-height:20px;width: 90px;text-align:center;">Overbooked</td>
<td style="line-height:20px;width: 390px;text-align:center;">Payment details of TDS / TCS deposited in bank by deductor have matched with details mentioned in the TDS / TCS statement but the
amount is over claimed in the statement. Final (F) credit will be reflected only when deductor reduces claimed amount in the statement or
makes new payment for excess amount claimed in the statement</td>
</tr>
</table>';


 

$html.='';
     
          
       
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
         }  
        
    else if ($_GET["type"] == "view_appointmentMeha") {
         $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
         $sql = "SELECT * FROM candidate WHERE   dept_remark='selected' AND id='".$_GET['id']."' ORDER BY id DESC";
     
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    
    		    $current_year = date("Y");
$next_year = date("Y") + 1;
$year_string = $current_year . "-" . $next_year;

$string = "OHC/APP/".$_GET['id']."/" . $year_string;
    		    
    		    
 $html=' 

 
<table>
    <tr>
        <td style="width: 540; text-align:center;"><h2><strong><u>Appointment Letter</u></strong></h2></td>
    </tr>
    <br>
 
    <tr>
        <td style="width:270; color: red; text-align:left;">Ref.: '.$string.'</td>
        <td style="width:200; color: red; text-align:right;">Date.: '.date("d-m-Y").'</td>
    </tr>
    <br>

    <tr>
        <td style="width: 540; text-align:center;"><h5>SUB: APPOINTMENT LETTER</h5></td>
    </tr>
    <br>
    <br>

    <tr>
        <td style="width:540; color: red;">Dear '.$row['candidate_name'].'</td>
    </tr>
    <br>
    <tr>
        <td style="width:540;">We are pleased to appoint you as "'.$row['finaldesignation'].'" - "'.$row['finaldepartment'].'" in the Meha Pharma Private Limited
        (Hereinafter referred to as “the Company”) based at Goa with effect from '.$row['tentative_joining_date'].' on the terms and conditions specified below: -:</td>
    </tr>
    <br>

    <tr>
        <td style="width: 20;">1.</td>
        <td style="width:520;">SALARY AND ALLOWANCES:<br>You shall be entitled to salary and allowances as mentioned in Annexure – I</td>
    </tr>
    <br>

    <tr>
        <td style="width: 20;">2.</td>
        <td style="width:520;">PLACE OF POSTING AND TRANSFER:<br>

Your place of posting, at present, will be at [Location]. You are liable to be posted / transferred to another department / post / location of the Company in India or abroad, as may be required. 
for the business of the Company. Your employment Can be transferred to any other joint venture companies, individual group companies, subsidiaries, or affiliates of the company currently existing or which may get incorporated in the future in India or abroad. Upon such transfer / posting the terms and conditions of service applicable to such post or at the place of transfer shall be applicable to you.
</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">3.</td>
        <td style="width:520;">This appointment is subject to receipt of satisfactory verification of the particulars given by you in your application form.</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">4.</td>
        <td style="width:520;">The company will expect you to work, in any section in which you are placed, with a high standard of initiative, efficiency and economy.</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">5.</td>
        <td style="width:540;">You will not give out any one, by word of mouth or otherwise, particular of details of our manufacturing process, technical know-how, administrative and our organizational matters of a confidential or secret nature which you may be a part of by virtue of your presence and working with our organization.</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">6.</td>
        <td style="width:520;">You shall abide by the rules and regulations of company, which are in force at present and also, as amended from time to time</td>
    </tr>
    <tr>
        <td style="width: 20;">7.</td>
        <td style="width:520;">You shall devote your whole time and attention exclusively to the work entrusted to you. You will not engage yourself directly or indirectly to work for any person, firm or company in any capacity.</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">8.</td>
        <td style="width:520;">You shall obey all lawful and reasonable instruction given to you by your superiors. You also hereby undertake to submit true and faithful information and / or explanation when required in respect of matters entrusted to you</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">9.</td>
        <td style="width:520;">You shall not accept any commission or any kind of gratification in cash or in kind from any person, factory, firm or company having dealing with this company and if you are offered any.</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">10.</td>
        <td style="width:520;">After confirmation, your annual increment will be based upon and granted on your satisfactory and diligent discharge of duties. Annual increment may be withheld at the discretion of the management; in case your work / conduct is not found up to the satisfaction. In case of resignation, the notice period shall be 45 days from the date of resignation or otherwise effective salary shall be compensated in lieu of your notice period.</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">11.</td>
        <td style="width:520;">Your address as given in your application form and as mentioned at the beginning of this letter will be deemed to be correct for the purpose of sending any communication to you. In case of any change in your address, you will inform the same to the management in writing within 3 days of such a change. Any communication sent to you, at your last known address, will be deemed to have been served upon you.</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">12.</td>
        <td style="width:520;">Your absence for a continuous period of 3 days without intimation (including absence when leave though applied for but not granted) or overstay for a period of 3 days after expiry of leave, will entail loss of your lien on the job and your services shall automatically come to an end without notice or intimation to you by the management. The management will presume that you have abandoned the employment of your own accord.</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">13.</td>
        <td style="width:520;">Sanction of privilege leave will depend upon the exigencies of work and shall be at the discretion of the management. For getting such a leave, it shall be your duty to apply at least three days in advance.</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">14.</td>
        <td style="width:520;">You will abide by the standing orders applicable to the company, other rules and regulations and service conditions applicable from time to time, governing the conduct and disciplinary matter pertaining to the employees of the management.</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">15.</td>
        <td style="width:540;">You shall be responsible for maintaining CGMP at our factory and also to comply with all statuary laws applicable to our factory.</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">16.</td>
        <td style="width:540;">Not with standing anything stated in this letter, the management reserves its exclusive right to terminate this appointment immediately and without giving any notice or compensation for any acts, of breach of conduct, misbehavior with your superiors, gross negligence of duty or for violation of any of the above conditions on your part and in such a case decision of the management of this company shall be final and binding on both the parties.</td>
    </tr>    <br>

    <tr>
        <td style="width: 20;">17.</td>
        <td style="width:540;">Please sign the duplicate of this letter as a token of your acceptance of above Terms and Condition.</td>
    </tr>    <br>

    <tr>
        <td style="width:180px;text-align: left;">Thanking you,</td>
        <td style="width:180px;"> </td>
        <td style="width:180px;"> </td>
    </tr>    <br>

    <tr>
        <td style="width:180px;text-align: center; font-weight: bold;">For Cyclone Pharmaceutical</td>
        <td style="width:180px;"> </td>
        <td style="width:180px;"> </td>
    </tr>    <br>

    <tr>
        <td style="width:180px; text-align: left;">Authorized Signatory</td>
        <td style="width:180px;"> </td>
        <td style="width:180px;"> </td>
    </tr>    <br>

    <tr>
        <td style="width:540px; text-align: right;">Accepted above terms & conditions</td>
        <td style="width:180px;"> </td>
        <td style="width:180px;"> </td>
    </tr>    <br>

    <tr>
        <td style="width:540px; text-align: right;">Signature</td>
        <td style="width:180px;"> </td>
        <td style="width:180px;"> </td>
    </tr>
</table>
 ';
    		    
    		    
    
    	
    		}
    	}

           $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('generateHrOffer.pdf', 'I');
    
    }
    
 }
$conn->close();
?>