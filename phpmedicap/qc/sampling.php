<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    mysqli_report(MYSQLI_REPORT_OFF);
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    if (!is_array($input)) {
        $input = array();
    }
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
function formatDurationText($seconds) {
    $seconds = intval($seconds);
    if ($seconds <= 0) { return "-"; }
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $h, $m, $s);
}

function medicap_sampling_scope_sql($conn, $alias = 'a') {
    $scope = isset($_GET['sampling_scope']) ? trim((string)$_GET['sampling_scope']) : '';
    if ($scope !== 'retest') {
        return '';
    }
    require_once __DIR__.'/../store/retest_helpers.php';
    return ' AND '.medicap_retest_sampling_scope_sql($alias);
}

function medicap_sampling_material_type_sql($conn, $alias = 'c') {
    $scope = isset($_GET['sampling_scope']) ? trim((string)$_GET['sampling_scope']) : '';
    $mt = isset($_GET['material_type']) ? trim((string)$_GET['material_type']) : '';
    if ($scope === 'retest' && $mt === '') {
        return '';
    }
    if ($mt === '') {
        $mt = 'Raw Material';
    }
    return " AND ".$alias.".material_type = '".$conn->real_escape_string($mt)."'";
}

function medicap_retest_sampling_next_step_label($testingStatus, $testingNo) {
    if (trim((string)$testingNo) === '') {
        return 'QC Testing → Allocation';
    }
    $st = strtolower(trim((string)$testingStatus));
    if ($st === 'pending') {
        return 'QC Testing → Allocation';
    }
    if ($st === 'allocated') {
        return 'QC Testing → Awaiting';
    }
    if (in_array($st, array('awaiting', 'inprocess', 'in progress', 'testing'), true)) {
        return 'QC Testing → Testing / Checking';
    }
    if (in_array($st, array('approved', 'complete', 'completed', 'checked'), true)) {
        return 'QC Testing → Log';
    }
    return 'QC Testing → ' . trim((string)$testingStatus);
}

