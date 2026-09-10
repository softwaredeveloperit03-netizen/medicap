<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = isset($_GET["token"]) ? $_GET["token"] : '';
        $currentUrl = isset($_GET["description"]) ? $_GET["description"] : '';
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

    function qc_testing_pdf_quiet() {
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        ob_start();
    }

    function qc_testing_pdf_flush() {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
    }

    function qc_testing_pdf_date($value) {
        if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '-';
        }
        $ts = strtotime($value);
        return $ts ? date('d-m-Y', $ts) : (string)$value;
    }

    function qc_testing_pdf_logo_html($logo) {
        $var = (!empty($logo))
            ? 'https://aurenyxgmp.com/php/phpdevlop/gmptotal/logos/'.$logo
            : '';
        return $var !== ''
            ? '<img src="'.htmlspecialchars($var, ENT_QUOTES, 'UTF-8').'" width="65" height="20" style="max-width:65px; max-height:20px;" />'
            : '&nbsp;';
    }

    function qc_testing_tests_with_spec($conn, $testingNo, $specificationNo = '') {
        $output = array();
        $testingNoEsc = $conn->real_escape_string(trim((string)$testingNo));
        if ($testingNoEsc === '') {
            return $output;
        }
        $specEsc = $conn->real_escape_string(trim((string)$specificationNo));
        // testing_tests has no `description` column — take it from spec_tests only.
        $sql = "SELECT t.*, st.reference_type,
                COALESCE(NULLIF(TRIM(st.description), ''), '') AS description,
                COALESCE(NULLIF(TRIM(st.description), ''), '') AS spec_description,
                COALESCE(NULLIF(TRIM(t.limits), ''), NULLIF(TRIM(st.limits), ''), '') AS limits,
                COALESCE(NULLIF(TRIM(st.limits), ''), NULLIF(TRIM(t.limits), ''), '') AS spec_limits
                FROM testing_tests t
                LEFT JOIN spec_tests st ON (
                    (IFNULL(t.spec_test_id,'') != '' AND st.id = t.spec_test_id)";
        if ($specEsc !== '') {
            $sql .= " OR (
                        IFNULL(t.spec_test_id,'') = '' AND st.test = t.test
                        AND IFNULL(st.subtest,'') = IFNULL(t.subtest,'')
                        AND st.specification_no = '".$specEsc."'
                    )";
        }
        $sql .= ")
                WHERE t.testing_no='".$testingNoEsc."'
                ORDER BY t.id ASC";
        $result = @$conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        return $output;
    }

    function qc_testing_pdf_header($logoHtml, $plantName, $plantAddress) {
        return '
            <table style="width: 100%; border-collapse: collapse; font-family: dejavusans;">
                <tr>
                    <td style="width:30%; border: 0.5px solid #000; padding: 4px; font-size: 8px; text-align:center; vertical-align:middle;">
                        '.$logoHtml.'
                    </td>
                    <td style="width:70%; border: 0.5px solid #000; padding: 3px 4px; font-weight: bold; text-align: center;">
                        <span style="font-size: 13px;">'.$plantName.'</span><br>
                        <span style="font-size: 9px;">'.$plantAddress.'</span>
                    </td>
                </tr>
            </table>';
    }

    function qc_testing_enrich_source_fields($conn, &$row) {
        $isRetest = false;
        $testingType = strtolower(trim((string)($row['testing_type'] ?? '')));
        if ($testingType === 'retest') {
            $isRetest = true;
        } else {
            require_once __DIR__.'/../../store/retest_helpers.php';
            medicap_ensure_sampling_retest_id_column($conn);
            $plantId = $conn->real_escape_string((string)($row['plant_id'] ?? ($_GET['plant_id'] ?? '')));
            $scopeSql = medicap_retest_sampling_scope_sql('');
            $samplingNo = trim((string)($row['sampling_no'] ?? ''));
            if ($samplingNo !== '') {
                $sampEsc = $conn->real_escape_string($samplingNo);
                $chk = $conn->query("SELECT id FROM sampling WHERE plant_id='".$plantId."' AND sampling_no='".$sampEsc."' AND ".$scopeSql." LIMIT 1");
                $isRetest = ($chk && $chk->num_rows > 0);
            }
            if (!$isRetest) {
                $materialCode = trim((string)($row['material_code'] ?? ''));
                $grnNo = trim((string)($row['grn_no'] ?? ''));
                $batchNo = trim((string)($row['batch_no'] ?? ''));
                if ($materialCode !== '' && ($grnNo !== '' || $batchNo !== '')) {
                    $matEsc = $conn->real_escape_string($materialCode);
                    $matchParts = array("plant_id='".$plantId."'", "material_code='".$matEsc."'", $scopeSql);
                    if ($grnNo !== '') {
                        $grnEsc = $conn->real_escape_string($grnNo);
                        $matchParts[] = "(grn_no='".$grnEsc."' OR old_grn='".$grnEsc."')";
                    }
                    if ($batchNo !== '') {
                        $matchParts[] = "batch_no='".$conn->real_escape_string($batchNo)."'";
                    }
                    $chk2 = $conn->query("SELECT id FROM sampling WHERE ".implode(' AND ', $matchParts)." LIMIT 1");
                    $isRetest = ($chk2 && $chk2->num_rows > 0);
                }
            }
        }
        $row['is_retest'] = $isRetest ? 'Yes' : 'No';
        $row['testing_source'] = $isRetest ? 'Retest' : 'Normal Testing';
    }

    /** Use specification linked to sampling/testing; fallback to latest approved spec for material. */
    function qc_testing_resolve_specification_no($conn, $materialCode, $testingSpecNo, $plantId) {
        $specNo = trim((string)$testingSpecNo);
        if ($specNo !== '' && strtoupper($specNo) !== 'NA') {
            return $specNo;
        }
        $matEsc = $conn->real_escape_string(trim((string)$materialCode));
        $plantEsc = $conn->real_escape_string(trim((string)$plantId));
        if ($matEsc === '') {
            return 'NA';
        }
        $sql = "SELECT specification_no FROM specification
                WHERE material_code='".$matEsc."' AND plant_id='".$plantEsc."'
                AND LOWER(TRIM(COALESCE(status,''))) IN ('approved','approve','active')
                ORDER BY id DESC LIMIT 1";
        $res = @$conn->query($sql);
        if ($res && $res->num_rows > 0 && ($r = $res->fetch_assoc())) {
            $found = trim((string)($r['specification_no'] ?? ''));
            if ($found !== '') {
                return $found;
            }
        }
        $sql2 = "SELECT specification_no FROM specification WHERE material_code='".$matEsc."' ORDER BY id DESC LIMIT 1";
        $res2 = @$conn->query($sql2);
        if ($res2 && $res2->num_rows > 0 && ($r2 = $res2->fetch_assoc())) {
            $found = trim((string)($r2['specification_no'] ?? ''));
            return $found !== '' ? $found : 'NA';
        }
        return 'NA';
    }

    /** Tests for chemist allocation — same list as Specification Logs → Add Specification. */
    function qc_testing_allocation_spec_tests($conn, $specificationNo, $plantId, $testingType = 'Normal') {
        $output = array();
        $specEsc = $conn->real_escape_string(trim((string)$specificationNo));
        $plantEsc = $conn->real_escape_string(trim((string)$plantId));
        if ($specEsc === '' || strtoupper($specEsc) === 'NA') {
            return $output;
        }
        $specRow = null;
        $specRes = @$conn->query("SELECT * FROM specification WHERE plant_id='".$plantEsc."' AND specification_no='".$specEsc."' ORDER BY id DESC LIMIT 1");
        if ($specRes && $specRes->num_rows > 0) {
            $specRow = $specRes->fetch_assoc();
        }
        if (!$specRow) {
            return $output;
        }
        $entryBy = isset($specRow['entry_by']) ? trim((string)$specRow['entry_by']) : '';
        $entryDate = isset($specRow['entry_date']) ? trim((string)$specRow['entry_date']) : '';
        $sql = "SELECT * FROM spec_tests WHERE plant_id='".$plantEsc."' AND specification_no='".$specEsc."'";
        if ($entryBy !== '' && $entryDate !== '') {
            $sql .= " AND entryBy='".$conn->real_escape_string($entryBy)."' AND entryOn='".$conn->real_escape_string($entryDate)."'";
        }
        $sql .= " ORDER BY id ASC";
        $result2 = @$conn->query($sql);
        if ($result2 && $result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $row2['person'] = '';
                $row2['person_alt'] = '';
                $row2['lab_no'] = '';
                $row2['isoutside'] = 'No';
                $output[] = $row2;
            }
        }
        return $output;
    }

    $sql = "SELECT * FROM token WHERE token='".$conn->real_escape_string($token)."'";
    $result = $conn->query($sql);
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);

    $pdfAction = isset($_GET['type']) ? $_GET['type'] : '';
    if (stripos($pdfAction, 'download') !== false || stripos($pdfAction, 'ARReport') !== false) {
        qc_testing_pdf_quiet();
    }
     
 
    
    if ($_GET["type"] == "getPendingAllocationTestings") {
        
        $output = Array();
        $plantId = isset($_GET['plant_id']) ? $_GET['plant_id'] : '';
        
        $sql = "select a.*,b.material_type,b.material_name,b.grade
        from testing a  left join material b on a.material_code = b.material_code where a.plant_id = '".$_GET['plant_id']."' AND b.material_type = '".$_GET['material_type']."' AND a.status = 'Pending' order by a.entry_date desc ";
         
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $specNo = qc_testing_resolve_specification_no(
                    $conn,
                    isset($row['material_code']) ? $row['material_code'] : '',
                    isset($row['specification_no']) ? $row['specification_no'] : '',
                    $plantId
                );
                $row['specification_no'] = $specNo;
                $testingType = isset($row['testing_type']) ? $row['testing_type'] : 'Normal';
                $specRes = @$conn->query("SELECT id FROM specification WHERE plant_id='".$conn->real_escape_string($plantId)."' AND specification_no='".$conn->real_escape_string($specNo)."' ORDER BY id DESC LIMIT 1");
                if ($specRes && $specRes->num_rows > 0 && ($specRow = $specRes->fetch_assoc())) {
                    $row['spec_id'] = $specRow['id'];
                }
                $row['spec_tests'] = qc_testing_allocation_spec_tests($conn, $specNo, $plantId, $testingType);
                qc_testing_enrich_source_fields($conn, $row);
                $output[] = $row;
                 
            }
        }
        echo json_encode($output);
   
    } 
    else if ($_GET["type"] == "getQcChecmist") {
        
        $output = Array();
        
        $sql = "SELECT `id`, `plant_id`, `employee_type`, `emp_level`, `emp_id`, `firstname`, `middlename`, `lastname`, CONCAT(firstname, ' ', lastname, ' ( ', emp_id , ' )') as empNAme, `contact_no`, `emp_email`, `department`, `operator_category`, `designation`, `status` FROM `employee` 
        where plant_id = '".$_GET['plant_id']."' AND department = 'Quality Control'  AND status = 'Active' order by firstname ASC ";
         
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } 
    else if ($_GET["type"] == "getMicrobiologist") {
        
        $output = Array();
        
        $sql = "SELECT `id`, `plant_id`, `employee_type`, `emp_level`, `emp_id`, `firstname`, `middlename`, `lastname`, CONCAT(firstname, ' ', lastname, ' ( ', emp_id , ' )') as empNAme, `contact_no`, `emp_email`, `department`, `operator_category`, `designation`, `status` FROM `employee` 
        where plant_id = '".$_GET['plant_id']."' AND department = 'Microbiology' AND designation LIKE '%mic%' AND status = 'Active' order by firstname ASC ";
         
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } 

    
    else if ($_GET["type"] == "saveTestingPersonAllocation") {
        
            $sql = "UPDATE `testing` SET `specification_no` = '".$input["specification_no"]."', `status` = 'Allocated', `micro_status` = '".$input["micro_status"]."', `outside_status` = '".$input["outside_status"]."' , `allocationBy` = '".$_GET["emp_id"]."', 
            `allocationOn` = '$entry_date'  WHERE testing_no = '".$input["testing_no"]."'";
            
             if ($conn->query($sql)) {
                 
                if (isset($input['spec_tests']) && is_array($input['spec_tests'])) {
                    $data = $input['spec_tests'];
                 
                    for ($i = 0; $i < count($data); $i++) {
                        $values = $data[$i];
           
                        $sql1 = "INSERT INTO `testing_tests`(`plant_id`, `testing_no`, `testFor`, `test_type`, `spec_test_id`, `test`, `subtest`, `limit_type`, `limits`, `lower_limit`, `upper_limit`, `unit`, `isoutside`, `person`, 
                        `person_alt`, `lab_no`, `entryBy`, `entryOn`,`status`,`parentTestId`) VALUES ( '".$_GET["plant_id"]."', '".$input["testing_no"]."', '".$values["testFor"]."', '".$values["test_type"]."', '".$values["id"]."', '".$values["test"]."', 
                        '".$values["subtest"]."', '".$values["limit_type"]."', '".$values["limits"]."', '".$values["lower_limit"]."', '".$values["upper_limit"]."', '".$values["unit"]."', '".$values["isoutside"]."', '".$values["person"]."',
                        '".$values["person_alt"]."', '".$values["lab_no"]."', '".$_GET["emp_id"]."', '$entry_date','Pending' , '0')";
                                
                        $conn->query($sql1);
                    }
                    
                } 
                
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "SendTestingForApproval") {
        
            $testingId = $conn->real_escape_string((string)($input["testingId"] ?? ''));
            $status = $conn->real_escape_string((string)($input["status"] ?? 'Checked'));
            $sql = "UPDATE `testing` SET `status` = '".$status."', `check_by` = '".$_GET["emp_id"]."', `check_date` = '$entry_date'  WHERE id = '".$testingId."'";
            
             if ($conn->query($sql)) {
                // No in-house micro tests → micro lane not required for approval queue
                $conn->query("UPDATE testing t SET t.micro_status = 'Checked'
                    WHERE t.id = '".$testingId."' AND NOT EXISTS (
                        SELECT 1 FROM testing_tests tt
                        WHERE tt.testing_no = t.testing_no AND tt.test_type = 'Microbiology' AND tt.isoutside = 'No'
                    )");
                // No outside tests → outside lane not required for approval queue
                $conn->query("UPDATE testing t SET t.outside_status = 'Checked'
                    WHERE t.id = '".$testingId."' AND NOT EXISTS (
                        SELECT 1 FROM testing_tests tt
                        WHERE tt.testing_no = t.testing_no AND tt.isoutside = 'Yes'
                    )");
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        
    }
    else if ($_GET["type"] == "SendTestingForApprovalMicro") {
        
            $sql = "UPDATE `testing` SET `micro_status` = '".$input["status"]."', `micCheckBy` = '".$_GET["emp_id"]."', `micCheckOn` = '$entry_date'  WHERE id = '".$input["testingId"]."'";
            
             if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        
    }
    else if ($_GET["type"] == "SendTestingForApprovalOutside") {
        
            $sql = "UPDATE `testing` SET `outside_status` = '".$input["status"]."', `outCheckBy` = '".$_GET["emp_id"]."', `outCheckOn` = '$entry_date'  WHERE id = '".$input["testingId"]."'";
            
             if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        
    }
    else if ($_GET["type"] == "updateAllocatedPerson") {
        
            $sql = "UPDATE `testing_tests` SET `person` = '".$input["person"]."', `person_alt` = '".$input["person_alt"]."', `lab_no` = '".$input["lab_no"]."'  WHERE id = '".$input["id"]."'";
            
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        
    }
    else if ($_GET["type"] == "getTestingAllocationLog") {
        
        $output = Array();
        
        $sql = "select t.id, t.plant_id, t.testing_no, t.sampling_no, t.batch_no, t.grn_no, t.ar_no, t.material_code, t.specification_no, t.status, t.allocationBy, t.allocationOn, b.material_type,b.material_name,b.grade FROM testing t 
        left join material b on t.material_code = b.material_code where t.plant_id = '".$_GET['plant_id']."' AND b.material_type = '".$_GET['material_type']."' AND t.status != 'Pending' order by t.allocationOn desc ";
         
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  
                $output1 = Array();
                
                $sql2 = "SELECT t.id, t.plant_id, t.test_type, t.test, t.subtest, t.isoutside, t.person, t.person_alt, t.lab_no, e1.firstname AS personName, e2.firstname AS person_altName, l.lab_name
                FROM testing_tests t
                LEFT JOIN employee e1 ON e1.emp_id = t.person
                LEFT JOIN employee e2 ON e2.emp_id = t.person_alt
                LEFT JOIN labs l ON l.lab_no = t.lab_no
                WHERE t.testing_no = '".$row["testing_no"]."'AND t.plant_id = '".$_GET["plant_id"]."' ";          
                      
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output1[] = $row2;
                    }
                }
                $row["testingTests"] = $output1;
       
                $output[] = $row;
                 
            }
        }
        echo json_encode($output);
   
    }
    else if ($_GET["type"] == "getPendingTestingForms") {
        
        $sql = "select t.* , b.material_type,b.material_name,b.grade, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode FROM testing t 
        LEFT JOIN sampling_batches s ON s.batch_no = t.batch_no AND s.ar_no = t.ar_no AND s.grn_no = t.grn_no
        LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
        LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
        left join material b on t.material_code = b.material_code where t.plant_id = '".$_GET['plant_id']."' AND b.material_type = '".$_GET['material_type']."' AND t.status = 'Allocated'
        AND EXISTS ( SELECT 1 FROM testing_tests tt WHERE tt.testing_no = t.testing_no AND tt.status = 'Pending' AND tt.test_type != 'Microbiology' AND tt.isoutside = 'No')  order by t.allocationOn desc ";
        
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                qc_testing_enrich_source_fields($conn, $row);
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getTestingFormsForChecking") {
        
        $sql = "select t.* , b.material_type,b.material_name,b.grade, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode FROM testing t 
        LEFT JOIN sampling_batches s ON s.batch_no = t.batch_no AND s.ar_no = t.ar_no AND s.grn_no = t.grn_no
        LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
        LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
        left join material b on t.material_code = b.material_code where t.plant_id = '".$_GET['plant_id']."' AND b.material_type = '".$_GET['material_type']."' AND t.status = 'Allocated'
        AND EXISTS ( SELECT 1 FROM testing_tests tt WHERE tt.testing_no = t.testing_no AND tt.status != 'Pending' AND tt.test_type != 'Microbiology' AND tt.isoutside = 'No')  order by t.allocationOn desc ";
        
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                qc_testing_enrich_source_fields($conn, $row);
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getTestingFormsForMicrobiologyChecking") {
        
        $sql = "select t.* , b.material_type,b.material_name,b.grade, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode FROM testing t 
        LEFT JOIN sampling_batches s ON s.batch_no = t.batch_no AND s.ar_no = t.ar_no AND s.grn_no = t.grn_no
        LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
        LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
        left join material b on t.material_code = b.material_code where t.plant_id = '".$_GET['plant_id']."' AND b.material_type = '".$_GET['material_type']."' AND t.micro_status = 'Allocated'
        AND EXISTS ( SELECT 1 FROM testing_tests tt WHERE tt.testing_no = t.testing_no AND tt.status != 'Pending' AND tt.test_type = 'Microbiology' AND tt.isoutside = 'No')  order by t.allocationOn desc ";
        
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getTestingFormsForOutsideChecking") {
        
        $sql = "select t.* , b.material_type,b.material_name,b.grade, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode FROM testing t 
        LEFT JOIN sampling_batches s ON s.batch_no = t.batch_no AND s.ar_no = t.ar_no AND s.grn_no = t.grn_no
        LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
        LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
        left join material b on t.material_code = b.material_code where t.plant_id = '".$_GET['plant_id']."' AND b.material_type = '".$_GET['material_type']."' AND t.outside_status = 'Allocated'
        AND EXISTS ( SELECT 1 FROM testing_tests tt WHERE tt.testing_no = t.testing_no AND tt.status != 'Pending' AND tt.isoutside = 'Yes')  order by t.allocationOn desc ";
        
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getTestByTestingNO") {
        
        $output1 = Array();
        $testingNoEsc = $conn->real_escape_string(trim((string)($_GET["testing_no"] ?? '')));
        
        $sql1="SELECT t.*, e1.firstname AS personName, e2.firstname AS person_altName,
                e3.firstname AS performByName,
                COALESCE(NULLIF(TRIM(t.limits), ''), st.limits) AS limits,
                COALESCE(NULLIF(TRIM(t.limit_type), ''), st.limit_type) AS limit_type,
                COALESCE(NULLIF(TRIM(t.lower_limit), ''), st.lower_limit) AS lower_limit,
                COALESCE(NULLIF(TRIM(t.upper_limit), ''), st.upper_limit) AS upper_limit,
                st.id AS spec_test_id, st.test_method_no, st.method AS moa_method_status, st.outside_testing
                FROM testing_tests t
                LEFT JOIN testing tg ON tg.testing_no = t.testing_no
                LEFT JOIN spec_tests st ON (
                    (IFNULL(t.spec_test_id,'') != '' AND st.id = t.spec_test_id)
                    OR (
                        IFNULL(t.spec_test_id,'') = ''
                        AND st.test = t.test
                        AND IFNULL(st.subtest,'') = IFNULL(t.subtest,'')
                        AND st.specification_no = tg.specification_no
                    )
                )
                LEFT JOIN employee e1 ON e1.emp_id = t.person
                LEFT JOIN employee e2 ON e2.emp_id = t.person_alt
                LEFT JOIN employee e3 ON e3.emp_id = t.performBy
                WHERE t.testing_no = '".$testingNoEsc."' AND t.test_type != 'Microbiology' AND t.plant_id = '".$_GET["plant_id"]."' AND t.isoutside = 'No'
                ORDER BY t.id ASC";
        
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1[] = $row1;
            }
        }
        
        echo json_encode($output1);
        
    }
    else if ($_GET["type"] == "getTestByTestingNOForApproval") {
        $testingNo = isset($_GET['testing_no']) ? trim((string)$_GET['testing_no']) : '';
        if ($testingNo === '') {
            echo json_encode(array());
            exit;
        }
        if (isset($_GET['with_spec']) && $_GET['with_spec'] === '1') {
            $specNo = isset($_GET['specification_no']) ? trim((string)$_GET['specification_no']) : '';
            echo json_encode(qc_testing_tests_with_spec($conn, $testingNo, $specNo));
            exit;
        }

        $output1 = Array();
        $testingNoEsc = $conn->real_escape_string($testingNo);
        $sql1 = "SELECT t.*, e1.firstname AS personName, e2.firstname AS person_altName, l.lab_name
                FROM testing_tests t
                LEFT JOIN employee e1 ON e1.emp_id = t.person
                LEFT JOIN employee e2 ON e2.emp_id = t.person_alt
                LEFT JOIN labs l ON l.lab_no = t.lab_no
                WHERE t.testing_no = '".$testingNoEsc."'";
        if (isset($_GET['plant_id']) && trim((string)$_GET['plant_id']) !== '') {
            $plantEsc = $conn->real_escape_string(trim((string)$_GET['plant_id']));
            $sql1 .= " AND (t.plant_id = '".$plantEsc."' OR IFNULL(t.plant_id,'') = '')";
        }
        $sql1 .= " ORDER BY t.id ASC";

        $result1 = $conn->query($sql1);
        if ($result1 && $result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1[] = $row1;
            }
        }

        echo json_encode($output1);
    }
    else if ($_GET["type"] == "getPendingTestForCorrection") {
        
        $output1 = Array();
        
        $sql1="SELECT t.*, e1.firstname AS performByName, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode , t1.testing_no, t1.sampling_no,
        t1.material_code,t1.batch_no,t1.ar_no,t1.grn_no,b.material_type,b.material_name,b.grade
                FROM testing_tests t
                LEFT JOIN employee e1 ON e1.emp_id = t.performBy

                LEFT JOIN testing t1 ON t1.testing_no = t.testing_no
                LEFT JOIN sampling_batches s ON s.batch_no = t1.batch_no AND s.ar_no = t1.ar_no AND s.grn_no = t1.grn_no
                LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
                LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
                left join material b on t1.material_code = b.material_code
                
                WHERE t.test_type != 'Microbiology' AND t.plant_id = '".$_GET["plant_id"]."' AND t.isoutside = 'No' AND t.status = 'Send_For_Edit' AND b.material_type = '".$_GET['material_type']."'";
        
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1[] = $row1;
            }
        }
        
        echo json_encode($output1);
        
    }
    else if ($_GET["type"] == "getPendingMicrobiologyTestForCorrection") {
        
        $output1 = Array();
        
        $sql1="SELECT t.*, e1.firstname AS performByName, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode , t1.testing_no, t1.sampling_no,
        t1.material_code,t1.batch_no,t1.ar_no,t1.grn_no,b.material_type,b.material_name,b.grade
                FROM testing_tests t
                LEFT JOIN employee e1 ON e1.emp_id = t.performBy

                LEFT JOIN testing t1 ON t1.testing_no = t.testing_no
                LEFT JOIN sampling_batches s ON s.batch_no = t1.batch_no AND s.ar_no = t1.ar_no AND s.grn_no = t1.grn_no
                LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
                LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
                left join material b on t1.material_code = b.material_code
                
                WHERE t.test_type = 'Microbiology' AND t.plant_id = '".$_GET["plant_id"]."' AND t.isoutside = 'No' AND t.status = 'Send_For_Edit' AND b.material_type = '".$_GET['material_type']."'";
        
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1[] = $row1;
            }
        }
        
        echo json_encode($output1);
        
    }
    else if ($_GET["type"] == "getPendingMicrobiologyTestForOutside") {
        
        $output1 = Array();
        
        $sql1="SELECT t.*, e1.firstname AS performByName, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode , t1.testing_no, t1.sampling_no,
        t1.material_code,t1.batch_no,t1.ar_no,t1.grn_no,b.material_type,b.material_name,b.grade
                FROM testing_tests t
                LEFT JOIN employee e1 ON e1.emp_id = t.performBy

                LEFT JOIN testing t1 ON t1.testing_no = t.testing_no
                LEFT JOIN sampling_batches s ON s.batch_no = t1.batch_no AND s.ar_no = t1.ar_no AND s.grn_no = t1.grn_no
                LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
                LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
                left join material b on t1.material_code = b.material_code
                
                WHERE  t.plant_id = '".$_GET["plant_id"]."' AND t.isoutside = 'Yes' AND t.status = 'Send_For_Edit' AND b.material_type = '".$_GET['material_type']."'";
        
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1[] = $row1;
            }
        }
        
        echo json_encode($output1);
        
    }
    else if ($_GET["type"] == "saveTestResult") {
        
        $sql = "UPDATE testing_tests  SET result = '".$input["result"]."', observation = '".$input["observation"]."', remark = '".$input["remark"]."', reason = '".$input["reason"]."', start_time = '".$input["start_time"]."',
        end_time = '".$input["end_time"]."', status = 'Inprocess', performBy = '".$_GET["emp_id"]."', performOn = '$entry_date' WHERE id = '".$input["selectedTestId"]."'";
     
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "updateTEstResultFromCorrection") {
        
        $sql = "UPDATE testing_tests  SET  status = 'Checked' WHERE id = '".$input["selectedTestId"]."'";
     
        if ($conn->query($sql)) {
            
                $sql11 = "INSERT INTO `testing_tests`(`plant_id`, `testing_no`, `testFor`, `test_type`, `spec_test_id`, `test`, `subtest`, `limit_type`, `limits`, `lower_limit`, `upper_limit`, `unit`, `isoutside`, `person`, 
                `person_alt`, `lab_no`, `entryBy`, `entryOn`,`status`,`result`, `observation`, `remark`, `reason`, `start_time`, `end_time`, `performBy`, `performOn`,`parentTestId`) VALUES ( '".$_GET["plant_id"]."', '".$input["testing_no"]."', 
                '".$input["testFor"]."', '".$input["test_type"]."', '".$input["spec_test_id"]."', '".$input["test"]."', '".$input["subtest"]."', '".$input["limit_type"]."', '".$input["limits"]."', '".$input["lower_limit"]."', 
                '".$input["upper_limit"]."', '".$input["unit"]."', '".$input["isoutside"]."', '".$input["person"]."', '".$input["person_alt"]."', '".$input["lab_no"]."', '".$_GET["emp_id"]."', '$entry_date','Inprocess',
                '".$input["result"]."', '".$input["observation"]."', '".$input["remark"]."', '".$input["reason"]."', '".$input["start_time"]."', '".$input["end_time"]."', '".$_GET["emp_id"]."', '$entry_date', '".$input["selectedTestId"]."' )";
                
                if ($conn->query($sql11)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
             
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "getPendingTestingFormsMicrobilogy") {
        
        $sql = "select t.* , b.material_type,b.material_name,b.grade, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode FROM testing t 
        LEFT JOIN sampling_batches s ON s.batch_no = t.batch_no AND s.ar_no = t.ar_no AND s.grn_no = t.grn_no
        LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
        LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
        left join material b on t.material_code = b.material_code where t.plant_id = '".$_GET['plant_id']."' AND b.material_type = '".$_GET['material_type']."' AND t.micro_status = 'Allocated'
        AND EXISTS ( SELECT 1 FROM testing_tests tt WHERE tt.testing_no = t.testing_no AND tt.status = 'Pending' AND tt.test_type = 'Microbiology' AND tt.isoutside = 'No') 
        order by t.allocationOn desc ";
        
         
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getMicrobiologyTEstingLog") {
        
        $sql = "select t.* , b.material_type,b.material_name,b.grade, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode FROM testing t 
        LEFT JOIN sampling_batches s ON s.batch_no = t.batch_no AND s.ar_no = t.ar_no AND s.grn_no = t.grn_no
        LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
        LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
        left join material b on t.material_code = b.material_code where t.plant_id = '".$_GET['plant_id']."' AND b.material_type = '".$_GET['material_type']."' AND ( t.micro_status = 'Approved' || t.micro_status = 'Rejected' )
        AND EXISTS ( SELECT 1 FROM testing_tests tt WHERE tt.testing_no = t.testing_no AND  tt.test_type = 'Microbiology' AND tt.isoutside = 'No') 
        order by t.allocationOn desc ";
        
         
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getPendingTestingFormsOutside") {
        
        $sql = "select t.* , b.material_type,b.material_name,b.grade, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode FROM testing t 
        LEFT JOIN sampling_batches s ON s.batch_no = t.batch_no AND s.ar_no = t.ar_no AND s.grn_no = t.grn_no
        LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
        LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
        left join material b on t.material_code = b.material_code where t.plant_id = '".$_GET['plant_id']."' AND b.material_type = '".$_GET['material_type']."' AND t.outside_status = 'Allocated'
        AND EXISTS ( SELECT 1 FROM testing_tests tt WHERE tt.testing_no = t.testing_no AND tt.status = 'Pending' AND  tt.isoutside = 'Yes') 
        order by t.allocationOn desc ";
        
         
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getOutsideTestingLog") {
        
        $sql = "select t.* , b.material_type,b.material_name,b.grade, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode FROM testing t 
        LEFT JOIN sampling_batches s ON s.batch_no = t.batch_no AND s.ar_no = t.ar_no AND s.grn_no = t.grn_no
        LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
        LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
        left join material b on t.material_code = b.material_code where t.plant_id = '".$_GET['plant_id']."' AND b.material_type = '".$_GET['material_type']."' AND ( t.outside_status = 'Approved' || t.outside_status = 'Rejected' )
        AND EXISTS ( SELECT 1 FROM testing_tests tt WHERE tt.testing_no = t.testing_no  AND  tt.isoutside = 'Yes') 
        order by t.allocationOn desc ";
        
         
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getTestByTestingNOMicrobilogy") {
        
        $output1 = Array();
        
        $sql1="SELECT t.*, e1.firstname AS personName, e2.firstname AS person_altName
                FROM testing_tests t
                LEFT JOIN employee e1 ON e1.emp_id = t.person
                LEFT JOIN employee e2 ON e2.emp_id = t.person_alt
                WHERE t.testing_no = '".$_GET["testing_no"]."' AND t.test_type = 'Microbiology' AND t.plant_id = '".$_GET["plant_id"]."' AND t.isoutside = 'No' ";
        
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1[] = $row1;
            }
        }
        
        echo json_encode($output1);
        
    }
    else if ($_GET["type"] == "getTestByTestingNOOutside") {
        
        $output1 = Array();
        
        $sql1="SELECT t.*, e1.lab_name AS personName
                FROM testing_tests t
                LEFT JOIN labs e1 ON e1.lab_no = t.lab_no
                 WHERE t.testing_no = '".$_GET["testing_no"]."' AND t.plant_id = '".$_GET["plant_id"]."' AND t.isoutside = 'Yes' ";
        
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1[] = $row1;
            }
        }
        
        echo json_encode($output1);
        
    }
    else if ($_GET["type"] == "checkTest") {
        
        $sql = "UPDATE testing_tests  SET checkingRemark = '".$input["checkingRemark"]."', status = '".$input["status"]."', checkingRemark = '".$input["checkingRemark"]."',
        checkBy = '".$_GET["emp_id"]."', checkOn = '$entry_date' WHERE id = '".$input["selectedTestId"]."'";
     
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }

    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    else if ($_GET["type"] == "getPendingTestingReport") {
      
       
        $sql="SELECT DISTINCT t.id ,t.* ,s.supplier_batch_no, m.material_subtype, m.material_name, m.grade FROM testing t 
            LEFT JOIN material m ON t.material_code=m.material_code  left join sampling s on t.sampling_no =s.sampling_no  
            WHERE  t.plant_id='".$_GET["plant_id"]."'  and t.status='inprocess' and m.material_type='".$_GET["material_type"]."'
            order by t.entry_date desc";//inprocess
       

        $output = Array();
          
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["observation"] = "";
   
                 
                               
                $sql1="SELECT tt.*,tt.status as testingtestStatus,st.test_type,st.specification_no,st.limits,st.unit,st.morethan,st.lessthan,st.upper_limit,st.lower_limit,
                st.limit_type,st.reference_type,st.id as spectTestId,st.test_master_id
                FROM testing_tests tt left join spec_tests st ON tt.spec_test_id = st.id  WHERE 
                tt.testing_no='".$row["testing_no"]."' AND st.test_type !='Microbiology' AND 
                st.specification_no='".$row["specification_no"]."'   AND tt.isoutside = 'No'   ";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        if ($row1["observation"] == "fail") {
                            $row["observation"] = "fail";
                        }
                    
                        $output1[] = $row1;
                
                    }
                }
                 
                            
                          $row["gradeName"] = $row["grade"];

                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingTestingReport_micro") {
      
       
        $sql="SELECT DISTINCT t.id ,t.* ,s.supplier_batch_no, m.material_subtype, m.material_name, m.grade FROM testing t 
            LEFT JOIN material m ON t.material_code=m.material_code  left join sampling s on t.sampling_no =s.sampling_no  
            WHERE  t.plant_id='".$_GET["plant_id"]."' and t.is_RDS='".$_GET["is_RDS"]."' and t.micro_status='inprocess' ";//inprocess
       

        $output = Array();
          
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["observation"] = "";
                
                

                // $sql1="SELECT *,t.id as ttt_id,t.status as t_status,tt.method as moa_status ,(select count(ttt.test_method_no) from test_methods ttt 
                // where ttt.test_method_no=s2.test_method_no) as method_count FROM testing_tests t   LEFT JOIN spec_tests s2 on s2.test=t.test  and 
                // t.spec_test_id=s2.id left join test tt on tt.id=s2.test_master_id WHERE t.testing_no='".$row["testing_no"]."' AND tt.test_type ='Microbiology' AND 
                // s2.specification_no='".$row["specification_no"]."'";
                
                
                
                $sql1 = "SELECT t.*, st.morethan, st.lessthan, st.upper_limit, st.lower_limit, st.limit_type, st.limits, st.description FROM testing_tests  t left join spec_tests 
                st ON  st.test=t.test  AND st.specification_no =t.specification_no left join test tt on tt.id=st.test_master_id WHERE t.testing_no='".$row["testing_no"]."'
                AND  tt.test_type ='Microbiology' AND t.isoutside = 'No' order by t.id desc";
                
                
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["observation"] == "fail") {
                            $row["observation"] = "fail";
                        }
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
        else if ($_GET["type"] == "getTestingReportforcs") {
        
         $sql = "SELECT t.*,s.sampling_start_time,s.exp_date,s.entry_by as sample_by,s.mfg_date,s.batch_no, m.material_type, m.material_subtype, m.material_name, m.grade   FROM testing t LEFT JOIN material m
         ON t.material_code=m.material_code left join sampling s on s.grn_no =  t.grn_no  where t.status='Approved' AND m.material_type = '".$_GET["material_type"]."'
         AND t.plant_id = '".$_GET["plant_id"]."'
         GROUP by t.id,m.material_type, m.material_subtype, m.material_name, m.grade,s.sampling_start_time,s.exp_date,s.mfg_date,s.batch_no,s.entry_by";
 
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
                
            
            $sql1="SELECT t.*,
       (SELECT reference_type FROM spec_tests st WHERE st.test = t.test AND st.subtest = t.subtest LIMIT 1) AS reference_type,
       (SELECT description FROM spec_tests st WHERE st.test = t.test AND st.specification_no = t.specification_no LIMIT 1) AS description,
       (SELECT limits FROM spec_tests st WHERE st.test = t.test AND st.specification_no = t.specification_no LIMIT 1) AS limits,
       s.control_sample
                            FROM testing_tests t
                            JOIN specification s ON t.specification_no = s.specification_no
                            WHERE t.testing_no = '".$row["testing_no"]."'
                            ORDER BY t.id DESC";
            
                $output1 = Array();
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row['control_sample'] = $row1['control_sample'];
                        $output1[] = $row1;
                    }
                }
                
                            $row["tests"] = $output1;
                            $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
                            $resQ = $conn->query($q);
                            $prodLatest = $resQ->fetch_assoc();
                            $row['gradeName'] = $prodLatest['gradeName'];
        
                    $output[] = $row;
                 
            }
        }
        echo json_encode($output);
    }

    
   else if ($_GET["type"] == "getPendingTestingReport_erp") {
        // print_r($_GET);exit;
       
         $sql="SELECT DISTINCT t.id ,t.* , m.material_subtype, m.material_name, m.grade , g.grade as gradeName , t1.status as t_status,s.ar_no as arno,c.grn_date ,s.batch_no FROM testing t
            LEFT JOIN material m ON t.material_code=m.material_code LEFT JOIN grade g on g.id = m.grade LEFT JOIN testing_tests t1 ON t.testing_no=t1.testing_no LEFT
            JOIN sampling s ON t.grn_no=s.grn_no LEFT JOIN challan_materials c on t.grn_no=c.grn_no
            WHERE  t.plant_id='".$_GET["plant_id"]."' and t.is_RDS='".$_GET["is_RDS"]."' and t.status='inprocess' and m.material_type='".$_GET["material_type"]."'";//inprocess
       
                                //old code vivek
       /* $sql = "SELECT t1. *,t.method_details,t.status,t3.chemical_reagents,t3.balance,t3.equipment_instruments,t3.glasswares, s2.specification_no,t.testing_no,
                s2.specification_no,t.testing_no,s2.reference_type,s2.limits,m.material_subtype, m.material_name, m.grade ,
                g.grade as gradeName  FROM testing_tests t left join testing t1 ON t.testing_no=t1.testing_no LEFT 
                join sampling s on t1.sampling_no=s.sampling_no LEFT JOIN spec_tests s2 on s2.specification_no=s.specification_no LEFT 
                join test_methods t3 on t3.test_id=s2.id  LEFT JOIN material m on t1.material_code=m.material_code LEFT 
                JOIN grade g ON g.id=m.grade  WHERE t1.status='inprocess' ORDER BY t.id DESC";*/
      
      
      
                                //old code
      
        /* $sql = "SELECT t.* , m.material_subtype, m.material_name, m.grade ,  g.grade as gradeName 
        FROM testing t 
        LEFT JOIN material m ON t.material_code=m.material_code 
       
        LEFT JOIN grade g on g.id = m.grade
        
        Where  (t.status='active' || t.status='pending')  and t.is_RDS = ".$_GET["is_RDS"]." ORDER BY t.id DESC";

        * changed removed active 13_02 t.status='active' ||*/
       
        $output = Array();
          
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["observation"] = "";
                 $sql1 = "SELECT t.* , 
                        (select reference_type from spec_tests st where  st.test=t.test and st.subtest =t.subtest limit 1) as reference_type
                     FROM testing_tests  t WHERE t.testing_no='".$row["testing_no"]."'  AND t.specification_no='".$row["specification_no"]."' and t.testing_Date!=''
                     order by id asc";
            //   echo  $sql1 = "SELECT t.* , 
            //             (select reference_type from spec_tests st where  st.test=t.test and st.subtest =t.subtest limit 1) as reference_type
            //          FROM testing_tests  t WHERE t.testing_no='".$row["testing_no"]."' and t.specification_no='".$row["specification_no"]."'  order by id desc";
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                       // print_r(); exit ;
                       // $sql2 = "SELECT * FROM spec_tests WHERE specification_no=(SELECT specification_no FROM testing WHERE testing_no='".$row["testing_no"]."') AND test='".$row1["test"]."' AND subtest='".$row1["subtest"]."'";
                        $sql2 = "SELECT * FROM spec_tests WHERE id = '".$row1["spec_test_no"]."' ";
                     
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["limit_type"] = $row2["limit_type"] ;
                                $row1["limits"] = $row2["limits"] ;
                                if ($row1["observation"] !== "pass") {
                                    if ($row2["limit_type"] == "Limits") {
                                        if ($row1["result"] >= $row2["lower_limit"] && $row1["result"] <= $row2["upper_limit"]) {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    } else if ($row2["limit_type"] == "LessThan") {
                                        if ($row1["result"] <= $row2["lessthan"]) {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    } else if ($row2["limit_type"] == "MoreThan") {
                                        if ($row1["result"] >= $row2["morethan"]) {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    } else if ($row2["limit_type"] == "Compliances") {
                                        if ($row1["result"] == "complies") {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    }
                                }
                            }
                        }
                        if ($row1["observation"] == "fail") {
                            $row["observation"] = "fail";
                        }
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
        //           $q= 'SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('.$row['grade'].')';
        //     $resQ = $conn->query($q);
        //   $prodLatest = $resQ->fetch_assoc();
           $row['gradeName'] = 'NA';
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 
    else if ($_GET["type"] == "getPendingTestingReport_finish") {
        // print_r($_GET);exit;
       
    //   $sql="SELECT DISTINCT t.id ,t.* , m.material_subtype, m.material_name, m.grade ,  g.grade as gradeName 
    //          FROM testing t 
    //         LEFT JOIN material m ON t.material_code=m.material_code 
    //          LEFT JOIN grade g on g.id = m.grade LEFT JOIN testing_tests t1 ON t.testing_no=t1.testing_no
    //          WHERE t1.status='pending'";//inprocess
    $sql="SELECT a.*,b.product_type,b.product_name,b.grade FROM samplingfg a left join product b on a.product_code=b.product_code left JOIN testing_tests c on a.sampling_no=c.fg_sampling_no WHERE c.status='pending' group by b.product_type,b.product_name,b.grade,a.id";
       
                                //old code vivek
       /* $sql = "SELECT t1. *,t.method_details,t.status,t3.chemical_reagents,t3.balance,t3.equipment_instruments,t3.glasswares, s2.specification_no,t.testing_no,
                s2.specification_no,t.testing_no,s2.reference_type,s2.limits,m.material_subtype, m.material_name, m.grade ,
                g.grade as gradeName  FROM testing_tests t left join testing t1 ON t.testing_no=t1.testing_no LEFT 
                join sampling s on t1.sampling_no=s.sampling_no LEFT JOIN spec_tests s2 on s2.specification_no=s.specification_no LEFT 
                join test_methods t3 on t3.test_id=s2.id  LEFT JOIN material m on t1.material_code=m.material_code LEFT 
                JOIN grade g ON g.id=m.grade  WHERE t1.status='inprocess' ORDER BY t.id DESC";*/
      
      
      
                                //old code
      
        /* $sql = "SELECT t.* , m.material_subtype, m.material_name, m.grade ,  g.grade as gradeName 
        FROM testing t 
        LEFT JOIN material m ON t.material_code=m.material_code 
       
        LEFT JOIN grade g on g.id = m.grade
        
        Where  (t.status='active' || t.status='pending')  and t.is_RDS = ".$_GET["is_RDS"]." ORDER BY t.id DESC";

        * changed removed active 13_02 t.status='active' ||*/
       
        $output = Array();
          
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["observation"] = "";
            //  echo   $sql1 = "SELECT t.* , 
            //             (select reference_type from spec_tests st where  st.test=t.test and st.subtest =t.subtest limit 1) as reference_type
            //          FROM testing_tests  t WHERE t.testing_no='".$row["testing_no"]."' order by id desc";
            // echo   $sql1 = "SELECT t.* , 
            //             (select reference_type from spec_tests st where  st.test=t.test and st.subtest =t.subtest limit 1) as reference_type
            //          FROM testing_tests  t WHERE t.fg_sampling_no='".$row["sampling_no"]."' order by id desc";
          $sql1="   SELECT *,t.status as t_status FROM testing_tests t LEFT JOIN spec_tests st on t.specification_no=t.specification_no and t.test=st.test and t.spec_test_no=st.id  WHERE t.status!='inprocess' and t.status!='pending' and t.fg_sampling_no='".$row["sampling_no"]."'
";
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                       // print_r(); exit ;
                       // $sql2 = "SELECT * FROM spec_tests WHERE specification_no=(SELECT specification_no FROM testing WHERE testing_no='".$row["testing_no"]."') AND test='".$row1["test"]."' AND subtest='".$row1["subtest"]."'";
                        $sql2 = "SELECT * FROM spec_tests WHERE id = '".$row1["spec_test_no"]."' ";
                     
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["limit_type"] = $row2["limit_type"] ;
                                $row1["limits"] = $row2["limits"] ;
                                if ($row1["observation"] !== "pass") {
                                    if ($row2["limit_type"] == "Limits") {
                                        if ($row1["result"] >= $row2["lower_limit"] && $row1["result"] <= $row2["upper_limit"]) {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    } else if ($row2["limit_type"] == "LessThan") {
                                        if ($row1["result"] <= $row2["lessthan"]) {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    } else if ($row2["limit_type"] == "MoreThan") {
                                        if ($row1["result"] >= $row2["morethan"]) {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    } else if ($row2["limit_type"] == "Compliances") {
                                        if ($row1["result"] == "complies") {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    }
                                }
                            }
                        }
                        if ($row1["observation"] == "fail") {
                            $row["observation"] = "fail";
                        }
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 
    else if ($_GET["type"] == "getRejectedTestingReport_Reanalysis") {
        
       $sql="SELECT DISTINCT t.id ,t.* ,j.batch_no, m.material_subtype, m.material_name, m.grade ,j.area_details
             FROM testing t 
            LEFT JOIN material m ON t.material_code=m.material_code LEFT JOIN sampling j  ON j.sampling_no =t.sampling_no 
             LEFT JOIN grade g on g.id = m.grade 
            WHERE  t.plant_id='".$_GET["plant_id"]."'   and t.status='reject' and m.material_type='".$_GET["material_type"]."'";//inprocess
       
                   
        $output = Array();
          
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["observation"] = "";
                
                
                $sql1 = "SELECT t.*, st.morethan, st.lessthan, st.upper_limit, st.lower_limit, st.limit_type, st.limits,st.test_method_no, st.description,
                (select additional_observation  from newoos ao  where ao.material_code='".$row["material_code"]."' order by ao.id desc limit 1) as previous_add_observation
                ,os.cause_find FROM testing_tests  t left join 
                spec_tests st ON  st.test=t.test  AND st.specification_no =t.specification_no left join test tt on tt.id=st.test_master_id left join newoos os on t.incident_oos_no=os.oos_no WHERE
                t.testing_no = '".$row["testing_no"]."' AND  st.test_type !='Microbiology' AND t.isoutside = 'No'  AND t.observation !='complies' and t.reanalysis='Yes'  order by t.id desc";
    
    
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        
                        
                        
                        
  
                        if ($row1["observation"] == "fail") {
                            $row["observation"] = "fail";
                        }
                        
                        $output1[] = $row1;
                        
                    }
                }
                
                
                $row["area_details"]=json_decode($row["area_details"]);
                
                $row["tests"] = $output1;
            
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
 
    
    else if ($_GET["type"] == "testing_correction") {
        
            // $sql = "update testing set correction='oos' where testing_no='".$_GET["testing_no"]."'";
            // if ($conn->query($sql)) {
              
             
                $sql2 = "UPDATE testing_tests SET correction='oos', error='".$conn->real_escape_string((string)($input["error"] ?? ''))."' WHERE id=".(int)$_GET["id"];
               if ($conn->query($sql2)) {
                    $conn->query("UPDATE testing SET correction='pending' WHERE testing_no='".$conn->real_escape_string((string)$_GET["testing_no"])."'");
                    $sql3 = "INSERT INTO oos_correction(
                                                        Plant_id,
                                                        testing_test_id,
                                                        testing_no,
                                                        specification_no,
                                                        sampling_no,
                                                        checklist,
                                                        correct_result,
                                                        ERROR,
                                                        error_type,
                                                        description,
                                                        classification,
                                                        immediate_action,
                                                        immediate_cause,
                                                        incident_description,
                                                        related_to,
                                                        TYPE,oos_no,oos_date
                                                    )
                                                    VALUES(
                                                        '".$_GET["plant_id"]."',
                                                        '".$_GET["id"]."',
                                                        '".$_GET["testing_no"]."',
                                                        '".$_GET["specification_no"]."',
                                                        '".$_GET["sampling_no"]."',
                                                        '".$conn->real_escape_string(json_encode(isset($input["checkPointData"]) ? $input["checkPointData"] : array()))."',
                                                        '".$conn->real_escape_string((string)($input["correct_result"] ?? ''))."',
                                                        '".$conn->real_escape_string((string)($input["error"] ?? ''))."',
                                                        '".$conn->real_escape_string((string)($input["error_type"] ?? ''))."',
                                                        '".$conn->real_escape_string((string)($input["description"] ?? ''))."',
                                                        '".$conn->real_escape_string((string)($input["classification"] ?? ''))."',
                                                        '".$conn->real_escape_string((string)($input["immediate_action"] ?? ''))."',
                                                        '".$conn->real_escape_string((string)($input["immediate_cause"] ?? ''))."',
                                                        '".$conn->real_escape_string((string)($input["incident_description"] ?? ''))."',
                                                        '".$conn->real_escape_string((string)($input["related_to"] ?? ''))."',
                                                        '".$conn->real_escape_string((string)($input["type"] ?? ''))."',
                                                        '".$conn->real_escape_string((string)($input["oos_no"] ?? ''))."',
                                                        '".$conn->real_escape_string((string)($input["oos_date"] ?? ''))."'
                                                    ) ";
                     $conn->query($sql3);
                     echo "{\"status\":\"success\"}";
               } 
            // }
            else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        
        
    }
    else if ($_GET["type"] == "testing_correction_incident") {
        
            // $sql = "update testing set correction='incident' where testing_no='".$_GET["testing_no"]."'";
            // if ($conn->query($sql)) {
              
             
                 $sql2 = "UPDATE testing_tests SET correction='incident' WHERE id=".$_GET["id"];
               if ($conn->query($sql2)) {
                     	$sql3 = "INSERT INTO incident (user_no, department, related_to, category, type, classification, 
		description, immediate_action, initiate_by, initiate_date,sampling_no,specification_no,testing_test_id,error,error_type ) VALUES 
		('".$_GET["user_no"]."', '".$_GET["department"]."', '".$input["related_to"]."', '".$input["category"]."', '".$input["type"]."', 
		'".$input["classification"]."', '".$input["description"]."', '".$input["immediate_action"]."', '".$_GET["emp_id"]."', '$entry_date', '".$_GET["sampling_no"]."'
		, '".$_GET["specification_no"]."', '".$_GET["testing_test_id"]."', '".$input["error"]."', '".$input["error_type"]."')";
		
                     $conn->query($sql3);
                     echo "{\"status\":\"success\"}";
            //   } 
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        
        
    }
    else if ($_GET["type"] == "reportError") {
        if ($input["form_type"] == "error1") {
            $sql = "INSERT INTO incident (related_to, category, type, justification, cause) VALUES ('Typographical Mistake', 'Unplanned Incident', 'Minor', '".$input["incident_description"]."', '".$input["immediate_cause"]."')";
            if ($conn->query($sql)) {
                $last_id = $conn->insert_id;
                echo "{\"status\":\"success\"}";
                $sql2 = "UPDATE testing_tests SET fail_status='active', fail_form='Incident Reporting Form', fail_no='".$last_id."', correct_result='".$input["correct_result"]."' WHERE id=".$input["test_no"];
                $conn->query($sql2);
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } else {
            echo "{\"status\":\"success\"}";
        }
        
    } 
    
    else if ($_GET["type"] == "rejectTonew") {
        
        if($input["observation_new"]=='complies'){
            $analysis='No';
        $sql2 = "UPDATE testing_tests SET test_status ='tested'   WHERE id='".$input["id"]."'";
        }else{
                $analysis='Yes';
        $sql2 = "UPDATE testing_tests SET test_status ='failed'   WHERE id='".$input["id"]."'";
        }
         
            
        if ($conn->query($sql2)) {
            
              $sql3 = "INSERT INTO testing_tests (test_status,testing_no, test, ismethod, method_details, isoutside,result,remark,observation,
             start_time,end_time,specification_no,spec_test_id,incident_oos_no,person,perform_by,person_alt,reanalysis) VALUES ('retested','".$input["testing_no"]."','".$input["test"]."',
             'No','1','No','".$input["result_new"]."','".$input["remark_new"]."','".$input["observation_new"]."','".$input["start_time_new"]."',
             '".$input["end_time_new"]."','".$input["specification_no"]."','".$input["spec_test_id"]."','".$input["incident_oos_no"]."','".$input["person"]."'
             ,'".$input["perform_by"]."','".$input["person_alt"]."','$analysis')";
            
             $conn->query($sql3);

            
             
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
         
        
    } 
    else if ($_GET["type"] == "Approverejecttest") {
        
        
         
          $sql = "UPDATE testing SET status='inprocess'   WHERE testing_no = '".$_GET["testing_no"]."'";
            
        if ($conn->query($sql)) {
              
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
         
        
    } 
    else if ($_GET["type"] == "Approverejecttest_Micro") {
        
        
         
          $sql = "UPDATE testing SET micro_status='inprocess'   WHERE testing_no = '".$_GET["testing_no"]."'";
            
        if ($conn->query($sql)) {
              
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
         
        
    } 
    
    else if ($_GET["type"] == "checkTesting") {
        
            $isRejct=true;  
            $json_obj = json_encode($input["spec_tests"]);
            $array = json_decode($json_obj, true);
            
                foreach ($array as $values)
                {
                    if($values["observation"] !== 'complies'){
                      $isRejct=false;
                    }
                }
                
            if($isRejct==true){
                $sql = "UPDATE testing SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
            }
            else{
                
                $sql = "UPDATE testing SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date',correction='YES' WHERE id='".$_GET["id"]."'";
           
            }
            
        if ($conn->query($sql)) {
           
            $json_obj = json_encode($input["spec_tests"]);
            $array = json_decode($json_obj, true);
            
                foreach ($array as $values)
                {
                      $sql1 = "Update testing_tests set checked_by = '".$_GET["emp_id"]."', checked_date='$entry_date' , checker_action = '".$values["error_type"]."'  WHERE spec_test_id='".$values["spec_test_id"]."'";
        
                    $conn->query($sql1);
                    
                }
                
                
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    else if ($_GET["type"] == "checkTesting_Micro") {
        
            $isRejct=true;  
            $json_obj = json_encode($input["spec_tests"]);
            $array = json_decode($json_obj, true);
            
                foreach ($array as $values)
                {
                    if($values["observation"] !== 'complies'){
                      $isRejct=false;
                    }
                }
                
            if($isRejct==true){
                $sql = "UPDATE testing SET micro_status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
            }
            else{
                
                $sql = "UPDATE testing SET micro_status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date',correction='YES' WHERE id='".$_GET["id"]."'";
           
            }
            
        if ($conn->query($sql)) {
           
            $json_obj = json_encode($input["spec_tests"]);
            $array = json_decode($json_obj, true);
            
                foreach ($array as $values)
                {
                    $sql1 = "Update testing_tests set checked_by = '".$_GET["emp_id"]."', checked_date='$entry_date' , checker_action = '".$values["error_type"]."'  WHERE id='".$values["id"]."'";
        
                    $conn->query($sql1);
                    
                }
                
                
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    
      
    else if ($_GET["type"] == "checkTesting_finish") {
        $sql = "UPDATE samplingfg SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
       $data =$input["spec_tests"];
      
        if ($conn->query($sql)) {
           
               
         for ($i = 0; $i < count($data); $i++) {
                $row1 = $data[$i];
                
                $sql1 = "Update testing_tests set checked_by = '".$_GET["emp_id"]."', checked_date='$entry_date' , checker_action = '".$row1["test_action"]."'  WHERE id='".$row1["id"]."'";
                    
                     $conn->query($sql1);
            }
            
            
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "approveTesting") {
        require_once __DIR__.'/../../store/retest_helpers.php';
        
        $sql = "UPDATE testing SET  approvalRemark = '".$input["remark"]."',  status = '".$input["status"]."', micro_status = '".$input["status"]."', outside_status = '".$input["status"]."', 
        approve_by = '".$_GET["emp_id"]."', approve_date = '$entry_date' WHERE id = '".$input["testingId"]."'";
 
        if ($conn->query($sql)) {
            $stockSync = medicap_retest_apply_testing_approval(
                $conn,
                $input,
                (string)($_GET['plant_id'] ?? ''),
                (string)($input['testingId'] ?? '')
            );
            if (!empty($stockSync['ok'])) {
                echo "{\"status\":\"success\"}";
            } else {
                echo json_encode(array(
                    'status' => 'success',
                    'stock_sync' => 'failed',
                    'msg' => $stockSync['msg'] ?? 'Testing approved but stock_book retest dates were not updated.',
                ));
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
    else if ($_GET["type"] == "getCheckedTestingReport") {
        
        $sql = "select t.* , b.material_type,b.material_name,b.grade,b.retest_month, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode FROM testing t 
        LEFT JOIN sampling_batches s ON s.batch_no = t.batch_no AND s.ar_no = t.ar_no AND s.grn_no = t.grn_no
        LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
        LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
        left join material b on t.material_code = b.material_code
        where t.plant_id = '".$_GET['plant_id']."' AND b.material_type = '".$_GET['material_type']."'
        AND LOWER(TRIM(t.status)) = 'checked'
        AND (
            LOWER(TRIM(t.micro_status)) = 'checked'
            OR NOT EXISTS (
                SELECT 1 FROM testing_tests tt
                WHERE tt.testing_no = t.testing_no AND tt.test_type = 'Microbiology' AND tt.isoutside = 'No'
            )
        )
        AND (
            LOWER(TRIM(t.outside_status)) = 'checked'
            OR NOT EXISTS (
                SELECT 1 FROM testing_tests tt
                WHERE tt.testing_no = t.testing_no AND tt.isoutside = 'Yes'
            )
        )
        order by t.allocationOn desc ";
        
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                qc_testing_enrich_source_fields($conn, $row);
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    
    } 
    else if ($_GET["type"] == "getRejectedTestingLog") {
        
        $sql = "select t.* , b.material_type,b.material_name,b.grade,b.retest_month, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode FROM testing t 
        LEFT JOIN sampling_batches s ON s.batch_no = t.batch_no AND s.ar_no = t.ar_no AND s.grn_no = t.grn_no
        LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
        LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
        left join material b on t.material_code = b.material_code where t.plant_id = '".$_GET['plant_id']."' AND b.material_type = '".$_GET['material_type']."' AND t.status = 'Rejected' AND t.micro_status = 'Rejected' AND t.outside_status = 'Rejected'  order by t.allocationOn desc ";
        
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    
    } 
    else if ($_GET["type"] == "getApproveTestingLog") {
        
        $sql = "select t.* , b.material_type,b.material_name,b.grade,b.retest_month, c1.LglNm as clientGrpCodeName, c2.LglNm as clientSubGrpCodeName, s.clientGrpCode , s.clientSubGrpCode FROM testing t 
        LEFT JOIN sampling_batches s ON s.batch_no = t.batch_no AND s.ar_no = t.ar_no AND s.grn_no = t.grn_no
        LEFT JOIN client c1 ON c1.client_code = s.clientGrpCode
        LEFT JOIN client c2 ON c2.client_code = s.clientSubGrpCode
        left join material b on t.material_code = b.material_code where t.plant_id = '".$_GET['plant_id']."' AND b.material_type = '".$_GET['material_type']."' AND t.status = 'Approved'  order by t.allocationOn desc ";
        
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                qc_testing_enrich_source_fields($conn, $row);
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    
    } 
     
 
 
    else if ($_GET["type"] == "getpmtestinglog") {
         $sql = "SELECT   t.*,  m.material_name, m.grade , m.material_type,
         (select GROUP_CONCAT(grade.grade) from grade where grade.id  in( grades)) as gradeName ,
         (select specification_no from specification  where specification.material_code = t.material_code limit 1)  as specification_no ,
         
         (select vendor_coa from challan_materials  where challan_materials.grn_no = t.grn_no limit 1)  as vendor_coa  ,
         (select ar_no from sampling  where t.grn_no = sampling.grn_no limit 1)  as arno  
         
         FROM testing t LEFT JOIN material m ON t.material_code = m.material_code
         where t.status = 'Approved'  and t.plant_id='".$_GET["plant_id"]."' and t.is_RDS='".$_GET["is_RDS"]."' and b.material_type='".$_GET["material_type"]."'  order by 1 desc";
 
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
              
               
                $output1 = Array();
 
                       $sql1="SELECT t.* , (select reference_type from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as reference_type ,
                              (select description from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as description ,
                              (select limits from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as limits FROM testing_tests  t WHERE t.testing_no='".$row["testing_no"]."' AND t.specification_no='".$row["specification_no"]."' and t.checker_action!=''     order by id asc";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                    $row["tests"] = $output1;
                      $q= 'SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('.$row['grade'].')';
            $resQ = $conn->query($q);
           $prodLatest = $resQ->fetch_assoc();
           $row['gradeName'] = $prodLatest['gradeName'];
                    $output[] = $row;
                 
            }
        }
        echo json_encode($output);
    } 
 
    else if ($_GET["type"] == "getCheckedTestingReport_finish") {
   
        $sql="SELECT *,a.id as fg_id FROM samplingfg a left join product b on a.product_code=b.product_code where a.status in('checked','reject') order by 1 desc";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
                
          
            $sql1="SELECT *,t.status as t_status FROM testing_tests t LEFT JOIN spec_tests st on t.specification_no=t.specification_no and t.test=st.test and t.spec_test_no=st.id  WHERE t.status!='inprocess' and t.status!='pending' and t.fg_sampling_no='".$row["sampling_no"]."'";
            
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                    $row["tests"] = $output1;
                    $output[] = $row;
                 
            }
        }
        echo json_encode($output);
    } 
    
 
    else if ($_GET["type"] == "getTestingReport_finish") {
        // $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t LEFT JOIN material m ON t.material_code=m.material_code
        //         where t.status='Approved'";
            $sql="    SELECT * FROM samplingfg a left join product b on a.product_code=b.product_code where a.status='Approved' order by 1 desc";
        // $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t 
        // JOIN material m ON
        // t.material_code=m.material_code AND t.status='Approved'";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
                
                // $sql1 = "SELECT a.*,b.limit_type,b.reference_type,b.limits FROM testing_tests a join spec_tests b on a.spec_test_no = b.id  
                // WHERE a.user_no='".$_GET["user_no"]."' AND testing_no='".$row["testing_no"]."'";
            //   $sql1="SELECT t. *,t.method_details,t.status,t3.chemical_reagents,t3.balance,t3.equipment_instruments,t3.glasswares,
            //             s2.specification_no,t.testing_no, s2.specification_no,t.testing_no,s2.reference_type,
            //             s2.limits FROM testing_tests t left join testing t1 ON t.testing_no=t1.testing_no LEFT
            //             join sampling s on t1.sampling_no=s.sampling_no LEFT JOIN spec_tests s2 on s2.specification_no=s.specification_no LEFT
            //             join test_methods t3 on t3.test_id=s2.id WHERE t.user_no='".$_GET["user_no"]."' AND t.testing_no='".$row["testing_no"]."'";
               
            //   $sql1="SELECT t.* , 
            //             (select reference_type from spec_tests st where  st.test=t.test and st.subtest =t.subtest limit 1) as reference_type
            //          FROM testing_tests  t WHERE t.testing_no='".$row["testing_no"]."' order by id desc";
               $sql1="SELECT *,t.status as t_status FROM testing_tests t LEFT JOIN spec_tests st on t.specification_no=t.specification_no and t.test=st.test and t.spec_test_no=st.id  WHERE t.status!='inprocess' and t.status!='pending' and t.fg_sampling_no='".$row["sampling_no"]."'";

                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                    $row["tests"] = $output1;
                    $output[] = $row;
                 
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTestingReport_finish_rds") {
    
            $sql="    SELECT * FROM samplingfg a left join product b on a.product_code=b.product_code where a.status='inprocess' order by 1 desc";
      
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
            $sql1="SELECT *,a.result as t_result,a.remark as a_remark FROM testing_tests a left join samplingfg b on a.specification_no=b.specification_no WHERE  a.specification_no='".$row["specification_no"]."' and a.status='inprocess'";

                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                    $row["tests"] = $output1;
                    $output[] = $row;
                 
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTestingCOATests") {
        $testingNo = isset($_GET['testing_no']) ? trim((string)$_GET['testing_no']) : '';
        $specNo = isset($_GET['specification_no']) ? trim((string)$_GET['specification_no']) : '';
        $tests = qc_testing_tests_with_spec($conn, $testingNo, $specNo);
        echo json_encode($tests);
    }
    else if ($_GET["type"] == "getTestingReport") {
         $output = array();
         $plantId = isset($_GET['plant_id']) ? $conn->real_escape_string($_GET['plant_id']) : '';
         if ($plantId === '') {
             $plantId = '1126';
         }
         $fromDate = isset($_GET['from_date']) ? $conn->real_escape_string($_GET['from_date']) : '';
         $toDate = isset($_GET['to_date']) ? $conn->real_escape_string($_GET['to_date']) : '';
         $includeTests = !isset($_GET['include_tests']) || $_GET['include_tests'] !== '0';
         $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade
                 FROM testing t
                 LEFT JOIN material m ON t.material_code=m.material_code
                 WHERE LOWER(TRIM(t.status)) IN ('approved', 'approve')
                 AND t.plant_id='".$plantId."'";
         if ($fromDate !== '') {
             $sql .= " AND DATE(t.entry_date) >= '".$fromDate."'";
         }
         if ($toDate !== '') {
             $sql .= " AND DATE(t.entry_date) <= '".$toDate."'";
         }
         $sql .= " ORDER BY t.id DESC LIMIT 500";
        $result = @$conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($includeTests) {
                    $output1 = qc_testing_tests_with_spec($conn, $row["testing_no"], isset($row["specification_no"]) ? $row["specification_no"] : '');
                    $row["tests"] = $output1;
                    $row["spec_tests"] = $output1;
                }
                $row['gradeName'] = isset($row['grade']) ? $row['grade'] : '';
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
  
  
    else if($_GET["type"]=="get_methods"){
    echo	$sql = "SELECT * FROM test_methods WHERE spec_test_id='".$_GET["id"]."'";
    		$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		$output = Array();
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
 
    
    
     
    
    
    else if ($_GET["type"] == "getPendingTestingFormsOutsideLog") {
 
  
        $sql = "SELECT t.*,s.batch_no,s.supplier_batch_no, m.material_name, m.grade as m_grade, m.material_type FROM testing t LEFT JOIN material m ON 
        t.material_code = m.material_code left join sampling s on t.sampling_no =s.sampling_no  WHERE   t.status !='pending' 
        AND  t.outside_status='Approved'    and t.plant_id='".$_GET["plant_id"]."'  order by 1 desc ";
        
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = Array(); 
                 
                   $sql1="SELECT *,t.id as ttt_id,t.status as t_status,tt.method as moa_status  FROM testing_tests t   LEFT JOIN 
                   spec_tests s2 on s2.test=t.test  and 
                t.spec_test_id=s2.id left join test tt on tt.test_method_no=s2.test_method_no 
                   AND tt.test=s2.test  WHERE t.testing_no='".$row["testing_no"]."'  AND t.isoutside != 'No'   AND 
                s2.specification_no='".$row["specification_no"]."'";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    
                    $output1[] = $row1;
                
                    }
                }
                
                $row["tests"] = $output1;
                
                
                if(count($output1) > 0){
                    $output[] = $row;

                }
                
                 
                
            }
        }
        
        echo json_encode($output);
        
    }
    
    
    
    else if ($_GET["type"] == "getTesting_methodsForApproval") {
        
 
                $output1 = Array();
                
             $sql1 = "select * from test_methods where test_method_no='".$_GET["test_method_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      //  $row1["method_details"] = json_decode($row1["method_details"]);
                                $row1["Genral_Instruction"] = json_decode($row1["Genral_Instruction"]); 
                                $row1["Associative_Document"] = json_decode($row1["Associative_Document"]); 
                                $row1["Refrenced_Document"] = json_decode($row1["Refrenced_Document"]); 
                                $row1["defination"] = json_decode($row1["defination"]); 
                                $row1["revision_history"] = json_decode($row1["revision_history"]); 
                                $row1["testinginstruction"] = json_decode($row1["testinginstruction"]); 
                                $row1["equipment_instruments"] = json_decode($row1["equipment_instruments"]); 
                                $row1["chemical_reagents"] = json_decode($row1["chemical_reagents"]); 
                                $row1["glasswares"] = json_decode($row1["glasswares"]); 
                                $row1["balance"] = json_decode($row1["balance"]); 
                                $row1["dilutions"] = json_decode($row1["dilutions"]); 
                                $row1["volumetric_solutions"] = json_decode($row1["volumetric_solutions"]); 
                                $row1["hplc"] = json_decode($row1["hplc"]); 
                                $row1["phases"] = json_decode($row1["phases"]); 
                                $output1[] = $row1;
                                
                         
                         
                         
                    }
                }
                
                  echo json_encode($output1);
    }
    
    else if ($_GET["type"] == "getTesting_methods") {
        
 
                $output1 = Array();
                
             $sql1 = "select * from test_methods where test_method_no='".$_GET["test_method_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                                                 $row1["volumetric_solutions"] = json_decode($row1["volumetric_solutions"]);

                         $row1["chemical_reagents"] = json_decode($row1["chemical_reagents"]);
                        $row1["balance1"] = json_decode($row1["balance"]);
                         $row1["testinginstruction"] = json_decode($row1["testinginstruction"]);
                         $row1["Genral_Instruction"] = json_decode($row1["Genral_Instruction"]);
                         $row1["Associative_Document"] = json_decode($row1["Associative_Document"]);
                         $row1["Refrenced_Document"] = json_decode($row1["Refrenced_Document"]);
                         $row1["defination"] = json_decode($row1["defination"]);
                         $row1["calculations"] = json_decode($row1["calculations"]);
                         $row1["hplc"] = json_decode($row1["hplc"]);
                         $row1["dilutions"] = json_decode($row1["dilutions"]);
                         $row1["phases"] = json_decode($row1["phases"]);
                         //$row1["equipment_instruments"] = json_decode($row1["equipment_instruments"]);
                         $row1["glasswares"] = json_decode($row1["glasswares"]);
                         
                         
                                            
                    //   $row1["equipment_data"] = json_decode($row1["equipment_instruments"]);
                       
                 
                $equipment_instruments = json_decode($row1["equipment_instruments"]);
                
               
                
                if (is_array($equipment_instruments)) {
                     for ($i = 0; $i < count($equipment_instruments); $i++) {
                        $equipment_id = $equipment_instruments[$i]->equipment_id;
                           $sql11 = "SELECT a.*, b.*
                            FROM equipment a
                            LEFT JOIN (
                                SELECT equipment_id, MAX(date) AS latest_calibration_date
                                FROM daily_caibration
                                GROUP BY equipment_id
                            ) latest_calibration ON a.equipment_code = latest_calibration.equipment_id
                            LEFT JOIN daily_caibration b ON a.equipment_code = b.equipment_id AND b.date = latest_calibration.latest_calibration_date
                            WHERE a.equipment_code='$equipment_id'";
                $result11 = $conn->query($sql11);
               $jadugar = Array();
                         if ($result11->num_rows > 0) {
                            while ($row11 = $result11->fetch_assoc()) {
                                     		  if ($row11["calibration_frequency_inhouse"] == 'Monthly' &&  $row11["date"]!='') {
                                    $N_date = $row11["date"];
                                    $cal_date = date('Y-m-d', strtotime('+1 month', strtotime($N_date)));
                                 }
                     		    else if ($row11["calibration_frequency_inhouse"]=='Daily Calibration'  &&  $row11["date"]!=''){
                    		      $N_date= $row11["date"] ;
                    		        $cal_date=  date('Y-m-d', strtotime('+1 day', strtotime($N_date)));
                    		    }
                    		    else if($row11["calibration_frequency_inhouse"]=='Annually'  &&  $row11["date"]!=''){
                    		      $N_date= $row11["date"] ;
                    		        $cal_date=  date('Y-m-d', strtotime('+1 year', strtotime($N_date)));
                    		    }
                                     $row11["cal_datess"] = $cal_date;
                     		   if ($todate <= $cal_date) {
                                    $row11["cal_stat"] = 'complete';
                                } else {
                                    $row11["cal_stat"] = 'Not complete';
                                }
                                 
                                
                                
                                
                            //  $jadugar[] = $row11;
                              $row1["eqdates"][] = $row11;

                            }
                        }
                    }
                }
                
                
              //   $balance = json_decode($row1["balance"]);
                 
                // if (is_array($balance)) {
                //      for ($i = 0; $i < count($balance); $i++) {
                //         $equipment_id = $balance[$i]->equipment_id;
                //      echo     $sql11 = "SELECT a.*, b.*
                //             FROM equipment a
                //             LEFT JOIN (
                //                 SELECT equipment_id, MAX(date) AS latest_calibration_date
                //                 FROM daily_caibration
                //                 GROUP BY equipment_id
                //             ) latest_calibration ON a.equipment_code = latest_calibration.equipment_id
                //             LEFT JOIN daily_caibration b ON a.equipment_code = b.equipment_id AND b.date = latest_calibration.latest_calibration_date
                //             WHERE a.status = 'approve' AND a.equipment_code='$equipment_id'";
                // $result11 = $conn->query($sql11);
              
                //          if ($result11->num_rows > 0) {
                //             while ($row11 = $result11->fetch_assoc()) {
                //                      		  if ($row11["calibration_frequency_inhouse"] == 'Monthly' &&  $row11["date"]!='') {
                //                     $N_date = $row11["date"];
                //                     $cal_date = date('Y-m-d', strtotime('+1 month', strtotime($N_date)));
                //                  }
                //      		    else if ($row11["calibration_frequency_inhouse"]=='Daily Calibration'  &&  $row11["date"]!=''){
                //     		      $N_date= $row11["date"] ;
                //     		        $cal_date=  date('Y-m-d', strtotime('+1 day', strtotime($N_date)));
                //     		    }
                //     		    else if($row11["calibration_frequency_inhouse"]=='Annually'  &&  $row11["date"]!=''){
                //     		      $N_date= $row11["date"] ;
                //     		        $cal_date=  date('Y-m-d', strtotime('+1 year', strtotime($N_date)));
                //     		    }
                //                      $row11["cal_datess"] = $cal_date;
                //      		   if ($todate <= $cal_date) {
                //                     $row11["cal_stat"] = 'complete';
                //                 } else {
                //                     $row11["cal_stat"] = 'Not complete';
                //                 }
                                 
                                
                                
                                
                              
                //               $row1["balance1"][] = $row11;

                //             }
                //         }
                //     }
                // }
                 
                
                $chemical_reagents = $row1['chemical_reagents'];
                
                if (is_array($chemical_reagents)) {
                    for ($i = 0; $i < count($chemical_reagents); $i++) {
                        $chemical_name = $chemical_reagents[$i]->material_name; // Accessing object property correctly
                        $sql12 = "SELECT a.material_name,a.material_type,a.material_code  FROM others_material  a 
                                  WHERE a.material_name='$chemical_name'";
                        $result12 = $conn->query($sql12);
                        if ($result12->num_rows > 0) {
                            while ($row12 = $result12->fetch_object()) {
                                // Move the code that depends on $row12["chemical_no"] here
                                $output15 = array();
                                $sql15 = "SELECT batch_no FROM engi_stock WHERE material_code='".$row12->material_code."'";
                                $result15 = $conn->query($sql15);
                                if ($result15->num_rows > 0) {
                                    while ($row15 = $result15->fetch_assoc()) {
                                        
                                       
                                $sql1 = "SELECT IFNULL(SUM(qty),0) as total_qty FROM engi_stock  where  batch_no    = '".$row15["batch_no"]."' ";
                            	$result1 = $conn->query($sql1);
                            	if($result1->num_rows > 0){
                            		while ($row2611 = $result1->fetch_assoc()) {
                            		    $row15['total_qty'] = $row2611['total_qty'] ;
                            		}
                            	}
                            	
                            	$sql2 = "SELECT IFNULL(SUM(qty),0) as diduct_qty FROM engi_stock_isshue  where  batch_no    = '".$row15["batch_no"]."' ";
                            	$result2 = $conn->query($sql2);
                            	if($result2->num_rows > 0){
                            		while ($row2611 = $result2->fetch_assoc()) {
                            		    $row15['diduct_qty'] = $row2611['diduct_qty'] ;
                            		}
                            	}
                            	
                               // $row15['uom'] =  $row12['unit'] ;
                                $row15['uom'] =  'kg';
                                $row15['avaliable_qty'] =  $row15['total_qty'] -  $row15['diduct_qty'];
                        	    $row15['avaliable_qty'] = number_format((float)$row15['avaliable_qty'], 2, '.', '');
                                                        
                                            
                                        
                                        $output15[] = $row15;
                                    }
                                }
                                
                                 
                                $row12->batch_no = $output15;
                                
                                $row1["chems_dats"][] = $row12;
                            }
                        }else{
                              $row1["chems_dats"][] = [];
                        }
                    }
                }
                
                                       $volumetric_solutions = $row1['volumetric_solutions'];
                        
                        if (is_array($volumetric_solutions)) {
                            for ($i = 0; $i < count($volumetric_solutions); $i++) {
                                $output15 = array();
                                $solution_no = $volumetric_solutions[$i]->solution_no;  
                        
                                $sql12 = "SELECT vp.volume_prepared, vp.batch_no FROM volumetric_preparation vp WHERE vp.solution_no='$solution_no'";
                                $result12 = $conn->query($sql12);
                                if ($result12->num_rows > 0) {
                                    while ($row12 = $result12->fetch_object()) {
                                        
                                        $sql2 = "SELECT IFNULL(SUM(qty),0) as diduct_qty FROM engi_stock_isshue WHERE 
                                        material_code = '$solution_no' AND batch_no = '" . $row12->batch_no . "'";
                                        
                                        $result2 = $conn->query($sql2);
                                        if ($result2->num_rows > 0) {
                                            while ($row2611 = $result2->fetch_assoc()) {
                                                $row12->diduct_qty = $row2611['diduct_qty'];
                                            }
                                        }
                        
                                        $row12->avaliable_qty = $row12->volume_prepared - $row12->diduct_qty;
                                        $row12->avaliable_qty = number_format((float)$row12->avaliable_qty, 2, '.', '');
                        
                                        $output15[] = $row12;
                                    }
                                } 
                        
                                $volumetric_solutions[$i]->batch_no = $output15;
                            }
                        
                            $row1['volumetric_solutions'] = $volumetric_solutions;
                        }
                     
                        $output1[] = $row1;
                        
                    }
                }
               
            
                
        
        echo json_encode($output1);
    
        
    }
    else if ($_GET["type"] == "getTestinglog") {
        $sql = "SELECT t.*, m.material_name, m.grade, m.material_type FROM testing t LEFT JOIN material m ON t.material_code=m.material_code where  t.plant_id = '".$_GET["plant_id"]."'   order by 1 desc ";
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                                                           
                $output1 = Array();
                
                $sql1 = "select * from testing_tests  where isoutside = 'No' AND testing_no = '".$row['testing_no']."' ";
         
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                             $output1[] = $row1;
                    }
                }
                
                $row["tests"] = $output1;
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingTestingForms_finish") {
        // $sql = "SELECT t.*, m.material_name, m.grade, m.material_type FROM testing t LEFT JOIN material m ON t.material_code=m.material_code WHERE t.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' and  t.status='inprocess'  and is_RDS=1 order by 1 desc ";
                   $sql="SELECT *,b.grade as pgrade FROM samplingfg a LEFT JOIN product b on a.product_code=b.product_code left join specification c on a.product_code=c.product_code where c.spec_type='Finish Product' and a.allocation_status='inprocess' and a.is_RDS='1' ";//

        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {


                

                $output1 = Array();
                //  $sql1 = "SELECT t. *,t.method_details,t.status,t3.chemical_reagents,t3.balance,t3.equipment_instruments,t3.glasswares, s2.specification_no,t.testing_no, s2.specification_no,t.testing_no,s2.reference_type,s2.limits FROM testing_tests t left join testing t1 ON t.testing_no=t1.testing_no LEFT join sampling s on t1.sampling_no=s.sampling_no LEFT JOIN spec_tests s2 on s2.specification_no=s.specification_no LEFT join test_methods t3 on t3.test_id=s2.id  WHERE s2.specification_no='".$row["specification_no"]."' AND t.testing_no='".$row["testing_no"]."' and t.status='pending'"; // 
                // $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
              
                // $sql1 = "SELECT t3. *,t.status,t3.chemical_reagents,t3.balance,t3.equipment_instruments,t3.glasswares, s2.specification_no FROM testing_tests t left join testing t1 ON t.testing_no=t1.testing_no LEFT join sampling s on t1.sampling_no=s.sampling_no LEFT JOIN spec_tests s2 on s2.specification_no=s.specification_no LEFT join test_methods t3 on t3.id=s2.id WHERE s2.specification_no='".$row["specification_no"]."' AND t.testing_no='".$row["testing_no"]."' and t.status='pending'";
            $sql1="   SELECT * FROM testing_tests a left join samplingfg b on a.fg_sampling_no=b.sampling_no LEFT JOIN spec_tests c on b.specification_no=c.specification_no LEFT JOIN test_methods d on d.test_id=c.id WHERE a.specification_no='".$row["specification_no"]."' and a.fg_sampling_no='".$row["sampling_no"]."' and b.status='pending' GROUP by a.id,b.id,c.id,d.id";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                         $row1["chemical_reagents"] = json_decode($row1["chemical_reagents"]);
                         $row1["balance"] = json_decode($row1["balance"]);
                         $row1["equipment_instruments"] = json_decode($row1["equipment_instruments"]);
                         $row1["glasswares"] = json_decode($row1["glasswares"]);
                         $output1[] = $row1;
                        
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            
                
            }
        }
        echo json_encode($output);
    }
  
    else if ($_GET["type"] == "oos_check") {
       echo   $sql = "UPDATE testing_tests SET  correction='primary_checking' WHERE id='".$_GET["testing_test_id"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	
    }
    else if ($_GET["type"] == "getOven") {
         $output1 = Array();
           $sql = "SELECT equipment_name,id,equipment_code,tag_no,location,department,equipment_type from equipment WHERE department = '".$_GET["deptName"]."' AND equipment_name LIKE '%".$_GET["cat"]."%'";
     $result1 = $conn->query($sql);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
	 echo json_encode($output1);
    }
    else if ($_GET["type"] == "getBalance") {
         $output1 = Array();
           $sql = "SELECT equipment_name,id,equipment_code,tag_no,location,department,equipment_type from equipment WHERE
           department = '".$_GET["deptName"]."'   AND equipment_type = 'Balance(Weighing)'";
     $result1 = $conn->query($sql);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
	 echo json_encode($output1);
    }
    else if ($_GET["type"] == "getDataLogger") {
         $output1 = Array();
           $sql = "SELECT equipment_name,id,equipment_code,tag_no,location,department,equipment_type from equipment 
           WHERE department = '".$_GET["deptName"]."' AND equipment_type = 'Data Logger'";
     $result1 = $conn->query($sql);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
	 echo json_encode($output1);
    }
    else if ($_GET["type"] == "oos_check_sec") {
           $sql = "UPDATE testing_tests SET  correction='secondary_checking' WHERE id='".$_GET["testing_test_id"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	
    }
    else if ($_GET["type"] == "oos_approved") {
       echo   $sql = "UPDATE testing_tests SET  correction='oos_approved' WHERE id='".$_GET["testing_test_id"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	
    }
    else if ($_GET["type"] == "getRejectedTestingForms_primary_oos") {
        $sql = "SELECT   t.*,  m.material_name, m.grade , m.material_type,
         (select GROUP_CONCAT(grade.grade) from grade where grade.id  in( grades)) as gradeName ,
         (select specification_no from specification  where specification.material_code = t.material_code limit 1)  as specification_no ,
         
         (select vendor_coa from challan_materials  where challan_materials.grn_no = t.grn_no limit 1)  as vendor_coa  ,
         (select ar_no from sampling  where t.grn_no = sampling.grn_no limit 1)  as arno  
         
         FROM testing t LEFT JOIN material m ON t.material_code = m.material_code
         where t.status in('checked','reject')  and t.plant_id='".$_GET["plant_id"]."' and t.is_RDS='".$_GET["is_rds"]."' and t.correction='pending'  order by 1 desc";
         $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
                
             
                $output1 = Array();
                     $sql1="SELECT DISTINCT t.id, t.*,b.id as oos_id,b.testing_test_id, b.testing_no, b.specification_no, b.sampling_no, b.checklist, b.correct_result, b.error, b.error_type, b.description, b.classification, b.immediate_action, b.immediate_cause, b.incident_description, b.related_to, b.type, b.oos_no, b.oos_date ,t.id as test_id, (select reference_type from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as reference_type , (select description from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as description , (select limits from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as limits FROM testing_tests t left join oos_correction b on t.id=b.testing_test_id
                     WHERE t.testing_no='".$row["testing_no"]."' AND t.specification_no='".$row["specification_no"]."' and t.checker_action='Reject'  and t.correction='oos'   order by id asc";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["checklist"] = json_decode($row1["checklist"]);
                        $output1[] = $row1;
                    }
                }
                
                    $row["tests"] = $output1;
                    if (count($output1) === 0) {
                        continue;
                    }
                    $gradeIds = trim((string)($row['grade'] ?? ''));
                    if ($gradeIds !== '' && preg_match('/^[\d,\s]+$/', $gradeIds)) {
                        $q= 'SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('.$gradeIds.')';
                        $resQ = @$conn->query($q);
                        if ($resQ && ($prodLatest = $resQ->fetch_assoc())) {
                            $row['gradeName'] = $prodLatest['gradeName'];
                        }
                    }
                    $output[] = $row;
                 
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRejectedTestingForms_secondary_oos") {
        $sql = "SELECT   t.*,  m.material_name, m.grade , m.material_type,
         (select GROUP_CONCAT(grade.grade) from grade where grade.id  in( grades)) as gradeName ,
         (select specification_no from specification  where specification.material_code = t.material_code limit 1)  as specification_no ,
         
         (select vendor_coa from challan_materials  where challan_materials.grn_no = t.grn_no limit 1)  as vendor_coa  ,
         (select ar_no from sampling  where t.grn_no = sampling.grn_no limit 1)  as arno  
         
         FROM testing t LEFT JOIN material m ON t.material_code = m.material_code
         where t.status in('checked','reject')  and t.plant_id='".$_GET["plant_id"]."' and t.is_RDS='".$_GET["is_rds"]."' and t.correction='primary'  order by 1 desc";
         $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
                
             
                $output1 = Array();
                     $sql1="SELECT DISTINCT t.id, t.*,b.id as oos_id,b.testing_test_id, b.testing_no, b.specification_no, b.sampling_no, b.checklist, b.correct_result, b.error, b.error_type, b.description, b.classification, b.immediate_action, b.immediate_cause, b.incident_description, b.related_to, b.type, b.oos_no, b.oos_date ,t.id as test_id, (select reference_type from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as reference_type , (select description from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as description , (select limits from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as limits FROM testing_tests t left join oos_correction b on t.id=b.testing_test_id
                     WHERE t.testing_no='".$row["testing_no"]."' AND t.specification_no='".$row["specification_no"]."' and t.checker_action='Reject'  and t.correction='primary_checking'   order by id asc";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["checklist"] = json_decode($row1["checklist"]);
                        $output1[] = $row1;
                    }
                }
                
                    $row["tests"] = $output1;
                    if (count($output1) === 0) {
                        continue;
                    }
                    $gradeIds = trim((string)($row['grade'] ?? ''));
                    if ($gradeIds !== '' && preg_match('/^[\d,\s]+$/', $gradeIds)) {
                        $q= 'SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('.$gradeIds.')';
                        $resQ = @$conn->query($q);
                        if ($resQ && ($prodLatest = $resQ->fetch_assoc())) {
                            $row['gradeName'] = $prodLatest['gradeName'];
                        }
                    }
                    $output[] = $row;
                 
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRejectedTestingForms_approval_oos") {
        $sql = "SELECT   t.*,  m.material_name, m.grade , m.material_type,
         (select GROUP_CONCAT(grade.grade) from grade where grade.id  in( grades)) as gradeName ,
         (select specification_no from specification  where specification.material_code = t.material_code limit 1)  as specification_no ,
         
         (select vendor_coa from challan_materials  where challan_materials.grn_no = t.grn_no limit 1)  as vendor_coa  ,
         (select ar_no from sampling  where t.grn_no = sampling.grn_no limit 1)  as arno  
         
         FROM testing t LEFT JOIN material m ON t.material_code = m.material_code
         where t.status in('checked','reject')  and t.plant_id='".$_GET["plant_id"]."' and t.is_RDS='".$_GET["is_rds"]."' and t.correction='secondary'  order by 1 desc";
         $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
                
             
                $output1 = Array();
                     $sql1="SELECT DISTINCT t.id, t.*,b.id as oos_id,b.testing_test_id, b.testing_no, b.specification_no, b.sampling_no, b.checklist, b.correct_result, b.error, b.error_type, b.description, b.classification, b.immediate_action, b.immediate_cause, b.incident_description, b.related_to, b.type, b.oos_no, b.oos_date ,t.id as test_id, (select reference_type from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as reference_type , (select description from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as description , (select limits from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as limits FROM testing_tests t left join oos_correction b on t.id=b.testing_test_id
                     WHERE t.testing_no='".$row["testing_no"]."' AND t.specification_no='".$row["specification_no"]."' and t.checker_action='Reject'  and t.correction='secondary_checking'   order by id asc";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["checklist"] = json_decode($row1["checklist"]);
                        $output1[] = $row1;
                    }
                }
                
                    $row["tests"] = $output1;
                    if (count($output1) === 0) {
                        continue;
                    }
                    $gradeIds = trim((string)($row['grade'] ?? ''));
                    if ($gradeIds !== '' && preg_match('/^[\d,\s]+$/', $gradeIds)) {
                        $q= 'SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('.$gradeIds.')';
                        $resQ = @$conn->query($q);
                        if ($resQ && ($prodLatest = $resQ->fetch_assoc())) {
                            $row['gradeName'] = $prodLatest['gradeName'];
                        }
                    }
                    $output[] = $row;
                 
            }
        }
        echo json_encode($output);
    } 
  
     else if ($_GET["type"] == "saveTestingFormOutside") {
        
        
        
           $input = $_POST;
            
            $plant_id =$_GET["plant_id"];
            $target_dir = "../../../../upload/outside_testing_report/";
           
        
        $flag = 1;
           
           if(isset($_FILES["report"]["name"])) {
            	$target_file = $target_dir.$plant_id.$_GET["id"]."_".basename($_FILES["report"]["name"]);
            	$msds_file = $plant_id.$_GET["id"]."_".basename($_FILES["report"]["name"]);
        	   // move_uploaded_file($_FILES["weigh_slip"]["tmp_name"], $target_file);
        	     $flag =0;
           }
        
        
         
        
     $sql = "UPDATE testing_tests  SET  remark='".$input["remark"]."', result='$msds_file', status='inprocess', perform_by='".$_GET["emp_id"]."' 
     WHERE id='".$_GET["id"]."'";
    
    if ($conn->query($sql)) {

            echo "{\"status\":\"success\"}";
            
            
                 
        if($flag == 0) {
        	    move_uploaded_file($_FILES["report"]["tmp_name"], $target_file);    
          }
            
            
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
      
    }
    else if ($_GET["type"] == "saveTestingawaitFormPack") {
        
        
        
        
        
        
        
        
        
     $sql = "UPDATE testing_tests  SET start_time = '".$input["start_date"]."', remark='".$input["remark"]."', result='".$input["result"]."', observation='".$input["observation"]."',
    end_time='".$input["end_time"]."',status='inprocess',perform_by='".$_GET["emp_id"]."' WHERE id='".$_GET["id"]."'";
    
    if ($conn->query($sql)) {
 
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
      
    }
    
    else if ($_GET["type"] == "saveTestingawaitForm") {
        
        
       $sql1="update testing set status='inprocess' , entry_by = '".$_GET['emp_id']."' , entry_date = '$entry_date' where testing_no='".$_GET["testing_no"]."'";
  if ($conn->query($sql1)) {
       
  
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
      
    }
    else if ($_GET["type"] == "saveTestingawaitForm_micro") {
        
       $sql1="update testing set micro_status='inprocess' where testing_no='".$_GET["testing_no"]."'";
       
        if ($conn->query($sql1)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
      
    }
    else if ($_GET["type"] == "saveTestingawaitForm_outside") {
        
       $sql1="update testing set outside_status='Approved' where testing_no='".$_GET["testing_no"]."'";
       
        if ($conn->query($sql1)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
      
    }
 
    else if ($_GET["type"] == "saveTestingForm_micro") {
                  $sql = "UPDATE micro_spec_test  SET remark='".$input["remark"]."', result='".$input["result"]."',end_time='".$input["end_time"]."',status='inprocess' WHERE spec_test_id='".$_GET["spec_test_id"]."'";
  if ($conn->query($sql)) {
      
      
//   echo   $sql1="update micro_test set status='inprocess' where id=''".$input["micro_test_id"]."''";
      
      
      
      
   $conn->query($sql1);
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
      
    }
    else if ($_GET["type"] == "saveTestingForm_finish") {
     $sql = "UPDATE testing_tests  SET remark='".$input["remark"]."', result='".$input["result"]."',end_time='".$input["end_time"]."',status='inprocess' WHERE fg_sampling_no='".$input["sampling_no"]."'";
  if ($conn->query($sql)) {
      
      
      $sql1="UPDATE samplingfg set status='inprocess' where specification_no='".$_GET["specification_no"]."'";
      
      
      
   $conn->query($sql1);
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
      
    }
    else if ($_GET["type"] == "saveTestingForm_ERP_micro") {
        error_reporting(0);
       
        
     echo   $sql = "UPDATE micro_test SET alalsis_start_date ='".$input["start_date"]."', alalsis_start_time='".$input["start_time"]."',
        alalsis_end_date='".$input["end_date"]."', alalsis_end_time='".$input["end_time"]."',
        status='inprocess' WHERE id='".$_GET["tid"]."'";
       
        if ($conn->query($sql) == TRUE) {
             $data =$input["spec_tests"];
         for ($i = 0; $i < count($data); $i++) {
                $row1 = $data[$i];
           echo     $sql1 = "update micro_spec_test set status='".$row1["status"]."' where spec_test_id='".$row1["spec_test_id"]."'";
              
                $conn->query($sql1);
            }
        
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        $sql = "";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            
        } else {
            $sql = "";
            $conn->query($sql);
        }
    }
    else if ($_GET["type"] == "saveTestingForm_ERP") {
        
        error_reporting(0);
       
        
        $sql = "UPDATE testing SET alalysis_start_date='".$input["start_date"]."', alalysis_start_time='".$input["start_time"]."',
        alalysis_end_date='".$input["end_date"]."', alalysis_end_time='".$input["end_time"]."',
        status='inprocess' WHERE id='".$input["id"]."'";
       
        if ($conn->query($sql) == TRUE) {
             $data =$input["spec_tests"];
         for ($i = 0; $i < count($data); $i++) {
                $row1 = $data[$i];
                $sql1 = "INSERT INTO testing_tests (plant_id,user_no, testing_no, test, subtest, description, isoutside,
                 method_details,result,testing_person,start_time,end_time,specification_no,spec_test_no,testing_date,testing_start_time,testing_end_time,testing_status,limits) 
                 VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."',
                '".$input["testing_no"]."','".$row1["test"]."',
                '".$row1["subtest"]."','".$row1["limits"]."', '".$row1["isoutside"]."',
                '".$row1["method_details"]."', '".$row1["result"]."','".$row1["tested_by"]."','".$input["start_time"]."',
                '".$input["end_time"]."','".$row1["specification_no"]."','".$row1["id"]."','".$row1["testing_date"]."','".$row1["start_time"]."','".$row1["end_time"]."','".$row1["status"]."','".$row1["limits"]."')";
              
                $conn->query($sql1);
            }
          /*  $descriptions = $input["descriptions"];
            for ($i = 0; $i < count($descriptions); $i++) {
                $data = $descriptions[$i];
                if ($data['option'] == "chemical") {
                    $chemicals = $data['list'];
                    for ($j = 0; $j < count($chemicals); $j++) {
                        $chemical = $chemicals[$j];
    
                        $sql1 = "INSERT INTO chemical_issue (chemical_no, batch_no, qty, purpose, status, entry_by, entry_date) VALUES ('".$chemical['id']."', '".$chemical['batch_no']."', '".$chemical['qty']."', 'TESTING', 'approve', '".$_GET["emp_id"]."', '$entry_date')"; 
                       $conn->query($sql1);
    
                        $sql1 = "SELECT * FROM chemicals WHERE chemical_no='".$chemical['id']."' AND batch_no='".$chemical['batch_no']."'";
                        $result = $conn->query($sql1);
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $received_qty = $row["received_qty"];
                                $issue_qty = $row["issue_qty"];
                                $issue_qty += +$chemical['qty'];
                                $sql1 = "UPDATE chemicals SET issue_qty='$issue_qty' WHERE id='".$row['id']."'";
                               $conn->query($sql1);
                                break;
                            }
                        }
                    }
                }
            }
             $sql = "INSERT INTO equipment_uses (equipment_no, batch_no, activity, cleaning_type, start_time, end_time, operator, entry_by, entry_date) VALUES ('".$input["equipment"]."', '', 'Testing', '".$input["cleaning_type"]."', '".$start_time."', '".$end_time."', '".$_GET["emp_id"]."', '".$_GET["emp_id"]."', '".$entry_date."')";
            $conn->query($sql); */
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        $sql = "SELECT * FROM testing_tests WHERE testing_no='".$input["testing_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            
        } else {
            $sql = "UPDATE testing SET status='active' WHERE testing_no='".$input["testing_no"]."'";
            $conn->query($sql);
        }
    }
    
    else if ($_GET["type"] == "saveTestingForm_ERP_finish") {
        error_reporting(0);
       
        
        $sql = "UPDATE  samplingfg SET alalysis_start_date='".$input["start_date"]."', alalysis_start_time='".$input["start_time"]."',
        alalysis_end_date='".$input["end_date"]."', alalysis_end_time='".$input["end_time"]."',
        testing_status='active' WHERE id='".$input["id"]."'";
       
        if ($conn->query($sql) == TRUE) {
             $data =$input["spec_tests"];
         for ($i = 0; $i < count($data); $i++) {
                $row1 = $data[$i];
                $sql1 = "INSERT INTO testing_tests (user_no, testing_no, test, subtest, description, isoutside,
                 method_details,result,testing_person,start_time,end_time,status,specification_no,spec_test_no,fg_sampling_no) VALUES ('".$_GET["user_no"]."',
                '".$input["testing_no"]."','".$row1["test"]."',
                '".$row1["subtest"]."','".$row1["description"]."', '".$row1["isoutside"]."',
                '".$row1["method_details"]."', '".$row1["result"]."','".$row1["tested_by"]."','".$input["start_time"]."',
                '".$input["end_time"]."','".$row1["status"]."','".$row1["specification_no"]."','".$row1["id"]."','".$input["sampling_no"]."')";
              
                $conn->query($sql1);
            }
 
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        $sql = "SELECT * FROM testing_tests WHERE testing_no='".$input["testing_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            
        } else {
            $sql = "UPDATE samplingfg SET testing_status='active' WHERE testing_no='".$input["testing_no"]."'";
            $conn->query($sql);
        }
    }
        else if($_GET['type'] == 'downloadTestingReport9'){
       $_GET['filename'] = 'A. R. Report'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
        $html= '
        <h2 style="text-align:center">A. R. Report</h2>
        <style>td { border:solid 1px BCBBBA;}</style>
        <table cellpadding="5">
            <thead>
                <tr>
                    <td style="background-color:#DDDAD9; width:100%; text-align:center;"><b>A. R. Report</b></td>
                </tr>
                <tr><td style="border:none;"></td></tr>
                <tr style="background-color:#DDDAD9;">
                    <td style="width:15%;">A R No.</td>
                    <td style="width:15%;">Sampling No</td>
                    <td style="width:20%;">Specification No</td>
                    <td style="width:20%;">Material Name</td>
                    <td style="width:15%;">Material Code</td>
                    <td style="width:15%;">Material Grade</td>
                   
                </tr>
            </thead>
            <tbody>';
        $plantId = isset($_GET['plant_id']) ? $conn->real_escape_string($_GET['plant_id']) : '';
        $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade
                FROM testing t
                LEFT JOIN material m ON t.material_code=m.material_code
                WHERE t.status='Approved'";
        if ($plantId !== '') {
            $sql .= " AND t.plant_id='".$plantId."'";
        }
        $sql .= " ORDER BY t.id DESC LIMIT 500";
            $result = @$conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                 $html.='
                    <tr>
                        <td style="width:15%;">'.htmlspecialchars((string)(isset($row['ar_no']) ? $row['ar_no'] : ''), ENT_QUOTES).'</td>
                        <td style="width:15%">'.htmlspecialchars((string)(isset($row['sampling_no']) ? $row['sampling_no'] : ''), ENT_QUOTES).'</td>
                        <td style="width:20%">'.htmlspecialchars((string)(isset($row['specification_no']) ? $row['specification_no'] : ''), ENT_QUOTES).'</td>
                        <td style="width:20%">'.htmlspecialchars((string)(isset($row['material_name']) ? $row['material_name'] : ''), ENT_QUOTES).'</td>
                        <td style="width:15%">'.htmlspecialchars((string)(isset($row['material_code']) ? $row['material_code'] : ''), ENT_QUOTES).'</td>
                        <td style="width:15%">'.htmlspecialchars((string)(isset($row['grade']) ? $row['grade'] : ''), ENT_QUOTES).'</td>
                    </tr>';
                }
            }
            $html.='
            </tbody>
        </table>
        <div></div>';
        $pdf->writeHTML($html, true, false, false, false, '');
        qc_testing_pdf_flush();
        $pdf->Output('REPORT LOG.pdf', 'I');
        exit;
    }

    
    

 else if($_GET['type'] == 'downloadlog') {
       $_GET['filename'] = 'A. R. Report'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
        $html= '
        <h2 style="text-align:center">A. R. Report</h2>
        <style>td { border:solid 1px BCBBBA;}</style>
        <table cellpadding="5">
            <thead>
                <tr>
                    <td style="background-color:#DDDAD9; width:100%; text-align:center;"><b>A. R. Report</b></td>
                </tr>
                <tr><td style="border:none;"></td></tr>
                <tr style="background-color:#DDDAD9;">
                    <td style="width:15%;">A R No.</td>
                    <td style="width:15%;">Sampling No</td>
                    <td style="width:20%;">Specification No</td>
                    <td style="width:20%;">Material Name</td>
                    <td style="width:15%;">Material Code</td>
                    <td style="width:15%;">Material Grade</td>
                   
                </tr>
            </thead>
            <tbody>';
        $sql = "SELECT t.*, m.material_name, m.grade, m.material_type FROM testing t LEFT JOIN material m ON t.material_code=m.material_code  order by 1 desc ";
            $output = Array();
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                  
                 $html.='
                    <tr>
                        <td style="width:15%;">'.$row['ar_no'].'</td>
                        <td style="width:15%">'.$row['sampling_no'].'</td>
                        <td style="width:20%">'.$row['specification_no'].'</td>
                        <td style="width:20%">'.$row['material_name'].'</td>
                        <td style="width:15%">'.$row['material_code'].'</td>
                        <td style="width:15%">'.$row['grade'].'</td>
                       
                    </tr>';
                }
            }
            $html.='
            </tbody>
        </table>
        <div></div>';
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('coa.pdf', 'I');
    }
 
    else if($_GET['type'] == 'ARReportlog') {
       $_GET['filename'] = 'A. R. Report'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
        $html= '
        <h2 style="text-align:center">A. R. Report</h2>
        <style>td { border:solid 1px BCBBBA;}</style>
        <table cellpadding="5">
            <thead>
                <tr>
                    <td style="background-color:#DDDAD9; width:100%; text-align:center;"><b>A. R. Report</b></td>
                </tr>
                <tr><td style="border:none;"></td></tr>
                <tr style="background-color:#DDDAD9;">
                    <td style="width:15%;">A R No.</td>
                    <td style="width:15%;">Sampling No</td>
                    <td style="width:20%;">Specification No</td>
                    <td style="width:20%;">Material Name</td>
                    <td style="width:15%;">Material Code</td>
                    <td style="width:15%;">Material Grade</td>
                   
                </tr>
            </thead>
            <tbody>';
            $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t LEFT JOIN material m ON t.material_code=m.material_code WHERE t.status='approve' AND m.material_type='Raw Material'";
            $output = Array();
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                  
                 $html.='
                    <tr>
                        <td style="width:15%;">'.$row['ar_no'].'</td>
                        <td style="width:15%">'.$row['sampling_no'].'</td>
                        <td style="width:20%">'.$row['specification_no'].'</td>
                        <td style="width:20%">'.$row['material_name'].'</td>
                        <td style="width:15%">'.$row['material_code'].'</td>
                        <td style="width:15%">'.$row['grade'].'</td>
                       
                    </tr>';
                }
            }
            $html.='
            </tbody>
        </table>
        <div></div>';
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('coa.pdf', 'I');
    }
    else if($_GET['type'] == 'ARReportdigital'){
        $sql = "SELECT * FROM testing WHERE ar_no='".$_GET["ar_no"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $_GET['filename'] = 'A. R. Report'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
                
                $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result2 = $conn->query($sql2);
                $row2 = $result2->fetch_assoc();
                $html.= '
                <h2 style="text-align:center">A. R. Report</h2>
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <thead>
                        <tr>
                            <td style="background-color:#DDDAD9; width:100%; text-align:center;"><b>A. R. Report</b></td>
                        </tr>
                        <tr>
                            <td rowspan="2" style="width:30%;"></td>
                            <td rowspan="2" style="width:30%;"></td>
                            <td style="width:40%;">Copy No :</td>
                        </tr>
                        <tr>
                            <td>Issued By :</td>
                        </tr>
                        <tr>
                            <td>Product Name:</td>
                            <td>A.R. NO.: '.$row['ar_no'].'</td>
                            <td>Batch No. :</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="width:20%;"><b>A. R. No.</b></td>
                            <td style="width:30%">'.$row['ar_no'].'</td>
                            <td style="width:20%"><b>Material Code</b></td>
                            <td style="width:30%">'.$row['material_code'].'</td>
                        </tr>
                        <tr>
                            <td rowspan="2"><b>Name of Material / Product</b></td>
                            <td rowspan="2">';
                            $sql4 = "SELECT * FROM material WHERE material_code='".$row['material_code']."'";
                            $result4 = $conn->query($sql4);
                            if($result4->num_rows > 0){
                                while ($row4 = $result4->fetch_assoc()) {
                                    $html.=''.$row4['material_name'].'';
                                }
                            }
                            $html.='</td>
                            <td><b>Receiving no</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Version No.</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Batch No /Lot No</b></td>
                            <td></td>
                            <td><b>Supersedes</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td rowspan="2"><b>Batch Size</b></td>
                            <td rowspan="2"></td>
                            <td><b>Mfg Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Exp. Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td rowspan="2"><b>Sample Quntity</b></td>
                            <td rowspan="2"></td>
                            <td><b>Specification Reference No.</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>SAP Reference No.</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Sample By /Date</b></td>
                            <td></td>
                            <td><b>Analysis Completion Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Reference</b></td>
                            <td></td>
                            <td><b>Effective Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; text-align:center;"><br><br><b>TESTS</b><br></td>
                        </tr>
                        <tr style="font-weight:bold">
                            <td style="width:7%;">Sr No.</td>
                            <td style="width:20%;">Test</td>
                            <td style="width:20%;">Subtest</td>
                            <td style="width:27%;">Specification</td>
                            <td style="width:26%;">Result</td>
                        </tr>';
                        $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                        $result1 = $conn->query($sql1);
                        if($result1->num_rows > 0){
                            $counter = 1;
                            while ($row1 = $result1->fetch_assoc()) {
                            $html.='<tr>
                                    <td>'.$counter++.'</td>
                                    <td><b>'.$row1['test'].'</b></td>
                                    <td>'.$row1['subtest'].'</td>
                                    <td>'.$row1['description'].'</td>
                                    <td>'.$row1['result'].'</td>
                                </tr>';
                            }
                        }
                        $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                        $result1 = $conn->query($sql1);
                        if($result1->num_rows > 0){
                            $counter = 1;
                            while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td style="width:3%; border:none;">'.$counter++.'</td>
                                <td style="width:97%; border:none;"><b>'.$row1['test'].'</b> : </td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="border:none;"><b>Observation</b> - '.$row1['observation'].'</td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="border:none;">Acceptance criteria:';
                                $sql3 = "SELECT * spec_tests WHERE 	specification_no='".$row['specification_no']."' ";
                                $result3 = $conn->query($sql3);
                                if($result3->num_rows > 0){
                                    while ($row3 = $result3->fetch_assoc()) {
                                        
                                    }
                                }
                                $html.='</td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="text-align:center;"><b>The Test complies/ Does not Comply</b></td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="width:48%;"><b>Analysed By / Date</b></td>
                                <td style="width:49%;"><b>Checked By / Date</b></td>
                            </tr>';
                        }
                    }
                    $html.='
            </table>
            <div></div>';
            }
            // EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('coa.pdf', 'I');
        }else{
            echo "Invalid Testing No.";
        }
    }
    else if ($_GET["type"] == "downloadTestingLog") {
        $_GET['filename'] = 'TestingLog'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">TestingLog</h2>
        <table border="1" cellpadding="5">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:14%;">Date</td>
                <td style="width:14%;">Sampling No</td>
                <td style="width:16%;">Specification No</td>
                <td style="width:14%;">Testing No</td>
                <td style="width:16%;">Material Name</td>
                <td style="width:14%;">Material Code</td>
                <td style="width:12%;">Material Grade</td>
            </tr>';
       $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t LEFT JOIN material m ON t.material_code=m.material_code WHERE t.status='Approved' ";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:14%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                <td style="width:14%;">'.$row['sampling_no'].'</td>
                <td style="width:16%;">'.$row['specification_no'].'</td>
                <td style="width:14%;">'.$row['testing_no'].'</td>
                <td style="width:16%;">'.$row['material_name'].'</td>
                <td style="width:14%;">'.$row['material_code'].'</td>
                <td style="width:12%;">'.$row['grade'].'</td>
            </tr>';
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('TestingLog.pdf', 'I');
    }
    else if ($_GET["type"] == "download") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        $output = array();
        $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t LEFT JOIN material m ON t.material_code=m.material_code WHERE t.status='approve' AND m.material_type='Raw Material' AND t.testing_no='".$_GET["testing_no"]."'";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<h3 style="text-align:center;">CERTIFICATE OF ANALYSIS</h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%;">
                            <table style="border:none;">
                                <tr>
                                    <td style="width:15%;  border:none;"><b>Material Type</b></td>
                                    <td style="width:45%; border:none;" >:'.$row['material_subtype'].' </td>
                                    <td style="width:40%; border:none;"><b>AR.No</b>:'.$row['ar_no'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;  border:none;"><b>Material Name</b></td>
                                    <td style="width:45%; border:none;" >:'.$row['material_name'].' </td>
                                    <td style="width:40%;  border:none;"><b>Release Date</b>:'.$row['entry_date'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;  border:none;  border:none;"><b>Code No</b></td>
                                    <td style="width:45%;  border:none;">:'.$row['material_code'].'</td>
                                    <td style="width:40%;  border:none;"><b>G.R.NO</b>:'.$row['grn_no'].'</td>
                                </tr>';
                            $sql1 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.challan_no=(SELECT challan_no FROM challan_materials WHERE grn_no='".$row["grn_no"]."')";
                            $result1 = $conn->query($sql1);
                            if ($result1->num_rows > 0) {
                                while ($row1 = $result1->fetch_assoc()) {
                                    $html.='
                                    <tr>
                                        <td style="width:15%;  border:none;"><b>Suppliers</b></td>
                                        <td style="width:45%; border:none;">: '.$row1["vendor_name"].'</td>
                                        <td style="width:40%;  border:none;"><b>Challan No</b>:'.$row1["challan_no"].'</td>
                                    </tr>
                                    <tr>
                                        <td style="width:15%;  border:none;"><b>Spec No</b></td>
                                         <td style="width:45%; border:none;">:'.$row['specification_no'].'</td>
                                        <td style="width:40%;  border:none;"><b>Challan Date</b>: '.$row1["challan_date"].'</td>
                                    </tr>';
                                }
                            }
                            $html.='          
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table border="1" cellpadding="5">
                                <tr>
                                    <td style="width:40%;"><b>Test</b></td>
                                    <td style="width:30%;"><b>Specification</b></td>
                                    <td style="width:30%;"><b>Result</b></td>
                                </tr>';
                                $output1 = array();
                                $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                                $result1 = $conn->query($sql1);
                                $j=1;
                                if ($result1->num_rows > 0) {
                                    while ($row1 = $result1->fetch_assoc()) {
                            $html.='<tr>
                                        <td style="width:40%;">'.$row1['test'].'</td>
                                        <td style="width:30%;">'.$row1['limits'].'</td>
                                        <td style="width:30%;">'.$row1['result'].'</td>
                                    </tr>';
                                    }
                                }
                            $html.='</table>
                        </td>
                    </tr>';
                    $html.='<tr>
                        <td style="width:100%;"><b>Conclusion: THe above Sample Complies as per IP.</b> In  the opinion of the undersigned,the sample referred
                        to above is of Standard quality as define in act and the rules mode there under for the result given here-above "This computer generated Certificate of analysis
                        is valid without signature"</td><br>
                        <b>Note:</b><br>
                        <div></div>
                    </tr>
                    <tr>
                        <td style="width:50%;"><div></div><b>Prepared By</b>'.$row['entry_by'].'</td>
                        <td style="width:50%;"><div></div><b>Approved By</b>'.$row['approve_by'].'</td>
                    </tr>';
                 $html.="
                 </table>";
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Testing Report.pdf', 'I');
    }
    else if($_GET["type"]=='updateTestingStatus') {
    //   echo  $sql = "UPDATE testing SET  correction='pending' WHERE testing_no='".$_GET["testingID"]."'";
    //       if ($conn->query($sql2)) { echo "{\"status\":\"success\"}";
    //           } 
    //         // }
    //         else {
    //             echo "{\"status\":\"".$conn->error."\"}";
    //         }
        
        
	    $sql = "UPDATE testing SET  correction='pending' WHERE testing_no='".$_GET["testingID"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	
   
    }
    else if($_GET["type"]=='updateTestingStatus_primary_oos') {
    //   echo  $sql = "UPDATE testing SET  correction='pending' WHERE testing_no='".$_GET["testingID"]."'";
    //       if ($conn->query($sql2)) { echo "{\"status\":\"success\"}";
    //           } 
    //         // }
    //         else {
    //             echo "{\"status\":\"".$conn->error."\"}";
    //         }
        
        
	    $sql = "UPDATE testing SET  correction='primary' WHERE testing_no='".$_GET["testingID"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	
   
    }
    else if($_GET["type"]=='updateTestingStatus_secondary_oos') {
    //   echo  $sql = "UPDATE testing SET  correction='pending' WHERE testing_no='".$_GET["testingID"]."'";
    //       if ($conn->query($sql2)) { echo "{\"status\":\"success\"}";
    //           } 
    //         // }
    //         else {
    //             echo "{\"status\":\"".$conn->error."\"}";
    //         }
        
        
	    $sql = "UPDATE testing SET  correction='secondary' WHERE testing_no='".$_GET["testingID"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	
   
    }
    else if($_GET["type"]=='updateTestingStatus_approve_oos') {
    //   echo  $sql = "UPDATE testing SET  correction='pending' WHERE testing_no='".$_GET["testingID"]."'";
    //       if ($conn->query($sql2)) { echo "{\"status\":\"success\"}";
    //           } 
    //         // }
    //         else {
    //             echo "{\"status\":\"".$conn->error."\"}";
    //         }
        
        
	    $sql = "UPDATE testing SET  correction='YES' WHERE testing_no='".$_GET["testingID"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	
   
    }
   else if($_GET['type'] == 'downloadTestingRDSReport'){
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        
        
        $sql = "SELECT * FROM testing WHERE testing_no='".$_GET['testing_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<h3 style="text-align:center;">Raw Data Sheet</h3>
                        <table  style="text-align:left;">
                        <tr>
                            <td style="width:23%">Department</td>
                             <td style="width:31%">Quality Control Department</td>
                            <td style="width:29%">Material Code:</td>
                             <td style="width:17%">'.$row["material_code"].'</td>
                        </tr>';
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $html.='<tr>
                            <td style="width:23%" rowspan="2">Name of Material / Product</td>
                             <td style="width:31%;vertical-align: middle;" rowspan="2">'.$row1["material_name"].'</td>
                            <td style="width:29%">SAP No.</td>
                             <td style="width:17%">Jul,2018</td>
                        </tr>';
                    }
                }
                $html.='
                <tr>
                    <td style="width:29%">Version No.</td>
                     <td style="width:17%">02</td>
                </tr>
                <tr>
                    <td style="width:23%" rowspan="2">Batch Size</td>
                     <td style="width:31%;vertical-align: middle;" rowspan="2">ATP003</td>
                    <td style="width:29%">Mfg Date</td>
                     <td style="width:17%">Jul,2018</td>
                </tr>
                <tr>
                    <td style="width:29%">Exp. Date</td>
                     <td style="width:17%">Jul,2018</td>
                </tr>
                <tr>
                    <td style="width:23%" rowspan="2">Sample Quantity</td>
                     <td style="width:31%;vertical-align: middle;" rowspan="2">ATP003</td>
                    <td style="width:29%">Specification Refrence No.</td>
                     <td style="width:17%">'.$row['specification_no'].'</td>
                </tr>
                <tr>
                    <td style="width:29%">SAP Reference No.</td>
                     <td style="width:17%">23</td>
                </tr>
                <tr>
                    <td style="width:23%">Sample By / Date</td>
                     <td style="width:31%">'.$row['entry_by'].' '.$row['entry_date'].'</td>
                    <td style="width:29%">Analysis Completion Date</td>
                     <td style="width:17%">20/08/2016</td>
                </tr>
                <tr>
                    <td style="width:23%">Reference</td>
                     <td style="width:31%">test</td>
                    <td style="width:29%">Effective Date</td>
                     <td style="width:17%">20/08/2016</td>
                </tr>
            </table>
            <h2 style="text-align: center;">ANALYTICAL REPORT SUMMARY</h2>
            
                        <table border="1" cellpadding="2">
                            <tr>
                                <td style="width:40%;text-align:center; height:20px;"><b>Test</b></td>
                                <td style="width:30%; text-align:center; height:20px;"><b>Specification</b></td>
                                <td style="width:30%; text-align:center; height:20px;"><b>Result</b></td>
                            </tr>';
                            $output1 = array();
                             $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                            $result1 = $conn->query($sql1);
                            $j=1;
                            if ($result1->num_rows > 0) {
                                while ($row1 = $result1->fetch_assoc()) {
                        $html.='<tr>
                                    <td style="width:40%; height:20px;"> '.$row1['test'].'</td>
                                    <td style="width:30%; height:20px;"> '.$row1['subtest'].'</td>
                                    <td style="width:30%; height:20px;"> '.$row1['result'].'</td>
                                </tr>';
                                }
                            }
                       
                 
            
            $html.='</table>';
            }
        }
        
        $sql = "SELECT * FROM testing_tests WHERE testing_no='T-01' GROUP BY test";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i = 1;
            $alphabet = range('A', 'Z');
            while ($row = $result->fetch_assoc()) {
                $html .= '<h3>'.$i.'. '.$row["test"].'</h3>';
                $sql1 = "SELECT * FROM testing_tests WHERE testing_no='T-01' WHERE test='".$row["test"]."' AND subtest !=''";
                // $sql1 = "SELECT * FROM testing_tests WHERE testing_no='T-01' AND test='".$row["test"]."' AND subtest !=''";

                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $html.='<span><b>'.$alphabet[$i].'. Observation:</b> '.$row["result"].'</span><br>';
                        $html.='<span><b>Acceptance criteria:</b><br>'.$row["description"].'</span><br>';
                        if ($row["status"] == "approve") {
                            $html.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test complies</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                        } else {
                            $html.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test Not Comply</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                        }
                    }
                } else {
                    $html.='<span><b>Observation:</b> '.$row["result"].'</span><br>';
                    $html.='<span><b>Acceptance criteria:</b><br>'.$row["description"].'</span><br>';
                    if ($row["status"] == "approve") {
                        $html.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test complies</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                    } else {
                        $html.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test Not Comply</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                    }
                }
                $i++;
            }
        }
        

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Testing RDS Report.pdf', 'I');
    }
    else if ($_GET["type"] == "ARReport") {
        $_GET['filename'] = 'A. R. Report'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
          $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t LEFT JOIN material m ON t.material_code=m.material_code WHERE t.status='Approved' AND t.id='".$_GET['id']."' ";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
                
       $html.='
        <h2 style="text-align:center">A. R. Report</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:25%;"><b>A. R. No.:</b></td>
                <td style="width:25%;">'.$row['ar_no'].'</td>
                <td style="width:25%;"><b>Specification No.:</b></td>
                <td style="width:25%;">'.$row['specification_no'].'</td>
            </tr>
            <tr>
                <td style="width:25%;"><b>Material Code:</b></td>
                <td style="width:25%;">'.$row['material_code'].'</td>
                <td style="width:25%;"><b>Material Name:</b></td>
                <td style="width:25%;">'.$row['material_name'].'</td>
            </tr>
            <tr>
                <td style="width:25%;"><b>Grade:</b></td>
                <td style="width:25%;">'.$row['gradeName'].'</td>
                <td style="width:25%;"><b>Chemical Name:</b></td>
                <td style="width:25%;">'.$row['chemical_name'].'</td>
            </tr></table>';
          
        $html.='<div></div>
        <h3>Tests:</h3>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:17%; text-align:centre;"><b>Test</b></td>
                <td style="width:15%; text-align:centre;"><b>Subtest</b></td>
                <td style="width:20%; text-align:centre;"><b>Description</b></td>
                <td style="width:18%; text-align:centre;"><b>Limits</b></td>
                <td style="width:15%; text-align:centre;"><b>Result</b></td>
                <td style="width:15%; text-align:centre;"><b>Reference Type</b></td>
             </tr>';
            
            
                 $sql1="SELECT t.* , (select reference_type from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as reference_type ,
                              (select description from spec_tests st where st.test=t.test and st.specification_no =t.specification_no limit 1) as description ,
                              (select limits from spec_tests st where st.test=t.test and st.specification_no =t.specification_no limit 1) as limits  FROM testing_tests  t WHERE t.testing_no='".$row["testing_no"]."'     order by id desc";
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      //  $output1[] = $row1;
                        
                             $html.='<tr>
                                         <td style="width:17%;">'.$row1['test'].'</td>
                                        <td style="width:15%;">'.$row1['subtest'].'</td>
                                        <td style="width:20%;">'.$row1['description'].'</td>
                                        <td style="width:18%;">'.$row1['limits'].'</td>
                                        <td style="width:15%;">'.$row1['status'].'</td>
                                        <td style="width:15%;">'.$row1['reference_type'].'</td>
                                    </tr>';
                    }
                }
            
            
            // $sql1 = "SELECT * FROM testing_tests WHERE id='".$_GET["id"]."'";
              
            //     $result1 = $conn->query($sql1);
            //     if ($result1->num_rows > 0) {
            //         while ($row1 = $result1->fetch_assoc()) {
            //             $output1[] = $row1;
                    
                
               
                
       
                
            //         }
            //     }
        $html.='</table>';
        
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Testing RDS Report.pdf', 'I');
                
                    
                
       }
    else if ($_GET["type"] == "downloadTestingReportDigital") {
        qc_testing_pdf_quiet();
        $_GET['filename'] = 'A. R. Report'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
         $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade,p.plant_name,p.plant_full_address FROM testing t LEFT JOIN material m ON t.material_code=m.material_code left join plant p on t.plant_id=p.plant_id WHERE t.status='Approved' AND t.id='".$_GET['id']."' ";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
       $html.='
        <h2 style="text-align:center">A. R. Report</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:25%;"><b>A. R. No.:</b></td>
                <td style="width:25%;">'.$row['ar_no'].'</td>
                <td style="width:25%;"><b>Specification No.:</b></td>
                <td style="width:25%;">'.$row['specification_no'].'</td>
            </tr>
            <tr>
                <td style="width:25%;"><b>Material Code:</b></td>
                <td style="width:25%;">'.$row['material_code'].'</td>
                <td style="width:25%;"><b>Material Name:</b></td>
                <td style="width:25%;">'.$row['material_name'].'</td>
            </tr>
            <tr>
                <td style="width:25%;"><b>Grade:</b></td>
                <td style="width:25%;">'.$row['grade'].'</td>
                <td style="width:25%;"><b>Chemical Name:</b></td>
                <td style="width:25%;">'.$row['chemical_name'].'</td>
            </tr></table>';
           
        $html.='<div></div>
        <h3>Tests:</h3>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:17%; text-align:centre;"><b>Test</b></td>
                <td style="width:15%; text-align:centre;"><b>Subtest</b></td>
                <td style="width:20%; text-align:centre;"><b>Description</b></td>
                <td style="width:18%; text-align:centre;"><b>Limits</b></td>
                <td style="width:15%; text-align:centre;"><b>Result</b></td>
                <td style="width:15%; text-align:centre;"><b>Reference Type</b></td>
             </tr>';
           
           
           
                 $sql1="SELECT t.* , (select reference_type from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as reference_type ,
                              (select description from spec_tests st where st.test=t.test and st.specification_no =t.specification_no limit 1) as description ,
                              (select limits from spec_tests st where st.test=t.test and st.specification_no =t.specification_no limit 1) as limits  FROM testing_tests  t WHERE t.testing_no='".$row["testing_no"]."'     order by id desc";
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      //  $output1[] = $row1;
                        
                             $html.='<tr>
                                         <td style="width:17%;">'.$row1['test'].'</td>
                                        <td style="width:15%;">'.$row1['subtest'].'</td>
                                        <td style="width:20%;">'.$row1['description'].'</td>
                                        <td style="width:18%;">'.$row1['limits'].'</td>
                                        <td style="width:15%;">'.$row1['status'].'</td>
                                        <td style="width:15%;">'.$row1['reference_type'].'</td>
                                    </tr>';
                    }
                }
           
           
           
                
         $html.='</table>';
    }
    }
         
           $html.='  <div></div>
        
         
         <table border="1" cellpadding="3">
      <tr style="background-color:black; color:white;">
      <td style="width: 250px;text-align:center;"><b>Checked By & Digital Signed By</b></td>
      <td style="width: 40px;text-align:center;"><img src="../../upload/pdf/sign.jpg" style="width:20px;height:20px;"></td>
      <td style="width: 250px;text-align:center;"><b>Approved By & Digital Signed By</b></td>
  </tr>
  <tr>
      <td style="width: 270px;" >
          <table>
              <tr>
                  <td style="width:70px;">Name</td>
                  <td style="width:191px;">:' . $row['check_by'] . '</td>
                 
              </tr>
              <tr>
              <td style="width:70px;">ID</td>
              <td style="width:70px;">:' . $row['firstname'] . '</td>
              <td style="width: 50px;">Department</td>
              <td style="width: 81px;">:Quality Control</td>
          </tr>
          <tr>
              <td style="width:70px;">Designation</td>
              <td style="width:191px;">:</td>
          </tr>
            
              <tr>
                  <td style="width:70px;">Date</td>
                  <td style="width:70px;">:' . date('d-m-Y', strtotime($row['check_date'])) . '</td>
                  <td style="width: 50px;">Time</td>
                  <td style="width: 81px;">:' . date('H:i:s', strtotime($row['check_date'])) . '</td>
              </tr>
              <br>
          <tr>
          <td style="width: 270px;font-size:15px;">For, '.$row['plant_name'].' </td>
          </tr>
          </table>
          
      </td>
      <td style="width: 270px;">
          <table >
              <tr>
                  <td style="width:70px;">Name</td>
                  <td style="width:191px;">:' . $row['approve_by'] . '</td>
                 
              </tr>
              <tr>
                  <td style="width: 70px;">ID</td>
                  <td style="width:60px;">:' . $row['firstname1'] . '</td>
                  <td style="width: 50px;">Department</td>
                  <td style="width: 81px;">:Quality Control</td>
              </tr>
              <tr>
                  <td style="width:70px;">Designation</td>
                  <td style="width:191px;">:</td>
              </tr>
              
              <tr>
              <td style="width:70px;">Date</td>
              <td style="width:70px;">:' . date('d-m-Y', strtotime($row['approve_date'])) . '</td>
              <td style="width: 40px;">Time</td>
              <td style="width: 81px;">:' . date('H:i:s', strtotime($row['approve_date'])) . '</td>
          </tr>
          <br>
          <tr>
          <td style="width: 265px;font-size:15px;">For, '.$row['plant_name'].' </td>
          </tr>
          </table>
      </td>
  </tr>
  <tr>
              <td style="width: 540px;text-align:center">' . $row['plant_full_address'] . '</td>
              </tr>
