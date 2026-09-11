<?php


// ini_set('display_errors', 1);
// error_reporting(E_ALL);



    require '../../db.php'; 
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    
 

    
    
    // print_r($_GET);exit;
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);
    if (!is_array($input)) {
        $input = array();
    }
    $counter = 1;
    $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    function raw_spec_tests_for_row($conn, $plantId, $specRow) {
        $output = array();
        $specNo = isset($specRow['specification_no']) ? trim($specRow['specification_no']) : '';
        if ($specNo === '') {
            return $output;
        }
        $plantEsc = $conn->real_escape_string($plantId);
        $specNoEsc = $conn->real_escape_string($specNo);
        $entryBy = isset($specRow['entry_by']) ? trim($specRow['entry_by']) : '';
        $entryDate = isset($specRow['entry_date']) ? trim($specRow['entry_date']) : '';
        $sql = "SELECT * FROM spec_tests WHERE plant_id='".$plantEsc."' AND specification_no='".$specNoEsc."'";
        if ($entryBy !== '' && $entryDate !== '') {
            $sql .= " AND entryBy='".$conn->real_escape_string($entryBy)."' AND entryOn='".$conn->real_escape_string($entryDate)."'";
        }
        $sql .= " ORDER BY id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        return $output;
    }

    function raw_spec_revisions_for_row($conn, $plantId, $specRow) {
        $output = array();
        $specNo = isset($specRow['specification_no']) ? trim($specRow['specification_no']) : '';
        if ($specNo === '') {
            return $output;
        }
        $plantEsc = $conn->real_escape_string($plantId);
        $specNoEsc = $conn->real_escape_string($specNo);
        // Full history for this Spec No (do not filter by current version_no —
        // revision rows often use padded/different version values and would be hidden).
        $sql = "SELECT * FROM spec_revision WHERE specification_no='".$specNoEsc."'";
        if ($plantEsc !== '') {
            $sql .= " AND (plant_id='".$plantEsc."' OR IFNULL(plant_id,'')='')";
        }
        $sql .= " ORDER BY id ASC";
        $result = @$conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        return $output;
    }

    function raw_next_specification_no($conn, $plantId, $specType) {
        $id = 0;
        $result = $conn->query("SELECT MAX(id) as id FROM specification");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $id = intval($row['id']);
        }
        $matType = 'FG';
        if ($specType == 'Raw Material') {
            $matType = 'RMS';
        } else if ($specType == 'Packing Material') {
            $matType = 'PMS';
        }
        $id++;
        $plantId = strval($plantId);
        if ($plantId == '181') {
            return 'PIPL/QCD/'.$matType.'/'.$id;
        }
        return 'SC/QCD/'.$matType.'/'.$id;
    }

    if ($_GET["type"] == "getTests") {
        $output = Array();
        $sql = "SELECT test FROM test WHERE classification='Raw Material' AND status='active' AND test_type='".$_GET["test_type"]."' GROUP BY test";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $output1 = array();
                $sql1 = "SELECT * FROM subtest WHERE classification='Raw Material' AND test_type='".$_GET["test_type"]."' AND test='".$row["test"]."' GROUP BY subtest";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        $output1[] = $row1;
                    }
                }
                $row["subtests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getMaterials") {
        $output = Array();
        $sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND material_type='Raw Material' AND material_subtype='".$_GET["material_subtype"]."'  AND material_code NOT IN (SELECT material_code FROM specification WHERE user_no='".$_GET["user_no"]."' AND status IN ('pending','checked', 'approve') AND spec_type LIKE 'Raw Material%')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getSpecificationByProduct") {
        $output = Array();
        $sql = "SELECT a.*,b.grade as m_grade,b.category FROM specification a left JOIN material b on 
        a.material_code=b.material_code WHERE a.material_code='".$_GET["material_code"]."' ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
              $output1 = Array();
            while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while($row2 = $result1->fetch_assoc()) {
                        $output1[] = $row2;
                    }
                }
                $row['tests'] =$output1;
                
                // if($row['m_grade'] == 'Inhouse'){
                //     $row['gradeName'] = $row['m_grade'];
                // }else{
                //     $q= 'SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('.$row['m_grade'].')';
                //     $resQ = $conn->query($q);
                //     $prodLatest = $resQ->fetch_assoc();
                //     $row['gradeName'] = $prodLatest['gradeName'];
                // }
                

                
                $output = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getSpecificationForSamplingByMaterialCode") {
        $output = Array();
        $sql = "SELECT id,specification_no,material_code,`chemical_qty`, `physical_qty`, `micro_qty`, `indentification_qty`, `sample_qty`, `control_sample`, `additional_sample`, `totalsample_qty`, `unit` FROM specification WHERE material_code = '".$_GET["material_code"]."' order by id desc limit 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output = $row;
            }
        }
        echo json_encode($output);
    } 
      else if($_GET['type'] == 'SpecificationLogPDF') {
         $_GET['filename'] = 'Raw Material Specification Report'; $_GET['pdftype'] = 'onlyheader'; include('../../pdfimp2.php');
         $html.='
         <h2 style="text-align:center">Raw Material Specification Report</h2>
         <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:center;"><b>Sr.</b></td>
                <td style="width:10%; text-align:center;"><b>Spec No</b></td>
                <td style="width:15%; text-align:center;"><b>Material Type</b></td>
                <td style="width:15%; text-align:center;"><b>Material Name</b></td>
                <td style="width:10%; text-align:center;"><b>Grade</b></td>
                <td style="width:15%; text-align:center;"><b>Material Code</b></td>
                <td style="width:10%; text-align:center;"><b>Version No</b></td>
                <td style="width:15%; text-align:center;"><b>C C Number</b></td>
            </tr>';
            $i=1;
              $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade 
        FROM specification s LEFT JOIN material m ON s.material_code=m.material_code 
        WHERE m.plant_id= '".$_GET["plant_id"]."' AND s.user_no='".$_GET["user_no"]."' AND s.spec_type LIKE 'Raw Material%' AND 
        ( s.status = 'approve' OR s.status = 'reject') ORDER BY s.id DESC";
        
        //   $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade 
        //             FROM specification s 
        //             LEFT JOIN material m 
        //             ON s.material_code = m.material_code 
        //             AND m.plant_id = '".$_GET["plant_id"]."' 
        //             WHERE s.user_no = '".$_GET["user_no"]."' 
        //             AND s.spec_type LIKE 'Packing Material%' 
        //             AND (s.status = 'approve' OR s.status = 'reject') 
        //             ORDER BY s.id DESC"; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:10%;">'.$i.'.</td>
                <td style="width:10%;">'.$row['specification_no'].'</td>
                <td style="width:15%;">'.$row['material_subtype'].'</td>
                <td style="width:15%;">'.$row['material_name'].'</td>
                <td style="width:10%;">'.$row['grade'].'</td>
                <td style="width:15%;">'.$row['material_code'].'</td>
                <td style="width:10%;">'.$row['version_no'].'</td>
                <td style="width:15%;">'.$row['cc_no'].'</td>
            </tr>';
            $i++;
            }
        }
        
         $html.='</table>';
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('RawMaterialSpecificationReport.pdf', 'I');
    }

    else if ($_GET["type"] == "getNextSpecificationNo") {
        $specType = isset($_GET['spec_type']) ? trim($_GET['spec_type']) : 'Raw Material';
        $specNo = raw_next_specification_no($conn, $_GET['plant_id'], $specType);
        echo json_encode(array('specification_no' => $specNo));
    }

    else if($_GET["type"]=="saveSpecification") {
        $colCheck = $conn->query("SHOW COLUMNS FROM specification LIKE 'dynamic_fields_json'");
        if (!($colCheck && $colCheck->num_rows > 0)) {
            $conn->query("ALTER TABLE specification ADD COLUMN dynamic_fields_json LONGTEXT NULL AFTER note");
        }
        
        if($input['specIs'] == 'New'
            || trim((string)($input['specification_no'] ?? '')) === ''
            || stripos((string)($input['specification_no'] ?? ''), 'Auto Generated') !== false){
        	$input['specification_no'] = raw_next_specification_no($conn, $_GET['plant_id'], $input['spec_type']);
        }
        
        $dynamicFieldsJson = isset($input['dynamic_fields_json']) ? json_encode($input['dynamic_fields_json']) : '{}';
        if ($dynamicFieldsJson === false) {
            $dynamicFieldsJson = '{}';
        }

    	$sql = "INSERT INTO `specification`(`plant_id`, `status`, `specIs`, `specification_no`, `spec_type`, `material_subtype`, `material_code`, `material_grade`, `chemical_qty`, 
        `physical_qty`, `micro_qty`, `indentification_qty`, `sample_qty`, `control_sample`, `additional_sample`, `totalsample_qty`, `unit`, `issuedDate`, `supersede_no`, 
        `version_no`, `review_date`, `effective_date`, `retest_period`, `sampling_plan`, `storage_condition`, `samplingDetails`, `hazardAndPrecautions`, `stockTransfer`, `note`, `entry_by`, 
        `dynamic_fields_json`, `entry_date`) VALUES ('".$_GET['plant_id']."', 'Pending', '".$input['specIs']."', '".$input['specification_no']."', '".$input['spec_type']."', '".$input['material_subtype']."', '".$input['material_code']."', 
        '".$input['material_grade']."', '".$input['chemical_qty']."', '".$input['physical_qty']."', '".$input['micro_qty']."', '".$input['indentification_qty']."', '".$input['sample_qty']."', 
        '".$input['control_sample']."', '".$input['additional_sample']."', '".$input['totalsample_qty']."', '".$input['unit']."', '".$input['issuedDate']."', '".$input['supersede_no']."', 
        '".$input['version_no']."', '".$input['review_date']."', '".$input['effective_date']."', '".$input['retest_period']."', '".$input['sampling_plan']."', '".$input['storage_condition']."', '".$input['samplingDetails']."', 
        '".$input['hazardAndPrecautions']."', '".$input['stockTransfer']."', '".$input['note']."', '".$_GET['emp_id']."', '".$conn->real_escape_string($dynamicFieldsJson)."', '".$entry_date."') ";
        
    	if($conn->query($sql)){
    	    
    		$spectTests = $input['spectTests'];
     		$len = count($spectTests);
     		
    		for($i = 0; $i<$len; $i++) {
    			$data = $spectTests[$i];
    			
                $sql="INSERT INTO `spec_tests`(`plant_id`, `testFor`, `specification_no`, `test_type`, `test`, `test_method_no`, `subtest`, `description`, `reference_type`, 
                `limit_type`, `lower_limit`, `upper_limit`, `unit`, `limits`, `sample_qty`, `release_stability`, `retest`, `reccSamplingQty`, `outside_testing`, 
                `status`, `compliances`, `test_master_id`, `sto`, `bulk_release`, `entryBy`, `entryOn`) VALUES ('".$_GET['plant_id']."','".$data['testFor']."',
                '".$input['specification_no']."','".$data['test_type']."','".$data['test']."','".$data['test_method_no']."','".$data['subtest']."','".$data['description']."',
                '".$data['reference_type']."','".$data['limit_type']."','".$data['lower_limit']."','".$data['upper_limit']."','".$data['unit']."','".$data['limits']."',
                '".$data['sample_qty']."','".$data['release_stability']."','".$data['retest']."','".$data['reccSamplingQty']."','".$data['outside_testing']."',
                'Pending','".$data['compliances']."','".$data['test_master_id']."','".$data['sto']."','".$data['bulk_release']."',
                '".$_GET['emp_id']."', '$entry_date')";
                
                $conn->query($sql);
                
    	    }
    	    
    		$revisionList = $input['revisionList'];
     		$lenR = count($revisionList);
     		
    		for($i = 0; $i<$lenR; $i++) {
    			$data = $revisionList[$i];
    			
                $sql="INSERT INTO `spec_revision`(`plant_id`, `specification_no`, `version_no`, `change_mode`, `reason`, `effective_date`, `status`, `entryBy`, `entryOn`) VALUES ('".$_GET['plant_id']."',
                '".$input['specification_no']."','".$data['version_no']."','".$data['change_mode']."','".$data['reason']."','".$data['effective_date']."','Pending','".$_GET['emp_id']."', '$entry_date')";
                
                $conn->query($sql);
                
    	    }
    	    
    		echo "{\"status\":\"success\"}";
    	}
    	else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    	
    } 
 
    
     else if ($_GET["type"] == "getTests1") {
	$sql = "SELECT * FROM subtest where test='".$_GET["test"]."'";
		$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}

    else if ($_GET["type"] == "getPendingSpecifications") {
            $output = Array();
            if($_GET["spec_type"] == 'Finish Product'){
                $sql = "SELECT a.*,b.product_name as material_name FROM specification a LEFT JOIN product b ON a.material_code = b.product_code WHERE a.status = 'Pending' AND a.plant_id = '".$_GET["plant_id"]."' AND a.spec_type = '".$_GET["spec_type"]."' ";
            }else{
                $sql = "SELECT a.*,b.material_name FROM specification a LEFT JOIN material b ON a.material_code = b.material_code WHERE a.status = 'Pending' AND a.plant_id = '".$_GET["plant_id"]."' AND a.spec_type = '".$_GET["spec_type"]."' ";
            }
        
        
        	$result = $conn->query($sql);
        	if($result && $result->num_rows > 0){
        		while($row = $result->fetch_assoc()){
        		    $row['spectTests'] = raw_spec_tests_for_row($conn, $_GET["plant_id"], $row);
        		    $row['revisionList'] = raw_spec_revisions_for_row($conn, $_GET["plant_id"], $row);
        			$output[] = $row;
        		}
        	}
        	
            echo json_encode($output);
            
    } 
    else if ($_GET["type"] == "checkSpecification") {
        
        $sql = "UPDATE specification SET check_by = '".$_GET["emp_id"]."', check_date = '$entry_date', status = '".$input["status"]."', checkingRemark = '".$input["checkingRemark"]."' WHERE id = '".$input["id"]."' ";
        if ($conn->query($sql)) { 
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    } 
    else if ($_GET["type"] == "getCheckedSpecifications") {
            $output = Array();
        
            if($_GET["spec_type"] == 'Finish Product'){
                $sql = "SELECT a.*,b.product_name as material_name FROM specification a LEFT JOIN product b ON a.material_code = b.product_code WHERE a.status = 'Checked' AND a.plant_id = '".$_GET["plant_id"]."' AND a.spec_type = '".$_GET["spec_type"]."' ";
            }else{
                $sql = "SELECT a.*,b.material_name FROM specification a LEFT JOIN material b ON a.material_code = b.material_code WHERE a.status = 'Checked' AND a.plant_id = '".$_GET["plant_id"]."' AND a.spec_type = '".$_GET["spec_type"]."' ";
            }
        
         	$result = $conn->query($sql);
        	if($result && $result->num_rows > 0){
        		while($row = $result->fetch_assoc()){
        		    $row['spectTests'] = raw_spec_tests_for_row($conn, $_GET["plant_id"], $row);
        		    $row['revisionList'] = raw_spec_revisions_for_row($conn, $_GET["plant_id"], $row);
        			$output[] = $row;
        		}
        	}
        	
            echo json_encode($output);
            
    }
    else if ($_GET["type"] == "approveSpecification") {
        
        $sql = "UPDATE specification SET approve_by = '".$_GET["emp_id"]."', approve_date = '$entry_date', status = '".$input["status"]."', approvalRemark = '".$input["approvalRemark"]."' WHERE id = '".$input["id"]."' ";
        if ($conn->query($sql)) { 
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "getApprovedSPecificationByTypeAndStatus") {
        
        	$output = Array();
            if($_GET["spec_type"] == 'Finish Product'){
                $sql = "SELECT a.*,b.product_name as material_name FROM specification a LEFT JOIN product b ON a.material_code = b.product_code WHERE a.status != 'Rejected' AND a.plant_id = '".$_GET["plant_id"]."' AND a.spec_type = '".$_GET["spec_type"]."' ";
            }else{
                $sql = "SELECT a.*,b.material_name FROM specification a LEFT JOIN material b ON a.material_code = b.material_code WHERE a.status != 'Rejected' AND a.plant_id = '".$_GET["plant_id"]."' AND a.spec_type = '".$_GET["spec_type"]."' ";
            }
        
        	$result = $conn->query($sql);
        	if($result->num_rows > 0){
        	
        		while($row = $result->fetch_assoc()){
        		    
        		    
                        $sql1 = "SELECT * FROM spec_tests WHERE plant_id = '".$_GET["plant_id"]."' AND specification_no = '".$row["specification_no"]."' ";
                    	$result1 = $conn->query($sql1);
                    	if($result1->num_rows > 0){
                    		$output1 = Array();
                    		while($row1 = $result1->fetch_assoc()){
                    			$output1[] = $row1;
                    		}
                    	}
                    	
                        $sql2 = "SELECT * FROM spec_revision WHERE plant_id = '".$_GET["plant_id"]."' AND specification_no = '".$row["specification_no"]."' ";
                    	$result2 = $conn->query($sql2);
                    	if($result2->num_rows > 0){
                    		$output2 = Array();
                    		while($row2 = $result2->fetch_assoc()){
                    			$output2[] = $row2;
                    		}
                    	}
                    	
                    $row['spectTests'] = $output1;
                    $row['revisionList'] = $output2;
        		     
        			$output[] = $row;
        		}
        	}
        	
            echo json_encode($output);
            
    }
    else if ($_GET["type"] == "getCombinedSpecificationLogs") {
        $output = Array();
        $log_type = isset($_GET["log_type"]) ? trim($_GET["log_type"]) : 'Raw Material';

        if ($log_type == 'Raw Material' || $log_type == 'Packing Material') {
            $sql = "SELECT a.*, b.material_name, '".$log_type."' as log_type 
                    FROM specification a 
                    LEFT JOIN material b ON a.material_code = b.material_code 
                    WHERE a.status != 'Rejected' 
                    AND a.plant_id = '".$_GET["plant_id"]."' 
                    AND a.spec_type = '".$log_type."' 
                    ORDER BY a.id DESC";
        } else if ($log_type == 'Finish Product') {
            $sql = "SELECT a.*, b.product_name as material_name, 'Finish Product' as log_type 
                    FROM specification a 
                    LEFT JOIN product b ON a.material_code = b.product_code 
                    WHERE a.status != 'Rejected' 
                    AND a.plant_id = '".$_GET["plant_id"]."' 
                    AND a.spec_type = 'Finish Product' 
                    ORDER BY a.id DESC";
        } else if ($log_type == 'Inprocess') {
            $sql = "SELECT a.*, b.product_name as material_name, 'Inprocess' as log_type 
                    FROM specification a 
                    LEFT JOIN product b ON a.product_code = b.product_code 
                    WHERE a.status != 'Rejected' 
                    AND a.plant_id = '".$_GET["plant_id"]."' 
                    AND a.spec_type LIKE 'Inprocess%' 
                    ORDER BY a.id DESC";
        } else if ($log_type == 'Water Specification') {
            $sql = "SELECT a.*, a.water_type as material_name, 'Water Specification' as log_type 
                    FROM specification a 
                    WHERE a.status != 'Rejected' 
                    AND a.plant_id = '".$_GET["plant_id"]."' 
                    AND a.spec_type = 'Water Specification' 
                    ORDER BY a.id DESC";
        } else {
            $sql = "SELECT a.*, b.material_name, 'Raw Material' as log_type 
                    FROM specification a 
                    LEFT JOIN material b ON a.material_code = b.material_code 
                    WHERE a.status != 'Rejected' 
                    AND a.plant_id = '".$_GET["plant_id"]."' 
                    AND a.spec_type = 'Raw Material' 
                    ORDER BY a.id DESC";
        }

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (!isset($row["material_code"]) || $row["material_code"] == "") {
                    $row["material_code"] = "-";
                }
                if (!isset($row["material_name"]) || $row["material_name"] == "") {
                    $row["material_name"] = "-";
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getDeviationStatusForSpecifications") {
        $output = Array();
        $raw_specs = isset($_GET["spec_nos"]) ? $_GET["spec_nos"] : '';
        $parts = explode(",", $raw_specs);
        $safe_specs = Array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $safe_specs[] = "'".$conn->real_escape_string($p)."'";
            }
        }

        if (count($safe_specs) === 0) {
            echo json_encode($output);
        } else {
            $in = implode(",", $safe_specs);
            $sql = "SELECT sourceDocument, status, deviation_no, id 
                    FROM deviation 
                    WHERE sourceDocument IN ($in)
                    ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $spec_no = $row["sourceDocument"];
                    if (!isset($output[$spec_no])) {
                        $output[$spec_no] = $row;
                    }
                }
            }
            echo json_encode($output);
        }
    }
    else if ($_GET["type"] == "getSpecificationRevisionRequestStatus") {
        $output = Array();
        $raw_specs = isset($_GET["spec_nos"]) ? $_GET["spec_nos"] : '';
        $parts = explode(",", $raw_specs);
        $safe_specs = Array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $safe_specs[] = "'".$conn->real_escape_string($p)."'";
            }
        }

        if (count($safe_specs) === 0) {
            echo json_encode($output);
        } else {
            $in = implode(",", $safe_specs);
            $sql = "SELECT id, refDocNo, status, revisionComment, entryBy, entryOn 
                    FROM revisionRequest
                    WHERE reqFor = 'Specification Revision Request' 
                    AND refDocNo IN ($in)
                    ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $spec_no = $row["refDocNo"];
                    if (!isset($output[$spec_no])) {
                        $output[$spec_no] = $row;
                    }
                }
            }
            echo json_encode($output);
        }
    }
    else if ($_GET["type"] == "saveSpecificationRevisionRequest") {
        if (function_exists('mysqli_report')) {
            @mysqli_report(MYSQLI_REPORT_OFF);
        }
        $plantId = isset($_GET["plant_id"]) ? $_GET["plant_id"] : '';
        $empId = isset($_GET["emp_id"]) ? $_GET["emp_id"] : '';
        try {
            @$conn->query("CREATE TABLE IF NOT EXISTS `revisionRequest` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `plant_id` VARCHAR(50) NULL,
                `refDocNo` VARCHAR(150) NULL,
                `refDocId` VARCHAR(80) NULL,
                `refDocName` VARCHAR(255) NULL,
                `revisionComment` LONGTEXT NULL,
                `reqFor` VARCHAR(150) NULL,
                `entryBy` VARCHAR(100) NULL,
                `entryOn` VARCHAR(50) NULL,
                `status` VARCHAR(50) NULL DEFAULT 'Pending',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $rrCols = array(
                'plant_id' => "VARCHAR(50) NULL",
                'refDocNo' => "VARCHAR(150) NULL",
                'refDocId' => "VARCHAR(80) NULL",
                'refDocName' => "VARCHAR(255) NULL",
                'revisionComment' => "LONGTEXT NULL",
                'reqFor' => "VARCHAR(150) NULL",
                'entryBy' => "VARCHAR(100) NULL",
                'entryOn' => "VARCHAR(50) NULL",
                'status' => "VARCHAR(50) NULL DEFAULT 'Pending'",
            );
            foreach ($rrCols as $col => $def) {
                $colCheck = @$conn->query("SHOW COLUMNS FROM `revisionRequest` LIKE '".$conn->real_escape_string($col)."'");
                if (!($colCheck && $colCheck->num_rows > 0)) {
                    @$conn->query("ALTER TABLE `revisionRequest` ADD COLUMN `".$col."` ".$def);
                }
            }

            $refDocNo = isset($input["specification_no"]) ? trim((string)$input["specification_no"]) : '';
            $refDocId = isset($input["specification_id"]) ? trim((string)$input["specification_id"]) : '';
            $refDocName = isset($input["specification_name"]) ? trim((string)$input["specification_name"]) : '';
            $reason = isset($input["reason"]) ? trim((string)$input["reason"]) : '';
            $remarks = isset($input["remarks"]) ? trim((string)$input["remarks"]) : '';

            if ($refDocNo == '' || $reason == '') {
                echo "{\"status\":\"failed\",\"message\":\"Missing required fields\"}";
            } else {
                $comment = $reason;
                if ($remarks != '') {
                    $comment .= " | Remarks: ".$remarks;
                }

                $sqlCheck = "SELECT id FROM revisionRequest 
                             WHERE plant_id = '".$conn->real_escape_string($plantId)."' 
                             AND reqFor = 'Specification Revision Request' 
                             AND refDocNo = '".$conn->real_escape_string($refDocNo)."' 
                             AND status = 'Pending' 
                             ORDER BY id DESC LIMIT 1";
                $resultCheck = $conn->query($sqlCheck);
                if ($resultCheck && $resultCheck->num_rows > 0) {
                    echo "{\"status\":\"exists\"}";
                } else {
                    $sql = "INSERT INTO revisionRequest (plant_id, refDocNo, refDocId, refDocName, revisionComment, reqFor, entryBy, entryOn, status)
                            VALUES ('".$conn->real_escape_string($plantId)."',
                                    '".$conn->real_escape_string($refDocNo)."',
                                    '".$conn->real_escape_string($refDocId)."',
                                    '".$conn->real_escape_string($refDocName)."',
                                    '".$conn->real_escape_string($comment)."',
                                    'Specification Revision Request',
                                    '".$conn->real_escape_string($empId)."',
                                    '".$entry_date."',
                                    'Pending')";
                    if ($conn->query($sql)) {
                        echo "{\"status\":\"success\"}";
                    } else {
                        echo json_encode(array("status" => "failed", "message" => $conn->error));
                    }
                }
            }
        } catch (Exception $e) {
            echo json_encode(array("status" => "failed", "message" => $e->getMessage()));
        }
    }
    else if ($_GET["type"] == "getChangeControlStatusForSpecifications") {
        $output = Array();
        $raw_specs = isset($_GET["spec_nos"]) ? $_GET["spec_nos"] : '';
        $parts = explode(",", $raw_specs);
        $safe_specs = Array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $safe_specs[] = $p;
            }
        }

        foreach ($safe_specs as $specNo) {
            $escaped = $conn->real_escape_string($specNo);
            $sql = "SELECT id, ctrl_no, status, titleOfcc, entryDate
                    FROM changecontrol
                    WHERE plant_id = '".$_GET["plant_id"]."'
                      AND (
                        titleOfcc LIKE 'Specification Revision - ".$escaped."%'
                        OR proposed LIKE '%Specification: ".$escaped."%'
                      )
                    ORDER BY id DESC
                    LIMIT 1";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $output[$specNo] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getSpecificationWithDetailsForDraft") {
        $output = Array();
        $specNo = isset($_GET["specification_no"]) ? trim($_GET["specification_no"]) : '';
        $specId = isset($_GET["spec_id"]) ? trim($_GET["spec_id"]) : '';
        if ($specNo == '' && $specId == '') {
            echo json_encode($output);
        } else {
            if ($specId !== '' && ctype_digit($specId)) {
                $sql = "SELECT * FROM specification
                        WHERE plant_id = '".$_GET["plant_id"]."'
                          AND id = '".$conn->real_escape_string($specId)."'
                        LIMIT 1";
            } else {
                $sql = "SELECT * FROM specification
                        WHERE plant_id = '".$_GET["plant_id"]."'
                          AND specification_no = '".$conn->real_escape_string($specNo)."'
                        ORDER BY id DESC
                        LIMIT 1";
            }
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $materialCode = isset($row["material_code"]) ? trim($row["material_code"]) : '';
                if ($materialCode !== '') {
                    $codeEsc = $conn->real_escape_string($materialCode);
                    $matRes = $conn->query("SELECT material_name, grade FROM material WHERE material_code='".$codeEsc."' LIMIT 1");
                    if ($matRes && $matRes->num_rows > 0) {
                        $matRow = $matRes->fetch_assoc();
                        if (empty($row["material_name"])) {
                            $row["material_name"] = $matRow["material_name"] ?? '';
                        }
                        if (empty($row["material_grade"])) {
                            $row["material_grade"] = $matRow["grade"] ?? '';
                        }
                    }
                    if (empty($row["material_name"])) {
                        $prodRes = $conn->query("SELECT product_name FROM product WHERE product_code='".$codeEsc."' LIMIT 1");
                        if ($prodRes && $prodRes->num_rows > 0) {
                            $row["material_name"] = $prodRes->fetch_assoc()["product_name"] ?? '';
                        }
                    }
                }

                $row["spectTests"] = raw_spec_tests_for_row($conn, $_GET["plant_id"], $row);
                $row["revisionList"] = raw_spec_revisions_for_row($conn, $_GET["plant_id"], $row);
                $output = $row;
            }
            echo json_encode($output);
        }
    }
    else if ($_GET["type"] == "saveSpecificationDraft") {
        if (!is_array($input)) {
            if (is_string($input) && trim($input) !== '') {
                $decoded = json_decode($input, true);
                if (is_array($decoded)) {
                    $input = $decoded;
                }
            }
        }
        if (!is_array($input)) {
            echo json_encode(array("status" => "failed", "message" => "Invalid request body"));
        } else {
        $sqlCreate = "CREATE TABLE IF NOT EXISTS specification_draft (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) NOT NULL,
            specification_no VARCHAR(100) NOT NULL,
            source_spec_id VARCHAR(50) DEFAULT NULL,
            source_version_no VARCHAR(20) DEFAULT NULL,
            draft_version_no VARCHAR(20) DEFAULT NULL,
            draft_title VARCHAR(255) DEFAULT NULL,
            draft_data LONGTEXT,
            status VARCHAR(30) DEFAULT 'Draft',
            entryBy VARCHAR(50) DEFAULT NULL,
            entryOn DATETIME DEFAULT CURRENT_TIMESTAMP,
            updateBy VARCHAR(50) DEFAULT NULL,
            updateOn DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($sqlCreate);
        $sqlCreateObs = "CREATE TABLE IF NOT EXISTS specification_obsolete_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) NOT NULL,
            specification_no VARCHAR(100) NOT NULL,
            previous_version_no VARCHAR(20) DEFAULT NULL,
            previous_spec_data LONGTEXT,
            linked_ctrl_no VARCHAR(50) DEFAULT NULL,
            linked_ctrl_status VARCHAR(50) DEFAULT NULL,
            entryBy VARCHAR(50) DEFAULT NULL,
            entryOn DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($sqlCreateObs);

        $specNo = isset($input["specification_no"]) ? trim($input["specification_no"]) : '';
        if ($specNo == '') {
            echo "{\"status\":\"failed\",\"message\":\"Missing specification number\"}";
        } else {
            $payload = json_encode($input);
            $title = isset($input["draft_title"]) ? $input["draft_title"] : "Specification Draft";
            $sourceId = isset($input["source_spec_id"]) ? $input["source_spec_id"] : '';
            $sourceVersion = isset($input["source_version_no"]) ? $input["source_version_no"] : '';
            $draftVersion = isset($input["draft_version_no"]) ? $input["draft_version_no"] : '';

            // Save previous approved/current version snapshot once before draft edits.
            if ($sourceVersion != '') {
                $sqlObsCheck = "SELECT id FROM specification_obsolete_log
                                WHERE plant_id = '".$_GET["plant_id"]."'
                                  AND specification_no = '".$conn->real_escape_string($specNo)."'
                                  AND previous_version_no = '".$conn->real_escape_string($sourceVersion)."'
                                ORDER BY id DESC LIMIT 1";
                $resultObsCheck = $conn->query($sqlObsCheck);
                if (!($resultObsCheck && $resultObsCheck->num_rows > 0)) {
                    $previousSpec = Array();
                    $sqlBase = "SELECT * FROM specification
                                WHERE plant_id = '".$_GET["plant_id"]."'
                                  AND specification_no = '".$conn->real_escape_string($specNo)."'
                                ORDER BY id DESC LIMIT 1";
                    $resultBase = $conn->query($sqlBase);
                    if ($resultBase && $resultBase->num_rows > 0) {
                        $rowBase = $resultBase->fetch_assoc();
                        $tests = Array();
                        $sqlT = "SELECT * FROM spec_tests
                                 WHERE plant_id = '".$_GET["plant_id"]."'
                                   AND specification_no = '".$conn->real_escape_string($specNo)."'";
                        $resT = $conn->query($sqlT);
                        if ($resT && $resT->num_rows > 0) {
                            while ($r = $resT->fetch_assoc()) {
                                $tests[] = $r;
                            }
                        }
                        $revs = Array();
                        $sqlR = "SELECT * FROM spec_revision
                                 WHERE plant_id = '".$_GET["plant_id"]."'
                                   AND specification_no = '".$conn->real_escape_string($specNo)."'
                                 ORDER BY id DESC";
                        $resR = $conn->query($sqlR);
                        if ($resR && $resR->num_rows > 0) {
                            while ($r2 = $resR->fetch_assoc()) {
                                $revs[] = $r2;
                            }
                        }
                        $rowBase["spectTests"] = $tests;
                        $rowBase["revisionList"] = $revs;
                        $previousSpec = $rowBase;
                    }
                    $payloadObs = json_encode($previousSpec);
                    $sqlObsInsert = "INSERT INTO specification_obsolete_log
                                     (plant_id, specification_no, previous_version_no, previous_spec_data, entryBy)
                                     VALUES
                                     ('".$_GET["plant_id"]."',
                                      '".$conn->real_escape_string($specNo)."',
                                      '".$conn->real_escape_string($sourceVersion)."',
                                      '".$conn->real_escape_string($payloadObs)."',
                                      '".$_GET["emp_id"]."')";
                    $conn->query($sqlObsInsert);
                }
            }

            $sqlCheck = "SELECT id FROM specification_draft
                         WHERE plant_id = '".$_GET["plant_id"]."'
                           AND specification_no = '".$conn->real_escape_string($specNo)."'
                           AND status = 'Draft'
                         ORDER BY id DESC
                         LIMIT 1";
            $resultCheck = $conn->query($sqlCheck);
            if ($resultCheck && $resultCheck->num_rows > 0) {
                $rowC = $resultCheck->fetch_assoc();
                $id = $rowC["id"];
                $sqlU = "UPDATE specification_draft SET
                            source_spec_id = '".$conn->real_escape_string($sourceId)."',
                            source_version_no = '".$conn->real_escape_string($sourceVersion)."',
                            draft_version_no = '".$conn->real_escape_string($draftVersion)."',
                            draft_title = '".$conn->real_escape_string($title)."',
                            draft_data = '".$conn->real_escape_string($payload)."',
                            updateBy = '".$_GET["emp_id"]."'
                         WHERE id = '".$id."'";
                if ($conn->query($sqlU)) {
                    echo json_encode(array("status" => "success", "id" => $id));
                } else {
                    echo json_encode(array("status" => "failed", "message" => $conn->error));
                }
            } else {
                $sqlI = "INSERT INTO specification_draft
                         (plant_id, specification_no, source_spec_id, source_version_no, draft_version_no, draft_title, draft_data, status, entryBy, updateBy)
                         VALUES
                         ('".$_GET["plant_id"]."',
                          '".$conn->real_escape_string($specNo)."',
                          '".$conn->real_escape_string($sourceId)."',
                          '".$conn->real_escape_string($sourceVersion)."',
                          '".$conn->real_escape_string($draftVersion)."',
                          '".$conn->real_escape_string($title)."',
                          '".$conn->real_escape_string($payload)."',
                          'Draft',
                          '".$_GET["emp_id"]."',
                          '".$_GET["emp_id"]."')";
                if ($conn->query($sqlI)) {
                    echo json_encode(array("status" => "success", "id" => $conn->insert_id));
                } else {
                    echo json_encode(array("status" => "failed", "message" => $conn->error));
                }
            }
        }
        }
    }
    else if ($_GET["type"] == "getSpecificationDraftBySpecNo") {
        $output = Array();
        $specNo = isset($_GET["specification_no"]) ? trim($_GET["specification_no"]) : '';
        if ($specNo == '') {
            echo json_encode($output);
        } else {
            $sql = "SELECT * FROM specification_draft
                    WHERE plant_id = '".$_GET["plant_id"]."'
                      AND specification_no = '".$conn->real_escape_string($specNo)."'
                    ORDER BY id DESC
                    LIMIT 1";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $row["draft_data"] = json_decode($row["draft_data"], true);
                $output = $row;
            }
            echo json_encode($output);
        }
    }
    else if ($_GET["type"] == "finalizeSpecificationDraftToPending") {
        $specNo = isset($input["specification_no"]) ? trim($input["specification_no"]) : '';
        if ($specNo == '') {
            echo "{\"status\":\"failed\",\"message\":\"Missing specification number\"}";
        } else {
            $escSpec = $conn->real_escape_string($specNo);
            $sqlDraft = "SELECT * FROM specification_draft
                         WHERE plant_id = '".$_GET["plant_id"]."'
                           AND specification_no = '".$escSpec."'
                         ORDER BY id DESC
                         LIMIT 1";
            $resDraft = $conn->query($sqlDraft);
            if (!($resDraft && $resDraft->num_rows > 0)) {
                echo "{\"status\":\"failed\",\"message\":\"Draft not found\"}";
            } else {
                $draftRow = $resDraft->fetch_assoc();
                $draftData = json_decode($draftRow["draft_data"], true);
                if (!is_array($draftData)) {
                    echo "{\"status\":\"failed\",\"message\":\"Invalid draft data\"}";
                } else {
                    $targetId = intval(isset($draftData["source_spec_id"]) ? $draftData["source_spec_id"] : 0);
                    if ($targetId <= 0) {
                        $sqlTarget = "SELECT id FROM specification
                                      WHERE plant_id = '".$_GET["plant_id"]."'
                                        AND specification_no = '".$escSpec."'
                                      ORDER BY id DESC LIMIT 1";
                        $resTarget = $conn->query($sqlTarget);
                        if ($resTarget && $resTarget->num_rows > 0) {
                            $targetId = intval($resTarget->fetch_assoc()["id"]);
                        }
                    }

                    if ($targetId <= 0) {
                        echo "{\"status\":\"failed\",\"message\":\"Specification record not found\"}";
                    } else {
                        $draftVersion = isset($draftData["draft_version_no"]) ? $conn->real_escape_string($draftData["draft_version_no"]) : '';
                        $sourceVersion = isset($draftData["source_version_no"]) ? $conn->real_escape_string($draftData["source_version_no"]) : '';
                        $reviewDate = isset($draftData["review_date"]) ? $conn->real_escape_string($draftData["review_date"]) : '';
                        $effectiveDate = isset($draftData["effective_date"]) ? $conn->real_escape_string($draftData["effective_date"]) : '';
                        $retestPeriod = isset($draftData["retest_period"]) ? $conn->real_escape_string($draftData["retest_period"]) : '';
                        $samplingPlan = isset($draftData["sampling_plan"]) ? $conn->real_escape_string($draftData["sampling_plan"]) : '';
                        $sampleQty = isset($draftData["sample_qty"]) ? $conn->real_escape_string($draftData["sample_qty"]) : '';
                        $controlSample = isset($draftData["control_sample"]) ? $conn->real_escape_string($draftData["control_sample"]) : '';
                        $additionalSample = isset($draftData["additional_sample"]) ? $conn->real_escape_string($draftData["additional_sample"]) : '';
                        $totalSample = isset($draftData["totalsample_qty"]) ? $conn->real_escape_string($draftData["totalsample_qty"]) : '';
                        $storage = isset($draftData["storage_condition"]) ? $conn->real_escape_string($draftData["storage_condition"]) : '';
                        $samplingDetails = isset($draftData["samplingDetails"]) ? $conn->real_escape_string($draftData["samplingDetails"]) : '';
                        $hazard = isset($draftData["hazardAndPrecautions"]) ? $conn->real_escape_string($draftData["hazardAndPrecautions"]) : '';
                        $note = isset($draftData["note"]) ? $conn->real_escape_string($draftData["note"]) : '';

                        $sqlUpdateSpec = "UPDATE specification SET
                            status = 'Pending',
                            version_no = '".$draftVersion."',
                            supersede_no = '".$sourceVersion."',
                            review_date = '".$reviewDate."',
                            effective_date = '".$effectiveDate."',
                            retest_period = '".$retestPeriod."',
                            sampling_plan = '".$samplingPlan."',
                            sample_qty = '".$sampleQty."',
                            control_sample = '".$controlSample."',
                            additional_sample = '".$additionalSample."',
                            totalsample_qty = '".$totalSample."',
                            storage_condition = '".$storage."',
                            samplingDetails = '".$samplingDetails."',
                            hazardAndPrecautions = '".$hazard."',
                            note = '".$note."',
                            check_by = '',
                            check_date = NULL,
                            approve_by = '',
                            approve_date = NULL
                            WHERE id = '".$targetId."'";

                        if (!($conn->query($sqlUpdateSpec))) {
                            echo "{\"status\":\"failed\",\"message\":\"".$conn->error."\"}";
                        } else {
                            $conn->query("DELETE FROM spec_tests WHERE plant_id = '".$_GET["plant_id"]."' AND specification_no = '".$escSpec."'");
                            $conn->query("DELETE FROM spec_revision WHERE plant_id = '".$_GET["plant_id"]."' AND specification_no = '".$escSpec."'");

                            $tests = isset($draftData["spectTests"]) && is_array($draftData["spectTests"]) ? $draftData["spectTests"] : Array();
                            for ($i = 0; $i < count($tests); $i++) {
                                $t = $tests[$i];
                                $sqlT = "INSERT INTO spec_tests
                                    (plant_id, testFor, specification_no, test_type, test, test_method_no, subtest, description, reference_type,
                                     limit_type, lower_limit, upper_limit, unit, limits, sample_qty, release_stability, retest, reccSamplingQty,
                                     outside_testing, status, compliances, test_master_id, sto, bulk_release, entryBy, entryOn)
                                    VALUES
                                    ('".$_GET["plant_id"]."',
                                     '".$conn->real_escape_string(isset($t["testFor"]) ? $t["testFor"] : "")."',
                                     '".$escSpec."',
                                     '".$conn->real_escape_string(isset($t["test_type"]) ? $t["test_type"] : "")."',
                                     '".$conn->real_escape_string(isset($t["test"]) ? $t["test"] : "")."',
                                     '".$conn->real_escape_string(isset($t["test_method_no"]) ? $t["test_method_no"] : "")."',
                                     '".$conn->real_escape_string(isset($t["subtest"]) ? $t["subtest"] : "")."',
                                     '".$conn->real_escape_string(isset($t["description"]) ? $t["description"] : "")."',
                                     '".$conn->real_escape_string(isset($t["reference_type"]) ? $t["reference_type"] : "")."',
                                     '".$conn->real_escape_string(isset($t["limit_type"]) ? $t["limit_type"] : "")."',
                                     '".$conn->real_escape_string(isset($t["lower_limit"]) ? $t["lower_limit"] : "")."',
                                     '".$conn->real_escape_string(isset($t["upper_limit"]) ? $t["upper_limit"] : "")."',
                                     '".$conn->real_escape_string(isset($t["unit"]) ? $t["unit"] : "")."',
                                     '".$conn->real_escape_string(isset($t["limits"]) ? $t["limits"] : "")."',
                                     '".$conn->real_escape_string(isset($t["sample_qty"]) ? $t["sample_qty"] : "")."',
                                     '".$conn->real_escape_string(isset($t["release_stability"]) ? $t["release_stability"] : "")."',
                                     '".$conn->real_escape_string(isset($t["retest"]) ? $t["retest"] : "")."',
                                     '".$conn->real_escape_string(isset($t["reccSamplingQty"]) ? $t["reccSamplingQty"] : "")."',
                                     '".$conn->real_escape_string(isset($t["outside_testing"]) ? $t["outside_testing"] : "")."',
                                     'Pending',
                                     '".$conn->real_escape_string(isset($t["compliances"]) ? $t["compliances"] : "")."',
                                     '".$conn->real_escape_string(isset($t["test_master_id"]) ? $t["test_master_id"] : "")."',
                                     '".$conn->real_escape_string(isset($t["sto"]) ? $t["sto"] : "")."',
                                     '".$conn->real_escape_string(isset($t["bulk_release"]) ? $t["bulk_release"] : "")."',
                                     '".$_GET["emp_id"]."',
                                     '".$entry_date."')";
                                $conn->query($sqlT);
                            }

                            $revs = isset($draftData["revisionList"]) && is_array($draftData["revisionList"]) ? $draftData["revisionList"] : Array();
                            for ($j = 0; $j < count($revs); $j++) {
                                $r = $revs[$j];
                                $sqlR = "INSERT INTO spec_revision
                                    (plant_id, specification_no, version_no, change_mode, reason, effective_date, status, entryBy, entryOn)
                                    VALUES
                                    ('".$_GET["plant_id"]."',
                                     '".$escSpec."',
                                     '".$conn->real_escape_string(isset($r["version_no"]) ? $r["version_no"] : "")."',
                                     '".$conn->real_escape_string(isset($r["change_mode"]) ? $r["change_mode"] : "")."',
                                     '".$conn->real_escape_string(isset($r["reason"]) ? $r["reason"] : "")."',
                                     '".$conn->real_escape_string(isset($r["effective_date"]) ? $r["effective_date"] : "")."',
                                     'Pending',
                                     '".$_GET["emp_id"]."',
                                     '".$entry_date."')";
                                $conn->query($sqlR);
                            }

                            $conn->query("UPDATE specification_draft SET status = 'Final Draft', updateBy = '".$_GET["emp_id"]."' WHERE id = '".$draftRow["id"]."'");
                            echo "{\"status\":\"success\"}";
                        }
                    }
                }
            }
        }
    }
    else if ($_GET["type"] == "getSpecificationDraftStatusForSpecifications") {
        $output = Array();
        $raw_specs = isset($_GET["spec_nos"]) ? $_GET["spec_nos"] : '';
        $parts = explode(",", $raw_specs);
        $safe_specs = Array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $safe_specs[] = "'".$conn->real_escape_string($p)."'";
            }
        }
        if (count($safe_specs) === 0) {
            echo json_encode($output);
        } else {
            $in = implode(",", $safe_specs);
            $sql = "SELECT id, specification_no, draft_version_no, updateOn, status
                    FROM specification_draft
                    WHERE plant_id = '".$_GET["plant_id"]."'
                      AND specification_no IN ($in)
                    ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $spec_no = $row["specification_no"];
                    if (!isset($output[$spec_no])) {
                        $output[$spec_no] = $row;
                    }
                }
            }
            echo json_encode($output);
        }
    }
    else if ($_GET["type"] == "getObsoleteSpecificationLogs") {
        $output = Array();
        $sqlCreateObs = "CREATE TABLE IF NOT EXISTS specification_obsolete_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) NOT NULL,
            specification_no VARCHAR(100) NOT NULL,
            previous_version_no VARCHAR(20) DEFAULT NULL,
            previous_spec_data LONGTEXT,
            linked_ctrl_no VARCHAR(50) DEFAULT NULL,
            linked_ctrl_status VARCHAR(50) DEFAULT NULL,
            entryBy VARCHAR(50) DEFAULT NULL,
            entryOn DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($sqlCreateObs);

        $sql = "SELECT * FROM specification_obsolete_log
                WHERE plant_id = '".$_GET["plant_id"]."'
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $specNo = $row["specification_no"];
                $sqlCC = "SELECT ctrl_no, status, entryDate
                          FROM changecontrol
                          WHERE plant_id = '".$_GET["plant_id"]."'
                            AND (titleOfcc LIKE 'Specification Revision - ".$conn->real_escape_string($specNo)."%'
                                 OR proposed LIKE '%Specification: ".$conn->real_escape_string($specNo)."%')
                          ORDER BY id DESC
                          LIMIT 1";
                $resCC = $conn->query($sqlCC);
                $cc = null;
                if ($resCC && $resCC->num_rows > 0) {
                    $cc = $resCC->fetch_assoc();
                }
                $ccStatus = strtolower(trim(isset($cc["status"]) ? $cc["status"] : ""));
                if (in_array($ccStatus, Array("approve", "approved", "complete", "closed", "close"))) {
                    $row["linked_ctrl_no"] = $cc["ctrl_no"];
                    $row["linked_ctrl_status"] = $cc["status"];
                    $row["cc_entry_date"] = $cc["entryDate"];
                    $row["previous_spec_data"] = json_decode($row["previous_spec_data"], true);
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getObsoleteSpecificationById") {
        $output = Array();
        $id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
        if ($id <= 0) {
            echo json_encode($output);
        } else {
            $sql = "SELECT * FROM specification_obsolete_log
                    WHERE plant_id = '".$_GET["plant_id"]."'
                      AND id = '".$id."'
                    LIMIT 1";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $row["previous_spec_data"] = json_decode($row["previous_spec_data"], true);
                $output = $row;
            }
            echo json_encode($output);
        }
    }
    else if ($_GET["type"] == "saveSpecificationCcEditHistory") {
        $sqlCreateHistory = "CREATE TABLE IF NOT EXISTS specification_cc_edit_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) NOT NULL,
            specification_no VARCHAR(100) NOT NULL,
            draft_particular VARCHAR(255) DEFAULT NULL,
            linked_ctrl_no VARCHAR(50) DEFAULT NULL,
            linked_ctrl_status VARCHAR(80) DEFAULT NULL,
            rejected_from_department VARCHAR(120) DEFAULT NULL,
            rejected_by VARCHAR(80) DEFAULT NULL,
            entryBy VARCHAR(50) DEFAULT NULL,
            entryOn DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($sqlCreateHistory);

        $specNo = isset($input["specification_no"]) ? trim($input["specification_no"]) : '';
        $draftParticular = isset($input["draft_particular"]) ? trim($input["draft_particular"]) : '';
        $ctrlNo = isset($input["ctrl_no"]) ? trim($input["ctrl_no"]) : '';
        $ctrlStatus = isset($input["ctrl_status"]) ? trim($input["ctrl_status"]) : '';
        if ($specNo == '') {
            echo "{\"status\":\"failed\"}";
        } else {
            $sql = "INSERT INTO specification_cc_edit_history
                    (plant_id, specification_no, draft_particular, linked_ctrl_no, linked_ctrl_status, rejected_from_department, rejected_by, entryBy)
                    VALUES
                    ('".$_GET["plant_id"]."',
                     '".$conn->real_escape_string($specNo)."',
                     '".$conn->real_escape_string($draftParticular)."',
                     '".$conn->real_escape_string($ctrlNo)."',
                     '".$conn->real_escape_string($ctrlStatus)."',
                     '-',
                     '-',
                     '".$_GET["emp_id"]."')";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        }
    }
    else if ($_GET["type"] == "getSpecificationCcEditHistory") {
        $output = Array();
        $sqlCreateHistory = "CREATE TABLE IF NOT EXISTS specification_cc_edit_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) NOT NULL,
            specification_no VARCHAR(100) NOT NULL,
            draft_particular VARCHAR(255) DEFAULT NULL,
            linked_ctrl_no VARCHAR(50) DEFAULT NULL,
            linked_ctrl_status VARCHAR(80) DEFAULT NULL,
            rejected_from_department VARCHAR(120) DEFAULT NULL,
            rejected_by VARCHAR(80) DEFAULT NULL,
            entryBy VARCHAR(50) DEFAULT NULL,
            entryOn DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($sqlCreateHistory);

        $specNo = isset($_GET["specification_no"]) ? trim($_GET["specification_no"]) : '';
        if ($specNo == '') {
            echo json_encode($output);
        } else {
            $escSpec = $conn->real_escape_string($specNo);
            $sqlCC = "SELECT id, ctrl_no, status, department_name, check_by, qaReviewedBy, concernHodBy
                      FROM changecontrol
                      WHERE plant_id = '".$_GET["plant_id"]."'
                        AND (titleOfcc LIKE 'Specification Revision - ".$escSpec."%'
                             OR proposed LIKE '%Specification: ".$escSpec."%')
                      ORDER BY id DESC
                      LIMIT 1";
            $resCC = $conn->query($sqlCC);
            if ($resCC && $resCC->num_rows > 0) {
                $cc = $resCC->fetch_assoc();
                $ccStatus = strtolower(trim($cc["status"]));
                $isRejected = (strpos($ccStatus, 'reject') !== false || strpos($ccStatus, 'back') !== false);
                $isApproved = in_array($ccStatus, Array("approve", "approved", "complete", "closed", "close"));
                if ($isApproved) {
                    // After final approval remove/hide edit history.
                    echo json_encode(Array());
                    return;
                }
                if ($isRejected) {
                    $ctrlNo = isset($cc["ctrl_no"]) ? $cc["ctrl_no"] : '';
                    $sqlExists = "SELECT id FROM specification_cc_edit_history
                                  WHERE plant_id = '".$_GET["plant_id"]."'
                                    AND specification_no = '".$escSpec."'
                                    AND linked_ctrl_no = '".$conn->real_escape_string($ctrlNo)."'
                                    AND (LOWER(linked_ctrl_status) LIKE '%reject%' OR LOWER(linked_ctrl_status) LIKE '%back%')
                                  ORDER BY id DESC LIMIT 1";
                    $resEx = $conn->query($sqlExists);
                    if (!($resEx && $resEx->num_rows > 0)) {
                        $rejectedBy = $cc["check_by"];
                        if ($rejectedBy == '' || $rejectedBy == null) {
                            $rejectedBy = $cc["qaReviewedBy"];
                        }
                        if ($rejectedBy == '' || $rejectedBy == null) {
                            $rejectedBy = $cc["concernHodBy"];
                        }
                        $rejDept = isset($cc["department_name"]) ? $cc["department_name"] : 'Department';
                        $sqlIns = "INSERT INTO specification_cc_edit_history
                                   (plant_id, specification_no, draft_particular, linked_ctrl_no, linked_ctrl_status, rejected_from_department, rejected_by, entryBy)
                                   VALUES
                                   ('".$_GET["plant_id"]."',
                                    '".$escSpec."',
                                    'Change Control sent back for draft rework',
                                    '".$conn->real_escape_string($ctrlNo)."',
                                    '".$conn->real_escape_string($cc["status"])."',
                                    '".$conn->real_escape_string($rejDept)."',
                                    '".$conn->real_escape_string($rejectedBy)."',
                                    '".$_GET["emp_id"]."')";
                        $conn->query($sqlIns);
                    }
                }
            }

            $sql = "SELECT * FROM specification_cc_edit_history
                    WHERE plant_id = '".$_GET["plant_id"]."'
                      AND specification_no = '".$escSpec."'
                    ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
    }
    else if ($_GET["type"] == "getRejectedSPecificationByTypeAndStatus" || $_GET["type"] == "getRejectedSpecifications") {
        
        
            if($_GET["spec_type"] == 'Finish Product'){
                $sql = "SELECT a.*,b.product_name as material_name FROM specification a LEFT JOIN product b ON a.material_code = b.product_code WHERE a.status = 'Rejected' AND a.plant_id = '".$_GET["plant_id"]."' AND a.spec_type = '".$_GET["spec_type"]."' ";
            }else{
                $sql = "SELECT a.*,b.material_name FROM specification a LEFT JOIN material b ON a.material_code = b.material_code WHERE a.status = 'Rejected' AND a.plant_id = '".$_GET["plant_id"]."' AND a.spec_type = '".$_GET["spec_type"]."' ";
            }
        
        	$result = $conn->query($sql);
        	if($result->num_rows > 0){
        		$output = Array();
        		while($row = $result->fetch_assoc()){
        		    
        		    
                        $sql1 = "SELECT * FROM spec_tests WHERE plant_id = '".$_GET["plant_id"]."' AND specification_no = '".$row["specification_no"]."' ";
                    	$result1 = $conn->query($sql1);
                    	if($result1->num_rows > 0){
                    		$output1 = Array();
                    		while($row1 = $result1->fetch_assoc()){
                    			$output1[] = $row1;
                    		}
                    	}
                    	
                        $sql2 = "SELECT * FROM spec_revision WHERE plant_id = '".$_GET["plant_id"]."' AND specification_no = '".$row["specification_no"]."' ";
                    	$result2 = $conn->query($sql2);
                    	if($result2->num_rows > 0){
                    		$output2 = Array();
                    		while($row2 = $result2->fetch_assoc()){
                    			$output2[] = $row2;
                    		}
                    	}
                    	
                    $row['spectTests'] = $output1;
                    $row['revisionList'] = $output2;
        		     
        			$output[] = $row;
        		}
        	}
        	
            echo json_encode($output);
            
    }
    else if ($_GET["type"] == "saveCorrectionSpecification") {
        $id = isset($input["id"]) ? intval($input["id"]) : 0;
        if ($id <= 0) {
            echo "{\"status\":\"failed\",\"message\":\"Invalid specification id\"}";
        } else {
            $totalsample = $conn->real_escape_string(isset($input["totalsample_qty"]) ? $input["totalsample_qty"] : "");
            $reviewDate = $conn->real_escape_string(isset($input["review_date"]) ? $input["review_date"] : "");
            $retestPeriod = $conn->real_escape_string(isset($input["retest_period"]) ? $input["retest_period"] : "");
            $storage = $conn->real_escape_string(isset($input["storage_condition"]) ? $input["storage_condition"] : "");
            $samplingDetails = $conn->real_escape_string(isset($input["samplingDetails"]) ? $input["samplingDetails"] : "");
            $hazard = $conn->real_escape_string(isset($input["hazardAndPrecautions"]) ? $input["hazardAndPrecautions"] : "");
            $note = $conn->real_escape_string(isset($input["note"]) ? $input["note"] : "");
            $specNo = $conn->real_escape_string(isset($input["specification_no"]) ? $input["specification_no"] : "");

            $sql = "UPDATE specification SET
                status = 'Pending',
                totalsample_qty = '".$totalsample."',
                review_date = '".$reviewDate."',
                retest_period = '".$retestPeriod."',
                storage_condition = '".$storage."',
                samplingDetails = '".$samplingDetails."',
                hazardAndPrecautions = '".$hazard."',
                note = '".$note."',
                check_by = '',
                check_date = NULL,
                approve_by = '',
                approve_date = NULL
                WHERE id = '".$id."' AND plant_id = '".$_GET["plant_id"]."' AND status = 'Rejected'";

            if ($conn->query($sql)) {
                $tests = isset($input["spectTests"]) && is_array($input["spectTests"]) ? $input["spectTests"] : Array();
                for ($i = 0; $i < count($tests); $i++) {
                    $t = $tests[$i];
                    if (!isset($t["id"]) || intval($t["id"]) <= 0) {
                        continue;
                    }
                    $testId = intval($t["id"]);
                    $limits = $conn->real_escape_string(isset($t["limits"]) ? $t["limits"] : "");
                    $retest = $conn->real_escape_string(isset($t["retest"]) ? $t["retest"] : "");
                    $conn->query("UPDATE spec_tests SET limits = '".$limits."', retest = '".$retest."', status = 'Pending'
                        WHERE id = '".$testId."' AND plant_id = '".$_GET["plant_id"]."' AND specification_no = '".$specNo."'");
                }
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        }
    }
 
    else if ($_GET["type"] == "saveRevisionRequest") {
        $export = "no";
        $domastic = "no";
    	if ($input["export"] == true) {
    	    $market_details = "yes";
    	}
    	if ($input["domastic"] == true) {
    	    $market_details = "yes";
        }
        
        $product = Array();
        if ($input["impact_quality"] == "Yes") {
            $product["product_name"] = $input["product_name"];
            $product["batch_no"] = $input["batch_no"];
        }
    	
        $sql = "INSERT INTO changecontrol (user_no,  department,change_related,change_title,existing_procedure,proposed_change,change_reason, export, domastic, impact_product, product_details, entry_by, entry_date)
        VALUES ('".$_GET["user_no"]."', '".$_GET["department"]."','".$input["change_related"]."','".$input["change_title"]."','".$input["existing_procedure"]."','".$input["proposed_change"]."','".$input["reason_for_changes"]."','".$export."','$domastic', '".$input["impact_quality"]."', '".json_encode($product)."', '".$_GET["emp_id"]."', '".$entry_date."')";
    	if($conn->query($sql)) {
    	    $last_id = $conn->insert_id;
    	    $sql1 = "SELECT ctrl_no FROM changecontrol WHERE id='$last_id'";
    	    $result1 = $conn->query($sql1);
    	    $row1 = $result1->fetch_assoc();
    	    $ctrl_no = $row1["ctrl_no"];
    	    $data = $input["departments"];
            for ($i = 0; $i < count($data); $i++) {
                $sql = "INSERT INTO change_comments (ctrl_no,department) VALUES ('$ctrl_no','".$data[$i]."')";
                $conn->query($sql);
            }
    
            $sql = "INSERT INTO pendingdocument (entry_id, form, formname, purpose, department, entry_by, entry_date, checker, approver) VALUES ('$ctrl_no', 'changecontrol', 'Change Control', 'Checking', '".$_GET["department"]."', '".$_GET["emp_id"]."', '$entry_date', 'true', 'true')";
            $conn->query($sql);
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPeriodicRevisions") {
        $output = Array();
        $sql = "SELECT s.*, DATE(s.approve_date) as approve_date, m.material_type, m.material_subtype, 
        m.material_name, m.grade FROM specification s LEFT JOIN material m ON s.material_code=m.material_code 
        WHERE  s.spec_type LIKE '%Raw Material%' AND s.status='approve' order by 1 desc";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM changecontrol"; 
                //WHERE  document_no='".$row["specification_no"]."' AND version_no='".$row["version_no"]."' AND status NOT IN ('reject', 'close')";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["change_title"] = $row1["change_title"];
                        $row["existing_procedure"] = $row1["existing_procedure"];
                        $row["proposed_change"] = $row1["proposed_change"];
                        $row["change_reason"] = $row1["change_reason"];
                        
                        $output1 = Array();
                        $sql2 = "SELECT * FROM change_comments WHERE ctrl_no='".$row1["ctrl_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result1->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output1[] = $row2;
                            }
                        }
                        $row["departments"] = $output1;
                    }
                }
                    $output1 = Array();
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['tests'] = $output1;
                    
                    $output1 = Array();
                    $sql1 = "SELECT * FROM spec_revision WHERE  spec_no='".$row["specification_no"]."'";
                    
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    $output[] = $row;
                /*}
                else{
                     $output[] = $row;
                }*/
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingRevisions") {
        $output = Array();
        $sql = "SELECT s.*, DATE(s.approve_date) as approve_date, m.material_type, m.material_subtype, m.material_name, m.grade FROM specification s LEFT JOIN material m ON s.material_code=m.material_code WHERE s.user_no='".$_GET["user_no"]."' AND s.spec_type LIKE 'Raw Material%' AND s.status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM changecontrol WHERE user_no='".$_GET["user_no"]."' AND document_no='".$row["specification_no"]."' AND version_no='".$row["version_no"]."' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $output1 = Array();
                    $sql1 = "SELECT * FROM spec_tests WHERE user_no='".$_GET["user_no"]."' AND specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['tests'] = $output1;
                    
                    $output1 = Array();
                    $sql1 = "SELECT * FROM spec_revision WHERE user_no='".$_GET["user_no"]."' AND spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveEditSpecification") {
    	
    	$version_no = +$input["version_no"];
    	$version_no++;
    	if (strlen($version_no) == 1) {
    	    $version_no = "0".$version_no;
    	}
    	
    	$sql = "INSERT INTO specification (user_no, spec_type, material_code, specification_no, version_no, supersede_no, sample_qty, micro_qty, shelf_life, storage, safety_precaution, entry_by, entry_date, unit, review_date, retest_period) VALUES ('".$_GET["user_no"]."','Raw Material Specification','".$input["material_code"]."','".$input["specification_no"]."','$version_no','".$input["supersede_no"]."','".$input["sample_qty"]."','".$input["micro_qty"]."','".$input["shelf_life"]."','".$input["storage"]."','".$input["safety_precaution"]."','".$_GET["emp_id"]."','".$entry_date."','".$input["unit"]."', '".$input["next_review_date"]."', '".$input["retest_period"]."')";
    	if($conn->query($sql)) {
    	    
    	    $sql1 = "UPDATE changecontrol SET status='close', close_by='".$_GET["emp_id"]."', close_date='$entry_date' WHERE document_no='".$input["specification_no"]."' AND version_no='".$input["version_no"]."'";
    	    $conn->query($sql1);
    	    
    		$tests = $input["tests"];
    		$len = count($tests);
    		for($i = 0; $i<$len; $i++) {
    			$data = $tests[$i];
    			
    			$limit = "";
    			if($data["limit"] == 'Limits'){
    				$data["lessthan"] = ''; 
    				$data["morethan"] = '';
    				
    				$limit = $data["lower_limit"] ." ". $data["unit"] . " to " . $data["upper_limit"] ." ". $data["unit"];
    			}else if($data["limit"] == 'LessThan'){
    				$data["lower_limit"] = ''; 
    				$data["upper_limit"] = ''; 
    				$data["morethan"] = '';
    				$limit = "NMT ".$data["lessthan"];
    			}else if($data["limit"] == 'MoreThan'){
    				$data["lower_limit"] = ''; 
    				$data["upper_limit"] = ''; 
    				$data["lessthan"] = ''; 
    				$limit = "NMT ".$data["morethan"];
    			}else if($data["limit"] == 'Compliances'){
    				$data["lower_limit"] = ''; 
    				$data["upper_limit"] = ''; 
    				$data["lessthan"] = '';
    				$data["morethan"] = '';
    				$limit = "Complies";
    			}
    			if ($data['retest_applicable'] == true) {
    			    $data['retest_applicable'] = 'yes';
    			} else {
    			    $data['retest_applicable'] = 'no';
    			}
    			$sql="INSERT INTO spec_tests (user_no, specification_no, version_no, test, subtest,description,reference_type,limit_type,lower_limit,upper_limit,lessthan,morethan, unit, sample_qty, retest, for_micro, limits) VALUES ('".$_GET["user_no"]."','".$input["specification_no"]."', '$version_no','".$data["test"]."','".$data["subtest"]."','".$data["description"]."','".$data["ref_type"]."','".$data["limit"]."','".$data["lower_limit"]."','".$data["upper_limit"]."','".$data["lessthan"]."','".$data["morethan"]."', '".$data["unit"]."', '".$data["sample_qty"]."', '".$data["retest_applicable"]."', '".$data["for_micro"]."', '$limit')";
    			$conn->query($sql);
    	    }
    	    
    	   $sql = "INSERT INTO spec_revision (user_no, spec_no, specification_no, version_no, change_mode, reason, effective_date) VALUES ('".$_GET["user_no"]."','".$input["specification_no"]."','".$input["specification_no"]."','".$input["version_no"]."','".$input["change_mode"]."','".$input["change_reason"]."', '".$input["effective_date"]."')";
    	   $conn->query($sql);
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    
    else if($_GET['type'] == 'SpecificationDigitalFGPDF') {
        
           $sql = "SELECT s.* ,m.product_name, m.grade,m.storage_condition as
         material_storage_conditions,p.plant_name,p.plant_full_address FROM specification s LEFT JOIN product m 
         ON s.product_code=m.product_code LEFT JOIN plant p on s.plant_id=p.plant_id WHERE 
         specification_no='".$_GET['specification_no']."' LIMIT 1";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        // $_GET['filename'] = 'RAW MATERIAL SPECIFICATION'; 
        // $_GET['pdftype'] = 'headfootdigital'; include("../../pdfimp.php");
        $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        
        
        $html.='
        <h3 style="text-align:center; ">Finish Product SPECIFICATION</h3>
        <style>
            .tdall { border:solid 1px BCBBBA; }
            .tdb { border-bottom:solid 1px BCBBBA; }
            .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
        </style><br><br><br>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <tr>
                <td class="tdb" style="width:15%;"><b>Department</b></td>
                <td class="tdb" style="width:85%;">: <b>Quality Control</b></td>
            </tr>
            <tr>
                <td class="tdb"><b>Product Name</b></td>
                <td class="tdb">: '.$row["product_name"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Specification No</b></td>
                <td class="tdbr" style="width:45%;">: '.$row['specification_no'].'</td>
                <td class="tdb" style="width:15%;"><b>Product Code</b></td>
                <td class="tdb" style="width:25%;">: '.$row["product_code"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Supersedes No</b></td>
                <td class="tdb">: NA</td>
                <td class="tdb"><b>Review Date</b></td>';
                
                if($row['review_date']=='0000-00-00'){
                  $html.='  <td class="tdb">: NA</td> ';
                }else{
                $html.='  <td class="tdb">: '.date('d-m-Y',strtotime($row['review_date'])).'</td> ';

                }
                
            $html.='    </tr>
            <tr>
                <td class="tdb"><b>Retest Period</b></td>
                <td class="tdbr">: 24 Months 0 Days</td>
                <td class="tdb"><b>Page No</b></td>
                <td class="tdb">:  1 Of 1 </td>
                
            </tr>
            
            <tr>
                <td><b>Storage Condition</b></td>
                <td colspan="3">: '.$row['material_storage_conditions'].'.</td>
                 
            </tr>
        </table>
        <div></div>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <thead>
                <tr style="background-color:#e8e6e6;font-weight:bold;" align="center">
                    <td class="tdall" style="width:7%">Sr.</td>
                    <td class="tdall" style="width:31%">Test</td>
                    <td class="tdall" style="width:31%">Specification</td>
                    <td class="tdall" style="width:31%">Reference</td>
                </tr>
            </thead>
            <tbody>';
            $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j =1;
            while ($row1 = $result1->fetch_assoc()) {
                $html.='
                <tr>
                    <td class="tdall" style="width:7%" align="center;">'.$j++.'.</td>
                    <td class="tdall" style="width:31%">'.$row1["test"].'</td>
                    <td class="tdall" style="width:31%">'.$row1["limits"].'</td>
                    <td class="tdall" style="width:31%">'.$row1["reference_type"].'</td>
                </tr>';
            }
           
          $html.='
        </table>
        <p style="text-align:center;"><b>REVISION HISTORY</b></p>
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9; text-align:center;">
                <td class="tdall">Specification No.</td>
                <td class="tdall">Revision No.</td>
                <td class="tdall">Change Made</td>
                <td class="tdall">Reasons for change</td>
                <th class="tdall"> Effective Date </th>
            </tr>';
            $sql2 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result2 = $conn->query($sql2);
            $output2 = Array();
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $html.='
                    <tr nobr="true">
                        <td class="tdall">'.$row["specification_no"].'</td>
                        <td class="tdall">'.$row2["specification_no"].'</td>
                        <td class="tdall">'.$row2["change_mode"].'</td>
                        <td class="tdall">'.$row2["reason"].'</td>
                        <td class="tdall">'.date('d-m-Y',strtotime($row2['effective_date'])).'</td>
                        
                    </tr>';
                }
            }
            $html.='
        </table>
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
                  <td style="width:191px;">:' . $row['entry_by'] . '</td>
                 
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
                  <td style="width:70px;">:' . date('d-m-Y', strtotime($row['entry_date'])) . '</td>
                  <td style="width: 50px;">Time</td>
                  <td style="width: 81px;">:' . date('H:i:s', strtotime($row['entry_date'])) . '</td>
              </tr>
              <br>
          <tr>
          <td style="width: 270px;font-size:10px;">For, '.$row['plant_name'].' </td>
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
          <td style="width: 265px;font-size:10px;">For, '.$row['plant_name'].' </td>
          </tr>
          </table>
      </td>
  </tr>
  <tr>
              <td style="width: 540px;text-align:center">' . $row['plant_full_address'] . '</td>
              </tr>
</table>
        ';
            
    
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    
        
    }
    else if($_GET['type'] == 'SpecificationFGPDF') {
         //saipro
        
            $sql = "SELECT s.* ,m.product_name, m.grade,m.storage_condition as
         material_storage_conditions,p.plant_name,p.plant_full_address FROM specification s LEFT JOIN product m 
         ON s.product_code=m.product_code LEFT JOIN plant p on s.plant_id=p.plant_id WHERE 
         specification_no='".$_GET['specification_no']."' LIMIT 1";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        // $_GET['filename'] = 'RAW MATERIAL SPECIFICATION'; 
        // $_GET['pdftype'] = 'headfootdigital'; include("../../pdfimp.php");
        $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        
        
        $html.='
        <h3 style="text-align:center; ">Finish Product SPECIFICATION</h3>
        <style>
            .tdall { border:solid 1px BCBBBA; }
            .tdb { border-bottom:solid 1px BCBBBA; }
            .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
        </style><br><br><br>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <tr>
                <td class="tdb" style="width:15%;"><b>Department</b></td>
                <td class="tdb" style="width:85%;">: <b>Quality Control</b></td>
            </tr>
            <tr>
                <td class="tdb"><b>Product Name</b></td>
                <td class="tdb">: '.$row["product_name"].' </td>
            </tr>
            <tr>
                <td class="tdb"><b>Specification No</b></td>
                <td class="tdbr" style="width:45%;">: '.$row['specification_no'].'</td>
                <td class="tdb" style="width:15%;"><b>Product Code</b></td>
                <td class="tdb" style="width:25%;">: '.$row["material_code"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Supersedes No</b></td>
                <td class="tdb">: NA</td>
                <td class="tdb"><b>Review Date</b></td>';
                
                if($row['review_date']=='0000-00-00'){
                  $html.='  <td class="tdb">: NA</td> ';
                }else{
                $html.='  <td class="tdb">: '.date('d-m-Y',strtotime($row['review_date'])).'</td> ';

                }
                
            $html.='    </tr>
            <tr>
                <td class="tdb"><b>Retest Period</b></td>
                <td class="tdbr">: 24 Months 0 Days</td>
                <td class="tdb"><b>Page No</b></td>
                <td class="tdb">:  1 Of 1 </td>
                
            </tr>
            
            <tr>
                <td><b>Storage Condition</b></td>
                <td colspan="3">: '.$row['material_storage_conditions'].'.</td>
                 
            </tr>
        </table>
        <div></div>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <thead>
                <tr style="background-color:#e8e6e6;font-weight:bold;" align="center">
                    <td class="tdall" style="width:7%">Sr.</td>
                    <td class="tdall" style="width:31%">Test</td>
                    <td class="tdall" style="width:31%">Specification</td>
                    <td class="tdall" style="width:31%">Reference</td>
                </tr>
            </thead>
            <tbody>';
            $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j =1;
            while ($row1 = $result1->fetch_assoc()) {
                $html.='
                <tr>
                    <td class="tdall" style="width:7%" align="center;">'.$j++.'.</td>
                    <td class="tdall" style="width:31%">'.$row1["test"].'</td>
                    <td class="tdall" style="width:31%">'.$row1["limits"].'</td>
                    <td class="tdall" style="width:31%">'.$row1["reference_type"].'</td>
                </tr>';
            }
           
          $html.='
        </table>
        <p style="text-align:center;"><b>REVISION HISTORY</b></p>
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9; text-align:center;">
                <td class="tdall">Specification No.</td>
                <td class="tdall">Version No.</td>
                <td class="tdall">Change Made</td>
                <td class="tdall">Reasons for change</td>
                <th class="tdall"> Effective Date </th>
            </tr>';
            $sql2 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result2 = $conn->query($sql2);
            $output2 = Array();
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $html.='
                    <tr nobr="true">
                        <td class="tdall">'.$row2["specification_no"].'</td>
                        <td class="tdall">'.$row2["version_no"].'</td>
                        <td class="tdall">'.$row2["change_mode"].'</td>
                        <td class="tdall">'.$row2["reason"].'</td>
                        <td class="tdall">'.date('d-m-Y',strtotime($row2['effective_date'])).'</td>
                        
                    </tr>';
                }
            }
            $html.=' </table> ';
            
    
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    
        
        
        
        
    
    }
    else if($_GET['type'] == 'SpecificationPDF') {
        
        if($_GET["plant_id"] == 28) {//demo
            
                        $sql = "SELECT s.* ,m.material_type, m.material_subtype, m.material_name, m.grade,m.storage_condition as
                     material_storage_conditions,p.plant_name,p.plant_full_address FROM specification s LEFT JOIN material m 
                     ON s.material_code=m.material_code LEFT JOIN plant p on s.plant_id=p.plant_id WHERE 
                     specification_no='".$_GET['specification_no']."' LIMIT 1";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                    // $_GET['filename'] = 'RAW MATERIAL SPECIFICATION'; 
                    // $_GET['pdftype'] = 'headfootdigital'; include("../../pdfimp.php");
                    $_GET['pdftype'] = 'onlyheader'; include("./pdfimp2.php");
                    
                   $html.='
                    <h3 style="text-align:center; ">RAW MATERIAL SPECIFICATION </h3>
                    <style>
                        .tdall { border:solid 1px BCBBBA; }
                        .tdb { border-bottom:solid 1px BCBBBA; }
                        .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
                    </style><br><br><br>
                    <table style="border:solid 1px BCBBBA;" cellpadding="2">
                        <tr>
                            <td class="tdb" style="width:15%;"><b>Department</b></td>
                            <td class="tdb" style="width:85%;">: <b>Quality Control</b></td>
                        </tr>
                        <tr>
                            <td class="tdb"><b>Material Name</b></td>
                            <td class="tdb">: '.$row["material_name"].'</td>
                        </tr>
                        <tr>
                            <td class="tdb"><b>Specification No</b></td>
                            <td class="tdbr" style="width:45%;">: '.$row['specification_no'].'</td>
                            <td class="tdb" style="width:15%;"><b>Material Code</b></td>
                            <td class="tdb" style="width:25%;">: '.$row["material_code"].'</td>
                        </tr>
                        <tr>
                            <td class="tdb"><b>Supersedes No</b></td>
                            <td class="tdb">: NA</td>
                            <td class="tdb"><b>Review Date</b></td>';
                            
                            if($row['review_date']=='0000-00-00'){
                              $html.='  <td class="tdb">: NA</td> ';
                            }else{
                            $html.='  <td class="tdb">: '.date('d-m-Y',strtotime($row['review_date'])).'</td> ';
            
                            }
                            
                        $html.='    </tr>
                        <tr>
                            <td class="tdb"><b>Retest Period</b></td>
                            <td class="tdbr">: 24 Months 0 Days</td>
                            <td class="tdb"><b>Page No</b></td>
                            <td class="tdb">:  1 Of 1 </td>
                            
                        </tr>
                        
                        <tr>
                            <td><b>Storage Condition</b></td>
                            <td colspan="3">: '.$row['material_storage_conditions'].'.</td>
                             
                        </tr>
                    </table>
                    <div></div>
                    <table style="border:solid 1px BCBBBA;" cellpadding="2">
                        <thead>
                            <tr style="background-color:#e8e6e6;font-weight:bold;" align="center">
                                <td class="tdall" style="width:7%">Sr.No</td>
                                <td class="tdall" style="width:31%">Test</td>
                                <td class="tdall" style="width:31%">Specification</td>
                                <td class="tdall" style="width:31%">Reference</td>
                            </tr>
                        </thead>
                        <tbody>';
                        $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                        $result1 = $conn->query($sql1);
                        $j =1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td class="tdall" style="width:7%" align="center;">'.$j++.'.</td>
                                <td class="tdall" style="width:31%">'.$row1["test"].'</td>
                                <td class="tdall" style="width:31%">'.$row1["limits"].'</td>
                                <td class="tdall" style="width:31%">'.$row1["reference_type"].'</td>
                            </tr>';
                        }
                       
                      $html.='
                    </table>
                    <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                    <table cellpadding="5">
                        <tr style="background-color:#DDDAD9; text-align:center;">
                            <td class="tdall">Specification No.</td>
                            <td class="tdall">Revision No.</td>
                            <td class="tdall">Change Made</td>
                            <td class="tdall">Reasons for change</td>
                            <th class="tdall"> Effective Date </th>
                        </tr>';
                        $sql2 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $html.='
                                <tr nobr="true">
                                    <td class="tdall">'.$row["specification_no"].'</td>
                                    <td class="tdall">'.$row2["specification_no"].'</td>
                                    <td class="tdall">'.$row2["change_mode"].'</td>
                                    <td class="tdall">'.$row2["reason"].'</td>
                                    <td class="tdall">'.date('d-m-Y',strtotime($row2['effective_date'])).'</td>
                                    
                                </tr>';
                            }
                        }
                        $html.='
                    </table>
                      
                   
                      </table>
                      
                  </td>
                  <td style="width: 270px;">
                      
                  </td>
              </tr>
            
            </table>
                    ';
                        
                
                    // EOD;
                    $pdf->writeHTML($html, true, false, false, false, '');
                    $pdf->Output('Specification.pdf', 'I');
                
        }
    else  if($_GET["plant_id"] == 86) { //Synth
               $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade,p.plant_id,m.packing_requirement,v.vendor_name,p.logo_path, m.cas_no, m.category,e.emp_id,e.designation FROM specification s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN plant p ON m.plant_id = p.plant_id left join vendor v on m.material_code = v.material_code LEFT JOIN employee e on s.entry_by = e.emp_id WHERE specification_no='".$_GET['specification_no']."' LIMIT 1";
        	$result = $conn->query($sql);
            $row = $result->fetch_assoc();{
        
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
 
        $html.='
        
 <table border="1">
    <tr>
    <td style="width: 135px;"> Department Name </td>
    <td style="width: 135px;">  Quality Control</td>
    <td style="width: 135px;"> Material Code</td>
    <td style="width: 135px;"> '.$row["material_code"].' </td>
    </tr>
    <tr>
        <td style="width: 135px;"> Name of Material </td>
        <td style="width: 135px;"> '.$row["material_name"].'</td>
        <td style="width: 135px;"> Specification No.</td>
        <td style="width: 135px;"> '.$row["specification_no"].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Reference </td>
        <td style="width: 135px;"> '.$row["reference"].'</td>
        <td style="width: 135px;"> Supersede No.</td>
        <td style="width: 135px;"> '.$row["supersede_no"].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Effective Date </td>
        <td style="width: 135px;"> '.$row["effective_date"].'</td>
        <td style="width: 135px;"> Review Date</td>
        <td style="width: 135px;"> '.$row["review_date"].'</td>
    </tr>
</table>
<div></div>
<table border="1">
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> Sr. No.</td>
        <td style="font-size:10px;width: 150px;"> Particulars</td>
        <td style="text-align:center;font-size:10px;width: 350px;"> Details</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 1</td>
        <td style="font-size:10px;width: 150px;"> Chemical Name</td>
        <td style="font-size:10px;width: 350px;"> '.$row["chemical_name"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 2</td>
        <td style="font-size:10px;width: 150px;"> Category</td>
        <td style="font-size:10px;width: 350px;"> '.$row["category"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 3</td>
        <td style="font-size:10px;width: 150px;"> Sample Qty.</td>
        <td style="font-size:10px;width: 350px;"> '.$row["sample_qty"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 4</td>
        <td style="font-size:10px;width: 150px;"> Shelf Life</td>
        <td style="font-size:10px;width: 350px;"> '.$row["shelf_life"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 5</td>
        <td style="font-size:10px;width: 150px;"> CAS No.</td>
        <td style="font-size:10px;width: 350px;"> '.$row["cas_no"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 6</td>
        <td style="font-size:10px;width: 150px;"> Molecular Formula</td>
        <td style="font-size:10px;width: 350px;"> '.$row["molecular_formula"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 7</td>
        <td style="font-size:10px;width: 150px;"> Structural Formula</td>
        <td style="font-size:10px;width: 350px;"></td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 8</td>
        <td style="font-size:10px;width: 150px;"> Retest Period</td>
        <td style="font-size:10px;width: 350px;"> '.$row["retest_period"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 9</td>
        <td style="font-size:10px;width: 150px;">Storage Condition</td>
        <td style="font-size:10px;width: 350px;"> '.$row["storage_condition"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 10</td>
        <td style="font-size:10px;width: 150px;"> Safety Precautions</td>
        <td style="font-size:10px;width: 350px;"> '.$row["safety_precaution"].'</td>
    </tr>
</table>

<h3 style="text-align:center;">Specification Test</h3>
<table border="1">
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> Sr. No.</td>
        <td style="font-size:10px;width: 150px;"> Test</td>
        <td style="text-align:center;font-size:10px;width: 350px;"> Specification</td>
    </tr>
    ';
     $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j =1;
            while ($row1 = $result1->fetch_assoc()) {
    $html.='
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> '.$j++.'</td>
        <td style="font-size:10px;width: 150px;"> '.$row1["test"].'</td>
        <td style="font-size:10px;width: 350px;"> '.$row1['description'].'</td>
    </tr>';

            }
$html.='</table>
<h3 style="text-align:center;">Revision History</h3>
        <table border="1">
    <tr>
        <td style="text-align:center;width: 100px;">Specification No.</td>
        <td style="text-align:center;width: 50px;">Revision No.</td>
        <td style="text-align:center;width: 300px;">Revision Description</td>
        <td style="text-align:center;width: 90px;">Effective Date</td>
    </tr>';
      $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j=1;
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
    $html.='<tr>
        <td style="text-align:center;width: 100px;">'.$row1['spec_no'].'</td>
        <td style="text-align:center;width: 50px;">'.$j++.'</td>
        <td style="text-align:center;width: 300px;">'.$row1['reason'].'</td>
        <td style="text-align:center;width: 90px;">'.$row1['effective_date'].'</td>
    </tr>';
                }}