function getSamplingActivityMeta($action) {
    $module = "Sampling";
    $form = "Raw Sampling";
    $nature = "View";
    if ($action == "getSamplings") { $form = "Sampling Log"; }
    else if ($action == "getActiveSamplings") { $form = "Checking"; }
    else if ($action == "updateActiveSampling" || $action == "updateCheckedSampling" || $action == "updateRejectedSampling") { $form = "Checking / Approval"; $nature = "Verification / Approval"; }
    else if ($action == "getCheckedSamplings" || $action == "getRejectedSamplings") { $form = "Approval / Correction"; }
    else if ($action == "allocatePerson") { $form = "Allocation"; $nature = "Allocation"; }
    else if ($action == "saveAreaCleaningRecord" || $action == "saveBalanceCleaningRecord") { $form = "Pre Sampling"; $nature = "Create / Submit"; }
    return array("module"=>$module, "form"=>$form, "nature"=>$nature);
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
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    $sqlLog = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR)
               VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sqlLog);

    if ($_GET["type"] == "getMaterials") {
        $output = Array();
        $sql = "SELECT * FROM material WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getSamplingAuditTrail") {
        $output = array();
        $from_date = isset($_GET["from_date"]) ? $_GET["from_date"] : date("Y-m-d", strtotime("-30 days"));
        $to_date = isset($_GET["to_date"]) ? $_GET["to_date"] : date("Y-m-d");
        $sql = "SELECT l.action,l.actiontime,l.REMOTE_ADDR,l.emp_id,l.department,l.token,
                TRIM(CONCAT(COALESCE(e.firstname,''), ' ', COALESCE(e.lastname,''))) as done_by_name,
                e.department as emp_department
                FROM log l
                LEFT JOIN employee e ON l.emp_id = e.emp_id
                WHERE DATE(l.actiontime) BETWEEN '".$from_date."' AND '".$to_date."'
                AND l.action != 'getSamplingAuditTrail'
                AND l.action IN (
                    'getSamplings','getActiveSamplings','getCheckedSamplings','getRejectedSamplings',
                    'allocatePerson','saveAreaCleaningRecord','saveBalanceCleaningRecord',
                    'updateActiveSampling','updateCheckedSampling','updateRejectedSampling'
                )
                ORDER BY l.actiontime DESC
                LIMIT 2000";
        $result = $conn->query($sql);
        $lastActionByToken = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $meta = getSamplingActivityMeta($row["action"]);
                $doneByName = trim($row["done_by_name"]) == "" ? $row["emp_id"] : trim($row["done_by_name"]);
                $doneByDept = trim($row["emp_department"]) == "" ? $row["department"] : trim($row["emp_department"]);
                $duration = "-";
                $tokenKey = isset($row["token"]) ? $row["token"] : "";
                if ($tokenKey != "" && isset($lastActionByToken[$tokenKey])) {
                    $prevTime = strtotime($lastActionByToken[$tokenKey]);
                    $currTime = strtotime($row["actiontime"]);
                    if ($prevTime !== false && $currTime !== false) {
                        $duration = formatDurationText(abs($prevTime - $currTime));
                    }
                }
                if ($tokenKey != "") { $lastActionByToken[$tokenKey] = $row["actiontime"]; }
                $output[] = array(
                    "activity_module" => $meta["module"],
                    "form" => $meta["form"],
                    "activity" => $row["action"],
                    "activity_nature" => $meta["nature"],
                    "done_by" => $doneByName,
                    "department" => $doneByDept,
                    "date_time" => $row["actiontime"],
                    "ip_address" => $row["REMOTE_ADDR"],
                    "duration_of_login" => $duration
                );
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getQcPersons") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE department='Quality Control'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getQcSampling") {
        $output = Array();
        $sql = "SELECT a.*, b.material_name AS material_name
            FROM specification a 
            LEFT JOIN material b 
            ON a.material_code = b.material_code ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     
 
 
    else if ($_GET["type"] == "allocatePerson") {
        
        $flag = 0;
        $mats = $input['materials'];
        for ($i = 0; $i < count($mats); $i++) {
            
            $temp = $mats[$i];
            
            $sql = "UPDATE sampling SET sampling_person = '".$input["sampling_person"]."', micro_person = '".$input["micro_person"]."',  alternate_qc_person = '".$input["alternate_qc_person"]."', 
            alternate_micro_person = '".$input["alternate_micro_person"]."', alloocationBy = '".$_GET['emp_id']."', alloocationOn = '$entry_date' , status = 'Allocated' WHERE id = '".$temp["id"]."'";
             
            if ($conn->query($sql)) {
                $flag = 0;
            }else{
                $flag = 1;
                break;
            }
            
        }
        
        if ($flag == 0) {
             echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
     
    }
    else if ($_GET["type"] == "saveAreaCleaningRecord") {
        if (empty($input["id"])) {
            echo json_encode(array("status" => "invalid", "message" => "Missing sampling id"));
            exit;
        }
        $id = intval($input["id"]);
        $cleaningDate = $conn->real_escape_string($input["cleaningDate"] ?? '');
        $cleaningTime = $conn->real_escape_string($input["cleaningTime"] ?? '');
        $cleaningDoneBy = $conn->real_escape_string($input["cleaningDoneBy"] ?? '');
        $cleanRemark = $conn->real_escape_string($input["cleanRemark"] ?? '');
        $checklistJson = $conn->real_escape_string(json_encode(isset($input["areaCleaningChecklist"]) ? $input["areaCleaningChecklist"] : array()));
        $agentsJson = $conn->real_escape_string(json_encode(isset($input["cleaningAgentsUsed"]) ? $input["cleaningAgentsUsed"] : array()));
        $empId = $conn->real_escape_string($_GET['emp_id'] ?? '');

        $baseSet = "`cleaningDate` = '".$cleaningDate."', `cleaningTime` = '".$cleaningTime."', `cleaningDoneBy` = '".$cleaningDoneBy."', 
        `cleanRemark` = '".$cleanRemark."', `areaCleaningChecklist` = '".$checklistJson."', `cleaningEntryBy` = '".$empId."', 
        `cleaningEntryOn` = '$entry_date', status = 'Area_Cleaning_Done'";

        $sql = "UPDATE `sampling` SET ".$baseSet.", `cleaningAgentsUsed` = '".$agentsJson."' WHERE id = '".$id."'";
        $ok = $conn->query($sql);
        if (!$ok) {
            $sql = "UPDATE `sampling` SET ".$baseSet." WHERE id = '".$id."'";
            $ok = $conn->query($sql);
        }
        echo $ok ? json_encode(array("status" => "success")) : json_encode(array("status" => "failed", "message" => $conn->error));
    }
    else if ($_GET["type"] == "saveBalanceCleaningRecord") {
        $mag1 = $conn->real_escape_string(isset($input["weighBalMagnehelic1"]) ? $input["weighBalMagnehelic1"] : '');
        $mag2 = $conn->real_escape_string(isset($input["weighBalMagnehelic2"]) ? $input["weighBalMagnehelic2"] : '');
        $mag3 = $conn->real_escape_string(isset($input["weighBalMagnehelic3"]) ? $input["weighBalMagnehelic3"] : '');
        $performed = $conn->real_escape_string(isset($input["weighBalCleaningPerformed"]) ? $input["weighBalCleaningPerformed"] : '');

        $sql = "UPDATE `sampling` SET `weighBalCleanDate` = '".$input["weighBalCleanDate"]."', `weighBalCleanStartTime` = '".$input["weighBalCleanStartTime"]."', `weighBalCleanEndTime` = '".$input["weighBalCleanEndTime"]."', 
        `weighBalCleanBalanceEq` = '".$input["weighBalCleanBalanceEq"]."', `weighBalCleanDoneBy` = '".$input["weighBalCleanDoneBy"]."', `balanceCleaningChecklist` = '".json_encode($input["balanceCleaningChecklist"])."', 
        `wbCheanEntryBy` = '".$_GET['emp_id']."', `wbCheanEntryOn` = '$entry_date',
        `weighBalMagnehelic1` = '".$mag1."', `weighBalMagnehelic2` = '".$mag2."', `weighBalMagnehelic3` = '".$mag3."',
        `weighBalCleaningPerformed` = '".$performed."' , status = 'Balance_Cleaning_Done' WHERE id = '".$input["id"]."'";

        $ok = $conn->query($sql);
        if (!$ok) {
            $sql = "UPDATE `sampling` SET `weighBalCleanDate` = '".$input["weighBalCleanDate"]."', `weighBalCleanStartTime` = '".$input["weighBalCleanStartTime"]."', `weighBalCleanEndTime` = '".$input["weighBalCleanEndTime"]."', 
            `weighBalCleanBalanceEq` = '".$input["weighBalCleanBalanceEq"]."', `weighBalCleanDoneBy` = '".$input["weighBalCleanDoneBy"]."', `balanceCleaningChecklist` = '".json_encode($input["balanceCleaningChecklist"])."', 
            `wbCheanEntryBy` = '".$_GET['emp_id']."', `wbCheanEntryOn` = '$entry_date' , status = 'Balance_Cleaning_Done' WHERE id = '".$input["id"]."'";
            $ok = $conn->query($sql);
        }
        echo $ok ? "{\"status\":\"success\"}" : "{\"status\":\"failed\"}";
    }



else if ($_GET["type"] == "saveSamplingRequest") {
    $sql1 = "SELECT * FROM specification WHERE material_code='".$input["material_code"]."' AND spec_type='Raw Material Specification' AND status='approve'";
    $result1 = $conn->query($sql1);
    if ($result1->num_rows > 0) {
        while ($row1 = $result1->fetch_assoc()) {
            $input["specification_no"] = $row1["specification_no"];
            break;
        }
    } else {
        $input["specification_no"] = "";
    }

    $sql = "INSERT INTO sampling (plant_id,material_code, batch_no, containers, grn_no, grn_date, mfg_date, exp_date, sampling_person,
    request_by, request_date, specification_no) VALUES ('".$_GET["plant_id"]."','".$input["material_code"]."', '".$input["batch_no"]."', '".$input["containers"]."', 
    '".$input["grn_no"]."', '".$input["grn_date"]."','".$input["mfg_date"]."', '".$input["exp_date"]."',
    '".$input["sampling_person"]."', '".$_GET["emp_id"]."', '$entry_date', '".$input["specification_no"]."')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}

else if ($_GET["type"] == "saveRetestSamplingRequest") {
    require_once __DIR__.'/../store/retest_helpers.php';
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(medicap_send_retest_grn($conn, $input, $_GET['plant_id'] ?? '', $_GET['emp_id'] ?? '', $entry_date));
}
else if ($_GET["type"] == "downloadSamplings") {
        $_GET['filename'] = 'Products'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $html.='
        <h2 style="text-align:cenetr">Sampling Label Printing</h2>
       <table border="1" cellpadding="4" style="width: 100%; border-collapse: collapse; text-align: left;">
    <thead>
        <tr style="background-color: #0E4370; color: white;">
            <th>Sr No.</th>
            <th>Material Name</th>
            <th>Material Code</th>
            <th>Batch No.</th>
            <th>Containers</th>
            <th>GRN No.</th>
            <th>GRN Date</th>
            <th>Sampling Person</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
            </thead>';

        $sql = "SELECT a.*, b.material_name AS material_name
        FROM specification a 
        LEFT JOIN material b 
        ON a.material_code = b.material_code ";

//   $sql = "SELECT * FROM specification  WHERE plant_id='".$_GET["plant_id"]."'  order by 1 desc";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                            <td > '.$row['material_name'].'</td>
                            <td> '.$row['control_sample'].' </td>
                            <td>'.$row['chemical_qty'].' </td>
                            <td>'.$row['micro_qty'].' </td>
                            <td> '.$row['totalsample_qty'].'</td>
                            <td> '.$row['entry_by'].'</td>
                            <td>'.$row['check_by'].' </td>
                              <td>'.$row['micro_qty'].' </td>
                            <td> '.$row['totalsample_qty'].'</td>
                            <td> '.$row['entry_by'].'</td>
                        </tr>';
                        $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('SamplingQuantity.pdf', 'I');
    }

else if ($_GET["type"] == "downloadSamplingQuantity") {
        $_GET['filename'] = 'Products'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $html.='
        <h2 style="text-align:cenetr">Sampaling Quantity</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                        <th>NAME OF THE RAW MATERIAL </th>
                        <th>RESERVE SAMPLE QTY.(gms)</th>
                        <th colspan="2">QTY.(gm) OF SAMPLE FOR</th>
                        <th>TOTAL SAMPLE QTY.(gm)</th>
                        <th>ASSIGNED BY</th>
                        <th>VERIFIED BY</th>
                    </tr>
                    <tr>
                        <th style="border-right:none !important"></th>
                        <th></th>
                        <th>CHEMICAL ANALYSIS</th>
                        <th>MICROBIOLOGY ANALYSIS</th>
                        <th style="border-right:none"></th>
                        <th style="border-right:none"></th>
                        <th></th>
                    </tr>
            </thead>';

        $sql = "SELECT a.*, b.material_name AS material_name
        FROM specification a 
        LEFT JOIN material b 
        ON a.material_code = b.material_code ";

//   $sql = "SELECT * FROM specification  WHERE plant_id='".$_GET["plant_id"]."'  order by 1 desc";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                            <td > '.$row['material_name'].'</td>
                            <td> '.$row['control_sample'].' </td>
                            <td>'.$row['chemical_qty'].' </td>
                            <td>'.$row['micro_qty'].' </td>
                            <td> '.$row['totalsample_qty'].'</td>
                            <td> '.$row['entry_by'].'</td>
                            <td>'.$row['check_by'].' </td>
                        </tr>';
                        $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('SamplingQuantity.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadLAfPDF") {
        $_GET['filename'] = 'Products'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        
        $html.='
        <h2 style="text-align:cenetr">OPERATION & CLEANING LOG FOR SAMPLING AND DISPENSING ROOM</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                        <th style="text-align:center;" colspan="7">RFAF Operation Log</th>
                        <th style="text-align:center;" colspan="3">Sampling Log</th>
                        <th style="text-align:center;" colspan="4">Dispensing</th>
                         <th style="text-align:center;" colspan="4">Area Cleaning Log</th>
                    </tr>
                    <tr>
                        <th style="text-align:center;">Date</th>
                        <th style="text-align:center;">LAF Start time</th>
                        <th style="text-align:center;">From</th>
                        <th style="text-align:center;">To</th>
                        <th style="text-align:center;">Utensil ID no.</th>
                        <th style="text-align:center;">*P Across HEPA</th>
                        <th style="text-align:center;">*P Across Pre-filter</th>
                        
                        <th style="text-align:center;">Material Name</th>
                        <th style="text-align:center;"> GRN No./ B. No.</th>
                        <th style="text-align:center;">Done By</th>
                        
                        
                        <th style="text-align:center;"> Product/Material Name</th>
                        <th style="text-align:center;"> B No. / Medicap lot no</th>
                        <th style="text-align:center;"> Batch Size </th>
                        <th style="text-align:center;">Done by</th>
                        
                        <th style="text-align:center;">From </th>
                        <th style="text-align:center;">To</th>
                        <th style="text-align:center;">Done By</th>
                        <th style="text-align:center;">Checked By</th>
                    </tr>
            </thead>';
$sql="SELECT a.*, b.material_name AS material_name
        FROM activity a 
        LEFT JOIN material b 
        ON a.plant_id = b.material_name";
     $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                            <td >' . $row['entry_date'] . ' </td>
                            <td >' . $row['from_time'] . ' </td>
                            <td >' . $row['start_date'] . ' </td>
                            <td >' . $row['end_date'] . ' </td>
                            <td >' . $row['equipment_code'] . ' </td>
                            <td > </td>
                            <td > </td>
                            <td >' . $row['material_name'] . '  </td>
                            <td> </td>
                            <td> </td>
                            <td></td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                            <td> ' . $row['start_date'] . ' </td>
                            <td> ' . $row['end_date'] . ' </td>
                            <td>' . $row['labour_no'] . '  </td>
                            <td> ' . $row['entry_by'] . ' </td>
                        </tr>';
                        $i++;
            }
        }


        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('SamplingLAf.pdf', 'I');
    }

