<?php 

function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } else if (is_string($mixed)) {
        return utf8_encode($mixed);
    }
    return $mixed;
}

function mx($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function ensureMaterialSourceTypeColumn($conn)
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $res = $conn->query("SHOW COLUMNS FROM material LIKE 'source_type'");
    if ($res && $res->num_rows == 0) {
        $conn->query("ALTER TABLE material ADD COLUMN source_type VARCHAR(30) NULL DEFAULT NULL AFTER material_type");
    }
}

function columnExists($conn, $table, $column)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = $conn->real_escape_string($column);
    $check = $conn->query("SHOW COLUMNS FROM `".$table."` LIKE '".$column."'");
    return $check && $check->num_rows > 0;
}

function getMaterialTypeCodeForPattern($materialType)
{
    $map = array(
        'Raw Material' => '10',
        'Packing Material' => '20',
        'General Material' => '30',
        'GM' => '30',
        'Engineering Spares' => '40',
        'Equipments' => '50',
        'Equipment' => '50',
    );
    return isset($map[$materialType]) ? $map[$materialType] : '10';
}

function getSourceTypeCodeForPattern($sourceType)
{
    $sourceType = trim((string)$sourceType);
    $map = array(
        'Imported' => '1',
        'Import' => '1',
        'Local' => '2',
        'Domestic' => '2',
        'Local/Imported' => '3',
        'Local/Import' => '3',
    );
    return isset($map[$sourceType]) ? $map[$sourceType] : '2';
}

function getGradeCodesPartForMaterialCode($conn, $plantId, $gradeCsv)
{
    $grades = array_filter(array_map('trim', explode(',', (string)$gradeCsv)));
    if (count($grades) === 0) {
        return '';
    }
    sort($grades, SORT_STRING);
    $part = '';
    $plantEsc = mysqli_real_escape_string($conn, $plantId);
    foreach ($grades as $gradeName) {
        $gradeEsc = mysqli_real_escape_string($conn, $gradeName);
        $sql = "SELECT code FROM grade WHERE plant_id='".$plantEsc."' AND grade='".$gradeEsc."' AND status='active' LIMIT 1";
        $res = $conn->query($sql);
        if (!$res || $res->num_rows == 0) {
            return false;
        }
        $row = $res->fetch_assoc();
        $code = trim((string)($row['code'] ?? ''));
        if ($code === '') {
            return false;
        }
        $part .= $code;
    }
    return $part;
}

function generateAutoMaterialCode($conn, $plantId, $materialType, $gradeCsv, $sourceType)
{
    $xx = getMaterialTypeCodeForPattern($materialType);
    $yPart = getGradeCodesPartForMaterialCode($conn, $plantId, $gradeCsv);
    if ($yPart === false || $yPart === '') {
        return false;
    }
    $z = getSourceTypeCodeForPattern($sourceType);
    $prefix = $xx.$yPart.$z;
    $plantEsc = mysqli_real_escape_string($conn, $plantId);
    $prefixEsc = mysqli_real_escape_string($conn, $prefix);
    $next = 1;
    $sql = "SELECT material_code FROM material WHERE plant_id='".$plantEsc."' AND material_code REGEXP '^".$prefixEsc."[0-9]{4}$' ORDER BY material_code DESC LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $lastCode = isset($row['material_code']) ? $row['material_code'] : '';
        if (strlen($lastCode) >= 4) {
            $next = intval(substr($lastCode, -4)) + 1;
        }
    }
    if ($next > 9999) {
        $next = 9999;
    }
    return $prefix.str_pad($next, 4, '0', STR_PAD_LEFT);
}

