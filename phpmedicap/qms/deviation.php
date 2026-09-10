<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('response_token: test123456');

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
    
    
    
    if ($_GET["type"] == "saveDeviation") {
        $sql = "INSERT INTO deviation (deviation_for,observed_in,description,immediate_action,impact_assessment,impact_other,department_review,initiate_date) VALUES ('".$input["deviation_for"]."','".json_encode($input["observed_in"])."','".$input["description"]."','".$input["immediate_action"]."','".$input["impact_assessment"]."','".$input["impact_other"]."','".$input["department_review"]."','$entry_date')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getPendingAttachments") {
        $output = Array();
          $sql = "SELECT * FROM deviation WHERE status !='CLOSED' AND initiate_dept IN ('".$_GET["department"]."', 'Master', 'Quality Assurance')";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
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
    
    elseif ($_GET["type"] == "initiateDeviation") {
        $sql = "INSERT INTO deviation (deviation_for, material_code, stage, document_no, observed_in, description, immediate_action, impact_assessment, impact_other, initiate_dept, initiate_by, initiate_date) VALUES ('".$input["deviation_for"]."','".$input["material_code"]."','".$input["stage"]."','".$input["document_no"]."','".json_encode($input["observed_in"])."','".$input["description"]."','".$input["immediate_action"]."','".$input["impact_assessment"]."','".$input["impact_other"]."','".$_GET["department"]."','".$_GET["emp_id"]."','$entry_date')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getPendingAttachments") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status !='CLOSED' AND initiate_dept IN ('".$_GET["department"]."', 'Master', 'Quality Assurance')";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
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
	    
	    $PRASAD = $_POST["particular"];
        if(isset($_FILES['attachment1'])) {
            $id = date("Ymd", $timestamp);
            $file_tmp =$_FILES['attachment1']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachment1']['name'])));
            $file_name = $id.$PRASAD.".".$file_ext;
            $attachment = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/deviation/".$file_name);
            
             $sql = "INSERT INTO deviation_files (deviation_no, particular, file) VALUES ('".$_GET["deviation_no"]."', '".$_POST["particular"]."', '$attachment')";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } else {
            echo "{\"status\":\"failed\"}";
        }
	} else if ($_GET["type"] == "getDeviationDetails") {
        $output = array();
        $sql = "SELECT * FROM deviation WHERE deviation_no='".$_GET["deviation_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
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
    } else if($_GET["type"] == "getPendingDeptChecking") {
        $output = Array();
         $sql = "SELECT *,(select count(status) from deviation_comments where  status='pending' and dev_no=a.deviation_no) as status_count
         FROM deviation a WHERE a.status ='PENDING' AND a.initiate_dept IN ('".$_GET["department"]."', 'Master', 'Quality Assurance') having status_count=0 ";
        //  $sql = "SELECT *| FROM deviation a WHERE a.status ='PENDING' AND a.initiate_dept IN ('".$_GET["department"]."', 'Master', 'Quality Assurance')";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
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
    } else if ($_GET["type"] == "checkDeptDeviation") {
        $sql = "UPDATE deviation SET status='INITIATED CHECKED', initiate_check_by='".$_GET["emp_id"]."', inititate_check_date='$entry_date' WHERE deviation_no='".$_GET["deviation_no"]."'";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getPendingQAVerification") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status ='INITIATED CHECKED'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
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
    } else if ($_GET["type"] == "verifyDeviation") {
        $sql = "UPDATE deviation SET status='INITIATED VERIFY', classification='".$input["classification"]."', initiate_verify_by='".$_GET["emp_id"]."', inititate_verify_date='$entry_date' WHERE deviation_no='".$input["deviation_no"]."'";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getPendingQAApproval") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status ='INITIATED VERIFY'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
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
    } else if ($_GET["type"] == "approveDeviation") {
        $sql = "UPDATE deviation SET status='INITIATED APPROVAL', deviation_status='".$input["deviation_status"]."', rca_prepared='".$input["rca_prepared"]."', justification='".$input["justification"]."', initiate_approve_by='".$_GET["emp_id"]."', inititate_approve_date='$entry_date' WHERE deviation_no='".$input["deviation_no"]."'";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getPendingInvestigation") {
        $output = Array();
        
         $sql = "SELECT * FROM deviation WHERE (status ='INITIATED VERIFY' or status ='INITIATED APPROVAL' ) and deviation_status='APPROVED'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
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
        
        
        
        //$sql = "SELECT * FROM deviation  WHERE status ='INITIATED APPROVAL' AND initiate_dept IN ('".$_GET["department"]."', 'Master')";
        //$result = $conn->query($sql);
        //if($result->num_rows > 0){
            //while($row = $result->fetch_assoc()) {
                //$row["observed_in"] = json_decode($row["observed_in"]);
                //$output1 = array();
	            //$sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
	            //$result1 = $conn->query($sql1);
	           // if ($result1->num_rows > 0) {
	                //while ($row1 = $result1->fetch_assoc()) {
	                    //$output1[] = $row1;
	                //}
	           // }
	           // $row["attachments"] = $output1;
                //$output[] = $row;
            //}
       // }
        //echo json_encode($output);
    } else if ($_GET["type"]=="saveInvestigation") {
        $sql = "UPDATE deviation SET status='INVESTIGATION', investigation_details='".$input["investigation_details"]."', investigation_by='".$_GET["emp_id"]."', investigation_date='$entry_date' WHERE deviation_no='".$input["deviation_no"]."'";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getPendingAssessments") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status ='INVESTIGATION'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
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
    } else if ($_GET["type"] == "saveAssessment") {
        if($_GET["impact"]='Available'){
            
         $sql = "UPDATE deviation SET status='ASSESSMENT', assessment_impact='".$input["assessment_impact"]."', assessment_risk='".$input["assessment_risk"]."', risk_no='".$input["risk_no"]."', previously_occured='".$input["previously_occured"]."', prev_ref_no='".$input["prev_ref_no"]."', issamerootcause='".$input["issamerootcause"]."', root_ref_no='".$input["root_ref_no"]."', prev_investigation='".$input["prev_investigation"]."', prev_investigation_no='".$input["prev_investigation_no"]."', assessment_comment='".$input["assessment_comment"]."', deviation_cause='".$input["deviation_cause"]."', assessment_by='".$_GET["emp_id"]."', assessment_date='$entry_date' WHERE deviation_no='".$input["deviation_no"]."'";
        }else{
            
        $sql = "UPDATE deviation SET status='ASSESSMENT',quality_impact='".$input["quality_impact"]."', category='".$input["category"]."', assessment_impact='".$input["assessment_impact"]."', assessment_risk='".$input["assessment_risk"]."', risk_no='".$input["risk_no"]."', previously_occured='".$input["previously_occured"]."', prev_ref_no='".$input["prev_ref_no"]."', issamerootcause='".$input["issamerootcause"]."', root_ref_no='".$input["root_ref_no"]."', prev_investigation='".$input["prev_investigation"]."', prev_investigation_no='".$input["prev_investigation_no"]."', assessment_comment='".$input["assessment_comment"]."', deviation_cause='".$input["deviation_cause"]."', assessment_by='".$_GET["emp_id"]."', assessment_date='$entry_date' WHERE deviation_no='".$input["deviation_no"]."'";
        }
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getPendingActionPlan") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status ='ASSESSMENT' AND initiate_dept IN 
        ('".$_GET["department"]."', 'Master')";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
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
    } else if ($_GET["type"] == "saveActionPlan") {
        $sql = "UPDATE deviation SET status='ACTION PLAN', correct_action='".json_encode($input["correct_action"])."', remidial_action='".json_encode($input["remidial_action"])."', prevent_action='".json_encode($input["prevent_action"])."', actionplan_by='".$_GET["emp_id"]."', actionplan_date='$entry_date' WHERE deviation_no='".$input["deviation_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getPendingRecommendations") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status ='ACTION PLAN'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $row["remidial_action"] = json_decode($row["remidial_action"]);
	            $row["prevent_action"] = json_decode($row["prevent_action"]);
	            $row["correct_action"] = json_decode($row["correct_action"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveRecommendation") {
        $sql = "UPDATE deviation SET status='RECOMMENDATION',recommendation='".$input["recommendation"]."', batch_disposition='".$input["batch_disposition"]."', qa_comment='".$input["qa_comment"]."', recommend_by='".$_GET["emp_id"]."', recommend_date='$entry_date' WHERE deviation_no='".$input["deviation_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getPendingEvaluations") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status ='RECOMMENDATION'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $row["remidial_action"] = json_decode($row["remidial_action"]);
	            $row["prevent_action"] = json_decode($row["prevent_action"]);
	            $row["correct_action"] = json_decode($row["correct_action"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveEvaluation") {
        $sql = "UPDATE deviation SET status='EVALUATION',cqa_comment='".$input["cqa_comment"]."', evaluation_by='".$_GET["emp_id"]."', evaluation_date='$entry_date' WHERE deviation_no='".$input["deviation_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getPendingClosing") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status ='EVALUATION'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $row["remidial_action"] = json_decode($row["remidial_action"]);
	            $row["prevent_action"] = json_decode($row["prevent_action"]);
	            $row["correct_action"] = json_decode($row["correct_action"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveClosing") {
        $sql = "UPDATE deviation SET status='CLOSED', close_by='".$_GET["emp_id"]."', close_date='$enty_date' WHERE deviation_no='".$input["deviation_no"]."'";
        if ($conn->query($sql)) {
            
            if($_GET["challan_no"]!=''){
            $sql1 = "UPDATE challan_materials SET status='inprocess', receiving='inprocess' WHERE challan_no='".$_GET["challan_no"]."'";
           $conn->query($sql1);
            }
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getDeviationLog") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE 
        initiate_dept IN ('".$_GET["department"]."', 'Master', 'Quality Assurance')";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $row["remidial_action"] = json_decode($row["remidial_action"]);
	            $row["prevent_action"] = json_decode($row["prevent_action"]);
	            $row["correct_action"] = json_decode($row["correct_action"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadDeviationLog") {
        $_GET['filename'] = 'Deviation Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center;color:brown">Deviation Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.	</td>
                    <td style="width: 10%;">Dev No.	</td>
                    <td style="width: 10%;">Doc No.	</td>
                    <td style="width: 15%;">Deviation For	</td>
                    <td style="width: 15%;">Material code	</td>
                    <td style="width: 15%;"> Initiate Dept	</td>
                    <td style="width: 15%;">Initiate Date	</td>
                    <td style="width: 15%;">Initiate by		</td>
                       
                </tr>
            </thead>';
              $i=1;
       $output = Array();
        $sql = "SELECT * FROM deviation WHERE initiate_dept IN ('".$_GET["department"]."', 'Master', 'Quality Assurance')";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row["observed_in"] = json_decode($row["observed_in"]);
                $output1 = array();
	            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $row["remidial_action"] = json_decode($row["remidial_action"]);
	            $row["prevent_action"] = json_decode($row["prevent_action"]);
	            $row["correct_action"] = json_decode($row["correct_action"]);
                $output[] = $row;
                $html.='<tr nobr="true">
                       <td style="width: 5%;">'. $i.'</td>
                        <td style="width: 10%;">'.$row['deviation_no'].'.</td>
                        <td style="width: 10%;">'.$row['document_no'].'</td>
                        <td style="width: 15%;">'.$row['deviation_for'].'</td>
                        <td style="width: 15%;">'.$row['material_code'].'</td>
                        <td style="width: 15%;">'.$row['initiate_dept'].'</td>
                        <td style="width: 15%;">'.date('d-m-Y',strtotime($row['initiate_date'])).'</td>
                         <td style="width: 15%;">'.$row['initiate_check_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Deviation Log.pdf', 'I');
        
    }else if ($_GET['type'] == 'downloadDeviationRecord') {
          $_GET['filename'] = 'Deviation Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html.='<h3 style="text-align:center;color:brown">Deviation Form</h3>
                <table border="0.1" cellpadding="5">';
                $html.=' ';
                $sql = "SELECT * FROM deviation WHERE  id='".$_GET["id"]."' 
        AND deviation_no='".$_GET['deviation_no']."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM deviation_files WHERE deviation_no='".$row["deviation_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
                $html.='   <tr>
                <td style="width:30%;background-color:#DDDAD9;"><b>Document No.</b></td>
                 <td style="width:70%">'.$row['document_no'].'</td>
                </tr>
                <tr>
                <td style="width:30%;background-color:#DDDAD9;"><b>Deviation For:</b></td>
                 <td style="width:70%">'.$row['deviation_for'].'</td>
                </tr>
                <tr>
                <td style="width:30%;background-color:#DDDAD9;"><b>Material Code</b></td>
                 <td style="width:70%">'.$row['material_code'].'</td>
                </tr>
                <tr>
                <td style="width:30%;background-color:#DDDAD9;"><b>Description Of Deviation</b></td>
                 <td style="width:70%">'.$row['description'].'</td>
                </tr>
                <tr>
                <td style="width:30%;background-color:#DDDAD9;"><b>Batch No/Equpment ID/ Instrument ID / Document No/No of Days</b></td>
                 <td style="width:70%">'.$row['document_no'].'</td>
                </tr>
                <tr>
                <td style="width:30%;background-color:#DDDAD9;"><b>Immediate Action / Remedial Action</b></td>
                 <td style="width:70%">'.$row['immediate_action'].'</td>
                </tr>
                <tr>
                <td style="width:30%;background-color:#DDDAD9;"><b>Impact Assessment</b></td>
                 <td style="width:70%">'.$row['impact_assessment'].'</td>
                </tr>
                <tr>
                <td style="width:30%;background-color:#DDDAD9;"><b>Other Batches Implicated(if any)</b></td>
                 <td style="width:70%">'.$row['impact_other'].'</td>
                </tr>';
	                }
	            }
            }
        }
                     $html.=' 
                </table>
               <h2>Deviation Data Observed In:</h2>
               <table cellpadding="5" border="0.1">
                <tr style="text-align:center;background-color:#DDDAD9;">
               <td style="width:10%;text-align:center"><b>Sr.</b></td>
                <td style="width:90%;text-align:center"><b>Observed In</b></td>
               </tr>
                <tr>
               <td style="width:10%;"></td>
                <td style="width:90%;"></td>
               </tr>
               </table>
               <h2>Attachments:</h2>
               <table cellpadding="5" border="0.1">
               <tr style="text-align:center;background-color:#DDDAD9;">
               <td style="width:10%;text-align:center"><b>Sr.</b></td>
                  <td style="width:90%;text-align:center"><b>Particular</b></td>
                    </tr>
                <tr>
               <td style="width:10%"><b></b></td>
                  <td style="width:90%"><b></b></td>
                    </tr>
	              </table>
                <h2>Classification Of Deviation :</h2>
               <table cellpadding="5" border="0.1">
               <tr>
               <td style="width:30%;text-align:center;background-color:#DDDAD9;"><b>Classification Of Deviation</b></td>
                  <td style="width:70%;text-align:center"></td>
                    </tr>
                    </table>
                         <h2>Head Quality Assurance Deviation is :</h2>
               <table cellpadding="5" border="0.1">
                    <tr>
               <td style="width:30%;background-color:#DDDAD9;"><b>Head Quality Assurance Deviation is</b></td>
                  <td style="width:70%;"></td>
                    </tr>
                    <tr>
               <td style="width:30%;background-color:#DDDAD9;"><b>RCA to be prepared</b></td>
                  <td style="width:70%;"></td>
                    </tr>
                     </table>
                         <h2>Investigation details:</h2>
               <table cellpadding="5" border="0.1">
                    <tr>
               <td style="width:30%;background-color:#DDDAD9;"><b>Investigation details (To be filled by Initiator):</b></td>
                  <td style="width:70%;"></td>
                    </tr>
                    </table>
                    <br pagebreak="true"/>.
                    <h2>Steps:</h2>
                    <table cellpadding="5" border="0.1">
                    <tr>
                    <td style="width:50%;background-color:#DDDAD9;"><b>Impact on Quality of the product:</b></td>
                   <td style="width:50%;"></td>
                    </tr>
                    <tr>
                    <td style="width:50%;background-color:#DDDAD9;"><b>Category of Deviation:</b></td>
                      <td style="width:50%;"></td>
                    </tr>
                     <tr>
                    <td style="width:50%;background-color:#DDDAD9;"><b>Regulatory Impact Assessment required ?</b></td>
                      <td style="width:50%;"></td>
                    </tr>
                     <tr>
                    <td style="width:50%;background-color:#DDDAD9;"><b>Risk Assessment required ?</b></td>
                      <td style="width:50%;"></td>
                    </tr>
                     <tr>
                    <td style="width:50%;background-color:#DDDAD9;"><b>Deviation occurred previously ?</b></td>
                    <td style="width:50%;"></td>
                    </tr>
                     <tr>
                    <td style="width:50%;background-color:#DDDAD9;"><b>QA Assessment:</b></td>
                    <td style="width:50%;"></td>
                    </tr>
                    <tr>
                    <td style="width:50%;background-color:#DDDAD9;"><b>Root / Probable / Contributory cause (s) of Deviation:</b></td>
                    <td style="width:50%;"></td>
                    </tr>
                    </table>
                     <h2>Corrective Actions / Preventive Actions :</h2>
                    <table cellpadding="5" border="0.1">
                   <tr style="text-align:center;background-color:#DDDAD9;">
                    <td style="width:10%;text-align:center"><b>Sr.</b></td>
                    <td style="width:20%;text-align:center"><b>Remedial Action</b></td>
                    <td style="width:20%;text-align:center"><b>Status</b></td>
                    <td style="width:25%;text-align:center"><b>Responsible Person,(if not completed)</b></td>
                    <td style="width:25%;text-align:center"><b>Target Completion Date, (if not completed)</b></td>
                    </tr>
                   <tr>
                    <td style="width:10%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;"></td>
                    </tr>
                    </table>
                    <h2>Final Recommendation ( External party / Regulatory):</h2>
                    <table cellpadding="5" border="0.1">
                    <tr>
                    <td style="width:100%"><b>Final Recommendation ( External party / Regulatory):</b></td>
                    </tr>
                    <tr>
                    <td style="width:100%"><b>Batch Disposition:</b></td>
                    </tr>
                    <tr>
                      <td style="width:100%"><b>QA Comment:</b></td>
                    </tr>
                    </table>
                    <h2>Evaluation by Corporate Quality Assurance Comments:</h2>
                   <table cellpadding="5" border="0.1">
                    <tr>
                      <td style="width:100%"><b>Evaluation by Corporate Quality Assurance Comments:</b></td>
                    </tr>
                 </table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Deviation Log.pdf', 'I');
     

}
}
$conn->close();
?>