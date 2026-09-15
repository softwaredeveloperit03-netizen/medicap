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



    require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    
    // // ini_set('display_errors', 1);
    // // error_reporting(E_ALL);
    
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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "getApprovedProducts") {
        $output = Array();
        $sql = "SELECT * FROM product WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalents"] = json_decode($row["equivalents"]);
                
                $stages = '[{"stage_no": "1", "stage": "DISPENSING"}, {"stage_no": "2", "stage": "DECARTONING OF AMPOULES"}, {"stage_no": "3", "stage": "RECONCILLATION OF AMPULES AFTER DECARTONING"}, {"stage_no": "4", "stage": "WASHING & DEPYROGENATION OF AMPOULES"}, {"stage_no": "5", "stage": "RECONCILLATION OF AMPULES AFTER WASHING"}, {"stage_no": "6", "stage": "DRY HEAT STERILIZATION"}, {"stage_no": "7", "stage": "CLEANING OF FILLING ACCESSORIES, FILTRATION ASSEMBLY & EQUIPMENTS"}, {"stage_no": "8", "stage": "STEAM STERILIZATION OF FILLING ACCESSORIES, FILTRATION ASSEMBLY AND EQUIPMENTS"}, {"stage_no": "9", "stage": "MANUFACTURING VESSELCLENING RECORD"}, {"stage_no": "10", "stage": "BATCH FILTRATION PROCESS"}, {"stage_no": "11", "stage": "FILLING PROCESS"}, {"stage_no": "12", "stage": "LEAK TEST OF AMPOLES"}, {"stage_no": "13", "stage": "VISUAL INSPECTION"}, {"stage_no": "14", "stage": "ACCOUNTIBILITY OF PROCESS LOSS/REJECTION"}, {"stage_no": "15", "stage": "ACCOUNTIBILITY OF SAMPLING DURING PROCESS"}]';
                $row["stages"] = json_decode($stages);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
      else if ($_GET["type"] == "getUnitFormulaLogZuma") {
      
        $output = Array();
     
      $sql = "SELECT DISTINCT  u.id,u.bom_type,u.master_formula_type,u.bom_batch_size,u.bom_batch_size_unit,u.product_type,u.mfr_no,u.product_code,u.formula_for,u.consumeableMaterial, u.average_weight,u.raw_materials,u.batch_size,u.unit,u.primary_pm_list,u.primary_pm_batch_size,u.status,
u.min_per,u.max_per,u.min_output_qty,u.max_output_qty,u.version_no,u.revison_history,u.theoritical_yield,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
(select grade from product p where u.product_code=p.product_code limit 1) as grade,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select color_index from product p where u.product_code=p.product_code limit 1) as color_index,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_code1 from product p where u.product_code=p.product_code limit 1) as product_code1
FROM unitformula u  where u.plant_id='".$_GET["plant_id"]."' order by u.id DESC;";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
                            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,product_brand_name,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["id"]."' ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row2['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ ? $resQ->fetch_assoc() : null; 
         
          $row2['gradeName'] = $prodLatest['gradeName'] ?? ''; 
                                
                                 $output2[] = $row2;
                            }
                        }
                        $row1['packing_materials'] = $output2;

                        $output1[] = $row1;
                    }
                    
                }
                
                 $output3 = Array();
                $sql3 = "select product_name from product where product_code ='".$row["product_code"]."' ";
               
                $result3 = $conn->query($sql3);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        $output3 = $row3;
                    }
                }
                
                
                $row['packing_configuration'] =$output1;
                $row['product_name1'] =$output3;
                $row["raw_materials"] = json_decode($row["raw_materials"]); 
                $row["label_claim"] = json_decode($row["label_claim"]); 
                $row["primary_pm_list"] = json_decode($row["primary_pm_list"]); 
                $row["consumeableMaterial"] = json_decode($row["consumeableMaterial"]); 
                $row["product_name2"] = json_decode($row["product_name"][0]); 
                $hist = json_decode((string)($row['revison_history'] ?? ''), true);
                $row['review_date'] = '';
                if (is_array($hist) && count($hist) > 0) {
                    $last = end($hist);
                    if (is_array($last)) {
                        $row['review_date'] = $last['review_date'] ?? $last['effective_date'] ?? '';
                    }
                }
                $output[] = $row;
            }
        }
       $output = utf8ize($output);
