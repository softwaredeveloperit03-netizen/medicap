<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    
    
    error_reporting(E_ALL);
ini_set('display_errors', 1);
    
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

    if (!function_exists('bpFillPlanMaterialDisplay')) {
        function bpFillPlanMaterialDisplay(&$mat) {
            if (!is_array($mat)) {
                return;
            }
            $qty = $mat['qty'] ?? ($mat['unit_qty'] ?? '');
            $batchQty = $mat['batch_qty'] ?? $qty;
            if (!isset($mat['overages']) || $mat['overages'] === null || $mat['overages'] === '') {
                $mat['overages'] = '0';
            }
            if (!isset($mat['batch_overages']) || $mat['batch_overages'] === null || $mat['batch_overages'] === '') {
                $mat['batch_overages'] = '0';
            }
            if (!isset($mat['total_qty']) || $mat['total_qty'] === null || $mat['total_qty'] === '') {
                $mat['total_qty'] = $qty;
            }
            if (!isset($mat['total_unit_qty']) || $mat['total_unit_qty'] === null || $mat['total_unit_qty'] === '') {
                $mat['total_unit_qty'] = $mat['total_qty'];
            }
            if (!isset($mat['total_final_qty']) || $mat['total_final_qty'] === null || $mat['total_final_qty'] === '') {
                $mat['total_final_qty'] = $batchQty;
            }
            if (!isset($mat['total_batch_qty']) || $mat['total_batch_qty'] === null || $mat['total_batch_qty'] === '') {
                $mat['total_batch_qty'] = $mat['total_final_qty'];
            }
            if (!isset($mat['lod_status']) || $mat['lod_status'] === null || trim((string)$mat['lod_status']) === '') {
                $mat['lod_status'] = 'No';
            }
            if (!isset($mat['assay_status']) || $mat['assay_status'] === null || trim((string)$mat['assay_status']) === '') {
                $mat['assay_status'] = 'No';
            }
            if (!isset($mat['stage']) || $mat['stage'] === null || trim((string)$mat['stage']) === '') {
                $mat['stage'] = 'General';
            }
        }
    }
    if (!function_exists('bpPlanCompletionPct')) {
        function bpPlanCompletionPct($conn, $planId, $plantId, $totalBatches) {
            $totalBatches = max(1, intval($totalBatches));
            $prepared = 0;
            $sql = "SELECT COUNT(*) AS c FROM mfg_work_order_hdr
                    WHERE batch_plan_id = '".intval($planId)."'
                      AND plant_id = '".$conn->real_escape_string((string)$plantId)."'
                      AND TRIM(IFNULL(lod_status,'')) <> ''";
            $res = @$conn->query($sql);
            if ($res && ($row = $res->fetch_assoc())) {
                $prepared = intval($row['c'] ?? 0);
            }
            return strval((int)round(100 * $prepared / $totalBatches, 0));
        }
    }

    if ($_GET["type"] == "getProducts") {
        $output = array();
        //$sql = "SELECT b.product_code, p.product_name, p.grade FROM unitformula b LEFT JOIN product p ON b.product_code=p.product_code WHERE p.product_type='".$_GET["product_type"]."'  AND b.bom_type='".$_GET["bom_type"]."'  GROUP BY b.product_code";
        $sql = "SELECT b.product_code, p.product_name, p.grade FROM unitformula b LEFT JOIN product p ON b.product_code=p.product_code WHERE p.product_type='".$_GET["product_type"]."'  AND b.bom_type='".$_GET["bom_type"]."'";
        //echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $sql1 = "SELECT IFNULL(SUM(tailing_qty), 0) as tailing_qty FROM bmr_tailing WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        $row["tailing_qty"] = $row1["tailing_qty"];
                    }
                } else {
                    $row["tailing_qty"] = 0;
                }
                
                $output1 = array();
                $sql1 = "SELECT * FROM unitformula WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                          $row1 = array_map('utf8_encode', $row1);
                        $row1["raw_materials"] = json_decode($row1["raw_materials"]);
                
                        $materials = $row1["raw_materials"];
                        for ($i = 0; $i < count($materials); $i++) {
                            $material = $materials[$i];
                            
                            $sql2="SELECT mt.material_code,mt.material_name,sum(sb.qty) as qty FROM material mt left join stock_book sb on mt.material_code = sb.material_code
                                 where mt.material_code='".$material->material_code."'";
                                
                            // $sql2 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                            $result2 = $conn->query($sql2);
                         //  echo json_ecode($result2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                      $row2 = array_map('utf8_encode', $row2);
                                    $material->material_name = $row2["material_name"];
                                    $material->grade = $row2["grade"];
                                     $material->received_qty =$row2["qty"];
                                }
                            }
                            $materials[$i] = $material;
                        }
                        
                        $row1["raw_materials"] = $materials;
                        
                        $row1["packing_materials"] = json_decode($row1["packing_materials"]);
                        
                        $materials = $row1["packing_materials"];
                        for ($i = 0; $i < count($materials); $i++) {
                            $material = $materials[$i];
                            $sql2 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                      $row2 = array_map('utf8_encode', $row2);
                                    $material->material_name = $row2["material_name"];
                                    $material->grade = $row2["grade"];
                                }
                            }
                            $materials[$i] = $material;
                        }
                        $row1["packing_materials"] = $materials;
                        $output1[] = $row1;
                        
                    }
                }
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "get_client_po_by_prod_code") {
           $output = array();
        $sql="SELECT*  FROM po_entry p left join order_materials o on p.order_no=o.order_no where   p.client_code='".$_GET["client_code"]."'";
        // $sql="SELECT p.po_no,o.order_qty,o.unit,o.rate,p.po_Date  FROM po_entry p join order_materials o on p.order_no=o.order_no where 
        //     p.client_code='".$_GET["client_code"]."' and p.status='approve'
        //     and o.product_code='".$_GET["product_code"]."'";
            
         $result = $conn->query($sql);
         while ($row = $result->fetch_assoc()) {
               $output[] = $row;
         }    
         echo json_encode($output);
    
    }
    else if ($_GET["type"] == "getApprovedBatchPlans") {
             $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.entry_by as palnned_by,a.approved_by,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size,p.dosage_form, p.product_name,.p.product_type,
             p.grade,b.pack_size,b.pack_unit FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'
             and a.status='approved' and qa_person!='' and dispense_request_sent_by='' "; 
        $result = $conn->query($sql);                   
       if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = Array();
                 $sql2="select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
                 FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id join material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id where a.work_order_id = '".$row["id"]."') as a join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code";
                 
                  
                 $result1 = $conn->query($sql2);
                 if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                            $output1[]=$row1;   
                    }
                     
                 }
                 $row["materials"] = $output1;
                $output[] = $row;
        }
    }
    echo json_encode($output);
    }
    else if ($_GET["type"] == "savePlan") {
        $sql = "INSERT INTO batch_planning (plant_name,bom_type, product_code, bom_no, min_output_qty, max_output_qty, unit, total_batches, tailing_batches, fresh_batches, entry_by, entry_date, dispatch_qty, planned_qty, can_plan_qty) VALUES ('".$_GET["department"]."', '".$input["bom_type"]."','".$input["product_code"]."','".$input["bom_no"]."','".$input["min_output_qty"]."','".$input["max_output_qty"]."','".$input["unit"]."','".$input["total_batches"]."','".$input["tailing_batches"]."','".$input["fresh_batches"]."', '".$_GET["emp_id"]."', '$entry_date','".$input["dispatch_qty"]."','".$input["planned_qty"]."', '".$input["can_plan_qty"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            $last_id = $conn->insert_id;
            $plan_no = $last_id;
            $sql = "SELECT plan_no FROM batch_planning WHERE id='".$last_id."'";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                $plan_no = $row["plan_no"];
            }
             for ($i = 0; $i < +$input["total_batches"]; $i++) {
                $sql = "INSERT INTO bmr (plan_no, bom_for, bom_type,company_unit, product_code, bom_no, min_output_qty, max_output_qty, raw_materials, packing_materials, islot, batch_size, status, entry_by, entry_date) VALUES ('$plan_no','".$input["bom_for"]."', '".$input["bom_type"]."','".$_GET["department"]."', '".$input["product_code"]."', '".$input["bom_no"]."', '".$input["min_output_qty"]."', '".$input["max_output_qty"]."', '".json_encode($input["raw_materials"])."', '".json_encode($input["packing_materials"])."', 'No', '".$input["min_output_qty"]."', 'pending', '".$_GET["emp_id"]."', '$entry_date')";
                $conn->query($sql);
            }
            for ($i = 0; $i < +$input["tailing_batches"]; $i++) {
                $sql = "INSERT INTO bmr (plan_no, bom_for, bom_type,company_unit, product_code, bom_no, min_output_qty, max_output_qty, batch_size, status, entry_by, entry_date) VALUES ('$plan_no','".$input["bom_for"]."', '".$input["bom_type"]."','".$_GET["department"]."', '".$input["product_code"]."', '".$input["bom_no"]."', '".$input["min_output_qty"]."', '".$input["max_output_qty"]."', '".$input["dispatch_qty"]."', 'pending', '".$_GET["emp_id"]."', '$entry_date')";
                $conn->query($sql);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    
    else if ($_GET["type"] == "getPlans") {
        $output = array();
        $bpHelper = dirname(__DIR__) . '/marketing/can_planned_wo_helpers.php';
        if (is_file($bpHelper)) {
            require_once $bpHelper;
        }
        if (function_exists('stp_backfill_line_approved_batch_plans')) {
            stp_backfill_line_approved_batch_plans($conn, $_GET['plant_id'] ?? '', $_GET['emp_id'] ?? '');
        }
          $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches,uf.id as u_id, b.plan_no,b.plan_type,b.plan_type,b.plan_for,
          b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,bf.rm_batch_size_unit as batch_size_unit, bf.rm_batch_size_unit,
          b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.TrdNm as company,bf.batch_formula_weight as planned_batch_size
          FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
          left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
          LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
          LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
          Where b.plant_id = '".$_GET["plant_id"]."'
         group by b.approve_date,b.approve_by,b.no_of_batches_can_planned,b.qty_can_planned,b.planned_qty,b.dispatch_qty,b.total_batches,b.pack_size,b.batch_no,
         b.batch_size,b.mfr_no,b.bfr_no,b.grade,b.pack_multiple_countries,b.country_specific,b.material_type,b.plan_based_on,b.plan_for_market,b.product_name,b.product_type,b.client_po_unit,b.client_po_qty,b.client_po_date,b.client_po_no,b.plan_client_code,b.plan_client_name,cl.TrdNm,
         plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
          b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight 
          ORDER By b.entry_date desc";
       
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
               
                   $sql1 ="SELECT a.*,c.role,c.split_into_lots,b.qty as avbl_stock,b.m_grade,material_name, material_subtype from batch_planning_materials a left join(SELECT mt.material_code,mt.material_name,grade as m_grade,
                sum(sb.qty) as qty,mt.material_subtype FROM material mt left join stock_book sb on mt.material_code = sb.material_code group by mt.material_code,mt.grade,
                mt.material_name,mt.material_subtype)b on a.material_code = b.material_code left join batch_materials c on a.bfr_no = c.bfr_no and a.material_code = c.material_code where a.material_type='Raw Material' and  a.batch_plan_id='".$row["id"]."'";
                
                
               
                $result1 = $conn->query($sql1);
               if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $mGrade = $row1['m_grade'] ?? ($row1['grade'] ?? '');
                         $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('". mysqli_real_escape_string($conn, (string)$mGrade) ."')";
             $resQ = $conn->query($q);
              $prodLatest = ($resQ) ? $resQ->fetch_assoc() : null; 
         
          $row1['gradeName'] = is_array($prodLatest) ? ($prodLatest['gradeName'] ?? '') : ''; 
          
          
          $cleaned_material_code = mysqli_real_escape_string($conn, $row1['material_code'] ?? '');
$cleaned_plan_no = mysqli_real_escape_string($conn, $row['plan_no'] ?? '');





$sql5 = "SELECT raw_materials FROM unitformula  a LEFT JOIN batch_planning b ON a.mfr_no = b.mfr_no WHERE  b.plan_no = '".$row['plan_no']."'";
          
 
                             
                $resQ1 = $conn->query($sql5);
                $prodLatest1 = ($resQ1) ? $resQ1->fetch_assoc() : null; 
         
            $matCode = $row1['material_code'] ?? '';
                
                $json_obj1 = is_array($prodLatest1) ? ($prodLatest1['raw_materials'] ?? null) : null;
$rmMaty = json_decode($json_obj1, true);

if (json_last_error() === JSON_ERROR_NONE && is_array($rmMaty)) {       
        foreach ($rmMaty as $itm) {
            if (!is_array($itm)) {
                continue;
            }
            $itmCode = $itm['material_Code'] ?? ($itm['material_code'] ?? '');
             if($itmCode == $matCode){
                 $row1['percent_qty'] = $itm['percent_qty'] ?? ''; 
             }
        }  
}
                
                bpFillPlanMaterialDisplay($row1);
                $output1[] = $row1;
                            
                            
                            
                            
                            
                            
                    }

                    
                    
                    
               }
                $row["raw_materials"] = $output1;
                
          
              
                $output1 = Array();
                
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["u_id"]."' ";
               
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT *,b.shortage_qty as short_qt from unitformula_packing_materials a left
                     JOIN batch_planning_materials b on a.material_code=b.material_code where  a.unit_formula_dtl_id ='".$row1["id"]."' and b.batch_plan_id='".$row["id"]."'";
                    //   echo  $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2 && $result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                $sqlite = "select sum(qty) as avbl_stock from  stock_book where material_code = '".$row2['material_code']."'";
                                 $result3 = $conn->query($sqlite);
                            if ($result3 && $result3->num_rows > 0) {
                            while ($row66 = $result3->fetch_assoc()) {
                            
                                $row2['avbl_stock'] = $row66['avbl_stock'];
                                }
                                
                            }
                                 $output2[] = $row2;
                                 
                                 
                            }
                            
                        }
                        $row1['packing_materials'] = $output2;
                         
                        $output1[] = $row1;
                    }
                    
                }
                $row['packing_configuration'] =$output1;
                $row['plan_comp_status'] = bpPlanCompletionPct(
                    $conn,
                    $row['id'] ?? 0,
                    $_GET['plant_id'] ?? '',
                    $row['total_batches'] ?? 1
                );
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPlansMEHA") {
        $output = array();
          $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches,uf.id as u_id, b.plan_no,b.plan_type,b.plan_type,b.plan_for,
          b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
          b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size
          FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
          left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
          LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
          LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
          Where b.plant_id = '".$_GET["plant_id"]."'
         group by b.approve_date,b.approve_by,b.no_of_batches_can_planned,b.qty_can_planned,b.planned_qty,b.dispatch_qty,b.total_batches,b.pack_size,b.batch_no,b.batch_size,b.mfr_no,b.bfr_no,b.grade,b.pack_multiple_countries,b.country_specific,b.material_type,b.plan_based_on,b.plan_for_market,b.product_name,b.product_type,b.client_po_unit,b.client_po_qty,b.client_po_date,b.client_po_no,b.plan_client_code,b.plan_client_name,cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
          b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit,u_id
          ORDER By b.entry_date desc";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
               
                //   $sql1 ="SELECT a.*,c.role,c.split_into_lots,b.qty as avbl_stock,material_name, material_subtype from batch_planning_materials a left join(SELECT mt.material_code,mt.material_name,grade as m_grade,
                // sum(sb.qty) as qty,mt.material_subtype FROM material mt left join stock_book sb on mt.material_code = sb.material_code group by mt.material_code,
                // mt.material_name,mt.material_subtype)b on a.material_code = b.material_code left join batch_materials c on a.bfr_no = c.bfr_no and a.material_code = c.material_code where a.material_type='Raw Material' and  a.batch_plan_id='".$row["id"]."'";
                
                $sql1="SELECT 
                    a.*, 
                    c.role, 
                    c.split_into_lots, 
                    CASE 
                        WHEN a.material_type = 'Intermediate/Finish Product' THEN fg.qty 
                        ELSE sb.qty 
                    END AS avbl_stock, 
                    CASE 
                        WHEN a.material_type = 'Intermediate/Finish Product' THEN p.product_name 
                        ELSE m.material_name 
                    END AS material_name, 
                    CASE 
                        WHEN a.material_type = 'Intermediate/Finish Product' THEN p.product_type 
                        ELSE m.material_subtype 
                    END AS material_subtype 
                FROM batch_planning_materials a 
                
                -- Join for Raw Material
                LEFT JOIN (
                    SELECT 
                        mt.material_code, 
                        mt.material_name, 
                        mt.material_subtype 
                    FROM material mt
                ) m 
                ON a.material_code = m.material_code 
                AND a.material_type = 'Raw Material' 
                
                -- Join stock for Raw Material
                LEFT JOIN (
                    SELECT 
                        sb.material_code, 
                        SUM(CAST(sb.qty AS DECIMAL(10,2))) AS qty  
                    FROM stock_book sb 
                    GROUP BY sb.material_code
                ) sb 
                ON a.material_code = sb.material_code 
                AND a.material_type = 'Raw Material' 
                
                -- Join for Intermediate/Finish Product
                LEFT JOIN (
                    SELECT 
                        p.product_code, 
                        p.product_name, 
                        p.product_type 
                    FROM product p
                ) p 
                ON a.material_code = p.product_code 
                AND a.material_type = 'Intermediate/Finish Product' 
                
                -- Join stock for Intermediate/Finish Product
                LEFT JOIN (
                    SELECT 
                        fg.material_code, 
                        SUM(CAST(fg.qty AS DECIMAL(10,2))) AS qty  
                    FROM fg_stock_book fg 
                    GROUP BY fg.material_code
                ) fg 
                ON a.material_code = fg.material_code 
                AND a.material_type = 'Intermediate/Finish Product' 
                
                -- Join batch_materials for role and split_into_lots
                LEFT JOIN batch_materials c 
                ON a.bfr_no = c.bfr_no 
                AND a.material_code = c.material_code 
                
                WHERE 
                    (a.material_type = 'Raw Material' OR a.material_type = 'Intermediate/Finish Product') 
                    AND a.batch_plan_id = '".$row["id"]."'";
               
                $result1 = $conn->query($sql1);
               if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('". $row1['m_grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
          
          
          $cleaned_material_code = mysqli_real_escape_string($conn, $row1['material_code']);
$cleaned_plan_no = mysqli_real_escape_string($conn, $row['plan_no']);





$sql5 = "SELECT raw_materials FROM unitformula  a LEFT JOIN batch_planning b ON a.mfr_no = b.mfr_no WHERE  b.plan_no = '".$row['plan_no']."'";
          
 
                             
                $resQ1 = $conn->query($sql5);
                $prodLatest1 = $resQ1->fetch_assoc(); 
         
            $matCode = $row1['material_code'];
                
                $json_obj1 = $prodLatest1['raw_materials'];
$rmMaty = json_decode($json_obj1, true);

if (json_last_error() === JSON_ERROR_NONE && isset($rmMaty)) {       
        foreach ($rmMaty as $itm) {
            
             if($itm['material_Code'] == $matCode){
                 $row1['percent_qty'] = $itm['percent_qty']; 
             }
        }  
}
                
                $output1[] = $row1;
                            
                            
                            
                            
                            
                            
                    }

                    
                    
                    
               }
                $row["raw_materials"] = $output1;
                
          
              
                $output1 = Array();
                
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["u_id"]."' ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT *,b.shortage_qty as short_qt from unitformula_packing_materials a left
                     JOIN batch_planning_materials b on a.material_code=b.material_code where  a.unit_formula_dtl_id ='".$row1["id"]."' and b.batch_plan_id='".$row["id"]."'";
                    //   echo  $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                $sqlite = "select sum(qty) as avbl_stock from  stock_book where material_code = '".$row2['material_code']."'";
                                 $result3 = $conn->query($sqlite);
                            if ($result3->num_rows > 0) {
                            while ($row66 = $result3->fetch_assoc()) {
                            
                                $row2['avbl_stock'] = $row66['avbl_stock'];
                                }
                                
                            }
                                 $output2[] = $row2;
                                 
                                 
                            }
                            
                        }
                        $row1['packing_materials'] = $output2;
                         
                        $output1[] = $row1;
                    }
                    
                }
                $row['packing_configuration'] =$output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_comp_bmr_sp") {
          $output = array();
         $sql ="SELECT					d.LglNm as client_name,
							d.id,
                        s.total_qty,
                        a.id,
                        a.fg_intimation_raised_by,
                        a.fg_intimation_raised_date,
                        a.fg_intimation_receive_by,
                        a.fg_intimation_receive_date,
                        a.batch_number,
                        p.dosage_form,
                        a.bmr_no,
                        a.fg_sampling_intimation,
                        a.work_order_no,
                        a.rm_qa_dislc_date AS palnned_by,
                        a.approved_by,
                        a.lod_status,
                        a.calculation_type,
                        a.batch_commence_date,
                        a.batch_plan_id,
                        a.sampling_intimation,
                        a.stage_checked_by,
                        a.tr_to_packing_dept_by,
                        a.batch_complete_date,
                        a.stability,
                        a.stability_reason,
                        a.process_validation,
                        a.qa_person,
                        a.qa_date,
                        a.no_of_lots,
                        uf.min_per AS min_yeild,
                        uf.max_per AS max_yeild,
                        a.bmr_start_by,
                        a.approved_by,
                        b.plan_no,
                        b.bfr_no,
                        b.mfr_no,
                        b.product_code,
                        b.batch_size,
                        p.product_name,
                        p.product_type,
                        p.grade,
                        b.pack_size,
                        d.TrdNm,
                        b.pack_unit,
                        a.dispense_request_sent_by,
                        a.dispense_request_sent_on,
                        a.dispensing_status,
                        rm_disp_completed_by,
                        a.pm_qa_dislc_status AS lc_status,
                        a.pm_qa_dislc_by AS lc_by,
                        a.pm_qa_dislc_date AS lc_date
                    FROM
                        mfg_work_order_hdr a
                    JOIN batch_planning b ON
                        a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                    JOIN product p ON
                        b.product_code = p.product_code AND b.plant_id = p.plant_id
                           left join client d on 
                    d.LglNm=p.manufactured_for
                    LEFT JOIN unitformula uf ON
                        uf.mfr_no = b.mfr_no AND uf.plant_id = b.plant_id
                    LEFT JOIN samplingfg s ON
                        b.product_code = s.product_code
                    
             where  a.plant_id ='".$_GET["plant_id"]."'   and a.fg_sampling_intimation='done'  ";
            //  where  a.plant_id ='".$_GET["plant_id"]."'  and a.bmr_status='Complete' and a.fg_sampling_intimation='done'  ";
            
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
            
    
        
    }
    else if ($_GET["type"] == "getPlans_from_to") {
                $todate = $_GET["to_date"];

// Create a DateTime object from the input date
$date = new DateTime($todate);

// Increment the date by one day
$date->modify('+1 day');

// Format the date in your desired format
  $next_day = $date->format('Y-m-d');
        $output = array();
          $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches,uf.id as u_id, b.plan_no,b.plan_type,b.plan_type,b.plan_for,
          b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
          b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size
          FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
          left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
          LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
          LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
          Where b.plant_id = '".$_GET["plant_id"]."'  and p.product_name like '%".$_GET["product_name"]."%'
                            AND b.entry_date BETWEEN '".$_GET["from_date"]."' AND '$next_day' 
          group by cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
          b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit,u_id
          ORDER By b.id desc";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
               
               // $sql1 = "SELECT a.*,b.material_name FROM batch_planning_materials a left join material b on a.material_code = b.material_code where batch_plan_id='".$row["id"]."'";
               /* $sql1 ="SELECT a.*,b.qty as avbl_stock,material_name,material_subtype from batch_planning_materials a left join(SELECT mt.material_code,mt.material_name,
                sum(sb.qty) as qty,mt.material_subtype FROM material mt left join stock_book sb on mt.material_code = sb.material_code group by mt.material_code,
                mt.material_name,mt.material_subtype)b on a.material_code = b.material_code where a.material_type='Raw Material' and  a.batch_plan_id='".$row["id"]."'";*/
                
                $sql1="select a.*,b.split_into_lots,process_step,yeild_contribution,b.role from (SELECT a.*,b.qty as avbl_stock,material_name,material_subtype,
                m_grade from batch_planning_materials a left join (SELECT mt.plant_id, mt.material_code,mt.material_name, sum(sb.qty) as qty,mt.material_subtype,
                mt.grade as m_grade FROM material mt left join stock_book sb on mt.material_code = sb.material_code group by mt.material_code, m_grade , 
                mt.material_name,mt.material_subtype)b on a.material_code = b.material_code where a.material_type='Raw Material'  and  
                a.batch_plan_id='".$row["id"]."')a 
                left join batch_materials b on a.plant_id = b.plant_id and a.bfr_no = b.bfr_no and a.material_code = b.material_code";
               
                $result1 = $conn->query($sql1);
               if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['m_grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
           $sql5=" SELECT
        JSON_UNQUOTE(JSON_EXTRACT(raw_materials, '$[0].material_subtype')) AS material_subtype,
    JSON_UNQUOTE(JSON_EXTRACT(raw_materials, '$[0].percent_qty')) AS percent_qty
FROM unitformula a
LEFT JOIN batch_planning b ON a.mfr_no = b.mfr_no
WHERE JSON_SEARCH(raw_materials, 'one', '".$row1['material_code']."') IS NOT NULL AND b.plan_no = '".$row['plan_no']."'";
      
                             $resQ1 = $conn->query($sql5);
              $prodLatest1 = $resQ1->fetch_assoc(); 
         
          $row1['percent_qty'] = $prodLatest1['percent_qty']; 
                            $output1[] = $row1;
                    }

                    
                    
                    
               }
                $row["raw_materials"] = $output1;
                
            //     $sql2 ="SELECT a.*,IFNULL(b.qty,0) as avbl_stock,material_name,material_subtype from batch_planning_materials a left join(
            //         SELECT mt.plant_id,mt.material_code,mt.material_name,sum(sb.qty) as qty,mt.material_subtype FROM material
            //         mt left join stock_book sb on mt.material_code = sb.material_code and mt.plant_id = sb.plant_id 
            //         group by mt.material_code,mt.material_name,mt.material_subtype,mt.plant_id)b
            //         on a.material_code = b.material_code and a.plant_id = b.plant_id where a.material_type='Packing Material'
            //         and a.batch_plan_id='".$row["id"]."'";
            //     $result2 = $conn->query($sql2);
            //   if ($result2->num_rows > 0) {
            //       while ($row2 = $result2->fetch_assoc()) {
            //               $output2[] = $row2;
            //         }
            //     }
            //     $row["packing_material"] = $output2;
            
            //  $output1 = Array();
            
            // 
            //  $output2 = array();
            //     $sql2 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,
            //     pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["id"]."' ";
               
            //     $result2 = $conn->query($sql2);
            //     if ($result1->num_rows > 0) {
            //         while ($row2 = $result2->fetch_assoc()) {
                        
            //             $output3 = Array();
            //             $sql3 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
            //             $result3 = $conn->query($sql3);
            //             if ($result3->num_rows > 0) {
            //                 while ($row3 = $result3->fetch_assoc()) {
            //                      $output3[] = $row3;
            //                 }
                            
            //             }
            //             $row1['packing_materials'] = $output2;
                 
            //           $output2[] = $row2;
            //         }
                    
            //     }
            //     $row['packing_configuration'] =$output2;
            //     $output[] = $row;
             $output2 = array();
              
                $output1 = Array();
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["u_id"]."' ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT *,c.qty as avbl_stock,b.shortage_qty as short_qt from unitformula_packing_materials a left JOIN batch_planning_materials b on a.material_code=b.material_code left
                        join stock_book c on a.material_code=c.material_code
                                    where  a.unit_formula_dtl_id ='".$row1["id"]."' and b.batch_plan_id='".$row["id"]."'";
                    //   echo  $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
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
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getPlansForPacking") {
        $output = array();
                        // cmr on 14/03/23
        //   $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,wh.actual_yeild,
        //   b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
        //   b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size
        //   FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
        //   left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
        //   LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
        //   LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
        //   LEFT join mfg_work_order_hdr wh on b.id = wh.batch_plan_id
        //   Where b.plant_id = '".$_GET["plant_id"]."' and wh.tr_to_packing_dept_by !=''
        //   group by cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
        //   b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit,wh.actual_yeild
        //   ORDER By b.id desc";
         
        //   $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,wh.actual_yeild,
        //   b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
        //   b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size,bd.stage_yield
        //   FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
        //   left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
        //   LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
        //   LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
        //   LEFT join mfg_work_order_hdr wh on b.id = wh.batch_plan_id
        //   LEFT JOIN batch_stages_ipqc_dtl bd on b.plan_no = bd.plan_no
        //   Where b.plant_id = '".$_GET["plant_id"]."' and wh.tr_to_packing_dept_by !=''
        //   group by cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
        //   b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit,wh.actual_yeild,bd.stage_yield
        //   ORDER By b.id desc";
          $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,
          b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
          b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size
          FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
          left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
          LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
          LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
          Where b.plant_id = '".$_GET["plant_id"]."' 
          group by cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
          b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit
          ORDER By b.id desc ";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql = "select * from batch_planing_raw_material_hdr where batch_plan_id = '".$row["id"]."' ";
                $result2 = $conn->query($sql);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2 = array();
                        $sql2 ="SELECT
                                    *,a.qty as b_qty ,c.qty as avbl_stock
                                FROM
                                    batch_planning_materials a
                                LEFT JOIN material b ON
                                    a.material_code = b.material_code
                                LEFT JOIN stock_book c ON
                                    b.material_code = c.material_code
                                
                                WHERE
                                    a.material_type = 'Packing Material' AND a.batch_plan_id='".$row2["batch_plan_id"]."'";
                        // $sql2 ="SELECT
                        //             *,a.qty as b_qty ,c.qty as avbl_stock
                        //         FROM
                        //             batch_planning_materials a
                        //         LEFT JOIN material b ON
                        //             a.material_code = b.material_code
                        //         LEFT JOIN stock_book c ON
                        //             b.material_code = c.material_code
                                
                        //         WHERE
                        //             a.material_type = 'Packing Material' AND a.pm_hdr_id='".$row2["id"]."' LIMIT 5";
                        $result3 = $conn->query($sql2);
                        if ($result3->num_rows > 0) {
                           while ($row3 = $result3->fetch_assoc()) {
                                   $output2[] = $row3;
                            }
                        }
                        $row2["packing_material"] = $output2;
                         $output1[] = $row2;
                    }
                    $row["pack_sizes"] = $output1;
                }
                $output[] = $row;
            }
        }else{
            $output=[];
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getbatchPlansForPacking") {
        $output = array();
                        // cmr on 14/03/23
        //   $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,wh.actual_yeild,
        //   b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
        //   b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size
        //   FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
        //   left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
        //   LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
        //   LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
        //   LEFT join mfg_work_order_hdr wh on b.id = wh.batch_plan_id
        //   Where b.plant_id = '".$_GET["plant_id"]."' and wh.tr_to_packing_dept_by !=''
        //   group by cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
        //   b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit,wh.actual_yeild
        //   ORDER By b.id desc";
         
        //   $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,wh.actual_yeild,
        //   b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
        //   b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size,bd.stage_yield
        //   FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
        //   left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
        //   LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
        //   LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
        //   LEFT join mfg_work_order_hdr wh on b.id = wh.batch_plan_id
        //   LEFT JOIN batch_stages_ipqc_dtl bd on b.plan_no = bd.plan_no
        //   Where b.plant_id = '".$_GET["plant_id"]."' and wh.tr_to_packing_dept_by !=''
        //   group by cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
        //   b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit,wh.actual_yeild,bd.stage_yield
        //   ORDER By b.id desc";
          $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,
          b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
          b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size
          FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
          left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
          LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
          LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
          Where b.plant_id = '".$_GET["plant_id"]."'  
          group by cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
          b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit
          ORDER By b.id desc";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql = "select * from batch_planing_raw_material_hdr where batch_plan_id = '".$row["id"]."'";
                $result2 = $conn->query($sql);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2 = array();
                        $sql2 ="SELECT a.*,IFNULL(b.qty,0) as avbl_stock,material_name,material_subtype from batch_planning_materials a left join(
                        SELECT mt.plant_id,mt.material_code,mt.material_name,sum(sb.qty) as qty,mt.material_subtype FROM material
                        mt left join stock_book sb on mt.material_code = sb.material_code and mt.plant_id = sb.plant_id 
                        group by mt.material_code,mt.material_name,mt.material_subtype,mt.plant_id)b
                        on a.material_code = b.material_code and a.plant_id = b.plant_id where a.material_type='Packing Material'
                        and a.pm_hdr_id='".$row2["id"]."'";
                        $result3 = $conn->query($sql2);
                        if ($result3->num_rows > 0) {
                           while ($row3 = $result3->fetch_assoc()) {
                                   $output2[] = $row3;
                            }
                        }
                        $row2["packing_material"] = $output2;
                         $output1[] = $row2;
                    }
                    $row["pack_sizes"] = $output1;
                }
                $output[] = $row;
            }
        }else{
            $output=[];
        }
        echo json_encode($output);
    }
    
     else if ($_GET["type"] == "get_batch_plan_details") {
        $output = array();
        $sql="SELECT a.id,a.batch_id,a.entry_date as plan_date, b.batch_size,b.bfr_no,b.mfr_no,a.status,a.qa_person ,
        a.qa_date, a.lod_status, a.assay_status, a.overages_percent, a.work_order_no, a.no_of_lots FROM  mfg_work_order_hdr a
              join batch_planning b on a.batch_plan_id = b.id and a.plant_id = b.plant_id
              where a.plant_id ='".$_GET["plant_id"]."' and a.batch_plan_id='".$_GET["batch_plan_id"]."'
              AND a.material_type = '".$_GET["material_type"]."' ";
           
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                  $sql2="select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
                 FROM mfg_work_order_dtl a left join mfg_work_order_hdr c on a.work_order_id = c.id left join material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code ";
                 
                 
                 $result1 = $conn->query($sql2);
                 if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        bpFillPlanMaterialDisplay($row1);
                            $output1[]=$row1;   
                    }
                     
                 }
                 $row["materials"] = $output1;
                $output[] = $row;
            }
            
        }else{
            $output=[];
        }
         echo json_encode($output);
     }
     else if ($_GET["type"] == "get_batch_plan_detailsMeha") {
         
         
        $sql="SELECT a.id,a.batch_id,a.entry_date as plan_date, b.batch_size,b.bfr_no,b.mfr_no,a.status,a.qa_person ,
        a.qa_date FROM  mfg_work_order_hdr a
              join batch_planning b on a.batch_plan_id = b.id and a.plant_id = b.plant_id
              where a.plant_id ='".$_GET["plant_id"]."' and a.batch_plan_id='".$_GET["batch_plan_id"]."'
              AND a.material_type = '".$_GET["material_type"]."' ";
           
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                //   $sql2="select a.*,b.avbl_stock from (SELECT a.*,b.material_name
                //  FROM mfg_work_order_dtl a left join mfg_work_order_hdr c on a.work_order_id = c.id left join material b
                //  on a.material_code = b.material_code and c.plant_id = b.plant_id where a.work_order_id = '".$row["id"]."') as a join
                //  (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                //  WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                //  GROUP by material_code) as b on a.material_code = b.material_code ";
                  $sql2="select a.* ,COALESCE(b.avbl_stock, c.avbl_stock) AS avbl_stock from (SELECT a.*,b.grade as m_grade,COALESCE(b.material_name, p.product_name) AS material_name
                 FROM mfg_work_order_dtl a left join mfg_work_order_hdr c on a.work_order_id = c.id left 
                 join material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id left join product p on a.material_code = p.product_code and c.plant_id = p.plant_id   where (a.material_subtype='Intermediate' or a.material_subtype='Raw Material') and  a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code   left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM fg_stock_book
                 WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as c on a.material_code = c.material_code";
                 
                 
                 $result1 = $conn->query($sql2);
                 if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        
                        
                        
                        
                            $output1[]=$row1;   
                    }
                     
                 }
                 $row["materials"] = $output1;
                $output[] = $row;
            }
            
        }else{
            $output=[];
        }
         echo json_encode($output);
     
     }
     else if ($_GET["type"] == "get_batch_plan_detailsMeha") {
         
         
        $sql="SELECT a.id,a.batch_id,a.entry_date as plan_date, b.batch_size,b.bfr_no,b.mfr_no,a.status,a.qa_person ,
        a.qa_date FROM  mfg_work_order_hdr a
              join batch_planning b on a.batch_plan_id = b.id and a.plant_id = b.plant_id
              where a.plant_id ='".$_GET["plant_id"]."' and a.batch_plan_id='".$_GET["batch_plan_id"]."'
              AND a.material_type = '".$_GET["material_type"]."' ";
           
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                //   $sql2="select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
                //  FROM mfg_work_order_dtl a left join mfg_work_order_hdr c on a.work_order_id = c.id left join material b
                //  on a.material_code = b.material_code and c.plant_id = b.plant_id where a.work_order_id = '".$row["id"]."') as a join
                //  (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                //  WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                //  GROUP by material_code) as b on a.material_code = b.material_code ";
                $sql2="SELECT 
                            a.id, 
                            a.work_order_id, 
                            a.material_code, 
                          --  a.required_qty, 
                          --  a.uom, 
                         --   a.created_at, 
                         --   a.updated_at, 
                         --   a.status, 
                         --   a.material_type, 
                         --   a.material_subtype, 
                         --   a.material_name, 
                            b.avbl_stock
                        FROM 
                            (SELECT 
                                a.id, 
                                a.work_order_id, 
                                a.material_code, 
                            --    a.required_qty, 
                            --    a.uom, 
                            --    a.created_at, 
                            --    a.updated_at, 
                             --   a.status, 
                                b.material_type, 
                                b.material_subtype, 
                                b.material_name
                             FROM mfg_work_order_dtl a
                             LEFT JOIN mfg_work_order_hdr c ON a.work_order_id = c.id
                             LEFT JOIN material b ON a.material_code = b.material_code AND c.plant_id = b.plant_id
                             WHERE a.work_order_id = '".$row["id"]."') AS a
                        JOIN 
                            (SELECT material_code, SUM(qty) AS avbl_stock 
                             FROM stock_book
                             WHERE material_code IN (SELECT material_code FROM mfg_work_order_dtl WHERE work_order_id ='".$row["id"]."') 
                             GROUP BY material_code) AS b 
                        ON a.material_code = b.material_code;
";
                 
                 
                 $result1 = $conn->query($sql2);
                 if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        
                        
                        
                        
                            $output1[]=$row1;   
                    }
                     
                 }
                 $row["materials"] = $output1;
                $output[] = $row;
            }
            
        }else{
            $output=[];
        }
         echo json_encode($output);
     
     }
     
    else if ($_GET["type"] == "batch_no") {
        
	$output = Array();

    	$sql = "SELECT a.*,b.plan_no FROM mfg_work_order_hdr a LEFT JOIN batch_planning b on a.batch_plan_id=b.id 
    	WHERE b.plan_no='".$_GET["plan_no"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    }
    else if ($_GET["type"] == "get_yeild") {
        
	$output = Array();

    	$sql = "SELECT * FROM mfg_work_order_hdr WHERE a.batch_number='".$_GET["batch_number"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    }
    else if ($_GET["type"] == "getPendingPlans") {
        $output = array();
        $t_date=$_GET["to_date"];
     $date = new DateTime($t_date);
$date->modify('+1 day');
 
 $to_date=$date->format('Y-m-d') . "\n";
        
        //$sql = "SELECT b.*, p.product_name FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.plant_name='".$_GET["department"]."' AND b.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY b.product_code ORDER BY id DESC";
       // $sql = "SELECT b.*, p.product_name FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.plant_name='".$_GET["department"]."' AND b.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
         $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_for,
        b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty, 
        b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name FROM batch_planning b LEFT 
        JOIN product p ON b.product_code=p.product_code  WHERE b.entry_date BETWEEN '".$_GET["from_date"]."' 
        AND '$to_date' AND b.status='PENDING' 
                group by plan_no,plan_type,plan_for,b.product_code,b.plan_no,b.status,b.entry_by,b.entry_date,b.qty_can_planned,b.no_of_batches_can_planned,
                b.approve_date,b.approve_by,b.planned_qty,b.dispatch_qty,b.total_batches,b.pack_size,b.batch_no,b.batch_size,b.mfr_no,b.bfr_no,b.grade,
                b.pack_multiple_countries,b.country_specific,b.product_name,b.product_type,b.client_po_unit,b.client_po_qty,b.client_po_date,b.client_po_no,
                b.plan_client_code,b.plan_client_name,b.plan_for_market,b.plan_based_on,b.material_type,b.plant_id,p.product_name,b.pack_unit,b.id ORDER By b.id desc;";
        // echo $sql;        
        // $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plant_name,b.bom_type,b.bom_for,b.product_code,bom_no,count(min_output_qty) as min_output_qty,count(max_output_qty) as max_output_qty,
        //         b.unit,count(total_batches) as total_batches,count(tailing_batches) as tailing_batches,count(fresh_batches) as fresh_batches,b.status,b.entry_by,b.entry_date,p.product_name FROM batch_planning b 
        //         LEFT JOIN product p ON b.product_code=p.product_code WHERE b.plant_name='".$_GET["department"]."' AND b.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' 
        //         group by plan_no,plant_name,bom_type,bom_for,b.product_code,b.bom_no,b.status,b.entry_by,b.entry_date,p.product_name,b.unit,b.id ORDER By b.id desc";
                 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["planned_qty"] = +$row["min_output_qty"] * +$row["total_batches"];
                $output1 = array();
                $output2 = array();
                $sql1 = "SELECT * FROM bmr WHERE plan_no='".$row["plan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["bom_type"] == 'Fresh Batch') {
                            $output1[] = $row1;
                        } else if ($row1["bom_type"] == 'Blending Batch') {
                            $output2[] = $row1;
                        }
                    }
                }
                $row["batches1"] = $output1;
                $row["batches2"] = $output2;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getApprovedPlans") {
        $output = array();
        $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plant_name,b.bom_type,b.bom_for,b.product_code,bom_no,count(min_output_qty) as min_output_qty,
                count(max_output_qty) as max_output_qty,
                b.unit, case when b.bom_type = 'Blending Batch' then (SELECT count(*) FROM bmr WHERE plan_no=b.plan_no and expected_start_date='0000-00-00') else 0 end as tailing_batches,
                case when b.bom_type = 'Fresh Batch' then (SELECT count(*) FROM bmr WHERE plan_no=b.plan_no and expected_start_date='0000-00-00')  else 0 end as total_batches,
                b.status,b.entry_by,b.entry_date,p.product_name,
                '' as allocated_batch_no  FROM batch_planning b 
                LEFT JOIN product p ON b.product_code=p.product_code WHERE b.plant_name='".$_GET["department"]."' AND b.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' AND b.status='APPROVED' 
                group by plan_no,plant_name,bom_type,bom_for,b.product_code,b.bom_no,b.status,b.entry_by,b.entry_date,p.product_name,b.unit,b.id ORDER By b.id desc";
               // echo $sql;
                  $show_only_approved= $_GET["show_only_approved"];
                //  echo $show_only_approved;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["planned_qty"] = +$row["min_output_qty"] * +$row["total_batches"];
                $output1 = array();
                $output2 = array();
                $output3 = array();
                $sql2 ="";
                if($show_only_approved == true){
                $sql2 = "SELECT * FROM bmr WHERE plan_no='".$row["plan_no"]."' and expected_start_date!='0000-00-00' ";
                }else{
                    $sql2 = "SELECT * FROM bmr WHERE plan_no='".$row["plan_no"]."' and expected_start_date='0000-00-00' ";
                }
              //  echo $sql2;
                $result1 = $conn->query($sql2);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["bom_type"] == 'Fresh Batch') {
                            $output1[] = $row1;
                        } else if ($row1["bom_type"] == 'Blending Batch') {
                            $output2[] = $row1;
                        }
                    }
                }
                $row["batches1"] = $output1;
                $row["batches2"] = $output2;
                
               /*$sql1 = "SELECT * FROM batch_no WHERE plan_no='".$row["plan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output3[] = $row1;
                    }
                }
                 $row["batches3"] = $output3;*/
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getApprovedPlansForQaApproval") {
        $output = array();
        $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plant_name,b.bom_type,b.bom_for,b.product_code,bom_no,count(min_output_qty) as min_output_qty,
                count(max_output_qty) as max_output_qty,
                b.unit, 
                case when b.bom_type = 'Blending Batch' then (SELECT count(*) FROM  bmr a join batch_no b1 on a.id = b1.batch_id where a.plan_no=b.plan_no  and b1.allocate_batch_no='') else 0 end as tailing_batches,
                case when b.bom_type = 'Fresh Batch' then (SELECT count(*) FROM  bmr a join batch_no b1 on a.id = b1.batch_id where a.plan_no=b.plan_no   and b1.allocate_batch_no='')  else 0 end as total_batches,
                b.status,b.entry_by,b.entry_date,p.product_name,
                '' as allocated_batch_no  FROM batch_planning b 
                LEFT JOIN product p ON b.product_code=p.product_code WHERE b.plant_name='".$_GET["department"]."' AND b.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' AND b.status='APPROVED' 
                group by plan_no,plant_name,bom_type,bom_for,b.product_code,b.bom_no,b.status,b.entry_by,b.entry_date,p.product_name,b.unit,b.id ORDER By b.id desc";
               // echo $sql;
                  $show_only_approved= $_GET["show_only_approved"];
                //  echo $show_only_approved;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["planned_qty"] = +$row["min_output_qty"] * +$row["total_batches"];
                $output1 = array();
                $output2 = array();
                $output3 = array();
                $sql2 ="";
                $sql2 = "SELECT a.*,b.allocate_batch_no FROM  bmr a join batch_no b on a.id = b.batch_id where a.plan_no='".$row["plan_no"]."'   and b.allocate_batch_no=''";
                
              //  echo $sql2;
                $result1 = $conn->query($sql2);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["bom_type"] == 'Fresh Batch') {
                            $output1[] = $row1;
                        } else if ($row1["bom_type"] == 'Blending Batch') {
                            $output2[] = $row1;
                        }
                    }
                }
                $row["batches1"] = $output1;
                $row["batches2"] = $output2;
                
                $sql1 = "SELECT * FROM batch_no WHERE plan_no='".$row["plan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output3[] = $row1;
                    }
                }
                 $row["batches3"] = $output3;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getApprovedPlansForProduction") {
        $output = array();
                
        $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plant_name,b.bom_type,b.bom_for,b.product_code,bom_no,count(min_output_qty) as min_output_qty,
                count(max_output_qty) as max_output_qty,
                b.unit, 
                case when b.bom_type = 'Blending Batch' then (select   count(*)  from bmr a join batch_no b1 on a.id = b1.batch_id where a.plan_no= b.plan_no and b1.bmr_received_by='' and b1.issue_by!='') else 0 end as tailing_batches,
                case when b.bom_type = 'Fresh Batch' then (select   count(*)  from bmr a join batch_no b1 on a.id = b1.batch_id where a.plan_no= b.plan_no and b1.bmr_received_by='' and b1.issue_by!='')  else 0 end as total_batches,
                b.status,b.entry_by,b.entry_date,p.product_name,
                '' as allocated_batch_no  FROM batch_planning b 
                LEFT JOIN product p ON b.product_code=p.product_code WHERE b.plant_name='".$_GET["department"]."' AND b.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' AND b.status='APPROVED' 
                group by plan_no,plant_name,bom_type,bom_for,b.product_code,b.bom_no,b.status,b.entry_by,b.entry_date,p.product_name,b.unit,b.id ORDER By b.id desc";
               // echo $sql;
                  $show_only_approved= $_GET["show_only_approved"];
                //  echo $show_only_approved;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["planned_qty"] = +$row["min_output_qty"] * +$row["total_batches"];
                $output1 = array();
                $output2 = array();
                $output3 = array();
                //$sql2 = "select  a.id,a.bom_type,a.bmr_no,b.allocate_batch_no as batch_no,a.expected_start_date,a.expected_end_date,b.issue_by,b.issue_to,b.issue_date from bmr a join batch_no b on a.id = b.batch_id where a.plan_no='".$row["plan_no"]."'";
                $sql2 = "select  a.id,a.bom_type,a.bmr_no,b.allocate_batch_no as batch_no,a.expected_start_date,a.expected_end_date,b.issue_by,b.issue_to,b.issue_date from bmr a join batch_no b on a.id = b.batch_id where a.plan_no='".$row["plan_no"]."' and b.bmr_received_by='' and b.issue_by!=''";
                //echo $sql2;
                $result1 = $conn->query($sql2);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["bom_type"] == 'Fresh Batch') {
                            $output1[] = $row1;
                        } else if ($row1["bom_type"] == 'Blending Batch') {
                            $output2[] = $row1;
                        }
                    }
                }
                $row["batches1"] = $output1;
                $row["batches2"] = $output2;
                
                $sql1 = "SELECT * FROM batch_no WHERE plan_no='".$row["plan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output3[] = $row1;
                    }
                }
                 $row["batches3"] = $output3;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPlansForStartProduction") {
        $output = array();
        $sql ="SELECT DISTINCT bp.entry_by, bn.batch_id as id,bn.plan_no,bn.bom_no, bn.plan_no,bp.bom_type,bp.product_code,p.product_name,bp.entry_by ,bn.allocate_batch_no as batch_no,bp.total_batches as batch_size from batch_no bn left join batch_planning bp on bn.plan_no = bp.plan_no JOIN product p on bp.product_code = p.product_code where bn.allocate_batch_no!='' order by 1 desc";
        
        $result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
       /* $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plant_name,b.bom_type,b.bom_for,b.product_code,bom_no,count(min_output_qty) as min_output_qty,
                count(max_output_qty) as max_output_qty,
                b.unit, 
                case when b.bom_type = 'Blending Batch' then (select   count(*)  from bmr a join batch_no b1 on a.id = b1.batch_id where a.plan_no= b.plan_no and b1.bmr_received_by='' and b1.issue_by!='') else 0 end as tailing_batches,
                case when b.bom_type = 'Fresh Batch' then (select   count(*)  from bmr a join batch_no b1 on a.id = b1.batch_id where a.plan_no= b.plan_no and b1.bmr_received_by='' and b1.issue_by!='')  else 0 end as total_batches,
                b.status,b.entry_by,b.entry_date,p.product_name,
                '' as allocated_batch_no  FROM batch_planning b 
                LEFT JOIN product p ON b.product_code=p.product_code WHERE b.plant_name='".$_GET["department"]."' AND b.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' AND b.status='APPROVED' 
                group by plan_no,plant_name,bom_type,bom_for,b.product_code,b.bom_no,b.status,b.entry_by,b.entry_date,p.product_name,b.unit,b.id ORDER By b.id desc";
               //echo $sql;
                  $show_only_approved= $_GET["show_only_approved"];
                //  echo $show_only_approved;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["planned_qty"] = +$row["min_output_qty"] * +$row["total_batches"];
                $output1 = array();
                $output2 = array();
                $output3 = array();
                //$sql2 = "select  a.id,a.bom_type,a.bmr_no,b.allocate_batch_no as batch_no,a.expected_start_date,a.expected_end_date,b.issue_by,b.issue_to,b.issue_date from bmr a join batch_no b on a.id = b.batch_id where a.plan_no='".$row["plan_no"]."'";
                $sql2 = "select  a.id,a.bom_type,a.bmr_no,b.allocate_batch_no as batch_no,a.expected_start_date,a.expected_end_date,b.issue_by,b.issue_to,b.issue_date from bmr a join batch_no b on a.id = b.batch_id where a.plan_no='".$row["plan_no"]."' and b.bmr_received_by='' and b.issue_by!=''";
                //echo $sql2;
                $result1 = $conn->query($sql2);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["bom_type"] == 'Fresh Batch') {
                            $output1[] = $row1;
                        } else if ($row1["bom_type"] == 'Blending Batch') {
                            $output2[] = $row1;
                        }
                    }
                }
                $row["batches1"] = $output1;
                $row["batches2"] = $output2;
                
                $sql1 = "SELECT * FROM batch_no WHERE plan_no='".$row["plan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output3[] = $row1;
                    }
                }
                 $row["batches3"] = $output3;
                $output[] = $row;
            }
        }
        echo json_encode($output);*/
    }
     else if ($_GET["type"] == "getApprovedMFG") {
        $output = array();
        $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plant_name,b.bom_type,b.bom_for,b.product_code,bom_no,count(min_output_qty) as min_output_qty,count(max_output_qty) as max_output_qty,
                b.unit,count(tailing_batches) as tailing_batches,count(fresh_batches) as fresh_batches,b.status,b.entry_by,b.entry_date,p.product_name FROM batch_planning b 
                LEFT JOIN product p ON b.product_code=p.product_code WHERE b.plant_name='".$_GET["department"]."' AND b.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' AND b.status='APPROVED' 
                group by plan_no,plant_name,bom_type,bom_for,b.product_code,b.bom_no,b.status,b.entry_by,b.entry_date,p.product_name,b.unit,b.id ORDER By b.id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["planned_qty"] = +$row["min_output_qty"] * +$row["total_batches"];
                $output1 = array();
                $output2 = array();
                $sql1 = "SELECT * FROM bmr WHERE plan_no='".$row["plan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["bom_type"] == 'Fresh Batch') {
                            $output1[] = $row1;
                        } else if ($row1["bom_type"] == 'Blending Batch') {
                            $output2[] = $row1;
                        }
                    }
                }
                $row["batches1"] = $output1;
                $row["batches2"] = $output2;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPlansByPlanNo") {
         $output = Array();
        $sql = "SELECT b.*, p.product_name FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.plan_no='".$_GET["plan_no"]."' order by b.id desc ";
        $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
    }
     else if ($_GET["type"] == "approveBatchPlanStatus") {
        $sql = "UPDATE batch_planning SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";

        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "downloadPlans") {
        $_GET['filename'] = 'downloadPlans'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Plans</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 10%;">Date</td>
                    <td style="width: 10%;">Plan No.</td>
                    <td style="width: 10%;">BOM For</td>
                    <td style="width: 20%;">Product code</td>
                    <td style="width: 20%;">Product Name</td>
                    <td style="width: 20%;">Output Range</td>
                </tr>
            </thead>';
             $i=1;
             $sql = "SELECT b.*, p.product_name FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code  WHERE b.plant_name='".$_GET["department"]."' AND b.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
             $result = $conn->query($sql);
             if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
        $html.='<tr nobr="true">
                    <td style="width: 10%;">'.$i.'</td>
                    <td style="width: 10%;">'.date("d/m/Y",($row['entry_date'])).'</td>
                    <td style="width: 10%;">'.$row['plan_no'].'</td>
                    <td style="width: 10%;">'.$row['bom_for'].'</td>
                    <td style="width: 20%;">'.$row['product_code'].'</td>
                    <td style="width: 20%;">'.$row['product_name'].'</td>
                    <td style="width: 20%;">'.$row['output_range'].'</td>
                </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadPlans.pdf', 'I');
        
    }else if ($_GET["type"] == "downloadPlansLog") {
        $_GET['filename'] = 'downloadPlans'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Plans</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                     <td style="width:10%;">Plan No.</td>
                     <td style="width: 10%;">Batch No.</td>
                    <td style="width: 10%;">Product Type</td>
                    <td style="width: 10%;">Product code</td>
                    <td style="width: 15%;">Product Name</td>
                    <td style="width: 10%;">Grade</td>
                    <td style="width: 10%;">Batch Size</td>
                    <td style="width: 10%;">Stability</td>
                     <td style="width: 10%;">Reason</td>
                </tr>
            </thead>';
             $i=1;
       $sql ="SELECT  b.product_type,a.id,a.material_type,a.batch_number,a.work_order_no,a.entry_by as palnned_by,a.approved_by,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,dispensing_status,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.grade,b.pack_size,b.pack_unit FROM mfg_work_order_hdr a 
             JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where    a.plant_id ='".$_GET["plant_id"]."'
             and a.status='approved'  and a.material_type like '%".$_GET["material_type"]."%' ORDER by a.id DESC "; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<tr nobr="true">
                    <td style="width: 5%;">'.$i.'</td>
                   <td style="width: 10%;">'.$row['plan_no'].'</td>
                    <td style="width: 10%;">'.$row['batch_number'].'</td>
                    <td style="width: 10%;">'.$row['product_type'].'</td>
                    <td style="width: 10%;">'.$row['product_code'].'</td>
                    <td style="width: 15%;">'.$row['product_name'].'</td>
                    <td style="width: 10%;">'.$row['grade'].'</td>
                    <td style="width: 10%;">'.$row['batch_size'].'</td>
                    <td style="width: 10%;">'.$row['stability'].'</td>
                     <td style="width: 10%;">'.$row['stability_reason'].'</td>
                </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadPlans.pdf', 'I');
      
        
        
        
    }
    else if ($_GET["type"] == "downloadReport") { 
	   
	    $_GET['filename'] = ' ';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
		$sql ="SELECT					d.LglNm as client_name,
	                       
	                       	a.raw_qc_sample_qty_by,
                            a.raw_qc_sample_qty__date,
                            a.raw_qc_check_by,
                            a.raw_qc_check_date,
                            a.raw_qc_approve_by,
                            a.raw_qc_approve_date,
                            a.pm_receive_by,
							d.id,
							a.raw_qc_intimation_raised_date,
							a.rm_dispensing_lc_by,
							a.bmr_start_by,
							a.raw_qc_intimation_raised_by,
							a.rm_dispensing_lc_date,
							a.rm_disp_completed_by,
							a.rm_received_by,
							a.rm_received_date,
							a.rm_disp_completed_date,
                        s.total_qty,a.id, a.fg_intimation_raised_by,a.fg_intimation_raised_date,
                        a.fg_intimation_receive_by,a.fg_intimation_receive_date,
                        a.batch_number, p.dosage_form,a.bmr_no, a.fg_sampling_intimation,
                        a.work_order_no,a.rm_qa_dislc_date ,
                        a.approved_by,p.generic_name,
                        a.lod_status,
                        a.calculation_type,
                        a.batch_commence_date,
                        a.batch_plan_id,
                        a.sampling_intimation,
                        a.stage_checked_by,
                        a.tr_to_packing_dept_by,
                        a.batch_complete_date,
                        a.stability,
                        a.stability_reason,
                        a.process_validation,
                        a.qa_person,
                        a.qa_date,
                        a.no_of_lots,
                        uf.min_per AS min_yeild,
                        uf.max_per AS max_yeild,
                        a.bmr_start_by,
                        a.approved_by,
                        a.qa_date,
                        b.plan_no,
                        b.bfr_no,
                        b.mfr_no,
                        b.product_code,
                        b.batch_size,
                        p.product_name,
                        p.product_type,
                        p.grade,
                        b.pack_size,
                        d.TrdNm,
                        b.pack_unit,
                        a.dispense_request_sent_by,
                        a.dispense_request_sent_on,
                        a.dispensing_status,
                       a.rm_qa_dislc_by,
                        a.qa_person,
                        rm_disp_completed_by,
                        a.pm_qa_dislc_status AS lc_status,
                        a.pm_qa_dislc_by AS lc_by,
                        a.pm_qa_dislc_date AS lc_date,
                        a.pm_disp_completed_by,
                        p.manufactured_for,
                        p.shelf_life,
                        a.bmr_completed_by,
                        a.qc_sample_qty_packing,
                        a.qc_sample_qty_by,
                        l.entry_date,a.qc_intimation_raised_by,
                        a.mfg_date,a.exp_date
                    FROM
                        mfg_work_order_hdr a
                    JOIN batch_planning b ON
                        a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                    JOIN product p ON
                        b.product_code = p.product_code AND b.plant_id = p.plant_id
                           left join client d on 
                    d.LglNm=p.manufactured_for
                    LEFT JOIN unitformula uf ON
                        uf.mfr_no = b.mfr_no AND uf.plant_id = b.plant_id
                    LEFT JOIN samplingfg s ON
                        b.product_code = s.product_code
                    left join lineclearance l ON
                           a.id=l.work_order_id 
                    
             where  a.plant_id ='".$_GET["plant_id"]."'  and a.bmr_status='Complete' and a.bmr_no='".$_GET["bmr_no"]."'";
            
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $datetimeValue = $row['rm_disp_completed_date'];
        $rm_compdate = date('Y-m-d', strtotime($datetimeValue)); // Format the date part
        $rm_comptime = date('H:i:s', strtotime($datetimeValue)); // Format the time part
                  $datetimeValue = $row['rm_dispensing_lc_date'];
        $rm_strtdate = date('Y-m-d', strtotime($datetimeValue)); // Format the date part
        $rm_strttime = date('H:i:s', strtotime($datetimeValue)); // Format the time part
                  $datetimeValuefg_intimation_raised_date = $row['fg_intimation_raised_date'];
        $fg_intimation_raised_date = date('Y-m-d', strtotime($datetimeValuefg_intimation_raised_date)); // Format the date part
        $fg_intimation_raised_time = date('H:i:s', strtotime($datetimeValuefg_intimation_raised_date)); // Format the time part
           
		$html= "";
		
				$html.= '<table border="1">

    <tr>
    <td style="line-height:30px;width: 140px;border-bottom:none;text-align:center;"> Annexure-01</td>
    <td style="line-height:30px;width: 400px;text-align:center;">BATCH MANUFACTURING RECORD TEMPLATE</td>
</tr>
<div>
</div>
<tr>
    <td style="line-height:30px;width: 540px;border-bottom:none;text-align:left;"> PRODUCT NAME: <b>'.$row['product_name'].'</b></td>
</tr>
<tr>
    <td style="line-height:30px;width: 540px;border-bottom:none;text-align:left;"> GENERIC NAME: <b>'.$row['generic_name'].'</b></td>
</tr>
<tr>';
if ($row['manufactured_for'] != '') {
    $html .= '<td style="line-height:30px;width: 250px;border-bottom:none;text-align:left;"> CUSTOMER NAME: <b>' . $row['manufactured_for'] . '</b></td>';
} else {
    $html .= '<td style="line-height:30px;width: 250px;border-bottom:none;text-align:left;"> CUSTOMER NAME:<b>NA</b></td>';
}

  $html.= '  <td style="line-height:30px;width: 140px;border-bottom:none;text-align:left;"> MFG DATE: <b>' . $row['mfg_date'] . '</b> </td>
    <td style="line-height:30px;width: 150px;border-bottom:none;text-align:left;"> EXPIRY DATE: <b>' . $row['exp_date'] . '</b></td>


</tr>
<tr>
    <td style="line-height:30px;width: 250px;border-bottom:none;text-align:left;"> BATCH SIZE:<b> ' . $row['batch_size'] . '</b>  </td>
    <td style="line-height:30px;width: 290px;border-bottom:none;text-align:left;"> BATCH NO: <b> ' . $row['batch_number'] . '</b> </td>

</tr>

    </table>
    <div>
    </div>
    <tr>
    <td style="line-height:30px;width: 540px;border-bottom:none;text-align:center;"> BATCH MANUFACTURING RECORD
      </td>
    </tr>
    <tr>
    <td style="line-height:30px;width: 540px;border-bottom:none;text-align:center;">
    CONFIDENTIAL–NOT TO BE REPRODUCED WITHOUT PERMISSION  </td>
    </tr>
  
    <table border="1">
    <tr>
    <td style="line-height:30px;width: 290px;border-bottom:none;text-align:left;"> PRODUCT CODE: <b>' . $row['product_code'] . '</b>  </td>
    <td style="line-height:30px;width: 250px;border-bottom:none;text-align:left;"> SHELF LIFE:  <b>' . $row['shelf_life'] . '</b>  </td>
    </tr>
    <tr>
    <td style="line-height:30px;width: 290px;border-bottom:none;text-align:left;"> FORMAT NUMBER FOR BMR:<b>' . $row['bmr_no'] . '</b> </td>
    <td style="line-height:30px;width: 250px;border-bottom:none;text-align:left;"> EFFECTIVE DATE: </td>
    </tr>
    <tr>
    <td style="line-height:30px;width: 290px;border-bottom:none;text-align:left;"> REVISION NUMBER: </td>
    <td style="line-height:30px;width: 250px;border-bottom:none;text-align:left;"> </td>
    </tr>
    <tr>
    <td style="line-height:30px;width: 240px;border-bottom:none;text-align:center;"> Document Issued By
    QA designee Sign/Date
     </td>
    <td style="line-height:30px;width: 300px;border-bottom:none;text-align:center;"> Document Received By
    Production designee Sign/Date
    </td>
    </tr>
    <tr>
    <td style="line-height:30px;width: 240px;border-bottom:none;text-align:center;"> <b>' . $row['qa_person'] . ' </b> - <b>' . $row['qa_date'] . '</b> </td>
    <td style="line-height:30px;width: 300px;border-bottom:none;text-align:center;"> <b>' . $row['dispense_request_sent_by'] . ' </b> - <b>' . $row['dispense_request_sent_on'] . '</b></td>
    </tr>
    <tr>
    <td style="line-height:30px;width: 240px;border-bottom:none;text-align:center;"> BMR Submitted to QA By
    Production designee 
    Sign /Date
     </td>
    <td style="line-height:30px;width: 300px;border-bottom:none;text-align:center;"> BMR Received by QA designee 
    Sign /Date
    </td>
    </tr>
    <tr>
    <td style="line-height:30px;width: 240px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:30px;width: 300px;border-bottom:none;text-align:center;"> </td>
    </tr>
</table>';

$html.='
<br pagebreak="true"/>
<table><tr style="background-color:gray;">
<td style="line-height:30px;width: 540px;border-bottom:none;text-align:left;">1.	GENERAL INSTRUCTIONS:</td>

</tr>
</table>
<table border="1";align="center";>
    <tr>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"> Sr. No. </td>
    <td style="line-height:20px;width: 480px;border-bottom:none;text-align:left;"> Particulars </td>
    </tr>';
    
     $sql1 = "select * from bmr_genral_instruction where work_order_id='".$row["id"]."'";
$result1 = $conn->query($sql1);

if ($result1->num_rows > 0) {
    $i = 1; // Initialize the row counter
    while ($row1 = $result1->fetch_assoc()) {
        $html .= '<tr>
            <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;">' . $i++ . '</td>
            <td style="line-height:20px;width: 480px;border-bottom:none;text-align:left;"> ' . $row1['instructions'] . '</td>
        </tr>
        ';
    }
}
	  $html .= '</table>
	  <br pagebreak="true"/>
<table border="1">
   


<tr>
<td style="line-height:30px;width: 540px;border-bottom:none;text-align:CENTER;">2.LIST OF EQUIPMENTS TO BE USED FOR MANUFACTURING:</td>
</tr>
</table>
<div></div>
<table border="1">
<tr style="background-color:gray;">
     <td style="line-height:15px;width: 60px;border-bottom:none;text-align:center;"rowspan="2"> Sr.NO</td>
    <td style="line-height:15px;width: 90px;border-bottom:none;text-align:center;"rowspan="2"> Name of Equipment </td>
    <td style="line-height:15px;width: 80px;border-bottom:none;text-align:center;"rowspan="2"> Capacity</td>
    <td style="line-height:15px;width: 80px;border-bottom:none;text-align:center;" rowspan="2"> Equipment ID</td>
    <td style="line-height:15px;width: 80px;border-bottom:none;text-align:center;"colspan="2"> Cleaning Time</td>
    <td style="line-height:15px;width: 70px;border-bottom:none;text-align:center;"rowspan="2"> Done By</td>
    <td style="line-height:15px;width: 80px;border-bottom:none;text-align:center;"rowspan="2"> Checked By</td>
</tr>
<tr style="background-color:gray;">
    <td style="line-height:15px;width: 40px;border-bottom:none;text-align:center;"> From</td>
    <td style="line-height:15px;width: 40px;border-bottom:none;text-align:center;"> To</td>
</tr>';
  $sql2 = "SELECT * from bmr_sp_equpments_all_data where work_order_id='".$row["id"]."'";
        $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
              $i = 1; // Initialize the row counter
            while ($row2 = $result2->fetch_assoc()) {
              
         
$html.='<tr>
   <td style="line-height:15px;width: 60px;border-bottom:none;text-align:left;"> ' . $i++ . '</td>
    <td style="line-height:15px;width: 90px;border-bottom:none;text-align:left;"> ' . $row2['equipment_name'] . '</td>
    <td style="line-height:15px;width: 80px;border-bottom:none;text-align:left;">  ' . $row2['capacity'] . ' </td>
    <td style="line-height:15px;width: 80px;border-bottom:none;text-align:left;font-size:9px;" > ' . $row2['equipment_code'] . '</td>
    <td style="line-height:15px;width: 40px;border-bottom:none;text-align:left;font-size:08px;"> ' . $row2['clean_from'] . '</td>
    <td style="line-height:15px;width: 40px;border-bottom:none;text-align:left;font-size:08px;"> ' . $row2['clean_to'] . '</td>
    <td style="line-height:15px;width: 70px;border-bottom:none;text-align:left;"> ' . $row2['entry_by'] . '</td>
    <td style="line-height:15px;width: 80px;border-bottom:none;text-align:left;"> ' . $row2['approve_by'] . '</td>
</tr>';
   }
        }
