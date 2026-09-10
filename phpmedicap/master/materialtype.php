<?php 
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
require '../db.php';
require '../token.php';
header('response_token: test123456');
$entry_date = date("Y-m-d h:i:s", $timestamp);
$output = Array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0){
    try{
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveMaterialtype") {
        $materialType = trim($input["material_type"] ?? $input["mat_type"] ?? '');
        $materialSubtype = trim($input["material_subtype"] ?? '');
        $shortCode = trim($input["Short_Code"] ?? '');
        $controlSampleType = trim($input["control_sample_type"] ?? '');
        $controlReserveCriteria = trim($input["control_reserve_criteria"] ?? '');
        $retestType = trim($input["retest_type"] ?? '');
        $retestMonths = trim($input["restest_months"] ?? '');

        $materialType = $conn->real_escape_string($materialType);
        $materialSubtype = $conn->real_escape_string($materialSubtype);
        $shortCode = $conn->real_escape_string($shortCode);
        $controlSampleType = $conn->real_escape_string($controlSampleType);
        $controlReserveCriteria = $conn->real_escape_string($controlReserveCriteria);
        $retestType = $conn->real_escape_string($retestType);
        $retestMonths = $conn->real_escape_string($retestMonths);
        $plantId = $conn->real_escape_string($_GET["plant_id"] ?? '');

        $colCheck = @$conn->query("SHOW COLUMNS FROM material_type LIKE 'restest_months'");
        if (!$colCheck || $colCheck->num_rows == 0) {
            @$conn->query("ALTER TABLE material_type ADD COLUMN restest_months VARCHAR(20) DEFAULT NULL");
        }

        $sql1 = "SELECT id FROM material_type
                 WHERE material_type = '".$materialType."'
                 AND material_subtype = '".$materialSubtype."'
                 AND plant_id = '".$plantId."'";

        $result1 = $conn->query($sql1);

        if ($result1 && $result1->num_rows > 0) {
            echo "{\"status\":\"Type Already Exist\"}";
        } else {
            $sql = "INSERT INTO material_type (
                plant_id, material_type, material_subtype, Short_Code,
                control_sample_type, control_reserve_criteria, retest_type, restest_months,
                entry_by, entry_date
            ) VALUES (
                '".$plantId."',
                '".$materialType."',
                '".$materialSubtype."',
                '".$shortCode."',
                ".($controlSampleType !== '' ? "'".$controlSampleType."'" : "NULL").",
                ".($controlReserveCriteria !== '' ? "'".$controlReserveCriteria."'" : "NULL").",
                ".($retestType !== '' ? "'".$retestType."'" : "NULL").",
                ".($retestMonths !== '' ? "'".$retestMonths."'" : "NULL").",
                '".$conn->real_escape_string($_GET["emp_id"])."',
                '$entry_date'
            )";

            if ($conn->query($sql) === TRUE) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        }
    }
         else  if ($_GET["type"] == "getDosageByNature") {
         
         $output = Array();
         $output1 = Array();
          $sql = "Select *,dosage_form_type as dosage_form from master_fg_types where plant_id = '".$_GET["plant_id"]."' AND product_nature = '".$_GET["product_nature"]."' order by 1 desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        } 
        
        $output1['data'] = $output;
        echo json_encode($output1);
    }
    
    else  if ($_GET["type"] == "saveFGtype") {
        if (!is_array($input)) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            $input = is_array($decoded) ? $decoded : array();
        }
        $plantId = isset($_GET["plant_id"]) ? trim((string)$_GET["plant_id"]) : '';
        if ($plantId === '' || strtolower($plantId) === 'null' || strtolower($plantId) === 'undefined') {
            $plantId = '1126';
        }
        $plantEsc = $conn->real_escape_string($plantId);
        $dosageForm = trim((string)($input["dosage_form_type"] ?? ''));
        $catCode = trim((string)($input["catCode"] ?? ''));
        $productNature = trim((string)($input["product_nature"] ?? ''));
        $fgSampling = trim((string)($input["fg_sampling_qty"] ?? ''));
        $fgReserve = trim((string)($input["fg_control_reserve_type"] ?? ''));
        $fgCriteria = trim((string)($input["fg_control_sample_criteria"] ?? ''));

        if ($dosageForm === '' || $catCode === '' || $productNature === '') {
            echo "{\"status\":\"All fields are required\"}";
        } else {
            $dosageEsc = $conn->real_escape_string($dosageForm);
            $catEsc = $conn->real_escape_string($catCode);
            // Duplicate only within same plant (case-insensitive)
            $sql = "SELECT id FROM master_fg_types
                    WHERE CAST(plant_id AS CHAR)='".$plantEsc."'
                    AND (
                      LOWER(TRIM(dosage_form_type)) = LOWER('".$dosageEsc."')
                      OR LOWER(TRIM(IFNULL(catCode,''))) = LOWER('".$catEsc."')
                    )
                    LIMIT 1";
            $result = @$conn->query($sql);
            if ($result && $result->num_rows > 0) {
                echo "{\"status\":\"Already Exist\"}";
            } else {
                $sql = "INSERT INTO master_fg_types (plant_id,product_nature,dosage_form_type,catCode,fg_sampling_qty, fg_control_reserve_type,fg_control_sample_criteria,
                entry_date,entered_by) VALUES (
                '".$plantEsc."',
                '".$conn->real_escape_string($productNature)."',
                '".$dosageEsc."',
                '".$catEsc."',
                '".$conn->real_escape_string($fgSampling)."',
                '".$conn->real_escape_string($fgReserve)."',
                '".$conn->real_escape_string($fgCriteria)."',
                '$entry_date',
                '".$_GET["emp_id"]."')";
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->real_escape_string($conn->error)."\"}";
                }
            }
        }
    }
    else if ($_GET["type"] == "deleteFGtype") {
        $id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
        if ($id <= 0 && is_array($input) && isset($input["id"])) {
            $id = intval($input["id"]);
        }
        if ($id <= 0) {
            echo "{\"status\":\"invalid\",\"message\":\"id required\"}";
        } else {
            $plant = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : '';
            $sql = "DELETE FROM master_fg_types WHERE id=".$id;
            if ($plant !== '') {
                $sql .= " AND plant_id='".$plant."'";
            }
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\",\"deleted\":".$conn->affected_rows."}";
            } else {
                echo "{\"status\":\"".$conn->real_escape_string($conn->error)."\"}";
            }
        }
    }
       else  if ($_GET["type"] == "save_FG_API_type") {
        $sql = "INSERT INTO  master_fg_types (plant_id,product_nature,dosage_form_type,fg_sampling_plan,
        fg_sampling_qty,fg_control_reserve_type,fg_control_sample_criteria,dosage_sub_form,entry_date,entered_by) 
        VALUES ('".$_GET["plant_id"]."','".$input["product_nature"]."','".$input["dosage_form_type"]."','".$input["fg_sampling_plan"]."',
        '".$input["fg_sampling_qty"]."','".$input["fg_control_reserve_type"]."','".$input["fg_control_sample_criteria"]."',
        '".$input["dosage_sub_form"]."','$entry_date','".$_GET["emp_id"]."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else  if ($_GET["type"] == "savesamplingplan") {
        $sql = "INSERT INTO  samplling_plan_packing (  plant_id ,fixed_qty,quantity, wise, material_type ,  material_subtype ,  material_code ,
  from_range ,  to_range ,  sampling_criteria ,  unit ) VALUES
         ('".$_GET["plant_id"]."','".$input["fixed_qty"]."','".$input["quantity"]."','".$input["wise"]."','".$input["material_type"]."','".$input["material_subtype"]."','".$input["material_code"]."',
        '".$input["from_range"]."','".$input["to_range"]."','".$input["sampling_criteria"]."',
        '".$input["unit"]."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else  if ($_GET["type"] == "savegenmaterialType") {
         
        
         $sql = "INSERT INTO  gen_material_type (  plant_id ,material_type,material_subtype, entry_by, entry_date) VALUES
         ('".$_GET["plant_id"]."','".$input["material_type1"]."','".$input["material_subtype"]."','".$_GET["emp_id"]."' , '$entry_date')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
     else  if ($_GET["type"] == "getsamplingplanpacking") {
        $sql = "SELECT s.*,m.material_name FROM samplling_plan_packing s left join material m ON s.material_code = m.material_code 
        WHERE s.plant_id =  '".$_GET["plant_id"]."' AND wise = 'Type' order by 1 desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else  if ($_GET["type"] == "getsamplingplanpackingproduct") {
        $sql = "SELECT s.*,m.material_name FROM samplling_plan_packing s left join material m ON s.material_code = m.material_code 
        WHERE s.plant_id =  '".$_GET["plant_id"]."' AND wise = 'Product' order by 1 desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else  if ($_GET["type"] == "get_fg_api_products") {
        $sql = "Select * from master_fg_types where plant_id = '".$_GET["plant_id"]."' order by 1 desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalents"] = json_decode($row["equivalent"]);
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else  if ($_GET["type"] == "Getcombis") {
        $sql = "Select * from product p where category = 'Combi' ";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else  if ($_GET["type"] == "dosage_form") {
        $sql = "Select * from master_fg_types where plant_id = '".$_GET["plant_id"]."' and product_nature='".$_GET["dosage_type"]."' order by 1 desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalents"] = json_decode($row["equivalent"]);
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else  if ($_GET["type"] == "Product_name") {
                   $sql = "SELECT * FROM product p WHERE ( product_type='".$_GET["product_type"]."'   OR dosage_form = '".$_GET['product_type']."' ) 
           and plant_id='".$_GET["plant_id"]."' ";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1=Array();
                //  $sql1="select * from unitformula where product_code='".$row["product_code"]."'";
                 $sql1="select * from unitformula where product_code='".$row1["product_code"]."'";
                $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                         $row["materials"] = $output1;
                         
                        $row["raw_materials"] = json_decode($row1["raw_materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else  if ($_GET["type"] == "get_mfr") {
        $output = array();

        $product_code = mysqli_real_escape_string($conn, $_GET['product_code']);
        
        $sql = "SELECT u.*, p.label_claim,p.dosage_type,p.product_name,p.dose_unit_type,p.dosage_form,p.unit,p.product_type
                FROM unitformula u 
                LEFT JOIN product p ON p.product_code = u.product_code 
                WHERE u.product_code = '$product_code'";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
        
            while ($row = $result->fetch_assoc()) {
        
                $output3 = array();
        
                // ✅ FIX HERE
                $raw_materials = json_decode($row["raw_materials"], true);
        
                foreach ($raw_materials as $row3) {
        
                    $rateSql = "SELECT 
                                    MIN(quotation_amt) AS qminrate, 
                                    MAX(quotation_amt) AS qmaxrate, 
                                    AVG(quotation_amt) AS qavgrate  
                                FROM quotation_dtl  
                                WHERE material_code = '".$row3['material_code']."'";
        
                    $rateResult = $conn->query($rateSql);
                    $rateData = $rateResult->fetch_assoc();
        
                    $row3["rate_stats"] = $rateData;
                    $row3["batch_size"] = $row['batch_size'];
                    $row3["b_unit"] = $row['unit'];;
        
                    $output3[] = $row3;
                }
        
                $row["raw_materials"] = $output3;
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    }
     else  if ($_GET["type"] == "getRate") {
         
         
            $input = json_decode(file_get_contents('php://input'), true);
            $output1 = $input['rmdate'];
            $output3 = $input['packingMaterials'];
            $rateType = $_GET["rateType"];
            
            $output2 = array(); // Initialize the output array
            
            function getRates($array, $rateType, $conn) {
                $resultArray = array();
                foreach ($array as $values) {
                    // $code = $values["material_Code"] || $values["material_code"] ;
                    $code = isset($values["material_Code"]) ? $values["material_Code"] : (isset($values["material_code"]) ? $values["material_code"] : null);
                    if (!$code) {
                        continue; // Skip if no material code is found
                    }
                    $sql1 = '';
            
                    if ($rateType == 'minquationrate') {
                        $sql1 = "SELECT MIN(quotation_amt) as rate,AVG(quotation_amt) as avg_rate FROM  quotation_dtl WHERE material_code = '$code' ";
                    } else if ($rateType == 'maxquationrate') {
                        $sql1 = "SELECT MAX(quotation_amt) as rate,AVG(quotation_amt) as avg_rate FROM  quotation_dtl WHERE material_code = '$code' ";
                    } else if ($rateType == 'avgquationrate') {
                        $sql1 = "SELECT AVG(quotation_amt) as rate,AVG(quotation_amt) as avg_rate FROM  quotation_dtl WHERE material_code = '$code' ";
                    } else if ($rateType == 'avgpurrate') {
                        $sql1 = "SELECT AVG(quotation_amt) as rate,AVG(quotation_amt) as avg_rate FROM po_material WHERE material_code = '$code' ";
                    } else if ($rateType == 'lastpurrate') {
                        $sql1 = "SELECT quotation_amt as rate FROM po_material WHERE material_code = '$code'  ORDER BY ID DESC LIMIT 1";
                    }
            
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row = $result1->fetch_assoc()) {
                            $resultArray[] = $row;
                        }
                    } else {
                        $resultArray[] = ["material_Code" => $code, "rate" => null]; // Include the material code even if no rate is found
                    }
                }
                return $resultArray;
            }
            
            $output2['rm_date'] = getRates($output1, $rateType, $conn);
            $output2['packingMaterials'] = getRates($output3, $rateType, $conn);
            
            if (!empty($output2)) {
                echo json_encode($output2);
            } else {
                echo json_encode(["status" => "No results found"]);
            }
     }
 
     else  if ($_GET["type"] == "get_eng_type") {
          $sql = "Select * from material_type where material_type = '".$_GET["material_type"]."' AND plant_id = '".$_GET["plant_id"]."' order by 1 desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalents"] = json_decode($row["equivalent"]);
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else  if ($_GET["type"] == "getenftype_for_wo") {
             $sql = "Select * from material_type where material_type = 'Engineering' AND plant_id = '".$_GET["plant_id"]."' order by 1 desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalents"] = json_decode($row["equivalent"]);
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else  if ($_GET["type"] == "get_eng_type1") {
          $sql = "Select * from material_type where  plant_id = '".$_GET["plant_id"]."' order by 1 desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalents"] = json_decode($row["equivalent"]);
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else  if ($_GET["type"] == "get_fg_api_products_list") {
          $output = array();
          $sql = "Select max(id) as id, product_nature from master_fg_types plant_id = '".$_GET["plant_id"]."' 
          group by product_nature order by 1 desc";
         $result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                        $sql1 = "SELECT id,dosage_form_type,dosage_sizes,dosage_shapes,dosage_sizes,dosage_sub_form as sub_type FROM master_fg_types WHERE product_nature='".$row["product_nature"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                                //$output1['dosage_form'] = $row1['dosage_form_type'];
                                //$output1['dosage_form_sizes'] = json_encode($row1['dosage_sizes']);
                            }
                        }
                        $row["sub_materials"] = $output1;
                        $output[] = $row;
                }
            }
             $data["material_types"] = $output;
            
            
            echo json_encode($data);
    }
    
    else if($_GET["type"] == "getrmpmMaterialtype") {
        $output = Array();
        $sql = "SELECT * FROM material_type WHERE plant_id = '".$_GET["plant_id"]."' order by id ASC";
     
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "get_gen_material_subtype") {
        $output = Array();
        $sql = "SELECT * FROM gen_material_type WHERE plant_id = '".$_GET["plant_id"]."'
        AND material_type = '".$_GET["material_type"]."' order by id desc";
     
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getdepartment") {
        $output = Array();
        $sql = "SELECT * FROM department     order by id desc";
     
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getmaterialBysubtyper") {
        $output = Array();
        $sql = "SELECT id,plant_id,material_name,material_code,material_subtype,material_type FROM others_material WHERE plant_id = '".$_GET["plant_id"]."' AND material_subtype = '".$_GET["material_subtype"]."' order by id desc";
     
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "saveStock") {
     
        $batches = $input["material_list"];
        $jadugar = false;
        
        
        for ($i = 0; $i < count($batches); $i++) {
            
            $material = $batches[$i];
            
             $sql = "INSERT INTO `engi_stock`( plant_id,`department`, `material_subtype`, `material_type`, `material_code`, `batch_no`, `mfg_date`,
            `exp_date`, `grn_no`, `pack_size`, `qty`, `unit`, `entry_by`, `entry_date`, `status`) VALUES ('".$_GET["plant_id"]."',
            '".$material["department"]."',
            '".$material["material_subtype"]."', '".$material["material_type"]."',   '".$material["material_code"]."', 
            '".$material["batch_no"]."', '".$material["mfg_date"]."', '".$material["exp_date"]."' ,'".$material["grn_no"]."',
            '".$material["pack_size"]."', '".$material["qty"]."', '".$material["unit"]."','".$_GET["emp_id"]."'  ,'$entry_date' , 'Approved')";
          
            // $conn->query($sql);
            
        if($conn->query($sql)){
            $jadugar = true;
        } else {
             $jadugar = false;
        }
            
        }
     
         if($jadugar){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
     
    }
    
    
    
    
    else if($_GET["type"] == "getMaterialtype") {
        $output = Array();
        $sql = "SELECT * FROM batch_checklist WHERE dosage_form='".$_GET["dosage_form"]."' order by id desc";
     
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "get_materialsby_type") {
        $output = Array();
       $sql = "SELECT * FROM material WHERE material_type='".$_GET["material_type"]."' and plant_id = '".$_GET["plant_id"]."' ";
       
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getRawMaterialtype") {
        $output = Array();
       $sql = "SELECT * FROM material_type WHERE material_type='Raw Material' and plant_id = '".$_GET["plant_id"]."' ";
       
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getMTypeByType") {
        $output = Array();
       $sql = "SELECT * FROM material_type WHERE ( material_type = 'Raw Material' OR material_type = 'Packing Material' ) and plant_id = '".$_GET["plant_id"]."' ";
       
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getMatTypeByMatType_UOM") {
        $output = Array();
       $sql = "SELECT id,material_type,material_subtype,Short_Code FROM material_type WHERE (material_type = 'Semi Finished Goods' or material_type = 'Finish Product' )and plant_id = '".$_GET["plant_id"]."' ";
       
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getMatTypeByMatType") {
        $output = Array();
       $sql = "SELECT id,material_type,material_subtype,Short_Code FROM material_type WHERE material_type = '".$_GET["material_type"]."' and plant_id = '".$_GET["plant_id"]."' ";
       
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getPackingMaterialtype1") {
        $output = Array();
        $sql = "SELECT * FROM material_type WHERE material_type='Packing Material'  and plant_id = '".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getPackingMaterialtype") {
        $output = Array();
        $sql = "SELECT max(id) as id,material_subtype FROM material_type WHERE material_type='Packing Material'  and plant_id = '".$_GET["plant_id"]."' group by material_subtype ";
        $result1 = $conn->query($sql);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output[] = $row1;
                            }
                        }
                        $output[] = $row1;
        echo json_encode($output);
    }
     else if($_GET["type"] == "getSubMaterials") {
        $output = Array();
        $sql = "SELECT * FROM material_type WHERE material_type='".$_GET["material_type"]."' and plant_id =  '".$_GET["plant_id"]."'";
       $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
         
    else if($_GET["type"] == "GetProducts") {
        $output = Array();
          $sql = "SELECT * FROM product WHERE plant_id='".$_GET['plant_id']."'";
       $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row['label_claim'] = json_decode($row['label_claim']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
        else if($_GET["type"] == "GetProductCode") {
            // print_r($)
        $output = Array();
          $sql = "SELECT * FROM product WHERE product_name='".$_GET['product_name']."'";
       $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
  
    else if($_GET["type"] == "getMaterialsForTesting") {
        $output = Array();
        $sql = "SELECT max(id) as id, material_type,has_nature_of_material FROM material_type  
        where plant_id =  '".$_GET["plant_id"]."'group by material_type,has_nature_of_material order by material_type";
        $result = $conn->query($sql);
        //$result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                        $sql1 = "SELECT id,material_subtype,sampling_criteria,sampling_qty,control_sample_type,
                        control_reserve_criteria,retest_type 
                        FROM material_type WHERE material_type='".$row["material_type"]."'  
                        and plant_id =  '".$_GET["plant_id"]."' ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                        $row["sub_materials"] = $output1;
                        $output[] = $row;
                }
            }
             $data["material_types"] = $output;
        echo json_encode($data);
    }
    else if($_GET["type"] == "getAllMaterials") {
        $output = Array();
        $sql = "SELECT max(id) as id, material_type FROM material_type where plant_id = '".$_GET["plant_id"]."' and status='Approved' group by material_type order by material_type";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
                    $sql1 = "SELECT id,material_subtype FROM material_type WHERE material_type='".$row["material_type"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["sub_materials"] = $output1;
                    $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
    }
} catch (\Throwable $e) {
   echo "{\"statuse\":\"".$e."\"}";
}

}else{
     echo "{\"status\":\"Method Not Found\"}";
}

$conn->close();
?>