echo json_encode($output);
    }
    else if ($_GET["type"] == "getPM_SubTypes") {
        $output = Array();
        $sql = "SELECT material_subtype FROM material WHERE  material_type='Packing Material'  group by material_subtype";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRawMaterials") {
        $output = Array();
        $sql = "SELECT * FROM material WHERE  material_type='Raw Material'  ORDER BY material_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPackingMaterials") {
        $output = Array();
        $sql = "SELECT * FROM material WHERE status='approve' AND material_type='Packing Material' AND material_subtype='".$_GET["material_type"]."' ORDER BY material_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getUnitFormulaLogZuma") {

    header('Content-Type: application/json');

    $output = array();

    // 🔐 Basic sanitization
    $plant_id = mysqli_real_escape_string($conn, $_GET["plant_id"]);

    $sql = "SELECT DISTINCT
        u.id,u.bom_type,u.master_formula_type,u.bom_batch_size,
        u.bom_batch_size_unit,u.product_type,u.mfr_no,u.product_code,
        u.formula_for,u.consumeableMaterial,u.average_weight,
        u.raw_materials,u.batch_size,u.unit,u.primary_pm_list,
        u.primary_pm_batch_size,u.status,
        u.min_per,u.max_per,u.min_output_qty,u.max_output_qty,u.version_no,u.revison_history,u.theoritical_yield,

        (SELECT product_name FROM product p WHERE u.product_code=p.product_code LIMIT 1) AS product_name,
        (SELECT grade FROM product p WHERE u.product_code=p.product_code LIMIT 1) AS grade,
        (SELECT generic_name FROM product p WHERE u.product_code=p.product_code LIMIT 1) AS generic_name,
        (SELECT color_index FROM product p WHERE u.product_code=p.product_code LIMIT 1) AS color_index,
        (SELECT product_type FROM product p WHERE u.product_code=p.product_code LIMIT 1) AS product_type2,
        (SELECT dosage_form FROM product p WHERE u.product_code=p.product_code LIMIT 1) AS dosage_form,
        (SELECT shelf_life FROM product p WHERE u.product_code=p.product_code LIMIT 1) AS shelf_life,
        (SELECT label_claim FROM product p WHERE u.product_code=p.product_code LIMIT 1) AS label_claim,
        (SELECT product_code1 FROM product p WHERE u.product_code=p.product_code LIMIT 1) AS product_code1

        FROM unitformula u
        WHERE u.plant_id = '$plant_id'
        ORDER BY u.id DESC";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {

        while ($row = $result->fetch_assoc()) {

            // ================= Packing Config =================
            $output1 = array();

            $unit_formula_id = (int)$row["id"];

            $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,
                            country_name,packing_type,pack_size,batch_size,unit 
                     FROM unitformula_pm_dtl 
                     WHERE unit_formula_id = '$unit_formula_id'";

            $result1 = $conn->query($sql1);

            if ($result1 && $result1->num_rows > 0) {

                while ($row1 = $result1->fetch_assoc()) {

                    $output2 = array();
                    $dtl_id = (int)$row1["id"];

                    // ================= Packing Materials =================
                    $sql2 = "SELECT * FROM unitformula_packing_materials 
                             WHERE unit_formula_dtl_id = '$dtl_id'";

                    $result2 = $conn->query($sql2);

                    if ($result2 && $result2->num_rows > 0) {

                        while ($row2 = $result2->fetch_assoc()) {

                            $grade_ids = mysqli_real_escape_string($conn, $row2["grade"]);

                            

                            $output2[] = $row2;
                        }
                    }

                    $row1['packing_materials'] = $output2;
                    $output1[] = $row1;
                }
            }

            // ================= Product Name =================
            $product_code = mysqli_real_escape_string($conn, $row["product_code"]);

            $sql3 = "SELECT product_name 
                     FROM product 
                     WHERE product_code = '$product_code'";

            $result3 = $conn->query($sql3);

            $output3 = array();
            if ($result3 && $result3->num_rows > 0) {
                $output3 = $result3->fetch_assoc();
            }

            // ================= Final Formatting =================
            $row['packing_configuration'] = $output1;
            $row['product_name1'] = $output3;

            $row["raw_materials"] = json_decode($row["raw_materials"], true);
            $row["label_claim"] = json_decode($row["label_claim"], true);
            $row["primary_pm_list"] = json_decode($row["primary_pm_list"], true);
            $row["consumeableMaterial"] = json_decode($row["consumeableMaterial"], true);

            // Fixed issue
            $row["product_name2"] = $row["product_name"];

            $hist = json_decode((string)($row['revison_history'] ?? ''), true);
            $row['review_date'] = '';
            if (is_array($hist) && count($hist) > 0) {
                $last = end($hist);
                if (is_array($last)) {
                    $row['review_date'] = $last['review_date'] ?? $last['effective_date'] ?? '';
                }
            }

            $output[] = $row;
        }
    }

    echo json_encode($output, JSON_PRETTY_PRINT);
}
    
    else if ($_GET["type"] == "getunitFormulaMaterial") {
        $output = Array();
        $sql = "select * from unitFormulaMaterial a where a.mfr_no ='".$_GET["mfr_no"]."' ";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if($row['material_type']=='Base'){
                   $output1 = Array();
                       $sql1 = "select * from unitformula a left join unitFormulaMaterial b on a.mfr_no=b.mfr_no where a.product_code='".$row["material_code"]."' ";
                         $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                         $row["BaseMaterials"] = $output1;
                }
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
else if ($_GET["type"] == "saveUnitFormula") {
        
        $id = date("YmdHis", $timestamp);
        
        $sql = "INSERT INTO unitformula (plant_id,user_no, product_code, average_weight ,version_no, entry_by, entry_date)
        VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."','".$input["product_code"]."','".$input["average_weight"]."' , '0', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $materials = $input["raw_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO unit_materials (user_no,mfr_no, material_type, material_code, qty, unit, overages, role, process,yield_contributing) VALUES ('".$_GET["user_no"]."','$last_id', 'Raw Material', '".$material['material_code']."', '".$material['qty']."', '".$material['unit']."', '".$material['overages']."', '".$material['role']."', '".$material['process']."','".$material["yield_contributing"]."')";
                $conn->query($sql1);
            }
            
            $materials = $input["packing_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO unit_materials (user_no,mfr_no, material_type, material_code, qty, unit, overages) VALUES ('".$_GET["user_no"]."','$last_id', 'Packing Material', '".$material['material_code']."', '".$material['qty']."', '".$material['unit']."', '".$material['overages']."')";
                $conn->query($sql1);
            }
            
            $materials = $input["additional_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO unit_materials (user_no, mfr_no, material_type, material_code, qty, unit, overages, role, process) VALUES ('".$_GET["user_no"]."','$last_id', 'Additional Material', '".$material['material_code']."', '".$material['qty']."', '".$material['unit']."', '".$material['overages']."', '".$material['role']."', '".$material['process']."')";
                $conn->query($sql1);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getPendingUnitFormulas") {
        $output = Array();
        $sql = "SELECT u.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code WHERE u.user_no='".$_GET["user_no"]."' AND u.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Raw Material'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["raw_materials"] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Additional Material'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["additional_materials"] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Packing Material'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["packing_materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveCombiMaster") {
        
        $sql = "INSERT INTO CombiMaster (plant_id, combi_product_name, combi_product_code)
        VALUES ('".$_GET["plant_id"]."', '".$input["combi_product_name"]."', '".$input["combi_product_code"]."')";

if ($conn->query($sql)) {

    // Get Last Inserted CombiMaster ID
    $last_id = $conn->insert_id;

    $array = $input["combiProductList"];
    $status1 = true;

    foreach ($array as $values) {

        $sql = "INSERT INTO CombiMaster_dtl 
                (`plant_id`, `CombiMaster_id`, `category`, `dosage_form`, `dosage_type`, `product_name`, `Qty`, `Qty_unit`,for_product,product_code)
                VALUES (
                    '".$_GET['plant_id']."',
                    '".$last_id."',
                    '".$values['category']."',
                    '".$values['dosage_form']."',
                    '".$values['dosage_type']."',
                    '".$values['product_name']."',
                    '".$values['Qty']."',
                    '".$values['Qty_unit']."',
                    '".$input["combi_product_code"]."',
                      '".$values['product_code']."'
                )";

        if (!$conn->query($sql)) {
            $status1 = false;
            break;
        }
    }

    if ($status1) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }

} else {
    echo "{\"status\":\"failed\"}";
}

    }
    else if ($_GET["type"] == "checkingUnitFormula") {
        $sql = "UPDATE unitformula SET status='".$_GET["status"]."', checked_by='".$_GET["emp_id"]."', checked_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if ($_GET["type"] == "approveUnitFormula") {
        $sql = "UPDATE unitformula SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            $newStatus = strtolower(trim($_GET["status"] ?? ''));
            if ($newStatus === 'approve' || $newStatus === 'approved') {
                $ufId = intval($_GET["id"]);
                $ufRes = $conn->query("SELECT * FROM unitformula WHERE id='".$ufId."' LIMIT 1");
                if ($ufRes && ($uf = $ufRes->fetch_assoc())) {
                    $plantEsc = $conn->real_escape_string($uf["plant_id"] ?? ($_GET["plant_id"] ?? ''));
                    $mfrEsc = $conn->real_escape_string($uf["mfr_no"] ?? '');
                    $codeEsc = $conn->real_escape_string($uf["product_code"] ?? '');
                    $existSql = "SELECT id, status FROM batch_formula_info
                        WHERE plant_id='".$plantEsc."' AND mfr_no='".$mfrEsc."' AND product_code='".$codeEsc."'
                        ORDER BY id DESC LIMIT 1";
                    $existRes = $conn->query($existSql);
                    if ($existRes && $existRes->num_rows > 0) {
                        $bfr = $existRes->fetch_assoc();
                        $cur = strtolower(trim($bfr["status"] ?? ''));
                        if ($cur === '' || $cur === 'pending') {
                            $conn->query("UPDATE batch_formula_info SET status='For QA Approval' WHERE id='".intval($bfr["id"])."'");
                        }
                    } else {
                        $countRes = $conn->query("SELECT COUNT(*) + 1 AS count FROM batch_formula_info WHERE plant_id='".$plantEsc."'");
                        $next = 1;
                        if ($countRes && ($cRow = $countRes->fetch_assoc())) {
                            $next = intval($cRow["count"]);
                        }
                        if ($next < 1) { $next = 1; }
                        $BFR_NO = "BFR".str_pad($next, 4, "0", STR_PAD_LEFT);
                        $ptype = $conn->real_escape_string($uf["product_type"] ?? '');
                        $batchSize = $conn->real_escape_string($uf["batch_size"] ?? $uf["bom_batch_size"] ?? '');
                        $batchUnit = $conn->real_escape_string($uf["unit"] ?? $uf["bom_batch_size_unit"] ?? '');
                        $rawJson = $conn->real_escape_string($uf["raw_materials"] ?? '');
                        $packJson = $conn->real_escape_string($uf["packing_materials"] ?? '');
                        $ins = "INSERT INTO batch_formula_info
                            (plant_id, mfr_no, bfr_no, product_type, product_code,
                             unit_formula_batch_weight, batch_formula_weight, raw_materials, packing_materials,
                             status, entry_by, entry_date, rm_batch_size_unit)
                            VALUES (
                            '".$plantEsc."', '".$mfrEsc."', '".$BFR_NO."', '".$ptype."', '".$codeEsc."',
                            '".$batchSize."', '".$batchSize."', '".$rawJson."', '".$packJson."',
                            'For QA Approval', '".$conn->real_escape_string($_GET["emp_id"] ?? '')."',
                            '".$entry_date."', '".$batchUnit."')";
                        $conn->query($ins);
                    }
                }
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if ($_GET["type"] == "getUnitFormulasforChecking") {
        $output = Array();
        $plant_id=$_GET['plant_id'];
 
             $sql = "SELECT DISTINCT  u.id,u.product_type as p_type,u.mfr_no,u.product_code,u.formula_for, u.average_weight,u.average_weight_unit as unit,u.theoritical_yield,u.min_per,u.max_per,u.status,u.entry_by,u.entry_date,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
 (select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name
 FROM unitformula u  where    u.checked_by is null
         and u.plant_id='$plant_id' and u.id in(select unit_formula_id from unitformula_pm_dtl) order by 1 desc";
      
             $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["id"]."' ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row2['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row2['gradeName'] = $prodLatest['gradeName']; 
                                
                                 $output2[] = $row2;
                            }
                            
                        }
                          
                        $row1['packing_materials'] = $output2;

                        $output1[] = $row1;
                    }
                    
                }
                
                $output11 = Array();
    $sql11 = "select * from unitFormulaMaterial a where a.mfr_no ='".$row["mfr_no"]."' ";
    $result11 = $conn->query($sql11);
            if ($result11->num_rows > 0) {
                while ($row11 = $result11->fetch_assoc()) {
                    if($row11['material_type']=='Base'){
                       $output123 = Array();
                           $sql123 = "select * from unitformula a left join unitFormulaMaterial b on a.mfr_no=b.mfr_no where a.product_code='".$row11["material_code"]."' ";
                             $result123 = $conn->query($sql123);
                            if ($result123->num_rows > 0) {
                                while ($row123 = $result123->fetch_assoc()) {
                                    $output123[] = $row123;
                                }
                            }
                             $row11["BaseMaterials"] = $output123;
                    }
                    
                    $output11[] = $row11;
                }
            }
                
                
                
                
                 $row['raw_materials'] =$output11;
                $row['packing_configuration'] =$output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "getUnitFormulasforCheckingzUMA") {
        $output = Array();
        $plant_id=$_GET['plant_id'];
   
             $sql = "SELECT DISTINCT  u.id,u.bom_type,u.master_formula_type,u.product_type as p_type,u.mfr_no,u.product_code,u.formula_for, u.average_weight,u.raw_materials,u.batch_size,u.consumeableMaterial,u.unit,u.primary_pm_list,u.primary_pm_batch_size,
u.min_per,u.max_per,u.min_output_qty,u.max_output_qty,u.version_no,u.revison_history,u.theoritical_yield,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
(select grade from product p where u.product_code=p.product_code limit 1) as grade,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_code1 from product p where u.product_code=p.product_code limit 1) as product_code1
FROM unitformula u  where    u.checked_by is null
         and u.plant_id='$plant_id' and u.id in(select unit_formula_id from unitformula_pm_dtl) order by 1 desc";
      
             $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,product_brand_name,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["id"]."' ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row2['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row2['gradeName'] = $prodLatest['gradeName']; 
                                
                                 $output2[] = $row2;
                            }
                            
                        }
                          
                        $row1['packing_materials'] = $output2;

                        $output1[] = $row1;
                    }
                    
                }
                   $row["raw_materials"] = json_decode($row["raw_materials"]);
                $row['packing_configuration'] =$output1;
                 $row["primary_pm_list"] = json_decode($row["primary_pm_list"]); 
                 $row["consumeableMaterial"] = json_decode($row["consumeableMaterial"]); 
                $hist = json_decode((string)($row['revison_history'] ?? ''), true);
                $row['review_date'] = '';
                if (is_array($hist) && count($hist) > 0) {
                    $last = end($hist);
                    if (is_array($last)) {
                        $row['review_date'] = $last['review_date'] ?? $last['effective_date'] ?? '';
                    }
                }

                $output[] = $row;
            }
        }
       $output = utf8ize($output);