$html.='</table>
 <br pagebreak="true"/>
<table border="1">
   
<tr style="background-color:gray;">
<td style="line-height:30px;width: 540px;border-bottom:none;text-align:center;">4.  LINE CLEARANCE FOR ALL STAGES:
Reference SOP No.: 
</td>
</tr>
</table>
<table border="1">
<tr>
   <td style="line-height:15px;width: 57px;border-bottom:none;text-align:center;"> Date</td>
   <td style="line-height:15px;width: 55px;border-bottom:none;text-align:center;"> Time</td>
    <td style="line-height:15px;width: 57px;border-bottom:none;text-align:center;"> Stage</td>
    <td style="line-height:15px;width: 53px;border-bottom:none;text-align:center;" > Previous Product Name</td>
    <td style="line-height:15px;width: 40px;border-bottom:none;text-align:center;"> Batch No.</td>
    <td style="line-height:15px;width: 35px;border-bottom:none;text-align:center;"> Temp.(°C)</td>
    <td style="line-height:15px;width: 50px;border-bottom:none;text-align:center;">Humidity(%RH)</td>
    <td style="line-height:15px;width: 70px;border-bottom:none;text-align:center;">Area Clean By</td>
    <td style="line-height:15px;width: 55px;border-bottom:none;text-align:center;"> Checked By</td>
    <td style="line-height:15px;width: 68px;border-bottom:none;text-align:center;"> Line Clearance Given By</td>
