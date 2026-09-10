<?php 
//   ini_set('display_errors', 1);
//     error_reporting(E_ALL); 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
 $entry_date = date("Y-m-d h:i:s", $timestamp);
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
    
    if ($_GET["type"] == "save_daily_form") 
    {
          $sql = "INSERT INTO daily (plant_id , facility_name, location, date, cleaing_task, cleaning_method, 
        strt_time,end_time,cleaning_solution,cleaning_concentration,inspection,  
        obnormalities,action_taken,add_notes,cleaning_person_name,supervisor_name,type)
     VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["location"]."', '".$input["date"]."', '".$input["cleaing_task"]."', '".$input["cleaning_method"]."',
        '".$input["strt_time"]."', '".$input["end_time"]."', '".$input["cleaning_solution"]."', '".$input["cleaning_concentration"]."', '".$input["inspection"]."',
        '".$input["obnormalities"]."','".$input["action_taken"]."','".$input["add_notes"]."','".$input["cleaning_person_name"]."','".$input["supervisor_name"]."','Daily')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

else if ($_GET["type"] == "get_daily_form") {
    
            $sql = "SELECT * FROM daily where type='Daily' order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}

else if ($_GET["type"] == "save_weekly_form") 
    {
          $sql = "INSERT INTO daily (plant_id , facility_name, location, date, cleaing_task, cleaning_method, 
        strt_time,end_time,cleaning_solution,cleaning_concentration,inspection,  
        obnormalities,action_taken,add_notes,cleaning_person_name,supervisor_name,type)
     VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["location"]."', '".$input["week_date"]."', '".$input["cleaing_task"]."', '".$input["cleaning_method"]."',
        '".$input["strt_time"]."', '".$input["end_time"]."', '".$input["cleaning_solution"]."', '".$input["cleaning_concentration"]."', '".$input["inspection"]."',
        '".$input["obnormalities"]."','".$input["action_taken"]."','".$input["add_notes"]."','".$input["cleaning_person_name"]."','".$input["supervisor_name"]."','Weekly')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
else if ($_GET["type"] == "get_weekly") {
    
            $sql = "SELECT * FROM daily where type='Weekly' order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}

// else if ($_GET["type"] == "saveJobCard") 
//     {
//           $sql = "INSERT INTO area_facility (plant_id , facility_name, location, area_name, area_type, area_id, 
//         area_desc,size,maxm_capacity,op_status,cleaning_status,  
//         supervisor,emer_contact,add_contact,access_control,security_protocol,surveillance)
//      VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["location"]."', '".$input["area_name"]."', '".$input["area_type"]."', '".$input["area_id"]."',
//         '".$input["area_desc"]."', '".$input["size"]."', '".$input["maxm_capacity"]."', '".$input["op_status"]."', '".$input["cleaning_status"]."',
//         '".$input["supervisor"]."','".$input["emer_contact"]."','".$input["add_contact"]."','".$input["access_control"]."','".$input["security_protocol"]."','".$input["surveillance"]."')";
//         if ($conn->query($sql)) {
//             echo "{\"status\":\"success\"}";
//         } else {
//             echo "{\"status\":\"".$conn->error."\"}";
//         }
//     }
    
else if ($_GET["type"] == "saveJobCard") 
{
    //   ini_set('display_errors', 1);
    // error_reporting(E_ALL); 
    $data = json_decode(file_get_contents("php://input"), true);
    $entry_date = date("Y-m-d H:i:s"); // or your preferred format

    // Generate jobcardno (JB-001, JB-002, ...)
    $prefix = "JB-";
    $getLast = "SELECT jobcardno FROM job_cards ORDER BY id DESC LIMIT 1";
    $res = $conn->query($getLast);
    $newNumber = 1;

    if ($res && $row = $res->fetch_assoc()) {
        $lastNo = intval(str_replace($prefix, "", $row["jobcardno"]));
        $newNumber = $lastNo + 1;
    }

    $jobcardno = $prefix . str_pad($newNumber, 3, "0", STR_PAD_LEFT);  // e.g., JB-001

    // Insert job card
  $sql = "INSERT INTO job_cards (
    jobcardno, identifiedBy, devOccuredDept, location, equipment_id, 
    description, priority, remark, status, created_at
) VALUES (
    '$jobcardno',
    '".$data["identifiedBy"]."', 
    '".$data["devOccuredDept"]."', 
    '".$data["location"]."', 
    '".$data["equipment_id"]."', 
    '".$data["description"]."', 
    '".$data["priority"]."', 
    '".$data["remark"]."',
    'DeptHeadInitiate',
    '$entry_date'
)";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "jobcardno" => $jobcardno]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}


 else if ($_GET['type'] == 'saveJobCardAprrvl') {
    $sql = "UPDATE job_cards SET status = 'ReceivedEginneringDept' , intialHodBy=  '".$_GET["emp_id"]."',intialHodOn= '$entry_date'  WHERE id = '" . $_GET['id'] . "'";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "failed", "error" => $conn->error]);
    }
} 

