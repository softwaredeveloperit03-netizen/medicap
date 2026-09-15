<?php
// ini_set('display_errors', 1);
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

function medicap_map_unitformula_as_bfr($uf) {
    $mapped = $uf;
    if (empty($mapped['bfr_no'])) {
        $mapped['bfr_no'] = 'BFR-UF-'.($uf['id'] ?? '');
    }
    if (!isset($mapped['batch_formula_weight']) || $mapped['batch_formula_weight'] === '' || $mapped['batch_formula_weight'] === null) {
        $mapped['batch_formula_weight'] = $uf['batch_size'] ?? ($uf['bom_batch_size'] ?? '');
    }
    if (empty($mapped['entry_date'])) {
        $mapped['entry_date'] = $uf['approve_date'] ?? '';
    }
    return $mapped;
}

function medicap_load_packing_configuration($conn, $plantId, $mfrNo, $productCode, $ufId = '') {
    $output_pm = array();
    $uid = intval($ufId);
    if ($uid < 1) {
        $plantEsc = mysqli_real_escape_string($conn, trim((string)$plantId));
        $mfrEsc = mysqli_real_escape_string($conn, trim((string)$mfrNo));
        $codeEsc = mysqli_real_escape_string($conn, trim((string)$productCode));
        $sqlUf = "SELECT id FROM unitformula WHERE 1=1";
        if ($plantEsc !== '') {
            $sqlUf .= " AND TRIM(IFNULL(plant_id,''))='".$plantEsc."'";
        }
        if ($mfrEsc !== '') {
            $sqlUf .= " AND TRIM(IFNULL(mfr_no,''))='".$mfrEsc."'";
        }
        if ($codeEsc !== '') {
            $sqlUf .= " AND TRIM(IFNULL(product_code,''))='".$codeEsc."'";
        }
        $sqlUf .= " ORDER BY id DESC LIMIT 1";
        $ufRes = $conn->query($sqlUf);
        if ($ufRes && ($ufRow = $ufRes->fetch_assoc())) {
            $uid = intval($ufRow['id']);
        }
    }
    if ($uid < 1) {
        return $output_pm;
    }
    $sql_pm = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,product_brand_name,
        pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$uid."' ";
    $result_pm = $conn->query($sql_pm);
    if ($result_pm && $result_pm->num_rows > 0) {
        while ($row_pm = $result_pm->fetch_assoc()) {
            $output_pm2 = array();
            $dtl_esc = mysqli_real_escape_string($conn, $row_pm["id"]);
            $sql_pm2 = "SELECT a.*, b.material_name, b.material_subtype
                from unitformula_packing_materials a
                left join material b on a.material_code=b.material_code
                where a.unit_formula_dtl_id ='".$dtl_esc."' ";
            $result_pm2 = $conn->query($sql_pm2);
            if ($result_pm2 && $result_pm2->num_rows > 0) {
                while ($row_pm2 = $result_pm2->fetch_assoc()) {
                    if (empty($row_pm2['material_name']) && !empty($row_pm2['material_name1'])) {
                        $row_pm2['material_name'] = $row_pm2['material_name1'];
                    }
                    if (empty($row_pm2['unit']) && !empty($row_pm2['unit_name'])) {
                        $row_pm2['unit'] = $row_pm2['unit_name'];
                    }
                    if (!isset($row_pm2['batch_qty']) || $row_pm2['batch_qty'] === '' || $row_pm2['batch_qty'] === null) {
                        $row_pm2['batch_qty'] = $row_pm2['total_qty'] ?? '';
                    }
                    if (isset($row_pm2['grade']) && $row_pm2['grade'] !== '' && $row_pm2['grade'] !== null) {
                        $g_esc = mysqli_real_escape_string($conn, $row_pm2['grade']);
                        $qG = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade where id in ('".$g_esc."')";
                        $resG = $conn->query($qG);
                        if ($resG && $gRow = $resG->fetch_assoc()) {
                            $row_pm2['gradeName'] = $gRow['gradeName'];
                        }
                    }
                    $output_pm2[] = $row_pm2;
                }
            }
            $row_pm['packing_materials'] = $output_pm2;
            $row_pm['packing_list'] = $output_pm2;
            $output_pm[] = $row_pm;
        }
    }
    return $output_pm;
}

function medicap_flatten_packing_lines($packCfg) {
    $flat = array();
    if (!is_array($packCfg)) {
        return $flat;
    }
    foreach ($packCfg as $cfg) {
        $lines = $cfg['packing_materials'] ?? ($cfg['packing_list'] ?? array());
        if (!is_array($lines)) { continue; }
        foreach ($lines as $pm) {
            $flat[] = $pm;
        }
    }
    return $flat;
}

function medicap_attach_packing_to_bfr($conn, $row, $ufId = '') {
    $packCfg = medicap_load_packing_configuration(
        $conn,
        $row['plant_id'] ?? '',
        $row['mfr_no'] ?? '',
        $row['product_code'] ?? '',
        $ufId
    );
    $row['packing_configuration'] = $packCfg;
    $flat = medicap_flatten_packing_lines($packCfg);
    if (empty($row['packing_materials']) || $row['packing_materials'] === '[]' || $row['packing_materials'] === 'null') {
        $row['packing_materials'] = $flat;
    } else if (is_string($row['packing_materials'])) {
        $decoded = json_decode($row['packing_materials'], true);
        if (!is_array($decoded) || count($decoded) === 0) {
            $row['packing_materials'] = $flat;
        } else {
            $row['packing_materials'] = $decoded;
        }
    }
    return $row;
}

function medicap_query_bfr_rows($conn, $plantId, $mfrNo, $productCode) {
    $rows = array();
    $plantEsc = mysqli_real_escape_string($conn, trim((string)$plantId));
    $mfrEsc = mysqli_real_escape_string($conn, trim((string)$mfrNo));
    $codeEsc = mysqli_real_escape_string($conn, trim((string)$productCode));
    $match = array();
    if ($mfrEsc !== '') {
        $match[] = "TRIM(IFNULL(mfr_no,''))='".$mfrEsc."'";
    }
    if ($codeEsc !== '') {
        $match[] = "TRIM(IFNULL(product_code,''))='".$codeEsc."'";
    }
    if (count($match) === 0) {
        return $rows;
    }
    $sql = "SELECT * FROM batch_formula_info WHERE (".implode(' OR ', $match).")";
    if ($plantEsc !== '') {
        $sql .= " AND TRIM(IFNULL(plant_id,''))='".$plantEsc."'";
    }
    $sql .= " ORDER BY id DESC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function medicap_insert_bfr_from_unitformula($conn, $uf) {
    $plantEsc = $conn->real_escape_string($uf['plant_id'] ?? '');
    $countRes = $conn->query("SELECT COUNT(*) + 1 AS count FROM batch_formula_info WHERE plant_id='".$plantEsc."'");
    $next = 1;
    if ($countRes && ($cRow = $countRes->fetch_assoc())) {
        $next = intval($cRow['count']);
    }
    if ($next < 1) { $next = 1; }
    $BFR_NO = "BFR".str_pad($next, 4, "0", STR_PAD_LEFT);
    $ptype = $conn->real_escape_string($uf['product_type'] ?? '');
    $batchSize = $conn->real_escape_string($uf['batch_size'] ?? ($uf['bom_batch_size'] ?? ''));
    $batchUnit = $conn->real_escape_string($uf['unit'] ?? ($uf['bom_batch_size_unit'] ?? ''));
    $rawJson = $conn->real_escape_string($uf['raw_materials'] ?? '');
    $packJson = $conn->real_escape_string($uf['packing_materials'] ?? '');
    $entryBy = $conn->real_escape_string($uf['entry_by'] ?? ($uf['approve_by'] ?? ''));
    $mfrEsc = $conn->real_escape_string($uf['mfr_no'] ?? '');
    $codeEsc = $conn->real_escape_string($uf['product_code'] ?? '');
    $now = date("Y-m-d H:i:s");
    $ins = "INSERT INTO batch_formula_info
        (plant_id, mfr_no, bfr_no, product_type, product_code,
         unit_formula_batch_weight, batch_formula_weight, raw_materials, packing_materials,
         status, entry_by, entry_date, rm_batch_size_unit)
        VALUES (
        '".$plantEsc."', '".$mfrEsc."', '".$BFR_NO."', '".$ptype."', '".$codeEsc."',
        '".$batchSize."', '".$batchSize."', '".$rawJson."', '".$packJson."',
        'For QA Approval', '".$entryBy."', '".$now."', '".$batchUnit."')";
    return $conn->query($ins);
}

function medicap_get_bfr_records($conn, $plantId, $mfrNo, $productCode, $createIfMissing = false) {
    $existing = medicap_query_bfr_rows($conn, $plantId, $mfrNo, $productCode);
    $rows = array();
    if (count($existing) > 0) {
        $rows = $existing;
    } else {
        $plantEsc = mysqli_real_escape_string($conn, trim((string)$plantId));
        $mfrEsc = mysqli_real_escape_string($conn, trim((string)$mfrNo));
        $codeEsc = mysqli_real_escape_string($conn, trim((string)$productCode));
        if ($mfrEsc === '' && $codeEsc === '') {
            return array();
        }
        if ($mfrEsc !== '' && $codeEsc !== '') {
            $ufMatch = "TRIM(IFNULL(mfr_no,''))='".$mfrEsc."' AND TRIM(IFNULL(product_code,''))='".$codeEsc."'";
        } else if ($mfrEsc !== '') {
            $ufMatch = "TRIM(IFNULL(mfr_no,''))='".$mfrEsc."'";
        } else {
            $ufMatch = "TRIM(IFNULL(product_code,''))='".$codeEsc."'";
        }
        $sql = "SELECT * FROM unitformula WHERE (".$ufMatch.")
            AND approve_by IS NOT NULL AND TRIM(approve_by) <> ''
            AND LOWER(IFNULL(status,'')) NOT IN ('reject','rejected')";
        if ($plantEsc !== '') {
            $sql .= " AND TRIM(IFNULL(plant_id,''))='".$plantEsc."'";
        }
        $sql .= " ORDER BY id DESC";
        $result = $conn->query($sql);
        $ufs = array();
        if ($result && $result->num_rows > 0) {
            while ($uf = $result->fetch_assoc()) {
                $ufs[] = $uf;
            }
        }
        if ($createIfMissing) {
            foreach ($ufs as $uf) {
                medicap_insert_bfr_from_unitformula($conn, $uf);
            }
            $created = medicap_query_bfr_rows($conn, $plantId, $mfrNo, $productCode);
            if (count($created) > 0) {
                $rows = $created;
            }
        }
        if (count($rows) === 0) {
            foreach ($ufs as $uf) {
                $rows[] = medicap_map_unitformula_as_bfr($uf);
            }
        }
    }
    foreach ($rows as $i => $r) {
        $rows[$i] = medicap_attach_packing_to_bfr($conn, $r);
    }
    return $rows;
}




    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