</tr>';
 $sql3 = "SELECT * FROM bmr_all_stages  where work_order_id='".$row["id"]."'";
        $result3 = $conn->query($sql3);
        if ($result3->num_rows > 0) {
              $i = 1; // Initialize the row counter
            while ($row3 = $result3->fetch_assoc()) {
    	

$html.='<tr>
   <td style="line-height:15px;width: 57px;border-bottom:none;text-align:center;" > ' . $row3['date'] . ' </td>
    <td style="line-height:15px;width: 55px;border-bottom:none;text-align:center;"> ' . $row3['time'] . ' </td>
    <td style="line-height:15px;width: 57px;border-bottom:none;text-align:center;"> ' . $row3['stage'] . ' </td>
    <td style="line-height:15px;width: 53px;border-bottom:none;text-align:center;"> ' . $row3['previous_product'] . ' </td>
    <td style="line-height:15px;width: 40px;border-bottom:none;text-align:center;"> ' . $row3['batch_number'] . ' </td>
    <td style="line-height:15px;width: 35px;border-bottom:none;text-align:center;"> ' . $row3['temp'] . ' </td>
    <td style="line-height:15px;width: 50px;border-bottom:none;text-align:center;"> ' . $row3['Humidity'] . ' </td>
    <td style="line-height:15px;width: 70px;border-bottom:none;text-align:center;"> ' . $row3['Area_Clean_By'] . ' </td>
    <td style="line-height:15px;width: 55px;border-bottom:none;text-align:center;"> ' . $row3['Checked_by'] . ' </td>
    <td style="line-height:15px;width: 68px;border-bottom:none;text-align:center;"> ' . $row3['Line_clearance_given_by'] . ' </td>
</tr>';
	}
    	}
