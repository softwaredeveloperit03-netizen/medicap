<?php 
require 'db.php';
require 'token.php';


 
//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);




function downloadPdf($c_cont,$devOccuredDept) {
 
 
          $cid = $_GET["client_no"];
        $fileName = $c_cont;
          
         
        $sms = '*WelCome To Meha QA Department , New Deviation has been Enrolled By '.$_GET['emp_id'].' In dept '.$devOccuredDept.' Please Login to meha.cpplgmp.com for more detailes and approval of deviation*';

         
        $apikey = "4d1e71a175894c17727931890d8d50850e5de95d";
        $instance = "apK9itvfIRxOZEr";
        
        // Construct the URL
     $url = 'https://app.nationalbulksms.com/api/send-media.php?number=+91' . urlencode($c_cont)
    . '&msg=' . urlencode($sms)
    . '&media=' . urlencode('https://mehapharma.com/wp-content/uploads/2024/01/MEHA_PHARMA2-1-1.png')
    . '&apikey=' . urlencode($apikey) 
    . '&instance=' . urlencode($instance);
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set the URL
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // Set options to return the result
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Execute the request
        $response = curl_exec($ch);
        
        // Check for errors
        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
        } else {
            // Success
          //  echo 'Response from API: ' . $response;
        }
        
        // Close cURL session
        curl_close($ch);
        
         
 
}






 $timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);
  
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
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
if ($_GET["type"] == "saveQmsDeviationsMeha") {
    
    
$input = $_POST;
$entry_date = date("Y-m-d H:i:s");

$department_code = "";
$sqlDC = "SELECT department_code FROM department WHERE department_name='".$input['department']."'";
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
          FROM deviation 
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
$deviation_no = "DR/".$department_code."/".$financial_year."/".$sr_no_formatted;


/* ================= FILE UPLOAD SECTION ================= */
$target_dir = "../../upload/deviation/";
$ic = 1;

$sqlMaxId = "SELECT max(id) as Key_Id FROM deviation";
$resMaxId = $conn->query($sqlMaxId);
if ($resMaxId->num_rows > 0) {
    $rowMax = $resMaxId->fetch_assoc();
    $ic = $rowMax['Key_Id'] + 1;
}

$devDetDoc = "";
if(isset($_FILES["devDetDoc"]["name"]) && $_FILES["devDetDoc"]["name"] != ""){
    $devDetDoc = $ic."devDetDoc".basename($_FILES["devDetDoc"]["name"]);
    move_uploaded_file($_FILES["devDetDoc"]["tmp_name"], $target_dir.$devDetDoc);
}

$standProceSysDoc = "";
if(isset($_FILES["standProceSysDoc"]["name"]) && $_FILES["standProceSysDoc"]["name"] != ""){
    $standProceSysDoc = $ic."standProceSysDoc".basename($_FILES["standProceSysDoc"]["name"]);
    move_uploaded_file($_FILES["standProceSysDoc"]["tmp_name"], $target_dir.$standProceSysDoc);
}


/* ================= INSERT QUERY ================= */
$sql = "INSERT INTO `deviation`(
    deviation_no, department_code, financial_year, sr_no,
    plant_id, status, identifiedBy, devOccuredDate, devOccuredDept, DeviationType,
    devIdentifiedDate, timeOfDev, typeOfDev, devScope, ScopeCode, scopeItem,
    detailsOfDev, relatedTo, relatedToOther, sourceDocument, briefInvestigation,
    reasonForDeviation, entryBy, entryDate,
    rnd,qa,regulatory,hr,it,micro,store,qc,ehs,production,admin,engg,capaData,closureData,
    devDetDoc, standProceSysDoc
) VALUES (
    '$deviation_no','$department_code','$financial_year','$next_sr',
    '".$_GET['plant_id']."','Pending','".$input['identifiedBy']."','".$input['devOccuredDate']."','".$input['devOccuredDept']."','".$input['DeviationType']."',
    '".$input['devIdentifiedDate']."','".$input['timeOfDev']."','".$input['typeOfDev']."','".$input['devScope']."','".$input['ScopeCode']."',
    '".$input['scopeItem']."','".$input['detailsOfDev']."','".$input['relatedTo']."','".$input['relatedToOther']."',
    '".$input['sourceDocument']."','".$input['briefInvestigation']."','".$input['reasonForDeviation']."',
    '".$_GET['emp_id']."','$entry_date',
    'NA','NA','NA','NA','NA','NA','NA','NA','NA','NA','NA','NA','[]','[]',
    '$devDetDoc','$standProceSysDoc'
)";