//      if ($_GET["type"] == "save_batch_formula ") {

   
//         $rows_count=0;
//           $sql = "Select count(*) as count from batch_formula_info where  plant_id = '".$_GET["plant_id"]."' 
//         and batch_formula_weight = '".$input["rm_batch_size_formula"]."' AND product_code = '".$input["product_code"]."' ";
//         $result = $conn->query($sql);
//         while($row = $result->fetch_assoc()){
//               $rows_count = $row['count'];
//         }
//         if($rows_count>0){
//               echo "{\"status\":\"Batch Size Already Exists\"}";
//         }else{
//             $last_id=0;
//             $sql = "Select count(*)+1 as count from batch_formula_info where  plant_id = '".$_GET["plant_id"]."' ";
//             $result = $conn->query($sql);
//             while($row = $result->fetch_assoc()){
//                   $last_id = $row['count'];
//             }
//             if($last_id==0){
//                 $last_id=1;
//             }
//             $length = 4;
//             $number = substr(str_repeat(0, $length).$last_id, - $length);
//             $BFR_NO =  "BFR".$number;
//             $sql = "INSERT INTO batch_formula_info (plant_id,mfr_no,bfr_no, product_type,product_code, 
//             unit_formula_batch_weight,
//             batch_formula_weight, raw_materials,packing_materials, bfr_type, version_no, effective_date, status, 
//             entry_by,rm_batch_size_unit,batch_formula_weight_unit) VALUES (
//     	    '".$_GET["plant_id"]."','".$input["mfr_no"]."','".$BFR_NO."','".$input["product_type"]."','".$input["product_code"]."',
//     	    '".$input["batch_size"]."','".$input["rm_batch_size_formula"]."','".json_encode($input["raw_materials"])."',
//     	    '".json_encode($input["packing_materials"])."',  '".$input["bfr_type"]."', '".$input["version_no"]."',
//     	    '".$input["effective_date"]."','pending','".$_GET["emp_id"]."','".$input["rm_batch_size_unit"]."',,'".$input["batch_size_unit"]."')";
//     	  //  echo $sql;
//     	    if ($conn->query($sql)) {
//     	         $parent_id = $conn->insert_id;
    	         
//     	                 $json_obj = json_encode($input["selectedPackDataList"]);
//               $array = json_decode($json_obj, true);
//                  $k=1;
//                 foreach ($array as $values)
//                 {
//   $sql1 = "INSERT INTO batch_formula_info_packsizes  (batch_formula_info_id, batchSizeKG, batchSizeNOS, pack_sizes )
//       VALUES ( '$parent_id','".$values["batchSizeKG"]."','".$values["batchSizeNOS"]."','".$values["pack_sizes"]."' )";
//         if ($conn->query($sql1)) {
//              $child_id = $conn->insert_id;
//                  for ($i = 0; $i < count($values['materials']); $i++) {
//                     $material = $values['materials'][$i];
//                      $sql2="Insert into batch_materials(batch_formula_info_packsizes_id,plant_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,bfr_no,grade,
//                     yeild_contribution,role,process_step,stage,split_into_lots,factor,strength) values('$child_id',
//                       '".$_GET["plant_id"]."','Raw Material','".$material["material_code"]."','".$material["qty"]."','".$material["unit"]."',
//                         '".$material["overages_per"]."','".$material["qty_overages_qty"]."','".$material["batch_qty"]."','$BFR_NO','".$material["grade"]."',
//                         '".$material["yeild_contribution"]."','".$material["role"]."','".$material["process_step"]."','".$material["stage"]."',
//                         '".$material["split_into_lots"]."','".$material["factor"]."','".$material["strength"]."')";
//                       //  echo $sql2;
//                     $conn->query($sql2);
                    
//                 }
//              $status1 = true;
//         } else {
//             $status1 = false;
//         }
                    
//                 }
    	         
    	        
    	        
    	        
    	        
    	        
    	        
    	       
    	        
           
                
//                 $materials = json_decode(json_encode($input["packing_materials"]),true);
//                 for ($j = 0; $j < count($materials); $j++) {
//                     $material_array = $materials[$j];
//                     $material_list=$material_array["packing_materials"];
//                     for ($i = 0; $i < count($material_list); $i++) {
//                         $material = $material_list[$i];
//                         $sql2="Insert into batch_materials(plant_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,bfr_no,grade,
//                         pack_size,pack_unit,mf_batch_size) values(
//                           '".$_GET["plant_id"]."','Packing Material','".$material["material_code"]."','".$material["qty"]."','".$material["unit_name"]."',
//                             '".$material["overages"]."','".$material["total_qty"]."','".$material["batch_qty"]."',
//                             '$BFR_NO','".$material["grade"]."',
//                             '".$material_array["pm_pack_size"]."','".$material_array["pm_pack_unit"]."','".$material_array["pm_batch_size"]."')";
//                          // echo $sql2;
//                         $conn->query($sql2);
//                     }
                    
//                 }
                
                
//     	        echo "{\"status\":\"success\"}";
//     	    } else {
//     	        echo "{\"status\":\"".$conn->error."\"}";
//     	    }
//         }
        
