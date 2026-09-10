<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
// 123356
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
     if ($_GET["type"] == "save_batch_formula") {

   
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
                    yeild_contribution,role,process_step,stage,split_into_lots,factor,strength) values(
                       '".$_GET["plant_id"]."','Raw Material','".$material["material_code"]."','".$material["qty"]."','".$material["unit"]."',
                        '".$material["overages_per"]."','".$material["qty_overages_qty"]."','".$material["batch_qty"]."','$BFR_NO','".$material["grade"]."',
                        '".$material["yeild_contribution"]."','".$material["role"]."','".$material["process_step"]."','".$material["stage"]."',
                        '".$material["split_into_lots"]."','".$material["factor"]."','".$material["strength"]."')";
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
    else if ($_GET["type"] == 'raiseRMPMIndent') {
        
        
        
        
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['result']) || empty($data['result'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or empty data']);
        exit;
    }

    $result = $data['result'];

    $conn->begin_transaction();
    try {
        foreach ($result as $item) {
            
           if (isset($item['indent_qty']) && $item['indent_qty'] != '0' && $item['total_amt'] != '' ) {

                
          
         
  
             
             $sql = "INSERT INTO requested_indent ( material_code, qty,  material_type, material_name, total_plan_qty, avbl_stock, excess_qty, requested_qty, indent_qty, 
            responsible_person, purchase_type, approx_del_date, balance_qty, quotation_selection, quotations, total_amt, plan_deadline_contract,
            plan_deadline_payment, plan_deadline_time, plan_deadline_time_2, Actual_Plan_deadline_For_Contract_Sign, Actual_Plan_deadline_For_Payment, Actual_Plan_deadline_Time) VALUES
('".$item['material_code']."','".$item['indent_qty']."','".$item['material_type']."','".$item['material_name']."','".$item['total_plan_qty']."',
'".$item['avbl_stock']."','".$item['excess_qty']."','".$item['requested_qty']."','".$item['indent_qty']."','".$item['responsible_person']."',
'".$item['purchase_type']."','".$item['approx_del_date']."','".$item['balance_qty']."','".$item['quotation_selection']."',
'".json_encode($item['quotations'])."','".$item['total_amt']."','".$item['plan_deadline_contract']."','".$item['plan_deadline_payment']."',
'".$item['plan_deadline_time']."','".$item['plan_deadline_time_2']."','".$item['Actual_Plan_deadline_For_Contract_Sign']."',
'".$item['Actual_Plan_deadline_For_Payment']."','".$item['Actual_Plan_deadline_Time']."');";
            
            if (!$conn->query($sql)) {
                throw new Exception("Insert failed: " . $conn->error);
            }
        }
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Indent records saved successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Failed to save records: ' . $e->getMessage()]);
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
           $output=Array();
         $sql = "SELECT a.*,b.product_name,b.generic_name,b.unit,u.average_weight,u.average_weight_unit,u.dosage_form,
       (select product_type from product p where u.product_code=p.product_code limit 1) as a_product_type
       FROM batch_formula_info a join product b on a.product_code = b.product_code left join unitformula u on  a.mfr_no=u.mfr_no
        where  a.approve_by is null AND a.plant_id='".$_GET["plant_id"]."' order by 1 desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $row["raw_materials"] = json_decode($row["raw_materials"]);
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
        echo json_encode($output);
     }
     else if ($_GET["type"] == "get_batch_formula_for_approval1") {
           $output=Array();
          $sql = "SELECT a.*,b.product_name,b.unit,u.average_weight,u.average_weight_unit,u.dosage_form,
       (select product_type from product p where u.product_code=p.product_code limit 1) as a_product_type
       FROM batch_formula_info a join product b on a.product_code = b.product_code left join unitformula u on  a.mfr_no=u.mfr_no
        where  a.approve_by ='' AND a.plant_id='".$_GET["plant_id"]."' AND u.dosage_form LIKE '%".$_GET["dsg"]."%' order by 1 desc";
        
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
        echo json_encode($output);
     }
     else if ($_GET["type"] == "get_batch_formula_log") {
          $output=Array();
           $sql = "   SELECT DISTINCT  c.status as c_status,u.dosage_form,u.id,u.bom_type,u.bom_batch_size_unit,u.bom_batch_size,u.master_formula_type,u.product_type,u.mfr_no,u.product_code,u.formula_for, u.average_weight,u.raw_materials,u.batch_size,u.unit,
u.primary_pm_list,u.primary_pm_batch_size,u.consumeableMaterial,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
(select grade from product p where u.product_code=p.product_code limit 1) as grade,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select product_code1 from product p where u.product_code=p.product_code limit 1) as product_code1
FROM unitformula u left join batch_formula_info c on u.mfr_no=c.mfr_no where u.plant_id='".$_GET["plant_id"]."' and c.status='Approve' order by u.id DESC;";
        // $sql = "SELECT distinct  a.mfr_no,a.product_code,a.product_type,b.product_name,a.batch_size,
        //  b.unit,a.status,a.average_weight,a.average_weight_unit,a.raw_materials FROM unitformula a inner join 
        //  batch_formula_info c on a.mfr_no=c.mfr_no join product b 
        // on a.product_code = b.product_code
        //         where c.status='Approve' and a.plant_id='".$_GET["plant_id"]."' order by 1 desc";
                 
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
                 $sql2="Select * from batch_formula_info where mfr_no = '".$row["mfr_no"]."'  ";
                 $result1 = $conn->query($sql2);
                 if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                            $output1[]=$row1;   
                    }
                     
                 }
                 $row["bfr_records"] = $output1;
                 $row["raw_materials"] = json_decode($row["raw_materials"] ?: '[]', true);
                 if (!is_array($row["raw_materials"])) { $row["raw_materials"] = array(); }
                 $row["primary_pm_list"] = json_decode($row["primary_pm_list"] ?? '[]', true);
                 if (!is_array($row["primary_pm_list"])) { $row["primary_pm_list"] = array(); }
                 $row["consumeableMaterial"] = json_decode($row["consumeableMaterial"] ?? '[]', true);
                 if (!is_array($row["consumeableMaterial"])) { $row["consumeableMaterial"] = array(); }
                 $output_pm = Array();
                 $uid_esc = mysqli_real_escape_string($conn, $row["id"]);
                 $sql_pm = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,product_brand_name,
                    pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$uid_esc."' ";
                 $result_pm = $conn->query($sql_pm);
                 if ($result_pm && $result_pm->num_rows > 0) {
                    while ($row_pm = $result_pm->fetch_assoc()) {
                        $output_pm2 = Array();
                        $dtl_esc = mysqli_real_escape_string($conn, $row_pm["id"]);
                        $sql_pm2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$dtl_esc."' ";
                        $result_pm2 = $conn->query($sql_pm2);
                        if ($result_pm2 && $result_pm2->num_rows > 0) {
                            while ($row_pm2 = $result_pm2->fetch_assoc()) {
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
                 $row['packing_configuration'] = $output_pm;
                $output[] = $row;
            }
        }
        echo json_encode($output);
     }
     else if ($_GET["type"] == "get_batch_formula_log_bmr") {
          $output=Array();
           $sql = "   SELECT DISTINCT  c.status as c_status,u.dosage_form,u.id,u.bom_type,u.bom_batch_size_unit,u.bom_batch_size,u.master_formula_type,u.product_type,u.mfr_no,u.product_code,u.formula_for, u.average_weight,u.raw_materials,u.batch_size,u.unit,
u.primary_pm_list,u.primary_pm_batch_size,u.consumeableMaterial,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
(select grade from product p where u.product_code=p.product_code limit 1) as grade,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
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
                 $sql2="Select * from batch_formula_info where mfr_no = '".$row["mfr_no"]."'  ";
                 $result1 = $conn->query($sql2);
                 if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                            $output1[]=$row1;   
                    }
                     
                 }
                 $row["bfr_records"] = $output1;
                 $row["raw_materials"] = json_decode($row["raw_materials"] ?: '[]', true);
                 if (!is_array($row["raw_materials"])) { $row["raw_materials"] = array(); }
                 $row["primary_pm_list"] = json_decode($row["primary_pm_list"] ?? '[]', true);
                 if (!is_array($row["primary_pm_list"])) { $row["primary_pm_list"] = array(); }
                 $row["consumeableMaterial"] = json_decode($row["consumeableMaterial"] ?? '[]', true);
                 if (!is_array($row["consumeableMaterial"])) { $row["consumeableMaterial"] = array(); }
                 $output_pm = Array();
                 $uid_esc = mysqli_real_escape_string($conn, $row["id"]);
                 $sql_pm = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,product_brand_name,
                    pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$uid_esc."' ";
                 $result_pm = $conn->query($sql_pm);
                 if ($result_pm && $result_pm->num_rows > 0) {
                    while ($row_pm = $result_pm->fetch_assoc()) {
                        $output_pm2 = Array();
                        $dtl_esc = mysqli_real_escape_string($conn, $row_pm["id"]);
                        $sql_pm2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$dtl_esc."' ";
                        $result_pm2 = $conn->query($sql_pm2);
                        if ($result_pm2 && $result_pm2->num_rows > 0) {
                            while ($row_pm2 = $result_pm2->fetch_assoc()) {
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
                 $row['packing_configuration'] = $output_pm;
                $output[] = $row;
            }
        }
        echo json_encode($output);
     }
     else if ($_GET["type"] == "get_batch_formula_log1") {
          $output=Array();
             $sql = "   SELECT DISTINCT  u.dosage_form,u.id,u.bom_type,u.bom_batch_size_unit,u.bom_batch_size,u.master_formula_type,u.product_type,u.mfr_no,u.product_code,u.formula_for, u.average_weight,u.raw_materials,u.batch_size,u.unit,
u.primary_pm_list,u.primary_pm_batch_size,u.consumeableMaterial,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
(select grade from product p where u.product_code=p.product_code limit 1) as grade,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select product_code1 from product p where u.product_code=p.product_code limit 1) as product_code1
FROM unitformula u left join batch_formula_info c on u.mfr_no=c.mfr_no  left join product p ON c.product_code = p.product_code  where u.plant_id='".$_GET["plant_id"]."' and c.status='Approve' 
AND p.product_name LIKE '%".$_GET["productName"]."%' order by u.id DESC;";
        // $sql = "SELECT distinct  a.mfr_no,a.product_code,a.product_type,b.product_name,a.batch_size,
        //  b.unit,a.status,a.average_weight,a.average_weight_unit,a.raw_materials FROM unitformula a inner join 
        //  batch_formula_info c on a.mfr_no=c.mfr_no join product b 
        // on a.product_code = b.product_code
        //         where c.status='Approve' and a.plant_id='".$_GET["plant_id"]."' order by 1 desc";
                 
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
                 $sql2="Select * from batch_formula_info where mfr_no = '".$row["mfr_no"]."'  ";
                 $result1 = $conn->query($sql2);
                 if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                            $output1[]=$row1;   
                    }
                     
                 }
                 $row["bfr_records"] = $output1;
                 $row["raw_materials"] = json_decode($row["raw_materials"] ?: '[]', true);
                 if (!is_array($row["raw_materials"])) { $row["raw_materials"] = array(); }
                 $row["primary_pm_list"] = json_decode($row["primary_pm_list"] ?? '[]', true);
                 if (!is_array($row["primary_pm_list"])) { $row["primary_pm_list"] = array(); }
                 $row["consumeableMaterial"] = json_decode($row["consumeableMaterial"] ?? '[]', true);
                 if (!is_array($row["consumeableMaterial"])) { $row["consumeableMaterial"] = array(); }
                 $output_pm = Array();
                 $uid_esc = mysqli_real_escape_string($conn, $row["id"]);
                 $sql_pm = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,product_brand_name,
                    pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$uid_esc."' ";
                 $result_pm = $conn->query($sql_pm);
                 if ($result_pm && $result_pm->num_rows > 0) {
                    while ($row_pm = $result_pm->fetch_assoc()) {
                        $output_pm2 = Array();
                        $dtl_esc = mysqli_real_escape_string($conn, $row_pm["id"]);
                        $sql_pm2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$dtl_esc."' ";
                        $result_pm2 = $conn->query($sql_pm2);
                        if ($result_pm2 && $result_pm2->num_rows > 0) {
                            while ($row_pm2 = $result_pm2->fetch_assoc()) {
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
                 $row['packing_configuration'] = $output_pm;
                $output[] = $row;
            }
        }
        echo json_encode($output);
     }
     else if ($_GET["type"] == "get_products_for_shortage_calculation") {
           $output=Array();
        $sql = "SELECT a.product_code,a.product_type,a.product_name,a.grade,a.pack_sizes FROM product a 
                where a.product_type='".$_GET["product_type"]."' and a.status='Approve' 
                and a.plant_id='".$_GET["plant_id"]."' order by a.product_name";
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
                        $sql3="Select id,bfr_no,batch_formula_weight,raw_materials,packing_materials 
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
          
          
          
         $sql = "SELECT a.product_code,a.product_type,a.product_name,a.grade,pack_sizes FROM product a 
        where a.dosage_form='".$_GET["dosage_form"]."' and a.status='Approve' and a.plant_id='".$_GET["plant_id"]."' ";
        
       
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
                 $row["mfr_records"] = $output1;
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

    //   else if ($_GET["type"] == "ZumaSplitPlanningMicro") {

          
      
    //     $output = Array();
        
    //   $sql = "SELECT a.id as pid,a.unit,a.order_no,b.file,c.TrdNm,b.po_no,b.po_type,b.valid_till,b.po_date,a.product_code,d.dosage_form as product_type,b.client_code,b.po_no,b.required_date as commencementDate,
    //   a.order_qty,a.pack_size,latest_unitformula.bom_batch_size,d.product_name,d.grade as product_grade, e.raw_materials,
    //   e.id as unit_formula_id,a.order_qty as qty_to_prepare,  COALESCE(sb.qty, 0) - COALESCE((
    //     SELECT SUM(qty)
    //     FROM fg_material_issue mi 
    //     WHERE mi.batch_no = sb.batch_no and mi.material_code=sb.material_code
    // ), 0) AS stock_qty ,sb.batch_no,
    // (select po_date from po_entry po where po.order_no=a.order_no) as po_date
    //   FROM 
    //   order_materials a LEFT JOIN po_entry b ON a.order_no = b.order_no 
    //   LEFT JOIN client c ON b.client_code=c.client_code
    //     LEFT JOIN product d ON a.product_code = d.product_code 
    //   LEFT JOIN 
    //         (
    //             SELECT 
    //                 product_code,
    //                 bom_batch_size,
    //                 MAX(id) as max_id
    //             FROM 
    //                 unitformula
    //             GROUP BY 
    //                 product_code,bom_batch_size
    //         ) latest_unitformula ON a.product_code = latest_unitformula.product_code
    //     LEFT JOIN 
    //         unitformula e ON latest_unitformula.max_id = e.id
    //         left join fg_stock_book sb on sb.material_code= a.product_code   ORDER BY a.sequence ASC, a.id DESC";
    // //   WHERE   a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC ";
        
    
      
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         $output1 = Array();

    //         while ($row = $result->fetch_assoc()) {
    //              $output1 = Array();
                 
    //              $sql1 = "SELECT a.*, b.id,b.unit_formula_id,b.market_type,b.country_specific,b.country_name,b.packing_type,
    //             b.pack_size,b.batch_size,b.unit  from unitformula_packing_materials a left join unitformula_pm_dtl b ON 
    //             a.unit_formula_dtl_id = b.id where b.unit_formula_id ='".$row["unit_formula_id"]."'  ";
               
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {

    //                     $output1[] = $row1;
    //                 }
                    
    //             }
    //              $output2 = Array();
                 
    //              $sql2 = "select *,a.oder_qty as Qty,a.balance_qty as bal_qty,IFNULL(a.batch_plan_id, 0) AS batch_plan_id from split_planning_qty a
    //              where a.order_no='".$row["order_no"]."' ";
               
    //             $result2 = $conn->query($sql2);
    //             if ($result2->num_rows > 0) {
    //                 while ($row2 = $result2->fetch_assoc()) {

    //                     $output2[] = $row2;
    //                 }
                    
    //             }
    //                                       $row["pack_size"] = json_decode($row["pack_size"]);

             
    //                 $row['splits'] =$output2;
    //                 $row['packing_configuration'] =$output1;
    //                 $rawMaterials = json_decode($row["raw_materials"], true);
    //                 $row["raw_materials"] = $rawMaterials;
                    
                    
    //              $output123 = Array();
    //               $sql22="Select id,mfr_no,batch_size from unitformula where product_code = 
    //              '".$row["product_code"]."'    ";
                
    //              $result1234 = $conn->query($sql22);
    //              if ($result1234->num_rows > 0) {
    //                 while ($row1234 = $result1234->fetch_assoc()) {
    //                     $output2 = Array();
    //                     $sql3="Select id,mfr_no,bfr_no,batch_formula_weight,raw_materials,packing_materials 
    //                     from batch_formula_info  where mfr_no = '".$row1234["mfr_no"]."' and status='Approve' ";
    //                     $result2 = $conn->query($sql3);
    //                           if ($result2->num_rows > 0) {
    //                                 while ($row2 = $result2->fetch_assoc()) {
    //                                      $output2[]=$row2;   
    //                                 }
    //                           }
    //                         $row1234["bfr_records"] = $output2;
    //                         $output123[]=$row1234;   
    //                 }
                     
    //              }
    //               $row["mfr_records"] = $output123;
                 
                 
                 
                 
                 
                 
                 
                 
                 
                 
                 
    //              $output55 = Array();
    //               $sql223="SELECT a.batch_number  FROM mfg_work_order_hdr a left join batch_planning b on a.batch_plan_id=b.id WHERE b.client_po_no= '".$row["order_no"]."' and b.product_code= '".$row["product_code"]."'";
                
    //              $result55 = $conn->query($sql223);
    //              if ($result55->num_rows > 0) {
    //                 while ($row55 = $result55->fetch_assoc()) {
                        
    //                         $output55[]=$row55;   
    //                 }
                     
    //              }
                 
    //               $row["batch_nos"] = $output55;
    //              $output555 = Array();
    //               $sql2235="SELECT a.approved_date  FROM mfg_work_order_hdr a left join batch_planning b on a.batch_plan_id=b.id WHERE b.client_po_no= '".$row["order_no"]."' and b.product_code= '".$row["product_code"]."'";
                
    //              $result555 = $conn->query($sql2235);
    //              if ($result555->num_rows > 0) {
    //                 while ($row555 = $result555->fetch_assoc()) {
                        
    //                         $output555[]=$row555;   
    //                 }
                     
    //              }
    //              $output5555 = Array();
    //               $sql22355="SELECT b.approve_date  FROM   batch_planning b  WHERE b.client_po_no= '".$row["order_no"]."' and b.product_code= '".$row["product_code"]."'";
                
    //              $result5555 = $conn->query($sql22355);
    //              if ($result5555->num_rows > 0) {
    //                 while ($row5555 = $result5555->fetch_assoc()) {
                        
    //                         $output5555[]=$row5555;   
    //                 }
                     
    //              }
    //              $output555555 = Array();
    //               $sql223555="SELECT b.total_batches  FROM   batch_planning b  WHERE b.client_po_no= '".$row["order_no"]."' and b.product_code= '".$row["product_code"]."'";
                
    //              $result55555 = $conn->query($sql223555);
    //              if ($result55555->num_rows > 0) {
    //                 while ($row55555 = $result55555->fetch_assoc()) {
                        
    //                         $output555555[]=$row55555;   
    //                 }
                     
    //              }
                 
    //               $row["total_batches"] = $output555555;
    //              $output5555551 = Array();
    //               $sql2235551="SELECT * FROM manufacturing_process a LEFT JOIN manufacturing_process_stages b on a.id=b.manufacturing_process_id WHERE a.product_code='".$row["product_code"]."'; ";
                
    //              $result555551 = $conn->query($sql2235551);
    //              if ($result555551->num_rows > 0) {
    //                 while ($row555551 = $result555551->fetch_assoc()) {
                        
    //                         $output5555551[]=$row555551;   
    //                 }
                     
    //              }
                 
    //               $row["stages"] = $output5555551;
    //               $row["total_batches"] = $output555555;
    //               $row["planDates"] = $output5555;
    //               $row["startsDates"] = $output555;
    //               $row["batch_nos"] = $output55;
                  
                  
                  
                  
                  
                  
                  
                  
                  
    //                          $outputData = [];
    //                             $sql99 = "SELECT
    //                                     a.id as wordOrderId,
    //                                     a.batch_number,
    //                                     a.approved_date AS startsDate,
    //                                     b.approve_date AS planDate,
    //                                     b.total_batches,
    //                                     b.entry_date AS Actual_planning_date,
    //                                     a.entry_date AS Actual_work_orderDate,
    //                                     a.qa_date AS Actual_batchApprovalDate,
    //                                     a.rm_disp_completed_date AS Actual_DispensingDate,
    //                                     a.rm_qa_dislc_date AS Actual_LineClearanceDate,
    //                                     p.work_orderDate,
    //                                     p.batchApprovalDate,
    //                                     p.LineClearanceDate,
    //                                     p.DispensingDate,
    //                                     p.productionDate,
    //                                     p.PackingDate,
    //                                     p.FgTransferDate,
    //                                     p.DispatchDate,
    //                                     p.inprocessRelease,
    //                                     p.fgRelease,
    //                                     p.FillingDate,
    //                                     b.start_date,
    //                                     b.id as batch_id
    //                                 FROM
    //                                     mfg_work_order_hdr a
    //                                 LEFT JOIN batch_planning b ON
    //                                     a.batch_plan_id = b.id
    //                                 LEFT JOIN product p ON
    //                                     b.product_code = p.product_code
    //                                  WHERE b.client_po_no = '".$row["order_no"]."' 
    //                                   AND b.product_code = '".$row["product_code"]."'
    //                             ";
                                
    //                             $result99 = $conn->query($sql99);
                                
    //                             if ($result99->num_rows > 0) {
    //                                 while ($rowData = $result99->fetch_assoc()) {
                                        
                                       
    //                                 //   if ( empty($start_date)) {
    //                                 //         $start_date = date("Y-m-d"); // current date in YYYY-MM-DD format
    //                                 //     }else{
    //                                          $start_date=$rowData['start_date'];
    //                                     // }
                                        
                                        
    //                                       $DispensingDate = new DateTime($start_date);
    //                                     $DispensingDate->modify('+' . intval($rowData['DispensingDate']) . ' days');
    //                                     $rowData['Forecast_DispensingDate'] = $DispensingDate->format('Y-m-d');
                                        
    //                                       $productionDate = new DateTime($rowData['Forecast_DispensingDate']);
    //                                     $productionDate->modify('+' . intval($rowData['productionDate']) . ' days');
    //                                     $rowData['Forecast_productionDate'] = $productionDate->format('Y-m-d');
                                       
    //                                       $FillingDate = new DateTime($rowData['Forecast_productionDate']);
    //                                     $FillingDate->modify('+' . intval($rowData['FillingDate']) . ' days');
    //                                     $rowData['Forecast_FillingDate'] = $FillingDate->format('Y-m-d');
                                       
    //                                       $inprocessRelease = new DateTime($rowData['Forecast_FillingDate']);
    //                                     $inprocessRelease->modify('+' . intval($rowData['inprocessRelease']) . ' days');
    //                                     $rowData['Forecast_inprocessRelease'] = $inprocessRelease->format('Y-m-d');
                                         
                                         
    //                                       $PackingDate = new DateTime($rowData['Forecast_inprocessRelease']);
    //                                     $PackingDate->modify('+' . intval($rowData['PackingDate']) . ' days');
    //                                     $rowData['Forecast_PackingDate'] = $PackingDate->format('Y-m-d');
                                        
    //                                       $fgRelease = new DateTime($rowData['Forecast_PackingDate']);
    //                                     $fgRelease->modify('+' . intval($rowData['fgRelease']) . ' days');
    //                                     $rowData['Forecast_fgRelease'] = $fgRelease->format('Y-m-d');
                                          
    //                                       $FgTransferDate = new DateTime($rowData['Forecast_fgRelease']);
    //                                     $FgTransferDate->modify('+' . intval($rowData['FgTransferDate']) . ' days');
    //                                     $rowData['Forecast_FgTransferDate'] = $FgTransferDate->format('Y-m-d');
                                        
                                        
                                        
                                        
                                        
                                        
    //                                     // $planningDate = new DateTime($rowData['Actual_planning_date']);
    //                                     // $planningDate->modify('+' . intval($rowData['work_orderDate']) . ' days');
    //                                     // $rowData['Forecast_work_orderDate'] = $planningDate->format('Y-m-d');
                                        
    //                                     // $batchApprovalDate = new DateTime($rowData['Forecast_work_orderDate']);
    //                                     // $batchApprovalDate->modify('+' . intval($rowData['batchApprovalDate']) . ' days');
    //                                     // $rowData['Forecast_batchApprovalDate'] = $batchApprovalDate->format('Y-m-d');
                                        
    //                                     // $LineClearanceDate = new DateTime($rowData['Forecast_batchApprovalDate']);
    //                                     // $LineClearanceDate->modify('+' . intval($rowData['LineClearanceDate']) . ' days');
    //                                     // $rowData['Forecast_LineClearanceDate'] = $LineClearanceDate->format('Y-m-d');
                                        
    //                                     // $DispensingDate = new DateTime($rowData['Forecast_LineClearanceDate']);
    //                                     // $DispensingDate->modify('+' . intval($rowData['DispensingDate']) . ' days');
    //                                     // $rowData['Forecast_DispensingDate'] = $DispensingDate->format('Y-m-d');
                                        
    //                                     // $productionDate = new DateTime($rowData['Forecast_DispensingDate']);
    //                                     // $productionDate->modify('+' . intval($rowData['productionDate']) . ' days');
    //                                     // $rowData['Forecast_productionDate'] = $productionDate->format('Y-m-d');
                                        
    //                                     // $PackingDate = new DateTime($rowData['Forecast_productionDate']);
    //                                     // $PackingDate->modify('+' . intval($rowData['PackingDate']) . ' days');
    //                                     // $rowData['Forecast_PackingDate'] = $PackingDate->format('Y-m-d');
                                        
    //                                     // $FgTransferDate = new DateTime($rowData['Forecast_PackingDate']);
    //                                     // $FgTransferDate->modify('+' . intval($rowData['FgTransferDate']) . ' days');
    //                                     // $rowData['Forecast_FgTransferDate'] = $FgTransferDate->format('Y-m-d');
                                        
    //                                     // $DispatchDate = new DateTime($rowData['Forecast_FgTransferDate']);
    //                                     // $DispatchDate->modify('+' . intval($rowData['DispatchDate']) . ' days');
    //                                     // $rowData['Forecast_DispatchDate'] = $DispatchDate->format('Y-m-d');
                                        
                                        
                                        
    //                                      $output1 = Array();
    //                     $sql1 = "SELECT * FROM mrp_stages WHERE work_order_id='".$rowData["wordOrderId"]."'  ";
                        
    //                     $result1 = $conn->query($sql1);
    //                     if ($result1->num_rows > 0) {
    //                         while ($row1 = $result1->fetch_assoc()) {
    //                             $output1[] = $row1;
    //                         }
    //                     }
                                        
    //                                     $rowData["stages"] = $output1;  
                                        
                                        
                                        
    //                                     $outputData[] = $rowData;
    //                                 }
    //                             }
                                
    //                             // then you can assign directly
    //                             $row["batch_details"] = $outputData;

                    
    //                 // $output[] = $row;
    //                 if (!empty($row["total_batches"]) && $row["total_batches"] != 0) {
    //                             $output[] = $row;
    //                         }
                    
                    
            
    //         }
    //     }
    //     echo json_encode($output);
    
    //   }
     else if ($_GET["type"] == "ZumaSplitPlanningMicro") {
        
        $output = Array();
        
        $sql = "SELECT a.id as pid, a.unit, a.order_no, b.file, c.TrdNm, b.po_no, b.po_type, b.valid_till, b.po_date, 
                a.product_code, d.dosage_form as product_type, b.client_code, b.po_no, b.required_date as commencementDate,
                a.order_qty, a.pack_size, latest_unitformula.bom_batch_size, d.product_name, d.grade as product_grade, 
                e.raw_materials, e.id as unit_formula_id, a.order_qty as qty_to_prepare,  
                COALESCE(sb.qty, 0) - COALESCE((
                    SELECT SUM(qty)
                    FROM fg_material_issue mi 
                    WHERE mi.batch_no = sb.batch_no AND mi.material_code = sb.material_code
                ), 0) AS stock_qty, sb.batch_no,
                (SELECT po_date FROM po_entry po WHERE po.order_no = a.order_no) as po_date
                FROM order_materials a 
                LEFT JOIN po_entry b ON a.order_no = b.order_no 
                LEFT JOIN client c ON b.client_code = c.client_code
                LEFT JOIN product d ON a.product_code = d.product_code 
                LEFT JOIN (
                    SELECT product_code, bom_batch_size, MAX(id) as max_id
                    FROM unitformula
                    GROUP BY product_code, bom_batch_size
                ) latest_unitformula ON a.product_code = latest_unitformula.product_code
                LEFT JOIN unitformula e ON latest_unitformula.max_id = e.id
                LEFT JOIN fg_stock_book sb ON sb.material_code = a.product_code   
                ORDER BY a.sequence ASC, a.id DESC";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                
                $unit_formula_id_escaped = mysqli_real_escape_string($conn, $row["unit_formula_id"]);
                $sql1 = "SELECT a.*, b.id, b.unit_formula_id, b.market_type, b.country_specific, b.country_name, 
                         b.packing_type, b.pack_size, b.batch_size, b.unit  
                         FROM unitformula_packing_materials a 
                         LEFT JOIN unitformula_pm_dtl b ON a.unit_formula_dtl_id = b.id 
                         WHERE b.unit_formula_id = '".$unit_formula_id_escaped."'";
                
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $output2 = Array();
                $order_no_escaped = mysqli_real_escape_string($conn, $row["order_no"]);
                 $sql2 = "SELECT *, a.oder_qty as Qty, a.balance_qty as bal_qty, IFNULL(a.batch_plan_id, 0) AS batch_plan_id 
                         FROM split_planning_qty a
                         WHERE a.order_no = '".$order_no_escaped."'";
                
                $result2 = $conn->query($sql2);
                if ($result2 && $result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2[] = $row2;
                    }
                }
                
                $row["pack_size"] = json_decode($row["pack_size"]);
                $row['splits'] = $output2;
                $row['packing_configuration'] = $output1;
                $rawMaterials = json_decode($row["raw_materials"], true);
                $row["raw_materials"] = $rawMaterials;
                
                $output123 = Array();
                $product_code_escaped = mysqli_real_escape_string($conn, $row["product_code"]);
                $sql22 = "SELECT id, mfr_no, batch_size 
                          FROM unitformula 
                          WHERE product_code = '".$product_code_escaped."'";
                
                $result1234 = $conn->query($sql22);
                if ($result1234 && $result1234->num_rows > 0) {
                    while ($row1234 = $result1234->fetch_assoc()) {
                        $output2 = Array();
                        $mfr_no_escaped = mysqli_real_escape_string($conn, $row1234["mfr_no"]);
                        $sql3 = "SELECT id, mfr_no, bfr_no, batch_formula_weight, raw_materials, packing_materials 
                                 FROM batch_formula_info  
                                 WHERE mfr_no = '".$mfr_no_escaped."' AND status = 'Approve'";
                        $result2 = $conn->query($sql3);
                        if ($result2 && $result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;   
                            }
                        }
                        $row1234["bfr_records"] = $output2;
                        $output123[] = $row1234;   
                    }
                }
                $row["mfr_records"] = $output123;
                
                $output55 = Array();
                $sql223 = "SELECT a.batch_number  
                           FROM mfg_work_order_hdr a 
                           LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
                           WHERE b.client_po_no = '".$order_no_escaped."' 
                           AND b.product_code = '".$product_code_escaped."'";
                
                $result55 = $conn->query($sql223);
                if ($result55 && $result55->num_rows > 0) {
                    while ($row55 = $result55->fetch_assoc()) {
                        $output55[] = $row55;   
                    }
                }
                
                $output555 = Array();
                $sql2235 = "SELECT a.approved_date  
                            FROM mfg_work_order_hdr a 
                            LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
                            WHERE b.client_po_no = '".$order_no_escaped."' 
                            AND b.product_code = '".$product_code_escaped."'";
                
                $result555 = $conn->query($sql2235);
                if ($result555 && $result555->num_rows > 0) {
                    while ($row555 = $result555->fetch_assoc()) {
                        $output555[] = $row555;   
                    }
                }
                
                $output5555 = Array();
                $sql22355 = "SELECT b.approve_date  
                              FROM batch_planning b  
                              WHERE b.client_po_no = '".$order_no_escaped."' 
                              AND b.product_code = '".$product_code_escaped."'";
                
                $result5555 = $conn->query($sql22355);
                if ($result5555 && $result5555->num_rows > 0) {
                    while ($row5555 = $result5555->fetch_assoc()) {
                        $output5555[] = $row5555;   
                    }
                }
                
                $output555555 = Array();
                $sql223555 = "SELECT b.total_batches  
                              FROM batch_planning b  
                              WHERE b.client_po_no = '".$order_no_escaped."' 
                              AND b.product_code = '".$product_code_escaped."'";
                
                $result55555 = $conn->query($sql223555);
                if ($result55555 && $result55555->num_rows > 0) {
                    while ($row55555 = $result55555->fetch_assoc()) {
                        $output555555[] = $row55555;   
                    }
                }
                
                $output5555551 = Array();
                $sql2235551 = "SELECT * 
                               FROM manufacturing_process a 
                               LEFT JOIN manufacturing_process_stages b ON a.id = b.manufacturing_process_id 
                               WHERE a.product_code = '".$product_code_escaped."'";
                
                $result555551 = $conn->query($sql2235551);
                if ($result555551 && $result555551->num_rows > 0) {
                    while ($row555551 = $result555551->fetch_assoc()) {
                        $output5555551[] = $row555551;   
                    }
                }
                
                $row["stages"] = $output5555551;
                $row["total_batches"] = $output555555;
                $row["planDates"] = $output5555;
                $row["startsDates"] = $output555;
                $row["batch_nos"] = $output55;
                
                $outputData = Array();
                 $sql99 = "SELECT a.id as wordOrderId, a.batch_number, a.approved_date AS startsDate, b.approve_date AS planDate,
                          b.total_batches, b.entry_date AS Actual_planning_date, a.entry_date AS Actual_work_orderDate,
                          a.qa_date AS Actual_batchApprovalDate, a.rm_disp_completed_date AS Actual_DispensingDate,
                          a.rm_qa_dislc_date AS Actual_LineClearanceDate, p.work_orderDate, p.batchApprovalDate,
                          p.LineClearanceDate, p.DispensingDate, p.productionDate, p.PackingDate, p.FgTransferDate,
                          p.DispatchDate, p.inprocessRelease, p.fgRelease, p.FillingDate, b.start_date, b.id as batch_id
                          FROM mfg_work_order_hdr a
                          LEFT JOIN batch_planning b ON a.batch_plan_id = b.id
                          LEFT JOIN product p ON b.product_code = p.product_code
                          WHERE b.client_po_no = '".$row["po_no"]."'
                          AND b.product_code = '".$product_code_escaped."'";
                
                $result99 = $conn->query($sql99);
                
                if ($result99 && $result99->num_rows > 0) {
                    while ($rowData = $result99->fetch_assoc()) {
                        $start_date = $rowData['start_date'];
                        
                        $DispensingDate = new DateTime($start_date);
                        $DispensingDate->modify('+' . intval($rowData['DispensingDate']) . ' days');
                        $rowData['Forecast_DispensingDate'] = $DispensingDate->format('Y-m-d');
                        
                        $productionDate = new DateTime($rowData['Forecast_DispensingDate']);
                        $productionDate->modify('+' . intval($rowData['productionDate']) . ' days');
                        $rowData['Forecast_productionDate'] = $productionDate->format('Y-m-d');
                        
                        $FillingDate = new DateTime($rowData['Forecast_productionDate']);
                        $FillingDate->modify('+' . intval($rowData['FillingDate']) . ' days');
                        $rowData['Forecast_FillingDate'] = $FillingDate->format('Y-m-d');
                        
                        $inprocessRelease = new DateTime($rowData['Forecast_FillingDate']);
                        $inprocessRelease->modify('+' . intval($rowData['inprocessRelease']) . ' days');
                        $rowData['Forecast_inprocessRelease'] = $inprocessRelease->format('Y-m-d');
                        
                        $PackingDate = new DateTime($rowData['Forecast_inprocessRelease']);
                        $PackingDate->modify('+' . intval($rowData['PackingDate']) . ' days');
                        $rowData['Forecast_PackingDate'] = $PackingDate->format('Y-m-d');
                        
                        $fgRelease = new DateTime($rowData['Forecast_PackingDate']);
                        $fgRelease->modify('+' . intval($rowData['fgRelease']) . ' days');
                        $rowData['Forecast_fgRelease'] = $fgRelease->format('Y-m-d');
                        
                        $FgTransferDate = new DateTime($rowData['Forecast_fgRelease']);
                        $FgTransferDate->modify('+' . intval($rowData['FgTransferDate']) . ' days');
                        $rowData['Forecast_FgTransferDate'] = $FgTransferDate->format('Y-m-d');
                        
                        $output1 = Array();
                        $wordOrderId_escaped = mysqli_real_escape_string($conn, $rowData["wordOrderId"]);
                        $sql1 = "SELECT * FROM mrp_stages WHERE work_order_id = '".$wordOrderId_escaped."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1 && $result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                        
                        $rowData["stages"] = $output1;  
                        $outputData[] = $rowData;
                    }
                }
                
                $row["batch_details"] = $outputData;
                
                // if (!empty($row["total_batches"]) && $row["total_batches"] != 0) {
                    $output[] = $row;
                // }
            }
        }
        echo json_encode($output);
    }
      else if ($_GET["type"] == "ZumaSplitPlanning") {
      
         
$output = array();

$sql = "SELECT DISTINCT 
    a.id, a.id AS pid, a.unit, a.order_no,
    b.file, c.TrdNm, b.po_no, b.po_type, b.valid_till, b.po_date,
    a.product_code, d.dosage_form AS product_type, b.client_code,
    b.required_date AS commencementDate,
    a.order_qty, a.pack_size,
    latest_unitformula.bom_batch_size, d.product_name, d.grade AS product_grade,
    e.raw_materials, e.id AS unit_formula_id,
    a.order_qty AS qty_to_prepare
FROM order_materials a
LEFT JOIN po_entry b ON a.order_no = b.order_no
LEFT JOIN client c ON b.client_code = c.client_code
LEFT JOIN product d ON a.product_code = d.product_code
LEFT JOIN (
    SELECT product_code, MAX(id) AS max_id, bom_batch_size
    FROM unitformula
    GROUP BY product_code
) latest_unitformula ON a.product_code = latest_unitformula.product_code
LEFT JOIN unitformula e ON latest_unitformula.max_id = e.id
ORDER BY a.id DESC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
 
        // ---- Get Stock Quantity ----
        $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_stock_book WHERE material_code = '".$row['product_code']."'";
        $result0 = $conn->query($sql0);
        $row['stock_qty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

        // ---- Get Issued Quantity ----
        $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_material_issue WHERE material_code = '".$row['product_code']."'";
        $result0 = $conn->query($sql0);
        $row['issuedQty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

        // ---- Calculate Available Stock ----
        $row['avbl_stock'] = $row['stock_qty'] - $row['issuedQty'];

        // ---- Decode pack_size ----
        $row['pack_size'] = json_decode($row['pack_size'], true);

        // ---- Packing Configuration ----
        $packing_config = array();
        $sql1 = "SELECT a.*, b.* 
                 FROM unitformula_packing_materials a 
                 LEFT JOIN unitformula_pm_dtl b ON a.unit_formula_dtl_id = b.id 
                 WHERE b.unit_formula_id = '".$row["unit_formula_id"]."'";
        $result1 = $conn->query($sql1);
        while ($result1 && $r1 = $result1->fetch_assoc()) {
            $packing_config[] = $r1;
        }
        $row['packing_configuration'] = $packing_config;

        // ---- Split Planning Quantities ----
                         $row['manufactureQty'] = 0;   // ✅ initialize safely
                            $splits = [];
                            
                            $sql2 = "
                                SELECT 
                                    a.*,
                                    a.oder_qty AS Qty,
                                    a.balance_qty AS bal_qty,
                                    IFNULL(a.batch_plan_id, 0) AS batch_plan_id
                                FROM split_planning_qty a
                                WHERE a.order_no = '".$row["order_no"]."'
                            ";
                            
                            $result2 = $conn->query($sql2);
                            
                            while ($result2 && ($r2 = $result2->fetch_assoc())) {
                            
                                $qty = (float)$r2['Qty']; // ✅ force numeric
                            
                                if ((int)$r2['batch_plan_id'] === 0) {
                                    $row['manufactureQty'] += $qty;
                                }
                            
                                $splits[] = $r2;
                            }
                            
                            $row['splits'] = $splits;

        // ---- Decode Raw Materials ----
        $row['raw_materials'] = json_decode($row['raw_materials'], true);

        // ---- MFR & BFR Records ----
        $mfr_records = array();
        $sql22 = "SELECT id, mfr_no, batch_size FROM unitformula WHERE product_code = '".$row["product_code"]."'";
        $result1234 = $conn->query($sql22);
        while ($result1234 && $r1234 = $result1234->fetch_assoc()) {
            $bfr_records = array();
            $sql3 = "SELECT * 
                     FROM batch_formula_info  
                     WHERE mfr_no = '".$r1234["mfr_no"]."' AND status = 'Approve'";
            $result2 = $conn->query($sql3);
            while ($result2 && $r2 = $result2->fetch_assoc()) {
                $bfr_records[] = $r2;
            }
            $r1234["bfr_records"] = $bfr_records;
            $mfr_records[] = $r1234;
        }
        $row["mfr_records"] = $mfr_records;

        // ---- Batch Numbers ----
        $batch_nos = array();
        $sql223 = "SELECT a.batch_number  
                   FROM mfg_work_order_hdr a 
                   LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
                   WHERE b.client_po_no = '".$row["order_no"]."' 
                     AND b.product_code = '".$row["product_code"]."'";
        $result55 = $conn->query($sql223);
        while ($result55 && $r55 = $result55->fetch_assoc()) {
            $batch_nos[] = $r55;
        }
        $row["batch_nos"] = $batch_nos;

        // ---- Plan Dates ----
        $planDates = array();
        $sql_plan = "SELECT b.approve_date  
                     FROM batch_planning b  
                     WHERE b.client_po_no = '".$row["order_no"]."' 
                       AND b.product_code = '".$row["product_code"]."'";
        $result_plan = $conn->query($sql_plan);
        while ($result_plan && $r = $result_plan->fetch_assoc()) {
            $planDates[] = $r;
        }
        $row["planDates"] = $planDates;

        // ---- Start Dates ----
        $startDates = array();
        $sql_start = "SELECT a.approved_date  
                      FROM mfg_work_order_hdr a 
                      LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
                      WHERE b.client_po_no = '".$row["order_no"]."' 
                        AND b.product_code = '".$row["product_code"]."'";
        $result_start = $conn->query($sql_start);
        while ($result_start && $r = $result_start->fetch_assoc()) {
            $startDates[] = $r;
        }
        $row["startsDates"] = $startDates;

        // ---- Total Batches ----
        $total_batches = array();
        $sql_batches = "SELECT b.total_batches  
                        FROM batch_planning b  
                        WHERE b.client_po_no = '".$row["order_no"]."' 
                          AND b.product_code = '".$row["product_code"]."'";
        $result_batches = $conn->query($sql_batches);
        while ($result_batches && $r = $result_batches->fetch_assoc()) {
            $total_batches[] = $r;
        }
        $row["total_batches"] = $total_batches;

        // ---- Manufacturing Process Stages ----
        $stages = array();
        $sql_stages = "SELECT * 
                       FROM manufacturing_process a 
                       LEFT JOIN manufacturing_process_stages b 
                       ON a.id = b.manufacturing_process_id 
                       WHERE a.product_code = '".$row["product_code"]."'";
        $_stages = $conn->query($sql_stages);
        while ($_stages && $r = $_stages->fetch_assoc()) {
            $stages[] = $r;
        }
        $row["stages"] = $stages;

        // ---- Batch Forecasting ----
        $batch_details = array();
        $sql99 = "SELECT
                    a.id AS wordOrderId,
                    a.batch_number,
                    a.approved_date AS startsDate,
                    b.approve_date AS planDate,
                    b.total_batches,
                    b.entry_date AS Actual_planning_date,
                    a.entry_date AS Actual_work_orderDate,
                    a.qa_date AS Actual_batchApprovalDate,
                    a.rm_disp_completed_date AS Actual_DispensingDate,
                    a.rm_qa_dislc_date AS Actual_LineClearanceDate,
                    p.work_orderDate,
                    p.batchApprovalDate,
                    p.LineClearanceDate,
                    p.DispensingDate,
                    p.productionDate,
                    p.PackingDate,
                    p.FgTransferDate,
                    p.DispatchDate
                FROM mfg_work_order_hdr a
                LEFT JOIN batch_planning b ON a.batch_plan_id = b.id
                LEFT JOIN product p ON b.product_code = p.product_code
                WHERE b.client_po_no = '".$row["order_no"]."' 
                  AND b.product_code = '".$row["product_code"]."'";
        $result99 = $conn->query($sql99);
        while ($result99 && $r99 = $result99->fetch_assoc()) {

            // Forecast Dates
            $dates = ['work_orderDate', 'batchApprovalDate', 'LineClearanceDate', 'DispensingDate', 'productionDate', 'PackingDate', 'FgTransferDate', 'DispatchDate'];
            $currentDate = new DateTime($r99['Actual_planning_date']);
            foreach ($dates as $key) {
                if (is_numeric($r99[$key])) {
                    $currentDate->modify('+' . intval($r99[$key]) . ' days');
                    $r99['Forecast_' . $key] = $currentDate->format('Y-m-d');
                } else {
                    $r99['Forecast_' . $key] = null;
                }
            }

            // Add Stage Data
            $r99_stages = array();
            $sql1 = "SELECT * FROM mrp_stages WHERE work_order_id = '".$r99["wordOrderId"]."'";
            $result1 = $conn->query($sql1);
            while ($result1 && $r1 = $result1->fetch_assoc()) {
                $r99_stages[] = $r1;
            }
            $r99['stages'] = $r99_stages;

            $batch_details[] = $r99;
        }
        $row["batch_details"] = $batch_details;

        // ---- Final Push ----
        // $i++;
// $i++;
// error_log("Test Loop $i: " . json_encode($row));
$output[] = $row;
// error_log("Total output entries: " . count($output));
}
}
// Final Output
echo json_encode($output);

      }