$html.='</table>

<tr>
<td style="line-height:30px;width: 540px;border-bottom:none;text-align:center;">5. WAREHOUSE DISPENSING:
Line Clearance Instruction 
</td>
</tr>';
$html .= '<table border="1">
    <tr>
        <td style="line-height:20px;width: 50px;border-bottom:none;text-align:center;"> Sr. No.</td>
        <td style="line-height:20px;width: 410px;border-bottom:none;text-align:center;"> Description</td>
        <td style="line-height:20px;width: 80px;border-bottom:none;text-align:center;"> Remark</td>
    </tr>';

 $sql4 = "SELECT checkpoints from lineclearance where work_order_id='" . $row["id"] . "'";
$result4 = $conn->query($sql4);

if ($result4->num_rows > 0) {
    $json_obj = $result4->fetch_assoc()['checkpoints'];
    $array = json_decode($json_obj, true);
    $J = 1; // Initialize the row counter
    
    foreach ($array as $values) {
        $checkpoint = $values['checkpoint'];
        $remarkss = $values['remarkss'];
        
        $html .= '<tr>
            <td style="line-height:20px;width: 50px;border-bottom:none;text-align:center;"> ' . $J++ . '</td>
            <td style="line-height:20px;width: 410px;border-bottom:none;text-align:left;"> ' . $checkpoint . '</td>
            <td style="line-height:20px;width: 80px;border-bottom:none;text-align:center;"> ' . $remarkss . ' </td>
        </tr>';
    }
}

