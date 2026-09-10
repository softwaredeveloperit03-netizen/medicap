<?php

  
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
   

    try{    
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

    function ensureQuotationCategoryColumn($conn) {
        $check = $conn->query("SHOW COLUMNS FROM `quotation_hdr` LIKE 'quotation_category'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE `quotation_hdr` ADD COLUMN `quotation_category` VARCHAR(50) DEFAULT 'Quotation' AFTER `material_type`");
        }
    }

    function columnExists($conn, $table, $column) {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = $conn->real_escape_string($column);
        $check = $conn->query("SHOW COLUMNS FROM `".$table."` LIKE '".$column."'");
        return $check && $check->num_rows > 0;
    }

    function parsePostedMaterialsPayload($postInput = null) {
        $raw = '';
        if (isset($_POST['materials'])) {
            $raw = $_POST['materials'];
            if (is_array($raw)) {
                return $raw;
            }
        }
        if ($raw === '' && is_array($postInput) && isset($postInput['materials'])) {
            if (is_array($postInput['materials'])) {
                return $postInput['materials'];
            }
            $raw = $postInput['materials'];
        }
        $raw = trim((string)$raw);
        if ($raw === '') {
            return array();
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = json_decode(stripslashes($raw), true);
        }
        if (!is_array($decoded)) {
            $decoded = json_decode(html_entity_decode($raw, ENT_QUOTES, 'UTF-8'), true);
        }
        return is_array($decoded) ? $decoded : array();
    }

    function quotationCategoryHdrFilter($conn, $category, $alias = 'qh') {
        $typeExpr = "UPPER(TRIM(IFNULL(".$alias.".material_type,'')))";
        $rmPmExpr = $typeExpr." IN ('RAW MATERIAL', 'PACKING MATERIAL', 'PHARMA RAW MATERIAL')";
        $catExpr = "UPPER(TRIM(IFNULL(".$alias.".quotation_category,'')))";

        if (columnExists($conn, 'quotation_hdr', 'quotation_category')) {
            if ($category === 'General') {
                // genNew saves quotation_category = General; legacy rows use non-RM/PM material_type (e.g. QC Materials / Chemical).
                return " AND (
                    ".$catExpr." = 'GENERAL'
                    OR (
                        ".$catExpr." IN ('', 'QUOTATION')
                        AND NOT (".$rmPmExpr.")
                    )
                    OR ".$typeExpr." = ''
                )";
            }
            return " AND (
                ".$catExpr." IN ('QUOTATION', '')
                OR ".$catExpr." NOT IN ('GENERAL')
            ) AND (".$rmPmExpr.")";
        }

        if ($category === 'General') {
            return " AND (NOT (".$rmPmExpr.") OR ".$typeExpr." = '')";
        }
        return " AND (".$rmPmExpr.")";
    }

    function quotationStatusFilter($pendingOnly, $alias = 'qh') {
        if ($pendingOnly) {
            return " AND LOWER(TRIM(IFNULL(".$alias.".status,''))) = 'pending'";
        }
        return " AND LOWER(TRIM(IFNULL(".$alias.".status,''))) IN ('approve', 'approved')";
    }

    function fetchComparativeMaterialMeta($conn, $plant_id, $material_code, $fallback_type = '', $material_id = 0) {
        $material_code = trim((string)$material_code);
        $material_id = (int)$material_id;
        $plantEsc = $conn->real_escape_string((string)$plant_id);
        $meta = array(
            'material_code' => $material_code,
            'material_type' => $fallback_type,
            'material_subtype' => '',
            'material_name' => $material_code !== '' ? $material_code : 'General Material',
            'grade' => '',
            'id' => 0
        );
        if ($material_code !== '' && stripos($material_code, 'null') === 0 && $material_id > 0) {
            $idEsc = $material_id;
            $sqlFix = "SELECT * FROM others_material WHERE id = '".$idEsc."' AND plant_id = '".$plantEsc."' LIMIT 1";
            $resultFix = $conn->query($sqlFix);
            if ($resultFix && $resultFix->num_rows > 0) {
                $rowFix = $resultFix->fetch_assoc();
                if (!empty($rowFix['material_name'])) {
                    $meta['material_name'] = $rowFix['material_name'];
                }
                if (!empty($rowFix['material_type'])) {
                    $meta['material_type'] = $rowFix['material_type'];
                }
                if (!empty($rowFix['material_subtype'])) {
                    $meta['material_subtype'] = $rowFix['material_subtype'];
                }
            }
        }
        if ($material_code !== '') {
            $code = $conn->real_escape_string($material_code);
            $chemicalSql = "SELECT id, chemical_no AS material_code, chemical_name AS material_name, 'QC Materials' AS material_type, 'Chemical' AS material_subtype, grade FROM chemical WHERE plant_id = '".$plantEsc."' AND chemical_no = '".$code."' LIMIT 1";
            $resultChem = $conn->query($chemicalSql);
            if ($resultChem && $resultChem->num_rows > 0) {
                return array_merge($meta, $resultChem->fetch_assoc());
            }
            $lookups = array(
                "SELECT * FROM my_view WHERE material_code = '".$code."' AND plant_id = '".$plantEsc."' LIMIT 1",
                "SELECT * FROM material WHERE material_code = '".$code."' AND plant_id = '".$plantEsc."' LIMIT 1",
                "SELECT * FROM others_material WHERE material_code = '".$code."' AND plant_id = '".$plantEsc."' LIMIT 1",
                "SELECT * FROM general_material WHERE material_code = '".$code."' LIMIT 1",
            );
            foreach ($lookups as $sql) {
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    return array_merge($meta, $result->fetch_assoc());
                }
            }
        }
        if ($material_id > 0) {
            $idLookups = array(
                "SELECT id, chemical_no AS material_code, chemical_name AS material_name, 'QC Materials' AS material_type, 'Chemical' AS material_subtype, grade FROM chemical WHERE id = '".$material_id."' AND plant_id = '".$plantEsc."' LIMIT 1",
                "SELECT * FROM others_material WHERE id = '".$material_id."' AND plant_id = '".$plantEsc."' LIMIT 1",
            );
            foreach ($idLookups as $sql) {
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if (!empty($row['material_code'])) {
                        $meta['material_code'] = $row['material_code'];
                    }
                    return array_merge($meta, $row);
                }
            }
        }
        return $meta;
    }

    function resolveGenQuotationMaterialCode($conn, $plant_id, $material) {
        $code = trim((string)($material['material_code'] ?? ''));
        $chemicalNo = trim((string)($material['chemical_no'] ?? ''));
        $subtype = strtolower(trim((string)($material['material_subtype'] ?? '')));
        $isChemLine = ($chemicalNo !== '')
            || in_array($subtype, array('chemical', 'chemicals', 'reagent', 'reagents'), true);

        if ($isChemLine && $chemicalNo !== '') {
            return $chemicalNo;
        }

        $plantEsc = $conn->real_escape_string((string)$plant_id);
        $material_id = (int)($material['id'] ?? 0);
        if ($material_id > 0) {
            $sql = "SELECT chemical_no FROM chemical WHERE id = '".$material_id."' AND plant_id = '".$plantEsc."' LIMIT 1";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $resolved = trim((string)($row['chemical_no'] ?? ''));
                if ($resolved !== '') {
                    return $resolved;
                }
            }
        }

        if ($code !== '' && stripos($code, 'null') !== 0 && stripos($code, 'GEN-OM-') !== 0) {
            return $code;
        }
        if ($chemicalNo !== '') {
            return $chemicalNo;
        }
        if ($material_id > 0) {
            $sql = "SELECT material_code FROM others_material WHERE id = '".$material_id."' AND plant_id = '".$plantEsc."' LIMIT 1";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $resolved = trim((string)($row['material_code'] ?? ''));
                if ($resolved !== '' && stripos($resolved, 'null') !== 0) {
                    return $resolved;
                }
            }
        }
        $material_name = trim((string)($material['material_name'] ?? $material['chemical_name'] ?? ''));
        if ($material_name !== '') {
            $nameEsc = $conn->real_escape_string($material_name);
            $subtype = trim((string)($material['material_subtype'] ?? ''));
            $subtypeSql = '';
            if ($subtype !== '') {
                $subtypeEsc = $conn->real_escape_string($subtype);
                $subtypeSql = " AND material_subtype IN ('".$subtypeEsc."', 'Chemicals', 'Chemical', 'Reagents')";
            }
            $sql = "SELECT material_code FROM others_material WHERE plant_id = '".$plantEsc."' AND material_name = '".$nameEsc."'".$subtypeSql." ORDER BY id DESC LIMIT 1";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $resolved = trim((string)($row['material_code'] ?? ''));
                if ($resolved !== '') {
                    return $resolved;
                }
            }
            $sql = "SELECT chemical_no FROM chemical WHERE plant_id = '".$plantEsc."' AND chemical_name = '".$nameEsc."' ORDER BY id DESC LIMIT 1";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $resolved = trim((string)($row['chemical_no'] ?? ''));
                if ($resolved !== '') {
                    return $resolved;
                }
            }
        }
        if ($material_id > 0) {
            return 'GEN-OM-'.$material_id;
        }
        return $code;
    }

    function quotationDtlMaterialMatchClause($conn, $material_code, $material_id, $alias = 'qd') {
        $code = trim((string)$material_code);
        $material_id = (int)$material_id;
        $parts = array();
        if ($code !== '') {
            $parts[] = "TRIM(IFNULL(".$alias.".material_code,'')) = '".$conn->real_escape_string($code)."'";
        }
        if ($material_id > 0) {
            $parts[] = $alias.".material_id = '".$material_id."'";
        }
        if (count($parts) === 0) {
            return " AND (".$alias.".material_code IS NULL OR TRIM(IFNULL(".$alias.".material_code,'')) = '')";
        }
        return " AND (".implode(' OR ', $parts).")";
    }

    function buildComparativesList($conn, $plant_id, $category, $pendingOnly) {
        $output = array();
        $catFilter = quotationCategoryHdrFilter($conn, $category, 'qh');
        $statusFilter = quotationStatusFilter($pendingOnly, 'qh');

        $sql = "SELECT qd.material_code, qd.material_id, MAX(qh.material_type) AS hdr_material_type
                FROM quotation_dtl qd
                INNER JOIN quotation_hdr qh ON qh.id = qd.quotation_hdr_id
                WHERE qh.plant_id = '".$plant_id."'
                  ".$catFilter."
                  ".$statusFilter."
                GROUP BY qd.material_id, qd.material_code
                ORDER BY MAX(qd.id) DESC";

        $result = $conn->query($sql);
        if (!$result) {
            return $output;
        }

        $detailCatFilter = quotationCategoryHdrFilter($conn, $category, 'qh');
        $detailStatusFilter = quotationStatusFilter($pendingOnly, 'qh');

        while ($row = $result->fetch_assoc()) {
            $material_code = trim((string)($row['material_code'] ?? ''));
            $material_id = (int)($row['material_id'] ?? 0);
            $meta = fetchComparativeMaterialMeta($conn, $plant_id, $material_code, $row['hdr_material_type'] ?? '', $material_id);

            $gradeName = '';
            if (!empty($meta['grade'])) {
                $gradeIds = $conn->real_escape_string($meta['grade']);
                $q = "SELECT GROUP_CONCAT(grade ORDER BY grade SEPARATOR ', ') AS gradeName
                      FROM grade
                      WHERE FIND_IN_SET(id, '".$gradeIds."')";
                $resQ = $conn->query($q);
                if ($resQ && $resQ->num_rows > 0) {
                    $prodLatest = $resQ->fetch_assoc();
                    $gradeName = $prodLatest['gradeName'] ?? '';
                }
            }
            $meta['gradeName'] = $gradeName;
            if ($gradeName !== '') {
                $meta['grade'] = $gradeName;
            }
            if (empty($meta['material_type']) && !empty($row['hdr_material_type'])) {
                $meta['material_type'] = $row['hdr_material_type'];
            }

            $output1 = array();
            $materialMatch = quotationDtlMaterialMatchClause($conn, $material_code, $material_id, 'qd');
            $quotationNoSelect = columnExists($conn, 'quotation_hdr', 'quotation_no')
                ? "qh.quotation_no,"
                : "";
            $sql1 = "SELECT
                        qh.id AS quotation_hdr_id,
                        ".$quotationNoSelect."
                        qh.vendor_quotation_no,
                        qh.vendor_quotation_date,
                        qh.entry_date,
                        qh.status,
                        qh.doc_url,
                        qh.approve_by,
                        qh.approve_date,
                        qh.material_type,
                        qd.id AS quotation_dtl_id,
                        qd.material_code,
                        qd.quotation_amt,
                        qd.quotation_per,
                        qd.gst_per,
                        qd.currency,
                        qd.pack_size,
                        qd.pack_unit,
                        v.vendor_name,
                        v.contact_email,
                        v.city,
                        v.gst_no
                     FROM quotation_dtl qd
                     INNER JOIN quotation_hdr qh ON qd.quotation_hdr_id = qh.id
                     LEFT JOIN vendor v ON qh.vendor_id = v.id
                     WHERE qh.plant_id = '".$plant_id."'
                       ".$materialMatch."
                       ".$detailCatFilter."
                       ".$detailStatusFilter."
                     ORDER BY qh.id DESC";

            $result1 = $conn->query($sql1);
            if ($result1 && $result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row1['quotation_hdr_id'] = $row1['quotation_hdr_id'] ?? $row1['id'];
                    $quotationNo = trim((string)($row1['quotation_no'] ?? ''));
                    $vendorQuotationNo = trim((string)($row1['vendor_quotation_no'] ?? ''));
                    if ($quotationNo === '' && $vendorQuotationNo !== '') {
                        $row1['quotation_no'] = $vendorQuotationNo;
                    }
                    $row1['tax_type'] = 'Local';
                    if (columnExists($conn, 'quotation_dtl', 'tax_type')) {
                        $taxSql = "SELECT IFNULL(NULLIF(tax_type,''), 'Local') AS tax_type FROM quotation_dtl WHERE id = '".$conn->real_escape_string($row1['quotation_dtl_id'])."' LIMIT 1";
                        $taxRes = $conn->query($taxSql);
                        if ($taxRes && $taxRes->num_rows > 0) {
                            $taxRow = $taxRes->fetch_assoc();
                            $row1['tax_type'] = $taxRow['tax_type'];
                        }
                    }
                    $output1[] = $row1;
                }
            }

            if (count($output1) > 0) {
                $meta['quotationDetails'] = $output1;
                $output[] = $meta;
            }
        }

        return $output;
    }

    ensureQuotationCategoryColumn($conn);
    