//      }
     if ($_GET["type"] == "save_batch_formula") {
         
         $rows_count = 0;
$sql = "SELECT COUNT(*) as count 
        FROM batch_formula_info 
        WHERE plant_id = '{$_GET["plant_id"]}' 
        AND batch_formula_weight = '{$input["rm_batch_size_formula"]}' 
        AND product_code = '{$input["product_code"]}'";
$result = $conn->query($sql);
if ($row = $result->fetch_assoc()) {
    $rows_count = $row['count'];
}

if ($rows_count > 0) {
    echo json_encode(["status" => "Batch Size Already Exists"]);
} else {

    // --- Generate BFR No ---
    $sql = "SELECT COUNT(*) + 1 AS count 
            FROM batch_formula_info 
            WHERE plant_id = '{$_GET["plant_id"]}'";
    $result = $conn->query($sql);
    $last_id = ($row = $result->fetch_assoc()) ? $row['count'] : 1;
    $BFR_NO = "BFR" . str_pad($last_id, 4, "0", STR_PAD_LEFT);

    // --- Insert into batch_formula_info ---
    $sql = "INSERT INTO batch_formula_info 
            (plant_id, mfr_no, bfr_no, product_type, product_code, 
             unit_formula_batch_weight, batch_formula_weight, raw_materials, packing_materials, 
             bfr_type, version_no, effective_date, status, entry_by, 
             rm_batch_size_unit)
            VALUES (
            '{$_GET["plant_id"]}', '{$input["mfr_no"]}', '$BFR_NO',
            '{$input["product_type"]}', '{$input["product_code"]}',
            '{$input["batch_size"]}', '{$input["rm_batch_size_formula"]}', 
            '" . $conn->real_escape_string(json_encode($input["raw_materials"])) . "',
            '" . $conn->real_escape_string(json_encode($input["packing_materials"])) . "',
            '{$input["bfr_type"]}', '{$input["version_no"]}', 
            '{$input["effective_date"]}', 'pending', '{$_GET["emp_id"]}', 
            '{$input["rm_batch_size_unit"]}')";
    
    if ($conn->query($sql)) {
        $parent_id = $conn->insert_id;
        $materialError = '';
        $esc = function ($v) use ($conn) {
            if (is_array($v) || is_object($v)) { return ''; }
            return $conn->real_escape_string((string) $v);
        };

        // --- Raw material lines (shape posted by planning/masterformula/log) ---
        $rmList = isset($input["raw_materials"]) && is_array($input["raw_materials"]) ? $input["raw_materials"] : array();
        foreach ($rmList as $material) {
            $sqlRm = "INSERT INTO batch_materials
                     (plant_id, material_type, material_code, qty, unit, overages, total_qty, batch_qty, bfr_no, grade,
                      yeild_contribution, role, process_step, stage, split_into_lots, factor, strength,
                      batch_size, batch_size_unit)
                     VALUES (
                     '".$esc($_GET["plant_id"])."', 'Raw Material', '".$esc($material["material_code"] ?? '')."',
                     '".$esc($material["qty"] ?? '')."',
                     '".$esc($material["unit"] ?? $material["Converted_unit"] ?? $material["unit_name"] ?? '')."',
                     '".$esc($material["overages"] ?? $material["overages_per"] ?? '')."',
                     '".$esc($material["total_qty"] ?? $material["qty_overages_qty"] ?? '')."',
                     '".$esc($material["batch_qty"] ?? '')."', '".$esc($BFR_NO)."',
                     '".$esc($material["gradeName"] ?? $material["grade"] ?? '')."',
                     '".$esc($material["yeild_contribution"] ?? '')."', '".$esc($material["role"] ?? '')."',
                     '".$esc($material["process_step"] ?? '')."', '".$esc($material["stage"] ?? '')."',
                     '".$esc($material["split_into_lots"] ?? '')."', '".$esc($material["factor"] ?? '')."',
                     '".$esc($material["strength"] ?? '')."',
                     '".$esc($input["rm_batch_size_formula"] ?? '')."', '".$esc($input["rm_batch_size_unit"] ?? '')."')";
            if (!$conn->query($sqlRm) && $materialError === '') { $materialError = $conn->error; }
        }

        // --- Packing material lines (packing_List_All: pack config -> packing_list) ---
        $pmConfigs = isset($input["packing_materials"]) && is_array($input["packing_materials"]) ? $input["packing_materials"] : array();
        foreach ($pmConfigs as $pack) {
            $packLines = isset($pack["packing_list"]) && is_array($pack["packing_list"]) ? $pack["packing_list"] : array();
            foreach ($packLines as $pm) {
                $sqlPm = "INSERT INTO batch_materials
                         (plant_id, material_type, material_code, qty, unit, overages, total_qty, batch_qty, bfr_no, grade,
                          pack_size, pack_unit, mf_batch_size, pm_type, batch_size, batch_size_unit)
                         VALUES (
                         '".$esc($_GET["plant_id"])."', 'Packing Material', '".$esc($pm["material_code"] ?? '')."',
                         '".$esc($pm["qty"] ?? '')."', '".$esc($pm["unit_name"] ?? $pm["unit"] ?? '')."',
                         '".$esc($pm["overages"] ?? '')."', '".$esc($pm["total_qty"] ?? '')."',
                         '".$esc($pm["batch_qty"] ?? '')."', '".$esc($BFR_NO)."',
                         '".$esc($pm["gradeName"] ?? $pm["grade"] ?? '')."',
                         '".$esc($pack["pack_size"] ?? '')."', '".$esc($pack["unit"] ?? '')."',
                         '".$esc($pack["pm_batch_size"] ?? '')."', '".$esc($pack["packing_type"] ?? '')."',
                         '".$esc($input["rm_batch_size_formula"] ?? '')."', '".$esc($input["rm_batch_size_unit"] ?? '')."')";
                if (!$conn->query($sqlPm) && $materialError === '') { $materialError = $conn->error; }
            }
        }

        // --- Legacy pack-size payload (only when the caller actually sends it) ---
        $packList = isset($input["selectedPackDataList"]) && is_array($input["selectedPackDataList"])
            ? $input["selectedPackDataList"] : array();
        foreach ($packList as $packData) {

            $sql1 = "INSERT INTO batch_formula_info_packsizes 
                     (batch_formula_info_id, batchSizeKG, batchSizeNOS, pack_sizes)
                     VALUES ('$parent_id', '{$packData["batchSizeKG"]}', 
                             '{$packData["batchSizeNOS"]}', '{$packData["pack_sizes"]}')";
            if ($conn->query($sql1)) {
                $child_id = $conn->insert_id;

                // =============================
                // 1️⃣ Insert Raw Materials for this Pack Size
                // =============================
                foreach ($packData['materials'] as $material) {
                    $sql2 = "INSERT INTO batch_materials 
                             (batch_formula_info_packsizes_id, plant_id, material_type, material_code, qty, unit, 
                              overages, total_qty, batch_qty, bfr_no, grade, yeild_contribution, role, process_step, 
                              stage, split_into_lots, factor, strength)
                             VALUES (
                             '$child_id', '{$_GET["plant_id"]}', 'Raw Material', '{$material["material_code"]}', 
                             '{$material["qty"]}', '{$material["unit"]}', '{$material["overages_per"]}', 
                             '{$material["qty_overages_qty"]}', '{$material["batch_qty"]}', '$BFR_NO', 
                             '{$material["grade"]}', '{$material["yeild_contribution"]}', '{$material["role"]}', 
                             '{$material["process_step"]}', '{$material["stage"]}', 
                             '{$material["split_into_lots"]}', '{$material["factor"]}', '{$material["strength"]}')";
                    $conn->query($sql2);
                }

                // =============================
                // 2️⃣ Insert Packing Materials for this Pack Size
                // =============================
                $pmList = $input["packing_materials"];
                foreach ($pmList as $pmData) {
                    if ($pmData["pack_sizes"] == $packData["pack_sizes"]) {
                        $pmMaterials = $pmData["packing_materials"];
                        foreach ($pmMaterials as $pm) {
                            $sql3 = "INSERT INTO batch_materials 
                                     (batch_formula_info_packsizes_id, plant_id, material_type, material_code, qty, unit, 
                                      overages, total_qty, batch_qty, bfr_no, grade, pack_size, pack_unit, mf_batch_size, role)
                                     VALUES (
                                     '$child_id', '{$_GET["plant_id"]}', 'Packing Material', '{$pm["material_code"]}', 
                                     '{$pm["qty"]}', '{$pm["unit_name"]}', '{$pm["overages"]}', '{$pm["total_qty"]}', 
                                     '{$pm["batch_qty"]}', '$BFR_NO', '{$pm["grade"]}', '{$pmData["pack_size"]}', 
                                     '{$pmData["pm_pack_unit"]}', '{$pmData["pm_batch_size"]}', '{$pm["role"]}')";
                            $conn->query($sql3);
                        }
                    }
                }
            }
        }

        if ($materialError !== '') {
            echo json_encode(["status" => "Batch formula saved but material lines failed: ".$materialError]);
        } else {
            echo json_encode(["status" => "success"]);
        }
    } else {
        echo json_encode(["status" => $conn->error]);
    }
}

     }
     
     else if (isset($_GET["type"]) && $_GET["type"] == "getMicroDataFromProcessing") {

    $plant_id = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : '';

    $output = array();

    // Fetch processed splits (submitted from Processing component)
    $sql = "SELECT sp.*,
            sp.id AS split_id,
            sp.oder_qty AS order_qty,
            sp.balance_qty,
            sp.batches AS batches_json,
            sp.work_order_planned_qty,
            sp.leftover,
            sp.excess,
            sp.entryOn,
            om.id AS pid,
            om.unit,
            om.order_no,
            om.pack_size,
            om.product_code,
            p.product_name,
            p.dosage_form AS product_type,
            p.grade AS product_grade,
            COALESCE(
                (SELECT SUM(sb.qty) FROM fg_stock_book sb WHERE sb.material_code = sp.product_code),
                0
            ) - COALESCE(
                (SELECT SUM(mi.qty) FROM fg_material_issue mi
                 INNER JOIN fg_stock_book sb ON mi.batch_no = sb.batch_no AND mi.material_code = sb.material_code
                 WHERE sb.material_code = sp.product_code),
                0
            ) AS stock_qty
        FROM split_planning_qty sp
        LEFT JOIN order_materials om ON om.order_no = sp.order_no AND om.product_code = sp.product_code
        LEFT JOIN product p ON p.product_code = sp.product_code
        WHERE   (sp.plant_id = '" . $plant_id . "' OR '" . $plant_id . "' = '')
        ORDER BY sp.entryOn DESC, sp.id DESC";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $pid = isset($row['pid']) ? $row['pid'] : $row['split_id'];
            $order_no = $row['order_no'];
            $product_code = $row['product_code'];

            $row['pack_size'] = is_string($row['pack_size']) ? json_decode($row['pack_size'], true) : $row['pack_size'];
            $row['stock_qty'] = floatval($row['stock_qty']);
            $row['order_qty'] = floatval($row['order_qty']);

            // Build total_batches and batch_details from batches JSON and Work_order_materials
            $batches_decoded = array();
            if (!empty($row['batches_json'])) {
                $batches_decoded = json_decode($row['batches_json'], true);
                if (!is_array($batches_decoded)) {
                    $batches_decoded = array();
                }
            }

            $total_batches_count = 0;
            foreach ($batches_decoded as $b) {
                $total_batches_count += isset($b['count']) ? intval($b['count']) : 0;
            }

            $row['total_batches'] = array(array('total_batches' => $total_batches_count));

            // Work_order_materials rows for this split (doc_no = split_planning_qty.id)
            // id + start_date used so Micro can update start date via UpdatesARTdATE (no batch_planning)
            $batch_details = array();
            // Select id for UpdatesARTdATE; start_date after running alter_work_order_start_date.sql
            $sql_wo = "SELECT id, workorder_no, batch_size, planQty, planUnit, entryOn,
                       IFNULL(start_date, entryOn) AS start_date
                       FROM Work_order_materials
                       WHERE doc_no = '" . $conn->real_escape_string($row['split_id']) . "'
                       AND order_no = '" . $conn->real_escape_string($order_no) . "'
                       AND product_code = '" . $conn->real_escape_string($product_code) . "'
                       ORDER BY id";
            $res_wo = $conn->query($sql_wo);
            $idx = 0;
            if ($res_wo && $res_wo->num_rows > 0) {
                while ($r_wo = $res_wo->fetch_assoc()) {
                    $idx++;
                    $wo_start = !empty($r_wo['start_date']) ? $r_wo['start_date'] : $r_wo['entryOn'];
                    $batch_details[] = array(
                        'wordOrderId' => $r_wo['workorder_no'],
                        'batch_number' => $r_wo['workorder_no'] ?: ('WO-' . $row['split_id'] . '-' . $idx),
                        'startsDate' => $wo_start,
                        'planDate' => $r_wo['entryOn'],
                        'start_date' => $wo_start,
                        'startDate' => $wo_start,
                        'batch_id' => $r_wo['id'],
                        'batch_size' => $r_wo['batch_size'],
                        'Forecast_DispensingDate' => $r_wo['entryOn'],
                        'Forecast_productionDate' => $r_wo['entryOn'],
                        'Forecast_FillingDate' => $r_wo['entryOn'],
                        'Forecast_inprocessRelease' => $r_wo['entryOn'],
                        'Forecast_PackingDate' => $r_wo['entryOn'],
                        'Forecast_fgRelease' => $r_wo['entryOn'],
                        'Forecast_FgTransferDate' => $r_wo['entryOn'],
                        'stages' => array()
                    );
                }
            }

            // If no Work_order_materials, build batch_details from batches JSON only
            if (empty($batch_details) && !empty($batches_decoded)) {
                $sn = 0;
                foreach ($batches_decoded as $b) {
                    $cnt = isset($b['count']) ? intval($b['count']) : 0;
                    $sz = isset($b['size']) ? $b['size'] : 0;
                    for ($j = 0; $j < $cnt; $j++) {
                        $sn++;
                        $batch_details[] = array(
                            'wordOrderId' => $row['split_id'],
                            'batch_number' => 'B-' . $row['split_id'] . '-' . $sn,
                            'startsDate' => $row['entryOn'],
                            'planDate' => $row['entryOn'],
                            'start_date' => $row['entryOn'],
                            'startDate' => $row['entryOn'],
                            'batch_id' => $row['split_id'],
                            'batch_size' => $sz,
                            'Forecast_DispensingDate' => $row['entryOn'],
                            'Forecast_productionDate' => $row['entryOn'],
                            'Forecast_FillingDate' => $row['entryOn'],
                            'Forecast_inprocessRelease' => $row['entryOn'],
                            'Forecast_PackingDate' => $row['entryOn'],
                            'Forecast_fgRelease' => $row['entryOn'],
                            'Forecast_FgTransferDate' => $row['entryOn'],
                            'stages' => array()
                        );
                    }
                }
            }

            $row['batch_details'] = $batch_details;

            // Splits for this order (same order_no + product_code)
            $splits = array();
            $sql_spl = "SELECT *, oder_qty AS Qty, balance_qty AS bal_qty, IFNULL(batch_plan_id, 0) AS batch_plan_id
                        FROM split_planning_qty
                        WHERE order_no = '" . $conn->real_escape_string($order_no) . "'
                        AND product_code = '" . $conn->real_escape_string($product_code) . "'";
            $res_spl = $conn->query($sql_spl);
            if ($res_spl && $res_spl->num_rows > 0) {
                while ($rs = $res_spl->fetch_assoc()) {
                    $splits[] = $rs;
                }
            }
            $row['splits'] = $splits;

            // MFR records for product (for View Micro planning)
            $mfr_records = array();
            $sql_mfr = "SELECT id, mfr_no, batch_size FROM unitformula WHERE product_code = '" . $conn->real_escape_string($product_code) . "'";
            $res_mfr = $conn->query($sql_mfr);
            if ($res_mfr && $res_mfr->num_rows > 0) {
                while ($rm = $res_mfr->fetch_assoc()) {
                    $bfr_records = array();
                    $sql_bfr = "SELECT * FROM batch_formula_info WHERE mfr_no = '" . $conn->real_escape_string($rm['mfr_no']) . "' AND status = 'Approve'";
                    $res_bfr = $conn->query($sql_bfr);
                    if ($res_bfr && $res_bfr->num_rows > 0) {
                        while ($rb = $res_bfr->fetch_assoc()) {
                            $bfr_records[] = $rb;
                        }
                    }
                    $rm['bfr_records'] = $bfr_records;
                    $mfr_records[] = $rm;
                }
            }
            $row['mfr_records'] = $mfr_records;
            $row['stages'] = array();
            $row['packing_configuration'] = array();

            $output[] = $row;
        }
    }

    echo json_encode($output);
}
else if ($_GET["type"] == "updateSequence") {
         
         header("Access-Control-Allow-Origin: *");

// Allow POST requests and necessary headers
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Your existing code
$data = json_decode(file_get_contents("php://input"), true);

// For debugging (remove in production)
// var_dump($data);

foreach ($data as $row) {
    $id = $row["id"];
    $sequence = $row["sequence"];
    $conn->query("UPDATE order_materials SET sequence = '$sequence' WHERE id = '$id'");
}

echo json_encode(["status" => "success"]);
     }
     if ($_GET["type"] == "save_batch_formulaMeha") {

   
        $rows_count=0;
           $sql = "Select count(*) as count from batch_formula_info where  plant_id = '".$_GET["plant_id"]."' 
        and batch_formula_weight = '".$input["rm_batch_size_formula"]."' AND product_code = '".$input["product_code"]."' ";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $rows_count = $row['count'];
        }
        if($rows_count>0){
              echo "{\"status\":\"Batch Size Already Exists\"}";
        }else{
            $last_id=0;
            $sql = "Select count(*)+1 as count from batch_formula_info where  plant_id = '".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            while($row = $result->fetch_assoc()){
                  $last_id = $row['count'];
            }
            if($last_id==0){
                $last_id=1;
            }
            $length = 4;
            $number = substr(str_repeat(0, $length).$last_id, - $length);
            $BFR_NO =  "BFR".$number;
            $sql = "INSERT INTO batch_formula_info (plant_id,mfr_no,bfr_no, product_type,product_code, 
            unit_formula_batch_weight,
            batch_formula_weight, raw_materials,packing_materials, bfr_type, version_no, effective_date, status, 
            entry_by,rm_batch_size_unit) VALUES (
    	    '".$_GET["plant_id"]."','".$input["mfr_no"]."','".$BFR_NO."','".$input["product_type"]."','".$input["product_code"]."',
    	    '".$input["batch_size"]."','".$input["rm_batch_size_formula"]."','".json_encode($input["raw_materials"])."',
    	    '".json_encode($input["packing_materials"])."',  '".$input["bfr_type"]."', '".$input["version_no"]."',
    	    '".$input["effective_date"]."','pending','".$_GET["emp_id"]."','".$input["rm_batch_size_unit"]."')";
    	  //  echo $sql;
    	    if ($conn->query($sql)) {
    	       // echo json_encode($input["raw_materials"]);
    	        $materials = json_decode(json_encode($input["raw_materials"]),true);
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                     $sql2="Insert into batch_materials(plant_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,bfr_no,grade,
                    yeild_contribution,role,process_step,stage,split_into_lots,factor,strength,dispensingIn) values(
                       '".$_GET["plant_id"]."','".$material["material_subtype"]."','".$material["material_code"]."','".$material["qty"]."','".$material["unit"]."',
                        '".$material["overages_per"]."','".$material["qty_overages_qty"]."','".$material["batch_qty"]."','$BFR_NO','".$material["grade"]."',
                        '".$material["yeild_contribution"]."','".$material["role"]."','".$material["process_step"]."','".$material["stage"]."',
                        '".$material["split_into_lots"]."','".$material["factor"]."','".$material["strength"]."','".$material["dispensingIn"]."')";
                       //  echo $sql2;
                    $conn->query($sql2);
                    
                }
                
                $materials = json_decode(json_encode($input["packing_materials"]),true);
                for ($j = 0; $j < count($materials); $j++) {
                    $material_array = $materials[$j];
                    $material_list=$material_array["packing_materials"];
                    for ($i = 0; $i < count($material_list); $i++) {
                        $material = $material_list[$i];
                        $sql2="Insert into batch_materials(plant_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,bfr_no,grade,
                        pack_size,pack_unit,mf_batch_size) values(
                           '".$_GET["plant_id"]."','Packing Material','".$material["material_code"]."','".$material["qty"]."','".$material["unit_name"]."',
                            '".$material["overages"]."','".$material["total_qty"]."','".$material["batch_qty"]."',
                            '$BFR_NO','".$material["grade"]."',
                            '".$material_array["pm_pack_size"]."','".$material_array["pm_pack_unit"]."','".$material_array["pm_batch_size"]."')";
                         // echo $sql2;
                        $conn->query($sql2);
                    }
                    
                }
                
                
    	        echo "{\"status\":\"success\"}";
    	    } else {
    	        echo "{\"status\":\"".$conn->error."\"}";
    	    }
        }
        
     }
     if ($_GET["type"] == "save_batch_formulaZuma") {

   
        $rows_count=0;
           $sql = "Select count(*) as count from batch_formula_info where  plant_id = '".$_GET["plant_id"]."' 
        and batch_formula_weight = '".$input["rm_batch_size_formula"]."' AND product_code = '".$input["product_code"]."' ";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $rows_count = $row['count'];
        }
        if($rows_count>0){
              echo "{\"status\":\"Batch Size Already Exists\"}";
        }else{
            $last_id=0;
            $sql = "Select count(*)+1 as count from batch_formula_info where  plant_id = '".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            while($row = $result->fetch_assoc()){
                  $last_id = $row['count'];
            }
            if($last_id==0){
                $last_id=1;
            }
            $length = 4;
            $number = substr(str_repeat(0, $length).$last_id, - $length);
            $BFR_NO =  "BFR".$number;
            $sql = "INSERT INTO batch_formula_info (plant_id,mfr_no,bfr_no, product_type,product_code, 
            unit_formula_batch_weight,
            batch_formula_weight, raw_materials,packing_materials, bfr_type, version_no, effective_date, status, 
            entry_by,rm_batch_size_unit) VALUES (
    	    '".$_GET["plant_id"]."','".$input["mfr_no"]."','".$BFR_NO."','".$input["product_type"]."','".$input["product_code"]."',
    	    '".$input["batch_size"]."','".$input["rm_batch_size_formula"]."','".json_encode($input["raw_materials"])."',
    	    '".json_encode($input["packing_materials"])."',  '".$input["bfr_type"]."', '".$input["version_no"]."',
    	    '".$input["effective_date"]."','pending','".$_GET["emp_id"]."','".$input["rm_batch_size_unit"]."')";
    	  //  echo $sql;
    	    if ($conn->query($sql)) {
    	       // echo json_encode($input["raw_materials"]);
    	        $materials = json_decode(json_encode($input["raw_materials"]),true);
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                     $sql2="Insert into batch_materials(plant_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,bfr_no,grade,
                    yeild_contribution,role,process_step,stage,split_into_lots,factor,strength,batch_size,batch_size_unit) values(
                       '".$_GET["plant_id"]."','Raw Material','".$material["material_code"]."','".$material["qty"]."','".$material["Converted_unit"]."',
                        '".$material["overages_per"]."','".$material["qty_overages_qty"]."','".$material["batch_qty"]."','$BFR_NO','".$material["grade"]."',
                        '".$material["yeild_contribution"]."','".$material["role"]."','".$material["process_step"]."','".$material["stage"]."',
                        '".$material["split_into_lots"]."','".$material["factor"]."','".$material["strength"]."','".$input["rm_batch_size_formula"]."','".$input["rm_batch_size_unit"]."')";
                       //  echo $sql2;
                    $conn->query($sql2);
                    
                }
    	        $materials = json_decode(json_encode($input["primary_pm_list"]),true);
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                     $sql2="Insert into batch_materials(plant_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,bfr_no,grade,pm_type,batch_size,batch_size_unit ) values(
                       '".$_GET["plant_id"]."','Packing Material','".$material["material_code"]."','".$material["qty"]."','".$material["unit_name"]."',
                        '".$material["overages"]."','".$material["total_qty"]."','".$material["batch_qty"]."','$BFR_NO','".$material["grade"]."','Primary Packing','".$input["rm_batch_size_formula"]."','".$input["rm_batch_size_unit"]."')";
                    
                    $conn->query($sql2);
                    
                }
                
                $materials = json_decode(json_encode($input["packing_materials"]),true);
                for ($j = 0; $j < count($materials); $j++) {
                    $material_array = $materials[$j];
                    $material_list=$material_array["packing_list"];
                    for ($i = 0; $i < count($material_list); $i++) {
                        $material = $material_list[$i];
                        $sql2="Insert into batch_materials(plant_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,bfr_no,grade,
                        pack_size,pack_unit,mf_batch_size,pm_type,batch_size,batch_size_unit) values(
                           '".$_GET["plant_id"]."','Packing Material','".$material["material_code"]."','".$material["qty"]."','".$material["unit_name"]."',
                            '".$material["overages"]."','".$material["total_qty"]."','".$material["batch_qty"]."',
                            '$BFR_NO','".$material["grade"]."',
                            '".$material_array["pack_size"]."','".$material_array["unit"]."','".$material_array["pm_batch_size"]."','Secondary Packing','".$input["rm_batch_size_formula"]."','".$input["rm_batch_size_unit"]."')";
                         // echo $sql2;
                        $conn->query($sql2);
                    }
                    
                }
                $materials = json_decode(json_encode($input["consumeableMaterial"]),true);
                   for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                     $sql2="Insert into batch_materials(plant_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,bfr_no,grade,pm_type ,batch_size,batch_size_unit) values(
                       '".$_GET["plant_id"]."','Consumeable Material','".$material["material_code"]."','".$material["qty"]."','".$material["unit_name"]."',
                        '".$material["overages"]."','".$material["total_qty"]."','".$material["batch_qty"]."','$BFR_NO','".$material["grade"]."','Consumeable Material','".$input["rm_batch_size_formula"]."','".$input["rm_batch_size_unit"]."')";
                    
                    $conn->query($sql2);
                    
                }
                
                
    	        echo "{\"status\":\"success\"}";
    	    } else {
    	        echo "{\"status\":\"".$conn->error."\"}";
    	    }
        }
        
     }
     else if ($_GET["type"] == "get_batch_formula_for_approval") {
         
         $output = array();
         $statusRaw = isset($_GET['status']) ? trim($_GET['status']) : '';
         $plantEsc = mysqli_real_escape_string($conn, $_GET["plant_id"] ?? '');
         // Approved unit formulas wait here until QA approves the BFR.
         $ufPending = $conn->query("SELECT u.* FROM unitformula u
            WHERE TRIM(IFNULL(u.plant_id,''))='".$plantEsc."'
            AND u.approve_by IS NOT NULL AND TRIM(u.approve_by) <> ''
            AND LOWER(IFNULL(u.status,'')) NOT IN ('reject','rejected')
            AND NOT EXISTS (
                SELECT 1 FROM batch_formula_info c
                WHERE TRIM(IFNULL(c.plant_id,''))=TRIM(IFNULL(u.plant_id,''))
                AND (
                    (TRIM(IFNULL(c.mfr_no,'')) <> '' AND TRIM(IFNULL(c.mfr_no,''))=TRIM(IFNULL(u.mfr_no,'')))
                    OR (TRIM(IFNULL(c.product_code,'')) <> '' AND TRIM(IFNULL(c.product_code,''))=TRIM(IFNULL(u.product_code,'')))
                )
            )");
         if ($ufPending && $ufPending->num_rows > 0) {
            while ($ufRow = $ufPending->fetch_assoc()) {
                medicap_insert_bfr_from_unitformula($conn, $ufRow);
            }
         }

  $sql = "SELECT a.*, 
               b.product_name, 
               b.generic_name, 
               b.unit, 
               u.average_weight, 
               u.average_weight_unit, 
               b.dosage_form,
               (SELECT product_type FROM product p WHERE u.product_code = p.product_code LIMIT 1) AS a_product_type
        FROM batch_formula_info a
        LEFT JOIN product b ON TRIM(IFNULL(a.product_code,'')) = TRIM(IFNULL(b.product_code,''))
        LEFT JOIN unitformula u ON TRIM(IFNULL(a.mfr_no,'')) = TRIM(IFNULL(u.mfr_no,''))
            AND TRIM(IFNULL(u.plant_id,'')) = TRIM(IFNULL(a.plant_id,''))
        WHERE TRIM(IFNULL(a.plant_id,'')) = '".$plantEsc."'";
        if ($statusRaw === '') {
            $sql .= " AND LOWER(TRIM(IFNULL(a.status,''))) IN ('pending', 'for qa approval')";
        } else {
            $statusEsc = mysqli_real_escape_string($conn, $statusRaw);
            $sql .= " AND LOWER(TRIM(a.status)) = LOWER('".$statusEsc."')";
        }
        $sql .= " ORDER BY a.id DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        $packs = array(); // packSizes array

        // --- Get all pack sizes for this batch_formula_info record ---
         $sql_pack = "SELECT * FROM batch_formula_info_packsizes 
                     WHERE batch_formula_info_id = '{$row["id"]}'";
        $result_pack = $conn->query($sql_pack);

        if ($result_pack && $result_pack->num_rows > 0) {
            while ($pack_row = $result_pack->fetch_assoc()) {

                             $raw_materials = array();
                            $packing_materials = array();
                            
                            // --- Get all materials for this pack size ---
                            $sql_mat = "SELECT a.*,b.material_subtype,b.material_name FROM batch_materials  a left join material b on a.material_code=b.material_code
                                        WHERE a.batch_formula_info_packsizes_id = '{$pack_row["id"]}'";
                            $result_mat = $conn->query($sql_mat);
                            
                            if ($result_mat && $result_mat->num_rows > 0) {
                                while ($mat_row = $result_mat->fetch_assoc()) {
                                    if (strtolower($mat_row['material_type']) === 'raw material') {
                                        $raw_materials[] = $mat_row;
                                    } elseif (strtolower($mat_row['material_type']) === 'packing material') {
                                        $packing_materials[] = $mat_row;
                                    }
                                }
                            }
                            $pack_row['raw_materials'] = $raw_materials;
$pack_row['packing_materials'] = $packing_materials;
                $packs[] = $pack_row;
            }
        }

        $decodedRm = $row["raw_materials"];
        if (is_string($decodedRm)) {
            $decodedRm = json_decode($decodedRm ?: '[]', true);
        }
        if (!is_array($decodedRm)) { $decodedRm = array(); }
        $row["raw_materials"] = $decodedRm;
        $decodedPm = $row["packing_materials"] ?? array();
        if (is_string($decodedPm)) {
            $decodedPm = json_decode($decodedPm ?: '[]', true);
        }
        if (!is_array($decodedPm)) { $decodedPm = array(); }
        if (count($packs) === 0 && (count($decodedRm) > 0 || count($decodedPm) > 0)) {
            $packs[] = array(
                'pack_sizes' => '',
                'batchSizeNOS' => '',
                'batchSizeKG' => $row['batch_formula_weight'] ?? '',
                'raw_materials' => $decodedRm,
                'packing_materials' => $decodedPm,
            );
        }
        $row['Packs'] = $packs;
        $row = medicap_attach_packing_to_bfr($conn, $row);
        $flatPm = medicap_flatten_packing_lines($row['packing_configuration'] ?? array());
        if (count($packs) === 0 && (count($decodedRm) > 0 || count($flatPm) > 0)) {
            $packs[] = array(
                'pack_sizes' => '',
                'batchSizeNOS' => '',
                'batchSizeKG' => $row['batch_formula_weight'] ?? '',
                'raw_materials' => $decodedRm,
                'packing_materials' => $flatPm,
            );
        } else if (count($packs) > 0 && count($flatPm) > 0) {
            foreach ($packs as $pi => $packRow) {
                if (empty($packRow['packing_materials'])) {
                    $packs[$pi]['packing_materials'] = $flatPm;
                }
            }
        }
        $row['Packs'] = $packs;
        if (empty($row['product_name'])) {
            $codeEsc = mysqli_real_escape_string($conn, $row['product_code'] ?? '');
            $pn = $conn->query("SELECT product_name, generic_name, dosage_form FROM product WHERE TRIM(product_code)='".$codeEsc."' LIMIT 1");
            if ($pn && ($pnRow = $pn->fetch_assoc())) {
                $row['product_name'] = $pnRow['product_name'];
                if (empty($row['generic_name'])) { $row['generic_name'] = $pnRow['generic_name']; }
                if (empty($row['dosage_form'])) { $row['dosage_form'] = $pnRow['dosage_form']; }
            }
        }

        $output[] = $row;
    }
}