echo json_encode($output);
    }
    else if ($_GET["type"] == "getUnitFormulasforApproval") {
        $output = Array();
         $sql = "   SELECT DISTINCT  u.id,u.product_type as p_type,u.mfr_no,u.product_code,u.formula_for, u.average_weight,u.average_weight_unit as unit,u.average_weight_unit as unit,u.theoritical_yield,u.min_per,u.max_per,u.status,u.entry_by,u.entry_date,u.checked_by,u.checked_date,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
 (select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name
 FROM unitformula u  where u.plant_id='".$_GET["plant_id"]."' and u.checked_by is not null and u.approve_by is null order by u.id DESC;";
        // $sql = "SELECT Distinct u.*, p.product_name, p.grade, p.generic_name,p.product_type,p.product_code1, p.dosage_form, p.shelf_life, p.label_claim,u.product_type as p_type FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code  where u.checked_by!='' and u.approve_by=''
        // and u.plant_id='".$_GET["plant_id"]."' order by 1 desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["id"]."' ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                
                                     $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row2['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row2['gradeName'] = $prodLatest['gradeName']; 
                                 $output2[] = $row2;
                            }
                            
                        }
                        $row1['packing_materials'] = $output2;
                        
                        
                        
                        
                        
                        
                        
                        
                         
                        $output1[] = $row1;
                    }
                    
                }
                
  $output11 = Array();
    $sql11 = "select * from unitFormulaMaterial a where a.mfr_no ='".$row["mfr_no"]."' ";
    $result11 = $conn->query($sql11);
            if ($result11->num_rows > 0) {
                while ($row11 = $result11->fetch_assoc()) {
                    if($row11['material_type']=='Base'){
                       $output123 = Array();
                           $sql123 = "select * from unitformula a left join unitFormulaMaterial b on a.mfr_no=b.mfr_no where a.product_code='".$row11["material_code"]."' ";
                             $result123 = $conn->query($sql123);
                            if ($result123->num_rows > 0) {
                                while ($row123 = $result123->fetch_assoc()) {
                                    $output123[] = $row123;
                                }
                            }
                             $row11["BaseMaterials"] = $output123;
                    }
                    
                    $output11[] = $row11;
                }
            }
                
                
                
                
                 $row['raw_materials'] =$output11;
                $row['packing_configuration'] =$output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "getUnitFormulasforApprovalZuma") {
        $output = Array();
         $sql = "   SELECT DISTINCT  u.id,u.bom_type,u.master_formula_type,u.product_type as p_type,u.mfr_no,u.product_code,u.bom_batch_size,u.formula_for,u.consumeableMaterial, u.average_weight,u.raw_materials,u.batch_size,u.unit,u.primary_pm_list,u.primary_pm_batch_size,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
(select grade from product p where u.product_code=p.product_code limit 1) as grade,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_code1 from product p where u.product_code=p.product_code limit 1) as product_code1
FROM unitformula u  where u.plant_id='".$_GET["plant_id"]."' and u.checked_by is not null and u.approve_by is null order by u.id DESC;";
        // $sql = "SELECT Distinct u.*, p.product_name, p.grade, p.generic_name,p.product_type,p.product_code1, p.dosage_form, p.shelf_life, p.label_claim,u.product_type as p_type FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code  where u.checked_by!='' and u.approve_by=''
        // and u.plant_id='".$_GET["plant_id"]."' order by 1 desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,product_brand_name,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["id"]."' ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                
                                     $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row2['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row2['gradeName'] = $prodLatest['gradeName']; 
                                 $output2[] = $row2;
                            }
                            
                        }
                        $row1['packing_materials'] = $output2;
                        
                        
                        
                        
                        
                        
                        
                        
                         
                        $output1[] = $row1;
                    }
                    
                }
                $row['packing_configuration'] =$output1;
                  $row["primary_pm_list"] = json_decode($row["primary_pm_list"]); 
                  $row["consumeableMaterial"] = json_decode($row["consumeableMaterial"]); 
                $output[] = $row;
            }
        }
       $output = utf8ize($output);