//       else if ($_GET["type"] == "ZumaSplitPlanning") {
          
         
// // $output = array();

// // $sql = "SELECT DISTINCT 
// //     a.id, a.id AS pid, a.unit, a.order_no,
// //     b.file, c.TrdNm, b.po_no, b.po_type, b.valid_till, b.po_date,
// //     a.product_code, d.dosage_form AS product_type, b.client_code,
// //     b.required_date AS commencementDate,
// //     a.order_qty, a.pack_size,
// //     latest_unitformula.bom_batch_size, d.product_name, d.grade AS product_grade,
// //     e.raw_materials, e.id AS unit_formula_id,
// //     a.order_qty AS qty_to_prepare
// // FROM order_materials a
// // LEFT JOIN po_entry b ON a.order_no = b.order_no
// // LEFT JOIN client c ON b.client_code = c.client_code
// // LEFT JOIN product d ON a.product_code = d.product_code
// // LEFT JOIN (
// //     SELECT product_code, MAX(id) AS max_id, bom_batch_size
// //     FROM unitformula
// //     GROUP BY product_code
// // ) latest_unitformula ON a.product_code = latest_unitformula.product_code
// // LEFT JOIN unitformula e ON latest_unitformula.max_id = e.id
// // ORDER BY a.id DESC";