$html.='</table>
<div></div>
<div></div>
<table border="1">
    <tr>
        <td style="width:180px;text-align:center;">Prepared by </td>
        <td style="width:180px;text-align:center;">Checked By</td>
        <td style="width:180px;text-align:center;">Approved By</td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;">'.$row["designation"].'</td>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;"></td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["entry_by"].'</td>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["check_by"].'</td>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["approve_by"].'</td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["entry_date"].'</td>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["check_date"].'</td>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["approve_date"].'</td>
    </tr>
    
</table>



';}
        //    }
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
      else  if($_GET["plant_id"] == 29) { //demo
               $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade,p.plant_id,m.packing_requirement,v.vendor_name,p.logo_path, m.cas_no, m.category,e.emp_id,e.designation FROM specification s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN plant p ON m.plant_id = p.plant_id left join vendor v on m.material_code = v.material_code LEFT JOIN employee e on s.entry_by = e.emp_id WHERE specification_no='".$_GET['specification_no']."' LIMIT 1";
        	$result = $conn->query($sql);
            $row = $result->fetch_assoc();{
        
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
 
        $html.='
        
 <table border="1">
    <tr>
    <td style="width: 135px;"> Department Name </td>
    <td style="width: 135px;">  Quality Control</td>
    <td style="width: 135px;"> Material Code</td>
    <td style="width: 135px;"> '.$row["material_code"].' </td>
    </tr>
    <tr>
        <td style="width: 135px;"> Name of Material </td>
        <td style="width: 135px;"> '.$row["material_name"].'</td>
        <td style="width: 135px;"> Specification No.</td>
        <td style="width: 135px;"> '.$row["specification_no"].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Reference </td>
        <td style="width: 135px;"> '.$row["reference"].'</td>
        <td style="width: 135px;"> Supersede No.</td>
        <td style="width: 135px;"> '.$row["supersede_no"].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Effective Date </td>
        <td style="width: 135px;"> '.$row["effective_date"].'</td>
        <td style="width: 135px;"> Review Date</td>
        <td style="width: 135px;"> '.$row["review_date"].'</td>
    </tr>
</table>
<div></div>
<table border="1">
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> Sr. No.</td>
        <td style="font-size:10px;width: 150px;"> Particulars</td>
        <td style="text-align:center;font-size:10px;width: 350px;"> Details</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 1</td>
        <td style="font-size:10px;width: 150px;"> Chemical Name</td>
        <td style="font-size:10px;width: 350px;"> '.$row["chemical_name"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 2</td>
        <td style="font-size:10px;width: 150px;"> Category</td>
        <td style="font-size:10px;width: 350px;"> '.$row["category"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 3</td>
        <td style="font-size:10px;width: 150px;"> Sample Qty.</td>
        <td style="font-size:10px;width: 350px;"> '.$row["sample_qty"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 4</td>
        <td style="font-size:10px;width: 150px;"> Shelf Life</td>
        <td style="font-size:10px;width: 350px;"> '.$row["shelf_life"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 5</td>
        <td style="font-size:10px;width: 150px;"> CAS No.</td>
        <td style="font-size:10px;width: 350px;"> '.$row["cas_no"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 6</td>
        <td style="font-size:10px;width: 150px;"> Molecular Formula</td>
        <td style="font-size:10px;width: 350px;"> '.$row["molecular_formula"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 7</td>
        <td style="font-size:10px;width: 150px;"> Structural Formula</td>
        <td style="font-size:10px;width: 350px;"></td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 8</td>
        <td style="font-size:10px;width: 150px;"> Retest Period</td>
        <td style="font-size:10px;width: 350px;"> '.$row["retest_period"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 9</td>
        <td style="font-size:10px;width: 150px;">Storage Condition</td>
        <td style="font-size:10px;width: 350px;"> '.$row["storage_condition"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 10</td>
        <td style="font-size:10px;width: 150px;"> Safety Precautions</td>
        <td style="font-size:10px;width: 350px;"> '.$row["safety_precaution"].'</td>
    </tr>
</table>

<h3 style="text-align:center;">Specification Test</h3>
<table border="1">
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> Sr. No.</td>
        <td style="font-size:10px;width: 150px;"> Test</td>
        <td style="text-align:center;font-size:10px;width: 350px;"> Specification</td>
    </tr>
    ';
     $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j =1;
            while ($row1 = $result1->fetch_assoc()) {
    $html.='
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> '.$j++.'</td>
        <td style="font-size:10px;width: 150px;"> '.$row1["test"].'</td>
        <td style="font-size:10px;width: 350px;"> '.$row1['description'].'</td>
    </tr>';

            }
$html.='</table>
<h3 style="text-align:center;">Revision History</h3>
        <table border="1">
    <tr>
        <td style="text-align:center;width: 100px;">Specification No.</td>
        <td style="text-align:center;width: 50px;">Revision No.</td>
        <td style="text-align:center;width: 300px;">Revision Description</td>
        <td style="text-align:center;width: 90px;">Effective Date</td>
    </tr>';
      $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j=1;
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
    $html.='<tr>
        <td style="text-align:center;width: 100px;">'.$row1['spec_no'].'</td>
        <td style="text-align:center;width: 50px;">'.$j++.'</td>
        <td style="text-align:center;width: 300px;">'.$row1['reason'].'</td>
        <td style="text-align:center;width: 90px;">'.$row1['effective_date'].'</td>
    </tr>';
                }}