echo json_encode($output);
    }
    else if ($_GET["type"] == "get_approved_UnitFormulas") {
        $output = Array();
         $sql = "   SELECT * , u.average_weight_unit as unit,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
 (select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select pack_sizes from product p where u.product_code=p.product_code limit 1) as pack_sizes
 FROM unitformula u  where u.checked_by is not null  and u.approve_by is not null and u.plant_id='".$_GET["plant_id"]."' order by u.id DESC;";
        // $sql = "SELECT Distinct u.*, p.product_name, p.grade, p.generic_name, p.shelf_life, 
        // p.label_claim FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code 
        // where u.checked_by!=''  and u.approve_by!=''
        // and u.plant_id='".$_GET["plant_id"]."' order by 1 desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["pack_sizes"] = json_decode($row["pack_sizes"]);
                $output1 = Array();
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["id"]."' ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                  $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row2['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row2['gradeName1'] = $prodLatest['gradeName']; 
                                 $output2[] = $row2;
                            }
                            
                        }
                        $row1['packing_materials'] = $output2;
                         
                        $output1[] = $row1;
                    }
                    
                }
                
                
            $output11 = Array();
    // Table name is lower case on the live server (case-sensitive MySQL).
    $sql11 = "select * from unitformulamaterial a where a.mfr_no ='".$row["mfr_no"]."' ";
    $result11 = $conn->query($sql11);
            if ($result11 && $result11->num_rows > 0) {
                while ($row11 = $result11->fetch_assoc()) {
                    if($row11['material_type']=='Base'){
                       $output123 = Array();
                           $sql123 = "select * from unitformula a left join unitformulamaterial b on a.mfr_no=b.mfr_no where a.product_code='".$row11["material_code"]."' ";
                             $result123 = $conn->query($sql123);
                            if ($result123 && $result123->num_rows > 0) {
                                while ($row123 = $result123->fetch_assoc()) {
                                    $output123[] = $row123;
                                }
                            }
                             $row11["BaseMaterials"] = $output123;
                    }
                    
                    $output11[] = $row11;
                }
            }
                
                
                
                
                 // Newer unit formulas keep their materials in unitformula.raw_materials (JSON).
                 $row['raw_materials'] = count($output11) > 0
                    ? $output11
                    : json_decode((string) $row['raw_materials']);
                $row['packing_configuration'] =$output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getRevisionHistoryByProduct") {
        $output = array();
        $product = mysqli_real_escape_string($conn, isset($_GET["product_code"]) ? $_GET["product_code"] : '');
        if ($product === '') {
            echo json_encode($output);
        } else {
            $sql = "SELECT mfr_no, version_no, revison_history, entry_date
                    FROM unitformula
                    WHERE plant_id='".$_GET["plant_id"]."'
                      AND product_code='".$product."'
                    ORDER BY id DESC";
            $result = $conn->query($sql);
            while ($result && $row = $result->fetch_assoc()) {
                $hist = json_decode((string) $row['revison_history'], true);
                if (is_array($hist) && count($hist) > 0) {
                    foreach ($hist as $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $item['spec_no'] = $item['spec_no'] ?? $item['revision_no'] ?? $row['mfr_no'];
                        $item['ver_no'] = $item['ver_no'] ?? $item['version_no'] ?? $row['version_no'];
                        $item['change_mode'] = $item['change_mode'] ?? $item['change_mode'] ?? '';
                        $item['change_reason'] = $item['change_reason'] ?? $item['reason'] ?? '';
                        $item['effective_date'] = $item['effective_date'] ?? $row['entry_date'];
                        $output[] = $item;
                    }
                } else if (trim((string) $row['mfr_no']) !== '' || trim((string) $row['version_no']) !== '') {
                    $output[] = array(
                        'spec_no' => $row['mfr_no'],
                        'ver_no' => $row['version_no'] !== '' && $row['version_no'] !== null ? $row['version_no'] : '00',
                        'change_mode' => '',
                        'change_reason' => '',
                        'effective_date' => $row['entry_date'],
                    );
                }
            }
            echo json_encode($output);
        }
    }
    else if ($_GET["type"] == "get_approved_UnitFormulasZuma") {
        $output = Array();
         $sql = "   SELECT DISTINCT  u.id,u.bom_type,u.master_formula_type,u.product_type,u.mfr_no,u.product_code,u.formula_for, u.average_weight,u.raw_materials,u.batch_size,u.unit,u.primary_pm_list,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
(select grade from product p where u.product_code=p.product_code limit 1) as grade,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_code1 from product p where u.product_code=p.product_code limit 1) as product_code1
FROM unitformula u  where u.checked_by!=''  and u.approve_by!='' and u.plant_id='".$_GET["plant_id"]."' and u.product_code='".$_GET["product_code"]."' and u.id='".$_GET["id"]."' order by u.id DESC;";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["id"]."' ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                  $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row2['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row2['gradeName1'] = $prodLatest['gradeName']; 
                                 $output2[] = $row2;
                            }
                            
                        }
                        $row1['packing_list'] = $output2;
                         
                        $output1[] = $row1;
                    }
                    
                }
                $row['packing_configuration'] =$output1;
  $row["primary_pm_list"] = json_decode($row["primary_pm_list"]); 
  $output = $row;
            }
        }
        echo json_encode($output);
    }
   
      else if ($_GET["type"] == "getUnitFormulaLog") {
        
        $output = Array();
 
          $sql = "SELECT u.*, (select product_name from product p where u.product_code=p.product_code limit 1) as product_name ,
      (select pack_sizes from product p where u.product_code=p.product_code limit 1) as pack_sizes

      FROM unitformula u  where u.plant_id = '".$_GET["plant_id"]."' order by u.id DESC";
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type, pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["id"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                 $output2[] = $row2;
                            }
                        }
                        $row1['packing_materials'] = $output2;
                        $output1[] = $row1;
                    }
                }
                
                $output11 = Array();
                $sql11 = "select a.*,b.grade from unitFormulaMaterial a left join my_view_all b on a.material_code = b.material_code  where a.mfr_no ='".$row["mfr_no"]."' order by a.id asc";
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $output123 = Array();
                        if($row11['material_type'] == 'Bulk'){
                            
                               
                                $sql123 = "select a.*, COALESCE(b.material_name, c.premixName) AS material_name, COALESCE(b.grade,'NA') as grade from premixBulkMaterialsView a 
                                left join my_view_all b on a.material_code = b.material_code left join primixMaster c on a.material_code = c.premixCode 
                                where a.material_ref_code = '".$row11["material_code"]."' order by a.id asc";
                                $result123 = $conn->query($sql123);
                                if ($result123->num_rows > 0) {
                                    while ($row123 = $result123->fetch_assoc()) {
                                        
                                        $row123['overages_per'] = 0;
                                        
                                        $totalQty = $row11['qty'];
                                        $mat1_percent = $row123['perQty'];
                                        $mat1_qty = ($totalQty * $mat1_percent) / 100;
                                        
                                        $row123['qty'] = $mat1_qty;
                                        $row123['total_qty'] = $mat1_qty;

                                        $row123['unit'] = $row11['unit'];
                                        
                                        $output1234 = Array();
                                        if($row123['material_type'] == 'Premix'){
                                                $sql1234 = "select a.*,b.material_name, b.grade from premixBulkMaterialsView a 
                                                left join my_view_all b on a.material_code = b.material_code where a.material_ref_code = '".$row123["material_code"]."' order by a.id asc";
                                                $result1234 = $conn->query($sql1234);
                                                if ($result1234->num_rows > 0) {
                                                    while ($row1234 = $result1234->fetch_assoc()) {
                                                        
                                                        
                                                        
                                                        $row1234['overages_per'] = 0;
                                                        
                                                        $totalQtyp = $row123['qty'];
                                                        $mat1_percentp = $row1234['perQty'];
                                                        $mat1_qtyp = ($totalQtyp * $mat1_percentp) / 100;
                                                        
                                                        $row1234['qty'] = $mat1_qtyp;
                                                        $row1234['total_qty'] = $mat1_qtyp;
                
                                                        $row1234['unit'] = $row123['unit'];
                                                        
                                                        
                                                        
                                                        
                                                        $output1234[] = $row1234;
                                                    }
                                                }
                                               
                                        }
                                        $row123["primixMaterials"] = $output1234;
                                    

                                        $output123[] = $row123;
                                    }
                                }
                               
                        }
                        $row11["bulkMaterials"] = $output123;
                        $output11[] = $row11;
                    }
                }
                
                
                $row['raw_materials'] =$output11;
                $row['packing_configuration'] =$output1;
                $row['pack_sizes'] =json_decode($row["pack_sizes"]); 
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getUnitFormulaLog_for_bmr") {
      
        $output = Array();
         
      $sql = "SELECT DISTINCT  u.id,u.bom_type,u.master_formula_type,u.product_type,u.mfr_no,u.product_code,u.formula_for, u.average_weight,u.raw_materials,u.batch_size,u.unit,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