</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        qc_testing_pdf_flush();
        $pdf->Output('Testing RDS Report.pdf', 'I');
                
                    
                
       }
       
       else if($_GET['type'] == 'downloadTestingCOAReport') {
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE);
        qc_testing_pdf_flush();
        if (function_exists('mysqli_report')) {
            @mysqli_report(MYSQLI_REPORT_OFF);
        }

        $testingNo = isset($_GET['testing_no']) ? trim((string)$_GET['testing_no']) : '';
        $testingNoEsc = $conn->real_escape_string($testingNo);
        if ($testingNoEsc === '') {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Testing number is required.';
            exit;
        }
        if (!isset($_GET['plant_id']) || trim((string)$_GET['plant_id']) === '') {
            $_GET['plant_id'] = '1126';
        }
        $plantIdEsc = $conn->real_escape_string((string)$_GET['plant_id']);

        $plant_full_name = 'Medicap';
        $plant_full_address = '';
        $logo = '';
        $plantRes = @$conn->query("SELECT plant_full_name, plant_full_address, logo_path FROM plant WHERE plant_id='".$plantIdEsc."' LIMIT 1");
        if ($plantRes && $plantRes->num_rows > 0) {
            $plantRow = $plantRes->fetch_assoc();
            $plant_full_name = isset($plantRow['plant_full_name']) ? $plantRow['plant_full_name'] : $plant_full_name;
            $plant_full_address = isset($plantRow['plant_full_address']) ? $plantRow['plant_full_address'] : '';
            $logo = isset($plantRow['logo_path']) ? $plantRow['logo_path'] : '';
        }

        // Do not select sampling.supplier_batch_no — column does not exist on Medicap.
        $sql = "SELECT t.*,
                (SELECT m.material_name FROM material m WHERE m.material_code = t.material_code LIMIT 1) AS material_name,
                (SELECT s.batch_no FROM sampling s WHERE s.sampling_no = t.sampling_no LIMIT 1) AS sample_batch_no,
                (SELECT s.mfg_date FROM sampling s WHERE s.sampling_no = t.sampling_no LIMIT 1) AS mfg_date,
                (SELECT s.exp_date FROM sampling s WHERE s.sampling_no = t.sampling_no LIMIT 1) AS exp_date,
                (SELECT s.sample_qty FROM sampling s WHERE s.sampling_no = t.sampling_no LIMIT 1) AS sample_qty,
                (SELECT s.entry_date FROM sampling s WHERE s.sampling_no = t.sampling_no LIMIT 1) AS sample_entry_date
                FROM testing t
                WHERE t.testing_no='".$testingNoEsc."'
                LIMIT 1";
        $result = @$conn->query($sql);
        if (!$result) {
            // Minimal fallback if subqueries fail
            $result = @$conn->query("SELECT t.* FROM testing t WHERE t.testing_no='".$testingNoEsc."' LIMIT 1");
        }
        $row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : array();
        if (empty($row)) {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Testing record not found for '.$testingNo;
            exit;
        }
        if (empty($row['material_name']) && !empty($row['material_code'])) {
            $mRes = @$conn->query("SELECT material_name FROM material WHERE material_code='".$conn->real_escape_string($row['material_code'])."' LIMIT 1");
            if ($mRes && ($mRow = $mRes->fetch_assoc())) {
                $row['material_name'] = $mRow['material_name'];
            }
        }

        $v = function ($key) use ($row) {
            return isset($row[$key]) && $row[$key] !== null && trim((string)$row[$key]) !== ''
                ? htmlspecialchars((string)$row[$key], ENT_QUOTES, 'UTF-8')
                : '-';
        };
        $batchNo = '-';
        if (!empty($row['sample_batch_no'])) {
            $batchNo = htmlspecialchars((string)$row['sample_batch_no'], ENT_QUOTES, 'UTF-8');
        } elseif (!empty($row['batch_no'])) {
            $batchNo = htmlspecialchars((string)$row['batch_no'], ENT_QUOTES, 'UTF-8');
        }

        $logoHtml = qc_testing_pdf_logo_html($logo);
        $pdf = new TCPDF('P', 'mm', 'A4');
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Medicap Laboratories');
        $pdf->SetTitle('Certificate of Analysis');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();

        $html = qc_testing_pdf_header($logoHtml, $plant_full_name, $plant_full_address);
        $html .= '
            <table cellpadding="2">
                <tr>
                    <td style="background-color:#DDDAD9; width:540px; text-align:center;"><b>CERTIFICATE OF ANALYSIS</b></td>
                </tr>
            </table>
            <br/>
            <table cellpadding="3" border="0.1">
                <tr>
                    <td style="width:120px;">Name of Material</td>
                    <td style="width:150px;">'.$v('material_name').'</td>
                    <td style="width:120px;">Medicap lot No.</td>
                    <td style="width:150px;">'.$batchNo.'</td>
                </tr>
                <tr>
                    <td style="width:120px;">Date of Received</td>
                    <td style="width:150px;">'.qc_testing_pdf_date(isset($row['sample_entry_date']) ? $row['sample_entry_date'] : '').'</td>
                    <td style="width:120px;">Date of Testing</td>
                    <td style="width:150px;">'.qc_testing_pdf_date(isset($row['alalysis_start_date']) ? $row['alalysis_start_date'] : (isset($row['entry_date']) ? $row['entry_date'] : '')).'</td>
                </tr>
                <tr>
                    <td style="width:120px;">MFG. Date</td>
                    <td style="width:150px;">'.qc_testing_pdf_date(isset($row['mfg_date']) ? $row['mfg_date'] : '').'</td>
                    <td style="width:120px;">Date of Release</td>
                    <td style="width:150px;">'.qc_testing_pdf_date(isset($row['approve_date']) ? $row['approve_date'] : '').'</td>
                </tr>
                <tr>
                    <td style="width:120px;">Exp. Date</td>
                    <td style="width:150px;">'.qc_testing_pdf_date(isset($row['exp_date']) ? $row['exp_date'] : '').'</td>
                    <td style="width:120px;">Qty of Sample</td>
                    <td style="width:150px;">'.$v('sample_qty').'</td>
                </tr>
                <tr>
                    <td style="width:120px;">Sampling No.</td>
                    <td style="width:150px;">'.$v('sampling_no').'</td>
                    <td style="width:120px;">Material Code</td>
                    <td style="width:150px;">'.$v('material_code').'</td>
                </tr>
                <tr>
                    <td style="width:120px;">Testing No.</td>
                    <td style="width:150px;">'.$v('testing_no').'</td>
                    <td style="width:120px;">&nbsp;</td>
                    <td style="width:150px;">&nbsp;</td>
                </tr>
            </table>
            <br/>
            <table cellpadding="3" border="0.1">
                <tr style="background-color:#DDDAD9;">
                    <td style="width:30px;"><b>Sr</b></td>
                    <td style="width:130px;"><b>Test</b></td>
                    <td style="width:130px;"><b>Description</b></td>
                    <td style="width:125px;"><b>Specification</b></td>
                    <td style="width:125px;"><b>Results</b></td>
                </tr>';

        $testRows = array();
        $specNo = isset($row['specification_no']) ? (string)$row['specification_no'] : '';
        try {
            $testRows = qc_testing_tests_with_spec($conn, $testingNo, $specNo);
        } catch (Throwable $e) {
            $testRows = array();
        }
        if (count($testRows) === 0) {
            $fb = @$conn->query("SELECT test, subtest, limits, result, isoutside, remark FROM testing_tests WHERE testing_no='".$testingNoEsc."' ORDER BY id ASC");
            if ($fb && $fb->num_rows > 0) {
                while ($tr = $fb->fetch_assoc()) {
                    $tr['description'] = '';
                    $testRows[] = $tr;
                }
            }
        }

        $counter = 1;
        if (count($testRows) > 0) {
            foreach ($testRows as $row1) {
                $testName = htmlspecialchars((string)($row1['test'] ?? ''), ENT_QUOTES, 'UTF-8');
                $sub = trim((string)($row1['subtest'] ?? ''));
                if ($sub !== '' && strtoupper($sub) !== 'NA') {
                    $testName .= ' / '.htmlspecialchars($sub, ENT_QUOTES, 'UTF-8');
                }
                $description = htmlspecialchars((string)($row1['description'] ?? $row1['spec_description'] ?? ''), ENT_QUOTES, 'UTF-8');
                $limits = htmlspecialchars((string)($row1['limits'] ?? $row1['spec_limits'] ?? ''), ENT_QUOTES, 'UTF-8');
                $isOutside = (string)($row1['isoutside'] ?? 'No');
                $rawResult = isset($row1['result']) ? (string)$row1['result'] : '';
                $resultCell = ($rawResult !== '') ? htmlspecialchars($rawResult, ENT_QUOTES, 'UTF-8') : '-';
                if (strcasecmp($isOutside, 'Yes') === 0 && $rawResult !== '') {
                    $resultCell = 'See attached report';
                }
                $html .= '<tr>
                    <td style="width:30px;">'.$counter++.'</td>
                    <td style="width:130px;">'.($testName !== '' ? $testName : '-').'</td>
                    <td style="width:130px;">'.($description !== '' ? $description : '-').'</td>
                    <td style="width:125px;">'.($limits !== '' ? $limits : '-').'</td>
                    <td style="width:125px;">'.$resultCell.'</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="5" style="text-align:center;">No test lines found.</td></tr>';
        }

        $html .= '</table>
            <br/>
            <table cellpadding="3">
                <tr>
                    <td style="width:60px;"><b>Remark:</b></td>
                    <td style="width:480px;">The sample referred complies with prescribed standard quality with respect to above test.</td>
                </tr>
            </table>
            <br/>
            <table cellpadding="3" border="0.1">
                <tr style="background-color:#DDDAD9;">
                    <td style="width:180px; text-align:center;"><b>Entry By</b></td>
                    <td style="width:180px; text-align:center;"><b>Checked By</b></td>
                    <td style="width:180px; text-align:center;"><b>Approved By</b></td>
                </tr>
                <tr>
                    <td style="width:180px; text-align:center;">'.$v('entry_by').'<br/>'.qc_testing_pdf_date($row['entry_date'] ?? '').'</td>
                    <td style="width:180px; text-align:center;">'.$v('check_by').'<br/>'.qc_testing_pdf_date($row['check_date'] ?? '').'</td>
                    <td style="width:180px; text-align:center;">'.$v('approve_by').'<br/>'.qc_testing_pdf_date($row['approve_date'] ?? '').'</td>
                </tr>
            </table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        qc_testing_pdf_flush();
        $pdf->Output('Testing_COA_Report.pdf', 'I');
        exit;
    }
       
       
       
    //   else if($_GET['type'] == 'downloadTestingCOAReport') {
    //   $_GET['filename'] =''; $_GET['pdftype']='onlyheader'; include("../../pdfimp2.php");
    //   //$sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t LEFT JOIN material m ON 
    //   //t.material_code=m.material_code  WHERE t.testing_no='".$_GET["testing_no"]."'"; 
    //   //$sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t 
    //  // LEFT JOIN material m  ON t.material_code=m.material_code WHERE t.status='approve' AND m.material_type='Raw Material'";
         
    //         //$result = $conn->query($sql);
    //         //if ($result->num_rows > 0) {
    //           // while ($row = $result->fetch_assoc()) {
    //                 // $sql2 = "SELECT * FROM sampling WHERE specification_no='".$row["specification_no"]."'";
    //           // $result2 = $conn->query($sql2);
    //           // if ($result2->num_rows > 0) {
    //               // while ($row2 = $result2->fetch_assoc()) {
                   
                   
    //               $sql="  select t. *,m.material_name,t.material_code, m.material_code as mCode, v.material_code, st.specification_no, st.test, st.limits, 
    //              v.vendor_name,c.qty,s.entry_date,s.sample_qty,s.batch_no,s.mfg_date,s.exp_date,tt.result from testing t LEFT JOIN material m on t.material_code = m.material_code
    //              LEFT JOIN vendor v on m.material_type = v.material_type left join spec_tests st on t.specification_no= st.specification_no left join testing_tests tt on t.testing_no= tt.testing_no
    //              LEFT JOIN challan_materials c ON m.material_code=c.material_code LEFT JOIN sampling s on t.grn_no=s.grn_no WHERE t.testing_no='".$_GET["testing_no"]."';  ";
    //               $result = $conn->query($sql);
    // $row = $result->fetch_assoc();{
    //               $html.= '
    //                      <style>td { border:solid 1px BCBBBA;}</style>
    //                     <table cellpadding="2">
    //                         <tr>
    //                             <td style="background-color:#DDDAD9; width:540px; text-align:center;">CERTIFICATE OF ANALYSIS</td>
    //                         </tr>
    //                     </table>
    //                     <div></div>
    //                     <table cellpadding="2" border="0.1">
    //                         <tr>
    //                             <td  style="width:108px">Material </td>
    //                               <td style="width:162px;">'.$row['material_name'].'</td>
    //                             <td style="width:108px">Medicap lot no</td>
    //                             <td style="width:162px;">'.$row['ar_no'].'</td>
    //                         </tr>
    //                         <tr>
    //                             <td style="width:108px">Completion Date</td>
    //                             <td style="width:162px">'.$row['alalysis_end_date'].'</td>
    //                             <td style="width:108px">Analysis Date</td>
    //                             <td style="width:162px">'.$row['alalysis_start_date'].'</td>
    //                           </tr>
    //                         <tr>
    //                             <td style="width:108px">Mfg. Date</td>
    //                              <td style="width:162px">'.$row['mfg_date'].'</td>
    //                              <td style="width:108px">Exp. Date</td>
    //                              <td style="width:162px">'.$row['exp_date'].'</td>
    //                         </tr>
    //                         <tr>
    //                             <td style="width:108px">GRN No</td>
    //                              <td style="width:162px">'.$row['grn_no'].'</td>
    //                             <td style="width:108px">Batch No</td>
    //                              <td style="width:162px">'.$row['batch_no'].'</td>
    //                         </tr>
    //                         <tr>
    //                          <td style="width:108px">Sampling Date</td>
    //                           <td style="width:162px">'.$row['entry_date'].'</td>
    //                           <td style="width:108px">Material Code</td>
    //                           <td style="width:162px">'.$row['mCode'].'</td>
    //                         </tr>
    //                         <tr>
    //                             <td style="width:108px">Sampled By</td>
    //                           <td style="width:162px">'.$row['entry_by'].'</td>
    //                           <td style="width:108px">Spec. No. :</td>
    //                         <td style="width:162px">'.$row['specification_no'].'</td>
    //                         </tr>
    //                         <tr>
    //                             <td style="width:108px">Vendor Name</td>
    //                           <td style="width:162px">'.$row['vendor_name'].'</td>
    //                           <td style="width:108px">Mfg.Name</td>
    //                           <td style="width:162px">'.$row['vendor_name'].'</td>
    //                         </tr>
    //                         ';
                    
    // }
    
        
    //                     $html.=' </table>
    //                     <div></div>
    //                     <table cellpadding="2" border="0.1">
                           
                       
    //                          <tr style="background-color:#DDDAD9; width:100%; ">
    //                             <td style="width:54px;"><b>Sr No.</b></td>
    //                             <td style="width:121.5px;"><b>Test</b></td>
    //                             <td style="width:121.5px;"><b>Limits</b></td>
    //                             <td style="width:121.5px;"><b>Results</b></td>
    //                             <td style="width:121.5px;"><b>Ref Type</b></td>
    //                      </tr>';
    //                             //   $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
    //                         //         $sql1="SELECT t.* , (select reference_type from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as reference_type ,
    //                         //   (select description from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as description ,
                            
    //                     //   (select limits from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as limits FROM testing_tests  t WHERE t.testing_no='".$row["testing_no"]."'     order by id desc";
    //                      $sql1="SELECT t.* , (select reference_type from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as reference_type ,
    //                           (select description from spec_tests st where st.test=t.test and st.specification_no =t.specification_no limit 1) as description ,
    //                           (select limits from spec_tests st where st.test=t.test and st.specification_no =t.specification_no limit 1) as limits  FROM testing_tests  t WHERE t.testing_no='".$row["testing_no"]."'     order by id desc";
    //                         $result1 = $conn->query($sql1);
    //                         if($result1->num_rows > 0){
    //                             $counter = 1;
    //                             while ($row1 = $result1->fetch_assoc()) {
                      
    //                                 $html.='<tr>
    //                                     <td style="width:10%;">'.$counter++.'</td>
    //                                     <td style="width:121.5px;">'.$row1['test'].'</td>
    //                                     <td style="width:121.5px;">'.$row1['limits'].'</td>
    //                                     <td style="width:121.5px;">'.$row1['result'].'</td>
    //                                     <td style="width:121.5px;">'.$row1['reference_type'].'</td>
    //                                 </tr>';
        
                                
                
    //                             }}
                                
                                
                                
            
    //                  $html.='
    //                     </table>
    //                     <div></div>  <div></div>
    //                     <table>
    //                         <tr>
    //                             <td style="border:none; width:70.2px;">Conclusion :</td>
    //                             <td style="border:none; width:469.8px;">The conclusion of the undersigned about the above mentioned product Complies as per';
    //                             $sql1="SELECT t.* , (select reference_type from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as reference_type ,
    //                           (select description from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as description ,
    //                           (select limits from spec_tests st where st.test=t.test and st.subtest =t.subtest limit 1) as limits FROM testing_tests  t WHERE t.testing_no='".$row["testing_no"]."'     order by id desc";
    //                         $result1 = $conn->query($sql1);
    //                         if($result1->num_rows > 0){
    //                             $counter = 1;
    //                             while ($row1 = $result1->fetch_assoc()) {
                      
    //                                 $html.='  '.$row1['reference_type'].',';
        
                                
                
    //                             }}
    //                             $html.=' Specification Laid down specification and is of a standard quality mentioned there-in and released for Manufacture Packing / Distribution.</td>
    //                         </tr>
    //                     </table>
    //                     <div></div>  <div></div>  <div></div>  <div></div>  <div></div>
    //                  <table cellpadding="2" border="0.1">
    //   <tr>
    //   <td style="width:135px;text-align:center"><b></b></td>
    //     <td style="width:135px;text-align:center"><b>Prepared By</b></td>
    //      <td style="width:135px;text-align:center"><b>Checked By</b></td>
    //       <td style="width:135px;text-align:center"><b>Approved By</b></td>
    //   </tr>
    //     <tr>
    //   <td style="width:135px;text-align:center"><b>Sign/Date</b></td>
    //     <td style="width:135px;text-align:center">'.$row['entry_date'].'</td>
    //      <td style="width:135px;text-align:center">'.$row['check_date'].'</td>
    //       <td style="width:135px;text-align:center">'.$row['approve_date'].'</td>
    //   </tr>
    //   <tr>
    //   <td style="width:135px;text-align:center"><b>Name/ID</b></td>
    //     <td style="width:135px;text-align:center">'.$row['entry_by'].'</td>
    //      <td style="width:135px;text-align:center">'.$row['check_by'].'</td>
    //       <td style="width:135px;text-align:center">'.$row['approve_by'].'</td>
    //   </tr>
    //   <tr>
    //   <td style="width:135px;text-align:center"><b>Designation</b></td>
    //     <td style="width:135px;text-align:center">'.$row['designation'].'</td>
    //      <td style="width:135px;text-align:center">'.$row['designation'].'</td>
    //       <td style="width:135px;text-align:center">'.$row['designation'].'</td>
    //   </tr>
    //   </table>';
                
         
        
    //     // EOD;
    //     $pdf->writeHTML($html, true, false, false, false, '');
    //     $pdf->Output('Testing COA Report.pdf', 'I');
    // }
    
    else if($_GET['type'] == 'downloadCorrugatedBox') {
       $_GET['filename'] =''; $_GET['pdftype']='onlyheader'; include("../../pdfimp2.php");
       
       
         $sql = "SELECT   t.*, 
         (select material_name from master_material  where material_code = t.material_code limit 1)  as material_name,
          (select specification_no from specification  where specification.material_code = t.material_code limit 1)  as specification_no ,
         
         (select grn_no from challan_materials  where challan_materials.grn_no = t.grn_no limit 1)  as vendor_coa  ,
         (select received_qty from sampling  where t.grn_no = sampling.grn_no limit 1)  as quantity  
         
         FROM testing t LEFT JOIN material b on t.material_code=b.material_code
         where t.status = 'Approved' AND t.testing_no='".$_GET["testingNo"]."'  and t.plant_id='".$_GET["plant_id"]."'  ";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
       
       
                           $html.= '
           <table>
                            <tr>
                                <td style="background-color:#DDDAD9; width:540px; text-align:center;">CORRUGATED BOX</td>
                            </tr>
                            
                        </table>
                        <div></div>
            <table cellpadding="2">
                            <tr>
                                <td style="width:270px; text-align:left;">Party Name- Naturaltein</td>
                                <td style="width:270px; text-align:;left;">GRN No.- '.$row['vendor_coa'].'</td>
                            </tr>
                             <tr>
                                <td style="width:540px; text-align:;left;">Packing Material-'.$row['material_name'].'</td>
                            </tr>
                             <tr>
                                <td style="width:270px; text-align:left;">Date of Receipt- '. date('d-m-Y', strtotime($row['entry_date'])).'</td>
                                <td style="width:270px; text-align:;left;">Quantity Received- '.$row['quantity'].'</td>
                            </tr>
                             <tr>
                                <td style="width:270px; text-align:left;">Date of Analysis- '. date('d-m-Y', strtotime($row['alalysis_start_date'])).'</td>
                                <td style="width:270px; text-align:;left;">Date of Approval- '. date('d-m-Y', strtotime($row['alalysis_end_date'])).'</td>
                            </tr>
                        </table>
                        <div></div>
          <table cellpadding="2">
                            <tr>
                                <td style="background-color:#DDDAD9;width:30px; text-align:center;">SR NO.</td>
                                <td style="background-color:#DDDAD9;width:170px; text-align:center;">TEST PARAMETER</td>
                                <td style="background-color:#DDDAD9;width:170px; text-align:center;">STANDARD</td>
                                <td style="background-color:#DDDAD9;width:170px; text-align:center;">OBSERVATIONS</td>
                            </tr>';
                            $i=1;
                 $sql1 = "select * from testing_tests where testing_no= '".$row['testing_no']."' and plant_id='67'";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
           
            while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td style="width:30px; text-align:center;">'.$i++.'</td>
                                <td style="width:170px; text-align:center;"> '.$row1['test'].'</td>
                                <td style="width:170px; text-align:center;">'.$row1['description'].'</td>
                                <td style="width:170px; text-align:center;">'.$row1['result'].'</td>
                            </tr>
                            ';
            }
        }
                            $html.='
                        </table>
                        ';
              }
        }
                        
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Corrugated Box.pdf', 'I');
    }