$html.='</table>
<div></div>
<div></div>
<table border="1">
    <tr>
        <td style="width:180px;text-align:center;">Prepared by </td>
        <td style="width:180px;text-align:center;">Checked By</td>
        <td style="width:180px;text-align:center;">Approved By</td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;">'.$row["designation"].'</td>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;"></td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["entry_by"].'</td>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["check_by"].'</td>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["approve_by"].'</td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["entry_date"].'</td>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["check_date"].'</td>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["approve_date"].'</td>
    </tr>
    
</table>



';}
        //    }
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
      else  if($_GET["plant_id"] == 57) { // Hamax
               $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade,p.plant_id,m.packing_requirement,v.vendor_name,p.logo_path, m.cas_no, m.category,e.emp_id,e.designation FROM specification s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN plant p ON m.plant_id = p.plant_id left join vendor v on m.material_code = v.material_code LEFT JOIN employee e on s.entry_by = e.emp_id WHERE specification_no='".$_GET['specification_no']."' LIMIT 1";
        	$result = $conn->query($sql);
            $row = $result->fetch_assoc();{
        
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
 
        $html.='
        
 <table border="1">
    <tr>
    <td style="width: 135px;"> Department Name </td>
    <td style="width: 135px;">  Quality Control</td>
    <td style="width: 135px;"> Material Code</td>
    <td style="width: 135px;"> '.$row["material_code"].' </td>
    </tr>
    <tr>
        <td style="width: 135px;"> Name of Material </td>
        <td style="width: 135px;"> '.$row["material_name"].'</td>
        <td style="width: 135px;"> Specification No.</td>
        <td style="width: 135px;"> '.$row["specification_no"].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Reference </td>
        <td style="width: 135px;"> '.$row["reference"].'</td>
        <td style="width: 135px;"> Supersede No.</td>
        <td style="width: 135px;"> '.$row["supersede_no"].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Effective Date </td>
        <td style="width: 135px;"> '.$row["effective_date"].'</td>
        <td style="width: 135px;"> Review Date</td>
        <td style="width: 135px;"> '.$row["review_date"].'</td>
    </tr>
</table>
<div></div>
<table border="1">
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> Sr. No.</td>
        <td style="font-size:10px;width: 150px;"> Particulars</td>
        <td style="text-align:center;font-size:10px;width: 350px;"> Details</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 1</td>
        <td style="font-size:10px;width: 150px;"> Chemical Name</td>
        <td style="font-size:10px;width: 350px;"> '.$row["chemical_name"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 2</td>
        <td style="font-size:10px;width: 150px;"> Category</td>
        <td style="font-size:10px;width: 350px;"> '.$row["category"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 3</td>
        <td style="font-size:10px;width: 150px;"> Sample Qty.</td>
        <td style="font-size:10px;width: 350px;"> '.$row["sample_qty"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 4</td>
        <td style="font-size:10px;width: 150px;"> Shelf Life</td>
        <td style="font-size:10px;width: 350px;"> '.$row["shelf_life"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 5</td>
        <td style="font-size:10px;width: 150px;"> CAS No.</td>
        <td style="font-size:10px;width: 350px;"> '.$row["cas_no"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 6</td>
        <td style="font-size:10px;width: 150px;"> Molecular Formula</td>
        <td style="font-size:10px;width: 350px;"> '.$row["molecular_formula"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 7</td>
        <td style="font-size:10px;width: 150px;"> Structural Formula</td>
        <td style="font-size:10px;width: 350px;"></td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 8</td>
        <td style="font-size:10px;width: 150px;"> Retest Period</td>
        <td style="font-size:10px;width: 350px;"> '.$row["retest_period"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 9</td>
        <td style="font-size:10px;width: 150px;">Storage Condition</td>
        <td style="font-size:10px;width: 350px;"> '.$row["storage_condition"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 10</td>
        <td style="font-size:10px;width: 150px;"> Safety Precautions</td>
        <td style="font-size:10px;width: 350px;"> '.$row["safety_precaution"].'</td>
    </tr>
</table>

<h3 style="text-align:center;">Specification Test</h3>
<table border="1">
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> Sr. No.</td>
        <td style="font-size:10px;width: 150px;"> Test</td>
        <td style="text-align:center;font-size:10px;width: 350px;"> Specification</td>
    </tr>
    ';
     $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j =1;
            while ($row1 = $result1->fetch_assoc()) {
    $html.='
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> '.$j++.'</td>
        <td style="font-size:10px;width: 150px;"> '.$row1["test"].'</td>
        <td style="font-size:10px;width: 350px;"> '.$row1['description'].'</td>
    </tr>';

            }
$html.='</table>
<h3 style="text-align:center;">Revision History</h3>
        <table border="1">
    <tr>
        <td style="text-align:center;width: 100px;">Specification No.</td>
        <td style="text-align:center;width: 50px;">Revision No.</td>
        <td style="text-align:center;width: 300px;">Revision Description</td>
        <td style="text-align:center;width: 90px;">Effective Date</td>
    </tr>';
      $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j=1;
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
    $html.='<tr>
        <td style="text-align:center;width: 100px;">'.$row1['spec_no'].'</td>
        <td style="text-align:center;width: 50px;">'.$j++.'</td>
        <td style="text-align:center;width: 300px;">'.$row1['reason'].'</td>
        <td style="text-align:center;width: 90px;">'.$row1['effective_date'].'</td>
    </tr>';
                }}
