<?php
 
//   ini_set('display_errors', 1);
// error_reporting(E_ALL);
 
    require '../db.php';
    require '../token.php';
    
    require '../tcpdf/tcpdf.php';
    
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
 
     if ($_GET["type"] == "saveRequisition") {
         
        $sql = "INSERT INTO `manpower`(`plant_id`, `project`, `department`, `designation`, `details`, `manpowerRequired`, `replacementEmployee`, 
        `availableManp`, `additionalManp`, `replacementManP`, `intternalDeploymentIfAny`, `skill1`, `skill2`, `skill3`, `educationQualification`, 
        `expRequired`, `noOfpeopleReq`, `timePeriodForReq`, `specificSuggestions`, `remarks`, `status`, `entry_by`, `entry_date`) VALUES('".$_GET["plant_id"]."',
        '".$input["project"]."', '".$input["department"]."', '".$input["designation"]."','".$input["details"]."', '".$input["manpowerRequired"]."', 
        '".$input["replacementEmployee"]."', '".$input["availableManp"]."','".$input["additionalManp"]."','".$input["replacementManP"]."',
        '".$input["intternalDeploymentIfAny"]."','".$input["skill1"]."','".$input["skill2"]."' , '".$input["skill3"]."','".$input["educationQualification"]."' , 
        '".$input["expRequired"]."','".$input["noOfpeopleReq"]."' , '".$input["timePeriodForReq"]."','".$input["specificSuggestions"]."' ,'".$input["remarks"]."' ,
        'Pending','".$_GET["emp_id"]."','$entry_date')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "approveManPower") {
        
        $sql = "Update manpower set  status = '".$input["status"]."', approved_by = '".$_GET["emp_id"]."', approved_date = '$entry_date' 
        where id = '".$input["id"]."' ";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getDepartmentRequisitionLog") {
        $output = array();
         $sql = "SELECT * FROM manpower WHERE plant_id='".$_GET["plant_id"]."' AND department='".$_GET["department"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if ($_GET["type"] == "saveDocument") {
        $sql = "INSERT INTO document_index (plant_id, department, document_name, document_no, document_version, effective_date, next_review_date,document_types,upload,
        entry_date) VALUES ('".$_GET["plant_id"]."', '".$input["department"]."', '".$input["document_name"]."', '".$input["document_no"]."', '".$input["document_version"]."', 
        '".$input["effective_date"]."', '".$input["next_review_date"]."', '".$input["document_types"]."', '".$input["upload"]."', '$entry_date')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getDocument") {
        $output = array();
        $sql = "SELECT * FROM document_index";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getRequisitionLog") {
        $output = array();
        $sql = "SELECT * FROM manpower where plant_id='".$_GET['plant_id']."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRequisitionPendingForApproval") {
        $output = array();
        $sql = "SELECT * FROM manpower where status = 'Pending' AND plant_id='".$_GET['plant_id']."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRequisitionForPlantHeadLog") {
        $output = array();
        $sql = "SELECT * FROM manpower where status != 'Pending' AND plant_id = '".$_GET['plant_id']."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getDesignationByDepartment") {
        $output = array();
        $sql = "SELECT a.id,a.designation_heading,a.designation FROM designation a left join department b ON a.dept_id = b.id where b.department_name = '".$_GET['deptName']."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRequisitionDeptLog") {
        $output = array();
        $sql = "SELECT * FROM manpower where department = '".$_GET['deptName']."' AND plant_id = '".$_GET['plant_id']."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "SaveMasterDocument") {
      
        // $json = file_get_contents('php://input');
        // $input = json_decode($json,true);
       
        
         if($conn->query($sql)){
    	
        $last_id = $conn->insert_id;

        foreach($input["checklistList"] as $checkDtlData){
    		
    	$sql="INSERT INTO mst_chlist_dtl (chklist_id ,check_point ,ch_description,evl_pr,evl_type)
                                value(".$last_id.",
                                      '".$checkDtlData["check_point"]."',
                                      '".$checkDtlData["description"]."',
                                      '".$checkDtlData["evaluation_parameter"]."',
                                      '".$checkDtlData["evaluation_type"]."')";
    	 $conn->query($sql);
        }
    
    
         echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    	 
    else if ($_GET["type"] == "getRequisitionLogIndividual") {
        $_GET['filename'] = 'Manpower Requisition';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');
        $html = '';

        $mpEsc = function ($value) {
            return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        };
        $mpVal = function ($value) use ($mpEsc) {
            $text = trim((string) ($value ?? ''));
            return $text === '' ? 'NA' : $mpEsc($text);
        };
        $mpDate = function ($value) {
            if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                return 'NA';
            }
            $ts = strtotime((string) $value);
            return $ts ? date('d-m-Y', $ts) : 'NA';
        };

        $idEsc = $conn->real_escape_string($_GET['id'] ?? '');
        $sql1 = "SELECT * FROM manpower WHERE id = '" . $idEsc . "' LIMIT 1";
        $result1 = $conn->query($sql1);

        if (!$result1 || $result1->num_rows === 0) {
            $html .= '<p style="text-align:center;">No requisition record found.</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Manpower_Requisition.pdf', 'I');
            exit;
        }

        $row = $result1->fetch_assoc();
        $html .= '<h2 style="text-align:center;color:#a52a2a;">ManPower Requisition Form</h2><br>';
        $html .= '<table cellpadding="4" border="1">
            <tr>
                <td style="font-weight:bolder;width:5%;">1.</td>
                <td style="text-align:left;width:45%;">Name of the Project / Department / Position</td>
                <td style="text-align:left;width:50%;">' . $mpVal($row['project']) . ' / ' . $mpVal($row['department']) . ' / ' . $mpVal($row['designation']) . '</td>
            </tr>
            <tr>
                <td style="font-weight:bolder;">2.</td>
                <td style="text-align:left;">Details Of</td>
                <td style="text-align:left;">' . $mpVal($row['details']) . '</td>
            </tr>
            <tr>
                <td style="font-weight:bolder;">3.</td>
                <td style="text-align:left;">
                    Manpower Required: ' . $mpVal($row['manpowerRequired']) . '<br><br>
                    Details of Replacement: ' . $mpVal($row['replacementEmployee']) . '
                </td>
                <td style="text-align:left;">
                    Available: ' . $mpVal($row['availableManp']) . '<br>
                    Additional: ' . $mpVal($row['additionalManp']) . '<br>
                    Replacement: ' . $mpVal($row['replacementManP']) . '
                </td>
            </tr>
            <tr>
                <td style="font-weight:bolder;">4.</td>
                <td style="text-align:left;">Internal Deployment suggested if any:</td>
                <td style="text-align:left;">' . $mpVal($row['intternalDeploymentIfAny']) . '</td>
            </tr>
            <tr>
                <td style="font-weight:bolder;">5.</td>
                <td style="text-align:left;" colspan="2">Skill sets required:<br>
                    1. ' . $mpVal($row['skill1']) . '<br>
                    2. ' . $mpVal($row['skill2']) . '<br>
                    3. ' . $mpVal($row['skill3']) . '
                </td>
            </tr>
            <tr>
                <td style="font-weight:bolder;">6.</td>
                <td style="text-align:left;">Educational Qualifications Required:</td>
                <td style="text-align:left;">' . $mpVal($row['educationQualification']) . '</td>
            </tr>
            <tr>
                <td style="font-weight:bolder;">7.</td>
                <td style="text-align:left;">Experience Required:</td>
                <td style="text-align:left;">' . $mpVal($row['expRequired']) . '</td>
            </tr>
            <tr>
                <td style="font-weight:bolder;">8.</td>
                <td style="text-align:left;">No. of People and Level required:</td>
                <td style="text-align:left;">' . $mpVal($row['noOfpeopleReq']) . '</td>
            </tr>
            <tr>
                <td style="font-weight:bolder;">9.</td>
                <td style="text-align:left;">Time period for recruitment:</td>
                <td style="text-align:left;">' . $mpVal($row['timePeriodForReq']) . '</td>
            </tr>
            <tr>
                <td style="font-weight:bolder;">10.</td>
                <td style="text-align:left;">Specific suggestions if any for speedy recruitment:</td>
                <td style="text-align:left;">' . $mpVal($row['specificSuggestions']) . '</td>
            </tr>
            <tr>
                <td style="font-weight:bolder;">11.</td>
                <td style="text-align:left;">Remarks:</td>
                <td style="text-align:left;">' . $mpVal($row['remarks']) . '</td>
            </tr>
            <tr>
                <td style="font-weight:bolder;">12.</td>
                <td style="text-align:left;">Manpower Requested by:</td>
                <td style="text-align:left;">' . $mpVal($row['entry_by']) . ' / ' . $mpDate($row['entry_date'] ?? '') . '</td>
            </tr>
            <tr>
                <td style="font-weight:bolder;"></td>
                <td style="text-align:left;">Approvals:</td>
                <td style="text-align:left;">' . $mpVal($row['approved_by']) . ' / ' . $mpDate($row['approved_date'] ?? '') . '</td>
            </tr>
            <tr>
                <td style="font-weight:bolder;"></td>
                <td style="text-align:left;">Status:</td>
                <td style="text-align:left;">' . $mpVal($row['status']) . '</td>
            </tr>
        </table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Manpower_Requisition.pdf', 'I');
        exit;
    }
    else if ($_GET["type"] == "downloadRequisitionLog") {
        $_GET['filename'] = 'Manpower Requisition Log';
        $_GET['pdftype'] = 'landscape';
        $_GET['pdffont'] = 'helvetica';
        $_GET['pdffonts'] = 7;
        include('../pdfimp2.php');
        $html = '';

        $mpEsc = function ($value) {
            return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        };
        $mpVal = function ($value) use ($mpEsc) {
            $text = trim((string) ($value ?? ''));
            return $text === '' ? 'NA' : $mpEsc($text);
        };
        $mpDate = function ($value) {
            if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                return 'NA';
            }
            $ts = strtotime((string) $value);
            return $ts ? date('d-m-Y', $ts) : 'NA';
        };

        $html .= '<h2 style="text-align:center;">Manpower Requisition Log</h2>';
        $html .= '<table border="1" cellpadding="3" cellspacing="0" width="100%">
            <thead>
            <tr style="background-color:#0b6b7a;color:#ffffff;">
                <th width="4%" align="center"><b>Sr</b></th>
                <th width="8%" align="left"><b>Date</b></th>
                <th width="10%" align="left"><b>Project</b></th>
                <th width="10%" align="left"><b>Department</b></th>
                <th width="10%" align="left"><b>Designation</b></th>
                <th width="8%" align="left"><b>Manpower Req.</b></th>
                <th width="12%" align="left"><b>Skill Sets</b></th>
                <th width="10%" align="left"><b>Qualification</b></th>
                <th width="8%" align="left"><b>Status</b></th>
                <th width="10%" align="left"><b>Requested By</b></th>
                <th width="10%" align="left"><b>Approved By</b></th>
            </tr>
            </thead>
            <tbody>';

        $plantId = $conn->real_escape_string($_GET['plant_id'] ?? '');
        $sql = "SELECT * FROM manpower WHERE plant_id = '" . $plantId . "' ORDER BY id DESC";
        $result = $conn->query($sql);
        $i = 1;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr nobr="true">
                    <td width="4%" align="center">' . $i . '</td>
                    <td width="8%" align="left">' . $mpDate($row['entry_date'] ?? '') . '</td>
                    <td width="10%" align="left">' . $mpVal($row['project'] ?? '') . '</td>
                    <td width="10%" align="left">' . $mpVal($row['department'] ?? '') . '</td>
                    <td width="10%" align="left">' . $mpVal($row['designation'] ?? '') . '</td>
                    <td width="8%" align="left">' . $mpVal($row['manpowerRequired'] ?? '') . '</td>
                    <td width="12%" align="left">' . $mpVal($row['skill1'] ?? '') . '</td>
                    <td width="10%" align="left">' . $mpVal($row['educationQualification'] ?? '') . '</td>
                    <td width="8%" align="left">' . $mpVal($row['status'] ?? '') . '</td>
                    <td width="10%" align="left">' . $mpVal($row['entry_by'] ?? '') . '</td>
                    <td width="10%" align="left">' . $mpVal($row['approved_by'] ?? '') . '</td>
                </tr>';
                $i++;
            }
        } else {
            $html .= '<tr><td colspan="11" align="center">No requisition records found.</td></tr>';
        }
        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Manpower_Requisition_Log.pdf', 'I');
        exit;
    }

    

 }else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>