if ($_GET["type"] == "saveQuotation") {
    
   
    
        	$qtNo = $_POST["vendor_quotation_no"];
    	$pid = $_GET["plant_id"];
        $file_name = '';
        if (isset($_FILES["document"])) {
            $file_tmp = $_FILES['document']['tmp_name'];
            $file_ext = strtolower(end(explode('.', $_FILES['document']['name'])));
            $file_name = $pid.$qtNo."quatation.".$file_ext;
            move_uploaded_file($file_tmp, "../../../upload/quotation/" . $file_name);
        }else{
            $file_name = 'NA';
        }
         
       
         $sql = "INSERT INTO quotation_hdr (plant_id,vendor_quotation_no,material_type, quotation_category, vendor_id, doc_url, entry_by, entry_date,vendor_quotation_date,validTill,status)
        VALUES ('".$_GET["plant_id"]."',   '".mysqli_real_escape_string($conn, $_POST["vendor_quotation_no"])."', '".$_POST["material_type"]."', 'Quotation',
            '".$_POST["vendor_no"]."','$file_name','".$_GET["emp_id"]."','".$entry_date."','".$_POST["vendor_quotation_date"]."','".$_POST["validTill"]."','Pending')";
    
    
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            
           $materials = json_decode($_POST["materials"],true);
            
            for ($i = 0; $i < count($materials); $i++) {
                
                $material = $materials[$i];
      
                $tax=$material["gst"]; 
                
             
            $sql1 = "INSERT INTO quotation_dtl (quotation_hdr_id,quotation_type, material_id,material_code,quoted_amt,quotation_amt, tax_applicable, gst_per,
            pack_size, pack_unit, quotation_per,Manufacturer,validTill,tax_type,currency) VALUES('".$last_id."','Local Purchase','".$material["id"]."','".$material["material_code"]."', 
            '".$material["quotation_amt"]."','".$material["quotation_amt"]."','Yes', '$tax', '".$material["pack_size"]."','".$material["pack_size_unit"]."',
            '".$material["quotation_per"]."','".$_POST["vendor_no"]."', '".$material["validTill"]."', '".$material["tax_type"]."', '".$material["currency"]."')"; 
                
                $conn->query($sql1);
            }
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
    
} 
else if ($_GET["type"] == "editQuotationRate") {
     
            $check = false;
            $hdrIds = array();
            
            $materials = json_decode($_POST["materials"],true);
            
            for ($i = 0; $i < count($materials); $i++) {
                
                $material = $materials[$i];
             
            $sql1 = "UPDATE quotation_dtl SET quotation_amt = '".$material["newQuotation_amt"]."' ,  validTill = '".$material["newValidTill"]."' where id = '".$material["id"]."'"; 
                
                if ($conn->query($sql1)) {
                    $check = true;
                } else {
                    $check = false;
                }
                
                
            $sql12 = "INSERT INTO quotation_dtl_history (quotation_hdr_id,quotation_type, material_id,material_code,quoted_amt,quotation_amt, tax_applicable, gst_per,
            pack_size, pack_unit, quotation_per,Manufacturer,validTill,entryBy,entryOn) VALUES('".$material["quotation_hdr_id"]."','Local Purchase','".$material["material_id"]."','".$material["material_code"]."', 
            '".$material["quoted_amt"]."','".$material["quotation_amt"]."','Yes', '".$material["gst_per"]."', '".$material["pack_size"]."','".$material["pack_unit"]."',
            '".$material["quotation_per"]."','".$material["Manufacturer"]."', '".$material["validTill"]."','".$_GET["emp_id"]."','".$entry_date."')"; 
                
                $conn->query($sql12);

                if (!empty($material["quotation_hdr_id"])) {
                    $hdrIds[$material["quotation_hdr_id"]] = $material["quotation_hdr_id"];
                }
            }

            // After update the quotation goes back for approval
            if (count($hdrIds) > 0) {
                $idList = implode(",", array_map(array($conn, 'real_escape_string'), $hdrIds));
                $conn->query("UPDATE quotation_hdr SET status='Pending', approve_by='0', approve_date=NULL WHERE id IN (".$idList.")");
            }
            
            
        if ($check) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
     
} 
 

