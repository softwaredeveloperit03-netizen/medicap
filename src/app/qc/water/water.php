<?php


// error_reporting(E_ALL);
// ini_set('display_errors', 1);

try{
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d H:i:s", $timestamp);
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

    if ($_GET["type"] == "savePoint") {
        
        
        
         
        $sql = "SELECT MAX(id) as lastId FROM water_point WHERE plant_id = '".$_GET["plant_id"]."' AND  water_type = '".$input["water_type"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lastId = $row['lastId'] + 1;
            }
        }else{
            $lastId = 1;
        }
        
        
        $lastIdFormatted = sprintf('%05d', $lastId);

         
        $point_no = '';
        
        $water_type = trim($input["water_type"]);
        $water_type_key = strtolower($water_type);
        if ($water_type_key == 'sterile water for irrigations') {
            $water_type = 'Sterile Water For Irrigation';
            $water_type_key = strtolower($water_type);
        }

        if($water_type_key == 'purified water'){
            $point_no = "PW".$lastIdFormatted;
        }
        else if($water_type_key == 'ro water'){
            $point_no = "RO".$lastIdFormatted;
        }
        else if($water_type_key == 'pure steam water'){
            $point_no = "PS".$lastIdFormatted;
        }
        else if($water_type_key == 'raw water'){
            $point_no = "RW".$lastIdFormatted;
        }
        else if($water_type_key == 'soft water'){
            $point_no = "SW".$lastIdFormatted;
        }
        else if($water_type_key == 'water for injections' || $water_type_key == 'water for injection'){
            $point_no = "WF".$lastIdFormatted;
        }
        else {
            $point_no = "W".$lastIdFormatted;
        }
        
         
        $sql = "INSERT INTO water_point (plant_id,point_no, point_name, water_type,  day, testing_type,department, section, entry_by, entry_date,point_type,water_for)
        VALUES ('".$_GET["plant_id"]."','$point_no', '".$input["point_name"]."', 
        '".$water_type."', '".$input["day"]."', '".$input["testing_type"]."', '".$input["department"]."', '".$input["section"]."', '".$_GET["emp_id"]."', '$entry_date', '".$input["point_type"]."', '".$input["water_for"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
        
        
    } else if ($_GET["type"] == "updatePoint") {
        $sql = "UPDATE water_point SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "allocateSamplingPerson") {
        $sampling_person = mysqli_real_escape_string($conn, $_GET["sampling_person"]);
        if (isset($_GET["schedule_id"]) && $_GET["schedule_id"] != "") {
            $schedule_id = (int)$_GET["schedule_id"];
            $sql = "UPDATE water_frequency_schedule 
                    SET sampling_allocated_to = '".$sampling_person."',
                        sampling_allocated_on = '".$entry_date."',
                        sampling_status = 'Allocated'
                    WHERE id = '".$schedule_id."'";
        } else {
            echo "{\"status\":\"failed\"}";
            exit;
        }
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
    
    
    
    else if ($_GET["type"] == "getPendingPoints") {
        $output = Array();
        $sql = "SELECT * FROM water_point WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPointsLog") {
        $output = Array();
        $sql = "SELECT * FROM water_point";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $row['frequency'] = json_decode($row['frequency']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getGrades") {
        $output = Array();
        $sql = "SELECT * FROM grade";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getTests") {
        $output = Array();
        $sql = "SELECT * FROM test";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM subtest WHERE test='".$row["test"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["subtests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveFreq") {
        $sql = "update water_point set frequency='".json_encode($input["freq"])."' where id='".$input['id']."'";
        if ($conn->query($sql)) {
            $conn->query("INSERT INTO water_testing_workflow (schedule_id, sampling_no, stage_name, action_name, action_by, action_on, remark, payload_json)
                          SELECT id, sampling_no, 'Testing', 'Completed', '".$_GET["emp_id"]."', '".$entry_date."', '".$remark."', '".mysqli_real_escape_string($conn, $tests_json)."'
                          FROM water_frequency_schedule WHERE id='".$schedule_id."' LIMIT 1");
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        
      } 
    else if ($_GET["type"] == "sendForSampling") {
        $id = (int)$_GET["id"];
        $sql = "UPDATE water_frequency_schedule 
                SET sampling_requested_by = '".$_GET["emp_id"]."',
                    sampling_requested_on = '".$entry_date."',
                    sampling_status = 'Requested'
                WHERE id='".$id."' AND status='Pending'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
      }
    else if ($_GET["type"] == "saveFreq1") {
 
        $sql = "update water_point set frequency='".json_encode($input["freq"])."' where id='".$input['id']."'";
        if ($conn->query($sql)) {
            $insp_array = $input["freq"];
            $sql1='';
            $sql1 = "insert into water_frequency_schedule(water_point_id,due_date,due_type,frequency,entry_by,entry_date,status,sampling_status) values";
            foreach ($insp_array as $value) {
                // echo $value['particular'];
                if(trim($value['particular']) =='Daily'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<730;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 1 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Frequency','Daily','".$_GET["emp_id"]."','$entry_date','Pending','Pending'),";
                    }
                }else if(trim($value['particular']) =='Weekly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<104;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 7 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Frequency','Weekly','".$_GET["emp_id"]."','$entry_date','Pending','Pending'),";
                    }
                }else if(trim($value['particular']) =='FortNightly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<52;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 15 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Frequency','Daily','".$_GET["emp_id"]."','$entry_date','Pending','Pending'),";
                    }
                }else if(trim($value['particular']) =='Monthly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<24;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 30 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Frequency','Monthly','".$_GET["emp_id"]."','$entry_date','Pending','Pending'),";
                    }
                }else if(trim($value['particular']) =='Quarterly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<8;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 90 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Frequency','Quarterly','".$_GET["emp_id"]."','$entry_date','Pending','Pending'),";
                    }
                }else if(trim($value['particular']) =='Half-Yearly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<4;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 180 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Frequency','Half-Yearly','".$_GET["emp_id"]."','$entry_date','Pending','Pending'),";
                    }
                }else if(trim($value['particular']) =='Annually'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<2;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 364 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Frequency','Annually','".$_GET["emp_id"]."','$entry_date','Pending','Pending'),";
                    }
                }
            }
          
           
             $sql1= rtrim($sql1, ',');
           // echo $sql1;
            $conn->query($sql1);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        
      } 
      else if($_GET["type"] == "get_monthly_schedule") {
        $output = Array();
           $sql = "SELECT a.*,b.point_no   ,b.point_name   ,b.department,b.section,
                         DATEDIFF(a.due_date, CURDATE()) AS remaining_days

         FROM  water_frequency_schedule a JOIN water_point b on a.water_point_id= b.id WHERE month(due_date)='".$_GET["month"]."' and year(due_date)='".$_GET["year"]."'
         and a.due_type =   '".$_GET["due_type"]."'  order by due_date"; 
        $result = $conn->query($sql);
    
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 
                $row['checklist'] = json_decode($row['checklist']);
                
                if (isset($row['intimation_data'])) {
                    $row['intimation_data'] = json_decode($row['intimation_data'], true);
                } else {
                    $row['intimation_data'] = [];
                }
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if ($_GET["type"] == "saveSpecification") {
        $sql = "INSERT INTO water_specification (water_type, grade, version_no, supersede_no, storage, 
        review_date, sample_qty, tests, revisions, entry_by, entry_date) VALUES ('".$input["water_type"]."', '".$input["grade"]."', '".$input["version_no"]."', 
        '".$input["supersede_no"]."', '".$input["storage"]."', '".$input["review_date"]."', '".$input["sample_qty"]."', '".json_encode($input["tests"])."', 
        '".json_encode($input["revisions"])."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $conn->query("INSERT INTO water_testing_workflow (schedule_id, sampling_no, stage_name, action_name, action_by, action_on, remark)
                          SELECT id, sampling_no, 'Checking', '".($status == "approve" ? "Approved" : "Rejected")."', '".$_GET["emp_id"]."', '".$entry_date."', '".$remark."'
                          FROM water_frequency_schedule WHERE id='".$schedule_id."' LIMIT 1");
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        
      } 
      else if ($_GET['type'] == "getSpecifications") {
        $output = Array();
        $sql = "SELECT * FROM water_specification";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["tests"] = json_decode($row["tests"]);
                $row["revisions"] = json_decode($row["revisions"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
      } else if ($_GET['type'] == "getSpecifications") {
         $output = Array();
            $sql = "SELECT * FROM test WHERE test_type='".$_GET["test_type"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = array();
                    $sql1 = "SELECT * FROM subtest WHERE test_type='".$_GET["test_type"]."' ";
                    //AND test='".$row["test"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["subtests"] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        
        
        
        
    } else if ($_GET["type"] == "allocateSampling") {
        $sql = "INSERT INTO water_sampling (sampling_person, entry_by entry_date) VALUES ('', '', '')";
    }
    
    
    
    
    else if ($_GET["type"] == "savePlan") {
        $sql = "INSERT INTO water_sampling_plan (point_no, location, testing_type, frequency, entry_by, entry_date) 
        VALUES ('".$input["point_no"]."', '".$input["sampling_location"]."', '".$input["testing_type"]."', '".$input["frequency"]."', 
        '".$_GET["emp_id"]."', '$entry_date')";
        
        
        if ($conn->query($sql)) {
            $conn->query("INSERT INTO water_testing_workflow (schedule_id, sampling_no, stage_name, action_name, action_by, action_on, remark)
                          SELECT id, sampling_no, 'Approval', '".($status == "approve" ? "Approved" : "Rejected")."', '".$_GET["emp_id"]."', '".$entry_date."', '".$remark."'
                          FROM water_frequency_schedule WHERE id='".$schedule_id."' LIMIT 1");
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    
    
    else if ($_GET["type"] == "saveTestingReport") {
        $schedule_id = (int)($input["id"] ?? 0);
        $tests_json = json_encode($input["tests"] ?? array());
        $remark = mysqli_real_escape_string($conn, $input["remark"] ?? "");

        $sql = "UPDATE water_frequency_schedule SET 
                    sampling_tests = '".$tests_json."',
                    testing_remark = '".$remark."',
                    testing_status = 'Completed',
                    checking_status = 'Pending',
                    sampling_status = 'Tested',
                    tested_by = '".$_GET["emp_id"]."',
                    tested_on = '".$entry_date."'
                WHERE id = '".$schedule_id."' LIMIT 1";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    
    
    
    else if ($_GET["type"] == "getSamplingPlans") {
        $output = Array();
        $sql = "SELECT * FROM water_sampling_plan";
        echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingSamplingAllocations") {
        $output = Array();
        $sql = "SELECT 
                    s.id,
                    s.water_point_id,
                    s.frequency,
                    s.due_date,
                    s.sampling_status,
                    p.department,
                    p.water_type,
                    p.point_no,
                    p.point_name,
                    p.testing_type
                FROM water_frequency_schedule s
                JOIN water_point p ON p.id = s.water_point_id
                WHERE s.sampling_status='Requested'
                ORDER BY s.due_date ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingSamplings") {
        $output = array();
        $sql = "SELECT 
                    s.id as schedule_id,
                    s.water_point_id,
                    s.frequency as schedule_frequency,
                    s.due_date,
                    s.sampling_allocated_to,
                    p.id,
                    p.point_no,
                    p.point_name,
                    p.water_type,
                    p.testing_type,
                    p.department,
                    p.section,
                    p.is_RDS
                FROM water_frequency_schedule s
                JOIN water_point p ON p.id = s.water_point_id
                WHERE s.sampling_status='Allocated'
                  AND (s.sampling_allocated_to='".$_GET["emp_id"]."' OR s.sampling_allocated_to IS NULL OR s.sampling_allocated_to='')
                ORDER BY s.due_date ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["isspecification"] = "no";
                $row["tests"] = array();

                // Use latest water specification JSON tests for this water type
                $sql1 = "SELECT tests FROM water_specification WHERE LOWER(water_type)=LOWER('".$row["water_type"]."') ORDER BY id DESC LIMIT 1";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    $row1 = $result1->fetch_assoc();
                    $decoded_tests = json_decode($row1["tests"], true);
                    if (is_array($decoded_tests) && count($decoded_tests) > 0) {
                        $row["isspecification"] = "yes";
                        $row["tests"] = $decoded_tests;
                    }
                }
                $row["frequency"] = $row["schedule_frequency"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    }
    else if ($_GET["type"] == "getPendingTestings") {
        $output = Array();
        $sql = "SELECT 
                    s.id,
                    s.water_point_id,
                    s.sampling_no,
                    s.frequency,
                    s.due_date,
                    s.chemical_qty,
                    s.microbiology_qty,
                    s.sampling_unit as unit,
                    s.sampled_by as sampling_by,
                    s.sampled_on as sampling_date,
                    s.testing_status,
                    s.checking_status,
                    s.approval_status,
                    s.sampling_tests,
                    p.point_no,
                    p.point_name,
                    p.department,
                    p.section,
                    p.water_type,
                    p.testing_type,
                    p.is_RDS
                FROM water_frequency_schedule s
                JOIN water_point p ON p.id = s.water_point_id
                WHERE s.sampling_status='Sampled'
                  AND IFNULL(s.testing_status,'Pending')='Pending'
                  AND IFNULL(p.is_RDS,'0')='0'
                ORDER BY s.sampled_on DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Priority: tests selected during sampling are stored on schedule table
                $stored_tests = json_decode($row["sampling_tests"], true);
                if (is_array($stored_tests) && count($stored_tests) > 0) {
                    $row["isspecification"] = "yes";
                    $row["tests"] = $stored_tests;
                } else {
                    // Fallback to latest water specification JSON tests
                    $row["isspecification"] = "no";
                    $row["tests"] = array();
                    $sql1 = "SELECT tests FROM water_specification WHERE LOWER(water_type)=LOWER('".$row["water_type"]."') ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1 && $result1->num_rows > 0) {
                        $row1 = $result1->fetch_assoc();
                        $decoded_tests = json_decode($row1["tests"], true);
                        if (is_array($decoded_tests) && count($decoded_tests) > 0) {
                            $row["isspecification"] = "yes";
                            $row["tests"] = $decoded_tests;
                        }
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTestingChecking") {
        $output = Array();
        $sql = "SELECT 
                    s.id,
                    s.water_point_id,
                    s.sampling_no,
                    s.frequency,
                    s.due_date,
                    s.sampled_by as sampling_by,
                    s.sampled_on,
                    s.testing_remark,
                    s.sampling_tests,
                    s.tested_by,
                    s.tested_on,
                    s.testing_status,
                    s.checking_status,
                    p.point_no,
                    p.point_name,
                    p.water_type,
                    p.testing_type
                FROM water_frequency_schedule s
                JOIN water_point p ON p.id = s.water_point_id
                WHERE IFNULL(s.testing_status,'Pending')='Completed'
                  AND IFNULL(s.checking_status,'Pending')='Pending'
                ORDER BY s.tested_on DESC, s.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["tests"] = array();
                $stored_tests = json_decode($row["sampling_tests"], true);
                if (is_array($stored_tests)) {
                    $row["tests"] = $stored_tests;
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "checkTesting") {
        $schedule_id = (int)($_GET["id"] ?? 0);
        $status = strtolower(trim($_GET["status"] ?? ""));
        $remark = mysqli_real_escape_string($conn, $_GET["remark"] ?? "");
        if ($schedule_id <= 0 || ($status != "approve" && $status != "reject")) {
            echo "{\"status\":\"failed\"}";
            exit;
        }

        if ($status == "approve") {
            $sql = "UPDATE water_frequency_schedule SET
                        checking_status = 'Approved',
                        approval_status = 'Pending',
                        check_by = '".$_GET["emp_id"]."',
                        check_on = '".$entry_date."',
                        checking_remark = '".$remark."'
                    WHERE id = '".$schedule_id."' LIMIT 1";
        } else {
            $sql = "UPDATE water_frequency_schedule SET
                        checking_status = 'Rejected',
                        approval_status = NULL,
                        testing_status = 'Pending',
                        sampling_status = 'Sampled',
                        check_by = '".$_GET["emp_id"]."',
                        check_on = '".$entry_date."',
                        checking_remark = '".$remark."'
                    WHERE id = '".$schedule_id."' LIMIT 1";
        }

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if ($_GET["type"] == "getTestingApproval") {
        $output = Array();
        $sql = "SELECT 
                    s.id,
                    s.water_point_id,
                    s.sampling_no,
                    s.frequency,
                    s.due_date,
                    s.sampling_tests,
                    s.testing_remark,
                    s.checking_remark,
                    s.check_by,
                    s.check_on,
                    p.point_no,
                    p.point_name,
                    p.water_type,
                    p.testing_type
                FROM water_frequency_schedule s
                JOIN water_point p ON p.id = s.water_point_id
                WHERE IFNULL(s.checking_status,'Pending')='Approved'
                  AND IFNULL(s.approval_status,'Pending')='Pending'
                ORDER BY s.check_on DESC, s.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["tests"] = array();
                $stored_tests = json_decode($row["sampling_tests"], true);
                if (is_array($stored_tests)) {
                    $row["tests"] = $stored_tests;
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "approveTesting") {
        $schedule_id = (int)($_GET["id"] ?? 0);
        $status = strtolower(trim($_GET["status"] ?? ""));
        $remark = mysqli_real_escape_string($conn, urldecode($_GET["remark"] ?? ""));
        if ($schedule_id <= 0 || ($status != "approve" && $status != "reject")) {
            echo "{\"status\":\"failed\"}";
            exit;
        }

        if ($status == "approve") {
            $sql = "UPDATE water_frequency_schedule SET
                        approval_status = 'Approved',
                        approved_by = '".$_GET["emp_id"]."',
                        approved_on = '".$entry_date."',
                        approval_remark = '".$remark."',
                        testing_status = 'Approved',
                        sampling_status = 'Closed',
                        status = 'Completed'
                    WHERE id = '".$schedule_id."' LIMIT 1";
        } else {
            $sql = "UPDATE water_frequency_schedule SET
                        approval_status = 'Rejected',
                        checking_status = 'Pending',
                        approved_by = '".$_GET["emp_id"]."',
                        approved_on = '".$entry_date."',
                        approval_remark = '".$remark."'
                    WHERE id = '".$schedule_id."' LIMIT 1";
        }

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if ($_GET["type"] == "getTestingLog") {
        $output = Array();
        $sql = "SELECT 
                    s.id,
                    s.water_point_id,
                    s.sampling_no,
                    s.frequency,
                    s.due_date,
                    s.sampling_tests,
                    s.testing_remark,
                    s.checking_remark,
                    s.approval_remark,
                    s.approved_by,
                    s.approved_on,
                    p.point_no,
                    p.point_name,
                    p.water_type,
                    p.testing_type
                FROM water_frequency_schedule s
                JOIN water_point p ON p.id = s.water_point_id
                WHERE IFNULL(s.approval_status,'')='Approved'
                ORDER BY s.approved_on DESC, s.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["tests"] = array();
                $stored_tests = json_decode($row["sampling_tests"], true);
                if (is_array($stored_tests)) {
                    $row["tests"] = $stored_tests;
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getActiveTestings") {
        // Backward compatible alias for checking queue
        $output = Array();
        $sql = "SELECT 
                    s.id,
                    s.water_point_id,
                    s.sampling_no,
                    s.frequency,
                    s.sampled_by as sampling_by,
                    s.sampling_tests,
                    s.testing_remark,
                    p.point_no,
                    p.point_name,
                    p.water_type,
                    p.testing_type
                FROM water_frequency_schedule s
                JOIN water_point p ON p.id = s.water_point_id
                WHERE IFNULL(s.testing_status,'Pending')='Completed'
                  AND IFNULL(s.checking_status,'Pending')='Pending'
                ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["tests"] = json_decode($row["sampling_tests"], true);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveSampling") {
        $sql1="SELECT count(*)+1 as id FROM water_frequency_schedule where sampling_no!=''";
        $result1 = $conn->query($sql1);
        $row1 = $result1->fetch_assoc();
        $last_id=$row1["id"];
        $number = substr(str_repeat(0, 4).$last_id, - 4);
        
        $sampling_no = "SM".$number;
        $water_point_id = isset($input["water_point_id"]) ? (int)$input["water_point_id"] : 0;
        $schedule_id = isset($input["schedule_id"]) ? (int)$input["schedule_id"] : 0;

        if ($schedule_id <= 0) {
            echo "{\"status\":\"failed\"}";
        } else {
            $tests_json = json_encode($input["tests"]);
            $bottle_json = json_encode($input["lists"]);
            $sql = "UPDATE water_frequency_schedule SET 
                        sampling_no = '".$sampling_no."',
                        bottle_list = '".$bottle_json."',
                        sampling_tests = '".$tests_json."',
                        microbiology_qty = '".$input["microbiology_qty"]."',
                        chemical_qty = '".$input["chemical_qty"]."',
                        sampling_unit = '".$input["unit"]."',
                        sampled_by = '".$_GET["emp_id"]."',
                        sampled_on = '".$entry_date."',
                        sampling_status = 'Sampled',
                        testing_status = 'Pending',
                        status = 'Completed'
                    WHERE id='".$schedule_id."'";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        }
    }
    else if ($_GET["type"] == "getQcPersons") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE department='Quality Control'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getSamplingPlan") {
        
        
$output = array();
$sql = "SELECT * FROM water_point ORDER BY id DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Get the current timestamp
    $timestamp = time();
    // Calculate the number of days in the current month
    $d = cal_days_in_month(CAL_GREGORIAN, date("m", $timestamp), date("Y", $timestamp));

    while ($row = $result->fetch_assoc()) {
        $temp = array();
        $temp["point_no"] = $row["point_no"];
        $temp["point_name"] = $row["point_name"];

        for ($i = 1; $i <= $d; $i++) {
            // Format the date correctly for each day of the month
            $date = date("Y-m-$i", $timestamp);

            // Check the frequency and assign "Y" if the condition matches
            if ($row["frequency"] == "once in a week" && date('N', strtotime($date)) == $row["day"]) {
                $temp[$i] = "Y";
            } else if ($row["frequency"] == "once in a month" && date("j", strtotime($date)) == $row["day"]) {
                $temp[$i] = "Y";
            } else if ($row["frequency"] == "everyday") {
                $temp[$i] = "Y";
            } else {
                $temp[$i] = "";
            }
        }
        $output[] = $temp;
    }
}

echo json_encode($output);


    }
    // else if ($_GET["type"] == "getSamplingPlan") {
    //     $output = array();
    //     $sql = "SELECT * FROM water_point order by id desc";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $temp = array();
    //             $temp["point_no"] = $row["point_no"];
    //             $temp["point_name"] = $row["point_name"];
    //             $d=cal_days_in_month(CAL_GREGORIAN,date("m", $timestamp),date("Y", $timestamp));
    //             for ($i = 1; $i <= $d; $i++) {
    //                 $date = date("Y-m-".$i, $timestamp);
    //                 if ($row["frequency"] == "once in a week" && date('N', strtotime($date)) == +$row["day"]) {
    //                     $temp[$i] = "Y";
    //                 } else if ($row["frequency"] == "once in a month" && date("Y-m-".$i, $timestamp) == date("Y-m-".$row["day"], $timestamp)) {
    //                     $temp[$i] = "Y";
    //                 } else if ($row["frequency"] == "everyday") {
    //                     $temp[$i] = "Y";
    //                 } else {
    //                     $temp[$i] = "";
    //                 }
    //             }
    //             $output[] = $temp;
    //         }
    //     }
    //     echo json_encode($output);
        
    // }
    else if ($_GET["type"] == "downloadPointsLog") {
        $_GET['filename'] = 'Water Points Log'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
        $html= "";
        $html.='
        <h2 style="text-align:center">Water Points Log</h2>
        <table cellpadding="1" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr</b></td>
                <td style="width:10%; text-align:centre;"><b>Point Name</b></td>
                <td style="width:10%; text-align:centre;"><b>Point_no</b></td>
                <td style="width:10%; text-align:centre;"><b>Water type</b></td>
                <td style="width:10%; text-align:centre;"><b>Frequency </b></td>
                <td style="width:10%; text-align:centre;"><b>day </b></td>
                <td style="width:10%; text-align:centre;"><b>testing type </b></td>
                <td style="width:10%; text-align:centre;"><b>Department </b></td>
                 <td style="width:20%; text-align:centre;"><b>Section </b></td>
            </tr>';
            $i=1;
            $sql = "SELECT * FROM water_point";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr>
                    <td style="width:10%;">'.$i.'</td>
                    <td style="width:10%;">'.$row['point_name'].'</td>
                    <td style="width:10%;">'.$row['point_no'].'</td>
                    <td style="width:10%;">'.$row['water_type'].'</td>
                    <td style="width:10%;">'.$row['frequency'].'</td>
                    <td style="width:10%;">'.$row['day'].'</td>
                    <td style="width:10%;">'.$row['testing_type'].'</td>
                    <td style="width:10%;">'.$row['department'].'</td>
                    <td style="width:20%;">'.$row['section'].'</td>
                </tr>';
                $i++;
                }
            }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('NCR.pdf', 'I');
        }else if ($_GET["type"] == "downloadTestingARLog") {
        $_GET['filename'] = 'Water Testing AR Report'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
        $html= "";
        $html.='
        <h2 style="syle="text-align:center">Water Testing AR Report</h2>
       <table cellpadding="5" border="1">
               
            <tr>
                <td style="width:5%; text-align:centre;"><b>Sr</b></td>
                <td style="width:5%; text-align:centre;"><b>Medicap lot no</b></td>
                <td style="width:10%; text-align:centre;"><b>Sampling No</b></td>
                <td style="width:10%; text-align:centre;"><b>Point No</b></td>
                <td style="width:10%; text-align:centre;"><b>Water Type </b></td>
                <td style="width:10%; text-align:centre;"><b>Point Name </b></td>
                <td style="width:10%; text-align:centre;"><b>Testing Type </b></td>
                <td style="width:10%; text-align:centre;"><b>Frequency </b></td>
                <td style="width:10%; text-align:centre;"><b>Sampling By </b></td>
                <td style="width:10%; text-align:centre;"><b>Sampling qty</b></td>
                <td style="width:10%; text-align:centre;"><b>Action </b></td>
            </tr>';
                $sql="SELECT s.*, m.media_name FROM media_stock s LEFT JOIN media m ON s.media_code=m.media_code WHERE DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='
                <tr>
                    <td style="width:5%;">'.$i.'</td>
                    <td style="width:5%;">'.$row['ar_no'].'</td>
                    <td style="width:10%;">'.$row['sampling_no'].'</td>
                    <td style="width:10%;">'.$row['point_no'].'</td>
                    <td style="width:10%;">'.$row['water_type'].'</td>
                    <td style="width:10%;">'.$row['point_name'].'</td>
                    <td style="width:10%;">'.$row['testing_type'].'</td>
                    <td style="width:10%;">'.$row['frequency'].'</td>
                    <td style="width:10%;">'.$row['sampling_by'].'</td>
                    <td style="width:10%;">'.$row['sampling_qty'].'</td>
                    <td style="width:10%;">'.$row['action'].'</td>
                </tr>';
                $i++;
                }
            }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('NCR.pdf', 'I');
        }else if ($_GET["type"] == "downloadTestingARReport") {
        $_GET['filename'] = 'Water Points Log'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
        $html= "";
        $html.='
        <h2 style="text-align:center">Water Points Log</h2>
       <style>td { border:solid 1px BCBBBA;}</style>
        <table cellpadding="2">
            <thead>
                <tr>
                    <td style="background-color:#DDDAD9; width:100%; text-align:center;"><b>A. R. Report</b></td>
                </tr>
            <tr>
                <td style="width:5%; text-align:centre;"><b>Sr</b></td>
                <td style="width:5%; text-align:centre;"><b>Medicap lot no</b></td>
                <td style="width:10%; text-align:centre;"><b>Sampling No</b></td>
                <td style="width:10%; text-align:centre;"><b>Point No</b></td>
                <td style="width:10%; text-align:centre;"><b>Water Type </b></td>
                <td style="width:10%; text-align:centre;"><b>Point Name </b></td>
                <td style="width:10%; text-align:centre;"><b>Testing Type </b></td>
                <td style="width:10%; text-align:centre;"><b>Frequency </b></td>
                 <td style="width:10%; text-align:centre;"><b>Sampling By </b></td>
                 <td style="width:10%; text-align:centre;"><b>Sampling </b></td>
                 <td style="width:10%; text-align:centre;"><b>Action </b></td>
                </tr>
            </thead>
            <tbody>';
            $sql = "SELECT * FROM water_point WHERE status='approve'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $i=1;
                while ($row = $result->fetch_assoc()) {
                $html.='<tr>
                    <td style="width:5%;">'.$i.'</td>
                    <td style="width:5%;">'.$row['ar_no'].'</td>
                    
                    <td style="width:10%;">'.$row['sampling_no'].'</td>
                    <td style="width:10%;">'.$row['point_no'].'</td>
                    <td style="width:10%;">'.$row['water_type'].'</td>
                    <td style="width:10%;">'.$row['point_name'].'</td>
                    <td style="width:10%;">'.$row['testing_type'].'</td>
                    <td style="width:10%;">'.$row['frequency'].'</td>
                     <td style="width:10%;">'.$row['sampling_by'].'</td>
                      <td style="width:10%;">'.$row['sampling'].'</td>
                       <td style="width:10%;">'.$row['action'].'</td>
                </tr>';
                $i++;
                }
            }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('NCR.pdf', 'I');
        }else if ($_GET["type"] == "downloadTestingCOALog") {
        $_GET['filename'] = 'Water Testing COA Report'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
        $html= "";
        $html.='
        <h2 style="text-align:center">Water Testing COA Report</h2>
        <table cellpadding="2" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr</b></td>
                <td style="width:10%; text-align:centre;"><b>Medicap lot no</b></td>
                <td style="width:10%; text-align:centre;"><b>Sampling No</b></td>
                <td style="width:10%; text-align:centre;"><b>Point No</b></td>
                <td style="width:10%; text-align:centre;"><b>Water Type </b></td>
                <td style="width:10%; text-align:centre;"><b>Point Name </b></td>
                <td style="width:10%; text-align:centre;"><b>Testing Type </b></td>
                <td style="width:10%; text-align:centre;"><b>Frequency </b></td>
                <td style="width:10%; text-align:centre;"><b>Sampling By </b></td>
                <td style="width:10%; text-align:centre;"><b>Sampling Qty</b></td>
            </tr>';
                $html.='<tr>
                    <td style="width:10%;">'.$i.'</td>
                    <td style="width:10%;">'.$row['ar_no'].'</td>
                    <td style="width:10%;">'.$row['sampling_no'].'</td>
                    <td style="width:10%;">'.$row['point_no'].'</td>
                    <td style="width:10%;">'.$row['water_type'].'</td>
                    <td style="width:10%;">'.$row['point_name'].'</td>
                    <td style="width:10%;">'.$row['testing_type'].'</td>
                    <td style="width:10%;">'.$row['frequency'].'</td>
                    <td style="width:10%;">'.$row['sampling_by'].'</td>
                    <td style="width:10%;">'.$row['sampling'].'</td>
                </tr>';
                $i++;
                
            
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('NCR.pdf', 'I');
    }else if ($_GET["type"] == "downloadSamplingPlan") {
        $_GET['filename'] = 'Water Sampling Plan'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
        $html= "";
        $html.='
        <h2 style="text-align:center">Water Sampling Plan</h2>
        <table cellpadding="2" border="1">
                
                <tr>
                    <td style="width:5%; text-align:centre;"><b>Point No.</b></td>
                    <td style="width:10%; text-align:centre;"><b>Point Name</b></td>
                    <td style="width:2%; text-align:centre;"><b>1</b></td>
                    <td style="width:2%; text-align:centre;"><b>2</b></td>
                    <td style="width:2%; text-align:centre;"><b>3</b></td>
                    <td style="width:2%; text-align:centre;"><b>4</b></td>
                    <td style="width:2%; text-align:centre;"><b>5</b></td>
                    <td style="width:2%; text-align:centre;"><b>6</b></td>
                    <td style="width:2%; text-align:centre;"><b>7</b></td>
                    <td style="width:2%; text-align:centre;"><b>8</b></td>
                    <td style="width:3%; text-align:centre;"><b>9</b></td>
                    <td style="width:3%; text-align:centre;"><b>10</b></td>
                    <td style="width:3%; text-align:centre;"><b>11</b></td>
                    <td style="width:3%; text-align:centre;"><b>12</b></td>
                    <td style="width:3%; text-align:centre;"><b>13</b></td>
                    <td style="width:3%; text-align:centre;"><b>14</b></td>
                    <td style="width:3%; text-align:centre;"><b>15</b></td>
                    <td style="width:3%; text-align:centre;"><b>16</b></td>
                    <td style="width:3%; text-align:centre;"><b>17</b></td>
                    <td style="width:3%; text-align:centre;"><b>18</b></td>
                    <td style="width:3%; text-align:centre;"><b>19</b></td>
                    <td style="width:3%; text-align:centre;"><b>20</b></td>
                    <td style="width:3%; text-align:centre;"><b>21</b></td>
                    <td style="width:3%; text-align:centre;"><b>22</b></td>
                    <td style="width:3%; text-align:centre;"><b>23</b></td>
                    <td style="width:3%; text-align:centre;"><b>24</b></td>
                    <td style="width:3%; text-align:centre;"><b>25</b></td>
                    <td style="width:3%; text-align:centre;"><b>26</b></td>
                    <td style="width:3%; text-align:centre;"><b>27</b></td>
                    <td style="width:3%; text-align:centre;"><b>28</b></td>
                    <td style="width:3%; text-align:centre;"><b>29</b></td>
                    <td style="width:3%; text-align:centre;"><b>30</b></td>
                    <td style="width:3%; text-align:centre;"><b>31</b></td>
                </tr>';
            $sql = "SELECT * FROM water_point WHERE status='approve'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
                $temp = array();
                $temp["point_no"] = $row["point_no"];
                $temp["point_name"] = $row["point_name"];
                $d=cal_days_in_month(CAL_GREGORIAN,date("m", $timestamp),date("Y", $timestamp));
                for ($i = 1; $i <= $d; $i++) {
                    $date = date("Y-m-".$i, $timestamp);
                    if ($row["frequency"] == "once in a week" && date('N', strtotime($date)) == +$row["day"]) {
                        $temp[$i] = "Y";
                    } else if ($row["frequency"] == "once in a month" && date("Y-m-".$i, $timestamp) == date("Y-m-".$row["day"], $timestamp)) {
                        $temp[$i] = "Y";
                    } else if ($row["frequency"] == "everyday") {
                        $temp[$i] = "Y";
                    } else {
                        $temp[$i] = "";
                    }
                }
                $output[] = $temp;
                $html.='<tr>
                            <td style="width:5%;">'.$row['point_no'].'</td>
                            <td style="width:10%;">'.$row['point_name'].'</td>
                            <td style="width:2%;">'.$temp[1].'</td>
                            <td style="width:2%;">'.$temp[2].'</td>
                            <td style="width:2%;">'.$temp[3].'</td>
                            <td style="width:2%;">'.$temp[4].'</td>
                            <td style="width:2%;">'.$temp[5].'</td>
                            <td style="width:2%;">'.$temp[6].'</td>
                            <td style="width:2%;">'.$temp[7].'</td>
                            <td style="width:2%;">'.$temp[8].'</td>
                            <td style="width:3%;">'.$temp[9].'</td>
                            <td style="width:3%;">'.$temp[10].'</td>
                            <td style="width:3%;">'.$temp[11].'</td>
                            <td style="width:3%;">'.$temp[12].'</td>
                            <td style="width:3%;">'.$temp[13].'</td>
                            <td style="width:3%;">'.$temp[14].'</td>
                            <td style="width:3%;">'.$temp[15].'</td>
                            <td style="width:3%;">'.$temp[16].'</td>
                            <td style="width:3%;">'.$temp[17].'</td>
                            <td style="width:3%;">'.$temp[18].'</td>
                            <td style="width:3%;">'.$temp[19].'</td>
                            <td style="width:3%;">'.$temp[20].'</td>
                            <td style="width:3%;">'.$temp[21].'</td>
                            <td style="width:3%;">'.$temp[22].'</td>
                            <td style="width:3%;">'.$temp[23].'</td>
                            <td style="width:3%;">'.$temp[24].'</td>
                            <td style="width:3%;">'.$temp[25].'</td>
                            <td style="width:3%;">'.$temp[26].'</td>
                            <td style="width:3%;">'.$temp[27].'</td>
                            <td style="width:3%;">'.$temp[28].'</td>
                            <td style="width:3%;">'.$temp[29].'</td>
                            <td style="width:3%;">'.$temp[30].' </td>
                            <td style="width:3%;">'.$temp[31].'</td>
                        </tr>';
                    $i++;
                }
            }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('SamplingPlan.pdf', 'I');
    }
}
$conn->close();
 }catch(Error $e){
    echo 'Error writing to database: ',  $e->getMessage(), "\n";
  }
?>