$html.='</table>
<div></div>
<div></div>
<table border="1">
    <tr>
        <td style="width:180px;text-align:center;">Prepared by </td>
        <td style="width:180px;text-align:center;">Checked By</td>
        <td style="width:180px;text-align:center;">Approved By</td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;">'.$row["designation"].'</td>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;"></td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["entry_by"].'</td>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["check_by"].'</td>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["approve_by"].'</td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["entry_date"].'</td>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["check_date"].'</td>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["approve_date"].'</td>
    </tr>
    
</table>



';}
        //    }
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
       else if($_GET["plant_id"] == 66) { //demo
               $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade,p.plant_id,m.packing_requirement,v.vendor_name,p.logo_path, m.cas_no, m.category,e.emp_id,e.designation FROM specification s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN plant p ON m.plant_id = p.plant_id left join vendor v on m.material_code = v.material_code LEFT JOIN employee e on s.entry_by = e.emp_id WHERE specification_no='".$_GET['specification_no']."' LIMIT 1";
        	$result = $conn->query($sql);
            $row = $result->fetch_assoc();{
        
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
 
        $html.='
        
 <table border="1">
    <tr>
    <td style="width: 135px;"> Department Name </td>
    <td style="width: 135px;">  Quality Control</td>
    <td style="width: 135px;"> Material Code</td>
    <td style="width: 135px;"> '.$row["material_code"].' </td>
    </tr>
    <tr>
        <td style="width: 135px;"> Name of Material </td>
        <td style="width: 135px;"> '.$row["material_name"].'</td>
        <td style="width: 135px;"> Specification No.</td>
        <td style="width: 135px;"> '.$row["specification_no"].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Reference </td>
        <td style="width: 135px;"> '.$row["reference"].'</td>
        <td style="width: 135px;"> Supersede No.</td>
        <td style="width: 135px;"> '.$row["supersede_no"].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Effective Date </td>
        <td style="width: 135px;"> '.$row["effective_date"].'</td>
        <td style="width: 135px;"> Review Date</td>
        <td style="width: 135px;"> '.$row["review_date"].'</td>
    </tr>
</table>
<div></div>
<table border="1">
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> Sr. No.</td>
        <td style="font-size:10px;width: 150px;"> Particulars</td>
        <td style="text-align:center;font-size:10px;width: 350px;"> Details</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 1</td>
        <td style="font-size:10px;width: 150px;"> Chemical Name</td>
        <td style="font-size:10px;width: 350px;"> '.$row["chemical_name"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 2</td>
        <td style="font-size:10px;width: 150px;"> Category</td>
        <td style="font-size:10px;width: 350px;"> '.$row["category"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 3</td>
        <td style="font-size:10px;width: 150px;"> Sample Qty.</td>
        <td style="font-size:10px;width: 350px;"> '.$row["sample_qty"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 4</td>
        <td style="font-size:10px;width: 150px;"> Shelf Life</td>
        <td style="font-size:10px;width: 350px;"> '.$row["shelf_life"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 5</td>
        <td style="font-size:10px;width: 150px;"> CAS No.</td>
        <td style="font-size:10px;width: 350px;"> '.$row["cas_no"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 6</td>
        <td style="font-size:10px;width: 150px;"> Molecular Formula</td>
        <td style="font-size:10px;width: 350px;"> '.$row["molecular_formula"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 7</td>
        <td style="font-size:10px;width: 150px;"> Structural Formula</td>
        <td style="font-size:10px;width: 350px;"></td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 8</td>
        <td style="font-size:10px;width: 150px;"> Retest Period</td>
        <td style="font-size:10px;width: 350px;"> '.$row["retest_period"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 9</td>
        <td style="font-size:10px;width: 150px;">Storage Condition</td>
        <td style="font-size:10px;width: 350px;"> '.$row["storage_condition"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 10</td>
        <td style="font-size:10px;width: 150px;"> Safety Precautions</td>
        <td style="font-size:10px;width: 350px;"> '.$row["safety_precaution"].'</td>
    </tr>
</table>

<h3 style="text-align:center;">Specification Test</h3>
<table border="1">
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> Sr. No.</td>
        <td style="font-size:10px;width: 150px;"> Test</td>
        <td style="text-align:center;font-size:10px;width: 350px;"> Specification</td>
    </tr>
    ';
     $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j =1;
            while ($row1 = $result1->fetch_assoc()) {
    $html.='
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> '.$j++.'</td>
        <td style="font-size:10px;width: 150px;"> '.$row1["test"].'</td>
        <td style="font-size:10px;width: 350px;"> '.$row1['description'].'</td>
    </tr>';

            }
$html.='</table>
<h3 style="text-align:center;">Revision History</h3>
        <table border="1">
    <tr>
        <td style="text-align:center;width: 100px;">Specification No.</td>
        <td style="text-align:center;width: 50px;">Revision No.</td>
        <td style="text-align:center;width: 300px;">Revision Description</td>
        <td style="text-align:center;width: 90px;">Effective Date</td>
    </tr>';
      $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j=1;
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
    $html.='<tr>
        <td style="text-align:center;width: 100px;">'.$row1['spec_no'].'</td>
        <td style="text-align:center;width: 50px;">'.$j++.'</td>
        <td style="text-align:center;width: 300px;">'.$row1['reason'].'</td>
        <td style="text-align:center;width: 90px;">'.$row1['effective_date'].'</td>
    </tr>';
                }}