(select grade from product p where u.product_code=p.product_code limit 1) as grade,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select color_index from product p where u.product_code=p.product_code limit 1) as color_index,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_code1 from product p where u.product_code=p.product_code limit 1) as product_code1
FROM unitformula u  where u.plant_id='".$_GET["plant_id"]."' and product_code='".$_GET["product_code"]."' order by u.id DESC;";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
                            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["id"]."' ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                  $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row2["grade"]."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row2['gradeName'] = $prodLatest['gradeName']; 
                    
                                
                                 $output2[] = $row2;
                            }
                        }
                        $row1['packing_materials'] = $output2;

                        $output1[] = $row1;
                    }
                    
                }
                
                 $output3 = Array();
                $sql3 = "select product_name from product where product_code ='".$row["product_code"]."' ";
               
                $result3 = $conn->query($sql3);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        $output3 = $row3;
                    }
                }
                
                
                $row['packing_configuration'] =$output1;
                $row['product_name1'] =$output3;
                $row["raw_materials"] = json_decode($row["raw_materials"]); 
                $row["product_name2"] = json_decode($row["product_name"][0]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getUnitFormulaLog_filter") {
        $output = Array();
        $sql = "SELECT u.id,bom_type,master_formula_type,u.product_type,u.mfr_no,u.product_code,p.product_code1,u.formula_for,
        u.average_weight,u.raw_materials,u.batch_size,u.unit,
        p.product_name, p.grade, p.generic_name,p.product_type, p.dosage_form, p.shelf_life, 
        p.label_claim,p.generic_name FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code 
        where u.plant_id='".$_GET["plant_id"]."' and p.product_type='".$_GET["product_type"]."'  and p.product_code1='".$_GET["product_code1"]."' order by u.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["id"]."' ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                 $output2[] = $row2;
                            }
                            
                        }
                        $row1['packing_materials'] = $output2;
                        
                        
                        
                        
                        
                        
                        
                        
                         
                        $output1[] = $row1;
                    }
                    
                }
                $row['packing_configuration'] =$output1;
                $row["raw_materials"] = json_decode($row["raw_materials"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "unitFormulaLogPDF"){
        require '../tcpdf/tcpdf.php';
        $_GET['filename'] = "Unit Formula Log"; $_GET['pdftype'] = "onlyheader"; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Unit Formula Log</h2>
        <table cellpadding="3">
            <thead>
                <tr>
                    <td style="width:6%;">Sr</td>
                    <td style="width:10%;">MFR No.</td>
                    <td style="width:18%;">Dosage Form</td>
                    <td style="width:30%;">Product Name</td>
                    <td style="width:18%;">Grade</td>
                    <td style="width:18%;">Status</td>
                </tr>
            </thead>
            <tbody>';
            $sql = "SELECT u.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width:6%;">'.$counter++.'</td>
                        <td style="width:10%;">'.$row['mfr_no'].'</td>
                        <td style="width:18%;">'.$row['dosage_form'].'</td>
                        <td style="width:30%;">'.$row['product_name'].'</td>
                        <td style="width:18%;">'.$row['grade'].'</td>
                        <td style="width:18%;">'.$row['status'].'</td>
                    </tr>';
                }
            }
            $html.='
            </tbody>
        </table>
        ';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
    } else if($_GET["type"] == "downloadUnitFormla"){
        if($_GET["plant_id"]==72){
                  require '../tcpdf/tcpdf.php';
        $_GET['filename'] = "Unit Formula Log"; $_GET['pdftype'] = "onlyheader"; include("../pdfimp2.php");
        $html.='
        
<table cellpadding="2"  border="1">
 
    <tr>
        <td style="width: 135px; font-weight: bold; font-size: 9px;"> Annexure Title:</td>
        <td style="width: 405px; font-size: 9px; font-weight: bold;   "> Master Reference Formula</td>
    </tr>';
    
                  
        //     $sql = "SELECT u.id,bom_type,master_formula_type,u.product_type,u.checked_by,u.checked_date,u.entry_date,u.approve_by,u.entry_by,u.approve_date,u.mrf_no,u.product_code,p.product_code1,
        //          u.formula_for, u.average_weight,u.raw_materials,u.batch_size,u.unit,u.list_customer,u.note,u.revison_history,u.version_no,u.refmfr_no,u.ref_sample_batch_no,u.complying_spec,u.colour,u.shelf_life,u.body,p.product_name, 
        //          p.grade, p.generic_name,p.product_type, p.dosage_form,p.label_claim,p.generic_name,pp.plant_name,pp.plant_full_address FROM unitformula u 
        //          LEFT JOIN product p ON u.product_code=p.product_code LEFT JOIN plant pp on u.plant_id=pp.plant_id
        // where u.id='".$_GET["id"]."' order by u.id DESC";
          $sql = "SELECT  u.*,p.product_code1,  p.product_name,p.grade, p.generic_name,p.product_type, p.dosage_form,p.label_claim,p.generic_name,pp.plant_name,pp.plant_full_address,up.pack_size as prasad,
         up.packing_instruction FROM unitformula u  LEFT JOIN product p ON u.product_code=p.product_code LEFT JOIN plant pp on u.plant_id=pp.plant_id LEFT JOIN unitformula_pm_dtl up on u.id=up.unit_formula_id 
        where u.id='".$_GET["id"]."' order by u.id DESC";
        
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $json_obj = $row['revison_history'];
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                     
                    $rev_no = $values["spec_no"];
                    $super = $values["spec_no"];
                    $effective_date = $values["effective_date"];
                }
                
                
                
                // for($i =0;$i< count($array)-1 ; $i ){
                //     $super = $array[$i]["spec_no"];
                // }
                
                
                
                
         $html.='
         
 
    <tr>
        <td style="width: 135px; font-size: 9px; font-weight: bold;"> Annexure No. : </td>
        <td style="width: 135px; font-size: 9px; "> A/SOP/FD/009/03-01 </td>
        <td style="width: 135px; font-size: 9px; font-weight: bold;"> Revision No. : </td>
        <td style="width: 135px; font-size: 9px; "> '.$rev_no.' </td>
    </tr>
    <tr>
        <td style="width: 135px; font-size: 9px; font-weight: bold;"> Ref. SOP No. </td>
        <td style="width: 135px; font-size: 9px; "> SOP/FD/009</td>
        <td style="width: 135px; font-size: 9px; font-weight: bold;"> Supersedes : </td>
        <td style="width: 135px; font-size: 9px; "> '.$super.' </td>
    </tr>
    <tr>
        <td style="width: 135px; font-size: 9px; font-weight: bold;"> Page No  :</td>
        <td style="width: 135px; font-size: 9px; "> 1 of 1 </td>
        <td style="width: 135px; font-size: 9px; font-weight: bold;"> Effective Date  :</td>
        <td style="width: 135px; font-size: 9px; ">'.date('d-m-Y', strtotime($effective_date)).'  </td>   
    </tr>
</table>

<div></div>

<table cellpadding="2" >
    <tr>
        <td style="width: 135px; font-size: 8px; font-weight: bold;"> PRODUCT NAME : </td>
        <td style="width: 125px; font-size: 9px; "> '.$row['product_name'].'</td>
        <td style="width: 150px; font-size: 8px; font-weight: bold;"> PRODUCT CODE NO.: </td>
        <td style="width: 130px; font-size: 9px; "> '.$row['product_code1'].' </td>
    </tr>
    <tr>
        <td style="width: 135px; font-size: 8px; font-weight: bold; ">  MRF NO.: </td>
        <td style="width: 125px; font-size: 9px; "> '.$row['mrf_no'].' </td>
        <td style="width: 150px; font-size: 8px; font-weight: bold;"> REFERENCE MFR NO.: </td>
        <td style="width: 130px; font-size: 9px; "> '.$row['refmfr_no'].'</td>
    </tr>
    <tr>
        <td style="width: 135px; font-size: 8px; font-weight: bold;"> COLOUR: </td>
        <td style="width: 125px; font-size: 9px; ">'.$row['colour'].' </td>
        <td style="width: 150px; font-size: 8px; font-weight: bold;"> REFERENCE SAMPLE BATCH NO: </td>
        <td style="width: 130px; font-size: 9px; "> '.$row['ref_sample_batch_no'].' </td>
    </tr>
    <tr>
        <td style="width: 135px; font-size: 8px;font-weight: bold; "> STD. PACK SIZE: </td>
        <td style="width: 125px; font-size: 9px; "> '.$row['prasad'].'</td>
        <td style="width: 150px; font-size: 8px;font-weight: bold; "> STD. LOT SIZE: </td>
        <td style="width: 130px; font-size: 9px; "> '.$row['batch_size'].' '.$row['unit'].' </td>
    </tr>
    
    <tr>
        <td style="width: 135px; font-size: 8px;font-weight: bold; "> DOMESTIC/EXPORT MARKETIN CACE OF EXPORT MARKET MENTION RM GRADES </td>
        <td style="width: 125px; font-size: 9px; "> '.$row['domestic'].'</td>
        <td style="width: 150px; font-size: 8px; font-weight: bold;"> RETEST PERIOD/SHELF LIFE </td>
        <td style="width: 130px; font-size: 9px; "> '.$row['shelf_life'].' </td>
    </tr>
    <tr>
        <td style="width: 135px; font-size: 8px; font-weight: bold;"> PRECAUTION AND SPECIAL INSTRUCTION </td>
        <td style="width: 125px; font-size: 9px; "> '.$row['precuation'].'</td>
        <td style="width: 150px; font-size: 8px; font-weight: bold;"> CRITICAL CONTROL CARE POINTS </td>
        <td style="width: 130px; font-size: 9px; "> '.$row['critical_point'].' </td>
    </tr>
    <tr>
        <td style="width: 135px; font-size: 8px;font-weight: bold; "> PACKAGING INSTRUCTION </td>
        <td style="width: 125px; font-size: 9px; "> '.$row['packing_instruction'].'</td>
        <td style="width: 150px; font-size: 8px; font-weight: bold;"> IN PROCESS PARAMETERS </td>
        <td style="width: 130px; font-size: 9px;  "> '.$row['inpro_parameter'].'  </td>
    </tr>
    <tr>
        <td style="width: 135px; font-size: 8px; font-weight: bold;"> STORAGE CONDITIONS </td>
        <td style="width: 125px; font-size: 9px; "> '.$row['storage_condi'].'</td>
        <td style="width: 150px; font-size: 8px; font-weight: bold;"> OTHER DETAILS(IF ANY)</td>
        <td style="width: 130px; font-size: 9px;  "> '.$row['others_details'].' </td>
    </tr>
    <br>
    <tr>
 
        <td style="width: 270px; font-size: 11px;text-align: center; font-weight: bold; ">COMPLYING WITH SPECIFICATION : </td>
        <td style="width: 270px; font-size: 9px; ">'.$row['complying_spec'].' </td>
    </tr>
  
</table>

<div></div>


<table cellpadding="2" border="1">
    <tr>
         <td style="width: 50px; font-size: 9px; font-weight: bold; text-align: center;">Sr. No.</td>
         <td style="width: 160px; font-size: 9px; font-weight: bold; text-align: center;">INGREDIENTS</td>
         <td style="width: 90px; font-size: 9px; font-weight: bold; text-align: center;">RAW MATERIAL CODE</td>
         <td style="width: 80px; font-size: 9px; font-weight: bold; text-align: center;">*GRADE</td>
         <td style="width: 70px; font-size: 9px; font-weight: bold; text-align: center;">%W/W</td>
         <td style="width: 90px; font-size: 9px; font-weight: bold; text-align: center;">STD QTY PER KG</td>
    </tr>
       ';
      $json_obj = $row['raw_materials'];
               $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {   
                    $per = $per+$values['percent_qty'];
                    $total = $total + $values['qty'];
                    $total1=number_format($total, 2);
                    
                      $html.='
    <tr>
         <td style="width: 50px; font-size: 9px;  text-align: center;">'.$k++.'</td>
         <td style="width: 160px; font-size: 9px; text-align: center; "> '.$values['material_name1'].'</td>
         <td style="width: 90px; font-size: 9px;  text-align: center;">'.$values['vendor_material_code'].'</td>
         <td style="width: 80px; font-size: 9px;  text-align: center;">';
                foreach ($values['grade'] as $values1)
                {    
                   $html.='  ' .$values1['grade'].'  ';
                } 
             $html.='</td> 
         <td style="width: 70px; font-size: 9px;  text-align: center;"> '.$values['percent_qty'].'</td>
         <td style="width: 90px; font-size: 9px; text-align: center; ">'.$values['qty'].' </td>
    </tr>';
    
    }
    
    
    //  $json_obj1 = $values['grade'];
    //           $array1 = json_decode($json_obj1, true);
    //             foreach ($array as $values1){
          
    //      $html.='<td style="width: 80px; font-size: 9px;  text-align: center;"> '.$values1['grade'].'</td>';
    //             }
    
    
    
    
    
                 $html.='
    <tr>
         <td style="width: 50px; font-size: 9px;  "> </td>
         <td style="width: 330px; font-size: 9px; font-weight: bold; "> TOTAL</td>
         <td style="width: 70px; font-size: 9px; text-align: center; "> '.$per.' </td>
         <td style="width: 90px; font-size: 9px; text-align: center; ">'.$total1.' </td>
    </tr>
</table>
<div></div>

<table cellpadding="2"  >
    <tr>
         <td style="width: 540px; font-size: 9px; ">
         '.$row['note'].' 
         </td>
    </tr>


      
</table>
<div></div>

<table cellpadding="2" >
    
    <tr>
        <td style="width: 540px; font-size: 9px;"><b>1. LIST OF CUSTOMERS : </b> </td>
    </tr>
       ';
      $json_obj = $row['list_customer'];
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                     
                      $html.='
                    <tr>
                             <td style="width: 40px; font-size: 9px;  text-align: center;">'.$k++.'</td>

                        <td style="width: 500px; font-size: 9px;"> '.$values["list_customer"]. '</td> 
                    </tr>';
    
                }
                