$html .= '</table>';
 $sql5 = "SELECT	 a.batch_number,b.product_code,b.batch_size,p.product_name, a.qa_person, rm_disp_completed_by FROM
                        mfg_work_order_hdr a
                    JOIN batch_planning b ON
                        a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                    JOIN product p ON
                        b.product_code = p.product_code AND b.plant_id = p.plant_id
                           left join lineclearance l ON
                           a.id=l.work_order_id
             where  a.plant_id ='".$_GET["plant_id"]."' and  a.rm_qa_dislc_date < '" . $row["rm_qa_dislc_date"] . "' ORDER by a.rm_qa_dislc_date desc limit 1";
        $result5 = $conn->query($sql5);
        $datetimeValue = $row['rm_qa_dislc_date'];
        $date = date('Y-m-d', strtotime($datetimeValue)); // Format the date part
        $time = date('H:i:s', strtotime($datetimeValue)); // Format the time part

        if ($result5->num_rows > 0) {
              $i = 1; // Initialize the row counter
            while ($row5 = $result5->fetch_assoc()) {
$html .= '<table>
<tr style="background-color:gray;">
<td style="line-height:30px;width: 540px;border-bottom:none;text-align:center;">5.1 Dispensing of Raw Material
Line Clearance checklist for RM dispensing.
</td>
</tr>
</table>
<div></div>
<table border="1">
    <tr>
     <td style="line-height:20px;width: 290px;border-bottom:none;text-align:left;"> Product: ' . $row['product_name'] . '  </td>
    <td style="line-height:20px;width: 250px;border-bottom:none;text-align:left;"> Batch No.: ' . $row['batch_number'] . ' </td>
    </tr>
    <tr>
     <td style="line-height:20px;width: 290px;border-bottom:none;text-align:left;"> Previous Product: ' . $row5['product_name'] . '</td>
    <td style="line-height:20px;width: 250px;border-bottom:none;text-align:left;"> Batch Size: ' . $row['batch_size'] . '</td>
    </tr>
    <tr>
     <td style="line-height:20px;width: 290px;border-bottom:none;text-align:left;"> Area Cleaning Done By:</td>
    <td style="line-height:20px;width: 250px;border-bottom:none;text-align:left;"> Checked By: ' . $row['rm_qa_dislc_by'] . '</td>
    </tr>
    <tr>
     <td style="line-height:20px;width: 290px;border-bottom:none;text-align:left;"> Line Clearance Given By QA:</td>
    <td style="line-height:20px;width: 250px;border-bottom:none;text-align:left;"> Date: ' . $date. '</td>
    </tr>
    <tr>
     <td style="line-height:20px;width: 290px;border-bottom:none;text-align:left;"> Line Clearance SOP Ref No: </td>
    <td style="line-height:20px;width: 250px;border-bottom:none;text-align:left;"> Time:' . $time. '</td>
    </tr>
</table>';

            }
        }
        $html.='
        
        
         <br pagebreak="true"/>

<table border="1">
<tr style="background-color:gray;">
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"> Sr.No</td>
    <td style="line-height:20px;width: 200px;text-align:center;">CheckList</td>
    <td style="line-height:20px;width: 100px;border-bottom:none;text-align:center;">Ok/Not Ok/NA </td>
    <td style="line-height:20px;width: 90px;text-align:center;">Checked By</td>
    <td style="line-height:20px;width: 90px;text-align:center;">Verified By</td>
</tr>';
$sql4 = "SELECT qc_checkpoints from lineclearance where work_order_id='" . $row["id"] . "'";
$result4 = $conn->query($sql4);

if ($result4->num_rows > 0) {
    $json_obj = $result4->fetch_assoc()['qc_checkpoints'];
    $array = json_decode($json_obj, true);
    $J = 1; // Initialize the row counter
    
    foreach ($array as $values) {
        $checkpoint = $values['checkpoint'];
        $remarkss = $values['remarkss'];
  $html.='<tr>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;">' . $J++. '</td>
    <td style="line-height:20px;width: 200px;text-align:center;">' . $checkpoint. ' </td>
    <td style="line-height:20px;width: 100px;border-bottom:none;text-align:center;">' . $remarkss. '</td>
    <td style="line-height:20px;width: 90px;text-align:center;">' . $row['rm_qa_dislc_by'] . '</td>
    <td style="line-height:20px;width: 90px;text-align:center;"> ' . $row['rm_qa_dislc_by'] . '</td>
</tr>';
}
}
 $html.='</table>

 <table>
<tr>
    <td style="line-height:20px;width: 540px;border-bottom:none;text-align:left;"> <b> Dispensing Record (BMR COPY) </b></td>
</tr>
 </table>

 <div></div>

<table border="1">
<tr style="background-color:gray;">
    <td style="line-height:20px;width: 540px;border-bottom:none;text-align:center;"> <b> Dispensing </b></td>
</tr>
<tr>
    <td style="line-height:20px;width: 135px;text-align:center;">Start Date </td>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:center;">' . $rm_strtdate . '</td>
    <td style="line-height:20px;width: 135px;text-align:center;">Start Time</td>
    <td style="line-height:20px;width: 135px;text-align:center;"> ' . $rm_strttime . '</td>
</tr>
<tr>
    <td style="line-height:20px;width: 135px;text-align:center;">End Date </td>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:center;">' . $rm_compdate . '</td>
    <td style="line-height:20px;width: 135px;text-align:center;">End Time</td>
    <td style="line-height:20px;width: 135px;text-align:center;"> ' . $rm_comptime . '</td>
</tr>
 </table>
 <br pagebreak="true"/>
<table border="1">
<tr style="background-color:gray;">
    <td style="line-height:20px;width: 540px;border-bottom:none;text-align:center;"> <b> Material Dispensing Record: </b></td>
</tr>
 <tr>
        <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;" rowspan="2"> RM CODE</td>
        <td style="line-height:20px;width: 54px;text-align:center;"  rowspan="2">Material Name </td>
        <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;"  rowspan="2">Grade</td>
        <td style="line-height:20px;width: 54px;text-align:center;"  rowspan="2">LOT No.</td>
        <td style="line-height:20px;width: 54px;text-align:center;"  rowspan="2"> Batch Qty </td>
        <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;"  rowspan="2"> AR. No.</td>
        <td style="line-height:20px;width: 54px;text-align:center;" >Weight in KG</td>
        <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;"  rowspan="2">Done By</td>
        <td style="line-height:20px;width: 54px;text-align:center;"  rowspan="2">Production Check By</td>
        <td style="line-height:20px;width: 54px;text-align:center;"  rowspan="2"> QA Check By  </td>
 </tr>
 <tr>
 <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;" > Net Wt</td>
 </tr>';
    $sql7 = "select a.*,b.avbl_stock  from (SELECT a.*,b.grade,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking,dd.net_total,dd.entry_by as dd_entry,dd.prod_approved_by,dd.qa_approved_by  FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 ( SELECT material_code,balance_qty as avbl_stock from vw_stock_summary
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 ) as b on a.material_code = b.material_code and a.material_type='RM' ";
         $result7 = $conn->query($sql7);
        if ($result7->num_rows > 0) {
         
              $i = 1; // Initialize the row counter
            while ($row7 = $result7->fetch_assoc()) {
                    $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row7['grade']."')";
                     $resQ = $conn->query($q);
                      $prodLatest = $resQ->fetch_assoc(); 
                 
                  $row7['gradeName'] = $prodLatest['gradeName'];
                 $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(a.undertest_qty,0)-IFNULL(b.issued_qty,0)) as balance_qty,
                                floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers, (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty
                                , case when 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                                case when 'LOD Basis' ='Assay Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty from
                            (SELECT IFNULL(SUM(qty), 0) as qty, grn_no,ar_no ,undertest_qty,pack_size ,batch_no,containers,lod_per,assay FROM stock_book
                        WHERE material_code='".$row7["material_code"]."' AND status='Approved' GROUP BY grn_no,ar_no,pack_size,undertest_qty, batch_no,containers,lod_per,assay) a
                        left join (SELECT grn_no,ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by grn_no,ar_no)b on TRIM(a.ar_no) = TRIM(b.ar_no)"; 
             $result2 = $conn->query($sql2);
              $row2 = $result2->fetch_assoc();{
                  
 $html.='<tr>
 <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;" >'.$row7['material_code'].'</td>
 <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;" >'.$row7['material_name'].'</td>
 <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;" >'.$row7['gradeName'].'</td>
 <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;" >'.$row7['lot_no'].'</td>
 <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;" >'.$row7['batch_qty'].'</td>
 <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;" >'.$row2['ar_no'].'</td>
 <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;" >'.$row7['net_total'].'</td>
 <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;" >'.$row7['dd_entry'].'</td>
 <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;" >'.$row7['prod_approved_by'].'</td>
 <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;" >'.$row7['qa_approved_by'].'</td>
 </tr>';
            
              }
            }
        }
 $html.=' </table>
 <table border="1">
<tr style="background-color:gray;">
     <td style="line-height:20px;width: 540px;border-bottom:none;text-align:center;"> <b>Dispensed Material Transfer Details</b></td>
</tr>
<tr>
     <td style="line-height:20px;width: 180px;border-bottom:none;text-align:center;">Transferred By Sign/Date </td>
     <td style="line-height:20px;width: 180px;border-bottom:none;text-align:center;">Received By Sign/ Date</td>
     <td style="line-height:20px;width: 180px;border-bottom:none;text-align:center;">Checked By Sign/ Date </td>
</tr>
<tr>
     <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">  '.$row['rm_disp_completed_by'].'/ '.$row['rm_disp_completed_date'].'</td>
     <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">'.$row['rm_received_by'].'/ '.$row['rm_received_date'].'</td>
     <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">'.$row['rm_received_by'].'/ '.$row['rm_received_date'].'</td>
</tr>
</table>'; $sql5 = "SELECT	 a.batch_number,b.product_code,b.batch_size,p.product_name, a.qa_person, rm_disp_completed_by FROM
                        mfg_work_order_hdr a
                    JOIN batch_planning b ON
                        a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                    JOIN product p ON
                        b.product_code = p.product_code AND b.plant_id = p.plant_id
                           left join lineclearance l ON
                           a.id=l.work_order_id
             where  a.plant_id ='".$_GET["plant_id"]."' and  a.rm_qa_dislc_date < '" . $row["rm_qa_dislc_date"] . "' ORDER by a.rm_qa_dislc_date desc limit 1";
        $result5 = $conn->query($sql5);
        $datetimeValue = $row['rm_qa_dislc_date'];
        $date = date('Y-m-d', strtotime($datetimeValue)); // Format the date part
        $time = date('H:i:s', strtotime($datetimeValue)); // Format the time part

        if ($result5->num_rows > 0) {
              $i = 1; // Initialize the row counter
            while ($row5 = $result5->fetch_assoc()) {

// $html.='<table>
// <tr>
//      <td style="line-height:20px;width: 540px;border-bottom:none;text-align:center;"> <b>5.2 Line Clearance Checklist for processing (Weighing/ Batching/ SFG)</b></td>
// </tr>
// </table>
// <div></div>

// <table border="1">
// <tr>
//      <td style="line-height:30px;width: 270px;border-bottom:none;text-align:left;"> <b>Previous Product: ' . $row5['product_name'] . '</b></td>
//      <td style="line-height:30px;width: 270px;border-bottom:none;text-align:left;"> <b>Batch No : ' . $row5['batch_number'] . '</b></td>
// </tr>
// <tr>
//      <td style="line-height:30px;width: 270px;border-bottom:none;text-align:left;"> <b>Area Cleaning Done By:</b></td>
//      <td style="line-height:30px;width: 270px;border-bottom:none;text-align:left;"> <b>Checked By:</b></td>
// </tr>
// <tr>
//      <td style="line-height:30px;width: 270px;border-bottom:none;text-align:left;"> <b>Line Clearance Given By IPQC:</b></td>
//      <td style="line-height:30px;width: 270px;border-bottom:none;text-align:left;"> <b>Date:</b></td>
// </tr>
// <tr>
//      <td style="line-height:30px;width: 270px;border-bottom:none;text-align:left;"> <b>Line Clearance SOP Ref No : SIPL/SOP/QA/05</b></td>
//      <td style="line-height:30px;width: 270px;border-bottom:none;text-align:left;"> <b>Time :</b></td>
// </tr>
// </table>
// ';
$html .= '
<br pagebreak="true">
<table>
<tr>
<td style="line-height:30px;width: 540px;border-bottom:none;text-align:center;">5.2 Line Clearance Checklist for processing (Weighing/ Batching/ SFG)
</td>
</tr>
</table>
<div></div>
<table border="1">
    <tr>
     <td style="line-height:20px;width: 290px;border-bottom:none;text-align:left;"> Product: ' . $row['product_name'] . '  </td>
    <td style="line-height:20px;width: 250px;border-bottom:none;text-align:left;"> Batch No.: ' . $row['batch_number'] . ' </td>
    </tr>
    <tr>
     <td style="line-height:20px;width: 290px;border-bottom:none;text-align:left;"> Previous Product: ' . $row5['product_name'] . '</td>
    <td style="line-height:20px;width: 250px;border-bottom:none;text-align:left;"> Batch Size: ' . $row['batch_size'] . '</td>
    </tr>
    <tr>
     <td style="line-height:20px;width: 290px;border-bottom:none;text-align:left;"> Area Cleaning Done By:</td>
    <td style="line-height:20px;width: 250px;border-bottom:none;text-align:left;"> Checked By: ' . $row['rm_qa_dislc_by'] . '</td>
    </tr>
    <tr>
     <td style="line-height:20px;width: 290px;border-bottom:none;text-align:left;"> Line Clearance Given By QA:</td>
    <td style="line-height:20px;width: 250px;border-bottom:none;text-align:left;"> Date: ' . $date. '</td>
    </tr>
    <tr>
     <td style="line-height:20px;width: 290px;border-bottom:none;text-align:left;"> Line Clearance SOP Ref No: </td>
    <td style="line-height:20px;width: 250px;border-bottom:none;text-align:left;"> Time:' . $time. '</td>
    </tr>
</table>';
}
}
$html.='

<div></div>

<table border="1">
<tr style="background-color:gray;">
<td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"> Sr.No</td>
<td style="line-height:20px;width: 200px;text-align:center;">CheckList</td>
<td style="line-height:20px;width: 100px;border-bottom:none;text-align:center;">Result </td>
<td style="line-height:20px;width: 90px;text-align:center;">Checked By</td>
<td style="line-height:20px;width: 90px;text-align:center;">Verified By</td>
</tr>';
  $sql20 = "SELECT * from bmr_lc_process_chklist where work_order_id='".$row["id"]."'";
        $result20 = $conn->query($sql20);
       
        if ($result20->num_rows > 0) {
              $i = 1; // Initialize the row counter
            while ($row20 = $result20->fetch_assoc()) {
                $row20['parameter'] = htmlspecialchars($row20['parameter']);
$html.='<tr >
<td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;">'.$i++.'</td>
<td style="line-height:20px;width: 200px;text-align:center;">' . $row20['parameter'] . ' </td>
<td style="line-height:20px;width: 100px;border-bottom:none;text-align:center;">' . $row20['result'] . '</td>
<td style="line-height:20px;width: 90px;text-align:center;">' . $row['bmr_start_by'] . '</td>
<td style="line-height:20px;width: 90px;text-align:center;">' . $row['raw_qc_intimation_raised_by'] . ' </td>
</tr>';
            }
        }