$html.='</table>
<div></div>
<div></div>
<table border="1">
    <tr>
        <td style="width:180px;text-align:center;">Prepared by </td>
        <td style="width:180px;text-align:center;">Checked By</td>
        <td style="width:180px;text-align:center;">Approved By</td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;">'.$row["designation"].'</td>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;"></td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["entry_by"].'</td>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["check_by"].'</td>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["approve_by"].'</td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["entry_date"].'</td>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["check_date"].'</td>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["approve_date"].'</td>
    </tr>
    
</table>



';}
        //    }
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
       else if($_GET["plant_id"] == 70) { //Max Chem
               $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade,p.plant_id,m.packing_requirement,v.vendor_name,p.logo_path, m.cas_no, m.category,e.emp_id,e.designation FROM specification s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN plant p ON m.plant_id = p.plant_id left join vendor v on m.material_code = v.material_code LEFT JOIN employee e on s.entry_by = e.emp_id WHERE specification_no='".$_GET['specification_no']."' LIMIT 1";
        	$result = $conn->query($sql);
            $row = $result->fetch_assoc();{
        
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
 
        $html.='
        
 <table border="1">
    <tr>
    <td style="width: 135px;"> Department Name </td>
    <td style="width: 135px;">  Quality Control</td>
    <td style="width: 135px;"> Material Code</td>
    <td style="width: 135px;"> '.$row["material_code"].' </td>
    </tr>
    <tr>
        <td style="width: 135px;"> Name of Material </td>
        <td style="width: 135px;"> '.$row["material_name"].'</td>
        <td style="width: 135px;"> Specification No.</td>
        <td style="width: 135px;"> '.$row["specification_no"].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Reference </td>
        <td style="width: 135px;"> '.$row["reference"].'</td>
        <td style="width: 135px;"> Supersede No.</td>
        <td style="width: 135px;"> '.$row["supersede_no"].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Effective Date </td>
        <td style="width: 135px;"> '.$row["effective_date"].'</td>
        <td style="width: 135px;"> Review Date</td>
        <td style="width: 135px;"> '.$row["review_date"].'</td>
    </tr>
</table>
<div></div>
<table border="1">
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> Sr. No.</td>
        <td style="font-size:10px;width: 150px;"> Particulars</td>
        <td style="text-align:center;font-size:10px;width: 350px;"> Details</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 1</td>
        <td style="font-size:10px;width: 150px;"> Chemical Name</td>
        <td style="font-size:10px;width: 350px;"> '.$row["chemical_name"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 2</td>
        <td style="font-size:10px;width: 150px;"> Category</td>
        <td style="font-size:10px;width: 350px;"> '.$row["category"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 3</td>
        <td style="font-size:10px;width: 150px;"> Sample Qty.</td>
        <td style="font-size:10px;width: 350px;"> '.$row["sample_qty"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 4</td>
        <td style="font-size:10px;width: 150px;"> Shelf Life</td>
        <td style="font-size:10px;width: 350px;"> '.$row["shelf_life"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 5</td>
        <td style="font-size:10px;width: 150px;"> CAS No.</td>
        <td style="font-size:10px;width: 350px;"> '.$row["cas_no"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 6</td>
        <td style="font-size:10px;width: 150px;"> Molecular Formula</td>
        <td style="font-size:10px;width: 350px;"> '.$row["molecular_formula"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 7</td>
        <td style="font-size:10px;width: 150px;"> Structural Formula</td>
        <td style="font-size:10px;width: 350px;"></td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 8</td>
        <td style="font-size:10px;width: 150px;"> Retest Period</td>
        <td style="font-size:10px;width: 350px;"> '.$row["retest_period"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 9</td>
        <td style="font-size:10px;width: 150px;">Storage Condition</td>
        <td style="font-size:10px;width: 350px;"> '.$row["storage_condition"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 10</td>
        <td style="font-size:10px;width: 150px;"> Safety Precautions</td>
        <td style="font-size:10px;width: 350px;"> '.$row["safety_precaution"].'</td>
    </tr>
</table>

<h3 style="text-align:center;">Specification Test</h3>
<table border="1">
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> Sr. No.</td>
        <td style="font-size:10px;width: 150px;"> Test</td>
        <td style="text-align:center;font-size:10px;width: 350px;"> Specification</td>
    </tr>
    ';
     $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j =1;
            while ($row1 = $result1->fetch_assoc()) {
    $html.='
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> '.$j++.'</td>
        <td style="font-size:10px;width: 150px;"> '.$row1["test"].'</td>
        <td style="font-size:10px;width: 350px;"> '.$row1['description'].'</td>
    </tr>';

            }
$html.='</table>
<h3 style="text-align:center;">Revision History</h3>
        <table border="1">
    <tr>
        <td style="text-align:center;width: 100px;">Specification No.</td>
        <td style="text-align:center;width: 50px;">Revision No.</td>
        <td style="text-align:center;width: 300px;">Revision Description</td>
        <td style="text-align:center;width: 90px;">Effective Date</td>
    </tr>';
      $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j=1;
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
    $html.='<tr>
        <td style="text-align:center;width: 100px;">'.$row1['spec_no'].'</td>
        <td style="text-align:center;width: 50px;">'.$j++.'</td>
        <td style="text-align:center;width: 300px;">'.$row1['reason'].'</td>
        <td style="text-align:center;width: 90px;">'.$row1['effective_date'].'</td>
    </tr>';
                }}
$html.='</table>
<div></div>
<div></div>
<table border="1">
    <tr>
        <td style="width:180px;text-align:center;">Prepared by </td>
        <td style="width:180px;text-align:center;">Checked By</td>
        <td style="width:180px;text-align:center;">Approved By</td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;">'.$row["designation"].'</td>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;"></td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["entry_by"].'</td>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["check_by"].'</td>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["approve_by"].'</td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["entry_date"].'</td>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["check_date"].'</td>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["approve_date"].'</td>
    </tr>
    
</table>



';}
        //    }
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }else if($_GET["plant_id"] == 67) { //saipro
        
            $sql = "SELECT s.* ,m.material_type, m.material_subtype, m.material_name, m.grade,m.storage_condition as
         material_storage_conditions,p.plant_name,p.plant_full_address FROM specification s LEFT JOIN material m 
         ON s.material_code=m.material_code LEFT JOIN plant p on s.plant_id=p.plant_id WHERE 
         specification_no='".$_GET['specification_no']."' LIMIT 1";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        // $_GET['filename'] = 'RAW MATERIAL SPECIFICATION'; 
        // $_GET['pdftype'] = 'headfootdigital'; include("../../pdfimp.php");
        $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        
        
        $html.='
        <h3 style="text-align:center; ">RAW MATERIAL SPECIFICATION</h3>
        <style>
            .tdall { border:solid 1px BCBBBA; }
            .tdb { border-bottom:solid 1px BCBBBA; }
            .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
        </style><br><br><br>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <tr>
                <td class="tdb" style="width:15%;"><b>Department</b></td>
                <td class="tdb" style="width:85%;">: <b>Quality Control</b></td>
            </tr>
            <tr>
                <td class="tdb"><b>Material Name</b></td>
                <td class="tdb">: '.$row["material_name"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Specification No</b></td>
                <td class="tdbr" style="width:45%;">: '.$row['specification_no'].'</td>
                <td class="tdb" style="width:15%;"><b>Material Code</b></td>
                <td class="tdb" style="width:25%;">: '.$row["material_code"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Supersedes No</b></td>
                <td class="tdb">: NA</td>
                <td class="tdb"><b>Review Date</b></td>';
                
                if($row['review_date']=='0000-00-00'){
                  $html.='  <td class="tdb">: NA</td> ';
                }else{
                $html.='  <td class="tdb">: '.date('d-m-Y',strtotime($row['review_date'])).'</td> ';

                }
                
            $html.='    </tr>
            <tr>
                <td class="tdb"><b>Retest Period</b></td>
                <td class="tdbr">: 24 Months 0 Days</td>
                <td class="tdb"><b>Page No</b></td>
                <td class="tdb">:  1 Of 1 </td>
                
            </tr>
            
            <tr>
                <td><b>Storage Condition</b></td>
                <td colspan="3">: '.$row['material_storage_conditions'].'.</td>
                 
            </tr>
        </table>
        <div></div>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <thead>
                <tr style="background-color:#e8e6e6;font-weight:bold;" align="center">
                    <td class="tdall" style="width:7%">Sr.</td>
                    <td class="tdall" style="width:31%">Test</td>
                    <td class="tdall" style="width:31%">Specification</td>
                    <td class="tdall" style="width:31%">Reference</td>
                </tr>
            </thead>
            <tbody>';
            $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j =1;
            while ($row1 = $result1->fetch_assoc()) {
                $html.='
                <tr>
                    <td class="tdall" style="width:7%" align="center;">'.$j++.'.</td>
                    <td class="tdall" style="width:31%">'.$row1["test"].'</td>
                    <td class="tdall" style="width:31%">'.$row1["limits"].'</td>
                    <td class="tdall" style="width:31%">'.$row1["reference_type"].'</td>
                </tr>';
            }
           
          $html.='
        </table>
        <p style="text-align:center;"><b>REVISION HISTORY</b></p>
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9; text-align:center;">
                <td class="tdall">Specification No.</td>
                <td class="tdall">Version No.</td>
                <td class="tdall">Change Made</td>
                <td class="tdall">Reasons for change</td>
                <th class="tdall"> Effective Date </th>
            </tr>';
            $sql2 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result2 = $conn->query($sql2);
            $output2 = Array();
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $html.='
                    <tr nobr="true">
                        <td class="tdall">'.$row2["specification_no"].'</td>
                        <td class="tdall">'.$row2["version_no"].'</td>
                        <td class="tdall">'.$row2["change_mode"].'</td>
                        <td class="tdall">'.$row2["reason"].'</td>
                        <td class="tdall">'.date('d-m-Y',strtotime($row2['effective_date'])).'</td>
                        
                    </tr>';
                }
            }
            $html.=' </table> ';
            
    
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    
        
        
        
        
    }else if($_GET["plant_id"] == 59) { //amardeep
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade,p.plant_id,m.packing_requirement,v.vendor_name,p.logo_path FROM specification s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN plant p ON m.plant_id = p.plant_id left join vendor v on m.material_code = v.material_code WHERE specification_no='".$_GET['specification_no']."' LIMIT 1";
        	$result = $conn->query($sql);
            $row = $result->fetch_assoc();{
        
        $_GET['filename'] = ''; $_GET['pdftype'] = 'noheader'; include("../../pdfimp2.php");

        $html.='<table border="1">
    <tr>
        <td style="width: 540px;text-align:center;">RAW MATERIAL SPECIFICATION </td>
    </tr>
    <tr>
        <td style="width: 135px;text-align:center;height: 40px;"rowspan="3"><br><br><img src="../../logos/'.$row['logo_path'].'" style="width:80px;height:30px;" ></td>
        <td style="width: 135px;text-align:center;"rowspan="6"> <br><br><br>'.$row['material_name'].'</td>
        <td style="width: 135px;"> Page No.</td>
        <td style="width: 135px;"></td>
    </tr>
    <tr>
 
    <td style="width: 135px;"> Reference</td>
    <td style="width: 135px;"> '.$row['reference'].'</td>

    </tr>
    <tr>
        <td style="width: 135px;"> Specification No.</td>
        <td style="width: 135px;"> '.$row['specification_no'].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"rowspan="3"> Department:
        Quality Control
        </td>
        <td style="width: 135px;"> Supersedes  No.</td>
        <td style="width: 135px;"> '.$row['supersede_no'].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Effective Date</td>
        <td style="width: 135px;"> '.$row['effective_date'].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Review Month</td>
        <td style="width: 135px;"> '.$row['review_in_months'].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Ref. SOP No. :</td>
        <td style="width: 135px;"> SOP/QC/030</td>
        <td style="width: 135px;"> Annexure</td>
        <td style="width: 135px;"> A/SOP/QC/030/02-00</td>
    </tr>

</table>
<div></div>

<table style="border-collapse: collapse; border: 1px solid black;">
        <tr>
            <td style="width: 540px;text-align:center;">GENERAL INFORMATION </td>
        </tr>
        <tr>
            <td style="width: 200px;"> Pharmacopoeial reference</td>
            <td style="width: 340px;">: '.$row['reference'].'</td>
        </tr>
        <tr>
            <td style="width: 200px;"> Special  requirement of pack</td>
            <td style="width: 340px;">: '.$row['packing_requirement'].'</td>
        </tr>
        <tr>
            <td style="width: 200px;"> Approved vendor</td>
            <td style="width: 340px;">: '.$row['vendor_name'].'</td>
        </tr>
        <tr>
            <td style="width: 200px;"> Handling hazards</td>
            <td style="width: 340px;">: '.$row['safety_precaution'].'</td>
        </tr>
        <tr>
            <td style="width: 200px;"> Quantity to be sampled</td>
            <td style="width: 340px;">: '.$row['totalsample_qty'].'</td>
        </tr>
        <tr>
            <td style="width: 200px;"> Retest Period</td>
            <td style="width: 340px;">: '.$row['retest_period'].'</td>
        </tr>
        <tr>
            <td style="width: 200px;"> Storage</td>
            <td style="width: 340px;">: '.$row['storage_condition'].'</td>
        </tr>

</table>
<div></div>
<table border="1">
    <tr>
        <td style="text-align:center;width: 30px;line-height:15px;"><br>Sr. No.</td>
        <td style="text-align:center;width: 130px;line-height:15px;"><br>TEST</td>
        <td style="text-align:center;width: 280px;line-height:15px;"><br>SPECIFICATIONS</td>
        <td style="text-align:center;width: 100px;line-height:15px;"><br>REFERENCE</td>
    </tr>';
     $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j =1;
            while ($row1 = $result1->fetch_assoc()) {
    $html.='<tr>
        <td style="text-align:center;width: 30px;line-height:15px;">'.$j++.'.</td>
        <td style="text-align:center;width: 130px;line-height:15px;">'.$row1["test"].'</td>
        <td style="text-align:center;width: 280px;line-height:15px;">'.$row1['limit_type'].'</td>
        <td style="text-align:center;width: 100px;line-height:15px;"> '.$row1['reference_type'].'</td>
    </tr>';
            }
               //  $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";

            	$result = $conn->query($sql1);
            $row1 = $result->fetch_assoc();{
    $html.='<tr>
        <td style="width: 540px;line-height:15px;"> # : Re-evaluation tests  :'.$row1['retest'].' </td>
    </tr>';
            }
$html.='</table>
<div></div>
<table>
    <tr>
        <td style="width: 540px;text-align:center;">REVISION HISTORY FOR SPECIFICATION:</td>
    </tr>
</table>
<div></div>
<table border="1">
    <tr>
        <td style="text-align:center;width: 50px;">Revision No.</td>
        <td style="text-align:center;width: 400px;">Revision Description</td>
        <td style="text-align:center;width: 90px;">Effective Date</td>
    </tr>';
      $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j=1;
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
    $html.='<tr>
        <td style="text-align:center;width: 50px;">'.$j++.'</td>
        <td style="text-align:center;width: 400px;">'.$row1['reason'].'</td>
        <td style="text-align:center;width: 90px;">'.$row1['effective_date'].'</td>
    </tr>';
                }}