else if($_GET['type'] == 'downloadRDSCorrugatedBox') {
       $_GET['filename'] =''; $_GET['pdftype']='onlyheader'; include("../../pdfimp2.php");
       
       
         $sql = "SELECT   t.*, 
         (select material_name from master_material  where material_code = t.material_code limit 1)  as material_name,
         (select material_subtype from master_material  where material_code = t.material_code limit 1)  as materialType,
          (select specification_no from specification  where specification.material_code = t.material_code limit 1)  as specification_no ,
         
         (select grn_no from challan_materials  where challan_materials.grn_no = t.grn_no limit 1)  as vendor_coa  ,
         (select received_qty from sampling  where t.grn_no = sampling.grn_no limit 1)  as quantity  
         
         FROM testing t LEFT JOIN material b on t.material_code=b.material_code
         where t.status = 'Approved' AND t.testing_no='".$_GET["testingNo"]."'  and t.plant_id='".$_GET["plant_id"]."'  ";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
       
       
                           $html.= '
           <table>
                            <tr>
                                <td style="background-color:#DDDAD9; width:540px; text-align:center;">CORRUGATED BOX</td>
                            </tr>
                            
                        </table>
                        <div></div>
            <table cellpadding="2">
                            <tr>
                                <td style="width:270px; text-align:left;">Party Name- Naturaltein</td>
                                <td style="width:270px; text-align:;left;">GRN No.- '.$row['vendor_coa'].'</td>
                            </tr>
                             <tr>
                                <td style="width:540px; text-align:;left;">'.$row['materialType'].'-'.$row['material_name'].'</td>
                            </tr>
                             <tr>
                                <td style="width:270px; text-align:left;">Date of Receipt- '. date('d-m-Y', strtotime($row['entry_date'])).'</td>
                                <td style="width:270px; text-align:;left;">Quantity Received- '.$row['quantity'].'</td>
                            </tr>
                             <tr>
                                <td style="width:270px; text-align:left;">Date of Analysis- '. date('d-m-Y', strtotime($row['check_date'])).'</td>
                                <td style="width:270px; text-align:;left;">Date of Approval- '. date('d-m-Y', strtotime($row['approve_date'])).'</td>
                            </tr>
                        </table>
                        <div></div>
          <table cellpadding="2">
                            <tr>
                                <td style="background-color:#DDDAD9;width:30px; text-align:center;">SR NO.</td>
                                <td style="background-color:#DDDAD9;width:170px; text-align:center;">TEST PARAMETER</td>
                                <td style="background-color:#DDDAD9;width:170px; text-align:center;">STANDARD</td>
                                <td style="background-color:#DDDAD9;width:170px; text-align:center;">OBSERVATIONS</td>
                            </tr>';
                            $i=1;
                 $sql1 = "select * from testing_tests where testing_no= '".$row['testing_no']."'";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
           
            while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td style="width:30px; text-align:center;">'.$i++.'</td>
                                <td style="width:170px; text-align:center;"> '.$row1['test'].'</td>
                                <td style="width:170px; text-align:center;">'.$row1['description'].'</td>
                                <td style="width:170px; text-align:center;">'.$row1['result'].'</td>
                            </tr>
                            ';
            }
        }
                            $html.='
                        </table>
                        ';
              }
        }
                        
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('RDS Corrugated Box.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadTestingAllocationLog") {
        qc_testing_pdf_flush();
        $materialType = isset($_GET["material_type"]) ? $_GET["material_type"] : "Raw Material";
        $_GET['filename'] = $materialType.' Testing Allocation Log';
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdfpage'] = 'L';
        include("../../pdfimp2.php");

        $html .= '
            <h2 style="text-align:center; font-size:12px;">'.$materialType.' TESTING ALLOCATION LOG</h2>
            <table cellpadding="4" border="1" width="100%" style="border-collapse:collapse;">
                <tr style="text-align:center; background-color:#DDDAD9; font-weight:bold;">
                    <td style="font-size: 7px;">Sr</td>
                    <td style="font-size: 7px;">Testing No</td>
                    <td style="font-size: 7px;">Sampling No</td>
                    <td style="font-size: 7px;">Material Name</td>
                    <td style="font-size: 7px;">Material Code</td>
                    <td style="font-size: 7px;">Medicap lot no.</td>
                    <td style="font-size: 7px;">Receiving no.</td>
                    <td style="font-size: 7px;">Spec No</td>
                    <td style="font-size: 7px;">Status</td>
                    <td style="font-size: 7px;">Allocated By/Dt</td>
                </tr>';

        $sql = "SELECT t.testing_no, t.sampling_no, t.batch_no, t.grn_no, t.ar_no, t.material_code, t.specification_no, t.status, t.allocationBy, t.allocationOn, b.material_name
                FROM testing t
                LEFT JOIN material b ON t.material_code = b.material_code
                WHERE t.plant_id = '".$_GET["plant_id"]."'
                AND b.material_type = '".$conn->real_escape_string($materialType)."'
                AND t.status != 'Pending'
                ORDER BY t.allocationOn DESC";

        $i = 1;
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '
                <tr>
                    <td style="font-size: 7px; text-align:center;">'.$i.'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['testing_no'].'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['sampling_no'].'</td>
                    <td style="font-size: 7px; text-align:left;">'.$row['material_name'].'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['material_code'].'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['batch_no'].'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['grn_no'].'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['specification_no'].'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['status'].'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['allocationBy'].' / '.qc_testing_pdf_date($row['allocationOn']).'</td>
                </tr>';
                $i++;
            }
        }

        $html .= '</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        qc_testing_pdf_flush();
        $pdf->Output('testing_allocation_log.pdf', 'I');
        exit;
    }
    else if ($_GET["type"] == "downloadRejectedTestingLog") {
        qc_testing_pdf_flush();
        include("../../pdfimp2.php");

        $materialType = isset($_GET["material_type"]) ? $_GET["material_type"] : "Raw Material";
        $logoHtml = qc_testing_pdf_logo_html($logo);

        $pdf = new TCPDF('L', 'mm', 'A4');
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Medicap Laboratories');
        $pdf->SetTitle($materialType.' Rejected Testing Log');
        $pdf->SetFont('dejavusans', '', 7);
        $pdf->SetMargins(4, 4, 4);
        $pdf->AddPage();

        $html = qc_testing_pdf_header($logoHtml, $plant_full_name, $plant_full_address);
        $html .= '
            <div></div>
            <table style="width: 100%; border-collapse: collapse; font-family: dejavusans;" cellpadding="3">
                <tr><td style="width:100%; font-size: 10px;text-align: center;"><b>'.$materialType.' REJECTED TESTING LOG</b></td></tr>
            </table>
            <div></div>
            <table style="width: 100%;font-family: dejavusans;" cellpadding="3" border="1">
                <tr>
                    <td style="font-size: 6px; font-weight: bold;">Sr</td>
                    <td style="font-size: 6px; font-weight: bold;">Testing No</td>
                    <td style="font-size: 6px; font-weight: bold;">Sampling No</td>
                    <td style="font-size: 6px; font-weight: bold;">Material Name</td>
                    <td style="font-size: 6px; font-weight: bold;">Material Code</td>
                    <td style="font-size: 6px; font-weight: bold;">Batch No</td>
                    <td style="font-size: 6px; font-weight: bold;">A.R. No</td>
                    <td style="font-size: 6px; font-weight: bold;">Document No</td>
                    <td style="font-size: 6px; font-weight: bold;">Spec No</td>
                    <td style="font-size: 6px; font-weight: bold;">Status</td>
                    <td style="font-size: 6px; font-weight: bold;">Allocated By/Dt</td>
                    <td style="font-size: 6px; font-weight: bold;">Checked By/Dt</td>
                    <td style="font-size: 6px; font-weight: bold;">Approved By/Dt</td>
                </tr>';

        $sql = "SELECT t.testing_no, t.sampling_no, t.batch_no, t.grn_no, t.ar_no, t.material_code, t.specification_no, t.status,
                t.allocationBy, t.allocationOn, t.check_by, t.check_date, t.approve_by, t.approve_date, b.material_name
                FROM testing t
                LEFT JOIN material b ON t.material_code = b.material_code
                WHERE t.plant_id = '".$_GET["plant_id"]."'
                AND b.material_type = '".$conn->real_escape_string($materialType)."'
                AND t.status = 'Rejected'
                AND t.micro_status = 'Rejected'
                AND t.outside_status = 'Rejected'
                ORDER BY t.allocationOn DESC";

        $i = 1;
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '
                <tr>
                    <td style="font-size: 6px; color:blue;">'.$i.'</td>
                    <td style="font-size: 6px; color:blue;">'.$row['testing_no'].'</td>
                    <td style="font-size: 6px; color:blue;">'.$row['sampling_no'].'</td>
                    <td style="font-size: 6px; color:blue;text-align:left;">'.$row['material_name'].'</td>
                    <td style="font-size: 6px; color:blue;">'.$row['material_code'].'</td>
                    <td style="font-size: 6px; color:blue;">'.$row['batch_no'].'</td>
                    <td style="font-size: 6px; color:blue;">'.$row['ar_no'].'</td>
                    <td style="font-size: 6px; color:blue;">'.$row['grn_no'].'</td>
                    <td style="font-size: 6px; color:blue;">'.$row['specification_no'].'</td>
                    <td style="font-size: 6px; color:blue;">'.$row['status'].'</td>
                    <td style="font-size: 6px; color:blue;">'.$row['allocationBy'].' / '.qc_testing_pdf_date($row['allocationOn']).'</td>
                    <td style="font-size: 6px; color:blue;">'.$row['check_by'].' / '.qc_testing_pdf_date($row['check_date']).'</td>
                    <td style="font-size: 6px; color:blue;">'.$row['approve_by'].' / '.qc_testing_pdf_date($row['approve_date']).'</td>
                </tr>';
                $i++;
            }
        }

        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        qc_testing_pdf_flush();
        $pdf->Output('rejected_testing_log.pdf', 'I');
        exit;
    }
    else if ($_GET["type"] == "downloadCoaLog") {
        qc_testing_pdf_flush();
        include("../../pdfimp2.php");

        $materialName = isset($_GET["material_name"]) ? trim($_GET["material_name"]) : '';
        $logoHtml = qc_testing_pdf_logo_html($logo);

        $pdf = new TCPDF('L', 'mm', 'A4');
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Medicap Laboratories');
        $pdf->SetTitle('Certificate of Analysis Log');
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->SetMargins(6, 6, 6);
        $pdf->AddPage();

        $html = qc_testing_pdf_header($logoHtml, $plant_full_name, $plant_full_address);
        $html .= '
            <div></div>
            <table style="width: 100%; border-collapse: collapse; font-family: dejavusans;" cellpadding="3">
                <tr><td style="width:100%; font-size: 10px;text-align: center;"><b>CERTIFICATE OF ANALYSIS LOG</b></td></tr>
            </table>
            <div></div>
            <table style="width: 100%;font-family: dejavusans;" cellpadding="4" border="1">
                <tr>
                    <td style="font-size: 7px; font-weight: bold;">Sr</td>
                    <td style="font-size: 7px; font-weight: bold;">Date</td>
                    <td style="font-size: 7px; font-weight: bold;">Material Code</td>
                    <td style="font-size: 7px; font-weight: bold;">Material Name</td>
                    <td style="font-size: 7px; font-weight: bold;">Medicap lot no</td>
                    <td style="font-size: 7px; font-weight: bold;">Sampling No</td>
                    <td style="font-size: 7px; font-weight: bold;">Testing No</td>
                    <td style="font-size: 7px; font-weight: bold;">Status</td>
                </tr>';

        $sql = "SELECT t.entry_date, t.material_code, m.material_name, t.ar_no, t.sampling_no, t.testing_no, t.status
                FROM testing t
                LEFT JOIN material m ON t.material_code = m.material_code
                WHERE t.status = 'Approved'
                AND t.plant_id = '".$_GET["plant_id"]."'";
        if ($materialName !== '') {
            $sql .= " AND m.material_name = '".$conn->real_escape_string($materialName)."'";
        }
        $sql .= " ORDER BY t.id DESC LIMIT 500";

        $i = 1;
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $matName = isset($row['material_name']) ? $row['material_name'] : '';
                $html .= '
                <tr>
                    <td style="font-size: 7px; color:blue;">'.$i.'</td>
                    <td style="font-size: 7px; color:blue;">'.qc_testing_pdf_date($row['entry_date']).'</td>
                    <td style="font-size: 7px; color:blue;">'.$row['material_code'].'</td>
                    <td style="font-size: 7px; color:blue;text-align:left;">'.$matName.'</td>
                    <td style="font-size: 7px; color:blue;">'.$row['ar_no'].'</td>
                    <td style="font-size: 7px; color:blue;">'.$row['sampling_no'].'</td>
                    <td style="font-size: 7px; color:blue;">'.$row['testing_no'].'</td>
                    <td style="font-size: 7px; color:blue;">'.$row['status'].'</td>
                </tr>';
                $i++;
            }
        }

        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        qc_testing_pdf_flush();
        $pdf->Output('coa_log.pdf', 'I');
        exit;
    }
    else if ($_GET["type"] == "downloadUsageLog") {
        qc_testing_pdf_flush();
        include("../../pdfimp2.php");

        $equipmentRows = json_decode(isset($_POST['equipment']) ? $_POST['equipment'] : '[]', true);
        $chemicalRows = json_decode(isset($_POST['chemical']) ? $_POST['chemical'] : '[]', true);
        if (!is_array($equipmentRows)) {
            $equipmentRows = array();
        }
        if (!is_array($chemicalRows)) {
            $chemicalRows = array();
        }

        $logoHtml = qc_testing_pdf_logo_html($logo);

        $pdf = new TCPDF('P', 'mm', 'A4');
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Medicap Laboratories');
        $pdf->SetTitle('Equipment and Chemical Usage Log');
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->SetMargins(8, 8, 8);
        $pdf->AddPage();

        $html = qc_testing_pdf_header($logoHtml, $plant_full_name, $plant_full_address);
        $html .= '
            <div></div>
            <table style="width: 100%; border-collapse: collapse; font-family: dejavusans;" cellpadding="3">
                <tr><td style="width:100%; font-size: 10px;text-align: center;"><b>EQUIPMENT &amp; CHEMICAL USAGE LOG</b></td></tr>
            </table>
            <div></div>
            <div style="font-size:9px; font-weight:bold;">Equipment Usage</div>
            <table style="width: 100%;font-family: dejavusans;" cellpadding="4" border="1">
                <tr>
                    <td style="font-size: 7px; font-weight: bold;">Sr</td>
                    <td style="font-size: 7px; font-weight: bold;">Testing No</td>
                    <td style="font-size: 7px; font-weight: bold;">Test</td>
                    <td style="font-size: 7px; font-weight: bold;">Equipment Name</td>
                    <td style="font-size: 7px; font-weight: bold;">Equipment Code</td>
                    <td style="font-size: 7px; font-weight: bold;">Start</td>
                    <td style="font-size: 7px; font-weight: bold;">End</td>
                    <td style="font-size: 7px; font-weight: bold;">Entry By</td>
                    <td style="font-size: 7px; font-weight: bold;">Entry On</td>
                </tr>';

        if (count($equipmentRows) === 0) {
            $html .= '<tr><td colspan="9" style="font-size:7px;">No equipment usage log found.</td></tr>';
        } else {
            $i = 1;
            foreach ($equipmentRows as $row) {
                $entryOn = isset($row['entry_on']) ? qc_testing_pdf_date($row['entry_on']) : '-';
                $html .= '
                <tr>
                    <td style="font-size: 7px; color:blue;">'.$i.'</td>
                    <td style="font-size: 7px; color:blue;">'.(isset($row['testing_no']) ? $row['testing_no'] : '').'</td>
                    <td style="font-size: 7px; color:blue;">'.(isset($row['test_name']) ? $row['test_name'] : '-').'</td>
                    <td style="font-size: 7px; color:blue;text-align:left;">'.(isset($row['equipment_name']) ? $row['equipment_name'] : '').'</td>
                    <td style="font-size: 7px; color:blue;">'.(isset($row['equipment_code']) ? $row['equipment_code'] : '').'</td>
                    <td style="font-size: 7px; color:blue;">'.(isset($row['start_time']) ? $row['start_time'] : '-').'</td>
                    <td style="font-size: 7px; color:blue;">'.(isset($row['end_time']) ? $row['end_time'] : '-').'</td>
                    <td style="font-size: 7px; color:blue;">'.(isset($row['entry_by']) ? $row['entry_by'] : '').'</td>
                    <td style="font-size: 7px; color:blue;">'.$entryOn.'</td>
                </tr>';
                $i++;
            }
        }

        $html .= '</table><div></div><div style="font-size:9px; font-weight:bold;">Chemical Usage</div>
            <table style="width: 100%;font-family: dejavusans;" cellpadding="4" border="1">
                <tr>
                    <td style="font-size: 7px; font-weight: bold;">Sr</td>
                    <td style="font-size: 7px; font-weight: bold;">Testing No</td>
                    <td style="font-size: 7px; font-weight: bold;">Test</td>
                    <td style="font-size: 7px; font-weight: bold;">Chemical Name</td>
                    <td style="font-size: 7px; font-weight: bold;">Chemical Code</td>
                    <td style="font-size: 7px; font-weight: bold;">Batch No</td>
                    <td style="font-size: 7px; font-weight: bold;">Qty Used</td>
                    <td style="font-size: 7px; font-weight: bold;">Entry By</td>
                    <td style="font-size: 7px; font-weight: bold;">Entry On</td>
                </tr>';

        if (count($chemicalRows) === 0) {
            $html .= '<tr><td colspan="9" style="font-size:7px;">No chemical usage log found.</td></tr>';
        } else {
            $i = 1;
            foreach ($chemicalRows as $row) {
                $entryOn = isset($row['entry_on']) ? qc_testing_pdf_date($row['entry_on']) : '-';
                $html .= '
                <tr>
                    <td style="font-size: 7px; color:blue;">'.$i.'</td>
                    <td style="font-size: 7px; color:blue;">'.(isset($row['testing_no']) ? $row['testing_no'] : '').'</td>
                    <td style="font-size: 7px; color:blue;">'.(isset($row['test_name']) ? $row['test_name'] : '-').'</td>
                    <td style="font-size: 7px; color:blue;text-align:left;">'.(isset($row['chemical_name']) ? $row['chemical_name'] : '').'</td>
                    <td style="font-size: 7px; color:blue;">'.(isset($row['chemical_code']) ? $row['chemical_code'] : '').'</td>
                    <td style="font-size: 7px; color:blue;">'.(isset($row['batch_no']) ? $row['batch_no'] : '-').'</td>
                    <td style="font-size: 7px; color:blue;">'.(isset($row['qty_used']) ? $row['qty_used'] : '-').'</td>
                    <td style="font-size: 7px; color:blue;">'.(isset($row['entry_by']) ? $row['entry_by'] : '').'</td>
                    <td style="font-size: 7px; color:blue;">'.$entryOn.'</td>
                </tr>';
                $i++;
            }
        }

        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        qc_testing_pdf_flush();
        $pdf->Output('usage_log.pdf', 'I');
        exit;
    }

} 
else {
    echo "{\"status\":\"invalid\"}";
}
$conn->close();
?>