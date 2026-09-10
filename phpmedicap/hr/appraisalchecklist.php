<?php



ini_set('display_errors', 1);
error_reporting(E_ALL);



require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set("Asia/Kolkata");
$input = json_decode(file_get_contents('php://input'), true);

$output = array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='" . $_GET["token"] . "'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }

    $txt = '{"process": "FRONTEND", "token": "' . $token . '", "action": "' . $_GET["type"] . '", "actiontime": "' . $entry_date . '", "department": "' . $_GET["department"] . '", "emp_id": "' . $_GET["emp_id"] . '", "method": "' . $_SERVER['REQUEST_METHOD'] . '", "REMOTE_ADDR": "' . $_SERVER['REMOTE_ADDR'] . '"}';
    $myfile = file_put_contents('../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

    if ($_GET["type"] ==  "saveChecklist") {
        
        $sql = "INSERT INTO appraisal_checklist_master(plant_id,department,designation,appraisal_type,checklist_heading,created_at,updated_at) VALUES('".$_GET["plant_id"]."','". $input["department"] . "','" . $input["designation"] . "','" . $input["appraisal_type"] . "','" . $input["checklist_heading"] . "','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "')";
        if ($conn->query($sql)) {
            $checkListId = $conn->insert_id;
            foreach ($input['details'] as $detail) {
                if(empty($detail['goal_type']))
                {
                    $detail['goal_type'] = null;
                }
                $checklistDetailSql = 'INSERT INTO appraisal_checklist_details (checklist_master_id,goal_type,checkpoint_particular,description,evualation_parameter,evualation_type,created_at,updated_at) VALUES (' . $checkListId . ',"'.$input['goal_type'].'", "' . $detail['checklist_particulars'] . '", "' . $detail['description'] . '", "' . $detail['evualation_parameter'] . '", "' . $detail['evualation_type'] . '","' . date('Y-m-d H:i:s') . '","' . date('Y-m-d H:i:s') . '")';
           
            }

            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
   
    
   else if ($_GET["type"] == "getChecklist") {
        $output = Array();
        $sql = "SELECT * FROM appraisal_checklist_master";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    
    else if ($_GET["type"] == "getEmployeesByDepartment") {
        $output = Array();
        $sql = "SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName,emp_id,department,designation FROM employee where department = '".$_GET['deptName']."'
         AND plant_id = '".$_GET['plant_id']."' ORDER BY id DESC";
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    
    else if ($_GET["type"] == "getAppraisalActiveForm") {
        $outputMain = Array();
        $sql1 = "SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName From employee e WHERE e.emp_id = a.emp_id) as empName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName From employee e WHERE e.emp_id = a.manager) as managerName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName From employee e WHERE e.emp_id = a.peer) as peerName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName From employee e WHERE e.emp_id = a.subordinate) as subordinateName
        FROM appraisalform a where a.plant_id = '".$_GET['plant_id']."' AND a.status = 'Active' ORDER BY a.id DESC";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($rowMain = $result1->fetch_assoc()) {
                 
                
            $output = array();
            
            $sql = "SELECT 
                k.kra,
                r.employeeId,r.mappedKraId,
                kraM.weightage AS kra_weightage,
            
                ROUND(AVG(CASE 
                    WHEN r.responseType = 'rating' THEN CAST(r.responseValue AS DECIMAL)
                    ELSE NULL 
                END), 2) AS avg_rating,
            
                ROUND((AVG(CASE 
                    WHEN r.responseType = 'rating' THEN CAST(r.responseValue AS DECIMAL)
                    ELSE NULL 
                END) / 5) * kraM.weightage, 2) AS kra_score,
            
                ROUND(SUM(CASE 
                    WHEN r.responseType = 'yesno' AND r.responseValue = 'Yes' THEN 1 
                    ELSE 0 
                END) / NULLIF(SUM(CASE WHEN r.responseType = 'yesno' THEN 1 ELSE 0 END), 0) * 100, 2) AS yes_percentage
            
            FROM 
                reviewResponse r
            JOIN 
                kpiMaster k ON r.kpiId = k.id
            JOIN 
                mappedKpi kraM ON k.kpiGroupId = kraM.kraId  where kraM.selfStatus = 'Done' AND kraM.managerStatus = 'Done' 
                AND kraM.peerStatus = 'Done' AND kraM.subordinateStatus = 'Done' AND kraM.plant_id = '".$_GET['plant_id']."'  AND r.employeeId = '".$rowMain['emp_id']."'
            GROUP BY 
                k.kra, kraM.weightage, r.employeeId,r.mappedKraId";
            
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    
                $response = Array();
                $sqlr = "SELECT a.*,b.question,
                (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS reviewerName From employee e WHERE e.emp_id = a.reviewerId) as reviewerName
                FROM reviewResponse a left join kpiMaster b ON a.kpiId = b.id where a.mappedKraId = '".$row['mappedKraId']."'";
                $resultr = $conn->query($sqlr);
                if ($resultr->num_rows > 0) {
                    while ($rowr = $resultr->fetch_assoc()) {
                        $response[] = $rowr;
                    }
                }
                    
                    
                    
                    $row['response'] = $response;
                    $output[] = $row;
                }
            }
                 
                
                $rowMain['result'] = $output;
                $outputMain[] = $rowMain;
            }
        }
        echo json_encode($outputMain);
    } 
    
    
    
    else if ($_GET["type"] == "getReportOfAppraisalEmployee") {
            $output = array();
            
            $sql = "SELECT 
                k.kra,
                r.employeeId,
                kraM.weightage AS kra_weightage,
            
                ROUND(AVG(CASE 
                    WHEN r.responseType = 'rating' THEN CAST(r.responseValue AS DECIMAL)
                    ELSE NULL 
                END), 2) AS avg_rating,
            
                ROUND((AVG(CASE 
                    WHEN r.responseType = 'rating' THEN CAST(r.responseValue AS DECIMAL)
                    ELSE NULL 
                END) / 5) * kraM.weightage, 2) AS kra_score,
            
                ROUND(SUM(CASE 
                    WHEN r.responseType = 'yesno' AND r.responseValue = 'Yes' THEN 1 
                    ELSE 0 
                END) / NULLIF(SUM(CASE WHEN r.responseType = 'yesno' THEN 1 ELSE 0 END), 0) * 100, 2) AS yes_percentage,
            
                GROUP_CONCAT(CASE 
                    WHEN r.responseType = 'text' THEN CONCAT('[', r.reviewerType, ']: ', r.responseValue)
                    ELSE NULL 
                END SEPARATOR ' || ') AS feedback_comments
            
            FROM 
                reviewResponse r
            JOIN 
                kpiMaster k ON r.kpiId = k.id
            JOIN 
                mappedKpi kraM ON k.kpiGroupId = kraM.kraId  where kraM.selfStatus = 'Done' AND kraM.managerStatus = 'Done' 
                AND kraM.peerStatus = 'Done' AND kraM.subordinateStatus = 'Done' AND kraM.plant_id = '".$_GET['plant_id']."' 
            GROUP BY 
                k.kra, kraM.weightage, r.employeeId";
            
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    // Handle splitting feedback_comments
                    $feedback = array();
                    if (!empty($row['feedback_comments'])) {
                        $commentParts = explode(' || ', $row['feedback_comments']);
                        foreach ($commentParts as $comment) {
                            if (preg_match('/\[(.*?)\]: (.*)/', $comment, $matches)) {
                                $feedback[] = array(
                                    'reviewerType' => $matches[1],
                                    'comment' => $matches[2]
                                );
                            }
                        }
                    }
                    
                    $row['feedback_comments'] = $feedback; // Replace string with array
                    $output[] = $row;
                }
            }
            
            echo json_encode($output);

    } 
    else if ($_GET["type"] == "getReviewers") {
        
        $output = Array();
        $manager = Array();
        
        $sql1 = "SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName,emp_id,department,designation FROM employee where department = '".$_GET['deptName']."'
        AND designation LIKE '%manager%' AND plant_id = '".$_GET['plant_id']."' ORDER BY id DESC";
         
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $manager[] = $row1;
            }
        }
        
        $peer = Array();
        $sql2 = "SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName,emp_id,department,designation FROM employee where department = '".$_GET['deptName']."'
        AND designation = '".$_GET['designation']."' AND plant_id = '".$_GET['plant_id']."' ORDER BY id DESC";
         
        $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $peer[] = $row2;
            }
        }
        
        $output['managers'] = $manager;
        $output['peers'] = $peer;
        
        echo json_encode($output);
    } 
    
    
    
    else if ($_GET["type"] == "makeReviewResponse") {
        
        $check = true;
             
        foreach ($input['kpiList'] as $val) {
          
           $sql = "INSERT INTO `reviewResponse`(`plant_id`, `cycleId`, `appraisalId`, `mappedKraId`, `employeeId`, `reviewerId`, `kpiId`, `reviewerType`, 
            `responseType`, `responseValue`, `entryBy`, `entryOn`) VALUES  ('".$_GET["plant_id"]."','".$input["cycleId"]."',
            '".$input["appraisalId"]."','".$input["mappedKraId"]."','".$input["employeeId"]."','".$input["reviewerId"]."','".$val["id"]."','".$input["reviewerType"]."',
            '".$val["evaluationType"]."','".$val["responseValue"]."','".$_GET["emp_id"]."','" . date('Y-m-d H:i:s') . "')";      
                if ($conn->query($sql)) {
                    
                } else {
                    $check = false;
                }
        }
            
        if ($check) {
            echo "{\"status\":\"success\"}";  
            
           
            
                    if($input['reviewerType'] == 'self'){
                         $sql1 = "UPDATE mappedKpi SET selfStatus = 'Done'  WHERE id = '".$input["mappedKraId"]."' ";
                    }
                    else if($input['reviewerType'] == 'manager'){
                        $sql1 = "UPDATE mappedKpi SET managerStatus = 'Done' WHERE id = '".$input["mappedKraId"]."' ";
                    }
                    else if($input['reviewerType'] == 'peer'){
                        $sql1 = "UPDATE mappedKpi SET peerStatus = 'Done' WHERE id = '".$input["mappedKraId"]."'";
                    }
                    else if($input['reviewerType'] == 'subordinate'){
                        $sql1 = "UPDATE mappedKpi SET subordinateStatus = 'Done' WHERE id = '".$input["mappedKraId"]."'";
                    }
            
             $conn->query($sql1);
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    
    
   else if ($_GET["type"] == "getAppraisalForReview") { //reviewerId reviewType
        $output = Array();
         $sql = "SELECT a.*,b.cycleName,b.department,b.designation,
                    (SELECT kra From kpiMaster k WHERE k.kpiGroupId = a.kraId LIMIT 1) as kra,
                    (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName From employee e WHERE e.emp_id = a.emp_id) as empName,
                    (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName From employee e WHERE e.emp_id = a.manager) as managerName,
                    (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName From employee e WHERE e.emp_id = a.peer) as peerName,
                    (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName From employee e WHERE e.emp_id = a.subordinate) as subordinateName
            FROM mappedKpi a left join appraisalform b ON b.id = a.appraisalId where a.plant_id = '".$_GET['plant_id']."'  ";

        if($_GET['reviewType'] == 'self'){
             $sql .= " AND a.selfStatus = 'Pending' AND a.self = '".$_GET['reviewerId']."' ";
        }
        else if($_GET['reviewType'] == 'manager'){
            $sql .= " AND a.managerStatus = 'Pending' AND a.manager = '".$_GET['reviewerId']."' ";
        }
        else if($_GET['reviewType'] == 'peer'){
            $sql .= " AND a.peerStatus = 'Pending' AND a.peer = '".$_GET['reviewerId']."' ";
        }
        else if($_GET['reviewType'] == 'subordinate'){
            $sql .= " AND a.subordinateStatus = 'Pending' AND a.subordinate = '".$_GET['reviewerId']."' ";
        }

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                    $output1 = Array();
                    
                     $sql1 = "SELECT * FROM kpiMaster where plant_id = '".$_GET['plant_id']."' AND kpiGroupId = '".$row['kraId']."'";
                    
                    if($_GET['reviewType'] == 'self'){   $sql1 .= " AND self = 'Yes' "; }
                    else if($_GET['reviewType'] == 'manager'){ $sql1 .= " AND manager = 'Yes' "; }
                    else if($_GET['reviewType'] == 'peer'){ $sql1 .= " AND peer = 'Yes' "; }
                    else if($_GET['reviewType'] == 'subordinate'){  $sql1 .= " AND subordinate = 'Yes' "; }
 
                     $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1['responseValue'] ='';
                            $output1[] = $row1;
                        }
                    }
                
                $row['kpis'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
   else if ($_GET["type"] == "getAppraisalCycleList") {
        $output = Array();
        $sql = "SELECT * FROM appraisalCycle where plant_id = '".$_GET['plant_id']."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
   else if ($_GET["type"] == "getApprovedAppraisalCycle") {
        $output = Array();
        $sql = "SELECT * FROM appraisalCycle where status = 'Approved' AND plant_id = '".$_GET['plant_id']."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
   else if ($_GET["type"] == "getAppraisalForm") {
        $output = Array();
        $sql = "SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName From employee e WHERE e.emp_id = a.emp_id) as empName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName From employee e WHERE e.emp_id = a.manager) as managerName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName From employee e WHERE e.emp_id = a.peer) as peerName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName From employee e WHERE e.emp_id = a.subordinate) as subordinateName
        FROM appraisalform a where a.plant_id = '".$_GET['plant_id']."' ORDER BY a.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
 
    else if ($_GET["type"] == "changeAppraisalStatus") {
        
        $sql = "UPDATE appraisalCycle SET status = '".$input["status"]."', lastModifyBy = '".$_GET["emp_id"]."', lastModifyOn = '" . date('Y-m-d H:i:s') . "' 
         WHERE id = '".$input["id"]."' ";    
         
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "saveAppraisalForm") {
        
        $sql = "INSERT INTO `appraisalform`(`plant_id`, `cycleId`, `cycleName`, `emp_id`, `department`, `designation`, `self`, `manager`, `peer`, `subordinate`, 
        `status`, `entryBy`, `entryOn`) VALUES ('".$_GET["plant_id"]."','".$input["cycleId"]."','".$input["cycleName"]."','".$input["emp_id"]."',
        '".$input["department"]."','".$input["designation"]."','".$input["self"]."','".$input["manager"]."','".$input["peer"]."','".$input["subordinate"]."',
        'Approved','".$_GET["emp_id"]."','" . date('Y-m-d H:i:s') . "')";  
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "saveAppraisalCycle") {
        
        $sql = "INSERT INTO appraisalCycle (plant_id,cycleName,startDate,endDate,status,entryBy,entryOn) VALUES ('".$_GET["plant_id"]."',
        '".$input["cycleName"]."','".$input["startDate"]."','".$input["endDate"]."','Pending','".$_GET["emp_id"]."','" . date('Y-m-d H:i:s') . "')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
   else if ($_GET["type"] == "getRelatedKpi") {
        $output = Array();
        $sql = "SELECT plant_id,department,designation,kra,kpiGroupId FROM kpiMaster where department = '".$_GET['deptName']."' AND 
        designation = '".$_GET['designation']."' AND plant_id = '".$_GET['plant_id']."' GROUP BY plant_id,department,designation,kra,kpiGroupId";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
   else if ($_GET["type"] == "getkraList") {
        $output = Array();
        $sql = "SELECT * FROM kraMaster where plant_id = '".$_GET['plant_id']."' ORDER BY kra ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveKraMaster") {
        
        $sql = "INSERT INTO kraMaster(plant_id,kra,entryBy,entryOn) VALUES ('".$_GET["plant_id"]."','".$input["kra"]."','".$_GET["emp_id"]."','" . date('Y-m-d H:i:s') . "')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "saveKraKpimaster") {
         
        // Step 1: Try to find existing group ID for this context
            $checkGroupIdSql = "SELECT kpiGroupId FROM kpiMaster 
                WHERE plant_id = '".$_GET["plant_id"]."' 
                AND department = '".$input["department"]."' 
                AND designation = '".$input["designation"]."' 
                AND kra = '".$input["kra"]."' 
                LIMIT 1";
            
            $result = $conn->query($checkGroupIdSql);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $kpiGroupId = $row['kpiGroupId'];  // Reuse existing group
            } else {
                
                $sql1 = "SELECT COUNT(*) as total FROM kpiMaster";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                $count = $row1['total'] + 2;
                $kpiGroupId = "KPI" . str_pad($count, 5, "0", STR_PAD_LEFT);
                
            }

        
        $check = true;
             
        foreach ($input['kpiList'] as $val) {
          
            $sql = "INSERT INTO `kpiMaster`(`plant_id`, `department`, `designation`, `kra`, `kpiGroupId`, `question`, `evaluationType`, 
            `self`, `manager`,`peer`, `subordinate`, `entryBy`, `entryOn`) VALUES ('".$_GET["plant_id"]."','".$input["department"]."',
            '".$input["designation"]."','".$input["kra"]."','$kpiGroupId','".$val["question"]."','".$val["evaluationType"]."','".$val["self"]."',
            '".$val["manager"]."','".$val["peer"]."','".$val["subordinate"]."','".$_GET["emp_id"]."','" . date('Y-m-d H:i:s') . "')";      
                if ($conn->query($sql)) {
                    
                } else {
                    $check = false;
                }
        }
            
        if ($check) {
            echo "{\"status\":\"success\"}";  
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
     else if ($_GET["type"] == "mappedKraToappraisal") {
         
      

        
        $check = true;
             
        foreach ($input['mappedKra'] as $val) {
            
            
            $self_yes_count = 0;
            $manager_yes_count = 0;
            $peer_yes_count = 0;
            $subordinate_yes_count = 0;
            
        $sql1 = "SELECT
                    SUM(CASE WHEN self = 'Yes' THEN 1 ELSE 0 END) AS self_yes_count,
                    SUM(CASE WHEN manager = 'Yes' THEN 1 ELSE 0 END) AS manager_yes_count,
                    SUM(CASE WHEN peer = 'Yes' THEN 1 ELSE 0 END) AS peer_yes_count,
                    SUM(CASE WHEN subordinate = 'Yes' THEN 1 ELSE 0 END) AS subordinate_yes_count
                FROM kpiMaster WHERE kpiGroupId = '".$val['kraId']."' AND plant_id = '".$_GET["plant_id"]."'";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $self_yes_count = $row1['self_yes_count'];
                $manager_yes_count = $row1['manager_yes_count'];
                $peer_yes_count = $row1['peer_yes_count'];
                $subordinate_yes_count = $row1['subordinate_yes_count'];
            }
        }
            
            $self = 'Done';
            $manager = 'Done';
            $peer = 'Done';
            $subordinate = 'Done';
            
            if($self_yes_count > 0){ $self = 'Pending'; }  
            if($manager_yes_count > 0){ $manager = 'Pending'; }  
            if($peer_yes_count > 0){ $peer = 'Pending'; }  
            if($subordinate_yes_count > 0){ $subordinate = 'Pending'; }
            
            
            
            
          
            $sql = "INSERT INTO `mappedKpi`(`plant_id`, `appraisalId`, `cycleId`,`emp_id`, `kraId`, `weightage`,`self`, `manager`,`peer`, `subordinate`, `entryBy`,
            `entryOn`,selfStatus,managerStatus,peerStatus,subordinateStatus) VALUES ('".$_GET["plant_id"]."','".$input["appraisalId"]."','".$input["cycleId"]."','".$input["emp_id"]."','".$val["kraId"]."',
            '".$val["weightage"]."','".$input["self"]."','".$input["manager"]."','".$input["peer"]."','".$input["subordinate"]."','".$_GET["emp_id"]."',
            '" . date('Y-m-d H:i:s') . "','$self','$manager','$peer','$subordinate')";   
            
                if ($conn->query($sql)) {
                    
                } else {
                    $check = false;
                }
        }
            
        if ($check) {
            echo "{\"status\":\"success\"}";  
            $sql1 = "UPDATE appraisalform SET status = 'Active', lastModifyBy = '".$_GET["emp_id"]."', lastModifyOn = '" . date('Y-m-d H:i:s') . "' 
            WHERE id = '".$input["appraisalId"]."' "; 
            $conn->query($sql1);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
        
        
        
        
        
    }
    else if ($_GET["type"] == "getKraKpiMaster") {
        $output = Array();
        
        $sql = "SELECT plant_id,department,designation,kra,kpiGroupId FROM kpiMaster where plant_id = '".$_GET['plant_id']."' GROUP BY plant_id,department,designation,kra,kpiGroupId";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                    $output1 = Array();
                    $sql1 = "SELECT * FROM kpiMaster where kra = '".$row['kra']."' ORDER BY question ASC";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    
                $row['kpiList'] = $output1;
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    } 
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
 
    else if ($_GET["type"] == "savePrepareList") {
        $sql = "UPDATE vendor_checklist SET preparechekclist='" . json_encode($input["preparechekclist"]) . "', status='prepare' WHERE id='" . $_GET["id"] . "' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    } 
    else if ($_GET["type"] == "save_apprasel") {
  $sql = "INSERT INTO apprasel ( plant_id,dep_area,strength,comment,avg_rating,behaviour,app_id,app_name,overall_comment,overall_scale,end_time,start_date,m_code,m_name) VALUES 
  ( '".$_GET["plant_id"]."','".$input["dep_area"]."','".$input["strength"]."', '".$input["comment"]."', '".$input["avg_rating"]."', '".$input["behaviour"]."', 
        '".$input["app_id"]."', '".$input["app_name"]."', '".$input["overall_comment"]."', '".$input["overall_scale"]."', '".$input["end_time"]."', 
        '".$input["start_date"]."', '".$input["m_name"]."', '".$input["m_code"]."')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "save_newTeam") {
    $sql = "INSERT INTO new_team ( plant_id, evualation_type,evualation_parameter,department,appraisal_type) VALUES 
  ( '".$_GET["plant_id"]."','".$input["evualation_type"]."','".$input["evualation_parameter"]."','".$input["department"]."','".$input["appraisal_type"]."')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
 
    
    else if ($_GET["type"] == "getPrepareChecklist") {
        $output = array();
        $sql = "SELECT * FROM vendor_checklist WHERE status='prepare'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["preparechekclist"] = json_decode($row["preparechekclist"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "gate_new_team") {
        $output = array();
        $sql = "SELECT * FROM new_team  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

             $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "gate_pending") {
        $output = array();
        $sql = "SELECT * FROM new_team  where status='' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

             $output[] = $row;
            }
        }
        echo json_encode($output);
    }
      else if ($_GET["type"] == "gateapprove") {
        $output = array();
        $sql = "SELECT * FROM new_team  where status='approve' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

             $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "update_status") {
        $sql = "UPDATE new_team SET status='".$_GET["status"]."' WHERE department ='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
 
 
 

    else if ($_GET["type"] == "getDesignation") {
        $output = array();
       // $sql = "SELECT a.designation,b.department_name FROM designation a left JOIN department b on a.dept_id=b.id where b.department_name='".$_GET["department"]."' group by b.department_name,a.designation;";
        $sql = "SELECT a.designation,b.department_name FROM designation a left JOIN department b on a.dept_id=b.id  group by b.department_name,a.designation;";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getapprisals_log") {
        $output = array();
        $sql = "SELECT * from apprisal where emp_id='".$_GET["emp_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getapprisals_logMeha") {
        $output = array();
        $sql = "SELECT * from apprisal where emp_id='".$_GET["emp_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

   $row["key_result_areas"] = json_decode($row["key_result_areas"]);  
   
   $row["competencies"] = json_decode($row["competencies"]);  
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getapprisals_log_for_deptHead") {
        $output = array();
          $sql = "SELECT DISTINCT a.id, a.*,e.firstname,e.lastname,e.designation from apprisal a left join employee e on a.emp_id = e.emp_id where 
         a.dept_head = 'pending' AND e.department = '".$_GET["department"]."' AND  a.plant_id='".$_GET["plant_id"]."' ";
         
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getapprisals_log_for_deptHeadMeha") {
        $output = array();
          $sql = "SELECT DISTINCT a.id, a.*,e.firstname,e.lastname,e.designation from apprisal a left join employee e on a.emp_id = e.emp_id where 
         a.dept_head = 'pending' AND e.department = '".$_GET["department"]."' AND  a.plant_id='".$_GET["plant_id"]."' ";
         
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
$row["key_result_areas"] = json_decode($row["key_result_areas"]);  
   
   $row["competencies"] = json_decode($row["competencies"]);  
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getapprisals_log_for_deptHeadNotification") {
        $output = array();
         $sql = "SELECT count(a.id) as pending_appraisal from apprisal a left join employee e on a.emp_id = e.emp_id where 
         a.dept_head = 'pending' AND e.department = '".$_GET["department"]."' AND  a.plant_id='".$_GET["plant_id"]."' ";
         
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output = $row;
            }
        }
        
      //  $output['pending_appraisal'] =3;
            $test = "You Have '".$output['pending_appraisal']."' Apprisal Request Pending For Approval";
            $output['text'] = $test;
    
    echo json_encode($output);
    }
    else if ($_GET["type"] == "getapprisals_log_for_HR") {
        $output = array();
         $sql = "SELECT DISTINCT a.id, a.*,e.firstname,e.lastname,e.designation from apprisal a left join
         employee e on a.emp_id = e.emp_id where  a.hr_status = 'pending'  AND  a.plant_id='".$_GET["plant_id"]."' ";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getapprisals_log_for_HRMeha") {
        $output = array();
         $sql = "SELECT DISTINCT a.id, a.*,e.firstname,e.lastname,e.designation,e.joining_date from apprisal a left join
         employee e on a.emp_id = e.emp_id where  a.hr_status = 'pending'  AND  a.plant_id='".$_GET["plant_id"]."' AND  e.plant_id='".$_GET["plant_id"]."' ";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
$row["key_result_areas"] = json_decode($row["key_result_areas"]);  
   
   $row["competencies"] = json_decode($row["competencies"]);  
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "HOgetapprisals_log_for_HR") {
        $output = array();
         $sql = "SELECT DISTINCT a.id, a.*,e.firstname,e.lastname,e.designation from apprisal a left join
         employee e on a.emp_id = e.emp_id where  a.hr_status = 'pending'  AND  a.plant_id='".$_GET["plantID"]."' ";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getapprisals_log_for_planthead") {
        $output = array();
          $sql = "SELECT DISTINCT a.id, a.*,e.firstname,e.lastname,e.designation from apprisal a left join employee e on a.emp_id = e.emp_id where  a.plant_head = 'pending'   AND  a.plant_id='".$_GET["plant_id"]."' ";
    //   echo  $sql = "SELECT a.*,e.firstname,e.lastname,e.designation from apprisal a left join employee e on a.emp_id = e.emp_id where a.status = 'pending' AND e.department = '".$_GET["department"]."' AND  a.plant_id='".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getapprisals_log_for_HR") {
        $output = array();
        $sql = "SELECT a.*,e.firstname,e.lastname,e.designation from apprisal a left join employee e on a.emp_id = e.emp_id where a.status = 'Inprocess'  AND  a.plant_id='".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "get_employee") {
        $output = array();
        $sql = "SELECT * from employee where department='".$_GET["department"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getapprisals_log_master") {
        $output = array();
        
         $sql = "SELECT DISTINCT a.id, a.*, a.designation AS desig, b.designation, b.emp_id AS b_id,d.firstname,d.lastname FROM apprisal a LEFT JOIN employee b 
        ON a.department = b.department LEFT JOIN (SELECT c.firstname, c.emp_id,c.lastname FROM employee c) d ON d.emp_id = a.emp_id 
        WHERE a.department = '".$_GET["department"]."' AND b.designation LIKE '%head%'  AND a.plant_id = '".$_GET["plant_id"]."' AND b.emp_id = '".$_GET["emp_id"]."' and  a.dept_head='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
     else if ($_GET["type"] == "get_dept_approve") {
        $output = array();
         $sql = "SELECT DISTINCT a.id, a.*, a.designation AS desig, b.designation, b.emp_id AS b_id,b.firstname,b.lastname FROM apprisal a LEFT JOIN employee b 
        ON a.emp_id = b.emp_id WHERE  a.dept_head='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "save_apprisal") {
         
        $sql = "INSERT INTO apprisal( department,entry_date,emp_id,plant_id, achivement, apprisal_type, designation, promotion, Request_reason, 
                rise) VALUES ('".$_GET["department"]."','$entry_date','".$_GET["emp_id"]."','".$_GET["plant_id"]."','".$input["achivement"]."','".$input["apprisal_type"]."',
                '".$input["designation"]."',' ".$input["promotion"]."','".$input["Request_reason"]."',
                '".$input["rise"]."') ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    
         
     }
     else if ($_GET["type"] == "save_apprisalMeha") {// Escape input values to prevent SQL injection and syntax errors
$department = $conn->real_escape_string($_GET["department"]);
$entry_date = $conn->real_escape_string($entry_date);
$emp_id = $conn->real_escape_string($_GET["emp_id"]);
$plant_id = $conn->real_escape_string($_GET["plant_id"]);
$achivement = $conn->real_escape_string($input["achivement"]);
$apprisal_type = $conn->real_escape_string($input["apprisal_type"]);
$designation = $conn->real_escape_string($input["designation"]);
$promotion = $conn->real_escape_string($input["promotion"]);
$request_reason = $conn->real_escape_string($input["Request_reason"]);
$rise = $conn->real_escape_string($input["rise"]);

// Convert JSON to a properly escaped string
$key_result_areas = $conn->real_escape_string(json_encode($input["key_result_areas"], JSON_UNESCAPED_UNICODE));
$competencies = $conn->real_escape_string(json_encode($input["competencies"], JSON_UNESCAPED_UNICODE));

$sql = "INSERT INTO apprisal (department, entry_date, emp_id, plant_id, achivement, apprisal_type, designation, promotion, Request_reason, rise, key_result_areas, competencies) 
        VALUES ('$department', '$entry_date', '$emp_id', '$plant_id', '$achivement', '$apprisal_type', '$designation', '$promotion', '$request_reason', '$rise', '$key_result_areas', '$competencies')";

if ($conn->query($sql)) {
    echo "{\"status\":\"success\"}";
} else {
    echo "{\"status\":\"" . $conn->error . "\"}";
}
}
     else if ($_GET["type"] == "save_apprisal_dept") {
         
        $sql = "INSERT INTO apprisal( dept_head,department,entry_date,emp_id,plant_id, achivement, apprisal_type, designation, promotion, Request_reason, 
                rise) VALUES ('Approve','".$_GET["department"]."','$entry_date','".$input["employee"]."','".$_GET["plant_id"]."','".$input["achivement"]."','".$input["apprisal_type"]."',
                '".$input["designation"]."',' ".$input["promotion"]."','".$input["Request_reason"]."',
                '".$input["rise"]."') ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    
         
     }
     else if ($_GET["type"] == "update_dept_status") {
          
         
         $sql = "UPDATE apprisal SET status = 'Inprocess', dept_head='" . $_GET["status"] . "' , rise = '" . $_GET["rise"] . "'  where id='". $_GET["id"] ."'";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    
         
     }
     else if ($_GET["type"] == "update_dept_statusMeha") {
      $key_result_areas = $conn->real_escape_string(json_encode($input["key_result_areas"], JSON_UNESCAPED_UNICODE));
$competencies = $conn->real_escape_string(json_encode($input["competencies"], JSON_UNESCAPED_UNICODE));
         
         $sql = "UPDATE apprisal SET status = 'Inprocess', dept_head='" . $_GET["status"] . "' , rise = '" . $_GET["rise"] . "' ,dept_head_by='" . $_GET["emp_id"] . "'  ,dept_head_date='$entry_date',
            key_result_areas='$key_result_areas' ,competencies='$competencies' 
         where id='". $_GET["id"] ."'";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    
         
     }
     else if ($_GET["type"] == "update_plant_status") {
          
         
         $sql = "UPDATE apprisal SET  plant_head='" . $_GET["status"] . "' , rise = '" . $_GET["rise"] . "'  where id='". $_GET["id"] ."'";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    
         
     }
     else if ($_GET["type"] == "update_HR_status") {
          
         
         $sql = "UPDATE apprisal SET  hr_status='" . $_GET["status"] . "' , rise = '" . $_GET["rise"] . "'  where id='". $_GET["id"] ."'";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    
         
     }
     else if ($_GET["type"] == "update_HR_statusMeha") {
         $terms_conditions = $conn->real_escape_string(json_encode($input["terms_conditions"], JSON_UNESCAPED_UNICODE));
          
         
         $sql = "UPDATE apprisal SET  hr_status='" . $_GET["status"] . "' , rise = '" . $_GET["rise"] . "' , terms_conditions = '$terms_conditions'
         , hr_by = '" . $_GET["emp_id"] . "' , hr_date = 'entry_date'   where id='". $_GET["id"] ."'";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    
         
     }
}

$conn->close();