$html.='</table>';}
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
    else if($_GET["plant_id"] == 58) { //amardeep
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade,p.plant_id,m.packing_requirement,v.vendor_name,p.logo_path FROM specification s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN plant p ON m.plant_id = p.plant_id left join vendor v on m.material_code = v.material_code WHERE specification_no='".$_GET['specification_no']."' LIMIT 1";
        	$result = $conn->query($sql);
            $row = $result->fetch_assoc();{
        
        $_GET['filename'] = ''; $_GET['pdftype'] = 'noheader'; include("../../pdfimp2.php");

        $html.='<table border="1">
    <tr>
        <td style="width: 540px;text-align:center;">RAW MATERIAL SPECIFICATION </td>
    </tr>
    <tr>
        <td style="width: 135px;text-align:center;height: 40px;"rowspan="3"><br><br><img src="../../logos/'.$row['logo_path'].'" style="width:80px;height:30px;" ></td>
        <td style="width: 135px;text-align:center;"rowspan="6"> <br><br><br>'.$row['material_name'].'</td>
        <td style="width: 135px;"> Page No.</td>
        <td style="width: 135px;"></td>
    </tr>
    <tr>
 
    <td style="width: 135px;"> Reference</td>
    <td style="width: 135px;"> '.$row['reference'].'</td>

    </tr>
    <tr>
        <td style="width: 135px;"> Specification No.</td>
        <td style="width: 135px;"> '.$row['specification_no'].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"rowspan="3"> Department:
        Quality Control
        </td>
        <td style="width: 135px;"> Supersedes  No.</td>
        <td style="width: 135px;"> '.$row['supersede_no'].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Effective Date</td>
        <td style="width: 135px;"> '.$row['effective_date'].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Review Month</td>
        <td style="width: 135px;"> '.$row['review_in_months'].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Ref. SOP No. :</td>
        <td style="width: 135px;"> SOP/QC/030</td>
        <td style="width: 135px;"> Annexure</td>
        <td style="width: 135px;"> A/SOP/QC/030/02-00</td>
    </tr>

</table>
<div></div>

<table style="border-collapse: collapse; border: 1px solid black;">
        <tr>
            <td style="width: 540px;text-align:center;">GENERAL INFORMATION </td>
        </tr>
        <tr>
            <td style="width: 200px;"> Pharmacopoeial reference</td>
            <td style="width: 340px;">: '.$row['reference'].'</td>
        </tr>
        <tr>
            <td style="width: 200px;"> Special  requirement of pack</td>
            <td style="width: 340px;">: '.$row['packing_requirement'].'</td>
        </tr>
        <tr>
            <td style="width: 200px;"> Approved vendor</td>
            <td style="width: 340px;">: '.$row['vendor_name'].'</td>
        </tr>
        <tr>
            <td style="width: 200px;"> Handling hazards</td>
            <td style="width: 340px;">: '.$row['safety_precaution'].'</td>
        </tr>
        <tr>
            <td style="width: 200px;"> Quantity to be sampled</td>
            <td style="width: 340px;">: '.$row['totalsample_qty'].'</td>
        </tr>
        <tr>
            <td style="width: 200px;"> Retest Period</td>
            <td style="width: 340px;">: '.$row['retest_period'].'</td>
        </tr>
        <tr>
            <td style="width: 200px;"> Storage</td>
            <td style="width: 340px;">: '.$row['storage_condition'].'</td>
        </tr>

</table>
<div></div>
<table border="1">
    <tr>
        <td style="text-align:center;width: 30px;line-height:15px;"><br>Sr. No.</td>
        <td style="text-align:center;width: 130px;line-height:15px;"><br>TEST</td>
        <td style="text-align:center;width: 280px;line-height:15px;"><br>SPECIFICATIONS</td>
        <td style="text-align:center;width: 100px;line-height:15px;"><br>REFERENCE</td>
    </tr>';
     $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j =1;
            while ($row1 = $result1->fetch_assoc()) {
    $html.='<tr>
        <td style="text-align:center;width: 30px;line-height:15px;">'.$j++.'.</td>
        <td style="text-align:center;width: 130px;line-height:15px;">'.$row1["test"].'</td>
        <td style="text-align:center;width: 280px;line-height:15px;">'.$row1['description'].'</td>
        <td style="text-align:center;width: 100px;line-height:15px;"> '.$row1['reference_type'].'</td>
    </tr>';
            }
               //  $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";

            	$result = $conn->query($sql1);
            $row1 = $result->fetch_assoc();{
    $html.='<tr>
        <td style="width: 540px;line-height:15px;"> # : Re-evaluation tests  :'.$row1['retest'].' </td>
    </tr>';
            }
$html.='</table>
<div></div>
<table>
    <tr>
        <td style="width: 540px;text-align:center;">REVISION HISTORY FOR SPECIFICATION:</td>
    </tr>
</table>
<div></div>
<table border="1">
    <tr>
        <td style="text-align:center;width: 50px;">Revision No.</td>
        <td style="text-align:center;width: 400px;">Revision Description</td>
        <td style="text-align:center;width: 90px;">Effective Date</td>
    </tr>';
      $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j=1;
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
    $html.='<tr>
        <td style="text-align:center;width: 50px;">'.$j++.'</td>
        <td style="text-align:center;width: 400px;">'.$row1['reason'].'</td>
        <td style="text-align:center;width: 90px;">'.$row1['effective_date'].'</td>
    </tr>';
                }}
$html.='</table>';}
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
    else if($_GET["plant_id"] == 60) { //captalo
               $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade,p.plant_id,m.packing_requirement,v.vendor_name,p.logo_path, m.cas_no, m.category,e.emp_id,e.designation FROM specification s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN plant p ON m.plant_id = p.plant_id left join vendor v on m.material_code = v.material_code LEFT JOIN employee e on s.entry_by = e.emp_id WHERE specification_no='".$_GET['specification_no']."' LIMIT 1";
        	$result = $conn->query($sql);
            $row = $result->fetch_assoc();{
        
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
 
        $html.='
        
 <table border="1">
    <tr>
    <td style="width: 135px;"> Department Name </td>
    <td style="width: 135px;">  Quality Control</td>
    <td style="width: 135px;"> Material Code</td>
    <td style="width: 135px;"> '.$row["material_code"].' </td>
    </tr>
    <tr>
        <td style="width: 135px;"> Name of Material </td>
        <td style="width: 135px;"> '.$row["material_name"].'</td>
        <td style="width: 135px;"> Specification No.</td>
        <td style="width: 135px;"> '.$row["specification_no"].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Reference </td>
        <td style="width: 135px;"> '.$row["reference"].'</td>
        <td style="width: 135px;"> Supersede No.</td>
        <td style="width: 135px;"> '.$row["supersede_no"].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Effective Date </td>
        <td style="width: 135px;"> '.$row["effective_date"].'</td>
        <td style="width: 135px;"> Review Date</td>
        <td style="width: 135px;"> '.$row["review_date"].'</td>
    </tr>
</table>
<div></div>
<table border="1">
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> Sr. No.</td>
        <td style="font-size:10px;width: 150px;"> Particulars</td>
        <td style="text-align:center;font-size:10px;width: 350px;"> Details</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 1</td>
        <td style="font-size:10px;width: 150px;"> Chemical Name</td>
        <td style="font-size:10px;width: 350px;"> '.$row["chemical_name"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 2</td>
        <td style="font-size:10px;width: 150px;"> Category</td>
        <td style="font-size:10px;width: 350px;"> '.$row["category"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 3</td>
        <td style="font-size:10px;width: 150px;"> Sample Qty.</td>
        <td style="font-size:10px;width: 350px;"> '.$row["sample_qty"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 4</td>
        <td style="font-size:10px;width: 150px;"> Shelf Life</td>
        <td style="font-size:10px;width: 350px;"> '.$row["shelf_life"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 5</td>
        <td style="font-size:10px;width: 150px;"> CAS No.</td>
        <td style="font-size:10px;width: 350px;"> '.$row["cas_no"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 6</td>
        <td style="font-size:10px;width: 150px;"> Molecular Formula</td>
        <td style="font-size:10px;width: 350px;"> '.$row["molecular_formula"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 7</td>
        <td style="font-size:10px;width: 150px;"> Structural Formula</td>
        <td style="font-size:10px;width: 350px;"></td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 8</td>
        <td style="font-size:10px;width: 150px;"> Retest Period</td>
        <td style="font-size:10px;width: 350px;"> '.$row["retest_period"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 9</td>
        <td style="font-size:10px;width: 150px;">Storage Condition</td>
        <td style="font-size:10px;width: 350px;"> '.$row["storage_condition"].'</td>
    </tr>
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> 10</td>
        <td style="font-size:10px;width: 150px;"> Safety Precautions</td>
        <td style="font-size:10px;width: 350px;"> '.$row["safety_precaution"].'</td>
    </tr>
</table>

<h3 style="text-align:center;">Specification Test</h3>
<table border="1">
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> Sr. No.</td>
        <td style="font-size:10px;width: 150px;"> Test</td>
        <td style="text-align:center;font-size:10px;width: 350px;"> Specification</td>
    </tr>
    ';
     $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j =1;
            while ($row1 = $result1->fetch_assoc()) {
    $html.='
    <tr>
        <td style="text-align:center;font-size:10px;width: 40px;"> '.$j++.'</td>
        <td style="font-size:10px;width: 150px;"> '.$row1["test"].'</td>
        <td style="font-size:10px;width: 350px;"> '.$row1['description'].'</td>
    </tr>';

            }
$html.='</table>
<h3 style="text-align:center;">Revision History</h3>
        <table border="1">
    <tr>
        <td style="text-align:center;width: 100px;">Specification No.</td>
        <td style="text-align:center;width: 50px;">Revision No.</td>
        <td style="text-align:center;width: 300px;">Revision Description</td>
        <td style="text-align:center;width: 90px;">Effective Date</td>
    </tr>';
      $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $j=1;
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
    $html.='<tr>
        <td style="text-align:center;width: 100px;">'.$row1['spec_no'].'</td>
        <td style="text-align:center;width: 50px;">'.$j++.'</td>
        <td style="text-align:center;width: 300px;">'.$row1['reason'].'</td>
        <td style="text-align:center;width: 90px;">'.$row1['effective_date'].'</td>
    </tr>';
                }}
$html.='</table>
<div></div>
<table border="1">
    <tr>
        <td style="width:180px;text-align:center;">Prepared by </td>
        <td style="width:180px;text-align:center;">Checked By</td>
        <td style="width:180px;text-align:center;">Approved By</td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;">'.$row["designation"].'</td>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Designation</td>
        <td style="width:100px;text-align:center;"></td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["entry_by"].'</td>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["check_by"].'</td>
        <td style="width:80px;text-align:center;">Name</td>
        <td style="width:100px;text-align:center;">'.$row["approve_by"].'</td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
        <td style="width:80px;text-align:center;">Sign</td>
        <td style="width:100px;text-align:center;"></td>
    </tr>
    <tr>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["entry_date"].'</td>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["check_date"].'</td>
        <td style="width:80px;text-align:center;">Date</td>
        <td style="width:100px;text-align:center;">'.$row["approve_date"].'</td>
    </tr>
    
</table>