// // $result = $conn->query($sql);

// // if ($result->num_rows > 0) {
// //     while ($row = $result->fetch_assoc()) {
 
// //         // ---- Get Stock Quantity ----
// //         $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_stock_book WHERE material_code = '".$row['product_code']."'";
// //         $result0 = $conn->query($sql0);
// //         $row['stock_qty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

// //         // ---- Get Issued Quantity ----
// //         $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_material_issue WHERE material_code = '".$row['product_code']."'";
// //         $result0 = $conn->query($sql0);
// //         $row['issuedQty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

// //         // ---- Calculate Available Stock ----
// //         $row['avbl_stock'] = $row['stock_qty'] - $row['issuedQty'];

// //         // ---- Decode pack_size ----
// //         $row['pack_size'] = json_decode($row['pack_size'], true);

// //         // ---- Packing Configuration ----
// //         $packing_config = array();
// //         $sql1 = "SELECT a.*, b.* 
// //                  FROM unitformula_packing_materials a 
// //                  LEFT JOIN unitformula_pm_dtl b ON a.unit_formula_dtl_id = b.id 
// //                  WHERE b.unit_formula_id = '".$row["unit_formula_id"]."'";
// //         $result1 = $conn->query($sql1);
// //         while ($result1 && $r1 = $result1->fetch_assoc()) {
// //             $packing_config[] = $r1;
// //         }
// //         $row['packing_configuration'] = $packing_config;