$html.='
</table>
<div></div> 
<table cellpadding="2" >
    
    <tr>
        <td style="width: 540px; font-size: 9px;"><b> 2. EQUIPMENTS REQUIRED  </b> </td>
    </tr>
       ';
      $json_obj = $row['equpment_list'];
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                     
                      $html.='
                    <tr>
                         <td style="width: 40px; font-size: 9px;  text-align: center;">'.$k++.'</td>

                        <td style="width: 500px; font-size: 9px;"> '.$values["list_customer"]. '</td> 
                    </tr>';
    
                }
                
$html.='
</table>
<div></div>


<table cellpadding="2" border="1">
        <tr>
            <td style="background-color:#DDDAD9; width:540px; text-align:center;">Revision History</td>
        </tr>
</table>


<table cellpadding="2" border="1">
 
    <tr>
        <td style="font-size: 9px; font-weight: bold; text-align: center; width: 40px;">Sr. No.</td>
        <td style="font-size: 9px; font-weight: bold; text-align: center; width: 80px;">Revision No:</td>
        <td style="font-size: 9px; font-weight: bold; text-align: center; width: 80px;">Effective Date</td>
        <td style="font-size: 9px; font-weight: bold; text-align: center; width: 340px;">Revision Description</td>
      </tr>
     ';
      $json_obj1 = $row['revison_history'];
                $array1 = json_decode($json_obj1, true);
                 $k=1;
                foreach ($array1 as $values1)
                {
                    $rivisionNo =$values1['spec_no'];
                    
                     $html.='
    <tr>
        <td style="font-size: 9px;  text-align: center;    width: 40px;"> '.$k++.'</td>
        <td style="font-size: 9px;  text-align: center;   width: 80px;"> '.$values1['spec_no'].'</td>
        <td style="font-size: 9px;  text-align: center;    width: 80px;"> '.date('d-m-Y', strtotime($values1['effective_date'])).' </td>
        <td style="font-size: 9px;      width: 340px;"> '.$values1['revision_description'].'</td>
        
      </tr>
      
    ';
     
                }
 $html.='</table>