else if ($_GET["type"] == "downloadSamplingIdentificationLable") {
        $_GET['filename'] = 'Products'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $html.='
        <h2 style="text-align:cenetr">SAMPLE FOR IDENTIFICATION TEST</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <td colspan="4" style="text-align: center;">
                        <U> <b> SAMPLE FOR IDENTIFICATION TEST </b></U>
                    </td>
                </tr>
                <tr>
                    <td>
                        Material Name:
                    </td>
                    <td colspan="3"></td>
                </tr>
                <tr>
                    <td>
                        GRN No.
                    </td>
                    <td>

                    </td>
                    <td>
                        Batch No.
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        Container No.
                    </td>
                    <td></td>
                    <td>of</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Sign. & Date </td>
                    <td colspan="3"></td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align: right;">
                        Number
                    </td>
                </tr>
            </thead>';
    
        $result = $conn->query($sql);
       
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('SamplingIdentificationLable.pdf', 'I');
    }
    
    else if ($_GET["type"] == "downloadSamplingPlan") {
        $_GET['filename'] = 'Products'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $html.='
        <h2 style="text-align:cenetr">VALIDATED SAMPLING PLAN FOR RAW MATERIAL</h2>
        <table border="1" cellpadding="5">
            <thead>
               <tr>
                        <th>NAME OF MATERIAL </th>
                        <th>MANUFACTURER NAME</th>
                        <th>SUPPLIER NAME</th>
                        <th>SAMPLING PLAN (containers)</th>
                        <th>ASSIGNED BY</th>
                        <th>VERIFIED BY</th>
                    </tr>
            </thead>';
            

    $sql="SELECT a.*, b.material_name FROM vendor a LEFT JOIN material b ON a.material_code = b.material_code";
       $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                            <td> ' . $row['material_name'] . ' </td>
                            <td> ' . $row['vendor_name'] . '</td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                            <td> ' . $row['entry_by'] . '</td>
                            <td> ' . $row['entry_approve'] . '</td>
                        </tr>';
                        $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('SamplingPlan.pdf', 'I');
    }


