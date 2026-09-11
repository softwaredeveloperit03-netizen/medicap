<?php
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);
    if (!is_array($input)) {
        $input = array();
    }

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
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    function water_fetch_spec_rows($conn, $sql) {
        $output = Array();
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $specNoEsc = $conn->real_escape_string($row["specification_no"]);
                $tests = Array();
                $testResult = $conn->query("SELECT * FROM spec_tests WHERE specification_no='".$specNoEsc."'");
                if ($testResult && $testResult->num_rows > 0) {
                    while ($testRow = $testResult->fetch_assoc()) {
                        $tests[] = $testRow;
                    }
                }
                $row['tests'] = $tests;

                $revisions = Array();
                $revisionResult = $conn->query("SELECT * FROM spec_revision WHERE spec_no='".$specNoEsc."'");
                if ($revisionResult && $revisionResult->num_rows > 0) {
                    while ($revisionRow = $revisionResult->fetch_assoc()) {
                        $revisions[] = $revisionRow;
                    }
                }
                $row['revisions'] = $revisions;
                $output[] = $row;
            }
        }
        return $output;
    }

    if($_GET["type"]=="saveSpecification") {
        $waterCols = array(
            'water_type' => "VARCHAR(255) NULL",
            'spec_type' => "VARCHAR(100) NULL",
            'micro_qty' => "VARCHAR(50) NULL",
            'review_in_months' => "VARCHAR(20) NULL",
            'storage' => "VARCHAR(255) NULL",
            'user_no' => "VARCHAR(50) NULL",
        );
        foreach ($waterCols as $col => $def) {
            $colCheck = @$conn->query("SHOW COLUMNS FROM `specification` LIKE '".$conn->real_escape_string($col)."'");
            if (!($colCheck && $colCheck->num_rows > 0)) {
                @$conn->query("ALTER TABLE `specification` ADD COLUMN `".$col."` ".$def);
            }
        }

        $id = 0;
    	$sql = "SELECT MAX(id) as id FROM specification";
    	$result = $conn->query($sql);
    	if ($result && $result->num_rows > 0) {
    	    while ($row = $result->fetch_assoc()) {
    	        $id = intval($row["id"] ?? 0);
    	    }
    	}
    	$id++;
    	$spec_no = "WS-0".$id;
        $userNo = $conn->real_escape_string($_GET["user_no"] ?? ($_GET["emp_id"] ?? ''));
        $plantId = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $empId = $conn->real_escape_string($_GET["emp_id"] ?? '');
        $waterType = $conn->real_escape_string($input["water_type"] ?? '');
        $versionNo = $conn->real_escape_string($input["version_no"] ?? '00');
        $supersedeNo = $conn->real_escape_string($input["supersede_no"] ?? '');
        $storage = $conn->real_escape_string($input["storage"] ?? '');
        $reviewDate = $conn->real_escape_string($input["review_date"] ?? '');
        $sampleQty = $conn->real_escape_string($input["sample_qty"] ?? '');
        $microQty = $conn->real_escape_string($input["micro_qty"] ?? '');
        $unit = $conn->real_escape_string($input["unit"] ?? ($input["sample_unit"] ?? ''));
        $reviewMonths = $conn->real_escape_string($input["next_review_date"] ?? ($input["review_in_months"] ?? ''));
    	
     	$sql = "INSERT INTO specification (plant_id,user_no, specification_no, water_type,spec_type, version_no, supersede_no, storage,
    	review_date, sample_qty,micro_qty, unit, review_in_months,entry_by, entry_date, status) VALUES ('".$plantId."','".$userNo."','$spec_no',
    	'".$waterType."','Water Specification', '".$versionNo."', '".$supersedeNo."', '".$storage."',
    	'".$reviewDate."', '".$sampleQty."', '".$microQty."', '".$unit."',
    	'".$reviewMonths."','".$empId."',
    	'$entry_date', 'checking')";
     
        if ($conn->query($sql)) {
            $experience_company = isset($input["tests"]) && is_array($input["tests"]) ? $input["tests"] : array();
    		$len = count($experience_company);
    		for($i = 0; $i<$len; $i++) {
    			$data = is_array($experience_company[$i]) ? $experience_company[$i] : array();
    	  
     			$sql="INSERT INTO spec_tests (plant_id,user_no, specification_no,test_type, test, subtest,description,limit_type,lower_limit,upper_limit, 
    			unit, sample_qty,limits) VALUES ('".$plantId."','".$userNo."','".$spec_no."','".$conn->real_escape_string($data["test_type"] ?? '')."','".$conn->real_escape_string($data["test"] ?? '')."','".$conn->real_escape_string($data["subtest"] ?? '')."','".$conn->real_escape_string($data["descr"] ?? ($data["description"] ?? ''))."',
    			'".$conn->real_escape_string($data["limit"] ?? ($data["limit_type"] ?? ''))."','".$conn->real_escape_string($data["lower_limit"] ?? '')."','".$conn->real_escape_string($data["upper_limit"] ?? '')."','".$conn->real_escape_string($data["unit"] ?? '')."', '".$conn->real_escape_string($data["sample_qty"] ?? '')."','".$conn->real_escape_string($data["limits"] ?? '')."')";
    			$conn->query($sql);
    	    } 
    	    $revisionHistory = isset($input["revisionHistory"]) && is_array($input["revisionHistory"]) ? $input["revisionHistory"] : array();
    	    $len = count($revisionHistory);
    	    for ($i =0; $i < $len; $i++) {
    	        $data = is_array($revisionHistory[$i]) ? $revisionHistory[$i] : array();
    	        $sql = "INSERT INTO spec_revision (user_no, spec_no, specification_no, version_no, change_mode, reason, effective_date) VALUES 
    	        ('".$userNo."','".$spec_no."','".$conn->real_escape_string($data["spec_no"] ?? '')."','".$conn->real_escape_string($data["ver_no"] ?? ($data["version_no"] ?? ''))."','".$conn->real_escape_string($data["change_mode"] ?? '')."','".$conn->real_escape_string($data["change_reason"] ?? ($data["reason"] ?? ''))."', 
    	        '".$conn->real_escape_string($data["effective_date"] ?? '')."')";
    	        $conn->query($sql);
    	    }
    	    
            echo "{\"status\":\"success\"}";
        } else {
            echo json_encode(array('status' => 'failed', 'message' => $conn->error));
        }
    } else if ($_GET["type"] == "getPendingSpecifications") {
        $plantEsc = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $sql = "SELECT * FROM specification
                WHERE plant_id='".$plantEsc."'
                AND spec_type='Water Specification'
                AND (
                    status IN ('checking', 'Checking', 'pending')
                    OR status IS NULL
                    OR TRIM(status) = ''
                )
                ORDER BY id DESC";
        echo json_encode(water_fetch_spec_rows($conn, $sql));
    } else if ($_GET["type"] == "checkSpecification") {
        $sql = "UPDATE specification SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getCheckedSpecifications") {
        $plantEsc = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $sql = "SELECT * FROM specification
                WHERE plant_id='".$plantEsc."'
                AND spec_type='Water Specification'
                AND status IN ('pending_approval', 'checked')
                ORDER BY id DESC";
        echo json_encode(water_fetch_spec_rows($conn, $sql));
    } else if ($_GET["type"] == "approveSpecification") {
        $sql = "UPDATE specification SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getSpecificationsLog") {
        $plantEsc = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $waterEsc = $conn->real_escape_string($_GET["water_type"] ?? '');
        $statusEsc = $conn->real_escape_string($_GET["status"] ?? '');
        $sql = "SELECT * FROM specification WHERE plant_id='".$plantEsc."'
        AND spec_type='Water Specification' AND water_type LIKE '%".$waterEsc."%'
        AND (status LIKE '%".$statusEsc."%' OR status IS NULL OR status='')
        ORDER BY id DESC";
        echo json_encode(water_fetch_spec_rows($conn, $sql));
    } else if ($_GET["type"] == "approveWaterSpecification") {
        $idEsc = (int)($_GET["id"] ?? 0);
        $newStatus = $conn->real_escape_string($_GET["new_status"] ?? 'approved');
        $setParts = Array("status='".$newStatus."'");
        if ($newStatus == 'pending_approval') {
            $setParts[] = "check_by='".$_GET["emp_id"]."'";
            $setParts[] = "check_date='$entry_date'";
        } else if ($newStatus == 'approved' || $newStatus == 'reject' || $newStatus == 'rejected') {
            $setParts[] = "approve_by='".$_GET["emp_id"]."'";
            $setParts[] = "approve_date='$entry_date'";
        }
        $sql = "UPDATE specification SET ".implode(", ", $setParts)." WHERE id='".$idEsc."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "downloadSpecificationsLog") {
        $_GET['filename'] = 'Water Specification'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Water Specification Log</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 12%;">Spec No</td>
                        <td style="width: 16%;">Water Type</td>
                        <td style="width: 10%;">Version No</td>
                        <td style="width: 14%;">Supersede No</td>
                        <td style="width: 12%;">Sample Qty</td>
                        <td style="width: 14%;">Effective Date</td>
                        <td style="width: 12%;">Revision Period</td>
                        <td style="width: 10%;">Status</td>
                    </tr>
                </thead>';
      	$sql = "SELECT * FROM specification WHERE plant_id='".$_GET["plant_id"]."' AND spec_type='Water Specification'";
        if (!empty($_GET["water_type"])) {
            $sql .= " AND water_type LIKE '%".$conn->real_escape_string($_GET["water_type"])."%'";
        }
        if (!empty($_GET["status"])) {
            $sql .= " AND status LIKE '%".$conn->real_escape_string($_GET["status"])."%'";
        }
        $sql .= " AND status != 'Rejected' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                    <td style="width: 12%;">'.htmlspecialchars($row['specification_no'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td style="width: 16%;">'.htmlspecialchars($row['water_type'] ?? $row['spec_type'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td style="width: 10%;">'.htmlspecialchars($row['version_no'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td style="width: 14%;">'.htmlspecialchars($row['supersede_no'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td style="width: 12%;">'.htmlspecialchars($row['sample_qty'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td style="width: 14%;">'.htmlspecialchars($row['effective_date'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td style="width: 12%;">'.htmlspecialchars($row['retest_period'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td style="width: 10%;">'.htmlspecialchars($row['status'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" style="text-align:center;">No water specification records found.</td></tr>';
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('SpecificationLog.pdf', 'I');
        exit;
    } else if($_GET['type'] == 'downloadSpecificationsRecord') { 
    
        $_GET['filename'] = 'Identification of Training Needs'; 
        $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");

  $sql = "SELECT * FROM specification WHERE user_no='".$_GET["user_no"]."' 
        AND spec_type='Water Specification' AND water_type LIKE '%".$_GET["water_type"]."%' 
        AND status LIKE '%".$_GET["status"]."%'";         
             if($result->num_rows > 0) {
                $counter = 1;
              $result = $conn->query($sql); {
                     
                   
                 
                    $html.='
                    <table cellpadding="5">
                        <tr>
                            <td style="width:30%;"><b>Specification No::</b></td>
                            <td style="width:70%">'.$row['specification_no'].'</td>
                        </tr>
                        <tr>
                            <td><b>Specification Type:</b></td>
                            <td>'.$row['spec_type'].'</td>
                        </tr>
                        <tr>
                            <td><b>Water Type::</b></td>
                            <td>'.$row["trainer_name"].'</td>
                        </tr>
                        <tr>
                            <td><b>Version No:</b></td>
                            <td>'.$row['proposed_date'].'</td>
                        </tr>
                        <tr>
                            <td><b>Supersede No:</b></td>
                            <td>'.$row['training_need'].'</td>
                        </tr>
                        </table>';
                    $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                    
                    $html.='<br><br><table cellpadding="5">
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width:10%;">Sr.</td>
                            <td style="width:24%;">Name of Employee</td>
                            <td style="width:22%;">Employee Code</td>
                            <td style="width:22%;">Department</td>
                            <td style="width:22%;">Designation</td>
                        </tr>';
                        $counter = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                            $result2 = $conn->query($sql2);
                            $row2 = $result2->fetch_assoc();
                            $html.='
                            <tr>
                                <td style="width:10%;">'.$counter++.'</td>
                                <td style="width:24%;">'.$row2['firstname'].''.$row2['lastname'].'</td>
                                <td style="width:22%;">'.$row1['emp_id'].'</td>
                                <td style="width:22%;">'.$row2['department'].'</td>
                                <td style="width:22%;">'.$row2['designation'].'</td>
                            </tr>';
                        }
                        $html.='</table>';
                    }
                }
                $html.='<div></div>';
                
               EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
                    
            } else{
                echo 'No Record';
            }
    }
}

$conn->close();
?>