';}
        //    }
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
   
     else if($_GET["plant_id"] == 77) {
         //demo
            
                        $sql = "SELECT s.* ,m.material_type, m.material_subtype, m.material_name, m.grade,m.storage_condition as
                     material_storage_conditions,p.plant_name,p.plant_full_address FROM specification s LEFT JOIN material m 
                     ON s.material_code=m.material_code LEFT JOIN plant p on s.plant_id=p.plant_id WHERE 
                     specification_no='".$_GET['specification_no']."' LIMIT 1";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                   
                    $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
                    $html='';
                    
                  $html.='
                 
                    <table border="1" cellpadding="7">
        <tr>
            <th><b>Format No.</b></th>
            <td>SOP/QC/005-F02</td>
            <th><b>Revision No.</b></th>
            <td>01</td>
        </tr>
        <tr>
            <th colspan="4" style="text-align: center;"><h3><b>RAW MATERIAL SPECIFICATION 1 </b></h3></th>
        </tr>
        <tr>
            <th><b>MATERIAL NAME</b></th>
            <td>Creatine Phosphate Sodium</td>
            <th><b>MATERIAL CODE</b></th>
            <td>AP00005IH</td>
        </tr>
        <tr>
            <th><b>GRADE</b></th>
            <td>IH</td>
            <th><b>EFFECTIVE DATE</b></th>
            <td></td>
        </tr>
        <tr>
            <th><b>PHARMACOPOEIAL/ COUNTRY SPECIFIC CODE</b></th>
            <td>IH</td>
            <th><b>VERSION No.</b></th>
            <td>02</td>
        </tr>
        <tr>
            <th><b>SPECIFICATION No.</b></th>
            <td>RMS-009</td>
            <th><b>SUPERSEDES No.</b></th>
            <td>01</td>
        </tr>
        <tr>
            <th><b>STP No.</b></th>
            <td>RMT-009</td>
            <th><b>PAGE No.</b></th>
            <td>1 of 2</td>
        </tr>
    </table>

    <table border="1" cellpadding="9">
        <tr>
            <th><b>Category</b></th>
            <td colspan="3">Myocardial nutritional drugs</td>
        </tr>
        <tr>
            <th><b>Cas No.:</b></th>
            <td colspan="3">922-32-7</td>
        </tr>
        <tr>
            <th><b>Pack Description</b></th>
            <td colspan="3">In airtight packaging</td>
        </tr>
        <tr>
            <th><b>Storage Condition</b></th>
            <td colspan="3">Seal and store in a cool and dark place</td>
        </tr>
        <tr>
            <th><b>Retest Duration</b></th>
            <td colspan="3">SOP/QC/073</td>
        </tr>
        <tr>
            <th><b>Sampling plan/Scheme</b></th>
            <td colspan="3">SOP/QC/015</td>
        </tr>
        <tr>
            <th><b>Proposed Shelf Life</b></th>
            <td colspan="3">2 years</td>
        </tr>
        
        <tr>
            <th><b>Total Sample Quantity</b></th>
            <td>108 g</td>
            
             <th><b>Control sample quantity</b></th>
            <td>72 g</td>
        </tr>
        <tr>
           
        </tr>
        
        <tr>
            <th><b>Sample for Analysis</b></th>
            <td colspan="3">36 g (25 g for chemical analysis, 11 g for microbiology analysis)</td>
        </tr>
    </table>
  <br pagebreak="true"/>';
  
  
     $html.='  <table border="1" cellspacing="0" cellpadding="5">
        <tr>
            <th>Sr. No</th>
            <th>Tests</th>
            <th>Acceptance Criteria</th>
            <th>Reference Test Method</th>
        </tr>
        <tr>
            <td>1.</td>
            <td>Appearance</td>
            <td>White or almost white or crystalline powder</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>2.</td>
            <td>Identification</td>
            <td>
                2.1. Phosphate reaction: yellow precipitation is generated during the heating of the solution<br>
                2.2. IR test: The infrared absorption spectrum is concordant with that obtained by the reference standard<br>
                2.3. HPLC: In the chromatogram recorded under the content determination, the retention time of the main peak of the test solution should be the same as that of the control solution<br>
                2.4. Sodium test: This product shows the reaction of sodium salt identification
            </td>
            <td>IH</td>
        </tr>
        <tr>
            <td>3.</td>
            <td>pH</td>
            <td>8.0–9.0 pH</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>4.</td>
            <td>Color and clarity of the product solution</td>
            <td>The solution shall be colorless and clear</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>5.</td>
            <td>Related substances</td>
            <td>
                Creatine, Creatinine and Sodium creatinine: NMT 0.5%; Any other individual unspecified impurity: NMT 0.10%; The total amount of impurities: NMT 2.0%
            </td>
            <td>IH</td>
        </tr>
        <tr>
            <td>6.</td>
            <td>Chloride</td>
            <td>NMT 0.02%</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>7.</td>
            <td>Barium</td>
            <td>Visual inspection, both liquids should be equally clarified</td>
            <td>IH</td>
        </tr> 
    
        <tr>
            <td>8.</td>
            <td><b>Heavy Metal</b></td>
            <td>NMT 0.001%</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>9.</td>
            <td><b>Water content</b></td>
            <td>22.0%-25.0%</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>10.</td>
            <td><b>Ethanol</b></td>
            <td>NMT 0.5%</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>11.</td>
            <td><b>Visible particle</b></td>
            <td>Should meet the requirements</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>12.</td>
            <td><b>Subvisible particle</b></td>
            <td>The number of particles containing 10µm and above in each sample shall not exceed 6000, and the number of particles containing 25µm and above shall not exceed 600</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>13.</td>
            <td><b>Abnormal toxicity</b></td>
            <td>Should comply with regulations</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>14.</td>
            <td><b>Sterility</b></td>
            <td>Should be sterile</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>15.</td>
            <td><b>Bacterial endotoxins</b></td>
            <td>NMT 0.15 EU/mg of Creatine Phosphate Sodium</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>16.</td>
            <td><b>Assay</b></td>
            <td>NLT 98.0%</td>
            <td>IH</td>
        </tr>
    
</table>
<div></div>
<div></div>
    
    
    <table border="1" cellpadding="5">
            <tr>
                <th rowspan="3"><b>Signature / Date</b></th>
                <th ><b>PREPARED BY</b></th>
                <th><b>CHECKED BY</b></th>
                <th colspan="3" style="text-align:centre"><b>APPROVED BY</b></th>
            </tr>
             <tr>
                <td> </td>
                <td> </td>
                <td></td>
                <td></td>
                <td> </td>
            </tr>
           
            <tr>
                <td><b>Officer / Executive QC</b></td>
                <td><b>Sr. Executive/ AM QC</b></td>
                <td><b>Head QC</b></td>
                <td><b>Head ML</b></td>
                <td><b>Head QA</b></td>
            </tr>
        </table>
 
        
        ';    
        
        
        
        $html.=' </table>';
                        
                
                    // EOD;
                    $pdf->writeHTML($html, true, false, false, false, '');
                    $pdf->Output('Specification.pdf', 'I');
                
        
     }
     else if($_GET["plant_id"] == 158) {
         //Meha
            
                        $sql = "SELECT s.* ,m.material_type, m.material_subtype, m.material_name, m.grade,m.storage_condition as
                     material_storage_conditions,p.plant_name,p.plant_full_address FROM specification s LEFT JOIN material m 
                     ON s.material_code=m.material_code LEFT JOIN plant p on s.plant_id=p.plant_id WHERE 
                     specification_no='".$_GET['specification_no']."' LIMIT 1";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                   
                    $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
                    $html='';
                    
                  $html.='
                 
                    <table border="1" cellpadding="7">
        <tr>
            <th><b>Format No.</b></th>
            <td>SOP/QC/005-F02</td>
            <th><b>Ref. Doc. No.</b></th>
            <td>01</td>
        </tr>
          <tr>
            <td colspan="3" style="border: 2px solid black; padding: 8px;">
                <b style="color: #003366;">Title:</b>
            </td>
              <td style="border: 2px solid black; padding: 8px;">
                <b style="color: #003366;">Effective Date:</b>
            </td>
        </tr>

        <tr>
            <td colspan="2" style="border: 2px solid black; padding: 8px;">
                <b style="color: #003366;">Specification- Version No.:</b>
            </td>
            <td style="font-weight: bold; text-align: center; background-color: #f2f2f2; border: 2px solid black; padding: 8px;">
                QUALITY CONTROL DEPARTMENT
            </td>
             <td style="border: 2px solid black; padding: 8px;">
                <b style="color: #003366;">Review Date:</b>
            </td>
             <td style="border: 2px solid black; padding: 8px;">
                <b style="color: #003366;">Product/Item Code:</b>
            </td>
        </tr>

        <tr>
            <td style="border: 2px solid black; padding: 8px;">
                <b style="color: #003366;">SYNONYMS:</b>
            </td>
            <td colspan="3" style="border: 2px solid black; padding: 8px;"></td>
        </tr>

        <tr>
            <td style="border: 2px solid black; padding: 8px;">
                <b style="color: #003366;">REFERENCE:</b>
            </td>
            <td colspan="3" style="border: 2px solid black; padding: 8px;"></td>
        </tr>

        <tr>
            <th colspan="4" style="text-align: center;"><h3><b>RAW MATERIAL SPECIFICATION</b></h3></th>
        </tr>
        
    </table> <div></div><div></div><div></div>
<table border="1" cellspacing="0" cellpadding="5">
    <thead>
     <tr>
            <th colspan="5">Electronically Signed by Following Authorized persons</th>
        </tr>
        <tr>
            <th>Sr. No.</th>
            <th>Name</th>
            <th>Designation</th>
            <th>Date</th>
            <th>Reason</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td></td>
            <td></td>
            <td></td>
            <td>I am the author of this document</td>
        </tr>
        <tr>
            <td>2</td>
            <td></td>
            <td></td>
            <td></td>
            <td>I am the reviewer of this document</td>
        </tr>
        <tr>
            <td>3</td>
            <td></td>
            <td></td>
            <td></td>
            <td>I am the reviewer of this document</td>
        </tr>
        <tr>
            <td>4</td>
            <td></td>
            <td></td>
            <td></td>
            <td>I am the approver of this document</td>
        </tr>
        <tr>
            <td>5</td>
            <td></td>
            <td></td>
            <td></td>
            <td>I am the authoriser of this document</td>
        </tr>
    </tbody>
</table>

   
  <br pagebreak="true"/>';
  
  
     $html.='  <table border="1" cellspacing="0" cellpadding="5">
        <tr>
            <th>Sr. No</th>
            <th>Tests</th>
            <th>Acceptance Criteria</th>
            <th>Reference Test Method</th>
        </tr>
        <tr>
            <td>1.</td>
            <td>Appearance</td>
            <td>White or almost white or crystalline powder</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>2.</td>
            <td>Identification</td>
            <td>
                2.1. Phosphate reaction: yellow precipitation is generated during the heating of the solution<br>
                2.2. IR test: The infrared absorption spectrum is concordant with that obtained by the reference standard<br>
                2.3. HPLC: In the chromatogram recorded under the content determination, the retention time of the main peak of the test solution should be the same as that of the control solution<br>
                2.4. Sodium test: This product shows the reaction of sodium salt identification
            </td>
            <td>IH</td>
        </tr>
        <tr>
            <td>3.</td>
            <td>pH</td>
            <td>8.0–9.0 pH</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>4.</td>
            <td>Color and clarity of the product solution</td>
            <td>The solution shall be colorless and clear</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>5.</td>
            <td>Related substances</td>
            <td>
                Creatine, Creatinine and Sodium creatinine: NMT 0.5%; Any other individual unspecified impurity: NMT 0.10%; The total amount of impurities: NMT 2.0%
            </td>
            <td>IH</td>
        </tr>
        <tr>
            <td>6.</td>
            <td>Chloride</td>
            <td>NMT 0.02%</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>7.</td>
            <td>Barium</td>
            <td>Visual inspection, both liquids should be equally clarified</td>
            <td>IH</td>
        </tr> 
    
        <tr>
            <td>8.</td>
            <td><b>Heavy Metal</b></td>
            <td>NMT 0.001%</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>9.</td>
            <td><b>Water content</b></td>
            <td>22.0%-25.0%</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>10.</td>
            <td><b>Ethanol</b></td>
            <td>NMT 0.5%</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>11.</td>
            <td><b>Visible particle</b></td>
            <td>Should meet the requirements</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>12.</td>
            <td><b>Subvisible particle</b></td>
            <td>The number of particles containing 10µm and above in each sample shall not exceed 6000, and the number of particles containing 25µm and above shall not exceed 600</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>13.</td>
            <td><b>Abnormal toxicity</b></td>
            <td>Should comply with regulations</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>14.</td>
            <td><b>Sterility</b></td>
            <td>Should be sterile</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>15.</td>
            <td><b>Bacterial endotoxins</b></td>
            <td>NMT 0.15 EU/mg of Creatine Phosphate Sodium</td>
            <td>IH</td>
        </tr>
        <tr>
            <td>16.</td>
            <td><b>Assay</b></td>
            <td>NLT 98.0%</td>
            <td>IH</td>
        </tr>
    
</table>
<div></div>
<div></div>
    
    
    <table border="1" cellpadding="5">
            <tr>
                <th rowspan="3"><b>Signature / Date</b></th>
                <th ><b>PREPARED BY</b></th>
                <th><b>CHECKED BY</b></th>
                <th colspan="3" style="text-align:centre"><b>APPROVED BY</b></th>
            </tr>
             <tr>
                <td> </td>
                <td> </td>
                <td></td>
                <td></td>
                <td> </td>
            </tr>
           
            <tr>
                <td><b>Officer / Executive QC</b></td>
                <td><b>Sr. Executive/ AM QC</b></td>
                <td><b>Head QC</b></td>
                <td><b>Head ML</b></td>
                <td><b>Head QA</b></td>
            </tr>
        </table>
 
        
        ';    
        
        
        
        $html.=' </table>';
                        
                
                    // EOD;
                    $pdf->writeHTML($html, true, false, false, false, '');
                    $pdf->Output('Specification.pdf', 'I');
                
        
     }
     else if($_GET["plant_id"] == 177 || $_GET["plant_id"] == 149 ){//demo
            
                        $sql = "SELECT s.* ,m.material_type, m.material_subtype, m.material_name, m.grade,m.storage_condition as
                     material_storage_conditions,p.plant_name,p.plant_full_address FROM specification s LEFT JOIN material m 
                     ON s.material_code=m.material_code LEFT JOIN plant p on s.plant_id=p.plant_id WHERE 
                     specification_no='".$_GET['specification_no']."' LIMIT 1";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                   
                    $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
                    $html='';
                    
                  $html.='
                    <h3 style="text-align:center; ">RAW MATERIAL SPECIFICATION</h3>
                    <style>
                        .tdall { border:solid 1px BCBBBA; }
                        .tdb { border-bottom:solid 1px BCBBBA; }
                        .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
                    </style><br><br><br>
                    
                    <table border="1">
    
    <tr>
        <td style="width: 135px;"> Name of Material </td>
        <td style="width: 399px;"> '.$row["material_name"].'</td>
    </tr>
    <tr>
         <td style="width:135px;"> Reference </td>
        <td style="width: 135px;"> '.$row["special_grade"].'</td>
        <td style="width: 132px;"> Spec/STP No.</td>
        <td style="width: 132px;"> '.$row["specification_no"].'</td>
       
    </tr>
    <tr>
        <td style="width: 135px;"> Supersede No.</td>
        <td style="width: 135px;"> '.$row["supersede_no"].'</td>
         <td style="width: 132px;"> Revision no.</td>
        <td style="width: 132px;"> '.$row["version_no"].'</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Effective Date </td>
        <td style="width: 135px;"> '.$row["effective_date"].'</td>
        <td style="width: 132px;"> Review Date </td>
        <td style="width: 132px;"> '.$row["review_date"].'</td>
        
       
    </tr>
</table>';
            $html.='
                    <div></div>
                    <table style="border:solid 1px BCBBBA;" cellpadding="2">
                        <thead>
                            <tr style="background-color:#e8e6e6;font-weight:bold;" align="center">
                                <td class="tdall" style="width:7%">Sr.No</td>
                                <td class="tdall" style="width:31%">Test</td>
                                <td class="tdall" style="width:62%">Specification</td>
                            </tr>
                        </thead>
                        <tbody>';
                        $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                        $result1 = $conn->query($sql1);
                        $j =1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td class="tdall" style="width:7%" align="center;">'.$j++.'.</td>
                                <td class="tdall" style="width:31%">'.$row1["test"].'</td>
                                <td class="tdall" style="width:62%">'.$row1["limits"].'</td>
                            </tr>';
                        }
                       
                      $html.='
                    </table>
                    <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                    <table cellpadding="5">
                        <tr style="background-color:#DDDAD9; text-align:center;">
                            <td class="tdall">Revision No.</td>
                            <td class="tdall">Change Control No</td>
                            <td class="tdall">Reasons for Revision</td>
                        </tr>';
                        $sql2 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $html.='
                                <tr nobr="true">
                                    <td class="tdall">'.$row2["revision_no"].'</td>
                                    <td class="tdall">'.$row2["change_mode"].'</td>
                                    <td class="tdall">'.$row2["reason"].'</td>

                                </tr>';
                            }
                        }
                        $html.='
                    </table>
                      
                   
                     
                    ';
                        
                
                    // EOD;
                    $pdf->writeHTML($html, true, false, false, false, '');
                    $pdf->Output('Specification.pdf', 'I');
                
        }
        else {//demo
            
                        $sql = "SELECT s.* ,m.material_type, m.material_subtype, m.material_name, m.grade,m.storage_condition as
                     material_storage_conditions,p.plant_name,p.plant_full_address FROM specification s LEFT JOIN material m 
                     ON s.material_code=m.material_code LEFT JOIN plant p on s.plant_id=p.plant_id WHERE 
                     specification_no='".$_GET['specification_no']."' LIMIT 1";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                   
                    $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
                    $html='';
                    
                  $html.='
                    <h3 style="text-align:center; ">RAW MATERIAL SPECIFICATION...</h3>
                    <style>
                        .tdall { border:solid 1px BCBBBA; }
                        .tdb { border-bottom:solid 1px BCBBBA; }
                        .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
                    </style><br><br><br>
                    <table style="border:solid 1px BCBBBA;" cellpadding="2">
                        <tr>
                            <td class="tdb" style="width:15%;"><b>Department</b></td>
                            <td class="tdb" style="width:85%;">: <b>Quality Control</b></td>
                        </tr>
                        <tr>
                            <td class="tdb"><b>Material Name</b></td>
                            <td class="tdb">: '.$row["material_name"].'</td>
                        </tr>
                        <tr>
                            <td class="tdb"><b>Specification No</b></td>
                            <td class="tdbr" style="width:45%;">: '.$row['specification_no'].'</td>
                            <td class="tdb" style="width:15%;"><b>Material Code</b></td>
                            <td class="tdb" style="width:25%;">: '.$row["material_code"].'</td>
                        </tr>
                        <tr>
                            <td class="tdb"><b>Supersedes No</b></td>
                            <td class="tdb">: NA</td>
                            <td class="tdb"><b>Review Date</b></td>';
                            
                            if($row['review_date']=='0000-00-00'){
                              $html.='  <td class="tdb">: NA</td> ';
                            }else{
                            $html.='  <td class="tdb">: '.date('d-m-Y',strtotime($row['review_date'])).'</td> ';
            
                            }
                            
                        $html.='    </tr>
                        <tr>
                            <td class="tdb"><b>Retest Period</b></td>
                            <td class="tdbr">: 24 Months 0 Days</td>
                            <td class="tdb"><b>Page No</b></td>
                            <td class="tdb">:  1 Of 1 </td>
                            
                        </tr>
                        
                        <tr>
                            <td><b>Storage Condition</b></td>
                            <td colspan="3">: '.$row['material_storage_conditions'].'.</td>
                             
                        </tr>
                    </table>';
            $html.='
                    <div></div>
                    <table style="border:solid 1px BCBBBA;" cellpadding="2">
                        <thead>
                            <tr style="background-color:#e8e6e6;font-weight:bold;" align="center">
                                <td class="tdall" style="width:7%">Sr.No</td>
                                <td class="tdall" style="width:31%">Test</td>
                                <td class="tdall" style="width:31%">Specification</td>
                                <td class="tdall" style="width:31%">Reference</td>
                            </tr>
                        </thead>
                        <tbody>';
                        $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                        $result1 = $conn->query($sql1);
                        $j =1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td class="tdall" style="width:7%" align="center;">'.$j++.'.</td>
                                <td class="tdall" style="width:31%">'.$row1["test"].'</td>
                                <td class="tdall" style="width:31%">'.$row1["limits"].'</td>
                                <td class="tdall" style="width:31%">'.$row1["reference_type"].'</td>
                            </tr>';
                        }
                       
                      $html.='
                    </table>
                    <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                    <table cellpadding="5">
                        <tr style="background-color:#DDDAD9; text-align:center;">
                            <td class="tdall">Specification No.</td>
                            <td class="tdall">Revision No.</td>
                            <td class="tdall">Change Made</td>
                            <td class="tdall">Reasons for change</td>
                            <th class="tdall"> Effective Date </th>
                        </tr>';
                        $sql2 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $html.='
                                <tr nobr="true">
                                    <td class="tdall">'.$row["specification_no"].'</td>
                                    <td class="tdall">'.$row2["specification_no"].'</td>
                                    <td class="tdall">'.$row2["change_mode"].'</td>
                                    <td class="tdall">'.$row2["reason"].'</td>
                                    <td class="tdall">'.date('d-m-Y',strtotime($row2['effective_date'])).'</td>
                                    
                                </tr>';
                            }
                        }
                        $html.='
                    </table>
                      
                   
                     
                    ';
                        
                
                    // EOD;
                    $pdf->writeHTML($html, true, false, false, false, '');
                    $pdf->Output('Specification.pdf', 'I');
                
        }
    }
    else if($_GET['type'] == 'SpecificationdigitalPDF') {
        if($_GET["plant_id"] == 72) {
            
                         $sql = "SELECT s.* ,m.material_type, m.material_subtype, m.material_name, m.grade,m.storage_condition as
                     material_storage_conditions,p.plant_name,p.plant_full_address FROM specification s LEFT JOIN material m 
                     ON s.material_code=m.material_code LEFT JOIN plant p on s.plant_id=p.plant_id WHERE 
                     specification_no='".$_GET['specification_no']."' LIMIT 1";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                    // $_GET['filename'] = 'RAW MATERIAL SPECIFICATION'; 
                    // $_GET['pdftype'] = 'headfootdigital'; include("../../pdfimp.php");
                    $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
                    
                    
                    $html.='
                    <h3 style="text-align:center; ">RAW MATERIAL SPECIFICATION</h3>
                    <style>
                        .tdall { border:solid 1px BCBBBA; }
                        .tdb { border-bottom:solid 1px BCBBBA; }
                        .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
                    </style><br><br><br>
                    <table style="border:solid 1px BCBBBA;" cellpadding="2">
                        <tr>
                            <td class="tdb" style="width:15%;"><b>Department 1</b></td>
                            <td class="tdb" style="width:85%;">: <b>Quality Control</b></td>
                        </tr>
                        <tr>
                            <td class="tdb"><b>Material Name</b></td>
                            <td class="tdb">: '.$row["material_name"].'</td>
                        </tr>
                        <tr>
                            <td class="tdb"><b>Specification No</b></td>
                            <td class="tdbr" style="width:45%;">: '.$row['specification_no'].'</td>
                            <td class="tdb" style="width:15%;"><b>Material Code</b></td>
                            <td class="tdb" style="width:25%;">: '.$row["material_code"].'</td>
                        </tr>
                        <tr>
                            <td class="tdb"><b>Supersedes No</b></td>
                            <td class="tdb">: NA</td>
                            <td class="tdb"><b>Review Date</b></td>';
                            
                            if($row['review_date']=='0000-00-00'){
                              $html.='  <td class="tdb">: NA</td> ';
                            }else{
                            $html.='  <td class="tdb">: '.date('d-m-Y',strtotime($row['review_date'])).'</td> ';
            
                            }
                            
                        $html.='    </tr>
                        <tr>
                            <td class="tdb"><b>Retest Period</b></td>
                            <td class="tdbr">: '.$row['retest_period'].'</td>
                            <td class="tdb"><b>Page No</b></td>
                            <td class="tdb">:  1 Of 1 </td>
                            
                        </tr>
                        
                        <tr>
                            <td><b>Storage Condition</b></td>
                            <td colspan="3">: '.$row['material_storage_conditions'].'.</td>
                             
                        </tr>
                    </table>
                    <div></div>
                    <table style="border:solid 1px BCBBBA;" cellpadding="2">
                        <thead>
                            <tr style="background-color:#e8e6e6;font-weight:bold;" align="center">
                                <td class="tdall" style="width:7%">Sr.</td>
                                <td class="tdall" style="width:31%">Test</td>
                                <td class="tdall" style="width:31%">Specification</td>
                                <td class="tdall" style="width:31%">Reference</td>
                            </tr>
                        </thead>
                        <tbody>';
                        $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                        $result1 = $conn->query($sql1);
                        $j =1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td class="tdall" style="width:7%" align="center;">'.$j++.'.</td>
                                <td class="tdall" style="width:31%">'.$row1["test"].'</td>
                                <td class="tdall" style="width:31%">'.$row1["limits"].'</td>
                                <td class="tdall" style="width:31%">'.$row1["reference_type"].'</td>
                            </tr>';
                        }
                       
                      $html.='
                    </table>
                    <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                    <table cellpadding="5">
                        <tr style="background-color:#DDDAD9; text-align:center;">
                            <td class="tdall">Revision No.</td>
                             <td class="tdall">Reasons for change</td>
                            <th class="tdall"> Effective Date </th>
                        </tr>';
                        $sql2 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $html.='
                                <tr nobr="true">
                                    <td class="tdall">'.$row2["specification_no"].'</td>
                                     <td class="tdall">'.$row2["reason"].'</td>
                                    <td class="tdall">'.date('d-m-Y',strtotime($row2['effective_date'])).'</td>
                                    
                                </tr>';
                                
                            }
                        }
                       
                        $html.='
                    </table>
                    <div>
                    </div>
                    <div>
                    </div>
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
                              <td style="width:191px;">:' . $row['entry_by'] . '</td>
                             
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
                              <td style="width:70px;">:' . date('d-m-Y', strtotime($row['entry_date'])) . '</td>
                              <td style="width: 50px;">Time</td>
                              <td style="width: 81px;">:' . date('H:i:s', strtotime($row['entry_date'])) . '</td>
                          </tr>
                          <br>
                      <tr>
                      <td style="width: 270px;font-size:10px;">For, '.$row['plant_name'].' </td>
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
                      <td style="width: 265px;font-size:10px;">For, '.$row['plant_name'].' </td>
                      </tr>
                      </table>
                  </td>
              </tr>
              <tr>
                          <td style="width: 540px;text-align:center">' . $row['plant_full_address'] . '</td>
                          </tr>
            </table>
                    ';
                        
                
                    EOD;
                    $pdf->writeHTML($html, true, false, false, false, '');
                    $pdf->Output('Specification.pdf', 'I');
                
        }
        else{
            
                        $sql = "SELECT s.* ,m.material_type, m.material_subtype, m.material_name, m.grade,m.storage_condition as
                     material_storage_conditions,p.plant_name,p.plant_full_address FROM specification s LEFT JOIN material m 
                     ON s.material_code=m.material_code LEFT JOIN plant p on s.plant_id=p.plant_id WHERE 
                     specification_no='".$_GET['specification_no']."' LIMIT 1";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                    // $_GET['filename'] = 'RAW MATERIAL SPECIFICATION'; 
                    // $_GET['pdftype'] = 'headfootdigital'; include("../../pdfimp.php");
                    $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
                    
                    
                    $html.='
                    <h3 style="text-align:center; ">RAW MATERIAL SPECIFICATION</h3>
                    <style>
                        .tdall { border:solid 1px BCBBBA; }
                        .tdb { border-bottom:solid 1px BCBBBA; }
                        .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
                    </style><br><br><br>
                    <table style="border:solid 1px BCBBBA;" cellpadding="2">
                        <tr>
                            <td class="tdb" style="width:15%;"><b>Department</b></td>
                            <td class="tdb" style="width:85%;">: <b>Quality Control</b></td>
                        </tr>
                        <tr>
                            <td class="tdb"><b>Material Name</b></td>
                            <td class="tdb">: '.$row["material_name"].'</td>
                        </tr>
                        <tr>
                            <td class="tdb"><b>Specification No</b></td>
                            <td class="tdbr" style="width:45%;">: '.$row['specification_no'].'</td>
                            <td class="tdb" style="width:15%;"><b>Material Code</b></td>
                            <td class="tdb" style="width:25%;">: '.$row["material_code"].'</td>
                        </tr>
                        <tr>
                            <td class="tdb"><b>Supersedes No</b></td>
                            <td class="tdb">: NA</td>
                            <td class="tdb"><b>Review Date</b></td>';
                            
                            if($row['review_date']=='0000-00-00'){
                              $html.='  <td class="tdb">: NA</td> ';
                            }else{
                            $html.='  <td class="tdb">: '.date('d-m-Y',strtotime($row['review_date'])).'</td> ';
            
                            }
                            
                        $html.='    </tr>
                        <tr>
                            <td class="tdb"><b>Retest Period</b></td>
                            <td class="tdbr">: 24 Months 0 Days</td>
                            <td class="tdb"><b>Page No</b></td>
                            <td class="tdb">:  1 Of 1 </td>
                            
                        </tr>
                        
                        <tr>
                            <td><b>Storage Condition</b></td>
                            <td colspan="3">: '.$row['material_storage_conditions'].'.</td>
                             
                        </tr>
                    </table>
                    <div></div>
                    <table style="border:solid 1px BCBBBA;" cellpadding="2">
                        <thead>
                            <tr style="background-color:#e8e6e6;font-weight:bold;" align="center">
                                <td class="tdall" style="width:7%">Sr.No</td>
                                <td class="tdall" style="width:31%">Test</td>
                                <td class="tdall" style="width:31%">Specification</td>
                                <td class="tdall" style="width:31%">Reference</td>
                            </tr>
                        </thead>
                        <tbody>';
                        $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                        $result1 = $conn->query($sql1);
                        $j =1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td class="tdall" style="width:7%" align="center;">'.$j++.'.</td>
                                <td class="tdall" style="width:31%">'.$row1["test"].'</td>
                                <td class="tdall" style="width:31%">'.$row1["limits"].'</td>
                                <td class="tdall" style="width:31%">'.$row1["reference_type"].'</td>
                            </tr>';
                        }
                       
                      $html.='
                    </table>
                    <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                    <table cellpadding="5">
                        <tr style="background-color:#DDDAD9; text-align:center;">
                            <td class="tdall">Specification No.</td>
                            <td class="tdall">Revision No.</td>
                            <td class="tdall">Change Made</td>
                            <td class="tdall">Reasons for change</td>
                            <th class="tdall"> Effective Date </th>
                        </tr>';
                        $sql2 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $html.='
                                <tr nobr="true">
                                    <td class="tdall">'.$row["specification_no"].'</td>
                                    <td class="tdall">'.$row2["specification_no"].'</td>
                                    <td class="tdall">'.$row2["change_mode"].'</td>
                                    <td class="tdall">'.$row2["reason"].'</td>
                                    <td class="tdall">'.date('d-m-Y',strtotime($row2['effective_date'])).'</td>
                                    
                                </tr>';
                            }
                        }
                        $html.='
                    </table>
                      <table border="1" cellpadding="3">
                  <tr style="background-color:black; color:white;">
                  <td style="width: 166.6px;text-align:center;"><b>Checked By & Digital Signed By</b></td>
                  <td style="width: 166.6px;text-align:center;"><b>Approved By & Digital Signed By</b></td>
                  <td style="width: 166.6px;text-align:center;"><b>Analyzed By & Digital Signed By</b></td>
               <td style="width: 40px;text-align:center;"><img src="../../upload/pdf/sign.jpg" style="width:20px;height:20px;"></td>

              </tr>
              <tr>
                  <td style="width: 270px;" >
                      <table>
                          <tr>
                              <td style="width:70px;">Name</td>
                              <td style="width:191px;">:' . $row['entry_by'] . '</td>
                             
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
                              <td style="width:70px;">:' . date('d-m-Y', strtotime($row['entry_date'])) . '</td>
                              <td style="width: 50px;">Time</td>
                              <td style="width: 81px;">:' . date('H:i:s', strtotime($row['entry_date'])) . '</td>
                          </tr>
                          <br>
                      <tr>
                      <td style="width: 270px;font-size:10px;">For, '.$row['plant_name'].' </td>
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
                      <td style="width: 265px;font-size:10px;">For, '.$row['plant_name'].' </td>
                      </tr>
                      </table>
                  </td>
              </tr>
              <tr>
                          <td style="width: 540px;text-align:center">' . $row['plant_full_address'] . '</td>
                          </tr>
            </table>
                    ';
                        
                
                    // EOD;
                    $pdf->writeHTML($html, true, false, false, false, '');
                    $pdf->Output('Specification.pdf', 'I');
                
        }
    }else if ($_GET["type"] == "Specification") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM specification s LEFT JOIN material m ON s.material_code=m.material_code WHERE specification_no='".$_GET['specification_no']."' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $sql2 = "SELECT * FROM sampling WHERE specification_no='".$row["specification_no"]."'";
                // $result2 = $conn->query($sql2);
                // if ($result2->num_rows > 0) {
                //     while ($row2 = $result2->fetch_assoc()) {
                        $html.='<h3 style="text-align:center;">Specification Report</h3>
                        <table border="1" cellpadding="3">
                            <tr>
                                <td style="width:25%;font-weight:bold">Specification No </td>
                                <td style="width:25%;">'.$row['specification_no'].'</td>
                                <td style="width:25%;font-weight:bold">Specification Type :</td>
                                <td style="width:25%;">'.$row["spec_type"].'</td>
                            </tr>
                            <tr>
                                <td style="width:25%;font-weight:bold">Material Type </td>
                                <td style="width:25%;">'.$row["material_subtype"].'</td>
                                <td style="width:25%;font-weight:bold">Material Name</td>
                                <td style="width:25%;">'.$row["material_name"].'</td>
                            </tr>
                            <tr>
                                <td style="width:25%;font-weight:bold">Material Grade</td>
                                <td style="width:25%;">'.$row["grade"].'</td>
                                <td style="width:25%;font-weight:bold">Version No</td>
                                <td style="width:25%;">'.$row["version_no"].'</td>
                            </tr>
                            <tr>
                                <td style="width:25%;font-weight:bold">Supersede No</td>
                                <td style="width:25%;">'.$row['supersede_no'].'</td>
                                <td style="width:25%;font-weight:bold">Chemical Sample Qty</td>
                                <td style="width:25%;">'.$row['sample_qty'].'</td>
                            </tr>
                            <tr>
                                <td style="width:25%;font-weight:bold">Micro Analysis Qty</td>
                                <td style="width:25%;">'.$row['qty'].'</td>
                                <td style="width:25%;font-weight:bold">Molecular Weight </td>
                                <td style="width:25%;"></td>
                            </tr>
                            <tr>
                                <td style="width:25%;font-weight:bold">Molecular Formula</td>
                                <td style="width:75%;"></td>
                            </tr>
                            <tr>
                                <td style="width:25%;font-weight:bold">Structural Formula </td>
                                <td style="width:75%;"></td>
                            </tr>
                            <tr>
                                <td style="width:25%;font-weight:bold">Storage Condition </td>
                                <td style="width:75%;"> Store Protected from light and moisture, at a temperature not exceeding 30oC.</td>
                            </tr>
                            <tr>
                                <td style="width:25%;font-weight:bold">Pack Unit </td>
                                <td style="width:25%;"></td>
                                <td style="width:25%;font-weight:bold">Expiry Period</td>
                                <td style="width:25%;"></td>
                            </tr>
                            <tr>
                                <td style="width:25%;font-weight:bold">Sampling Plan</td>
                                <td style="width:25%;"></td>
                                <td style="width:25%;font-weight:bold">Sample to be drawn from</td>
                                <td style="width:25%;"></td>
                            </tr>
                            <tr>
                                <td style="width:25%;font-weight:bold">Hazaradous And Precautions </td>
                                <td style="width:25%;"></td>
                                <td style="width:25%;font-weight:bold">Sample to be drawn with </td>
                                <td style="width:25%;"></td>
                            </tr>
                        </table>
                        <h3 style="text-align:center;">Pharmacopoeial Tests</h3>
                        <table border="1" cellpadding="3">
                            <tr>
                                <td style="width:15%;font-weight:bold;">Test Type</td>
                                <td style="width:15%;font-weight:bold;">Test</td>
                                <td style="width:15%;font-weight:bold;">Sub Test</td>
                                <td style="width:15%;font-weight:bold;">Reference Type</td>
                                <td style="width:15%;font-weight:bold;">Limit Type</td>
                                <td style="width:15%;font-weight:bold;">Limits</td>
                                <td style="width:10%;font-weight:bold;">Retest</td>
                            </tr>';
                        $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td style="width:15%;"></td>
                                <td style="width:15%;"></td>
                                <td style="width:15%;"></td>
                                <td style="width:15%;"></td>
                                <td style="width:15%;"></td>
                                <td style="width:15%;"></td>
                                <td style="width:10%;"></td>
                            </tr>';
                            }
                        }
                        $html.='
                        </table>
                        <h3 style="text-align:center;">Client Specific Tests</h3>
                        <table border="1" cellpadding="3">
                            <tr>
                                <td style="width:10%;font-weight:bold;">Client Code</td>
                                <td style="width:15%;font-weight:bold;">Client Name</td>
                                <td style="width:12%;font-weight:bold;">Test Type</td>
                                <td style="width:12%;font-weight:bold;">Test</td>
                                <td style="width:11%;font-weight:bold;">Sub Test</td>
                                <td style="width:10%;font-weight:bold;">Reference Type</td>
                                <td style="width:10%;font-weight:bold;">Limit Type</td>
                                <td style="width:10%;font-weight:bold;">Limits</td>
                                <td style="width:10%;font-weight:bold;">Retest</td>
                            </tr>';
                        $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td style="width:10%;"></td>
                                <td style="width:15%;"></td>
                                <td style="width:12%;">'.$row[''].'</td>
                                <td style="width:12%;">'.$row1['test'].'</td>
                                <td style="width:11%;">'.$row1['subtest'].'</td>
                                <td style="width:10%;">'.$row1['reference_type'].'</td>
                                <td style="width:10%;">'.$row1['limit_type'].'</td>
                                <td style="width:10%;">'.$row1['lower_limit'].'-'.$row1['upper_limit'].'</td>
                                <td style="width:10%;">'.$row1['retest'].'</td>
                            </tr>';
                            }
                        }
                        $html.='
                        </table>
                        <h3 style="text-align:center;">Revision History:</h3>
                        <table border="1" cellpadding="3">
                            <tr>
                                <td style="width:20%;font-weight:bold;">Sr</td>
                                <td style="width:20%;font-weight:bold;">Specification No</td>
                                <td style="width:20%;font-weight:bold;">Version No</td>
                                <td style="width:20%;font-weight:bold;">Change Mode</td>
                                <td style="width:20%;font-weight:bold;">Reason for change</td>
                            </tr>';
                        $i=1;
                        $sql2 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $html.='<tr>
                                    <td style="width:20%;">'.$i++.'</td>
                                    <td style="width:20%;">'.$row2['specification_no'].'</td>
                                    <td style="width:20%;">'.$row2['version_no'].'</td>
                                    <td style="width:20%;">'.$row2['change_mode'].'</td>
                                    <td style="width:20%;">'.$row2['reason'].'</td>
                                </tr>';
                            }
                        }
                        $html.='
                        </table>';
                //     }
                // }
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
        
    }
    // else if ($_GET["type"] == "SpecificationLogPDF") {
    //     $output = Array();
    //     $sql = "SELECT * FROM training_needs WHERE plant_id = '".$_GET["plant_id"]."'";
    //     $result = $conn->query($sql);
    //     if($result->num_rows > 0) {
    //         while($row = $result->fetch_assoc()) {
                
    //             $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row0 = $result1->fetch_assoc()) {
    //                     $row["trainer_name"] = $row0["trainer_name"];
    //                 }
    //             }

    //             $output1 = Array();
    //              $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {

    //                     $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
    //                     $result2 = $conn->query($sql2);
    //                     if ($result2->num_rows > 0) {
    //                         while ($row2 = $result2->fetch_assoc()) {
    //                             $row1["firstname"] = $row2["firstname"];
    //                             $row1["lastname"] = $row2["lastname"];
    //                             $row1["department"] = $row2["department"];
    //                             $row1["designation"] = $row2["designation"];
    //                         }
    //                     }
    //                     $output1[] = $row1;
    //                 }
    //             }
    //             $row["employees"] = $output1;
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }
    else if($_GET['type'] == 'downloadRawMaterialSpecificationReport') {
         $_GET['filename'] = 'Raw Material Specification Report'; $_GET['pdftype'] = 'onlyheader'; include('../../pdfimp2.php');
         $html.='
         <h2 style="text-align:cenetr">Raw Material Specification Report</h2>
         <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:10%; text-align:centre;"><b>Spec No</b></td>
                <td style="width:15%; text-align:centre;"><b>Material Type</b></td>
                <td style="width:15%; text-align:centre;"><b>Material Name</b></td>
                <td style="width:10%; text-align:centre;"><b>Grade</b></td>
                <td style="width:15%; text-align:centre;"><b>Material Code</b></td>
                <td style="width:10%; text-align:centre;"><b>Version No</b></td>
                <td style="width:15%; text-align:centre;"><b>Revision Period</b></td>
            </tr>';
            $i=1;
            $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM specification s LEFT JOIN material m ON s.material_code=m.material_code WHERE s.user_no='".$_GET["user_no"]."' AND s.spec_type LIKE 'Raw Material%'  ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:10%;">'.$i.'.</td>
                <td style="width:10%;">'.$row['specification_no'].'</td>
                <td style="width:15%;">'.$row['material_subtype'].'</td>
                <td style="width:15%;">'.$row['material_name'].'</td>
                <td style="width:10%;">'.$row['grade'].'</td>
                <td style="width:15%;">'.$row['material_code'].'</td>
                <td style="width:10%;">'.$row['version_no'].'</td>
                <td style="width:15%;">'.$row['revision_period'].'</td>
            </tr>';
            $i++;
            }
        }
         $html.='</table>';
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('RawMaterialSpecificationReport.pdf', 'I');
    }
    else if($_GET['type'] == 'SpecificationLogPDF') {
         $_GET['filename'] = 'Raw Material Specification Report'; $_GET['pdftype'] = 'onlyheader'; include('../../pdfimp2.php');
         $html.='
         <h2 style="text-align:center">Raw Material Specification Report</h2>
         <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:center;"><b>Sr.</b></td>
                <td style="width:10%; text-align:center;"><b>Spec No</b></td>
                <td style="width:15%; text-align:center;"><b>Material Type</b></td>
                <td style="width:15%; text-align:center;"><b>Material Name</b></td>
                <td style="width:10%; text-align:center;"><b>Grade</b></td>
                <td style="width:15%; text-align:center;"><b>Material Code</b></td>
                <td style="width:10%; text-align:center;"><b>Version No</b></td>
                <td style="width:15%; text-align:center;"><b>C C Number</b></td>
            </tr>';
            $i=1;
              $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade 
        FROM specification s LEFT JOIN material m ON s.material_code=m.material_code 
        WHERE m.plant_id= '".$_GET["plant_id"]."' AND s.user_no='".$_GET["user_no"]."' AND s.spec_type LIKE 'Raw Material%' AND 
        ( s.status = 'approve' OR s.status = 'reject') ORDER BY s.id DESC";
        
        //   $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade 
        //             FROM specification s 
        //             LEFT JOIN material m 
        //             ON s.material_code = m.material_code 
        //             AND m.plant_id = '".$_GET["plant_id"]."' 
        //             WHERE s.user_no = '".$_GET["user_no"]."' 
        //             AND s.spec_type LIKE 'Packing Material%' 
        //             AND (s.status = 'approve' OR s.status = 'reject') 
        //             ORDER BY s.id DESC"; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:10%;">'.$i.'.</td>
                <td style="width:10%;">'.$row['specification_no'].'</td>
                <td style="width:15%;">'.$row['material_subtype'].'</td>
                <td style="width:15%;">'.$row['material_name'].'</td>
                <td style="width:10%;">'.$row['grade'].'</td>
                <td style="width:15%;">'.$row['material_code'].'</td>
                <td style="width:10%;">'.$row['version_no'].'</td>
                <td style="width:15%;">'.$row['cc_no'].'</td>
            </tr>';
            $i++;
            }
        }
        
         $html.='</table>';
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('RawMaterialSpecificationReport.pdf', 'I');
    }
    else if ($_GET['type'] == 'downloadCombinedSpecificationLogs') {
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE);
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $log_type = isset($_GET['log_type']) ? trim($_GET['log_type']) : 'Raw Material';
        $plantEsc = $conn->real_escape_string((string)($_GET['plant_id'] ?? ''));
        $logTypeEsc = $conn->real_escape_string($log_type);

        if ($log_type == 'Raw Material' || $log_type == 'Packing Material') {
            $sql = "SELECT a.*, b.material_name, '".$logTypeEsc."' as log_type
                    FROM specification a
                    LEFT JOIN material b ON a.material_code = b.material_code
                    WHERE a.status != 'Rejected'
                    AND a.plant_id = '".$plantEsc."'
                    AND a.spec_type = '".$logTypeEsc."'
                    ORDER BY a.id DESC";
        } else if ($log_type == 'Finish Product') {
            $sql = "SELECT a.*, b.product_name as material_name, 'Finish Product' as log_type
                    FROM specification a
                    LEFT JOIN product b ON a.material_code = b.product_code
                    WHERE a.status != 'Rejected'
                    AND a.plant_id = '".$plantEsc."'
                    AND a.spec_type = 'Finish Product'
                    ORDER BY a.id DESC";
        } else if ($log_type == 'Inprocess') {
            $sql = "SELECT a.*, b.product_name as material_name, 'Inprocess' as log_type
                    FROM specification a
                    LEFT JOIN product b ON a.product_code = b.product_code
                    WHERE a.status != 'Rejected'
                    AND a.plant_id = '".$plantEsc."'
                    AND a.spec_type LIKE 'Inprocess%'
                    ORDER BY a.id DESC";
        } else if ($log_type == 'Water Specification') {
            $sql = "SELECT a.*, a.water_type as material_name, 'Water Specification' as log_type
                    FROM specification a
                    WHERE a.status != 'Rejected'
                    AND a.plant_id = '".$plantEsc."'
                    AND a.spec_type = 'Water Specification'
                    ORDER BY a.id DESC";
        } else {
            $sql = "SELECT a.*, b.material_name, 'Raw Material' as log_type
                    FROM specification a
                    LEFT JOIN material b ON a.material_code = b.material_code
                    WHERE a.status != 'Rejected'
                    AND a.plant_id = '".$plantEsc."'
                    AND a.spec_type = 'Raw Material'
                    ORDER BY a.id DESC";
        }

        $_GET['filename'] = $log_type . ' Specification Log';
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdfpage'] = 'L';
        include('../../pdfimp2.php');
        if (isset($pdf) && is_object($pdf)) {
            @$pdf->setPrintHeader(false);
            @$pdf->setPrintFooter(false);
        }

        $html .= '
        <h2 style="text-align:center; font-size:14px; margin:4px 0 8px 0;">' . htmlspecialchars($log_type, ENT_QUOTES, 'UTF-8') . ' Specification Log</h2>
        <table cellpadding="3" border="1" style="border-collapse:collapse; width:100%;">
            <tr style="background-color:#DDDAD9; text-align:center; font-weight:bold;">
                <td style="width:4%; font-size:7px;">Sr.</td>
                <td style="width:11%; font-size:7px;">Spec. No.</td>
                <td style="width:10%; font-size:7px;">Spec. Type</td>
                <td style="width:9%; font-size:7px;">Item Code</td>
                <td style="width:16%; font-size:7px;">Item Name</td>
                <td style="width:7%; font-size:7px;">Ver.</td>
                <td style="width:9%; font-size:7px;">Supersede</td>
                <td style="width:8%; font-size:7px;">Status</td>
                <td style="width:9%; font-size:7px;">Entry By/On</td>
                <td style="width:9%; font-size:7px;">Check By/On</td>
                <td style="width:8%; font-size:7px;">App. By/On</td>
            </tr>';

        $i = 1;
        $result = @$conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $entryOn = (!empty($row['entry_date']) && $row['entry_date'] !== '0000-00-00 00:00:00')
                    ? date('d-m-Y', strtotime($row['entry_date'])) : '-';
                $checkOn = (!empty($row['check_date']) && $row['check_date'] !== '0000-00-00 00:00:00')
                    ? date('d-m-Y', strtotime($row['check_date'])) : '-';
                $approveOn = (!empty($row['approve_date']) && $row['approve_date'] !== '0000-00-00 00:00:00')
                    ? date('d-m-Y', strtotime($row['approve_date'])) : '-';
                $itemCode = trim((string)($row['material_code'] ?? ''));
                if ($itemCode === '' && !empty($row['product_code'])) {
                    $itemCode = (string)$row['product_code'];
                }
                if ($itemCode === '' && !empty($row['water_type'])) {
                    $itemCode = (string)$row['water_type'];
                }
                $itemName = trim((string)($row['material_name'] ?? ''));
                if ($itemName === '' && !empty($row['water_type'])) {
                    $itemName = (string)$row['water_type'];
                }
                $html .= '
                <tr nobr="true">
                    <td style="width:4%; font-size:7px; text-align:center;">' . $i++ . '</td>
                    <td style="width:11%; font-size:7px; text-align:center;">' . htmlspecialchars($row['specification_no'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
                    <td style="width:10%; font-size:7px; text-align:center;">' . htmlspecialchars($row['spec_type'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
                    <td style="width:9%; font-size:7px; text-align:center;">' . htmlspecialchars($itemCode !== '' ? $itemCode : '-', ENT_QUOTES, 'UTF-8') . '</td>
                    <td style="width:16%; font-size:7px; text-align:left;">' . htmlspecialchars($itemName !== '' ? $itemName : '-', ENT_QUOTES, 'UTF-8') . '</td>
                    <td style="width:7%; font-size:7px; text-align:center;">' . htmlspecialchars($row['version_no'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
                    <td style="width:9%; font-size:7px; text-align:center;">' . htmlspecialchars($row['supersede_no'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
                    <td style="width:8%; font-size:7px; text-align:center;">' . htmlspecialchars($row['status'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
                    <td style="width:9%; font-size:7px; text-align:center;">' . htmlspecialchars($row['entry_by'] ?? '-', ENT_QUOTES, 'UTF-8') . '<br/>' . $entryOn . '</td>
                    <td style="width:9%; font-size:7px; text-align:center;">' . htmlspecialchars($row['check_by'] ?? '-', ENT_QUOTES, 'UTF-8') . '<br/>' . $checkOn . '</td>
                    <td style="width:8%; font-size:7px; text-align:center;">' . htmlspecialchars($row['approve_by'] ?? '-', ENT_QUOTES, 'UTF-8') . '<br/>' . $approveOn . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="11" style="text-align:center; font-size:8px;">No specification records found.</td></tr>';
        }

        $html .= '</table>';
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Specification_Log.pdf', 'I');
        exit;
    }
    else if ($_GET['type'] == 'downloadApprovedSpecificationForm') {
        $specNo = $conn->real_escape_string(trim($_GET['specification_no'] ?? ''));
        $plantId = $conn->real_escape_string(trim($_GET['plant_id'] ?? ''));
        if ($specNo === '') {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Specification number is required.';
            exit;
        }

        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade,
                m.storage_condition AS material_storage_conditions,
                p.plant_name, p.plant_full_address
                FROM specification s
                LEFT JOIN material m ON s.material_code = m.material_code
                LEFT JOIN plant p ON s.plant_id = p.plant_id
                WHERE s.specification_no = '" . $specNo . "'";
        if ($plantId !== '') {
            $sql .= " AND (s.plant_id = '" . $plantId . "' OR TRIM(IFNULL(s.plant_id,'')) = '')";
        }
        $sql .= " LIMIT 1";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Specification not found for ' . htmlspecialchars($specNo, ENT_QUOTES, 'UTF-8') . '.';
            exit;
        }
        $row = $result->fetch_assoc();

        $_GET['filename'] = 'Raw Material Specification';
        $_GET['pdftype'] = 'onlyheader';
        include('../../pdfimp2.php');

        $reviewDate = 'NA';
        if (!empty($row['review_date']) && $row['review_date'] !== '0000-00-00') {
            $reviewDate = date('d-m-Y', strtotime($row['review_date']));
        }
        $retestPeriod = trim((string)($row['retest_period'] ?? ''));
        if ($retestPeriod === '') {
            $retestPeriod = '24 Months 0 Days';
        }
        $storage = trim((string)($row['material_storage_conditions'] ?? $row['storage_condition'] ?? ''));
        if ($storage === '') {
            $storage = 'NA';
        }

        $html .= '
        <h3 style="text-align:center;">RAW MATERIAL SPECIFICATION</h3>
        <style>
            .tdall { border:solid 1px #BCBBBA; }
            .tdb { border-bottom:solid 1px #BCBBBA; }
            .tdbr { border-bottom:solid 1px #BCBBBA; border-right:solid 1px #BCBBBA; }
        </style>
        <br><br>
        <table style="border:solid 1px #BCBBBA;" cellpadding="2">
            <tr>
                <td class="tdb" style="width:15%;"><b>Department</b></td>
                <td class="tdb" style="width:85%;">: <b>Quality Control</b></td>
            </tr>
            <tr>
                <td class="tdb"><b>Material Name</b></td>
                <td class="tdb">: ' . htmlspecialchars($row['material_name'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td class="tdb"><b>Specification No</b></td>
                <td class="tdbr" style="width:45%;">: ' . htmlspecialchars($row['specification_no'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                <td class="tdb" style="width:15%;"><b>Material Code</b></td>
                <td class="tdb" style="width:25%;">: ' . htmlspecialchars($row['material_code'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td class="tdb"><b>Supersedes No</b></td>
                <td class="tdb">: ' . htmlspecialchars($row['supersede_no'] ?? 'NA', ENT_QUOTES, 'UTF-8') . '</td>
                <td class="tdb"><b>Review Date</b></td>
                <td class="tdb">: ' . $reviewDate . '</td>
            </tr>
            <tr>
                <td class="tdb"><b>Retest Period</b></td>
                <td class="tdbr">: ' . htmlspecialchars($retestPeriod, ENT_QUOTES, 'UTF-8') . '</td>
                <td class="tdb"><b>Page No</b></td>
                <td class="tdb">: 1 Of 1</td>
            </tr>
            <tr>
                <td><b>Storage Condition</b></td>
                <td colspan="3">: ' . htmlspecialchars($storage, ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>
        <div></div>
        <table style="border:solid 1px #BCBBBA;" cellpadding="2">
            <thead>
                <tr style="background-color:#e8e6e6;font-weight:bold;" align="center">
                    <td class="tdall" style="width:7%">Sr.No</td>
                    <td class="tdall" style="width:31%">Test</td>
                    <td class="tdall" style="width:31%">Specification</td>
                    <td class="tdall" style="width:31%">Reference</td>
                </tr>
            </thead>
            <tbody>';

        $specTests = raw_spec_tests_for_row($conn, $_GET['plant_id'] ?? '', $row);
        $j = 1;
        if (count($specTests) > 0) {
            foreach ($specTests as $row1) {
                $html .= '
                <tr>
                    <td class="tdall" align="center;">' . $j++ . '.</td>
                    <td class="tdall">' . htmlspecialchars($row1['test'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="tdall">' . htmlspecialchars($row1['limits'] ?? ($row1['description'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="tdall">' . htmlspecialchars($row1['reference_type'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td class="tdall" colspan="4" align="center">No test lines found.</td></tr>';
        }

        $html .= '
            </tbody>
        </table>
        <p style="text-align:center;"><b>REVISION HISTORY</b></p>
        <table cellpadding="5" border="1">
            <tr style="background-color:#DDDAD9; text-align:center;">
                <td class="tdall">Specification No.</td>
                <td class="tdall">Revision No.</td>
                <td class="tdall">Change Made</td>
                <td class="tdall">Reasons for change</td>
                <td class="tdall">Effective Date</td>
            </tr>';

        $revisionRows = raw_spec_revisions_for_row($conn, $_GET['plant_id'] ?? '', $row);
        if (count($revisionRows) > 0) {
            foreach ($revisionRows as $row2) {
                $eff = (!empty($row2['effective_date']) && $row2['effective_date'] !== '0000-00-00')
                    ? date('d-m-Y', strtotime($row2['effective_date']))
                    : '';
                $html .= '
                <tr>
                    <td class="tdall">' . htmlspecialchars($row['specification_no'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="tdall">' . htmlspecialchars($row2['version_no'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="tdall">' . htmlspecialchars($row2['change_mode'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="tdall">' . htmlspecialchars($row2['reason'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="tdall">' . $eff . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td class="tdall" colspan="5" align="center">No revision history added.</td></tr>';
        }

        $html .= '</table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $specNo) . '.pdf', 'I');
        exit;
    }
}

$conn->close();
?>