$html.='</table>
<br pagebreak="true"/>
<table border="1">
     <tr>
             <td style="width: 540px; font-weight: bold; font-size: 11px; text-align: center;">
                 SAIPRO INDUSTRIES PVT.LTD.<br>
                 GatNo.286,287,A/P–Kasaramboli,Tal-Mulshi,Dist-Pune412115.<BR>
                 DAILYOPRP&CCPCHECKLIST(QUALITYBMR-SHEET1)
 
             </td>
     </tr>';$sql8 = "select * from sp_bmr where work_order_id='".$row["id"]."'";
    	$result8 = $conn->query($sql8);
    	if($result8->num_rows > 0){
    		while($row8 = $result8->fetch_assoc()){
//  $html.='    <tr style="background-color:yellow;">
//         <td style="width: 50px; text-align: center; font-size: 10px;  font-weight: bold;" >DATE</td>
//         <td style="width: 180px; text-align: center; font-size: 10px;  font-weight: bold;">CONTROLTEMP&HUMIDITY(OPRP1,CCP1) '.$row8['oprp1_room'].'</td>
//         <td style="width: 100px; text-align: center; font-size: 10px; font-weight: bold;" >Time</td>
//         <td style="width: 120px; text-align: center; font-size: 10px; font-weight: bold;" >Obserbvations</td>
//         <td style="width: 90px; text-align: center; font-size: 10px; font-weight: bold;" >Remarks</td>
//     </tr>';
 $html.='    <tr style="background-color:yellow;">
        <td style="width: 230px; text-align: center; font-size: 10px;  font-weight: bold;">CONTROLTEMP&HUMIDITY(OPRP1,CCP1) '.$row8['oprp1_room'].'</td>
        <td style="width: 170px; text-align: center; font-size: 10px; font-weight: bold;" >Obserbvations</td>
        <td style="width: 140px; text-align: center; font-size: 10px; font-weight: bold;" >Remarks</td>
    </tr>';
     $sql9 = "SELECT * FROM sp_bmr_dtl WHERE sp_bmr_id='".$row8["id"]."'";
                        
                        $result9 = $conn->query($sql9);
                        if ($result9->num_rows > 0) {
                          while ($row9 = $result9->fetch_assoc()) {
// $html.='    <tr>
//         <td style="width: 160px; text-align: center; font-size: 10px" >Temp(25degree+/-2)</td>
//         <td style="width: 110px; text-align: center; font-size: 9px">'.$row9['temp'].'</td>
//         <td style="width: 100px; text-align: center; font-size: 9px"  rowspan="2"></td>
//         <td style="width: 120px; text-align: center; font-size: 9px"  rowspan="2">'.$row8['oprp1_observation'].'</td>
//         <td style="width: 90px; text-align: center; font-size: 9px" rowspan="2" >'.$row8['oprp1_remark'].'</td>

//     </tr>
//      <tr>
//         <td style="width: 90px; text-align: center; font-size: 10px">Humidity( < 55)</td>
//         <td style="width: 90px; text-align: center; font-size: 9px">'.$row9['humidity'].'</td>
//     </tr>';
$html.='   <tr>
        <td style="width: 160px; text-align: center; font-size: 10px" >Temp(25degree+/-2)</td>
        <td style="width: 70px; text-align: center; font-size: 9px">'.$row9['temp'].'</td>
        <td style="width: 170px; text-align: center; font-size: 9px"  rowspan="2">'.$row8['oprp1_observation'].'</td>
        <td style="width: 140px; text-align: center; font-size: 9px" rowspan="2" >'.$row8['oprp1_remark'].'</td>

    </tr>
     <tr>
        <td style="width: 160px; text-align: center; font-size: 10px">Humidity( < 55)</td>
        <td style="width: 70px; text-align: center; font-size: 9px">'.$row9['humidity'].'</td>
    </tr>';
                          }
                        }
    		}
    	}
$html.=' 

    </table>';
    	  $sql10 = "select * from sp_bmr_oprpccp2 where work_order_id='".$row["id"]."'";
    	$result10 = $conn->query($sql10);
    	if($result10->num_rows > 0){
    		while($row10 = $result10->fetch_assoc()){
$html.='  <table border="1">
 
<tr style="background-color: maroon; color: white;font-weight: bold;">
     
     <th colspan="6" style="background-color:yellow;color:black ;width: 540px;text-align:center;">'.$row10['blender'].'+'.$row10['sifter'].' ('.$row10['checkpoint'].')
    </th>
    </tr>     
    <tr style="background-color:rgb(236, 236, 162) ;font-weight:bolder;">
   <td style="width: 50px; text-align: center; font-size: 9px"  ></td>
<td style="width: 112.5px; text-align: center; font-size: 10px" >Sieves</td>
<td style="width: 112.5px; text-align: center; font-size: 9px">Mesh size</td>
<td style="width: 122.5px; text-align: center; font-size: 9px" >Observation</td>
<td style="width: 142px; text-align: center; font-size: 9px" >Remark</td>
                                              
    </tr>   ';        $sql1 = "SELECT * FROM sp_bmr_oprpccp2_dtl WHERE sp_bmr_oprpccp2='".$row10["id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                          while ($row1 = $result1->fetch_assoc()) {       
    
 $html.='    <tr >
       <td style="width: 50px; text-align: center; font-size: 9px"  ></td>
<td style="width: 112.5px; text-align: center; font-size: 10px" >'.$row1['sieves'].'</td>
<td style="width: 112.5px; text-align: center; font-size: 9px">'.$row1['mesh_size'].'</td>
<td style="width: 122.5px; text-align: center; font-size: 9px" >'.$row1['observation'].'</td>
<td style="width: 142px; text-align: center; font-size: 9px" >'.$row1['remark'].'</td>


    </tr>';
                          }
                            
                        }
   
  $html.='  </table>
 ';
}
}
$html.='<br pagebreak="true"/>
<table>
<tr>
    <td style="line-height:30px;width: 540px;border-bottom:none;text-align:left;"> <b>SIFTING </b></td>
</tr>
</table>
<div></div>';
 $sql11 = "select * from sifting where work_order_id='".$row["id"]."'";
    	$result11 = $conn->query($sql11);
    	if($result11->num_rows > 0){
    		while($row11 = $result11->fetch_assoc()){
$html.='<table>
<tr>
    <td style="line-height:30px;width: 540px;border-bottom:none;text-align:left;"> <b>Equipment No: '.$row11['equipment'].'</b></td>
</tr>
</table>
<table border="1">
<tr>
        <td style="width: 90px; border-bottom: none; text-align: center;" rowspan="2"><b>Material</b></td>
        <td style="width: 90px; border-bottom: none; text-align: center;" rowspan="2"><b>Sieve Size</b></td>
        <td style="width: 90px; border-bottom: none; text-align: center;" colspan="2"><b>Time</b></td>
        <td style="width: 90px; border-bottom: none; text-align: center;" colspan="2"><b>Sieve Integrity</b></td>
        <td style="width: 90px; border-bottom: none; text-align: center;" rowspan="2"><b>Done By</b></td>
        <td style="width: 90px; border-bottom: none; text-align: center;" rowspan="2"><b>Checked By</b></td>
    </tr>
    <tr>
        <td style="width: 45px; border-bottom: none; text-align: center;"><b>Start Time</b></td>
        <td style="width: 45px; border-bottom: none; text-align: center;"><b>End Time</b></td>
        <td style="width: 45px; border-bottom: none; text-align: center; font-size: 11px;"><b>Before</b></td>
        <td style="width: 45px; border-bottom: none; text-align: center; font-size: 11px;"><b>After</b></td>
    </tr>';
      $sql1 = "SELECT * FROM sifting_dtl WHERE sifting_id='".$row11["id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                          while ($row1 = $result1->fetch_assoc()) {
 $html.='<tr>
        <td style="width: 90px; border-bottom: none; text-align: center;" ><b>'.$row1['material'].'</b></td>
        <td style="width: 90px; border-bottom: none; text-align: center;" ><b>'.$row1['sieve_size'].'</b></td>
        <td style="width: 45px; border-bottom: none; text-align: center;" ><b>'.$row1['start_time'].'</b></td>
        <td style="width: 45px; border-bottom: none; text-align: center;" ><b>'.$row1['end_time'].'</b></td>
        <td style="width: 45px; border-bottom: none; text-align: center;" ><b>'.$row1['sieve_before'].'</b></td>
        <td style="width: 45px; border-bottom: none; text-align: center;" ><b>'.$row1['sieve_after'].'</b></td>
        <td style="width: 90px; border-bottom: none; text-align: center;" ><b>'.$row['bmr_start_by'].'</b></td>
        <td style="width: 90px; border-bottom: none; text-align: center;" ><b>'.$row['raw_qc_intimation_raised_by'].'</b></td>
    </tr>';  
                          }
                        }
$html.='</table>

';
}
}

$html.='<br pagebreak="true"/><table>
 
                <tr>
                    <td style="width: 540px; font-size: 12; font-weight: bold;text-allign-left">6. Manufacturing Procedure / Operations:</td>
                  
                </tr>
                <br>
                <br>
             <tr>
                    <td style="width: 540px; font-size: 12; font-weight: bold;text-allign-left"> B)  BLENDING  :  ---</td>
                  
                </tr>
                <div>
                </div>
             <tr>
                    <td style="width: 540px; font-size: 12; font-weight: bold;text-allign-left"> 1) Check the material in the Blending room & Unload in the Blender.</td>
                  
                </tr>
                <br>
             <tr>
                    <td style="width: 540px; font-size: 12; font-weight: bold;text-allign-left"> 2) Mix the content by starting the blender for 45 mins..</td>
                  
                </tr>
                <br>
             <tr>
                    <td style="width: 540px; font-size: 12; font-weight: bold;text-allign-left"> 3) Mix the content Clockwise & Anti-clockwise for every 15 min..</td>
                  
                </tr>
                <br>
             <tr>
                    <td style="width: 540px; font-size: 12; font-weight: bold;text-allign-left"> 4) Note: after uniform mixing of blend, transfer material to  vibro sifter</td>
                  
                </tr>
                <br>
            </table>';
  $html.='<div style="width: 540px;">
    <table border="1"; style="table-layout: auto;">
        <tr style="font-weight: bold;">
            <th style=""><b>Blending Details Equipment</b></th>';
            	  $sql12 = "SELECT * FROM sp_bmr_blender where work_order_id='".$row["id"]."' ORDER BY Processing ASC";
    	$result12 = $conn->query($sql12);
    	if($result12->num_rows > 0){
    		while($row12 = $result12->fetch_assoc()){
   $html.='           <th >'.$row12['blender'].'</th>';
    		}
    	}
     $html.='        </tr>
        <tr style="font-weight: bold;">
            <th style=""><b>Processing</b></th>';
            	  $sql12 = "SELECT * FROM sp_bmr_blender where work_order_id='".$row["id"]."' ORDER BY Processing ASC";
    	$result12 = $conn->query($sql12);
    	if($result12->num_rows > 0){
    		while($row12 = $result12->fetch_assoc()){
   $html.='           <th >'.$row12['Processing'].'</th>';
    		}
    	}
     $html.='        </tr>
        <tr style="font-weight: bold;">
            <th style=""><b>blend Start Time</b></th>';
            	  $sql12 = "SELECT * FROM sp_bmr_blender where work_order_id='".$row["id"]."' ORDER BY Processing ASC";
    	$result12 = $conn->query($sql12);
    	if($result12->num_rows > 0){
    		while($row12 = $result12->fetch_assoc()){
   $html.='           <th >'.$row12['blend_start_time'].'</th>';
    		}
    	}
     $html.='        </tr>
        <tr style="font-weight: bold;">
            <th style=""><b>blend End Time</b></th>';
            	  $sql12 = "SELECT * FROM sp_bmr_blender where work_order_id='".$row["id"]."' ORDER BY Processing ASC";
    	$result12 = $conn->query($sql12);
    	if($result12->num_rows > 0){
    		while($row12 = $result12->fetch_assoc()){
   $html.='           <th >'.$row12['blend_end_time'].'</th>';
    		}
    	}
     $html.='        </tr>
       
    </table>
    
    
    
    

';
  $html.='
    <table border="1"; style="table-layout: auto;">
        <tr style="font-weight: bold;">
            <th style=""><b>Sifting Details Equipment</b></th>';
            	  $sql12 = "SELECT * FROM sp_bmr_sifting where work_order_id='".$row["id"]."'";
    	$result12 = $conn->query($sql12);
    	if($result12->num_rows > 0){
    		while($row12 = $result12->fetch_assoc()){
   $html.='           <th >'.$row12['siftter_equip'].'</th>';
    		}
    	}
    
     $html.='        </tr>
        <tr style="font-weight: bold;">
            <th style=""><b>Sifting Start Time</b></th>';
            	  $sql12 = "SELECT * FROM sp_bmr_sifting where work_order_id='".$row["id"]."'";
    	$result12 = $conn->query($sql12);
    	if($result12->num_rows > 0){
    		while($row12 = $result12->fetch_assoc()){
   $html.='           <th >'.$row12['sift_start_time'].'</th>';
    		}
    	}
     $html.='        </tr>
        <tr style="font-weight: bold;">
            <th style=""><b>Sifting End Time</b></th>';
            	  $sql12 = "SELECT * FROM sp_bmr_sifting where work_order_id='".$row["id"]."'";
    	$result12 = $conn->query($sql12);
    	if($result12->num_rows > 0){
    		while($row12 = $result12->fetch_assoc()){
   $html.='           <th >'.$row12['sift_start_time'].'</th>';
    		}
    	}
     $html.='        </tr>
        <tr style="font-weight: bold;">
            <th style=""><b>Sampling Time</b></th>';
            	  $sql12 = "SELECT * FROM sp_bmr_sifting where work_order_id='".$row["id"]."'";
    	$result12 = $conn->query($sql12);
    	if($result12->num_rows > 0){
    		while($row12 = $result12->fetch_assoc()){
   $html.='           <th >'.$row12['sampling_time'].'</th>';
    		}
    	}
     $html.='        </tr>
       
    </table>
    
    
    
    
</div><br pagebreak="true"/>
<table>
 <tr>
                <td style="width:540px; font-size: 12; text-align: left; font-weight: bold;">PRODUCT & CUSTOMER NAME: '. $row["product_name"].' FOR '. $row["manufactured_for"].'</td>

                </tr>
                
                <br>
                <tr>
                <td style="width:540px; font-size: 12; text-align: left; font-weight: bold;">BATCH NO: '. $row["batch_number"].'</td>

                </tr>
                
                <br>
                <tr>
                <td style="width:540px; font-size: 12; text-align: left; font-weight: bold;">DATE: '. $row["raw_qc_intimation_raised_date"].' </td>

                </tr>
                <br>
                <tr>
                <td style="width:540px; font-size: 12; text-align: center; font-weight: bold;">ENSURE THAT THE BULK SEMI-FINISH GOODS RELEASED:  </td>

                </tr>
                <br>
                <br>
                </table>
                <table border="1">
                <tr>
                <td style="width:270px; font-size: 12;height: 40px; text-align: center; font-weight: bold;">PROCESS</td>
                <td style="width:270px; font-size: 12;height: 40px; text-align: center; font-weight: bold;">NAME/ SIGN &DATE  </td>
                </tr>
                <tr>
                <td style="width:270px; font-size: 12;height: 40px; text-align: center; font-weight: bold;">SAMPLE INTIMATION RAISED BY</td>
                <td style="width:270px; font-size: 12;height: 40px; text-align: center; font-weight: bold;"> '. $row["raw_qc_intimation_raised_by"].' '. $row["raw_qc_intimation_raised_date"].' </td>
                </tr>
                <tr>
                <td style="width:270px; font-size: 12;height: 40px; text-align: center; font-weight: bold;">SAMPLE COLLECTED BY 
                '. $row["raw_qc_sample_qty"].' GMS</td>
                <td style="width:270px; font-size: 12;height: 40px; text-align: center; font-weight: bold;"> '. $row["raw_qc_sample_qty_by"].' '. $row["raw_qc_sample_qty__date"].'  </td>
                </tr>
                <tr>
                <td style="width:270px; font-size: 12;height: 40px; text-align: center; font-weight: bold;">SAMPLE CHECKED BY</td>
                <td style="width:270px; font-size: 12;height: 40px; text-align: center; font-weight: bold;"> '. $row["raw_qc_check_by"].' '. $row["raw_qc_check_date"].' </td>
                </tr>
                <tr>
                <td style="width:270px; font-size: 12;height: 40px; text-align: center; font-weight: bold;">SAMPLE APPROVED BY</td>
                <td style="width:270px; font-size: 12;height: 40px; text-align: center; font-weight: bold;"> '. $row["raw_qc_approve_by"].' '. $row["raw_qc_approve_date"].'</td>
                </tr>
                 <tr>
                <td style="width: 540px;px; font-size: 12;height: 40px; text-align: center; font-weight: bold;"> TRANSFER THE SEMI-FINISH GOODS TO PACKAGING HALL</td>

                </tr>
                </table>
                <br pagebreak="true"/>

