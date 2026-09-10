<?php




//   ini_set('display_errors', 1);
// error_reporting(E_ALL);



require 'db.php';
require 'token.php';
require './tcpdf/tcpdf.php';
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

if ($result->num_rows > 0) {
    
    while($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
        $string = explode("$",$string);
        $_GET["emp_id"] = $string[0];           
        $_GET["department"] = $string[1];
        break;
    }

    $sql = "INSERT INTO log(process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
      if ($_GET["type"] == "getExternalTrainers") {
        $sql = "SELECT * FROM externaltrainer WHERE status= 'Approved' AND  plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
            $row["skills_data"] = json_decode($row["skills_data"]);
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    }
    
    
    else if ($_GET["type"] == "getTrainers") {
        $sql = "SELECT * FROM externaltrainer WHERE status='active'";
        $result = $conn->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    } 
    
 
    
    else if ($_GET["type"] == "saveTrainingNeeds") {
        
        
        
              $trNoID = 1;
            $sql = "SELECT id FROM training_needs ORDER BY id DESC LIMIT 1";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $trNoID = $row["id"] + 1; 
            }
            $trNo = "T00" . $trNoID;
        
        
        
      
          $sql = "INSERT INTO training_needs (trNo,plant_id,department, subject, training_category,reference_document, 
            training_need , entry_by, entry_date,docToRead,selectedFileName,status,announce_status,trainer,attendance,questionaries, retraining_status)  VALUES ('$trNo','".$_GET["plant_id"]."','".$input["department_name"]."',
            '".$input["subject"]."','".$input["training_category"]."','".$input["reference_document"]."',
            '".$input["training_needs"]."','".$_GET["emp_id"]."','$entry_date','".$input["docToRead"]."','".$input["selectedFileName"]."','pending','pending','external','pending','pending','NO')";
            
        if ($conn->query($sql) === TRUE) {
            
            $training_no = $conn->insert_id;

            $data = $input["employees"];
            for ($i = 0; $i < count($data); $i++) {
                $temp = $data[$i];
                $sql1 = "INSERT INTO tn_employees (plant_id,tn_no, emp_id,in_department,attendance,exam,feedback,mapQuestion) VALUES 
                ('".$_GET["plant_id"]."',$training_no, '".$temp['emp_id']."', '".$temp['in_department']."', 'pending', 'pending', 'NA', 'pending' )";
                $conn->query($sql1);
            }
            
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if ($_GET["type"] == "saveOJTTrainingNeeds") {
        
                      $trNoID = 1;
            $sql = "SELECT id FROM training_needs ORDER BY id DESC LIMIT 1";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $trNoID = $row["id"] + 1; 
            }
            $trNo = "T00" . $trNoID;
      
         $sql = "INSERT INTO training_needs (trNo,plant_id,department, subject, training_category,reference_document, 
            training_need , entry_by, entry_date,status,announce_status,trainer,attendance,questionaries, retraining_status)  VALUES ('$trNo','".$_GET["plant_id"]."','".$input["department_name"]."',
            '".$input["subject"]."','".$input["training_category"]."','".$input["reference_document"]."',
            '".$input["training_needs"]."','".$_GET["emp_id"]."','$entry_date','pending','pending','external','pending','pending','NO')";
            
        if ($conn->query($sql) === TRUE) {
            
            $training_no = $conn->insert_id;

            $data = $input["employees"];
            for ($i = 0; $i < count($data); $i++) {
                $temp = $data[$i];
                $sql1 = "INSERT INTO tn_employees (plant_id,tn_no, emp_id,in_department,otherDet,attendance,exam,feedback,mapQuestion) VALUES 
                ('".$_GET["plant_id"]."',$training_no, '".$temp['emp_id']."', '".$temp['in_department']."','".json_encode($temp["othersDetailsData"])."', 'pending', 'pending', 'NA', 'pending')";
                $conn->query($sql1);
            }
            
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if ($_GET["type"] == "saveInductionTraining") {
         
           
         
        $admin = 'Pending';
        $qa = 'Pending';
        $qc = 'Pending';
        $production = 'Pending';
        $store = 'Pending';
        $engineering = 'Pending';
        $it = 'Pending';
        $quality_head = 'Pending';
         
        // if($input["admin"] == 'true'){  $admin = 'Pending';    } ;
        // if($input["qa"] == 'true'){  $qa = 'Pending';    } ;
        // if($input["qc"] == 'true'){  $qc = 'Pending';    } ;
        // if($input["production"] == 'true'){  $production = 'Pending';    } ;
        // if($input["store"] == 'true'){  $store = 'Pending';    } ;
        // if($input["engineering"] == 'true'){  $engineering = 'Pending';    } ;
        // if($input["it"] == 'true'){  $it = 'Pending';    } ;
        // if($input["quality_head"] == 'true'){  $quality_head = 'Pending';    } ;
        



        $sql = "SELECT  tnNo   FROM training_induction  order by id desc limit 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            // If there is a tnNo found in the database
            $row = $result->fetch_assoc();
            $tnNo = $row['tnNo'];
            $number = preg_replace('/[^0-9]/', '', $tnNo); // Extract numeric part
            
            // Increment the numeric part
            $nextNumber = $number + 1;
        } else {
            // If no tnNo is found in the database, start with 1
            $nextNumber = 1;
        }
  
        $prefix = 'T';
        $trainingNo = $prefix.$nextNumber;
        
          $jadu = false;
          
            $json_obj = json_encode($input["empData"]);
            $array = json_decode($json_obj, true);
                
        foreach ($array as $emp){
            
                   $sql = "INSERT INTO training_induction (plant_id,tnNo,emp_id,training_from,training_To, admin,qa,qc,production,store,engineering,
                  it,quality_head,entry_by,entry_on,status,quality_headChecklist,itChecklist,engineeringChecklist,storeChecklist,productionChecklist,
                  qcChecklist,qaChecklist,adminChecklist)  VALUES ('".$_GET["plant_id"]."','$trainingNo', '".$emp['emp_id']."','".$input["training_from"]."',
                  '".$input["training_To"]."','$admin','$qa','$qc','$production','$store','$engineering','$it','$quality_head','".$_GET["emp_id"]."',
                  '$entry_date','TO_HR','[]','[]','[]','[]','[]','[]','[]','[]')";
                        
                    if ($conn->query($sql) === TRUE) {
                        
                        $sql1 = "UPDATE employee SET induction_training = 'Inprocess' WHERE emp_id = '".$emp['emp_id']."'";
                        $conn->query($sql1);
                        
                        
                        $jadu = true;
                        
                    } else {
                        $jadu = false;
                    }
        }
        
                    if ($jadu) {
                        echo "{\"status\":\"success\"}";
                    } else {
                        echo "{\"status\":\"failed\"}";
                    }
        
    }
    
    
    
    else if ($_GET["type"] == "saveOJT") {
         
        $hr = 'NO';
        $qc = 'NO';
        $purchase = 'NO';
        $qa = 'NO';
        $micro = 'NO';
        $engg = 'NO';
        $store = 'NO';
        $packing = 'NO';
        $rnd = 'NO';
        $admin = 'NO';
        $regulatory = 'NO';
        $production = 'NO';
        $it = 'NO';
        $adl = 'NO';
        $management = 'NO';
        $account = 'NO';
        $fgStore = 'NO';
        $generalStore = 'NO';
        $ipqc = 'NO';
        $ehs = 'NO';
        $security = 'NO';
        $marketing = 'NO';
        $planning = 'NO';
        $logistics = 'NO';
     
        
        $string = $input["departments"];
        
        if (strpos($string, 'Logistics') !== false) {  $logistics = 'YES';  }
        if (strpos($string, 'Planning') !== false) { $planning = 'YES'; }
        if (strpos($string, 'Marketing') !== false) { $marketing = 'YES';   }
        if (strpos($string, 'Security') !== false) { $security = 'YES'; }
        if (strpos($string, 'EHS') !== false) { $ehs = 'YES'; }
        if (strpos($string, 'IPQC') !== false) { $ipqc = 'YES'; }
        if (strpos($string, 'General Store') !== false) { $generalStore = 'YES'; }
        if (strpos($string, 'FG Store') !== false) { $fgStore = 'YES'; }
        if (strpos($string, 'Accounts') !== false) { $account = 'YES'; }
        if (strpos($string, 'Management') !== false) { $management = 'YES'; }
        if (strpos($string, 'ADL') !== false) { $adl = 'YES'; }
        if (strpos($string, 'IT') !== false) { $it = 'YES';}
        if (strpos($string, 'Production') !== false) {    $production = 'YES'; }
        if (strpos($string, 'Regulatory') !== false) {    $regulatory = 'YES'; }
        if (strpos($string, 'Admin') !== false) {    $admin = 'YES';  }
        if (strpos($string, 'R AND D') !== false) {    $rnd = 'YES'; }
        if (strpos($string, 'Store') !== false) {   $store = 'YES'; }
        if (strpos($string, 'Packing') !== false) {   $packing = 'YES'; }
        if (strpos($string, 'Engineering') !== false) {   $engg = 'YES'; }
        if (strpos($string, 'Human Resource') !== false) {   $hr = 'YES';  }
        if (strpos($string, 'Quality Control') !== false) {   $qc = 'YES'; }
        if (strpos($string, 'Purchase') !== false) { $purchase = 'YES'; }
        if (strpos($string, 'Quality Assurance') !== false) { $qa = 'YES'; }
        if (strpos($string, 'Microbiology') !== false) { $micro = 'YES'; }
        
         
   
        
         
      $sql = "INSERT INTO onJonTraining(id, plant_id, emp_id, emp_dept, trainingFor, training_from, training_To, 
  hr, qc, purchase, qa, micro, engg, store, packing, rnd, admin, regulatory, production, 
  it, adl, management, account, fgStore, generalStore, ipqc, ehs, security, marketing, planning,
   logistics) VALUES (
        '".$_GET["plant_id"]."', '".$_GET['selemp_id']."','".$input["training_from"]."','".$input["training_To"]."','$admin','$qa','$qc',
        '$production','$store','$engineering','$it','$quality_head','".$_GET["emp_id"]."','$entry_date','Pending')";
            
        if ($conn->query($sql) === TRUE) {
            
            $sql1 = "UPDATE employee SET induction_training = 'Inprocess' WHERE emp_id = '".$_GET['selemp_id']."'";
            $conn->query($sql1);
            
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    
    else if ($_GET["type"] == "getInductionTrainingForDepartment") {
        
      
              $sql = "SELECT tnNo, training_from, training_To  FROM training_induction WHERE plant_id = '".$_GET["plant_id"]."' 
           AND  status != 'Drop'";
           
           
            
            
            if ($_GET["deptName"] == 'Human Resource') {  
               $sql .="AND admin = 'Pending' AND  status = 'TO_HR'     GROUP BY tnNo, training_from, training_To ORDER BY tnNo DESC ";   
                 $day = 'DAY 1';
            } 
            else if ($_GET["deptName"] == 'Quality Assurance') {  
                $sql .= " AND qa = 'Pending' AND status = 'TO_QA'   GROUP BY tnNo, training_from, training_To ORDER BY tnNo DESC ";    
                $day = 'DAY 1';
            } 
            else if ($_GET["deptName"] == 'Quality Control') { 
                $sql .= " AND qc = 'Pending' AND  status = 'TO_QC'   GROUP BY tnNo, training_from, training_To ORDER BY tnNo DESC ";   
                  $day = 'DAY 1';
            }
            
            else if ($_GET["deptName"] == 'Production') { 
                $sql .= " AND production = 'Pending' AND status = 'TO_PROD'    GROUP BY tnNo, training_from, training_To ORDER BY tnNo DESC";    
                $day = 'DAY 2';
            }
            else if ($_GET["deptName"] == 'Store') {  
                $sql .= " AND store = 'Pending' AND status = 'TO_STORE'   GROUP BY tnNo, training_from, training_To ORDER BY tnNo DESC ";    
                $day = 'DAY 2';
            }
            else if ($_GET["deptName"] == 'Engineering') {  
                $sql .= " AND engineering = 'Pending' AND status = 'TO_ENGG'   GROUP BY tnNo, training_from, training_To ORDER BY tnNo DESC ";  
                $day = 'DAY 2';
            }
            else if ($_GET["deptName"] == 'IT') {  
                $sql .= " AND it = 'Pending' AND status = 'TO_IT'   GROUP BY tnNo, training_from, training_To ORDER BY tnNo DESC ";     
                $day = 'DAY 2';
            } 
            else if ($_GET["deptName"] == 'Quality Head') {  
                $sql .= " AND quality_head = 'Pending' AND status = 'TO_QHEAD'   GROUP BY tnNo, training_from, training_To ORDER BY tnNo DESC ";    
                $day = 'DAY 2';
            }
             
 
         
        
        $result = $conn->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                 $data1 = Array();
                 $data11 = Array();
                 
                    $sql11 = "SELECT t.*, e.firstname, e.middlename, e.lastname, e.joining_date ,e.department
                    FROM training_induction t 
                    LEFT JOIN employee e ON e.emp_id = t.emp_id 
                    WHERE t.plant_id = '".$_GET["plant_id"]."' AND t.tnNo = '".$row["tnNo"]."'  AND  t.status != 'Drop' ";
                    $result11 = $conn->query($sql11);
                     if($result11->num_rows > 0) {
                        while($row11 = $result11->fetch_assoc()) {
                          $data11[] = $row11;  
                        }
                    }
                    
                    
                    $sql1 = "SELECT subject_covered FROM induction_checklist  WHERE department_name = '".$_GET["deptName"]."'";
                    $result1 = $conn->query($sql1);
                     if($result1->num_rows > 0) {
                        while($row1 = $result1->fetch_assoc()) {
                          $data1[] = $row1;  
                        }
                    }
                    
                    $row['employees'] = $data11;
                    
                    
                    $data112['department_name'] = $_GET["deptName"];
                    $data112['responsibility'] = '';
                    $data112['remark'] = '';
                    $data112['trDate'] = '';
                    $data112['toTime'] = '';
                    $data112['fromTime'] = '';
                    $data112['cheklist'] = $data1;
                    
                    $row['cheklist'] = $data112;
                    $row['day'] = $day;
                
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    }
    else if ($_GET["type"] == "getInductionTrainingSkipStatusLog") {
        
      
$sql = "SELECT 
     
             MAX(id) as id,
             MAX(tnNo) as tnNo,
              MAX(training_To) as training_To,
               MAX(training_from) as training_from,
            MAX(admin) as admin, 
            MAX(qa) as qa, 
            MAX(qc) as qc, 
            MAX(production) as production, 
            MAX(store) as store, 
            MAX(engineering) as engineering, 
            MAX(it) as it, 
            MAX(quality_head) as quality_head, 
            MAX(status) as status 
        FROM 
            training_induction 
        WHERE 
            plant_id = '".$_GET["plant_id"]."' AND 
            status != 'Complete' 
        GROUP BY 
            tnNo";
             
        $result = $conn->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                 $data1 = Array();
                 $data11 = Array();
                 
                    $sql11 = "SELECT t.*, e.firstname, e.middlename, e.lastname, e.joining_date ,e.department
                    FROM training_induction t 
                    LEFT JOIN employee e ON e.emp_id = t.emp_id 
                    WHERE t.plant_id = '".$_GET["plant_id"]."' AND t.tnNo = '".$row["tnNo"]."'  AND  t.status != 'Drop' ";
                    $result11 = $conn->query($sql11);
                     if($result11->num_rows > 0) {
                        while($row11 = $result11->fetch_assoc()) {
                          $data11[] = $row11;  
                        }
                    }
                    
            $row['employees'] = $data11;
               
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    }
    else if ($_GET["type"] == "savedeptinductionTraining") {
        
             $sql;
              
            
            if ($_GET["deptName"] == 'Human Resource') {   
                 $sql = "Update training_induction SET  admin = 'Done' ,status = 'TO_QA', adminBy = '".$_GET['emp_id']."' , adminOn = '$entry_date'  , adminChecklist = '".json_encode($input["checklist"])."'  where tnNo = '".$_GET["tnNo"]."' ";    
            } 
            else if ($_GET["deptName"] == 'Quality Assurance') {  
                $sql = "Update training_induction SET  qa = 'Done' ,status = 'TO_QC', qaBy = '".$_GET['emp_id']."' , qaOn = '$entry_date'  , qaChecklist = '".json_encode($input["checklist"])."'  where tnNo = '".$_GET["tnNo"]."'";
            } 
            else if ($_GET["deptName"] == 'Quality Control') { 
                $sql = "Update training_induction SET  qc = 'Done' ,status = 'TO_PROD', qcBy = '".$_GET['emp_id']."' , qcOn = '$entry_date'  , qcChecklist = '".json_encode($input["checklist"])."'  where tnNo = '".$_GET["tnNo"]."'";
            }
            else if ($_GET["deptName"] == 'Production') { 
                $sql = "Update training_induction SET  production = 'Done' ,status = 'TO_STORE', prodBy = '".$_GET['emp_id']."' , prodOn = '$entry_date'  , productionChecklist = '".json_encode($input["checklist"])."'  where tnNo = '".$_GET["tnNo"]."'";
            }
            else if ($_GET["deptName"] == 'Store') {  
                $sql = "Update training_induction SET  store = 'Done' ,status = 'TO_ENGG', storeBy = '".$_GET['emp_id']."' , storeOn = '$entry_date'  , storeChecklist = '".json_encode($input["checklist"])."'  where tnNo = '".$_GET["tnNo"]."'";
            }
            else if ($_GET["deptName"] == 'Engineering') {  
                $sql = "Update training_induction SET  engineering = 'Done' ,status = 'TO_IT', enggBy = '".$_GET['emp_id']."'  , enggOn = '$entry_date' , engineeringChecklist = '".json_encode($input["checklist"])."'  where tnNo = '".$_GET["tnNo"]."'";
            }
            else if ($_GET["deptName"] == 'IT') {  
                $sql = "Update training_induction SET  it = 'Done'  ,status = 'TO_QHEAD', itBy = '".$_GET['emp_id']."' , itOn = '$entry_date'  , itChecklist = '".json_encode($input["checklist"])."'  where tnNo = '".$_GET["tnNo"]."'";
            } 
            else if ($_GET["deptName"] == 'Quality Head') {  
                $sql = "Update training_induction SET  quality_head = 'Done'  ,status = 'Pending', qhBy = '".$_GET['emp_id']."'  , qhOn = '$entry_date'   , quality_headChecklist = '".json_encode($input["checklist"])."'  where tnNo = '".$_GET["tnNo"]."'";
            }
         
         
        
          if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
            
            
            $sql1 = "SELECT * FROM training_induction   WHERE  admin = 'Done' AND qa = 'Done' AND qc = 'Done' AND production = 'Done' 
            AND store = 'Done' AND engineering = 'Done' AND it = 'Done' AND quality_head = 'Done'  AND  tnNo = '".$_GET["tnNo"]."'";
            
            $result1 = $conn->query($sql1);
            if($result1->num_rows > 0) {
                
                $sql11 = "Update training_induction SET  status = 'Pending'    where tnNo = '".$_GET["tnNo"]."'";
                $conn->query($sql11);
             
            }
            
             
            
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if ($_GET["type"] == "skipTrainingDept") {
        
             $sql;
            
            if ($_GET["deptName"] == 'admin') {   
                 $sql = "Update training_induction SET  admin = 'Pending1' ,status = '".$_GET['status']."'   where tnNo = '".$_GET["tnNo"]."' ";    
            } 
            else if ($_GET["deptName"] == 'qa') {  
                $sql = "Update training_induction SET  qa = 'Pending1' ,status = '".$_GET['status']."'   where tnNo = '".$_GET["tnNo"]."'";
            } 
            else if ($_GET["deptName"] == 'qc') { 
                $sql = "Update training_induction SET  qc = 'Pending1' ,status = '".$_GET['status']."'   where tnNo = '".$_GET["tnNo"]."'";
            }
            else if ($_GET["deptName"] == 'production') { 
                $sql = "Update training_induction SET  production = 'Pending1' ,status = '".$_GET['status']."'    where tnNo = '".$_GET["tnNo"]."'";
            }
            else if ($_GET["deptName"] == 'store') {  
                $sql = "Update training_induction SET  store = 'Pending1' ,status = '".$_GET['status']."'     where tnNo = '".$_GET["tnNo"]."'";
            }
            else if ($_GET["deptName"] == 'engineering') {  
                $sql = "Update training_induction SET  engineering = 'Pending1' ,status = '".$_GET['status']."'      where tnNo = '".$_GET["tnNo"]."'";
            }
            else if ($_GET["deptName"] == 'it') {  
                $sql = "Update training_induction SET  it = 'Pending1'  ,status = '".$_GET['status']."'     where tnNo = '".$_GET["tnNo"]."'";
            } 
            else if ($_GET["deptName"] == 'quality_head') {  
                $sql = "Update training_induction SET  quality_head = 'Pending1'  ,status = '".$_GET['status']."'   where tnNo = '".$_GET["tnNo"]."'";
            }
         
         
        
          if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if ($_GET["type"] == "performTrainingDept") {
        
             $sql;
            
            if ($_GET["deptName"] == 'admin') {   
                 $sql = "Update training_induction SET  admin = 'Pending' ,status = '".$_GET['status']."'   where tnNo = '".$_GET["tnNo"]."' ";    
            } 
            else if ($_GET["deptName"] == 'qa') {  
                $sql = "Update training_induction SET  qa = 'Pending' ,status = '".$_GET['status']."'   where tnNo = '".$_GET["tnNo"]."'";
            } 
            else if ($_GET["deptName"] == 'qc') { 
                $sql = "Update training_induction SET  qc = 'Pending' ,status = '".$_GET['status']."'   where tnNo = '".$_GET["tnNo"]."'";
            }
            else if ($_GET["deptName"] == 'production') { 
                $sql = "Update training_induction SET  production = 'Pending' ,status = '".$_GET['status']."'    where tnNo = '".$_GET["tnNo"]."'";
            }
            else if ($_GET["deptName"] == 'store') {  
                $sql = "Update training_induction SET  store = 'Pending' ,status = '".$_GET['status']."'     where tnNo = '".$_GET["tnNo"]."'";
            }
            else if ($_GET["deptName"] == 'engineering') {  
                $sql = "Update training_induction SET  engineering = 'Pending' ,status = '".$_GET['status']."'      where tnNo = '".$_GET["tnNo"]."'";
            }
            else if ($_GET["deptName"] == 'it') {  
                $sql = "Update training_induction SET  it = 'Pending'  ,status = '".$_GET['status']."'     where tnNo = '".$_GET["tnNo"]."'";
            } 
            else if ($_GET["deptName"] == 'quality_head') {  
                $sql = "Update training_induction SET  quality_head = 'Pending'  ,status = '".$_GET['status']."'   where tnNo = '".$_GET["tnNo"]."'";
            }
         
         
        
          if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if ($_GET["type"] == "getTrainingCordinator") {
        
            
        
         $data1 = Array();
        
             $sql1 = "SELECT id,emp_level,emp_id,firstname,lastname FROM employee   WHERE ( emp_level = 'Level 2' OR emp_level = 'Level 1' ) 
            AND  department = '".$_GET["deptName"]."'";
            $result1 = $conn->query($sql1);
             if($result1->num_rows > 0) {
                while($row1 = $result1->fetch_assoc()) {
             
                   $data1[] = $row1;  
                }
            }
                    
            
        echo json_encode($data1);
    }
    
    
    else if ($_GET["type"] == "getInductionTrainingLog") {
        
            
        
         $data1 = Array();
        
             $sql1 = "SELECT t.*, e.firstname, e.middlename, e.lastname, e.joining_date ,e.department
                    FROM training_induction t 
                    LEFT JOIN employee e ON e.emp_id = t.emp_id     WHERE t.status != 'Drop' AND t.plant_id = '".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
             if($result1->num_rows > 0) {
                while($row = $result1->fetch_assoc()) {
                    $row['quality_headChecklist'] = json_decode($row['quality_headChecklist']);
                    $row['itChecklist'] = json_decode($row['itChecklist']);
                    $row['engineeringChecklist'] = json_decode($row['engineeringChecklist']);
                    $row['storeChecklist'] = json_decode($row['storeChecklist']);
                    $row['productionChecklist'] = json_decode($row['productionChecklist']  , true);
                    $row['qcChecklist'] = json_decode($row['qcChecklist']);
                    $row['qaChecklist'] = json_decode($row['qaChecklist']);
                     $row['adminChecklist'] = json_decode($row['adminChecklist']);
                  $data1[] = $row;  
                }
            }
                    
            
        echo json_encode($data1);
    }
    
    


    
    
    else if ($_GET["type"] == "getInductionTrainingForempReport") {
        
         $data1 = Array();
        
               $sql1 = "SELECT t.*, e.firstname, e.middlename, e.lastname, e.joining_date ,e.department
                    FROM training_induction t LEFT JOIN employee e ON e.emp_id = t.emp_id    WHERE
                      t.quality_head != 'Pending' AND t.it != 'Pending' AND t.engineering != 'Pending' AND t.store != 'Pending'
                    AND t.production != 'Pending' AND t.qc != 'Pending' AND t.qa != 'Pending' AND t.admin != 'Pending' 
                    AND t.emp_id = '".$_GET["loger_id"]."' AND  t.plant_id = '".$_GET["plant_id"]."' AND t.status = 'Pending'";
                    
            $result1 = $conn->query($sql1);
             if($result1->num_rows > 0) {
                while($row = $result1->fetch_assoc()) {
                    $row['quality_headChecklist'] = json_decode($row['quality_headChecklist']);
                    $row['itChecklist'] = json_decode($row['itChecklist']);
                    $row['engineeringChecklist'] = json_decode($row['engineeringChecklist']);
                    $row['storeChecklist'] = json_decode($row['storeChecklist']);
                    $row['productionChecklist'] = json_decode($row['productionChecklist']);
                    $row['qcChecklist'] = json_decode($row['qcChecklist']);
                    $row['qaChecklist'] = json_decode($row['qaChecklist']);
                    $row['adminChecklist'] = json_decode($row['adminChecklist']);
                    $row['day'] = 'Day 3';
                    
                  $data1[] = $row;  
                }
            }
            
        echo json_encode($data1);
    }
    else if ($_GET["type"] == "inductiontrainingReportForDeptHead") {
        
         $data1 = Array();
        
             $sql1 = "SELECT t.*, e.firstname, e.middlename, e.lastname, e.joining_date ,e.department
                    FROM training_induction t 
                    LEFT JOIN employee e ON e.emp_id = t.emp_id    WHERE e.department = '".$_GET["deptName"]."' AND t.status = 'TO_DEPT_HEAD' AND  t.plant_id = '".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
             if($result1->num_rows > 0) {
                while($row = $result1->fetch_assoc()) {
                    $row['quality_headChecklist'] = json_decode($row['quality_headChecklist']);
                    $row['itChecklist'] = json_decode($row['itChecklist']);
                    $row['engineeringChecklist'] = json_decode($row['engineeringChecklist']);
                    $row['storeChecklist'] = json_decode($row['storeChecklist']);
                    $row['productionChecklist'] = json_decode($row['productionChecklist']);
                    $row['qcChecklist'] = json_decode($row['qcChecklist']);
                    $row['qaChecklist'] = json_decode($row['qaChecklist']);
                     $row['adminChecklist'] = json_decode($row['adminChecklist']);
                     $row['day'] = 'Day 3';
                  $data1[] = $row;  
                }
            }
            
        echo json_encode($data1);
    }
    else if ($_GET["type"] == "inductionforhrreview") {
        
         $data1 = Array();
        
             $sql1 = "SELECT t.*, e.firstname, e.middlename, e.lastname, e.joining_date ,e.department
                    FROM training_induction t 
                    LEFT JOIN employee e ON e.emp_id = t.emp_id    WHERE   t.status = 'TO_ADMIN_PERSONNEL' AND  t.plant_id = '".$_GET["plant_id"]."'
                     AND  t.status != 'Drop' ";
            $result1 = $conn->query($sql1);
             if($result1->num_rows > 0) {
                while($row = $result1->fetch_assoc()) {
                    $row['quality_headChecklist'] = json_decode($row['quality_headChecklist']);
                    $row['itChecklist'] = json_decode($row['itChecklist']);
                    $row['engineeringChecklist'] = json_decode($row['engineeringChecklist']);
                    $row['storeChecklist'] = json_decode($row['storeChecklist']);
                    $row['productionChecklist'] = json_decode($row['productionChecklist']);
                    $row['qcChecklist'] = json_decode($row['qcChecklist']);
                    $row['qaChecklist'] = json_decode($row['qaChecklist']);
                     $row['adminChecklist'] = json_decode($row['adminChecklist']);
                     $row['day'] = 'Day 3';
                  $data1[] = $row;  
                }
            }
            
        echo json_encode($data1);
    }
    else if ($_GET["type"] == "inductionforQaReview") {
        
         $data1 = Array();
        
             $sql1 = "SELECT t.*, e.firstname, e.middlename, e.lastname, e.joining_date ,e.department
                    FROM training_induction t 
                    LEFT JOIN employee e ON e.emp_id = t.emp_id    WHERE   t.status = 'TO_QA_Manager' AND  t.plant_id = '".$_GET["plant_id"]."'
                    AND  t.status != 'Drop'";
            $result1 = $conn->query($sql1);
             if($result1->num_rows > 0) {
                while($row = $result1->fetch_assoc()) {
                    $row['quality_headChecklist'] = json_decode($row['quality_headChecklist']);
                    $row['itChecklist'] = json_decode($row['itChecklist']);
                    $row['engineeringChecklist'] = json_decode($row['engineeringChecklist']);
                    $row['storeChecklist'] = json_decode($row['storeChecklist']);
                    $row['productionChecklist'] = json_decode($row['productionChecklist']);
                    $row['qcChecklist'] = json_decode($row['qcChecklist']);
                    $row['qaChecklist'] = json_decode($row['qaChecklist']);
                     $row['adminChecklist'] = json_decode($row['adminChecklist']);
                     $row['day'] = 'Day 3';
                  $data1[] = $row;  
                }
            }
            
        echo json_encode($data1);
    }
    
    else if ($_GET["type"] == "dropFormInduction") {
         
      $sql1 = "UPDATE training_induction SET status = 'Drop'   WHERE id = '".$_GET['id']."'";
            
        if ($conn->query($sql1)) { 
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if ($_GET["type"] == "saveInductionTrainingEMpReport") {
         
      $sql1 = "UPDATE training_induction SET status = 'TO_DEPT_HEAD' ,emp_report = '".$input["emp_report"]."' ,
      empRepBy = '".$_GET["emp_id"]."' , empRepOn = '$entry_date'   WHERE id = '".$_GET['id']."'";
            
        if ($conn->query($sql1)) {
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if ($_GET["type"] == "saveLevelFrequency") {
         
         $sql1 = "insert into levelFrequency (plant_id,emp_level,frequency,entryBy,entryOn) values(
        '".$_GET["plant_id"]."','".$input['emp_level']."','".$input['frequency']."','".$_GET['emp_id']."','$entry_date')";
            
        if ($conn->query($sql1)) {
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    
    else if ($_GET["type"] == "getfrequencyLevel") {
        
         $data1 = Array();
        
            $sql1 = "SELECT * FROM levelFrequency WHERE plant_id = '".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
            if($result1->num_rows > 0) {
                while($row = $result1->fetch_assoc()) {
                  $data1[] = $row;  
                }
            }
            
        echo json_encode($data1);
    }
    else if ($_GET["type"] == "getEmployeeByLevel") {
        
         $data1 = Array();
        
             $sql1 = "SELECT e.emp_id,e.department ,e.lastname ,e.middlename ,e.firstname,e.emp_level ,l.frequency,e.designation  FROM employee e
            left join levelFrequency l ON l.emp_level = e.emp_level  WHERE e.plant_id = '".$_GET["plant_id"]."'
            AND e.department = '".$_GET["deptmt101"]."' AND e.emp_level = '".$_GET["level"]."'";
            $result1 = $conn->query($sql1);
            if($result1->num_rows > 0) {
                while($row = $result1->fetch_assoc()) {
                     
                  $data1[] = $row;  
                }
            }
            
        echo json_encode($data1);
    }

     else if($_GET["type"]=="generateGmpSchedule") {
          
          
             $subject = $input["subject"];
            $department_name = $input["department_name"];
            $emp_level = $input["emp_level"];
            $frequency = $input["frequency"];
            $training_category = $input["training_category"];
       
            $date = $input["lastDateOfTraning"];
            
            
                if($frequency == '1'){ $ind = 24; }
                else if($frequency == '2'){ $ind = 12; }
                else if($frequency == '3'){ $ind = 8; }
                else if($frequency == '4'){ $ind = 6; }
                else if($frequency == '5'){ $ind = 5; }
                else if($frequency == '6'){ $ind = 4;  }
                else if($frequency == '7'){ $ind = 4;  }
                else if($frequency == '8'){ $ind = 3;  }
                else if($frequency == '9'){ $ind = 3;  }
                else if($frequency == '10'){ $ind = 2; }
                else if($frequency == '11'){ $ind = 2; }
                else if($frequency == '12'){ $ind = 2; }
            
            
            $status = 0;
            
            
            
            for($i=0; $i<$ind;$i++){
                
                if($frequency == '1'){ $date = date('Y-m-d', strtotime($date. ' + 30 days'));   }
                else if($frequency == '2'){ $date = date('Y-m-d', strtotime($date. ' + 60 days'));   }
                else if($frequency == '3'){ $date = date('Y-m-d', strtotime($date. ' + 90 days'));    }
                else if($frequency == '4'){ $date = date('Y-m-d', strtotime($date. ' + 120 days'));    }
                else if($frequency == '5'){ $date = date('Y-m-d', strtotime($date. ' + 150 days'));    }
                else if($frequency == '6'){ $date = date('Y-m-d', strtotime($date. ' + 180 days'));    }
                else if($frequency == '7'){ $date = date('Y-m-d', strtotime($date. ' + 210 days'));    }
                else if($frequency == '8'){ $date = date('Y-m-d', strtotime($date. ' + 240 days')); }
                else if($frequency == '9'){  $date = date('Y-m-d', strtotime($date. ' + 270 days')); }
                else if($frequency == '10'){  $date = date('Y-m-d', strtotime($date. ' + 300 days')); }
                else if($frequency == '11'){  $date = date('Y-m-d', strtotime($date. ' + 330 days'));   }
                else if($frequency == '12'){ $date = date('Y-m-d', strtotime($date. ' + 360 days'));   }
                    
                                  $trNoID = 1;
            $sql = "SELECT id FROM training_needs ORDER BY id DESC LIMIT 1";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $trNoID = $row["id"] + 1; 
            }
            $trNo = "T00" . $trNoID;
                 
                
            $sql = "INSERT INTO training_needs (trNo,plant_id,department,training_category, subject,reference_document, proposed_trainer, 
            training_need , proposed_date,training_date, entry_by, entry_date,status,announce_status,trainer,attendance,questionaries, retraining_status)  VALUES ('$trNo','".$_GET["plant_id"]."','$department_name','$training_category',
            '$subject','-','-','-','$date','$date','".$_GET["emp_id"]."','$entry_date','pending','pending','external','pending','pending','NO')";
                

                if($conn->query($sql)){  
                     $training_no = $conn->insert_id;
                     $sql1='';
                     $emps = $input["employees"];
                     
                $sql1 = "INSERT INTO tn_employees (plant_id,tn_no, emp_id,attendance,exam,feedback,mapQuestion) VALUES";
                
                    foreach ($emps as $value) {
                        $sql1 =$sql1."('".$_GET["plant_id"]."',$training_no, '".$value['emp_id']."', 'pending', 'pending', 'NA', 'pending'),";
                    }
                             
                $sql1= rtrim($sql1, ',');
           
                $conn->query($sql1);
                    
                $status = 0;
                
                } else {
                    $status = 1;
                }
            }
                
              
        if($status == 0){  
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
            
        
    }

    
    
    
    
    
    
    
    
    else if ($_GET["type"] == "saveDeptHeadEvaluation") {
         
      $sql1 = "UPDATE training_induction SET status = 'TO_ADMIN_PERSONNEL' ,eval_dept_head = '".$input["eval_dept_head"]."',
      eval_dept_head_by = '".$_GET["emp_id"]."' , eval_dept_head_on = '$entry_date'    WHERE id = '".$_GET['id']."'";
            
        if ($conn->query($sql1)) {
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if ($_GET["type"] == "saveAdminPerssonalEvaluation") {
         
      $sql1 = "UPDATE training_induction SET status = 'TO_QA_Manager' ,eval_hr = '".$input["eval_hr"]."' ,
      eval_hr_by = '".$_GET["emp_id"]."',eval_hr_on = '$entry_date'  WHERE id = '".$_GET['id']."'";
            
        if ($conn->query($sql1)) {
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if ($_GET["type"] == "inductionFromQamanagerApproval") {
         
      $sql1 = "UPDATE training_induction SET status = 'Complete' ,
      qaManagerBy = '".$_GET["emp_id"]."',qaManagerOn = '$entry_date'  WHERE id = '".$_GET['id']."'";
            
        if ($conn->query($sql1)) {
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    
    else if ($_GET["type"] == "saveTrainingNeedsdept") {
                     $trNoID = 1;
            $sql = "SELECT id FROM training_needs ORDER BY id DESC LIMIT 1";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $trNoID = $row["id"] + 1; 
            }
            $trNo = "T00" . $trNoID;
            
            
         $sql = "INSERT INTO training_needs (trNo,plant_id,department, subject, other_subject,reference_document, proposed_trainer, 
            training_need , proposed_date,training_date, entry_by, entry_date,status,announce_status,trainer,attendance,questionaries, retraining_status)  VALUES ('$trNo','".$_GET["plant_id"]."','".$input["department"]."',
            '".$input["subject"]."','".$input["other_subject"]."','".$input["reference_document"]."','".$input["trainer"]."',
            '".$input["training_needs"]."','".$input["training_date"]."','".$input["training_date"]."','".$_GET["emp_id"]."','$entry_date','pending','pending','external','pending','pending','NO')";
            
        if ($conn->query($sql) === TRUE) {
            
                        $training_no = $conn->insert_id;

            $data = $input["employees"];
            for ($i = 0; $i < count($data); $i++) {
                $temp = $data[$i];
                $sql1 = "INSERT INTO tn_employees (plant_id,tn_no, emp_id,attendance,exam,feedback,mapQuestion) VALUES ('".$_GET["plant_id"]."',$training_no, '".$temp['emp_id']."' , 'pending', 'pending', 'NA', 'pending')";
                $conn->query($sql1);
            }
            
            
            echo "{\"status\":\"success\"}";
            
        } else {
             echo "{\"status\": . $conn->error}";

        }
        
    }
    else if ($_GET["type"] == "saveQuestions") {
        
        
        
         
               $json_obj = json_encode($input);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
        
     
                     $sql = "INSERT INTO question_master (plant_id,type,question,option1,option2,option3,option4,answer, entry_by, entry_date)  
                     VALUES ('".$_GET["plant_id"]."', '".$_GET["qtype"]."', '".$values["question"]."', '".$values["option1"]."', '".$values["option2"]."',
                     '".$values["option3"]."','".$values["option4"]."', '".$values["answer"]."','".$_GET["emp_id"]."','$entry_date')";
                     
                    if ($conn->query($sql)) {
                        echo "{\"status\":\"success\"}";
                        
                    } else {
                         echo "{\"status\": . $conn->error}";
            
                    }
          
                }
            
 
        
    }
    else if ($_GET["type"] == "saveinductionChecklist") {
        
        
        
         
               $json_obj = json_encode($input);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
        
     
                     $sql = "INSERT INTO induction_checklist (plant_id,department_name,subject_covered,  entry_by, entry_date)  
                     VALUES ('".$_GET["plant_id"]."',   '".$values["department_name"]."', '".$values["subject_covered"]."','".$_GET["emp_id"]."','$entry_date')";
                     
                    if ($conn->query($sql)) {
                       $k = 0;
                        
                    } else {
                         $k = 1;
            
                    }
          
                }
            
            
                    if ( $k == 0) {
                        echo "{\"status\":\"success\"}";
                        
                    } else {
                         echo "{\"status\": . $conn->error}";
            
                    }
 
        
    }
    
    
     else if($_GET["type"]=="getInductionCheklist") {
        $output = array();
      	   $sql = "SELECT department_name FROM  induction_checklist WHERE plant_id='".$_GET["plant_id"]."' group by department_name";  
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    
    		    
        $output1 = array();
      	$sql1 = "SELECT * FROM  induction_checklist WHERE department_name='".$row["department_name"]."' ";  
    	$result1 = $conn->query($sql1);
    	if($result1->num_rows > 0){
    		while($row1 = $result1->fetch_assoc()) {
    	 
    		    $output1[] = $row1;
    		}
    	}	
    		    
    		    $row['checklist'] = $output1;
    		    
    	 
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
     else if($_GET["type"]=="getsessionquestion") {
        $output = array();
      	   $sql = "SELECT  * FROM  question_master WHERE plant_id='".$_GET["plant_id"]."' AND  type='".$_GET["qtype"]."'";  
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    	 
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
      else if($_GET["type"]=="getClientsLog") {
        $output = array();
      	   $sql = "SELECT c.*, a.agent_name, s.state_name FROM client c LEFT JOIN agent a ON c.agent_no=a.agent_no 
    	LEFT JOIN state s ON c.state_code=s.state_code WHERE c.user_no='".$_GET["user_no"]."'"; //ORDER BY c.company";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $row["branch"] = json_decode($row["branch"]);
    		   $row["divisions"] = json_decode($row["divisions"]);
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "savecategorytraining") {
        
                      $trNoID = 1;
            $sql = "SELECT id FROM training_needs ORDER BY id DESC LIMIT 1";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $trNoID = $row["id"] + 1; 
            }
            $trNo = "T00" . $trNoID;
     
        
        $sql = "INSERT INTO training_needs (trNo,plant_id,department, subject, other_subject,reference_document, proposed_trainer, 
            training_need , proposed_date,training_date, entry_by, entry_date,status,announce_status,trainer,attendance,questionaries, retraining_status)  VALUES ('$trNo','".$_GET["plant_id"]."','".$input["department"]."',
            '".$input["subject"]."','".$input["other_subject"]."','".$input["reference_document"]."','".$input["trainer_name"]."',
            '".$input["training_needs"]."','".$input["protraining_date"]."','".$input["training_date"]."','".$_GET["emp_id"]."','$entry_date','pending','pending','external','pending','pending','NO')";
            
        if ($conn->query($sql) === TRUE) {
            
            $training_no = $conn->insert_id;
     
            
            
            $data = $input["employees"];
            for ($i = 0; $i < count($data); $i++) {
                $temp = $data[$i];
                $sql1 = "INSERT INTO tn_employees (plant_id,tn_no, emp_id,attendance,exam,feedback,mapQuestion) VALUES ('".$_GET["plant_id"]."',$training_no, '".$temp['emp_id']."', 'pending', 'pending', 'NA', 'pending')";
                $conn->query($sql1);
            }
            
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
        else if ($_GET["type"] == "getScheduleLogAnnouncementLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND subject  NOT LIKE '%SOP%' AND announce_status = 'Done'  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
        else if ($_GET["type"] == "getScheduleLogAnnouncementLogForDept") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active'   AND announce_status = 'Done' AND
         training_category = '".$_GET["training_category"]."' AND department = '".$_GET["dept_name"]."' AND plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                
                
                        
          $sql22 = "SELECT 
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["entry_by"]."' LIMIT 1) AS entry_by_name,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["hodApproveBy"]."' LIMIT 1) AS hodApproveName,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["qaApprovedBy"]."' LIMIT 1) AS qaApproveName
                    FROM dual";
                    
                        $result22 = $conn->query($sql22);
                        if ($result22->num_rows > 0) {
                            while ($row22 = $result22->fetch_assoc()) {
                            $row["entry_by_name"] = $row22["entry_by_name"];
                            $row["hodApproveName"] = $row22["hodApproveName"];
                            $row["qaApproveName"] = $row22["qaApproveName"];
                             }
                        }
                
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
        else if ($_GET["type"] == "getOJTScheduleLogAnnouncementLogForDept") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active'   AND announce_status = 'Done' AND
         training_category = '".$_GET["training_category"]."' AND department = '".$_GET["dept_name"]."' AND plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        
                                                $row1["otherDet"] = json_decode($row1["otherDet"]);

                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                
                
                        
          $sql22 = "SELECT 
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["entry_by"]."' LIMIT 1) AS entry_by_name,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["hodApproveBy"]."' LIMIT 1) AS hodApproveName,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["qaApprovedBy"]."' LIMIT 1) AS qaApproveName
                    FROM dual";
                    
                        $result22 = $conn->query($sql22);
                        if ($result22->num_rows > 0) {
                            while ($row22 = $result22->fetch_assoc()) {
                            $row["entry_by_name"] = $row22["entry_by_name"];
                            $row["hodApproveName"] = $row22["hodApproveName"];
                            $row["qaApproveName"] = $row22["qaApproveName"];
                             }
                        }
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 


    else if ($_GET["type"] == "getScheduleLog_announcement") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND subject  NOT LIKE '%SOP%' AND announce_status = 'Pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getScheduleLog_announcementForDept") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active'   AND announce_status = 'Pending' AND
         training_category = '".$_GET["training_category"]."' AND department = '".$_GET["dept_name"]."' AND plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                
                        
          $sql22 = "SELECT 
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["entry_by"]."' LIMIT 1) AS entry_by_name,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["hodApproveBy"]."' LIMIT 1) AS hodApproveName,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["qaApprovedBy"]."' LIMIT 1) AS qaApproveName
                    FROM dual";
                    
                        $result22 = $conn->query($sql22);
                        if ($result22->num_rows > 0) {
                            while ($row22 = $result22->fetch_assoc()) {
                            $row["entry_by_name"] = $row22["entry_by_name"];
                            $row["hodApproveName"] = $row22["hodApproveName"];
                            $row["qaApproveName"] = $row22["qaApproveName"];
                             }
                        }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getOJTScheduleLog_announcementForDept") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active'  AND announce_status = 'Pending' AND
         training_category = '".$_GET["training_category"]."' AND department = '".$_GET["dept_name"]."' AND plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        
                        $row1["otherDet"] = json_decode($row1["otherDet"]);

                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                
                
                
                        
          $sql22 = "SELECT 
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["entry_by"]."' LIMIT 1) AS entry_by_name,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["hodApproveBy"]."' LIMIT 1) AS hodApproveName,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["qaApprovedBy"]."' LIMIT 1) AS qaApproveName
                    FROM dual";
                    
                        $result22 = $conn->query($sql22);
                        if ($result22->num_rows > 0) {
                            while ($row22 = $result22->fetch_assoc()) {
                            $row["entry_by_name"] = $row22["entry_by_name"];
                            $row["hodApproveName"] = $row22["hodApproveName"];
                            $row["qaApproveName"] = $row22["qaApproveName"];
                             }
                        }
                
                
                
                
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getOjtLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE training_category = '".$_GET["training_category"]."' 
        AND department = '".$_GET["dept_name"]."' AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        
                        
                                     $row1["otherDet"] = json_decode($row1["otherDet"]);

                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "ALLgetOjtLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE training_category = '".$_GET["training_category"]."' 
          AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        
                        
                                     $row1["otherDet"] = json_decode($row1["otherDet"]);

                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getOJTFeedbackLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE   feedbackRequired = 'YES'  AND training_category = 'Level 3 ( On The Job Training )'
        AND department = '".$_GET["dept_name"]."' AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        
                        
                        if($row1['feedback'] == 'Done'){
                         $row1["feedback_exp"] = json_decode($row1["feedback_exp"]);

                        }else{
                            $row1["feedback_exp"] = [];
                        }
                        


                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getcGMPFeedbackLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE   feedbackRequired = 'YES'  AND training_category = 'cGMP Training'
        AND department = '".$_GET["dept_name"]."' AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        
                        
                        if($row1['feedback'] == 'Done'){
                         $row1["feedback_exp"] = json_decode($row1["feedback_exp"]);

                        }else{
                            $row1["feedback_exp"] = [];
                        }
                        
                     


                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "gettrainingFeedbackLogForDept") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE   feedbackRequired = 'YES'  AND training_category ='".$_GET["training_category"]."'
        AND department = '".$_GET["dept_name"]."' AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        
                        
                        if($row1['feedback'] == 'Done'){
                         $row1["feedback_exp"] = json_decode($row1["feedback_exp"]);

                        }else{
                            $row1["feedback_exp"] = [];
                        }
                        
                     


                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "gettrainingFeedbackLogAll") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE   feedbackRequired = 'YES'  AND training_category ='".$_GET["training_category"]."'
          AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        
                        
                        if($row1['feedback'] == 'Done'){
                         $row1["feedback_exp"] = json_decode($row1["feedback_exp"]);

                        }else{
                            $row1["feedback_exp"] = [];
                        }
                        
                     


                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 


    else if ($_GET["type"] == "AnnounceTraining") {
        
        
        if($input["selfLern"] == 'YES'){
            $sql = "UPDATE training_needs SET  announce_status = 'Done' ,attendance = 'active' WHERE id='".$input["id"]."'";

        }else{
            $sql = "UPDATE training_needs SET  announce_status='Done' WHERE id='".$input["id"]."'";
        }

        
        
        if ($conn->query($sql) === TRUE) {
 
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }


    else if ($_GET["type"] == "saveScheduleTraining") {

        $sql = "UPDATE training_needs SET proposed_trainer='".$input["proposed_trainer"]."',
        proposed_date='".$input["training_date"]."', training_date='".$input["training_date"]."',
        training_time='".$input["training_time"]."',feedbackRequired='".$input["feedbackRequired"]."',
        venue='".$input["venue"]."', slTime = '".$input["slTime"]."', approve_by='".$_GET["emp_id"]."', 
        approve_date='".$entry_date."', status='TO_HOD' WHERE id='".$input["id"]."'";
        
        
        if ($conn->query($sql) === TRUE) {
            
            $data = $input["empList"];
            for ($i = 0; $i < count($data); $i++) {
                $temp = $data[$i];
                
                $sql1 = "INSERT INTO tn_employees (plant_id,tn_no, emp_id) VALUES 
                ('".$_GET["plant_id"]."','".$input["id"]."', '".$temp['emp_id']."')";
                
                $conn->query($sql1);
            }
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    } 
    else if ($_GET["type"] == "saveOJTScheduleTraining") {

        $sql = "UPDATE training_needs SET proposed_trainer='".$input["proposed_trainer"]."',
        proposed_date='".$input["training_date"]."', training_date='".$input["training_date"]."',
        training_time='".$input["training_time"]."',feedbackRequired='".$input["feedbackRequired"]."', approve_by='".$_GET["emp_id"]."', 
        approve_date='".$entry_date."', status='TO_HOD' WHERE id='".$input["id"]."'";
        
        
        if ($conn->query($sql) === TRUE) {
            
            $data = $input["empList"];
            for ($i = 0; $i < count($data); $i++) {
                $temp = $data[$i];
                
                $sql1 = "UPDATE tn_employees set empTraningDateFrom = '".$temp['empTraningDateFrom']."', 
                empTraningDateTo = '".$temp['empTraningDateTo']."'
                where id = '".$temp['id']."' ";
                
                $conn->query($sql1);
            }
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    } 
    else if ($_GET["type"] == "saveScheduleTrainingGMP") {

        $sql = "UPDATE training_needs SET proposed_trainer='".$input["proposed_trainer"]."',training_time='".$input["training_time"]."',
        venue='".$input["venue"]."', approve_by='".$_GET["emp_id"]."',feedbackRequired='".$input["feedbackRequired"]."', approve_date='".$entry_date."', status='TO_HOD' WHERE id='".$input["id"]."'";
        
        
        if ($conn->query($sql) === TRUE) {
            
            $data = $input["empList"];
            for ($i = 0; $i < count($data); $i++) {
                $temp = $data[$i];
                
                $sql1 = "INSERT INTO tn_employees (plant_id,tn_no, emp_id) VALUES 
                ('".$_GET["plant_id"]."','".$input["id"]."', '".$temp['emp_id']."')";
                
                $conn->query($sql1);
            }
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    } 
    
    else if($_GET["type"] == "saveDailyTraining") {
    	$sql = "INSERT INTO training_daily(subject, date_of_joining, department, trainer, time, duration, topic) VALUES
    	('".$_POST["subject"]."','".$_POST["date_of_joining"]."','".$_POST["department"]."','".$_POST["trainer"]."','".$_POST["time"]."','".$_POST["duration"]."','".$_POST["topic"]."')";
    	if($qa->query($sql) === TRUE) {
    	    echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"failed\"}";
    	}
    } else if($_GET["type"] == "getDailyTraining") {
        $sql = "SELECT * FROM training_daily";
        $result = $qa->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    }   else if($_GET["type"] == "getInductionTraining") {
        $sql = "SELECT * FROM training_induction";
        $result = $qa->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    } 
 
  else if($_GET["type"] == "getTrainingAnnouncement") {
        $sql = "SELECT * FROM training_announcement ORDER By id DESC";
        $result = $conn->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql = "SELECT * FROM training_a_participants WHERE ta_no='".$row["id"]."'";
                $result1 = $conn->query($sql);
                if ($result1->num_rows > 0) {
                    while($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["participants"] = $output1;
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    }else if($_GET["type"] == "getPendingSchedule") {
        $sql = "SELECT * FROM training_announcement ORDER By id DESC";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              $data[] = $row;  
            }
            echo json_encode($data);
        }else{
            echo "[]";
        }
    } else if ($_GET["type"] == "getEmployeeAnnouncements") {
        $output = Array();
        $sql = "SELECT * FROM training_a_participants WHERE emp_id='".$_GET["emp_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM training_announcement WHERE id=".$row["ta_no"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output[] = $row1;
                    }
                }
            }
        }
        echo json_encode($output);
    } 
 
    else if($_GET["type"] == "getAttendanceTraining") {
        $sql = "SELECT * FROM training_attendance";
        $result = $qa->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    }
    else if($_GET["type"] == "saveFeedbackTraining") {
        
        
        
         $sql = "UPDATE tn_employees SET feedback_exp ='".json_encode($input)."' ,feedback = 'Done'  WHERE   id =  '".$_GET["tid"]."'";
        
    
    	if($conn->query($sql)) {
 
    	    
    	    echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"failed\"}";
    	}
    } 
    
 
    
    else if($_GET["type"] == "saveIntimationTraining") {
        $sql = "INSERT INTO training_intimation(joining_date, topic, training_period, intimation_given_to, intimation_given_by, trainer, trainees) VALUES
        ('".$_POST["joining_date"]."','".$_POST["topic"]."','".$_POST["training_period"]."','".$_POST["intimation_given_to"]."','".$_POST["intimation_given_by"]."','".$_POST["trainer"]."','".$_POST["trainees"]."')";
    	if($qa->query($sql) === TRUE) {
    	    echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"failed\"}";
    	}
    } else if($_GET["type"] == "getIntimationTraining") {
        $sql = "SELECT * FROM training_intimation";
        $result = $qa->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    } else if($_GET["type"] == "saveQuestionaireTraining") {
        $sql = "INSERT INTO training_questionaire(department, date, trainee_name, subject, evaluators_name, duration, total_marks, marks_scored, evaluators_assessment, percentage_marks) VALUES
        ('".$_POST["department"]."','".$_POST["date"]."','".$_POST["trainee_name"]."','".$_POST["subject"]."','".$_POST["evaluators_name"]."','".$_POST["duration"]."',
        '".$_POST["total_marks"]."','".$_POST["marks_scored"]."','".$_POST["evaluators_assessment"]."','".$_POST["percentage_marks"]."')";
    	if($qa->query($sql) === TRUE) {
    	    echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"failed\"}";
    	}
    } else if($_GET["type"] == "getQuestionaireTraining") {
        $sql = "SELECT * FROM training_questionaire";
        $result = $qa->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    } 
    
    else if($_GET["type"] == "saveRecordTraining") {
        $sql = "INSERT INTO training_record(sop_name, sop_no, revision_no, training_subject, training_date, department, training_given_by, training_duration, topics_covered, time_from, time_to) VALUES
        ('".$_POST["sop_name"]."','".$_POST["sop_no"]."','".$_POST["revision_no"]."','".$_POST["training_subject"]."','".$_POST["training_date"]."','".$_POST["department"]."',
        '".$_POST["training_given_by"]."','".$_POST["training_duration"]."','".$_POST["topics_covered"]."','".$_POST["time_from"]."','".$_POST["time_to"]."')";
    	if($qa->query($sql) === TRUE) {
    	    echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"failed\"}";
    	}
    }
    else if($_GET["type"] == "saveemployeequestionsanswer") {
         
         $sql = "UPDATE  tn_employees  SET  exam = 'Complete', marks ='".$input["secuMark"]."',totalmark ='".$input["totalmark"]."',
        result ='".$input["result"]."' , answers ='".json_encode($input["questions"])."' 
        Where  id = '".$_GET["id"]."'   ";
        
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }

    	
    }
    else if($_GET["type"] == "ojtSaveemployeequestionsanswer") {
         
         $sql = "UPDATE  tn_employees  SET  exam = 'Complete', marks ='".$input["secuMark"]."',totalmark ='".$input["totalmark"]."',
        result ='".$input["result"]."' , answers ='".json_encode($input["questions"])."' 
        Where  id = '".$_GET["id"]."'   ";
        
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }

    	
    }
    else if($_GET["type"] == "complteOjtEvaluation") {
         
         $sql = "UPDATE  tn_employees  SET  exam = 'Complete' Where  id = '".$_GET["id"]."'   ";
        
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }

    	
    }
    else if($_GET["type"] == "saveOJTEMployeeExamResult") {
         
         $sql = "UPDATE  tn_employees  SET  otherDet ='".json_encode($input)."'  Where  id = '".$_GET["id"]."'   ";
        
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }

    	
    }
    
 
    
    
    else if ($_GET["type"] == "getTrainingNeeds") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                 
                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getTrainingNeedsForDept") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE plant_id = '".$_GET["plant_id"]."' AND training_category = '".$_GET["training_category"]."' AND 
        department = '".$_GET["dept_name"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                 
                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    
    else if ($_GET["type"] == "getOJTTraining") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE training_category = 'Level 3 ( On The Job Training )' AND plant_id = '".$_GET["plant_id"]."'
        AND department = '".$_GET["deptmt101"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                 
                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getTrainingNeedsByDepartment") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE plant_id = '".$_GET["plant_id"]."' AND department = '".$_GET["dept_name"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
             

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if($_GET['type'] == 'identificationneedslog') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
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
    else if ($_GET["type"] == "getTrainingNeedsforneedbase") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE subject = 'Need-Base Training'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsPurchase") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE department = 'Purchase'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsManage") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE department = 'Management'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsRD") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE department = 'R & D'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsEHS") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE department = 'EHS'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsEngg") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE department = 'Enginering Store'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsIPQC") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE department = 'IPQC'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsAdmin") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE department = 'Admin'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsProduction") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE department = 'Production'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsStore") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE department = 'Store'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsMarketing") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE department = 'Marketing'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsforHR") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE  department = 'Human Resource'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsforQMS") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE subject = 'QMS Training'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsforDoc") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE subject = 'Documentation Training'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTrainingNeedsforojt") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE subject = 'On Job Training'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }

                $output1 = Array();
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingTrainings") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='pending' AND retraining_status = 'NO' AND training_category ='".$_GET["training_category"]."'
        AND department ='".$_GET["dept_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation  FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingOJTTrainings") {
        $output = Array();
       $sql = "SELECT * FROM training_needs WHERE status='pending' AND retraining_status = 'NO' AND training_category ='".$_GET["training_category"]."'
        AND department ='".$_GET["dept_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation  FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                         $row1["otherDet"] = json_decode($row1["otherDet"]);

                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingTrainingscGMP") {
        $output = Array();
        $sql = "SELECT *, DATEDIFF(proposed_date, CURDATE()) AS remaining_days FROM training_needs WHERE status='pending' AND
        retraining_status = 'NO'   AND training_category ='".$_GET["training_category"]."'
        AND department ='".$_GET["dept_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation  FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 
    else if ($_GET["type"] == "getPendingTrainingsForDept") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='pending' AND plant_id = '".$_GET["plant_id"]."'
        AND training_category = '".$_GET["training_category"]."'   AND department = '".$_GET["dept_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation  FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 
    
    
    else if ($_GET["type"] == "getPendingNeedbaseTrainings") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='pending' AND subject =  'Need-Base Training'  AND retraining_status = 'NO'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation  FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingQMSTrainings") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='pending' AND subject =  'QMS Training'  AND retraining_status = 'NO'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation  FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingDocTrainings") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='pending' AND subject =  'Documentation Training'  AND retraining_status = 'NO'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation  FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 
    
    
    else if ($_GET["type"] == "getawaitingReTrainings") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='pending' AND retraining_status = 'YES'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation  FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
 
    
    else if ($_GET["type"] == "getScheduleLog") {
        $output = Array(); 
        $sql = "SELECT * FROM training_needs WHERE   subject  NOT LIKE '%SOP%' AND status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    else if ($_GET["type"] == "getScheduleLogForDept") {
        $output = Array(); 
        $sql = "SELECT * FROM training_needs WHERE   subject  NOT LIKE '%SOP%' AND status != 'pending' AND 
        training_category = '".$_GET["training_category"]."' AND department = '".$_GET["dept_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getOJTScheduleLogForDept") {
        $output = Array(); 
        $sql = "SELECT * FROM training_needs WHERE    status != 'pending' AND 
        training_category = '".$_GET["training_category"]."' AND department = '".$_GET["dept_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        
                        
                    $row1["otherDet"] = json_decode($row1["otherDet"]);
                        
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                
                
                
          $sql22 = "SELECT 
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["entry_by"]."' LIMIT 1) AS entry_by_name,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["hodApproveBy"]."' LIMIT 1) AS hodApproveName,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["qaApprovedBy"]."' LIMIT 1) AS qaApproveName
                    FROM dual";
                    
                        $result22 = $conn->query($sql22);
                        if ($result22->num_rows > 0) {
                            while ($row22 = $result22->fetch_assoc()) {
                            $row["entry_by_name"] = $row22["entry_by_name"];
                            $row["hodApproveName"] = $row22["hodApproveName"];
                            $row["qaApproveName"] = $row22["qaApproveName"];
                             }
                        }
                
                
                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getOJTScheduleLogForDeptHoldRej") {
        $output = Array(); 
        $sql = "SELECT * FROM training_needs WHERE    status = '".$_GET["status"]."' AND 
        training_category = '".$_GET["training_category"]."' AND department = '".$_GET["dept_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                         
                    $row1["otherDet"] = json_decode($row1["otherDet"]);
                        
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                 
          $sql22 = "SELECT 
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["entry_by"]."' LIMIT 1) AS entry_by_name,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["hodApproveBy"]."' LIMIT 1) AS hodApproveName,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["qaApprovedBy"]."' LIMIT 1) AS qaApproveName
                    FROM dual";
                    
                        $result22 = $conn->query($sql22);
                        if ($result22->num_rows > 0) {
                            while ($row22 = $result22->fetch_assoc()) {
                            $row["entry_by_name"] = $row22["entry_by_name"];
                            $row["hodApproveName"] = $row22["hodApproveName"];
                            $row["qaApproveName"] = $row22["qaApproveName"];
                             }
                        }
                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getScheduleLogForHODApproval") {
        $output = Array(); 
        $sql = "SELECT * FROM training_needs WHERE   subject  NOT LIKE '%SOP%' AND status = 'TO_HOD'    AND department = '".$_GET["dept_name"]."'
        AND   plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        
                    $row1["otherDet"] = json_decode($row1["otherDet"]);
 
                        $output1[] = $row1;
                    }
                     
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getScheduleLogForQAApproval") {
        $output = Array(); 
        $sql = "SELECT * FROM training_needs WHERE  status = 'TO_QA'    AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                    $row1["otherDet"] = json_decode($row1["otherDet"]);
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getScheduleLogForQAHead") {
        $output = Array(); 
        $sql = "SELECT * FROM training_needs WHERE  status = 'TO_QHEAD'    AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                    $row1["otherDet"] = json_decode($row1["otherDet"]);
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
  
  
    
    else if ($_GET["type"] == "getRetrainingLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND retraining_status = 'YES'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRetrainingLogPurchase") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND retraining_status = 'YES'   AND department = 'Purchase' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRetrainingLogManagement") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND retraining_status = 'YES'   AND department = 'Management' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRetrainingLogRD") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND retraining_status = 'YES'   AND department = 'R & D' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRetrainingLogEHS") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND retraining_status = 'YES'   AND department = 'EHS' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRetrainingLogEngg") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND retraining_status = 'YES'   AND department = 'Enginering Store' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRetrainingLogIPQC") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND retraining_status = 'YES'   AND department = 'IPQC' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRetrainingLogAdmin") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND retraining_status = 'YES'   AND department = 'Admin' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRetrainingLogProduction") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND retraining_status = 'YES'   AND department = 'Production' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRetrainingLogStore") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND retraining_status = 'YES'   AND department = 'Store' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRetrainingLogMarketing") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND retraining_status = 'YES'   AND department = 'Marketing' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    else if ($_GET["type"] == "employeegetScheduleLog") {
        $output = Array();
        
           
          $sql = "SELECT tn.*,t.*,e.firstname,e.lastname FROM tn_employees tn LEFT JOIN training_needs t ON tn.tn_no = t.id left join  
         employee e ON e.emp_id = tn.emp_id  WHERE e.emp_id = '".$_GET["emp_id"]."' AND t.announce_status='Done'  
         AND t.plant_id = '".$_GET["plant_id"]."'";
       
       
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                if($row["training_category"] == 'Level 3 ( On The Job Training )'){
                     $row["otherDet"] = json_decode($row["otherDet"]);
                }else{
                     $row["otherDet"] = [];
                }
                
           
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getSelfLearningEmployee") {
        $output = Array();
        
           
          $sql = "SELECT tn.*,tn.id as tnemp_id,t.subject,t.training_category,t.department,t.reference_document,t.proposed_trainer,
          t.training_need,t.training_time,t.training_date,t.entry_date,t.entry_by,t.status,t.proposed_date,t.slTime,t.selectedFileName,
          t.docToRead,e.firstname,e.lastname FROM tn_employees tn LEFT JOIN training_needs t ON tn.tn_no = t.id left join  
         employee e ON e.emp_id = tn.emp_id  WHERE e.emp_id = '".$_GET["emp_id"]."' AND t.announce_status='Done'  AND  ( tn.attendance='pending'  
         OR tn.attendance='Reading' ) AND training_category = 'Level 1 ( Read a Document )'  AND t.plant_id = '".$_GET["plant_id"]."'";
       
        
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "employeequestions") {
        $output = Array();
         $sql = "SELECT tn.tn_no,tn.emp_id,tn.attendance,tn.exam,t.duration,t.evaluator_name,t.marks,tn.id as tnId ,t.questions,t.department,t.proposed_date,t.status,t.training_need,t.proposed_trainer,t.reference_document,t.training_category,t.subject
        ,e.firstname,e.lastname FROM tn_employees tn LEFT JOIN training_needs t ON tn.tn_no = t.id 
        left join  employee e ON e.emp_id = tn.emp_id  
        WHERE tn.attendance = 'Present' AND t.questionaries='Active' AND training_category != 'Level 3 ( On The Job Training )'   AND tn.exam  = 'pending' AND tn.emp_id = '".$_GET["emp_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             $row["questions"] = json_decode($row["questions"]);

               
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "OJTemployeequestions") {
        $output = Array();
         $sql = "SELECT tn.*,tn.id as tnId ,t.department,t.proposed_date,t.status,t.training_need,t.proposed_trainer,t.reference_document,t.training_category,t.subject
        ,e.firstname,e.lastname FROM tn_employees tn LEFT JOIN training_needs t ON tn.tn_no = t.id 
        left join  employee e ON e.emp_id = tn.emp_id  
        WHERE tn.attendance = 'Present' AND tn.mapQuestion='Active' AND training_category = 'Level 3 ( On The Job Training )'   AND tn.exam  = 'pending' AND tn.emp_id = '".$_GET["emp_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
             $row["otherDet"] = json_decode($row["otherDet"]);

               
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getemployeeTrainningResult") {
        $output = Array();
       $sql = "SELECT tn.*,tn.id as tnId ,t.department,t.proposed_date,t.status,t.training_need,t.proposed_trainer,t.reference_document,
       t.training_category,t.subject
        ,e.firstname,e.lastname FROM tn_employees tn LEFT JOIN training_needs t ON tn.tn_no = t.id 
        left join  employee e ON e.emp_id = tn.emp_id  
        WHERE  tn.exam  = 'Complete' AND tn.emp_id = '".$_GET["emp_id"]."' AND training_category != 'Level 3 ( On The Job Training )' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
          
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "OJTgetemployeeTrainningResult") {
        $output = Array();
       $sql = "SELECT tn.*,tn.id as tnId ,t.department,t.proposed_date,t.status,t.training_need,t.proposed_trainer,t.reference_document,
       t.training_category,t.subject
        ,e.firstname,e.lastname FROM tn_employees tn LEFT JOIN training_needs t ON tn.tn_no = t.id 
        left join  employee e ON e.emp_id = tn.emp_id  
        WHERE  tn.exam  = 'Complete' AND tn.emp_id = '".$_GET["emp_id"]."' AND training_category = 'Level 3 ( On The Job Training )' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
          
                             $row["otherDet"] = json_decode($row["otherDet"]);

                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
   
    else if ($_GET["type"] == "getemployeeTrainningFeedback") {
        $output = Array();
          $sql = "SELECT tn.id as tid,t.*,e.firstname,e.lastname FROM tn_employees tn LEFT JOIN training_needs t ON tn.tn_no = t.id 
        left join  employee e ON e.emp_id = tn.emp_id  
        WHERE tn.attendance = 'present' AND t.status='active' AND tn.feedback='NA'  AND t.feedbackRequired = 'YES'  AND tn.emp_id = '".$_GET["emp_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     
   
    
    else if ($_GET["type"] == "getPendingScheduleTrainings") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getPendingAttendanceTrainings") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE announce_status='Done' AND attendance='pending'    AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                $output1 = Array();
                $sql1 = "SELECT tn.*,e.firstname , e.lastname,e.department,e.designation  FROM tn_employees tn left join employee e ON tn.emp_id = e.emp_id WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingAttendanceTrainingsForSep") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE announce_status='Done' AND attendance='pending' 
        AND  training_category = '".$_GET["training_category"]."' 
        AND department = '".$_GET["dept_name"]."'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                $output1 = Array();
                $sql1 = "SELECT tn.*,e.firstname , e.lastname,e.department,e.designation  FROM tn_employees tn left join employee e ON tn.emp_id = e.emp_id WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   
    else if ($_GET["type"] == "getPendingAttendanceTrainingsForOJT") {
        
         
     
                $output = Array();
                
                 $sql = "SELECT tn.*,tn.id as tnemp_id,t.department,t.proposed_date,t.status,t.training_need,t.proposed_trainer,t.trNo,
                 t.reference_document,t.training_category,t.subject,e.firstname , e.lastname,e.department,e.designation  FROM tn_employees tn 
                 left join employee e ON tn.emp_id = e.emp_id left join training_needs t ON t.id = tn.tn_no  where t.announce_status = 'Done'
                 AND t.training_category = 'Level 3 ( On The Job Training )'  AND tn.attendance = 'pending'  AND 
                 tn.in_department = '".$_GET["dept_name"]."' AND t.plant_id = '".$_GET["plant_id"]."'";
                
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        
                    $row["otherDet"] = json_decode($row["otherDet"]);

                        
                        $output[] = $row;
                    }
                }
          
         
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingAttendanceTrainingForEmployee") {
        
         
     
                $output = Array();
                
                 $sql = "SELECT tn.*,tn.id as tnemp_id,t.department,t.proposed_date,t.status,t.training_need,t.proposed_trainer,t.reference_document,t.training_category,t.subject,
                 e.firstname , e.lastname,e.department,e.designation  FROM tn_employees tn left join employee e ON tn.emp_id = e.emp_id
                left join training_needs t ON t.id = tn.tn_no  where t.announce_status = 'Done' AND t.training_category = 'Level 3 ( On The Job Training )' 
                AND ( tn.attendance = 'pending' OR tn.training_end_time = 'pending' )  AND tn.emp_id = '".$_GET["emp_id"]."' AND t.plant_id = '".$_GET["plant_id"]."'";
                
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        
                    $row["otherDet"] = json_decode($row["otherDet"]);

                        
                        $output[] = $row;
                    }
                }
          
         
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getOJTForPractical") {
        
         
     
                $output = Array();
                
            $sql = "SELECT tn.*,tn.id as tnemp_id,t.department,t.proposed_date,t.status,t.training_need,t.proposed_trainer,t.reference_document,
            t.training_category,t.subject,e.firstname , e.lastname,e.department,e.designation  FROM tn_employees tn left join employee e ON
            tn.emp_id = e.emp_id left join training_needs t ON t.id = tn.tn_no  where t.announce_status = 'Done' AND 
            t.training_category = 'Level 3 ( On The Job Training )' AND  tn.attendance = 'Present'  
            AND tn.in_department = '".$_GET["dept_name"]."' AND tn.plant_id = '".$_GET["plant_id"]."' ";
                
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                    $row["otherDet"] = json_decode($row["otherDet"]);
                        $output[] = $row;
                    }
                }
          
         
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendinQuestionariesForOJT") {
        
         
     
                $output = Array();
                
                 $sql = "SELECT tn.*,tn.id as tnemp_id,t.department,t.proposed_date,t.status,t.training_need,t.proposed_trainer,t.trNo,
                 t.reference_document,t.training_category,t.subject,e.firstname , e.lastname,e.department,e.designation  
                 FROM tn_employees tn left join employee e ON tn.emp_id = e.emp_id
                left join training_needs t ON t.id = tn.tn_no  where t.announce_status = 'Done' AND t.training_category = 'Level 3 ( On The Job Training )' 
                AND tn.attendance = 'Present' AND tn.mapQuestion = 'Pending' AND tn.in_department = '".$_GET["dept_name"]."' AND t.plant_id = '".$_GET["plant_id"]."'";
                
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                                            $row["otherDet"] = json_decode($row["otherDet"]);

                        $output[] = $row;
                    }
                }
          
         
        echo json_encode($output);
    }
    
     else if ($_GET["type"] == "getquestions") {
        $output = Array();
        $sql = "SELECT * FROM question_master_hdr WHERE   department_name =  '".$_GET["dept_name"]."'  OR department_name = 'FOR ALL' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['questions'] = json_decode($row['questions']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
      
    
 
    
    else if ($_GET["type"] == "getAttendanceLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT tn.*,e.firstname , e.lastname,e.department,e.designation  FROM tn_employees tn left join employee e ON tn.emp_id = e.emp_id WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getAttendanceLogForSep") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND  training_category = '".$_GET["training_category"]."' 
        AND department = '".$_GET["dept_name"]."'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              
                $output1 = Array();
                $sql1 = "SELECT tn.*,e.firstname , e.lastname,e.department,e.designation  FROM tn_employees tn 
                left join employee e ON tn.emp_id = e.emp_id WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getAttendanceLogForDept") {
        $output = Array();
          $sql = "SELECT * FROM training_needs WHERE attendance='active' AND  training_category = '".$_GET["training_category"]."' 
        AND department = '".$_GET["dept_name"]."'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT tn.*,e.firstname , e.lastname,e.department,e.designation  FROM tn_employees tn 
                left join employee e ON tn.emp_id = e.emp_id WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getQuestionnierForDept") {
        $output = Array();
         $sql = "SELECT * FROM training_needs WHERE attendance='active' AND questionaries = 'pending' AND  training_category = '".$_GET["training_category"]."' 
        AND department = '".$_GET["dept_name"]."'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                $output1 = Array();
                $sql1 = "SELECT tn.*,e.firstname , e.lastname,e.department,e.designation  FROM tn_employees tn 
                left join employee e ON tn.emp_id = e.emp_id WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getNeedBAseAttendanceLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND subject = 'Need-Base Training'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPurchaseAttendanceLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND department = 'Purchase'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     else if ($_GET["type"] == "getequipmentBydept") {
        $output = Array();
        $sql = "SELECT  id,plant_id,equipment_code,equipment_name,department,location,serial_no FROM equipment 
        WHERE status ='Active' AND department = '".$_GET["dept_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getAttendanceLogEngg") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND department = 'Enginering Store'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getIPQCAttendanceLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND department = 'IPQC'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getAdminAttendanceLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND department = 'Admin'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getProductionAttendanceLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND department = 'Production'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getStoreAttendanceLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND department = 'Store'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getMarketingAttendanceLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND department = 'Marketing'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getQMSAttendanceLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND subject = 'QMS Training'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getDocAttendanceLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND subject = 'Documentation Training'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getOJTAttendanceLog") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND subject = 'On Job Training'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "scheduleTrainingapproveByHod") {
 

         $sql = "UPDATE training_needs SET hodApproveBy = '".$_GET["emp_id"]."',  hodRemark = '".$input["remark"]."', 
        hodApproveOn = '$entry_date', status = '".$_GET["status"]."' WHERE id = ".$_GET["trainingId"];
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        
    }
    
    else if ($_GET["type"] == "scheduleTrainingapproveByQA") {
  
         $sql = "UPDATE training_needs SET qaApprovedBy = '".$_GET["emp_id"]."', 
        qaApprovedOn = '$entry_date', status = '".$_GET["status"]."' WHERE id = ".$_GET["trainingId"];
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if ($_GET["type"] == "scheduleTrainingapproveByQHead") {
  
         $sql = "UPDATE training_needs SET qHeadBy = '".$_GET["emp_id"]."', 
        qHeadOn = '$entry_date', status = '".$_GET["status"]."' WHERE id = ".$_GET["trainingId"];
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    
    else if ($_GET["type"] == "saveAttendance") {
 


         $sql = "UPDATE training_needs SET training_start_time='".$input["training_start_time"]."', 
        training_end_time='".$input["training_end_time"]."', attendance='active' WHERE id=".$input["id"];
       
        if ($conn->query($sql) === TRUE) {
            $data = $input["employees"];
            for ($i = 0; $i < count($data); $i++) {
                $temp = $data[$i];
                $sql1 = "UPDATE   tn_employees SET attendance = '".$temp["attendance"]."' WHERE id = '".$temp["id"]."' ";
                $conn->query($sql1);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        
    }
    else if ($_GET["type"] == "saveAttendanceForOJT") {
            
        
          $sql = "UPDATE   tn_employees SET otherDet = '".json_encode($input["othersDetailsData1"])."'  WHERE id = '".$input["tnemp_id"]."' ";
       
        if ($conn->query($sql) === TRUE) {
    
        
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if ($_GET["type"] == "saveAttendanceForOJTfrorEmp") {
            
        
          $sql = "UPDATE   tn_employees SET otherDet = '".json_encode($input["othersDetailsData1"])."'  WHERE id = '".$input["tnemp_id"]."' ";
       
        if ($conn->query($sql) === TRUE) {
    
        
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if ($_GET["type"] == "CompleteAttendanceSelfLearning") {
            
        
          $sql = "UPDATE   tn_employees SET training_end_time = '$entry_date' ,attendance = 'Present'   WHERE id = '".$input["tnemp_id"]."' ";
       
        if ($conn->query($sql) === TRUE) {
    
        
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if ($_GET["type"] == "saveAttendanceSelfLearning") {
            
        
          $sql = "UPDATE   tn_employees SET training_start_time = '$entry_date' ,attendance = 'Reading'  WHERE id = '".$input["tnemp_id"]."' ";
       
        if ($conn->query($sql) === TRUE) {
    
        
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if ($_GET["type"] == "completeOjtAttendance") {
            
        
          $sql = "UPDATE   tn_employees SET attendance = 'Present' ,attendance_by= '".$_GET["emp_id"]."'  WHERE id = '".$input["tnemp_id"]."' ";
       
        if ($conn->query($sql) === TRUE) {
    
        
            echo "{\"status\":\"success\"}";
            
            
            
            $sql1 = "SELECT * FROM tn_employees   WHERE  attendance = 'pending'   AND  tn_no = '".$input["tn_no"]."'";
            
            $result1 = $conn->query($sql1);
            if($result1->num_rows > 0) {
            
             
            }else{
                    
                 $sql11 = "Update training_needs SET  attendance = 'active'    where id = '".$input["tn_no"]."'";
                $conn->query($sql11);
            }
              
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if ($_GET["type"] == "saveAttendanceForOJTref") {
           
        
        // if($input["attendance"] == 'Present'){
        //     $end = 'pending';
        // }else{
        //     $end = 'Done';
        // }
         
        
        
        //  $sql = "UPDATE   tn_employees SET attendance = '".$input["attendance"]."', training_start_time = '".$input["training_start_time"]."',
        // training_end_time = '$end' WHERE id = '".$input["tnemp_id"]."' ";
       
        // if ($conn->query($sql) === TRUE) {
            
        // $sql1 = "SELECT id FROM tn_employees WHERE  attendance = 'pending' AND tn_no = '".$input["tn_no"]."' ";
        // $result1 = $conn->query($sql1);
        // if ($result1->num_rows > 0) {

        // }else{
        //     $sql0 = "UPDATE training_needs SET   attendance = 'active' WHERE id = '".$input["tn_no"]."' ";
        //     $conn->query($sql0);
        // }
        
        //     echo "{\"status\":\"success\"}";
        // } else {
        //     echo "{\"status\":\"failed\"}";
        // }
        
    }
    else if ($_GET["type"] == "saveAttendanceForOJTendTime") {
        
         $sql = "UPDATE   tn_employees SET  otherDet = '".json_encode($input["othersDetailsData1"])."' WHERE id = '".$input["tnemp_id"]."' ";
       
        if ($conn->query($sql) === TRUE) {
            
                 $sql1 = "SELECT id FROM tn_employees WHERE  attendance = 'pending' AND tn_no = '".$input["tn_no"]."' ";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {

        }else{
            $sql0 = "UPDATE training_needs SET   attendance = 'active' WHERE id = '".$input["tn_no"]."' ";
            $conn->query($sql0);
        }
             
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if ($_GET["type"] == "getPendingQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingNeedBaseQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND subject = 'Need-Base Training'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingPurchaseQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND department = 'Purchase'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingManagementQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND department = 'Management'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingRDQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND department = 'R & D'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingEHSQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND department = 'EHS'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingEnggQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND department = 'Enginering Store'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingIPQCQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND department = 'IPQC'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingAdminQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND department = 'Admin'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingProductionQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND department = 'Production'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingQuestionariesStore") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND department = 'Store'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingMarketingQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND department = 'Marketing'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingQMSQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND subject = 'QMS Training'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingDocQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND subject = 'Documentation Training'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingOJTQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE status='active' AND attendance='active' AND subject = 'On Job Training'  AND questionaries='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["emp_name"] = $row2["emp_name"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $row1["attendance"] = 'pending';
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getCompletedTrainings") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND   plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getCompletedTrainingsForDept") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND training_category = '".$_GET["training_category"]."' 
        AND plant_id='".$_GET["plant_id"]."'  AND   department='".$_GET["dept_name"]."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getCompletedTrainingsForqa") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND training_category = '".$_GET["training_category"]."' 
        AND plant_id='".$_GET["plant_id"]."'   ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getCompletedTrainingscgmpForDept") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND training_category='cGMP Training' AND plant_id='".$_GET["plant_id"]."'
        AND   department='".$_GET["dept_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getCompletedTrainingscgmp") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND training_category='cGMP Training' AND   plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                $output1 = Array();
                $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
  
    
    else if ($_GET["type"] == "getCompletedQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE questionaries='active' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                 $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }

                $row["questions"] = json_decode($row["questions"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
 
    else if ($_GET["type"] == "getCompletedQMSQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE questionaries='active' AND subject = 'QMS Training'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }

                $row["questions"] = json_decode($row["questions"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    else if ($_GET["type"] == "getCompletedDocQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE questionaries='active' AND subject = 'Documentation Training'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }

                $row["questions"] = json_decode($row["questions"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    else if ($_GET["type"] == "getCompletedOJTQuestionaries") {
        $output = Array();
        $sql = "SELECT * FROM training_needs WHERE questionaries='active' AND subject = 'On Job Training'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }

                $row["questions"] = json_decode($row["questions"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "saveQuestionnaries") {
         $sql = "UPDATE training_needs SET evaluator_name='".$input["evaluator_name"]."', duration='".$input["duration"]."', marks='".$input["total_marks"]."', questions='".json_encode($input["questions"])."', questionaries='active' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }     
    else if ($_GET["type"] == "saveQuestionnariesForALL") {
         $sql = "UPDATE training_needs SET questionaries = 'Active', questions = '".json_encode($input["questions"])."' ,
        evaluator_name='".$input["evaluator_name"]."', duration='".$input["duration"]."',
        marks='".$input["total_marks"]."' WHERE id = '".$input["tnNo"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
                     $sql = "UPDATE tn_employees SET mapQuestion='Active', questions = '".json_encode($input["questionsForEmp"])."' 
                     WHERE tn_no = '".$input["tnNo"]."'";

        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    
    else if ($_GET["type"] == "saveQuestionnariesForOJT") {
         $sql = "UPDATE tn_employees SET mapQuestion='Active', otherDet = '".json_encode($input)."' WHERE id='".$_GET["tnemp_id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if ($_GET["type"] == "saveQuestionMaster") {
          $sql = "INSERT INTO `question_master_hdr`(`plant_id`, `department_name`,`training_category`, `subject`, `questinner_heading`, `evaluator_name`,
         `total_marks`, `questions`,duration) VALUES ('".$_GET["plant_id"]."','".$input["department_name"]."','".$input["training_category"]."','".$input["subject"]."','".$input["questinner_heading"]."',
         '".$input["evaluator_name"]."','".$input["total_marks"]."','".json_encode($input["questions"])."','".$input["duration"]."') ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    
    
    else if ($_GET["type"] == "getqestionirries") {
        $output = Array();
        $sql = "SELECT * FROM question_master_hdr where department_name = '".$_GET["dept_name"]."' OR department_name = 'FOR ALL' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["questions"] = json_decode($row["questions"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getAllQestionirries") {
        $output = Array();
        $sql = "SELECT * FROM question_master_hdr where plant_id = '".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["questions"] = json_decode($row["questions"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    else if ($_GET["type"] == "getTrainingRecords") {
        $output = Array();
        $sql = "SELECT * FROM tn_employees WHERE emp_id='".$_GET["employee"]."' AND attendance != 'pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM training_needs WHERE id='".$row["tn_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["attendance"] = $row["attendance"];
                        $row1["marks"] = $row["marks"];
                        $row1["result"] = $row["result"];
                        $row1["feedback"] = $row["feedback"];
                    
                        $start = strtotime($row1["training_start_time"]);
                        $end = strtotime($row1["training_end_time"]);
                        $elapsed = $end - $start;
                        $row1["duration"] = date("H:i", $elapsed);
                        
                
                        $output[] = $row1;
                    }
                }
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getSelfCertificates") {
        $output = Array();
        $sql = "SELECT * FROM self_certificate";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM sops WHERE sop_no='".$row["sop_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows >0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["sop_name"] = $row1["sop_name"];
                        $row["department"] = $row1["department"];
                        break;
                    }
                }
                $sql1 = "SELECT * FROM employee WHERE emp_id='".$row["entry_by"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows >0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["emp_name"] = $row1["emp_name"];
                        break;
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    else if ($_GET["type"] == "getPendingEmployeeExam") {
        $output = Array();
        $sql = "SELECT * FROM tn_employees WHERE attendance='present' AND exam='pending' AND emp_id='".$_GET["emp_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $sql1 = "SELECT * FROM training_needs WHERE id='".$row["tn_no"]."'";
                $result1 = $conn->query($sql1);
                if ($conn->query($sql1)) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $row1["evaluation_id"] = $row["id"];

                        $sql2 = "SELECT * FROM externaltrainer WHERE id=".$row1["trainer_name"];
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["trainer_name"] = $row2["trainer_name"];
                            }
                        }

                        $row1["questions"] = json_decode($row1["questions"]);
                        $output[] = $row1;
                    }
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveEvaluationSheet") {
        $marks = 0;
        for ($i = 0; $i < count($input); $i++) {
            $question = $input[$i];
            if ($question['answer'] == $question['selectedAns']) {
                $marks++;
            }
        }
        $sql = "UPDATE tn_employees SET marks='$marks', exam='inprocess', questions='".json_encode($input)."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getCompletedEmployeeExam") {
        $output = Array();
        $sql = "SELECT * FROM tn_employees WHERE attendance='present' AND exam !='pending' AND emp_id='".$_GET["emp_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $sql1 = "SELECT * FROM training_needs WHERE id='".$row["tn_no"]."'";
                $result1 = $conn->query($sql1);
                if ($conn->query($sql1)) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $row1["evaluation_id"] = $row["id"];

                        $sql2 = "SELECT * FROM externaltrainer WHERE id=".$row1["trainer_name"];
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["trainer_name"] = $row2["trainer_name"];
                            }
                        }

                        $row1["questions"] = json_decode($row["questions"]);
                        $output[] = $row1;
                    }
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getLabours") {
        $output = Array();
        $sql = "SELECT labour_name,labour_no,id FROM labour ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["status"] = "true";
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDailyAnnoucement") {
        $sql = "INSERT INTO daily_training ( training_time, trainer_no,subject , participants, entry_by, entry_date,plant_id) VALUES ('".$input["training_time"]."', '".$input["trainer"]."', '".$input["subject"]."', '".json_encode($input["participants"])."', '".$_GET["emp_id"]."', '$entry_date','".$_GET["plant_id"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getDailyAnnoucementLog") {
        $output = Array();
        $sql = "SELECT * FROM daily_training";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id='".$row["trainer_no"]."'";
                $result1 = $conn->query($sql1);
                while ($row1 = $result1->fetch_assoc()) {
                    $row["trainer_name"] = $row1["trainer_name"];
                    break;
                }
                $row["participants"] = json_decode($row["participants"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingDailyAnnoucements") {
        $output = Array();
        $sql = "SELECT *,DATE(entry_date) AS training_date FROM daily_training WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id='".$row["trainer_no"]."'";
                $result1 = $conn->query($sql1);
                while ($row1 = $result1->fetch_assoc()) {
                    $row["trainer_name"] = $row1["trainer_name"];
                    break;
                }
                $row["participants"] = json_decode($row["participants"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getdailytraininglog") {
        $output = Array();
        $sql = "SELECT *,DATE(entry_date) AS training_date FROM daily_training WHERE status != 'pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id='".$row["trainer_no"]."'";
                $result1 = $conn->query($sql1);
                while ($row1 = $result1->fetch_assoc()) {
                    $row["trainer_name"] = $row1["trainer_name"];
                    break;
                }
                $row["participants"] = json_decode($row["participants"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getCheckedDailyAnnoucements") {
        $output = Array();
        $sql = "SELECT * , DATE(entry_date) AS training_date FROM daily_training WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id='".$row["trainer_no"]."'";
                $result1 = $conn->query($sql1);
                while ($row1 = $result1->fetch_assoc()) {
                    $row["trainer_name"] = $row1["trainer_name"];
                    break;
                }
                $row["participants"] = json_decode($row["participants"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "checkDailyAnnoucement") {
        $sql = "UPDATE daily_training SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    
        else if ($_GET["type"] == "getsubject12") {
         $sql = " SELECT * FROM `training_subject` WHERE plant_id = '".$_GET["plant_id"]."'  ";
          $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "getsubject1") {
         $sql = " SELECT * FROM `training_subject` WHERE plant_id = '".$_GET["plant_id"]."' AND  
         training_category = '".$_GET["training_category"]."' ";
          $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getAllDepartments") {
         $sql = " SELECT * FROM `department` ";
          $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    
    
    

    
        
    else if ($_GET["type"] == "save_training_category") {
        
        $sql = "INSERT INTO training_category_table  (training_category)  values('".$input["training_category"]."')";
    
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }

    
    else if ($_GET["type"] == "getTrainingCategory") {
        $sql = "SELECT * FROM training_category_table order by id Desc";
        $result = $conn->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    } 
    
    
     else if ($_GET["type"] == "addsubjects") {
        $sql = "INSERT INTO `training_subject`( `plant_id`, `subject`,training_desc,training_category,grade) VALUES
        ('".$_GET["plant_id"]."','".$input["subject"]."','".$input["training_desc"]."','".$input["training_category"]."','".$input["training_category"]."')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    
    
    else if ($_GET["type"] == "getsubject") {
         $sql = " SELECT * FROM `training_subject` WHERE plant_id = '".$_GET["plant_id"]."' ";
          $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getinhousetrainer") {
        $sql = "SELECT * FROM externaltrainer WHERE trainer_type = 'inhouse' AND plant_id = '".$_GET["plant_id"]."' ";
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "approveDailyAnnoucement") {
        $sql = "UPDATE daily_training SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    
    else if ($_GET["type"] == "saveExternalTrainer") {
        $input = $_POST;
        
            $target_dir = "../../upload/training/";
             
           $plant_id = $_GET["plant_id"];
           $trainer_name = $input["trainer_name"];
           
           if(isset($_FILES["certificate"]["name"])) {
            	$target_file = $target_dir.$plant_id.$trainer_name."_".basename($_FILES["certificate"]["name"]);
            	$msds_file = $plant_id.$trainer_name."_".basename($_FILES["certificate"]["name"]);
        	    move_uploaded_file($_FILES["certificate"]["tmp_name"], $target_file);
        	     
           }
        
        
       $sql = "INSERT INTO externaltrainer (trainer_type,trainer_name, organisation, designation, qualification, experience, 
        contact_no, email, certificate, entry_by, entry_date,plant_id,skills_data) VALUES ('".$input["trainer_type"]."', '".$input["trainer_name"]."','".$input["organisation"]."', 
        '".$input["designation"]."', '".$input["qualification"]."', '".$input["experience"]."', '".$input["contact_no"]."', '".$input["email"]."',
        '$msds_file', '".$_GET["emp_id"]."', '$entry_date','".$_GET["plant_id"]."','".$input["skills_data"]."'  )";
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    
     
    else if ($_GET["type"] == "getExternalTrainersLog") {
        $sql = "SELECT * FROM externaltrainer WHERE   plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
            $row["skills_data"] = json_decode($row["skills_data"]);
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    }
    
    else if ($_GET["type"] == "getExternalTrainersforApproval") {
        $sql = "SELECT * FROM externaltrainer WHERE status= 'pending' AND  plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
            $row["skills_data"] = json_decode($row["skills_data"]);
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    }
    
    
    else if ($_GET["type"] == "ApproveTrainer") {
        $sql = "UPDATE externaltrainer SET status= '".$_GET["status"]."' ,approve_by = '".$_GET["emp_id"]."', approve_date = '$entry_date' where  id = '".$_GET["id"]."'";
    
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    
    else if ($_GET["type"] == "getTrainers") {
        $sql = "SELECT * FROM externaltrainer WHERE status='active'";
        $result = $conn->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    } 
    
    
    else if ($_GET["type"] == "getPendingRetrainingsByDept") {
        $output = array();
        $sql = "select tn.* from training_needs tn LEFT JOIN tn_employees t ON tn.id = t.tn_no
        WHERE (t.result = 'Fail' OR t.attendance = 'Absent') AND tn.retraining_status = 'NO'  
        AND tn.plant_id = '".$_GET["plant_id"]."' AND tn.training_category = '".$_GET["training_category"]."'  
        AND tn.department = '".$_GET["dept_name"]."' GROUP BY tn.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                
                 $output1 = array();
                $sql1 = "SELECT t.*, e.firstname,e.lastname, e.department,e.designation FROM tn_employees t LEFT 
                JOIN employee e ON t.emp_id = e.emp_id WHERE tn_no='".$row["id"]."' AND (t.attendance='Absent' OR t.result='Fail')";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         if ($row1["attendance"] == "Absent") {
                            $row1["reason"] = 'Absent';
                        } else {
                            $row1["reason"] = $row1["result"];
                        }
                         
                      
                        $output1[] = $row1;
                        
                    }
                }
                
                $row["employees"] = $output1;

                $output[] = $row;

            }
        }
        echo json_encode($output);
        
    } 
    
 
    
    
    else if ($_GET["type"] == "saveRetraining") {
        
        
        
        
            $trNoID = 1;
            $sql = "SELECT id FROM training_needs ORDER BY id DESC LIMIT 1";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $trNoID = $row["id"] + 1; 
            }
            $trNo = "RT0" . $trNoID;
        
        
        
       $sql = "INSERT INTO training_needs (trNo,plant_id,department, subject, training_category,reference_document, proposed_trainer, 
            training_need , proposed_date,docToRead,selectedFileName,slTime, entry_by, entry_date , status,announce_status,trainer,attendance,questionaries, retraining_status)  VALUES ('$trNo','".$_GET["plant_id"]."',
            '".$input["department"]."','".$input["subject"]."','".$input["training_category"]."','".$input["reference_document"]."',
            '".$input["proposed_trainer"]."','".$input["training_need"]."','".$input["training_date"]."' ,'".$input["docToRead"]."' ,
            '".$input["selectedFileName"]."' ,'".$input["slTime"]."' ,
            '".$_GET["emp_id"]."','$entry_date','pending','pending','external','pending','pending','NO')";
            
        if ($conn->query($sql) === TRUE) {
             $training_no = $conn->insert_id;
             
                $sql0 = "UPDATE training_needs  SET retraining_status = 'OK' WHERE id = '".$input["id"]."'";
                $conn->query($sql0);
            
               
 
            $data = $input["employees"];
            for ($i = 0; $i < count($data); $i++) {
                $temp = $data[$i];
                $sql1 = "INSERT INTO tn_employees (plant_id,tn_no, emp_id,attendance,exam,feedback,mapQuestion) VALUES ('".$_GET["plant_id"]."',$training_no, '".$temp['emp_id']."' , 'pending', 'pending', 'NA', 'pending')";
                $conn->query($sql1);
            }
            
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }


	else if($_GET["type"]=="downloadTrainingNeeds"){
	      $_GET['filename'] = 'TrainingNeeds '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
	      $html.='
	      <table cellpadding="5" border="0.1">
	      <tr>
	      <td style="width:5%;text-align:center"><b>Sr.	</b></td>
	       <td style="width:15%;text-align:center"><b>Department</b></td>
	        <td style="width:15%;text-align:center"><b>Title Of Sop</b></td>
	         <td style="width:10%;text-align:center"><b>Sop No</b></td>
	          <td style="width:25%;text-align:center"><b>Name Of Employee</b></td>
	           <td style="width:15%;text-align:center"><b>Mark of requirement</b></td>
	            <td style="width:15%;text-align:center"><b>Target Date/ Week</b></td>
	      </tr>
	       <tr>
	      <td style="width:5%"></td>
	       <td style="width:15%"></td>
	        <td style="width:15%"></td>
	         <td style="width:10%"></td>
	          <td style="width:25%"></td>
	           <td style="width:15%"></td>
	            <td style="width:15%"></td>
	      </tr>
	      </table>';
         
       $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('IncidentReport.pdf', 'I');
    
    
    
    
}else if ($_GET["type"] == "downloadCertificateLog") {
        $_GET['filename'] = 'Training Certificate '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr</td>
                    <td style="width: 20%;">Department</td>
                    <td style="width: 15%;">Training Subject</td>
                    <td style="width: 15%;">Trainer</td>
                    <td style="width: 15%;">Training Date</td>
                    <td style="width: 15%;">Training Time</td>
                     <td style="width: 15%;">Proposed Training Date</td>
                    
                   
                </tr>
            </thead>';
             $i=1;
        $sql = "SELECT * FROM  ";
    //   echo $sql;
        $result = $conn->query($sql);
        if($result->num_rows > 0){  
            $i = 1;
            while($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'</td>
                        <td style="width: 20%;">'.$row['department'].'</td>
                        <td style="width: 15%;">'.$row['training_sub'].'</td>
                        <td style="width: 15%;">'.$row['trainer_name'].'</td>
                        <td style="width: 15%;">'.$row['training_date'].'</td>
                        <td style="width: 15%;">'.$row['training_time'].'</td>
                         <td style="width: 15%;">'.$row['proposed_date'].'</td>
                       
                    </tr>';
                $i++;
            }
        }
       
             $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Training Certificate.pdf', 'I');
    }
    
} 

$conn->close();
?>