else if ($_GET["type"] == "downloadReserveSamplingLable") {
        $_GET['filename'] = 'Products'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $html.='
        <h2 style="text-align:cenetr"> RESERVE SAMPLE LABLE</h2>
        <table border="1" cellpadding="5">
            <thead>
               <tr>
                    <td colspan="4" style="text-align: center;">
                        <U> <b> RESERVE SAMPLE LABLE </b></U>
                    </td>
                </tr>
                <tr>
                    <td>
                        MATERIAL NAME:
                    </td>
                    <td colspan="3"></td>
                </tr>
                <tr>
                    <td>
                        GRN No.
                    </td>
                    <td>

                    </td>
                    <td>
                        A.R. No.
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        MFG. DATE:
                    </td>
                    <td>
                
                    </td>
                    <td>
                        EXP. DATE
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td>B. NO.</td>
                    <td colspan="3"></td>
                </tr>
                <tr>
                    <td>
                        MRF.:
                    </td>
                    <td>
                
                    </td>
                    <td>
                        SUPP.:
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td>STORAGE CONDITION.</td>
                    <td colspan="3"></td>
                </tr>
                <tr>
                    <td>
                        QTY. OF RESERVE SAMPLE:
                    </td>
                    <td>
                
                    </td>
                    <td>
                        TO BE DISTROYED ON:
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        SAMPLED BY:
                    </td>
                    <td>
                
                    </td>
                    <td>
                        DATE:
                    </td>
                    <td></td>
                </tr>
               
                <tr>
                    <td colspan="4" style="text-align: right;">
                        Number
                    </td>
                </tr>
            </thead>';
    
        $result = $conn->query($sql);
       
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ReserveSamplingLable.pdf', 'I');
    }