<table border="1">
<tr>
<td style="width: 180px; font-size: 12;height: 20px; text-align: center; font-weight: bold;"rowspan="2"> Parameters to Check</td>
<td style="width: 360px; font-size: 12;height: 20px; text-align: center; font-weight: bold;"> Complies (✓) & Not Complies (X)</td>
</tr>
<tr>
<td style="width: 120px; font-size: 10;height: 20px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height: 20px; text-align: center; font-weight: bold;"> Lot 1</td>
<td style="width: 40px; font-size: 10;height: 20px; text-align: center; font-weight: bold;">Lot 2</td>
<td style="width: 40px; font-size: 10;height: 20px; text-align: center; font-weight: bold;"> Lot 3</td>
<td style="width: 40px; font-size: 10;height: 20px; text-align: center; font-weight: bold;"> Lot 4</td>
<td style="width: 40px; font-size: 10;height: 20px; text-align: center; font-weight: bold;"> Lot 5</td>
<td style="width: 40px; font-size: 10;height: 20px; text-align: center; font-weight: bold;"> Lot 6</td>
</tr>
<tr>
<td style="width: 180px; font-size: 12;height:40px; text-align: center; font-weight: bold;">Uniform Mixing / Blending of Powder</td>
<td style="width: 120px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
</tr>
<tr>
<td style="width: 180px; font-size: 12;height:40px; text-align: center; font-weight: bold;">Appearance & Color of Powder</td>
<td style="width: 120px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
</tr>
<tr>
<td style="width: 180px; font-size: 12;height:40px; text-align: center; font-weight: bold;">Foreign Matter</td>
<td style="width: 120px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
</tr>
<tr>
<td style="width: 180px; font-size: 12;height:40px; text-align: center; font-weight: bold;">Aroma of Powdered Product</td>
<td style="width: 120px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
</tr>
<tr>
<td style="width: 180px; font-size: 12;height:40px; text-align: center; font-weight: bold;">Aroma After Mixing in Water</td>
<td style="width: 120px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
</tr>
<tr>
<td style="width: 180px; font-size: 12;height:40px; text-align: center; font-weight: bold;">Mixability /Solubility in water</td>
<td style="width: 120px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
</tr>
<tr>
<td style="width: 180px; font-size: 12;height:40px; text-align: center; font-weight: bold;">Consistency after Mixing</td>
<td style="width: 120px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
</tr>
<tr>
<td style="width: 180px; font-size: 12;height:40px; text-align: center; font-weight: bold;">Sieve Integrity </td>
<td style="width: 120px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
</tr>
<tr>
<td style="width: 180px; font-size: 12;height:40px; text-align: center; font-weight: bold;">Colour , Taste & Time Required for Mixing in Water</td>
<td style="width: 120px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
</tr>
<tr>
<td style="width: 180px; font-size: 12;height:40px; text-align: center; font-weight: bold;">Overall Product Remark</td>
<td style="width: 120px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
</tr>
<tr>
<td style="width: 180px; font-size: 12;height:40px; text-align: center; font-weight: bold;">Moisture</td>
<td style="width: 120px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
</tr>
<tr>
<td style="width: 180px; font-size: 12;height:40px; text-align: center; font-weight: bold;">pH</td>
<td style="width: 120px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"> </td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
<td style="width: 40px; font-size: 10;height:40px; text-align: center; font-weight: bold;"></td>
</tr>

</table><br pagebreak="true"/>

<table border="1">
<tr>
<td style="width: 67px; font-size: 13;height:60px; text-align: center; font-weight: bold;">SR.NO</td>
<td style="width: 87px; font-size: 13;height:60px; text-align: center; font-weight: bold;">DATE</td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;">PRODUCT NAME</td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;">QTY INWARD IN SFG (KG)</td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;">QTY IN HAND SFG (KG)</td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;">Done By</td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;">Checked By</td>

</tr>
<tr>
<td style="width: 67px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 87px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>

</tr>
<tr>
<td style="width: 67px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 87px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>

</tr>
<tr>
<td style="width: 67px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 87px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>

</tr>
<tr>
<td style="width: 67px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 87px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>

</tr>

</table>
<div>
</div>
<div>
</div>
<div>
</div>
<table border="1">
<tr>
<td style="width: 67px; font-size: 13;height:60px; text-align: center; font-weight: bold;">SR.NO</td>
<td style="width: 87px; font-size: 13;height:60px; text-align: center; font-weight: bold;">DATE</td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;">PRODUCT NAME</td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;">QTY TRANSFER FOR FILLING (KG)</td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;">QTY IN HAND SFG (KG)</td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;">Done By</td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;">Checked By</td>

</tr>
<tr>
<td style="width: 67px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 87px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>

</tr>
<tr>
<td style="width: 67px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 87px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>

</tr>
<tr>
<td style="width: 67px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 87px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>

</tr>
<tr>
<td style="width: 67px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 87px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>
<td style="width: 77px; font-size: 13;height:60px; text-align: center; font-weight: bold;"></td>

</tr>

</table><br pagebreak="true"/>
<table>
<tr>
   <td> <b>Dispensing of Packing Material</b></td>
</tr>
<tr>
<td>
<tr>
    <td><b>Line Clearance Checklist for PM Dispensing</b></td>
</tr>
</td>
</tr>
</table>
<table border="1">
<tr>
<td style="width: 67px;    font-weight: bold;">SR.NO</td>
<td style="width: 241px;    font-weight: bold;">Checklist</td>
<td style="width: 77px;    font-weight: bold;">OK/Not<br> OK/NA</td>
<td style="width: 77px;    font-weight: bold;">Checked By</td>
<td style="width: 77px;    font-weight: bold;">Verified By</td>

</tr>';
 $sql14 = "SELECT * FROM pm_dispensing_checklist_transaction WHERE work_order_id='".$row["id"]."'";
        $result14 = $conn->query($sql14);
        $i=1;
        if ($result14->num_rows > 0) {
            while ($row14 = $result14->fetch_assoc()) {
$html.='<tr>
<td style="width: 67px;    "> ' .$i++ .'</td>
<td style="width: 241px;   "> '. $row14["checkpoint"].'</td>
<td style="width: 77px;    "> '. $row14["remarkss"].'</td>
<td style="width: 77px;    "> '. $row14["checked_by"].'</td>
<td style="width: 77px;   "> '. $row14["verified_by"].'</td>

</tr>';
}
}
$html.='
</table>
<div></div>
<table border="1">
    <tr>
        <td style="width:40px"> Sr.No.</td>
        <td style="width:260px"> Name of Packing Materials</td>
        <td style="width:60px"> Quantity</td>
        <td style="width:60px"> Given By</td>
        <td style="width:60px"> Received By</td>
        <td style="width:60px"> Checked By</td>
    </tr>';
    $sql15 = "SELECT a.*,b.material_name FROM mfg_work_order_dtl a left join material b on a.material_code=b.material_code WHERE a.work_order_id='".$row["id"]."' and a.material_code like '%PM%' ";
        $result15 = $conn->query($sql15);
        $i=1;
        if ($result15->num_rows > 0) {
            while ($row15 = $result15->fetch_assoc()) {
 $html.='   <tr>
        <td style="width:40px"> '.$i++.' </td>
        <td style="width:260px"> '. $row15["material_name"].'</td>
        <td style="width:60px"> '. $row15["total_batch_qty"].'</td>
        <td style="width:60px"> '. $row["pm_disp_completed_by"].'</td>
        <td style="width:60px">'. $row["pm_receive_by"].' </td>
        <td style="width:60px">'. $row["pm_receive_by"].'  </td>
    </tr>';
            }
        }
 $html.='
</table><br pagebreak="true"/><h1 >DAILY OPRP & CCP CHECKLIST</h1>';

$sql16 = "SELECT * FROM oprpccp_equip2 WHERE work_order_id='" . $row["id"] . "' and section='1' ";
$result16 = $conn->query($sql16);
$i = 1;
if ($result16->num_rows > 0) {
    while ($row16 = $result16->fetch_assoc()) {
        $html .= '<div style="width: 540px;">
            <table border="1" style="table-layout: auto;">
                <tr>';
               if ($row16["equipment"] == 'Control Temp & Humidity (CCP 1)E Primary Section 1' ||
            $row16["equipment"] == 'Control Temp & Humidity (CCP 1)E Primary Section 2') {
                $html .= '<td style="background-color: yellow;" colspan="5">' . $row16["equipment"] . '</td>';

            }
            else{
                $html .= '<td style="background-color: yellow;" colspan="7">' . $row16["equipment"] . '</td>';
            }
             $html .= '   </tr>';

        if ($row16["equipment"] == 'Control Temp & Humidity (CCP 1)E Primary Section 1' ||
            $row16["equipment"] == 'Control Temp & Humidity (CCP 1)E Primary Section 2') {

            $html .= '<tr>
                <td>Temp (25 degree +/- 2)</td>                                
                <td>Humidity (< 60)</td>       
                <td>Time</td>                                
                <td>Observation</td>       
                <td>Remarks</td>
            </tr>';
        }
        else if ($row16["equipment"] == 'Metal Detector (CCP 3)' ) {

            $html .= '<tr>
                    <td >Cleanliness of Hopper</td>                          
                    <td >Cleanliness of Discharge Channel</td>                          
                    <td >Air Pressure <br> (4 Bar Min- bar Max)</td>                          
                    <td >Sensitivity %</td>      
                    <td >FE(0.8MM)</td>                          
                    <td >NON_FE(0.1MM)</td>                          
                    <td >SS(1.2MM)</td>
            </tr>';
        }
        else if ($row16["equipment"] == 'Pouch Sealer (OPRP 2)' ) {

            $html .= '<tr>
                   <td >Heater is working <br> or Not</td>                          
                    <td >Seal Cleanliness</td>                          
                    <td >Film Folds are <br> Sealed Properly</td>                          
                    <td >Seal strength through<br> squeez test</td>   
                    <td >Time </td>                                
                    <td >Observation </td>       
                    <td >Remarks </td>  
            </tr>';
        }
        else if ($row16["equipment"] == 'Wad Sealer (OPRP 3)' ) {

            $html .= '<tr>
                   <td >Heater is working <br> or Not</td>                          
                    <td >Seal Cleanliness</td>                          
                    <td >Film Folds are <br> Sealed Properly</td>                          
                    <td >Seal strength through<br> squeez test</td>                                                                  
                    <td >Time </td>                                
                    <td >Observation </td>       
                    <td >Remarks </td>         
            </tr>';
        }
        
        $sql17 = "select * FROM oprpccp_equip_dtl2 a left JOIN oprpccp_equip2  b on a.oprpccp_equip2_id=b.id WHERE  a.oprpccp_equip2_id='" . $row16["id"] . "' and b.work_order_id='" . $row["id"] . "'";
$result17 = $conn->query($sql17);
$i = 1;
if ($result17->num_rows > 0) {
    while ($row17 = $result17->fetch_assoc()) {
    if ($row17["equipment"] == 'Control Temp & Humidity (CCP 1)E Primary Section 1' ||
            $row17["equipment"] == 'Control Temp & Humidity (CCP 1)E Primary Section 2') {

            $html .= '<tr>
                <td>' . $row17["tmep"] . '</td>                                
                <td>' . $row17["humidity"] . ' (< 60)</td>       
                <td>' . $row17["time"] . '</td>                                
                <td>' . $row17["obervation"] . '</td>       
                <td>' . $row17["remark"] . '</td>
            </tr>';
        }
        else if ($row17["equipment"] == 'Metal Detector (CCP 3)' ) {

            $html .= '<tr>
                   <td >' . $row17["cleanliness_hoper"] . '</td>                          
                    <td >' . $row17["cleanliness_discharge_channel"] . '</td>                          
                    <td >' . $row17["airpressure"] . '</td>                          
                    <td >' . $row17["sensitivity"] . '</td>   
                    <td >' . $row17["fe"] . ' </td>                                
                    <td >' . $row17["non_fe"] . ' </td>       
                    <td >' . $row17["ss"] . ' </td>  
            </tr>';
        }
        else if ($row17["equipment"] == 'Pouch Sealer (OPRP 2)' ) {

            $html .= '<tr>
                   <td >' . $row17["heater_working"] . '</td>                          
                    <td >' . $row17["seal_cleanliness"] . '</td>                          
                    <td >' . $row17["film_folds"] . '</td>                          
                    <td >' . $row17["seal_strength"] . '</td>   
                    <td >' . $row17["time"] . ' </td>                                
                    <td >' . $row17["obervation"] . ' </td>       
                    <td >' . $row17["remark"] . ' </td>  
            </tr>';
        }
        else if ($row17["equipment"] == 'Wad Sealer (OPRP 3)' ) {

            $html .= '<tr>
                   <td >' . $row17["wad_heater_working"] . '</td>                          
                    <td >' . $row17["wad_seal_cleanliness"] . '</td>                          
                    <td >' . $row17["wad_film_folds"] . '</td>                          
                    <td >' . $row17["wad_seal_strength"] . '</td>                                                                  
                    <td >' . $row17["time"] . ' </td>                                
                    <td >' . $row17["obervation"] . ' </td>       
                    <td >' . $row17["remark"] . ' </td>    
            </tr>';
        }
        
    }}

        $html .= '</table></div>';
    }
}