else if ($_GET["type"] == "saveGENQuotation") {
        ensureQuotationCategoryColumn($conn);
       
        
        $qtNo = $_POST["vendor_quotation_no"];
    	$pid = isset($_GET["plant_id"]) ? $_GET["plant_id"] : (isset($_GET["plantID"]) ? $_GET["plantID"] : '');
    	
        $file_name = '';
        if (isset($_FILES["document"])) {
            $file_tmp = $_FILES['document']['tmp_name'];
            $file_ext = strtolower(end(explode('.', $_FILES['document']['name'])));
            $file_name = $pid.$qtNo."quatation.".$file_ext;
            move_uploaded_file($file_tmp, "../../../upload/quotation/" . $file_name);
        }else{
            $file_name = 'NA';
        }
         
      
       
        $material_type = $conn->real_escape_string($_POST["material_type"] ?? '');
        $vendor_no = $conn->real_escape_string($_POST["vendor_no"] ?? '');
        $vendor_quotation_no = $conn->real_escape_string($_POST["vendor_quotation_no"] ?? '');

        $materials = parsePostedMaterialsPayload(is_array($input) ? $input : null);
        if (count($materials) === 0) {
            echo "{\"status\":\"No materials received. Add at least one material line and try again.\"}";
            exit;
        }

        if (columnExists($conn, 'quotation_hdr', 'quotation_category')) {
            $sql = "INSERT INTO quotation_hdr (plant_id,vendor_quotation_no,material_type, quotation_category, vendor_id, doc_url, entry_by, entry_date,vendor_quotation_date,status)
            VALUES ('".$_GET["plant_id"]."','".$vendor_quotation_no."','".$material_type."', 'General',
                '".$vendor_no."','$file_name','".$_GET["emp_id"]."','".$entry_date."','".$entry_date."','Pending')";
        } else {
            $sql = "INSERT INTO quotation_hdr (plant_id,vendor_quotation_no,material_type, vendor_id, doc_url, entry_by, entry_date,vendor_quotation_date,status)
            VALUES ('".$_GET["plant_id"]."','".$vendor_quotation_no."','".$material_type."',
                '".$vendor_no."','$file_name','".$_GET["emp_id"]."','".$entry_date."','".$entry_date."','Pending')";
        }
    
    
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $hasTaxType = columnExists($conn, 'quotation_dtl', 'tax_type');
            $inserted = 0;
            
            for ($i = 0; $i < count($materials); $i++) {
                
                $material = $materials[$i];
                $quotation_per = isset($material["quotation_per"]) && $material["quotation_per"] !== ''
                    ? $material["quotation_per"]
                    : (isset($material["unit"]) ? $material["unit"] : 'NA');
                $tax = isset($material["tax"]) && $material["tax"] !== ''
                    ? $material["tax"]
                    : (isset($material["gst"]) ? $material["gst"] : '0');
                $pack_size = isset($material["pack_size"]) && $material["pack_size"] !== '' ? $material["pack_size"] : 'NA';
                $pack_size_unit = isset($material["pack_size_unit"]) && $material["pack_size_unit"] !== '' ? $material["pack_size_unit"] : 'NA';
                $tax_type = isset($material["tax_type"]) && $material["tax_type"] !== '' ? $material["tax_type"] : 'Local';
                $material_id = (int)($material["id"] ?? 0);
                $material_code = resolveGenQuotationMaterialCode($conn, $_GET["plant_id"], $material);
                if ($material_code === '' || $material_code === '0') {
                    continue;
                }
                $material_code = $conn->real_escape_string($material_code);
                $quotation_amt = $conn->real_escape_string($material["quotation_amt"] ?? '0');
                $currency = $conn->real_escape_string($material["currency"] ?? 'INR');
             
                if ($hasTaxType) {
                    $sql1 = "INSERT INTO quotation_dtl (quotation_hdr_id,quotation_type, material_id,material_code,quotation_amt, tax_applicable, gst_per,
                    pack_size, pack_unit, quotation_per,Manufacturer,currency,tax_type) VALUES('".$last_id."','Local Purchase','".$material_id."','".$material_code."', 
                    '".$quotation_amt."','Yes', '".$tax."', '".$pack_size."','".$pack_size_unit."',
                    '".$quotation_per."','NA','".$currency."','".$tax_type."')";
                } else {
                    $sql1 = "INSERT INTO quotation_dtl (quotation_hdr_id,quotation_type, material_id,material_code,quotation_amt, tax_applicable, gst_per,
                    pack_size, pack_unit, quotation_per,Manufacturer,currency) VALUES('".$last_id."','Local Purchase','".$material_id."','".$material_code."', 
                    '".$quotation_amt."','Yes', '".$tax."', '".$pack_size."','".$pack_size_unit."',
                    '".$quotation_per."','NA','".$currency."')";
                }
                
                if (!$conn->query($sql1)) {
                    $conn->query("DELETE FROM quotation_dtl WHERE quotation_hdr_id = '".$last_id."'");
                    $conn->query("DELETE FROM quotation_hdr WHERE id = '".$last_id."'");
                    echo "{\"status\":\"".$conn->error."\"}";
                    exit;
                }
                $inserted++;
            }

            if ($inserted === 0) {
                $conn->query("DELETE FROM quotation_hdr WHERE id = '".$last_id."'");
                echo "{\"status\":\"Could not save quotation lines. Check material code and try again.\"}";
                exit;
            }
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
    
} 
 
