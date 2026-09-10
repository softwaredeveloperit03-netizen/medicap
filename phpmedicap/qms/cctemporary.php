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
        $sql = "INSERT INTO cctemporary (document_type, cc_related, change_title, document_no, existing_procedure, proposed_change, change_reason, affected_for,initiate_dept, initiate_by, initiate_date,action_plan) VALUE ('".$input["document_type"]."', '".$input["cc_related"]."', '".$input["change_title"]."', '".$input["document_no"]."', '".$input["existing_procedure"]."', '".$input["proposed_change"]."', '".$input["change_reason"]."', '".$input["affected_for"]."','".$_GET["department"]."', '".$_GET["emp_id"]."', '$entry_date', '".json_encode($input["actions"])."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingAttachements") {
        $output = array();
        $sql = "SELECT * FROM cctemporary WHERE status !='CLOSED' AND initiate_dept IN ('".$_GET["department"]."', 'Master', 'Quality Assurance')";
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
            move_uploaded_file($file_tmp,"../upload/cctemporary/".$file_name);
            
            $sql = "INSERT INTO cctemporary_files (cc_no, type, particular, file) VALUES ('".$_GET["cc_no"]."', '".$_POST["type"]."', '".$_POST["particular"]."', '$attachment')";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } else {
            echo "{\"status\":\"failed\"}";
        }
	} else if ($_GET["type"] == "deleteAttachment") {
	    $sql = "DELETE FROM cctemporary_files WHERE id='".$_GET["id"]."'";
	    if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
	} else if ($_GET["type"] == "getCCDetails") {
        $output = array();
        $sql = "SELECT * FROM cctemporary WHERE cc_no='".$_GET["cc_no"]."'";
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
                echo json_encode($row);
                break;
            }
        } else {
            echo "{}";
        }
    } else if ($_GET["type"] == "getInitiatedCC") {
        $output = array();
        $sql = "SELECT * FROM cctemporary WHERE status ='Initiated' AND initiate_dept IN ('".$_GET["department"]."', 'Master')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["action_plan"] = json_decode($row["action_plan"]);
                
                $output1 = array();
	            $sql1 = "SELECT * FROM cctemporary_files WHERE cc_no='".$row["cc_no"]."'";
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
        $sql = "UPDATE cctemporary SET status='Initiated Dept Checking', dept_remark='".$input["remark"]."', dept_check_by='".$_GET["emp_id"]."', dept_check_date='$entry_date' WHERE cc_no='".$input["cc_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingPreApproval") {
        $output = array();
        $sql = "SELECT * FROM cctemporary WHERE status ='Initiated Dept Checking'";
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
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "savePreApprovalCC") {
        $sql = "UPDATE cctemporary SET status='PRE APPROVAL OF QA EXECUTIVE',change_proposed='".$input["proposed_change"]."', change_status='".$input["change_status"]."', reject_reason='".$input["reject_reason"]."', preapproval_by='".$_GET["emp_id"]."', preapproval_date='$entry_date' WHERE cc_no='".$input["cc_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingPreApprovalChecking") {
        $output = array();
        $sql = "SELECT * FROM cctemporary WHERE status ='PRE APPROVAL OF QA EXECUTIVE' AND LOWER(change_status)='approved'";
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
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "preApprovalChecking") {
        $sql = "UPDATE cctemporary SET status='PRE APPROVAL OF QA HEAD', preapproval_remark='".$input["remark"]."', preapproval_check_by='".$input["emp_id"]."', preapproval_check_date='$entry_date' WHERE cc_no='".$input["cc_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $departments = $input["departments"];
            for ($i = 0; $i < count($departments); $i++) {
                $department = $departments[$i];
                $sql = "INSERT INTO cc_departments (cc_no, department) VALUES ('".$input["cc_no"]."', '$department')";
                $conn->query($sql);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingConcernedDeptReview") {
        $output = array();
        $sql = "SELECT c.*, d.id as dept_id FROM cc_departments d LEFT JOIN cctemporary c ON d.cc_no=c.cc_no WHERE d.status ='pending' AND LOWER(c.change_status)='approved' AND (department='".$_GET["department"]."' OR department LIKE '%%')";
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
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDeptReview") {
        $sql = "UPDATE cc_departments SET remark='".$input["remark"]."', status='done', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE id='".$input["dept_id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            $sql = "SELECT id FROM cc_departments WHERE cc_no='".$input["cc_no"]."' AND status='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows == 0) {
                $sql = "UPDATE cctemporary SET status='CONCERNED DEPT REVIEW' WHERE cc_no='".$input["cc_no"]."'";
                $conn->query($sql);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingPostApproval") {
        $output = array();
        $sql = "SELECT * FROM cctemporary WHERE status ='CONCERNED DEPT REVIEW' AND LOWER(change_status)='approved'";
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
    } else if ($_GET["type"] == "savePostApproval") {
        $sql = "UPDATE cctemporary SET status='POST APPROVAL OF QA HEAD', postapproval_comment='".$input["postapproval_comment"]."',postapproval_status='".$input["postapproval_status"]."', batches_no='".$input["batches_no"]."', postapproval_by='".$_GET["emp_id"]."', postapproval_date='".$entry_date."' WHERE cc_no='".$input["cc_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingPostApprovalbyCQA") {
        $output = array();
        $sql = "SELECT * FROM cctemporary WHERE status ='POST APPROVAL OF QA HEAD' AND LOWER(change_status)='approved'";
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
    } else if ($_GET["type"] == "savePostApprovalChecking") {
        $sql = "UPDATE cctemporary SET status='POST APPROVAL OF CQA', postapproval1_comment='".$input["postapproval_comment"]."', postapproval1_by='".$_GET["emp_id"]."', postapproval1_date='".$entry_date."' WHERE cc_no='".$input["cc_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingExtension") {
        $output = array();
        $sql = "SELECT * FROM cctemporary WHERE initiate_dept IN ('".$_GET["department"]."', 'Master') 
        AND status='POST APPROVAL OF CQA' AND UPPER(change_status)='APPROVED' AND UPPER(postapproval_status)='APPROVED'";
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
        $sql = "SELECT * FROM cctemporary WHERE status='ADDITIONAL EVALUATION'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
	            $sql1 = "SELECT * FROM cctemporary_files WHERE cc_no='".$row["cc_no"]."' AND type !='Additional Evaluation Plan'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM cctemporary_files WHERE cc_no='".$row["cc_no"]."' AND type ='Additional Evaluation Plan'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments1"] = $output1;
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
    } else if ($_GET["type"] == "saveAdditional") {
        $sql = "UPDATE cctemporary SET status='POST IMPLEMENTATION', implementation_comment='".$input["implementation_comment"]."', ispermanent='".$input["ispermanent"]."', cqa_recommendation='".$input["cqa_recommendation"]."',additional_evaluation='".$input["additional_evaluation"]."', evaluation_by='".$_GET["emp_id"]."', evaluation_date='$entry_date' WHERE cc_no='".$input["cc_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingPostImplementation") {
        $output = array();
        $sql = "SELECT * FROM cctemporary WHERE status='POST IMPLEMENTATION'";
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
    } else if ($_GET["type"] == "savePostImplementation") {
        $sql = "UPDATE cctemporary SET status='COA RECOMMENDATION', implement_comment='".$input["comment"]."', implement_by='".$_GET["emp_id"]."', implement_date='$entry_date' WHERE cc_no='".$input["cc_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingClosing") {
        $output = array();
        $sql = "SELECT * FROM cctemporary WHERE status='COA RECOMMENDATION'";
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
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM cctemporary_files WHERE cc_no='".$row["cc_no"]."' AND type='CLOSURE'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments2"] = $output1;
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
    } else if ($_GET["type"] == "closeCC") {
        $sql = "UPDATE cctemporary SET status='CLOSED', close_comment='".$input["close_comment"]."', close_by='".$_GET["emp_id"]."', close_date='$entry_date' WHERE cc_no='".$input["cc_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getCCLog") {
        $output = array();
        $sql = "SELECT * FROM cctemporary WHERE initiate_dept IN ('".$_GET["department"]."', 'Quality Assurance', 'Master')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
	            $sql1 = "SELECT * FROM cctemporary_files WHERE cc_no='".$row["cc_no"]."' AND type !='Additional Evaluation Plan'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM cctemporary_files WHERE cc_no='".$row["cc_no"]."' AND type ='Additional Evaluation Plan'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments1"] = $output1;
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
    }
     else if ($_GET["type"] == "downloadCCLog") {
           $_GET['filename'] = 'CC Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
          $output = array();
        $sql = "SELECT * FROM cctemporary WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
	          
            
        $html.="";
        $html.='&nbsp;&nbsp;<b>Format No:</b>QA004/F/01-04<br>
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
                    <td style="width:25%;">'.date('d-m-Y',strtotime($row['initiate_date'])).'</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>A. CHANGE APPLICABLE TO: </b>(Part A : To be completed by change initiator) </td>
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
                </tr>';
                $output1 = array();
	            $sql1 = "SELECT * FROM cctemporary_files WHERE cc_no='".$row["cc_no"]."' AND type=''";
	            $result1 = $conn->query($sql1);
	            $i=1;
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                 $file=$row1['file'];
        $html.='<tr>
                    <td style="width:30%;">'.$i++.'</td>
                    <td style="width:70%;">
                         <a href="https://'.$_SERVER['SERVER_NAME'].'/api/gmptotal/upload/cctemporary/'.$row1['file'].'">'.$row1['particular'].'</a>
                    </td>
                </tr>';
                    }
                }
        $html.='
                <tr>
                    <td style="width:100%;"><b>* Attach separate sheet is required</b><br></td>
                </tr>
                </table>
                <br pagebreak="true"/>
                
                
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
                for($n=0;$n<count($action_plans);$n++){
                    $action=$action_plans[$n];
                
        $html.='<tr>
                    <td style="width:5%;">'.$j++.'</td>
                    <td style="width:20%;">'.$action->attribute.'</td>
                    <td style="width:7%;">'.$action->status.'</td>
                    <td style="width:48%;">'.$action->description.'</td>
                    <td style="width:20%;">'.$action->responsibility.'</td>
                </tr>';
                }
        $html.='</table>
                <br pagebreak="true"/>
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
                                <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['initiate_date'])).'</td>
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
                                <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['dept_check_date'])).'</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>PRE-APPROVAL OF CHANGE CONTROL:</b><br>
                        <b>Tick the Classification of the proposed change:</b> &nbsp;&nbsp;'.$row['change_proposed'].'<br>
                        <b>Proposed Changes are:</b> &nbsp;&nbsp;'.$row['change_status'].'<br>';
                        if ($row['change_status'] !== 'APPROVED') {
                            $html.='<b>Reason for Rejection:</b> &nbsp;&nbsp;'.$row['reject_reason'].'<br>';
                        } else {
                            $html.='<b>Reason for Rejection:</b> &nbsp;&nbsp; Not Applicable<br>';
                        }
                        $html.='
                        <b>QA Executive/ Designee:</b><br>
                        <table>
                            <tr>
                                 <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['preapproval_by'].'</td>
                                <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['preapproval_date'])).'</td>
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
                                <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['preapproval_check_date'])).'</td>
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
                                        <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row[$row3['entry_date']])).'</td>
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
                                        <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row4['entry_date'])).'</td>
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
                                        <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row5['entry_date'])).'</td>
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
                                        <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row6['entry_date'])).'</td>
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
                                        <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row7['entry_date'])).'</td>
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
                                        <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row11['entry_date'])).'</td>
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
	            $sql12 = "SELECT * FROM cc_departments WHERE department='AQA' AND cc_no='".$row["cc_no"]."'";
	            $result12 = $conn->query($sql12);
	            if ($result12->num_rows > 0) {
	                while ($row12 = $result12->fetch_assoc()) {
                        $html.='<tr>
                        <td style="width:100%;"><b>Evaluation by AQA department:</b><br>
                            <table>
                                <tr>
                                    <td style="width:33.33%; border:none;"><b>Name</b>:'.$row12['entry_by'].'</td>
                                    <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                                    <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row12['entry_date'])).'</td>
                                </tr>
                            </table>
                        </td>
                    </tr>';
	                }
	            }
	            else{
	                $html.='<tr>
                        <td style="width:100%;"><b>Evaluation by AQA department:</b><br>
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
                                        <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row13['entry_date'])).'</td>
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
                <br pagebreak="true"/>
                ';
                $j=1;
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
                                <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['postapproval_date'])).'</td>
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
                    <td style="width:5%;"><b>'.$j++.'</b></td>
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
                                <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['extension_date'])).'</td>
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
                            <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['extension_check_date'])).'</td>
                        </tr>
                        </table>
                    </td>
                                        
                </tr>
                <tr>
                    <td style="width:100%;"><b>Additional Impact Evaluation or Action Plan Requirements:</b><br>
                    '.$row['additional_evaluation'].'<div></div></td>
                </tr>
                </table>
                <br pagebreak="true"/>
                
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
                    <td style="width:100%;"><b>Temporary change to be made permanent:</b>'.$row['ispermanent'].'<br></td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <b>D.2 CHANGE CONTROL POST RECOMME NDATIONS: </b><br>
                        <b>CQA internal and external recommendations:</b>'.$row['close_comment'].'<br>
                        <div></div>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>E. CLOSURE OF CHANGE  CONTROL :</b><br>
                        <b>Comments by Quality Assurance Head (or Designee):</b><br><br>
                        <table>
                         <tr>
                            <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['close_by'].'</td>
                            <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                            <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['close_date'])).'</td>
                        </tr>
                        </table>
                    </td>                  
                </tr>
                <tr>
                    <td style="width:100%;"><b>F CLOSURE OF CHANGE CONTRTOL:</b><br>
                        <b>Comment by Corporate Quality Assurance Head (or Designee):</b><br><br>
                        <table>
                         <tr>
                            <td style="width:33.33%; border:none;"><b>Name</b>:'.$row['close_by'].'</td>
                            <td style="width:33.33%; border:none;"><b>Signature:</b>_______________</td>
                            <td style="width:33.33%; border:none;"><b>Date:</b>'.date('d-m-Y',strtotime($row['close_date'])).'</td>
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
	            $sql1 = "SELECT * FROM cctemporary_files WHERE cc_no='".$row["cc_no"]."' AND type !=''";
	            $result1 = $conn->query($sql1);
	            $i=1;
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                 $file=$row1['file'];
        $html.='<tr>
                    <td style="width:30%;">'.$i++.'</td>
                    <td style="width:70%;">
                         <a href="https://'.$_SERVER['SERVER_NAME'].'/api/gmptotal/upload/cctemporary/'.$row1['file'].'">'.$row1['particular'].'</a>
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
       // $_GET['filename'] = 'CC Record'; $_GET['pdftype'] = 'onlyheader'; //include("../pdfimp2.php");
          $_GET['filename'] = 'Leave Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center;color:brown">Change Control Record</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width: 9%;">Change Control No</td>
                    <td rowspan="2" style="width: 10%;">Initiating Dept</td>
                    <td rowspan="2" style="width: 10%;">Date of Initiation</td>
                    <td style="width: 22%;">Details of Change Control</td>
                    <td rowspan="2" style="width: 10%;">Classification of Change</td>
                    <td rowspan="2" style="width: 10%;">Change Control  Status  Approved/Rejected/Cancelled Date</td>
                    <td rowspan="2" style="width: 10%;">Target  Completion Date</td>
                    <td rowspan="2" style="width: 10%;">Extension if any (TCD)</td>
                    <td rowspan="2" style="width: 9%;">Change Control Closing (Sign/  Date)</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:22%;">Request Change for batch no.& No of Days</td>
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
                                <td style="width: 9%;">'.$row['cc_no'].'</td>
                                <td style="width: 10%;">'.$row['initiate_dept'].'</td>
                                <td style="width: 10%;">'.$row['initiate_date'].'</td>
                                <td style="width: 22%;">'.$row['batches_no'].'</td>
                                <td style="width: 10%;">'.$row['proposed_change'].'</td>
                                <td style="width: 10%;">'.$row['change_status'].'</td>
                                <td style="width: 10%;">'.$row['postapproval_date'].'</td>
                                <td style="width: 10%;">'.$row['extension_plan'].'</td>
                                <td style="width: 9%;">'.$row['close_date'].'</td>
                            </tr>';
                        $i++;
                    }
                }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('CC Record.pdf', 'I');
    }

     

}else {
    echo "Invalid Token";
}

$conn->close();
?>