// //         // ---- Split Planning Quantities ----
// //         $splits = array();
// //         $sql2 = "SELECT *, a.oder_qty AS Qty, a.balance_qty AS bal_qty, 
// //                         IFNULL(a.batch_plan_id, 0) AS batch_plan_id 
// //                  FROM split_planning_qty a 
// //                  WHERE a.order_no = '".$row["order_no"]."'";
// //         $result2 = $conn->query($sql2);
// //         while ($result2 && $r2 = $result2->fetch_assoc()) {
// //             $splits[] = $r2;
// //         }
// //         $row['splits'] = $splits;

// //         // ---- Decode Raw Materials ----
// //         $row['raw_materials'] = json_decode($row['raw_materials'], true);

// //         // ---- MFR & BFR Records ----
// //         $mfr_records = array();
// //         $sql22 = "SELECT id, mfr_no, batch_size FROM unitformula WHERE product_code = '".$row["product_code"]."'";
// //         $result1234 = $conn->query($sql22);
// //         while ($result1234 && $r1234 = $result1234->fetch_assoc()) {
// //             $bfr_records = array();
// //             $sql3 = "SELECT * 
// //                      FROM batch_formula_info  
// //                      WHERE mfr_no = '".$r1234["mfr_no"]."' AND status = 'Approve'";
// //             $result2 = $conn->query($sql3);
// //             while ($result2 && $r2 = $result2->fetch_assoc()) {
// //                 $bfr_records[] = $r2;
// //             }
// //             $r1234["bfr_records"] = $bfr_records;
// //             $mfr_records[] = $r1234;
// //         }
// //         $row["mfr_records"] = $mfr_records;

// //         // ---- Batch Numbers ----
// //         $batch_nos = array();
// //         $sql223 = "SELECT a.batch_number  
// //                   FROM mfg_work_order_hdr a 
// //                   LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
// //                   WHERE b.client_po_no = '".$row["order_no"]."' 
// //                      AND b.product_code = '".$row["product_code"]."'";
// //         $result55 = $conn->query($sql223);
// //         while ($result55 && $r55 = $result55->fetch_assoc()) {
// //             $batch_nos[] = $r55;
// //         }
// //         $row["batch_nos"] = $batch_nos;

// //         // ---- Plan Dates ----
// //         $planDates = array();
// //         $sql_plan = "SELECT b.approve_date  
// //                      FROM batch_planning b  
// //                      WHERE b.client_po_no = '".$row["order_no"]."' 
// //                       AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_plan);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $planDates[] = $r;
// //         }
// //         $row["planDates"] = $planDates;

// //         // ---- Start Dates ----
// //         $startDates = array();
// //         $sql_start = "SELECT a.approved_date  
// //                       FROM mfg_work_order_hdr a 
// //                       LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
// //                       WHERE b.client_po_no = '".$row["order_no"]."' 
// //                         AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_start);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $startDates[] = $r;
// //         }
// //         $row["startsDates"] = $startDates;

// //         // ---- Total Batches ----
// //         $total_batches = array();
// //         $sql_batches = "SELECT b.total_batches  
// //                         FROM batch_planning b  
// //                         WHERE b.client_po_no = '".$row["order_no"]."' 
// //                           AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_batches);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $total_batches[] = $r;
// //         }
// //         $row["total_batches"] = $total_batches;

// //         // ---- Manufacturing Process Stages ----
// //         $stages = array();
// //         $sql_stages = "SELECT * 
// //                       FROM manufacturing_process a 
// //                       LEFT JOIN manufacturing_process_stages b 
// //                       ON a.id = b.manufacturing_process_id 
// //                       WHERE a.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_stages);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $stages[] = $r;
// //         }
// //         $row["stages"] = $stages;

// //         // ---- Batch Forecasting ----
// //         $batch_details = array();
// //         $sql99 = "SELECT
// //                     a.id AS wordOrderId,
// //                     a.batch_number,
// //                     a.approved_date AS startsDate,
// //                     b.approve_date AS planDate,
// //                     b.total_batches,
// //                     b.entry_date AS Actual_planning_date,
// //                     a.entry_date AS Actual_work_orderDate,
// //                     a.qa_date AS Actual_batchApprovalDate,
// //                     a.rm_disp_completed_date AS Actual_DispensingDate,
// //                     a.rm_qa_dislc_date AS Actual_LineClearanceDate,
// //                     p.work_orderDate,
// //                     p.batchApprovalDate,
// //                     p.LineClearanceDate,
// //                     p.DispensingDate,
// //                     p.productionDate,
// //                     p.PackingDate,
// //                     p.FgTransferDate,
// //                     p.DispatchDate
// //                 FROM mfg_work_order_hdr a
// //                 LEFT JOIN batch_planning b ON a.batch_plan_id = b.id
// //                 LEFT JOIN product p ON b.product_code = p.product_code
// //                 WHERE b.client_po_no = '".$row["order_no"]."' 
// //                   AND b.product_code = '".$row["product_code"]."'";
// //         $result99 = $conn->query($sql99);
// //         while ($result99 && $r99 = $result99->fetch_assoc()) {

// //             // Forecast Dates
// //             $dates = ['work_orderDate', 'batchApprovalDate', 'LineClearanceDate', 'DispensingDate', 'productionDate', 'PackingDate', 'FgTransferDate', 'DispatchDate'];
// //             $currentDate = new DateTime($r99['Actual_planning_date']);
// //             foreach ($dates as $key) {
// //                 if (is_numeric($r99[$key])) {
// //                     $currentDate->modify('+' . intval($r99[$key]) . ' days');
// //                     $r99['Forecast_' . $key] = $currentDate->format('Y-m-d');
// //                 } else {
// //                     $r99['Forecast_' . $key] = null;
// //                 }
// //             }

// //             // Add Stage Data
// //             $r99_stages = array();
// //             $sql1 = "SELECT * FROM mrp_stages WHERE work_order_id = '".$r99["wordOrderId"]."'";
// //             $result1 = $conn->query($sql1);
// //             while ($result1 && $r1 = $result1->fetch_assoc()) {
// //                 $r99_stages[] = $r1;
// //             }
// //             $r99['stages'] = $r99_stages;

// //             $batch_details[] = $r99;
// //         }
// //         $row["batch_details"] = $batch_details;

// //         // ---- Final Push ----
// //         // $i++;
// // // $i++;
// // // error_log("Test Loop $i: " . json_encode($row));
// // $output[] = $row;
// // // error_log("Total output entries: " . count($output));
// // }
// // }
// // // Final Output
// // echo json_encode($output);

      
          
         
// // $output = array();

// // $sql = "SELECT DISTINCT 
// //     a.id, a.id AS pid, a.unit, a.order_no,
// //     b.file, c.TrdNm, b.po_no, b.po_type, b.valid_till, b.po_date,
// //     a.product_code, d.dosage_form AS product_type, b.client_code,
// //     b.required_date AS commencementDate,
// //     a.order_qty, a.pack_size,
// //     latest_unitformula.bom_batch_size, d.product_name, d.grade AS product_grade,
// //     e.raw_materials, e.id AS unit_formula_id,
// //     a.order_qty AS qty_to_prepare
// // FROM order_materials a
// // LEFT JOIN po_entry b ON a.order_no = b.order_no
// // LEFT JOIN client c ON b.client_code = c.client_code
// // LEFT JOIN product d ON a.product_code = d.product_code
// // LEFT JOIN (
// //     SELECT product_code, MAX(id) AS max_id, bom_batch_size
// //     FROM unitformula
// //     GROUP BY product_code
// // ) latest_unitformula ON a.product_code = latest_unitformula.product_code
// // LEFT JOIN unitformula e ON latest_unitformula.max_id = e.id
// // ORDER BY a.id DESC";

// // $result = $conn->query($sql);

// // if ($result->num_rows > 0) {
// //     while ($row = $result->fetch_assoc()) {
 
// //         // ---- Get Stock Quantity ----
// //         $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_stock_book WHERE material_code = '".$row['product_code']."'";
// //         $result0 = $conn->query($sql0);
// //         $row['stock_qty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

// //         // ---- Get Issued Quantity ----
// //         $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_material_issue WHERE material_code = '".$row['product_code']."'";
// //         $result0 = $conn->query($sql0);
// //         $row['issuedQty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

// //         // ---- Calculate Available Stock ----
// //         $row['avbl_stock'] = $row['stock_qty'] - $row['issuedQty'];

// //         // ---- Decode pack_size ----
// //         $row['pack_size'] = json_decode($row['pack_size'], true);

// //         // ---- Packing Configuration ----
// //         $packing_config = array();
// //         $sql1 = "SELECT a.*, b.* 
// //                  FROM unitformula_packing_materials a 
// //                  LEFT JOIN unitformula_pm_dtl b ON a.unit_formula_dtl_id = b.id 
// //                  WHERE b.unit_formula_id = '".$row["unit_formula_id"]."'";
// //         $result1 = $conn->query($sql1);
// //         while ($result1 && $r1 = $result1->fetch_assoc()) {
// //             $packing_config[] = $r1;
// //         }
// //         $row['packing_configuration'] = $packing_config;

// //         // ---- Split Planning Quantities ----
// //         $splits = array();
// //         $sql2 = "SELECT *, a.oder_qty AS Qty, a.balance_qty AS bal_qty, 
// //                         IFNULL(a.batch_plan_id, 0) AS batch_plan_id 
// //                  FROM split_planning_qty a 
// //                  WHERE a.order_no = '".$row["order_no"]."'";
// //         $result2 = $conn->query($sql2);
// //         while ($result2 && $r2 = $result2->fetch_assoc()) {
// //             $splits[] = $r2;
// //         }
// //         $row['splits'] = $splits;

// //         // ---- Decode Raw Materials ----
// //         $row['raw_materials'] = json_decode($row['raw_materials'], true);

// //         // ---- MFR & BFR Records ----
// //         $mfr_records = array();
// //         $sql22 = "SELECT id, mfr_no, batch_size FROM unitformula WHERE product_code = '".$row["product_code"]."'";
// //         $result1234 = $conn->query($sql22);
// //         while ($result1234 && $r1234 = $result1234->fetch_assoc()) {
// //             $bfr_records = array();
// //             $sql3 = "SELECT * 
// //                      FROM batch_formula_info  
// //                      WHERE mfr_no = '".$r1234["mfr_no"]."' AND status = 'Approve'";
// //             $result2 = $conn->query($sql3);
// //             while ($result2 && $r2 = $result2->fetch_assoc()) {
// //                 $bfr_records[] = $r2;
// //             }
// //             $r1234["bfr_records"] = $bfr_records;
// //             $mfr_records[] = $r1234;
// //         }
// //         $row["mfr_records"] = $mfr_records;

// //         // ---- Batch Numbers ----
// //         $batch_nos = array();
// //         $sql223 = "SELECT a.batch_number  
// //                   FROM mfg_work_order_hdr a 
// //                   LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
// //                   WHERE b.client_po_no = '".$row["order_no"]."' 
// //                      AND b.product_code = '".$row["product_code"]."'";
// //         $result55 = $conn->query($sql223);
// //         while ($result55 && $r55 = $result55->fetch_assoc()) {
// //             $batch_nos[] = $r55;
// //         }
// //         $row["batch_nos"] = $batch_nos;

// //         // ---- Plan Dates ----
// //         $planDates = array();
// //         $sql_plan = "SELECT b.approve_date  
// //                      FROM batch_planning b  
// //                      WHERE b.client_po_no = '".$row["order_no"]."' 
// //                       AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_plan);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $planDates[] = $r;
// //         }
// //         $row["planDates"] = $planDates;

// //         // ---- Start Dates ----
// //         $startDates = array();
// //         $sql_start = "SELECT a.approved_date  
// //                       FROM mfg_work_order_hdr a 
// //                       LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
// //                       WHERE b.client_po_no = '".$row["order_no"]."' 
// //                         AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_start);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $startDates[] = $r;
// //         }
// //         $row["startsDates"] = $startDates;

// //         // ---- Total Batches ----
// //         $total_batches = array();
// //         $sql_batches = "SELECT b.total_batches  
// //                         FROM batch_planning b  
// //                         WHERE b.client_po_no = '".$row["order_no"]."' 
// //                           AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_batches);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $total_batches[] = $r;
// //         }
// //         $row["total_batches"] = $total_batches;

// //         // ---- Manufacturing Process Stages ----
// //         $stages = array();
// //         $sql_stages = "SELECT * 
// //                       FROM manufacturing_process a 
// //                       LEFT JOIN manufacturing_process_stages b 
// //                       ON a.id = b.manufacturing_process_id 
// //                       WHERE a.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_stages);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $stages[] = $r;
// //         }
// //         $row["stages"] = $stages;

// //         // ---- Batch Forecasting ----
// //         $batch_details = array();
// //         $sql99 = "SELECT
// //                     a.id AS wordOrderId,
// //                     a.batch_number,
// //                     a.approved_date AS startsDate,
// //                     b.approve_date AS planDate,
// //                     b.total_batches,
// //                     b.entry_date AS Actual_planning_date,
// //                     a.entry_date AS Actual_work_orderDate,
// //                     a.qa_date AS Actual_batchApprovalDate,
// //                     a.rm_disp_completed_date AS Actual_DispensingDate,
// //                     a.rm_qa_dislc_date AS Actual_LineClearanceDate,
// //                     p.work_orderDate,
// //                     p.batchApprovalDate,
// //                     p.LineClearanceDate,
// //                     p.DispensingDate,
// //                     p.productionDate,
// //                     p.PackingDate,
// //                     p.FgTransferDate,
// //                     p.DispatchDate
// //                 FROM mfg_work_order_hdr a
// //                 LEFT JOIN batch_planning b ON a.batch_plan_id = b.id
// //                 LEFT JOIN product p ON b.product_code = p.product_code
// //                 WHERE b.client_po_no = '".$row["order_no"]."' 
// //                   AND b.product_code = '".$row["product_code"]."'";
// //         $result99 = $conn->query($sql99);
// //         while ($result99 && $r99 = $result99->fetch_assoc()) {

// //             // Forecast Dates
// //             $dates = ['work_orderDate', 'batchApprovalDate', 'LineClearanceDate', 'DispensingDate', 'productionDate', 'PackingDate', 'FgTransferDate', 'DispatchDate'];
// //             $currentDate = new DateTime($r99['Actual_planning_date']);
// //             foreach ($dates as $key) {
// //                 if (is_numeric($r99[$key])) {
// //                     $currentDate->modify('+' . intval($r99[$key]) . ' days');
// //                     $r99['Forecast_' . $key] = $currentDate->format('Y-m-d');
// //                 } else {
// //                     $r99['Forecast_' . $key] = null;
// //                 }
// //             }

