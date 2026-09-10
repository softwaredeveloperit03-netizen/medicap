<?php
require 'db.php';
require 'token.php';
require 'tcpdf/tcpdf.php';
  
// ini_set('display_errors', 1);
//  error_reporting(E_ALL);
 

$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0){
while($row = $result->fetch_assoc()) {
	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	$string = explode("$",$string);
	$_GET["emp_id"] = $string[0];
	$_GET["department"] = $string[1];
	break;
}

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
// $conn->query($sql);

if($_GET["type"]=="getDepartments"){
	$sql = "SELECT * FROM department WHERE status='active' AND department_name !='Quality Assurance'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
            $row["status"] = false;
			$output[] = $row;
		}
	}
	echo json_encode($output);
} 
 
else if ($_GET["type"] == "saveformMeha") {
  $entry_date = date("Y-m-d H:i:s");
  $input = json_decode(file_get_contents('php://input'), true);
  if (!is_array($input)) {
    $input = $_POST;
  }

$department_code = "";
$dept_name = isset($input['department']) ? $input['department'] : (isset($input['department_name']) ? $input['department_name'] : '');
$sqlDC = "SELECT department_code FROM department WHERE department_name='".$conn->real_escape_string($dept_name)."'";
$result = $conn->query($sqlDC);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $department_code = $row['department_code'];
}

/* ================= FINANCIAL YEAR ================= */
// Financial year Apr–Mar
$currentMonth = date('m');
if ($currentMonth >= 4) {
    $financial_year = date('y') . "-" . date('y', strtotime('+1 year'));
} else {
    $financial_year = date('y', strtotime('-1 year')) . "-" . date('y');
}

/* ================= SERIAL NUMBER ================= */
$sqlSr = "SELECT MAX(sr_no) as max_sr 
          FROM changecontrol 
          WHERE department_code='$department_code' 
          AND financial_year='$financial_year'";

$resultSr = $conn->query($sqlSr);

$next_sr = 1;
if ($resultSr->num_rows > 0) {
    $rowSr = $resultSr->fetch_assoc();
    if (!empty($rowSr['max_sr'])) {
        $next_sr = $rowSr['max_sr'] + 1;
    }
}

$sr_no_formatted = str_pad($next_sr, 3, '0', STR_PAD_LEFT);

/* ================= DEVIATION NUMBER ================= */
$ctrl_no = "CCF/".$department_code."/".$financial_year."/".$sr_no_formatted;



    $changeReqFor = isset($input["changeReqFor"]) ? $input["changeReqFor"] : '';
    if (is_array($changeReqFor)) {
        $changeReqFor = implode(', ', $changeReqFor);
    }
    $titleOfcc = isset($input["titleOfcc"]) ? $conn->real_escape_string($input["titleOfcc"]) : '';
    $changeReqForOther = isset($input["changeReqForOther"]) ? $conn->real_escape_string($input["changeReqForOther"]) : '';
    $dept_name_val = isset($input["department_name"]) ? $conn->real_escape_string($input["department_name"]) : $conn->real_escape_string($dept_name);
    $justification = isset($input["JUSTIFICATION"]) ? $conn->real_escape_string($input["JUSTIFICATION"]) : '';
    $proposed = isset($input["PROPOSED"]) ? $conn->real_escape_string($input["PROPOSED"]) : '';
    $capaDate = isset($input["capaDate"]) ? $conn->real_escape_string($input["capaDate"]) : '';
    $capaDetails = isset($input["capaDetails"]) ? $conn->real_escape_string($input["capaDetails"]) : '';
    $changeCAPA = isset($input["changeCAPA"]) ? $conn->real_escape_string($input["changeCAPA"]) : '';

    $sql = "INSERT INTO `changecontrol`(  ctrl_no, department_code, financial_year, sr_no,
                `plant_id`, `department_name`, `entryBy`, `entryDate`, `status`, 
                `justification`, `proposed`, `capaDate`, `capaDetails`, 
                `changeCAPA`, `changeReqFor`, `changeReqForOther`,`titleOfcc`
            ) 
            VALUES ( '$ctrl_no','$department_code','$financial_year','$next_sr',
                '".$_GET["plant_id"]."', '".$dept_name_val."', '".$_GET["emp_id"]."', 
                '$entry_date', 'Pending', 
                '".$justification."', '".$proposed."', '".$capaDate."', 
                '".$capaDetails."', '".$changeCAPA."', '".$conn->real_escape_string($changeReqFor)."', 
                '".$changeReqForOther."','".$titleOfcc."'
            )";

    if ($conn->query($sql)) {
        $last_id = $conn->insert_id;
        echo json_encode(array("status" => "success", "id" => $last_id, "ctrl_no" => $ctrl_no));
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
}


 else if ($_GET["type"]=="saveform") {
    
            $input    = $_POST;
                     
                    
                $ic =1;    
            $sql = "SELECT max(id) as Key_Id  FROM changecontrol  ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                   $ic = $row['Key_Id'] + 1;
                }
            }else{
                 $ic =1;  
            }
             
                    $pid = $_GET["plant_id"];
                    $dp = $input["department_name"];
                        $exisDoc = "NA";
                        $changeDetDoc = "NA";
                        $justChangeDoc = "NA";
        
                    if (isset($_FILES["exisDoc"])) {
                        $file_tmp = $_FILES['exisDoc']['tmp_name'];
                        $file_ext = strtolower(end(explode('.', $_FILES['exisDoc']['name'])));
                        $exisDoc = $ic.$pid.$dp."exisDoc.".$file_ext;
                        move_uploaded_file($file_tmp, "../../upload/changeControl/" . $exisDoc);
                    }
                    
                    if (isset($_FILES["changeDetDoc"])) {
                        $file_tmp = $_FILES['changeDetDoc']['tmp_name'];
                        $file_ext = strtolower(end(explode('.', $_FILES['changeDetDoc']['name'])));
                        $changeDetDoc = $ic.$pid.$dp."changeDetDoc.".$file_ext;
                        move_uploaded_file($file_tmp, "../../upload/changeControl/" . $changeDetDoc);
                    }
        
                    if (isset($_FILES["justChangeDoc"])) {
                        $file_tmp = $_FILES['justChangeDoc']['tmp_name'];
                        $file_ext = strtolower(end(explode('.', $_FILES['justChangeDoc']['name'])));
                        $justChangeDoc = $ic.$pid.$dp."justChangeDoc.".$file_ext;
                        move_uploaded_file($file_tmp, "../../upload/changeControl/" . $justChangeDoc);
                    }
        
             
                    
  
	$sql= "INSERT INTO `changecontrol`(`plant_id`,`department_name`, `dateOfIssuance`, `section`, `nameOfProductDoc`, `batchNoDocNo`,
	`changeReqFor`, `existingProcedure`, `exisDoc`, `changedDetails`, `changeDetDoc`, `justificationOfChange`, `justChangeDoc`,
	`changeAffDoc`, `tentativeDateClosing`, `remark`, `entryBy`, `entryDate`,`status`,closinChecklist,checlistData,actionData)
	VALUES ('".$_GET["plant_id"]."','".$input["department_name"]."','".$input["dateOfIssuance"]."',
	'".$input["section"]."','".$input["nameOfProductDoc"]."','".$input["batchNoDocNo"]."','".$input["changeReqFor"]."',
	'".$input["existingProcedure"]."','".$exisDoc."','".$input["changedDetails"]."','".$changeDetDoc."',
	'".$input["justificationOfChange"]."','".$justChangeDoc."','".$input["changeAffDoc"]."','".$input["tentativeDateClosing"]."',
	'".$input["remark"]."','".$_GET["emp_id"]."','$entry_date' ,'Pending','[]','[]','[]')";
  
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
}

else if ($_GET["type"] == "getCCTravelHistory") {
    $ctrl_no = isset($_GET["ctrl_no"]) ? $conn->real_escape_string(trim($_GET["ctrl_no"])) : '';
    $plant_id = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : '';
    if ($ctrl_no === '' || $plant_id === '') {
        echo json_encode(array("found" => false, "stages" => array(), "message" => "CC number and plant required"));
        exit;
    }
    $sql = "SELECT id, ctrl_no, department_name, status, entryDate, entryBy, concernHodOn, concernHodBy, QaRevOn, QaRevBy, APPROVAL_QA, assessmentOn, assessmentBy, qa_head_remark, action_plan_assigned_to, target_completion_date, 
    qaReviewedOn, qaReviewedBy, closedOn, closedBy, ClosureOn, ClosureBy FROM changecontrol WHERE (ctrl_no = '".$ctrl_no."' OR id = '".intval($ctrl_no)."') AND plant_id = '".$plant_id."' LIMIT 1";
    $result = $conn->query($sql);
    if (!$result || $result->num_rows === 0) {
        echo json_encode(array("found" => false, "stages" => array(), "message" => "CC not found"));
        exit;
    }
    $row = $result->fetch_assoc();
    $stages = array();
    $stages[] = array("key" => "initiation", "label" => "Initiation", "completed" => !empty($row["entryDate"]), "date" => $row["entryDate"], "user" => $row["entryBy"]);
    $stages[] = array("key" => "dept_head", "label" => "Dept Head Review", "completed" => !empty($row["concernHodOn"]), "date" => $row["concernHodOn"], "user" => $row["concernHodBy"]);
    $stages[] = array("key" => "impact_action", "label" => "Impact & Action Plan", "completed" => !empty($row["QaRevOn"]) || !empty($row["APPROVAL_QA"]), "date" => $row["QaRevOn"], "user" => $row["QaRevBy"]);
    $dept_consent_done = in_array($row["status"], array("FOR_DEPT_CONSENT_AND_REVIEW", "For_QA_Head_Meha", "For_QA_Review_After_Assessment", "complete", "FinalCommentQA")) || !empty($row["assessmentOn"]);
    $stages[] = array("key" => "dept_consent", "label" => "Dept Consent & Review", "completed" => $dept_consent_done, "date" => null, "user" => null);
    $stages[] = array("key" => "assessment_qa", "label" => "Assessment by QA", "completed" => !empty($row["assessmentOn"]), "date" => $row["assessmentOn"], "user" => $row["assessmentBy"]);
    $stages[] = array("key" => "qa_head_assign", "label" => "QA Head Assign", "completed" => !empty($row["qa_head_remark"]) || !empty($row["target_completion_date"]), "date" => $row["target_completion_date"], "user" => $row["action_plan_assigned_to"]);
    $stages[] = array("key" => "action_review_qa", "label" => "Action & Review by QA", "completed" => !empty($row["qaReviewedOn"]), "date" => $row["qaReviewedOn"], "user" => $row["qaReviewedBy"]);
    $stages[] = array("key" => "cc_verification", "label" => "CC Verification", "completed" => !empty($row["closedOn"]), "date" => $row["closedOn"], "user" => $row["closedBy"]);
    $stages[] = array("key" => "closed", "label" => "Closed", "completed" => !empty($row["ClosureOn"]) || (isset($row["status"]) && (strtolower($row["status"]) === "complete" || strtolower($row["status"]) === "closed")), "date" => $row["ClosureOn"], "user" => $row["ClosureBy"]);
    echo json_encode(array("found" => true, "ctrl_no" => $row["ctrl_no"], "department_name" => $row["department_name"], "status" => $row["status"], "stages" => $stages));
    exit;
}

else if ($_GET["type"] == "getCCFordeptConcern") {
    $output = Array();
    $sql = "SELECT * FROM changecontrol WHERE status='Pending' AND plant_id = '".$_GET["plant_id"]."' 
    AND department_name = '".$_GET["deptName"]."' order by id desc";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
            $sql1 = "SELECT CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy FROM employee e1 
                WHERE e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1 && $result1->num_rows > 0) {
                $row1 = $result1->fetch_assoc();
                $row["initiatedBy"] = $row1["initiatedBy"];
            } else {
                $row["initiatedBy"] = $row["entryBy"];
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getCCForconsentAndReview") {
    $output = Array();
    $sql = "SELECT * FROM changecontrol WHERE status='QA HEAD COMMENT' AND plant_id = '".$_GET["plant_id"]."' 
     order by id desc";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                }
            }
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