else if ($_GET['type'] == 'EnggJobCardAprrvl') {
    $sql = "UPDATE job_cards SET EnggRemark='".$input["EnggRemark"]."' , status = 'CheckedByEnggHOD' , EnggHodBy=  '".$_GET["emp_id"]."',EnggHodOn= '$entry_date'  WHERE id = '" . $_GET['id'] . "'";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "failed", "error" => $conn->error]);
    }
}
else if ($_GET['type'] == 'saveJobCardEnggHod') {
    $sql = "UPDATE job_cards SET status = 'SendToHodEngg', EnggBy=  '".$_GET["emp_id"]."',EnggOn= '$entry_date'  WHERE id = '" . $_GET['id'] . "'";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "failed", "error" => $conn->error]);
    }
}
else if ($_GET['type'] == 'jobCardSave') {
    $sql = "UPDATE job_cards SET 
        status = 'VerificationUserDept',
        EnggDoneBy = '" . $_GET["emp_id"] . "',
        EnggDoneOn = '$entry_date',
        actionTaken = '" . $input["actionTaken"] . "',
        ppe = '" . $input["ppe"] . "',
        scrapDetails = '" . $input["scrapDetails"] . "',
        scrapGenerated = '" . $input["scrapGenerated"] . "',
        shutdownRequired = '" . $input["shutdownRequired"] . "',
        sparesDetails = '" . $input["sparesDetails"] . "',
        sparesUsed = '" . $input["sparesUsed"] . "'
        WHERE id = '" . $_GET['id'] . "'";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "failed", "error" => $conn->error]);
    }
}
else if ($_GET['type'] == 'jobCardVerification') {
    $sql = "UPDATE job_cards SET 
        status = 'VerifiedUserDept',
        VerifiedBy = '" . $_GET["emp_id"] . "',
        VerifiedOn = '$entry_date',
        areaCleaned = '" . $input["areaCleaned"] . "',
        VerificationRemark = '" . $input["VerificationRemark"] . "'
        WHERE id = '" . $_GET['id'] . "'";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "failed", "error" => $conn->error]);
    }
}
else if ($_GET['type'] == 'jobCardToQA') {
    $sql = "UPDATE job_cards SET 
        status = 'VerifiedQADept',
        VerifiedQABy = '" . $_GET["emp_id"] . "',
        VerifiedQAOn = '$entry_date'
        WHERE id = '" . $_GET['id'] . "'";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "failed", "error" => $conn->error]);
    }
}