// //             // Add Stage Data
// //             $r99_stages = array();
// //             $sql1 = "SELECT * FROM mrp_stages WHERE work_order_id = '".$r99["wordOrderId"]."'";
// //             $result1 = $conn->query($sql1);
// //             while ($result1 && $r1 = $result1->fetch_assoc()) {
// //                 $r99_stages[] = $r1;
// //             }
// //             $r99['stages'] = $r99_stages;

// //             $batch_details[] = $r99;
// //         }
// //         $row["batch_details"] = $batch_details;

// //         // ---- Final Push ----
// //         // $i++;
// // // $i++;
// // // error_log("Test Loop $i: " . json_encode($row));
// // $output[] = $row;
// // // error_log("Total output entries: " . count($output));
// // }
// // }
// // // Final Output
// // echo json_encode($output);

      
          
         
// // $output = array();

// // $sql = "SELECT DISTINCT 
// //     a.id, a.id AS pid, a.unit, a.order_no,
// //     b.file, c.TrdNm, b.po_no, b.po_type, b.valid_till, b.po_date,
// //     a.product_code, d.dosage_form AS product_type, b.client_code,
// //     b.required_date AS commencementDate,
// //     a.order_qty, a.pack_size,
// //     latest_unitformula.bom_batch_size, d.product_name, d.grade AS product_grade,
// //     e.raw_materials, e.id AS unit_formula_id,
// //     a.order_qty AS qty_to_prepare
// // FROM order_materials a
// // LEFT JOIN po_entry b ON a.order_no = b.order_no
// // LEFT JOIN client c ON b.client_code = c.client_code
// // LEFT JOIN product d ON a.product_code = d.product_code
// // LEFT JOIN (
// //     SELECT product_code, MAX(id) AS max_id, bom_batch_size
// //     FROM unitformula
// //     GROUP BY product_code
// // ) latest_unitformula ON a.product_code = latest_unitformula.product_code
// // LEFT JOIN unitformula e ON latest_unitformula.max_id = e.id
// // ORDER BY a.id DESC";

// // $result = $conn->query($sql);

// // if ($result->num_rows > 0) {
// //     while ($row = $result->fetch_assoc()) {
 
// //         // ---- Get Stock Quantity ----
// //         $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_stock_book WHERE material_code = '".$row['product_code']."'";
// //         $result0 = $conn->query($sql0);
// //         $row['stock_qty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

// //         // ---- Get Issued Quantity ----
// //         $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_material_issue WHERE material_code = '".$row['product_code']."'";
// //         $result0 = $conn->query($sql0);
// //         $row['issuedQty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

// //         // ---- Calculate Available Stock ----
// //         $row['avbl_stock'] = $row['stock_qty'] - $row['issuedQty'];

// //         // ---- Decode pack_size ----
// //         $row['pack_size'] = json_decode($row['pack_size'], true);

// //         // ---- Packing Configuration ----
// //         $packing_config = array();
// //         $sql1 = "SELECT a.*, b.* 
// //                  FROM unitformula_packing_materials a 
// //                  LEFT JOIN unitformula_pm_dtl b ON a.unit_formula_dtl_id = b.id 
// //                  WHERE b.unit_formula_id = '".$row["unit_formula_id"]."'";
// //         $result1 = $conn->query($sql1);
// //         while ($result1 && $r1 = $result1->fetch_assoc()) {
// //             $packing_config[] = $r1;
// //         }
// //         $row['packing_configuration'] = $packing_config;

// //         // ---- Split Planning Quantities ----
// //         $splits = array();
// //         $sql2 = "SELECT *, a.oder_qty AS Qty, a.balance_qty AS bal_qty, 
// //                         IFNULL(a.batch_plan_id, 0) AS batch_plan_id 
// //                  FROM split_planning_qty a 
// //                  WHERE a.order_no = '".$row["order_no"]."'";
// //         $result2 = $conn->query($sql2);
// //         while ($result2 && $r2 = $result2->fetch_assoc()) {
// //             $splits[] = $r2;
// //         }
// //         $row['splits'] = $splits;

// //         // ---- Decode Raw Materials ----
// //         $row['raw_materials'] = json_decode($row['raw_materials'], true);

// //         // ---- MFR & BFR Records ----
// //         $mfr_records = array();
// //         $sql22 = "SELECT id, mfr_no, batch_size FROM unitformula WHERE product_code = '".$row["product_code"]."'";
// //         $result1234 = $conn->query($sql22);
// //         while ($result1234 && $r1234 = $result1234->fetch_assoc()) {
// //             $bfr_records = array();
// //             $sql3 = "SELECT * 
// //                      FROM batch_formula_info  
// //                      WHERE mfr_no = '".$r1234["mfr_no"]."' AND status = 'Approve'";
// //             $result2 = $conn->query($sql3);
// //             while ($result2 && $r2 = $result2->fetch_assoc()) {
// //                 $bfr_records[] = $r2;
// //             }
// //             $r1234["bfr_records"] = $bfr_records;
// //             $mfr_records[] = $r1234;
// //         }
// //         $row["mfr_records"] = $mfr_records;

// //         // ---- Batch Numbers ----
// //         $batch_nos = array();
// //         $sql223 = "SELECT a.batch_number  
// //                   FROM mfg_work_order_hdr a 
// //                   LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
// //                   WHERE b.client_po_no = '".$row["order_no"]."' 
// //                      AND b.product_code = '".$row["product_code"]."'";
// //         $result55 = $conn->query($sql223);
// //         while ($result55 && $r55 = $result55->fetch_assoc()) {
// //             $batch_nos[] = $r55;
// //         }
// //         $row["batch_nos"] = $batch_nos;

// //         // ---- Plan Dates ----
// //         $planDates = array();
// //         $sql_plan = "SELECT b.approve_date  
// //                      FROM batch_planning b  
// //                      WHERE b.client_po_no = '".$row["order_no"]."' 
// //                       AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_plan);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $planDates[] = $r;
// //         }
// //         $row["planDates"] = $planDates;

// //         // ---- Start Dates ----
// //         $startDates = array();
// //         $sql_start = "SELECT a.approved_date  
// //                       FROM mfg_work_order_hdr a 
// //                       LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
// //                       WHERE b.client_po_no = '".$row["order_no"]."' 
// //                         AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_start);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $startDates[] = $r;
// //         }
// //         $row["startsDates"] = $startDates;

// //         // ---- Total Batches ----
// //         $total_batches = array();
// //         $sql_batches = "SELECT b.total_batches  
// //                         FROM batch_planning b  
// //                         WHERE b.client_po_no = '".$row["order_no"]."' 
// //                           AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_batches);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $total_batches[] = $r;
// //         }
// //         $row["total_batches"] = $total_batches;

// //         // ---- Manufacturing Process Stages ----
// //         $stages = array();
// //         $sql_stages = "SELECT * 
// //                       FROM manufacturing_process a 
// //                       LEFT JOIN manufacturing_process_stages b 
// //                       ON a.id = b.manufacturing_process_id 
// //                       WHERE a.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_stages);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $stages[] = $r;
// //         }
// //         $row["stages"] = $stages;

// //         // ---- Batch Forecasting ----
// //         $batch_details = array();
// //         $sql99 = "SELECT
// //                     a.id AS wordOrderId,
// //                     a.batch_number,
// //                     a.approved_date AS startsDate,
// //                     b.approve_date AS planDate,
// //                     b.total_batches,
// //                     b.entry_date AS Actual_planning_date,
// //                     a.entry_date AS Actual_work_orderDate,
// //                     a.qa_date AS Actual_batchApprovalDate,
// //                     a.rm_disp_completed_date AS Actual_DispensingDate,
// //                     a.rm_qa_dislc_date AS Actual_LineClearanceDate,
// //                     p.work_orderDate,
// //                     p.batchApprovalDate,
// //                     p.LineClearanceDate,
// //                     p.DispensingDate,
// //                     p.productionDate,
// //                     p.PackingDate,
// //                     p.FgTransferDate,
// //                     p.DispatchDate
// //                 FROM mfg_work_order_hdr a
// //                 LEFT JOIN batch_planning b ON a.batch_plan_id = b.id
// //                 LEFT JOIN product p ON b.product_code = p.product_code
// //                 WHERE b.client_po_no = '".$row["order_no"]."' 
// //                   AND b.product_code = '".$row["product_code"]."'";
// //         $result99 = $conn->query($sql99);
// //         while ($result99 && $r99 = $result99->fetch_assoc()) {

// //             // Forecast Dates
// //             $dates = ['work_orderDate', 'batchApprovalDate', 'LineClearanceDate', 'DispensingDate', 'productionDate', 'PackingDate', 'FgTransferDate', 'DispatchDate'];
// //             $currentDate = new DateTime($r99['Actual_planning_date']);
// //             foreach ($dates as $key) {
// //                 if (is_numeric($r99[$key])) {
// //                     $currentDate->modify('+' . intval($r99[$key]) . ' days');
// //                     $r99['Forecast_' . $key] = $currentDate->format('Y-m-d');
// //                 } else {
// //                     $r99['Forecast_' . $key] = null;
// //                 }
// //             }

// //             // Add Stage Data
// //             $r99_stages = array();
// //             $sql1 = "SELECT * FROM mrp_stages WHERE work_order_id = '".$r99["wordOrderId"]."'";
// //             $result1 = $conn->query($sql1);
// //             while ($result1 && $r1 = $result1->fetch_assoc()) {
// //                 $r99_stages[] = $r1;
// //             }
// //             $r99['stages'] = $r99_stages;

// //             $batch_details[] = $r99;
// //         }
// //         $row["batch_details"] = $batch_details;

// //         // ---- Final Push ----
// //         // $i++;
// // // $i++;
// // // error_log("Test Loop $i: " . json_encode($row));
// // $output[] = $row;
// // // error_log("Total output entries: " . count($output));
// // }
// // }
// // // Final Output
// // echo json_encode($output);

      
          
         
// // $output = array();

// // $sql = "SELECT DISTINCT 
// //     a.id, a.id AS pid, a.unit, a.order_no,
// //     b.file, c.TrdNm, b.po_no, b.po_type, b.valid_till, b.po_date,
// //     a.product_code, d.dosage_form AS product_type, b.client_code,
// //     b.required_date AS commencementDate,
// //     a.order_qty, a.pack_size,
// //     latest_unitformula.bom_batch_size, d.product_name, d.grade AS product_grade,
// //     e.raw_materials, e.id AS unit_formula_id,
// //     a.order_qty AS qty_to_prepare
// // FROM order_materials a
// // LEFT JOIN po_entry b ON a.order_no = b.order_no
// // LEFT JOIN client c ON b.client_code = c.client_code
// // LEFT JOIN product d ON a.product_code = d.product_code
// // LEFT JOIN (
// //     SELECT product_code, MAX(id) AS max_id, bom_batch_size
// //     FROM unitformula
// //     GROUP BY product_code
// // ) latest_unitformula ON a.product_code = latest_unitformula.product_code
// // LEFT JOIN unitformula e ON latest_unitformula.max_id = e.id
// // ORDER BY a.id DESC";

// // $result = $conn->query($sql);

// // if ($result->num_rows > 0) {
// //     while ($row = $result->fetch_assoc()) {
 
// //         // ---- Get Stock Quantity ----
// //         $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_stock_book WHERE material_code = '".$row['product_code']."'";
// //         $result0 = $conn->query($sql0);
// //         $row['stock_qty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

// //         // ---- Get Issued Quantity ----
// //         $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_material_issue WHERE material_code = '".$row['product_code']."'";
// //         $result0 = $conn->query($sql0);
// //         $row['issuedQty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

// //         // ---- Calculate Available Stock ----
// //         $row['avbl_stock'] = $row['stock_qty'] - $row['issuedQty'];

// //         // ---- Decode pack_size ----
// //         $row['pack_size'] = json_decode($row['pack_size'], true);

// //         // ---- Packing Configuration ----
// //         $packing_config = array();
// //         $sql1 = "SELECT a.*, b.* 
// //                  FROM unitformula_packing_materials a 
// //                  LEFT JOIN unitformula_pm_dtl b ON a.unit_formula_dtl_id = b.id 
// //                  WHERE b.unit_formula_id = '".$row["unit_formula_id"]."'";
// //         $result1 = $conn->query($sql1);
// //         while ($result1 && $r1 = $result1->fetch_assoc()) {
// //             $packing_config[] = $r1;
// //         }
// //         $row['packing_configuration'] = $packing_config;

// //         // ---- Split Planning Quantities ----
// //         $splits = array();
// //         $sql2 = "SELECT *, a.oder_qty AS Qty, a.balance_qty AS bal_qty, 
// //                         IFNULL(a.batch_plan_id, 0) AS batch_plan_id 
// //                  FROM split_planning_qty a 
// //                  WHERE a.order_no = '".$row["order_no"]."'";
// //         $result2 = $conn->query($sql2);
// //         while ($result2 && $r2 = $result2->fetch_assoc()) {
// //             $splits[] = $r2;
// //         }
// //         $row['splits'] = $splits;

// //         // ---- Decode Raw Materials ----
// //         $row['raw_materials'] = json_decode($row['raw_materials'], true);

// //         // ---- MFR & BFR Records ----
// //         $mfr_records = array();
// //         $sql22 = "SELECT id, mfr_no, batch_size FROM unitformula WHERE product_code = '".$row["product_code"]."'";
// //         $result1234 = $conn->query($sql22);
// //         while ($result1234 && $r1234 = $result1234->fetch_assoc()) {
// //             $bfr_records = array();
// //             $sql3 = "SELECT * 
// //                      FROM batch_formula_info  
// //                      WHERE mfr_no = '".$r1234["mfr_no"]."' AND status = 'Approve'";
// //             $result2 = $conn->query($sql3);
// //             while ($result2 && $r2 = $result2->fetch_assoc()) {
// //                 $bfr_records[] = $r2;
// //             }
// //             $r1234["bfr_records"] = $bfr_records;
// //             $mfr_records[] = $r1234;
// //         }
// //         $row["mfr_records"] = $mfr_records;

// //         // ---- Batch Numbers ----
// //         $batch_nos = array();
// //         $sql223 = "SELECT a.batch_number  
// //                   FROM mfg_work_order_hdr a 
// //                   LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
// //                   WHERE b.client_po_no = '".$row["order_no"]."' 
// //                      AND b.product_code = '".$row["product_code"]."'";
// //         $result55 = $conn->query($sql223);
// //         while ($result55 && $r55 = $result55->fetch_assoc()) {
// //             $batch_nos[] = $r55;
// //         }
// //         $row["batch_nos"] = $batch_nos;

// //         // ---- Plan Dates ----
// //         $planDates = array();
// //         $sql_plan = "SELECT b.approve_date  
// //                      FROM batch_planning b  
// //                      WHERE b.client_po_no = '".$row["order_no"]."' 
// //                       AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_plan);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $planDates[] = $r;
// //         }
// //         $row["planDates"] = $planDates;

// //         // ---- Start Dates ----
// //         $startDates = array();
// //         $sql_start = "SELECT a.approved_date  
// //                       FROM mfg_work_order_hdr a 
// //                       LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
// //                       WHERE b.client_po_no = '".$row["order_no"]."' 
// //                         AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_start);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $startDates[] = $r;
// //         }
// //         $row["startsDates"] = $startDates;

// //         // ---- Total Batches ----
// //         $total_batches = array();
// //         $sql_batches = "SELECT b.total_batches  
// //                         FROM batch_planning b  
// //                         WHERE b.client_po_no = '".$row["order_no"]."' 
// //                           AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_batches);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $total_batches[] = $r;
// //         }
// //         $row["total_batches"] = $total_batches;