require '../db.php';
require '../token.php';
header('response_token: test123456');

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$output = Array();
$token = $_GET["token"];
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
   
    if ($_GET["type"] == "checkMaterialDuplicate") {
        $plant_id = mysqli_real_escape_string($conn, $_GET["plant_id"]);
        $material_type = isset($_GET["material_type"]) ? mysqli_real_escape_string($conn, trim($_GET["material_type"])) : '';
        $material_name = isset($_GET["material_name"]) ? trim($_GET["material_name"]) : '';
        $material_code = isset($_GET["material_code"]) ? strtoupper(trim($_GET["material_code"])) : '';
        $duplicates = array();
        $typeClause = $material_type !== '' ? " AND material_type='".$material_type."'" : '';

        if ($material_name !== '') {
            $nameEsc = mysqli_real_escape_string($conn, $material_name);
            $sql = "SELECT material_code, material_name FROM material WHERE plant_id='".$plant_id."'".$typeClause."
                AND LOWER(TRIM(material_name))=LOWER('".$nameEsc."')
                AND material_code IS NOT NULL AND material_code != '' AND material_code != 'NA' LIMIT 1";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $duplicates[] = array(
                    "field" => "material_name",
                    "message" => "Material name already exists (code: ".$row["material_code"].")."
                );
            }
        }

        if ($material_code !== '') {
            $codeEsc = mysqli_real_escape_string($conn, $material_code);
            $sql = "SELECT material_code, material_name FROM material WHERE plant_id='".$plant_id."'".$typeClause."
                AND UPPER(TRIM(material_code))='".$codeEsc."'
                AND material_code IS NOT NULL AND material_code != '' AND material_code != 'NA' LIMIT 1";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $duplicates[] = array(
                    "field" => "material_code",
                    "message" => "Material code already exists (name: ".$row["material_name"].")."
                );
            }
        }

        echo json_encode(array(
            "status" => count($duplicates) > 0 ? "duplicate" : "available",
            "duplicate" => $duplicates
        ));
    }
    else if ($_GET["type"] == "saveMaterial") {
        try{
            ensureMaterialSourceTypeColumn($conn);
            $data = $_POST;        
       
            $msdsFile = "NA"; 
            
            $matIs = isset($data['matIs']) ? $data['matIs'] : 'OWN'; // OWN / Client
            $source_type = isset($data['source_type']) ? trim($data['source_type']) : 'Local';
            if ($source_type === '') {
                echo json_encode(array("status" => "invalid", "message" => "Source type is required."));
                exit;
            }
            
            if ($matIs == 'OWN') {
                $GL = 'MC00';
            } else {
                
                $GL = isset($data['client_code']) ? $data['client_code'] : '';
              
            }

            $material_name_raw = isset($data["material_name"]) ? trim($data["material_name"]) : '';
            if ($material_name_raw === '') {
                echo json_encode(array("status" => "invalid", "message" => "Material name is required."));
                exit;
            }
            $user_material_code = isset($data["material_code"]) ? strtoupper(trim($data["material_code"])) : '';
            $plant_id = mysqli_real_escape_string($conn, $_GET["plant_id"]);
            $material_type_esc = mysqli_real_escape_string($conn, isset($data["material_type"]) ? $data["material_type"] : '');
            $typeClause = $material_type_esc !== '' ? " AND material_type='".$material_type_esc."'" : '';

            $nameEsc = mysqli_real_escape_string($conn, $material_name_raw);
            $sqlDupName = "SELECT id FROM material WHERE plant_id='".$plant_id."'".$typeClause."
                AND LOWER(TRIM(material_name))=LOWER('".$nameEsc."')
                AND material_code IS NOT NULL AND material_code != '' AND material_code != 'NA' LIMIT 1";
            $resDupName = $conn->query($sqlDupName);
            if ($resDupName && $resDupName->num_rows > 0) {
                echo json_encode(array("status" => "duplicate", "message" => "Material name already exists for this plant."));
                exit;
            }
            if ($user_material_code !== '') {
                $codeEsc = mysqli_real_escape_string($conn, $user_material_code);
                $sqlDupCode = "SELECT id FROM material WHERE plant_id='".$plant_id."'".$typeClause."
                    AND UPPER(TRIM(material_code))='".$codeEsc."' LIMIT 1";
                $resDupCode = $conn->query($sqlDupCode);
                if ($resDupCode && $resDupCode->num_rows > 0) {
                    echo json_encode(array("status" => "duplicate", "message" => "Material code already exists for this plant."));
                    exit;
                }
            }
 
            $material_name = $nameEsc; 
            $grade =   isset($data["grade"]) ? $data["grade"] : '';

            if ($user_material_code === '') {
                $gradeCodePart = getGradeCodesPartForMaterialCode($conn, $_GET["plant_id"], $grade);
                if ($gradeCodePart === false || $gradeCodePart === '') {
                    echo json_encode(array(
                        "status" => "invalid",
                        "message" => "Each selected grade must have a code configured in Grade Master."
                    ));
                    exit;
                }
            }
                
            $source_type_esc = mysqli_real_escape_string($conn, $source_type);
            $sql = "INSERT INTO `material`(`plant_id`, `material_type`,`source_type`,`material_sub_type_id`, `material_subtype`, `material_name`,`material_nature`, `location`, 
            `storage_condition`, `inventory`, `density`, `unit`, `lead_time`, `category`, `hsn`, `gst`,`tax`, `status`, `entry_by`,`entry_date`, `sub_type`, 
            `equivalancy_applicable`, `equivalent_to`, `description`, `client_code`,`mainGroupSeries`, `grade`, `uom`, `alternate_uom`,`pack_size`,`packing_requirement`, 
            `safety`, `color_index`, `type`, `product_n`, `m_photo`,`leverages`,`specificGravity`,`texture`,`madeOf`,`dimension`,`material_name_report`,`plant_code`,
            `materialTypeCode`,`materialSubTypeCode`,`packSizeCode`,`tax_type`,`maxInventory`,`moq`,`plasticType`,`inventoryValueMax`,`premixItem`,`assayCalculation`,
            `Functional_categoryList`,`indent_type`,`artwork`) VALUES ('".$_GET["plant_id"]."','".$data["material_type"]."','".$source_type_esc."','".$data["material_sub_type_id"]."',
            '".$data["material_subtype"]."','".$material_name."','".$data["material_nature"] ."','".$data["location"]."','".$data["storage_condition"]."',
            '".$data["inventory"]."','".$data["density"]."','".$data["uom"]."','".$data["lead_time"]."','".$data["category"]."','".$data["hsn"]."','".$data["gst"]."','".$data["tax"]."', 
            'Pending','".$_GET["emp_id"]."','".$entry_date."','".$data["sub_type"]."','".$data["equivalancy_applicable"]."','".$data["equivalent_to"]."',
            '".$data["description"]."','".$data["client_code"]."','".$data["client_code"]."','$grade','".$data["uom"]."','".$data["alternate_uom"]."',
            '".$data["pack_size"]."','".$data["packing_requirement"]."','".$data["safety"]."','".$data["color_index"]."','".$data["type"]."','".$data["product_n"]."',
            '$msdsFile','".$data["leverages"]."','".$data["specificGravity"]."','".$data["texture"]."','".$data["madeOf"]."','".$data["dimension"]."',
            '".$data["material_name_report"]."','".$data["plant_code"]."','".$data["materialTypeCode"]."','".$data["materialSubTypeCode"]."',
            '".$data["packSizeCode"]."','".$data["taxType"]."','".$data["maxInventory"]."','".$data["moq"]."','".$data["plasticType"]."','".$data["inventoryValueMax"]."','".$data["premixItem"]."','".$data["assayCalculation"]."',
            '".$data["Functional_categoryList"]."', '".$data["indent_type"]."', '".$data["artwork"]."')";
                         
            if($conn->query($sql)){ 
                $last_id = $conn->insert_id;
                $material_code = 'NA';

                if ($user_material_code !== '') {
                    $material_code = $user_material_code;
                } else {
                    $material_code = generateAutoMaterialCode(
                        $conn,
                        $_GET["plant_id"],
                        $data["material_type"],
                        $grade,
                        $source_type
                    );
                    if ($material_code === false) {
                        $conn->query("DELETE FROM material WHERE id='".$last_id."'");
                        echo json_encode(array(
                            "status" => "invalid",
                            "message" => "Unable to generate material code. Ensure each selected grade has a code in Grade Master."
                        ));
                        exit;
                    }
                }
                
                    $mother_material_code = '';
                    if ($matIs == 'OWN') {
                        $mother_material_code = $material_code;
                    } else {
                    
                        if (!empty($data['mother_material_code'])) {
                            $mother_material_code = $data['mother_material_code'];
                        } else {
                            $mother_material_code = '';
                        }
                    }
                 
                $update = $conn->prepare("UPDATE material SET material_code=? ,mother_material_code=? WHERE id=?");
                $update->bind_param("ssi", $material_code,$mother_material_code, $last_id);
                $update->execute();

                echo json_encode(array(
                    "status" => "success",
                    "material_code" => $material_code
                ));
                
            }else{
                echo "{\"status\":\"".$conn->error."\"}";
            }
                                    
     
        } catch (\Throwable $e) {
               echo "{\"statuse\":\"".$e."\"}";
        }
    }  
    else if ($_GET["type"] == "getAllMaterialsForLog") {
    // Get filter parameters
    $material_subtype = isset($_GET["material_subtype"]) ? mysqli_real_escape_string($conn, $_GET["material_subtype"]) : '';
    $grade = isset($_GET["grade"]) ? mysqli_real_escape_string($conn, $_GET["grade"]) : '';
    $material_nature = isset($_GET["material_nature"]) ? mysqli_real_escape_string($conn, $_GET["material_nature"]) : '';
    $search = isset($_GET["search"]) ? mysqli_real_escape_string($conn, trim($_GET["search"])) : '';
    
    // Build WHERE conditions for filtering
    $whereConditions = [];
    
    // Search condition (searches in material_code and material_name)
    if (!empty($search)) {
        $searchEscaped = "%" . $search . "%";
        $whereConditions[] = "(material_code LIKE '" . $searchEscaped . "' OR material_name LIKE '" . $searchEscaped . "')";
    }
    
    if (!empty($material_subtype)) {
        $whereConditions[] = "material_subtype = '" . $material_subtype . "'";
    }
    if (!empty($grade)) {
        $whereConditions[] = "grade = '" . $grade . "'";
    }
    if (!empty($material_nature)) {
        $whereConditions[] = "material_nature = '" . $material_nature . "'";
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Main SQL Query: UNION ALL to fetch from both material and others_material tables
    // NO LIMIT - fetches all records
    $sql = "
        SELECT * FROM (
            SELECT 
                id,
                material_code,
                COALESCE(material_name, '') AS material_name,
                COALESCE(material_type, '') AS material_type,
                COALESCE(material_subtype, '') AS material_subtype,
                COALESCE(grade, '') AS grade,
               
                COALESCE(indend_prepare_date, 0) AS indend_prepare_date,
                COALESCE(Purchase_prepare_date, 0) AS Purchase_prepare_date,
                COALESCE(moisture, 0) AS moisture,
                COALESCE(Sampling_prepare_date, 0) AS Sampling_prepare_date,
                COALESCE(release_prepare_date, 0) AS release_prepare_date,
                COALESCE(PurchaseDeliveryTime, 0) AS PurchaseDeliveryTime,
                COALESCE(ForPayment, 0) AS ForPayment,
                'material' AS source_table
            FROM material
            " . $whereClause . "
            
            UNION ALL
            
            SELECT 
                id,
                material_code,
                COALESCE(material_name, '') AS material_name,
                COALESCE(material_type, '') AS material_type,
                COALESCE(material_subtype, '') AS material_subtype,
                COALESCE(grade, '') AS grade,
                
                COALESCE(indend_prepare_date, 0) AS indend_prepare_date,
                COALESCE(Purchase_prepare_date, 0) AS Purchase_prepare_date,
                COALESCE(moisture, 0) AS moisture,
                COALESCE(Sampling_prepare_date, 0) AS Sampling_prepare_date,
                COALESCE(release_prepare_date, 0) AS release_prepare_date,
                COALESCE(PurchaseDeliveryTime, 0) AS PurchaseDeliveryTime,
                COALESCE(ForPayment, 0) AS ForPayment,
                'others_material' AS source_table
            FROM others_material
            " . $whereClause . "
        ) AS combined_results
        ORDER BY material_code ASC
    ";
    
    $result = $conn->query($sql);
    $output = array();
    
    // Check for SQL errors
    if (!$result) {
        // SQL Error - return error response
        $response = array(
            'data' => array(),
            'total' => 0,
            'error' => 'Database query error: ' . $conn->error
        );
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Fetch all results
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    
    // Return all data (no pagination)
    $response = array(
        'data' => $output,
        'total' => count($output)
    );
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
    else if ($_GET["type"] == "downloadMaterialLogExcel") {
        $material_type = isset($_GET["material_type"]) ? mysqli_real_escape_string($conn, $_GET["material_type"]) : '';
        $search = isset($_GET["search"]) ? trim($_GET["search"]) : '';
        $searchEsc = mysqli_real_escape_string($conn, $search);
        $plant_id = mysqli_real_escape_string($conn, $_GET["plant_id"]);

        $where = array();
        $where[] = "plant_id = '".$plant_id."'";
        if ($material_type !== '') {
            $where[] = "material_type = '".$material_type."'";
        }
        $where[] = "(status = 'Approved' OR status = 'In-Active')";

        if ($search !== '') {
            $like = "%".$searchEsc."%";
            $where[] = "(
                IFNULL(material_code,'') LIKE '".$like."' OR
                IFNULL(material_name,'') LIKE '".$like."' OR
                IFNULL(material_subtype,'') LIKE '".$like."' OR
                IFNULL(grade,'') LIKE '".$like."' OR
                IFNULL(material_nature,'') LIKE '".$like."' OR
                IFNULL(uom,'') LIKE '".$like."' OR
                IFNULL(status,'') LIKE '".$like."' OR
                IFNULL(entry_by,'') LIKE '".$like."' OR
                IFNULL(entry_date,'') LIKE '".$like."'
            )";
        }

        $sql = "SELECT id, entry_date, material_type, material_subtype, material_code, material_name, grade, material_nature, uom, status, entry_by,
                category, alternate_uom, material_name_report, sub_type, color_index, dimension, madeOf
                FROM material
                WHERE ".implode(" AND ", $where)."
                ORDER BY id DESC";

        $result = $conn->query($sql);

        $mode = 'mixed';
        if ($material_type === 'Raw Material') {
            $mode = 'raw';
        } else if ($material_type === 'Packing Material') {
            $mode = 'packing';
        }

        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename=Material_Master_Log_".date('Ymd_His').".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo chr(239).chr(187).chr(191);

        if ($mode === 'raw') {
            $headers = array(
                "Sr. No", "Entry Date", "Material Group/Type", "Material Code", "Material Name",
                "Category", "Nature of Material", "Pharmacopoeia/Grade", "Item Unit", "Billing Unit",
                "Status", "Material Status", "Entry By"
            );
        } else if ($mode === 'packing') {
            $headers = array(
                "Sr. No", "Entry Date", "Material Group/Type", "Material Code", "Material Name",
                "Material name (report)", "Pharmacopoeia/Grade", "Item Unit", "Billing Unit",
                "Sub Type", "Color", "Dimension", "Material Made of",
                "Status", "Material Status", "Entry By"
            );
        } else {
            $headers = array(
                "Sr. No", "Entry Date", "Material Type", "Material Group/Type", "Material Code", "Material Name",
                "Category", "Nature of Material", "Material name (report)", "Pharmacopoeia/Grade",
                "Item Unit", "Billing Unit", "Sub Type", "Color", "Dimension", "Material Made of",
                "Status", "Material Status", "Entry By"
            );
        }

        $ncols = count($headers);

        $headerClasses = array("h1","h2","h3","h4","h5","h6","h7","h8","h9","h10","h11");

        echo '<html><head><meta charset="UTF-8">
        <style>
            body { font-family: Calibri, Arial, sans-serif; margin: 0; padding: 10px; }
            .title { font-size: 15px; font-weight: 700; color: #1d3557; margin-bottom: 8px; }
            table { border-collapse: collapse; width: 100%; table-layout: auto; }
            th, td {
                border: 1px solid #9aa7b3;
                padding: 6px 8px;
                vertical-align: middle;
                word-wrap: break-word;
                overflow-wrap: anywhere;
                white-space: normal;
                font-size: 11px;
            }
            th { color: #fff; text-align: center; font-weight: 700; }
            .h1 { background: #1f4e78; } .h2 { background: #2f5597; } .h3 { background: #0070c0; }
            .h4 { background: #0f766e; } .h5 { background: #2e7d32; } .h6 { background: #6a1b9a; }
            .h7 { background: #5d4037; } .h8 { background: #37474f; } .h9 { background: #455a64; }
            .h10 { background: #1565c0; } .h11 { background: #283593; }
            .num { text-align: center; }
            .center { text-align: center; }
            .row-even { background: #f7fbff; }
            .row-odd { background: #ffffff; }
            .status-approved { color: #1b5e20; font-weight: 700; }
            .status-inactive { color: #b71c1c; font-weight: 700; }
            .ms-active { color: #0d6b0d; font-weight: 700; }
            .ms-inactive { color: #c0392b; font-weight: 700; }
        </style>
        </head><body>';

        echo '<div class="title">Material Master Log - '.mx($material_type === '' ? 'All' : $material_type).'</div>';
        echo '<table>';
        echo '<thead><tr>';
        for ($i = 0; $i < $ncols; $i++) {
            echo '<th class="'.$headerClasses[$i % 11].'">'.mx($headers[$i]).'</th>';
        }
        echo '</tr></thead><tbody>';

        $cellNA = 'NA';

        if ($result && $result->num_rows > 0) {
            $sr = 1;
            while ($row = $result->fetch_assoc()) {
                $entryDate = $row['entry_date'];
                if (!empty($entryDate)) {
                    $time = strtotime($entryDate);
                    $entryDate = $time ? date('d-m-Y H:i', $time) : $entryDate;
                } else {
                    $entryDate = 'NA';
                }

                $status = $row['status'] ?: 'NA';
                $materialStatus = 'NA';
                if ($status === 'Approved') {
                    $materialStatus = 'Active';
                } else if ($status === 'In-Active') {
                    $materialStatus = 'In Active';
                }

                $rowClass = ($sr % 2 === 0) ? 'row-even' : 'row-odd';
                $statusClass = ($status === 'Approved') ? 'status-approved' : (($status === 'In-Active') ? 'status-inactive' : '');
                $msClass = ($materialStatus === 'Active') ? 'ms-active' : (($materialStatus === 'In Active') ? 'ms-inactive' : '');

                $mt = isset($row['material_type']) ? $row['material_type'] : '';
                $isRowRaw = ($mt === 'Raw Material');
                $isRowPacking = ($mt === 'Packing Material');

                echo '<tr class="'.$rowClass.'">';
                echo '<td class="num">'.mx($sr).'</td>';
                echo '<td class="center">'.mx($entryDate).'</td>';

                if ($mode === 'raw') {
                    echo '<td>'.mx($row['material_subtype'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_code'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_name'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['category'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_nature'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['grade'] ?: $cellNA).'</td>';
                    echo '<td class="center">'.mx($row['uom'] ?: $cellNA).'</td>';
                    echo '<td class="center">'.mx($row['alternate_uom'] ?: $cellNA).'</td>';
                    echo '<td class="'.$statusClass.'">'.mx($status).'</td>';
                    echo '<td class="'.$msClass.'">'.mx($materialStatus).'</td>';
                    echo '<td>'.mx($row['entry_by'] ?: $cellNA).'</td>';
                } else if ($mode === 'packing') {
                    echo '<td>'.mx($row['material_subtype'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_code'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_name'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_name_report'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['grade'] ?: $cellNA).'</td>';
                    echo '<td class="center">'.mx($row['uom'] ?: $cellNA).'</td>';
                    echo '<td class="center">'.mx($row['alternate_uom'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['sub_type'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['color_index'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['dimension'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['madeOf'] ?: $cellNA).'</td>';
                    echo '<td class="'.$statusClass.'">'.mx($status).'</td>';
                    echo '<td class="'.$msClass.'">'.mx($materialStatus).'</td>';
                    echo '<td>'.mx($row['entry_by'] ?: $cellNA).'</td>';
                } else {
                    echo '<td>'.mx($mt ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_subtype'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_code'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_name'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($isRowRaw ? ($row['category'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td>'.mx($isRowRaw ? ($row['material_nature'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td>'.mx($isRowPacking ? ($row['material_name_report'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td>'.mx($row['grade'] ?: $cellNA).'</td>';
                    echo '<td class="center">'.mx($row['uom'] ?: $cellNA).'</td>';
                    echo '<td class="center">'.mx($row['alternate_uom'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($isRowPacking ? ($row['sub_type'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td>'.mx($isRowPacking ? ($row['color_index'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td>'.mx($isRowPacking ? ($row['dimension'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td>'.mx($isRowPacking ? ($row['madeOf'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td class="'.$statusClass.'">'.mx($status).'</td>';
                    echo '<td class="'.$msClass.'">'.mx($materialStatus).'</td>';
                    echo '<td>'.mx($row['entry_by'] ?: $cellNA).'</td>';
                }

                echo '</tr>';

                $sr++;
            }
        } else {
            echo '<tr><td colspan="'.(int)$ncols.'" class="center">No records found</td></tr>';
        }

        echo '</tbody></table></body></html>';
        exit;
    }
    else if ($_GET["type"] == "saveClientMatCode") {
        try{
            
            $data = $input;        
       
            $msdsFile = "NA";
               
            $material_name = mysqli_real_escape_string($conn, $data["material_name"]);
            $grade =   $data["grade"]; 
                 
            $sql = " INSERT INTO `material`(`plant_id`, `matIs`,`material_type`,`mother_material_code`,`material_sub_type_id`, `material_subtype`, `material_name`,`material_nature`, `location`, 
            `storage_condition`, `inventory`, `density`, `unit`, `lead_time`, `category`, `hsn`, `gst`,`tax`, `status`, `entry_by`,`entry_date`, `sub_type`, `equivalancy_applicable`, `equivalent_to`, `description`, 
            `client_code`,`mainGroupSeries`, `grade`, `uom`, `alternate_uom`,`pack_size`,`packing_requirement`, `safety`, `color_index`, `type`, `product_n`, `m_photo`,`leverages`,`specificGravity`,`texture`,`madeOf`,
            `dimension`,`material_name_report`,`plant_code`, `materialTypeCode`,`materialSubTypeCode`,`packSizeCode`,`tax_type`,`maxInventory`,`moq`,`plasticType`,`inventoryValueMax`,`premixItem`,`assayCalculation`,
            Functional_categoryList,testingRequired,controlSample,sampleForTesting,samplingUnit,indent_type) VALUES ('".$_GET["plant_id"]."','CHILD','".$data["material_type"]."','".$data["material_code"]."','".$data["material_sub_type_id"]."',
            '".$data["material_subtype"]."','".$material_name."','".$data["material_nature"] ."','".$data["location"]."','".$data["storage_condition"]."','".$data["inventory"]."','".$data["density"]."','".$data["uom"]."',
            '".$data["lead_time"]."','".$data["category"]."','".$data["hsn"]."','".$data["gst"]."','".$data["tax"]."','Pending','".$_GET["emp_id"]."','".$entry_date."','".$data["sub_type"]."','".$data["equivalancy_applicable"]."',
            '".$data["equivalent_to"]."','".$data["description"]."','".$data["client_code"]."','".$data["mainGroupSeries"]."','$grade','".$data["uom"]."','".$data["alternate_uom"]."', '".$data["pack_size"]."',
            '".$data["packing_requirement"]."','".$data["safety"]."','".$data["color_index"]."','".$data["type"]."','".$data["product_n"]."','$msdsFile','".$data["leverages"]."','".$data["specificGravity"]."','".$data["texture"]."',
            '".$data["madeOf"]."','".$data["dimension"]."','".$data["material_name_report"]."','".$data["plant_code"]."','".$data["materialTypeCode"]."','".$data["materialSubTypeCode"]."',
            '".$data["packSizeCode"]."','".$data["tax_type"]."','".$data["maxInventory"]."','".$data["moq"]."','".$data["plasticType"]."','".$data["inventoryValueMax"]."','".$data["premixItem"]."',
            '".$data["assayCalculation"]."','".$data["Functional_categoryList"]."','".$data["testingRequired"]."','".$data["controlSample"]."','".$data["sampleForTesting"]."','".$data["samplingUnit"]."','".$data["indent_type"]."')";
                         
            if($conn->query($sql)){ 
                echo "{\"status\":\"success\"}";
                
                $material_code = 'NA';
                $last_id = $conn->insert_id;
                $plant_code = $data['plant_code'];
                $materialTypeCode = $data['materialTypeCode'];
                $materialSubTypeCode = $data['materialSubTypeCode'];
                $packSizeCode = $data['packSizeCode'];
                $padded_id = str_pad($last_id,4, '0', STR_PAD_LEFT);
                $GL = $data['client_code'];
                if ($data["material_type"] == 'Raw Material') {
                    $material_code = $plant_code.$materialTypeCode.$GL.$materialSubTypeCode.$padded_id;
                } else {
                    $material_code = $plant_code.$materialTypeCode.$GL.$materialSubTypeCode.$packSizeCode.$padded_id;
                }
                $update = $conn->prepare("UPDATE material SET material_code=? WHERE id=?");
                $update->bind_param("si", $material_code, $last_id);
                $update->execute();
                
            }else{
                echo "{\"status\":\"".$conn->error."\"}";
            }
                                    
     
        } catch (\Throwable $e) {
               echo "{\"statuse\":\"".$e."\"}";
        }
    }  
    else if ($_GET["type"] == "rndSaveMaterial") {
        try{
            
            $data = $_POST;        
       
            $msdsFile = "NA"; 
               
            $material_name = mysqli_real_escape_string($conn, $data["material_name"]);
            $grade =   $data["grade"]; 
                
            $sql = " INSERT INTO `rndMaterial`(`plant_id`, `material_type`,mother_material_code,`material_sub_type_id`, `material_subtype`, `material_name`,`material_nature`, `location`, 
            `storage_condition`, `inventory`, `density`, `unit`, `lead_time`, `category`, `hsn`, `gst`,`tax`, `status`, `entry_by`,`entry_date`, `sub_type`, 
            `equivalancy_applicable`, `equivalent_to`, `description`, `client_code`,`mainGroupSeries`, `grade`, `uom`, `alternate_uom`,`pack_size`,`packing_requirement`, 
            `safety`, `color_index`, `type`, `product_n`, `m_photo`,`leverages`,`specificGravity`,`texture`,`madeOf`,`dimension`,`material_name_report`,`plant_code`,
            `materialTypeCode`,`materialSubTypeCode`,`packSizeCode`,`tax_type`,`maxInventory`,`moq`,`plasticType`,`inventoryValueMax`,`premixItem`,`newPackMatDevelopment`) VALUES ('".$_GET["plant_id"]."','".$data["material_type"]."',
            '".$data["mother_material_code"]."','".$data["material_sub_type_id"]."','".$data["material_subtype"]."','".$material_name."','".$data["material_nature"] ."','".$data["location"]."','".$data["storage_condition"]."',
            '".$data["inventory"]."','".$data["density"]."','".$data["uom"]."','".$data["lead_time"]."','".$data["category"]."','".$data["hsn"]."','".$data["gst"]."','".$data["tax"]."', 
            'Pending','".$_GET["emp_id"]."','".$entry_date."','".$data["sub_type"]."','".$data["equivalancy_applicable"]."','".$data["equivalent_to"]."',
            '".$data["description"]."','".$data["client_code"]."','".$data["mainGroupSeries"]."','$grade','".$data["uom"]."','".$data["alternate_uom"]."',
            '".$data["pack_size"]."','".$data["packing_requirement"]."','".$data["safety"]."','".$data["color_index"]."','".$data["type"]."','".$data["product_n"]."',
            '$msdsFile','".$data["leverages"]."','".$data["specificGravity"]."','".$data["texture"]."','".$data["madeOf"]."','".$data["dimension"]."',
            '".$data["material_name_report"]."','".$data["plant_code"]."','".$data["materialTypeCode"]."','".$data["materialSubTypeCode"]."',
            '".$data["packSizeCode"]."','".$data["taxType"]."','".$data["maxInventory"]."','".$data["moq"]."','".$data["plasticType"]."','".$data["inventoryValueMax"]."','".$data["premixItem"]."','".$data["newPackMatDevelopment"]."')";
                         
            if($conn->query($sql)){ 
                echo "{\"status\":\"success\"}";
                
                $material_code = 'NA';
                $last_id = $conn->insert_id;
                $plant_code = $data['plant_code'];
                $materialTypeCode = $data['materialTypeCode'];
                $materialSubTypeCode = $data['materialSubTypeCode'];
                $packSizeCode = $data['packSizeCode'];
                $padded_id = str_pad($last_id,4, '0', STR_PAD_LEFT);
                $GL = 'RND';
                if ($data["material_type"] == 'Raw Material') {
                    $material_code = $plant_code.$materialTypeCode.$GL.$materialSubTypeCode.$padded_id;
                } else {
                    $material_code = $plant_code.$materialTypeCode.$GL.$materialSubTypeCode.$packSizeCode.$padded_id;
                }
                $update = $conn->prepare("UPDATE rndMaterial SET material_code=? WHERE id=?");
                $update->bind_param("si", $material_code, $last_id);
                $update->execute();
                
            }else{
                echo "{\"status\":\"".$conn->error."\"}";
            }
                                    
     
        } catch (\Throwable $e) {
               echo "{\"statuse\":\"".$e."\"}";
        }
    }  
     
    
        else if ($_GET["type"] == "updateMasterMaterialPurchaseStatus") {
        
          
            if($input['material_type']=='Raw Material' || $input['material_type']=='Packing Material'){
       $sql="UPDATE material SET indend_prepare_date='".$input["indend_prepare_date"]."',Purchase_prepare_date='".$input["Purchase_prepare_date"]."',moisture='".$input["moisture"]."',Sampling_prepare_date='".$input["Sampling_prepare_date"]."',release_prepare_date='".$input["release_prepare_date"]."',PurchaseDeliveryTime='".$input["PurchaseDeliveryTime"]."',ForPayment='".$input["ForPayment"]."' WHERE material_code='".$input["material_code"]."'";
            }else{
               $sql="UPDATE others_material SET indend_prepare_date='".$input["indend_prepare_date"]."',Purchase_prepare_date='".$input["Purchase_prepare_date"]."',moisture='".$input["moisture"]."',Sampling_prepare_date='".$input["Sampling_prepare_date"]."',release_prepare_date='".$input["release_prepare_date"]."',PurchaseDeliveryTime='".$input["PurchaseDeliveryTime"]."',ForPayment='".$input["ForPayment"]."' WHERE material_code='".$input["material_code"]."'";
            }
        if ($conn->query($sql)) {
             echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
 
    else if($_GET["type"] == "getMaterials_rec") {
          $output = Array();
        $sql="select * from material where plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                
            $q= "SELECT GROUP_CONCAT(grade)  as grade FROM    grade where id in ('".  $row['grade']."')";
            $resQ = $conn->query($q);
            $prodLatest = $resQ->fetch_assoc();
            $row['grade'] = $prodLatest['grade']; 
             
            
            
                 $output[] = $row;
            }
    } echo json_encode($output);
    }
    else if($_GET["type"] == "getMaterials_RM") {
          $output = Array();
      echo  $sql="select * from material";
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                
                 $output[] = $row;
            }
    } echo json_encode($output);
    }
     else if($_GET["type"] == "saveHSNCode") {
        $sql = "INSERT INTO HSN (HSN_Code ,entry_by,entry_date) VALUES ('".$input["hsncode"]."','".$_GET["emp_id"]."','".$entry_date."')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if($_GET["type"] == "approveMaterial") {
        
        $sql = "UPDATE material SET status = 'For_QA_Approval' ,approve_by = '".$_GET["emp_id"]."' , approve_date = '".$entry_date."' 
        where id = '".$_GET["id"]."'";
        
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "approveMaterialFromAccounts") {
        
        $sql = "UPDATE material SET status = 'For_QC_Approval' , hsn = '".$input["hsn"]."' , gst = '".$input["gst"]."' ,tax_type = '".$input["tax_type"]."' , approveAcc_by = '".$_GET["emp_id"]."' , approveAcc_date = '".$entry_date."' 
        where id = '".$_GET["id"]."'";
        
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "approveMaterialFromQc") {
        
        $sql = "UPDATE material SET status = 'For_QA_Approval' , testingRequired = '".$input["testingRequired"]."' , controlSample = '".$input["controlSample"]."' , sampleForTesting = '".$input["sampleForTesting"]."' , 
        samplingUnit = '".$input["samplingUnit"]."' , approveQc_by = '".$_GET["emp_id"]."' ,  approveQc_date = '".$entry_date."' where id = '".$_GET["id"]."'";
        
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "approveMaterialFromQa") {
        
        $sql = "UPDATE material SET status = 'Approved' ,approveQa_by = '".$_GET["emp_id"]."' , approveQa_date = '".$entry_date."' 
        where id = '".$_GET["id"]."'";
        
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "approveRndMaterial") {
        
        $sql = "UPDATE rndMaterial SET status = 'TO_NPD_APPROVAL' ,approve_by = '".$_GET["emp_id"]."' , approve_date = '".$entry_date."' 
        where id = '".$_GET["id"]."'";
        
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "acceptNewVenRegiRequuest") {
        
        $sql = "UPDATE `newVendorRegiRequestFromNpd` SET  `status` =  'REQ_ACCEPTED' , `venRegiReqAcceptBy` = '".$_GET["emp_id"]."' , `venRegiReqAcceptOn` = '$entry_date' where id = '".$input["id"]."' ";
        
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        } 
    }
    
    else if($_GET["type"] == "completeNewVenRegiRequuest") {
        
        $sql = "UPDATE `newVendorRegiRequestFromNpd` SET  `status` =  'Complete' , `venRegiReqCompBy` = '".$_GET["emp_id"]."' , `venRegiReqCompOn` = '$entry_date' where id = '".$input["id"]."' ";
        
        if($conn->query($sql)){
            
                $sql1 = "UPDATE `rndMaterial` SET   isNewVenRegi = 'DONE' where material_code = '".$input["material_code"]."' ";
                
                if($conn->query($sql1)){
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if($_GET["type"] == "approveRndMaterialFromNPD") {
        
        if($input['vendor_no'] == 'Register New Vendor'){
            
            $sql = "UPDATE rndMaterial SET status = 'Approved' ,npdApproveBy = '".$_GET["emp_id"]."' , npdApproveOn = '".$entry_date."' , isNewVenRegi = 'YES'   where id = '".$_GET["id"]."'";
            
            if($conn->query($sql)){

                $sql1 = "INSERT INTO `newVendorRegiRequestFromNpd`(`plant_id`, `material_code`, `material_name`, `vendorDetails`, `status`, `requestBy`, `requestOn`) VALUES ('".$_GET["plant_id"]."',
                '".$input["material_code"]."', '".$input["material_name"]."', '".$input["vendorDetails"]."','Pending','".$_GET["emp_id"]."','$entry_date')";
                
                if($conn->query($sql1)){
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
                
                
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
            
        }else{
            
            $sql = "UPDATE rndMaterial SET status = 'Approved' ,npdApproveBy = '".$_GET["emp_id"]."' , npdApproveOn = '".$entry_date."' ,  isNewVenRegi = 'NO'  where id = '".$_GET["id"]."'";
            
            if($conn->query($sql)){
                
                $sql1 = "INSERT INTO `mst_vendor_materials`(`plant_id`, `supplier_code`,`material_code`, `entry_by`, `entry_date`, `material_type`, `material_subtype`) VALUES  ('".$_GET["plant_id"]."',
                '".$input["vendor_no"]."', '".$input["material_code"]."', '".$_GET["emp_id"]."','$entry_date', '".$input["material_type"]."' , '".$input["material_subtype"]."')";
                
                if($conn->query($sql1)){
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
                
                
                
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }

        }
    }
    
    else if($_GET["type"] == "getVendorRegiRequestFromNpd"){
        $output = Array();
        $sql="select * from newVendorRegiRequestFromNpd where plant_id = '".$_GET["plant_id"]."' order by id desc" ;
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output[] = $row;
            }
        } 
        echo json_encode($output);
    }
    else if($_GET["type"] == "getHSNCode") 
    {
          $output = Array();
        $sql="select * from HSN" ;
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                
                 $output[] = $row;
            }
         } 
            echo json_encode($output);
    }
    else if($_GET["type"] == "getMaterials") {
 
        $output = Array();
        $plant = $_GET["plant_id"];
 
         
        $sql = "SELECT * FROM material m  WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."'
        AND (status IS NULL OR (status != 'In-Active' AND status != 'In Active' AND status != 'Inactive' AND LOWER(TRIM(status)) != 'in-active' AND status != 'Absolute' )) ORDER BY m.id DESC";
        
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row["equivalent_to"] = json_decode($row["equivalent_to"]); 
                $output[] = $row;
            }
        }
        // echo json_encode($output);
        
$output = utf8ize($output);
echo json_encode($output);
    } 
    else if($_GET["type"] == "getRndMaterials") {
 
        $output = Array();
        $plant = $_GET["plant_id"];

        $sql = "SELECT * FROM rndMaterial m  WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' and m.status = 'Approved'  ORDER BY m.material_name ASC";
      //  $sql = "SELECT * FROM materialMasterViewRndNormal m  WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' and m.status = 'Approved'  ORDER BY m.material_name ASC";
        
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if($_GET["type"] == "getExistingMaterial") {
 
        $output = Array();
  
         
        $sql = "SELECT m.* FROM material m  WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.material_subtype='".$_GET["material_subtype"]."'
        AND m.status = 'Approved' AND  m.matIs = 'OWN' ORDER BY m.id DESC";
        
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 

    else if($_GET["type"] == "getRndExistingMaterial") {
 
        $output = Array();
        $plant = $_GET["plant_id"];
 
         
        $sql = "SELECT * FROM rndMaterial m  WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.material_subtype='".$_GET["material_subtype"]."'
        AND status = 'Approved' ORDER BY m.id DESC";
        
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                
                $output1 = Array();
                $sql1 = "SELECT m.*,c.LglNm FROM clientMaterialsCodes m left join client_combined_view c ON m.client_code = c.client_code WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_code='".$row["material_code"]."'   ORDER BY m.id DESC";
                $result1 = $conn->query($sql1);
                     if($result1->num_rows > 0){
                    while($row1 = $result1->fetch_assoc()){
                        $output1[] = $row1;
                    }
                }
                
                $row['ClientMaterialCOdes'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
   
    else if($_GET["type"] == "getMaterialsForApproval") {
        $output = Array();
       $sql = "SELECT * FROM material  WHERE plant_id= '".$_GET["plant_id"]."' AND material_type='".$_GET["material_type"]."'
        AND status='Pending' ORDER BY material_name ASC";
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row["equivalent_to"] = json_decode($row["equivalent_to"]); 
                $output[] = $row;
            }
        }
       $output = utf8ize($output);
echo json_encode($output);
    } 
    else if($_GET["type"] == "getMaterialFOrAccountsApproval") {
        $output = Array();
        $sql = "SELECT * FROM material  WHERE plant_id= '".$_GET["plant_id"]."' AND material_type='".$_GET["material_type"]."'
        AND status='For_Accounts_Approval' ORDER BY id DESC";
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row["equivalent_to"] = json_decode($row["equivalent_to"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "getMaterialFOrQcApproval") {
        $output = Array();
        $sql = "SELECT * FROM material  WHERE plant_id= '".$_GET["plant_id"]."' AND material_type='".$_GET["material_type"]."'
        AND status='For_QC_Approval' ORDER BY id DESC";
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row["equivalent_to"] = json_decode($row["equivalent_to"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "getMaterialFOrQaApproval") {
        $output = Array();
        $sql = "SELECT * FROM material  WHERE plant_id= '".$_GET["plant_id"]."' AND material_type='".$_GET["material_type"]."'
        AND status='For_QA_Approval' ORDER BY id DESC";
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row["equivalent_to"] = json_decode($row["equivalent_to"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "getRndMaterialsForApproval") {
        $output = Array();
        $sql = "SELECT * FROM rndMaterial  WHERE plant_id= '".$_GET["plant_id"]."' AND material_type='".$_GET["material_type"]."'
        AND status='Pending' ORDER BY id DESC";
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row["equivalent_to"] = json_decode($row["equivalent_to"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "getRndMaterialsForNpdApproval") {
        $output = Array();
        $sql = "SELECT * FROM rndMaterial  WHERE plant_id= '".$_GET["plant_id"]."' AND material_type='".$_GET["material_type"]."'
        AND ( status='TO_NPD_APPROVAL' OR isNewVenRegi != 'NO' ) ORDER BY id DESC";
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "getMaterialsByStatus") {
        $output = Array();
        $statusReq = trim((string)($_GET["status"] ?? ''));
        $statusFilter = "status = '".$conn->real_escape_string($statusReq)."'";
        if (strcasecmp($statusReq, 'In-Active') === 0) {
            $statusFilter = "(status = 'In-Active' OR status = 'In Active' OR status = 'Inactive' OR LOWER(TRIM(status)) = 'in-active')";
        } elseif (strcasecmp($statusReq, 'Approved') === 0) {
            $statusFilter = "(status = 'Approved' OR status = 'approve')";
        }
        $sql = "SELECT * FROM material WHERE plant_id= '".$_GET["plant_id"]."' AND material_type='".$conn->real_escape_string($_GET["material_type"])."'
        AND ".$statusFilter." ORDER BY id DESC";
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row["equivalent_to"] = json_decode($row["equivalent_to"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    
    else if($_GET["type"] == "getMaterialsVendor") {
 
        $output = Array();
        $plant = $_GET["plant_id"];
        if($plant==0){
        $sql = "SELECT DISTINCT m.*,c.LglNm FROM material m LEFT JOIN client c ON m.client_code=c.client_code 
        WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.status='approve' ORDER BY m.id DESC";
        //$sql = "SELECT m.*,g.material_name,g.material_sub_type_id,g.general_material_type,g.material_subtype FROM material m LEFT JOIN general_material g ON m.client_code=c.client_code 
      // WHERE plant_id='".$_GET["plant_id"]."' and general_material_type='".$_GET["material_type"]."'  order by 1 desc";
            
      }
      else if($_GET["plant_id"]==58 || $_GET["plant_id"]==59){
          $plant=$_GET["plant_id"];
       
         $sql = "SELECT DISTINCT m.*,p.m_photo ,p.structure_file_path,p.msds_file_path FROM material m left join product_other_information_api p 
         on m.id=p.product_id  WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.status='approve'
         ORDER BY m.id DESC"; 
        
        }
      else{
          $sql = "SELECT DISTINCT m.*,p.m_photo ,p.structure_file_path,p.msds_file_path FROM material m left join product_other_information_api p 
         on m.id=p.product_id  left join mst_vendor_materials v ON v.material_code = m.material_code WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.status='approve'
         and p.product_code=''  and v.manufacturer_code = '".$_GET["vendor_no"]."' ORDER BY m.id DESC"; 
        
        }
        
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                // $row = array_map('utf8_encode', $row);

              //  print_r($row);
          
          
             $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM grade where id in ('".$row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
          
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 

    else if($_GET["type"] == "getMaterials_pk") {
 
        $output = Array();

        $sql = "SELECT  * FROM material   WHERE plant_id= '".$_GET["plant_id"]."' AND material_type='Packing Material' AND  (status = 'approve' OR status = 'Pending' )   ORDER BY id";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "getMaterials_pk_vendor") {
 
        $output = Array();
        $plant = $_GET["plant_id"];
        if($plant==0){
        $sql = "SELECT DISTINCT m.material_code, m.*,c.LglNm FROM material m LEFT JOIN client c ON m.client_code=c.client_code 
        WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.status='approve' ORDER BY m.id DESC";
        //$sql = "SELECT m.*,g.material_name,g.material_sub_type_id,g.general_material_type,g.material_subtype FROM material m LEFT JOIN general_material g ON m.client_code=c.client_code 
       // WHERE plant_id='".$_GET["plant_id"]."' and general_material_type='".$_GET["material_type"]."'  order by 1 desc";
            
       }else{
    //  echo    $sql = "SELECT DISTINCT m.material_code,m.id, m.*,p.artwork_no as p_artwork_no,p.structure_file_path,p.msds_file_path , p.artwork as p_artwork,p.shadecard as p_shadecard,p.artwork_file as p_artwork_file,p.shadecard_file as p_shadecard_file,p.m_photo as pm_photo, c.LglNm,z.product_code,m.product_n,z.product_name as z_name FROM material m left join product_other_information_api p on m.id=p.product_id LEFT JOIN client c on m.client_code=c.client_code LEFT JOIN product z on m.product_n=z.product_code   WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.status='approve'  ORDER BY m.id DESC"; 
          $sql = "SELECT DISTINCT m.material_code,m.id, m.*,p.artwork_no as p_artwork_no,p.structure_file_path,p.msds_file_path , p.artwork as p_artwork,
         p.shadecard as p_shadecard,p.artwork_file as p_artwork_file,p.shadecard_file as p_shadecard_file,p.m_photo as pm_photo, c.LglNm FROM material m 
         left join product_other_information_api p on m.id=p.product_id left join mst_vendor_materials v ON v.material_code = m.material_code  LEFT JOIN client c on m.client_code=c.client_code   WHERE m.plant_id= '".$_GET["plant_id"]."' 
         AND m.material_type='".$_GET["material_type"]."' AND m.status='approve'  and v.manufacturer_code = '".$_GET["vendor_no"]."'  ORDER BY m.id DESC"; 
        
        }
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row = array_map('utf8_encode', $row);

              //  print_r($row);
          
          
             $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
          
                
                 $row["p_artwork"] = json_decode($row["p_artwork"]); 
                 $row["p_shadecard"] = json_decode($row["p_shadecard"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
      else if($_GET["type"] == "getActiveMaterials") {
        $output = Array();
        $plant = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $sql = "SELECT * FROM material m
                WHERE m.plant_id='".$plant."'
                AND (m.status = 'Approved' OR m.status = 'approve')
                AND m.material_type = 'Raw Material'
                AND (m.material_subtype = 'API' OR LOWER(TRIM(IFNULL(m.material_subtype,''))) = 'api')
                ORDER BY m.material_name ASC";
        $result = $conn->query($sql);
        if($result && $result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row = array_map('utf8_encode', $row);
                $eq = isset($row['equivalent_to']) ? $row['equivalent_to'] : '';
                if (is_string($eq) && $eq !== '') {
                    $decoded = json_decode($eq, true);
                    $row['equivalent_to'] = is_array($decoded) ? $decoded : array();
                } elseif (!is_array($eq)) {
                    $row['equivalent_to'] = array();
                }
                $gradeIds = isset($row['grade']) ? trim($row['grade']) : '';
                $row['gradeName'] = '';
                if ($gradeIds !== '' && preg_match('/^[0-9,]+$/', $gradeIds)) {
                    $q = "SELECT GROUP_CONCAT(grade) AS gradeName FROM grade WHERE id IN (".$gradeIds.")";
                    $resQ = $conn->query($q);
                    if ($resQ && $resQ->num_rows > 0) {
                        $prodLatest = $resQ->fetch_assoc();
                        $row['gradeName'] = $prodLatest['gradeName'] ?? '';
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "updateMasterMaterial") {
        
           $input = $_POST;        
          
           $data = json_decode($input["data"], true);
            $plant_id=$_GET["plant_id"];
           $id=$data["id"];
            $target_dir = "../../../upload/product/";
           if(isset($_FILES["structure_file"]["name"])) {
            	$target_file = $target_dir.$plant_id.$id."_".basename($_FILES["structure_file"]["name"]);
            	$structure_file = $plant_id.$id."_".basename($_FILES["structure_file"]["name"]);
        	    move_uploaded_file($_FILES["structure_file"]["tmp_name"], $target_file);
        	   
           }
           if(isset($_FILES["msds_file"]["name"])) {
            	$target_file = $target_dir.$plant_id.$id."_".basename($_FILES["msds_file"]["name"]);
            	$msds_file = $plant_id.$id."_".basename($_FILES["msds_file"]["name"]);
        	    move_uploaded_file($_FILES["msds_file"]["tmp_name"], $target_file);
        	     
           }
           if(isset($_FILES["photo"]["name"])) {
            	$target_file = $target_dir.$plant_id.$id."_".basename($_FILES["photo"]["name"]);
            	$photo = $plant_id.$id."_".basename($_FILES["photo"]["name"]);
        	    move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
        	     
           }
           if(isset($_FILES["shadecard_file"]["name"])) {
            	$target_file = $target_dir.$plant_id.$id."_".basename($_FILES["shadecard_file"]["name"]);
            	$shadecard_file = $plant_id.$id."_".basename($_FILES["shadecard_file"]["name"]);
        	    move_uploaded_file($_FILES["shadecard_file"]["tmp_name"], $target_file);
        	     
           }
           if(isset($_FILES["artwork_file"]["name"])) {
            	$target_file = $target_dir.$plant_id.$id."_".basename($_FILES["artwork_file"]["name"]);
            	$artwork_file = $plant_id.$id."_".basename($_FILES["artwork_file"]["name"]);
        	    move_uploaded_file($_FILES["artwork_file"]["tmp_name"], $target_file);
        	     
           }
            $gradeData = is_array($data["grade"])?implode(",",$data["grade"]):$data["grade"];
        //$sql = "UPDATE material SET material_name='".$input["material_name"]."', grade='".$input["grade"]."', material_nature='".$input["material_nature"]."', order_qty='".$input["order_qty"]."',order_unit='".$input["order_unit"]."',inventory='".$input["inventory"]."',inv_unit='".$input["inv_unit"]."', location='".$input["location"]."' ,client_code='".$input["client_code"]."',storage_condition='".$input["storage_condition"]."',cas_no='".$input["cas_no"]."',category='".$input["category"]."' WHERE id='".$input["id"]."'";
      $sql="UPDATE material SET gst='".$data["gst"]."',hsn='".$data["hsn"]."',order_qty='".$data["order_qty"]."', uom='".$data["uom"]."',alternate_uom='".$_GET["alt_uom"]."', equivalancy_applicable='".$data["equivalancy_applicable"]."', 
equivalancy_factor='".$data["equivalancy_factor"]."', cas_no='".$data["cas_number"]."' ,inventory='".$data["inventory"]."',
order_unit='".$data["order_unit"]."',inv_unit='".$data["inv_unit"]."' ,max_limit='".$data["max_limit"]."' ,
unit='".$data["unit"]."' ,pack_size='".$data["pack_size"]."', density='".$data["density"]."', material_nature='".$data["material_nature"]."'
,material_name='".$data["material_name"]."', grade='".$gradeData."', material_subtype='".$data["material_subtype"]."',
location='".$data["location"]."',molecular_wt='".$data["molecular_wt"]."',molecular_formula='".$data["molecular_formula"]."',storage_condition='".$data["storage_condition"]."',safety='".$data["safety"]."',material_appearance='".$data["material_appearance"]."',
description='".$data["description"]."',packing_requirement='".$data["packing_requirement"]."',lead_time='".$data["lead_time"]."',lead_time='".$data["lead_time"]."' WHERE id='".$data["id"]."'";
        if ($conn->query($sql)) {
            $sql2="UPDATE product_other_information_api  set structure_file_path='".$structure_file."',msds_file_path='".$msds_file."',m_photo='".$photo."' where product_id='".$data["id"]."'";
          $conn->query($sql2);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        // else if ($_GET["type"] == "update_purchase_Material") {
        
       
        //  echo  $sql="update material set hsn='".$input["hsn"]."',gst='".$input["gst"]."',
        //     order_qty='".$input["order_qty"]."', inventory='".$input["inventory"]."' ,max_unit='".$input["unit"]."',
        //     max_limit='".$input["max_limit"]."' WHERE id='".$_GET["id"]."' "; 
            
            
        //       if ($conn->query($sql)) {
        //         echo "{\"status\":\"success\"}";
        //     } else {
        //         echo "{\"status\":\"".$conn->error."\"}";
        //     }
        // }
        else if ($_GET["type"] == "update_gst") {
        
            $sql="update material set gst='".$_GET["gst"]."'
            WHERE id='".$_GET["id"]."' ";  
            
            
              if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        }
        else if ($_GET["type"] == "update_packing_Material101") {
        
             $sql="update material set hsn='".$input["hsn"]."',gst='".$input["gst"]."',
            order_qty='".$input["order_qty"]."', inventory='".$input["inventory"]."' ,max_unit='".$input["unit"]."',
            max_limit='".$input["max_limit"]."' WHERE id='".$_GET["id"]."' ";  
              if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        }
        else if ($_GET["type"] == "update_purchase_Material") {
        
               $sql="update material set hsn='".$input["hsn"]."',gst='".$input["gst"]."',
            order_qty='".$input["order_qty"]."', inventory='".$input["inventory"]."' ,max_unit='".$input["unit"]."',
            max_limit='".$input["max_limit"]."' WHERE id='".$_GET["id"]."' ";  
              if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        }
        else if ($_GET["type"] == "updateMaterialForApproval") {
            header('Content-Type: application/json; charset=utf-8');
            try {
                $data = $_POST;
                $id = isset($_GET["id"]) ? intval($_GET["id"]) : (isset($data["id"]) ? intval($data["id"]) : 0);
                $plant_id = mysqli_real_escape_string($conn, $_GET["plant_id"] ?? '');

                if ($id <= 0 || $plant_id === '') {
                    echo json_encode(array("status" => "invalid", "message" => "Invalid material id or plant."));
                    exit;
                }

                $checkSql = "SELECT id, status, material_type, material_code, material_name FROM material
                    WHERE id='".$id."' AND plant_id='".$plant_id."' LIMIT 1";
                $checkRes = $conn->query($checkSql);
                if (!$checkRes || $checkRes->num_rows === 0) {
                    echo json_encode(array("status" => "not_found", "message" => "Material record not found."));
                    exit;
                }
                $existing = $checkRes->fetch_assoc();
                if (($existing["status"] ?? '') === 'Absolute') {
                    echo json_encode(array("status" => "invalid", "message" => "Cannot update obsolete material."));
                    exit;
                }

                $material_name_raw = isset($data["material_name"]) ? trim($data["material_name"]) : '';
                if ($material_name_raw === '') {
                    echo json_encode(array("status" => "invalid", "message" => "Material name is required."));
                    exit;
                }

                $material_type_esc = mysqli_real_escape_string($conn, $existing["material_type"] ?? '');
                $nameEsc = mysqli_real_escape_string($conn, $material_name_raw);
                $typeClause = $material_type_esc !== '' ? " AND material_type='".$material_type_esc."'" : '';
                $dupSql = "SELECT id FROM material WHERE plant_id='".$plant_id."'".$typeClause."
                    AND LOWER(TRIM(material_name))=LOWER('".$nameEsc."')
                    AND id != '".$id."'
                    AND material_code IS NOT NULL AND material_code != '' AND material_code != 'NA' LIMIT 1";
                $dupRes = $conn->query($dupSql);
                if ($dupRes && $dupRes->num_rows > 0) {
                    echo json_encode(array("status" => "duplicate", "message" => "Material name already exists for this plant."));
                    exit;
                }

                $equivalent_to = '';
                if (isset($data["equivalent_to"])) {
                    $eq = $data["equivalent_to"];
                    $equivalent_to = is_string($eq) ? $eq : json_encode($eq);
                }

                $esc = function ($key, $default = '') use ($conn, $data) {
                    return mysqli_real_escape_string($conn, isset($data[$key]) ? trim((string)$data[$key]) : $default);
                };

                $sets = array(
                    "material_subtype='".$esc("material_subtype")."'",
                    "material_nature='".$esc("material_nature")."'",
                    "category='".$esc("category")."'",
                    "material_name='".$nameEsc."'",
                    "material_name_report='".$esc("material_name_report")."'",
                    "grade='".$esc("grade")."'",
                    "uom='".$esc("uom")."'",
                    "unit='".$esc("uom")."'",
                    "alternate_uom='".$esc("alternate_uom")."'",
                    "color_index='".$esc("color_index")."'",
                    "dimension='".$esc("dimension")."'",
                    "madeOf='".$esc("madeOf")."'",
                    "tax_type='".$esc("tax_type")."'",
                    "gst='".$esc("gst")."'",
                    "equivalancy_applicable='".$esc("equivalancy_applicable")."'",
                    "assayCalculation='".$esc("assayCalculation")."'",
                    "sub_type='".$esc("sub_type")."'",
                    "artwork='".$esc("artwork")."'",
                    "product_n='".$esc("product_n")."'",
                    "indent_type='".$esc("indent_type")."'",
                    "density='".$esc("density")."'",
                    "storage_condition='".$esc("storage_condition")."'",
                    "inventory='".$esc("inventory")."'",
                    "maxInventory='".$esc("maxInventory")."'",
                    "specificGravity='".$esc("specificGravity")."'",
                    "texture='".$esc("texture")."'",
                    "moq='".$esc("moq")."'",
                    "plasticType='".$esc("plasticType")."'",
                    "inventoryValueMax='".$esc("inventoryValueMax")."'",
                    "premixItem='".$esc("premixItem")."'",
                    "lead_time='".$esc("lead_time")."'",
                    "description='".$esc("description")."'",
                    "safety='".$esc("safety")."'",
                    "equivalent_to='".mysqli_real_escape_string($conn, $equivalent_to)."'",
                    "status='Pending'",
                    "approve_by=''",
                    "approve_date=NULL",
                    "approveAcc_by=''",
                    "approveAcc_date=NULL",
                    "approveQa_by=''",
                    "approveQa_date=NULL"
                );

                if (columnExists($conn, 'material', 'retest_month')) {
                    $sets[] = "retest_month='".$esc("retest_month")."'";
                }
                if (columnExists($conn, 'material', 'modified_by')) {
                    $sets[] = "modified_by='".mysqli_real_escape_string($conn, $_GET["emp_id"] ?? '')."'";
                }
                if (columnExists($conn, 'material', 'modified_date')) {
                    $sets[] = "modified_date='".$entry_date."'";
                }

                $sql = "UPDATE material SET ".implode(", ", $sets)." WHERE id='".$id."' AND plant_id='".$plant_id."'";
                if ($conn->query($sql)) {
                    echo json_encode(array("status" => "success", "message" => "Material updated and sent for approval."));
                } else {
                    echo json_encode(array("status" => "error", "message" => $conn->error));
                }
            } catch (\Throwable $e) {
                echo json_encode(array("status" => "error", "message" => $e->getMessage()));
            }
        }
        else if ($_GET["type"] == "updateMaterial") {
              $input = $_POST;        
          
           $data = json_decode($input["data"], true);
            $plant_id=$_GET["plant_id"];
           $id=$data["id"];
            $target_dir = "../../../upload/product/";
           if(isset($_FILES["structure_file"]["name"])) {
            	$target_file = $target_dir.$plant_id.$id."_".basename($_FILES["structure_file"]["name"]);
            	$structure_file = $plant_id.$id."_".basename($_FILES["structure_file"]["name"]);
        	    move_uploaded_file($_FILES["structure_file"]["tmp_name"], $target_file);
        	   
           }
           if(isset($_FILES["msds_file"]["name"])) {
            	$target_file = $target_dir.$plant_id.$id."_".basename($_FILES["msds_file"]["name"]);
            	$msds_file = $plant_id.$id."_".basename($_FILES["msds_file"]["name"]);
        	    move_uploaded_file($_FILES["msds_file"]["tmp_name"], $target_file);
        	     
           }
           if(isset($_FILES["photo"]["name"])) {
            	$target_file = $target_dir.$plant_id.$id."_".basename($_FILES["photo"]["name"]);
            	$photo = $plant_id.$id."_".basename($_FILES["photo"]["name"]);
        	    move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
        	     
           }
           if(isset($_FILES["shadecard_file"]["name"])) {
            	$target_file = $target_dir.$plant_id.$id."_".basename($_FILES["shadecard_file"]["name"]);
            	$shadecard_file = $plant_id.$id."_".basename($_FILES["shadecard_file"]["name"]);
        	    move_uploaded_file($_FILES["shadecard_file"]["tmp_name"], $target_file);
        	     
           }
           if(isset($_FILES["artwork_file"]["name"])) {
            	$target_file = $target_dir.$plant_id.$id."_".basename($_FILES["artwork_file"]["name"]);
            	$artwork_file = $plant_id.$id."_".basename($_FILES["artwork_file"]["name"]);
        	    move_uploaded_file($_FILES["artwork_file"]["tmp_name"], $target_file);
        	     
           }
            $gradeData = is_array($data["grade"])?implode(",",$data["grade"]):$data["grade"];
        //$sql = "UPDATE material SET material_name='".$input["material_name"]."', grade='".$input["grade"]."', material_nature='".$input["material_nature"]."', order_qty='".$input["order_qty"]."',order_unit='".$input["order_unit"]."',inventory='".$input["inventory"]."',inv_unit='".$input["inv_unit"]."', location='".$input["location"]."' ,client_code='".$input["client_code"]."',storage_condition='".$input["storage_condition"]."',cas_no='".$input["cas_no"]."',category='".$input["category"]."' WHERE id='".$input["id"]."'";
       $sql="UPDATE material SET material_subtype='".$data["material_subtype"]."',material_name='".$data["material_name"]."',grade='".$data["grade"]."'
       ,uom='".$data["uom"]."',alternate_uom='".$data["alt_uom"]."',sub_type='".$data["sub_type"]."'
       ,storage_condition='".$data["storage_condition"]."' ,description='".$data["description"]."' ,lead_time='".$data["lead_time"]."'
       ,client_code='".$data["client_code"]."'
       ,printing_type='".$data["printing_type"]."'
       ,product_n='".$data["product_n"]."' WHERE id='".$data["id"]."'";
        if ($conn->query($sql)) {
            
            $sql2="update product_other_information_api set artwork='".$input["artworkList"]."',shadecard='".$input["shadeList"]."',artwork_no='".$data["artwork_no"]."',artwork_file='".$artwork_file."',shadecard_file='".$shadecard_file."',m_photo='".$photo."' WHERE product_id='".$data["id"]."'";
            $conn->query($sql2);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "del_type") {
          $sql = "DELETE FROM material_type  WHERE id='".$_GET["id"]."'";
       //echo $sql;
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "ChangeMaterialStatus") {
        $status = trim((string)($_GET["status"] ?? ''));
        $id = $conn->real_escape_string((string)($_GET["id"] ?? ''));
        $plantId = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));
        if ($id === '' || $status === '') {
            echo json_encode(array("status" => "id and status are required"));
            exit;
        }
        // Normalize inactive / active values used across legacy data
        if (strcasecmp($status, 'In-Active') === 0 || strcasecmp($status, 'In Active') === 0 || strcasecmp($status, 'Inactive') === 0) {
            $status = 'In-Active';
        }
        if (strcasecmp($status, 'Approved') === 0 || strcasecmp($status, 'approve') === 0) {
            $status = 'Approved';
        }
        $statusEsc = $conn->real_escape_string($status);
        $sql = "UPDATE material SET status = '".$statusEsc."'";
        if ($status === 'In-Active') {
            if (!empty($_GET['in_active_date']) && columnExists($conn, 'material', 'in_active_date')) {
                $sql .= ", in_active_date = '".$conn->real_escape_string($_GET['in_active_date'])."'";
            }
            if (!empty($_GET['inactivated_by_id']) && columnExists($conn, 'material', 'inactivated_by_id')) {
                $sql .= ", inactivated_by_id = '".$conn->real_escape_string($_GET['inactivated_by_id'])."'";
            }
            if (!empty($_GET['inactivated_by_name']) && columnExists($conn, 'material', 'inactivated_by_name')) {
                $sql .= ", inactivated_by_name = '".$conn->real_escape_string($_GET['inactivated_by_name'])."'";
            }
        }
        $sql .= " WHERE id = '".$id."'";
        if ($plantId !== '') {
            $sql .= " AND plant_id = '".$plantId."'";
        }
        if ($conn->query($sql)) {
            if ($conn->affected_rows > 0) {
                echo json_encode(array("status" => "success"));
            } else {
                echo json_encode(array("status" => "no row updated — check material id and plant"));
            }
        } else {
            echo json_encode(array("status" => $conn->error));
        }
    }
    else if ($_GET["type"] == "deleteMaterial") {
        $sql = "DELETE FROM material  WHERE id='".$_GET["id"]."'";
       //echo $sql;
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getMaterialInfoById") {
    
        
         $sql = "select a.*,b.category , b.grade, b.retest_month,b.material_name,c.grade as gradeName from product_other_information_api a  left join material b on a.product_id = b.id left join grade c on c.id = b.grade WHERE a.product_id='".$_GET["id"]."'";
       
       $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            

                // print_r($row);exit;
            }
           
           $sql= 'SELECT *  FROM    grade where id in ('.  $output[0]["grade"].')';
          $result = $conn->query($sql);
         $gradeData = []; 
          if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                 $gradeData[] = $row;
                }
            }

         $output[0]["gradeData"] = $gradeData ;
        }
        
        
        
        echo json_encode($output);
    }
    else if ($_GET["type"] == "uploadMaterialRecords") {
        $faileds = array();
        for ($i = 0; $i < count($input); $i++) {
            $data = $input[$i];
            $sql = "SELECT id FROM material WHERE material_name='".$data["Material Name"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows == 0) {
                $sql = "INSERT INTO material (material_name, material_type, material_subtype, grade, order_qty, order_unit, inventory) VALUES ('".$data["Material Name"]."', 'Raw Material', '".$data["Material Type"]."', '".$data["GRADE"]."', '".$data["MIN ORDER QTY"]."', '".$data["UNIT"]."', '".$data["MIN.INVENTORY LEVEL"]."')";
                if ($conn->query($sql) == FALSE) {
                    $data["REMARK"] = $conn->error;
                }
            } else {
                $sql = "UPDATE material SET material_name='".$data["Material Name"]."', material_subtype='".$data["Material Type"]."', grade='".$data["GRADE"]."',order_qty='".$data["MIN ORDER QTY"]."', order_unit='".$data["UNIT"]."', inventory='".$data["MIN.INVENTORY LEVEL"]."', '";
                if ($conn->query($sql) == FALSE) {
                    $data["REMARK"] = $conn->error;
                }
            }
        }
        $results = array();
        $results["status"] = "success";
        $results["results"] = $faileds;
        echo json_encode($results);
    }
    else if ($_GET["type"] == "save_vendor_material") {
        $flag = true;
        $inserted = 0;
        $array = is_array($input) ? $input : array();
        if (count($array) === 0) {
            echo json_encode(array("status" => "error", "message" => "No materials to save"));
            exit;
        }

        foreach ($array as $values){
            if (!is_array($values) || empty($values["material_code"]) || empty($values["vendor_no"])) {
                $flag = false;
                continue;
            }
            $matCode = mysqli_real_escape_string($conn, $values["material_code"]);
            $vendorNo = mysqli_real_escape_string($conn, $values["vendor_no"]);
            $matType = mysqli_real_escape_string($conn, isset($values["material_type"]) ? $values["material_type"] : '');
            $matSubtype = mysqli_real_escape_string($conn, isset($values["material_subtype"]) ? $values["material_subtype"] : '');
            $clientGrp = mysqli_real_escape_string($conn, isset($values["clientGrpCode"]) ? $values["clientGrpCode"] : '');
            $clientSubGrp = mysqli_real_escape_string($conn, isset($values["clientSubGrpCode"]) ? $values["clientSubGrpCode"] : '');
            $leadTime = mysqli_real_escape_string($conn, isset($values["lead_time"]) ? $values["lead_time"] : '0');

            $sql= "SELECT id FROM mst_vendor_materials where plant_id='".mysqli_real_escape_string($conn, $_GET["plant_id"])."' AND material_code = '".$matCode."' AND supplier_code = '".$vendorNo."' LIMIT 1";
            $result = $conn->query($sql);
            if($result && $result->num_rows > 0){
                continue;
            }

            $sql1 = "INSERT INTO mst_vendor_materials (`plant_id`, `supplier_code`, `material_code`,`material_type`, `material_subtype`,`clientGrpCode`,`clientSubGrpCode`, `lead_time`, `entry_by`, `entry_date`) VALUES (
            '".mysqli_real_escape_string($conn, $_GET["plant_id"])."', '".$vendorNo."','".$matCode."', '".$matType."', '".$matSubtype."', '".$clientGrp."', '".$clientSubGrp."',
            '".$leadTime."', '".mysqli_real_escape_string($conn, $_GET["emp_id"])."', '$entry_date')";

            if($conn->query($sql1)) {
                $inserted++;
            } else {
                $flag = false;
            }
        }

    	if($flag){
    		echo json_encode(array("status" => "success", "inserted" => $inserted));
    	} else {
    		echo json_encode(array("status" => "error", "message" => $conn->error));
    	}
    	
    }
    else if ($_GET["type"] == "get_suppliers_by_material_code") {
         $output = Array();
           // $sql = "SELECT * FROM vendor WHERE vendor_type='Supplier' AND material_type='Raw Material'  and plant_id= '".$_GET["plant_id"]."'";
            $sql = "SELECT * FROM vendor WHERE vendor_no 
            in(select  supplier_code from mst_vendor_materials where material_code = '".$_GET["material_code"]."' 
            and plant_id= '".$_GET["plant_id"]."') 
            and plant_id= '".$_GET["plant_id"]."'";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    else if ($_GET["type"] == "get_supplier_by_materials_log") {
         $output = Array();
 
            $sql = "SELECT a.supplier_code as vendor_no, COUNT(supplier_code) as no_of_products, a.entry_by,a.entry_date, b.vendor_type,b.vendor_name FROM mst_vendor_materials a  
            LEFT JOIN vendor b ON a.supplier_code = b.vendor_no  WHERE  a.plant_id = '".$_GET["plant_id"]."' GROUP BY a.supplier_code  ";

            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    else if ($_GET["type"] == "get_vendor_map_search") {
         $output = Array();
           // $sql = "SELECT * FROM vendor WHERE vendor_type='Supplier' AND material_type='Raw Material'  and plant_id= '".$_GET["plant_id"]."'";
               $sql = "SELECT *FROM ( SELECT a.vendor_no, a.vendor_name, a.vendor_type, COUNT(b.material_code) AS no_of_products
                  FROM mst_vendor_materials b JOIN vendor a ON a.plant_id = b.plant_id AND a.vendor_no = b.supplier_code
                  WHERE a.plant_id = '".$_GET["plant_id"]."' AND (a.vendor_name LIKE '%".$_GET["value"]."%' OR a.vendor_no LIKE '%".$_GET["value"]."%'  ) GROUP BY a.vendor_no, a.vendor_name, a.vendor_type
                  UNION ALL
                  SELECT a.vendor_no, a.vendor_name, a.vendor_type, COUNT(b.material_code) AS no_of_products
                  FROM mst_vendor_materials b JOIN vendor a ON a.plant_id = b.plant_id AND a.vendor_no = b.manufacturer_code
                  WHERE a.plant_id = '".$_GET["plant_id"]."'  AND (a.vendor_name LIKE '%".$_GET["value"]."%' OR a.vendor_no LIKE '%".$_GET["value"]."%'  ) GROUP BY a.vendor_no, a.vendor_name, a.vendor_type ) AS a
                   ORDER BY (SELECT MAX(id) FROM mst_vendor_materials WHERE vendor_no = a.vendor_no) DESC";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    
    
    
    
    
    
        else if ($_GET["type"] == "get_supplier_by_materials_log_material") {
        
        
        
        $output = array();

$sql = "SELECT * FROM (SELECT a.vendor_no, a.vendor_name, a.vendor_type, COUNT(b.material_code) AS no_of_products
        FROM mst_vendor_materials b 
        JOIN vendor a ON a.plant_id = b.plant_id AND a.vendor_no = b.supplier_code
        WHERE a.plant_id = '".$_GET["plant_id"]."' 
        GROUP BY a.vendor_no, a.vendor_name, a.vendor_type

        UNION ALL

        SELECT a.vendor_no, a.vendor_name, a.vendor_type, COUNT(b.material_code) AS no_of_products
        FROM mst_vendor_materials b 
        JOIN vendor a ON a.plant_id = b.plant_id AND a.vendor_no = b.manufacturer_code
        WHERE a.plant_id = '".$_GET["plant_id"]."' 
        GROUP BY a.vendor_no, a.vendor_name, a.vendor_type) AS a
        ORDER BY vendor_no DESC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row = array_map('utf8_encode', $row);
        $output1 = array();
        
        $sql2 = "SELECT vm.*, m.material_type, m.material_subtype, m.material_name, vm.material_type AS vmaterial_type
                FROM mst_vendor_materials vm
                LEFT JOIN material m ON m.material_code = vm.material_code
                WHERE vm.plant_id = '".$_GET["plant_id"]."' and m.material_name like '%".$_GET["value"]."%'
                AND (vm.manufacturer_code = '".$row["vendor_no"]."' OR vm.supplier_code = '".$row["vendor_no"]."')";
        
        $result2 = $conn->query($sql2);
        
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $row2 = array_map('utf8_encode', $row2);
                
                if ($row["vmaterial_type"] == 'Equipments') {
                    $sql1 = "SELECT * FROM general_material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row2['material_name'] = $row1['material_name']; 
                            $row2['material_type'] = $row1['material_subtype']; 
                            $row2['material_subtype'] = $row1['material_subtype']; 
                        }
                    }
                } elseif ($row["vmaterial_type"] == 'Service Provider') {
                    $sql1 = "SELECT * FROM service WHERE service_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row2['material_name'] = $row1['service_title']; 
                            $row2['material_type'] = $row['vmaterial_type']; 
                            $row2['material_subtype'] = $row1['service_type']; 
                        }
                    }
                }
                $output1[] = $row2;
            }
             $row["products"] = $output1;
        }
        if($row["products"]!=''){
              $output[] = $row;
        }
      
    }
    
}
echo json_encode($output);

    }

    
    
    
    
    
    
    else if ($_GET["type"] == "get_supplier_by_materials_loga") {
        
        $_GET['filename'] = 'get supplier log'; $_GET['pdftype'] = 'onlyheader'; include("./pdfimp2.php");
        $html="";

        $html.='
        <h2 style="text-align:center">Test Master</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:8%;">Sr No</td>
                    <td style="width:23%;">Vendor Code</td>
                    <td style="width:23%;">Vendor Type</td>
                    <td style="width:23%;">Vendor Name</td>
                    <td style="width:23%;">No of Product</td>
                </tr>
            </thead>
            <tbody>';
            $i=1;
        echo $sql = "select * from (SELECT a.vendor_no,a.vendor_name,a.vendor_type,count(b.material_code) as no_of_products FROM mst_vendor_materials b
            join vendor a on a.plant_id = b.plant_id and a.vendor_no= b.supplier_code 
            Where a.plant_id= '".$_GET["plant_id"]."'
            group by a.vendor_no,a.vendor_name,a.vendor_type
            union ALL
            SELECT a.vendor_no,a.vendor_name,a.vendor_type,count(b.material_code) as no_of_products FROM mst_vendor_materials b
            join vendor a on a.plant_id = b.plant_id and a.vendor_no= b.manufacturer_code
            Where a.plant_id= '".$_GET["plant_id"]."'
            group by a.vendor_no,a.vendor_name,a.vendor_type)a order by vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                $html.='
                <tr >
                    <td style="width: 8%;">'.$i++.'</td>
                    <td style="width: 23%;">'.$row['vendor_no'].'</td>
                    <td style="width: 23%;">'.$row['vendor_type'].'</td>
                    <td style="width: 23%;">'.$row['vendor_name'].'</td>
                    <td style="width: 23%;">'.$row['no_of_products'].'</td>
                     
                </tr>';
                }
            }
        $html.="</tbody></table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('get supplier log.pdf', 'I');
    }
    else if ($_GET["type"] == "get_materials_by_supplier") {
         $output = Array();
         $vendorNo = isset($_GET["vendor_no"]) ? $conn->real_escape_string($_GET["vendor_no"]) : '';
                     
             $sql = "SELECT vm.*,
                COALESCE(mat.material_name, om.material_name, gm.material_name, svc.service_title, '') AS material_name,
                COALESCE(mat.material_type, om.material_type, gm.material_subtype, vm.material_type) AS material_type,
                COALESCE(mat.material_subtype, om.material_subtype, gm.material_subtype, vm.material_subtype) AS material_subtype,
                (select c.LglNm from client c where c.client_code = vm.clientGrpCode LIMIT 1) as clientGrpCodeName,
                (select c.LglNm from client c where c.client_code = vm.clientSubGrpCode LIMIT 1) as clientSubGrpCodeName
                from mst_vendor_materials vm
                LEFT JOIN material mat ON mat.material_code = vm.material_code
                LEFT JOIN others_material om ON om.material_code = vm.material_code AND om.plant_id = vm.plant_id
                LEFT JOIN general_material gm ON gm.material_code = vm.material_code
                LEFT JOIN service svc ON svc.service_code = vm.material_code
                Where vm.plant_id= '".$_GET["plant_id"]."' and  (vm.manufacturer_code='".$vendorNo."' or vm.supplier_code='".$vendorNo."')";
                
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $output[] = $row;
                }
            }
            echo json_encode($output);
    }
      else if ($_GET["type"] == "get_materials_by_supplier1") {
         $output = Array();
                        echo    $sql = "SELECT * FROM vendor WHERE status='approved' AND plant_id= '".$_GET["plant_id"]."' ";
     
        //     $sql = "SELECT vm.* , m.material_type , m.material_subtype ,m.material_name,vm.material_type as vmaterial_type   from mst_vendor_materials   vm 
        // left join my_view m on m.material_code=vm.material_code 
        //     Where vm.plant_id= '".$_GET["plant_id"]."' and  (vm.manufacturer_code='".$_GET["vendor_no"]."' or vm.supplier_code='".$_GET["vendor_no"]."')";         
                    
                    
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            // echo json_encode($output);
            $output = utf8ize($output);
echo json_encode($output);
    }
    else if($_GET["type"] == "getMaterialsGrade") {
          
          print_r($_GET);
          
          $sql= 'SELECT *  FROM    grade where id in ('.  $_GET['grade'].')';
          $result = $conn->query($sql);
          $output = [];
          if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                 $output[] = $row;
                }
            }
          echo json_encode($output);
     }
    else if ($_GET["type"] == "save_stock") {
        $sql = "INSERT INTO stock (plant_id,total_qty,batch_no,loose_qty,total_pack,unit,pack_size,date,chemical_no,chemical_name) VALUES 
        ('".$_GET["plant_id"]."','".$input["total_qty"]."','".$input["batch_no"]."','".$input["loose_qty"]."','".$input["total_pack"]."','".$input["unit"]."','".$input["pack_size"]."','".$input["date"]."' ,'".$_GET["chemical_no"]."','".$_GET["chemical_name"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    
     else if($_GET["type"]=="saveLocation"){ 


        $sql = "INSERT INTO location (plant_id,location)
        VALUES  ('".$_GET['plant_id']."','".$input["location"]."')";
        
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    }
    else if($_GET["type"]=="gate_stock"){
        $output = Array();
        $chemNo = $conn->real_escape_string(trim((string)($_GET["chemical_no"] ?? '')));
        $plantId = $conn->real_escape_string(trim((string)($_GET["plant_id"] ?? '')));
        if ($chemNo !== '') {
            $where = "chemical_no='".$chemNo."'";
            if ($plantId !== '') {
                $where .= " AND (plant_id='".$plantId."' OR IFNULL(plant_id,'')='')";
            }
            $sql = "SELECT * FROM stock WHERE ".$where." ORDER BY id DESC";
            $result = @$conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
    	echo json_encode($output);
    	
    }
    else if($_GET["type"]=="gate_Unit"){
        $output = Array();
           $sql = "SELECT * FROM unit "; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    }
    else if($_GET["type"]=="getLocation"){
        $output = Array();
        $sql = "SELECT * FROM location"; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    }
    else if($_GET["type"]=="getLocation"){
        $output = Array();
        $sql = "SELECT * FROM location"; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    }
    else if($_GET["type"] == "packingmateriallog") {
 
        $output = Array();
    //     $plant = $_GET["plant_id"];
    //     if($plant==0){
    //     $sql = "SELECT m.*,c.LglNm FROM material m LEFT JOIN client c ON m.client_code=c.client_code 
    //     WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.status='approve' ORDER BY m.id DESC";
    //     //$sql = "SELECT m.*,g.material_name,g.material_sub_type_id,g.general_material_type,g.material_subtype FROM material m LEFT JOIN general_material g ON m.client_code=c.client_code 
    //   // WHERE plant_id='".$_GET["plant_id"]."' and general_material_type='".$_GET["material_type"]."'  order by 1 desc";
            
    //   }else{
    //   echo   $sql = "SELECT m.* FROM material m  WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.status='approve'  ORDER BY m.id DESC"; 
        
    //     }
    $sql="SELECT * FROM `material` WHERE material_type='Packing Material' order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row = array_map('utf8_encode', $row);

              //  print_r($row);
          
          
             $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
          
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
}

$conn->close();
?>