else if ($_GET["type"] == "saveConcernHodComment") {
    $entry_date = date("Y-m-d H:i:s");
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    $comment = isset($input["CommentByQaHead"]) ? $conn->real_escape_string($input["CommentByQaHead"]) : '';
    $id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
    $sql = "UPDATE changecontrol SET status = 'QA HEAD COMMENT', concernHodComment = '".$comment."',
        concernHodBy = '".$_GET["emp_id"]."', concernHodOn = '".$entry_date."' WHERE id = '".$id."'";
    if ($conn->query($sql)) {
        echo "{\"status\": true}";
    } else {
        echo "{\"status\": false}";
    }
}
    else if($_GET['type'] == 'getCCForDeptMeha'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'FOR_DEPT_REVIEW' AND plant_id= '".$_GET["plant_id"]."'";
            
            if ($_GET["deptName"] == 'Engineering') {$sql .="AND engg = 'Pending'"; }
            else if($_GET["deptName"] == 'Admin') {$sql .="AND admin = 'Pending'";   }
            else if($_GET["deptName"] == 'Production') {$sql .="AND production = 'Pending'";  }
            else if($_GET["deptName"] == 'EHS') {$sql .="AND ehs = 'Pending'";  }
            else if($_GET["deptName"] == 'Quality Control') {$sql .="AND qc = 'Pending'";  }
            else if($_GET["deptName"] == 'Store') {$sql .="AND store = 'Pending'";  }
            else if($_GET["deptName"] == 'Microbiology') {$sql .="AND micro = 'Pending'";  }
            else if($_GET["deptName"] == 'IT') {$sql .="AND it = 'Pending'"; }
            else if($_GET["deptName"] == 'Human Resource') {$sql .="AND hr = 'Pending'"; }
            else if($_GET["deptName"] == 'Regulatory') {$sql .="AND regulatory = 'Pending'"; }
            else if($_GET["deptName"] == 'Quality Assurance') {$sql .="AND qa = 'Pending'";   }
            else if($_GET["deptName"] == 'R AND D') {$sql .="AND rnd = 'Pending'"; }  
            else {$sql .="AND rnd = 'sdfsdf'"; }  
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                }
            }
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
    else if($_GET['type'] == 'saveDeptReviewMeha'){ 
        
        $input = $_POST;
        $target_dir = "../../upload/changeControl/";
        
                    
       $ic = $input['ccNo'];  
       $deptName = $input['deptName'];  
                  
                    
            $consentRevDoc = "NA";
                    if(isset($_FILES["consentRevDoc"]["name"])){
                        $target_file = $target_dir.$ic.$deptName."CRDoc".basename($_FILES["consentRevDoc"]["name"]);
                        $consentRevDoc = $ic.$deptName."CRDoc".basename($_FILES["consentRevDoc"]["name"]);
                        move_uploaded_file($_FILES["consentRevDoc"]["tmp_name"], $target_file);
                    }
                    
        $sql = "INSERT INTO `CcConsentReview`(`plant_id`, `ccNo`, `ImpactDept`, `Responsibility`,
        `reviewBy`, `reviewOn`,`department`)  
        Values('".$_GET['plant_id']."','".$input['ccNo']."','".$input['ImpactDept']."',
        '".$input['Responsibility']."','".$_GET['emp_id']."','$entry_date','".$input['deptName']."') ";
        
    
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
             $sql1 ="";
            if ($input["deptName"] == 'Engineering') {
                
                $sql1 ="UPDATE `changecontrol`  SET engg = 'Done' where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Admin') {
                
                $sql1 ="UPDATE `changecontrol`  SET admin = 'Done' where id = '".$input['id']."' ";   }

            else if($input["deptName"] == 'Production') {
                
                $sql1 ="UPDATE `changecontrol`  SET production = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'EHS') {
                
                $sql1 ="UPDATE `changecontrol`  SET ehs = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Quality Control') {
                
                $sql1 .="UPDATE `changecontrol`  SET qc = 'Done'   where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Store') {
                
                $sql1 ="UPDATE `changecontrol`  SET store = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Microbiology') {
                
                $sql1 ="UPDATE `changecontrol`  SET micro = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'IT') {
                
                $sql1 ="UPDATE `changecontrol`  SET it = 'Done'   where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Human Resource') {
                
                $sql1 ="UPDATE `changecontrol`  SET hr = 'Done'   where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Regulatory') {
                
                $sql1 ="UPDATE `changecontrol`  SET regulatory = 'Done' where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Quality Assurance') {
                
                $sql1 ="UPDATE `changecontrol`  SET qa = 'Done'   where id = '".$input['id']."' ";   }

            else if($input["deptName"] == 'R AND D') {
                
                $sql1 ="UPDATE `changecontrol`  SET rnd = 'Done' where id = '".$input['id']."' ";      } 

            
            $conn->query($sql1);
            
            
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }



    else if($_GET['type'] == 'getCCForDeptConsentAndReview'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'FOR_DEPT_REVIEW' AND plant_id= '".$_GET["plant_id"]."'";
            // status= 'FOR_DEPT_CONSENT_AND_REVIEW'
            if ($_GET["deptName"] == 'Engineering') {$sql .="AND engg = 'Pending'"; }
            else if($_GET["deptName"] == 'Admin') {$sql .="AND admin = 'Pending'";   }
            else if($_GET["deptName"] == 'Production') {$sql .="AND production = 'Pending'";  }
            else if($_GET["deptName"] == 'EHS') {$sql .="AND ehs = 'Pending'";  }
            else if($_GET["deptName"] == 'Quality Control') {$sql .="AND qc = 'Pending'";  }
            else if($_GET["deptName"] == 'Store') {$sql .="AND store = 'Pending'";  }
            else if($_GET["deptName"] == 'Microbiology') {$sql .="AND micro = 'Pending'";  }
            else if($_GET["deptName"] == 'IT') {$sql .="AND it = 'Pending'"; }
            else if($_GET["deptName"] == 'Human Resource') {$sql .="AND hr = 'Pending'"; }
            else if($_GET["deptName"] == 'Regulatory') {$sql .="AND regulatory = 'Pending'"; }
            else if($_GET["deptName"] == 'Quality Assurance') {$sql .="AND qa = 'Pending'";   }
            else if($_GET["deptName"] == 'R AND D') {$sql .="AND rnd = 'Pending'"; }  
            else {$sql .="AND rnd = 'sdfsdf'"; }  
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                }
            }
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
        else if($_GET['type'] == 'getCcDeptComByQa'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'FOR_DEPT_REVIEW'  AND plant_id= '".$_GET["plant_id"]."' AND 
            engg != 'Pending' AND admin != 'Pending' AND production != 'Pending' AND ehs != 'Pending' AND qc != 'Pending' AND 
            store != 'Pending' AND micro != 'Pending' AND it != 'Pending' AND hr != 'Pending' AND regulatory != 'Pending' AND 
            qa != 'Pending' AND rnd != 'Pending' ";
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                }
            }
            
            
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }

    else if($_GET['type'] == 'getCcForAssementByQa'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'FOR_DEPT_CONSENT_AND_REVIEW'  AND plant_id= '".$_GET["plant_id"]."' AND 
            engg != 'Pending' AND admin != 'Pending' AND production != 'Pending' AND ehs != 'Pending' AND qc != 'Pending' AND 
            store != 'Pending' AND micro != 'Pending' AND it != 'Pending' AND hr != 'Pending' AND regulatory != 'Pending' AND 
            qa != 'Pending' AND rnd != 'Pending' ";
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                               $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
                    
                     $sql1 = "SELECT 
            CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
            CONCAT(e2.firstname, ' ', e2.lastname) AS authBy
        FROM 
            employee e1
        LEFT JOIN 
            employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
        WHERE 
            e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
                
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                }
            }
            
            
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
    else if($_GET['type'] == 'getCcForImpactReguAndMArkAuth'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'For_Impact_On_Regu_Affars'  AND plant_id= '".$_GET["plant_id"]."' ";
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                }
            }
            
            
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
        else if($_GET['type'] == 'getCcQAByMeha'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'For_QA_Head_Meha'  AND plant_id= '".$_GET["plant_id"]."' ";
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
                         $row["equipmentChangeData"] = json_decode($row["equipmentChangeData"]);
                         $row["departmentChangeData"] = json_decode($row["departmentChangeData"]);
                          $row["documentEquipmentData"] = json_decode($row["documentEquipmentData"]);
                         $row["documentChangeData"] = json_decode($row["documentChangeData"]);
                          $row["equipmentChangeDataTwo"] = json_decode($row["equipmentChangeDataTwo"]);
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy,
    CONCAT(e3.firstname, ' ', e3.lastname) AS impactReguByName
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e3 ON e3.emp_id = '".$row["impactReguBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                    $row["impactReguByName"] =  $row1["impactReguByName"];
                }
            }
            
            
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }

    else if ($_GET['type'] == 'getQAEmployees') {
        $output = array();
        $sql = "SELECT emp_id, CONCAT(firstname, ' ', lastname) AS fullname FROM employee WHERE plant_id = '".$_GET["plant_id"]."' AND department = 'Quality Assurance' AND status = 'active' ORDER BY firstname, lastname";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if ($_GET['type'] == 'saveQAHeadReview') {
        $entry_date = date("Y-m-d H:i:s");
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $approval_qa = isset($input['APPROVAL_QA']) ? $conn->real_escape_string($input['APPROVAL_QA']) : '';
        $sql = "UPDATE changecontrol SET status = 'FOR_DEPT_REVIEW', APPROVAL_QA = '".$approval_qa."' WHERE id = '".$id."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

    else if ($_GET['type'] == 'saveActionPlanApproval') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $approval_qa = isset($input['APPROVAL_QA']) ? $conn->real_escape_string($input['APPROVAL_QA']) : '';
        $approval_status = isset($input['action_plan_approval_status']) ? $conn->real_escape_string($input['action_plan_approval_status']) : '';
        $action_plan_assigned_to = isset($input['action_plan_assigned_to']) ? $conn->real_escape_string($input['action_plan_assigned_to']) : '';
        $target_completion_date = isset($input['target_completion_date']) ? $conn->real_escape_string($input['target_completion_date']) : '';
        $sql = "UPDATE changecontrol SET 
            status = 'FOR_DEPT_REVIEW', 
            APPROVAL_QA = '".$approval_qa."', 
            qa_head_remark = '".$approval_status."', 
            action_plan_assigned_to = '".$action_plan_assigned_to."', 
            target_completion_date = '".$target_completion_date."' 
            WHERE id = '".$id."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

    else if($_GET['type'] == 'getCcForActionAndReviewByQAMeha'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'For_QA_Review_After_Assessment'  AND plant_id= '".$_GET["plant_id"]."' ";
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
                         $row["equipmentChangeData"] = json_decode($row["equipmentChangeData"]);
                         $row["departmentChangeData"] = json_decode($row["departmentChangeData"]);
                          $row["documentEquipmentData"] = json_decode($row["documentEquipmentData"]);
                         $row["documentChangeData"] = json_decode($row["documentChangeData"]);
                          $row["equipmentChangeDataTwo"] = json_decode($row["equipmentChangeDataTwo"]);
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy,
    CONCAT(e3.firstname, ' ', e3.lastname) AS impactReguByName
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e3 ON e3.emp_id = '".$row["impactReguBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                    $row["impactReguByName"] =  $row1["impactReguByName"];
                }
            }
            
            
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
        else if($_GET['type'] == 'getCcForApprovalOfChangeByQAHead'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'For_Approval_Of_Change_By_QA_Head'  AND plant_id= '".$_GET["plant_id"]."' ";
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy,
    CONCAT(e3.firstname, ' ', e3.lastname) AS impactReguByName,
    CONCAT(e4.firstname, ' ', e4.lastname) AS qaReviewedByName
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e3 ON e3.emp_id = '".$row["impactReguBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e4 ON e4.emp_id = '".$row["qaReviewedBy"]."' AND e4.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                    $row["impactReguByName"] =  $row1["impactReguByName"];
                    $row["qaReviewedByName"] =  $row1["qaReviewedByName"];
                }
            }
            
             
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
            
            
            $row["actionData"] =  json_decode($row["actionData"]);
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }

    else if($_GET['type'] == 'getCcForApprovalOfChangeByQAHead'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'For_Approval_Of_Change_By_QA_Head'  AND plant_id= '".$_GET["plant_id"]."' ";
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy,
    CONCAT(e3.firstname, ' ', e3.lastname) AS impactReguByName,
    CONCAT(e4.firstname, ' ', e4.lastname) AS qaReviewedByName
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e3 ON e3.emp_id = '".$row["impactReguBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e4 ON e4.emp_id = '".$row["qaReviewedBy"]."' AND e4.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                    $row["impactReguByName"] =  $row1["impactReguByName"];
                    $row["qaReviewedByName"] =  $row1["qaReviewedByName"];
                }
            }
            
             
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
            
            
            $row["actionData"] =  json_decode($row["actionData"]);
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
    else if($_GET['type'] == 'getCcFormonitoringFollowup'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'For_Monitoring_And_FollowUp'   AND plant_id= '".$_GET["plant_id"]."' 
              AND department_name= '".$_GET["deptName"]."' ";
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy,
    CONCAT(e3.firstname, ' ', e3.lastname) AS impactReguByName,
    CONCAT(e4.firstname, ' ', e4.lastname) AS qaReviewedByName
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e3 ON e3.emp_id = '".$row["impactReguBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e4 ON e4.emp_id = '".$row["qaReviewedBy"]."' AND e4.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                    $row["impactReguByName"] =  $row1["impactReguByName"];
                    $row["qaReviewedByName"] =  $row1["qaReviewedByName"];
                }
            }
            
             
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
            
            
            $row["actionData"] =  json_decode($row["actionData"]);
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
    else if($_GET['type'] == 'getCcForQaAssessmentChecklist'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'For_QA_Assessment_Checklist'   AND plant_id= '".$_GET["plant_id"]."'  ";
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy,
    CONCAT(e3.firstname, ' ', e3.lastname) AS impactReguByName,
    CONCAT(e4.firstname, ' ', e4.lastname) AS qaReviewedByName,
    CONCAT(e5.firstname, ' ', e5.lastname) AS monitoringByName
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e3 ON e3.emp_id = '".$row["impactReguBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e4 ON e4.emp_id = '".$row["qaReviewedBy"]."' AND e4.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e5 ON e5.emp_id = '".$row["monitoringBy"]."' AND e5.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                    $row["impactReguByName"] =  $row1["impactReguByName"];
                    $row["qaReviewedByName"] =  $row1["qaReviewedByName"];
                    $row["monitoringByName"] =  $row1["monitoringByName"];
                }
            }
            
             
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
            
            
            $row["actionData"] =  json_decode($row["actionData"]);
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
    else if($_GET['type'] == 'getCcForClosinChecklist'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'For_Closin_Checklist'   AND plant_id= '".$_GET["plant_id"]."'  ";
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
                       $row["checlistData"] = json_decode($row["checlistData"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy,
    CONCAT(e3.firstname, ' ', e3.lastname) AS impactReguByName,
    CONCAT(e4.firstname, ' ', e4.lastname) AS qaReviewedByName,
    CONCAT(e5.firstname, ' ', e5.lastname) AS monitoringByName
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e3 ON e3.emp_id = '".$row["impactReguBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e4 ON e4.emp_id = '".$row["qaReviewedBy"]."' AND e4.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e5 ON e5.emp_id = '".$row["monitoringBy"]."' AND e5.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                    $row["impactReguByName"] =  $row1["impactReguByName"];
                    $row["qaReviewedByName"] =  $row1["qaReviewedByName"];
                    $row["monitoringByName"] =  $row1["monitoringByName"];
                }
            }
            
             
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
            
            
            $row["actionData"] =  json_decode($row["actionData"]);
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
    
    
            else if($_GET['type'] == 'getCcClosedByQaMeha'){ 
            $output = Array();
          $sql = "
    SELECT 
        c.*, c2.* 
    FROM changecontrol c
    LEFT JOIN changecontrol2 c2 ON c.id = c2.id 
    WHERE c.status = 'FinalCommentQA' 
    AND c.plant_id = '".$_GET["plant_id"]."'";

            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
                         $row["equipmentChangeData"] = json_decode($row["equipmentChangeData"]);
                         $row["departmentChangeData"] = json_decode($row["departmentChangeData"]);
                          $row["documentEquipmentData"] = json_decode($row["documentEquipmentData"]);
                         $row["documentChangeData"] = json_decode($row["documentChangeData"]);
                          $row["equipmentChangeDataTwo"] = json_decode($row["equipmentChangeDataTwo"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy,
    CONCAT(e3.firstname, ' ', e3.lastname) AS impactReguByName,
    CONCAT(e4.firstname, ' ', e4.lastname) AS qaReviewedByName,
    CONCAT(e5.firstname, ' ', e5.lastname) AS monitoringByName,
    CONCAT(e6.firstname, ' ', e6.lastname) AS changeReviewedByName,
    CONCAT(e6.firstname, ' ', e6.lastname) AS changeReviewedByName
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e3 ON e3.emp_id = '".$row["impactReguBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e4 ON e4.emp_id = '".$row["qaReviewedBy"]."' AND e4.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e5 ON e5.emp_id = '".$row["monitoringBy"]."' AND e5.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e6 ON e6.emp_id = '".$row["changeReviewedBy"]."' AND e6.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                    $row["impactReguByName"] =  $row1["impactReguByName"];
                    $row["qaReviewedByName"] =  $row1["qaReviewedByName"];
                    $row["monitoringByName"] =  $row1["monitoringByName"];
                    $row["changeReviewedByName"] =  $row1["changeReviewedByName"];
                }
            }
            
             
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
            
            
            $row["actionData"] =  json_decode($row["actionData"]);
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }

    
    
    
    
               else if($_GET['type'] == 'getCcLogMeha'){ 
            $output = Array();
       $sql = "
    SELECT 
        c.*, 
        c.status AS statusMeha, 
        c2.* 
    FROM changecontrol c
    LEFT JOIN changecontrol2 c2 ON c.id = c2.id 
    WHERE c.plant_id = '".$_GET["plant_id"]."'Order by c.id desc";

            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
                         $row["equipmentChangeData"] = json_decode($row["equipmentChangeData"]);
                         $row["departmentChangeData"] = json_decode($row["departmentChangeData"]);
                          $row["documentEquipmentData"] = json_decode($row["documentEquipmentData"]);
                         $row["documentChangeData"] = json_decode($row["documentChangeData"]);
                          $row["equipmentChangeDataTwo"] = json_decode($row["equipmentChangeDataTwo"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy,
    CONCAT(e3.firstname, ' ', e3.lastname) AS impactReguByName,
    CONCAT(e4.firstname, ' ', e4.lastname) AS qaReviewedByName,
    CONCAT(e5.firstname, ' ', e5.lastname) AS monitoringByName,
    CONCAT(e6.firstname, ' ', e6.lastname) AS changeReviewedByName,
    CONCAT(e6.firstname, ' ', e6.lastname) AS changeReviewedByName
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e3 ON e3.emp_id = '".$row["impactReguBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e4 ON e4.emp_id = '".$row["qaReviewedBy"]."' AND e4.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e5 ON e5.emp_id = '".$row["monitoringBy"]."' AND e5.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e6 ON e6.emp_id = '".$row["changeReviewedBy"]."' AND e6.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                    $row["impactReguByName"] =  $row1["impactReguByName"];
                    $row["qaReviewedByName"] =  $row1["qaReviewedByName"];
                    $row["monitoringByName"] =  $row1["monitoringByName"];
                    $row["changeReviewedByName"] =  $row1["changeReviewedByName"];
                }
            }
            
             
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
            
            
            $row["actionData"] =  json_decode($row["actionData"]);
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
 
    
    
    
        else if($_GET['type'] == 'getCcForClosedByQaMeha'){ 
            $output = Array();
          $sql = "
    SELECT 
        c.*, c2.* 
    FROM changecontrol c
    LEFT JOIN changecontrol2 c2 ON c.id = c2.id 
    WHERE c.status = 'To_QA_FinalRev' 
    AND c.plant_id = '".$_GET["plant_id"]."'";

            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
                         $row["equipmentChangeData"] = json_decode($row["equipmentChangeData"]);
                         $row["departmentChangeData"] = json_decode($row["departmentChangeData"]);
                          $row["documentEquipmentData"] = json_decode($row["documentEquipmentData"]);
                         $row["documentChangeData"] = json_decode($row["documentChangeData"]);
                          $row["equipmentChangeDataTwo"] = json_decode($row["equipmentChangeDataTwo"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy,
    CONCAT(e3.firstname, ' ', e3.lastname) AS impactReguByName,
    CONCAT(e4.firstname, ' ', e4.lastname) AS qaReviewedByName,
    CONCAT(e5.firstname, ' ', e5.lastname) AS monitoringByName,
    CONCAT(e6.firstname, ' ', e6.lastname) AS changeReviewedByName,
    CONCAT(e6.firstname, ' ', e6.lastname) AS changeReviewedByName
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e3 ON e3.emp_id = '".$row["impactReguBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e4 ON e4.emp_id = '".$row["qaReviewedBy"]."' AND e4.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e5 ON e5.emp_id = '".$row["monitoringBy"]."' AND e5.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e6 ON e6.emp_id = '".$row["changeReviewedBy"]."' AND e6.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                    $row["impactReguByName"] =  $row1["impactReguByName"];
                    $row["qaReviewedByName"] =  $row1["qaReviewedByName"];
                    $row["monitoringByName"] =  $row1["monitoringByName"];
                    $row["changeReviewedByName"] =  $row1["changeReviewedByName"];
                }
            }
            
             
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
            
            
            $row["actionData"] =  json_decode($row["actionData"]);
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }

    
    else if($_GET['type'] == 'getCcForClosedByQa'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'For_Change_Closed_By_QA_Head'   AND plant_id= '".$_GET["plant_id"]."'  ";
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
                       $row["checlistData"] = json_decode($row["checlistData"]);
                       $row["closinChecklist"] = json_decode($row["closinChecklist"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy,
    CONCAT(e3.firstname, ' ', e3.lastname) AS impactReguByName,
    CONCAT(e4.firstname, ' ', e4.lastname) AS qaReviewedByName,
    CONCAT(e5.firstname, ' ', e5.lastname) AS monitoringByName,
    CONCAT(e6.firstname, ' ', e6.lastname) AS changeReviewedByName,
    CONCAT(e6.firstname, ' ', e6.lastname) AS changeReviewedByName
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e3 ON e3.emp_id = '".$row["impactReguBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e4 ON e4.emp_id = '".$row["qaReviewedBy"]."' AND e4.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e5 ON e5.emp_id = '".$row["monitoringBy"]."' AND e5.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e6 ON e6.emp_id = '".$row["changeReviewedBy"]."' AND e6.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                    $row["impactReguByName"] =  $row1["impactReguByName"];
                    $row["qaReviewedByName"] =  $row1["qaReviewedByName"];
                    $row["monitoringByName"] =  $row1["monitoringByName"];
                    $row["changeReviewedByName"] =  $row1["changeReviewedByName"];
                }
            }
            
             
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
            
            
            $row["actionData"] =  json_decode($row["actionData"]);
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
    else if($_GET['type'] == 'getCcLog'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  department_name = '".$_GET["deptName"]."'    AND plant_id= '".$_GET["plant_id"]."'  ";
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
                       $row["checlistData"] = json_decode($row["checlistData"]);
                       $row["closinChecklist"] = json_decode($row["closinChecklist"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy,
    CONCAT(e3.firstname, ' ', e3.lastname) AS impactReguByName,
    CONCAT(e4.firstname, ' ', e4.lastname) AS qaReviewedByName,
    CONCAT(e5.firstname, ' ', e5.lastname) AS monitoringByName,
    CONCAT(e6.firstname, ' ', e6.lastname) AS changeReviewedByName,
    CONCAT(e6.firstname, ' ', e6.lastname) AS changeReviewedByName,
    CONCAT(e6.firstname, ' ', e6.lastname) AS closedByName
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e3 ON e3.emp_id = '".$row["impactReguBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e4 ON e4.emp_id = '".$row["qaReviewedBy"]."' AND e4.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e5 ON e5.emp_id = '".$row["monitoringBy"]."' AND e5.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e6 ON e6.emp_id = '".$row["changeReviewedBy"]."' AND e6.plant_id = '".$_GET["plant_id"]."'
LEFT JOIN 
    employee e7 ON e7.emp_id = '".$row["closedBy"]."' AND e7.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                    $row["impactReguByName"] =  $row1["impactReguByName"];
                    $row["qaReviewedByName"] =  $row1["qaReviewedByName"];
                    $row["monitoringByName"] =  $row1["monitoringByName"];
                    $row["changeReviewedByName"] =  $row1["changeReviewedByName"];
                    $row["closedByName"] =  $row1["closedByName"];
                }
            }
            
             
            $output2 = Array();
            $sql2 = "select * from CcConsentReview where ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2[] =  $row2;
                }
            }
            
            
            $row["actionData"] =  json_decode($row["actionData"]);
             
                    $row['deptReview'] = $output2;
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
else if($_GET['type'] == 'saveQaReview'){ 
    $engg = 'NA';
    $admin = 'NA';
    $production = 'NA';
    $ehs = 'NA';
    $qc = 'NA';
    $store = 'NA';
    $micro = 'NA';
    $it = 'NA';
    $hr = 'NA';
    $regulatory = 'NA';
    $qa = 'NA';
    $rnd = 'NA';
    if($input['engg'] == true){ $engg = 'Pending'; }
    if($input['admin'] == true){ $admin = 'Pending'; }
    if($input['production'] == true){ $production = 'Pending'; }
    if($input['ehs'] == true){ $ehs = 'Pending'; }
    if($input['qc'] == true){ $qc = 'Pending'; }
    if($input['store'] == true){ $store = 'Pending'; }
    if($input['micro'] == true){ $micro = 'Pending'; }
    if($input['it'] == true){ $it = 'Pending'; }
    if($input['hr'] == true){ $hr = 'Pending'; }
    if($input['regulatory'] == true){ $regulatory = 'Pending'; }
    if($input['qa'] == true){ $qa = 'Pending'; }
    if($input['rnd'] == true){ $rnd = 'Pending'; }
            // $sql = "UPDATE `changecontrol`  SET status = 'FOR_DEPT_REVIEW',
            // classification='".$input['classification']."',APPROVAL_QA='".$input['APPROVAL_QA']."',
            // engg = '".$engg."', admin = '".$admin."', production = '".$production."', ehs = '".$ehs."', qc = '".$qc."', store = '".$store."', 
            // micro = '".$micro."', it = '".$it."', hr = '".$hr."', regulatory = '".$regulatory."', qa = '".$qa."', rnd = '".$rnd."', 
            // QaRevBy = '".$_GET['emp_id']."', QaRevOn = '$entry_date' where id = '".$_GET['id']."' "; 
            
            $sql = "UPDATE `changecontrol` SET 
    status = 'For_QA_Head_Meha',
    classification = '".$input['classification']."',
    APPROVAL_QA = '".$input['APPROVAL_QA']."',
    engg = '".$engg."', admin = '".$admin."', production = '".$production."', ehs = '".$ehs."', 
    qc = '".$qc."', store = '".$store."', micro = '".$micro."', it = '".$it."', 
    hr = '".$hr."', regulatory = '".$regulatory."', qa = '".$qa."', rnd = '".$rnd."', 
    DeptamvCheck = '".$input['DeptamvCheck']."',
    DeptbmrMfrCheck = '".$input['DeptbmrMfrCheck']."',
    DeptdmfCheck = '".$input['DeptdmfCheck']."',
    DeptenvironmentCheck = '".$input['DeptenvironmentCheck']."',
    DeptequipmentCheck = '".$input['DeptequipmentCheck']."',
    DeptfdaLicenseCheck = '".$input['DeptfdaLicenseCheck']."',
    Deptiso9001Check = '".$input['Deptiso9001Check']."',
    DeptkosherCheck = '".$input['DeptkosherCheck']."',
    DeptmachineCheck = '".$input['DeptmachineCheck']."',
    DeptmaterialCheck = '".$input['DeptmaterialCheck']."',
    DeptmeasurementsCheck = '".$input['DeptmeasurementsCheck']."',
    DeptmoaAwrCheck = '".$input['DeptmoaAwrCheck']."',
    DeptothersCheck = '".$input['DeptothersCheck']."',
    DeptproductQualityCheck = '".$input['DeptproductQualityCheck']."',
    DeptpvCheck = '".$input['DeptpvCheck']."',
    DeptsafetyCheck = '".$input['DeptsafetyCheck']."',
    DeptsdsCheck = '".$input['DeptsdsCheck']."',
    DeptskillsCheck = '".$input['DeptskillsCheck']."',
    DeptsopCheck = '".$input['DeptsopCheck']."',
    DeptspecificationCheck = '".$input['DeptspecificationCheck']."',
    DeptstabilityStudiesCheck = '".$input['DeptstabilityStudiesCheck']."',
    DeptstatutoryCheck = '".$input['DeptstatutoryCheck']."',
    DeptstpCheck = '".$input['DeptstpCheck']."',
    DepttrainingCheck = '".$input['DepttrainingCheck']."',
    DeptvalidationCheck = '".$input['DeptvalidationCheck']."',
    DeptvendorCheck = '".$input['DeptvendorCheck']."',
    DeptwhoLicenseCheck = '".$input['DeptwhoLicenseCheck']."',
    amvCheck = '".$input['amvCheck']."',
    bmrMfrCheck = '".$input['bmrMfrCheck']."',
    dmfCheck = '".$input['dmfCheck']."',
    environmentCheck = '".$input['environmentCheck']."',
    equipmentCheck = '".$input['equipmentCheck']."',
    fdaLicenseCheck = '".$input['fdaLicenseCheck']."',
    iso9001Check = '".$input['iso9001Check']."',
    kosherCheck = '".$input['kosherCheck']."',
    machineCheck = '".$input['machineCheck']."',
    materialCheck = '".$input['materialCheck']."',
    measurementsCheck = '".$input['measurementsCheck']."',
    moaAwrCheck = '".$input['moaAwrCheck']."',
    othersCheck = '".$input['othersCheck']."',
    productQualityCheck = '".$input['productQualityCheck']."',
    pvCheck = '".$input['pvCheck']."',
    safetyCheck = '".$input['safetyCheck']."',
    sdsCheck = '".$input['sdsCheck']."',
    skillsCheck = '".$input['skillsCheck']."',
    sopCheck = '".$input['sopCheck']."',
    specificationCheck = '".$input['specificationCheck']."',
    stabilityStudiesCheck = '".$input['stabilityStudiesCheck']."',
    statutoryCheck = '".$input['statutoryCheck']."',
    stpCheck = '".$input['stpCheck']."',
    trainingCheck = '".$input['trainingCheck']."',
    validationCheck = '".$input['validationCheck']."',
    vendorCheck = '".$input['vendorCheck']."',
    whoLicenseCheck = '".$input['whoLicenseCheck']."',
    amvRemarks = '".$input['amvRemarks']."',
    bmrMfrRemarks = '".$input['bmrMfrRemarks']."',
    dmfRemarks = '".$input['dmfRemarks']."',
    environmentRemarks = '".$input['environmentRemarks']."',
    equipmentRemarks = '".$input['equipmentRemarks']."',
    fdaLicenseRemarks = '".$input['fdaLicenseRemarks']."',
    iso9001Remarks = '".$input['iso9001Remarks']."',
    kosherRemarks = '".$input['kosherRemarks']."',
    machineRemarks = '".$input['machineRemarks']."',
    materialRemarks = '".$input['materialRemarks']."',
    measurementsRemarks = '".$input['measurementsRemarks']."',
    moaAwrRemarks = '".$input['moaAwrRemarks']."',
    othersRemarks = '".$input['othersRemarks']."',
    productQualityRemarks = '".$input['productQualityRemarks']."',
    pvRemarks = '".$input['pvRemarks']."',
    safetyRemarks = '".$input['safetyRemarks']."',
    sdsRemarks = '".$input['sdsRemarks']."',
    skillsRemarks = '".$input['skillsRemarks']."',
    sopRemarks = '".$input['sopRemarks']."',
    specificationRemarks = '".$input['specificationRemarks']."',
    stabilityStudiesRemarks = '".$input['stabilityStudiesRemarks']."',
    statutoryRemarks = '".$input['statutoryRemarks']."',
    stpRemarks = '".$input['stpRemarks']."',
    trainingRemarks = '".$input['trainingRemarks']."',
    validationRemarks = '".$input['validationRemarks']."',
    vendorRemarks = '".$input['vendorRemarks']."',
    whoLicenseRemarks = '".$input['whoLicenseRemarks']."',
    QaRevBy = '".$_GET['emp_id']."', 
    QaRevOn = '$entry_date'
WHERE id = '".$_GET['id']."'";
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

else if($_GET['type'] == 'saveQaConsentAndReview'){ 
        
        
    $engg = 'NA';
    $admin = 'NA';
    $production = 'NA';
    $ehs = 'NA';
    $qc = 'NA';
    $store = 'NA';
    $micro = 'NA';
    $it = 'NA';
    $hr = 'NA';
    $regulatory = 'NA';
    $qa = 'NA';
    $rnd = 'NA';
    
    
    if($input['engg'] == true){ $engg = 'Pending'; }
    if($input['admin'] == true){ $admin = 'Pending'; }
    if($input['production'] == true){ $production = 'Pending'; }
    if($input['ehs'] == true){ $ehs = 'Pending'; }
    if($input['qc'] == true){ $qc = 'Pending'; }
    if($input['store'] == true){ $store = 'Pending'; }
    if($input['micro'] == true){ $micro = 'Pending'; }
    if($input['it'] == true){ $it = 'Pending'; }
    if($input['hr'] == true){ $hr = 'Pending'; }
    if($input['regulatory'] == true){ $regulatory = 'Pending'; }
    if($input['qa'] == true){ $qa = 'Pending'; }
    if($input['rnd'] == true){ $rnd = 'Pending'; }
     
       
             
            $sql = "UPDATE `changecontrol`  SET status = 'FOR_DEPT_REVIEW',
            engg = '".$engg."', admin = '".$admin."', production = '".$production."', ehs = '".$ehs."', qc = '".$qc."', store = '".$store."', 
            micro = '".$micro."', it = '".$it."', hr = '".$hr."', regulatory = '".$regulatory."', qa = '".$qa."', rnd = '".$rnd."', 
            revAndConBy = '".$_GET['emp_id']."', revAndConOn = '$entry_date' where id = '".$_GET['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if($_GET['type'] == 'saveDeptCommentMeha'){ 
        
        $input = $_POST;
        $sql = "INSERT INTO `CcConsentReview`(`plant_id`, `ccNo`, `reviewComment`, 
        `reviewBy`, `reviewOn`,`department`)  Values('".$_GET['plant_id']."','".$input['ccNo']."','".$input['reviewComment']."',
      '".$_GET['emp_id']."','$entry_date','".$input['deptName']."') ";
        
    
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
             $sql1 ="";
            if ($input["deptName"] == 'Engineering') {
                
                $sql1 ="UPDATE `changecontrol`  SET engg = 'Done' where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Admin') {
                
                $sql1 ="UPDATE `changecontrol`  SET admin = 'Done' where id = '".$input['id']."' ";   }

            else if($input["deptName"] == 'Production') {
                
                $sql1 ="UPDATE `changecontrol`  SET production = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'EHS') {
                
                $sql1 ="UPDATE `changecontrol`  SET ehs = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Quality Control') {
                
                $sql1 .="UPDATE `changecontrol`  SET qc = 'Done'   where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Store') {
                
                $sql1 ="UPDATE `changecontrol`  SET store = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Microbiology') {
                
                $sql1 ="UPDATE `changecontrol`  SET micro = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'IT') {
                
                $sql1 ="UPDATE `changecontrol`  SET it = 'Done'   where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Human Resource') {
                
                $sql1 ="UPDATE `changecontrol`  SET hr = 'Done'   where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Regulatory') {
                
                $sql1 ="UPDATE `changecontrol`  SET regulatory = 'Done' where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Quality Assurance') {
                
                $sql1 ="UPDATE `changecontrol`  SET qa = 'Done'   where id = '".$input['id']."' ";   }

            else if($input["deptName"] == 'R AND D') {
                
                $sql1 ="UPDATE `changecontrol`  SET rnd = 'Done' where id = '".$input['id']."' ";      } 

            
            $conn->query($sql1);
            
            
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }

    else if($_GET['type'] == 'saveDeptConcentAndReview'){ 
        
        $input = $_POST;
        $target_dir = "../../upload/changeControl/";
        
                    
       $ic = $input['ccNo'];  
       $deptName = $input['deptName'];  
                  
                    
            $consentRevDoc = "NA";
                    if(isset($_FILES["consentRevDoc"]["name"])){
                        $target_file = $target_dir.$ic.$deptName."CRDoc".basename($_FILES["consentRevDoc"]["name"]);
                        $consentRevDoc = $ic.$deptName."CRDoc".basename($_FILES["consentRevDoc"]["name"]);
                        move_uploaded_file($_FILES["consentRevDoc"]["tmp_name"], $target_file);
                    }
                    
        $sql = "INSERT INTO `CcConsentReview`(`plant_id`, `ccNo`, `reviewComment`, `changeAcceptedNotAcc`, `consentRevDoc`,
        `reviewBy`, `reviewOn`,`department`)  Values('".$_GET['plant_id']."','".$input['ccNo']."','".$input['reviewComment']."',
        '".$input['changeAcceptedNotAcc']."','$consentRevDoc','".$_GET['emp_id']."','$entry_date','".$input['deptName']."') ";
        
    
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
             $sql1 ="";
            if ($input["deptName"] == 'Engineering') {
                
                $sql1 ="UPDATE `changecontrol`  SET engg = 'Done' where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Admin') {
                
                $sql1 ="UPDATE `changecontrol`  SET admin = 'Done' where id = '".$input['id']."' ";   }

            else if($input["deptName"] == 'Production') {
                
                $sql1 ="UPDATE `changecontrol`  SET production = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'EHS') {
                
                $sql1 ="UPDATE `changecontrol`  SET ehs = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Quality Control') {
                
                $sql1 .="UPDATE `changecontrol`  SET qc = 'Done'   where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Store') {
                
                $sql1 ="UPDATE `changecontrol`  SET store = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Microbiology') {
                
                $sql1 ="UPDATE `changecontrol`  SET micro = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'IT') {
                
                $sql1 ="UPDATE `changecontrol`  SET it = 'Done'   where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Human Resource') {
                
                $sql1 ="UPDATE `changecontrol`  SET hr = 'Done'   where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Regulatory') {
                
                $sql1 ="UPDATE `changecontrol`  SET regulatory = 'Done' where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Quality Assurance') {
                
                $sql1 ="UPDATE `changecontrol`  SET qa = 'Done'   where id = '".$input['id']."' ";   }

            else if($input["deptName"] == 'R AND D') {
                
                $sql1 ="UPDATE `changecontrol`  SET rnd = 'Done' where id = '".$input['id']."' ";      } 

            
            $conn->query($sql1);
            
            
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
     else if($_GET['type'] == 'saveMehaAssByQA'){
         $sql ="UPDATE `changecontrol`  SET 
         status = 'For_QA_Head_Meha' ,
         departmentChangeData =  '".json_encode(isset($input['departmentChangeData']) ? $input['departmentChangeData'] : [])."' ,
         equipmentChangeData =  '".json_encode(isset($input['equipmentChangeData']) ? $input['equipmentChangeData'] : [])."' ,
         assessmentBy = '".$_GET['emp_id']."',
         assessmentOn = '$entry_date' 
         where id = '".$input['id']."' "; 
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if($_GET['type'] == 'saveAssessmentByQA'){
         
        $status = 'For_QA_Review_After_Assessment';
         
        
        if($input['typeMajor'] == 'YES' || $input['typeCritical'] == 'YES'){
            $status = 'For_Impact_On_Regu_Affars';
        }
          
         $sql ="UPDATE `changecontrol`  SET status = '$status' , scopeOfChange = '".$input['scopeOfChange']."' , typeMinor = '".$input['typeMinor']."'
        , typeMajor = '".$input['typeMajor']."', typeCritical = '".$input['typeCritical']."' ,
         assessmentBy = '".$_GET['emp_id']."', assessmentOn = '$entry_date' where id = '".$input['id']."' "; 
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    
    
  
    
    else if ($_GET['type'] == 'ClosureSave') {

    $id      = $input['id'];
    $closure = $input['Closure'];
    $emp_id  = $_GET["emp_id"];
    $filePath = null;

    if (isset($_FILES['closure_doc']) && $_FILES['closure_doc']['error'] === 0) {

        $uploadDir = __DIR__ . '/upload/control/qa_head_closure/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($_FILES['closure_doc']['name'], PATHINFO_EXTENSION);
        $fileName = 'QA_HEAD_CLOSURE_' . time() . '.' . $ext;
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['closure_doc']['tmp_name'], $targetFile)) {
            $filePath = 'upload/control/qa_head_closure/' . $fileName;
        }
    }

    $sql = "UPDATE changecontrol SET 
            status = 'complete',
            Closure = '$closure',
            ClosureBy = '$emp_id',
            ClosureOn = '$entry_date'";

    if ($filePath !== null) {
        $sql .= ", qa_head_closure_doc = '$filePath'";
    }

    $sql .= " WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => $conn->error]);
    }
}


    
    else if($_GET['type'] == 'SaveImpactOnReguAffaiAndMarketing'){
          
         $sql ="UPDATE `changecontrol`  SET status = 'For_QA_Review_After_Assessment' , reviewCommmentByRegu = '".$input['reviewCommmentByRegu']."' ,
         impactReguConclusion = '".$input['impactReguConclusion']."' ,impactReguBy = '".$_GET['emp_id']."', impactReguOn = '$entry_date' 
         where id = '".$input['id']."' "; 
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    
     else if($_GET['type'] == 'saveMehaQaRevieweAndActions'){
          
         $sql ="UPDATE `changecontrol`  SET 
         status = 'For_QA_Head_Meha' ,
         documentEquipmentData = '".json_encode($input['documentEquipmentData'])."' ,
         equipmentChangeDataTwo = '".json_encode($input['equipmentChangeData'])."' ,
         documentChangeData = '".json_encode($input['documentChangeData'])."' ,
         qaReviewedBy = '".$_GET['emp_id']."',
         qaReviewedOn = '$entry_date'
         where id = '".$input['id']."' "; 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    
    else if($_GET['type'] == 'saveQaRevieweAndActions'){
          
         $sql ="UPDATE `changecontrol`  SET status = 'For_Approval_Of_Change_By_QA_Head' , actionData = '".json_encode($input['actionData'])."' ,
         action1 = '".$input['action1']."',action2 = '".$input['action2']."',qaRepresentativeCommentOnQaReviewed = '".$input['qaRepresentativeCommentOnQaReviewed']."' ,
         qaReviewedBy = '".$_GET['emp_id']."', qaReviewedOn = '$entry_date' where id = '".$input['id']."' "; 
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
      else if($_GET['type'] == 'saveccQaHead'){
        
         $sql = "INSERT INTO changecontrol2 (
    id, status, approvalOfCcByQaHeadBy, approvalOfCcByQaHeadOn,

    productQualityCompletion, productQualityQAReview2, productQualityRemarks2,
    sopCompletion, sopQAReview2, sopRemarks2,
    specificationCompletion, specificationQAReview2, specificationRemarks2,
    stpCompletion, stpQAReview2, stpRemarks2,
    sdsCompletion, sdsQAReview2, sdsRemarks2,
    moaAwrCompletion, moaAwrQAReview2, moaAwrRemarks2,
    bmrMfrCompletion, bmrMfrQAReview2, bmrMfrRemarks2,
    equipmentCompletion, equipmentQAReview2, equipmentRemarks2,
    measurementsCompletion, measurementsQAReview2, measurementsRemarks2,
    materialCompletion, materialQAReview2, materialRemarks2,
    validationCompletion, validationQAReview2, validationRemarks2,
    stabilityStudiesCompletion, stabilityStudiesQAReview2, stabilityStudiesRemarks2,
    pvCompletion, pvQAReview2, pvRemarks2,
    amvCompletion, amvQAReview2, amvRemarks2,
    dqOqIqPqCompletion, dqOqIqPqQAReview2, dqOqIqPqRemarks2,
    statutoryCompletion, statutoryQAReview2, statutoryRemarks2,
    fdaLicenseCompletion, fdaLicenseQAReview2, fdaLicenseRemarks2,
    whoLicenseCompletion, whoLicenseQAReview2, whoLicenseRemarks2,
    kosherCompletion, kosherQAReview2, kosherRemarks2,
    iso9001Completion, iso9001QAReview2, iso9001Remarks2,
    dmfCompletion, dmfQAReview2, dmfRemarks2,
    otherCertsCompletion, otherCertsQAReview2, otherCertsRemarks2,
    environmentCompletion, environmentQAReview2, environmentRemarks2,
    machineCompletion, machineQAReview2, machineRemarks2,
    skillsCompletion, skillsQAReview2, skillsRemarks2,
    safetyCompletion, safetyQAReview2, safetyRemarks2,
    vendorCompletion, vendorQAReview2, vendorRemarks2,
    trainingCompletion, trainingQAReview2, trainingRemarks2,
    otherCompletion, otherQAReview2, otherRemarks2,Effectiveness,EffectivenessOpp
) VALUES (
    '".$input['id']."', 'To_QA_FinalRev', '".$_GET['emp_id']."', '$entry_date',
    
    '".$input['productQualityCompletion']."', '".$input['productQualityQAReview2']."', '".$input['productQualityRemarks2']."',
    '".$input['sopCompletion']."', '".$input['sopQAReview2']."', '".$input['sopRemarks2']."',
    '".$input['specificationCompletion']."', '".$input['specificationQAReview2']."', '".$input['specificationRemarks2']."',
    '".$input['stpCompletion']."', '".$input['stpQAReview2']."', '".$input['stpRemarks2']."',
    '".$input['sdsCompletion']."', '".$input['sdsQAReview2']."', '".$input['sdsRemarks2']."',
    '".$input['moaAwrCompletion']."', '".$input['moaAwrQAReview2']."', '".$input['moaAwrRemarks2']."',
    '".$input['bmrMfrCompletion']."', '".$input['bmrMfrQAReview2']."', '".$input['bmrMfrRemarks2']."',
    '".$input['equipmentCompletion']."', '".$input['equipmentQAReview2']."', '".$input['equipmentRemarks2']."',
    '".$input['measurementsCompletion']."', '".$input['measurementsQAReview2']."', '".$input['measurementsRemarks2']."',
    '".$input['materialCompletion']."', '".$input['materialQAReview2']."', '".$input['materialRemarks2']."',
    '".$input['validationCompletion']."', '".$input['validationQAReview2']."', '".$input['validationRemarks2']."',
    '".$input['stabilityStudiesCompletion']."', '".$input['stabilityStudiesQAReview2']."', '".$input['stabilityStudiesRemarks2']."',
    '".$input['pvCompletion']."', '".$input['pvQAReview2']."', '".$input['pvRemarks2']."',
    '".$input['amvCompletion']."', '".$input['amvQAReview2']."', '".$input['amvRemarks2']."',
    '".$input['dqOqIqPqCompletion']."', '".$input['dqOqIqPqQAReview2']."', '".$input['dqOqIqPqRemarks2']."',
    '".$input['statutoryCompletion']."', '".$input['statutoryQAReview2']."', '".$input['statutoryRemarks2']."',
    '".$input['fdaLicenseCompletion']."', '".$input['fdaLicenseQAReview2']."', '".$input['fdaLicenseRemarks2']."',
    '".$input['whoLicenseCompletion']."', '".$input['whoLicenseQAReview2']."', '".$input['whoLicenseRemarks2']."',
    '".$input['kosherCompletion']."', '".$input['kosherQAReview2']."', '".$input['kosherRemarks2']."',
    '".$input['iso9001Completion']."', '".$input['iso9001QAReview2']."', '".$input['iso9001Remarks2']."',
    '".$input['dmfCompletion']."', '".$input['dmfQAReview2']."', '".$input['dmfRemarks2']."',
    '".$input['otherCertsCompletion']."', '".$input['otherCertsQAReview2']."', '".$input['otherCertsRemarks2']."',
    '".$input['environmentCompletion']."', '".$input['environmentQAReview2']."', '".$input['environmentRemarks2']."',
    '".$input['machineCompletion']."', '".$input['machineQAReview2']."', '".$input['machineRemarks2']."',
    '".$input['skillsCompletion']."', '".$input['skillsQAReview2']."', '".$input['skillsRemarks2']."',
    '".$input['safetyCompletion']."', '".$input['safetyQAReview2']."', '".$input['safetyRemarks2']."',
    '".$input['vendorCompletion']."', '".$input['vendorQAReview2']."', '".$input['vendorRemarks2']."',
    '".$input['trainingCompletion']."', '".$input['trainingQAReview2']."', '".$input['trainingRemarks2']."',
    '".$input['otherCompletion']."', '".$input['otherQAReview2']."', '".$input['otherRemarks2']."', '".$input['Effectiveness']."', '".$input['EffectivenessOpp']."'
)";
   if ($conn->query($sql)) {
        // Now update the changecontrol table status to 'To_QA_FinalRev'
        $updateSql = "UPDATE changecontrol SET status = 'To_QA_FinalRev' WHERE id = '".$input['id']."'";
        
        if ($conn->query($updateSql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
}
    else if($_GET['type'] == 'saveChangeApprovalByQaHead'){
          
         $sql ="UPDATE `changecontrol`  SET status = 'For_Monitoring_And_FollowUp' , approvalOfChange = '".$input['approvalOfChange']."',
         commentByHeadQaOnAPprovalOfChange = '".$input['commentByHeadQaOnAPprovalOfChange']."',justificationOfRejection = '".$input['justificationOfRejection']."' ,
         approvalOfCcByQaHeadBy = '".$_GET['emp_id']."', approvalOfCcByQaHeadOn = '$entry_date' where id = '".$input['id']."' "; 
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if($_GET['type'] == 'saveCcForMonitoringFollowUp'){
          
         $sql ="UPDATE `changecontrol` SET status = 'For_QA_Assessment_Checklist' , extTcd = '".$input['extTcd']."',extAtcd = '".$input['extAtcd']."',
         impCheck1 = '".$input['impCheck1']."' ,impCheck2 = '".$input['impCheck2']."' ,impCheck3 = '".$input['impCheck3']."' ,
         impCheck4 = '".$input['impCheck4']."' , monitoringBy = '".$_GET['emp_id']."', monitoringOn = '$entry_date' where id = '".$input['id']."'"; 
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if($_GET['type'] == 'saveCcForQaAssessmentChecklist'){
          
        $sql ="UPDATE `changecontrol` SET status = 'For_Closin_Checklist' , checlistData = '".json_encode($input['checlistData'])."' , 
        qaAssCheckBy = '".$_GET['emp_id']."', qaAssCheckOn = '$entry_date' where id = '".$input['id']."'"; 
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if($_GET['type'] == 'saveCcForClosinChecklist'){
          
        $sql ="UPDATE `changecontrol` SET status = 'For_Change_Closed_By_QA_Head' , closinChecklist = '".json_encode($input['closinChecklist'])."' , 
        changeReviewedBy = '".$_GET['emp_id']."', changeReviewedOn = '$entry_date' where id = '".$input['id']."'"; 
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if($_GET['type'] == 'closedByQaHead'){
          
        $sql ="UPDATE `changecontrol` SET status = 'Closed',closedBy = '".$_GET['emp_id']."',closedOn = '$entry_date' where id = '".$input['id']."'"; 
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
  else if($_GET['type'] == 'closedByQaHeadMeha'){
          
        $sql ="UPDATE `changecontrol` SET status = 'FinalCommentQA',
        BatchNo='".$input['BatchNo']."',
        Verification='".$input['Verification']."',
        closedBy = '".$_GET['emp_id']."',closedOn = '$entry_date' where id = '".$input['id']."'"; 
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }

    else if($_GET['type'] == 'getCcForQaaAssessment'){ 
            $output = Array();
            
            $sql = "SELECT * FROM changecontrol WHERE engg != 'Pending' AND admin != 'Pending' AND production != 'Pending' AND 
            ehs != 'Pending' AND qc != 'Pending' AND store != 'Pending' AND micro != 'Pending' AND it != 'Pending' AND hr != 'Pending' AND 
            regulatory != 'Pending' AND qa != 'Pending' AND rnd != 'Pending' AND plant_id= '".$_GET["plant_id"]."'";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    
                                $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
            
             $sql1 = "SELECT 
    CONCAT(e1.firstname, ' ', e1.lastname) AS initiatedBy, 
    CONCAT(e2.firstname, ' ', e2.lastname) AS authBy
FROM 
    employee e1
LEFT JOIN 
    employee e2 ON e2.emp_id = '".$row["concernHodBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
WHERE 
    e1.emp_id = '".$row["entryBy"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["initiatedBy"] =  $row1["initiatedBy"];
                    $row["authBy"] =  $row1["authBy"];
                }
            }
                    
                    
                    
            $output1 = Array();
            
            $sql1 = "SELECT * FROM CcConsentReview WHERE ccNo= '".$row["ctrl_no"]."' AND plant_id= '".$_GET["plant_id"]."'";
            
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                     $output1[] = $row1;
                }
            }
                    $row['deptReview'] = $output1;
                     $output[] = $row;
                }
            }
            echo json_encode($output);
    }






   else if($_GET["type"] == "CCLogMehaPdf")
        {
            $_GET['filename'] = '';
        $_GET['pdftype'] = 'onlyheader';
        include("./pdfimp2.php");
         $sql = "
    SELECT 
        c.*, 
        c.status AS statusMeha, 
        c2.* 
    FROM changecontrol c
    LEFT JOIN changecontrol2 c2 ON c.id = c2.id 
    WHERE c.id = '".$_GET["id"]."'";
      $result = $conn->query($sql);
    $row = $result->fetch_assoc();{
         $html .= '';
         $html .= '<h1 style="text-align: center;border:1px solid black;">Change Control</h1>
                    <div>
                        <div class="clr-col-lg-12 clr-col-12 clr-col-sm-12 clr-col-xs-12">
                            <table class="table table-border" cellpadding="5">
                                <tr>
                                    <td style="text-align: left; font-weight: bolder;">CC No.</td>
                                    <td style="text-align: left; font-weight: bolder; color:blue; ">  '.$row["ctrl_no"].'
                                       </td>
                                    <td style="text-align: left; font-weight: bolder;">Change Control Issued By</td>
                                    <td style="text-align: left; font-weight: bolder; color:blue; ">
                                      '.$row["entryBy"].' / '.$row["department_name"].'  </td>
                                </tr>
                                <tr>
                                    <td style="text-align: left; font-weight: bolder;">Initiated By </td>
                                    <td style="text-align: left; font-weight: bolder; color:blue; ">
                                        '.$row["entryBy"].'</td>
                                    <td style="text-align: left; font-weight: bolder;">Department</td>
                                    <td style="text-align: left; font-weight: bolder; color:blue; ">
                                         '.$row["department_name"].'</td>
                                </tr>
                                <tr>
                                    <td style="text-align: left; font-weight: bolder;">Changed Requested For</td>
                                    <td style="text-align: left; font-weight: bolder; color:blue;">
                                        '.$row["changeReqFor"].'
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: left;font-weight: bolder;" colspan="4">
                                        <b>REASON & JUSTIFICATION OF PROPOSED CHANGE:</b><br><br>
                                        <span style="color: blue;"> '.$row["justification"].'
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: left;font-weight: bolder;" colspan="4">
                                        <b>Is the Change due to CAPA:</b><br><br>
                                        <span style="color: blue;"> '.$row["changeCAPA"].' 
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: left;font-weight: bolder;" >
                                        <b>CAPA:</b></td><td>
                                        <span style="color: blue;"> '.$row["capaDetails"].'
                                        </span>
                                    </td>
                            
                                    <td style="text-align: left;font-weight: bolder;">
                                        <b>Dated On:</b></td><td>
                                        <span style="color: blue;"> '.$row["capaDate"].'
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: left; font-weight: bolder;" colspan="4">
                                        <b>DETAILS OF PROPOSED CHANGE:</b><br><br>
                                        <span style="color: blue;">'.$row["proposed"].'</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: left; font-weight: bolder;" colspan="4">
                                        <b>COMMENTS BY INITIATION DEPARTMENT HOD:</b><br><br>
                                        <span style="color: blue;">'.$row["concernHodComment"].'</span>
                                    </td>
                                </tr>
                            </table>
                            <h2> Impact assessment of Proposed Change:</h2>
                            <h4> EVALUATION AND IMPACT ASSESSMENT OF PROPOSED CHANGE: (To be filled up by Quality
                                Assurance)</h4>
    <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%; text-align: left;">
    <thead style="background-color: #f2f2f2;">
        <tr>
            <th>Process</th>
            <th>Status</th>
            <th>Responsible Dept</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        <tr>
    <td>Product Quality</td>
    <td style="color: blue;">' . (!empty($row["productQualityCheck"]) ? ($row["productQualityCheck"] == 1 ? "Check" : $row["productQualityCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptproductQualityCheck"]) ? $row["DeptproductQualityCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["productQualityRemarks"]) ? $row["productQualityRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Standard Operating Procedure (SOP)</td>
    <td style="color: blue;">' . (!empty($row["sopCheck"]) ? ($row["sopCheck"] == 1 ? "Check" : $row["sopCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptsopCheck"]) ? $row["DeptsopCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["sopRemarks"]) ? $row["sopRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Specification</td>
    <td style="color: blue;">' . (!empty($row["specificationCheck"]) ? ($row["specificationCheck"] == 1 ? "Check" : $row["specificationCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptspecificationCheck"]) ? $row["DeptspecificationCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["specificationRemarks"]) ? $row["specificationRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Standard Test Procedure (STP)</td>
    <td style="color: blue;">' . (!empty($row["stpCheck"]) ? ($row["stpCheck"] == 1 ? "Check" : $row["stpCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptstpCheck"]) ? $row["DeptstpCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["stpRemarks"]) ? $row["stpRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Safety Data Sheet (SDS)</td>
    <td style="color: blue;">' . (!empty($row["sdsCheck"]) ? ($row["sdsCheck"] == 1 ? "Check" : $row["sdsCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptsdsCheck"]) ? $row["DeptsdsCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["sdsRemarks"]) ? $row["sdsRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Method of Analysis & Awareness (MOA)</td>
    <td style="color: blue;">' . (!empty($row["moaAwrCheck"]) ? ($row["moaAwrCheck"] == 1 ? "Check" : $row["moaAwrCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptmoaAwrCheck"]) ? $row["DeptmoaAwrCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["moaAwrRemarks"]) ? $row["moaAwrRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Batch Manufacturing Record (BMR) / Master Formula Record (MFR)</td>
    <td style="color: blue;">' . (!empty($row["bmrMfrCheck"]) ? ($row["bmrMfrCheck"] == 1 ? "Check" : $row["bmrMfrCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptbmrMfrCheck"]) ? $row["DeptbmrMfrCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["bmrMfrRemarks"]) ? $row["bmrMfrRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Equipment</td>
    <td style="color: blue;">' . (!empty($row["equipmentCheck"]) ? ($row["equipmentCheck"] == 1 ? "Check" : $row["equipmentCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptequipmentCheck"]) ? $row["DeptequipmentCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["equipmentRemarks"]) ? $row["equipmentRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Measurements</td>
    <td style="color: blue;">' . (!empty($row["measurementsCheck"]) ? ($row["measurementsCheck"] == 1 ? "Check" : $row["measurementsCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptmeasurementsCheck"]) ? $row["DeptmeasurementsCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["measurementsRemarks"]) ? $row["measurementsRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Raw Materials</td>
    <td style="color: blue;">' . (!empty($row["materialCheck"]) ? ($row["materialCheck"] == 1 ? "Check" : $row["materialCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptmaterialCheck"]) ? $row["DeptmaterialCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["materialRemarks"]) ? $row["materialRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Validation</td>
    <td style="color: blue;">' . (!empty($row["validationCheck"]) ? ($row["validationCheck"] == 1 ? "Check" : $row["validationCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptvalidationCheck"]) ? $row["DeptvalidationCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["validationRemarks"]) ? $row["validationRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Stability Studies</td>
    <td style="color: blue;">' . (!empty($row["stabilityStudiesCheck"]) ? ($row["stabilityStudiesCheck"] == 1 ? "Check" : $row["stabilityStudiesCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptstabilityStudiesCheck"]) ? $row["DeptstabilityStudiesCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["stabilityStudiesRemarks"]) ? $row["stabilityStudiesRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Process Validation (PV)</td>
    <td style="color: blue;">' . (!empty($row["pvCheck"]) ? ($row["pvCheck"] == 1 ? "Check" : $row["pvCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptpvCheck"]) ? $row["DeptpvCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["pvRemarks"]) ? $row["pvRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Analytical Method Validation (AMV)</td>
    <td style="color: blue;">' . (!empty($row["amvCheck"]) ? ($row["amvCheck"] == 1 ? "Check" : $row["amvCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptamvCheck"]) ? $row["DeptamvCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["amvRemarks"]) ? $row["amvRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Statutory Compliance</td>
    <td style="color: blue;">' . (!empty($row["statutoryCheck"]) ? ($row["statutoryCheck"] == 1 ? "Check" : $row["statutoryCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptstatutoryCheck"]) ? $row["DeptstatutoryCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["statutoryRemarks"]) ? $row["statutoryRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>FDA License</td>
    <td style="color: blue;">' . (!empty($row["fdaLicenseCheck"]) ? ($row["fdaLicenseCheck"] == 1 ? "Check" : $row["fdaLicenseCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptfdaLicenseCheck"]) ? $row["DeptfdaLicenseCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["fdaLicenseRemarks"]) ? $row["fdaLicenseRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>WHO License</td>
    <td style="color: blue;">' . (!empty($row["whoLicenseCheck"]) ? ($row["whoLicenseCheck"] == 1 ? "Check" : $row["whoLicenseCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptwhoLicenseCheck"]) ? $row["DeptwhoLicenseCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["whoLicenseRemarks"]) ? $row["whoLicenseRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Environmental Compliance</td>
    <td style="color: blue;">' . (!empty($row["environmentCheck"]) ? ($row["environmentCheck"] == 1 ? "Check" : $row["environmentCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptenvironmentCheck"]) ? $row["DeptenvironmentCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["environmentRemarks"]) ? $row["environmentRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Machine Maintenance</td>
    <td style="color: blue;">' . (!empty($row["machineCheck"]) ? ($row["machineCheck"] == 1 ? "Check" : $row["machineCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptmachineCheck"]) ? $row["DeptmachineCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["machineRemarks"]) ? $row["machineRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Workforce Skills & Training</td>
    <td style="color: blue;">' . (!empty($row["skillsCheck"]) ? ($row["skillsCheck"] == 1 ? "Check" : $row["skillsCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptskillsCheck"]) ? $row["DeptskillsCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["skillsRemarks"]) ? $row["skillsRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Workplace Safety</td>
    <td style="color: blue;">' . (!empty($row["safetyCheck"]) ? ($row["safetyCheck"] == 1 ? "Check" : $row["safetyCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptsafetyCheck"]) ? $row["DeptsafetyCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["safetyRemarks"]) ? $row["safetyRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Vendor Compliance</td>
    <td style="color: blue;">' . (!empty($row["vendorCheck"]) ? ($row["vendorCheck"] == 1 ? "Check" : $row["vendorCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DeptvendorCheck"]) ? $row["DeptvendorCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["vendorRemarks"]) ? $row["vendorRemarks"] : 'NA') . '</td>
</tr>
<tr>
    <td>Training</td>
    <td style="color: blue;">' . (!empty($row["trainingCheck"]) ? ($row["trainingCheck"] == 1 ? "Check" : $row["trainingCheck"]) : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["DepttrainingCheck"]) ? $row["DepttrainingCheck"] : 'NA') . '</td>
    <td style="color: blue;">' . (!empty($row["trainingRemarks"]) ? $row["trainingRemarks"] : 'NA') . '</td>
</tr></tbody>
</table>
               <div></div>
               <table class="table  table-border" cellpadding="3">
                <tr>
                   <td colspan="2" style="text-align: left; font-weight: bolder; padding: 5px;">
                    Classification</td>
                   <td colspan="2" style="color: blue;">'.$row["classification"].' </td>
                   </tr>
                <tr>
                   <td style="text-align: left;" colspan="4">
                    <span style="text-align: left;"><b>REVIEW OF ACTION PLAN & APPROVAL BY
                        QA:</b></span><br><br>
                    <span style="text-align: left;color: blue;"> '.$row["APPROVAL_QA"].'</span>
                    </td>
                   </tr>
              
                </table><div></div><div></div>
               <table class="table table-border">';
                  
            $output2 = array();
            $sql2 = "SELECT * FROM CcConsentReview WHERE ccNo = '".$row['ctrl_no']."'";
            $result2 = $conn->query($sql2);
    
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    // Decode JSON actionData if exists
                    $row2["actionData"] = !empty($row2["actionData"]) ? json_decode($row2["actionData"], true) : [];
    
                    $output2[] = $row2;
            }
        }

        // Add department review data
        $row['deptReview'] = $output2;
        $output[] = $row;

        // Generate HTML
        $html .= '
    

<table class="table table-border" style="width: 100%; border-collapse: collapse;">
    <tr>
        <td colspan="3" style="text-align: center; font-weight: bold; font-size: 14px; padding: 10px;">
            CC Consent & Review By Dept.
        </td>
    </tr>
    <tr>
        <th style="text-align: left; font-weight: bold; background-color: aquamarine; padding: 8px;">
            Department
        </th>
        <th style="text-align: left; font-weight: bold; background-color: aquamarine; padding: 8px;">
            Impact Assessment and Action Plan
        </th>
        <th style="text-align: left; font-weight: bold; background-color: aquamarine; padding: 8px;">
            Responsibility / TCD
        </th>
    </tr>';

foreach ($output2 as $values) {
    $department = !empty($values['department']) ? htmlspecialchars($values['department'], ENT_QUOTES, 'UTF-8') : 'NA';
    $impactAssessment = !empty($values['ImpactDept']) ? htmlspecialchars($values['ImpactDept'], ENT_QUOTES, 'UTF-8') : 'NA';
    $responsibility = !empty($values['Responsibility']) ? htmlspecialchars($values['Responsibility'], ENT_QUOTES, 'UTF-8') : 'NA';

    $html .= '
    <tr>
        <td style="padding: 8px; border: 1px solid #ddd;">' . $department . '</td>
        <td style="padding: 8px; border: 1px solid #ddd;">' . $impactAssessment . '</td>
        <td style="padding: 8px; border: 1px solid #ddd;">' . $responsibility . '</td>
    </tr>';
}

$html .= '</table>';

           $html .= ' </div>
           </div>

<table cellpadding="6" class="table table-border" style="width: 100%; border-collapse: collapse;">
    <tr>
        <td colspan="7" style="text-align: left; font-weight: bold; padding: 10px;">
            Department Affected Documents:
        </td>
    </tr>
    <tr>
        <th style="text-align: left; background-color: aquamarine; padding: 8px;">Department Name</th>
        <th style="text-align: left; background-color: aquamarine; padding: 8px;">Name of the Document</th>
        <th style="text-align: left; background-color: aquamarine; padding: 8px;">Document No.</th>
        <th style="text-align: left; background-color: aquamarine; padding: 8px;">Existing Reference No.</th>
        <th style="text-align: left; background-color: aquamarine; padding: 8px;">Revised Reference No.</th>
        <th style="text-align: left; background-color: aquamarine; padding: 8px;">TCD</th>
        <th style="text-align: left; background-color: aquamarine; padding: 8px;">Remarks</th>
    </tr>';

// Decode the JSON data for departments
$json_obj = $row['departmentChangeData'];
$array = json_decode($json_obj, true);

// Check if decoding was successful
if (!empty($array)) {
    foreach ($array as $values) {

        // Safely extract values with fallback
        $departmentName = htmlspecialchars($values['departmentName'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $documentName   = htmlspecialchars($values['documentName'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $documentNo     = htmlspecialchars($values['documentNo'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $existingRefNo  = htmlspecialchars($values['existingRefNo'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $revisedRefNo   = htmlspecialchars($values['revisedRefNo'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $tcDate         = htmlspecialchars($values['tcDate'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $remarks        = htmlspecialchars($values['remarks'] ?? 'NA', ENT_QUOTES, 'UTF-8');

        // Append each row
        $html .= '
        <tr>
            <td style="text-align: left; color: blue; padding: 8px;"><b>' . $departmentName . '</b></td>
            <td style="text-align: left; color: blue; padding: 8px;"><b>' . $documentName . '</b></td>
            <td style="text-align: left; color: blue; padding: 8px;"><b>' . $documentNo . '</b></td>
            <td style="text-align: left; color: blue; padding: 8px;"><b>' . $existingRefNo . '</b></td>
            <td style="text-align: left; color: blue; padding: 8px;"><b>' . $revisedRefNo . '</b></td>
            <td style="text-align: left; color: blue; padding: 8px;"><b>' . $tcDate . '</b></td>
            <td style="text-align: left; color: blue; padding: 8px;"><b>' . $remarks . '</b></td>
        </tr>';
    }
} else {
    // Optional: Add a message if no data is available
    $html .= '
    <tr>
        <td colspan="7" style="text-align: center; color: red; padding: 10px;">
            No department documents found.
        </td>
    </tr>';
}

// Close the table tag outside the loop
$html .= '</table>';

            
$html .=  '<br><br>
  
            <table cellpadding="6" class="table table-border">
                <tr>
                    <td style="text-align: left;" colspan="7"><b>Equipment to be revised / updated</b></td>
                </tr>
                <tr>
                    <td style="text-align: left; background-color: aquamarine;"><b>Sr. No.</b></td>
                    <td style="text-align: left; background-color: aquamarine;"><b>Department Name</b></td>
                    <td style="text-align: left; background-color: aquamarine;"><b>Equipment</b></td>
                    <td style="text-align: left; background-color: aquamarine;"><b>Equipment Code</b></td>
                    <td style="text-align: left; background-color: aquamarine;"><b>New Equipment Code</b></td>
                    <td style="text-align: left; background-color: aquamarine;"><b>TCD Sign & Date</b></td>
                    <td style="text-align: left; background-color: aquamarine;"><b>Remarks</b></td>
                </tr>';

// Decode the JSON data for equipment changes
$json_obj = $row['equipmentChangeData'];
$array = json_decode($json_obj, true);

// Check if decoding was successful and array is not empty
if (!empty($array)) {
    $sr_no = 1; // Initialize serial number
    foreach ($array as $values) {
        // Safely extract values with fallback
        $departmentName = htmlspecialchars($values['departmentName'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $equipment      = htmlspecialchars($values['equipmentName'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $equipmentCode  = htmlspecialchars($values['equipmentCode'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $newEquipmentCode = htmlspecialchars($values['newEquipmentCode'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $tcdSignDate    = htmlspecialchars($values['tcdSignDate'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $remarks        = htmlspecialchars($values['remarks'] ?? 'NA', ENT_QUOTES, 'UTF-8');

        // Append each row to the table
        $html .=  '<tr>
            <td style="text-align: left; color: blue;"><b>' . $sr_no . '</b></td>
            <td style="text-align: left; color: blue;"><b>' . $departmentName . '</b></td>
            <td style="text-align: left; color: blue;"><b>' . $equipment . '</b></td>
            <td style="text-align: left; color: blue;"><b>' . $equipmentCode . '</b></td>
            <td style="text-align: left; color: blue;"><b>' . $newEquipmentCode . '</b></td>
            <td style="text-align: left; color: blue;"><b>' . $tcdSignDate . '</b></td>
            <td style="text-align: left; color: blue;"><b>' . $remarks . '</b></td>
        </tr>';

        $sr_no++; // Increment serial number
    }
} else {
    // If no data found
    $html .= '<tr>
        <td colspan="7" style="text-align: center; color: red;"><b>No Equipment Change Data Found</b></td>
    </tr>';
}

$html .= '</table> ';
      $html .= '   
    <div> </div>
    <label><strong>REVIEW OF ACTION PLAN & APPROVAL BY QA:</strong></label>
    <p style="color: blue; border: 1px solid black;">' . (!empty($row["ACTION_PLAN"]) ? $row["ACTION_PLAN"] : 'NA') . '</p>
       
    <div class="clr-row">
        <div class="clr-col-lg-12">
            <div class="clr-row">
                <div class="clr-col-12">
                    <table cellpadding="6" class="table table-border">
                        <tr>
                            <td colspan="7" style="text-align: left;"><b>Document Change Records:</b></td>
                        </tr>
                        <tr style="background-color: aquamarine;">
                            <td><b>Sr. No.</b></td>
                            <td><b>Name of the Document</b></td>
                            <td><b>Document No.</b></td>
                            <td><b>Updated by</b></td>
                            <td><b>Department</b></td>
                            <td><b>Verified by</b></td>
                            <td><b>Remarks</b></td>
                        </tr>';

// Decode the JSON data for document changes
$json_obj = $row['documentChangeData'];  // Make sure it's documentChangeData, not equipmentChangeData if this is for documents.
$array = json_decode($json_obj, true);

// Check if decoding was successful and array is not empty
if (!empty($array)) {
    $sr_no = 1; // Initialize serial number
    foreach ($array as $values) {
        // Safely extract values with fallback
        $documentName  = htmlspecialchars($values['documentName'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $documentNo    = htmlspecialchars($values['documentNo'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $updatedBy     = htmlspecialchars($values['updatedBy'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $department    = htmlspecialchars($values['department'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $verifiedBy    = htmlspecialchars($values['verifiedBy'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $remarks       = htmlspecialchars($values['remarks'] ?? 'NA', ENT_QUOTES, 'UTF-8');

        $html .= '    
            <tr>
                <td style="color: blue;"><b>' . $sr_no . '</b></td>
                <td style="color: blue;"><b>' . $documentName . '</b></td>
                <td style="color: blue;"><b>' . $documentNo . '</b></td>
                <td style="color: blue;"><b>' . $updatedBy . '</b></td>
                <td style="color: blue;"><b>' . $department . '</b></td>
                <td style="color: blue;"><b>' . $verifiedBy . '</b></td>
                <td style="color: blue;"><b>' . $remarks . '</b></td>
            </tr>';
        
        $sr_no++;
    }
} else {
    $html .= '
        <tr>
            <td colspan="7" style="text-align: center; color: red;"><b>No Document Change Data Found</b></td>
        </tr>';
}

$html .= '   
                    </table>
                </div>
            </div>
        </div>
    </div>';

    $html .= '            
    <div class="clr-row">
        <div class="clr-col-12">
            <table cellpadding="6" class="table table-border">
                <tr>
                    <td colspan="7" style="text-align: left;"><b>Equipment Review</b></td>
                </tr>
                <tr style="background-color: aquamarine;">
                    <td><b>Sr. No.</b></td>
                    <td><b>Name of the Equipment</b></td>
                    <td><b>PO No.</b></td>
                    <td><b>Updated by</b></td>
                    <td><b>Department</b></td>
                    <td><b>Verified by</b></td>
                    <td><b>Remarks</b></td>
                </tr>';

// Decode the JSON data for equipment changes
$json_obj = $row['equipmentChangeDataTwo'];
$array = json_decode($json_obj, true);

// Check if decoding was successful and array is not empty
if (!empty($array)) {
    $sr_no = 1; // Initialize serial number
    foreach ($array as $values) {
        // Safely extract values with fallback
        $equipmentName = htmlspecialchars($values['equipmentName'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $poNo          = htmlspecialchars($values['poNo'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $updatedBy     = htmlspecialchars($values['updatedBy'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $department    = htmlspecialchars($values['department'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $verifiedBy    = htmlspecialchars($values['verifiedBy'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $remarks       = htmlspecialchars($values['remarks'] ?? 'NA', ENT_QUOTES, 'UTF-8');

        $html .= '    
                <tr>
                    <td style="color: blue;"><b>' . $sr_no . '</b></td>
                    <td style="color: blue;"><b>' . $equipmentName . '</b></td>
                    <td style="color: blue;"><b>' . $poNo . '</b></td>
                    <td style="color: blue;"><b>' . $updatedBy . '</b></td>
                    <td style="color: blue;"><b>' . $department . '</b></td>
                    <td style="color: blue;"><b>' . $verifiedBy . '</b></td>
                    <td style="color: blue;"><b>' . $remarks . '</b></td>
                </tr>';
        
        $sr_no++;
    }
} else {
    $html .= '
        <tr>
            <td colspan="7" style="text-align: center; color: red;"><b>No Equipment Review Data Found</b></td>
        </tr>';
}

$html .= '   
            </table>
        </div>
    </div>';

    $html .= '           
    <div class="clr-row">
        <div class="clr-col-12">
            <table cellpadding="6" class="table table-border">
                <tr>
                    <td colspan="6" style="text-align: left;"><b>Training Review:</b></td>
                </tr>
                <tr style="background-color: aquamarine;">
                    <td><b>Sr. No.</b></td>
                    <td><b>Name of the Document/Equipment</b></td>
                    <td><b>Document No.</b></td>
                    <td><b>Training Date</b></td>
                    <td><b>Reviewed by QA</b></td>
                    <td><b>Remarks</b></td>
                </tr>';

// Decode the JSON data for document/equipment training review
$json_obj = $row['documentEquipmentData'];
$array = json_decode($json_obj, true);

// Check if decoding was successful and array is not empty
if (!empty($array)) {
    $sr_no = 1; // Initialize serial number
    foreach ($array as $values) {
        // Safely extract values with fallback
        $documentEquipmentName = htmlspecialchars($values['documentEquipmentName'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $documentNo            = htmlspecialchars($values['documentNo'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $trainingDate          = htmlspecialchars($values['trainingDate'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $reviewedByQA          = htmlspecialchars($values['reviewedByQA'] ?? 'NA', ENT_QUOTES, 'UTF-8');
        $remarks               = htmlspecialchars($values['remarks'] ?? 'NA', ENT_QUOTES, 'UTF-8');

        $html .= '    
                <tr>
                    <td style="color: blue;"><b>' . $sr_no . '</b></td>
                    <td style="color: blue;"><b>' . $documentEquipmentName . '</b></td>
                    <td style="color: blue;"><b>' . $documentNo . '</b></td>
                    <td style="color: blue;"><b>' . $trainingDate . '</b></td>
                    <td style="color: blue;"><b>' . $reviewedByQA . '</b></td>
                    <td style="color: blue;"><b>' . $remarks . '</b></td>
                </tr>';

        $sr_no++;
    }
} else {
    $html .= '
        <tr>
            <td colspan="6" style="text-align: center; color: red;"><b>No Training Review Data Found</b></td>
        </tr>';
}

$html .= '   
            </table>
        </div>
    </div>';
$html .= '   
          
<table border="1" cellpadding="5" class="table table-bordered">
    <tr>
        <th>Process</th>
        <th>Status</th>
        <th>Responsible Dept</th>
        <th>Remarks</th>
    </tr>

    <tr>
        <td><strong>Product Quality</strong></td>
        <td style="color: blue;">' . (!empty($row["productQualityCompletion"]) ? $row["productQualityCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["productQualityQAReview2"]) ? $row["productQualityQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["productQualityRemarks2"]) ? $row["productQualityRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Standard Operating Procedure (SOP)</strong></td>
        <td style="color: blue;">' . (!empty($row["sopCompletion"]) ? $row["sopCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["sopQAReview2"]) ? $row["sopQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["sopRemarks2"]) ? $row["sopRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Specification</strong></td>
        <td style="color: blue;">' . (!empty($row["specificationCompletion"]) ? $row["specificationCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["specificationQAReview2"]) ? $row["specificationQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["specificationRemarks2"]) ? $row["specificationRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Standard Test Procedure (STP)</strong></td>
        <td style="color: blue;">' . (!empty($row["stpCompletion"]) ? $row["stpCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["stpQAReview2"]) ? $row["stpQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["stpRemarks2"]) ? $row["stpRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Safety Data Sheet (SDS)</strong></td>
        <td style="color: blue;">' . (!empty($row["sdsCompletion"]) ? $row["sdsCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["sdsQAReview2"]) ? $row["sdsQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["sdsRemarks2"]) ? $row["sdsRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Method of Analysis & Awareness (MOA)</strong></td>
        <td style="color: blue;">' . (!empty($row["moaAwrCompletion"]) ? $row["moaAwrCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["moaAwrQAReview2"]) ? $row["moaAwrQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["moaAwrRemarks2"]) ? $row["moaAwrRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Batch Manufacturing Record (BMR) / Master Formula Record (MFR)</strong></td>
        <td style="color: blue;">' . (!empty($row["bmrMfrCompletion"]) ? $row["bmrMfrCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["bmrMfrQAReview2"]) ? $row["bmrMfrQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["bmrMfrRemarks2"]) ? $row["bmrMfrRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Equipment</strong></td>
        <td style="color: blue;">' . (!empty($row["equipmentCompletion"]) ? $row["equipmentCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["equipmentQAReview2"]) ? $row["equipmentQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["equipmentRemarks2"]) ? $row["equipmentRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Measurements</strong></td>
        <td style="color: blue;">' . (!empty($row["measurementsCompletion"]) ? $row["measurementsCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["measurementsQAReview2"]) ? $row["measurementsQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["measurementsRemarks2"]) ? $row["measurementsRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Material</strong></td>
        <td style="color: blue;">' . (!empty($row["materialCompletion"]) ? $row["materialCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["materialQAReview2"]) ? $row["materialQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["materialRemarks2"]) ? $row["materialRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Validation</strong></td>
        <td style="color: blue;">' . (!empty($row["validationCompletion"]) ? $row["validationCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["validationQAReview2"]) ? $row["validationQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["validationRemarks2"]) ? $row["validationRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Stability Studies</strong></td>
        <td style="color: blue;">' . (!empty($row["stabilityStudiesCompletion"]) ? $row["stabilityStudiesCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["stabilityStudiesQAReview2"]) ? $row["stabilityStudiesQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["stabilityStudiesRemarks2"]) ? $row["stabilityStudiesRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Process Validation (PV)</strong></td>
        <td style="color: blue;">' . (!empty($row["pvCompletion"]) ? $row["pvCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["pvQAReview2"]) ? $row["pvQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["pvRemarks2"]) ? $row["pvRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Analytical Method Validation (AMV)</strong></td>
        <td style="color: blue;">' . (!empty($row["amvCompletion"]) ? $row["amvCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["amvQAReview2"]) ? $row["amvQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["amvRemarks2"]) ? $row["amvRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Statutory</strong></td>
        <td style="color: blue;">' . (!empty($row["statutoryCompletion"]) ? $row["statutoryCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["statutoryQAReview2"]) ? $row["statutoryQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["statutoryRemarks2"]) ? $row["statutoryRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>FDA License</strong></td>
        <td style="color: blue;">' . (!empty($row["fdaLicenseCompletion"]) ? $row["fdaLicenseCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["fdaLicenseQAReview2"]) ? $row["fdaLicenseQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["fdaLicenseRemarks2"]) ? $row["fdaLicenseRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>WHO License</strong></td>
        <td style="color: blue;">' . (!empty($row["whoLicenseCompletion"]) ? $row["whoLicenseCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["whoLicenseQAReview2"]) ? $row["whoLicenseQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["whoLicenseRemarks2"]) ? $row["whoLicenseRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Environment</strong></td>
        <td style="color: blue;">' . (!empty($row["environmentCompletion"]) ? $row["environmentCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["environmentQAReview2"]) ? $row["environmentQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["environmentRemarks2"]) ? $row["environmentRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Machine</strong></td>
        <td style="color: blue;">' . (!empty($row["machineCompletion"]) ? $row["machineCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["machineQAReview2"]) ? $row["machineQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["machineRemarks2"]) ? $row["machineRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Skills</strong></td>
        <td style="color: blue;">' . (!empty($row["skillsCompletion"]) ? $row["skillsCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["skillsQAReview2"]) ? $row["skillsQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["skillsRemarks2"]) ? $row["skillsRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Safety</strong></td>
        <td style="color: blue;">' . (!empty($row["safetyCompletion"]) ? $row["safetyCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["safetyQAReview2"]) ? $row["safetyQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["safetyRemarks2"]) ? $row["safetyRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Vendor</strong></td>
        <td style="color: blue;">' . (!empty($row["vendorCompletion"]) ? $row["vendorCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["vendorQAReview2"]) ? $row["vendorQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["vendorRemarks2"]) ? $row["vendorRemarks2"] : 'NA') . '</td>
    </tr>

    <tr>
        <td><strong>Training</strong></td>
        <td style="color: blue;">' . (!empty($row["trainingCompletion"]) ? $row["trainingCompletion"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["trainingQAReview2"]) ? $row["trainingQAReview2"] : 'NA') . '</td>
        <td style="color: blue;">' . (!empty($row["trainingRemarks2"]) ? $row["trainingRemarks2"] : 'NA') . '</td>
    </tr>
</table>';

    
    
    
      $html .=  '
                        </div>
                    </div>
                  <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                    <tr>
                        <th colspan="2" style="text-align: left;">DETAILS OF PROPOSED CHANGE:</th>
                    </tr>
                    <tr>
                        <td colspan="2" style="color: blue;">'.$row["EffectivenessOpp"].'</td>
                    </tr>
                
                    <tr>
                        <th colspan="2" style="text-align: left;">Comments for Effectiveness Monitoring Required:</th>
                    </tr>
                    <tr>
                        <td colspan="2" style="color: blue;">'.$row["Effectiveness"].'</td>
                    </tr>
                
                    <tr>
                        <th style="text-align: left;">Effective Batch No:</th>
                        <td style="color: blue;">'.$row["BatchNo"].'</td>
                        
                    </tr>
                
                    <tr>
                        <th colspan="2" style="text-align: left;">Comments for Verification of Effectiveness:</th>
                    </tr>
                    <tr>
                        <td colspan="2" style="color: blue;">'.$row["Verification"].'</td>
                    </tr>
                
                    <tr>
                        <th colspan="2" style="text-align: left;">Change Control Closure:</th>
                    </tr>
                    <tr>
                        <td colspan="2" style="color: blue;">'.$row["Closure"].'</td>
                    </tr>
                </table>

            ';
    }
                    $pdf->writeHTML($html, true, false, false, false, '');
                    $pdf->Output('deviation.pdf', 'I');
                }
         


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////  

 else if($_GET["type"] == "saveQADeptCC"){ }
                            
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////     
else if ($_GET["type"] == "getDeptReviewCC") {
    // $output = Array();
    // $sql = "SELECT * FROM changecontrol WHERE status='approve'  order by 1 desc";
    // $result = $conn->query($sql);
    // if ($result->num_rows > 0) {
    //     while ($row = $result->fetch_assoc()) {
    //          $row["change_related"] = json_decode($row["change_related"]);
    //         // $row["departments"] = json_decode($row["departments"]);
    //         $output[] = $row;
    //     }
    // }
    // echo json_encode($output);
                        if($_GET["deptName"]=='R AND D'){
                            $dept='R_AND_D';
                        }else{
                            $dept=$_GET["deptName"];
                        }
       $dept=$_GET["deptName"];
        $col_head='comment_'.$dept;
       $dept = str_replace(' ', '_', $dept);
        $output = Array();
     //   $sql = "SELECT * FROM changecontrol WHERE status='approve' AND  $dept = 'pending'";
        $sql =  "SELECT cc.*, p.product_name
    FROM changecontrol cc
    JOIN product p ON cc.product_name = p.product_code
    WHERE cc.status = 'approve' AND  cc.$dept = 'pending'
    ORDER BY cc.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             $row["change_related"] = json_decode($row["change_related"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
}
//---------------------------------------------------------------------------------------------------------------------------//
 
//---------------------------------------------------------------------------------------------------------------------------//
	 else if ($_GET["type"] == "getccClosure") {
       
        $output = Array();
        $dept = str_replace(' ', '_', $dept);
        $dept=$_GET["deptName"];
          
    $sql = "SELECT * FROM `changecontrol`where status ='closure'" ;
   
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             $row["change_related"] = json_decode($row["change_related"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
       
   }
//---------------------------------------------------------------------------------------------------------------------------//
	 else if ($_GET["type"] == "getccqa") {
       
        $output = Array();
        $dept = str_replace(' ', '_', $dept);
        $dept=$_GET["deptName"];
          
   // $sql = "SELECT * FROM `changecontrol`where dept_count=count AND  status !='closure' order by 1 desc" ;
   $sql="SELECT cc.*, p.product_name
    FROM changecontrol cc
    JOIN product p ON cc.product_name = p.product_code
    WHERE cc.status != 'closure' And cc.dept_count=count
    ORDER BY cc.id DESC;";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             $row["change_related"] = json_decode($row["change_related"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
       
   }
  //--------------------------------------------------------------------------------------------------------------------------//
else if ($_GET["type"] == "saveCCclosure") {
    $sql = "UPDATE changecontrol SET status='log', check_by='".$_GET["emp_id"]."', check_date='$entry_date', 
    document= '".$input['document']."' ,
    version= '".$input['version']."' 
    WHERE ctrl_no='".$_GET["ctrl_no"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\": true}";
    } else {
        echo "{\"status\": false}";
    }
 }
  //--------------------------------------------------------------------------------------------------------------------------//
else if ($_GET["type"] == "checkChangeControl") {
    $sql = "UPDATE changecontrol SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date', dept_remark='".$_GET["remark"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\": true}";
    } else {
        echo "{\"status\": false}";
    }
 }
  //--------------------------------------------------------------------------------------------------------------------------//
else if ($_GET["type"] == "ccprimary") {
    $sql = "UPDATE changecontrol SET status='Review', check_by='".$_GET["emp_id"]."', check_date='$entry_date', comments= '".$input['comment']."' ,validation_required= '".$input['validation']."'  WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\": true}";
    } else {
        echo "{\"status\": false}";
    }
 }
   //--------------------------------------------------------------------------------------------------------------------------//

 else if ($_GET["type"] == "getInprocessDept") {
    $output = Array();
   //  $sql = "SELECT * FROM changecontrol WHERE status='Review' order by 1 desc";
          $sql = "SELECT cc.*, p.product_name
    FROM changecontrol cc
    JOIN product p ON cc.product_name = p.product_code
    WHERE cc.status = 'Review'
    ORDER BY cc.id DESC;";

    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["product_details"] = json_decode($row["product_details"]);
            $row["departments"] = json_decode($row["departments"]);
             $row["change_related"] = json_decode($row["change_related"]);
              $row["classifications"] = json_decode($row["classifications"]);
               $row["market_details"] = json_decode($row["market_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

 else if($_GET["type"] == "saveCCDept"){
        //  ini_set('display_errors', 1);
        //  error_reporting(E_ALL);
                      if($_GET["deptName"]=='Quality Assurance'){
                            $dept='Quality_Assurance';
                        }else  if($_GET["deptName"]=='Quality Assurance'){
                            $dept='Quality_Assurance';
                        }else if($_GET["deptName"]=='Quality Control'){
                            $dept='Quality_Control';
                        }else if($_GET["deptName"]=='R AND D'){
                            $dept='R_AND_D';
                        }else if($_GET["deptName"]=='Human Resource'){
                            $dept='Human_Resource';
                        }else{
                            $dept=$_GET["deptName"];
                        }
                   $col_head='comment_'.$dept;
                   $dept = str_replace(' ', '_', $dept);
                   $count=$input['count']+1;
                    $sql=" UPDATE changecontrol SET  $dept='done',count='$count', $col_head = '".$input['Commentsss']."' 
                   WHERE ctrl_no='" . $_GET["ctrl_no"] . "'";
                          if($conn->query($sql)){
                            echo "{\"status\":\"success\"}";
                        }else {
                            echo "{\"status\":\"failed\"}";
                        }
                            }
                            
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////    
                 
                    else if ($_GET["type"] == "reviewChangeControl") {

                    $qa = 'NO';
                    $qc = 'NO';
                    $engineering = 'NO';
                    $hr = 'NO';
                    $microbiology = 'NO';
                    $rnd = 'NO';
                    $ra = 'NO';
                    $rnd = 'NO';
                    $store = 'NO';      
                    $col_head='comment_'.$dept;
                    $id = $_GET["id"] ;

                   if($input["microbiology"] == 'true'){  $microbiology = 'Pending';    } ;
                    if($input["qa"] == 'true'){  $qa = 'Pending';    } ;
                    if($input["qc"] == 'true'){  $qc = 'Pending';    } ;
                    if($input["store"] == 'true'){  $store = 'Pending';    } ;
                    if($input["engineering"] == 'true'){  $engineering = 'Pending';    } ;
                    if($input["hr"] == 'true'){  $hr = 'Pending';    } ;
                    if($input["ra"] == 'true'){  $ra = 'Pending';    } ;
                    if($input["rnd"] == 'true'){  $rnd = 'Pending';    } ;

      
                //   $count=$input['count']+1;
            if($input["market_approval"] == 'true'){  $market_aprvl = 'YES';    } ;
            if($input["market_not_approval"] == 'true'){  $market_not_approval = 'YES';    } ;
            if($input["validation_required"] == 'true'){  $validation_required ='YES';   }else{  $validation_required ='No';   } ;
            if($input["validation_not_required"] == 'true'){  $validation_not_required = 'YES';    } ;
            $sql = "UPDATE changecontrol SET status='approve', 
                        Quality_Control = '$qc', 
                        Regulatory = '$ra', 
                        Human_Resource = '$hr',
                        Quality_Assurance = '$qa', 
                        Engineering = '$engineering', 
                        microbiology = '$microbiology', 
                        R_AND_D = '$rnd', 
                        dept_count='".$input['total_count']."',
                        Store = '$store'
                        WHERE ctrl_no='".$_GET["ctrl_no"]."'";
  // echo $sql;
    if ($conn->query($sql)) {
        echo "{\"status\": true}";
    } else {
        echo "{\"status\": false}";
    }
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  else if ($_GET["type"] == "reviewrndChangeControl") {
                    $id = $_GET["id"] ;
                //   $count=$input['count']+1;
            $sql = "UPDATE changecontrol SET status='rnd',
                   validation_rnd= '".json_encode($input["validationrnd"])."',
                   market_approval  =  '".json_encode($input["market_approval"])."',
                   verify_by='".$_GET["emp_id"]."', verify_date='$entry_date', comments_rnd='".$input["commentrnd"]."'
                        WHERE ctrl_no='".$_GET["ctrl_no"]."'";
  // echo $sql;
    if ($conn->query($sql)) {
        echo "{\"status\": true}";
    } else {
        echo "{\"status\": false}";
    }
}
//    -------------------------------------------------------------------------------------------------------------------------------------------------
  else if($_GET["type"] == "saveDevaitonDeptH"){
                    $input    = $_POST;      
                      $qa = 'NO';
                    $qc = 'NO';
                    $production = 'NO';
                    $engineering = 'NO';
                    $it = 'NO';
                    $hr = 'NO';
                    $microbiology = 'NO';
                    // $sc = 'NO';
                    $ra = 'NO';
                    $packing = 'NO';
                    $bd = 'NO';
                    $warehouse = 'NO';      
                    $target_dir = "../../upload/deviation/";
                    $col_head='comment_'.$dept;
                    $col_head_file='deviationHodFile';
                    $id = $_GET["id"] ;
                    $dep = $col_head_file;
                    $file_name = "";
                    if(isset($_FILES["jugad"]["name"])){
                        $target_file = $target_dir.$id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        $file_name = $id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        move_uploaded_file($_FILES["jugad"]["tmp_name"], $target_file);
                    }
                  
                    $investigation_hod = str_replace(["'", '"'], '', $input['investigation_hod']);
                    $comments = str_replace(["'", '"'], '', $input['comments']);
                    $Corrective_p = str_replace(["'", '"'], '', $input['Corrective_p']);
                    $preventive_action_plan_details = str_replace(["'", '"'], '', $input['preventive_action_plan_details']);
                    $preventive_action_plan = str_replace(["'", '"'], '', $input['preventive_action_plan']);
                    $other_action_plan = str_replace(["'", '"'], '', $input['other_action_plan']);
                    $other_action_plan_details = str_replace(["'", '"'], '', $input['other_action_plan_details']);
                    $corrective_action_plan = str_replace(["'", '"'], '', $input['corrective_action_plan']);
                    
                    
                    if($input["microbiology"] == 'true'){  $microbiology = 'Pending';    } ;
                    if($input["qa"] == 'true'){  $qa = 'Pending';    } ;
                    if($input["qc"] == 'true'){  $qc = 'Pending';    } ;
                    if($input["production"] == 'true'){  $production = 'Pending';    } ;
                    if($input["warehouse"] == 'true'){  $warehouse = 'Pending';    } ;
                    if($input["engineering"] == 'true'){  $engineering = 'Pending';    } ;
                    if($input["it"] == 'true'){  $it = 'Pending';    } ;
                    if($input["hr"] == 'true'){  $hr = 'Pending';    } ;
                    if($input["ra"] == 'true'){  $ra = 'Pending';    } ;
                    if($input["packing"] == 'true'){  $packing = 'Pending';    } ;
                    if($input["bd"] == 'true'){  $bd = 'Pending';    } ;
                    
                        $sql = "UPDATE deviation SET status='approve',
                        comments= '$comments',
                        document_hod= '".$input['document_hod']."',
                        investigation_hod= '$cleaned_input',
                        corrective_action_plan= '".$input['corrective_action_plan']."',
                        Corrective_p= '$Corrective_p',
                        preventive_action_plan= '$preventive_action_plan',
                        preventive_action_plan_details= '$preventive_action_plan_details',
                        other_action_plan= '$other_action_plan',
                        other_action_plan_details= '$other_action_plan_details',
                        
                        Quality_Control = '$qc', 
                        ra = '$ra', 
                        IT = '$it', 
                        Production = '$production', 
                        Human_Resource = '$hr',
                        bd = '$bd', 
                        Quality_Assurance = '$qa', 
                        Engineering = '$engineering', 
                        microbiology = '$microbiology', 
                        packing = '$packing', 
                        warehouse = '$warehouse',dept_count='".$input['total_count']."', $col_head_file='$file_name'
                        WHERE id='".$_GET["id"]."'";
                        
            if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        }else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    // --------------------------------------------------------------------------------------------------------------------------------------------------------


else if ($_GET["type"] == "getApprovedChangeControls") {
    $output = Array();
    $sql = "SELECT * FROM changecontrol WHERE status='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["product_details"] = json_decode($row["product_details"]);
            $row["departments"] = json_decode($row["departments"]);
             $row["change_related"] = json_decode($row["change_related"]);
              $row["classifications"] = json_decode($row["classifications"]);
               $row["market_details"] = json_decode($row["market_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}else if ($_GET["type"] == "closeChangeControl") {
    $sql = "UPDATE changecontrol SET close_by='".$_GET["emp_id"]."', close_date='".$entry_date."', close_remark='".$input["remark"]."', status='".$input["status"]."' WHERE ctrl_no='".$input["ctrl_no"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}else if ($_GET["type"] == "getChangeControlsDept") {
    $output = Array();
    $sql = "SELECT * FROM changecontrol WHERE status='close' order by 1 desc";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["product_details"] = json_decode($row["product_details"]);
            $row["departments"] = json_decode($row["departments"]);
             $row["change_related"] = json_decode($row["change_related"]);
              $row["classifications"] = json_decode($row["classifications"]);
               $row["market_details"] = json_decode($row["market_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}else if ($_GET["type"] == "getAdminDepartments") {
        $output = Array();
        $sql = "SELECT * FROM changecontrol where departments LIKE '%Admin%'  order by 1 desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
}else if($_GET["type"]=="getRegulatoryDepartments"){
	$output = Array();
    $sql = "SELECT * FROM changecontrol where departments LIKE '%Regulatory%'  order by 1 desc";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else {
    echo "[]";
}

$conn->close();
?>