// //         // ---- Manufacturing Process Stages ----
// //         $stages = array();
// //         $sql_stages = "SELECT * 
// //                       FROM manufacturing_process a 
// //                       LEFT JOIN manufacturing_process_stages b 
// //                       ON a.id = b.manufacturing_process_id 
// //                       WHERE a.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_stages);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $stages[] = $r;
// //         }
// //         $row["stages"] = $stages;

// //         // ---- Batch Forecasting ----
// //         $batch_details = array();
// //         $sql99 = "SELECT
// //                     a.id AS wordOrderId,
// //                     a.batch_number,
// //                     a.approved_date AS startsDate,
// //                     b.approve_date AS planDate,
// //                     b.total_batches,
// //                     b.entry_date AS Actual_planning_date,
// //                     a.entry_date AS Actual_work_orderDate,
// //                     a.qa_date AS Actual_batchApprovalDate,
// //                     a.rm_disp_completed_date AS Actual_DispensingDate,
// //                     a.rm_qa_dislc_date AS Actual_LineClearanceDate,
// //                     p.work_orderDate,
// //                     p.batchApprovalDate,
// //                     p.LineClearanceDate,
// //                     p.DispensingDate,
// //                     p.productionDate,
// //                     p.PackingDate,
// //                     p.FgTransferDate,
// //                     p.DispatchDate
// //                 FROM mfg_work_order_hdr a
// //                 LEFT JOIN batch_planning b ON a.batch_plan_id = b.id
// //                 LEFT JOIN product p ON b.product_code = p.product_code
// //                 WHERE b.client_po_no = '".$row["order_no"]."' 
// //                   AND b.product_code = '".$row["product_code"]."'";
// //         $result99 = $conn->query($sql99);
// //         while ($result99 && $r99 = $result99->fetch_assoc()) {

// //             // Forecast Dates
// //             $dates = ['work_orderDate', 'batchApprovalDate', 'LineClearanceDate', 'DispensingDate', 'productionDate', 'PackingDate', 'FgTransferDate', 'DispatchDate'];
// //             $currentDate = new DateTime($r99['Actual_planning_date']);
// //             foreach ($dates as $key) {
// //                 if (is_numeric($r99[$key])) {
// //                     $currentDate->modify('+' . intval($r99[$key]) . ' days');
// //                     $r99['Forecast_' . $key] = $currentDate->format('Y-m-d');
// //                 } else {
// //                     $r99['Forecast_' . $key] = null;
// //                 }
// //             }

// //             // Add Stage Data
// //             $r99_stages = array();
// //             $sql1 = "SELECT * FROM mrp_stages WHERE work_order_id = '".$r99["wordOrderId"]."'";
// //             $result1 = $conn->query($sql1);
// //             while ($result1 && $r1 = $result1->fetch_assoc()) {
// //                 $r99_stages[] = $r1;
// //             }
// //             $r99['stages'] = $r99_stages;

// //             $batch_details[] = $r99;
// //         }
// //         $row["batch_details"] = $batch_details;

// //         // ---- Final Push ----
// //         // $i++;
// // // $i++;
// // // error_log("Test Loop $i: " . json_encode($row));
// // $output[] = $row;
// // // error_log("Total output entries: " . count($output));
// // }
// // }
// // // Final Output
// // echo json_encode($output);

      
          
         
// // $output = array();

// // $sql = "SELECT DISTINCT 
// //     a.id, a.id AS pid, a.unit, a.order_no,
// //     b.file, c.TrdNm, b.po_no, b.po_type, b.valid_till, b.po_date,
// //     a.product_code, d.dosage_form AS product_type, b.client_code,
// //     b.required_date AS commencementDate,
// //     a.order_qty, a.pack_size,
// //     latest_unitformula.bom_batch_size, d.product_name, d.grade AS product_grade,
// //     e.raw_materials, e.id AS unit_formula_id,
// //     a.order_qty AS qty_to_prepare
// // FROM order_materials a
// // LEFT JOIN po_entry b ON a.order_no = b.order_no
// // LEFT JOIN client c ON b.client_code = c.client_code
// // LEFT JOIN product d ON a.product_code = d.product_code
// // LEFT JOIN (
// //     SELECT product_code, MAX(id) AS max_id, bom_batch_size
// //     FROM unitformula
// //     GROUP BY product_code
// // ) latest_unitformula ON a.product_code = latest_unitformula.product_code
// // LEFT JOIN unitformula e ON latest_unitformula.max_id = e.id
// // ORDER BY a.id DESC";

// // $result = $conn->query($sql);

// // if ($result->num_rows > 0) {
// //     while ($row = $result->fetch_assoc()) {
 
// //         // ---- Get Stock Quantity ----
// //         $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_stock_book WHERE material_code = '".$row['product_code']."'";
// //         $result0 = $conn->query($sql0);
// //         $row['stock_qty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

// //         // ---- Get Issued Quantity ----
// //         $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_material_issue WHERE material_code = '".$row['product_code']."'";
// //         $result0 = $conn->query($sql0);
// //         $row['issuedQty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

// //         // ---- Calculate Available Stock ----
// //         $row['avbl_stock'] = $row['stock_qty'] - $row['issuedQty'];

// //         // ---- Decode pack_size ----
// //         $row['pack_size'] = json_decode($row['pack_size'], true);

// //         // ---- Packing Configuration ----
// //         $packing_config = array();
// //         $sql1 = "SELECT a.*, b.* 
// //                  FROM unitformula_packing_materials a 
// //                  LEFT JOIN unitformula_pm_dtl b ON a.unit_formula_dtl_id = b.id 
// //                  WHERE b.unit_formula_id = '".$row["unit_formula_id"]."'";
// //         $result1 = $conn->query($sql1);
// //         while ($result1 && $r1 = $result1->fetch_assoc()) {
// //             $packing_config[] = $r1;
// //         }
// //         $row['packing_configuration'] = $packing_config;

// //         // ---- Split Planning Quantities ----
// //         $splits = array();
// //         $sql2 = "SELECT *, a.oder_qty AS Qty, a.balance_qty AS bal_qty, 
// //                         IFNULL(a.batch_plan_id, 0) AS batch_plan_id 
// //                  FROM split_planning_qty a 
// //                  WHERE a.order_no = '".$row["order_no"]."'";
// //         $result2 = $conn->query($sql2);
// //         while ($result2 && $r2 = $result2->fetch_assoc()) {
// //             $splits[] = $r2;
// //         }
// //         $row['splits'] = $splits;

// //         // ---- Decode Raw Materials ----
// //         $row['raw_materials'] = json_decode($row['raw_materials'], true);

// //         // ---- MFR & BFR Records ----
// //         $mfr_records = array();
// //         $sql22 = "SELECT id, mfr_no, batch_size FROM unitformula WHERE product_code = '".$row["product_code"]."'";
// //         $result1234 = $conn->query($sql22);
// //         while ($result1234 && $r1234 = $result1234->fetch_assoc()) {
// //             $bfr_records = array();
// //             $sql3 = "SELECT * 
// //                      FROM batch_formula_info  
// //                      WHERE mfr_no = '".$r1234["mfr_no"]."' AND status = 'Approve'";
// //             $result2 = $conn->query($sql3);
// //             while ($result2 && $r2 = $result2->fetch_assoc()) {
// //                 $bfr_records[] = $r2;
// //             }
// //             $r1234["bfr_records"] = $bfr_records;
// //             $mfr_records[] = $r1234;
// //         }
// //         $row["mfr_records"] = $mfr_records;

// //         // ---- Batch Numbers ----
// //         $batch_nos = array();
// //         $sql223 = "SELECT a.batch_number  
// //                   FROM mfg_work_order_hdr a 
// //                   LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
// //                   WHERE b.client_po_no = '".$row["order_no"]."' 
// //                      AND b.product_code = '".$row["product_code"]."'";
// //         $result55 = $conn->query($sql223);
// //         while ($result55 && $r55 = $result55->fetch_assoc()) {
// //             $batch_nos[] = $r55;
// //         }
// //         $row["batch_nos"] = $batch_nos;

// //         // ---- Plan Dates ----
// //         $planDates = array();
// //         $sql_plan = "SELECT b.approve_date  
// //                      FROM batch_planning b  
// //                      WHERE b.client_po_no = '".$row["order_no"]."' 
// //                       AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_plan);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $planDates[] = $r;
// //         }
// //         $row["planDates"] = $planDates;

// //         // ---- Start Dates ----
// //         $startDates = array();
// //         $sql_start = "SELECT a.approved_date  
// //                       FROM mfg_work_order_hdr a 
// //                       LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
// //                       WHERE b.client_po_no = '".$row["order_no"]."' 
// //                         AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_start);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $startDates[] = $r;
// //         }
// //         $row["startsDates"] = $startDates;

// //         // ---- Total Batches ----
// //         $total_batches = array();
// //         $sql_batches = "SELECT b.total_batches  
// //                         FROM batch_planning b  
// //                         WHERE b.client_po_no = '".$row["order_no"]."' 
// //                           AND b.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_batches);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $total_batches[] = $r;
// //         }
// //         $row["total_batches"] = $total_batches;

// //         // ---- Manufacturing Process Stages ----
// //         $stages = array();
// //         $sql_stages = "SELECT * 
// //                       FROM manufacturing_process a 
// //                       LEFT JOIN manufacturing_process_stages b 
// //                       ON a.id = b.manufacturing_process_id 
// //                       WHERE a.product_code = '".$row["product_code"]."'";
// //         $result = $conn->query($sql_stages);
// //         while ($result && $r = $result->fetch_assoc()) {
// //             $stages[] = $r;
// //         }
// //         $row["stages"] = $stages;

// //         // ---- Batch Forecasting ----
// //         $batch_details = array();
// //         $sql99 = "SELECT
// //                     a.id AS wordOrderId,
// //                     a.batch_number,
// //                     a.approved_date AS startsDate,
// //                     b.approve_date AS planDate,
// //                     b.total_batches,
// //                     b.entry_date AS Actual_planning_date,
// //                     a.entry_date AS Actual_work_orderDate,
// //                     a.qa_date AS Actual_batchApprovalDate,
// //                     a.rm_disp_completed_date AS Actual_DispensingDate,
// //                     a.rm_qa_dislc_date AS Actual_LineClearanceDate,
// //                     p.work_orderDate,
// //                     p.batchApprovalDate,
// //                     p.LineClearanceDate,
// //                     p.DispensingDate,
// //                     p.productionDate,
// //                     p.PackingDate,
// //                     p.FgTransferDate,
// //                     p.DispatchDate
// //                 FROM mfg_work_order_hdr a
// //                 LEFT JOIN batch_planning b ON a.batch_plan_id = b.id
// //                 LEFT JOIN product p ON b.product_code = p.product_code
// //                 WHERE b.client_po_no = '".$row["order_no"]."' 
// //                   AND b.product_code = '".$row["product_code"]."'";
// //         $result99 = $conn->query($sql99);
// //         while ($result99 && $r99 = $result99->fetch_assoc()) {

// //             // Forecast Dates
// //             $dates = ['work_orderDate', 'batchApprovalDate', 'LineClearanceDate', 'DispensingDate', 'productionDate', 'PackingDate', 'FgTransferDate', 'DispatchDate'];
// //             $currentDate = new DateTime($r99['Actual_planning_date']);
// //             foreach ($dates as $key) {
// //                 if (is_numeric($r99[$key])) {
// //                     $currentDate->modify('+' . intval($r99[$key]) . ' days');
// //                     $r99['Forecast_' . $key] = $currentDate->format('Y-m-d');
// //                 } else {
// //                     $r99['Forecast_' . $key] = null;
// //                 }
// //             }

// //             // Add Stage Data
// //             $r99_stages = array();
// //             $sql1 = "SELECT * FROM mrp_stages WHERE work_order_id = '".$r99["wordOrderId"]."'";
// //             $result1 = $conn->query($sql1);
// //             while ($result1 && $r1 = $result1->fetch_assoc()) {
// //                 $r99_stages[] = $r1;
// //             }
// //             $r99['stages'] = $r99_stages;

// //             $batch_details[] = $r99;
// //         }
// //         $row["batch_details"] = $batch_details;

// //         // ---- Final Push ----
// //         // $i++;
// // // $i++;
// // // error_log("Test Loop $i: " . json_encode($row));
// // $output[] = $row;
// // // error_log("Total output entries: " . count($output));
// // }
// // }
// // // Final Output
// // echo json_encode($output);

      
          
         
// $output = array();

// $sql = "SELECT DISTINCT 
//     a.id, a.id AS pid, a.unit, a.order_no,
//     b.file, c.TrdNm, b.po_no, b.po_type, b.valid_till, b.po_date,
//     a.product_code, d.dosage_form AS product_type, b.client_code,
//     b.required_date AS commencementDate,
//     a.order_qty, a.pack_size,
//     latest_unitformula.bom_batch_size, d.product_name, d.grade AS product_grade,
//     e.raw_materials, e.id AS unit_formula_id,
//     a.order_qty AS qty_to_prepare
// FROM order_materials a
// LEFT JOIN po_entry b ON a.order_no = b.order_no
// LEFT JOIN client c ON b.client_code = c.client_code
// LEFT JOIN product d ON a.product_code = d.product_code
// LEFT JOIN (
//     SELECT product_code, MAX(id) AS max_id, bom_batch_size
//     FROM unitformula
//     GROUP BY product_code
// ) latest_unitformula ON a.product_code = latest_unitformula.product_code
// LEFT JOIN unitformula e ON latest_unitformula.max_id = e.id
// ORDER BY a.id DESC";

// $result = $conn->query($sql);

// if ($result->num_rows > 0) {
//     while ($row = $result->fetch_assoc()) {
 
//         // ---- Get Stock Quantity ----
//         $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_stock_book WHERE material_code = '".$row['product_code']."'";
//         $result0 = $conn->query($sql0);
//         $row['stock_qty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

//         // ---- Get Issued Quantity ----
//         $sql0 = "SELECT SUM(qty) AS avblStock FROM fg_material_issue WHERE material_code = '".$row['product_code']."'";
//         $result0 = $conn->query($sql0);
//         $row['issuedQty'] = ($result0->num_rows > 0) ? $result0->fetch_assoc()['avblStock'] : 0;

//         // ---- Calculate Available Stock ----
//         $row['avbl_stock'] = $row['stock_qty'] - $row['issuedQty'];

//         // ---- Decode pack_size ----
//         $row['pack_size'] = json_decode($row['pack_size'], true);

//         // ---- Packing Configuration ----
//         $packing_config = array();
//         $sql1 = "SELECT a.*, b.* 
//                  FROM unitformula_packing_materials a 
//                  LEFT JOIN unitformula_pm_dtl b ON a.unit_formula_dtl_id = b.id 
//                  WHERE b.unit_formula_id = '".$row["unit_formula_id"]."'";
//         $result1 = $conn->query($sql1);
//         while ($result1 && $r1 = $result1->fetch_assoc()) {
//             $packing_config[] = $r1;
//         }
//         $row['packing_configuration'] = $packing_config;

//         // ---- Split Planning Quantities ----
//         $splits = array();
//         $sql2 = "SELECT *, a.oder_qty AS Qty, a.balance_qty AS bal_qty, 
//                         IFNULL(a.batch_plan_id, 0) AS batch_plan_id 
//                  FROM split_planning_qty a 
//                  WHERE a.order_no = '".$row["order_no"]."'";
//         $result2 = $conn->query($sql2);
//         while ($result2 && $r2 = $result2->fetch_assoc()) {
//             $splits[] = $r2;
//         }
//         $row['splits'] = $splits;

//         // ---- Decode Raw Materials ----
//         $row['raw_materials'] = json_decode($row['raw_materials'], true);

//         // ---- MFR & BFR Records ----
//         $mfr_records = array();
//         $sql22 = "SELECT id, mfr_no, batch_size FROM unitformula WHERE product_code = '".$row["product_code"]."'";
//         $result1234 = $conn->query($sql22);
//         while ($result1234 && $r1234 = $result1234->fetch_assoc()) {
//             $bfr_records = array();
//             $sql3 = "SELECT * 
//                      FROM batch_formula_info  
//                      WHERE mfr_no = '".$r1234["mfr_no"]."' AND status = 'Approve'";
//             $result2 = $conn->query($sql3);
//             while ($result2 && $r2 = $result2->fetch_assoc()) {
//                 $bfr_records[] = $r2;
//             }
//             $r1234["bfr_records"] = $bfr_records;
//             $mfr_records[] = $r1234;
//         }
//         $row["mfr_records"] = $mfr_records;

