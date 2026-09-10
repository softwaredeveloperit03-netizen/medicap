<?php
require '../db.php';
require '../token.php';

header('Access-Control-Allow-Origin: *');

        ini_set('display_errors', 1);
        error_reporting(E_ALL);


date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
     
    if ($_GET["type"] == "getCompleteLeadForNpd") {
        $output = Array();
        
         
        $sql = "SELECT e.*,c.LglNm , b.firstname as entryByName FROM enquiry e 
        LEFT JOIN client_combined_view c ON e.client_code = c.client_code  
        LEFT JOIN employee b ON e.entryBy = b.emp_id  
        WHERE e.status = 'Complete'  order by e.id desc"; 
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   
                $output1 = Array();
                $sql1 = "SELECT * FROM enquiryProduct where status != 'Pending' AND enquiryId = '".$row["id"]."' ";   
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $row['productList'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "acknowledgeEnquiry") {
        
        $sql = "UPDATE enquiry SET acknowledgeStatus = 'Acknowledged', acknowledgeBy = '".$_GET["emp_id"]."', 
        acknowledgeOn = '$entry_date' WHERE id = '".$input["id"]."'";
       
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if($_GET["type"]=="saveGeneratedCOde"){
        
         
        $data = $input["selectedMaterial"]; // Already an array
        $input = $input["selectedFormula"]; // Already an array
 
        $flag = 0;
        
        foreach ($data as $row) {
         
            $sql = "UPDATE `tentitiveUnitFormulaMaterial` SET `clientMaterial_code` = '".$row['clientMaterial_code']."'  WHERE id = '".$row['id']."'";
        
            if ($conn->query($sql) === TRUE) {
                $flag = 1; // At least one success
                
                
                
                $plant_id = $_GET['plant_id'];
                $emp_id = $_GET['emp_id'];
                $client_code = $input['client_code'];
                $clientMaterial_code = $row['clientMaterial_code'];
                $material_code = $row['material_code'];
                $entry_date = date('Y-m-d H:i:s');
                
                // First, check if clientMaterial_code already exists for this plant and client
                $checkSql = "SELECT COUNT(*) as cnt FROM clientMaterialsCodes WHERE clientMaterial_code = ?  ";
                
                $stmt = $conn->prepare($checkSql);
                $stmt->bind_param("s", $clientMaterial_code);
                $stmt->execute();
                $result = $stmt->get_result();
                $rowExists = $result->fetch_assoc();
                
                if ($rowExists['cnt'] == 0) {
                    // Not existing — insert new record
                    $insertSql = "INSERT INTO clientMaterialsCodes 
                        (plant_id, clientMaterial_code, material_code, client_code, clientProvidedMatCode, entryBy, entryOn)
                        VALUES (?, ?, ?, ?, 'NA', ?, ?)";
                
                    $stmt = $conn->prepare($insertSql);
                    $stmt->bind_param("ssssss", $plant_id, $clientMaterial_code, $material_code, $client_code, $emp_id, $entry_date);
                
                    if ($stmt->execute()) {
                        $flag = 1; // Success
                    } else {
                        error_log("DB Insert Failed: " . $stmt->error);
                        $flag = 0; // Failure
                    }
                }  
                
               
            } else {
                error_log("DB Insert Failed: " . $conn->error);
                $flag = 0; // Track failure
            }
        }
 
    	if($flag == 1) {
    	    $sql = "UPDATE tentitiveUnitformula SET status = 'Code_Created', isClientMatCode = 'YES' WHERE id = '".$input["id"]."' ";
    	    $conn->query($sql);
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    }
    else if($_GET["type"]=="saveGeneratedCOdeRndMaster"){
        
        
        
        $plant_id = $_GET['plant_id'];
        $emp_id = $_GET['emp_id'];
        $client_code = $input['client_code'];
        $material_code = $input['material_code'];
        $entry_date = date('Y-m-d H:i:s');
         

        $sql = "SELECT MAX(id) AS max_id FROM clientMaterialsCodes WHERE plant_id = '$plant_id' ";
        
        $result = $conn->query($sql);
        $last_id = 1; // Default starting value
        if ($result && $row = $result->fetch_assoc()) {
            if (!is_null($row['max_id'])) {
                $last_id = $row['max_id'] + 1;
            }
        }


        
        $clientMaterial_code = 'NA';
        $plant_code = $input['plant_code'];
        $materialTypeCode = $input['materialTypeCode'];
        $materialSubTypeCode = $input['materialSubTypeCode'];
        $packSizeCode = $input['packSizeCode'];
        $cCode = $input['client_code'];
        $padded_id = str_pad($last_id,4, '0', STR_PAD_LEFT);
       
        if ($input["material_type"] == 'Raw Material') {
            $clientMaterial_code = $plant_code.$materialTypeCode.$cCode.$materialSubTypeCode.$padded_id;
        } else {
            $clientMaterial_code = $plant_code.$materialTypeCode.$cCode.$materialSubTypeCode.$packSizeCode.$padded_id;
        }
        
     
        $insertSql = "INSERT INTO clientMaterialsCodes (plant_id, clientMaterial_code, material_code, client_code, clientProvidedMatCode, entryBy, entryOn) VALUES (?, ?, ?, ?, 'NA', ?, ?)";
    
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param("ssssss", $plant_id, $clientMaterial_code, $material_code, $client_code, $emp_id, $entry_date);
        
    	if($stmt->execute()) {
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    }
    else if($_GET["type"]=="saveMaterialToFormula"){
        
        
        $plant_id = $_GET['plant_id'];
        $emp_id = $_GET['emp_id'];
        $feasibilityFormNo = $input['feasibilityFormNo'];
        $material_code = $input['material_code'];
        $entry_date = date('Y-m-d H:i:s');
         
     
        $insertSql = "INSERT INTO `formulaMatCodes`(`plant_id`, `feasibilityFormNo`, `material_code`, `entryBy`, `entryOn`) VALUES  (?, ?, ?, ?, ?)";
    
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param("sssss", $plant_id, $feasibilityFormNo, $material_code, $emp_id, $entry_date);
        
    	if($stmt->execute()) {
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    }
    else if($_GET["type"]=="getMaterialMappWithFormula"){
        
        $output = Array();
         $sql = "SELECT a.*,b.material_name,b.material_type FROM `formulaMatCodes` a LEFT JOIN materialMasterViewRndNormal b ON a.material_code = b.material_code 
        WHERE a.plant_id = '".$_GET["plant_id"]."' AND a.feasibilityFormNo = '".$_GET["feasibilityFormNo"]."' order by a.id ASC"; 
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if($_GET["type"]=="getClientLastSeries"){
        
        $output = Array();
        $sql = "SELECT COUNT(*) as ClientSeries FROM `material`  WHERE plant_id = '".$_GET["plant_id"]."' AND client_code = '".$_GET["client_code"]."' "; 
        $ClientSeries = 0;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $ClientSeries = $row['ClientSeries'];
            }
        }
        echo "{\"ClientSeries\":\"$ClientSeries\"}";
        
    }
    else if ($_GET["type"] == "getEnquiryProductWiseForFeasibility") {
        $output = Array();
        $sql = "SELECT p.*, e.enquiry_no, e.client_code, e.sample_required, e.sample_qty, e.reference_sample, e.refNoOfunits, e.country, e.details, 
        c.LglNm , b.firstname as entryByName FROM `enquiryProduct` p 
        LEFT JOIN enquiry e ON p.enquiryId =e.id 
        LEFT JOIN client_combined_view c ON e.client_code = c.client_code  
        LEFT JOIN employee b ON p.finalBy = b.emp_id 
        WHERE p.plant_id = '".$_GET["plant_id"]."' AND p.status = 'FINAL' AND e.acknowledgeStatus = 'Acknowledged' order by p.id desc"; 
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getFeasabilityFormLog") {
        $output = Array();
        $sql = "SELECT p.*, e.enquiry_no, e.client_code, e.sample_required, e.sample_qty, e.reference_sample, e.refNoOfunits, e.country, e.details, 
        c.LglNm,c.source_table,c.status as clientStatus,
        (select CONCAT(firstname, ' ', lastname) AS entryByName from employee  where emp_id = p.fesibilityBy ) as entryByName,
        (select designation from employee  where emp_id = p.fesibilityBy ) as entryByDesi,
        (select CONCAT(firstname, ' ', lastname) AS fesibilityReviewByName from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByName,
        (select designation from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByDesi,
        (select CONCAT(firstname, ' ', lastname) AS fesibilityApproveByName from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByName,
        (select designation from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByDesi,
        (select CONCAT(firstname, ' ', lastname) AS developementAssignToName from employee  where emp_id = p.developementAssignTo ) as developementAssignToName
        FROM `enquiryProduct` p 
        LEFT JOIN enquiry e ON p.enquiryId =e.id 
        LEFT JOIN client_combined_view c ON e.client_code = c.client_code  
        WHERE p.plant_id = '".$_GET["plant_id"]."' AND p.feasibilityFormNo != 'NA'  order by p.id desc"; 
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['ingredients'] = json_decode($row['ingredients']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        // (select CONCAT(firstname) AS acceptedByRndByName from employee  where emp_id = p.acceptedByRndBy ) as acceptedByRndByName,
        // (select CONCAT(firstname, ' ', emp_id) AS devAssignToName from employee  where emp_id = p.developementAssignTo ) as devAssignToName
        
    }
    else if ($_GET["type"] == "getProductEnquiryForRAndD") {
        $output = Array();
        $sql = "SELECT p.*, e.enquiry_no, e.client_code, e.sample_required, e.sample_qty, e.reference_sample, e.refNoOfunits, e.country, e.details, 
        c.LglNm , c.source_table,c.status as clientStatus,
        (select CONCAT(firstname, ' ', lastname) AS entryByName from employee  where emp_id = p.fesibilityBy ) as entryByName,
        (select designation from employee  where emp_id = p.fesibilityBy ) as entryByDesi,
        (select CONCAT(firstname, ' ', lastname) AS fesibilityReviewByName from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByName,
        (select designation from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByDesi,
        (select CONCAT(firstname, ' ', lastname) AS fesibilityApproveByName from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByName,
        (select designation from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByDesi,
        (select CONCAT(firstname, ' ', lastname) AS developementAssignToName from employee  where emp_id = p.developementAssignTo ) as developementAssignToName
        FROM `enquiryProduct` p 
        LEFT JOIN enquiry e ON p.enquiryId =e.id 
        LEFT JOIN client_combined_view c ON e.client_code = c.client_code  
        WHERE p.plant_id = '".$_GET["plant_id"]."' AND p.status = 'TO_RND'  order by p.id desc"; 
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['ingredients'] = json_decode($row['ingredients']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getFeasabilityFormForApproval") {
        $output = Array();
        $sql = "SELECT p.*, e.enquiry_no, e.client_code, e.sample_required, e.sample_qty, e.reference_sample, e.refNoOfunits, e.country, e.details, 
        c.LglNm , c.source_table,c.status as clientStatus,
        (select CONCAT(firstname, ' ', lastname) AS entryByName from employee  where emp_id = p.fesibilityBy ) as entryByName,
        (select designation from employee  where emp_id = p.fesibilityBy ) as entryByDesi,
        (select CONCAT(firstname, ' ', lastname) AS fesibilityReviewByName from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByName,
        (select designation from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByDesi,
        (select CONCAT(firstname, ' ', lastname) AS fesibilityApproveByName from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByName,
        (select designation from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByDesi,
        (select CONCAT(firstname, ' ', lastname) AS developementAssignToName from employee  where emp_id = p.developementAssignTo ) as developementAssignToName
        FROM `enquiryProduct` p 
        LEFT JOIN enquiry e ON p.enquiryId =e.id 
        LEFT JOIN client_combined_view c ON e.client_code = c.client_code  
        WHERE p.plant_id = '".$_GET["plant_id"]."' AND p.status = 'Reviewed_By_RND'  order by p.id desc"; 
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {  
                $row['ingredients'] = json_decode($row['ingredients']);   
                $output[] = $row;   
            } 
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getFeasabilityFormForTentitiveUnitFormula") {
        $output = Array();
        
        
        if($_GET["dept_head"] == 'Yes'){
            
            $sql = "SELECT p.*, e.enquiry_no, e.client_code, e.sample_required, e.sample_qty, e.reference_sample, e.refNoOfunits, e.country, e.details, 
            c.LglNm , c.source_table,c.status as clientStatus,
            (select CONCAT(firstname, ' ', lastname) AS entryByName from employee  where emp_id = p.fesibilityBy ) as entryByName,
            (select designation from employee  where emp_id = p.fesibilityBy ) as entryByDesi,
            (select CONCAT(firstname, ' ', lastname) AS fesibilityReviewByName from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByName,
            (select designation from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByDesi,
            (select CONCAT(firstname, ' ', lastname) AS fesibilityApproveByName from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByName,
            (select designation from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByDesi,
            (select CONCAT(firstname, ' ', lastname) AS developementAssignToName from employee  where emp_id = p.developementAssignTo ) as developementAssignToName
            FROM `enquiryProduct` p 
            LEFT JOIN enquiry e ON p.enquiryId =e.id 
            LEFT JOIN client_combined_view c ON e.client_code = c.client_code  
            WHERE p.plant_id = '".$_GET["plant_id"]."' AND ( p.status = 'Approved_By_RND' OR p.isTentitiveFormulaPrepared = 'YES')  order by p.id desc";
        
        }else{
            
            $sql = "SELECT p.*, e.enquiry_no, e.client_code, e.sample_required, e.sample_qty, e.reference_sample, e.refNoOfunits, e.country, e.details, 
            c.LglNm , 
            (select CONCAT(firstname, ' ', lastname) AS entryByName from employee  where emp_id = p.fesibilityBy ) as entryByName,
            (select designation from employee  where emp_id = p.fesibilityBy ) as entryByDesi,
            (select CONCAT(firstname, ' ', lastname) AS fesibilityReviewByName from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByName,
            (select designation from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByDesi,
            (select CONCAT(firstname, ' ', lastname) AS fesibilityApproveByName from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByName,
            (select designation from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByDesi,
            (select CONCAT(firstname, ' ', lastname) AS developementAssignToName from employee  where emp_id = p.developementAssignTo ) as developementAssignToName
            FROM `enquiryProduct` p 
            LEFT JOIN enquiry e ON p.enquiryId =e.id 
            LEFT JOIN client_combined_view c ON e.client_code = c.client_code  
            WHERE p.plant_id = '".$_GET["plant_id"]."' AND ( p.status = 'Approved_By_RND' OR p.isTentitiveFormulaPrepared = 'YES') AND developementAssignTo = '".$_GET["emp_id"]."'  order by p.id desc";
        
        }
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['ingredients'] = json_decode($row['ingredients']);
                
                    $tentativeFormula = Array();
                    $sql1 = "select * from tentitiveUnitformula where enquiryProductId = '".$row['id']."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
            
                            $materialList = Array();
                          
                            $sql11 = "select a.*,m.material_name,m.grade from tentitiveUnitFormulaMaterial a left join materialMasterViewRndNormal m ON a.material_code = m.material_code 
                            where a.mfr_no = '".$row1['mfr_no']."' order by a.material_type desc";
                            $result11 = $conn->query($sql11);
                            if ($result11->num_rows > 0) {
                                while ($row11 = $result11->fetch_assoc()) {
                                    $materialList[] = $row11;
                                }
                            }
            
                            $row1['materialList']  = $materialList;
                            $tentativeFormula = $row1;
                        }
                    }
 
                
                $row['tentativeFormula'] = $tentativeFormula;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getFeasabilityFormForTentitiveUnitFormulaForDevRFequyirement") {
        $output = Array();
        
        
        if($_GET["dept_head"] == 'Yes'){
            
            $sql = "SELECT a.*, e.enquiry_no, e.client_code, e.sample_required, e.sample_qty, e.reference_sample, e.refNoOfunits, e.country, e.details,p.fesibilityBy,p.fesibilityReviewBy,p.fesibilityApproveBy,p.developementAssignTo,p.ingredients,
            p.feasibilityFormNo,p.fesibilityOn,c.LglNm , p.tentative_launch_dt, p.product_name, p.suggestive_fill_volume, p.demography, p.projected_volume,p.remark,p.labelClaim,p.totalBulkCost,p.pack_size,p.packType,p.prodFeasibiTcd, 
            p.projected_volume_for, p.primary_packaging_type, p.secondary_packaging_type, p.primary_claims, p.secondary_claim, p.product_certification, p.colour_match_reference, p.textures_appearance_reference, p.fg_benchmark_product, 
            p.performance_expectation, p.benchmark_ingredient_list, p.fragrance_reference, p.phase_of_development, p.export_certification, p.shelf_life, p.mandatory_tests_reports, p.target_price, p.mrp,p.fesibilityApproveOn,p.fesibilityReviewOn,
            (select CONCAT(firstname, ' ', lastname) AS entryByName from employee  where emp_id = p.fesibilityBy ) as entryByName,
            (select designation from employee  where emp_id = p.fesibilityBy ) as entryByDesi,
            (select CONCAT(firstname, ' ', lastname) AS fesibilityReviewByName from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByName,
            (select designation from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByDesi,
            (select CONCAT(firstname, ' ', lastname) AS fesibilityApproveByName from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByName,
            (select designation from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByDesi,
            (select CONCAT(firstname, ' ', lastname) AS developementAssignToName from employee  where emp_id = p.developementAssignTo ) as developementAssignToName
            FROM `tentitiveUnitformula` a  
            LEFT JOIN enquiryProduct p ON p.id = a.enquiryProductId 
            LEFT JOIN enquiry e ON p.enquiryId = e.id 
            LEFT JOIN client_combined_view c ON e.client_code = c.client_code  
            WHERE p.plant_id = '".$_GET["plant_id"]."' AND  a.status = 'Pending' order by p.id desc";
        
        }else{
            
            $sql = "SELECT a.*, e.enquiry_no, e.client_code, e.sample_required, e.sample_qty, e.reference_sample, e.refNoOfunits, e.country, e.details,p.fesibilityBy,p.fesibilityReviewBy,p.fesibilityApproveBy,p.developementAssignTo,p.ingredients,
            p.feasibilityFormNo,p.fesibilityOn,c.LglNm , p.tentative_launch_dt, p.product_name, p.suggestive_fill_volume, p.demography, p.projected_volume, p.remark,p.labelClaim,p.totalBulkCost,p.pack_size,p.packType,p.prodFeasibiTcd, 
            p.projected_volume_for, p.primary_packaging_type, p.secondary_packaging_type, p.primary_claims, p.secondary_claim, p.product_certification, p.colour_match_reference, p.textures_appearance_reference, p.fg_benchmark_product, 
            p.performance_expectation, p.benchmark_ingredient_list, p.fragrance_reference, p.phase_of_development, p.export_certification, p.shelf_life, p.mandatory_tests_reports, p.target_price, p.mrp, p.fesibilityApproveOn,p.fesibilityReviewOn,
            (select CONCAT(firstname, ' ', lastname) AS entryByName from employee  where emp_id = p.fesibilityBy ) as entryByName,
            (select designation from employee  where emp_id = p.fesibilityBy ) as entryByDesi,
            (select CONCAT(firstname, ' ', lastname) AS fesibilityReviewByName from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByName,
            (select designation from employee  where emp_id = p.fesibilityReviewBy ) as fesibilityReviewByDesi,
            (select CONCAT(firstname, ' ', lastname) AS fesibilityApproveByName from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByName,
            (select designation from employee  where emp_id = p.fesibilityApproveBy ) as fesibilityApproveByDesi,
            (select CONCAT(firstname, ' ', lastname) AS developementAssignToName from employee  where emp_id = p.developementAssignTo ) as developementAssignToName
            FROM `tentitiveUnitformula` a  
            LEFT JOIN enquiryProduct p ON p.id = a.enquiryProductId 
            LEFT JOIN enquiry e ON p.enquiryId = e.id 
            LEFT JOIN client_combined_view c ON e.client_code = c.client_code  
            WHERE p.plant_id = '".$_GET["plant_id"]."' AND a.status = 'Pending' AND p.developementAssignTo = '".$_GET["emp_id"]."'  order by p.id desc";
        
        }
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['ingredients'] = json_decode($row['ingredients']);
                 
                $rawMaterialList = Array(); $packingMaterialList = Array();
              
                $sql11 = "select a.*,m.material_name,m.grade,m.unit as reqUnit,m.materialFrom, m.newPackMatDevelopment,m.mother_material_code from tentitiveUnitFormulaMaterial a left join materialMasterViewRndNormal m ON a.material_code = m.material_code 
                where a.mfr_no = '".$row['mfr_no']."' AND a.plant_id = '".$row['plant_id']."'  ";
                
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        
                        $row11['requestQty'] = 0;
                        $row11['invalid'] = false;
                        $row11['is'] = 'OLD';
                        
                        if($row11['material_type'] == 'Raw Material'){
                            $rawMaterialList[] = $row11;
                        }else if($row11['material_type'] == 'Packing Material'){
                            $packingMaterialList[] = $row11;
                        }
                        
                    }
                }
      
                      
                $row['rawMaterialList'] = $rawMaterialList;
                $row['packingMaterialList'] = $packingMaterialList;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getRndMaterialRequestToStore") {
        $output = Array();
        
 
        $sql = "SELECT a.*,m.material_name,
        (select CONCAT(firstname, ' ', lastname) AS requestByName from employee  where emp_id = a.requestBy ) as requestByName
        from requirementMaterialFromRnd a 
        left join rndMaterialAndClientMatCodeView m ON a.material_code = m.material_code where (a.status = 'Pending' OR a.status = 'FOR_INDENT_REQ_STORE' ) AND a.plant_id = '".$_GET['plant_id']."' AND a.requestTo = '".$_GET['requestTo']."'  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getRndMaterialRequestToNPD") {
        $output = Array();
        
 
        $sql = "SELECT a.*,m.material_name,
        (select CONCAT(firstname, ' ', lastname) AS requestByName from employee  where emp_id = a.requestBy ) as requestByName
        from requirementMaterialFromRnd a left join rndMaterialAndClientMatCodeView m ON a.material_code = m.material_code 
        where a.status = 'Pending' AND a.plant_id = '".$_GET['plant_id']."' AND a.requestTo = '".$_GET['requestTo']."' AND a.material_type = 'Raw Material'  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getRndReqMaterStatusLog") {
        $output = Array();
         
        $sql = "SELECT a.*,m.material_name,
        (select CONCAT(firstname, ' ', lastname) AS requestByName from employee  where emp_id = a.requestBy ) as requestByName,
        (select CONCAT(firstname, ' ', lastname) AS npdActionByByName from employee  where emp_id = a.npdActionBy ) as npdActionByByName,
        (select vendor_name from vendor  where vendor_no = a.freeSampFrom ) as freeSampFromName
        from requirementMaterialFromRnd a left join rndMaterialAndClientMatCodeView m ON a.material_code = m.material_code 
        where a.status != 'Pending' AND a.plant_id = '".$_GET['plant_id']."' AND a.requestTo = '".$_GET['requestTo']."' AND a.material_type = 'Raw Material'  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getRequestForPackingmaterialDevelopement") {
        $output = Array();
        
 
        $sql = "SELECT a.*,m.material_name,
        (select CONCAT(firstname, ' ', lastname) AS requestByName from employee  where emp_id = a.requestBy ) as requestByName
        from requirementMaterialFromRnd a left join rndMaterialAndClientMatCodeView m ON a.material_code = m.material_code 
        where a.status = 'Pending' AND a.plant_id = '".$_GET['plant_id']."' AND a.requestTo = '".$_GET['requestTo']."' AND a.material_type = 'Packing Material' AND m.newPackMatDevelopment = 'YES'  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getTetetiveFormulaForCodeCreation") { 
          
        $tentativeFormula = Array();
        $sql1 = "select a.*,c.enquiry_no,d.LglNm,b.feasibilityFormNo,b.client_code,b.client_code as regClientCode from tentitiveUnitformula a
        LEFT JOIN enquiryProduct b ON b.id =a.enquiryProductId 
        LEFT JOIN enquiry c ON b.enquiryId = c.id 
        LEFT JOIN client_combined_view d ON d.client_code = c.client_code 
        where a.isClientMatCode = 'NO' AND a.plant_id = '".$_GET["plant_id"]."'";
        
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {

                $materialList = Array();
              
                $sql11 = "select a.*,m.id as matId,m.material_name,m.grade,m.plant_code,m.materialTypeCode,m.materialSubTypeCode,m.packSizeCode from tentitiveUnitFormulaMaterial a 
                left join rndMaterial m ON a.material_code = m.material_code where a.mfr_no = '".$row1['mfr_no']."'";
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $row11['clientMaterial_code'] = '';
                        $materialList[] = $row11;
                    }
                }

                $row1['materialList']  = $materialList;
                $tentativeFormula[] = $row1;
            }
        }
 
        
        echo json_encode($tentativeFormula);
        
    }
    else if ($_GET["type"] == "saveFeasibilityForm") {
        
        $sql = "SELECT COUNT(*) AS total FROM enquiryProduct WHERE feasibilityFormNo != 'NA' AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        
        if ($result && $row = $result->fetch_assoc()) {
            $count = (int)$row['total'];
        } else {
            $count = 0;
        }
        $nextNumber = $count + 1;
        $paddedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        $feasibilityFormNo = "FFN" . $paddedNumber;
        
        
        
         
        $remark = $conn->real_escape_string($input["remark"]);
        $labelClaim = $conn->real_escape_string($input["labelClaim"]);
        $totalBulkCost = $conn->real_escape_string($input["totalBulkCost"]);
        $packType = $conn->real_escape_string($input["packType"]);
        $prodFeasibiTcd = $conn->real_escape_string($input["prodFeasibiTcd"]);
 
        $sql = "UPDATE `enquiryProduct` SET `feasibilityFormNo` = '$feasibilityFormNo', `status`= 'TO_RND', `ingredients`= '".json_encode($input["ingredients"])."', `remark`= '$remark', 
        `labelClaim`= '$labelClaim', `totalBulkCost`= '$totalBulkCost', `packType`= '$packType',`prodFeasibiTcd`= '$prodFeasibiTcd' , 
        `fesibilityBy` = '".$_GET["emp_id"]."' , `fesibilityOn` =  '$entry_date' WHERE id = '".$input["id"]."'";
       
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "sentClientForLegal") {

 
        $sql = "UPDATE `tempClient` SET  `status`= 'To_Legal', enquiry_no = '".$input["enquiry_no"]."'  WHERE client_code = '".$input["client_code"]."'";
       
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "saveRequestMatFromNPD") {

        $status = "FOR_INDENT_REQ_STORE";
        
        if($input["materialFrom"] == 'Indent'){
            $status = "FOR_INDENT_REQ_STORE";
        }else{
            $status = "FOR_FREE_SAMPLE";
        }

        $sql = "UPDATE `requirementMaterialFromRnd` SET  `status`= '$status', materialFrom = '".$input["materialFrom"]."', freeSampFrom = '".$input["freeSampFrom"]."',
        `npdActionBy` = '".$_GET["emp_id"]."' , `npdActionOn` =  '$entry_date'  WHERE id = '".$input["entryId"]."'";
       
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "sendToClientForMOreDetails") {

       

        $sql = "UPDATE `requirementMaterialFromRnd` SET  `status`= 'SEND_TO_CLIENT  ', materialFrom = '".$input["materialFrom"]."', freeSampFrom = '".$input["freeSampFrom"]."',
        `npdActionBy` = '".$_GET["emp_id"]."' , `npdActionOn` =  '$entry_date'  WHERE id = '".$input["entryId"]."'";
       
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "reviewFeasabilityForm") {

 
        $sql = "UPDATE `enquiryProduct` SET  `status`= 'Reviewed_By_RND',
        `fesibilityReviewBy` = '".$_GET["emp_id"]."' , `fesibilityReviewOn` =  '$entry_date' WHERE id = '".$input["id"]."'";
       
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "getRndEmployees") {

 
        $sql = "UPDATE `enquiryProduct` SET  `status`= 'Reviewed_By_RND',
        `fesibilityReviewBy` = '".$_GET["emp_id"]."' , `fesibilityReviewOn` =  '$entry_date' WHERE id = '".$input["id"]."'";
       
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else { 
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "Save_npd_roll") {

 
        $sql = "insert into npd_roll (`roll`) values('".$_GET["roll"]."')";
       
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";  
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "approveFeasabilityForm") {

 
        $sql = "UPDATE `enquiryProduct` SET  `status`= 'Approved_By_RND', `developementAssignTo` = '".$input["developementAssignTo"]."' ,
        `fesibilityApproveBy` = '".$_GET["emp_id"]."' , `fesibilityApproveOn` =  '$entry_date' WHERE id = '".$input["id"]."'";
       
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "saveInitiateTrial") {

 
        $sql = "INSERT INTO `dev_trialRND`(`plant_id`, `status`, `product_code`, `product_name`, `exp_start_date`, `exp_complition_date`, `trial_Description`, `mfr_no`, `client_code`, `enquiryProductId`, `scientist`, `equipmentList`, `procedureList`, 
        `instructionList`, `testList`, `entryBy`, `entryOn`) VALUES  ('".$_GET['plant_id']."', 'Pending', '".$input['product_code']."','".$input['product_name']."','".$input['exp_start_date']."', '".$input['exp_complition_date']."', 
        '".$input['trial_Description']."', '".$input['mfr_no']."', '".$input['client_code']."', '".$input['enquiryProductId']."','".$input['scientist']."',
        '".json_encode($input['equipmentList'])."','".json_encode($input['procedureList'])."','".json_encode($input['instructionList'])."',
        '".json_encode($input['testList'])."','".$_GET['emp_id']."', '$entry_date')";
       
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            $trialNo = $conn->insert_id;

            $materialList = $input['materialList'];
            
            foreach ($materialList as $mat) {
                 
                $sql = "INSERT INTO `trialMaterials`(`plant_id`, `trialNo`, `material_code`, `qty`, `unit`, `contriToYield`, `overages_per`, `total_qty`, `roll`, `entryBy`, `entryOn`) VALUES ('".$_GET['plant_id']."', '$trialNo',
                '".$mat['material_code']."','".$mat['qty']."','".$mat['unit']."', '".$mat['contriToYield']."', '".$mat['overages_per']."', '".$mat['total_qty']."','".$mat['roll']."','".$_GET['emp_id']."', '$entry_date')";
               
                $conn->query($sql);
                
            }
            
             
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "approvedDevTrial") {

 
        $sql = "UPDATE `dev_trialRND`  SET `status` = '".$input['status']."', `approveBy` = '".$_GET['emp_id']."', `approveOn` = '$entry_date'  WHERE `id` = '".$input['id']."' ";
       
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "GetSavedRoll") {
        
         $output = Array();
             $sql = "SELECT *  FROM roll";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                      $output[] = $row;}
            }
            echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPendingDevTrials") {
        
            $output = Array();
         
            $sql = "SELECT  a.*, 
            (select c.LglNm from client_combined_view c where c.client_code = a.client_code ) as clientName,  
            (select CONCAT(e.firstname , ' ' , e.lastname , ' ( ' ,e.emp_id , ' ) ' ) as scientistName from employee e where e.emp_id = a.scientist ) as scientistName  
            FROM `dev_trialRND` a WHERE a.plant_id = '".$_GET["plant_id"]."' AND a.status =  'Pending' order by a.id DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    $output1 = Array();
                    
                    $sql1 = "SELECT  a.*, b.material_type,b.material_subtype,b.material_name,b.grade FROM `trialMaterials` a LEFT JOIN  rndMaterial b ON a.material_code = b.material_code  WHERE a.trialNo = '".$row["id"]."' ";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                              $output1[] = $row1;
                        }
                    }
                    
                    $row["equipmentList"] = json_decode($row["equipmentList"]);
                    $row["procedureList"] = json_decode($row["procedureList"]);
                    $row["instructionList"] = json_decode($row["instructionList"]);
                    $row["testList"] = json_decode($row["testList"]);
                    $row['materialList'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    } 
    else if ($_GET["type"] == "getApprovedDevTrials") {
        
            $output = Array();
         
            $sql = "SELECT  a.*, 
            (select c.LglNm from client_combined_view c where c.client_code = a.client_code ) as clientName,  
            (select CONCAT(e.firstname , ' ' , e.lastname , ' ( ' ,e.emp_id , ' ) ' ) as scientistName from employee e where e.emp_id = a.scientist ) as scientistName  
            FROM `dev_trialRND` a WHERE a.plant_id = '".$_GET["plant_id"]."' AND a.status =  'Approved' AND a.isCodeGen =  'NO' order by a.id DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    $output1 = Array();
                    
                    $sql1 = "SELECT  a.*, b.material_type,b.material_subtype,b.material_name,b.grade,b.plant_code,b.materialTypeCode,b.materialSubTypeCode,b.packSizeCode FROM `trialMaterials` a LEFT JOIN  rndMaterial b ON a.material_code = b.material_code  WHERE a.trialNo = '".$row["id"]."' ";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            
                            
                            $output11 = Array();
                            $sql11 = "SELECT b.material_type,b.material_subtype,b.material_name,b.material_code  FROM `material` b WHERE b.material_name LIKE '%".$row1["material_name"]."%' AND b.status = 'Approved' AND matIs = 'OWN' ";
                            $result11 = $conn->query($sql11);
                            if ($result11->num_rows > 0) {
                                while ($row11 = $result11->fetch_assoc()) {
                                    $output11[] = $row11;
                                }
                            }
                            
                            $row1['motherMatCodes'] = $output11;
                            $row1['mother_material_code'] = '';
                            $row1['clientProvideMatCode'] = '';
                            $row1['clientMatCode'] = 'NA';
                            $output1[] = $row1;
                        }
                    }
                    
                    $row["equipmentList"] = json_decode($row["equipmentList"]);
                    $row["procedureList"] = json_decode($row["procedureList"]);
                    $row["instructionList"] = json_decode($row["instructionList"]);
                    $row["testList"] = json_decode($row["testList"]);
                    $row['materialList'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    } 
    else if ($_GET["type"] == "getTrialLog") {
        
            $output = Array();
         
            $sql = "SELECT  a.*, 
            (select c.LglNm from client_combined_view c where c.client_code = a.client_code ) as clientName,  
            (select CONCAT(e.firstname , ' ' , e.lastname , ' ( ' ,e.emp_id , ' ) ' ) as scientistName from employee e where e.emp_id = a.scientist ) as scientistName  
            FROM `dev_trialRND` a WHERE a.plant_id = '".$_GET["plant_id"]."'  order by a.id DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    $output1 = Array();
                    
                    $sql1 = "SELECT  a.*, b.material_type,b.material_subtype,b.material_name,b.grade FROM `trialMaterials` a LEFT JOIN  rndMaterial b ON a.material_code = b.material_code  WHERE a.trialNo = '".$row["id"]."' ";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                              $output1[] = $row1;
                        }
                    }
                    
                    $row["equipmentList"] = json_decode($row["equipmentList"]);
                    $row["procedureList"] = json_decode($row["procedureList"]);
                    $row["instructionList"] = json_decode($row["instructionList"]);
                    $row["testList"] = json_decode($row["testList"]);
                    $row['materialList'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRndAndPrdEquipments") {
        
         $output = Array();
            $sql = "SELECT `id`, `plant_id`, `equipment_category`, `equipment_type`, `equipment_code`, `equipment_name`, `department`, `location`, `capacity`, `from_range`, `to_range`, `unit` 
            FROM `equipment` WHERE plant_id = '".$_GET["plant_id"]."' AND status =  'Active' AND ( department = 'Production' OR department = 'R AND D' )";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                      $output[] = $row;
                    
                }
            }
            echo json_encode($output);
    } 
    else if ($_GET["type"] == "getProducts") {
        
         $output = Array();
             $sql = "SELECT a.*,b.client_code  FROM tentitiveUnitformula a left join enquiryProduct b ON a.enquiryProductId = b.id  WHERE a.plant_id='".$_GET["plant_id"]."' ORDER BY a.id desc ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) { 
                    
                        $output1 = Array();
                        $sql1 = "SELECT a.*,m.material_name,m.grade FROM tentitiveUnitFormulaMaterial a left join rndMaterial m ON a.material_code = m.material_code 
                        WHERE a.mfr_no='".$row["mfr_no"]."'  ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $row1['check'] = false;
                                $row1['roll'] = '';
                                $output1[] = $row1;
                            }
                        }
                        
                      $row["unitMaterials"] = $output1;
                     $output[] = $row;
                     
                }
            }
            echo json_encode($output);
    } 
 
}

$conn->close();
?>