if($conn->query($sql)){
    echo json_encode([
        "status" => "success",
        "deviation_no" => $deviation_no
    ]);
}else{
    echo json_encode(["status" => $conn->error]);
}
}
    
 
    if ($_GET["type"] == "saveQmsDeviations") {
                
                    $input    = $_POST;
                    $target_dir = "../../upload/deviation/";
                    
                $ic =1;    
            $sql = "SELECT max(id) as Key_Id  FROM deviation  ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                   $ic = $row['Key_Id'] + 1;
                }
            }else{
                 $ic =1;  
            }
                    
                    $devDetDoc = "";
                    if(isset($_FILES["devDetDoc"]["name"])){
                        $target_file = $target_dir.$ic."devDetDoc".basename($_FILES["devDetDoc"]["name"]);
                        $devDetDoc = $ic."devDetDoc".basename($_FILES["devDetDoc"]["name"]);
                        move_uploaded_file($_FILES["devDetDoc"]["tmp_name"], $target_file);
                    }
                    
                    $standProceSysDoc = "";
                    if(isset($_FILES["standProceSysDoc"]["name"])){
                        $target_file = $target_dir.$ic."standProceSysDoc".basename($_FILES["standProceSysDoc"]["name"]);
                        $standProceSysDoc = $ic."standProceSysDoc".basename($_FILES["standProceSysDoc"]["name"]);
                        move_uploaded_file($_FILES["standProceSysDoc"]["tmp_name"], $target_file);
                    }
                    
                        
    $sql = "INSERT INTO `deviation`(`plant_id`, `status`, `identifiedBy`, `devOccuredDate`, `devOccuredDept`, `devIdentifiedDate`,`timeOfDev`,
    `typeOfDev`, `devScope`, `scopeItem`, `detailsOfDev`, `devDetDoc`, `standProcedureSystem`, `standProceSysDoc`, `entryBy`,`entryDate`,
    rnd,qa,regulatory,hr,it,micro,store,qc,ehs,production,admin,engg,capaData,closureData) Values('".$_GET['plant_id']."','Pending','".$input['identifiedBy']."',
    '".$input['devOccuredDate']."','".$input['devOccuredDept']."','".$input['devIdentifiedDate']."','".$input['timeOfDev']."','".$input['typeOfDev']."',
    '".$input['devScope']."','".$input['scopeItem']."','".$input['detailsOfDev']."','$devDetDoc','".$input['standProcedureSystem']."',
    '$standProceSysDoc','".$_GET['emp_id']."','$entry_date','NA','NA','NA','NA','NA','NA','NA','NA','NA','NA','NA','NA','[]','[]') ";
                
    
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
           // $c_cont = '9226077619';
            $c_cont1 = '7722000550';
            $devOccuredDept = $input['devOccuredDept'];
          
           // downloadPdf($c_cont,$devOccuredDept);
            downloadPdf($c_cont1,$devOccuredDept);
            
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
            
            
    } 
  
    else if($_GET['type'] == 'getDeviationConcernApprovalData'){ 
            $output = Array();
            $sql = "SELECT * FROM deviation WHERE devOccuredDept= '".$_GET["deptName"]."' AND status= 'Pending' AND plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    else if($_GET['type'] == 'gtQaEmployees'){ 
            $output = Array();
            $sql = "SELECT CONCAT(firstname,' ', lastname, ' ', '(', emp_id, ')') AS empName,department, emp_id,id FROM employee WHERE  department = 'Quality Assurance' AND plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    else if($_GET['type'] == 'getDeviationForQaReview'){ 
            $output = Array();
            $sql = "SELECT * FROM deviation WHERE  status= 'Inprocess' AND plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    else if($_GET['type'] == 'getDeviationFoeExternalAgency'){ 
            $output = Array();
            
            $sql = "SELECT * FROM deviation WHERE  status= 'Reviewed' AND engg != 'Pending' AND admin != 'Pending' AND production != 'Pending' AND 
            ehs != 'Pending' AND qc != 'Pending' AND store != 'Pending' AND micro != 'Pending' AND it != 'Pending' AND hr != 'Pending' AND 
            regulatory != 'Pending' AND qa != 'Pending' AND rnd != 'Pending' AND plant_id= '".$_GET["plant_id"]."'";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
            $output1 = Array();
            
            $sql1 = "SELECT * FROM deviationConsentReview WHERE ( deviationID= '".$row["deviation_no"]."' OR deviationID= '".$row["id"]."' ) 
            AND plant_id= '".$_GET["plant_id"]."'";
            
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
    else if($_GET['type'] == 'getDeviationForAssessmentByQA'){ 
            $output = Array();
            
            $sql = "SELECT * FROM deviation WHERE  status= 'To_Assessment' ";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
            $output1 = Array();
            
            $sql1 = "SELECT * FROM deviationConsentReview WHERE ( deviationID= '".$row["deviation_no"]."' OR deviationID= '".$row["id"]."' ) 
            AND plant_id= '".$_GET["plant_id"]."'";
            
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
    else if($_GET['type'] == 'getDeviationForQaHeadApproval'){ 
        
            $output = Array();
            
            $sql = "SELECT * FROM deviation WHERE  status= 'Closure_Approval_QA_Head' ";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
            $output1 = Array();
            
            $sql1 = "SELECT * FROM deviationConsentReview WHERE ( deviationID= '".$row["deviation_no"]."' OR deviationID= '".$row["id"]."' ) 
            AND plant_id= '".$_GET["plant_id"]."'";
            
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
    else if($_GET['type'] == 'getDeviationForCapa'){ 
            $output = Array();
            
            $sql = "SELECT * FROM deviation WHERE  status= 'TO_CAPA'  AND  plant_id= '".$_GET["plant_id"]."'";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
            $output1 = Array();
            
            $sql1 = "SELECT * FROM deviationConsentReview WHERE ( deviationID= '".$row["deviation_no"]."' OR deviationID= '".$row["id"]."' ) 
            AND plant_id= '".$_GET["plant_id"]."'";
            
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
    else if($_GET['type'] == 'getDeviatiogetDeviationForConsernHoadAfterCapa'){ 
            $output = Array();
            
            $sql = "SELECT * FROM deviation WHERE  status= 'CONSERN_HOD_AFTER_CAPA'  AND  plant_id= '".$_GET["plant_id"]."'
            AND  devOccuredDept= '".$_GET["deptName"]."'";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
            $output1 = Array();
            
            $sql1 = "SELECT * FROM deviationConsentReview WHERE ( deviationID= '".$row["deviation_no"]."' OR deviationID= '".$row["id"]."' ) 
            AND plant_id= '".$_GET["plant_id"]."'";
            
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                     $output1[] = $row1;
                }
            }
                    $row['deptReview'] = $output1;
                    $row['capaData'] = json_decode($row['capaData']);
                     $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    else if($_GET['type'] == 'getDeviatiogetDeviationForQareviewAfterCapa'){ 
            $output = Array();
            
            $sql = "SELECT * FROM deviation WHERE  status= 'TO_QA_REVIEW_ON_CAPA'  AND  plant_id= '".$_GET["plant_id"]."'";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
            $output1 = Array();
            
            $sql1 = "SELECT * FROM deviationConsentReview WHERE ( deviationID= '".$row["deviation_no"]."' OR deviationID= '".$row["id"]."' ) 
            AND plant_id= '".$_GET["plant_id"]."'";
            
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                     $output1[] = $row1;
                }
            }
                    $row['deptReview'] = $output1;
                    $row['capaData'] = json_decode($row['capaData']);
                     $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    else if($_GET['type'] == 'getDeviationForMonitoringFollowUp'){ 
            $output = Array();
            
            $sql = "SELECT * FROM deviation WHERE  status= 'FOR_FOLLOWUP_AND_CLOSURE'  AND  plant_id= '".$_GET["plant_id"]."'";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
            $output1 = Array();
            
            $sql1 = "SELECT * FROM deviationConsentReview WHERE ( deviationID= '".$row["deviation_no"]."' OR deviationID= '".$row["id"]."' ) 
            AND plant_id= '".$_GET["plant_id"]."'";
            
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                     $output1[] = $row1;
                }
            }
                    $row['deptReview'] = $output1;
                    $row['capaData'] = json_decode($row['capaData']);
                     $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    else if($_GET['type'] == 'getDeviationFOrClosure'){ 
            $output = Array();
            
            $sql = "SELECT * FROM deviation WHERE  status= 'FOR_CLOSURE'  AND  plant_id= '".$_GET["plant_id"]."'";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
            $output1 = Array();
            
            $sql1 = "SELECT * FROM deviationConsentReview WHERE ( deviationID= '".$row["deviation_no"]."' OR deviationID= '".$row["id"]."' ) 
            AND plant_id= '".$_GET["plant_id"]."'";
            
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                     $output1[] = $row1;
                }
            }
                    $row['deptReview'] = $output1;
                    $row['capaData'] = json_decode($row['capaData']);
                    $row['closureData'] = json_decode($row['closureData']);
                     $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    else if($_GET['type'] == 'getDeviationLog'){ 
            $output = Array();
            
            $sql = "SELECT * FROM deviation WHERE     plant_id= '".$_GET["plant_id"]."'";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
            $output1 = Array();
            
            $sql1 = "SELECT * FROM deviationConsentReview WHERE ( deviationID= '".$row["deviation_no"]."' OR deviationID= '".$row["id"]."' ) 
            AND plant_id= '".$_GET["plant_id"]."'";
            
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                     $output1[] = $row1;
                }
            }
                    $row['deptReview'] = $output1;
                    $row['capaData'] = json_decode($row['capaData']);
                    $row['closureData'] = json_decode($row['closureData']);
                     $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    else if($_GET['type'] == 'getDeviationForConsentAndReview'){ 
            $output = Array();
            $sql = "SELECT * FROM deviation WHERE  status= 'Reviewed' AND plant_id= '".$_GET["plant_id"]."'";
            
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
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
    else if($_GET['type'] == 'saveConcernHodCOmmentMeha'){ 
        $input    = $_POST;
        $target_dir = "../../upload/deviation/";
                 $ic = $input['id'];  
            $immActtionnDoc = "";
                    if(isset($_FILES["immActtionnDoc"]["name"])){
                        $target_file = $target_dir.$ic."immActtionnDoc".basename($_FILES["immActtionnDoc"]["name"]);
                        $immActtionnDoc = $ic."immActtionnDoc".basename($_FILES["immActtionnDoc"]["name"]);
                        move_uploaded_file($_FILES["immActtionnDoc"]["tmp_name"], $target_file);
                    }
            $sql = "UPDATE `deviation`  SET tcd = '".$input['tcd']."', ProCorrAct = '".$input['ProCorrAct']."', ImmCerrAct = '".$input['ImmCerrAct']."',status = 'Inprocess', 
            approveByHod = '".$_GET['emp_id']."',
            approveDateHod = '$entry_date'
            where id = '".$input['id']."' "; 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

    else if($_GET['type'] == 'saveConcernHodCOmment'){ 
        
        $input    = $_POST;
        $target_dir = "../../upload/deviation/";
        
                    
         
                 $ic = $input['id'];  
            
                 
                    
            $immActtionnDoc = "";
                    if(isset($_FILES["immActtionnDoc"]["name"])){
                        $target_file = $target_dir.$ic."immActtionnDoc".basename($_FILES["immActtionnDoc"]["name"]);
                        $immActtionnDoc = $ic."immActtionnDoc".basename($_FILES["immActtionnDoc"]["name"]);
                        move_uploaded_file($_FILES["immActtionnDoc"]["tmp_name"], $target_file);
                    }
                    
             
            $sql = "UPDATE `deviation`  SET concernHodComment = '".$input['concernHodComment']."', detbyHod = '".$input['detbyHod']."',
            needToVerify = '".$input['needToVerify']."',berifJustification = '".$input['berifJustification']."',
            justForDeviation = '".$input['justForDeviation']."',status = 'Inprocess', approveByHod = '".$_GET['emp_id']."',
            approveDateHod = '$entry_date',immActtionnDoc = '$immActtionnDoc',descOfImmAction = '".$input['descOfImmAction']."'
            where id = '".$input['id']."' "; 
    
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if($_GET['type'] == 'saveDeptConcentAndReviewMeha'){ 
        
        $input    = $_POST;
        $target_dir = "../../upload/deviation/";
       $ic = $input['id'];  
       $deptName = $input['deptName'];  
            $consentRevDoc = "";
                    if(isset($_FILES["consentRevDoc"]["name"])){
                        $target_file = $target_dir.$ic.$deptName."CRDoc".basename($_FILES["consentRevDoc"]["name"]);
                        $consentRevDoc = $ic.$deptName."CRDoc".basename($_FILES["consentRevDoc"]["name"]);
                        move_uploaded_file($_FILES["consentRevDoc"]["tmp_name"], $target_file);
                    }
        $sql = "INSERT INTO `deviationConsentReview`(`plant_id`, `deviationNo`, `deviationID`, `department`, `consentAndReview`, `consentRevDoc`,
        `reviewBy`, `reviewOn`)  Values('".$_GET['plant_id']."','".$input['deviationNo']."','".$input['deviationID']."','".$input['deptName']."',
        '".$input['consentAndReview']."','$consentRevDoc','".$_GET['emp_id']."','$entry_date') ";
    
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
             $sql1 ="";
            if ($input["deptName"] == 'Engineering') {
                
                $sql1 ="UPDATE `deviation`  SET engg = 'Done' where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Admin') {
                
                $sql1 ="UPDATE `deviation`  SET admin = 'Done' where id = '".$input['id']."' ";   }

            else if($input["deptName"] == 'Production') {
                
                $sql1 ="UPDATE `deviation`  SET production = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'EHS') {
                
                $sql1 ="UPDATE `deviation`  SET ehs = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Quality Control') {
                
                $sql1 .="UPDATE `deviation`  SET qc = 'Done'   where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Store') {
                
                $sql1 ="UPDATE `deviation`  SET store = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Microbiology') {
                
                $sql1 ="UPDATE `deviation`  SET micro = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'IT') {
                
                $sql1 ="UPDATE `deviation`  SET it = 'Done'   where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Human Resource') {
                
                $sql1 ="UPDATE `deviation`  SET hr = 'Done'   where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Regulatory') {
                
                $sql1 ="UPDATE `deviation`  SET regulatory = 'Done' where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Quality Assurance') {
                
                $sql1 ="UPDATE `deviation`  SET qa = 'Done'   where id = '".$input['id']."' ";   }

            else if($input["deptName"] == 'R AND D') {
                
                $sql1 ="UPDATE `deviation`  SET rnd = 'Done' where id = '".$input['id']."' ";      } 
            $conn->query($sql1);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }

    else if($_GET['type'] == 'saveDeptConcentAndReview'){ 
        
        $input    = $_POST;
        $target_dir = "../../upload/deviation/";
        
                    
       $ic = $input['id'];  
       $deptName = $input['deptName'];  
                  
                    
            $consentRevDoc = "";
                    if(isset($_FILES["consentRevDoc"]["name"])){
                        $target_file = $target_dir.$ic.$deptName."CRDoc".basename($_FILES["consentRevDoc"]["name"]);
                        $consentRevDoc = $ic.$deptName."CRDoc".basename($_FILES["consentRevDoc"]["name"]);
                        move_uploaded_file($_FILES["consentRevDoc"]["tmp_name"], $target_file);
                    }
                    
        $sql = "INSERT INTO `deviationConsentReview`(`plant_id`, `deviationNo`, `deviationID`, `department`, `consentAndReview`, `consentRevDoc`,
        `reviewBy`, `reviewOn`)  Values('".$_GET['plant_id']."','".$input['deviationNo']."','".$input['deviationID']."','".$input['deptName']."',
        '".$input['consentAndReview']."','$consentRevDoc','".$_GET['emp_id']."','$entry_date') ";
        
    
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
             $sql1 ="";
            if ($input["deptName"] == 'Engineering') {
                
                $sql1 ="UPDATE `deviation`  SET engg = 'Done' where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Admin') {
                
                $sql1 ="UPDATE `deviation`  SET admin = 'Done' where id = '".$input['id']."' ";   }

            else if($input["deptName"] == 'Production') {
                
                $sql1 ="UPDATE `deviation`  SET production = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'EHS') {
                
                $sql1 ="UPDATE `deviation`  SET ehs = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Quality Control') {
                
                $sql1 .="UPDATE `deviation`  SET qc = 'Done'   where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Store') {
                
                $sql1 ="UPDATE `deviation`  SET store = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'Microbiology') {
                
                $sql1 ="UPDATE `deviation`  SET micro = 'Done' where id = '".$input['id']."' ";  }

            else if($input["deptName"] == 'IT') {
                
                $sql1 ="UPDATE `deviation`  SET it = 'Done'   where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Human Resource') {
                
                $sql1 ="UPDATE `deviation`  SET hr = 'Done'   where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Regulatory') {
                
                $sql1 ="UPDATE `deviation`  SET regulatory = 'Done' where id = '".$input['id']."' "; }

            else if($input["deptName"] == 'Quality Assurance') {
                
                $sql1 ="UPDATE `deviation`  SET qa = 'Done'   where id = '".$input['id']."' ";   }

            else if($input["deptName"] == 'R AND D') {
                
                $sql1 ="UPDATE `deviation`  SET rnd = 'Done' where id = '".$input['id']."' ";      } 

            
            $conn->query($sql1);
            
            
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
        else if($_GET['type'] == 'saveQaReviewMeha'){ 
        
        
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
     
       
             
            $sql = "UPDATE `deviation`  SET reoccurrence = '".$input['reoccurrence']."',  
                    selectedDeviation = '".$input['selectedDeviation']."',  
                    recurrenceDetails = '".$input['recurrenceDetails']."',  
                    detailedInvestigationRequired = '".$input['detailedInvestigationRequired']."',  
                    qaReview = '".$input['qaReview']."',  
                    deviationImpact = '".$input['deviationImpact']."',  
                    changeControlRequired = '".$input['changeControlRequired']."',  
                    riskAssessmentRequired = '".$input['riskAssessmentRequired']."',  
                    processValidationRequired = '".$input['processValidationRequired']."',  
                    cleaningValidationRequired = '".$input['cleaningValidationRequired']."',  
                    stabilityStudyRequired = '".$input['stabilityStudyRequired']."',  
                    capaRequired = '".$input['capaRequired']."',  
                    otherImpactDetails = '".$input['otherImpactDetails']."',  
                    classification = '".$input['classification']."'  ,
                    engg = '".$engg."', admin = '".$admin."', production = '".$production."', ehs = '".$ehs."', qc = '".$qc."', store = '".$store."', 
                    micro = '".$micro."', it = '".$it."', hr = '".$hr."', regulatory = '".$regulatory."', qa = '".$qa."', rnd = '".$rnd."', 
                    qaReviewedBy = '".$_GET['emp_id']."', qaReviewedOn = '$entry_date' ,status = 'Reviewed' where id = '".$input['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
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
     
       
             
            $sql = "UPDATE `deviation`  SET reviewOfQA = '".$input['reviewOfQA']."', otherDetetails = '".$input['otherDetetails']."',
            engg = '".$engg."', admin = '".$admin."', production = '".$production."', ehs = '".$ehs."', qc = '".$qc."', store = '".$store."', 
            micro = '".$micro."', it = '".$it."', hr = '".$hr."', regulatory = '".$regulatory."', qa = '".$qa."', rnd = '".$rnd."', 
            qaReviewedBy = '".$_GET['emp_id']."', qaReviewedOn = '$entry_date' ,status = 'Reviewed' where id = '".$input['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if($_GET['type'] == 'saveExternalAnegcyCommentMeha'){ 
            $sql = "UPDATE `deviation`  SET deviationApproval = '".$input['deviationApproval']."', 
                                            commentRejection = '".$input['commentRejection']."',
                                            RecommendedCapa = '".$input['RecommendedCapa']."' ,
            externalAgencyBy = '".$_GET['emp_id']."', externalAgencyOn = '$entry_date' ,status = 'To_Assessment' where id = '".$input['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

    else if($_GET['type'] == 'saveExternalAnegcyComment'){ 
        
         
             
            $sql = "UPDATE `deviation`  SET externalRevAndApproval = '".$input['externalRevAndApproval']."', 
            commentByExternalAgency = '".$input['commentByExternalAgency']."',approvalByExternalAgency = '".$input['approvalByExternalAgency']."' ,
            externalAgencyBy = '".$_GET['emp_id']."', externalAgencyOn = '$entry_date' ,status = 'To_Assessment' where id = '".$input['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if($_GET['type'] == 'saveDeviationAsessmentByQaMeha'){ 
        $sql = "UPDATE `deviation`  SET capaImplemented = '".$input['capaImplemented']."' ,
                                        devStatus = '".$input['newDocument']."',
                                        documentRevised = '".$input['documentRevised']."' ,
                                        trainingImparted = '".$input['trainingImparted']."' ,
                                        documentsAttached = '".$input['documentsAttached']."' ,
                                        capaEffective = '".$input['capaEffective']."' ,
                                        otherCapa = '".$input['otherCapa']."' ,
                                        ClosureComment = '".$input['ClosureComment']."' 
        ,qaAssessmentBy = '".$_GET['emp_id']."',
        qaAssessmentOn = '$entry_date' ,status = 'Closure_Approval_QA_Head' where id = '".$input['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

    else if($_GET['type'] == 'saveDeviationAsessmentByQA'){ 
         
        $sql = "UPDATE `deviation`  SET evaluHistoryOfDev = '".$input['evaluHistoryOfDev']."', refDevNo = '".$input['refDevNo']."' ,
        devStatus = '".$input['devStatus']."',classificationOfDevi = '".$input['classificationOfDevi']."' ,
        investigationReq = '".$input['investigationReq']."' ,impactAssessmentReq = '".$input['impactAssessmentReq']."' ,
        riskAssessmentReq = '".$input['riskAssessmentReq']."' ,approvalRecomm = '".$input['approvalRecomm']."' ,
        rejectionJustification = '".$input['rejectionJustification']."' ,commentByQaAssessment = '".$input['commentByQaAssessment']."' ,
        targetDataOfCompletion = '".$input['targetDataOfCompletion']."' ,qaAssessmentBy = '".$_GET['emp_id']."',
        qaAssessmentOn = '$entry_date' ,status = 'Assessment_Approval_QA_Head' where id = '".$input['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if($_GET['type'] == 'saveDeviationApprovalByQaMeha'){ 
         
    $sql = "UPDATE `deviation` SET 
    commentByQaHeadApproval = '".$input['commentByQaHeadApproval']."' ,
    deviationApprovalQaBy = '".$_GET['emp_id']."',
    deviationApprovalQaOn = '$entry_date' ,status = 'Complete' where id = '".$input['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

    else if($_GET['type'] == 'saveDeviationApprovalByQa'){ 
         
    $sql = "UPDATE `deviation` SET qaHeadApproval = '".$input['qaHeadApproval']."' ,commentByQaHeadApproval = '".$input['commentByQaHeadApproval']."' ,
    rejectionJustificationByQaHeadApproval = '".$input['rejectionJustificationByQaHeadApproval']."' ,deviationApprovalQaBy = '".$_GET['emp_id']."',
    deviationApprovalQaOn = '$entry_date' ,status = 'TO_CAPA' where id = '".$input['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET['type'] == 'saveDeviationcapa'){ 
         
    $sql = "UPDATE `deviation` SET rootCauseIdentified = '".$input['rootCauseIdentified']."' ,toolUsedToIdentified = '".$input['toolUsedToIdentified']."' ,
    nameOfTool = '".$input['nameOfTool']."' ,berifDetailsOfRootCause = '".$input['berifDetailsOfRootCause']."' ,capaRequired = '".$input['capaRequired']."' ,
    capaData = '".json_encode($input['capaData'])."' ,capaBy = '".$_GET['emp_id']."',
    capaOn = '$entry_date' ,status = 'CONSERN_HOD_AFTER_CAPA' where id = '".$input['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
        $sql = "INSERT INTO capa (plant_id,department, origin, document_no, document_file, plan, initiate_by, initiate_date) 
        VALUES ('".$_GET["plant_id"]."','".$input["devOccuredDept"]."', 'Deviations', '".$input["deviation_no"]."', 'NA',
        '".json_encode($input['capaData'])."', '".$_GET["emp_id"]."', '$entry_date')";
		$conn->query($sql);
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET['type'] == 'saveDeviationConsernHodCommentAfterCapa'){ 
         
    $sql = "UPDATE `deviation` SET concernHodCommentAfterCapa = '".$input['concernHodCommentAfterCapa']."' , 
    CHCAfterCapaBy = '".$_GET['emp_id']."',CHCAfterCapaOn = '$entry_date' ,status = 'TO_QA_REVIEW_ON_CAPA' 
    where id = '".$input['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET['type'] == 'saveDeviationQaReviewCapa'){ 
         
    $sql = "UPDATE `deviation` SET qaReviewOnCapa = '".$input['qaReviewOnCapa']."' , 
    qaRivewOnCapaBy = '".$_GET['emp_id']."',qaRivewOnCapaon = '$entry_date' ,status = 'FOR_FOLLOWUP_AND_CLOSURE' 
    where id = '".$input['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET['type'] == 'saveDeviationMonitoringClosure'){ 
         
    $sql = "UPDATE `deviation` SET extensionDetails = '".$input['extensionDetails']."' , closureData = '".json_encode($input['closureData'])."' ,
    monitaringBy = '".$_GET['emp_id']."',monitaringOn = '$entry_date' ,status = 'FOR_CLOSURE' 
    where id = '".$input['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET['type'] == 'saveDeviationClosure'){ 
         
    $sql = "UPDATE `deviation` SET capaImple = '".$input['capaImple']."' ,impactedDocumentRevised = '".$input['impactedDocumentRevised']."' ,
    trainingToRelevent = '".$input['trainingToRelevent']."' ,impactedProductDoc = '".$input['impactedProductDoc']."' ,
    impactedBEINo = '".$input['impactedBEINo']."' ,impactedActiProOth = '".$input['impactedActiProOth']."' ,
    othImpactedProdDoc = '".$input['othImpactedProdDoc']."' ,riskAssImpAsssStatus = '".$input['riskAssImpAsssStatus']."' ,
    postMonitoringOfAss = '".$input['postMonitoringOfAss']."' ,devCloserDate = '".$input['devCloserDate']."' ,
    closureCommentByQaHead = '".$input['closureCommentByQaHead']."' ,closureBy = '".$_GET['emp_id']."',closureOn = '$entry_date' ,
    status = 'Complete' where id = '".$input['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
     else if($_GET['type'] == 'getDeviationsPastClosure'){
  

  $plant_id = $conn->real_escape_string($plant_id);
  global $deviation_table;
  $t = $conn->real_escape_string($deviation_table);

echo  $sql = "SELECT d.id, d.deviation_no, d.devOccuredDept,
          d.devOccuredDate, d.status, d.identifiedBy, d.DeviationType,
          d.tcd AS closure_date
          FROM `$t` d
          WHERE tcd IS NOT NULL
          AND tcd < CURDATE()
          AND (d.status IS NULL OR TRIM(LOWER(d.status)) != 'complete')
          AND (d.plant_id = '$plant_id' OR '$plant_id' = '' OR d.plant_id IS NULL)
          ORDER BY d.id DESC";

  $res = $conn->query($sql);
  $out = [];
  if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
      $row['department'] = $row['devOccuredDept'] ?? '';
      if (!empty($row['closure_date'])) {
        $row['closure_date'] = date('Y-m-d', strtotime($row['closure_date']));
      }
      $out[] = $row;
    }
  }
  $conn->close();
  echo json_encode($out);
}

/**
 * Save Justification For Delay form. Creates table if not exists.
 */
 else if($_GET['type'] == 'saveDeviationClosure') {
  $raw = file_get_contents('php://input');
  $input = json_decode($raw, true);
  if (!is_array($input)) {
    $input = $_POST;
  }

  $conn = getConnection();
  if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    return;
  }

  $tbl = 'justification_for_delay';
  $create = "CREATE TABLE IF NOT EXISTS $tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deviation_no VARCHAR(100) NOT NULL,
    department VARCHAR(200) NOT NULL,
    initial_target_completion_date DATE NOT NULL,
    date_for_justification DATE NOT NULL,
    title_for_justification VARCHAR(500) NOT NULL,
    new_target_date_closure DATE NOT NULL,
    justification_text TEXT NOT NULL,
    initiated_by_sign_date VARCHAR(200) DEFAULT NULL,
    comments_by_manager_qa VARCHAR(500) DEFAULT NULL,
    department_head_sign_date VARCHAR(200) DEFAULT NULL,
    manager_qa_sign_date VARCHAR(200) DEFAULT NULL,
    reviewer_sign_date VARCHAR(200) DEFAULT NULL,
    approver_sign_date VARCHAR(200) DEFAULT NULL,
    plant_id VARCHAR(50) DEFAULT NULL,
    entry_date DATETIME DEFAULT CURRENT_TIMESTAMP
  )";
  $conn->query($create);

  $dev_no       = $conn->real_escape_string(trim($input['deviation_no'] ?? ''));
  $dept         = $conn->real_escape_string(trim($input['department'] ?? ''));
  $initial_tcd  = $conn->real_escape_string($input['initial_target_completion_date'] ?? '');
  $date_just    = $conn->real_escape_string($input['date_for_justification'] ?? '');
  $title        = $conn->real_escape_string(trim($input['title_for_justification'] ?? ''));
  $new_tcd      = $conn->real_escape_string($input['new_target_date_closure'] ?? '');
  $just_text    = $conn->real_escape_string(trim($input['justification_text'] ?? ''));
  $init_sign    = $conn->real_escape_string($input['initiated_by_sign_date'] ?? '');
  $comments_qa  = $conn->real_escape_string($input['comments_by_manager_qa'] ?? '');
  $dept_head    = $conn->real_escape_string($input['department_head_sign_date'] ?? '');
  $mgr_qa       = $conn->real_escape_string($input['manager_qa_sign_date'] ?? '');
  $reviewer     = $conn->real_escape_string($input['reviewer_sign_date'] ?? '');
  $approver     = $conn->real_escape_string($input['approver_sign_date'] ?? '');
  $plant_id     = $conn->real_escape_string($input['plant_id'] ?? '');

  $sql = "INSERT INTO $tbl (
    deviation_no, department, initial_target_completion_date, date_for_justification,
    title_for_justification, new_target_date_closure, justification_text,
    initiated_by_sign_date, comments_by_manager_qa, department_head_sign_date,
    manager_qa_sign_date, reviewer_sign_date, approver_sign_date, plant_id
  ) VALUES (
    '$dev_no','$dept','$initial_tcd','$date_just','$title','$new_tcd','$just_text',
    '$init_sign','$comments_qa','$dept_head','$mgr_qa','$reviewer','$approver','$plant_id'
  )";

  if ($conn->query($sql)) {
    echo json_encode(['status' => 'success', 'message' => 'Justification for delay saved successfully.']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Save failed: ' . $conn->error]);
  }
  $conn->close();
}
    
    
    
    
    
} else {
    echo "Invalid Token";
}

$conn->close();
?>