//         // ---- Batch Numbers ----
//         $batch_nos = array();
//         $sql223 = "SELECT a.batch_number  
//                   FROM mfg_work_order_hdr a 
//                   LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
//                   WHERE b.client_po_no = '".$row["order_no"]."' 
//                      AND b.product_code = '".$row["product_code"]."'";
//         $result55 = $conn->query($sql223);
//         while ($result55 && $r55 = $result55->fetch_assoc()) {
//             $batch_nos[] = $r55;
//         }
//         $row["batch_nos"] = $batch_nos;

//         // ---- Plan Dates ----
//         $planDates = array();
//         $sql_plan = "SELECT b.approve_date  
//                      FROM batch_planning b  
//                      WHERE b.client_po_no = '".$row["order_no"]."' 
//                       AND b.product_code = '".$row["product_code"]."'";
//         $result = $conn->query($sql_plan);
//         while ($result && $r = $result->fetch_assoc()) {
//             $planDates[] = $r;
//         }
//         $row["planDates"] = $planDates;

//         // ---- Start Dates ----
//         $startDates = array();
//         $sql_start = "SELECT a.approved_date  
//                       FROM mfg_work_order_hdr a 
//                       LEFT JOIN batch_planning b ON a.batch_plan_id = b.id 
//                       WHERE b.client_po_no = '".$row["order_no"]."' 
//                         AND b.product_code = '".$row["product_code"]."'";
//         $result = $conn->query($sql_start);
//         while ($result && $r = $result->fetch_assoc()) {
//             $startDates[] = $r;
//         }
//         $row["startsDates"] = $startDates;

//         // ---- Total Batches ----
//         $total_batches = array();
//         $sql_batches = "SELECT b.total_batches  
//                         FROM batch_planning b  
//                         WHERE b.client_po_no = '".$row["order_no"]."' 
//                           AND b.product_code = '".$row["product_code"]."'";
//         $result = $conn->query($sql_batches);
//         while ($result && $r = $result->fetch_assoc()) {
//             $total_batches[] = $r;
//         }
//         $row["total_batches"] = $total_batches;

//         // ---- Manufacturing Process Stages ----
//         $stages = array();
//         $sql_stages = "SELECT * 
//                       FROM manufacturing_process a 
//                       LEFT JOIN manufacturing_process_stages b 
//                       ON a.id = b.manufacturing_process_id 
//                       WHERE a.product_code = '".$row["product_code"]."'";
//         $result = $conn->query($sql_stages);
//         while ($result && $r = $result->fetch_assoc()) {
//             $stages[] = $r;
//         }
//         $row["stages"] = $stages;

//         // ---- Batch Forecasting ----
//         $batch_details = array();
//         $sql99 = "SELECT
//                     a.id AS wordOrderId,
//                     a.batch_number,
//                     a.approved_date AS startsDate,
//                     b.approve_date AS planDate,
//                     b.total_batches,
//                     b.entry_date AS Actual_planning_date,
//                     a.entry_date AS Actual_work_orderDate,
//                     a.qa_date AS Actual_batchApprovalDate,
//                     a.rm_disp_completed_date AS Actual_DispensingDate,
//                     a.rm_qa_dislc_date AS Actual_LineClearanceDate,
//                     p.work_orderDate,
//                     p.batchApprovalDate,
//                     p.LineClearanceDate,
//                     p.DispensingDate,
//                     p.productionDate,
//                     p.PackingDate,
//                     p.FgTransferDate,
//                     p.DispatchDate
//                 FROM mfg_work_order_hdr a
//                 LEFT JOIN batch_planning b ON a.batch_plan_id = b.id
//                 LEFT JOIN product p ON b.product_code = p.product_code
//                 WHERE b.client_po_no = '".$row["order_no"]."' 
//                   AND b.product_code = '".$row["product_code"]."'";
//         $result99 = $conn->query($sql99);
//         while ($result99 && $r99 = $result99->fetch_assoc()) {

//             // Forecast Dates
//             $dates = ['work_orderDate', 'batchApprovalDate', 'LineClearanceDate', 'DispensingDate', 'productionDate', 'PackingDate', 'FgTransferDate', 'DispatchDate'];
//             $currentDate = new DateTime($r99['Actual_planning_date']);
//             foreach ($dates as $key) {
//                 if (is_numeric($r99[$key])) {
//                     $currentDate->modify('+' . intval($r99[$key]) . ' days');
//                     $r99['Forecast_' . $key] = $currentDate->format('Y-m-d');
//                 } else {
//                     $r99['Forecast_' . $key] = null;
//                 }
//             }

//             // Add Stage Data
//             $r99_stages = array();
//             $sql1 = "SELECT * FROM mrp_stages WHERE work_order_id = '".$r99["wordOrderId"]."'";
//             $result1 = $conn->query($sql1);
//             while ($result1 && $r1 = $result1->fetch_assoc()) {
//                 $r99_stages[] = $r1;
//             }
//             $r99['stages'] = $r99_stages;

//             $batch_details[] = $r99;
//         }
//         $row["batch_details"] = $batch_details;

//         // ---- Final Push ----
//         // $i++;
// // $i++;
// // error_log("Test Loop $i: " . json_encode($row));
// $output[] = $row;
// // error_log("Total output entries: " . count($output));
// }
// }
// // Final Output
// echo json_encode($output);

//       }
      else if ($_GET["type"] == "save_planStages") {
         
        
         $sql = "INSERT INTO mrp_stages 
( plant_id, work_order_id, stage, Equipment, equipment_code, performed_by, result, entry_by, entry_date) 
VALUES (
 
    '".$_GET['plant_id']."',
    '".$input['wordOrderId']."',
    '".$input['stages']."',
    '".$input['Equipment']."',
    '".$input['equipment_code']."',
    '".$input['performed_by']."',
    '".$input['result']."',
    '".$_GET['emp_id']."',
    '$entry_date'
)";
       
           if ($conn->query($sql)) {
                 echo "{\"status\":\"success\"}";
           } else{
                 echo "{\"status\":\"".$conn->error."\"}";
           }
          
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
                product_name, grade, bfr_no, mfr_no,  pack_unit, batch_size,total_batches, dispatch_qty, planned_qty,
                qty_can_planned, no_of_batches_can_planned, status, entry_by, entry_date,client_po_qty,client_po_unit,plan_based_on,
                country_specific,pack_multiple_countries,planned_for_month,planned_for_year,multi_packing,pack_size)
                values('".$_GET["plant_id"]."' ,'".$plan_no."' ,'".$input["plan_type"]."' ,'".$input["plan_type"]."',
                '".$input["plan_for_market"]."' ,'".$input["plan_client_name"]."' ,'".$input["client_code"]."' ,'".$input["order_no"]."',
                '".$input["po_date"]."' ,'".$input["product_type"]."' ,'".$input["product_code"]."' ,'".$input["product_name"]."',
                '".$input["grade"]."' ,'".$input["bfr_no"]."' ,'".$input["mfr_no"]."'   ,'".$input["pack_unit"]."',
                '".$input["batch_size"]."','".$input["number_of_batches"]."' ,'".$input["dispatch_qty"]."' ,'".$input["planned_qty"]."' ,'".$input["can_plan_qty"]."',
                '".$input["lowest_batch"]."' ,'Pending' ,'".$_GET["emp_id"]."', '$entry_date',
                '".$input["client_po_qty"]."', '".$input["client_po_unit"]."', '".$input["plan_based_on"]."',
                '".$input["country_specific"]."', '".$input["pack_multiple_countries"]."', '".$input["month"]."', '".$input["year"]."', '".$input["multi_packing"]."', '".$input["packSize"]."')";
       
           if ($conn->query($sql)) {
            $batch_plan_id = $conn->insert_id;
	        $materials = json_decode(json_encode($input["primaryPacking_materials"]),true);
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql2="Insert into batch_planning_materials(plant_id,batch_plan_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,plan_qty,bfr_no,grade,
                   batches_can_plan,shortage_qty,stage) values('".$_GET["plant_id"]."' ,'".$batch_plan_id."',
                   'Primary Packing Materials','".$material["material_code"]."','".$material["qty"]."','".$material["unit"]."',
                   '".$material["overages"]."','".$material["total_qty"]."','".$material["batch_qtyqqqq"]."','".$material["plan"]."', '".$material["bfr_no"]."',
                   '".$material["grade"]."','".$material["can_plan_batches"]."','".$material["short_qt"]."','".$material["stage"]."')";
                   
                $conn->query($sql2);
                
            }
	        $materials = json_decode(json_encode($input["ConsumeableMaterial"]),true);
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql2="Insert into batch_planning_materials(plant_id,batch_plan_id,material_type,material_code,qty,unit,overages,total_qty,batch_qty,plan_qty,bfr_no,grade,
                   batches_can_plan,shortage_qty,stage) values('".$_GET["plant_id"]."' ,'".$batch_plan_id."',
                   'Consumeable Material','".$material["material_code"]."','".$material["qty"]."','".$material["unit"]."',
                   '".$material["overages"]."','".$material["total_qty"]."','".$material["batch_qtyqqqq"]."','".$material["plan"]."', '".$material["bfr_no"]."',
                   '".$material["grade"]."','".$material["can_plan_batches"]."','".$material["short_qt"]."','".$material["stage"]."')";
                   
                $conn->query($sql2);
                
            }
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
                               '".$item["pm_overages"]."','".$item["total_qty"]."','".$item["batch_qtyqqqq"]."','".$item["plan"]."',
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
           
           
           
            $materials = json_decode(json_encode($input["selectedorders"]),true);
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql2=" update split_planning_qty set batch_plan_id= '".$batch_plan_id."',actual_planning_date='".$entry_date."',NoofBatchesPlan='".$input["number_of_batches"]."' where id='".$material["id"]."'";
                $conn->query($sql2);}
	        echo "{\"status\":\"success\"}";
           } else{
                 echo "{\"status\":\"".$conn->error."\"}";
           }
          
      }
          else if ($_GET["type"] == "get_raw_materials_by_bfr_no_for_marketing") {
            $output=Array();
 
              $sql="SELECT   distinct a.id,
    a.*,  
    c.material_name 
FROM 
    batch_materials a 
LEFT JOIN 
    vw_stock_summary b ON a.material_code = b.material_code AND a.plant_id = b.plant_id 
LEFT JOIN 
    material c ON a.material_code = c.material_code AND c.plant_id = a.plant_id AND a.material_type = c.material_type 
WHERE 
     
    a.bfr_no = '".$_GET["bfr_no"]."' 
    AND a.material_type = 'Raw Material' 
    AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.qty desc
     ";       
              $result = $conn->query($sql);
     
        
            $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                
                        $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."'";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["qty1"] = $row1["qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as dist_qty FROM stock_book WHERE material_code='".$row["material_code"]."' and   (status ='Distroy' or status ='Return')";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["dist_qty"] = $row1["dist_qty"];
                    }
                }
                
                $sql1 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$row["material_code"]."' ";
            
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["issue_qty1"] = $row1["issue_qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                 (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."')";
          
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["m_qty"] = $row1["m_qty"];
                    }
                }
                  $row["EOU_STOCK"] = $row["qty1"] - $row["issue_qty1"]- $row["m_qty"] - $row["dist_qty"];
                  if($row["EOU_STOCK"] < 0 ){
                      $row["avbl_stock"]=0;
                  }else{
                      $row["avbl_stock"]=$row["EOU_STOCK"];
                  }
                  $row["Issue"] = $row["issue_qty1"]+ $row["m_qty"];
                
                
                
                
                
                
                $output[] = $row;
            }
        }
        
        //////////////Raw Material end here
        //////////////Raw Material end here
        //////////////Raw Material end here
        //////////////Raw Material end here
        //////////////Raw Material end here
           $output77=Array();
 
              $sql77="SELECT   
    a.*, 
   
    
    c.material_name 
FROM 
    batch_materials a 
LEFT JOIN 
    vw_stock_summary b ON a.material_code = b.material_code AND a.plant_id = b.plant_id 
LEFT JOIN 
    material c ON a.material_code = c.material_code AND c.plant_id = a.plant_id AND a.material_type = c.material_type 
WHERE 
     
    a.bfr_no = '".$_GET["bfr_no"]."' 
    AND a.material_type = 'Packing Material'  and a.pm_type = 'Primary Packing'
    AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.qty desc
     ";       
              $result77 = $conn->query($sql77);
     
        
            $result77 = $conn->query($sql77);
        if($result77->num_rows > 0){
            while($row = $result77->fetch_assoc()){
                
                        $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."'";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["qty1"] = $row1["qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as dist_qty FROM stock_book WHERE material_code='".$row["material_code"]."' and   (status ='Distroy' or status ='Return')";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["dist_qty"] = $row1["dist_qty"];
                    }
                }
                
                $sql1 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$row["material_code"]."' ";
            
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["issue_qty1"] = $row1["issue_qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                 (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."')";
          
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["m_qty"] = $row1["m_qty"];
                    }
                }
                  $row["EOU_STOCK"] = $row["qty1"] - $row["issue_qty1"]- $row["m_qty"] - $row["dist_qty"];
                  if($row["EOU_STOCK"] < 0 ){
                      $row["avbl_stock"]=0;
                  }else{
                      $row["avbl_stock"]=$row["EOU_STOCK"];
                  }
                  $row["Issue"] = $row["issue_qty1"]+ $row["m_qty"];
                
                
                
                
                
                
                $output77[] = $row;
            }
        }
        
        
        /////////////Primary packing end here
        /////////////Primary packing end here
        /////////////Primary packing end here
           $output78=Array();
 
              $sql78="SELECT   
    a.*, 
    
    c.material_name 
FROM 
    batch_materials a 
LEFT JOIN 
    vw_stock_summary b ON a.material_code = b.material_code AND a.plant_id = b.plant_id 
LEFT JOIN 
    others_material c ON a.material_code = c.material_code AND c.plant_id = a.plant_id  
WHERE 
     
    a.bfr_no = '".$_GET["bfr_no"]."' 
    AND a.material_type = 'Consumeable Material'  and a.pm_type = 'Consumeable Material'
    AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.qty desc
     ";       
            
     
        
            $result78 = $conn->query($sql78);
        if($result78->num_rows > 0){
            while($row = $result78->fetch_assoc()){
                
                        $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."'";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["qty1"] = $row1["qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as dist_qty FROM stock_book WHERE material_code='".$row["material_code"]."' and   (status ='Distroy' or status ='Return')";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["dist_qty"] = $row1["dist_qty"];
                    }
                }
                
                $sql1 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$row["material_code"]."' ";
            
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["issue_qty1"] = $row1["issue_qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                 (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."')";
          
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["m_qty"] = $row1["m_qty"];
                    }
                }
                  $row["EOU_STOCK"] = $row["qty1"] - $row["issue_qty1"]- $row["m_qty"] - $row["dist_qty"];
                  if($row["EOU_STOCK"] < 0 ){
                      $row["avbl_stock"]=0;
                  }else{
                      $row["avbl_stock"]=$row["EOU_STOCK"];
                  }
                  $row["Issue"] = $row["issue_qty1"]+ $row["m_qty"];
                
                
                
                
                
                
                $output78[] = $row;
            }
        }
        
        
        /////////////Primary packing end here
        /////////////Primary packing end here
        /////////////Primary packing end here
      
       
       
       $output3['raw_materials'] =$output;
       $output3['primaryPacking_materials'] =$output77;
       $output3['ConsumeableMaterial'] =$output78;
       echo json_encode($output3);
      }
          else if ($_GET["type"] == "get_raw_materials_by_bfr_no") {
            $output=Array();
 
              $sql="SELECT   distinct a.id,
    a.*, 
   
   
    c.material_name 
FROM 
    batch_materials a 
LEFT JOIN 
    vw_stock_summary b ON a.material_code = b.material_code AND a.plant_id = b.plant_id 
LEFT JOIN 
    material c ON a.material_code = c.material_code AND c.plant_id = a.plant_id AND a.material_type = c.material_type 
WHERE 
     
    a.bfr_no = '".$_GET["bfr_no"]."' 
    AND a.material_type = 'Raw Material' 
    AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.qty desc
     ";       
              $result = $conn->query($sql);
     
        
            $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                
                        $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."'";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["qty1"] = $row1["qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as dist_qty FROM stock_book WHERE material_code='".$row["material_code"]."' and   (status ='Distroy' or status ='Return')";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["dist_qty"] = $row1["dist_qty"];
                    }
                }
                
                $sql1 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$row["material_code"]."' ";
            
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["issue_qty1"] = $row1["issue_qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                 (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."')";
          
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["m_qty"] = $row1["m_qty"];
                    }
                }
                  $row["EOU_STOCK"] = $row["qty1"] - $row["issue_qty1"]- $row["m_qty"] - $row["dist_qty"];
                  if($row["EOU_STOCK"] < 0 ){
                      $row["avbl_stock"]=0;
                  }else{
                      $row["avbl_stock"]=$row["EOU_STOCK"];
                  }
                  $row["Issue"] = $row["issue_qty1"]+ $row["m_qty"];
                
                
                
                
                
                
                $output[] = $row;
            }
        }
        
        //////////////Raw Material end here
        //////////////Raw Material end here
        //////////////Raw Material end here
        //////////////Raw Material end here
        //////////////Raw Material end here
           $output77=Array();
 
              $sql77="SELECT   
    a.*, 
   
   
    c.material_name 