else if ($_GET['type'] == 'jobCardToQaApprvl') {
    $sql = "UPDATE job_cards SET 
        status = 'VerifiedIPQADept',
        VerifiedIPQABy = '" . $_GET["emp_id"] . "',
        VerifiedIPQAOn = '$entry_date',
        capaRef = '" . $input["capaRef"] . "',
        readyForUse = '" . $input["readyForUse"] . "',
         recleaningVerified = '" . $input["recleaningVerified"] . "',
        canBeUsed = '" . $input["canBeUsed"] . "'
        WHERE id = '" . $_GET['id'] . "'";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "failed", "error" => $conn->error]);
    }
}
else if ($_GET['type'] == 'jobCardToCloser') {
    $sql = "UPDATE job_cards SET 
        status = 'JobCardForCloseout',
        FinalQABy = '" . $_GET["emp_id"] . "',
        FinalQAOn = '$entry_date'
        WHERE id = '" . $_GET['id'] . "'";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "failed", "error" => $conn->error]);
    }
}
else if ($_GET['type'] == 'jobCardClosed') {
    $sql = "UPDATE job_cards SET 
        status = 'JobCardCloseout',
        CloseoutBy = '" . $_GET["emp_id"] . "',
        CloseoutOn = '$entry_date',
         nameEmp = '" . $input["nameEmp"] . "',
        historyCardUpdate = '" . $input["historyCardUpdate"] . "',
          jobCardClosed = '" . $input["jobCardClosed"] . "'
        WHERE id = '" . $_GET['id'] . "'";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "failed", "error" => $conn->error]);
    }
}
    else if ($_GET["type"] == "getEquiptment") {
    
            $sql = "SELECT * FROM equipment where department = '".$_GET['dept']."' ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row; 
    		}
    	}
    	echo json_encode($output);
}
 else if ($_GET["type"] == "getEmp") {
    
            $sql = "SELECT * FROM employee where department = 'Store' ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row; 
    		}
    	}
    	echo json_encode($output);
}
 else if ($_GET["type"] == "getResponse") {
   $sql =   "SELECT a.*, e.equipment_name FROM job_cards a LEFT JOIN equipment e ON a.equipment_id = e.equipment_code WHERE 
             a.devOccuredDept='".$_GET['dept']."';" ;
            // $sql = "SELECT * FROM job_cards ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
 else if ($_GET["type"] == "getResponse2") {
    $sql = "
        SELECT 
            a.*, 
            e.equipment_name 
        FROM 
            job_cards a 
        LEFT JOIN 
            equipment e 
        ON 
            a.equipment_id = e.equipment_code 
        WHERE 
            a.status = 'DeptHeadInitiate';
    ";
    // $sql = "SELECT * FROM job_cards ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
 else if ($_GET["type"] == "getResponse3") {
    $sql = "
        SELECT 
            a.*, 
            e.equipment_name 
        FROM 
            job_cards a 
        LEFT JOIN 
            equipment e 
        ON 
            a.equipment_id = e.equipment_code 
        WHERE 
            a.status = 'ReceivedEginneringDept';
    ";
    // $sql = "SELECT * FROM job_cards ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
 else if ($_GET["type"] == "getResponse4") {
    $sql = "
        SELECT 
            a.*, 
            e.equipment_name 
        FROM 
            job_cards a 
        LEFT JOIN 
            equipment e 
        ON 
            a.equipment_id = e.equipment_code 
        WHERE 
            a.status = 'SendToHodEngg';
    ";
    // $sql = "SELECT * FROM job_cards ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
else if ($_GET["type"] == "getResponse5") {
    $sql = "
        SELECT 
            a.*, 
            e.equipment_name 
        FROM 
            job_cards a 
        LEFT JOIN 
            equipment e 
        ON 
            a.equipment_id = e.equipment_code 
        WHERE 
            a.status = 'CheckedByEnggHOD';
    ";
    // $sql = "SELECT * FROM job_cards ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
else if ($_GET["type"] == "getResponse6") {
    $sql = "
        SELECT 
            a.*, 
            e.equipment_name 
        FROM 
            job_cards a 
        LEFT JOIN 
            equipment e 
        ON 
            a.equipment_id = e.equipment_code 
        WHERE 
            a.status = 'VerificationUserDept' And devOccuredDept='".$_GET['dept']."';
    ";
    // $sql = "SELECT * FROM job_cards ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
else if ($_GET["type"] == "getResponse7") {
    $sql = "
        SELECT 
            a.*, 
            e.equipment_name 
        FROM 
            job_cards a 
        LEFT JOIN 
            equipment e 
        ON 
            a.equipment_id = e.equipment_code 
        WHERE 
            a.status = 'VerifiedUserDept' And devOccuredDept='".$_GET['dept']."';
    ";
    // $sql = "SELECT * FROM job_cards ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
else if ($_GET["type"] == "getResponse8") {
    $sql = "
        SELECT 
            a.*, 
            e.equipment_name 
        FROM 
            job_cards a 
        LEFT JOIN 
            equipment e 
        ON 
            a.equipment_id = e.equipment_code 
        WHERE 
            a.status = 'VerifiedQADept';
    ";
    // $sql = "SELECT * FROM job_cards ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
else if ($_GET["type"] == "getResponse9") {
    $sql = "
        SELECT 
            a.*, 
            e.equipment_name 
        FROM 
            job_cards a 
        LEFT JOIN 
            equipment e 
        ON 
            a.equipment_id = e.equipment_code 
        WHERE 
            a.status = 'VerifiedIPQADept';
    ";
    // $sql = "SELECT * FROM job_cards ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
else if ($_GET["type"] == "getResponse10") {
    $sql = "
        SELECT 
            a.*, 
            e.equipment_name 
        FROM 
            job_cards a 
        LEFT JOIN 
            equipment e 
        ON 
            a.equipment_id = e.equipment_code 
        WHERE 
            a.status = 'JobCardForCloseout';
    ";
    // $sql = "SELECT * FROM job_cards ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
else if ($_GET["type"] == "getResponse11") {
    $sql = "
        SELECT 
            a.*, 
            e.equipment_name 
        FROM 
            job_cards a 
        LEFT JOIN 
            equipment e 
        ON 
            a.equipment_id = e.equipment_code;
    ";
    // $sql = "SELECT * FROM job_cards ";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
else if ($_GET["type"] == "save_utility") {
          $sql = "INSERT INTO utility (plant_id , facility_name, location, equp_name, equp_type, equp_id, 
        manufacturer,model,utility_type,consump_rate,measur_unit,  
        conn_status,last_maint_date,next_maint_date,usage_status,oper_notes,supervisor,emer_contact,add_contact)
     VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["location"]."', '".$input["equp_name"]."', '".$input["equp_type"]."', '".$input["equp_id"]."',
        '".$input["manufacturer"]."', '".$input["model"]."', '".$input["utility_type"]."', '".$input["consump_rate"]."', '".$input["measur_unit"]."',
        '".$input["conn_status"]."','".$input["last_maint_date"]."','".$input["next_maint_date"]."','".$input["usage_status"]."','".$input["oper_notes"]."','".$input["supervisor"]."','".$input["emer_contact"]."','".$input["add_contact"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
//     else if ($_GET["type"] == "downloadPDF") { 
// 	    $_GET['filename'] = ' ';
// 		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
//         $sql = "
//         SELECT 
//             a.*, 
//             e.equipment_name 
//         FROM 
//             job_cards a 
//         LEFT JOIN 
//             equipment e 
//         ON 
//             a.equipment_id = e.equipment_code;
//         ";       
//         $result = $conn->query($sql);
//         $row = $result->fetch_assoc();{
//         $html = '
// <table border="0" cellpadding="4" cellspacing="0" style="width:100%; border-collapse: collapse; font-family:helvetica;">
//   <tr>
//     <td width="47"><strong>1.0</strong></td>
//     <td colspan="8" style="color: rgb(61, 61, 233);"><strong>Job card No.:</strong> ' . htmlspecialchars($jobcardNo) . '</td>
//   </tr>
//   <tr>
//     <td><strong>2.0</strong></td>
//     <td colspan="11" style="color: rgb(58, 182, 9);"><strong>Details of Job (' . htmlspecialchars($dept) . ')</strong></td>
//   </tr>
//   <tr>
//     <td>2.1</td>
//     <td>Department Name:</td>
//     <td colspan="8" style="color: rgb(61, 61, 233);">' . htmlspecialchars($dept) . '</td>
//   </tr>
//   <tr>
//     <td>2.2</td>
//     <td>Area/ Location</td>
//     <td colspan="8" style="color: rgb(61, 61, 233);">' . htmlspecialchars($location) . '</td>
//   </tr>
//   <tr>
//     <td>2.3</td>
//     <td>Equipment/ Instruments ID</td>
//     <td colspan="8" style="color: rgb(61, 61, 233);">' . htmlspecialchars($equipmentName) . ' (' . htmlspecialchars($equipmentId) . ')</td>
//   </tr>
//   <tr>
//     <td>2.4</td>
//     <td>Details of Job/activities</td>
//     <td colspan="8" style="color: rgb(61, 61, 233);">' . htmlspecialchars($description) . '</td>
//   </tr>
//   <tr>
//     <td>2.5</td>
//     <td>Priority</td>
//     <td colspan="8" style="color: rgb(61, 61, 233);">' . htmlspecialchars($priority) . '</td>
//   </tr>
//   <tr>
//     <td>2.6</td>
//     <td>Remark</td>
//     <td colspan="8" style="color: rgb(61, 61, 233);">' . htmlspecialchars($remark) . '</td>
//   </tr>
//   <tr>
//     <td>2.7</td>
//     <td>Job Card raised by:</td>
//     <td colspan="8" style="color: rgb(61, 61, 233);">' . htmlspecialchars($identifiedBy) . '</td>
//   </tr>
//   <tr>
//     <td>2.8</td>
//     <td>Checked by HOD/Shift In charge:</td>
//     <td colspan="8" style="color: rgb(61, 61, 233);">' . htmlspecialchars($hodChecked) . '</td>
//   </tr>
//   <tr>
//     <td><strong>3.0</strong></td>
//     <td colspan="11" style="color: rgb(58, 182, 9);"><strong>Execution (Engineering)</strong></td>
//   </tr>
//   <tr>
//     <td>3.1</td>
//     <td>Job Card received by:</td>
//     <td colspan="8" style="color: rgb(61, 61, 233);">' . htmlspecialchars($enggBy) . '</td>
//   </tr>
//   <tr>
//     <td>3.2</td>
//     <td>Initial Observation/ Remark:</td>
//     <td colspan="8" style="color: rgb(61, 61, 233);">' . htmlspecialchars($enggRemark) . '</td>
//   </tr>
//   <tr>
//     <td>3.3</td>
//     <td>Initial Observation:</td>
//     <td colspan="8" style="color: rgb(61, 61, 233);">' . htmlspecialchars($enggHodBy) . '</td>
//   </tr>
//   <tr>
//     <td colspan="12" style="text-align: center; color: rgb(118, 235, 50);">Execution</td>
//   </tr>
//   <tr>
//     <td>3.4</td>
//     <td>Shutdown Required</td>
//     <td colspan="10" style="color: rgb(61, 61, 233);">' . htmlspecialchars($shutdownRequired) . '</td>
//   </tr>
//   <tr>
//     <td>3.5</td>
//     <td>Protective Equipment</td>
//     <td colspan="10" style="color: rgb(61, 61, 233);">' . htmlspecialchars($ppe) . '</td>
//   </tr>
//   <tr>
//     <td>3.7</td>
//     <td>Engineering Action Taken</td>
//     <td colspan="10" style="color: rgb(61, 61, 233);">' . htmlspecialchars($actionTaken) . '</td>
//   </tr>
//   <tr>
//     <td>3.8</td>
//     <td>Spares Utilized</td>
//     <td colspan="10" style="color: rgb(61, 61, 233);">' . htmlspecialchars($sparesUsed) . '</td>
//   </tr>
//   <tr>
//     <td>3.9</td>
//     <td>Scrap Generated</td>
//     <td colspan="10" style="color: rgb(61, 61, 233);">' . htmlspecialchars($scrapGenerated) . '</td>
//   </tr>
//   <tr>
//     <td><strong>4.0</strong></td>
//     <td colspan="11" style="color: rgb(58, 182, 9); text-align:center;"><strong>Verification of Job after completion</strong></td>
//   </tr>
//   <tr>
//     <td>4.1</td>
//     <td>Remarks</td>
//     <td colspan="10" style="color: rgb(61, 61, 233);">' . htmlspecialchars($verificationRemarks) . '</td>
//   </tr>
//   <tr>
//     <td>4.2</td>
//     <td>Area Cleaned</td>
//     <td colspan="10" style="color: rgb(61, 61, 233);">' . htmlspecialchars($areaCleaned) . '</td>
//   </tr>
//   <tr>
//     <td><strong>5.0</strong></td>
//     <td colspan="11" style="color: rgb(58, 182, 9); text-align:center;"><strong>Impact Assessment by QA</strong></td>
//   </tr>
//   <tr>
//     <td>5.1</td>
//     <td>CAPA / Change Control Ref</td>
//     <td colspan="10" style="color: rgb(61, 61, 233);">' . htmlspecialchars($capaRef) . '</td>
//   </tr>
//   <tr>
//     <td>5.3</td>
//     <td>Equipment Ready for Routine Use</td>
//     <td colspan="10" style="color: rgb(61, 61, 233);">' . htmlspecialchars($readyForUse);

// // Conditional block if "No"
// if (strtolower($readyForUse) === "no") {
//   $html .= '<br><div style="margin-top:10px; color: rgb(61, 61, 233);">
//               <label>If \'No\', recleaning performed and verified:</label> ' . htmlspecialchars($reCleaningVerified) . '
//             </div>';
// }

// $html .= '</td>
//   </tr>
//   <tr>
//     <td>5.4</td>
//     <td>Equipment/Area can be used for routine use</td>
//     <td colspan="10" style="color: rgb(61, 61, 233);">' . htmlspecialchars($canBeUsed) . '</td>
//   </tr>
//   <tr>
//     <td>5.5</td>
//     <td>Verified by IPQA</td>
//     <td colspan="10" style="color: rgb(61, 61, 233);">' . htmlspecialchars($verifiedIpqaBy) . '</td>
//   </tr>
//   <tr>
//     <td><strong>6.0</strong></td>
//     <td colspan="11" style="color: rgb(58, 182, 9); text-align:center;"><strong>Closeout (Engineering)</strong></td>
//   </tr>
//   <tr>
//     <td>6.1</td>
//     <td>Spares handover to stores (After repairs)</td>
//     <td colspan="10" style="color: rgb(61, 61, 233);">Given by (ENG): ' . htmlspecialchars($givenByEng);

// // Conditional block if GIVEN_BY_ENG_VALUE === 'yes'
// if (strtolower($givenByEng) === "yes") {
//   $html .= '<br><div style="margin-top:10px;">
//               <label>Received by (STR):</label> ' . htmlspecialchars($receivedByStr) . '
//             </div>';
// }

// $html .= '</td>
//   </tr>
//   <tr>
//     <td>6.2</td>
//     <td>Machine History Card Update</td>
//     <td colspan="10" style="color: rgb(61, 61, 233);">' . htmlspecialchars($historyCardUpdate) . '</td>
//   </tr>';
//      $html.=' </table>';}
// 	 $pdf->SetY('24');	$pdf->writeHTML($html, true, false, false, false, '');
//         $pdf->Output('','I');
	   
// 	   //}
//         }
else if ($_GET["type"] == "downloadPDF") { 
    $_GET['filename'] = ' ';
    $_GET['pdftype'] = 'onlyheader';  
    include('../pdfimp2.php');

    $sql = "
        SELECT 
            a.*, 
            e.equipment_name 
        FROM 
            job_cards a 
        LEFT JOIN 
            equipment e 
        ON 
            a.equipment_id = e.equipment_code
          WHERE 
            a.id = '". $_GET["id"] ."';
    ";       

    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        // Extract variables from $row for easy usage
        $jobcardNo       = $row['jobcardno'] ?? '';
        $dept            = $row['department'] ?? '';
        $location        = $row['location'] ?? '';
        $equipmentName   = $row['equipment_name'] ?? '';
        $equipmentId     = $row['equipment_id'] ?? '';
        $description     = $row['description'] ?? '';
        $priority        = $row['priority'] ?? '';
        $remark          = $row['remark'] ?? '';
        $identifiedBy    = $row['identified_by'] ?? '';
        $hodChecked      = $row['hod_checked'] ?? '';
        $enggBy          = $row['engg_by'] ?? '';
        $enggRemark      = $row['engg_remark'] ?? '';
        $enggHodBy       = $row['engg_hod_by'] ?? '';
        $shutdownRequired= $row['shutdown_required'] ?? '';
        $ppe             = $row['ppe'] ?? '';
        $actionTaken     = $row['action_taken'] ?? '';
        $sparesUsed      = $row['spares_used'] ?? '';
        $scrapGenerated  = $row['scrap_generated'] ?? '';
        $verificationRemarks = $row['verification_remarks'] ?? '';
        $areaCleaned     = $row['area_cleaned'] ?? '';
        $capaRef         = $row['capa_ref'] ?? '';
        $readyForUse     = $row['ready_for_use'] ?? '';
        $reCleaningVerified = $row['recleaning_verified'] ?? '';
        $canBeUsed       = $row['can_be_used'] ?? '';
        $verifiedIpqaBy  = $row['verified_ipqa_by'] ?? '';
        $givenByEng      = $row['given_by_eng'] ?? '';
        $receivedByStr   = $row['received_by_str'] ?? '';
        $historyCardUpdate = $row['history_card_update'] ?? '';

        $html = '<div></div>
<h2 style="
    color: black; 
    text-align: center; 
    font-size: 17px; 
    letter-spacing: 1.0px; 
    text-shadow: 1px 1px 2px #00000033;
    margin-top: 20px;
    margin-bottom: 20px;
">
    JOB CARD FOR MAINTENANCE WORK
</h2>
<table border="0.1" cellpadding="6"  style="width:100%; border-collapse: collapse; font-family:helvetica;">
 <tr>
  <td width="8%"><strong>1.0</strong></td>
  <td colspan="2" style="color: rgb(61, 61, 233); width: 92%;"><strong>Job card No.:</strong> ' . $row['jobcardno'] . '</td>
</tr>

  <tr>
    <td ><strong>2.0</strong></td>
    <td colspan="2" style="color: rgb(58, 182, 9);"><strong>Details of Job (' . htmlspecialchars($row['devOccuredDept']) . ')</strong></td>
  </tr>
  <tr>
    <td width="8%">2.1</td>
    <td style=" width: 30%;">Department Name:</td>
    <td colspan="3" style="color: rgb(61, 61, 233);width: 62%;">' . htmlspecialchars($row['devOccuredDept']) . '</td>
  </tr>
  <tr>
    <td>2.2</td>
    <td>Area/ Location</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['location']) . '</td>
  </tr>
  <tr>
    <td>2.3</td>
    <td>Equipment/ Instruments ID</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['equipment_name']) . ' (' . htmlspecialchars($row['equipment_id']) . ')</td>
  </tr>
  <tr>
    <td>2.4</td>
    <td>Details of Job/activities</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['description']) . '</td>
  </tr>
  <tr>
    <td>2.5</td>
    <td>Priority</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['priority']) . '</td>
  </tr>
  <tr>
    <td>2.6</td>
    <td>Remark</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['remark']) . '</td>
  </tr>
  <tr>
    <td>2.7</td>
    <td>Job Card raised by:</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['identifiedBy']) . '</td>
  </tr>
  <tr>
    <td>2.8</td>
    <td>Checked by HOD/Shift In charge:</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['intialHodBy']) . '</td>
  </tr>
  <tr>
    <td><strong>3.0</strong></td>
    <td colspan="4" style="color: rgb(58, 182, 9);"><strong>Execution (Engineering)</strong></td>
  </tr>
  <tr>
    <td>3.1</td>
    <td>Job Card received by:</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['EnggBy']) . '</td>
  </tr>
  <tr>
    <td>3.2</td>
    <td>Initial Observation/ Remark:</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['EnggRemark']) . '</td>
  </tr>
  <tr>
    <td>3.3</td>
    <td>Checked by HOD :</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['EnggHodBy']) . '</td>
  </tr>
 
  <tr>
    <td>3.4</td>
    <td>Shutdown Required</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['shutdownRequired']) . '</td>
  </tr>
  <tr>
    <td>3.5</td>
    <td>Protective Equipment</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['ppe']) . '</td>
  </tr>
  <tr>
    <td>3.7</td>
    <td>Engineering Action Taken</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['actionTaken']) . '</td>
  </tr>
  <tr>
    <td>3.8</td>
    <td>Spares Utilized</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['sparesUsed']) . '</td>
  </tr>
  <tr>
    <td>3.9</td>
    <td>Scrap Generated</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['scrapGenerated']) . '</td>
  </tr>
  <tr>
    <td><strong>4.0</strong></td>
    <td colspan="4" style="color: rgb(58, 182, 9); text-align:center;"><strong>Verification of Job after completion</strong></td>
  </tr>
  <tr>
    <td>4.1</td>
    <td>Remarks</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['verificationRemarks']) . '</td>
  </tr>
  <tr>
    <td>4.2</td>
    <td>Area Cleaned</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['areaCleaned']) . '</td>
  </tr>
   <tr>
    <td>4.3</td>
    <td>Job Verified By: Officer</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['VerifiedIPQABy']) . '</td>
  </tr>
    <tr>
    <td>4.4</td>
    <td>User dept. Head / Shift In-charge</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['VerifiedBy']) . '</td>
  </tr>
  <tr>
    <td><strong>5.0</strong></td>
    <td colspan="4" style="color: rgb(58, 182, 9); text-align:center;"><strong>Impact Assessment by QA</strong></td>
  </tr>
  <tr>
    <td>5.1</td>
    <td>CAPA / Change Control Ref</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['capaRef']) . '</td>
  </tr>
    <tr>
    <td>5.2</td>
    <td>Equipment / Area cleaned</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['readyForUse']) . '</td>
  </tr>
  <tr>
    <td>5.3</td>
    <td>Equipment Ready for Routine Use</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['readyForUse']);

