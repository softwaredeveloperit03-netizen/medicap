<?php



    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    require_once __DIR__ . '/standard_chemicals_seed.php';
    require_once __DIR__ . '/seed_om_helper.php';
    require_once __DIR__ . '/../schema_tables.php';
    
    
//     ini_set('display_errors', 1);
// error_reporting(E_ALL);

    
    
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
        $currentUrl =$_GET["description"];
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
 $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    function decodeChemicalJsonField($value) {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            return array();
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : array();
    }

    function normalizeChemicalMakeList($make) {
        $out = array();
        if (!is_array($make)) {
            return $out;
        }
        foreach ($make as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = array('make' => trim($item));
            } elseif (is_array($item)) {
                $label = trim((string)($item['make'] ?? $item['name'] ?? $item['make_name'] ?? ''));
                if ($label !== '') {
                    $out[] = array('make' => $label);
                }
            }
        }
        return $out;
    }

    function normalizeChemManufacturerList($rows) {
        $out = array();
        if (!is_array($rows)) {
            return $out;
        }
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $vendorNo = trim((string)($row['vendor_no'] ?? $row['mfg_no'] ?? $row['code'] ?? ''));
            $vendorName = trim((string)($row['vendor_name'] ?? $row['mfg_name'] ?? $row['manufacturer_name'] ?? ''));
            if ($vendorNo === '' && $vendorName === '') {
                continue;
            }
            $out[] = array(
                'vendor_no' => $vendorNo,
                'vendor_name' => $vendorName,
            );
        }
        return $out;
    }

    function fetchChemManufacturers($conn, $chemicalId, $plantId) {
        $output1 = array();
        $chemIdEsc = $conn->real_escape_string((string)$chemicalId);
        $plantEsc = $conn->real_escape_string((string)$plantId);
        $sql1 = "SELECT * FROM chem_manufaturer WHERE chemical_id='".$chemIdEsc."'";
        if ($plantEsc !== '') {
            $sql1 .= " AND (plant_id='".$plantEsc."' OR IFNULL(plant_id,'')='')";
        }
        $result1 = @$conn->query($sql1);
        if ($result1 && $result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1[] = $row1;
            }
        }
        return normalizeChemManufacturerList($output1);
    }

    function enrichChemicalRow($conn, $row, $plantId) {
        if (!is_array($row)) {
            return $row;
        }
        $row['make'] = normalizeChemicalMakeList(decodeChemicalJsonField($row['make'] ?? array()));
        $row['pack_size'] = decodeChemicalJsonField($row['pack_size'] ?? array());
        $row['manufaturer'] = fetchChemManufacturers($conn, $row['id'] ?? 0, $plantId);
        return $row;
    }

    function fetchChemicalStockRows($conn, $chemicalNo, $plantId) {
        $output = array();
        $chemNoEsc = $conn->real_escape_string(trim((string)$chemicalNo));
        if ($chemNoEsc === '') {
            return $output;
        }
        $plantEsc = $conn->real_escape_string(trim((string)$plantId));
        $where = "chemical_no='".$chemNoEsc."'";
        if ($plantEsc !== '') {
            $where .= " AND (plant_id='".$plantEsc."' OR IFNULL(plant_id,'')='')";
        }
        $result = @$conn->query("SELECT * FROM stock WHERE ".$where." ORDER BY id DESC");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        return $output;
    }

    if ($_GET["type"] == "saveChemical" || $_GET["type"] == "saveMasterChemical") {
        if (function_exists('medicap_ensure_schema')) {
            medicap_ensure_schema($conn);
        }
        $payload = is_array($input) ? $input : array();
        if (!empty($_POST)) {
            $payload = array_merge($payload, $_POST);
        }
        if (count($payload) === 0) {
            $rawBody = file_get_contents('php://input');
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $plant = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : '');
        $empId = $conn->real_escape_string(isset($_GET["emp_id"]) ? $_GET["emp_id"] : '');
        $userNo = isset($_GET["user_no"]) ? $conn->real_escape_string($_GET["user_no"]) : '';
        $chemicalName = isset($payload["chemical_name"]) ? trim($payload["chemical_name"]) : '';
        if ($chemicalName === '') {
            echo json_encode(array("status" => "error", "message" => "Chemical name is required"));
            exit;
        }
        $nameEsc = $conn->real_escape_string($chemicalName);
        $dup = $conn->query("SELECT id FROM chemical WHERE plant_id='".$plant."' AND chemical_name='".$nameEsc."' LIMIT 1");
        if ($dup && $dup->num_rows > 0) {
            echo json_encode(array("status" => "error", "message" => "Duplicate entry for chemical name"));
            exit;
        }

        $chemCols = array();
        $colRes = $conn->query("SHOW COLUMNS FROM chemical");
        if ($colRes) {
            while ($col = $colRes->fetch_assoc()) {
                $chemCols[$col['Field']] = true;
            }
        }

        $seq = 1;
        $maxSql = $conn->query("SELECT chemical_no FROM chemical WHERE plant_id='".$plant."' AND chemical_no LIKE 'CHM-%' ORDER BY id DESC LIMIT 1");
        if ($maxSql && $maxSql->num_rows > 0) {
            $lastNo = $maxSql->fetch_assoc();
            if (preg_match('/CHM-(\d+)/', (string)$lastNo['chemical_no'], $m)) {
                $seq = intval($m[1]) + 1;
            }
        }
        $chemNo = 'CHM-'.str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        $makeRaw = isset($payload["make"]) ? $payload["make"] : array();
        $makeJson = json_encode(is_array($makeRaw) ? $makeRaw : array());
        $packSizeRaw = isset($payload["packsize_data"]) ? $payload["packsize_data"] : (isset($payload["pack_size"]) ? $payload["pack_size"] : '[]');
        if (is_array($packSizeRaw)) {
            $packSize = json_encode($packSizeRaw);
        } else {
            $packSize = (string)$packSizeRaw;
        }

        $rowData = array(
            'plant_id' => $plant,
            'user_no' => $userNo,
            'chemical_no' => $chemNo,
            'chem_type' => isset($payload["chem_type"]) ? trim($payload["chem_type"]) : 'Chemical',
            'chemical_name' => $chemicalName,
            'molecular_wt' => isset($payload["molecular_wt"]) ? trim((string)$payload["molecular_wt"]) : '',
            'pack_size' => $packSize,
            'cas_name' => isset($payload["cas_name"]) ? trim((string)$payload["cas_name"]) : '',
            'grade' => isset($payload["grade"]) ? trim((string)$payload["grade"]) : 'AR',
            'make' => $makeJson,
            'unit' => isset($payload["unit"]) ? trim((string)$payload["unit"]) : 'g',
            'entry_by' => $empId,
            'entry_date' => $entry_date,
            'status' => 'approve',
            'msds_file' => '',
            'pka' => isset($payload["pka"]) ? trim((string)$payload["pka"]) : '',
            'ph_range' => isset($payload["ph_range"]) ? trim((string)$payload["ph_range"]) : '',
            'base_color' => isset($payload["base_color"]) ? trim((string)$payload["base_color"]) : '',
            'acid_color' => isset($payload["acid_color"]) ? trim((string)$payload["acid_color"]) : '',
            'indicator' => isset($payload["indicator"]) ? trim((string)$payload["indicator"]) : $chemicalName,
            'open_date' => isset($payload["open_date"]) ? trim((string)$payload["open_date"]) : '',
            'start_date' => isset($payload["start_date"]) ? trim((string)$payload["start_date"]) : '',
            'approve_by' => $empId,
            'approve_date' => $entry_date,
        );

        if (isset($_FILES["msdsFile"]["name"]) && $_FILES["msdsFile"]["name"] !== '') {
            $target_dir = "../../../upload/product/msds/";
            $target_file = $target_dir.$plant.$chemicalName."_".basename($_FILES["msdsFile"]["name"]);
            $rowData['msds_file'] = $plant.$chemicalName."_".basename($_FILES["msdsFile"]["name"]);
            move_uploaded_file($_FILES["msdsFile"]["tmp_name"], $target_file);
        }

        $useFields = array();
        $useValues = array();
        foreach ($rowData as $field => $val) {
            if (!isset($chemCols[$field])) {
                continue;
            }
            $useFields[] = $field;
            $useValues[] = "'".$conn->real_escape_string((string)$val)."'";
        }

        if (count($useFields) === 0) {
            echo json_encode(array("status" => "error", "message" => "Chemical table schema not available"));
            exit;
        }

        $sql = "INSERT INTO chemical (".implode(',', $useFields).") VALUES (".implode(',', $useValues).")";
        if ($conn->query($sql)) {
            $chem_id = $conn->insert_id;
            $materials = isset($payload["manufacturer"]) ? $payload["manufacturer"] : array();
            if (is_string($materials)) {
                $materialsArray = json_decode($materials, true);
            } else {
                $materialsArray = is_array($materials) ? $materials : array();
            }
            if (is_array($materialsArray)) {
                foreach ($materialsArray as $values) {
                    if (!is_array($values)) {
                        continue;
                    }
                    $vendorNo = $conn->real_escape_string(isset($values["vendor_no"]) ? $values["vendor_no"] : '');
                    $vendorName = $conn->real_escape_string(isset($values["vendor_name"]) ? $values["vendor_name"] : '');
                    if ($vendorNo === '' && $vendorName === '') {
                        continue;
                    }
                    $sql1 = "INSERT INTO chem_manufaturer(chemical_id,vendor_no,vendor_name,plant_id)
                        VALUES('".$chem_id."','".$vendorNo."','".$vendorName."','".$plant."')";
                    $conn->query($sql1);
                }
            }

            if (function_exists('medicap_others_material_insert')) {
                $gradeEsc = $conn->real_escape_string($rowData['grade']);
                $unitEsc = $conn->real_escape_string($rowData['unit']);
                $casEsc = $conn->real_escape_string($rowData['cas_name']);
                $omCheck = $conn->query("SELECT id FROM others_material WHERE plant_id='".$plant."' AND material_subtype='Chemicals' AND (material_code='".$conn->real_escape_string($chemNo)."' OR material_name='".$nameEsc."') LIMIT 1");
                if (!$omCheck || $omCheck->num_rows === 0) {
                    @medicap_others_material_insert($conn, array(
                        'plant_id' => "'".$plant."'",
                        'material_type' => "'QC Material'",
                        'material_subtype' => "'Chemicals'",
                        'material_code' => "'".$conn->real_escape_string($chemNo)."'",
                        'material_name' => "'".$nameEsc."'",
                        'unit' => "'".$unitEsc."'",
                        'grade' => "'".$gradeEsc."'",
                        'cas_no' => "'".$casEsc."'",
                        'cas_name' => "'".$casEsc."'",
                        'status' => "'Approved'",
                        'entry_by' => "'".$empId."'",
                        'entry_date' => "'".$entry_date."'",
                        'description' => "''",
                        'composition' => "''"
                    ));
                }
            }

            echo json_encode(array("status" => "success", "chemical_no" => $chemNo, "message" => "Record saved"));
        } else {
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
    }
    
    else if($_GET["type"] == "approveChemical") {
        
        
        $sql = "update chemical SET status = 'approve' , remark = '".$input["remark"]."', approve_by = '".$_GET["emp_id"]."', 
        approve_date= '$entry_date'  where id = '".$_GET["id"]."'  ";
       
        
        if ($conn->query($sql)) {
               
               echo "{\"status\":\"success\"}";
               
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    else if($_GET["type"] == "saveAdditionalDetails") {
        
        
            
        $target_dir = "../../../upload/column/";
    
    $a = $_GET["ser_no"];
        $file_name = "";
        if(isset($_FILES["document"]["name"])){
            $target_file = $target_dir."coa_file".$a.basename($_FILES["document"]["name"]);
            $file_name = "coa_file".$a.basename($_FILES["document"]["name"]);
            move_uploaded_file($_FILES["document"]["tmp_name"], $target_file);
        }
        
        
        
        
        
        
         $sql = "update columns_management SET coa_file = '$file_name' , use_before = '".$input["use_before"]."' where material_code = '".$_GET["material_code"]."' AND 
        ser_no = '".$_GET["ser_no"]."' ";
       
        
        if ($conn->query($sql)) {
               
               echo "{\"status\":\"success\"}";
               
               
               
               
               
                      $sql = "update engi_stock SET  add_det = '0',use_before = '".$input["use_before"]."' where id = '".$_GET["id"]."'";
                     $conn->query($sql);
               
               
               
               
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    else if($_GET["type"] == "saveAdditionalDetails1") {
        
         $sql = "update engi_stock SET  use_before = '".$_POST["use_before"]."'  , opening_date = '".$_POST["opening_date"]."' where id = '".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
               
               echo "{\"status\":\"success\"}"; 
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    
    
    
    
    //     if ($conn->query($sql)) {
    //           $chem_id = $conn->insert_id; 
    //                 $json_obj = json_encode($input["manufacturer"]);
    //           $array = json_decode($json_obj, true);
    //              $k=1;
    //             foreach ($array as $values)
    //             {
    //         $sql1="INSERT INTO chem_manufaturer( chemical_id, vendor_no,vendor_name, plant_id)
    //                                 values('".$chem_id."','".$values["vendor_no"]."','".$values["vendor_name"]."','".$_GET["plant_id"]."')";
    //               if ($conn->query($sql1)) {
    //          $status1 = true;
    //     } else {
    //         $status1 = false;
    //     }
                    
    //             }
    //     }
    //      if ($status1) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    
    // }
    else if ($_GET["type"] == "getChemicalsApproval") {
        $output = Array();
 
   
        $sql = "SELECT * FROM chemical WHERE status = 'pending' AND  plant_id='".$_GET["plant_id"]."' ORDER BY id desc";    
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1="select * from  chem_manufaturer where chemical_id='".$row['id']."'";
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
                
                $row["pack_size"] = json_decode($row["pack_size"]);
                $row["manufaturer"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getChemicalDetail") {
        $output = null;
        if (function_exists('medicap_ensure_schema')) {
            medicap_ensure_schema($conn);
        }
        $plant = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));
        $chemNo = $conn->real_escape_string(trim((string)($_GET["chemical_no"] ?? '')));
        $chemId = (int)($_GET["id"] ?? 0);
        if ($chemNo !== '') {
            $sql = "SELECT * FROM chemical WHERE plant_id='".$plant."' AND chemical_no='".$chemNo."' LIMIT 1";
        } elseif ($chemId > 0) {
            $sql = "SELECT * FROM chemical WHERE plant_id='".$plant."' AND id='".$chemId."' LIMIT 1";
        } else {
            echo json_encode(null);
            exit;
        }
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = enrichChemicalRow($conn, $result->fetch_assoc(), $plant);
            $row['stock'] = fetchChemicalStockRows($conn, $row['chemical_no'] ?? '', $plant);
            $output = $row;
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getChemicalsLog") {
        if (function_exists('medicap_ensure_schema')) {
            medicap_ensure_schema($conn);
        }
        $output = Array();
        $plant = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));
        // Chemical Master log reads from chemical table (dashboard expects chemical_no / chemical_name).
        $sql = "SELECT * FROM chemical WHERE plant_id='".$plant."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = enrichChemicalRow($conn, $row, $plant);
            }
        }
        // Fallback for plants that still store chemicals in others_material only.
        if (count($output) === 0) {
            $sqlOm = "SELECT *, material_code AS chemical_no, material_name AS chemical_name, cas_no AS cas_name
                FROM others_material
                WHERE material_subtype IN ('Chemicals','Chemical') AND plant_id='".$plant."'
                ORDER BY id DESC";
            $resultOm = $conn->query($sqlOm);
            if ($resultOm && $resultOm->num_rows > 0) {
                while ($row = $resultOm->fetch_assoc()) {
                    $row['make'] = normalizeChemicalMakeList(decodeChemicalJsonField($row['make'] ?? array()));
                    $row['manufaturer'] = array();
                    $row['pack_size'] = array();
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "seedStandardChemicals") {
        if (function_exists('medicap_ensure_schema')) {
            medicap_ensure_schema($conn);
        }
        $plant = $conn->real_escape_string($_GET["plant_id"]);
        $empId = $conn->real_escape_string($_GET["emp_id"]);
        $userNo = isset($_GET["user_no"]) ? $conn->real_escape_string($_GET["user_no"]) : '';
        $masterUserName = isset($input["masterUserName"]) ? $conn->real_escape_string(trim($input["masterUserName"])) : 'Master User';
        $chemicals = (isset($input["chemicals"]) && is_array($input["chemicals"])) ? $input["chemicals"] : array();
        if (count($chemicals) === 0 && function_exists('medicap_standard_chemicals_seed')) {
            $chemicals = medicap_standard_chemicals_seed();
        }
        $chemCols = array();
        $colRes = $conn->query("SHOW COLUMNS FROM chemical");
        if ($colRes) {
            while ($col = $colRes->fetch_assoc()) {
                $chemCols[$col['Field']] = true;
            }
        }
        $omCols = array();
        $omColRes = $conn->query("SHOW COLUMNS FROM others_material");
        if ($omColRes) {
            while ($col = $omColRes->fetch_assoc()) {
                $omCols[$col['Field']] = true;
            }
        }
        $added = 0;
        $skipped = 0;
        $errors = array();
        $seq = 1;
        $nextId = 1;
        $idRes = $conn->query("SELECT COALESCE(MAX(id),0)+1 AS next_id FROM chemical");
        if ($idRes && ($idRow = $idRes->fetch_assoc())) {
            $nextId = intval($idRow['next_id']);
        }
        $hasAutoInc = false;
        if (isset($chemCols['id'])) {
            $aiRes = $conn->query("SHOW COLUMNS FROM chemical WHERE Field='id' AND Extra LIKE '%auto_increment%'");
            $hasAutoInc = ($aiRes && $aiRes->num_rows > 0);
        }
        $maxSql = $conn->query("SELECT chemical_no FROM chemical WHERE plant_id='".$plant."' AND chemical_no LIKE 'CHM-%' ORDER BY id DESC LIMIT 1");
        if ($maxSql && $maxSql->num_rows > 0) {
            $lastNo = $maxSql->fetch_assoc();
            if (preg_match('/CHM-(\d+)/', $lastNo['chemical_no'], $m)) {
                $seq = intval($m[1]) + 1;
            }
        }
        foreach ($chemicals as $chem) {
            $name = isset($chem["chemical_name"]) ? trim($chem["chemical_name"]) : '';
            if ($name === '') {
                continue;
            }
            $nameEsc = $conn->real_escape_string($name);
            $check = $conn->query("SELECT id FROM chemical WHERE plant_id='".$plant."' AND chemical_name='".$nameEsc."' LIMIT 1");
            if ($check && $check->num_rows > 0) {
                $skipped++;
                continue;
            }
            $mw = $conn->real_escape_string(isset($chem["molecular_wt"]) ? $chem["molecular_wt"] : '');
            $cas = $conn->real_escape_string(isset($chem["cas_name"]) ? $chem["cas_name"] : '');
            $grade = $conn->real_escape_string(isset($chem["grade"]) ? $chem["grade"] : 'AR');
            $unit = $conn->real_escape_string(isset($chem["unit"]) ? $chem["unit"] : 'g');
            $chemType = $conn->real_escape_string(isset($chem["chem_type"]) ? $chem["chem_type"] : 'Chemical');
            $chemNo = 'CHM-'.str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
            $seq++;
            $makeJson = '[]';
            $fields = array('plant_id', 'user_no', 'chemical_no', 'chemical_name', 'molecular_wt', 'pack_size', 'cas_name', 'grade', 'make', 'unit', 'entry_by', 'entry_date', 'status', 'approve_by', 'approve_date');
            $values = array("'".$plant."'", "'".$userNo."'", "'".$chemNo."'", "'".$nameEsc."'", "'".$mw."'", "'[]'", "'".$cas."'", "'".$grade."'", "'".$makeJson."'", "'".$unit."'", "'".$empId."'", "'".$entry_date."'", "'approve'", "'".$empId."'", "'".$entry_date."'");
            if (isset($chemCols['chem_type'])) {
                array_splice($fields, 2, 0, array('chem_type'));
                array_splice($values, 2, 0, array("'".$chemType."'"));
            }
            if (!$hasAutoInc && isset($chemCols['id'])) {
                array_unshift($fields, 'id');
                array_unshift($values, "'".$nextId."'");
                $nextId++;
            }
            $useFields = array();
            $useValues = array();
            for ($fi = 0; $fi < count($fields); $fi++) {
                if (isset($chemCols[$fields[$fi]])) {
                    $useFields[] = $fields[$fi];
                    $useValues[] = $values[$fi];
                }
            }
            $sql = "INSERT INTO chemical (".implode(',', $useFields).") VALUES (".implode(',', $useValues).")";
            if ($conn->query($sql)) {
                $added++;
                // Keep others_material in sync for MOA / stock lookups that still use Chemicals subtype.
                if (isset($omCols['material_subtype']) && function_exists('medicap_others_material_insert')) {
                    $omCheck = $conn->query("SELECT id FROM others_material WHERE plant_id='".$plant."' AND material_subtype='Chemicals' AND (material_code='".$chemNo."' OR material_name='".$nameEsc."') LIMIT 1");
                    if (!$omCheck || $omCheck->num_rows === 0) {
                        @medicap_others_material_insert($conn, array(
                            'plant_id' => "'".$plant."'",
                            'material_type' => "'QC Material'",
                            'material_subtype' => "'Chemicals'",
                            'material_code' => "'".$chemNo."'",
                            'material_name' => "'".$nameEsc."'",
                            'unit' => "'".$unit."'",
                            'grade' => "'".$grade."'",
                            'cas_no' => "'".$cas."'",
                            'cas_name' => "'".$cas."'",
                            'status' => "'Approved'",
                            'entry_by' => "'".$empId."'",
                            'entry_date' => "'".$entry_date."'",
                            'description' => "''",
                            'composition' => "''"
                        ));
                    }
                }
            } else {
                $errors[] = $name.': '.$conn->error;
            }
        }
        echo json_encode(array(
            "status" => "success",
            "added" => $added,
            "skipped" => $skipped,
            "errors" => $errors,
            "masterUserName" => $masterUserName
        ));
    }
    else if ($_GET["type"] == "getReagentsLog") {
        $output = Array();
        $plant = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));

        $sql = "SELECT * FROM others_material WHERE material_subtype='Reagents' AND plant_id='".$plant."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        // Fallback: QC reagent master is often in indicator (Reagents module).
        if (count($output) === 0) {
            $sqlInd = "SELECT id, indicator_no AS material_code, indicator AS material_name, plant_id
                FROM indicator WHERE plant_id='".$plant."' ORDER BY indicator ASC";
            $resultInd = $conn->query($sqlInd);
            if ($resultInd && $resultInd->num_rows > 0) {
                while ($row = $resultInd->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "searcheqp") {
        $output = Array();
        $plant = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));
        $value = $conn->real_escape_string((string)($_GET["value"] ?? ''));
        $sql = "SELECT * FROM chemical WHERE plant_id='".$plant."'
            AND ( grade LIKE '%".$value."%' OR chemical_name LIKE '%".$value."%' OR chemical_no LIKE '%".$value."%' OR cas_name LIKE '%".$value."%' )
            ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM chem_manufaturer WHERE chemical_id='".$row['id']."'";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                if (isset($row["make"]) && is_string($row["make"])) {
                    $decodedMake = json_decode($row["make"], true);
                    if (is_array($decodedMake)) {
                        $row["make"] = $decodedMake;
                    }
                }
                $row["manufaturer"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "saveDirectChallan") {
        $materials = $input["materials"];
        for ($i = 0; $i < count($materials); $i++) {
            $material = $materials[$i];
            $material["status"] = "pending";
            $materials[$i] = $material;
        }
        $sql = "INSERT INTO challan (user_no, material_type, challan_no, challan_date, vendor_no, tax_invoice, transport, entry_by, entry_date, gross_total, gst_total, net_total, po_no, po_date) VALUES ('".$_GET["user_no"]."', 'Chemicals','".$input["challan_no"]."', '".$input["challan_date"]."', '".$input["vendor_no"]."', '".$input["tax_invoice"]."', '".$input["transport"]."', '".$_GET["emp_id"]."', '$entry_date', '".$input["gross_total"]."', '".$input["gst_total"]."', '".$input["net_total"]."', '".$input["po_no"]."', '".$input["po_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $materials = $input["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO challan_materials (user_no, challan_no, material_type, material_code, qty, unit, rate, gst, required_for, gross_total, gst_total, net_total) VALUES ('".$_GET["user_no"]."','".$input["challan_no"]."', 'Chemicals', '".$material["chemical_no"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["rate"]."', '".$material["gst"]."', 'Own', '".$material["gross_total"]."','".$material["gst_total"]."', '".$material["net_total"]."')";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getChallansLog") {
        $output = Array();
        $sql = "SELECT c.*, v.vendor_name, v.email, v.gst_no FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.material_type='Chemicals' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY c.id ORDER BY c.id DESC ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    		    $output1 = array();
                $sql1 = "SELECT p.*, m.chemical_name, m.molecular_wt, m.grade, p.material_code as chemical_no FROM challan_materials p LEFT JOIN chemical m ON p.material_code=m.chemical_no WHERE p.user_no='".$_GET["user_no"]."' AND p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["received_rate"] = $row1["quotation_amt"];
                        $row1["diff"] = 0;
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingReceivings") {
        $output = Array();
         $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type, 
         m.chemical_name, m.molecular_wt,c.material_code as chemical_no, m.grade FROM challan_materials c LEFT 
         JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN chemical m ON c.material_code=m.chemical_no 
         LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='pending' AND c.material_type='Chemicals' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"]=="receiveMaterial") {
        $flag = 0;
        $file = "";
        $dev_no = "";

        $dedusting = array();
        if ($_POST["dedustingmaterial"] == "Yes") {
            $dedusting = $_POST["dedusting"];
        }

        if ($_POST["coa_received"] == 'Yes') {
            if (isset($_FILES["coa"])) {
                $rand_no = date("YmdHis", $timestamp);
                $file = "../upload/coa/".$rand_no.basename($_FILES["coa"]["name"]).".pdf";
                move_uploaded_file($_FILES["coa"]["tmp_name"], $file);
                $file = $rand_no.basename($_FILES["coa"]["name"]).".pdf";
            } else {
                $flag = 1;
            }
        } else {
            $sql = "SELECT IFNULL(MAX(i_no), 0) as  i_no FROM deviation";
            $i_no = 0;
            $invoice_no = "";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $i_no = $row["i_no"];
                    break;
                }
            }
            $i_no++;
            $num = strlen($i_no);
            if($num == '1'){
                $dev_no = 'DEV-00'.$i_no;
            }else if($num == '2'){
                $dev_no = 'DEV-0'.$i_no;
            }else{
                $dev_no = 'DEV-'.$i_no;
            }
            $mfg_date = $_POST["mfg_date"];
            $expiry_date = $_POST["exp_date"];
    
            $deviation = json_decode($_POST["deviation"]);
            $product = Array();
            $product["product_code"] = $_POST["material_code"];
            $product["batch_no"] = $_POST["batch_no"];
            $product["mfg_date"] = $_POST["mfg_date"];
            $product["exp_date"] = $_POST["exp_date"];
    
            $sql = "INSERT INTO deviation (user_no, dev_no,i_no,department,related_to,category,type,deviation_date,justification,cause,description,entry_by,entry_date, product_details) VALUES ('".$_GET["user_no"]."','$dev_no','$i_no','".$_GET["department"]."','".$deviation->related_to."','".$deviation->deviation_category."', '".$deviation->type."', '".$entry_date."', '".$deviation->justification."','".$deviation->cause."','".$deviation->description."','".$_GET["emp_id"]."','$entry_date', '".json_encode($product)."')";
            if ($conn->query($sql)) {
                $departments = $deviation->departments;
                if(count($departments) > 0){
                    for ($i = 0; $i < count($departments); $i++) {
                        $temp = $departments[$i];
                        $sql = "INSERT INTO deviation_comments (dev_no,department) VALUES ('$dev_no','$temp->name')";
                        $conn->query($sql);
                    }
                }
            }
        }
    
        if($flag==0){
            $damange = "pending";
            $temp = Array();
            $temp["pack_size"] = $_POST["pack_size"];
            if ($_POST["isdamagecontainer"] == "Yes") {
                $temp["outer_damage"] = $_POST["outer_damage"];
                $temp["inner_damage"] = $_POST["inner_damage"];
                $temp["damage_status"] = "pending";
            } else {
                $damange = "no";
            }
            $temp["isdamagecontainer"] = $_POST["isdamagecontainer"];
            $temp["damange_remark"] = "";
            $temp["packing_condition"] = $_POST["packing_condition"];
            $temp["outer_packing"] = $_POST["outer_packing"];
            $temp["container_type"] = $_POST["container_type"];
            $temp["container_subtype"] = $_POST["container_subtype"];
            $temp["vehicle_condition"] = $_POST["vehicle_condition"];
            $temp["coa_received"] = $_POST["coa_received"];
            if ($_POST["coa_received"] == 'No') {
                $temp["deviation_no"] = $dev_no;
            } else {
                $temp["coa_file"] = $file;
            }
            $temp["received_by"] = $_GET["emp_id"];
            $temp["received_date"] = $entry_date;
            $sql = "UPDATE challan_materials SET batches='".$_POST["batches"]."', manufacturer='".$_POST["manufacturer"]."', cleaning_type='".$_POST["cleaning_type"]."', received_qty='".$_POST["received_qty"]."', containers='".$_POST["containerTotal"]."', receiving_details='".json_encode($temp)."', coa_received='".$_POST["coa_received"]."', status='inprocess', receiving='inprocess', damage='".$damange."', dedusting='".$_POST["dedustingmaterial"]."', dedusting_details='".$dedusting."', receiving='approve' WHERE id='".$_GET["id"]."'";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"failed\",\"\error\":\"".$conn->error."\"}";
            }
        } else{
            echo "{\"status\":\"failed\",\"reason\":\"Upload COA\"}"; 
        }
    }else if ($_GET["type"] == "getReceivingLog") {
        $output = Array();
           $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.chemical_name, m.molecular_wt,c.material_code as chemical_no, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN chemical m ON c.material_code=m.chemical_no LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.receiving !='pending' AND c.material_type='Chemicals' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingWeighingMaterials") {
        $output = Array();
        // $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND m.material_type='Raw Material' AND receiving='approve' AND weighing='pending' GROUP BY c.id";
         $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.chemical_name, m.molecular_wt,c.material_code as chemical_no, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN chemical m ON c.material_code=m.chemical_no LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND c.material_type='Chemicals' AND receiving='approve'AND weighing='pending' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $damage = 0;
                if ($row["damage"] == "approve") {
                    $row["damage_details"] = json_decode($row["damage_details"]);

                    $damage_details = $row["damage_details"];
                    $damange_containers = $damage_details->containers;
                    $damage = +$damage_details->total_damage;
                    for ($i = 0; $i < count($damange_containers); $i++) {
                        $container = $damange_containers[$i];
                        $container->gross_wt = 0;
                        $container->tare_wt = 0;
                        $container->net_wt = 0;
                        $container->weight_by = "";
                        $container->check_by = "";
                        $damange_containers[$i]  = $container;
                    }
                    $row["damage_containers"] = $damange_containers;
                }else{
                    $row["damage_containers"] = [];
                }
                $output1 = Array();
                $containers = +$row["containers"] - $damage;
                for ($i = 1; $i <= $containers; $i++) {
                    $temp = Array();
                    $temp["container_no"] = $i;
                    $temp["gross_wt"] = 0;
                    $temp["tare_wt"] = 0;
                    $temp["net_wt"] = 0;
                    $temp['weight_by'] = "";
                    $temp['check_by'] = "";
                    $output1[] = $temp;
                }
                
                $row["weight_containers"] = $output1;

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "saveWeighingMaterials") {
        $sql = "UPDATE challan_materials SET weighing='approve', weighing_details='".json_encode($input)."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }else if ($_GET["type"] == "getWeighingMaterials") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.chemical_name, m.molecular_wt,c.material_code as chemical_no, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN chemical m ON c.material_code=m.chemical_no LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND weighing !='pending' AND c.material_type='Chemicals' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getPendingGRN") {
        
        
        $output = Array();
         $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.material_name,m.material_type,m.grade,
         o.column_type,o.column_no,o.capacity,o.packing_mat,
        m.material_subtype FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN my_view m 
        ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no left join others_material o on o.material_code =c.material_code WHERE c1.plant_id='".$_GET["plant_id"]."' 
        AND c.grn ='checking' AND m.material_subtype  = '".$_GET["material_subtype"]."'   ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output[] = $row;
            }
        }
        echo json_encode($output);
    
        
        
    }else if ($_GET["type"] == "update_batch_details") {
        
            $jadugar=0;
        
            $json_obj = json_encode($input["batches"]);
            $array = json_decode($json_obj, true);
                
            foreach ($array as $values){
                    
                 $sql = "INSERT INTO engi_stock (material_type,material_subtype, material_code, grn_no, pack_size, qty, entry_by, 
                entry_date,plant_id,mfg_date,exp_date,batch_no) VALUES ('".$values["material_type"]."','".$values["material_subtype"]."','".$values["material_code"]."','".$values["grn_no"]."',
                '".$values["pack_size"]."', '".$values["received_qty"]."', 
                '".$_GET["emp_id"]."', '$entry_date','".$_GET["plant_id"]."','".$values["mfg_date"]."','".$values["exp_date"]."','".$values["batch_no"]."')";
                
                if ($conn->query($sql)) {
                    $jadugar =1;
                    
                        if ($_GET["material_subtype"] == "Columns") {
                            $sql1 = "INSERT INTO columns_management (material_type,material_subtype, material_code, grn_no,  entry_by, 
                            entry_date,plant_id,mfg_date,exp_date, ser_no ) VALUES ('".$values["material_type"]."','".$values["material_subtype"]."',
                            '".$values["material_code"]."','".$values["grn_no"]."','".$_GET["emp_id"]."', '$entry_date','".$_GET["plant_id"]."',
                            '".$values["mfg_date"]."','".$values["exp_date"]."','".$values["batch_no"]."')";
                            $conn->query($sql1);
                        }
                        
                } else {
                   $jadugar =2;
                }
                
            }
     
            if ($jadugar == 1) {
                
             $sql1 = "UPDATE challan_materials SET grn='approve'  WHERE id='".$input["id"]."'";
            $conn->query($sql1);
            
            echo "{\"status\":\"success\"}";
                
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        
        
    }
    else if ($_GET["type"] == "getGRNLog") {
          
      
      
            $output = Array();
        $sql = "SELECT c.*, c1.challan_date,es.exp_date,es.mfg_date,es.batch_no, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.material_name,m.material_type,m.grade,
        m.material_subtype FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN my_view m 
        ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no left join engi_stock es on ( c.grn_no = es.grn_no AND c.material_code = es.material_code) WHERE c1.plant_id='".$_GET["plant_id"]."' 
        AND c.grn ='approve' AND m.material_subtype  =  '".$_GET["material_subtype"]."'    ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getMicrobiologyGRNLog") {
          
      
      
            $output = Array();
        $sql = "SELECT c.*, c1.challan_date,es.exp_date,es.mfg_date,es.batch_no, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.material_name,m.material_type,m.grade,
        m.material_subtype FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN my_view m 
        ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no left join engi_stock es on ( c.grn_no = es.grn_no AND c.material_code = es.material_code) WHERE c1.plant_id='".$_GET["plant_id"]."' 
        AND c.grn ='approve' m.material_subtype  =  '".$_GET["material_subtype"]."'  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    
    
    else if($_GET["type"]=="getStock") {
        
        
         $sql = "SELECT * FROM my_view  where  material_subtype= '".$_GET["material_subtype"]."'  AND plant_id  = '".$_GET["plant_id"]."' ";

    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    
    		    
    		    
            	$sql1 = "SELECT IFNULL(SUM(qty),0) as total_qty FROM engi_stock  where  material_code   = '".$row["material_code"]."' ";
            	$result1 = $conn->query($sql1);
            	if($result1->num_rows > 0){
            		while ($row1 = $result1->fetch_assoc()) {
            		    $row['total_qty'] = $row1['total_qty'] ;
            		}
            	}
            	
            	$sql2 = "SELECT IFNULL(SUM(qty),0) as diduct_qty FROM engi_stock_isshue  where  material_code   = '".$row["material_code"]."' ";
            	$result2 = $conn->query($sql2);
            	if($result2->num_rows > 0){
            		while ($row2 = $result2->fetch_assoc()) {
            		    $row['diduct_qty'] = $row2['diduct_qty'] ;
            		}
            	}
            	
            	
            		$output1 = Array();
            		
        	$sql3 = "SELECT e.id,e.add_det,e.unit,e.qty,e.exp_date,e.mfg_date,e.batch_no,e.grn_no,e.material_code,m.material_name FROM engi_stock e left join my_view m on
            	m.material_code= e.material_code where  e.material_code   = '".$row["material_code"]."' AND e.plant_id   = '".$_GET["plant_id"]."' ";
            	
            	$result3 = $conn->query($sql3);
            	if($result3->num_rows > 0){
            		while ($row3 = $result3->fetch_assoc()) {
            		    $output1[] = $row3 ;
            		}
            	}
            	
            		$output12 = Array();
            		
            	$sql32 = "SELECT e.qty,e.batch_no,e.grn_no,e.material_code,m.material_name FROM engi_stock_isshue e left join my_view m on
            	m.material_code= e.material_code where  e.material_code   = '".$row["material_code"]."' AND e.plant_id   = '".$_GET["plant_id"]."' ";
            	
            	$result32 = $conn->query($sql32);
            	if($result32->num_rows > 0){
            		while ($row32 = $result32->fetch_assoc()) {
            		    $output12[] = $row32 ;
            		}
            	}
    	
    	
    	
        	   $row['avaliable_qty'] =  $row['total_qty'] -  $row['diduct_qty'];
        	   $row['avaliable_qty'] = number_format((float)$row['avaliable_qty'], 2, '.', '');
        	   
		        $row['stock_data'] = $output1;
		        $row['consumption_data'] = $output12;
    		   $output[] = $row;
    		   
    		   
    		}
    	}
    	
    	
    	
    	echo json_encode($output);
    	
    	
    }
    else if ($_GET["type"] == "downloadWeighingMaterials") {
        $_GET['filename'] = 'WeighingMaterials'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:cenetr">WeighingMaterials</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:10%;">Sr.</td>
                        <td style="width:15%;">Challan No</td>
                        <td style="width:15%;">Material Code</td>
                        <td style="width:15%;">Chemical Name</td>
                        <td style="width:15%;">Grade</td>
                        <td style="width:15%;">Accepted By</td>
                        <td style="width:15%;">Containers</td>
                    </tr>
                </thead>';
                $output = Array();
                $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.chemical_name, m.molecular_wt,c.material_code as chemical_no, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN chemical m ON c.material_code=m.chemical_no LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND weighing !='pending' AND c.material_type='Chemicals' GROUP BY c.id";
                $result = $conn->query($sql);
                $j=1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                    $row["receiving_details"] = json_decode($row["receiving_details"]);
                    $row["weighing_details"] = json_decode($row["weighing_details"]);
                     $receiving_details = $row["receiving_details"];
                        //for ($i = 0; $i < count($receiving_details); $i++) {
                            $receiving_detail = $receiving_details;
                    $output[] = $row;
                    
            $html.='<tr nobr="true">
                        <td style="width:10%;">'.$j.'.</td>
                        <td style="width:15%;">'.$row['challan_no'].'</td>
                        <td style="width:15%;">'.$row['material_code'].'</td>
                        <td style="width:15%;">'.$row['chemical_name'].'</td>
                        <td style="width:15%;">'.$row['grade'].'</td>
                        <td style="width:15%;">'.$receiving_detail->received_by.'</td>
                        <td style="width:15%;">'.$row['containers'].'</td>
                    </tr>';
                    $j++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('WeighingMaterials.pdf', 'I');
    }
    
    else if ($_GET["type"] == "downloadgetStock") {
        $_GET['filename'] = 'Stock'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Stock</h2>
        <table border="1" cellpadding="5">
                  <tr>
                        <td style="width:5%; text-align:center;"><b>Sr.</b></td>
                        <td style="width:14%; text-align:center;"><b>GRN No</b></td>
                        <td style="width:15%; text-align:center;"><b>Vendor Name</b></td>
                        <td style="width:10%; text-align:center;"><b>Chemical No</b></td>
                        <td style="width:15%; text-align:center;"><b>Chemical Name</b></td>
                        <td style="width:15%; text-align:center;"><b>Grade</b></td>
                        <td style="width:11%; text-align:center;">vBatch No</b></td>
                        <td style="width:15%; text-align:center;"><b>Available Qty</b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:14%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:11%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
            </table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Stock.pdf', 'I');
        
        
    }else if ($_GET["type"] == "downloadChallanLog") {
        $_GET['filename'] = 'Chemicals Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
      
        $html.='
        <h2 style="text-align:center">Chemicals Log</h2>
        <table border="1" cellpadding="5">
                <tr>
                    <td style="width:5%;  text-align:center;"><b>Sr</b></td>
                    <td style="width:10%; text-align:center;"><b>Standard Type</b></td>
                    <td style="width:10%; text-align:center;"><b>Vendor Name</b>	</td>
                    <td style="width:10%; text-align:center;"><b>Challan No.</b></td>
                    <td style="width:10%; text-align:center;"><b>Challan Date</b></td>
                    <td style="width:5%;  text-align:center;"><b>PO No.</b></td>
                    <td style="width:10%; text-align:center;"><b>PO Date</b></td>
                    <td style="width:10%; text-align:center;"><b>Invoice No.</b></td>
                    <td style="width:10%; text-align:center;"><b>Amount</b></td>
                    <td style="width:10%; text-align:center;"><b>Prepared Date</b></td>
                    <td style="width:10%; text-align:center;"><b>Prepared By</b></td>
                </tr>
                <tr>
                    <td style="width:5%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:5%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                </tr>
            </table>';
            
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ChemicalsLog.pdf', 'I');
    }
    else if ($_GET["type"] == "receivingMaterialLogPDF") {
        $_GET['filename'] = 'receivingMaterialLog'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.="";
        $html.='
        <h2 style="text-align:center">Receiving Material Log</h2>
        <table border="1" cellpadding="5">
                <tr>
                    <td style="width: 5%;text-align:center;"><b>Sr.</b></td>
                    <td style="width: 15%;text-align:center;"><b>vendor Name</b></td>
                    <td style="width: 10%;text-align:center;"><b>Challan  No</b></td>
                    <td style="width: 10%;teext-align:center;"><b>Challan Date</b></td>
                    <td style="width: 10%;text-align:center;"><b>Grade</b></td>
                    <td style="width: 15%;text-align:center;"><b>Chemical Name</b></td>
                    <td style="width: 10%;text-align;center;"><b>Chemical No</b></td>
                    <td style="width: 15%;text-align:center;"><b>Receiving Date</b></td>
                    <td style="width: 10%;text-align;center;"><b>Qty</b></td>
                </tr>
                <tr>
                    <td style="width: 5%;"></td>
                    <td style="width: 15%;"></td>
                    <td style="width: 10%;"></td>
                    <td style="width: 10%;"></td>
                    <td style="width: 10%;"></td>
                    <td style="width: 15%;"></td>
                    <td style="width: 10%;"></td>
                    <td style="width: 15%;"></td>
                    <td style="width: 10%;"></td>
                </tr>
            </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('receivingMaterialLog.pdf', 'I');
    }
    
    
    else if ($_GET["type"] == "GRNLogPDF") {
        $_GET['filename'] = 'GRNLog'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">GRNLog</h2>
        <table border="1" cellpadding="5">
                    <tr>
                        <td style="width: 5%;"><b>Sr.</b></td>
                        <td style="width: 10%;"><b>Date</b></td>
                        <td style="width: 15%;"><b>Material Code</b></td>
                        <td style="width: 10%;"><b>Chemical Name</b></td>
                        <td style="width: 10%;"><b>Grade</b></td>
                        <td style="width: 10%;"><b> Vendor Name</b></td>
                        <td style="width: 15%;"><b>Containers</b></td>
                        <td style="width: 15%;"><b>Receiving Date</b></td>
                        <td style="width: 10%;"><b>Qty</b></td>
                    </tr>
                    <tr>
                        <td style="width: 5%;"></td>
                        <td style="width: 10%;"></td>
                        <td style="width: 15%;"></td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%;"></td>
                        <td style="width: 15%;"></td>
                        <td style="width: 15%;"></td>
                        <td style="width: 10%;"></td>
                    </tr>
    </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('GRNLog.pdf', 'I');
    }else if ($_GET["type"] == "downloadChemicalsLog") {
        $_GET['filename'] = 'Chemicals Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $plant = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : '');
        $grade = isset($_GET["grade"]) ? $conn->real_escape_string(trim($_GET["grade"])) : '';
        $where = "plant_id='".$plant."'";
        if ($grade !== '') {
            $where .= " AND grade LIKE '%".$grade."%'";
        }
        $html.='
        <h2 style="text-align:center">Chemicals Log</h2>
        <table border="1" cellpadding="5">
                <tr style="background-color:#DDDAD9; font-weight:bold; border:solid 1px black">
                    <td style="width:10%;">Sr</td>
                    <td style="width:15%;">Chemical No</td>
                    <td style="width:10%;">Name</td>
                    <td style="width:10%;">Grade</td>
                    <td style="width:15%;">Molecular Wt</td>
                    <td style="width:10%;">Unit</td>
                    <td style="width:10%;">Pack Size</td>
                    <td style="width:15%;">Cas Name</td>
                </tr>';
                $i=1;
                $sql = "SELECT * FROM chemical WHERE ".$where." ORDER BY chemical_name";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $packSize = $row['pack_size'];
                if (is_string($packSize)) {
                    $decodedPack = json_decode($packSize, true);
                    if (is_array($decodedPack)) {
                        $packSize = implode(', ', $decodedPack);
                    }
                }
            $html.='<tr nobr="true">
                        <td style="width:10%;">'.$i.'</td>
                        <td style="width:15%;">'.$row['chemical_no'].'</td>
                        <td style="width:10%;">'.$row['chemical_name'].'</td>
                        <td style="width:10%;">'.$row['grade'].'</td>
                        <td style="width:15%;">'.$row['molecular_wt'].'</td>
                        <td style="width:10%;">'.$row['unit'].'</td>
                        <td style="width:10%;">'.$packSize.'</td>
                        <td style="width:15%;">'.$row['cas_name'].'</td>
                    </tr>';
                    $i++;
                    }
                }
        if ($i === 1) {
            $sqlOm = "SELECT *, material_code AS chemical_no, material_name AS chemical_name, cas_no AS cas_name
                FROM others_material
                WHERE material_subtype='Chemicals' AND plant_id='".$plant."'";
            if ($grade !== '') {
                $sqlOm .= " AND grade LIKE '%".$grade."%'";
            }
            $sqlOm .= " ORDER BY material_name";
            $resultOm = $conn->query($sqlOm);
            if ($resultOm && $resultOm->num_rows > 0) {
                while ($row = $resultOm->fetch_assoc()) {
                    $html.='<tr nobr="true">
                        <td style="width:10%;">'.$i.'</td>
                        <td style="width:15%;">'.$row['chemical_no'].'</td>
                        <td style="width:10%;">'.$row['chemical_name'].'</td>
                        <td style="width:10%;">'.$row['grade'].'</td>
                        <td style="width:15%;">'.$row['molecular_wt'].'</td>
                        <td style="width:10%;">'.$row['unit'].'</td>
                        <td style="width:10%;">'.$row['pack_size'].'</td>
                        <td style="width:15%;">'.$row['cas_name'].'</td>
                    </tr>';
                    $i++;
                }
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ChemicalsLog.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadWeighingMaterials") {
        $_GET['filename'] = 'Chemicals Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:cenetr">Chemicals Log</h2>
        <table border="1" cellpadding="5">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">Sr</td>
                    <td style="width:15%;">Chemical No</td>
                    <td style="width:15%;">Material Code</td>
                    <td style="width:15%;">Chemical Name</td>
                    <td style="width:15%;">Grade</td>
                    <td style="width:15%;">Accepted Qty	</td>
                    <td style="width:15%;">Containers</td>
                </tr>';
                  $output = Array();
                  $i=1;
                    $sql = "SELECT c.*, v.vendor_name, v.email, v.gst_no FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.material_type='Chemicals' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY c.id ORDER BY c.id DESC ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                            $row["make"] = json_decode($row["make"]);
                            $output[] = $row;
                $html.='<tr>
                            <td style="width:10%;">'.$i.'</td>
                            <td style="width:15%;">'.$row['chemical_no'].'</td>
                            <td style="width:15%;">'.$row['material_code'].'</td>
                            <td style="width:15%;">'.$row['chemical_name'].'</td>
                            <td style="width:15%;">'.$row['grade'].'</td>
                            <td style="width:15%;">'.$row['accepted_qty'].'</td>
                            <td style="width:15%;">'.$row['container'].'</td>
                        </tr>';
                        $i++;
                        }
                    }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ChemicalsLog.pdf', 'I');
}



}

$conn->close();
?>