<div></div> 


<table border="1" cellpadding="2">
<tr>
    <td style="width: 135px; font-size: 9px; font-weight: bold; text-align: center;"></td>
    <td style="width: 135px; font-size: 9px; font-weight: bold; text-align: center;">Prepared By</td>
    <td style="width: 135px; font-size: 9px; font-weight: bold; text-align: center;">Reviewed By</td>
    <td style="width: 135px; font-size: 9px; font-weight: bold; text-align: center;">Approved By</td>
</tr>
<tr>
    <td style="width: 135px; font-size: 9px; font-weight: bold; text-align: center;">Name</td>
    <td style="width: 135px; font-size: 9px;  text-align: center;">'.$row['entry_by'].' </td>
    <td style="width: 135px; font-size: 9px;  text-align: center;">'.$row['entry_by'].' </td>
    <td style="width: 135px; font-size: 9px;  text-align: center;">'.$row['entry_by'].' </td>
   
</tr>
<tr>
    <td style="width: 135px; font-size: 9px; font-weight: bold; text-align: center;">Department</td>
    <td style="width: 135px; font-size: 9px;  text-align: center;">Production</td>
    <td style="width: 135px; font-size: 9px;  text-align: center;">Production</td>
    <td style="width: 135px; font-size: 9px;  text-align: center;">Production</td>
    
</tr>
<tr>
    <td style="width: 135px; font-size: 9px; font-weight: bold; text-align: center;">Designation</td>
    <td style="width: 135px; font-size: 9px;  text-align: center;"></td>
    <td style="width: 135px; font-size: 9px;  text-align: center;"></td>
    <td style="width: 135px; font-size: 9px;  text-align: center;"></td>
   
</tr>
<tr>
    <td style="width: 135px; font-size: 9px; font-weight: bold; text-align: center;">Sign/Date</td>
    <td style="width: 135px; font-size: 9px;  text-align: center;">'.date('d-m-Y', strtotime($row['entry_date'])).' </td>
    <td style="width: 135px; font-size: 9px;  text-align: center;">'.date('d-m-Y', strtotime($row['entry_date'])).'</td>
    <td style="width: 135px; font-size: 9px;  text-align: center;">'.date('d-m-Y', strtotime($row['entry_date'])).'</td>
   
</tr>

</table>



        
        
        
        ';
            
}      
        }   
            
            
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
            
        }
        else{
             require '../tcpdf/tcpdf.php';
        $_GET['filename'] = "Unit Formula Log"; $_GET['pdftype'] = "onlyheader"; include("../pdfimp2.php");
        
        $html.=' <table  > 
                         <tr>
                             <td style="background-color:#DDDAD9; width:540px; text-align:center;">Bill of Material (BOM)</td>
                         </tr>
                </table>
                <div></div>
                        ';
           $sql = "SELECT u.id,u.bom_type,master_formula_type,u.product_type,u.checked_by,u.checked_date,u.approve_by,u.approve_date,u.mfr_no,u.product_code,u.formula_for,
        u.average_weight,u.raw_materials,u.batch_size,u.unit,u.version_no,u.revison_history,
        p.product_name, p.grade, p.generic_name,p.product_type,p.dosage_type, p.dosage_form, p.shelf_life, 
        p.label_claim,p.generic_name,pp.plant_name,pp.plant_full_address FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code 
        LEFT JOIN plant pp on u.plant_id=pp.plant_id
        
        where u.id='".$_GET["id"]."' order by u.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
         $html.='

<table cellpadding="2" border="1">
<tr>
    <td  style="font-size:10px; font-weight: bold;  width:108px"> BOM / Formula No : </td>
      <td style="font-size:10px;  width:162px;"> '.$row['mfr_no'].'</td>
    <td style="font-size:10px; font-weight: bold; width:108px"> Standard Batch Size:</td>
    <td style="font-size:10px;  width:162px;"> '.$row['batch_size'].' '.$row['unit'].'</td>
</tr>
 
<tr>
    <td style="font-size:10px; font-weight: bold; width:108px"> Product Name :</td>
     <td style="font-size:10px;  width:162px"> '.$row['product_name'].'</td>
     <td style="font-size:10px; font-weight: bold;  width:108px"> Product Code :</td>
     <td style="font-size:10px;  width:162px"> '.$row['product_code'].'</td>
</tr>
<tr>
    <td style="font-size:10px; font-weight: bold; width:108px"> Dosage Form :</td>
     <td style="font-size:10px;  width:162px"> '.$row['dosage_form'].'</td>
    <td style="font-size:10px;font-weight: bold;  width:108px"> Product Nature :</td>
     <td style="font-size:10px;  width:162px"> '.$row['dosage_type'].'</td>
</tr>
<tr>
 <td style="font-size:10px; font-weight: bold;  width:108px"> Client Name : </td>
  <td style="font-size:10px;  width:432px"></td>
 
</tr>
</table>

<div></div>
 
<table cellpadding="2">
        <tr>
            <td style="background-color:#DDDAD9; width:540px; text-align:center;">Raw Materials List : </td>
        </tr>
</table>
<div></div>

 
<table cellpadding="2" border="1">
 
    <tr>
        <td style="font-size: 9px; font-weight: bold; text-align: center; width: 77px;">Sr. No.</td>
        <td style="font-size: 9px; font-weight: bold; text-align: center; width: 97px;">RM Code</td>
        <td style="font-size: 9px; font-weight: bold; text-align: center; width: 107px;">Material Name</td>
        <td style="font-size: 9px; font-weight: bold; text-align: center; width: 90px;">Standard Qty</td>
        <td style="font-size: 9px; font-weight: bold; text-align: center; width: 91px;">Percent Qty</td>
         <td style="font-size: 9px; font-weight: bold; text-align: center; width: 77px;">UOM</td>
      </tr>
     ';
      $json_obj = $row['raw_materials'];
                $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                    // $term = $values['term'];
                    // $term_heading = $values['term_heading'];
                     $html.='
    <tr>
        <td style="font-size: 9px; text-align: center;    width: 77px;"> '.$k++.'</td>
        <td style="font-size: 9px; text-align: center;    width: 97px;"> '.$values['material_code'].'</td>
        <td style="font-size: 9px; text-align: center;    width: 107px;"> '.$values['material_name'].'   </td>
        <td style="font-size: 9px;  text-align: center;   width: 90px;"> '.$values['qty'].' '.$values['unit'].'</td>
        <td style="font-size: 9px;  text-align: center;   width: 91px;">  '.$values['percent_qty'].'</td>
         <td style="font-size: 9px; text-align: center;    width: 77px;">   '.$row['unit'].' </td>
      </tr>';
     
                }
   
     
     
     
      $html.='
