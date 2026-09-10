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
 
else if ($_GET["type"] == "getCCFordeptConcern") {
    $output = Array();
    $sql = "SELECT * FROM changecontrol WHERE status='Pending' AND plant_id = '".$_GET["plant_id"]."' 
    AND department_name = '".$_GET["deptName"]."' order by id desc";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getCCForconsentAndReview") {
    $output = Array();
    $sql = "SELECT * FROM changecontrol WHERE status='TO_QA_FOR_CIRCULATION' AND plant_id = '".$_GET["plant_id"]."' 
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
    
    
 $sql = "UPDATE changecontrol SET status = 'TO_QA_FOR_CIRCULATION', concernHodComment =  '".$input["concernHodComment"]."' ,
 concernHodBy = '".$_GET["emp_id"]."', concernHodOn = '$entry_date' WHERE id = '".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\": true}";
    } else {
        echo "{\"status\": false}";
    }
    
    
}

    else if($_GET['type'] == 'getCCForDeptConsentAndReview'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'FOR_DEPT_CONSENT_AND_REVIEW' AND plant_id= '".$_GET["plant_id"]."'";
            
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
    else if($_GET['type'] == 'getCcForActionAndReviewByQA'){ 
            $output = Array();
            $sql = "SELECT * FROM changecontrol WHERE  status= 'For_QA_Review_After_Assessment'  AND plant_id= '".$_GET["plant_id"]."' ";
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["changeAffDoc"] = json_decode($row["changeAffDoc"]);
            
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
     
       
             
            $sql = "UPDATE `changecontrol`  SET status = 'FOR_DEPT_CONSENT_AND_REVIEW',
            engg = '".$engg."', admin = '".$admin."', production = '".$production."', ehs = '".$ehs."', qc = '".$qc."', store = '".$store."', 
            micro = '".$micro."', it = '".$it."', hr = '".$hr."', regulatory = '".$regulatory."', qa = '".$qa."', rnd = '".$rnd."', 
            revAndConBy = '".$_GET['emp_id']."', revAndConOn = '$entry_date' where id = '".$_GET['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
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


    else if($_GET['type'] == 'getCcForQaaAssessment'){ 
            $output = Array();
            
            $sql = "SELECT * FROM changecontrol WHERE  status= 'FOR_DEPT_CONSENT_AND_REVIEW' AND engg != 'Pending' AND admin != 'Pending' AND production != 'Pending' AND 
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