if (strtolower($row['readyForUse']) === "no") {
  $html .= '<br><div style="margin-top:10px; color: rgb(61, 61, 233);">
              <label>If \'No\', recleaning performed and verified:</label> ' . htmlspecialchars($row['reCleaningVerified']) . '
            </div>';
}
$html .= '</td>
  </tr>
  <tr>
    <td>5.4</td>
    <td>Verified by IPQA</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['VerifiedIPQABy']) . '</td>
  </tr>
  <tr>
    <td>5.5</td>
    <td>Verified by QA</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['VerifiedIPQAOn']) . '</td>
  </tr>
  <tr>
    <td><strong>6.0</strong></td>
    <td colspan="4" style="color: rgb(58, 182, 9); text-align:center;"><strong>Closeout (Engineering)</strong></td>
  </tr>
  <tr>
    <td>6.1</td>
    <td>Spares handover to stores (After repairs)</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">Given by (ENG): ' . htmlspecialchars($row['givenByEng']);

if (strtolower($row['givenByEng']) === "yes") {
  $html .= '<br><div style="margin-top:10px;">
              <label>Received by (STR):</label> ' . htmlspecialchars($row['receivedByStr']) . '
            </div>';
}

$html .= '</td>
  </tr>
  <tr>
    <td>6.2</td>
    <td>Machine History Card Update</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['historyCardUpdate']) . '</td>
  </tr>
  <tr>
    <td>6.3</td>
    <td>Closed By</td>
    <td colspan="3" style="color: rgb(61, 61, 233);">' . htmlspecialchars($row['CloseoutBy']) . '</td>
  </tr>