else if ($_GET["type"] == "correcetion_quote") {
        $material=$input;

       $sql = "INSERT INTO quotation_dtl (quotation_hdr_id,quotation_type, material_id,material_code,quotation_amt, tax_applicable, gst_per, pack_size, pack_unit, quotation_per) VALUES 
             ('".$material["quote_id"]."','".$material["quotation_type"]."','".$material["matCode"]["id"]."','".$material["matCode"]["material_code"]."', '".$material["quotation_amt"]."', 
            '".$material["gst_applicable"]."', '".$material["igstPer"]."', '".$material["pack_size"]."', '".$material["pack_size_unit"]."', '".$material["quotation_per"]."')"; 
            
        
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
     
}


else if ($_GET["type"] == "getvendorquatationmaterialforshort") {
        
        
     $sql = "SELECT mst.supplier_code , mst.manufacturer_data , mv.* FROM mst_vendor_materials mst left join my_view mv ON mst.material_code = mv.material_code where
    mst.supplier_code = '".$_GET["vendor_no"]."' AND  mst.material_code = '".$_GET["material_code"]."' AND mst.plant_id = '".$_GET["plant_id"]."' ";
    
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['manufacturer_data'] = json_decode($row['manufacturer_data']);
            $output[] = $row;
        }
    }
        
       echo json_encode($output);
}
else if ($_GET["type"] == "getvendorquatationmaterialforshortHo") {
        
        
     $sql = "SELECT mst.supplier_code , mst.manufacturer_data , mv.* FROM mst_vendor_materials mst left join my_view mv ON mst.material_code = mv.material_code where
    mst.supplier_code = '".$_GET["vendor_no"]."' AND  mst.material_code = '".$_GET["material_code"]."' AND mst.plant_id = '".$_GET["plantID"]."' ";
    
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['manufacturer_data'] = json_decode($row['manufacturer_data']);
            $output[] = $row;
        }
    }
        
       echo json_encode($output);
}


else if ($_GET["type"] == "save_purchase") {
  $sql = "INSERT INTO purchess ( plant_id,v_name,v_address,con_per,con_email,con_ph,soft_name,ver_name,lic_method,l_name,d_name,del_method,p_name,p_id,qty,unit_ph,
  shipping_address,shipping_method,exp_date,pay_method,pay_status,pay_amount,order_status,attachments) VALUES
  ( '".$_GET["plant_id"]."','".$input["v_name"]."','".$input["v_address"]."', '".$input["con_per"]."', '".$input["con_email"]."', '".$input["con_ph"]."', 
        '".$input["soft_name"]."', '".$input["ver_name"]."' ,'".$input["lic_method"]."' ,'".$input["l_name"]."' ,'".$input["d_name"]."' ,'".$input["del_method"]."' ,'".$input["p_name"]."' ,'".$input["p_id"]."' ,
        '".$input["qty"]."' ,'".$input["unit_ph"]."' ,'".$input["shipping_address"]."' ,'".$input["shipping_method"]."' ,'".$input["exp_date"]."' ,'".$input["pay_method"]."' ,'".$input["pay_status"]."' ,'".$input["pay_amount"]."' ,'".$input["order_status"]."','".$input["attachments"]."')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }


else if ($_GET["type"] == "getPendingQuotations") {
    $output = Array();

       $sql= "SELECT q.*, v.vendor_name, v.gst_no FROM quotation_hdr q LEFT JOIN vendor v ON q.vendor_id=v.id WHERE 
      q.status='Pending' and q.plant_id = '".$_GET["plant_id"]."' ORDER BY q.id DESC";     
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $output1 = Array(); 
             
              
             
        $sql1="SELECT distinct q.id ,q.*, v.material_name,v.material_type,v.material_subtype , ve.vendor_no  FROM quotation_dtl q join 
        quotation_hdr qh on q.quotation_hdr_id = qh.id LEFT JOIN my_view v ON qh.plant_id=v.plant_id and q.material_code = v.material_code
        left join vendor ve on ve.id = qh.vendor_id where q.quotation_hdr_id = '".$row['id']."' ";
             
              
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    
                        $output1[] = $row1;
                    }
                    
                }
            
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
    
    echo json_encode($output);
    
}
else if ($_GET["type"] == "getQuotationHdrDetails") {
    header('Content-Type: application/json; charset=utf-8');
    $output = null;
    $id = isset($_GET["id"]) ? $conn->real_escape_string($_GET["id"]) : '';
    $plant_id = isset($_GET["plant_id"]) ? trim($conn->real_escape_string($_GET["plant_id"])) : '';
    if ($plant_id === 'null' || $plant_id === 'undefined') {
        $plant_id = '';
    }

    if ($id !== '' && $plant_id !== '') {
        $sql = "SELECT q.*, v.vendor_name, v.gst_no FROM quotation_hdr q
                LEFT JOIN vendor v ON q.vendor_id = v.id
                WHERE q.id = '".$id."' AND q.plant_id = '".$plant_id."'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $output1 = Array();
            $sql1 = "SELECT distinct q.id, q.*, v.material_name, v.material_type, v.material_subtype, ve.vendor_no
                     FROM quotation_dtl q
                     JOIN quotation_hdr qh ON q.quotation_hdr_id = qh.id
                     LEFT JOIN my_view v ON qh.plant_id = v.plant_id AND q.material_code = v.material_code
                     LEFT JOIN vendor ve ON ve.id = qh.vendor_id
                     WHERE q.quotation_hdr_id = '".$id."'";
            $result1 = $conn->query($sql1);
            if ($result1 && $result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    if (!isset($row1['tax_type']) || $row1['tax_type'] === '' || $row1['tax_type'] === null) {
                        $row1['tax_type'] = 'Local';
                    }
                    $output1[] = $row1;
                }
            }
            $row["materials"] = $output1;
            $quotationNo = trim((string)($row['quotation_no'] ?? ''));
            $vendorQuotationNo = trim((string)($row['vendor_quotation_no'] ?? ''));
            if ($quotationNo === '' && $vendorQuotationNo !== '') {
                $row['quotation_no'] = $vendorQuotationNo;
            }
            $output = $row;
        }
    }

    echo json_encode($output);
}
else if ($_GET["type"] == "HogetPendingQuotations") {
    $output = Array();

       $sql= "SELECT q.*, v.vendor_name, v.email,v.city, v.gst_no FROM quotation_hdr q LEFT JOIN vendor v ON q.vendor_id=v.id WHERE 
      q.status='pending'   ORDER BY q.id DESC";     
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $output1 = Array(); 
             
             
                $sql12="SELECT  plant_name  FROM plant where plant_id = '".$row['plant_id']."' ";
                 $result12 = $conn->query($sql12);
                if ($result12->num_rows > 0) {
                    while ($row12 = $result12->fetch_assoc()) {
                         $row["plant_name"] = $row12["plant_name"];
                    }
                }
            
              
             
        $sql1="SELECT distinct q.id ,q.*, v.material_name,v.material_type,v.material_subtype , ve.vendor_no  FROM quotation_dtl q join 
        quotation_hdr qh on q.quotation_hdr_id = qh.id LEFT JOIN my_view v ON qh.plant_id=v.plant_id and q.material_code = v.material_code
        left join vendor ve on ve.id = qh.vendor_id where q.quotation_hdr_id = '".$row['id']."' ";
             
              
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    
                        $output1[] = $row1;
                    }
                    
                }
            
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
    
    echo json_encode($output);
    
} 
else if ($_GET["type"] == "getPendingQuotationsForNotification") {
    $output = Array();

        $sql= "SELECT count(*) as Pending_quatation  FROM quotation_hdr  where status='Pending' and plant_id = '".$_GET["plant_id"]."' ORDER BY id DESC";     
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
                $output = $row;
            }
        }
        
        $test = "You Have '".$output['Pending_quatation']."' Quotation Pending For Approval";
        $output['text'] = $test;
    
    echo json_encode($output);
    
} 
else if ($_GET["type"] == "getPendingQuotationsForCorrection") {
    $output = Array();

       $sql= "SELECT q.*, v.vendor_name, v.contact_email,v.city, v.gst_no FROM quotation_hdr q LEFT JOIN vendor v ON q.vendor_id=v.id WHERE 
      q.status='reject' and q.plant_id = '".$_GET["plant_id"]."' ORDER BY q.id DESC";     
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $output1 = Array(); 
             
              
             
        $sql1="SELECT distinct q.id ,q.*, v.material_name,v.material_type,v.material_subtype , ve.vendor_no  FROM quotation_dtl q join 
        quotation_hdr qh on q.quotation_hdr_id = qh.id LEFT JOIN my_view v ON qh.plant_id=v.plant_id and q.material_code = v.material_code
        left join vendor ve on ve.id = qh.vendor_id where q.quotation_hdr_id = '".$row['id']."' ";
             
              
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    
                        $output1[] = $row1;
                    }
                    
                }
            
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
    
    echo json_encode($output);
    
} 
else if ($_GET["type"] == "HogetPendingQuotationsForCorrection") {
    $output = Array();

       $sql= "SELECT q.*, v.vendor_name, v.email,v.city, v.gst_no FROM quotation_hdr q LEFT JOIN vendor v ON q.vendor_id=v.id WHERE 
      q.status='reject'  ORDER BY q.id DESC";     
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $output1 = Array();
             
                $sql12="SELECT  plant_name  FROM plant where plant_id = '".$row['plant_id']."' ";
                 $result12 = $conn->query($sql12);
                if ($result12->num_rows > 0) {
                    while ($row12 = $result12->fetch_assoc()) {
                         $row["plant_name"] = $row12["plant_name"];
                    }
                }
                
        $sql1="SELECT distinct q.id ,q.*, v.material_name,v.material_type,v.material_subtype , ve.vendor_no  FROM quotation_dtl q join 
        quotation_hdr qh on q.quotation_hdr_id = qh.id LEFT JOIN my_view v ON qh.plant_id=v.plant_id and q.material_code = v.material_code
        left join vendor ve on ve.id = qh.vendor_id where q.quotation_hdr_id = '".$row['id']."' ";
              
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    
                        $output1[] = $row1;
                    }
                }
            
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
    
    echo json_encode($output);
    
} 
// else if ($_GET["type"] == "updateCorrectQuotation ") {
//     $sql = "UPDATE quotation_hdr SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
//     if ($conn->query($sql)) {
//         $materials = json_decode($input["materials"],true);
            