</table>

 




<div></div>
 

<div></div>     

</table>
          <table border="1" cellpadding="3">
      <tr style="background-color:black; color:white;">
      <td style="width: 250px;text-align:center;"><b>Checked By & Digital Signed By</b></td>
      <td style="width: 40px;text-align:center;"><img src="../upload/pdf/sign.jpg" style="width:20px;height:20px;"></td>
      <td style="width: 250px;text-align:center;"><b>Approved By & Digital Signed By</b></td>
  </tr>
  <tr>
      <td style="width: 270px;" >
          <table>
              <tr>
                  <td style="width:70px;">Name</td>
                  <td style="width:191px;">:' . $row['checked_by'] . '</td>
                 
              </tr>
              <tr>
              <td style="width:70px;">ID</td>
              <td style="width:70px;">: </td>
              <td style="width: 50px;">Department</td>
              <td style="width: 81px;">:   </td>
          </tr>
          <tr>
              <td style="width:70px;">Designation</td>
              <td style="width:191px;">:</td>
          </tr>
            
              <tr>
                  <td style="width:70px;">Date</td>
                  <td style="width:70px;">:' . date('d-m-Y', strtotime($row['checked_date'])) . '</td>
                  <td style="width: 50px;">Time</td>
                  <td style="width: 81px;">:' . date('H:i:s', strtotime($row['checked_date'])) . '</td>
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
                  <td style="width: 81px;">:   </td>
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
        
            }
            
        }    
        
       
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
        }
        
        
        
    }else if($_GET['type'] == 'unitFormulaPDF'){
        $sql = "SELECT u.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code WHERE u.id='".$_GET["id"]."' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                require '../tcpdf/tcpdf.php';
                $_GET['filename'] = "Unit Formula"; $_GET['pdftype'] = "onlyheader"; include("../pdfimp2.php");
                $html.='
                <b>Product Details:</b><br>
                <table cellpadding="3">
                    <tr>
                        <td style="width:20%">Product Code</td>
                        <td style="width:30%">'.$row['product_code'].'</td>
                        <td style="width:20%">Product Name</td>
                        <td style="width:30%">'.$row['product_name'].'</td>
                    </tr>
                    <tr>
                        <td>Generic Name</td>
                        <td>'.$row['generic_name'].'</td>
                        <td>Grade</td>
                        <td>'.$row['grade'].'</td>
                    </tr>
                </table>
                <div></div>
                <b>Raw Materials List:</b><br>
                <table cellpadding="3">
                    <tr>
                        <td style="width:5%;">Sr.</td>
                        <td style="width:14%;">Material Type</td>
                        <td style="width:12%;">Material Subtype</td>
                        <td style="width:12%;">Material Code</td>
                        <td style="width:15%;">Material Name</td>
                        <td style="width:10%;">Grade</td>
                        <td style="width:12%;">Qty</td>
                        <td style="width:10%;">Overages</td>
                        <td style="width:10%;">Role</td>
                    </tr>';
                    $output1 = Array();
                    $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Raw Material'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        $counter = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td>'.$counter++.'</td>
                                <td>'.$row1['material_type'].'</td>
                                <td>'.$row1['material_subtype'].'</td>
                                <td>'.$row1['material_code'].'</td>
                                <td>'.$row1['material_name'].'</td>
                                <td>'.$row1['grade'].'</td>
                                <td>'.$row1['qty'].'</td>
                                <td>'.$row1['overages'].'</td>
                                <td>'.$row1['role'].'</td>
                            </tr>';
                        }
                    }
                $html.='
                </table>
                <div></div>
                <b>Additional Materials List:</b><br>
                <table cellpadding="3">
                    <tr>
                        <td style="width:5%;">Sr.</td>
                        <td style="width:14%;">Material Type</td>
                        <td style="width:12%;">Material Subtype</td>
                        <td style="width:12%;">Material Code</td>
                        <td style="width:15%;">Material Name</td>
                        <td style="width:10%;">Grade</td>
                        <td style="width:12%;">Qty</td>
                        <td style="width:10%;">Overages</td>
                        <td style="width:10%;">Role</td>
                    </tr>';
                    $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Additional Material'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        $counter =1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td>'.$counter++.'</td>
                                <td>'.$row1['material_type'].'</td>
                                <td>'.$row1['material_subtype'].'</td>
                                <td>'.$row1['material_code'].'</td>
                                <td>'.$row1['material_name'].'</td>
                                <td>'.$row1['grade'].'</td>
                                <td>'.$row1['qty'].'</td>
                                <td>'.$row1['overages'].'</td>
                                <td>'.$row1['role'].'</td>
                            </tr>';
                        }
                    }
                $html.='
                </table>
                <div></div>
                <b>Packing Materials List:</b><br>
                <table cellpadding="3">
                    <tr>
                        <td style="width:5%;">Sr.</td>
                        <td style="width:14%;">Material Type</td>
                        <td style="width:12%;">Material Subtype</td>
                        <td style="width:12%;">Material Code</td>
                        <td style="width:15%;">Material Name</td>
                        <td style="width:10%;">Grade</td>
                        <td style="width:12%;">Qty</td>
                        <td style="width:10%;">Overages</td>
                        <td style="width:10%;">Role</td>
                    </tr>';
                    $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Packing Material'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        $counter =1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td>'.$counter++.'</td>
                                <td>'.$row1['material_type'].'</td>
                                <td>'.$row1['material_subtype'].'</td>
                                <td>'.$row1['material_code'].'</td>
                                <td>'.$row1['material_name'].'</td>
                                <td>'.$row1['grade'].'</td>
                                <td>'.$row1['qty'].'</td>
                                <td>'.$row1['overages'].'</td>
                                <td>'.$row1['role'].'</td>
                            </tr>';
                        }
                    }
                $html.='
                </table>';
                
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('', 'I');
            }
        }
    }

}

$conn->close();
?>