</table>';


        $pdf->SetY(24);
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
    } else {
        echo "No job card data found.";
    }
}

else if ($_GET["type"] == "saveMeasuringOperation") 
    {
          $sql = "INSERT INTO measuring_device (plant_id , facility_name, location, dev_name, dev_type, dev_id, 
        manufacturer,model,last_cali_date,next_cali_date,cali_status,  
        area,sp_location,purpose,opert_note,last_maint_date,next_maint_date,maint_notes,dev_custodian,emer_contact,add_contact)
     VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["location"]."', '".$input["dev_name"]."', '".$input["dev_type"]."', '".$input["dev_id"]."',
        '".$input["manufacturer"]."', '".$input["model"]."', '".$input["last_cali_date"]."', '".$input["next_cali_date"]."', '".$input["cali_status"]."',
        '".$input["area"]."','".$input["sp_location"]."','".$input["purpose"]."','".$input["opert_note"]."','".$input["last_maint_date"]."','".$input["next_maint_date"]."','".$input["maint_notes"]."','".$input["dev_custodian"]."','".$input["emer_contact"]."','".$input["add_contact"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "get_measuring_device") {
    
            $sql = "SELECT * FROM measuring_device order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
else if ($_GET["type"] == "savefliter_cleaning_form") {
    
          $sql = "INSERT INTO fliter_cleaning (plant_id , facility_name, dept_location, equipment_type, other_eqp, eqp_id,model,filter_type,other_filter,filter_id,replacement_date,  
           cleaning_date,cleaning_time,cleaning_method,cleaning_solution,initial_pressure,final_pressure,visual_inspection,observation,add_action,personnel_name,personnel_id,reviewed_by,date_review,approval_by,ap_date)
     VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["dept_location"]."', '".$input["equipment_type"]."', '".$input["other_eqp"]."', '".$input["eqp_id"]."',
        '".$input["model"]."', '".$input["filter_type"]."', '".$input["other_filter"]."', '".$input["filter_id"]."', '".$input["replacement_date"]."',
        '".$input["cleaning_date"]."','".$input["cleaning_time"]."','".$input["cleaning_method"]."','".$input["cleaning_solution"]."','".$input["initial_pressure"]."',
        '".$input["final_pressure"]."','".$input["visual_inspection"]."','".$input["observation"]."','".$input["add_action"]."','".$input["personnel_name"]."',
        '".$input["personnel_id"]."','".$input["reviewed_by"]."','".$input["date_review"]."','".$input["approval_by"]."','".$input["ap_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
}
else if ($_GET["type"] == "savecleaning_seheaduleform") {
    
          $sql = "INSERT INTO cleaning_seheadule (plant_id , facility_name, dept_location, equipment_type, other_eqp, eqp_id,model,filter_type,other_filter,filter_id,schedule_id,  
           last_cleaning_date,next_cleaning_date,c_interval,c_frequency,c_accigned,s_approval)
     VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["dept_location"]."', '".$input["equipment_type"]."', '".$input["other_eqp"]."', '".$input["eqp_id"]."',
        '".$input["model"]."', '".$input["filter_type"]."', '".$input["other_filter"]."', '".$input["filter_id"]."', '".$input["schedule_id"]."',
        '".$input["last_cleaning_date"]."','".$input["next_cleaning_date"]."','".$input["c_interval"]."','".$input["c_frequency"]."','".$input["c_accigned"]."',
        '".$input["s_approval"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
}
 

}

$conn->close();
?>