//             for ($i = 0; $i < count($materials); $i++) {
                
//                 $material = $materials[$i];
        
                
//                 $sql1="update quotation_dtl set quotation_amt='".$material["exp_date"]."',quotation_per='".$material["quotation_per"]."',currency='".$material["currency"]."',tax_type='".$material["tax_type"]."',
//                 gst_per='".$material["gst_per"]."',pack_size='".$material["pack_size"]."',pack_unit='".$material["pack_unit"]."' where id = '".$material["pack_unit"]."'";
//             }
        
        
//         echo "{\"status\":\"success\"}";
//     } else {
//         echo "{\"status\":\"".$conn->error."\"}";
//     }
// }
else if ($_GET["type"] == "updateCorrectQuotation") {
    
    $entry_date = date('Y-m-d H:i:s');
$id = $_GET["id"];
$status = $_GET["status"];
$emp_id = $_GET["emp_id"];
$input = json_decode(file_get_contents('php://input'), true);

$sql = "UPDATE quotation_hdr 
        SET status='$status', approve_by='$emp_id', approve_date='$entry_date' 
        WHERE id='$id'";

if ($conn->query($sql)) {
    $materials = $input["materials"];

    foreach ($materials as $material) {
        $material_id = $material["id"]; // ensure frontend sends this!

        // 🔹 Step 1: Store current version in history
        $history_sql = "
            INSERT INTO quotation_dtl_history1 
            (quotation_dtl_id, quotation_type, quotation_hdr_id, material_id, material_code, Manufacturer,
             quoted_amt, quotation_amt, currency, currency_rate, tax_applicable, tax_type, gst_per,
             pack_size, pack_unit, quotation_per, vendor_material_request_id, validTill, modified_by, action_type)
            SELECT id, quotation_type, quotation_hdr_id, material_id, material_code, Manufacturer,
                   quoted_amt, quotation_amt, currency, currency_rate, tax_applicable, tax_type, gst_per,
                   pack_size, pack_unit, quotation_per, vendor_material_request_id, validTill,
                   '$emp_id', 'UPDATE'
            FROM quotation_dtl
            WHERE id = '$material_id'
        ";
        $conn->query($history_sql);

        // 🔹 Step 2: Update the live record
        $update_sql = "
            UPDATE quotation_dtl 
            SET quotation_amt='" . $material["quotation_amt"] . "',
                quotation_per='" . $material["quotation_per"] . "',
                currency='" . $material["currency"] . "',
                tax_type='" . $material["tax_type"] . "',
                gst_per='" . $material["gst_per"] . "',
                pack_size='" . $material["pack_size"] . "',
                pack_unit='" . $material["pack_unit"] . "',
                validTill='" . $material["validTill"] . "'
            WHERE id = '$material_id'
        ";
        $conn->query($update_sql);
    }

    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => $conn->error]);
}
}
else if ($_GET["type"] == "updateQuotation") {
    $status = isset($_GET["status"]) ? strtolower(trim($_GET["status"])) : '';
    if ($status === 'approve') {
        $status = 'approve';
    } elseif ($status === 'reject') {
        $status = 'reject';
    } else {
        $status = $conn->real_escape_string($_GET["status"]);
    }
    $sql = "UPDATE quotation_hdr SET status='".$status."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date',Reject_remark='".$conn->real_escape_string($_GET["reject_remark"] ?? '')."' WHERE id='".$conn->real_escape_string($_GET["id"])."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
}
else if ($_GET["type"] == "DelQuote") {
    $sql = "DELETE FROM quotation_dtl   WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
 }
 
 
 else if ($_GET["type"] == "getvendorquatationmaterial") {
        
        
     $sql = "SELECT mst.supplier_code , mst.manufacturer_data , mv.* FROM mst_vendor_materials mst left join my_view mv ON mst.material_code = mv.material_code where
    mst.supplier_code = '".$_GET["vendor_no"]."' AND mst.plant_id = '".$_GET["plant_id"]."' ";
    
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['pack_size_unit'] = $row['unit'];
            $row['quotation_per'] = $row['unit'];
            $row['gst'] = $row['gst'];
            $row['tax'] = $row['tax'];
            
            
            
            
            
            
            
                        
                       $output2 = Array();
                     
                         $sql2 = "SELECT quotation_hdr.*,quotation_dtl.*, vendor.vendor_name, vendor.contact_email,vendor.city, 
                vendor.gst_no FROM quotation_dtl LEFT JOIN quotation_hdr ON quotation_dtl.quotation_hdr_id = quotation_hdr.id
                LEFT JOIN vendor ON quotation_hdr.vendor_id=vendor.id WHERE quotation_dtl.material_code ='".$row["material_code"]."' and 
                quotation_hdr.plant_id ='".$_GET["plant_id"]."' ORDER BY quotation_hdr.id DESC";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }  
                        $row['comparison']=$output2;
                    
                         
                    
            
             $output[] = $row;
        }
    }
        
       echo json_encode($output);
}
 else if ($_GET["type"] == "getgeneralMaterial") {
        
        
    $sql = "SELECT * FROM others_material where   plant_id = '".$_GET["plant_id"]."' ";
     
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
        
    echo json_encode($output);
}
 else if ($_GET["type"] == "Hogetvendorquatationmaterial") {
        
        
     $sql = "SELECT mst.supplier_code , mst.manufacturer_data , mv.* FROM mst_vendor_materials mst left join my_view mv ON mst.material_code = mv.material_code where
    mst.supplier_code = '".$_GET["vendor_no"]."'   ";
    
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['manufacturer_data'] = json_decode($row['manufacturer_data']);
            $output[] = $row;
        }
    }
        
       echo json_encode($output);
}

 
 
 
else if ($_GET["type"] == "getQuotationLog") {
    $output = Array();
    
    
if($_GET["logic"] == 2){
        
       $sql= "SELECT q.*, v.vendor_name,v.gst_no,
    (select firstname from employee e WHERE  e.emp_id=q.entry_by AND e.plant_id = '".$_GET["plant_id"]."' LIMIT 1) as entry_by_name,
    (select firstname from employee e  WHERE  e.emp_id=q.approve_by AND e.plant_id = '".$_GET["plant_id"]."' LIMIT 1) as approve_by_name FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status !='reject' and q.plant_id = '".$_GET["plant_id"]."' and q.material_type = '".$_GET["category"]."' 
    ORDER BY q.id DESC";  
    
    }else if($_GET["logic"] == 3){           
      $sql= "SELECT q.*, v.vendor_name, v.gst_no ,
         (select firstname from employee e WHERE  e.emp_id=q.entry_by AND e.plant_id = '".$_GET["plant_id"]."' LIMIT 1) as entry_by_name,
    (select firstname from employee e WHERE   e.emp_id=q.approve_by AND e.plant_id = '".$_GET["plant_id"]."' LIMIT 1) as approve_by_name FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status != 'reject' and q.plant_id = '".$_GET["plant_id"]."' and
    ( q.material_type LIKE '%".$_GET["category"]."%' OR v.vendor_name LIKE '%".$_GET["category"]."%'  OR q.quotation_no LIKE '%".$_GET["category"]."%' )
    ORDER BY q.id DESC";  
    }else if($_GET["logic"] == "all" || $_GET["logic"] == "4"){
       $sql= "SELECT q.*, v.vendor_name,  v.gst_no ,
        (select firstname from employee e WHERE  e.emp_id=q.entry_by AND e.plant_id = '".$_GET["plant_id"]."' LIMIT 1) as entry_by_name,
    (select firstname from employee e WHERE  e.emp_id=q.approve_by AND e.plant_id = '".$_GET["plant_id"]."' LIMIT 1) as approve_by_name FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status != 'reject' and q.plant_id = '".$_GET["plant_id"]."' 
    ORDER BY q.id DESC";  
    }
    else{
       $sql= "SELECT q.*, v.vendor_name,  v.gst_no ,
        (select firstname from employee e WHERE  e.emp_id=q.entry_by AND e.plant_id = '".$_GET["plant_id"]."' LIMIT 1) as entry_by_name,
    (select firstname from employee e WHERE  e.emp_id=q.approve_by AND e.plant_id = '".$_GET["plant_id"]."' LIMIT 1) as approve_by_name FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status ='approve' and q.plant_id = '".$_GET["plant_id"]."' 
    ORDER BY q.id DESC";  
        
    }
    
     
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $output1 = Array();
             $output11 = Array();
             
              $sql1="SELECT distinct q.id ,q.*, v.material_name,v.material_type,v.material_subtype , ve.vendor_no  FROM quotation_dtl q join 
        quotation_hdr qh on q.quotation_hdr_id = qh.id LEFT JOIN my_view v ON qh.plant_id=v.plant_id and q.material_code = v.material_code
        left join vendor ve on ve.id = qh.vendor_id where q.quotation_hdr_id = '".$row['id']."' ";
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row['check'] = false;
                        $row1['newQuotation_amt'] = $row1['quotation_amt'] ;
                        $row1['newValidTill'] = $row1['validTill'] ;
                        $output1[] = $row1;
                    }
                }
                
              $sql11="SELECT distinct q.id ,q.*, v.material_name,v.material_type,v.material_subtype , ve.vendor_no  FROM quotation_dtl_history q join 
        quotation_hdr qh on q.quotation_hdr_id = qh.id LEFT JOIN my_view v ON qh.plant_id=v.plant_id and q.material_code = v.material_code
        left join vendor ve on ve.id = qh.vendor_id where q.quotation_hdr_id = '".$row['id']."' ";
                 $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $output11[] = $row11;
                    }
                }
            
                $row["history"] = $output11;
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
    echo json_encode($output);
} 
else if ($_GET["type"] == "HOgetQuotationLog") {
    $output = Array();
    
    
if($_GET["logic"] == 2){
        
  echo   $sql= "SELECT q.*, v.vendor_name, v.email,v.city, v.gst_no,
    (select firstname from employee e WHERE  e.emp_id=q.entry_by AND  e.plant_id =  q.plant_id  ) as entry_by_name,
    (select firstname from employee e  WHERE  e.emp_id=q.approve_by AND e.plant_id =  q.plant_id  ) as approve_by_name FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status !='reject'   and q.material_type = '".$_GET["category"]."' 
    ORDER BY q.id DESC";  
    
    }else if($_GET["logic"] == 3){           
     $sql= "SELECT q.*, v.vendor_name, v.email,v.city, v.gst_no ,
         (select firstname from employee e WHERE  e.emp_id=q.entry_by AND e.plant_id =  q.plant_id ) as entry_by_name,
    (select firstname from employee e WHERE   e.emp_id=q.approve_by AND e.plant_id =  q.plant_id ) as approve_by_name FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status != 'reject'   and
    ( q.material_type LIKE '%".$_GET["category"]."%' OR v.vendor_name LIKE '%".$_GET["category"]."%'  OR q.quotation_no LIKE '%".$_GET["category"]."%' )
    ORDER BY q.id DESC";  
    }    else{
     $sql= "SELECT q.*, v.vendor_name, v.email,v.city, v.gst_no ,
        (select firstname from employee e WHERE  e.emp_id=q.entry_by AND e.plant_id =  q.plant_id   ) as entry_by_name,
    (select firstname from employee e WHERE  e.emp_id=q.approve_by AND e.plant_id =  q.plant_id  ) as approve_by_name FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status !='reject'   
    ORDER BY q.id DESC";  
        
    }
    
     
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $output1 = Array();
             
              $sql1="SELECT distinct q.id ,q.*, v.material_name,v.material_type,v.material_subtype , ve.vendor_no  FROM quotation_dtl q join 
        quotation_hdr qh on q.quotation_hdr_id = qh.id LEFT JOIN my_view v ON qh.plant_id=v.plant_id and q.material_code = v.material_code
        left join vendor ve on ve.id = qh.vendor_id where q.quotation_hdr_id = '".$row['id']."' ";
       
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    
                        $output1[] = $row1;
                    }
                    
                }
               $sql12="SELECT  plant_name  FROM plant where plant_id = '".$row['plant_id']."' ";
       
                 $result12 = $conn->query($sql12);
                if ($result12->num_rows > 0) {
                    while ($row12 = $result12->fetch_assoc()) {
                    
                         $row["plant_name"] = $row12["plant_name"];
                    }
                    
                }
            
                $row["materials"] = $output1;
                
                $output[] = $row;
            }
        }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getQuotationByVendor") {
    $output = Array();
      $sql= "SELECT q.plant_id, v.id,v.vendor_name,v.vendor_no,v.vendor_type FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status='approve' and q.plant_id = '".$_GET["plant_id"]."' 
    ORDER BY q.id DESC";  
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $output1 = Array();
             $sql1="SELECT q.*,v.vendor_name,v.vendor_no,v.vendor_type FROM quotation_hdr q LEFT JOIN vendor v ON q.vendor_id=v.id
             WHERE q.status='approve' and q.vendor_id = '".$row['id']."' ORDER BY id DESC";
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                     $output2 = Array();
                     $sql1="SELECT distinct q.*, v.material_name,v.material_type,v.material_subtype FROM quotation_dtl q LEFT JOIN master_material v 
                     ON q.material_id=v.id and q.material_code = v.material_code where q.quotation_hdr_id = '".$row1['id']."' ";
                         $result2 = $conn->query($sql1);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                                $output2[] = $row2;
                            }
                            
                        }
                    
                        $row1["materials"] = $output2;
                        $output1[] = $row1;
                    }
                    
                }
            
                $row['quatation'] = $output1;
                $output[] = $row;
            }
        }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getQuotationBymat") {
    $output = Array();
      $sql= "SELECT q.plant_id, v.id,v.vendor_name,v.vendor_no,v.vendor_type FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status='approve' and q.plant_id = '".$_GET["plant_id"]."' 
    ORDER BY q.id DESC";  
    // $sql= "SELECT q.plant_id, v.id,v.vendor_name,v.vendor_no,v.vendor_type FROM quotation_hdr q 
    // LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status='approve' and q.plant_id = '".$_GET["plant_id"]."' 
    // ORDER BY q.id DESC";
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $output1 = Array();
             $sql1="SELECT q.*,v.vendor_name,v.vendor_no,v.vendor_type FROM quotation_hdr q LEFT JOIN vendor v ON q.vendor_id=v.id
             WHERE q.status='approve' and q.vendor_id = '".$row['id']."' ORDER BY id DESC";
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                     $output2 = Array();
                     $sql1="SELECT distinct q.*, v.material_name,v.material_type,v.material_subtype FROM quotation_dtl q LEFT JOIN master_material v 
                     ON q.material_id=v.id and q.material_code = v.material_code where q.quotation_hdr_id = '".$row1['id']."' ";
                         $result2 = $conn->query($sql1);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                                $output2[] = $row2;
                            }
                            
                        }
                    
                        $row1["materials"] = $output2;
                        $output1[] = $row1;
                    }
                    
                }
            
                $row['quatation'] = $output1;
                $output[] = $row;
            }
        }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getQuotationByVendor") {
    $output = Array();
      $sql= "SELECT q.plant_id, v.id,v.vendor_name,v.vendor_no,v.vendor_type FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status='approve' and q.plant_id = '".$_GET["plant_id"]."' 
    ORDER BY q.id DESC";  
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $output1 = Array();
             $sql1="SELECT q.*,v.vendor_name,v.vendor_no,v.vendor_type FROM quotation_hdr q LEFT JOIN vendor v ON q.vendor_id=v.id
             WHERE q.status='approve' and q.vendor_id = '".$row['id']."' ORDER BY id DESC";
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                     $output2 = Array();
                     $sql1="SELECT distinct q.*, v.material_name,v.material_type,v.material_subtype FROM quotation_dtl q LEFT JOIN master_material v 
                     ON q.material_id=v.id and q.material_code = v.material_code where q.quotation_hdr_id = '".$row1['id']."' ";
                         $result2 = $conn->query($sql1);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                                $output2[] = $row2;
                            }
                            
                        }
                    
                        $row1["materials"] = $output2;
                        $output1[] = $row1;
                    }
                    
                }
            
                $row['quatation'] = $output1;
                $output[] = $row;
            }
        }
    echo json_encode($output);
} 
else if ($_GET["type"]=="getApproveQuotations") {
    if($_GET['from'] == '' && $_GET['to'] == ''){
        $sql = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.status='approve'";
    }else{
        $sql = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE
        q.user_no='".$_GET["user_no"]."'AND vendor_no LIKE '%".$_GET["vendor_no"]."%' AND q.entry_date BETWEEN '" . $_GET['from'] . "' AND  '" . $_GET['to'] . "' AND q.status='approve'";
    }
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $materials = json_decode($row["materials"]);
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql2 = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND material_code='".$material->material_code."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $material->material_name = $row2["material_name"];
                        $material->material_type = $row2["material_type"];
                        $material->material_subtype = $row2["material_subtype"];
                        $material->grade = $row2["grade"];
                        $materials[$i] = $material;
                    }
                }
            }
            $row["materials"] = $materials;
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