echo json_encode($output);

     }
          else if ($_GET["type"] == "get_batch_formula_for_approval_Zuma") {
           $output=Array();
         $sql = "SELECT a.*,b.product_name,b.generic_name,b.unit,u.average_weight,u.average_weight_unit,u.dosage_form,u.bom_batch_size,u.bom_batch_size_unit,
       (select product_type from product p where u.product_code=p.product_code limit 1) as a_product_type
       FROM batch_formula_info a join product b on a.product_code = b.product_code left join unitformula u on  a.mfr_no=u.mfr_no
        where  a.plant_id='".$_GET["plant_id"]."' and a.status='".$_GET["status"]."' order by 1 desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                
                    $output1 = Array();
                        $sql1 = "SELECT a.*,b.material_name,b.material_subtype   FROM batch_materials a left join others_material b on a.material_code=b.material_code  WHERE a.bfr_no='".$row["bfr_no"]."' and a.pm_type='Consumeable Material' and a.material_type='Consumeable Material' ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                    $output2 = Array();
                        $sql2 = "SELECT * FROM batch_materials a left join material b on a.material_code=b.material_code WHERE a.bfr_no='".$row["bfr_no"]."' and a.pm_type='Primary Packing' and a.material_type='Packing Material' ";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                    $output3 = Array();
                        $sql3 = "SELECT * FROM batch_materials a left join material b on a.material_code=b.material_code WHERE a.bfr_no='".$row["bfr_no"]."' and a.pm_type='Secondary Packing' and a.material_type='Packing Material' ";
                        
                        $result3 = $conn->query($sql3);
                        if ($result3->num_rows > 0) {
                            while ($row3 = $result3->fetch_assoc()) {
                                $output3[] = $row3;
                            }
                        }
                
                 $row["PrimaryPacking"] = $output2;
                 $row["SecondaryPacking"] = $output3;
                 $row["ConsumeableMaterial"] = $output1;
                 $row["raw_materials"] = json_decode($row["raw_materials"]);
                 
                $output[] = $row;
            }
        }
       $output = utf8ize($output);