$sql16 = "SELECT * FROM oprpccp_equip2 WHERE work_order_id='" . $row["id"] . "' and section='2' ";
$result16 = $conn->query($sql16);
$i = 1;
if ($result16->num_rows > 0) {
    while ($row16 = $result16->fetch_assoc()) {
        $html .= '<div style="width: 540px;">
            <table border="1" style="table-layout: auto;">
                <tr>';
               if ($row16["equipment"] == 'Control Temp & Humidity (CCP 1)E Primary Section 1' ||
            $row16["equipment"] == 'Control Temp & Humidity (CCP 1)E Primary Section 2') {
                $html .= '<td style="background-color: yellow;" colspan="5">' . $row16["equipment"] . '</td>';

            }
            else{
                $html .= '<td style="background-color: yellow;" colspan="7">' . $row16["equipment"] . '</td>';
            }
             $html .= '   </tr>';

        if ($row16["equipment"] == 'Control Temp & Humidity (CCP 1)E Primary Section 1' ||
            $row16["equipment"] == 'Control Temp & Humidity (CCP 1)E Primary Section 2') {

            $html .= '<tr>
                <td>Temp (25 degree +/- 2)</td>                                
                <td>Humidity (< 60)</td>       
                <td>Time</td>                                
                <td>Observation</td>       
                <td>Remarks</td>
            </tr>';
        }
        else if ($row16["equipment"] == 'Metal Detector (CCP 3)' ) {

            $html .= '<tr>
                    <td >Cleanliness of Hopper</td>                          
                    <td >Cleanliness of Discharge Channel</td>                          
                    <td >Air Pressure <br> (4 Bar Min- bar Max)</td>                          
                    <td >Sensitivity %</td>      
                    <td >FE(0.8MM)</td>                          
                    <td >NON_FE(0.1MM)</td>                          
                    <td >SS(1.2MM)</td>
            </tr>';
        }
        else if ($row16["equipment"] == 'Pouch Sealer (OPRP 2)' ) {

            $html .= '<tr>
                   <td >Heater is working <br> or Not</td>                          
                    <td >Seal Cleanliness</td>                          
                    <td >Film Folds are <br> Sealed Properly</td>                          
                    <td >Seal strength through<br> squeez test</td>   
                    <td >Time </td>                                
                    <td >Observation </td>       
                    <td >Remarks </td>  
            </tr>';
        }
        else if ($row16["equipment"] == 'Wad Sealer (OPRP 3)' ) {

            $html .= '<tr>
                   <td >Heater is working <br> or Not</td>                          
                    <td >Seal Cleanliness</td>                          
                    <td >Film Folds are <br> Sealed Properly</td>                          
                    <td >Seal strength through<br> squeez test</td>                                                                  
                    <td >Time </td>                                
                    <td >Observation </td>       
                    <td >Remarks </td>         
            </tr>';
        }
        
        $sql17 = "select * FROM oprpccp_equip_dtl2 a left JOIN oprpccp_equip2  b on a.oprpccp_equip2_id=b.id WHERE  a.oprpccp_equip2_id='" . $row16["id"] . "' and b.work_order_id='" . $row["id"] . "'";
$result17 = $conn->query($sql17);
$i = 1;
if ($result17->num_rows > 0) {
    while ($row17 = $result17->fetch_assoc()) {
    if ($row17["equipment"] == 'Control Temp & Humidity (CCP 1)E Primary Section 1' ||
            $row17["equipment"] == 'Control Temp & Humidity (CCP 1)E Primary Section 2') {

            $html .= '<tr>
                <td>' . $row17["tmep"] . '</td>                                
                <td>' . $row17["humidity"] . ' (< 60)</td>       
                <td>' . $row17["time"] . '</td>                                
                <td>' . $row17["obervation"] . '</td>       
                <td>' . $row17["remark"] . '</td>
            </tr>';
        }
        else if ($row17["equipment"] == 'Metal Detector (CCP 3)' ) {

            $html .= '<tr>
                   <td >' . $row17["cleanliness_hoper"] . '</td>                          
                    <td >' . $row17["cleanliness_discharge_channel"] . '</td>                          
                    <td >' . $row17["airpressure"] . '</td>                          
                    <td >' . $row17["sensitivity"] . '</td>   
                    <td >' . $row17["fe"] . ' </td>                                
                    <td >' . $row17["non_fe"] . ' </td>       
                    <td >' . $row17["ss"] . ' </td>  
            </tr>';
        }
        else if ($row17["equipment"] == 'Pouch Sealer (OPRP 2)' ) {

            $html .= '<tr>
                   <td >' . $row17["heater_working"] . '</td>                          
                    <td >' . $row17["seal_cleanliness"] . '</td>                          
                    <td >' . $row17["film_folds"] . '</td>                          
                    <td >' . $row17["seal_strength"] . '</td>   
                    <td >' . $row17["time"] . ' </td>                                
                    <td >' . $row17["obervation"] . ' </td>       
                    <td >' . $row17["remark"] . ' </td>  
            </tr>';
        }
        else if ($row17["equipment"] == 'Wad Sealer (OPRP 3)' ) {

            $html .= '<tr>
                   <td >' . $row17["wad_heater_working"] . '</td>                          
                    <td >' . $row17["wad_seal_cleanliness"] . '</td>                          
                    <td >' . $row17["wad_film_folds"] . '</td>                          
                    <td >' . $row17["wad_seal_strength"] . '</td>                                                                  
                    <td >' . $row17["time"] . ' </td>                                
                    <td >' . $row17["obervation"] . ' </td>       
                    <td >' . $row17["remark"] . ' </td>    
            </tr>';
        }
        
    }}

        $html .= '</table></div>';
    }
}
 $html.='<br pagebreak="true"/>
 <table>
   <tr>
            <td style="width: 540px;height: 30px; text-align: center; font-size: 9px">PRIMARY PACKAGING SECTION (FILLING LINE)</td>
        </tr>
        <br>
        <br>
            <tr>
            <td style="width: 540px;height: 30px; text-align: center; bold; font-size:  12px"> Ensure that all filling material received in primary packing section.</td>
        </tr>
        </table>';
        $sql18="select * from primary_packing where work_order_id= '".$row["id"]."'"; 
  
                          $result18 = $conn->query($sql18);
                                    if ($result18->num_rows > 0) {
                                        while ($row18 = $result18->fetch_assoc()) {
 $html.='<table border="1">

<tr>
<td style="width: 290px;height: 40px; text-align: left; bold; font-size:  12px"> PRODUCT NAME: ' . $row["product_name"] . '</td>
<td style="width: 250px;height: 40px; text-align: left; bold; font-size:  12px"> CUSTOMER NAME:</td>
</tr>
<tr>
<td style="width: 290px;height: 40px; text-align: left; bold; font-size:  12px"> BATCH NO.: ' . $row["batch_number"] . ' </td>
<td style="width: 250px;height: 40px; text-align: left; bold; font-size:  12px"> MFG DATE: </td>
</tr>
<tr>
<td style="width: 290px;height: 40px; text-align: left; bold; font-size:  12px"> FILLING QTY:  </td>
<td style="width: 250px;height: 40px; text-align: left; bold; font-size:  12px"> ' . $row18["Filling_qty"] . ' </td>
</tr>
<tr>
<td style="width: 290px;height: 40px; text-align: left; bold; font-size:  12px"> Packing Type:  </td>
<td style="width: 250px;height: 40px; text-align: left; bold; font-size:  12px"> ' . $row18["packing_type"] . '</td>
</tr>

<tr>
<td style="width: 30px;height: 40px; text-align: center; font-size: 9px">SR. NO.</td>

<td style="width: 90px;height: 40px; text-align: center; font-size: 9px">START TIME</td>
<td style="width: 90px;height: 40px; text-align: center; font-size: 9px">PACK SIZE</td>
<td style="width: 50px;height: 40px; text-align: center; font-size: 9px">UNITS PACKED</td>
<td style="width: 110px;height: 40px; text-align: center; font-size: 9px">UNITS DAMAGE</td>
<td style="width: 55px;height: 40px; text-align: center; font-size: 9px">PACKING MATERIAL LOSSESS</td>
<td style="width: 55px;height: 40px; text-align: center; font-size: 9px">PREPARE BY</td>
<td style="width: 60px;height: 40px; text-align: center; font-size: 9px">CHECKED BY</td>

</tr>';$sql3="select * from primary_packing_dtl where primary_packing_id= '".$row18["id"]."'"; 
                                  $result3 = $conn->query($sql3);
                                    if ($result3->num_rows > 0) {
                                        $i=1;
                                        while ($row3 = $result3->fetch_assoc()) {
 $html.='<tr>
<td style="width: 30px;height: 40px; text-align: center; font-size: 9px"> ' . $i++ . '</td>
<td style="width: 90px;height: 40px; text-align: center; font-size: 9px"> ' . $row3["start_time"] . '</td>
<td style="width: 90px;height: 40px; text-align: center; font-size: 9px"> ' . $row3["pack_size"] . '</td>
<td style="width: 50px;height: 40px; text-align: center; font-size: 9px"> ' . $row3["unit_packed"] . '</td>
<td style="width: 110px;height: 40px; text-align: center; font-size: 9px" > ' . $row3["unit_damage"] . '</td>
<td style="width: 55px;height: 40px; text-align: center; font-size: 9px"> ' . $row3["pack_material_losss"] . '</td>
<td style="width: 55px;height: 40px; text-align: center; font-size: 9px"> ' . $row3["prepare_by"] . '</td>
<td style="width: 60px;height: 40px; text-align: center; font-size: 9px"> ' . $row["bmr_completed_by"] . '</td>

</tr>';
}
}

 $html.='
   </table>';  
                                        }
                                        
                                    }
             $html.='';    
 $html.='<br pagebreak="true"/>
 <table>
   <tr>
            <td style="width: 540px;height: 30px; text-align: center; font-size: 9px">SECONDARY PACKAGING SECTION (FILLING LINE)</td>
        </tr>
        <br>
        <br>
            <tr>
            <td style="width: 540px;height: 30px; text-align: center; bold; font-size:  12px"> Ensure that all filling material received in primary packing section.</td>
        </tr>
        </table>';
        $sql18="select * from secondary_packing where work_order_id= '".$row["id"]."'"; 
  
                          $result18 = $conn->query($sql18);
                                    if ($result18->num_rows > 0) {
                                        while ($row18 = $result18->fetch_assoc()) {
 $html.='<table border="1">

<tr>
<td style="width: 290px;height: 40px; text-align: left; bold; font-size:  12px"> PRODUCT NAME: ' . $row["product_name"] . '</td>
<td style="width: 250px;height: 40px; text-align: left; bold; font-size:  12px"> CUSTOMER NAME:</td>
</tr>
<tr>
<td style="width: 290px;height: 40px; text-align: left; bold; font-size:  12px"> BATCH NO.: ' . $row["batch_number"] . ' </td>
<td style="width: 250px;height: 40px; text-align: left; bold; font-size:  12px"> MFG DATE: </td>
</tr>
<tr>
<td style="width: 290px;height: 40px; text-align: left; bold; font-size:  12px"> FILLING QTY:  </td>
<td style="width: 250px;height: 40px; text-align: left; bold; font-size:  12px"> ' . $row18["Filling_qty"] . ' </td>
</tr>
<tr>
<td style="width: 290px;height: 40px; text-align: left; bold; font-size:  12px"> Packing Type:  </td>
<td style="width: 250px;height: 40px; text-align: left; bold; font-size:  12px"> ' . $row18["packing_type"] . '</td>
</tr>

<tr>
<td style="width: 30px;height: 40px; text-align: center; font-size: 9px">SR. NO.</td>

<td style="width: 90px;height: 40px; text-align: center; font-size: 9px">START TIME</td>
<td style="width: 90px;height: 40px; text-align: center; font-size: 9px">PACK SIZE</td>
<td style="width: 50px;height: 40px; text-align: center; font-size: 9px">UNITS PACKED</td>
<td style="width: 110px;height: 40px; text-align: center; font-size: 9px">UNITS DAMAGE</td>
<td style="width: 55px;height: 40px; text-align: center; font-size: 9px">PACKING MATERIAL LOSSESS</td>
<td style="width: 55px;height: 40px; text-align: center; font-size: 9px">PREPARE BY</td>
<td style="width: 60px;height: 40px; text-align: center; font-size: 9px">CHECKED BY</td>

</tr>';$sql3="select * from secondary_packing_dtl where secondary_packing_id= '".$row18["id"]."'"; 
                                  $result3 = $conn->query($sql3);
                                    if ($result3->num_rows > 0) {
                                        $i=1;
                                        while ($row3 = $result3->fetch_assoc()) {
 $html.='<tr>
<td style="width: 30px;height: 40px; text-align: center; font-size: 9px"> ' . $i++ . '</td>
<td style="width: 90px;height: 40px; text-align: center; font-size: 9px"> ' . $row3["start_time"] . '</td>
<td style="width: 90px;height: 40px; text-align: center; font-size: 9px"> ' . $row3["pack_size"] . '</td>
<td style="width: 50px;height: 40px; text-align: center; font-size: 9px"> ' . $row3["unit_packed"] . '</td>
<td style="width: 110px;height: 40px; text-align: center; font-size: 9px" > ' . $row3["unit_damage"] . '</td>
<td style="width: 55px;height: 40px; text-align: center; font-size: 9px"> ' . $row3["pack_material_losss"] . '</td>
<td style="width: 55px;height: 40px; text-align: center; font-size: 9px"> ' . $row3["prepare_by"] . '</td>
<td style="width: 60px;height: 40px; text-align: center; font-size: 9px"> ' . $row["bmr_completed_by"] . '</td>

</tr>';
}
}

 $html.='
   </table>';  
                                        }
                                        
                                    }
             $html.='<br pagebreak="true"/>

<table>
<tr>
<td style="line-height:50px;width:540px;border-bottom:none;text-align:center;"> SAMPLING OF FINISHED PRODUCT: </td>
</tr>
</table>
<table>
<tr>
<td style="line-height:50px;width:540px;border-bottom:none;text-align:left;"> 	INTIMATION TO QUALITY CONTROL:</td>
</tr>
</table>
<table border="1">
<tr>
  <td style="line-height:40px;width: 320px;border-bottom:none;text-align:center;"> PROCESS</td>
  <td style="line-height:40px;width: 220px;border-bottom:none;text-align:center;"> NAME/SIGN/DATE</td>
 </tr>
<tr>
    <td style="line-height:40px;width: 320px;border-bottom:none;text-align:center;"> SAMPLE INTIMATION RAISED BY </td>
    <td style="line-height:40px;width: 220px;border-bottom:none;text-align:center;"> ' . $row["qc_intimation_raised_by"] . '  </td>
</tr>
<tr>
  <td style="line-height:40px;width: 320px;border-bottom:none;text-align:left;"> SAMPLE COLLLECTED BY QC  ' . $row["qc_sample_qty_packing"] . ' QTY  </td>
  <td style="line-height:40px;width: 220px;border-bottom:none;text-align:center;">  ' . $row["qc_sample_qty_by"] . '  </td>
</tr>

</table>
<div></div>
<div></div>

<table>
<tr>
<td style="line-height:20px;width: 270px;border-bottom:none;text-align:center;"> SIGN/DATE : PRODUCTION MANAGER </td>
<td style="line-height:20px;width: 270px;border-bottom:none;text-align:center;"> SIGN/DATE  : IPQC </td>
</tr>
</table>
<br pagebreak="true"/>
<table border="1">
<tr>
    <td style="line-height:20px;width: 540px;border-bottom:none;text-align:center;"> FINISH PRODUCT - PACKING QUALITY & SAMPLE CHECK</td>
</tr>
<tr>
    <td style="line-height:20px;width: 240px;border-bottom:none;text-align:center;"> Name of Product</td>
    <td style="line-height:20px;width: 300px;border-bottom:none;text-align:center;"> </td>
</tr>
<tr>
    <td style="line-height:20px;width: 240px;border-bottom:none;text-align:center;"> Batch No.</td>
    <td style="line-height:20px;width: 300px;border-bottom:none;text-align:center;"> </td>
</tr>
<tr>
    <td style="line-height:20px;width: 240px;border-bottom:none;text-align:center;"> Date of Mfg.</td>
    <td style="line-height:20px;width: 300px;border-bottom:none;text-align:center;"> </td>
</tr>
<tr>
    <td style="line-height:20px;width: 540px;border-bottom:none;text-align:center;"> Parameters to Check</td>
</tr>';
 $sql2="select * from packing_quality_sample_check where work_order_id= '".$row["id"]."'"; 
  
                        $result3 = $conn->query($sql2);
                                    if ($result3->num_rows > 0) {
                                        $i=1;
                                        while ($row3 = $result3->fetch_assoc()) {

$html.='<tr>
    <td style="line-height:20px;width: 240px;border-bottom:none;text-align:center;">  ' . $row3["parameter"] . '</td>
    <td style="line-height:20px;width: 300px;border-bottom:none;text-align:center;">  ' . $row3["remark"] . '</td>
</tr>';
}
}

$html.='</table>
<div></div>
<table>
<tr>
    <td style="line-height:20px;width: 540px;border-bottom:none;text-align:left;"> TRANSFER OF FINAL PRODUCT TO WAREHOUSE  </td>
</tr>
</table>
<div></div>

<table border="1">
<tr>
    <td style="line-height:20px;width: 270px;border-bottom:none;text-align:center;"> PROCESS</td>
    <td style="line-height:20px;width: 270px;border-bottom:none;text-align:center;"> </td>
</tr>
<tr>
<td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> FG TRANSFER INITIMATION RAISED BY <br>(PRODUCTION)</td>
 <td style="line-height:30px;width: 270px;border-bottom:none;text-align:left;"> DATE: '.$fg_intimation_raised_date.' <br> Time:'.$fg_intimation_raised_time.'<br>
 SIGN:  ' . $row["fg_intimation_raised_by"] . '
</td>
</tr>
<tr>
     <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;">FG TRASNFER INTIMATION  RECEIVED BY <br>(WAREHOUSE) </td>
      <td style="line-height:30px;width: 270px;border-bottom:none;text-align:left;"> DATE:  ' . $row["fg_intimation_receive_date"] . ' <br> SIGN:' . $row["fg_intimation_receive_by"] . '
       </td>
 </tr>
 <tr>
     <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> FINISH PRODUCT RELEASE BY 
     (QC/IPQC) 
      </td>
           <td style="line-height:30px;width: 270px;border-bottom:none;text-align:left;"> DATE: <br> TIME: <br> SIGN:
            </td>
 </tr>
</table><br pagebreak="true"/>
<table>
 <tr>
 <td style="line-height:70px;width:540px;border-bottom:none;text-align:center;"> FINAL QUALITY CHECK</td>
</tr>
 </table>

 <table>
 <tr>
 <td style="line-height:70px;width:540px;border-bottom:none;text-align:left;">* RANDOM UNITS CHECK BY QA/QC:</td>
</tr>
 </table>

 <table border="1">
 <tr>
      <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> PROCESS</td>
     <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> QTY</td>
     
 </tr>'; 
 $sql2="select * from bmr_final_batch_plan where work_order_id= '".$row["id"]."'"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
$html.='    <tr>
       <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;">  NO. OF UNITS CHECKED</td>
      <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> ' . $row2["units_checked"] . ' </td>
   </tr>
   <tr>
   <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> DEFECT OBSERVED </td>
  <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> ' . $row2["defect_observed"] . ' </td>
</tr>
<tr>
<td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> DEVIATION (If applicable) </td>
<td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> ' . $row2["deviation"] . ' </td>
</tr>';
}
}
$html.='
 </table>';    
            }
        } 
  
  
  
		$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	   

        }
	    
        else if ($_GET["type"] == "downloadBatchPlanLog") {
        $_GET['filename'] = 'downloadPlans'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Plans</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Date</td>
                    <td style="width: 10%;">Plan No.</td>
                    <td style="width: 15%;">Dosage Form</td>
                    <td style="width: 15%;">Product code</td>
                    <td style="width: 15%;">Product Name</td>
                    <td style="width: 10%;">Grade</td>
                    <td style="width: 10%;">Batch Size</td>
                    <td style="width: 10%;">Prepared By</td>
                </tr>
            </thead>';
             $i=1;
        $sql = "SELECT b.*, p.product_name FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.plant_name='".$_GET["department"]."' AND b.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<tr nobr="true">
                    <td style="width: 5%;">'.$i.'</td>
                    <td style="width: 10%;">'.date("d/m/Y",($row['entry_date'])).'</td>
                    <td style="width: 10%;">'.$row['plan_no'].'</td>
                    <td style="width: 15%;">'.$row['dosage_form'].'</td>
                    <td style="width: 15%;">'.$row['product_code'].'</td>
                    <td style="width: 15%;">'.$row['product_name'].'</td>
                    <td style="width: 10%;">'.$row['grade'].'</td>
                    <td style="width: 10%;">'.$row['batch_size'].'</td>
                    <td style="width: 10%;">'.$row['entry_by'].'</td>
                </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadPlans.pdf', 'I');
    }  

 else if ($_GET["type"] == "downloadBatchPlans") {
        $_GET['filename'] = 'downloadPlans'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Plans</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Date</td>
                    <td style="width: 10%;">Plan No.</td>
                    <td style="width: 15%;">Dosage Form</td>
                    <td style="width: 15%;">Product code</td>
                    <td style="width: 15%;">Product Name</td>
                    <td style="width: 10%;">Grade</td>
                    <td style="width: 10%;">Batch Size</td>
                    <td style="width: 10%;">Prepared By</td>
                </tr>
            </thead>';
             $i=1;
$sql ="SELECT  b.entry_by,b.entry_date,b.product_type,a.id,a.material_type,a.batch_number,a.work_order_no,a.entry_by as palnned_by,a.approved_by,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,dispensing_status,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.grade,b.pack_size,b.pack_unit FROM mfg_work_order_hdr a 
             JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where a.ebmr_status !='notaplicable'  AND   a.plant_id ='".$_GET["plant_id"]."'
             and a.status='approved'  and a.material_type like '%".$_GET["material_type"]."%' ORDER by a.id DESC "; 
             $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<tr nobr="true">
                    <td style="width: 5%;">'.$i.'</td>
                    <td style="width: 10%;">'.$row['entry_date'].'</td>
                    <td style="width: 10%;">'.$row['plan_no'].'</td>
                    <td style="width: 15%;">'.$row['entry_by'].'</td>
                    <td style="width: 15%;">'.$row['product_code'].'</td>
                    <td style="width: 15%;">'.$row['product_name'].'</td>
                    <td style="width: 10%;">'.$row['grade'].'</td>
                    <td style="width: 10%;">'.$row['batch_size'].'</td>
                    <td style="width: 10%;">'.$row['entry_by'].'</td>
                </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadPlans.pdf', 'I');
    }  

}

$conn->close();
?>