else if ($_GET["type"] == "getComparatives") {
    header('Content-Type: application/json; charset=utf-8');

    $plant_id = isset($_GET["plant_id"]) ? trim($conn->real_escape_string($_GET["plant_id"])) : '';
    $category = isset($_GET["quotation_category"]) ? $_GET["quotation_category"] : 'Quotation';
    if ($category !== 'General' && $category !== 'Quotation') {
        $category = 'Quotation';
    }

    if ($plant_id === '' || $plant_id === 'null' || $plant_id === 'undefined') {
        echo json_encode(array());
        exit;
    }

    echo json_encode(buildComparativesList($conn, $plant_id, $category, false));
}
else if ($_GET["type"] == "getComparativesPending") {
    header('Content-Type: application/json; charset=utf-8');

    $plant_id = isset($_GET["plant_id"]) ? trim($conn->real_escape_string($_GET["plant_id"])) : '';
    $category = isset($_GET["quotation_category"]) ? $_GET["quotation_category"] : 'Quotation';
    if ($category !== 'General' && $category !== 'Quotation') {
        $category = 'Quotation';
    }

    if ($plant_id === '' || $plant_id === 'null' || $plant_id === 'undefined') {
        echo json_encode(array());
        exit;
    }

    echo json_encode(buildComparativesList($conn, $plant_id, $category, true));
}
else if ($_GET["type"] == "getQuotationsForApprovalByMaterial") {
    header('Content-Type: application/json; charset=utf-8');

    $output = array();
    $plant_id = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : '';

    if ($plant_id === '') {
        echo json_encode($output);
        exit;
    }

    $sql = "SELECT DISTINCT m.*
            FROM material m
            INNER JOIN quotation_dtl qd ON qd.material_code = m.material_code
            INNER JOIN quotation_hdr qh ON qh.id = qd.quotation_hdr_id
            WHERE m.plant_id = '".$plant_id."'
              AND qh.plant_id = '".$plant_id."'
              AND UPPER(qh.status) = 'PENDING'
            ORDER BY m.id DESC";

    $result = $conn->query($sql);
    if (!$result) {
        echo json_encode($output);
        exit;
    }

    while ($row = $result->fetch_assoc()) {
        $output1 = array();

        $taxTypeSelect = columnExists($conn, 'quotation_dtl', 'tax_type')
            ? "IFNULL(NULLIF(quotation_dtl.tax_type, ''), 'Local') AS tax_type,"
            : "'Local' AS tax_type,";
        $validTillSelect = columnExists($conn, 'quotation_dtl', 'validTill')
            ? "quotation_dtl.validTill,"
            : "";

        $sql1 = "SELECT
                    quotation_hdr.id AS quotation_hdr_id,
                    quotation_hdr.vendor_quotation_no,
                    quotation_hdr.vendor_quotation_date,
                    quotation_hdr.entry_date,
                    quotation_hdr.status,
                    quotation_hdr.doc_url,
                    quotation_hdr.approve_by,
                    quotation_hdr.approve_date,
                    quotation_hdr.material_type,
                    quotation_dtl.id AS quotation_dtl_id,
                    quotation_dtl.material_code,
                    quotation_dtl.quotation_amt,
                    quotation_dtl.quotation_per,
                    quotation_dtl.gst_per,
                    ".$taxTypeSelect."
                    quotation_dtl.currency,
                    quotation_dtl.pack_size,
                    quotation_dtl.pack_unit,
                    ".$validTillSelect."
                    vendor.vendor_name,
                    vendor.vendor_no,
                    vendor.contact_email,
                    vendor.city,
                    vendor.gst_no
                 FROM quotation_dtl
                 LEFT JOIN quotation_hdr ON quotation_dtl.quotation_hdr_id = quotation_hdr.id
                 LEFT JOIN vendor ON quotation_hdr.vendor_id = vendor.id
                 WHERE quotation_dtl.material_code = '".$conn->real_escape_string($row["material_code"])."'
                   AND quotation_hdr.plant_id = '".$plant_id."'
                 ORDER BY quotation_hdr.id DESC";

        $result1 = $conn->query($sql1);
        if ($result1 && $result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1[] = $row1;
            }
        }

        if (count($output1) > 0) {
            $pendingCount = 0;
            foreach ($output1 as $qRow) {
                if (strtoupper($qRow["status"] ?? '') === 'PENDING') {
                    $pendingCount++;
                }
            }
            $row["quotationDetails"] = $output1;
            $row["pendingCount"] = $pendingCount;
            $output[] = $row;
        }
    }

    echo json_encode($output);
}
else if ($_GET["type"] == "HOgetComparatives") {
    $output = Array();
       $sql = "SELECT * FROM material WHERE material_code in(select material_code FROM quotation_dtl ) ORDER BY id DESC ";
        $result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                       $q = "SELECT GROUP_CONCAT(grade) AS gradeName FROM grade WHERE id IN ('".$row['grade']."')";
                    $resQ = $conn->query($q);
                    $prodLatest = $resQ->fetch_assoc();
                    $row['gradeName'] = $prodLatest['gradeName'];
                    
                     $output1 = Array();
                     
                         $sql1 = "SELECT quotation_hdr.*,quotation_dtl.*, vendor.vendor_name, vendor.email,vendor.city, 
                vendor.gst_no FROM quotation_dtl LEFT JOIN quotation_hdr ON quotation_dtl.quotation_hdr_id = quotation_hdr.id
                LEFT JOIN vendor ON quotation_hdr.vendor_id=vendor.id WHERE quotation_dtl.material_code ='".$row["material_code"]."'  ORDER BY quotation_hdr.id DESC";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }                      

                        $row["quotationDetails"] = $output1;
                        
                        
                $sql12="SELECT  plant_name  FROM plant where plant_id = '".$row['plant_id']."' ";
                $result12 = $conn->query($sql12);
                if ($result12->num_rows > 0) {
                    while ($row12 = $result12->fetch_assoc()) {
                         $row["plant_name"] = $row12["plant_name"];
                    }
                }
                        
                        
                        
                        $output[] = $row;
                }
            }
         
        echo json_encode($output);
    
} 
else if ($_GET["type"] == "getComparativesByMaterial") {
    $output = Array();
       $sql = "SELECT * FROM material WHERE material_code = '".$_GET["material_code"]."'  ";
        $result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                   
                    
                     $output1 = Array();
                     
       $sql1 = "SELECT quotation_hdr.*,quotation_dtl.*, vendor.vendor_name, vendor.email,vendor.city, 
                vendor.gst_no FROM quotation_dtl LEFT JOIN quotation_hdr ON quotation_dtl.quotation_hdr_id = quotation_hdr.id
                LEFT JOIN vendor ON quotation_hdr.vendor_id=vendor.id WHERE quotation_dtl.material_code ='".$row["material_code"]."' and 
                vendor.plant_id =('".$_GET["plant_id"]."' or '".$_GET["plantID"]."') ORDER BY quotation_hdr.id DESC";
                //         echo $sql1 = "SELECT quotation_hdr.*,quotation_dtl.*, vendor.vendor_name, vendor.email,vendor.city, 
                // vendor.gst_no FROM quotation_dtl LEFT JOIN quotation_hdr ON quotation_dtl.quotation_hdr_id = quotation_hdr.id
                // LEFT JOIN vendor ON quotation_hdr.vendor_id=vendor.id WHERE quotation_dtl.material_code ='".$row["material_code"]."' and 
                // quotation_hdr.plant_id =('".$_GET["plant_id"]."' or '".$_GET["plantID"]."') ORDER BY quotation_hdr.id DESC";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }                      

                        $row["quotationDetails"] = $output1;
                        $output[] = $row;
                }
            }
         
        echo json_encode($output);
    
} 
else if($_GET['type'] == 'downloadQuotationLog'){
        $_GET['filename'] = 'Quotation Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Quotation Log</h2>
        <table cellpadding="5" border="1">
            <tr style="background-color:#DDDAD9;">
                <td style="width:5%;"><b>Sr No.</b></td>
                <td style="width:15%;"><b>Quotation No</b></td>
                <td style="width:15%;"><b>Material Type</b></td>
                <td style="width:25%;"><b>Vendor Name</b></td>
                <td style="width:20%;"><b>No of Material</b></td>
                <td style="width:20%;"><b>Quotation Date</b></td>
            </tr>';

    $j=1;
    $sql= "SELECT q.*, v.vendor_name, v.contact_email,v.city, v.gst_no FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status='approve' and q.plant_id = '".$_GET["plant_id"]."' 
    ORDER BY q.id DESC";     
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
             $sql1="SELECT q.*, v.material_name,v.material_type,v.material_subtype FROM quotation_dtl q 
             LEFT JOIN material v 
                      ON q.material_id=v.id and q.material_code = v.material_code
                      where q.quotation_hdr_id = '".$row['id']."' ";
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                
            //     if ($material->material_type == 'Chemicals') {
            //         $sql1 = "SELECT * FROM chemical WHERE chemical_no='".$material->material_code."'";
            //         $result1 = $conn->query($sql1);
            //         if ($result1->num_rows > 0) {
            //             while ($row1 = $result1->fetch_assoc()) {
            //                 $material->material_name = $row1["chemical_name"];
            //                 $material->grade = $row1["grade"];
            //             }
            //         }
            //     } else if ($material->material_type == 'Glasswares') {
            //         $sql1 = "SELECT * FROM glassware WHERE glassware_no='".$material->material_code."'";
            //         $result1 = $conn->query($sql1);
            //         if ($result1->num_rows > 0) {
            //             while ($row1 = $result1->fetch_assoc()) {
            //                 $material->material_name = $row1["description"];
            //             }
            //         }
            //     } else if ($material->material_type == 'General Material') {
            //         $sql1 = "SELECT * FROM general_material WHERE material_code='".$material->material_code."'";
            //         $result1 = $conn->query($sql1);
            //         if ($result1->num_rows > 0) {
            //             while ($row1 = $result1->fetch_assoc()) {
            //                 $material->material_name = $row1["material_name"];
            //             }
            //         }
            //     } else {
            //         $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
            //         $result1 = $conn->query($sql1);
            //         if ($result1->num_rows > 0) {
            //             while ($row1 = $result1->fetch_assoc()) {
            //                 $material->material_name = $row1["material_name"];
            //                 $material->grade = $row1["grade"];
            //             }
            //         }
            //     }
            }
            $output[] = $row;
             $html.='
             <tr nobr="true">
                <td style="width: 5%;"></td>
                <td style="width: 15%;">'.$row['quotation_no'].'</td>
                <td style="width: 15%;">'.$row['material_type'].'</td>
                <td style="width: 25%;">'.$row['vendor_name'].'</td>
                <td style="width: 20%;">'.$row['vendor_id'].'</td>
                <td style="width: 20%;">'.date('d-m-Y ', strtotime($row['entry_date'])).'</td>
            </tr>';

            }
        }
    }
    $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('QuotationLog.pdf', 'I');
}
    // }
           
    
}else {
    echo "{\"status\":\"invalid\"}";
}
 }catch (\Throwable $e) {
               echo "{\"statuse\":\"".$e."\"}";
             //	echo "{\"status\":\"exception\"}";
            }
$conn->close();
?>