else if ($_GET["type"] == "getSamplingRecords") {
    $output = Array();
    $sql = "SELECT * FROM sampling";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingSamplings") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status = 'inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            if ($row["area_status"] == "complete") {
                $row["area_details"] = json_decode($row["area_details"]);
            }

            if ($row["clearance_status"] !== 'pending') {
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];

                        if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                            $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                            $conn->query($sql2);
                            $row["clearance_status"] = "complete";
                        }
                        break;
                    }
                }

                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["material_code"] = $row1["material_no"];
                        $temp["batch_no"] = $row1["batch_no"];
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
            }

            if ($row["specification_no"] == "") {
                $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."' AND spec_type='Raw Material Specification' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["specification_no"] = $row1["specification_no"];
                        
                        $sql1 = "UPDATE sampling SET specification_no='".$row["specification_no"]."' WHERE id='".$row["id"]."'";
                        $conn->query($sql1);
                        break;
                    }
                }
            }

            $sql1 = "SELECT * FROM specification WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["specification_no"] = $row1["specification_no"];
                    $row["composite_qty"] = +$row1["sample_qty"];
                    $row["unit"] = $row1["unit"];

                    $sql2 = "SELECT IFNULL(SUM(sample_qty), 0) as identication_qty FROM spec_tests WHERE specification_no='".$row1["specification_no"]."' AND test='Identification'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["identication_qty"] = +$row2["identication_qty"];
                        }
                    } else {
                        $row["identication_qty"] = 0;
                    }
                    $row["actual_indentification"] = number_format(+$row["identication_qty"] * 2, 2);
                    $row["actual_composite"] = number_format(+$row["composite_qty"] * 2, 2);
                    break;
                }
            }

            if ($row["specification_no"] == "") {
                $row["current_status"] = "Specification not Available";
            } else if ($row["area_status"] == 'pending') {
                $row["current_status"] = 'Area Checkpoints';
            } else if ($row["area_status"] == 'complete' && $row["clearance_status"] == 'pending') {
                $row["current_status"] = 'Line Clearance';
            } else if ($row["area_status"] == 'complete' && $row["clearance_status"] == 'inprocess') {
                $row["current_status"] = 'Line Clearance';
            } else if ($row["clearance_status"] == 'complete' && $row["area_status"] == 'complete' && $row["sample_status"] == 'pending') {
                $row["current_status"] = 'Sampling Information';
                $output1 = Array();
                for ($i = 0; $i < +$row["containers"]; $i++) {
                    $temp = Array();
                    $temp['container_no'] = $i + 1;
                    $temp['identication_qty'] = +$row["identication_qty"];
                    $temp['composite_qty'] = +$row["composite_qty"];
                    $temp['status'] = "pending";
                    $output1[] = $temp;
                }
                $row["container_details"] = $output1;
            }

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingAreaCheckpoints") {
    $output = Array();
    if($_GET['for']=='oos'){
        
     $sql="SELECT s.*  FROM `sampling` s WHERE s.oos_sampling='Allocate'  ORDER BY s.allocation_date DESC";
    }else{
        
      $sql="SELECT s.*  FROM `sampling` s WHERE s.status='allocate' AND s.area_status='pending' ORDER BY s.allocation_date DESC";
    }
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            $sql11 = "select * from newoos where material_code='".$row["material_code"]."' and batch_no='".$row["batch_no"]."'";
            $result11 = $conn->query($sql11);
            if ($result11->num_rows > 0) {
                while ($row11 = $result11->fetch_assoc()) {
                    $row["new_oos_id"] = $row11["id"];
                    
                }
            }
            $sql1 = "SELECT m.* , (select GROUP_CONCAT(grade.grade) from grade where FIND_IN_SET(grade.id , m.grade)) as gradeName
                 FROM material m WHERE m.material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                    $row["gradeName"] = $row1["gradeName"];
                }
            }
            
            
                    
            $sql1 = "SELECT firstname,emp_id FROM employee  WHERE emp_id ='".$row["sampling_person"]."' AND plant_id='".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["sampling_person_name"] = $row1["firstname"]."-".$row1["emp_id"] ;
                }
            }
            
            
            
            

            if ($row["area_status"] !== "pending") {
                $row["area_details"] = json_decode($row["area_details"]);
            }

            if ($row["specification_no"] == "") {
                $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."' AND spec_type='Raw Material Specification' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["specification_no"] = $row1["specification_no"];
                        
                        $sql1 = "UPDATE sampling SET specification_no='".$row["specification_no"]."' WHERE id='".$row["id"]."'";
                        $conn->query($sql1);
                        break;
                    }
                }
            }

            if ($row["specification_no"] == "") {
                $row["current_status"] = "Specification not Available";
            } else if ($row["area_status"] == 'pending') {
                $row["current_status"] = 'Area Checkpoints';
            } else if ($row["area_status"] !== 'complete') {
                $row["current_status"] = 'Area Checkpoints';
            }
            
            
             $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE grn_no = '".$row["grn_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getAllocatelog") {
    $output = Array();
    
     $sql="SELECT *  FROM sampling where  allocation_date != '' AND plant_id='".$_GET["plant_id"]."' ORDER BY allocation_date DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            
            $sql1 = "SELECT m.* , (select GROUP_CONCAT(grade.grade) from grade where FIND_IN_SET(grade.id , m.grade)) as gradeName
                 FROM material m WHERE m.material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                    $row["gradeName"] = $row1["gradeName"];
                }
            }
            
            
            
            $sql1 = "SELECT firstname,emp_id FROM employee  WHERE emp_id ='".$row["sampling_person"]."' AND plant_id='".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["sampling_person_name"] = $row1["firstname"]."-".$row1["emp_id"] ;
                }
            }
            
            $sql1 = "SELECT firstname,emp_id FROM employee  WHERE emp_id ='".$row["alternate_micro_person"]."' AND plant_id='".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["alternate_micro_person_name"] = $row1["firstname"]."-".$row1["emp_id"] ;
                }
            }
            $sql1 = "SELECT firstname,emp_id FROM employee  WHERE emp_id ='".$row["alternate_qc_person"]."' AND plant_id='".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["alternate_qc_person_name"] = $row1["firstname"]."-".$row1["emp_id"] ;
                }
            }
            $sql1 = "SELECT firstname,emp_id FROM employee  WHERE emp_id ='".$row["micro_person"]."' AND plant_id='".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["micro_person_name"] = $row1["firstname"]."-".$row1["emp_id"] ;
                }
            }
            
            
            

            if ($row["area_status"] !== "pending") {
                $row["area_details"] = json_decode($row["area_details"]);
            }

            if ($row["specification_no"] == "") {
                $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."' AND spec_type='Raw Material Specification' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["specification_no"] = $row1["specification_no"];
                        
                        $sql1 = "UPDATE sampling SET specification_no='".$row["specification_no"]."' WHERE id='".$row["id"]."'";
                        $conn->query($sql1);
                        break;
                    }
                }
            }

            if ($row["specification_no"] == "") {
                $row["current_status"] = "Specification not Available";
            } else if ($row["area_status"] == 'pending') {
                $row["current_status"] = 'Area Checkpoints';
            } else if ($row["area_status"] !== 'complete') {
                $row["current_status"] = 'Area Checkpoints';
            }
            
            
             $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE grn_no = '".$row["grn_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingLineClearance") {
    $output = Array();
    
    //  $sql = "SELECT * FROM sampling WHERE status = 'allocated' AND sampling_person='".$_GET["emp_id"]."' AND area_status='complete' AND (clearance_status='pending' OR clearance_status='inprocess')";
     $sql = "SELECT * FROM sampling WHERE status = 'allocated' AND sampling_person='".$_GET["emp_id"]."' AND area_status='complete' AND (clearance_status='pending' OR clearance_status='inprocess')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $row["area_details"] = json_decode($row["area_details"]);

            if ($row["clearance_status"] !== 'pending') {
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE id='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];

                        if ($row1['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                            $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                            $conn->query($sql2);
                            $row["clearance_status"] = "complete";
                        }
                        break;
                    }
                }

                $sql1 = "SELECT * FROM lineclearance WHERE id !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["material_code"] = $row1["material_no"];
                        $temp["batch_no"] = $row1["batch_no"];
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
            }

            $row["current_status"] = 'Line Clearance';

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingSamplingForm") {
    $output = Array();
    // inprocess
     $sql = "SELECT * FROM sampling WHERE status = 'allocated' AND sampling_person='".$_GET["emp_id"]."' AND clearance_status='complete' AND sample_status='pending'  ORDER BY id DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $row["area_details"] = json_decode($row["area_details"]);

            if ($row["clearance_status"] !== 'pending') {
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE id='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];

                        if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                            $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                            $conn->query($sql2);
                            $row["clearance_status"] = "complete";
                        }
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
            }

            $sql1 = "SELECT * FROM specification WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["specification_no"] = $row1["specification_no"];
                    $row["composite_qty"] = +$row1["sample_qty"];
                    $row["unit"] = $row1["unit"];

                    $sql2 = "SELECT IFNULL(SUM(sample_qty), 0) as identication_qty FROM spec_tests WHERE specification_no='".$row1["specification_no"]."' AND test='Identification'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["identication_qty"] = +$row2["identication_qty"];
                        }
                    } else {
                        $row["identication_qty"] = 0;
                    }
                    $row["actual_indentification"] = number_format(+$row["identication_qty"] * 2, 2);
                    $row["actual_composite"] = number_format(+$row["composite_qty"] * 2, 2);
                    break;
                }
            }

            $output1 = Array();
            for ($i = 0; $i < +$row["containers"]; $i++) {
                $temp = Array();
                $temp['container_no'] = $i + 1;
                $temp['identication_qty'] = +$row["identication_qty"];
                $temp['composite_qty'] = +$row["composite_qty"];
                $temp['status'] = "pending";
                $output1[] = $temp;
            }
            $row["container_details"] = $output1;

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "callforclearance") {
    $sql = "INSERT INTO lineclearance (department,section,activity,material_no,grn_no,batch_no, checkpoints,request_by,request_date) VALUES ('".$_GET["department"]."','Sampling','".$input["material_type"]." Sampling','".$input["material_code"]."','".$input["grn_no"]."','".$input["batch_no"]."','".json_encode($input["checkpoints"])."','".$_GET["emp_id"]."','$entry_date')";
    if ($conn->query($sql)) {
        $last_id = $conn->insert_id;
        $sql = "UPDATE sampling SET clearance_no='".$last_id."', clearance_status='inprocess' WHERE id='".$input["id"]."'";
        $conn->query($sql);
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "saveAreaCheckpoints") {
  
        
    $sql = "UPDATE sampling SET area_details='".json_encode($input)."', area_status='complete' WHERE id='".$_GET["id"]."'";
    
    if ($conn->query($sql)) {
        
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getLaminars") {
    $output = Array();
    $sql = "SELECT * FROM equipment";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEquipments") {
    $output = Array();
    $sql = "SELECT id,equipment_code,equipment_name  FROM equipment where equipment_type LIKe '%Balance(Weighing)%' and department='Store'";
    
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
    echo json_encode($output);
}
else if ($_GET["type"] == "getLafEquipments") {
    $output = Array();
    $sql = "SELECT id,equipment_code,equipment_name  FROM equipment where equipment_type!='Balance(Weighing)' and department='Store'";
    
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

    else if ($_GET["type"] == "saveSamplingInfo") {
        $sql = "UPDATE sampling SET sampling_details='".json_encode($input)."', sample_status='complete', status='active', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if ($_GET["type"] == "getActiveSamplings") { 
        
        $output = array();
        $scope = isset($_GET['sampling_scope']) ? trim((string)$_GET['sampling_scope']) : '';
        $statusSql = ($scope === 'retest') ? "(a.status = 'Active' OR a.status = 'Sampled')" : "a.status = 'Active'";
    
        $sql = "SELECT a.*,c.material_type,c.material_subtype,c.material_name,c.grade,c.storage_condition,c.material_subtype,c.sampleForTesting,c.unit as baseUnit,
                (select CONCAT(e.firstname , ' ' , e.lastname) as samplngPersonName from employee e where e.emp_id = a.sampling_person limit 1) as  samplngPersonName,
                (select vendor_name from vendor e where e.vendor_no = a.manufacturer_no limit 1) as  manuNAme,
                (select vendor_name from vendor e where e.vendor_no = a.supplier_no limit 1) as  supplierNAme
                FROM sampling a 
                join material c on a.material_code = c.material_code 
                WHERE ".$statusSql." AND  a.plant_id = '".$_GET["plant_id"]."'".medicap_sampling_material_type_sql($conn, 'c').medicap_sampling_scope_sql($conn, 'a');
         
     
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["sampling_details"] = json_decode($row["sampling_details"]);
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    
    }
 
    else if ($_GET["type"] == "updateActiveSampling") {
        
        $sql = "UPDATE sampling SET status = '".$input["status"]."', checkingRemark ='".$input["checkingRemark"]."', check_by = '".$_GET["emp_id"]."', check_date = '$entry_date' WHERE id = '".$input["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
         
    }
 
    
    
    
    
    
    else if ($_GET["type"] == "getCheckedSamplings") { 
        
        $output = array();
    
        $sql = "SELECT a.*,c.material_type,c.material_subtype,c.material_name,c.grade,c.storage_condition,c.material_subtype,c.sampleForTesting,c.unit as baseUnit,
                (select CONCAT(e.firstname , ' ' , e.lastname) as samplngPersonName from employee e where e.emp_id = a.sampling_person limit 1) as  samplngPersonName,
                (select vendor_name from vendor e where e.vendor_no = a.manufacturer_no limit 1) as  manuNAme,
                (select vendor_name from vendor e where e.vendor_no = a.supplier_no limit 1) as  supplierNAme
                FROM sampling a 
                join material c on a.material_code = c.material_code 
                WHERE a.status = 'Checked' AND  a.plant_id = '".$_GET["plant_id"]."'".medicap_sampling_material_type_sql($conn, 'c').medicap_sampling_scope_sql($conn, 'a');
         
     
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["sampling_details"] = json_decode($row["sampling_details"]);
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    
    }
    else if ($_GET["type"] == "getRejectedSamplings") { 
        
        $output = array();
    
        $sql = "SELECT a.*,c.material_type,c.material_subtype,c.material_name,c.grade,c.storage_condition,c.material_subtype,c.sampleForTesting,c.unit as baseUnit,
                (select CONCAT(e.firstname , ' ' , e.lastname) as samplngPersonName from employee e where e.emp_id = a.sampling_person limit 1) as  samplngPersonName,
                (select vendor_name from vendor e where e.vendor_no = a.manufacturer_no limit 1) as  manuNAme,
                (select vendor_name from vendor e where e.vendor_no = a.supplier_no limit 1) as  supplierNAme
                FROM sampling a 
                join material c on a.material_code = c.material_code 
                WHERE a.status = 'Rejected' AND  a.plant_id = '".$_GET["plant_id"]."' AND  c.material_type = '".$_GET["material_type"]."'";
         
     
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["sampling_details"] = json_decode($row["sampling_details"]);
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    
    }
 


    else if ($_GET["type"] == "updateCheckedSampling") {
            
             $sql = "UPDATE sampling SET status = '".$input["status"]."', approvalRemark ='".$input["approvalRemark"]."', approve_by = '".$_GET["emp_id"]."', approve_date = '$entry_date' WHERE id = '".$input["id"]."'";
            
            if ($conn->query($sql)) {
                        
                if( $input["status"] == 'Approved'){
                    
                       require_once __DIR__.'/../store/retest_helpers.php';
                       medicap_ensure_sampling_retest_id_column($conn);
                       $sampId = $conn->real_escape_string((string)$input['id']);
                       $testingType = 'Normal';
                       $scopeRes = $conn->query("SELECT id FROM sampling WHERE id='".$sampId."' AND ".medicap_retest_sampling_scope_sql('')." LIMIT 1");
                       if ($scopeRes && $scopeRes->num_rows > 0) {
                           $testingType = 'Retest';
                       }

                     $sql1 = "INSERT INTO `testing`(`plant_id`, `testing_type`, `specification_no`, `sampling_no`,`batch_no`, `grn_no`, `ar_no`, `material_code`, `status`,  `entry_by`, `entry_date`) VALUES ('".$_GET["plant_id"]."' , '".$testingType."',
                            '".$input["specification_no"]."', '".$input["sampling_no"]."','".$input["batch_no"]."', '".$input["grn_no"]."', '".$input["ar_no"]."', '".$input["material_code"]."', 'Pending' , '".$_GET["emp_id"]."', '$entry_date' )";
            
                    if ($conn->query($sql1)) {
                        
                        echo "{\"status\":\"success\"}";
                        
                         $sql11 = "UPDATE stock_book SET status = 'Under Test' WHERE grn_no = '".$input["grn_no"]."' AND ar_no = '".$input["ar_no"]."' AND batch_no = '".$input["batch_no"]."'";
                        $conn->query($sql11);
                        
                        
                        $sql111 = "INSERT INTO `material_issue`(`plant_id`, `grn_no`, `ar_no`, `material_code`, `batch_no`, `issue_for`, `qty`, `unit`, `status`, `material_type`, `entry_by`,`entry_date`) 
                        VALUES ('".$_GET["plant_id"]."' , '".$input["grn_no"]."', '".$input["ar_no"]."', '".$input["material_code"]."', '".$input["batch_no"]."', 'SAMPLING' , '".$input["convertedQtyToBaseUnit"]."', '".$input["baseUnit"]."', 
                        'Approved', '".$input["material_type"]."','".$_GET["emp_id"]."', '$entry_date')";
                        
                       $conn->query($sql111);

                       $sRes = $conn->query("SELECT retest_id, old_grn, grn_no FROM sampling WHERE id='".$sampId."' LIMIT 1");
                       if ($sRes && $sampRow = $sRes->fetch_assoc()) {
                           medicap_retest_mark_sampling_approved($conn, $sampRow);
                       }
                         
                    } else {
                        echo "{\"status\":\"failed\"}";
                    }
                    
                }else{
                    echo "{\"status\":\"success\"}";
                }
                
        } else {
            echo "{\"status\":\"failed\"}";
        }
           
    } 
    else if ($_GET["type"] == "updateRejectedSampling") {
            
        $sql = "UPDATE sampling SET status = '".$input["status"]."', rejectRemark ='".$input["rejectRemark"]."', reSamplingSendBy = '".$_GET["emp_id"]."', reSamplingSendOn = '$entry_date' WHERE id = '".$input["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
           
    } 

 
 

    else if ($_GET["type"] == "getSamplings") { 
        
        $output = array();
    
        $sql = "SELECT a.*,c.material_type,c.material_subtype,c.material_name,c.grade,c.storage_condition,c.material_subtype,c.sampleForTesting,c.unit as baseUnit,
                (select CONCAT(e.firstname , ' ' , e.lastname) as samplngPersonName from employee e where e.emp_id = a.sampling_person limit 1) as  samplngPersonName,
                (select vendor_name from vendor e where e.vendor_no = a.manufacturer_no limit 1) as  manuNAme,
                (select vendor_name from vendor e where e.vendor_no = a.supplier_no limit 1) as  supplierNAme
                FROM sampling a 
                join material c on a.material_code = c.material_code 
                WHERE a.status = 'Approved' AND  a.plant_id = '".$_GET["plant_id"]."'".medicap_sampling_material_type_sql($conn, 'c').medicap_sampling_scope_sql($conn, 'a');
         
     
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $scope = isset($_GET['sampling_scope']) ? trim((string)$_GET['sampling_scope']) : '';
            $plantId = $conn->real_escape_string((string)$_GET['plant_id']);
            while ($row = $result->fetch_assoc()) {
                $row["sampling_details"] = json_decode($row["sampling_details"]);
                if ($scope === 'retest') {
                    $sampNo = $conn->real_escape_string((string)($row['sampling_no'] ?? ''));
                    $row['testing_no'] = '';
                    $row['testing_status'] = '';
                    if ($sampNo !== '') {
                        $tRes = $conn->query("SELECT testing_no, status FROM testing WHERE sampling_no='".$sampNo."' AND plant_id='".$plantId."' ORDER BY id DESC LIMIT 1");
                        if ($tRes && $tRes->num_rows > 0) {
                            $tRow = $tRes->fetch_assoc();
                            $row['testing_no'] = $tRow['testing_no'] ?? '';
                            $row['testing_status'] = $tRow['status'] ?? '';
                        }
                    }
                    $row['next_step'] = medicap_retest_sampling_next_step_label($row['testing_status'], $row['testing_no']);
                }
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    
    }
else if($_GET["type"] =="getSamplingDetails")
{
    
     $output = Array();
        $sql = "SELECT * FROM specification WHERE material_code='".$_GET["material_code"]."' order by id desc ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
              $output1 = Array();
            while($row = $result->fetch_assoc()) {
$row['composite'] =  floatval($row['chemical_qty']) +  floatval($row['micro_qty']) +  floatval($row['indentification_qty']) +  floatval($row['physical_qty']) +  floatval($row['additional_sample']);
                $sql1 = "select * from sampling where sampling_no='".$_GET["sampling_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while($row2 = $result1->fetch_assoc()) {
                        $output1[] = $row2;
                    }
                }
               
                $output = $row;
            }
        }
        echo json_encode($output);
     
}
else if ($_GET["type"] == "getAllSamplings") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status NOT IN ('pending', 'inprocess')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $temp = Array();
            $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                    $temp["area_cleaned"] = $row1["area_cleaned"];
                    $temp["product_traces"] = $row1["product_traces"];
                    $temp["temperature"] = $row1["temperature"];
                    $temp["humidity"] = $row1["humidity"];
                    $temp["entry_by"] = $row1["entry_by"];
                    $temp["entry_date"] = $row1["entry_date"];
                    $temp["status"] = $row1["status"];

                    if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                        $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                        $conn->query($sql2);
                        $row["clearance_status"] = "complete";
                    }
                    break;
                }
            }

            $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $temp["material_code"] = $row1["material_no"];
                    $temp["batch_no"] = $row1["batch_no"];
                    break;
                }
            }
            $row["clearance_details"] = $temp;

            $row["area_details"] = json_decode($row["area_details"]);
            $row["sample_details"] = json_decode($row["sampling_details"]);
            
            
            $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE grn_no = '".$row["grn_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;


            $output[] = $row;
        }
    }
    echo json_encode($output);
}

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>