FROM 
    batch_materials a 
LEFT JOIN 
    vw_stock_summary b ON a.material_code = b.material_code AND a.plant_id = b.plant_id 
LEFT JOIN 
    material c ON a.material_code = c.material_code AND c.plant_id = a.plant_id AND a.material_type = c.material_type 
WHERE 
     
    a.bfr_no = '".$_GET["bfr_no"]."' 
    AND a.material_type = 'Packing Material'  and a.pm_type = 'Primary Packing'
    AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.qty desc
     ";       
              $result77 = $conn->query($sql77);
     
        
            $result77 = $conn->query($sql77);
        if($result77->num_rows > 0){
            while($row = $result77->fetch_assoc()){
                
                        $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."'";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["qty1"] = $row1["qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as dist_qty FROM stock_book WHERE material_code='".$row["material_code"]."' and   (status ='Distroy' or status ='Return')";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["dist_qty"] = $row1["dist_qty"];
                    }
                }
                
                $sql1 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$row["material_code"]."' ";
            
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["issue_qty1"] = $row1["issue_qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                 (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."')";
          
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["m_qty"] = $row1["m_qty"];
                    }
                }
                  $row["EOU_STOCK"] = $row["qty1"] - $row["issue_qty1"]- $row["m_qty"] - $row["dist_qty"];
                  if($row["EOU_STOCK"] < 0 ){
                      $row["avbl_stock"]=0;
                  }else{
                      $row["avbl_stock"]=$row["EOU_STOCK"];
                  }
                  $row["Issue"] = $row["issue_qty1"]+ $row["m_qty"];
                
                
                
                
                
                
                $output77[] = $row;
            }
        }
        
        
        /////////////Primary packing end here
        /////////////Primary packing end here
        /////////////Primary packing end here
           $output78=Array();
 
              $sql78="SELECT   
    a.*, 
   
   
    c.material_name 
FROM 
    batch_materials a 
LEFT JOIN 
    vw_stock_summary b ON a.material_code = b.material_code AND a.plant_id = b.plant_id 
LEFT JOIN 
    others_material c ON a.material_code = c.material_code AND c.plant_id = a.plant_id  
WHERE 
     
    a.bfr_no = '".$_GET["bfr_no"]."' 
    AND a.material_type = 'Consumeable Material'  and a.pm_type = 'Consumeable Material'
    AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.qty desc
     ";       
            
     
        
            $result78 = $conn->query($sql78);
        if($result78->num_rows > 0){
            while($row = $result78->fetch_assoc()){
                
                        $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."'";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["qty1"] = $row1["qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as dist_qty FROM stock_book WHERE material_code='".$row["material_code"]."' and   (status ='Distroy' or status ='Return')";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["dist_qty"] = $row1["dist_qty"];
                    }
                }
                
                $sql1 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$row["material_code"]."' ";
            
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["issue_qty1"] = $row1["issue_qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                 (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."')";
          
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["m_qty"] = $row1["m_qty"];
                    }
                }
                  $row["EOU_STOCK"] = $row["qty1"] - $row["issue_qty1"]- $row["m_qty"] - $row["dist_qty"];
                  if($row["EOU_STOCK"] < 0 ){
                      $row["avbl_stock"]=0;
                  }else{
                      $row["avbl_stock"]=$row["EOU_STOCK"];
                  }
                  $row["Issue"] = $row["issue_qty1"]+ $row["m_qty"];
                
                
                
                
                
                
                $output78[] = $row;
            }
        }
        
        
        /////////////Primary packing end here
        /////////////Primary packing end here
        /////////////Primary packing end here
      
       
       
       $output3['raw_materials'] =$output;
       $output3['primaryPacking_materials'] =$output77;
       $output3['ConsumeableMaterial'] =$output78;
       echo json_encode($output3);
      }
//           else if ($_GET["type"] == "get_raw_materials_by_bfr_no") {
//             $output=Array();
 
//               $sql="SELECT   
//     a.*, 
   
//     COALESCE((SELECT SUM(bb.plan_qty) 
//               FROM batch_planning_materials bb 
//               WHERE a.material_code = bb.material_code 
//               AND dispensing_status = 'pending'), 0) AS booking_stock,
//     (SELECT COUNT(*) AS count_of_batch_plan_id
//      FROM (
//          SELECT bb.batch_plan_id 
//          FROM batch_planning_materials bb    WHERE  bb.material_code=a.material_code
      
//          AND dispensing_status = 'pending'  
//          GROUP BY bb.batch_plan_id
//      ) AS subquery_alias) AS batches_booked,
//     c.material_name 
// FROM 
//     batch_materials a 
// LEFT JOIN 
//     vw_stock_summary b ON a.material_code = b.material_code AND a.plant_id = b.plant_id 
// LEFT JOIN 
//     material c ON a.material_code = c.material_code AND c.plant_id = a.plant_id AND a.material_type = c.material_type 
// WHERE 
     
//     a.bfr_no = '".$_GET["bfr_no"]."' 
//     AND a.material_type = 'Raw Material' 
//     AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.qty desc
//      ";       
//               $result = $conn->query($sql);
     
        
//             $result = $conn->query($sql);
//         if($result->num_rows > 0){
//             while($row = $result->fetch_assoc()){
                
//                         $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."'";
//                 //AND challan_for='EOU'";
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
//                          $row["qty1"] = $row1["qty"];
//                     }
//                 }
//                 $sql1 = "SELECT IFNULL(SUM(qty), 0) as dist_qty FROM stock_book WHERE material_code='".$row["material_code"]."' and   (status ='Distroy' or status ='Return')";
//                 //AND challan_for='EOU'";
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
//                          $row["dist_qty"] = $row1["dist_qty"];
//                     }
//                 }
                
//                 $sql1 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$row["material_code"]."' ";
            
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
//                         $row["issue_qty1"] = $row1["issue_qty"];
//                     }
//                 }
//                 $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
//                  (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."')";
          
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
//                         $row["m_qty"] = $row1["m_qty"];
//                     }
//                 }
//                   $row["EOU_STOCK"] = $row["qty1"] - $row["issue_qty1"]- $row["m_qty"] - $row["dist_qty"];
//                   if($row["EOU_STOCK"] < 0 ){
//                       $row["avbl_stock"]=0;
//                   }else{
//                       $row["avbl_stock"]=$row["EOU_STOCK"];
//                   }
//                   $row["Issue"] = $row["issue_qty1"]+ $row["m_qty"];
                
                
                
                
                
                
//                 $output[] = $row;
//             }
//         }
        
//         //////////////Raw Material end here
//         //////////////Raw Material end here
//         //////////////Raw Material end here
//         //////////////Raw Material end here
//         //////////////Raw Material end here
//           $output77=Array();
 
//               $sql77="SELECT   
//     a.*, 
   
//     COALESCE((SELECT SUM(bb.plan_qty) 
//               FROM batch_planning_materials bb 
//               WHERE a.material_code = bb.material_code 
//               AND dispensing_status = 'pending'), 0) AS booking_stock,
//     (SELECT COUNT(*) AS count_of_batch_plan_id
//      FROM (
//          SELECT bb.batch_plan_id 
//          FROM batch_planning_materials bb    WHERE  bb.material_code=a.material_code
      
//          AND dispensing_status = 'pending'  
//          GROUP BY bb.batch_plan_id
//      ) AS subquery_alias) AS batches_booked,
//     c.material_name 
// FROM 
//     batch_materials a 
// LEFT JOIN 
//     vw_stock_summary b ON a.material_code = b.material_code AND a.plant_id = b.plant_id 
// LEFT JOIN 
//     material c ON a.material_code = c.material_code AND c.plant_id = a.plant_id AND a.material_type = c.material_type 
// WHERE 
     
//     a.bfr_no = '".$_GET["bfr_no"]."' 
//     AND a.material_type = 'Packing Material'  and a.pm_type = 'Primary Packing'
//     AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.qty desc
//      ";       
//               $result77 = $conn->query($sql77);
     
        
//             $result77 = $conn->query($sql77);
//         if($result77->num_rows > 0){
//             while($row = $result77->fetch_assoc()){
                
//                         $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."'";
//                 //AND challan_for='EOU'";
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
//                          $row["qty1"] = $row1["qty"];
//                     }
//                 }
//                 $sql1 = "SELECT IFNULL(SUM(qty), 0) as dist_qty FROM stock_book WHERE material_code='".$row["material_code"]."' and   (status ='Distroy' or status ='Return')";
//                 //AND challan_for='EOU'";
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
//                          $row["dist_qty"] = $row1["dist_qty"];
//                     }
//                 }
                
//                 $sql1 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$row["material_code"]."' ";
            
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
//                         $row["issue_qty1"] = $row1["issue_qty"];
//                     }
//                 }
//                 $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
//                  (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."')";
          
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
//                         $row["m_qty"] = $row1["m_qty"];
//                     }
//                 }
//                   $row["EOU_STOCK"] = $row["qty1"] - $row["issue_qty1"]- $row["m_qty"] - $row["dist_qty"];
//                   if($row["EOU_STOCK"] < 0 ){
//                       $row["avbl_stock"]=0;
//                   }else{
//                       $row["avbl_stock"]=$row["EOU_STOCK"];
//                   }
//                   $row["Issue"] = $row["issue_qty1"]+ $row["m_qty"];
                
                
                
                
                
                
//                 $output77[] = $row;
//             }
//         }
        
        
//         /////////////Primary packing end here
//         /////////////Primary packing end here
//         /////////////Primary packing end here
//           $output78=Array();
 
//               $sql78="SELECT   
//     a.*, 
   
//     COALESCE((SELECT SUM(bb.plan_qty) 
//               FROM batch_planning_materials bb 
//               WHERE a.material_code = bb.material_code 
//               AND dispensing_status = 'pending'), 0) AS booking_stock,
//     (SELECT COUNT(*) AS count_of_batch_plan_id
//      FROM (
//          SELECT bb.batch_plan_id 
//          FROM batch_planning_materials bb    WHERE  bb.material_code=a.material_code
      
//          AND dispensing_status = 'pending'  
//          GROUP BY bb.batch_plan_id
//      ) AS subquery_alias) AS batches_booked,
//     c.material_name 
// FROM 
//     batch_materials a 
// LEFT JOIN 
//     vw_stock_summary b ON a.material_code = b.material_code AND a.plant_id = b.plant_id 
// LEFT JOIN 
//     others_material c ON a.material_code = c.material_code AND c.plant_id = a.plant_id  
// WHERE 
     
//     a.bfr_no = '".$_GET["bfr_no"]."' 
//     AND a.material_type = 'Consumeable Material'  and a.pm_type = 'Consumeable Material'
//     AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.qty desc
//      ";       
            
     
        
//             $result78 = $conn->query($sql78);
//         if($result78->num_rows > 0){
//             while($row = $result78->fetch_assoc()){
                
//                         $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."'";
//                 //AND challan_for='EOU'";
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
//                          $row["qty1"] = $row1["qty"];
//                     }
//                 }
//                 $sql1 = "SELECT IFNULL(SUM(qty), 0) as dist_qty FROM stock_book WHERE material_code='".$row["material_code"]."' and   (status ='Distroy' or status ='Return')";
//                 //AND challan_for='EOU'";
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
//                          $row["dist_qty"] = $row1["dist_qty"];
//                     }
//                 }
                
//                 $sql1 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$row["material_code"]."' ";
            
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
//                         $row["issue_qty1"] = $row1["issue_qty"];
//                     }
//                 }
//                 $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
//                  (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."')";
          
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
//                         $row["m_qty"] = $row1["m_qty"];
//                     }
//                 }
//                   $row["EOU_STOCK"] = $row["qty1"] - $row["issue_qty1"]- $row["m_qty"] - $row["dist_qty"];
//                   if($row["EOU_STOCK"] < 0 ){
//                       $row["avbl_stock"]=0;
//                   }else{
//                       $row["avbl_stock"]=$row["EOU_STOCK"];
//                   }
//                   $row["Issue"] = $row["issue_qty1"]+ $row["m_qty"];
                
                
                
                
                
                
//                 $output78[] = $row;
//             }
//         }
        
        
//         /////////////Primary packing end here
//         /////////////Primary packing end here
//         /////////////Primary packing end here
      
       
       
//       $output3['raw_materials'] =$output;
//       $output3['primaryPacking_materials'] =$output77;
//       $output3['ConsumeableMaterial'] =$output78;
//       echo json_encode($output3);
//       }
  
     
     else if ($_GET["type"] == "get_packing_materials_by_mfr_id") {
         
        $output1 = Array();
       $sql= "select * from unitformula_pm_dtl where unit_formula_id = '".$_GET["unit_formula_id"]."' and market_type ='".$_GET["market_type"]."' order by country_name ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                    $output2 = Array();
                    $sql1 = "SELECT id,pack_size,batch_size,unit,packing_type FROM unitformula_pm_dtl WHERE unit_formula_id = '".$_GET["unit_formula_id"]."' order by pack_size";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output3 = Array();
                             $sql3 = "select a.*,u.pack_size,bm.batch_qty,IFNULL(b.avbl_stock,0) as avbl_stock from (SELECT * FROM unitformula_packing_materials WHERE unit_formula_dtl_id='".$row1["id"]."')a
                                    left join (
                                    
                                    SELECT material_code,balance_qty as avbl_stock FROM vw_stock_summary 
                                    
                                    )b on a.material_code =b.material_code LEFT JOIN unitformula_pm_dtl u on a.unit_formula_dtl_id=u.id left JOIN unitformula u1 on u.unit_formula_id=u1.id LEFT JOIN batch_planning bp on u1.mfr_no=bp.mfr_no LEFT JOIN batch_materials bm on bp.bfr_no=bm.bfr_no
                            ";
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
     
     
      else if ($_GET["type"] == "UpdatesARTdATE") {
        
        $sql = "Update batch_planning set start_date = '".$input["startDate"]."' where  id='".$input["id"]."'";
         if ($conn->query($sql)) {
           
            echo "{\"status\":\"success\"}";
        }  else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
     }
      else if ($_GET["type"] == "update_batch_formula_status") {
           $packing_materials = json_decode(json_encode($input),true);
        $sql = "Update batch_formula_info set approve_by = '".$_GET["emp_id"]."',
                status = '".$packing_materials["status"]."',
                pm_pack_unit  = '".$packing_materials["pm_pack_unit"]."',
                pm_pack_size  = '".$packing_materials["pm_pack_size"]."',
                packing_materials = '".json_encode($packing_materials["packing_materials"])."',
                approve_date = '".$entry_date."' where  id='".$packing_materials["id"]."'";
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