echo json_encode($output);
     }
     else if ($_GET["type"] == "get_batch_formula_for_approval1") {
           $output=Array();
          $sql = "SELECT a.*,b.product_name,b.unit,u.average_weight,u.average_weight_unit,u.dosage_form,
       (select product_type from product p where u.product_code=p.product_code limit 1) as a_product_type
       FROM batch_formula_info a join product b on a.product_code = b.product_code left join unitformula u on  a.mfr_no=u.mfr_no
        where  a.plant_id='".$_GET["plant_id"]."' AND LOWER(TRIM(a.status))='pending'
        AND u.dosage_form LIKE '%".(isset($_GET["dsg"]) ? $_GET["dsg"] : (isset($_GET["dossageForm"]) ? $_GET["dossageForm"] : ''))."%' order by 1 desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $row["raw_materials"] = json_decode($row["raw_materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
     }
   else if ($_GET["type"] == "get_bfr_records") {
        $output = medicap_get_bfr_records(
            $conn,
            $_GET["plant_id"] ?? '',
            $_GET["mfr_no"] ?? '',
            $_GET["product_code"] ?? '',
            true
        );
        echo json_encode($output);
     }
   else if ($_GET["type"] == "get_batch_formula_log") {
          $output=Array();
           $sql = "   SELECT DISTINCT  u.dosage_form,u.id,u.bom_type,u.bom_batch_size_unit,u.bom_batch_size,u.master_formula_type,u.product_type,u.mfr_no,u.product_code,u.formula_for, u.average_weight,u.raw_materials,u.batch_size,u.unit,u.status,
u.primary_pm_list,u.primary_pm_batch_size,u.consumeableMaterial,
(select c.status from batch_formula_info c where c.mfr_no=u.mfr_no AND c.plant_id=u.plant_id order by c.id desc limit 1) as c_status,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
(select grade from product p where u.product_code=p.product_code limit 1) as grade,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select product_code1 from product p where u.product_code=p.product_code limit 1) as product_code1
FROM unitformula u where u.plant_id='".$_GET["plant_id"]."'
AND u.approve_by IS NOT NULL AND TRIM(u.approve_by) <> ''
AND LOWER(IFNULL(u.status,'')) NOT IN ('reject','rejected')
AND CAST(IFNULL(NULLIF(TRIM(u.bom_batch_size),''),'0') AS DECIMAL(18,6)) > 0
AND EXISTS (
    SELECT 1 FROM batch_formula_info c
    WHERE TRIM(IFNULL(c.plant_id,''))=TRIM(IFNULL(u.plant_id,''))
    AND (
        (TRIM(IFNULL(c.mfr_no,'')) <> '' AND TRIM(IFNULL(c.mfr_no,''))=TRIM(IFNULL(u.mfr_no,'')))
        OR (TRIM(IFNULL(c.product_code,'')) <> '' AND TRIM(IFNULL(c.product_code,''))=TRIM(IFNULL(u.product_code,'')))
    )
    AND LOWER(TRIM(IFNULL(c.status,''))) IN ('approve','approved')
)
order by u.id DESC;";
        // $sql = "SELECT distinct  a.mfr_no,a.product_code,a.product_type,b.product_name,a.batch_size,
        //  b.unit,a.status,a.average_weight,a.average_weight_unit,a.raw_materials FROM unitformula a inner join 
        //  batch_formula_info c on a.mfr_no=c.mfr_no join product b 
        // on a.product_code = b.product_code
        //         where c.status='Approve' and a.plant_id='".$_GET["plant_id"]."' order by 1 desc";
                 
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
                 $row["bfr_records"] = medicap_get_bfr_records(
                    $conn,
                    $_GET["plant_id"] ?? '',
                    $row["mfr_no"] ?? '',
                    $row["product_code"] ?? '',
                    false
                 );
                 $row["raw_materials"] = json_decode($row["raw_materials"] ?: '[]', true);
                 if (!is_array($row["raw_materials"])) { $row["raw_materials"] = array(); }
                 $row["primary_pm_list"] = json_decode($row["primary_pm_list"] ?? '[]', true);
                 if (!is_array($row["primary_pm_list"])) { $row["primary_pm_list"] = array(); }
                 $row["consumeableMaterial"] = json_decode($row["consumeableMaterial"] ?? '[]', true);
                 if (!is_array($row["consumeableMaterial"])) { $row["consumeableMaterial"] = array(); }
                 $row['packing_configuration'] = medicap_load_packing_configuration(
                    $conn,
                    $_GET["plant_id"] ?? '',
                    $row["mfr_no"] ?? '',
                    $row["product_code"] ?? '',
                    $row["id"] ?? ''
                 );
                $output[] = $row;
            }
        }
        echo json_encode($output);
     }
     else if ($_GET["type"] == "get_batch_formula_log_bmr") {
          $output=Array();
           $sql = "   SELECT DISTINCT  c.status as c_status,u.dosage_form,u.id,u.bom_type,u.master_formula_type,u.product_type,u.mfr_no,u.product_code,u.formula_for, u.average_weight,u.raw_materials,u.batch_size,u.unit,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
(select grade from product p where u.product_code=p.product_code limit 1) as grade,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_code1 from product p where u.product_code=p.product_code limit 1) as product_code1,
(select product_code1 from product p where u.product_code=p.product_code limit 1) as product_code1
FROM unitformula u left join batch_formula_info c on u.mfr_no=c.mfr_no where u.plant_id='".$_GET["plant_id"]."' and u.product_code='".$_GET["product_code"]."' and c.status='Approve' order by u.id DESC;";
        // $sql = "SELECT distinct  a.mfr_no,a.product_code,a.product_type,b.product_name,a.batch_size,
        //  b.unit,a.status,a.average_weight,a.average_weight_unit,a.raw_materials FROM unitformula a inner join 
        //  batch_formula_info c on a.mfr_no=c.mfr_no join product b 
        // on a.product_code = b.product_code
        //         where c.status='Approve' and a.plant_id='".$_GET["plant_id"]."' order by 1 desc";
                 
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
                 $row["bfr_records"] = medicap_get_bfr_records(
                    $conn,
                    $_GET["plant_id"] ?? '',
                    $row["mfr_no"] ?? '',
                    $row["product_code"] ?? '',
                    false
                 );
                 $row["raw_materials"] = json_decode($row["raw_materials"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
     }
     else if ($_GET["type"] == "get_batch_formula_log1") {
          $output=Array();
             $sql = "   SELECT DISTINCT  u.dosage_form,u.id,u.bom_type,u.bom_batch_size_unit,u.bom_batch_size,u.master_formula_type,u.product_type,u.mfr_no,u.product_code,u.formula_for, u.average_weight,u.raw_materials,u.batch_size,u.unit,u.status,
(select c.status from batch_formula_info c where c.mfr_no=u.mfr_no AND c.plant_id=u.plant_id order by c.id desc limit 1) as c_status,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
(select grade from product p where u.product_code=p.product_code limit 1) as grade,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_code1 from product p where u.product_code=p.product_code limit 1) as product_code1,
(select product_code1 from product p where u.product_code=p.product_code limit 1) as product_code1
FROM unitformula u left join product p ON u.product_code = p.product_code  where u.plant_id='".$_GET["plant_id"]."'
AND u.approve_by IS NOT NULL AND TRIM(u.approve_by) <> ''
AND LOWER(IFNULL(u.status,'')) NOT IN ('reject','rejected')
AND CAST(IFNULL(NULLIF(TRIM(u.bom_batch_size),''),'0') AS DECIMAL(18,6)) > 0
AND p.product_name LIKE '%".$_GET["productName"]."%'
AND EXISTS (
    SELECT 1 FROM batch_formula_info c
    WHERE TRIM(IFNULL(c.plant_id,''))=TRIM(IFNULL(u.plant_id,''))
    AND (
        (TRIM(IFNULL(c.mfr_no,'')) <> '' AND TRIM(IFNULL(c.mfr_no,''))=TRIM(IFNULL(u.mfr_no,'')))
        OR (TRIM(IFNULL(c.product_code,'')) <> '' AND TRIM(IFNULL(c.product_code,''))=TRIM(IFNULL(u.product_code,'')))
    )
    AND LOWER(TRIM(IFNULL(c.status,''))) IN ('approve','approved')
)
order by u.id DESC;";
        // $sql = "SELECT distinct  a.mfr_no,a.product_code,a.product_type,b.product_name,a.batch_size,
        //  b.unit,a.status,a.average_weight,a.average_weight_unit,a.raw_materials FROM unitformula a inner join 
        //  batch_formula_info c on a.mfr_no=c.mfr_no join product b 
        // on a.product_code = b.product_code
        //         where c.status='Approve' and a.plant_id='".$_GET["plant_id"]."' order by 1 desc";
                 
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
                 $row["bfr_records"] = medicap_get_bfr_records(
                    $conn,
                    $_GET["plant_id"] ?? '',
                    $row["mfr_no"] ?? '',
                    $row["product_code"] ?? '',
                    false
                 );
                 $row["raw_materials"] = json_decode($row["raw_materials"]); 
                 $row['packing_configuration'] = medicap_load_packing_configuration(
                    $conn,
                    $_GET["plant_id"] ?? '',
                    $row["mfr_no"] ?? '',
                    $row["product_code"] ?? '',
                    $row["id"] ?? ''
                 );
                $output[] = $row;
            }
        }
        echo json_encode($output);
     }
     else if ($_GET["type"] == "get_products_for_shortage_calculation") {
           $output=Array();
        // The screen's "Product Type" dropdown lists dosage form types, so match either
        // column. Product master status is not a gate (Medicap products stay 'Pending');
        // the approved unit formula + approved BFR below is the real gate.
        $sql = "SELECT a.product_code,a.product_type,a.product_name,a.grade,a.pack_sizes FROM product a 
                where (a.product_type='".$_GET["product_type"]."' or a.dosage_form='".$_GET["product_type"]."')
                and (a.status IS NULL OR LOWER(TRIM(a.status)) NOT IN ('reject','rejected','in-active','inactive','obsolete'))
                and a.plant_id='".$_GET["plant_id"]."' order by a.product_name";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
                 $sql2="Select id,mfr_no,batch_size from unitformula where product_code = 
                 '".$row["product_code"]."'  and LOWER(TRIM(status)) in ('approve','approved') ";
                
                 $result1 = $conn->query($sql2);
                 if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql3="Select id,bfr_no,batch_formula_weight,raw_materials,packing_materials 
                        from batch_formula_info  where mfr_no = '".$row1["mfr_no"]."' and LOWER(TRIM(status)) in ('approve','approved') ";
                        $result2 = $conn->query($sql3);
                              if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                         $output2[]=$row2;   
                                    }
                              }
                            $row1["bfr_records"] = $output2;
                            $output1[]=$row1;   
                    }
                     
                 }
                 $row["mfr_records"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
     }
     else if ($_GET["type"] == "get_products_formulation_shortage_calculationMacroZuma") {
          $output=Array();
          
          if($_GET['p_type']=='Finish Product Branded'){
        $sql = "SELECT *,a.brand_name as product_name FROM product_brand_name a LEFT JOIN product b on a.product_code =b.product_code where b.dosage_form='".$_GET['dosage_form']."'  and a.plant_id='".$_GET["plant_id"]."' ";
          }else{
                 $sql = "SELECT a.product_code,a.product_type,a.product_name,a.grade,pack_sizes FROM product a 
        where a.dosage_form='".$_GET["dosage_form"]."' and a.status='Approve' and a.plant_id='".$_GET["plant_id"]."' ";
          }
          
        //  $sql = "SELECT a.product_code,a.product_type,a.product_name,a.grade,pack_sizes FROM product a 
        // where a.dosage_form='".$_GET["dosage_form"]."' and a.status='Approve' and a.plant_id='".$_GET["plant_id"]."' ";
        
       
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
                 $sql2="Select id,mfr_no,batch_size from unitformula where product_code = 
                 '".$row["product_code"]."'  and status='Approve' ";
                
                 $result1 = $conn->query($sql2);
                 if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql3="Select id,mfr_no,bfr_no,batch_formula_weight,raw_materials,packing_materials 
                        from batch_formula_info  where mfr_no = '".$row1["mfr_no"]."' and status='Approve' ";
                        $result2 = $conn->query($sql3);
                              if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                         $output2[]=$row2;   
                                    }
                              }
                            $row1["bfr_records"] = $output2;
                            $output1[]=$row1;   
                    }
                     
                 }
                 $output3 = Array();
                 $sql3="SELECT month, year,product_name,product_code, SUM(oder_qty) AS oder_qty FROM split_planning_qty  where product_name='".$row["product_name"]."' GROUP BY month,product_code, year,product_name  ";
                
                 $result3 = $conn->query($sql3);
                 if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                       
                            $output3[]=$row3;   
                    }
                     
                 }
                 $row["mfr_records"] = $output1;
                 $row["split_records"] = $output3;
                $output[] = $row;
            }
        }
        echo json_encode($output);
     }
     else if ($_GET["type"] == "get_products_formulation_shortage_calculation") {
          $output=Array();
          
          
          
        // Planning gate is an approved unit formula + approved BFR (below).
        // Product master status is not used as a gate here: Medicap products stay
        // 'Pending' because the Account/QC/QA product approval chain is not in use.
         $sql = "SELECT a.product_code,a.product_type,a.product_name FROM product a 
        where a.dosage_form='".$_GET["dosage_form"]."' and a.plant_id='".$_GET["plant_id"]."'
        and (a.status IS NULL OR LOWER(TRIM(a.status)) NOT IN ('reject','rejected','in-active','inactive','obsolete')) ";
        
       
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
                 $sql2="Select id,mfr_no from unitformula where product_code = 
                 '".$row["product_code"]."'  and LOWER(TRIM(status)) in ('approve','approved') ";
                
                 $result1 = $conn->query($sql2);
                 if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql3="Select id,mfr_no,bfr_no,batch_formula_weight,raw_materials,packing_materials 
                        from batch_formula_info  where mfr_no = '".$row1["mfr_no"]."' and LOWER(TRIM(status)) in ('approve','approved') ";
                        $result2 = $conn->query($sql3);
                              if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        
                                        
                                                     $output123 = Array();
                                                        $sql123 = "SELECT * FROM batch_formula_info_packsizes  WHERE batch_formula_info_id='".$row2["id"]."' ";
                                                        
                                                        $result123 = $conn->query($sql123);
                                                        if ($result1->num_rows > 0) {
                                                            while ($row123 = $result123->fetch_assoc()) {
                                                                $row123['batch_size']=$row123['batchSizeKG'];
                                                                $row123['pack_size']=intval($row123['pack_sizes']);
                                                                $row123['unit'] = trim(preg_replace('/[0-9]+/', '', $row123['pack_sizes']));

                                                                $output123[] = $row123;
                                                            }
                                                        }
                                               $row2["pack_sizes"] = $output123;          
                                         $output2[]=$row2;   
                                    }
                              }
                            $row1["bfr_records"] = $output2;
                            $output1[]=$row1;   
                    }
                     
                 }
                 $row["mfr_records"] = $output1;
                 
                 
                //  /////////////////////////////////////////////////////////////////////////
                
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
     }
      else if ($_GET["type"] == "save_plan") {
         // echo json_encode($input);
          $last_id=0;
        $sql = "Select count(*)+1 as count from batch_planning where  plant_id = '".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['count'];
        }
        if($last_id==0){
            $last_id=1;
        }
        $length = 4;
        $number = substr(str_repeat(0, $length).$last_id, - $length);
        $plan_no =  "PL".$number;
        
           $sql="INSERT INTO batch_planning(plant_id, plan_no, plan_for, plan_type, plan_for_market,
                plan_client_name, plan_client_code, client_po_no, client_po_date, product_type, product_code,
                product_name, grade, bfr_no, mfr_no, pack_size, pack_unit, batch_size,total_batches, dispatch_qty, planned_qty,
                qty_can_planned, no_of_batches_can_planned, status, entry_by, entry_date,client_po_qty,client_po_unit,plan_based_on,
                country_specific,pack_multiple_countries)
                values('".$_GET["plant_id"]."' ,'".$plan_no."' ,'".$input["plan_for"]."' ,'".$input["plan_type"]."',
                '".$input["plan_for_market"]."' ,'".$input["plan_client_name"]."' ,'".$input["client_code"]."' ,'".$input["client_po_no"]."',
                '".$input["client_po_date"]."' ,'".$input["dosage_form"]."' ,'".$input["product_name"]."' ,'".$input["product_name"]."',
                '".$input["grade"]."' ,'".$input["bfr_no"]."' ,'".$input["mfr_no"]."' ,'".$input["pack_size"]."' ,'".$input["pack_unit"]."',
                '".$input["batch_size"]."','".$input["number_of_batches"]."' ,'".$input["dispatch_qty"]."' ,'".$input["planned_qty"]."' ,'".$input["can_plan_qty"]."',
                '".$input["lowest_batch"]."' ,'Pending' ,'".$_GET["emp_id"]."', '$entry_date',
                '".$input["client_po_qty"]."', '".$input["client_po_unit"]."', '".$input["plan_based_on"]."',
                '".$input["country_specific"]."', '".$input["pack_multiple_countries"]."')";
               // echo $sql;
           if ($conn->query($sql)) {
            $batch_plan_id = $conn->insert_id;
	        $materials = json_decode(json_encode($input["raw_materials"]),true);
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql2="Insert into batch_planning_materials(plant_id,batch_plan_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,plan_qty,bfr_no,grade,
                   batches_can_plan,shortage_qty,stage) values('".$_GET["plant_id"]."' ,'".$batch_plan_id."',
                   'Raw Material','".$material["material_code"]."','".$material["qty"]."','".$material["unit"]."',
                   '".$material["overages"]."','".$material["total_qty"]."','".$material["batch_qty"]."','".$material["plan_qty"]."', '".$material["bfr_no"]."',
                   '".$material["grade"]."','".$material["can_plan_batches"]."','".$material["shortage_qty"]."','".$material["stage"]."')";
                   
                $conn->query($sql2);
                
            }
          
            $mat = json_decode(json_encode($input["packing_materials"]),true);
            for ($i = 0; $i < count($mat); $i++) {
                  $item = $mat[$i];
                $sql3="insert into batch_planing_raw_material_hdr(batch_plan_id,country_name,pack_size,pack_size_unit,pack_batch_size,pack_type,batch_size) values(
                      '".$batch_plan_id."','".$item["country_name"]."','".$item["pack_size"]."','".$item["unit"]."','".$item["pack_batch_size"]."',
                      '".$item["pack_type"]."','".$item["batch_size"]."'  )";
                if ($conn->query($sql3)) {
                    // $materials = $item['packing_materials'];
                    // $materials = json_decode(json_encode($input["packing_materials"]),true);
                    $pm_hdr_id = $conn->insert_id;
                    // for ($j = 0; $j < count($materials); $j++) {
                        //   $material = $materials[$j];
                        
                            $sql4="Insert into batch_planning_materials(plant_id,batch_plan_id,pm_hdr_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,plan_qty,
                            bfr_no,grade,pack_size,pack_unit,mf_batch_size,batches_can_plan,shortage_qty) values('".$_GET["plant_id"]."','".$batch_plan_id."','".$pm_hdr_id."','Packing Material','".$item["material_code"]."','".$item["qty"]."','".$item["unit"]."',
                               '".$item["pm_overages"]."','".$item["total_qty"]."','".($item["batch_qty"] ?? $item["batch_qtyqqqq"] ?? '')."','".$item["plan"]."',
                               '".$item["bfr_no"]."','".$item["grade"]."',
                               '".$item["pack_size"]."','".$item["pack_unit"]."','".$item["pm_batch_size"]."',
                               '".$item["can_plan"]."','".$item["short_qt"]."')";
                    
                    
                            //   old working code
                            // $sql4="Insert into batch_planning_materials(batch_plan_id,pm_hdr_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,plan_qty,
                            // bfr_no,grade,pack_size,pack_unit,mf_batch_size,batches_can_plan,shortage_qty) values(
                            //   '".$batch_plan_id."','".$pm_hdr_id."','Packing Material','".$material["material_code"]."','".$material["qty"]."','".$material["unit"]."',
                            //   '".$material["overages"]."','".$material["total_qty"]."','".$material["batch_qty"]."','".$material["plan_qty"]."',
                            //   '".$material["bfr_no"]."','".$material["grade"]."',
                            //   '".$material["pack_size"]."','".$material["pack_unit"]."','".$material["pm_batch_size"]."',
                            //   '".$material["can_plan_batches"]."','".$material["shortage_qty"]."')";
                            
                            $conn->query($sql4);
                    // }
                }
           }
	        echo "{\"status\":\"success\"}";
           } else{
                 echo "{\"status\":\"".$conn->error."\"}";
           }
          
      }
      else if ($_GET["type"] == "save_planMeha") {
          
          
         // echo json_encode($input);
          $last_id=0;
        $sql = "Select count(*)+1 as count from batch_planning where  plant_id = '".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['count'];
        }
        if($last_id==0){
            $last_id=1;
        }
        $length = 4;
        $number = substr(str_repeat(0, $length).$last_id, - $length);
        $plan_no =  "PL".$number;
        
           $sql="INSERT INTO batch_planning(plant_id, plan_no, plan_for, plan_type, plan_for_market,
                plan_client_name, plan_client_code, client_po_no, client_po_date, product_type, product_code,
                product_name, grade, bfr_no, mfr_no, pack_size, pack_unit, batch_size,total_batches, dispatch_qty, planned_qty,
                qty_can_planned, no_of_batches_can_planned, status, entry_by, entry_date,client_po_qty,client_po_unit,plan_based_on,
                country_specific,pack_multiple_countries)
                values('".$_GET["plant_id"]."' ,'".$plan_no."' ,'".$input["plan_for"]."' ,'".$input["plan_type"]."',
                '".$input["plan_for_market"]."' ,'".$input["plan_client_name"]."' ,'".$input["client_code"]."' ,'".$input["client_po_no"]."',
                '".$input["client_po_date"]."' ,'".$input["dosage_form"]."' ,'".$input["product_name"]."' ,'".$input["product_name"]."',
                '".$input["grade"]."' ,'".$input["bfr_no"]."' ,'".$input["mfr_no"]."' ,'".$input["pack_size"]."' ,'".$input["pack_unit"]."',
                '".$input["batch_size"]."','".$input["number_of_batches"]."' ,'".$input["dispatch_qty"]."' ,'".$input["planned_qty"]."' ,'".$input["can_plan_qty"]."',
                '".$input["lowest_batch"]."' ,'Pending' ,'".$_GET["emp_id"]."', '$entry_date',
                '".$input["client_po_qty"]."', '".$input["client_po_unit"]."', '".$input["plan_based_on"]."',
                '".$input["country_specific"]."', '".$input["pack_multiple_countries"]."')";
               // echo $sql;
           if ($conn->query($sql)) {
            $batch_plan_id = $conn->insert_id;
	        $materials = json_decode(json_encode($input["raw_materials"]),true);
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql2="Insert into batch_planning_materials(plant_id,batch_plan_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,plan_qty,bfr_no,grade,
                   batches_can_plan,shortage_qty,stage,dispensingIn) values('".$_GET["plant_id"]."' ,'".$batch_plan_id."',
                   '".$material["material_type"]."','".$material["material_code"]."','".$material["qty"]."','".$material["unit"]."',
                   '".$material["overages"]."','".$material["total_qty"]."','".$material["batch_qty"]."','".$material["plan_qty"]."', '".$material["bfr_no"]."',
                   '".$material["grade"]."','".$material["can_plan_batches"]."','".$material["shortage_qty"]."','".$material["stage"]."','".$material["dispensingIn"]."')";
                   
                $conn->query($sql2);
                
            }
          
            $mat = json_decode(json_encode($input["packing_materials"]),true);
            for ($i = 0; $i < count($mat); $i++) {
                  $item = $mat[$i];
                $sql3="insert into batch_planing_raw_material_hdr(batch_plan_id,country_name,pack_size,pack_size_unit,pack_batch_size,pack_type,batch_size) values(
                      '".$batch_plan_id."','".$item["country_name"]."','".$item["pack_size"]."','".$item["unit"]."','".$item["pack_batch_size"]."',
                      '".$item["pack_type"]."','".$item["batch_size"]."'  )";
                if ($conn->query($sql3)) {
                    // $materials = $item['packing_materials'];
                    // $materials = json_decode(json_encode($input["packing_materials"]),true);
                    $pm_hdr_id = $conn->insert_id;
                    // for ($j = 0; $j < count($materials); $j++) {
                        //   $material = $materials[$j];
                        
                            $sql4="Insert into batch_planning_materials(plant_id,batch_plan_id,pm_hdr_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,plan_qty,
                            bfr_no,grade,pack_size,pack_unit,mf_batch_size,batches_can_plan,shortage_qty) values('".$_GET["plant_id"]."','".$batch_plan_id."','".$pm_hdr_id."','Packing Material','".$item["material_code"]."','".$item["qty"]."','".$item["unit"]."',
                               '".$item["pm_overages"]."','".$item["total_qty"]."','".($item["batch_qty"] ?? $item["batch_qtyqqqq"] ?? '')."','".$item["plan"]."',
                               '".$item["bfr_no"]."','".$item["grade"]."',
                               '".$item["pack_size"]."','".$item["pack_unit"]."','".$item["pm_batch_size"]."',
                               '".$item["can_plan"]."','".$item["short_qt"]."')";
                    
                    
                            //   old working code
                            // $sql4="Insert into batch_planning_materials(batch_plan_id,pm_hdr_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,plan_qty,
                            // bfr_no,grade,pack_size,pack_unit,mf_batch_size,batches_can_plan,shortage_qty) values(
                            //   '".$batch_plan_id."','".$pm_hdr_id."','Packing Material','".$material["material_code"]."','".$material["qty"]."','".$material["unit"]."',
                            //   '".$material["overages"]."','".$material["total_qty"]."','".$material["batch_qty"]."','".$material["plan_qty"]."',
                            //   '".$material["bfr_no"]."','".$material["grade"]."',
                            //   '".$material["pack_size"]."','".$material["pack_unit"]."','".$material["pm_batch_size"]."',
                            //   '".$material["can_plan_batches"]."','".$material["shortage_qty"]."')";
                            
                            $conn->query($sql4);
                    // }
                }
           }
	        echo "{\"status\":\"success\"}";
           } else{
                 echo "{\"status\":\"".$conn->error."\"}";
           }
          
      
      }
      else if ($_GET["type"] == "get_raw_materials_by_bfr_no") {
            $output=Array();
         
    
              $sql="SELECT a.*,round(b.balance_qty,2) as avbl_stock,c.material_name from batch_materials a left JOIN vw_stock_summary b on 
              a.material_code = b.material_code and a.plant_id = b.plant_id left JOIN material c on a.material_code = c.material_code
              and c.plant_id = a.plant_id and a.material_type = c.material_type where a.bfr_no='".$_GET["bfr_no"]."'  and a.material_type='Raw Material' 
              and a.plant_id =  '".$_GET["plant_id"]."' ";     
      
        
            $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
      
       
       $output3['raw_materials'] =$output;
       echo json_encode($output3);
      }
      else if ($_GET["type"] == "get_raw_materials_by_bfr_noMeha") {
            $output=Array();
         
    
              $sql="
                          SELECT 
                a.*, 
                ROUND(
                    CASE 
                        WHEN a.material_type = 'Intermediate/Finish Product' THEN fg.qty 
                        ELSE vw.balance_qty 
                    END, 
                    2
                ) AS avbl_stock, 
                CASE 
                    WHEN a.material_type = 'Intermediate/Finish Product' 
                    THEN p.product_name 
                    ELSE m.material_name 
                END AS material_name
            FROM batch_materials a 
            LEFT JOIN vw_stock_summary vw 
                ON a.material_code = vw.material_code 
                AND a.plant_id = vw.plant_id 
                AND a.material_type = 'Raw Material' 
            LEFT JOIN fg_stock_book fg 
                ON a.material_code = fg.material_code 
                AND a.plant_id = fg.plant_id 
                AND a.material_type = 'Intermediate/Finish Product' 
            LEFT JOIN material m 
                ON a.material_code = m.material_code 
                AND m.plant_id = a.plant_id 
                AND a.material_type = 'Raw Material' 
            LEFT JOIN product p 
                ON a.material_code = p.product_code 
                AND p.plant_id = a.plant_id 
                AND a.material_type = 'Intermediate/Finish Product' 
            WHERE a.bfr_no = '".$_GET["bfr_no"]."'
            AND (a.material_type = 'Raw Material' OR a.material_type = 'Intermediate/Finish Product') ";     
      
        
            $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
      
       
       $output3['raw_materials'] =$output;
       echo json_encode($output3);
      }
     
     else if ($_GET["type"] == "get_packing_materials_by_mfr_id") {
         
        $output1 = Array();
       $sql= "select * from unitformula_pm_dtl where unit_formula_id = '".$_GET["unit_formula_id"]."'  ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                
                
                    $output2 = Array();
                    
                    $sql1 = "SELECT id,pack_size,batch_size,unit,packing_type FROM unitformula_pm_dtl WHERE unit_formula_id = '".$_GET["unit_formula_id"]."' order by pack_size";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output3 = Array();
                             $sql3 = "SELECT * FROM unitformula_packing_materials WHERE unit_formula_dtl_id='".$row1["id"]."' ";
                            $result3 = $conn->query($sql3);
                            if ($result3->num_rows > 0) {
                                while ($row3 = $result3->fetch_assoc()) {
                                   $output3[] = $row3; 
                                }
                            }
                            
                           $row1['packing_materials'] = $output3;
                           $output2[] = $row1;
                        }
                    }
                
                
                        $row["pack_size1"] = $output2;
                $output1[] = $row;
           }
       }
       
       
       
       $output1['pack_sizes'] =$output1;
       $output1['packing_materials'] =$output3;
       $output1['pack_size1'] =$output2;
       echo json_encode($output1);
      }
      else if ($_GET["type"] == "get_packing_materials_by_bfr_no") {
          $sql="SELECT a.*,c.material_name,IFNULL(b.avbl_stock,0) as avbl_stock  from batch_materials a left join 
          (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
           WHERE material_code in(SELECT material_code from batch_materials where bfr_no='".$_GET["bfr_no"]."'
           and material_type='Packing Material'
           and pack_size='".$_GET["pack_size"]."'
           and pack_unit='".$_GET["pack_unit"]."'
           and batch_qty>0)
          GROUP by material_code) as b on a.material_code = b.material_code join master_material 
          c on a.material_code = c.material_code where a. bfr_no='".$_GET["bfr_no"]."' 
          and a.material_type='Packing Material'
          and a.pack_size='".$_GET["pack_size"]."'
          and a.pack_unit='".$_GET["pack_unit"]."'
          and a.batch_qty>0";
          
            $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
      }
     
     
      else if ($_GET["type"] == "update_batch_formula_status") {
           $packing_materials = json_decode(json_encode($input),true);
           $id = intval($packing_materials["id"] ?? 0);
           $rawStatus = trim($packing_materials["status"] ?? '');
           if ($rawStatus === '' || strcasecmp($rawStatus, 'Approve') === 0) {
               $newStatus = 'Approve';
           } else if (strcasecmp($rawStatus, 'Reject') === 0 || strcasecmp($rawStatus, 'Rejected') === 0) {
               $newStatus = 'Reject';
           } else {
               $newStatus = $conn->real_escape_string($rawStatus);
           }
           $pm_pack_unit = $conn->real_escape_string($packing_materials["pm_pack_unit"] ?? '');
           $pm_pack_size = $conn->real_escape_string($packing_materials["pm_pack_size"] ?? '');
           $packJson = '';
           if (isset($packing_materials["packing_materials"]) && $packing_materials["packing_materials"] !== '') {
               $packJson = $conn->real_escape_string(json_encode($packing_materials["packing_materials"]));
           }
           $setPack = ($packJson !== '') ? ", packing_materials = '".$packJson."'" : '';
           $setPmUnit = ($pm_pack_unit !== '') ? ", pm_pack_unit = '".$pm_pack_unit."'" : '';
           $setPmSize = ($pm_pack_size !== '') ? ", pm_pack_size = '".$pm_pack_size."'" : '';
        $sql = "Update batch_formula_info set approve_by = '".$_GET["emp_id"]."',
                status = '".$newStatus."',
                approve_date = '".$entry_date."'".$setPack.$setPmUnit.$setPmSize." where id='".$id."'";
         if ($conn->query($sql)) {
           
            echo "{\"status\":\"success\"}";
        }  else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
     }
       else if ($_GET["type"] == "update_batch_formula_status_Zuma") {
          
           $packing_materials = json_decode(json_encode($input),true);
          if($packing_materials["status"]=='Review'){
              $by='checked_by';
              $date='checked_date';
          }else{
                $by='approve_by';
              $date='approve_date';
          }
          
          
         $sql = "Update batch_formula_info set $by = '".$_GET["emp_id"]."',
                status = '".$packing_materials["status"]."',
                pm_pack_unit  = '".$packing_materials["pm_pack_unit"]."',
                pm_pack_size  = '".$packing_materials["pm_pack_size"]."',
                 $date = '".$entry_date."' where  id='".$packing_materials["id"]."'";
         if ($conn->query($sql)) {
           
            echo "{\"status\":\"success\"}";
        }  else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
     }
     else if ($_GET["type"] == "get_pack_size_by_mfrno") {
         
     $sql ="SELECT DISTINCT a.id , a.*,d.bfr_no ,b.overages as pm_overages,b.material_subtype,b.unit_formula_dtl_id,b.material_name,b.qty,b.unit_name,b.role,b.total_qty,
        b.material_code,b.grade,d.batch_formula_weight,COALESCE(e.balance_qty,'0') AS balance_qty FROM unitformula_pm_dtl a left JOIN unitformula_packing_materials b on 
        a.id=b.unit_formula_dtl_id LEFT JOIN unitformula c on a.unit_formula_id=c.id LEFT JOIN batch_formula_info d on c.mfr_no=d.mfr_no LEFT JOIN vw_stock_summary e
        on b.material_code=e.material_code LEFT JOIN batch_materials f on d.bfr_no=f.bfr_no WHERE d.batch_formula_weight!='' and
         b.unit_formula_dtl_id='".$_GET["unit_formula_dtl_id"]."' and f.bfr_no='".$_GET["bfr_no"]."'" ; // 04/09/23
    
        
        $result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
     
    
         
     
         
     }
     
     else if ($_GET["type"] == "save_work_order") {
         
          $last_id=0;
        $sql = "Select count(*)+1 as count from mfg_work_order_hdr where  plant_id = '".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['count'];
        }
        if($last_id==0){
            $last_id=1;
        }
        $length = 4;
        $number = substr(str_repeat(0, $length).$last_id, - $length);
        $work_order_no =  "WO".$number;
        
        $sql = "INSERT INTO mfg_work_order_hdr (plant_id,batch_id,work_order_no,lod_status,assay_status,
        batch_overages,overages_percent,ebmr_status,entry_by)
        values('".$_GET["plant_id"]."','".$input["selected_batch_index"]."','".$work_order_no."','".$input["lod_criteria"]."',
               '".$input["assay_criteria"]."','".$input["batch_overages"]."','".$input["overages_percent"]."',
               '".$input["ebmr_status"]."','".$_GET["emp_id"]."')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $r_materials = $input["raw_materials"];
            for ($i = 0; $i <count($r_materials); $i++) {
                $material = $r_materials[$i];
                $sql = "INSERT INTO  mfg_work_order_dtl(work_order_id,material_code,unit_qty,unit,grade,overages_percent,total_qty,batch_qty,
                lod_status,assay_status,final_batch_qty)
                values('".$last_id."','".$material["material_code"]."','".$material["qty"]."',
                '".$material["unit"]."', '".$material["grade"]."','".$material["batch_overages"]."','".$material["total_qty"]."','".$material["batch_qty"]."',
                '".$material["lod_status"]."','".$material["assay_status"]."','".$material["total_final_qty"]."')";
                $conn->query($sql);
                
            }
            $p_materials = $input["packing_materials"];
            for ($i = 0; $i <count($p_materials); $i++) { 
                  $material = $p_materials[$i];
                $sql = "INSERT INTO  mfg_work_order_dtl(work_order_id,material_code,unit_qty,unit,grade,overages_percent,total_qty,batch_qty,
                lod_status,assay_status,final_batch_qty)
                values('".$last_id."','".$material["material_code"]."','".$material["qty"]."',
                '".$material["unit"]."', '".$material["grade"]."','".$material["batch_overages"]."','".$material["total_qty"]."','".$material["batch_qty"]."',
                '".$material["lod_status"]."','".$material["assay_status"]."','".$material["total_final_qty"]."')";
                $conn->query($sql);
                 
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
     else if ($_GET["type"] == "downloadPlan") {
        $_GET['filename'] = 'Raw Material Planning for 01-2022'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Raw Material Planning for 01-2022</h2>
        <table cellpadding="5" border="1">
        <tr>
            <td style="width:30%; text-align:centre;"><b>Product Type</b></td>
            <td style="width:40%; text-align:centre;"><b>Product Name</b></td>
            <td style="width:30%; text-align:centre;"><b>Batch Size</b></td>
        </tr>
        </table>
        <h3>Raw Materials:</h3>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:5%; text-align:centre;" rowspan="2"><b>Sr.</b></td>
                <td style="width:10%; text-align:centre;" rowspan="2"><b>Particular</b></td>
                <td style="width:5%; text-align:centre;" rowspan="2"><b>Unit</b></td>
                <td style="width:15%; text-align:centre;"><b>Available</b></td>
                <td style="width:10%; text-align:centre;" rowspan="2"><b>Mimimum Stock</b></td>
                <td style="width:10%; text-align:centre;" rowspan="2"><b>kg/kg. Consumption</b></td>
                <td style="width:10%; text-align:centre;" rowspan="2"><b>Required for Month as per above planning</b></td>
                <td style="width:10%; text-align:centre;" rowspan="2"><b>Total Required</b></td>
                <td style="width:10%; text-align:centre;" rowspan="2"><b>Net Requirement</b></td>
                <td style="width:15%; text-align:centre;"><b>Schedule<br>(Material received date to Factory)</b></td>
            </tr>
            <tr>
                <td style="width:5%; text-align:centre;"><b>Warehouse as on 2022-01-17</b></td>
                <td style="width:5%; text-align:centre;"><b>WIP<br>(Sol. stock)</b></td>
                <td style="width:5%; text-align:centre;"><b>Total</b></td>
                <td style="width:5%; text-align:centre;"><b>Jan 02 - 10</b></td>
                <td style="width:5%; text-align:centre;"><b>Jan 11 - 20</b></td>
                <td style="width:5%; text-align:centre;"><b>Jan 21 - 31</b></td>
            </tr>
            <tr>
                <td style="width:5%;"></td>
                <td style="width:10%;"></td>
                <td style="width:5%;"></td>
                <td style="width:5%;"></td>
                <td style="width:5%;"></td>
                <td style="width:5%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:5%;"></td>
                <td style="width:5%;"></td>
                <td style="width:5%;"></td>
            </tr>
        </table>
        <h3>Raw Materials Common:</h3>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;" rowspan="2"><b>Sr.</b></td>
                <td style="width:10%; text-align:centre;" rowspan="2"><b>Particular</b></td>
                <td style="width:10%; text-align:centre;" rowspan="2"><b>Unit</b></td>
                <td style="width:10%; text-align:centre;" rowspan="2"><b>Stock as on 2022-01-17</b></td>
                <td style="width:10%; text-align:centre;" rowspan="2"><b>Mimimum Stock</b></td>
                <td style="width:20%; text-align:centre;"><b>Requirement</b></td>
                <td style="width:10%; text-align:centre;" rowspan="2"><b>Net Requirement</b></td>
                <td style="width:20%; text-align:centre;"><b>Schedule<br>(Material received date to Factory)</b></td>
            </tr>
            <tr>
                <td style="width:10%; text-align:centre;"><b>Jan 02 - 10</b></td>
                <td style="width:10%; text-align:centre;"><b>Total</b></td>
                <td style="width:10%; text-align:centre;"><b>Jan 11 - 20</b></td>
                <td style="width:10%; text-align:centre;"><b>Jan 21 - 31</b></td>
            </tr>
            <tr>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
            </tr>
        </table>
        ';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Raw Material Planning for 01-2022.pdf', 'I');
    }
    
}else {
    echo "{\"status\":\"invalid\"}";
}
 }catch (\Throwable $e) {
               echo "{\"statuse\":\"".$e."\"}";
             //	echo "{\"status\":\"exception\"}";
            }
$conn->close();
 
?>