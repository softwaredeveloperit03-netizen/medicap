<?php
try{
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    
    
    
    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);


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

    if ($_GET["type"] == "getStoreEmployees") {
        $output = array();
         $sql = "SELECT * FROM employee WHERE department='".$_GET['department']."' and plant_id='".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "getDispensingchecks") {
        $output = array();
        $sql = "SELECT * FROM pm_dispensing_checklist_transaction WHERE work_order_id='".$_GET["wo_id"]."' and sp_bmr_sifting_id='".$_GET["sift_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "saveStock") {
        $mat_type= $input["material_type"];
       
        if($mat_type!='Finish Product' && $mat_type !='Finish Product Branded'){
         
            $batches = $input["material_list"];
        for ($i = 0; $i < count($batches); $i++) {
            $material = $batches[$i];
            $sql = "INSERT INTO stock_book (plant_id,vendor_no,product_code,inword_no, inword_date,stock_type, material_code,
            batch_no, qty, unit, ar_no, grn_no, grn_date, mfg_date, exp_date, status,pack_size,entry_date) VALUES ('".$_GET["plant_id"]."','".$input["vendor_no"]."',
            '".$input["product_code"]."', '".$input["inword_no"]."', '".$input["inword_date"]."','Opening', '".$material["material_code"]."', 
            '".$material["batch_no"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["ar_no"]."', '".$material["grn_no"]."', 
            '".$material["grn_date"]."', '".$material["mfg_date"]."', '".$material["exp_date"]."', '".$material["status"]."', '".$material["pack_size"]."','$entry_date')";
            $conn->query($sql);
        }
        echo "{\"status\":\"success\"}";
        }
        else if($mat_type=='Finish Product' || $mat_type =='Finish Product Branded'){
            $batches = $input["material_list"];
        for ($i = 0; $i < count($batches); $i++) {
            $material = $batches[$i];
             $sql = "INSERT INTO fg_stock_book (plant_id,   stock_type, material_code,
            batch_no, qty, qty_unit, ar_no, batch_size,  mfg_date, exp_date, status,pack_size,entry_date) VALUES ('".$_GET["plant_id"]."', 
        'Opening', '".$material["material_code"]."', 
            '".$material["batch_no"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["ar_no"]."', '".$material["batch_size"]."', 
            '".$material["mfg_date"]."', '".$material["exp_date"]."', '".$material["status"]."', '".$material["pack_size"]."','$entry_date')";
            $conn->query($sql);
        }
        echo "{\"status\":\"success\"}";
        }
        
    }
    else if ($_GET["type"] == "getDispensingRequests") {
        
        
        $output = array();
        // $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='pending' GROUP BY d.product_code";
        
           $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.entry_by as palnned_by,a.approved_by,p.dosage_form,b.plan_no,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,p.dosage_form,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,
             a.overages_percent, a.batch_overages,";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_store_status as store_status,a.rm_store_status_entry_by as store_status_entry_by,a.rm_store_status_entry_date as store_status_entry_date ";
             }else{
                $sql = $sql." a.pm_store_status as store_status,a.pm_store_status_entry_by as store_status_entry_by,a.pm_store_status_entry_date as store_status_entry_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." and rm_store_status_entry_by is null and a.material_type = 'RM' order by a.id desc ";
            }else{
                $sql = $sql." and pm_store_status_entry_by=''  and a.material_type = 'PM' ";
            }
             
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                // Get header values for overages and batch_overages
                $header_overages_percent = floatval($row['overages_percent'] ?? 0);
                $header_batch_overages = floatval($row['batch_overages'] ?? 0);
                
                $sql1 = "select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name,b.grade as m_grade,
                        c.overages_percent as header_overages_percent, c.batch_overages as header_batch_overages
                 FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id join material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id where a.work_order_id = '".$row["id"]."'
                 and b.material_type = '".$_GET["material_type"] ."' ) as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                            $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['m_grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
        //   ////////////////////////////////////////////////////////////////////
        
        
        //   $sql5=" SELECT JSON_UNQUOTE(JSON_EXTRACT(raw_materials, '$[0].percent_qty')) AS percent_qty FROM unitformula a LEFT JOIN 
        //   batch_planning b ON a.mfr_no = b.mfr_no WHERE JSON_SEARCH(raw_materials, 'one', '".$row1['material_code']."') IS NOT NULL
        //   AND b.plan_no = '".$row['plan_no']."'";

        //     $resQ1 = $conn->query($sql5);
        //     $prodLatest1 = $resQ1->fetch_assoc(); 
         
        //     $row1['percent_qty'] = $prodLatest1['percent_qty']; 
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
          
          
          
          
          
          
        // Populate missing overages, batch_overages, and total_batch_qty fields
        // Get header values (use from row1 if available, otherwise from row)
        $header_overages = floatval($row1['header_overages_percent'] ?? $header_overages_percent);
        $header_batch_overages_val = floatval($row1['header_batch_overages'] ?? $header_batch_overages);
        
        // Overages (%) - use material-level value if available, otherwise use header value
        if (empty($row1['overages']) || $row1['overages'] == '0' || $row1['overages'] == null) {
            $row1['overages'] = $header_overages;
        } else {
            $row1['overages'] = floatval($row1['overages']);
        }
        
        // Batch Overages (%) - use material-level value if available, otherwise use header value
        if (empty($row1['batch_overages']) || $row1['batch_overages'] == '0' || $row1['batch_overages'] == null) {
            $row1['batch_overages'] = $header_batch_overages_val;
        } else {
            $row1['batch_overages'] = floatval($row1['batch_overages']);
        }
        
        // Batch Total Qty - calculate if NULL or empty
        $batch_qty = floatval($row1['batch_qty'] ?? 0);
        $batch_overages_val = floatval($row1['batch_overages'] ?? 0);
        
        if (empty($row1['total_batch_qty']) || $row1['total_batch_qty'] == '0' || $row1['total_batch_qty'] == null) {
            // Calculate: batch_qty + (batch_qty * batch_overages / 100)
            if ($batch_qty > 0) {
                $row1['total_batch_qty'] = round($batch_qty + ($batch_qty * $batch_overages_val / 100), 4);
            } else {
                $row1['total_batch_qty'] = 0;
            }
        } else {
            $row1['total_batch_qty'] = floatval($row1['total_batch_qty']);
        }
        
        // Ensure lod_status and assay_status are set (should come from mfg_work_order_dtl)
        if (empty($row1['lod_status']) || $row1['lod_status'] == null) {
            $row1['lod_status'] = '';
        }
        if (empty($row1['assay_status']) || $row1['assay_status'] == null) {
            $row1['assay_status'] = '';
        }
          
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        } 
        echo json_encode($output);
    
    } 
       else if ($_GET["type"] == "saveRequestPM_saipro") {
           $sql = "UPDATE mfg_work_order_hdr SET pm_store_status='".$input["status"]."',
         pm_store_status_entry_by='".$_GET["emp_id"]."', 
         pm_store_remarks = '".$input["remarks"]."',
         pm_store_status_entry_date='$entry_date'
        WHERE id='".$_GET["work_id"]."'";
         
        if ($conn->query($sql)) {
          $r_materials = $input["materials"];
            for ($i = 0; $i <count($r_materials); $i++) {
                $material = $r_materials[$i];
                     "Debug: id = " . $_GET["id"] . ", material_id = " . $material["id"] . ", stock_status = " . $material['physical_stock_status'] . "<br>";
                   $sql1 = "Update mfg_work_order_dtl  set physical_stock ='".$material['physical_stock_status']."' ,actual_qtyy='".$material['actual_qtyy']."'
                        where   id = '".$material["id"]."' ";
                         
                $conn->query($sql1);
                
            }
            if($input["checklist"]!=''){
                        $check = $input["checklist"];
            for ($i = 0; $i <count($check); $i++) {
                $checks = $check[$i];
                 $sql1 = "INSERT INTO pm_dispensing_checklist_transaction(sp_bmr_sifting_id, checkpoint, remarkss,work_order_id)VALUES( '".$_GET["sift_id"]."','".$checks["checkpoint"]."','".$checks["remarkss"]."','".$input["work_order_id"]."')";
                         
                $conn->query($sql1);
                
            }
            }
    
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "get_PM_Dispensing_Requests") {
        $output = array();
       
          $sql = "SELECT b.*,a.id as a_id,a.pm_dispense_request_sent_on,p.grade,a.pm_dispense_request_sent_by,a.work_order_no,a.batch_number, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,
          b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
          b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size,a.id as a_id 
       
          FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
           left join mfg_work_order_hdr a on b.id= a.batch_plan_id LEFT
           JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
          LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
          LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
          Where b.plant_id = '".$_GET["plant_id"]."' and  a.pm_store_status='0' and a.batch_number!='' and (a.pm_dispensing_status!='0' or a.pm_dispensing_status ='0')  
          group by cl.company,p.grade,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,a.id,
          b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight,a.batch_number ,uf.batch_size_unit   
          ORDER By b.id desc LIMIT 20";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                      $output1 = array();
                $sql = "select batch_plan_id,pack_size,batch_size from batch_planing_raw_material_hdr where batch_plan_id = '".$row["id"]."' group by batch_plan_id,batch_size,pack_size LIMIT 20";
                $result2 = $conn->query($sql);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2 = array();
                       
                                     $sql2 ="SELECT   a.*,     b.*,     d.id AS dtl_id, pmlot.actual_qtyy
                                            FROM batch_planning_materials a 
                                            LEFT JOIN material b ON a.material_code = b.material_code
                                            LEFT JOIN mfg_work_order_dtl d ON b.material_code = d.material_code 
                                            LEFT JOIN mfg_work_order_dtl_pm_lots pmlot ON pmlot.work_order_id = '".$row["a_id"]."' 
                                            WHERE a.material_type = 'Packing Material' 
                                              AND a.batch_plan_id = '".$row2["batch_plan_id"]."' 
                                              AND d.work_order_id = '".$row["a_id"]."' 
                                              AND a.pack_size = '".$row2["pack_size"]."' 
                                               GROUP by a.id,b.id,dtl_id,pmlot.actual_qtyy  
                                            LIMIT 50 ";
                                   
                                
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
    // else if ($_GET["type"] == "get_PM_Dispensing_Requests") {
    //     $output = array();
    //                     // cmr on 14/03/23
    //     //   $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,wh.actual_yeild,
    //     //   b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
    //     //   b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size
    //     //   FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
    //     //   left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
    //     //   LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
    //     //   LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
    //     //   LEFT join mfg_work_order_hdr wh on b.id = wh.batch_plan_id
    //     //   Where b.plant_id = '".$_GET["plant_id"]."' and wh.tr_to_packing_dept_by !=''
    //     //   group by cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
    //     //   b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit,wh.actual_yeild
    //     //   ORDER By b.id desc";
         
    //     //   $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,wh.actual_yeild,
    //     //   b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
    //     //   b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size,bd.stage_yield
    //     //   FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
    //     //   left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
    //     //   LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
    //     //   LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
    //     //   LEFT join mfg_work_order_hdr wh on b.id = wh.batch_plan_id
    //     //   LEFT JOIN batch_stages_ipqc_dtl bd on b.plan_no = bd.plan_no
    //     //   Where b.plant_id = '".$_GET["plant_id"]."' and wh.tr_to_packing_dept_by !=''
    //     //   group by cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
    //     //   b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit,wh.actual_yeild,bd.stage_yield
    //     //   ORDER By b.id desc";
    //       $sql = "SELECT b.*,a.id as a_id,a.pm_dispense_request_sent_on,p.grade,a.pm_dispense_request_sent_by,a.work_order_no,a.batch_number, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,
    //       b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
    //       b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size,a.id as a_id,
    //       (SELECT COUNT(pk_plan) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.pk_plan!='0') as pk_plan_status
       
    //       FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
    //       left join mfg_work_order_hdr a on b.id= a.batch_plan_id LEFT
    //       JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
    //       LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
    //       LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
    //       Where b.plant_id = '".$_GET["plant_id"]."' and  a.pm_store_status='' and a.batch_number!='' and (a.pm_dispensing_status!='' or a.pm_dispensing_status ='')  
    //       group by cl.company,p.grade,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,a.id,
    //       b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight,a.batch_number ,uf.batch_size_unit HAVING    pk_plan_status != '0'
    //       ORDER By b.id desc LIMIT 20";
       
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $output1 = array();
    //             $sql = "select batch_plan_id,pack_size,batch_size from batch_planing_raw_material_hdr where batch_plan_id = '".$row["id"]."' group by batch_plan_id,batch_size,pack_size LIMIT 20";
    //             $result2 = $conn->query($sql);
    //             if ($result2->num_rows > 0) {
    //                 while ($row2 = $result2->fetch_assoc()) {
    //                     $output2 = array();
                       
                       
    //                                           // $sql2 ="SELECT * FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code
    //                     //     LEFT JOIN stock_book c ON b.material_code = c.material_code  left JOIN mfg_work_order_dtl d on
    //                     //     b.material_code=d.material_code  WHERE a.material_type = 'Packing Material' and a.batch_plan_id='".$row2["batch_plan_id"]."'
    //                     //     and d.work_order_id='".$row["a_id"]."' AND a.pack_size = '".$row2["pack_size"]."'   LIMIT 50 ";  // stckbook
                            
    //                     $sql2 ="SELECT * FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code
    //                          left JOIN mfg_work_order_dtl d on
    //                         b.material_code=d.material_code  WHERE a.material_type = 'Packing Material' and a.batch_plan_id='".$row2["batch_plan_id"]."'
    //                         and d.work_order_id='".$row["a_id"]."' AND a.pack_size = '".$row2["pack_size"]."'   LIMIT 50 ";
                            
                                
                                
    //                     $result3 = $conn->query($sql2);
    //                     if ($result3->num_rows > 0) {
    //                       while ($row3 = $result3->fetch_assoc()) {
    //                               $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row3['grade']."')";
    //          $resQ = $conn->query($q);
    //           $prodLatest = $resQ->fetch_assoc(); 
         
    //       $row3['gradeName'] = $prodLatest['gradeName']; 
                        
                               
                               
    //                               $output2[] = $row3;
    //                         }
    //                     }
    //                     $row2["packing_material"] = $output2;
    //                      $output1[] = $row2;
    //                 }
    //                 $row["pack_sizes"] = $output1;
    //             }
    //             $output[] = $row;
    //         }
    //     }else{
    //         $output=[];
    //     }
    //     echo json_encode($output);
    // } 
        else if ($_GET["type"] == "approve_work_order_saipro_pm") {
        
         $last_id=0;
        $sql0 = "Select count(*)+1 as count from mfg_work_order_hdr where  plant_id = '".$_GET["plant_id"]."' and qa_person!='' ";
        $result0 = $conn->query($sql0);
        while($row0 = $result->fetch_assoc()){
              $last_id = $row0['count'];
        }
        if($last_id==0){
            $last_id=1;
        }
        $length = 4;
        $number = substr(str_repeat(0, $length).$last_id, - $length);
        $batch_number =  "B".$number;
        $sql ="update sp_bmr_sifting set pk_plan = '".$_GET["status"]."' ,
               pm_approved_by ='".$_GET["emp_id"]."',pm_approved_date= '".$entry_date."',
                pm_dispensing_status ='Request Sent',
                	pm_dispense_request_sent_by	='".$_GET["emp_id"]."', 
                pm_dispense_request_sent_on	='$entry_date'
               where  id ='".$_GET["sift_id"]."' "; 
             
        if ($conn->query($sql)) {
              $json_obj1 = json_encode($input["packing_materials"]);
              
              $length = strlen($json_obj1);

              $i - 0;
              for( $i = 0 ; $i< $length ; $i++){
                  
                  
                    
             $json_obj = json_encode($input["packing_materials"][$i]['packing_list']);
              $array = json_decode($json_obj, true);
               
                foreach ($array as $values)
                {
                    
                //   $sql = "update mfg_work_order_dtltt set actual_qtyy = '".$values["actual_qtyy"]."' where  id ='".$values["d_id"]."' ";
                  $sql = "INSERT INTO mfg_work_order_dtl_pm_lots( sp_bmr_sifting_id,work_order_id, pack_size_id, material_code, grade, unit_qty, actual_qtyy, unit, overages,
                  total_unit_qty, batch_qty, batch_overages, total_batch_qty, lod_status, assay_status, physical_stock, status, containers, ars, operator_name,
                  done_by, check_by, entry_date, despensing_status,batch_size,pack_size) VALUES ('".$_GET["sift_id"]."','".$_GET["id"]."','".$values["pack_size_id"]."','".$values["material_code"]."',
                  '".$values["gradeName"]."','".$values["b_qty"]."','".$values["actual_qtyy"]."','".$values["pack_size_unit"]."','".$values["overages"]."',
                  '".$values["total_qty"]."','".$values["batch_qty"]."','".$values["batch_overages"]."','".$values["total_batch_qty"]."',
                  '".$values["lod_status"]."','".$values["assay_status"]."','".$values["physical_stock"]."','".$values["status"]."','".$values["containers"]."',
                  '".$values["ars"]."','".$values["operator_name"]."','".$values["done_by"]."','".$values["check_by"]."','".$values["entry_date"]."',
                  '".$values["despensing_status"]."', '".$_GET["batch_size"]."', '".$values["pack_size"]."') ";
                 $conn->query($sql);
                 
                }
                  
              }
            
          
            
            
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
    //     else if ($_GET["type"] == "approve_work_order_saipro_pm") {
        
    //      $last_id=0;
    //     $sql0 = "Select count(*)+1 as count from mfg_work_order_hdr where  plant_id = '".$_GET["plant_id"]."' and qa_person!='' ";
    //     $result0 = $conn->query($sql0);
    //     while($row0 = $result->fetch_assoc()){
    //           $last_id = $row0['count'];
    //     }
    //     if($last_id==0){
    //         $last_id=1;
    //     }
    //     $length = 4;
    //     $number = substr(str_repeat(0, $length).$last_id, - $length);
    //     $batch_number =  "B".$number;
    //     $sql ="update mfg_work_order_hdr set status = '".$_GET["status"]."' ,
    //           pm_approved_by ='".$_GET["emp_id"]."',pm_approved_date= '".$entry_date."',
    //             pm_dispensing_status ='Request Sent',
    //             	pm_dispense_request_sent_by	='".$_GET["emp_id"]."', 
    //             pm_dispense_request_sent_on	='$entry_date'
    //           where  id ='".$_GET["id"]."' "; 
             
    //     if ($conn->query($sql)) {
    //           $json_obj1 = json_encode($input["packing_materials"]);
              
    //           $length = strlen($json_obj1);

    //           $i - 0;
    //           for( $i = 0 ; $i< $length ; $i++){
                  
                  
                    
    //          $json_obj = json_encode($input["packing_materials"][$i]['packing_list']);
    //           $array = json_decode($json_obj, true);
               
    //             foreach ($array as $values)
    //             {
                    
    //               $sql = "update mfg_work_order_dtl set actual_qtyy = '".$values["actual_qtyy"]."' where  id ='".$values["d_id"]."' ";
    //              $conn->query($sql);
                 
    //             }
                  
    //           }
            
          
            
            
            
            
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    
    // }
        else if ($_GET["type"] == "get_pm_workorders_for_approval_sppm_data") {
        
        $output = array(); //JOIN stock_book c ON b.material_code = c.material_code left
           $sql = "SELECT *,b.grade as bgrade,b.material_code as m_code,d.id as d_id,a.qty as b_qty FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code LEFT 
                     JOIN (select id,material_code from mfg_work_order_dtl d WHERE d.work_order_id='".$_GET["work_order_id"]."')
                    d on d.material_code=b.material_code WHERE a.material_type = 'Packing Material' and a.batch_plan_id='".$_GET["batch_plan_id"]."' and a.pack_size='".$_GET["pack_size"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {  
                $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row['bgrade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
                 $output[] = $row;
                
            }
        }
        echo json_encode($output);
    
        
        
    }
    else if ($_GET["type"] == "get_pm_workorders_for_approval_sp") {
     $sql = "SELECT a.*, b.id as batch_plan_id, b.bfr_no, b.mfr_no, b.product_code, b.batch_size, p.product_name, p.product_type,
            p.grade, b.pack_size, b.pack_unit
            FROM mfg_work_order_hdr a
            JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
            JOIN product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
            LEFT JOIN mfg_work_order_dtl m ON a.id = m.work_order_id
            WHERE a.plant_id = '" . $_GET["plant_id"] . "'  AND a.status='approved' and a.dispensing_status!='' and raw_qc_intimation!='' and pm_dispensing_status='0'  GROUP by a.id,b.id,p.id, m.actual_qtyy ORDER BY a.id DESC LIMIT 10" ; // Example: limit to 100 rows
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $output = array();
        while ($row = $result->fetch_assoc()) {
            $output1 = array();
        //   echo $sql2 = "SELECT * FROM batch_planing_raw_material_hdr WHERE batch_plan_id = '" . $row["batch_plan_id"] . "' LIMIT 20";
           $sql2 = "SELECT batch_size,pack_size,batch_plan_id,pack_size_unit FROM batch_planing_raw_material_hdr WHERE batch_plan_id = '" . $row["batch_plan_id"] . "' GROUP BY pack_size,batch_size,pack_size_unit";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2 = array();
                      $sql3 = "SELECT *,b.material_code as m_code,d.id as d_id,a.qty as b_qty FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code LEFT 
                    JOIN stock_book c ON b.material_code = c.material_code left JOIN (select id,material_code,batch_overages,total_batch_qty  from mfg_work_order_dtl d WHERE d.work_order_id='".$row["id"]."')
                    d on d.material_code=b.material_code WHERE a.material_type = 'Packing Material' and a.batch_plan_id='".$row2["batch_plan_id"]."' LIMIT 20"; // Example: limit to 50 rows
                    $result3 = $conn->query($sql3);
                    if ($result3->num_rows > 0) {
                        while ($row3 = $result3->fetch_assoc()) {
                            
                            //         $sqlite = "select sum(qty) as avbl_stock from  stock_book where material_code = '".$row3['material_code']."'";
                            //      $result3 = $conn->query($sqlite);
                            // if ($result3->num_rows > 0) {
                            // while ($row66 = $result3->fetch_assoc()) {
                            
                            //     $row3['avbl_stock'] = $row66['avbl_stock'];
                            //     }
                                
                            // }
                            
                             $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row3['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row3['gradeName'] = $prodLatest['gradeName']; 
                        
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
        echo json_encode($output);
    }
}
    else if ($_GET["type"] == "get_PM_Dispensing_Requests_checking") {
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
        //   Where b.plant_id = '".$_GET["plant_id"]."' and wh.tr_to_packing_dept_by !=''where
        //   group by cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
        //   b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit,wh.actual_yeild,bd.stage_yield
        //   ORDER By b.id desc";
          $sql = "SELECT b.*,p.grade,a.pm_store_status_entry_by,a.pm_store_status_entry_date,a.pm_store_remarks,a.id as a_id,a.batch_number,a.work_order_no, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,
          b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
          b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size,a.id as a_id,
              (SELECT COUNT(pm_store_status) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.pm_store_status='pending') as pm_store_status_count
          FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
           left join mfg_work_order_hdr a on b.id= a.batch_plan_id LEFT
           JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
          LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
          LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
          Where b.plant_id = '".$_GET["plant_id"]."' and  (a.pm_store_status='pending' or a.pm_store_status='0')  and a.batch_number!='' and (a.pm_dispensing_status!='0' or a.pm_dispensing_status ='0')
          group by cl.company,p.grade,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,a.id,
          b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight,a.batch_number ,uf.batch_size_unit 
          ORDER By b.id desc LIMIT 50";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql = "select * from batch_planing_raw_material_hdr where batch_plan_id = '".$row["id"]."'";
                $result2 = $conn->query($sql);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2 = array();
                        $sql2 ="SELECT
                                *
                            FROM
                                batch_planning_materials a
                            LEFT JOIN material b ON
                                a.material_code = b.material_code
                            LEFT JOIN stock_book c ON
                                b.material_code = c.material_code
                            left JOIN mfg_work_order_dtl d on
                            b.material_code=d.material_code
                            WHERE
                                a.material_type = 'Packing Material' and a.batch_plan_id='".$row2["batch_plan_id"]."' and d.work_order_id='".$row["a_id"]."'   LIMIT 50;
                                ";
                        $result3 = $conn->query($sql2);
                        if ($result3->num_rows > 0) {
                           while ($row3 = $result3->fetch_assoc()) {
                                     $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row3['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row3['gradeName'] = $prodLatest['gradeName']; 
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
    // else if ($_GET["type"] == "get_PM_Dispensing_Requests_checking") {
    //     $output = array();
    //                     // cmr on 14/03/23
    //     //   $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,wh.actual_yeild,
    //     //   b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
    //     //   b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size
    //     //   FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
    //     //   left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
    //     //   LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
    //     //   LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
    //     //   LEFT join mfg_work_order_hdr wh on b.id = wh.batch_plan_id
    //     //   Where b.plant_id = '".$_GET["plant_id"]."' and wh.tr_to_packing_dept_by !=''
    //     //   group by cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
    //     //   b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit,wh.actual_yeild
    //     //   ORDER By b.id desc";
         
    //     //   $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,wh.actual_yeild,
    //     //   b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
    //     //   b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size,bd.stage_yield
    //     //   FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
    //     //   left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
    //     //   LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
    //     //   LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
    //     //   LEFT join mfg_work_order_hdr wh on b.id = wh.batch_plan_id
    //     //   LEFT JOIN batch_stages_ipqc_dtl bd on b.plan_no = bd.plan_no
    //     //   Where b.plant_id = '".$_GET["plant_id"]."' and wh.tr_to_packing_dept_by !=''
    //     //   group by cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
    //     //   b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit,wh.actual_yeild,bd.stage_yield
    //     //   ORDER By b.id desc";
    //       $sql = "SELECT b.*,p.grade,a.pm_store_status_entry_by,a.pm_store_status_entry_date,a.pm_store_remarks,a.id as a_id,a.batch_number,a.work_order_no, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,
    //       b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
    //       b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size,a.id as a_id
    //       FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
    //       left join mfg_work_order_hdr a on b.id= a.batch_plan_id LEFT
    //       JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
    //       LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
    //       LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
    //       Where b.plant_id = '".$_GET["plant_id"]."' and  a.pm_store_status='pending' and a.batch_number!='' and a.pm_dispensing_status!=''
    //       group by cl.company,p.grade,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,a.id,
    //       b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight,a.batch_number ,uf.batch_size_unit
    //       ORDER By b.id desc LIMIT 50";
       
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $output1 = array();
    //             $sql = "select * from batch_planing_raw_material_hdr where batch_plan_id = '".$row["id"]."'";
    //             $result2 = $conn->query($sql);
    //             if ($result2->num_rows > 0) {
    //                 while ($row2 = $result2->fetch_assoc()) {
    //                     $output2 = array();
    //                     $sql2 ="SELECT
    //                             *
    //                         FROM
    //                             batch_planning_materials a
    //                         LEFT JOIN material b ON
    //                             a.material_code = b.material_code
    //                         LEFT JOIN stock_book c ON
    //                             b.material_code = c.material_code
    //                         left JOIN mfg_work_order_dtl d on
    //                         b.material_code=d.material_code
    //                         WHERE
    //                             a.material_type = 'Packing Material' and a.batch_plan_id='".$row2["batch_plan_id"]."' and d.work_order_id='".$row["a_id"]."'   LIMIT 50;
    //                             ";
    //                     $result3 = $conn->query($sql2);
    //                     if ($result3->num_rows > 0) {
    //                       while ($row3 = $result3->fetch_assoc()) {
    //                                  $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row3['grade']."')";
    //          $resQ = $conn->query($q);
    //           $prodLatest = $resQ->fetch_assoc(); 
         
    //       $row3['gradeName'] = $prodLatest['gradeName']; 
    //                               $output2[] = $row3;
    //                         }
    //                     }
    //                     $row2["packing_material"] = $output2;
    //                      $output1[] = $row2;
    //                 }
    //                 $row["pack_sizes"] = $output1;
    //             }
    //             $output[] = $row;
    //         }
    //     }else{
    //         $output=[];
    //     }
    //     echo json_encode($output);
    // } 
       else if ($_GET["type"] == "get_PM_Dispensing_Requests_Approval") {
        $output = array();
        
          $sql = "SELECT b.*,a.batch_plan_id,p.product_type,p.grade,a.pm_store_status_entry_by,a.pm_store_status_entry_date,a.work_order_no,a.fg_sampling_intimation,a.pm_store_remarks,a.id as a_id,a.batch_number, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,
          b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
          b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size,a.id as a_id
             
          FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
           left join mfg_work_order_hdr a on b.id= a.batch_plan_id LEFT
           JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
          LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
          LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
          Where      a.pm_store_status='checked'  and a.batch_number!='' 
          group by p.grade,cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,a.id,
          b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight,a.batch_number ,uf.batch_size_unit  ,p.product_type
          ORDER By b.id desc LIMIT 50";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                $output1 = array();
                $sql = "select batch_plan_id,pack_size,batch_size from batch_planing_raw_material_hdr where batch_plan_id = '".$row["batch_plan_id"]."' group by batch_plan_id,batch_size,pack_size LIMIT 20";
                $result2 = $conn->query($sql);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2 = array();
                       
                            
                        // $sql2 ="SELECT * FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code
                        //      left JOIN mfg_work_order_dtl d on
                        //     b.material_code=d.material_code  WHERE a.material_type = 'Packing Material' and a.batch_plan_id='".$row2["batch_plan_id"]."'
                        //     and d.work_order_id='".$row["a_id"]."' AND a.pack_size = '".$row2["pack_size"]."'    LIMIT 50 ";
                        $sql2 ="SELECT
                                        *,
                                        (
                                        SELECT
                                            SUM(sb.qty)
                                        FROM
                                            stock_book sb
                                        WHERE
                                            sb.material_code = b.material_code
                                    ) AS avbl_stock
                                    FROM
                                        batch_planning_materials a
                                    LEFT JOIN material b ON
                                        a.material_code = b.material_code
                                    LEFT JOIN mfg_work_order_dtl d ON
                                        b.material_code = d.material_code
                                    WHERE
                                          a.material_type = 'Packing Material' and a.batch_plan_id='".$row["batch_plan_id"]."' and d.work_order_id='".$row["a_id"]."'    LIMIT 50;
                                ";
                                
                                
                        $result3 = $conn->query($sql2);
                        if ($result3->num_rows > 0) {
                           while ($row3 = $result3->fetch_assoc()) {
                                   $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row3['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row3['gradeName'] = $prodLatest['gradeName']; 
                        
                               
                               
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
    // else if ($_GET["type"] == "get_PM_Dispensing_Requests_Approval") {
    //     $output = array();
        
    //       $sql = "SELECT b.*,p.grade,a.pm_store_status_entry_by,a.pm_store_status_entry_date,a.work_order_no,a.fg_sampling_intimation,a.pm_store_remarks,a.id as a_id,a.batch_number, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,
    //       b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
    //       b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size,a.id as a_id,
    //           (SELECT COUNT(pm_store_status) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.pm_store_status='checked') as pm_store_status_count
    //       FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
    //       left join mfg_work_order_hdr a on b.id= a.batch_plan_id LEFT
    //       JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
    //       LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
    //       LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
    //       Where b.plant_id = '".$_GET["plant_id"]."' and (a.pm_store_status='pending' or a.pm_store_status='0' or a.pm_store_status='checked') and a.batch_number!='' and ( a.pm_dispensing_status!='0' or a.pm_dispensing_status='0')
    //       group by p.grade,cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,a.id,
    //       b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight,a.batch_number ,uf.batch_size_unit having pm_store_status_count != '0'
    //       ORDER By b.id desc LIMIT 50";
       
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
             
    //             $output[] = $row;
    //         }
    //     }else{
    //         $output=[];
    //     }
    //     echo json_encode($output);
    // } 
    // else if ($_GET["type"] == "get_PM_Dispensing_Requests_Approval") {
    //     $output = array();
        
    //       $sql = "SELECT b.*,p.grade,a.pm_store_status_entry_by,a.pm_store_status_entry_date,a.work_order_no,a.fg_sampling_intimation,a.pm_store_remarks,a.id as a_id,a.batch_number, b.id,count(b.plan_no) as no_of_batches, b.plan_no,b.plan_type,b.plan_type,b.plan_for,
    //       b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
    //       b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size,a.id as a_id
    //       FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
    //       left join mfg_work_order_hdr a on b.id= a.batch_plan_id LEFT
    //       JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
    //       LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
    //       LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
    //       Where b.plant_id = '".$_GET["plant_id"]."' and  a.pm_store_status='checked' and a.batch_number!='' and a.pm_dispensing_status!=''
    //       group by p.grade,cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,a.id,
    //       b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight,a.batch_number ,uf.batch_size_unit
    //       ORDER By b.id desc LIMIT 50";
       
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $output1 = array();
    //             $sql = "select * from batch_planing_raw_material_hdr where batch_plan_id = '".$row["id"]."'";
    //             $result2 = $conn->query($sql);
    //             if ($result2->num_rows > 0) {
    //                 while ($row2 = $result2->fetch_assoc()) {
    //                     $output2 = array();
    //                     $sql2 ="SELECT
    //                             *
    //                         FROM
    //                             batch_planning_materials a
    //                         LEFT JOIN material b ON
    //                             a.material_code = b.material_code
    //                         LEFT JOIN stock_book c ON
    //                             b.material_code = c.material_code
    //                         left JOIN mfg_work_order_dtl d on
    //                         b.material_code=d.material_code
    //                         WHERE
    //                             a.material_type = 'Packing Material' and a.batch_plan_id='".$row2["batch_plan_id"]."' and d.work_order_id='".$row["a_id"]."'   LIMIT 50;
    //                             ";
    //                     $result3 = $conn->query($sql2);
    //                     if ($result3->num_rows > 0) {
    //                       while ($row3 = $result3->fetch_assoc()) {
    //                                  $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row3['grade']."')";
    //          $resQ = $conn->query($q);
    //           $prodLatest = $resQ->fetch_assoc(); 
         
    //       $row3['gradeName'] = $prodLatest['gradeName']; 
    //                               $output2[] = $row3;
    //                         }
    //                     }
    //                     $row2["packing_material"] = $output2;
    //                      $output1[] = $row2;
    //                 }
    //                 $row["pack_sizes"] = $output1;
    //             }
    //             $output[] = $row;
    //         }
    //     }else{
    //         $output=[];
    //     }
    //     echo json_encode($output);
    // } 
    
    else if ($_GET["type"] == "sp_prev_data") {
         $output = array();
        
        $currentTimestamp = date("Y-m-d H:i:s");
           	$sql = "SELECT p.product_name, p.product_code, b.batch_number, 
                    m.rm_dispensing_lc_date as date_of_cleaning,
                    m.rm_dispensing_lc_by as checked_by,
                    a.entry_by as cleaned_by,
                    a.request_date,
                    a.pleasure_diff_reading,
                    a.laf_eqip_code,
                    a.balance_eqip_code
                    FROM lineclearance a 
                    LEFT JOIN mfg_work_order_hdr m ON a.work_order_id = m.id 
                    LEFT JOIN batch_planning b ON m.batch_plan_id = b.id 
                    LEFT JOIN product p ON b.product_code = p.product_code 
                    WHERE m.rm_dispensing_lc_date < '$entry_date' 
                    AND m.rm_store_lc_status = 'Approved'
                    ORDER BY m.rm_dispensing_lc_date DESC LIMIT 1; ";
          	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output = $row;
    		}
    	}
	
	echo json_encode($output);
         
     }
    else if ($_GET["type"] == "getDispensingAcceptedRequests") {
        $output = array();
        //$sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='pending' GROUP BY d.product_code";
        
         $sql ="SELECT a.id,p.dosage_form,a.batch_number,a.work_order_no,a.entry_by as palnned_by,a.approved_by,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_store_lc_status as store_lc_status,a.rm_store_status as store_status,a.rm_store_status_entry_by as store_status_entry_by,a.rm_store_status_entry_date as store_status_entry_date ";
             }else{
                $sql = $sql." a.pm_store_lc_status as store_lc_status,a.pm_store_status as store_status,a.pm_store_status_entry_by as store_status_entry_by,a.pm_store_status_entry_date as store_status_entry_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." and rm_store_status_entry_by!='' and rm_store_lc_status='Pending' ";
            }else{
                $sql = $sql." and pm_store_status_entry_by!='' and pm_store_lc_status='Pending' ";
            }
            
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
                 FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id join material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id where a.work_order_id = '".$row["id"]."') as a join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
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
    else if ($_GET["type"] == "get_Dispensing_Requests_For_Inprocess_Activity") {
        
          $output = array();
        $fifo_method='';
       echo $sql ="select param_value from software_customization where param_label ='Fifo Method' and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $fifo_method = $row["param_value"];
                
            }
            
        }
        $lod_status='';
        $assay_status='';
         $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,a.lod_status,a.calculation_type,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." and rm_store_status_entry_by!='' and rm_qa_dislc_status='Approved' ";
            }else{
                $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 

                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                      
                                      $output1[] = $row1;
                    }
                }
                $row["fifo_method"] =$fifo_method;
                $row["materials"] = $output1;
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
    }
//      else if ($_GET["type"] == "get_Dispensing_Requests_For_Inprocess_Activity_Formulation") {
         
         
         
//         $output = array();
//         $fifo_method='';
//         $sql ="select param_value from software_customization where param_label ='Fifo Method' and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
//       $result = $conn->query($sql);
//         if ($result->num_rows > 0) {
//             while ($row = $result->fetch_assoc()) {
//             $fifo_method = $row["param_value"];
                
//             }
            
//         }
//         $lod_status='';
//         $assay_status='';
//         $plant_id=$_GET["plant_id"];
//         $material_type = $_GET["material_type"] ?? 'Raw Material';
//         $rpt_type = $_GET["rpt_type"] ?? 'Request';
        
//         // Build lc_status fields based on material_type
//         $lc_status_fields = "";
        
        
//         // Handle work orders from canplan (without batch_plan_id) and from batch_planning (with batch_plan_id)
//           $sql ="SELECT 
//     a.bmr_no,
//     a.id,
//     a.batch_plan_id,
//     a.batch_number,
//     a.work_order_no,
//     a.rm_qa_dislc_date AS planned_by,
//     a.approved_by,
//     a.lod_status,
//     a.calculation_type,
//     a.stability,
//     a.stability_reason,
//     a.process_validation,
//     a.qa_person,
//     a.qa_date,

//     CASE 
//         WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0'
//         THEN (
//             SELECT om.product_code
//             FROM Work_order_materials wm
//             LEFT JOIN order_materials om 
//                 ON wm.order_no = om.order_no
//             WHERE wm.workorder_no = a.work_order_no
//             LIMIT 1
//         )
//         ELSE b.product_code
//     END AS product_code,

//     CASE 
//         WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0'
//         THEN (
//             SELECT p.product_name
//             FROM Work_order_materials wm
//             LEFT JOIN order_materials om 
//                 ON wm.order_no = om.order_no
//             LEFT JOIN product p 
//                 ON om.product_code = p.product_code 
//               AND wm.plant_id = p.plant_id
//             WHERE wm.workorder_no = a.work_order_no
//             LIMIT 1
//         )
//         ELSE p.product_name
//     END AS product_name,

//     CASE 
//         WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0'
//         THEN (
//             SELECT p.product_type
//             FROM Work_order_materials wm
//             LEFT JOIN order_materials om 
//                 ON wm.order_no = om.order_no
//             LEFT JOIN product p 
//                 ON om.product_code = p.product_code 
//               AND wm.plant_id = p.plant_id
//             WHERE wm.workorder_no = a.work_order_no
//             LIMIT 1
//         )
//         ELSE p.product_type
//     END AS product_type,

//     CASE 
//         WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0'
//         THEN (
//             SELECT wm.batch_size
//             FROM Work_order_materials wm
//             WHERE wm.workorder_no = a.work_order_no
//             LIMIT 1
//         )
//         ELSE b.batch_size
//     END AS batch_size,

//     b.plan_no,
//     b.bfr_no,
//     b.mfr_no,
//     b.pack_size,
//     b.pack_unit,

//     a.dispense_request_sent_by,
//     a.dispense_request_sent_on,
//     a.dispensing_status,
//     a.rm_disp_completed_by,

//     a.rm_qa_dislc_status AS lc_status,
//     a.rm_qa_dislc_by AS lc_by,
//     a.rm_qa_dislc_date AS lc_date

// FROM mfg_work_order_hdr a

// LEFT JOIN batch_planning b 
//     ON a.batch_plan_id = b.id 
//   AND a.plant_id = b.plant_id

// LEFT JOIN product p 
//     ON b.product_code = p.product_code 
//   AND b.plant_id = p.plant_id

// WHERE a.plant_id = '$plant_id'
//   AND a.dispensing_status = 'Request Sent'
//   AND (  a.rm_disp_completed_by = '0')
//   AND a.rm_qa_dislc_status = 'Approved'

// ORDER BY a.id DESC;";
     
//         $result = $conn->query($sql);

//         if ($result->num_rows > 0) {
//             while ($row = $result->fetch_assoc()) {
//                 $calculation_type = $row['calculation_type'];
//                 // $row["checkpoints"] = json_decode($row["checkpoints"]);
//                 $output1 = array();
//         //   if($_GET["material_type"] == 'Raw Material'){
             
//                   $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*, b.density,b.uom,b.alternate_uom,b.material_nature,
//                   COALESCE(b.material_type, bm.material_type) AS material_type,
//                   COALESCE(b.material_subtype, bm.material_type) AS material_subtype,
//                   COALESCE(b.material_name, bm.bulkName) AS material_name,
                
//                   b.category, 
//                 IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM mfg_work_order_dtl a 
//                  join mfg_work_order_hdr c on a.work_order_id = c.id
//                  left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
//                  left join bulkMaster bm on a.material_code = bm.bulkCode and c.plant_id = bm.plant_id 
//                  left join dispensing_details_hdr dd on a.id = dd.dtl_id
//                  where a.work_order_id = '".$row["id"]."' ) as a 
//                  left join
//                  ( SELECT material_code,balance_qty as avbl_stock from vw_stock_summary
//                  WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
//                  ) as b on a.material_code = b.material_code
//                  ";
//                 //  ) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                
                
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
                        
//                         if($row1['avbl_stock']== null){
//                             $bSum=" select IFNULL(SUM(mfg_qty), 0) AS qty from bulk_stock where bulkcode='".$row1['material_code']."'";
//                             $resultbSum = $conn->query($bSum);
//                             $rowbSum = $resultbSum->fetch_assoc();
//                             $bSum1=" select IFNULL(SUM(qty), 0) AS issued_qty from material_issue where material_code='".$row1['material_code']."'";
//                             $resultbSum1 = $conn->query($bSum1);
//                             $rowbSum1 = $resultbSum1->fetch_assoc();
                            
//                             $row1['avbl_stock'] = intval($rowbSum['qty']) - intval($rowbSum1['issued_qty']);

//                         }
                        
//                       $output2 = array();
//                       $category = $row1["category"];
//                       if($row1['material_type']!='Bulk'){
                          
                     
//                       $sql2 = "
//                         SELECT
//                             a.*,
//                             IFNULL(b.issued_qty, 0) AS issued_qty,
//                             FLOOR((a.qty - IFNULL(b.issued_qty, 0)) / a.pack_size) AS intact_containers
//                         FROM
//                         (
//                             SELECT IFNULL(SUM(qty), 0) AS qty, exp_date, mfg_date, grn_no, grn_date, vendor_no, ar_no, pack_size, batch_no, containers, lod_per
//                             FROM stock_book
//                             WHERE
//                                 material_code = '".$row1["material_code"]."'
//                                 AND status = 'Approved'
//                             GROUP BY exp_date, mfg_date, grn_no, grn_date, vendor_no, ar_no, pack_size, batch_no, containers, lod_per
//                         ) a
//                         LEFT JOIN
//                         (
//                             SELECT
//                                 ar_no,
//                                 IFNULL(SUM(qty), 0) AS issued_qty
//                             FROM material_issue
//                             GROUP BY ar_no
//                         ) b
//                         ON TRIM(a.ar_no) = TRIM(b.ar_no)
//                         ORDER BY a.ar_no ASC
//                         ";
//                          }
//                       else if($row1['material_type']=='Bulk'){
                          
                     
//                       $sql2 = "
//                         SELECT
//                             a.*,
//                             IFNULL(b.issued_qty, 0) AS issued_qty
                            
//                         FROM
//                         (
//                             SELECT IFNULL(SUM(mfg_qty), 0) AS qty, exp_date, mfg_date,  arno as  ar_no
//                             FROM bulk_stock
//                             WHERE
//                                 bulkcode = '".$row1["material_code"]."'
//                                 AND status = 'Active'
//                             GROUP BY exp_date, mfg_date,arno
//                         ) a
//                         LEFT JOIN
//                         (
//                             SELECT
//                                 ar_no,
//                                 IFNULL(SUM(qty), 0) AS issued_qty
//                             FROM material_issue
//                             GROUP BY ar_no
//                         ) b
//                         ON TRIM(a.ar_no) = TRIM(b.ar_no)
//                         ORDER BY a.ar_no ASC
//                         ";
//                          }
//                           $result2 = $conn->query($sql2);
//                       if ($result2->num_rows > 0) {
//                             while ($row2 = $result2->fetch_assoc()) {
                               
                               
                                
           
//               $sql11 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row1["material_code"]."' ";
//                 $result11 = $conn->query($sql11);
//                 if ($result1->num_rows > 0) {
//                     while ($row11 = $result11->fetch_assoc()) {
//                          $row2["qty1"] = $row11["qty"];
//                     }
//                 }
                
                
                
                
                
                
//                      $sql110 = "SELECT vendor_name,vendor_no FROM vendor where vendor_no =  '".$row2["vendor_no"]."' ";
//                 $result100 = $conn->query($sql110);
//                 if ($result100->num_rows > 0) {
//                     while ($row110 = $result100->fetch_assoc()) {
//                          $row2["vendor_name"] = $row110['vendor_name'];
//                          $row2["vendor_no"] = $row110['vendor_no'];
//                     }
//                 }
                
                
                
                
                
                
                
              
//                 $row2["issue_qty1"]=0;
//                  $sql11 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
//                  (SELECT ar_no FROM stock_book WHERE material_code='".$row1["material_code"]."' and ar_no='".$row2["ar_no"]."')";
            
//                 $result11 = $conn->query($sql11);
//                 if ($result11->num_rows > 0) {
//                     while ($row11 = $result11->fetch_assoc()) {
//                         $row2["mmm_qty"] = $row11["m_qty"];
//                     }
//                 }
                
//                  $row2["balance_qty"] = $row2["qty"] - $row2["issue_qty1"]- $row2["mmm_qty"];
//                 $row2["Issue"] = $row2["issue_qty1"]+ $row2["m_qty"];    
//                   $row2["pure_qtys"] = ($row2["balance_qty"] * $row2["assay_qty"])/100;
               


                
//                  if ($row2["balance_qty"] < 0) {
//                                     $row2["balance_qty"] = 0; // Use = to assign the value
//                                 }
                
//                                 $output2[] = $row2;
//                             }
//                         }

//                         $row1["available_ars"] = $output2;
                        
//                         $row1["containers"] = json_decode($row1["containers"]);
//                         $row1["ars"] = json_decode($row1["ars"]);
//                         if ($row1["status"] == "pending") {
//                             $flag = 1;
//                         }
                        
                        
                        
//             $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['grade']."')";
//              $resQ = $conn->query($q);
//               $prodLatest = $resQ->fetch_assoc(); 
         
//           $row1['gradeName'] = $prodLatest['gradeName']; 
                        
                      
//                                       $output1[] = $row1;
                    
                        
//                     }
//                 }
//                 $row["fifo_method"] =$fifo_method;
                
//                 $row["materials"] = $output1;
                
                
                
                
//                  $jadugar = 0;
            
//             foreach ($output1 as $material) {
              
//               if ($material["dispence_id"] == '0'){
//                     $jadugar++;
                    
//                 }
                
//             }
                    
            
            
//             if(  $jadugar > 0){
                
//                  $output[] = $row;
              
                
//             }else if($jadugar == 0){
//                  $sql = "UPDATE mfg_work_order_hdr SET  
//         rm_disp_completed_date='".$entry_date."', rm_disp_completed_by='".$_GET["emp_id"]."'  WHERE id='".$row["id"]."'";
         
//       $conn->query($sql);
                
//             }
                
                
                   
                
//                 // $output[] = $row;
                
                
                
//             }
            
//         } 
        
        
//         echo json_encode($output);
         
     
//      }
     else if ($_GET["type"] == "get_Dispensing_Requests_For_Inprocess_Activity_Formulation") {
         
         
         
        $output = array();
        $fifo_method='';
        $sql ="select param_value from software_customization where param_label ='Fifo Method' and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $fifo_method = $row["param_value"];
                
            }
            
        }
        $lod_status='';
        $assay_status='';
        $material_type = $_GET["material_type"] ?? 'Raw Material';
        $rpt_type = $_GET["rpt_type"] ?? 'Request';
        
        // Build lc_status fields based on material_type
        $lc_status_fields = "";
        
        
        // Handle work orders from canplan (without batch_plan_id) and from batch_planning (with batch_plan_id)
          $sql ="SELECT 
    a.bmr_no,
    a.id,
    a.batch_plan_id,
    a.batch_number,
    a.work_order_no,
    a.rm_qa_dislc_date AS planned_by,
    a.approved_by,
    a.lod_status,
    a.calculation_type,
    a.stability,
    a.stability_reason,
    a.process_validation,
    a.qa_person,
    a.qa_date,

    CASE 
        WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0'
        THEN (
            SELECT om.product_code
            FROM Work_order_materials wm
            LEFT JOIN order_materials om 
                ON wm.order_no = om.order_no
            WHERE wm.workorder_no = a.work_order_no
            LIMIT 1
        )
        ELSE b.product_code
    END AS product_code,

    CASE 
        WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0'
        THEN (
            SELECT p.product_name
            FROM Work_order_materials wm
            LEFT JOIN order_materials om 
                ON wm.order_no = om.order_no
            LEFT JOIN product p 
                ON om.product_code = p.product_code 
               AND wm.plant_id = p.plant_id
            WHERE wm.workorder_no = a.work_order_no
            LIMIT 1
        )
        ELSE p.product_name
    END AS product_name,

    CASE 
        WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0'
        THEN (
            SELECT p.product_type
            FROM Work_order_materials wm
            LEFT JOIN order_materials om 
                ON wm.order_no = om.order_no
            LEFT JOIN product p 
                ON om.product_code = p.product_code 
               AND wm.plant_id = p.plant_id
            WHERE wm.workorder_no = a.work_order_no
            LIMIT 1
        )
        ELSE p.product_type
    END AS product_type,

    CASE 
        WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0'
        THEN (
            SELECT wm.batch_size
            FROM Work_order_materials wm
            WHERE wm.workorder_no = a.work_order_no
            LIMIT 1
        )
        ELSE b.batch_size
    END AS batch_size,

    b.plan_no,
    b.bfr_no,
    b.mfr_no,
    b.pack_size,
    b.pack_unit,

    a.dispense_request_sent_by,
    a.dispense_request_sent_on,
    a.dispensing_status,
    a.rm_disp_completed_by,

    a.rm_qa_dislc_status AS lc_status,
    a.rm_qa_dislc_by AS lc_by,
    a.rm_qa_dislc_date AS lc_date

FROM mfg_work_order_hdr a

LEFT JOIN batch_planning b 
    ON a.batch_plan_id = b.id 
   AND a.plant_id = b.plant_id

LEFT JOIN product p 
    ON b.product_code = p.product_code 
   AND b.plant_id = p.plant_id

WHERE a.plant_id ='".$_GET['plant_id']."'
  AND a.dispensing_status = 'Request Sent'
  AND  a.rm_disp_completed_by = '0'
  AND a.rm_qa_dislc_status = 'Approved'

ORDER BY a.id DESC;";
//   AND (  a.rm_disp_completed_by != '0')
//   AND a.rm_qa_dislc_status = 'Approved'

// ORDER BY a.id DESC;";
     
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                // $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
        //   if($_GET["material_type"] == 'Raw Material'){
             
                  $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*, b.density,b.uom,b.alternate_uom,b.material_nature,
                  COALESCE(b.material_type, bm.material_type) AS material_type,
                  COALESCE(b.material_subtype, bm.material_type) AS material_subtype,
                  COALESCE(b.material_name, bm.bulkName) AS material_name,
                
                  b.category, 
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM mfg_work_order_dtl a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join bulkMaster bm on a.material_code = bm.bulkCode and c.plant_id = bm.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.dtl_id
                 where a.work_order_id = '".$row["id"]."' ) as a 
                 left join
                 ( SELECT material_code,balance_qty as avbl_stock from vw_stock_summary
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                 ) as b on a.material_code = b.material_code
                 ";
                //  ) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        if($row1['avbl_stock']== null){
                            $bSum=" select IFNULL(SUM(mfg_qty), 0) AS qty from bulk_stock where bulkcode='".$row1['material_code']."'";
                            $resultbSum = $conn->query($bSum);
                            $rowbSum = $resultbSum->fetch_assoc();
                            $bSum1=" select IFNULL(SUM(qty), 0) AS issued_qty from material_issue where material_code='".$row1['material_code']."'";
                            $resultbSum1 = $conn->query($bSum1);
                            $rowbSum1 = $resultbSum1->fetch_assoc();
                            
                            $row1['avbl_stock'] = intval($rowbSum['qty']) - intval($rowbSum1['issued_qty']);

                        }
                        
                      $output2 = array();
                      $category = $row1["category"];
                      if($row1['material_type']!='Bulk'){
                          
                     
                       $sql2 = "
                        SELECT
                            a.*,
                            IFNULL(b.issued_qty, 0) AS issued_qty,
                            FLOOR((a.qty - IFNULL(b.issued_qty, 0)) / a.pack_size) AS intact_containers
                        FROM
                        (
                            SELECT IFNULL(SUM(qty), 0) AS qty, exp_date, mfg_date, grn_no, grn_date, vendor_no, ar_no, pack_size, batch_no, containers, lod_per
                            FROM stock_book
                            WHERE
                                material_code = '".$row1["material_code"]."'
                                AND status = 'Approved'
                            GROUP BY exp_date, mfg_date, grn_no, grn_date, vendor_no, ar_no, pack_size, batch_no, containers, lod_per
                        ) a
                        LEFT JOIN
                        (
                            SELECT
                                ar_no,
                                IFNULL(SUM(qty), 0) AS issued_qty
                            FROM material_issue
                            GROUP BY ar_no
                        ) b
                        ON TRIM(a.ar_no) = TRIM(b.ar_no)
                        ORDER BY a.ar_no ASC
                        ";
                         }
                      else if($row1['material_type']=='Bulk'){
                          
                     
                       $sql2 = "
                        SELECT
                            a.*,
                            IFNULL(b.issued_qty, 0) AS issued_qty
                            
                        FROM
                        (
                            SELECT IFNULL(SUM(mfg_qty), 0) AS qty, exp_date, mfg_date,  arno as  ar_no
                            FROM bulk_stock
                            WHERE
                                bulkcode = '".$row1["material_code"]."'
                                AND status = 'Active'
                            GROUP BY exp_date, mfg_date,arno
                        ) a
                        LEFT JOIN
                        (
                            SELECT
                                ar_no,
                                IFNULL(SUM(qty), 0) AS issued_qty
                            FROM material_issue
                            GROUP BY ar_no
                        ) b
                        ON TRIM(a.ar_no) = TRIM(b.ar_no)
                        ORDER BY a.ar_no ASC
                        ";
                         }
                          $result2 = $conn->query($sql2);
                       if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                               
                               
                                
           
              $sql11 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row1["material_code"]."' ";
                $result11 = $conn->query($sql11);
                if ($result1->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                         $row2["qty1"] = $row11["qty"];
                    }
                }
                
                
                
                
                
                
                     $sql110 = "SELECT vendor_name,vendor_no FROM vendor where vendor_no =  '".$row2["vendor_no"]."' ";
                $result100 = $conn->query($sql110);
                if ($result100->num_rows > 0) {
                    while ($row110 = $result100->fetch_assoc()) {
                         $row2["vendor_name"] = $row110['vendor_name'];
                         $row2["vendor_no"] = $row110['vendor_no'];
                    }
                }
                
                
                
                
                
                
                
              
                $row2["issue_qty1"]=0;
                 $sql11 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                 (SELECT ar_no FROM stock_book WHERE material_code='".$row1["material_code"]."' and ar_no='".$row2["ar_no"]."')";
            
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $row2["mmm_qty"] = $row11["m_qty"];
                    }
                }
                
                 $row2["balance_qty"] = $row2["qty"] - $row2["issue_qty1"]- $row2["mmm_qty"];
                $row2["Issue"] = $row2["issue_qty1"]+ $row2["m_qty"];    
                  $row2["pure_qtys"] = ($row2["balance_qty"] * $row2["assay_qty"])/100;
               


                
                 if ($row2["balance_qty"] < 0) {
                                    $row2["balance_qty"] = 0; // Use = to assign the value
                                }
                
                                $output2[] = $row2;
                            }
                        }

                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                        
                        
                        
            $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
                        
                      
                                      $output1[] = $row1;
                    
                        
                    }
                }
                $row["fifo_method"] =$fifo_method;
                
                $row["materials"] = $output1;
                
                
                
                
                 $jadugar = 0;
            
            foreach ($output1 as $material) {
              
               if ($material["dispence_id"] == '0'){
                    $jadugar++;
                    
                }
                
            }
                    
            
            
            if(  $jadugar > 0){
                
                 $output[] = $row;
              
                
            }else if($jadugar == 0){
                 $sql = "UPDATE mfg_work_order_hdr SET  
        rm_disp_completed_date='".$entry_date."', rm_disp_completed_by='".$_GET["emp_id"]."'  WHERE id='".$row["id"]."'";
         
       $conn->query($sql);
                
            }
                
                
                   
                
                // $output[] = $row;
                
                
                
            }
            
        } 
        
        
        echo json_encode($output);
         
     
     }
     else if ($_GET["type"] == "get_Dispensing_Requests_For_Inprocess_Activity_FormulationComplete") {
         
        $output = array();
        $fifo_method='';
        $sql ="select param_value from software_customization where param_label ='Fifo Method' and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $fifo_method = $row["param_value"];
                
            }
            
        }
        $lod_status='';
        $assay_status='';
          $sql ="SELECT a.bmr_no,a.id,a.batch_plan_id,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,a.lod_status,a.calculation_type,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,rm_disp_completed_by,
               a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id  where  a.plant_id ='".$_GET["plant_id"]."'
             and    a.rm_qa_dislc_status='Approved'  ";
            
     
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                if($_GET["material_type"] == 'Raw Material'){
             
                 $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.grade,b.density,  b.uom,b.alternate_uom,b.material_nature,b.unit_conversion ,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,
                wd.assay_status,IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 ( SELECT material_code,balance_qty as avbl_stock from vw_stock_summary
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 
                 
                 ) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                }else{
                 $sql1=" SELECT a.*,bp.country_name,bp.pack_size,bp.pack_size_unit, IFNULL(b.uom,'Nos') as uom,IFNULL(dd.id,0) as dispence_id,IFNULL(b.qty,0) as avbl_stock,material_name,material_subtype from batch_planning_materials a left join(
                        SELECT mt.plant_id,mt.uom,mt.material_code,mt.material_name,sum(sb.qty) as qty,mt.material_subtype FROM material
                        mt left join stock_book sb on mt.material_code = sb.material_code and mt.plant_id = sb.plant_id 
                        group by mt.material_code,mt.material_name,mt.material_subtype,mt.plant_id,mt.uom)b
                        on a.material_code = b.material_code and a.plant_id = b.plant_id 
                        left join dispensing_details_hdr dd on a.pm_hdr_id = dd.pm_hdr_id
                        left JOIN batch_planing_raw_material_hdr bp on a.pm_hdr_id = bp.id
                        where a.material_type='Packing Material'
                        and a.batch_plan_id='".$row["batch_plan_id"]."'"   ;
                }
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                      $output2 = array();
                      $category = $row1["category"];
                       $sql2="SELECT
    a.*,
    IFNULL(b.issued_qty, 0) as issued_qty,
    FLOOR((a.qty - IFNULL(b.issued_qty, 0)) / a.pack_size) as intact_containers,
    (a.qty - IFNULL(b.issued_qty, 0)) - (a.pack_size * (FLOOR((a.qty - IFNULL(b.issued_qty, 0)) / a.pack_size))) as loose_Qty,
    CASE WHEN 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' THEN ROUND((a.qty - IFNULL(b.issued_qty, 0)) - (((a.qty - IFNULL(b.issued_qty, 0)) * lod_per) / 100), 2) ELSE 0 END as dry_qty,
    CASE WHEN 'LOD Basis' = 'Assay Basis' AND 'Active' = 'Active' THEN ROUND((a.qty - IFNULL(b.issued_qty, 0)) - (((a.qty - IFNULL(b.issued_qty, 0)) * assay) / 100), 2) ELSE 0 END as pure_qty,
    ('".$row1["batch_qty"]."' * 100) / assay_qty as pure_qtys,
    ('".$row1["batch_qty"]."' * (SELECT equivalancy_factor FROM material WHERE material_code = '".$row1["material_code"]."')) AS material_e_factor,
c.lod_qty,
c.lod_test,
assay_qty,
test_assay,
specification_no 
FROM
    (
    SELECT IFNULL(SUM(qty), 0) AS qty,exp_date,mfg_date, grn_no,grn_date,vendor_no, ar_no, undertest_qty, pack_size, batch_no, containers, lod_per, assay
    FROM
        stock_book
    WHERE
        material_code = '".$row1["material_code"]."' AND
    STATUS
        = 'Approved'
    GROUP BY id,grn_no,grn_date, ar_no, pack_size, undertest_qty, batch_no, containers, lod_per, assay ORDER by ar_no asc
) a
LEFT JOIN(
    SELECT  ar_no, IFNULL(SUM(qty), 0) AS issued_qty
    FROM
        material_issue
    GROUP BY  ar_no
) b
ON
    TRIM(a.ar_no) = TRIM(b.ar_no)
LEFT JOIN(
    SELECT
        a.result AS lod_qty, a.test AS lod_test
    FROM
        testing_tests  a
    LEFT JOIN specification b ON
        a.specification_no = b.specification_no
    WHERE
        b.material_code = '".$row1["material_code"]."' AND a.test = 'LOD'
    ORDER BY
        a.id
    DESC
LIMIT 1
) c
ON
    c.lod_test = 'LOD'
LEFT JOIN(
    SELECT
        a.result AS assay_qty,
        a.test AS test_assay,
    	a.specification_no
    FROM
        testing_tests  a
    LEFT JOIN specification b ON
        a.specification_no = b.specification_no
    WHERE
        b.material_code = '".$row1["material_code"]."' AND a.test LIKE '%Assay%'
    ORDER BY
        a.id
    DESC
LIMIT 1
) d
ON
    d.test_assay LIKE '%Assay%'";
                          $result2 = $conn->query($sql2);
                       if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                               
                               
                                
                                
              $sql11 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row1["material_code"]."' ";
                $result11 = $conn->query($sql11);
                if ($result1->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                         $row2["qty1"] = $row11["qty"];
                    }
                }
                
                
                
                
                
                
                     $sql110 = "SELECT vendor_name,vendor_no FROM vendor where vendor_no =  '".$row2["vendor_no"]."' ";
                $result100 = $conn->query($sql110);
                if ($result100->num_rows > 0) {
                    while ($row110 = $result100->fetch_assoc()) {
                         $row2["vendor_name"] = $row110['vendor_name'];
                         $row2["vendor_no"] = $row110['vendor_no'];
                    }
                }
                
                
                
                
                
                
                
                 $sql11 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$row1["material_code"]."' ";
            // echo    $sql1 = "SELECT IFNULL(SUM(qty), 0) as issue_qty FROM material_issue WHERE ar_no IN 
            //     (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved' 
            //     AND challan_for='EOU')";
                $result11 = $conn->query($sql11);
                if ($result1->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $row2["issue_qty1"] = $row11["issue_qty"];
                    }
                }
                 $sql11 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                 (SELECT ar_no FROM stock_book WHERE material_code='".$row1["material_code"]."' and ar_no='".$row2["ar_no"]."')";
            // echo    $sql1 = "SELECT IFNULL(SUM(qty), 0) as issue_qty FROM material_issue WHERE ar_no IN 
            //     (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved' 
            //     AND challan_for='EOU')";
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $row2["mmm_qty"] = $row11["m_qty"];
                    }
                }
                
                 $row2["balance_qty"] = $row2["qty"] - $row2["issue_qty1"]- $row2["mmm_qty"];
                $row2["Issue"] = $row2["issue_qty1"]+ $row2["m_qty"];      
                
                 if ($row2["balance_qty"] < 0) {
                                    $row2["balance_qty"] = 0; // Use = to assign the value
                                }
                
                                $output2[] = $row2;
                            }
                        }

                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                        
                        
                        
            $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
                        
                      
                                      $output1[] = $row1;
                    
                        
                    }
                }
                $row["fifo_method"] =$fifo_method;
                
                $row["materials"] = $output1;
                
                
                
                 
                 $output[] = $row;
              
          
                   
                
                // $output[] = $row;
                
                
                
            }
            
        } 
        
        
        echo json_encode($output);
         
     }
     else if ($_GET["type"] == "get_Dispensing_Requests_For_Inprocess_Activity_Formulation1") {
         
        $output = array();
        $fifo_method='';
        $sql ="select param_value from software_customization where param_label ='Fifo Method' and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $fifo_method = $row["param_value"];
                
            }
            
        }
        $lod_status='';
        $assay_status='';
         $sql ="SELECT a.bmr_no,a.id,a.batch_plan_id,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,a.lod_status,a.calculation_type,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'
             and dispensing_status ='Request Sent'   "; 
             
            if($_GET["material_type"] == 'Raw Material' && $_GET["rpt_type"]=='Request'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                $sql = $sql." and rm_disp_completed_by='' and rm_qa_dislc_status='Approved' order by a.id desc ";
            }else if($_GET["material_type"] == 'Packing Material' && $_GET["rpt_type"]=='Request'){
                $sql = $sql." and pm_store_status_entry_by!=''" ;
            }
            else  if($_GET["material_type"] == 'Raw Material' && $_GET["rpt_type"]=='Completed'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved'  ";
            }
            else{
                $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
     
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                if($_GET["material_type"] == 'Raw Material'){
                // $sql1 = "SELECT
                //     a.lot_no,
                //     GROUP_CONCAT(DISTINCT a.material_code) AS material_codes,    SUM(a.batch_qty) AS total_batch_qty,    MAX(a.status) AS status,
                //     MAX(a.material_type) AS material_type,    MAX(a.category) AS category,    MAX(a.material_subtype) AS material_subtype,
                //     MAX(a.material_name) AS material_name,    MAX(a.lod_status) AS lod_status,    MAX(a.assay_status) AS assay_status,
                //     MAX(a.qa_status) AS qa_status,    MAX(a.prod_status) AS prod_status,    MAX(b.avbl_stock) AS avbl_stock
                // FROM (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status, IFNULL(dd.id,0) as dispence_id
                // ,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots a join mfg_work_order_hdr c on a.work_order_id = c.id left
                // join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code left join material b on a.material_code = b.material_code
                // and c.plant_id = b.plant_id left join dispensing_details_hdr dd on a.id = dd.lot_id where a.work_order_id = '".$row["id"]."') AS a LEFT JOIN 
                // ( SELECT material_code,balance_qty as avbl_stock from vw_stock_summary WHERE plant_id='".$_GET["plant_id"]."' and material_code in 
                // (SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                // ) AS b ON a.material_code = b.material_code AND a.material_type = '".$_GET["material_type"]."'
                // WHERE a.work_order_id = '".$row["id"]."'
                // GROUP BY a.lot_no; ";
                 $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.grade,b.density,  b.uom,b.alternate_uom,b.material_nature,b.unit_conversion ,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,
                wd.assay_status,IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 ( SELECT material_code,balance_qty as avbl_stock from vw_stock_summary
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 
                 
                 ) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                }else{
                 $sql1=" SELECT a.*,bp.country_name,bp.pack_size,bp.pack_size_unit, IFNULL(b.uom,'Nos') as uom,IFNULL(dd.id,0) as dispence_id,IFNULL(b.qty,0) as avbl_stock,material_name,material_subtype from batch_planning_materials a left join(
                        SELECT mt.plant_id,mt.uom,mt.material_code,mt.material_name,sum(sb.qty) as qty,mt.material_subtype FROM material
                        mt left join stock_book sb on mt.material_code = sb.material_code and mt.plant_id = sb.plant_id 
                        group by mt.material_code,mt.material_name,mt.material_subtype,mt.plant_id,mt.uom)b
                        on a.material_code = b.material_code and a.plant_id = b.plant_id 
                        left join dispensing_details_hdr dd on a.pm_hdr_id = dd.pm_hdr_id
                        left JOIN batch_planing_raw_material_hdr bp on a.pm_hdr_id = bp.id
                        where a.material_type='Packing Material'
                        and a.batch_plan_id='".$row["batch_plan_id"]."'"   ;
                }
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT
    a.*,
    IFNULL(b.issued_qty, 0) as issued_qty,
    FLOOR((a.qty - IFNULL(b.issued_qty, 0)) / a.pack_size) as intact_containers,
    (a.qty - IFNULL(b.issued_qty, 0)) - (a.pack_size * (FLOOR((a.qty - IFNULL(b.issued_qty, 0)) / a.pack_size))) as loose_Qty,
    CASE WHEN 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' THEN ROUND((a.qty - IFNULL(b.issued_qty, 0)) - (((a.qty - IFNULL(b.issued_qty, 0)) * lod_per) / 100), 2) ELSE 0 END as dry_qty,
    CASE WHEN 'LOD Basis' = 'Assay Basis' AND 'Active' = 'Active' THEN ROUND((a.qty - IFNULL(b.issued_qty, 0)) - (((a.qty - IFNULL(b.issued_qty, 0)) * assay) / 100), 2) ELSE 0 END as pure_qty,
    ('".$row1["batch_qty"]."' * 100) / assay_qty as pure_qtys,
    ('".$row1["batch_qty"]."' * (SELECT equivalancy_factor FROM material WHERE material_code = '".$row1["material_code"]."')) AS material_e_factor,
c.lod_qty,
c.lod_test,
assay_qty,
test_assay,
specification_no 
FROM
    (
    SELECT IFNULL(SUM(qty), 0) AS qty, grn_no,grn_date, ar_no, undertest_qty, pack_size, batch_no, containers, lod_per, assay
    FROM
        stock_book
    WHERE
        material_code = '".$row1["material_code"]."' AND
    STATUS
        = 'Approved'
    GROUP BY grn_no,grn_date, ar_no, pack_size, undertest_qty, batch_no, containers, lod_per, assay ORDER by grn_date ASC
) a
LEFT JOIN(
    SELECT  ar_no, IFNULL(SUM(qty), 0) AS issued_qty
    FROM
        material_issue
    GROUP BY  ar_no
) b
ON
    TRIM(a.ar_no) = TRIM(b.ar_no)
LEFT JOIN(
    SELECT
        a.result AS lod_qty, a.test AS lod_test
    FROM
        testing_tests  a
    LEFT JOIN specification b ON
        a.specification_no = b.specification_no
    WHERE
        b.material_code = '".$row1["material_code"]."' AND a.test = 'LOD'
    ORDER BY
        a.id
    DESC
LIMIT 1
) c
ON
    c.lod_test = 'LOD'
LEFT JOIN(
    SELECT
        a.result AS assay_qty,
        a.test AS test_assay,
    	a.specification_no
    FROM
        testing_tests  a
    LEFT JOIN specification b ON
        a.specification_no = b.specification_no
    WHERE
        b.material_code = '".$row1["material_code"]."' AND a.test LIKE '%Assay%'
    ORDER BY
        a.id
    DESC
LIMIT 1
) d
ON
    d.test_assay LIKE '%Assay%'";
//                       $sql2="SELECT
//     a.*,
//     IFNULL(b.issued_qty, 0) as issued_qty,
//     FLOOR((a.qty - IFNULL(b.issued_qty, 0)) / a.pack_size) as intact_containers,
//     (a.qty - IFNULL(b.issued_qty, 0)) - (a.pack_size * (FLOOR((a.qty - IFNULL(b.issued_qty, 0)) / a.pack_size))) as loose_Qty,
//     CASE WHEN 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' THEN ROUND((a.qty - IFNULL(b.issued_qty, 0)) - (((a.qty - IFNULL(b.issued_qty, 0)) * lod_per) / 100), 2) ELSE 0 END as dry_qty,
//     CASE WHEN 'LOD Basis' = 'Assay Basis' AND 'Active' = 'Active' THEN ROUND((a.qty - IFNULL(b.issued_qty, 0)) - (((a.qty - IFNULL(b.issued_qty, 0)) * assay) / 100), 2) ELSE 0 END as pure_qty,
//     ('".$row1["batch_qty"]."' * 100) / assay_qty as pure_qtys,
//     ('".$row1["batch_qty"]."' * (SELECT equivalancy_factor FROM material WHERE material_code = '".$row1["material_code"]."')) AS material_e_factor,
// c.lod_qty,
// c.lod_test,
// assay_qty,
// test_assay,
// specification_no 
// FROM
//     (
//     SELECT IFNULL(SUM(qty), 0) AS qty, grn_no,grn_date, ar_no, undertest_qty, pack_size, batch_no, containers, lod_per, assay
//     FROM
//         stock_book
//     WHERE
//         material_code = '".$row1["material_code"]."' AND
//     STATUS
//         = 'Approved'
//     GROUP BY grn_no,grn_date, ar_no, pack_size, undertest_qty, batch_no, containers, lod_per, assay ORDER by grn_date ASC
// ) a
// LEFT JOIN(
//     SELECT grn_no, ar_no, IFNULL(SUM(qty), 0) AS issued_qty
//     FROM
//         material_issue
//     GROUP BY grn_no, ar_no
// ) b
// ON
//     TRIM(a.ar_no) = TRIM(b.ar_no)
// LEFT JOIN(
//     SELECT
//         a.result AS lod_qty, a.test AS lod_test
//     FROM
//         testing_tests  a
//     LEFT JOIN specification b ON
//         a.specification_no = b.specification_no
//     WHERE
//         b.material_code = '".$row1["material_code"]."' AND a.test = 'LOD'
//     ORDER BY
//         a.id
//     DESC
// LIMIT 1
// ) c
// ON
//     c.lod_test = 'LOD'
// LEFT JOIN(
//     SELECT
//         a.result AS assay_qty,
//         a.test AS test_assay,
//     	a.specification_no
//     FROM
//         testing_tests  a
//     LEFT JOIN specification b ON
//         a.specification_no = b.specification_no
//     WHERE
//         b.material_code = '".$row1["material_code"]."' AND a.test LIKE '%Assay%'
//     ORDER BY
//         a.id
//     DESC
// LIMIT 1
// ) d
// ON
//     d.test_assay LIKE '%Assay%'";
        //               $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty,         //  (a.qty-IFNULL(a.undertest_qty,0)-IFNULL(b.issued_qty,0)) as balance_qty,        //  floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,           //  (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
        //  case when 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty, //  case when 'LOD Basis' ='Assay Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty,//  c.lod_qty, c.lod_test//  FROM (SELECT IFNULL(SUM(qty), 0) as qty, grn_no,ar_no ,undertest_qty,pack_size ,batch_no,containers,lod_per,assay //       FROM stock_book//       WHERE material_code='".$row1["material_code"]."' AND status='Approved' //       GROUP BY grn_no,ar_no,pack_size,undertest_qty, batch_no,containers,lod_per,assay) a//  LEFT JOIN (SELECT grn_no,ar_no, IFNULL(SUM(qty), 0) as issued_qty //             FROM material_issue //             GROUP BY grn_no,ar_no) b ON TRIM(a.ar_no) = TRIM(b.ar_no)//  LEFT JOIN (SELECT a.lower_limit as lod_qty, a.test as lod_test //             FROM spec_tests a //             LEFT JOIN specification b ON a.specification_no=b.specification_no //             WHERE b.material_code='".$row1["material_code"]."' AND a.test='LOD' order by a.id  desc limit 1) c ON c.lod_test = 'LOD' //   ";
            //   working
//   $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(a.undertest_qty,0)-IFNULL(b.issued_qty,0)) as balance_qty,//             floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers, (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, case when 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty, case when 'LOD Basis' ='Assay Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty from//         (SELECT IFNULL(SUM(qty), 0) as qty, grn_no,ar_no ,undertest_qty,pack_size ,batch_no,containers,lod_per,assay FROM stock_book//     WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY grn_no,ar_no,pack_size,undertest_qty, batch_no,containers,lod_per,assay) a//     left join (SELECT grn_no,ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by grn_no,ar_no)b on TRIM(a.ar_no) = TRIM(b.ar_no)";//   $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(a.undertest_qty,0)-IFNULL(b.issued_qty,0)) as balance_qty,//             floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers, (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, case when 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty, case when 'LOD Basis' ='Assay Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty from//         (SELECT IFNULL(SUM(qty), 0) as qty, grn_no,ar_no ,undertest_qty,pack_size ,batch_no,containers,lod_per,assay FROM stock_book//     WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY grn_no,ar_no,pack_size,undertest_qty, batch_no,containers,lod_per,assay) a//     left join (SELECT grn_no,ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by grn_no,ar_no) b on a.ar_no = b.ar_no and a.grn_no = b.grn_no"; 
 
                          $result2 = $conn->query($sql2);
                       if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                               
                               
                                
                                
              $sql11 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row1["material_code"]."' ";
                $result11 = $conn->query($sql11);
                if ($result1->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                         $row2["qty1"] = $row11["qty"];
                    }
                }
                 $sql11 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$row1["material_code"]."' ";
            // echo    $sql1 = "SELECT IFNULL(SUM(qty), 0) as issue_qty FROM material_issue WHERE ar_no IN 
            //     (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved' 
            //     AND challan_for='EOU')";
                $result11 = $conn->query($sql11);
                if ($result1->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $row2["issue_qty1"] = $row11["issue_qty"];
                    }
                }
                 $sql11 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                 (SELECT ar_no FROM stock_book WHERE material_code='".$row1["material_code"]."' and ar_no='".$row2["ar_no"]."')";
            // echo    $sql1 = "SELECT IFNULL(SUM(qty), 0) as issue_qty FROM material_issue WHERE ar_no IN 
            //     (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved' 
            //     AND challan_for='EOU')";
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $row2["mmm_qty"] = $row11["m_qty"];
                    }
                }
                
                 $row2["balance_qty"] = $row2["qty"] - $row2["issue_qty1"]- $row2["mmm_qty"];
                $row2["Issue"] = $row2["issue_qty1"]+ $row2["m_qty"];      
                
                 if ($row2["balance_qty"] < 0) {
                                    $row2["balance_qty"] = 0; // Use = to assign the value
                                }
                
                                $output2[] = $row2;
                            }
                        }

                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                        
                        
                        
            $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
                        
                        
                        
                        
                      
                                      $output1[] = $row1;
                    }
                }
                // $row["fifo_method"] =$fifo_method;
                
                // $row["materials"] = $output1;
                // $output[] = $row;
                    // Check if all materials have status 'dispensed'
        $allMaterialsDispensed = true;
        foreach ($output1 as $material) {
            if ($material['status'] !== 'dispensed') {
                $allMaterialsDispensed = false;
                break;
            }
        }

        // Add $row to $output only if any material has a status other than 'dispensed'
        if (!$allMaterialsDispensed) {
            $row["fifo_method"] = $fifo_method;
            $row["materials"] = $output1;
            $output[] = $row;
        }


            }
        } 
        
        
        echo json_encode($output);}
     
     else if ($_GET["type"] == "getDispensingLog") {
         
        $output = array();
        $fifo_method='';
        $sql ="select param_value from software_customization where param_label ='Fifo Method' and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $fifo_method = $row["param_value"];
                
            }
            
        }
        $lod_status='';
        $assay_status='';
         $sql ="SELECT a.bmr_no,a.id,a.batch_plan_id,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,a.lod_status,a.calculation_type,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'
             and dispensing_status ='Request Sent'   "; 
             
            if($_GET["material_type"] == 'Raw Material' && $_GET["rpt_type"]=='Request'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                $sql = $sql." and rm_disp_completed_by='' and rm_qa_dislc_status='Approved' order by a.id desc ";
            }else if($_GET["material_type"] == 'Packing Material' && $_GET["rpt_type"]=='Request'){
                $sql = $sql." and pm_store_status_entry_by!=''" ;
            }
            else  if($_GET["material_type"] == 'Raw Material' && $_GET["rpt_type"]=='Completed'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved'  ";
            }
            else{
                $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
     
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                if($_GET["material_type"] == 'Raw Material'){
                // $sql1 = "SELECT
                //     a.lot_no,
                //     GROUP_CONCAT(DISTINCT a.material_code) AS material_codes,    SUM(a.batch_qty) AS total_batch_qty,    MAX(a.status) AS status,
                //     MAX(a.material_type) AS material_type,    MAX(a.category) AS category,    MAX(a.material_subtype) AS material_subtype,
                //     MAX(a.material_name) AS material_name,    MAX(a.lod_status) AS lod_status,    MAX(a.assay_status) AS assay_status,
                //     MAX(a.qa_status) AS qa_status,    MAX(a.prod_status) AS prod_status,    MAX(b.avbl_stock) AS avbl_stock
                // FROM (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status, IFNULL(dd.id,0) as dispence_id
                // ,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots a join mfg_work_order_hdr c on a.work_order_id = c.id left
                // join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code left join material b on a.material_code = b.material_code
                // and c.plant_id = b.plant_id left join dispensing_details_hdr dd on a.id = dd.lot_id where a.work_order_id = '".$row["id"]."') AS a LEFT JOIN 
                // ( SELECT material_code,balance_qty as avbl_stock from vw_stock_summary WHERE plant_id='".$_GET["plant_id"]."' and material_code in 
                // (SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                // ) AS b ON a.material_code = b.material_code AND a.material_type = '".$_GET["material_type"]."'
                // WHERE a.work_order_id = '".$row["id"]."'
                // GROUP BY a.lot_no; ";
                 $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.grade,b.density,  b.uom,b.alternate_uom,b.material_nature,b.unit_conversion ,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,
                wd.assay_status,IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 ( SELECT material_code,balance_qty as avbl_stock from vw_stock_summary
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 
                 
                 ) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                }else{
                 $sql1=" SELECT a.*,bp.country_name,bp.pack_size,bp.pack_size_unit, IFNULL(b.uom,'Nos') as uom,IFNULL(dd.id,0) as dispence_id,IFNULL(b.qty,0) as avbl_stock,material_name,material_subtype from batch_planning_materials a left join(
                        SELECT mt.plant_id,mt.uom,mt.material_code,mt.material_name,sum(sb.qty) as qty,mt.material_subtype FROM material
                        mt left join stock_book sb on mt.material_code = sb.material_code and mt.plant_id = sb.plant_id 
                        group by mt.material_code,mt.material_name,mt.material_subtype,mt.plant_id,mt.uom)b
                        on a.material_code = b.material_code and a.plant_id = b.plant_id 
                        left join dispensing_details_hdr dd on a.pm_hdr_id = dd.pm_hdr_id
                        left JOIN batch_planing_raw_material_hdr bp on a.pm_hdr_id = bp.id
                        where a.material_type='Packing Material'
                        and a.batch_plan_id='".$row["batch_plan_id"]."'"   ;
                }
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT
    a.*,
    IFNULL(b.issued_qty, 0) as issued_qty,
    (a.qty - IFNULL(a.undertest_qty, 0) - IFNULL(b.issued_qty, 0)) as balance_qty,
    FLOOR((a.qty - IFNULL(b.issued_qty, 0)) / a.pack_size) as intact_containers,
    (a.qty - IFNULL(b.issued_qty, 0)) - (a.pack_size * (FLOOR((a.qty - IFNULL(b.issued_qty, 0)) / a.pack_size))) as loose_Qty,
    CASE WHEN 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' THEN ROUND((a.qty - IFNULL(b.issued_qty, 0)) - (((a.qty - IFNULL(b.issued_qty, 0)) * lod_per) / 100), 2) ELSE 0 END as dry_qty,
    CASE WHEN 'LOD Basis' = 'Assay Basis' AND 'Active' = 'Active' THEN ROUND((a.qty - IFNULL(b.issued_qty, 0)) - (((a.qty - IFNULL(b.issued_qty, 0)) * assay) / 100), 2) ELSE 0 END as pure_qty,
    ('".$row1["batch_qty"]."' * 100) / assay_qty as pure_qtys,
    ('".$row1["batch_qty"]."' * (SELECT equivalancy_factor FROM material WHERE material_code = '".$row1["material_code"]."')) AS material_e_factor,
c.lod_qty,
c.lod_test,
assay_qty,
test_assay,
specification_no
FROM
    (
    SELECT
        IFNULL(SUM(qty),
        0) AS qty,
        grn_no,
        ar_no,
        undertest_qty,
        pack_size,
        batch_no,
        containers,
        lod_per,
        assay
    FROM
        stock_book
    WHERE
        material_code = '".$row1["material_code"]."' AND
    STATUS
        = 'Approved'
    GROUP BY
        grn_no,
        ar_no,
        pack_size,
        undertest_qty,
        batch_no,
        containers,
        lod_per,
        assay
) a
LEFT JOIN(
    SELECT
        grn_no,
        ar_no,
        IFNULL(SUM(qty),
        0) AS issued_qty
    FROM
        material_issue
    GROUP BY
        grn_no,
        ar_no
) b
ON
    TRIM(a.ar_no) = TRIM(b.ar_no)
LEFT JOIN(
    SELECT
        a.result AS lod_qty,
        a.test AS lod_test
    FROM
        testing_tests  a
    LEFT JOIN specification b ON
        a.specification_no = b.specification_no
    WHERE
        b.material_code = '".$row1["material_code"]."' AND a.test = 'LOD'
    ORDER BY
        a.id
    DESC
LIMIT 1
) c
ON
    c.lod_test = 'LOD'
LEFT JOIN(
    SELECT
        a.result AS assay_qty,
        a.test AS test_assay,
    	a.specification_no
    FROM
        testing_tests  a
    LEFT JOIN specification b ON
        a.specification_no = b.specification_no
    WHERE
        b.material_code = '".$row1["material_code"]."' AND a.test LIKE '%Assay%'
    ORDER BY
        a.id
    DESC
LIMIT 1
) d
ON
    d.test_assay LIKE '%Assay%'";
        //               $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, 
        //  (a.qty-IFNULL(a.undertest_qty,0)-IFNULL(b.issued_qty,0)) as balance_qty,
        //  floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers, 
        //  (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
        //  case when 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty, 
        //  case when 'LOD Basis' ='Assay Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty,
        //  c.lod_qty, c.lod_test
        //  FROM (SELECT IFNULL(SUM(qty), 0) as qty, grn_no,ar_no ,undertest_qty,pack_size ,batch_no,containers,lod_per,assay 
        //       FROM stock_book
        //       WHERE material_code='".$row1["material_code"]."' AND status='Approved' 
        //       GROUP BY grn_no,ar_no,pack_size,undertest_qty, batch_no,containers,lod_per,assay) a
        //  LEFT JOIN (SELECT grn_no,ar_no, IFNULL(SUM(qty), 0) as issued_qty 
        //             FROM material_issue 
        //             GROUP BY grn_no,ar_no) b ON TRIM(a.ar_no) = TRIM(b.ar_no)
        //  LEFT JOIN (SELECT a.lower_limit as lod_qty, a.test as lod_test 
        //             FROM spec_tests a 
        //             LEFT JOIN specification b ON a.specification_no=b.specification_no 
        //             WHERE b.material_code='".$row1["material_code"]."' AND a.test='LOD' order by a.id  desc limit 1) c ON c.lod_test = 'LOD'
                //   ";
            //   working
                    //   $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(a.undertest_qty,0)-IFNULL(b.issued_qty,0)) as balance_qty,
                    //             floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers, (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, case when 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty, case when 'LOD Basis' ='Assay Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty from
                    //         (SELECT IFNULL(SUM(qty), 0) as qty, grn_no,ar_no ,undertest_qty,pack_size ,batch_no,containers,lod_per,assay FROM stock_book
                    //     WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY grn_no,ar_no,pack_size,undertest_qty, batch_no,containers,lod_per,assay) a
                    //     left join (SELECT grn_no,ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by grn_no,ar_no)b on TRIM(a.ar_no) = TRIM(b.ar_no)";
                    //   $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(a.undertest_qty,0)-IFNULL(b.issued_qty,0)) as balance_qty,
                    //             floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers, (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, case when 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty, case when 'LOD Basis' ='Assay Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty from
                    //         (SELECT IFNULL(SUM(qty), 0) as qty, grn_no,ar_no ,undertest_qty,pack_size ,batch_no,containers,lod_per,assay FROM stock_book
                    //     WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY grn_no,ar_no,pack_size,undertest_qty, batch_no,containers,lod_per,assay) a
                    //     left join (SELECT grn_no,ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by grn_no,ar_no) b on a.ar_no = b.ar_no and a.grn_no = b.grn_no"; 
 
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                        
                        
                        
            $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
                        
                        
                        
                        
                      
                                      $output1[] = $row1;
                    }
                }
                $row["fifo_method"] =$fifo_method;
                
                $row["materials"] = $output1;
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
    
     }
          else if ($_GET["type"] == "get_Dispensing_Requests_For_Inprocess_Activity_Formulation_pk") {
        $output = array();
        $fifo_method='';
        $sql ="select param_value from software_customization where param_label ='Fifo Method' and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $fifo_method = $row["param_value"];
                
            }
            
        }
        $lod_status='';
        $assay_status='';
         $sql ="SELECT DISTINCT
                        a.bmr_no,
                        a.id,
                        b.id as b_id,
                        a.batch_plan_id,
                        a.batch_number,
                        a.work_order_no,
                        a.rm_qa_dislc_date AS planned_by,
                        a.approved_by,
                        a.lod_status,
                        a.calculation_type,
                        a.stability,
                        a.stability_reason,
                        a.process_validation,
                        a.qa_person,
                        a.qa_date,
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
                        b.pack_unit,+
                        a.dispense_request_sent_by,
                        a.dispense_request_sent_on,
                        a.dispensing_status,
                        rm_disp_completed_by,
                        a.pm_disp_completed_by,
                        a.pm_qa_dislc_status AS lc_status,
                        a.pm_qa_dislc_by AS lc_by,
                        a.pm_qa_dislc_date AS lc_date
                    FROM
                        mfg_work_order_hdr a
                    JOIN
                        batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                    JOIN
                        product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
                    
                    WHERE
                        a.plant_id = '".$_GET["plant_id"]."' 
                        
                      and a.pm_store_status='Accept' and a.pm_disp_completed_by=''
                          order by a.id desc";
     
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                if($_GET["material_type"] == 'Raw Material'){
             
                 $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.grade,b.density,  b.uom,b.alternate_uom,b.material_nature,b.unit_conversion ,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,
                wd.assay_status,IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 ( SELECT material_code,balance_qty as avbl_stock from vw_stock_summary
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 
                 
                 ) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                }else{
                 $sql1=" SELECT a.*,bp.country_name,bp.pack_size,bp.pack_size_unit, IFNULL(b.uom,'Nos') as uom,IFNULL(dd.id,0) as dispence_id,IFNULL(b.qty,0) as avbl_stock,material_name,material_subtype from batch_planning_materials a left join(
                        SELECT mt.plant_id,mt.uom,mt.material_code,mt.material_name,sum(sb.qty) as qty,mt.material_subtype FROM material
                        mt left join stock_book sb on mt.material_code = sb.material_code and mt.plant_id = sb.plant_id 
                        group by mt.material_code,mt.material_name,mt.material_subtype,mt.plant_id,mt.uom)b
                        on a.material_code = b.material_code and a.plant_id = b.plant_id 
                        left join dispensing_details_hdr dd on a.pm_hdr_id = dd.pm_hdr_id
                        left JOIN batch_planing_raw_material_hdr bp on a.pm_hdr_id = bp.id
                        where a.material_type='Packing Material'
                        and a.batch_plan_id='".$row["batch_plan_id"]."'"   ;
                }
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                      $output2 = array();
                      $category = $row1["category"];
                       $sql2="SELECT
    a.*,
    IFNULL(b.issued_qty, 0) as issued_qty,
    FLOOR((a.qty - IFNULL(b.issued_qty, 0)) / a.pack_size) as intact_containers,
    (a.qty - IFNULL(b.issued_qty, 0)) - (a.pack_size * (FLOOR((a.qty - IFNULL(b.issued_qty, 0)) / a.pack_size))) as loose_Qty,
    CASE WHEN 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' THEN ROUND((a.qty - IFNULL(b.issued_qty, 0)) - (((a.qty - IFNULL(b.issued_qty, 0)) * lod_per) / 100), 2) ELSE 0 END as dry_qty,
    CASE WHEN 'LOD Basis' = 'Assay Basis' AND 'Active' = 'Active' THEN ROUND((a.qty - IFNULL(b.issued_qty, 0)) - (((a.qty - IFNULL(b.issued_qty, 0)) * assay) / 100), 2) ELSE 0 END as pure_qty,
    
    ('".$row1["batch_qty"]."' * (SELECT equivalancy_factor FROM material WHERE material_code = '".$row1["material_code"]."')) AS material_e_factor
FROM
    (
    SELECT IFNULL(SUM(qty), 0) AS qty,exp_date,mfg_date, grn_no,grn_date,vendor_no, ar_no, undertest_qty, pack_size, batch_no, containers, lod_per, assay
    FROM
        stock_book
    WHERE
        material_code = '".$row1["material_code"]."' AND
    STATUS
        = 'Approved'
    GROUP BY id,grn_no,grn_date, ar_no, pack_size, undertest_qty, batch_no, containers, lod_per, assay ORDER by ar_no asc
) a
LEFT JOIN(
    SELECT  ar_no, IFNULL(SUM(qty), 0) AS issued_qty
    FROM
        material_issue
    GROUP BY  ar_no
) b 
ON
    TRIM(a.ar_no) = TRIM(b.ar_no)
";

                          $result2 = $conn->query($sql2);
                       if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                               
                               
                                
                                
              $sql11 = " SELECT
                        tt.result AS lod_qty
                    
                    FROM
                        testing_tests  tt
                    LEFT JOIN specification b ON
                        tt.specification_no = b.specification_no
                         left join testing t on t.testing_no= tt.testing_no and t.ar_no='".$row2["ar_no"]."'
                    WHERE
                        b.material_code = '".$row1["material_code"]."' AND tt.test LIKE '%loss on dry%' and t.ar_no!=''
                    ORDER BY
                        tt.id
                    DESC";
                $result11 = $conn->query($sql11);
                if ($result1->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                         $row2["lod_qty"] = $row11["lod_qty"];
                       
                    }
                }
              $sql11 = " SELECT
                        tt.result AS assay_qty,
                        tt.test AS test_assay,
                    	tt.specification_no
                    
                    FROM
                        testing_tests  tt
                    LEFT JOIN specification b ON
                        tt.specification_no = b.specification_no
                         left join testing t on t.testing_no= tt.testing_no and t.ar_no='".$row2["ar_no"]."'
                    WHERE
                        b.material_code = '".$row1["material_code"]."' AND tt.test LIKE '%Assay%' and t.ar_no!=''
                    ORDER BY
                        tt.id
                    DESC";
                $result11 = $conn->query($sql11);
                if ($result1->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                         $row2["assay_qty"] = $row11["assay_qty"];
                         $row2["test_assay"] = $row11["test_assay"];
                         $row2["specification_no"] = $row11["specification_no"];
                        //  $row2["pure_qtys"] = $row11["pure_qtys"];
                    }
                }
              $sql11 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row1["material_code"]."' ";
                $result11 = $conn->query($sql11);
                if ($result1->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                         $row2["qty1"] = $row11["qty"];
                    }
                }
                
                
                
                
                
                
                     $sql110 = "SELECT vendor_name,vendor_no FROM vendor where vendor_no =  '".$row2["vendor_no"]."' ";
                $result100 = $conn->query($sql110);
                if ($result100->num_rows > 0) {
                    while ($row110 = $result100->fetch_assoc()) {
                         $row2["vendor_name"] = $row110['vendor_name'];
                         $row2["vendor_no"] = $row110['vendor_no'];
                    }
                }
                
                
                
                
                
                
                
                 $sql11 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$row1["material_code"]."' ";
            // echo    $sql1 = "SELECT IFNULL(SUM(qty), 0) as issue_qty FROM material_issue WHERE ar_no IN 
            //     (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved' 
            //     AND challan_for='EOU')";
                $result11 = $conn->query($sql11);
                if ($result1->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $row2["issue_qty1"] = $row11["issue_qty"];
                    }
                }
                 $sql11 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                 (SELECT ar_no FROM stock_book WHERE material_code='".$row1["material_code"]."' and ar_no='".$row2["ar_no"]."')";
            // echo    $sql1 = "SELECT IFNULL(SUM(qty), 0) as issue_qty FROM material_issue WHERE ar_no IN 
            //     (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved' 
            //     AND challan_for='EOU')";
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $row2["mmm_qty"] = $row11["m_qty"];
                    }
                }
                
                 $row2["balance_qty"] = $row2["qty"] - $row2["issue_qty1"]- $row2["mmm_qty"];
                $row2["Issue"] = $row2["issue_qty1"]+ $row2["m_qty"];    
                  $row2["pure_qtys"] = ($row2["balance_qty"] * $row2["assay_qty"])/100;
                //  if ($row2["balance_qty"] >= $row1["batch_qty"]) {
                //         $row2["dispensed_qty"] = $row1["batch_qty"];
                //     } else if($row1["batch_qty"] > $row2["balance_qty"]) {
                //         // Handle the case where balance_qty is less than batch_qty
                //         $row2["dispensed_qty"] =$row2["balance_qty"]; // or handle it in another way that fits your logic
                //     }else{
                //         $row2["dispensed_qty"]=0;
                //     }


                
                 if ($row2["balance_qty"] < 0) {
                                    $row2["balance_qty"] = 0; // Use = to assign the value
                                }
                
                                $output2[] = $row2;
                            }
                        }

                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                        
                        
                        
            $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
                        
                      
                                      $output1[] = $row1;
                    
                        
                    }
                }
                $row["fifo_method"] =$fifo_method;
                
                $row["materials"] = $output1;
                
                
                
                
                 $jadugar = 0;
            
            foreach ($output1 as $material) {
              
               if ($material["dispence_id"] == '0'){
                    $jadugar++;
                    
                }
                
            }
                    
            
            
                 $output[] = $row;
    //         if(  $jadugar > 0){
                
    //              $output[] = $row;
              
                
    //         }else if($jadugar == 0){
    //              $sql = "UPDATE mfg_work_order_hdr SET  
    //     rm_disp_completed_date='".$entry_date."', rm_disp_completed_by='".$_GET["emp_id"]."'  WHERE id='".$row["id"]."'";
         
    //   $conn->query($sql);
                
    //         }
                
                
                   
                
                // $output[] = $row;
                
                
                
            
            }
        } 
        
        
        echo json_encode($output);
    }
    //  else if ($_GET["type"] == "get_Dispensing_Requests_For_Inprocess_Activity_Formulation_pk") {
    //     $output = array();
    //     $fifo_method='';
    //     $sql ="select param_value from software_customization where param_label ='Fifo Method' and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
    //   $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //         $fifo_method = $row["param_value"];
                
    //         }
            
    //     }
    //     $lod_status='';
    //     $assay_status='';
    //      $sql ="SELECT DISTINCT
    //                     a.bmr_no,
    //                     a.id,
    //                     b.id as b_id,
    //                     a.batch_plan_id,
    //                     a.batch_number,
    //                     a.work_order_no,
    //                     a.rm_qa_dislc_date AS planned_by,
    //                     a.approved_by,
    //                     a.lod_status,
    //                     a.calculation_type,
    //                     a.stability,
    //                     a.stability_reason,
    //                     a.process_validation,
    //                     a.qa_person,
    //                     a.qa_date,
    //                     a.approved_by,
    //                     b.plan_no,
    //                     b.bfr_no,
    //                     b.mfr_no,
    //                     b.product_code,
    //                     b.batch_size,
    //                     p.product_name,
    //                     p.product_type,
    //                     p.grade,
    //                     b.pack_size,
    //                     b.pack_unit,+
    //                     a.dispense_request_sent_by,
    //                     a.dispense_request_sent_on,
    //                     a.dispensing_status,
    //                     rm_disp_completed_by,
    //                     a.pm_disp_completed_by,
    //                     a.pm_qa_dislc_status AS lc_status,
    //                     a.pm_qa_dislc_by AS lc_by,
    //                     a.pm_qa_dislc_date AS lc_date,
    //           (SELECT COUNT(pm_store_status) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.pm_store_status='Accept' and z.pm_disp_completed_by='0') as pm_store_status_count
    //                 FROM
    //                     mfg_work_order_hdr a
    //                 JOIN
    //                     batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
    //                 JOIN
    //                     product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
    //                 WHERE
    //                     a.plant_id = '".$_GET["plant_id"]."'
    //                     AND (a.pm_dispensing_status = 'Request Sent' or a.pm_dispensing_status = '0')
    //                   and (a.pm_store_status='Accept' or a.pm_store_status='0')  HAVING    pm_store_status_count != '0'
    //                       order by a.id desc";
     
    //     $result = $conn->query($sql);

    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
              
    //             $output[] = $row;
    //         }
    //     } 
        
        
    //     echo json_encode($output);
    // }
    //  else if ($_GET["type"] == "get_Dispensing_Requests_For_Inprocess_Activity_Formulation_pk") {
    //     $output = array();
    //     $fifo_method='';
    //     $sql ="select param_value from software_customization where param_label ='Fifo Method' and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
    //   $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //         $fifo_method = $row["param_value"];
                
    //         }
            
    //     }
    //     $lod_status='';
    //     $assay_status='';
    //      $sql ="SELECT DISTINCT
    //                     a.bmr_no,
    //                     a.id,
    //                     b.id as b_id,
    //                     a.batch_plan_id,
    //                     a.batch_number,
    //                     a.work_order_no,
    //                     a.rm_qa_dislc_date AS planned_by,
    //                     a.approved_by,
    //                     a.lod_status,
    //                     a.calculation_type,
    //                     a.stability,
    //                     a.stability_reason,
    //                     a.process_validation,
    //                     a.qa_person,
    //                     a.qa_date,
    //                     a.approved_by,
    //                     b.plan_no,
    //                     b.bfr_no,
    //                     b.mfr_no,
    //                     b.product_code,
    //                     b.batch_size,
    //                     p.product_name,
    //                     p.product_type,
    //                     p.grade,
    //                     b.pack_size,
    //                     b.pack_unit,
    //                     a.dispense_request_sent_by,
    //                     a.dispense_request_sent_on,
    //                     a.dispensing_status,
    //                     rm_disp_completed_by,
    //                     a.pm_disp_completed_by,
    //                     a.pm_qa_dislc_status AS lc_status,
    //                     a.pm_qa_dislc_by AS lc_by,
    //                     a.pm_qa_dislc_date AS lc_date
    //                 FROM
    //                     mfg_work_order_hdr a
    //                 JOIN
    //                     batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
    //                 JOIN
    //                     product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
    //                 WHERE
    //                     a.plant_id = '".$_GET["plant_id"]."'
    //                     AND a.pm_dispensing_status = 'Request Sent'
    //                   and a.pm_store_status='Accept'
    //                       order by a.id desc";
     
    //     $result = $conn->query($sql);

    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $calculation_type = $row['calculation_type'];
    //             $row["checkpoints"] = json_decode($row["checkpoints"]);
    //              $sql9 = "select * from batch_planing_raw_material_hdr where batch_plan_id = '".$row["b_id"]."'";
    //              $result9 = $conn->query($sql9);
    //             $pm_id;
    //     if ($result9->num_rows > 0) {
    //         while ($row9 = $result9->fetch_assoc()) {
    //             $pm_id=$row9['id'];
                
    //         }
    //     }
    //             $output1 = array();
               
             
    //             //  $sql1="SELECT *,a.pack_size as p_size, a.material_code AS m_code,s.qty as avbl_qty,a.id as bpm_id FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code LEFT JOIN stock_book c 
    //             //  ON b.material_code = c.material_code LEFT JOIN mfg_work_order_dtl d ON b.material_code = d.material_code LEFT JOIN batch_planing_raw_material_hdr e ON
    //             //  e.batch_plan_id=d.work_order_id and a.pm_hdr_id=e.id left join stock_book s on a.material_code=s.material_code WHERE 
    //             //   a.material_type = 'Packing Material' AND a.batch_plan_id = '".$row["batch_plan_id"]."'  AND d.work_order_id = '".$row["id"]."'";
                   
    //               $sql1="SELECT *,d.id as upid,a.pack_size as p_size, a.material_code AS m_code,a.id as bpm_id FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code 
    //           LEFT JOIN mfg_work_order_dtl d ON b.material_code = d.material_code LEFT JOIN batch_planing_raw_material_hdr e ON
    //              e.batch_plan_id=d.work_order_id and a.pm_hdr_id=e.id   WHERE 
    //               a.material_type = 'Packing Material' AND a.batch_plan_id = '".$row["batch_plan_id"]."'  AND d.work_order_id = '".$row["id"]."'";
                   
                   
            
                
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
                        
                        
    //                      $sql24 ="select sum(qty) as avbl_qty from stock_book where material_code = '".$row1['material_code']."'";
                        
    //                       $result24 = $conn->query($sql24);
    //                     if ($result24->num_rows > 0) {
    //                         while ($row24 = $result24->fetch_assoc()) {
    //                             $row1['avbl_qty'] = $row24['avbl_qty'];
    //                         }
    //                     }
                        
                        
                        
                        
                        
    //                   $output2 = array();
    //                   $category = $row1["category"];
    //                   $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(a.undertest_qty,0)-IFNULL(b.issued_qty,0)) as balance_qty,
    //                             floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers, (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, case when 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty, case when 'LOD Basis' ='Assay Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty from
    //                         (SELECT IFNULL(SUM(qty), 0) as qty, grn_no,ar_no ,undertest_qty,pack_size ,batch_no,containers,lod_per,assay FROM stock_book
    //                     WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY grn_no,ar_no,pack_size,undertest_qty, batch_no,containers,lod_per,assay) a
    //                     left join (SELECT grn_no,ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by grn_no,ar_no)b on TRIM(a.ar_no) = TRIM(b.ar_no)"; 
  
    //                       $result2 = $conn->query($sql2);
    //                     if ($result2->num_rows > 0) {
    //                         while ($row2 = $result2->fetch_assoc()) {
    //                             $row2['product_code'] = $row['product_code'];
    //                             $row2['material_code'] = $row1['material_code'];
    //                             $row2['work_order_id'] = $row1['work_order_id'];
    //                             $output2[] = $row2;
    //                         }
    //                     }
    //                     $row1["available_ars"] = $output2;
                        
    //                     $row1["containers"] = json_decode($row1["containers"]);
    //                     $row1["ars"] = json_decode($row1["ars"]);
    //                     if ($row1["status"] == "pending") {
    //                         $flag = 1;
    //                     }
                      
    //                                   $output1[] = $row1;
    //                 }
    //             }
    //             $row["fifo_method"] =$fifo_method;
    //             $row["materials"] = $output1;
    //             $output[] = $row;
    //         }
    //     } 
        
        
    //     echo json_encode($output);
    // }
     else if ($_GET["type"] == "dis_prev_data") {
          	$sql = "SELECT *,a.id,l.work_order_id,p.product_name as p_name FROM mfg_work_order_hdr a left JOIN lineclearance l on a.id=l.work_order_id left joIN batch_planning b on a.batch_plan_id = b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code WHERE a.id='".$_GET["prev_id"]."' limit 1 ";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);
         
     }
     else if ($_GET["type"] == "getDispensingDetails") {
          $output1 = array();
          
       $material_type=  $_GET["material_type"];
       if($material_type=='Raw Material'){
            $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,a.id as a_id,b.material_type,b.category,b.material_subtype,
                b.material_name,wd.lod_status,wd.assay_status,b.grade as m_grade,
                IFNULL(dd.id,0) as dispence_id,dd.qa_approved_by FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$_GET["id"]."' and dd.qa_checking='Yes'  and b.material_type='".$_GET["material_type"]."' ) as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$_GET["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code ";
       }
       else if($material_type=='Packing Material'){
            $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,a.id as a_id,b.material_type,b.category,b.material_subtype,
                b.material_name,wd.lod_status,wd.assay_status,b.grade as m_grade,
                IFNULL(dd.id,0) as dispence_id,dd.qa_approved_by FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$_GET["id"]."' and dd.qa_checking='Yes'  and b.material_subtype='Primary Packing Material' ) as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$_GET["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code ";
       }
          
                
            
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                       $category = $row1["category"];
                     
                        $sql2="select * from dispensing_details_hdr where id = '".$row1["dispence_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2 = $row2;
                            }
                        }
                         $row1["gross_total"] = $output2["gross_total"];
                          $row1["net_total"] = $output2["net_total"];
                           $row1["tare_total"] = $output2["tare_total"];
                        $row1["available_ars"] = json_decode($output2["ars"]);
                        $row1["containers"] = json_decode($output2["containers"]);
                       
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                        
                        
                        
                        
                    $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['m_grade']."')";
                    $resQ = $conn->query($q);
                    $prodLatest = $resQ->fetch_assoc(); 
                    $row1['grade'] = $prodLatest['gradeName']; 
                        
                        
                        
                        
                      
                                      $output1[] = $row1;
                    }
                }
       
        
        
        echo json_encode($output1);
    }
    //  else if ($_GET["type"] == "get_Dispensing_Requests_qa_checking") {
    //     $output = array();
    //     $fifo_method='';
    //     $sql ="select param_value from software_customization where param_label ='Fifo Method' 
    //     and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
    //   $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //         $fifo_method = $row["param_value"];
                
    //         }
            
    //     }
    //     $lod_status='';
    //     $assay_status='';
    //      $sql ="SELECT a.id,a.batch_number,a.no_of_lots,a.work_order_no,a.rm_qa_dislc_date as 
    //      palnned_by,a.approved_by,a.lod_status,a.calculation_type,
    //      a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
    //      a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,p.dosage_form,b.batch_size,
    //      p.product_name,p.product_type,
    //          p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, 
    //          a.dispense_request_sent_on,a.dispensing_status,";
    //          if($_GET["material_type"] == 'Raw Material'){
               
    //             $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
    //          }else{
    //             $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
    //          }
             
             
    //         $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
    //          b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
    //          b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'
    //          and dispensing_status ='Request Sent'   "; 
    //         if($_GET["material_type"] == 'Raw Material'){
    //             $sql = $sql." and rm_store_status_entry_by!='' and rm_qa_dislc_status='Approved' ";
    //         }else{
    //             $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
    //         }
        
    //     $result = $conn->query($sql);

    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $calculation_type = $row['calculation_type'];
    //             $row["checkpoints"] = json_decode($row["checkpoints"]);
    //             $output1 = array();
    //             $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,a.id as a_id,b.material_type,b.category,b.material_subtype,
    //             b.material_name,wd.lod_status,wd.assay_status,b.grade as m_grade,
    //             IFNULL(dd.id,0) as dispence_id,dd.qa_approved_by FROM work_order_batch_lots  a 
    //              join mfg_work_order_hdr c on a.work_order_id = c.id
    //              left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
    //              left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
    //              left join dispensing_details_hdr dd on a.id = dd.lot_id
    //              where a.work_order_id = '".$row["id"]."' and dd.qa_checking='Yes' ) as a left join
    //              (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
    //              WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
    //              GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
            
                      
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                   $category = $row1["category"];
                     
    //                     $sql2="select * from dispensing_details_hdr where id = '".$row1["dispence_id"]."'";
    //                     $result2 = $conn->query($sql2);
    //                     if ($result2->num_rows > 0) {
    //                         while ($row2 = $result2->fetch_assoc()) {
    //                             $output2 = $row2;
    //                         }
    //                     }
    //                      $row1["gross_total"] = $output2["gross_total"];
    //                       $row1["net_total"] = $output2["net_total"];
    //                       $row1["tare_total"] = $output2["tare_total"];
    //                     $row1["available_ars"] = json_decode($output2["ars"]);
    //                     $row1["containers"] = json_decode($output2["containers"]);
                       
    //                     if ($row1["status"] == "pending") {
    //                         $flag = 1;
    //                     }
                        
                        
                        
                        
    //                 $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['m_grade']."')";
    //                 $resQ = $conn->query($q);
    //                 $prodLatest = $resQ->fetch_assoc(); 
    //                 $row1['grade'] = $prodLatest['gradeName']; 
                        
                        
                        
                        
                      
    //                                   $output1[] = $row1;
    //                 }
    //             }
    //             $row["fifo_method"] =$fifo_method;
    //             $row["materials"] = $output1;
    //             $output[] = $row;
    //         }
    //     } 
        
        
    //     echo json_encode($output);
    // }
     else if ($_GET["type"] == "get_Dispensing_Requests_qa_checking") {
        $output = array();
        $fifo_method = '';
        
        // Escape input to prevent SQL injection
        $plant_id = mysqli_real_escape_string($conn, $_GET["plant_id"]);
        $material_type = isset($_GET["material_type"]) ? mysqli_real_escape_string($conn, $_GET["material_type"]) : '';
        
        $sql = "SELECT param_value FROM software_customization WHERE param_label = 'Fifo Method' 
                AND module = 'Dispensing Activity' AND plant_id = '".$plant_id."'";
      
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $fifo_method = $row["param_value"];
                break; // Only need first result
            }
        }
        
        $sql = "SELECT a.id, a.batch_number, a.no_of_lots, a.work_order_no, a.rm_qa_dislc_date as 
                palnned_by, a.approved_by, a.lod_status, a.calculation_type,
                a.stability, a.stability_reason, a.process_validation, a.qa_person, a.qa_date,
                (SELECT COALESCE(wm.doc_no, wm.workorder_no) FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1) as plan_no,
                
                (SELECT wm.product_code FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1) as product_code,
                p.dosage_form,
                (SELECT wm.batch_size FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1) as batch_size,
                p.product_name, p.product_type,
      
                (SELECT wm.packingStyle FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1) as pack_size,
                (SELECT wm.packingUnit FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1) as pack_unit,
                a.dispense_request_sent_by, 
                a.dispense_request_sent_on, a.dispensing_status, ";
        
        if ($material_type == 'Raw Material') {
            $sql = $sql . " a.rm_qa_dislc_status as lc_status, a.rm_qa_dislc_by as lc_by, a.rm_qa_dislc_date as lc_date ";
        } else {
            $sql = $sql . " a.pm_qa_dislc_status as lc_status, a.pm_qa_dislc_by as lc_by, a.pm_qa_dislc_date as lc_date ";
        }
        
        $sql = $sql . " FROM mfg_work_order_hdr a 
                LEFT JOIN Work_order_materials wm ON a.work_order_no = wm.workorder_no
                LEFT JOIN product p ON wm.product_code = p.product_code AND a.plant_id = p.plant_id
                WHERE a.plant_id = '".$plant_id."'
                AND dispensing_status = 'Request Sent'";
        
        if ($material_type == 'Raw Material') {
            $sql = $sql . " AND rm_store_status_entry_by != '' AND rm_qa_dislc_status = 'Approved'";
        } else {
            $sql = $sql . " AND pm_store_status_entry_by != '' AND pm_qa_dislc_status = 'Approved'";
        }
        
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Initialize checkpoints if exists
                if (isset($row["checkpoints"])) {
                    $row["checkpoints"] = json_decode($row["checkpoints"]);
                }
                
                $output1 = array();
                $work_order_id = mysqli_real_escape_string($conn, $row["id"]);
                
                 $sql1 = "SELECT a.*, b.avbl_stock FROM (
                   SELECT a.*, a.id as a_id, 
                  COALESCE(b.material_type, bm.material_type) AS material_type, 
                   b.category, 
                  COALESCE(b.material_subtype, bm.material_type) AS material_subtype,
                  COALESCE(b.material_name, bm.bulkName) AS material_name,
                   wd.lod_status, wd.assay_status, b.grade as m_grade,
                   IFNULL(dd.id, 0) as dispence_id, dd.qa_approved_by FROM work_order_batch_lots a JOIN mfg_work_order_hdr c ON a.work_order_id = c.id LEFT JOIN 
                   mfg_work_order_dtl wd ON wd.work_order_id = c.id AND wd.material_code = a.material_code
                   LEFT JOIN material b ON a.material_code = b.material_code
                   LEFT JOIN bulkMaster bm ON a.material_code = bm.bulkCode
                   AND c.plant_id = b.plant_id LEFT JOIN dispensing_details_hdr dd ON wd.id = dd.dtl_id 
                    WHERE a.work_order_id = '".$work_order_id."' AND dd.qa_checking = 'Yes'
                ) as a 
                LEFT JOIN (
                    SELECT material_code, SUM(qty) as avbl_stock 
                    FROM stock_book
                    WHERE plant_id = '".$plant_id."' 
                    AND material_code IN (SELECT material_code FROM work_order_batch_lots WHERE work_order_id = '".$work_order_id."') 
                    GROUP BY material_code
                ) as b
                
                ON a.material_code = b.material_code AND a.material_type = '".$material_type."'";
                      
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        // Initialize output2 to prevent undefined variable errors
                        $output2 = array(
                            "gross_total" => 0,
                            "net_total" => 0,
                            "tare_total" => 0,
                            "ars" => "[]",
                            "containers" => "[]"
                        );
                        
                        $dispence_id = mysqli_real_escape_string($conn, $row1["dispence_id"]);
                        $sql2 = "SELECT * FROM dispensing_details_hdr WHERE id = '".$dispence_id."'";
                        $result2 = $conn->query($sql2);
                        
                        if ($result2 && $result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2 = $row2;
                            }
                        }
                        
                        // Safely assign values with defaults
                        $row1["gross_total"] = isset($output2["gross_total"]) ? $output2["gross_total"] : 0;
                        $row1["net_total"] = isset($output2["net_total"]) ? $output2["net_total"] : 0;
                        $row1["tare_total"] = isset($output2["tare_total"]) ? $output2["tare_total"] : 0;
                        $row1["available_ars"] = isset($output2["ars"]) ? json_decode($output2["ars"]) : array();
                        $row1["containers"] = isset($output2["containers"]) ? json_decode($output2["containers"]) : array();
                        
                        // Get grade information
                        if (!empty($row1['m_grade'])) {
                            $m_grade = mysqli_real_escape_string($conn, $row1['m_grade']);
                            $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade WHERE id IN ('".$m_grade."')";
                            $resQ = $conn->query($q);
                            if ($resQ && $resQ->num_rows > 0) {
                                $prodLatest = $resQ->fetch_assoc();
                                $row1['grade'] = isset($prodLatest['gradeName']) ? $prodLatest['gradeName'] : '';
                            } else {
                                $row1['grade'] = '';
                            }
                        } else {
                            $row1['grade'] = '';
                        }
                        
                        $output1[] = $row1;
                    }
                }
                
                $row["fifo_method"] = $fifo_method;
                $row["materials"] = $output1;
                $output[] = $row;
            }
        } 
        
        echo json_encode($output);
    }
    //get_Dispensing_Requests_For_Production_Activity_Formulation
    // else if ($_GET["type"] == "get_Dispensing_Requests_prod_checking") {
    //     $output = array();
    //     $fifo_method='';
    //     $sql ="select param_value from software_customization where param_label ='Fifo Method' 
    //     and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
    //   $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //         $fifo_method = $row["param_value"];
                
    //         }
             
    //     }
    //     $lod_status='';
    //     $assay_status='';
    //      $sql ="SELECT a.id,a.batch_number,a.no_of_lots,a.work_order_no,a.rm_qa_dislc_date as 
    //      palnned_by,a.approved_by,a.lod_status,a.calculation_type,
    //      a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
    //      a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,p.dosage_form,b.batch_size,
    //      p.product_name,p.product_type,
    //          p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, 
    //          a.dispense_request_sent_on,a.dispensing_status,";
    //          if($_GET["material_type"] == 'Raw Material'){
               
    //             $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
    //          }else{
    //             $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
    //          }
             
             
    //         $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
    //          b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
    //          b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'
    //          and dispensing_status ='Request Sent'   "; 
    //         if($_GET["material_type"] == 'Raw Material'){
    //             $sql = $sql." and rm_store_status_entry_by!='' and rm_qa_dislc_status='Approved' ";
    //         }else{ 
    //             // $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
    //             $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
    //         }
        
    //     $result = $conn->query($sql);

    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $calculation_type = $row['calculation_type'];
    //             $row["checkpoints"] = json_decode($row["checkpoints"]);
    //             $output1 = array();
    //             $sql1 = "select a.*,b.avbl_stock from (SELECT a.*,prod_approved_by,dd.prod_checking,dd.id as  dd_id,b.material_type,b.category,b.material_subtype,b.grade as m_grade,
    //             b.material_name,wd.lod_status,wd.assay_status,
    //             IFNULL(dd.id,0) as dispence_id,dd.prod_status FROM work_order_batch_lots  a 
    //              join mfg_work_order_hdr c on a.work_order_id = c.id
    //              left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
    //              left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
    //              left join dispensing_details_hdr dd on a.id = dd.lot_id
    //              where a.work_order_id = '".$row["id"]."' and dd.prod_checking='Yes' ) as a left join
    //              (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
    //              WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots
    //              where work_order_id ='".$row["id"]."') 
    //              GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                   $category = $row1["category"];
                   
    //                     $sql2="select * from dispensing_details_hdr where id = '".$row1["dispence_id"]."'";
    //                     $result2 = $conn->query($sql2);
    //                     if ($result2->num_rows > 0) {
    //                         while ($row2 = $result2->fetch_assoc()) {
    //                             $output2 = $row2;
    //                         }
    //                     }
    //                      $row1["gross_total"] = $output2["gross_total"];
    //                       $row1["net_total"] = $output2["net_total"];
    //                       $row1["tare_total"] = $output2["tare_total"];
    //                     $row1["available_ars"] = json_decode($output2["ars"]);
    //                     $row1["containers"] = json_decode($output2["containers"]);
                       
    //                     if ($row1["status"] == "pending") {
    //                         $flag = 1;
    //                     }
    //                       $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['m_grade']."')";
    //                  $resQ = $conn->query($q);
    //                   $prodLatest = $resQ->fetch_assoc(); 
                 
    //               $row1['grade'] = $prodLatest['gradeName']; 
                              
    //                                   $output1[] = $row1;
    //                 }
    //             }
    //             $row["fifo_method"] =$fifo_method;
    //             $row["materials"] = $output1;
    //             $output[] = $row;
    //         }
    //     } 
        
        
    //     echo json_encode($output);
   
    // }
    else if ($_GET["type"] == "get_Dispensing_Requests_prod_checking") {
        
        
       $output = array();
        $fifo_method = '';
        
        // Escape input to prevent SQL injection
 $plant_id = mysqli_real_escape_string($conn, $_GET["plant_id"]);
        $material_type = isset($_GET["material_type"]) ? mysqli_real_escape_string($conn, $_GET["material_type"]) : '';
        
        $sql = "SELECT param_value FROM software_customization WHERE param_label = 'Fifo Method' 
                AND module = 'Dispensing Activity' AND plant_id = '".$plant_id."'";
      
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $fifo_method = $row["param_value"];
                break; // Only need first result
            }
        }
        
        
        
        $sql = "SELECT a.id, a.batch_number, a.no_of_lots, a.work_order_no, a.rm_qa_dislc_date as 
                palnned_by, a.approved_by, a.lod_status, a.calculation_type,
                a.stability, a.stability_reason, a.process_validation, a.qa_person, a.qa_date,
                (SELECT COALESCE(wm.doc_no, wm.workorder_no) FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1) as plan_no,
                
                (SELECT wm.product_code FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1) as product_code,
                p.dosage_form,
                (SELECT wm.batch_size FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1) as batch_size,
                p.product_name, p.product_type,
      
                (SELECT wm.packingStyle FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1) as pack_size,
                (SELECT wm.packingUnit FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1) as pack_unit,
                a.dispense_request_sent_by, 
                a.dispense_request_sent_on, a.dispensing_status, ";
        
        if ($material_type == 'Raw Material') {
            $sql = $sql . " a.rm_qa_dislc_status as lc_status, a.rm_qa_dislc_by as lc_by, a.rm_qa_dislc_date as lc_date ";
        } else {
            $sql = $sql . " a.pm_qa_dislc_status as lc_status, a.pm_qa_dislc_by as lc_by, a.pm_qa_dislc_date as lc_date ";
        }
        
        $sql = $sql . " FROM mfg_work_order_hdr a 
                LEFT JOIN Work_order_materials wm ON a.work_order_no = wm.workorder_no
                LEFT JOIN product p ON wm.product_code = p.product_code AND a.plant_id = p.plant_id
                WHERE a.plant_id = '".$plant_id."'
                AND dispensing_status = 'Request Sent'";
        
        if ($material_type == 'Raw Material') {
            $sql = $sql . " AND rm_store_status_entry_by != '' AND rm_qa_dislc_status = 'Approved'";
        } else {
            $sql = $sql . " AND pm_store_status_entry_by != '' AND pm_qa_dislc_status = 'Approved'";
        }
        
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];   
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                  $work_order_id = mysqli_real_escape_string($conn, $row["id"]);
                 $sql1 = "select a.*,b.avbl_stock from (
                   SELECT a.*, a.id as a_id, 
                  COALESCE(b.material_type, bm.material_type) AS material_type, 
                   b.category, 
                  COALESCE(b.material_subtype, bm.material_type) AS material_subtype,
                  COALESCE(b.material_name, bm.bulkName) AS material_name,
                   wd.lod_status, wd.assay_status, b.grade as m_grade,
                   IFNULL(dd.id, 0) as dispence_id, dd.prod_approved_by,dd.prod_status FROM work_order_batch_lots a JOIN mfg_work_order_hdr c ON a.work_order_id = c.id LEFT JOIN 
                   mfg_work_order_dtl wd ON wd.work_order_id = c.id AND wd.material_code = a.material_code
                   LEFT JOIN material b ON a.material_code = b.material_code
                   LEFT JOIN bulkMaster bm ON a.material_code = bm.bulkCode
                   AND c.plant_id = b.plant_id LEFT JOIN dispensing_details_hdr dd ON wd.id = dd.dtl_id 
                    WHERE a.work_order_id = '".$work_order_id."' AND dd.prod_checking = 'Yes'
                ) as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots
                 where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                       $category = $row1["category"];
                   
                        $sql2="select * from dispensing_details_hdr where id = '".$row1["dispence_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2 = $row2;
                            }
                        }
                         $row1["gross_total"] = $output2["gross_total"];
                          $row1["net_total"] = $output2["net_total"];
                           $row1["tare_total"] = $output2["tare_total"];
                        $row1["available_ars"] = json_decode($output2["ars"]);
                        $row1["containers"] = json_decode($output2["containers"]);
                       
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                          $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['m_grade']."')";
                     $resQ = $conn->query($q);
                      $prodLatest = $resQ->fetch_assoc(); 
                 
                  $row1['grade'] = $prodLatest['gradeName']; 
                              
                                      $output1[] = $row1;
                    }
                }
                $row["fifo_method"] =$fifo_method;
                $row["materials"] = $output1;
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
   
    
    }
    else if ($_GET["type"] == "get_Dispensing_Requests_prod_checking_pk") {
        $output = array();
        $fifo_method='';
        $sql ="select param_value from software_customization where param_label ='Fifo Method' 
        and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $fifo_method = $row["param_value"];
                
            }
            
        }
        $lod_status='';
        $assay_status='';
        //   $sql ="SELECT a.id,a.batch_number,a.no_of_lots,a.work_order_no,a.rm_qa_dislc_date as 
        //  palnned_by,a.approved_by,a.lod_status,a.calculation_type,
        //  a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
        //  a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,p.dosage_form,b.batch_size,
        //  p.product_name,.p.product_type,
        //      p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, 
        //      a.dispense_request_sent_on,a.dispensing_status,a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date
        //      FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
        //      b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
        //      b.plant_id = p.plant_id where  a.plant_id ='29'
        //      and dispensing_status ='Request Sent' and pm_store_status_entry_by!=''";
        $sql="SELECT a.id,a.batch_number,a.no_of_lots,a.work_order_no,a.rm_qa_dislc_date as 
         palnned_by,a.approved_by,a.lod_status,a.calculation_type,
         a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
         a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,p.dosage_form,b.batch_size,
         p.product_name,p.product_type,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, 
             a.dispense_request_sent_on,a.dispensing_status,a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date
             FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id left JOIN batch_planning_materials bp on b.id=bp.batch_plan_id left JOIN dispensing_details_hdr dd on bp.pm_hdr_id=dd.pm_hdr_id
             
             
             where  a.plant_id ='29'
             and a.dispensing_status ='Request Sent' and a.pm_store_status_entry_by!='' and bp.dispensing_status='complete'";
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                // $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                // IFNULL(dd.id,0) as dispence_id FROM work_order_batch_lots  a 
                //  join mfg_work_order_hdr c on a.work_order_id = c.id
                //  left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                //  left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                //  left join dispensing_details_hdr dd on a.id = dd.lot_id
                //  where a.work_order_id = '".$row["id"]."' and dd.prod_checking='Yes' and dd.prod_approved_by='') as a left join
                //  (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                //  WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                //  GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                $sql1 = "SELECT
                                    a.*,
                                    b.avbl_stock
                                FROM
                                    (
                                    SELECT
                                        a.*,
                                        b.material_code,
                                        d.material_type AS m_type,
                                        d.category,
                                        d.material_subtype,
                                        d.material_name,
                                        c.lod_status AS lod_stat,
                                        c.assay_status AS assay_stat,
                                        b.disp_id AS dispence_id,
                                        b.pm_hdr_id,
                                        b.batch_qty
                                    FROM
                                        mfg_work_order_hdr a
                                    LEFT JOIN batch_planning_materials b ON
                                        a.batch_plan_id = b.batch_plan_id
                                    LEFT JOIN mfg_work_order_dtl c ON
                                        b.material_code = c.material_code AND c.work_order_id = a.id
                                    LEFT JOIN material d ON
                                        b.material_code = d.material_code AND a.plant_id = d.plant_id
                                    LEFT JOIN dispensing_details_hdr e ON
                                        a.id = e.lot_id
                                    WHERE
                                        a.id = '".$row["id"]."'  
                                ) AS a
                                LEFT JOIN(
                                    SELECT
                                        material_code,
                                        SUM(qty) AS avbl_stock
                                    FROM
                                        stock_book
                                    WHERE
                                        plant_id = '".$_GET["plant_id"]."' AND material_code IN(
                                        SELECT
                                            material_code
                                        FROM
                                            work_order_batch_lots
                                        WHERE
                                            work_order_id = '".$row["id"]."'
                                    )
                                GROUP BY
                                    material_code
                                ) AS b
                                ON
                                    a.material_code = b.material_code AND a.material_type = 'Packing Material' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                       $category = $row1["category"];
                     /* $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$lod_status."' = 'On Dried Basis' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                         case when '".$assay_status."' = 'Assay Basis' then  ROUND((((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no";*/
                        //echo $sql2;
                        $sql2="select * from dispensing_details_hdr where id = '".$row1["dispence_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2 = $row2;
                            }
                        }
                         $row1["gross_total"] = $output2["gross_total"];
                          $row1["net_total"] = $output2["net_total"];
                           $row1["tare_total"] = $output2["tare_total"];
                        $row1["available_ars"] = json_decode($output2["ars"]);
                        $row1["containers"] = json_decode($output2["containers"]);
                       
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                      
                                      $output1[] = $row1;
                    }
                }
                $row["fifo_method"] =$fifo_method;
                $row["materials"] = $output1;
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
    }
    
    else if($_GET["type"] == "get_dispensing_Activity_By_Id") {
        $output2;
        $sql2="select * from dispensing_details_hdr where pm_hdr_id = '".$_GET["pm_hdr_id"]."'";
        $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                
                  $row2["ars"] = json_decode($row2["ars"]);
                 $row2["containers"] = json_decode($row2["containers"]);
                $output2 = $row2;
            }
        }
         echo json_encode($output2);
    }
  
     else if($_GET["type"] == "get_bmr_checklist_by_product_Code") {
         $output2 = array();
         
        $sql2="select * from stages_ipqc where product_code = '".$_GET["product_code"]."' limit 1";
         $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $output= Array();
                        //old code #working
                
                $sql111="select  specification_no  from specification  where product_code = '".$_GET['product_code']."' order by id desc limit 1";
                
                $result111 = $conn->query($sql111);
                if ($result111->num_rows > 0) {
                    while ($row111 = $result111->fetch_assoc()) {
                         
                        $specification_no = $row111['specification_no'];
                    }
                } 
                
                $sql="select DISTINCT a.stage_hdr_id, a.id,a.stage_hdr_id,a.process_type,a.stage_name,a.stage,a.ipqc_testing,a.next_stage,
                a.yieldRequired,a.exp_yeild_percent,a.yeild_unit,a.split_into_lots,a.blending_mixing,'Pending' as ipqc_status,s.test,s.limit_type,s.description,s.lower_limit,
                s.upper_limit,s.limits,s.description from stages_ipqc_dtl a LEFT JOIN spec_tests s on a.test_name=s.test  where
                a.stage_hdr_id = '".$row2['id']."' AND  s.specification_no = '$specification_no' ";
                
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                          
                        $output[] = $row;
                    }
                }
                
                // $row2['bmr_checklist'] = $output;
                // $output2 = $row2;
                
                $output1= Array();
                $sql="select id as batch_stage_id ,stage_hdr_id,stage_dtl_id,lot_no,remarks_entry_by,process_entry_by,yeild_entry_by,
                test_result_status,test_result from batch_stages_ipqc_dtl 
                where stage_hdr_id = '".$row2['id']."' AND plan_no = '".$_GET['plan_no']."'";
                
                $result1 = $conn->query($sql);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                              
              $sql1="select b.*,s.yieldRequired from batch_stages_ipqc_dtl b left join stages_ipqc_dtl s ON b.stage_dtl_id = s.id  where 
            b.id = '".$row1['batch_stage_id']."' AND ( b.yeild_entry_by!='' OR s.yieldRequired = 'No' )   AND
            b.process_entry_by!='' AND b.test_result_status ='Approved'  AND b.plan_no = '".$_GET['plan_no']."' ";
                            
                            $result11 = $conn->query($sql1);
                            if ($result11->num_rows > 0) {
                                 $row1['stage_status'] = 'Complete';
                            }else{
                                $row1['stage_status'] = 'Pending';
                            }
                        
                        
                        
                        $output1[] = $row1;
                    }
                }
                
                
                
                
                $output3= Array();
                $sql="SELECT b1. *,b2.stage_hdr_id as stage_id,b2.stage_name,b2.stage as step,b1.lot_no as test_nos,s.ar_no,t.perform_by,t.remark,t.start_time,
                t.end_time,t.observation,t.test,st.limits,t.result FROM batch_stages_ipqc_dtl b1 
                left join stages_ipqc_dtl b2 ON b1.stage_dtl_id=b2.id 
                LEFT JOIN technical_info s ON b1.ti_no=s.ti_no 
                LEFT JOIN testing_tests t ON t.bmr_qcsample_tests_id = s.id 
                LEFT JOIN spec_tests st ON t.spec_test_id = st.id 
                where b1.stage_hdr_id = '".$row2['id']."'  AND b1.plan_no = '".$_GET['plan_no']."' ";
                
                
                $result3 = $conn->query($sql);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        $output3[] = $row3;
                    }
                }
                
                $output5= Array();
                $sql5="SELECT b1. *,b2.stage_hdr_id as stage_id,b2.stage_name,b2.stage as step,b1.lot_no as test_nos,s.ar_no,t.perform_by,t.remark,t.start_time,
                t.end_time,t.observation,t.test,st.limits,t.result FROM batch_stages_ipqc_dtl b1 
                left join stages_ipqc_dtl b2 ON b1.stage_dtl_id=b2.id 
                LEFT JOIN technical_info s ON b1.ti_no=s.ti_no 
                LEFT JOIN testing_tests t ON t.bmr_qcsample_tests_id = s.id 
                LEFT JOIN spec_tests st ON t.spec_test_id = st.id 
                where b1.stage_hdr_id = '".$row2['id']."' AND b1.yeild_entry_by!='' AND b1.plan_no = '".$_GET['plan_no']."' ";
                
                
                $result5 = $conn->query($sql5);
                if ($result5->num_rows > 0) {
                    while ($row5 = $result5->fetch_assoc()) {
                        $output5[] = $row5;
                    }
                }
                
                $output4= Array();
                
                $sql="SELECT b1. *,e2.firstname,e.equipment_name,b2.stage_hdr_id as stage_id,b2.stage_name,b1.lot_no as test_no FROM batch_stages_ipqc_dtl
                b1 left join stages_ipqc_dtl b2 ON b1.stage_dtl_id=b2.id LEFT JOIN equipment e ON b1.equipment_code=e.equipment_code LEFT JOIN employee 
                e2 ON b1.operator_code=e2.emp_id where b1.equipment_code!='' and b1.stage_hdr_id = '".$row2['id']."' AND b1.plan_no = '".$_GET['plan_no']."' ";
                $result4 = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row4 = $result4->fetch_assoc()) {
                        $output4[] = $row4;
                    }
                }
                
                $row2['eq_usage'] = $output4;
                $row2['ti_sheet'] = $output3;
                $row2['yieldsData'] = $output5;
                $row2['bmr_checklist'] = $output;
                $row2['stage_process_details'] = $output1;
                $row2["stages_test"] = json_decode($row2["stages_test"]);
                $output2 = $row2;
                
                
                
            }
        }
         echo json_encode($output2);
    }
     else if($_GET["type"] == "get_bmr_checklist_by_product_Code_pk") {
         
         
         $output2 = array();
         
        $sql2="select * from packing_stages_ipqc where product_code = '".$_GET["product_code"]."' limit 1";
         $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $output= Array();
                        //old code #working
                
                $sql111="select  specification_no  from specification  where product_code = '".$_GET['product_code']."' order by id desc limit 1";
                
                $result111 = $conn->query($sql111);
                if ($result111->num_rows > 0) {
                    while ($row111 = $result111->fetch_assoc()) {
                         
                        $specification_no = $row111['specification_no'];
                    }
                } 
                
                $sql="select DISTINCT a.stage_hdr_id, a.id,a.stage_hdr_id,a.process_type,a.stage_name,a.stage,a.ipqc_testing,a.next_stage,
                a.yieldRequired,a.exp_yeild_percent,a.yeild_unit,a.split_into_lots,a.blending_mixing,'Pending' as ipqc_status,s.test,s.limit_type,s.description,s.lower_limit,
                s.upper_limit,s.limits,s.description from packing_stages_ipqc_dtl a LEFT JOIN spec_tests s on a.test_name=s.test  where
                a.stage_hdr_id = '".$row2['id']."' AND  s.specification_no = '$specification_no' ";
                
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                          
                        $output[] = $row;
                    }
                }
                
                // $row2['bmr_checklist'] = $output;
                // $output2 = $row2;
                
                $output1= Array();
                $sql="select id as batch_stage_id ,stage_hdr_id,stage_dtl_id,lot_no,remarks_entry_by,process_entry_by,yeild_entry_by,
                test_result_status,test_result from packing_batch_stages_ipqc_dtl 
                where stage_hdr_id = '".$row2['id']."' AND plan_no = '".$_GET['plan_no']."'";
                
                $result1 = $conn->query($sql);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                              
              $sql1="select b.*,s.yieldRequired from packing_batch_stages_ipqc_dtl b left join packing_stages_ipqc_dtl s ON b.stage_dtl_id = s.id  where 
            b.id = '".$row1['batch_stage_id']."' AND ( b.yeild_entry_by!='' OR s.yieldRequired = 'No' )   AND
            b.process_entry_by!='' AND b.test_result_status ='Approved'  AND b.plan_no = '".$_GET['plan_no']."' ";
                            
                            $result11 = $conn->query($sql1);
                            if ($result11->num_rows > 0) {
                                 $row1['stage_status'] = 'Complete';
                            }else{
                                $row1['stage_status'] = 'Pending';
                            }
                        
                        
                        
                        $output1[] = $row1;
                    }
                }
                
                
                
                
                $output3= Array();
                $sql="SELECT b1. *,b2.stage_hdr_id as stage_id,b2.stage_name,b2.stage as step,b1.lot_no as test_nos,s.ar_no,t.perform_by,t.remark,t.start_time,
                t.end_time,t.observation,t.test,st.limits,t.result FROM packing_batch_stages_ipqc_dtl b1 
                left join packing_stages_ipqc_dtl b2 ON b1.stage_dtl_id=b2.id 
                LEFT JOIN technical_info s ON b1.ti_no=s.ti_no 
                LEFT JOIN testing_tests t ON t.bmr_qcsample_tests_id = s.id 
                LEFT JOIN spec_tests st ON t.spec_test_id = st.id 
                where b1.stage_hdr_id = '".$row2['id']."'  AND b1.plan_no = '".$_GET['plan_no']."' ";
                
                
                $result3 = $conn->query($sql);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        $output3[] = $row3;
                    }
                }
                
                $output5= Array();
                $sql5="SELECT b1. *,b2.stage_hdr_id as stage_id,b2.stage_name,b2.stage as step,b1.lot_no as test_nos,s.ar_no,t.perform_by,t.remark,t.start_time,
                t.end_time,t.observation,t.test,st.limits,t.result FROM packing_batch_stages_ipqc_dtl b1 
                left join packing_stages_ipqc_dtl b2 ON b1.stage_dtl_id=b2.id 
                LEFT JOIN technical_info s ON b1.ti_no=s.ti_no 
                LEFT JOIN testing_tests t ON t.bmr_qcsample_tests_id = s.id 
                LEFT JOIN spec_tests st ON t.spec_test_id = st.id 
                where b1.stage_hdr_id = '".$row2['id']."' AND b1.yeild_entry_by!='' AND b1.plan_no = '".$_GET['plan_no']."' ";
                
                
                $result5 = $conn->query($sql5);
                if ($result5->num_rows > 0) {
                    while ($row5 = $result5->fetch_assoc()) {
                        $output5[] = $row5;
                    }
                }
                
                $output4= Array();
                
                $sql="SELECT b1. *,e2.firstname,e.equipment_name,b2.stage_hdr_id as stage_id,b2.stage_name,b1.lot_no as test_no FROM packing_batch_stages_ipqc_dtl
                b1 left join packing_stages_ipqc_dtl b2 ON b1.stage_dtl_id=b2.id LEFT JOIN equipment e ON b1.equipment_code=e.equipment_code LEFT JOIN employee 
                e2 ON b1.operator_code=e2.emp_id where b1.equipment_code!='' and b1.stage_hdr_id = '".$row2['id']."' AND b1.plan_no = '".$_GET['plan_no']."' ";
                $result4 = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row4 = $result4->fetch_assoc()) {
                        $output4[] = $row4;
                    }
                }
                
                $row2['eq_usage'] = $output4;
                $row2['ti_sheet'] = $output3;
                $row2['yieldsData'] = $output5;
                $row2['bmr_checklist'] = $output;
                $row2['stage_process_details'] = $output1;
                $row2["stages_test"] = json_decode($row2["stages_test"]);
                $output2 = $row2;
                
                
                
            }
        }
         echo json_encode($output2);
    
     }
    //  else if($_GET["type"] == "get_bmr_checklist_by_product_Code_pk") {
    //      $output2 = array();
         
    //     $sql2="select * from packing_stage  where product_code = '".$_GET["product_code"]."' limit 1";
    //      $result2 = $conn->query($sql2);
    //     if ($result2->num_rows > 0) {
    //         while ($row2 = $result2->fetch_assoc()) {
    //             $output= Array();
    //                     //old code #working
                
    //             $sql111="select  specification_no  from specification  where product_code = '".$_GET['product_code']."' order by id desc limit 1";
                
    //             $result111 = $conn->query($sql111);
    //             if ($result111->num_rows > 0) {
    //                 while ($row111 = $result111->fetch_assoc()) {
                         
    //                     $specification_no = $row111['specification_no'];
    //                 }
    //             } 
                
    //             $sql="select DISTINCT a.stage_hdr_id, a.id,a.stage_hdr_id,a.process_type,a.stage_name,a.stage,a.ipqc_testing,a.next_stage,
    //              a.exp_yeild_percent,a.yeild_unit,a.split_into_lots,a.blending_mixing,'Pending' as ipqc_status,s.test,s.limit_type,s.description,s.lower_limit,
    //             s.upper_limit,s.limits,s.description from packing_stages_ipqc_dtl  a LEFT JOIN spec_tests s on a.test_name=s.test  where
    //             a.stage_hdr_id = '".$row2['id']."' AND  s.specification_no = '$specification_no' ";
                
    //             $result = $conn->query($sql);
    //             if ($result->num_rows > 0) {
    //                 while ($row = $result->fetch_assoc()) {
                          
    //                     $output[] = $row;
    //                 }
    //             }
                
    //             // $row2['bmr_checklist'] = $output;
    //             // $output2 = $row2;
                
    //             $output1= Array();
    //             $sql="select id as batch_stage_id ,stage_hdr_id,id as stage_dtl_id,
    //             test_result from packing_stages_ipqc_dtl  
    //             where stage_hdr_id = '".$row2['id']."' AND plan_no = '".$_GET['plan_no']."'";
                
    //             $result1 = $conn->query($sql);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
                              
    //           $sql1="select b.*,s.yieldRequired from packing_batch_stages_ipqc_dtl  b left join packing_stages_ipqc_dtl  s ON b.stage_dtl_id = s.id  where 
    //         b.id = '".$row1['batch_stage_id']."' AND ( b.yeild_entry_by!='' OR s.yieldRequired = 'No' )   AND
    //         b.process_entry_by!='' AND b.test_result_status ='Approved'  AND b.plan_no = '".$_GET['plan_no']."' ";
                            
    //                         $result11 = $conn->query($sql1);
    //                         if ($result11->num_rows > 0) {
    //                              $row1['stage_status'] = 'Complete';
    //                         }else{
    //                             $row1['stage_status'] = 'Pending';
    //                         }
                        
                        
                        
    //                     $output1[] = $row1;
    //                 }
    //             }
            
    //             //           $output1= Array();
    //             // $sql="select id as batch_stage_id ,stage_hdr_id,stage_dtl_id,lot_no,remarks_entry_by,process_entry_by,yeild_entry_by,test_result_status,test_result from packing_batch_stages_ipqc_dtl 
    //             // where stage_hdr_id = '".$row2['id']."' ";
                
    //             // $result1 = $conn->query($sql);
    //             // if ($result->num_rows > 0) {
    //             //     while ($row1 = $result1->fetch_assoc()) {
    //             //         $output1[] = $row1;
    //             //     }
                    
    //             // }
                
                
                
                
    //             $output3= Array();
    //             $sql="SELECT b1. *,b2.stage_hdr_id as stage_id,b2.stage_name,b2.stage as step,b1.lot_no as test_nos,s.ar_no,t.perform_by,t.remark,t.start_time,
    //             t.end_time,t.observation,t.test,st.limits,t.result FROM packing_batch_stages_ipqc_dtl b1 
    //             left join  packing_stages_ipqc_dtl  b2 ON b1.stage_dtl_id=b2.id 
    //             LEFT JOIN technical_info s ON b1.ti_no=s.ti_no 
    //             LEFT JOIN testing_tests t ON t.bmr_qcsample_tests_id = s.id 
    //             LEFT JOIN spec_tests st ON t.spec_test_id = st.id 
    //             where b1.stage_hdr_id = '".$row2['id']."'  AND b1.plan_no = '".$_GET['plan_no']."' ";
                
                
    //             $result3 = $conn->query($sql);
    //             if ($result3->num_rows > 0) {
    //                 while ($row3 = $result3->fetch_assoc()) {
    //                     $output3[] = $row3;
    //                 }
    //             }
                
    //             $output5= Array();
    //             $sql5="SELECT b1. *,b2.stage_hdr_id as stage_id,b2.stage_name,b2.stage as step,b1.lot_no as test_nos,s.ar_no,t.perform_by,t.remark,t.start_time,
    //             t.end_time,t.observation,t.test,st.limits,t.result FROM packing_batch_stages_ipqc_dtl  b1 
    //             left join  packing_stages_ipqc_dtl  b2 ON b1.stage_dtl_id=b2.id 
    //             LEFT JOIN technical_info s ON b1.ti_no=s.ti_no 
    //             LEFT JOIN testing_tests t ON t.bmr_qcsample_tests_id = s.id 
    //             LEFT JOIN spec_tests st ON t.spec_test_id = st.id 
    //             where b1.stage_hdr_id = '".$row2['id']."' AND b1.yeild_entry_by!='' AND b1.plan_no = '".$_GET['plan_no']."' ";
                
                
    //             $result5 = $conn->query($sql5);
    //             if ($result5->num_rows > 0) {
    //                 while ($row5 = $result5->fetch_assoc()) {
    //                     $output5[] = $row5;
    //                 }
    //             }
                
    //             $output4= Array();
                
    //             $sql="SELECT b1. *,e2.firstname,e.equipment_name,b2.stage_hdr_id as stage_id,b2.stage_name,b1.lot_no as test_no FROM packing_batch_stages_ipqc_dtl
    //             b1 left join  packing_stages_ipqc_dtl  b2 ON b1.stage_dtl_id=b2.id LEFT JOIN equipment e ON b1.equipment_code=e.equipment_code LEFT JOIN employee 
    //             e2 ON b1.operator_code=e2.emp_id where b1.equipment_code!='' and b1.stage_hdr_id = '".$row2['id']."' AND b1.plan_no = '".$_GET['plan_no']."' ";
    //             $result4 = $conn->query($sql);
    //             if ($result->num_rows > 0) {
    //                 while ($row4 = $result4->fetch_assoc()) {
    //                     $output4[] = $row4;
    //                 }
    //             }
                
    //             $row2['eq_usage'] = $output4;
    //             $row2['ti_sheet'] = $output3;
    //             $row2['yieldsData'] = $output5;
    //             $row2['bmr_checklist'] = $output;
    //             $row2['stage_process_details'] = $output1;
    //             $row2["stages_test"] = json_decode($row2["stages_test"]);
    //             $output2 = $row2;
                
                
                
    //         }
    //     }
    //      echo json_encode($output2);
    // }
    else if($_GET["type"] == "get_bmr_checklist_for_testing") {
        $output2;
        $sql2="select * from stages_ipqc where product_code = '".$_GET["product_code"]."' limit 1";
        $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $output= Array();
                $sql="select a.*,'Pending' as ipqc_status from stages_ipqc_dtl a where stage_hdr_id = '".$row2['id']."' ";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                    
                }
                $row2['bmr_checklist'] = $output;
                $output2 = $row2;
                
                
                $output1= Array();
                $sql="select id as batch_stage_id ,stage_hdr_id,stage_dtl_id,lot_no,remarks_entry_by,process_entry_by,yeild_entry_by 
                ti_recd_by,test_result,test_remarks,test_result_status from batch_stages_ipqc_dtl 
                where stage_hdr_id = '".$row2['id']."' and plan_no= '".$_GET["plan_no"]."'  
                AND bfr_no ='".$_GET["bfr_no"]."' ";
                
                $result1 = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                    
                }
                $row2['bmr_checklist'] = $output;
                $row2['stage_process_details'] = $output1;
                $output2 = $row2;
                
                
                
            }
        }
         echo json_encode($output2);
    }
    else if($_GET["type"] == "get_bmr_checklist_for_testing_pk") {
        $output2;
        $sql2="select * from packing_stage where product_code = '".$_GET["product_code"]."' limit 1";
        $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $output= Array();
                $sql="select a.*,'Pending' as ipqc_status from packing_stages_ipqc_dtl a where stage_hdr_id = '".$row2['id']."' ";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                    
                }
                $row2['bmr_checklist'] = $output;
                $output2 = $row2;
                
                
                $output1= Array();
                $sql="select id as batch_stage_id ,stage_hdr_id,stage_dtl_id,lot_no,remarks_entry_by,process_entry_by,yeild_entry_by 
                ti_recd_by,test_result,test_remarks,test_result_status from packing_batch_stages_ipqc_dtl 
                where stage_hdr_id = '".$row2['id']."' and plan_no= '".$_GET["plan_no"]."'  
                AND bfr_no ='".$_GET["bfr_no"]."' ";
                
                $result1 = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                    
                }
                $row2['bmr_checklist'] = $output;
                $row2['stage_process_details'] = $output1;
                $output2 = $row2;
                
                
                
            }
        }
         echo json_encode($output2);
    }
    else if ($_GET["type"] == "get_Dispensing_Activity_Requests_Formulation") {
        $output = array();
        $fifo_method='';
        $sql ="select param_value from software_customization where param_label ='Fifo Method' and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $fifo_method = $row["param_value"];
                
            }
            
        }
        $lod_status='';
        $assay_status='';
         $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,a.lod_status,a.assay_status,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." and rm_store_status_entry_by!='' and rm_qa_dislc_status='Approved' ";
            }else{
                $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
         echo $sql;
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lod_status = $row['lod_status'];
                $assay_status = $row['assay_status'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,IFNULL(dd.id,0) as dispence_id
                 FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                         $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$lod_status."' = 'On Dried Basis' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                         case when '".$assay_status."' = 'Assay Basis' then  ROUND((((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no";
                    //echo $sql2;
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $sql2 ="Select * from dispensing_details_hdr where id = '".$row1["dispence_id"]."' limit 1";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output3 = $row2;
                            }
                        }
                        
                        $row1["ar_data"] = $output3;
                        
                      
                                      $output1[] = $row1;
                    }
                }
                $row["fifo_method"] =$fifo_method;
                $row["materials"] = $output1;
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
    }
    
    
    
    else if ($_GET["type"] == "getHoldRequests") {
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.dosage_form, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='On Hold' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
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
    } else if ($_GET["type"] == "saveRequestRM") {
        $sql = "UPDATE mfg_work_order_hdr SET rm_store_status='".$input["status"]."',
         rm_store_status_entry_by='".$_GET["emp_id"]."', 
         rm_store_remarks = '".$input["remarks"]."',
         rm_store_status_entry_date='$entry_date'
        WHERE id='".$_GET["id"]."'";
         
        if ($conn->query($sql)) {
            $r_materials = $input["materials"];
            for ($i = 0; $i <count($r_materials); $i++) {
                $material = $r_materials[$i];
                $sql = "Update mfg_work_order_dtl set physical_stock ='".$material['physical_stock_status']."' 
                        where work_order_id = '".$_GET["id"]."' and id = '".$material["id"]."' ";
                         
                $conn->query($sql);
                
            }
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "update_qa_dispence_status") {
        $sql = "UPDATE dispensing_details_hdr SET qa_status='".$_GET["status"]."',
        qa_approved_date='".$entry_date."', qa_approved_by='".$_GET["emp_id"]."'  WHERE id='".$_GET["id"]."'";
         
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "update_prod_dispence_status") {
        $sql = "UPDATE dispensing_details_hdr SET prod_status='".$_GET["status"]."',
        prod_approved_date='".$entry_date."', prod_approved_by='".$_GET["emp_id"]."'  WHERE id='".$_GET["id"]."'";
        // $sql = "UPDATE dispensing_details_hdr SET prod_status='".$_GET["status"]."',
        // prod_approved_date='".$entry_date."', prod_approved_by='".$_GET["emp_id"]."'  WHERE pm_hdr_id='".$_GET["pm_hdr_id"]."'";
       
        if ($conn->query($sql)) {
        $sql="update batch_planning_materials set disp_id='2' where pm_hdr_id='".$_GET["pm_hdr_id"]."'";
           $result = $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }  
    }
    else if ($_GET["type"] == "update_dispense_complete_by_store") {
        $sql = "UPDATE mfg_work_order_hdr SET  
        rm_disp_completed_date='".$entry_date."', rm_disp_completed_by='".$_GET["emp_id"]."'  WHERE id='".$_GET["id"]."'";
         
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    // else if ($_GET["type"] == "pm_update_dispense_complete_by_store") {
    //       $sql = "UPDATE sp_bmr_sifting SET  
    //     pm_disp_completed_date='".$entry_date."', pm_disp_completed_by='".$_GET["emp_id"]."'  WHERE id='".$_GET["sift_id"]."'";
         
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    else if ($_GET["type"] == "pm_update_dispense_complete_by_store") {
          $sql = "UPDATE mfg_work_order_hdr SET  
        pm_disp_completed_date='".$entry_date."', pm_disp_completed_by='".$_GET["emp_id"]."'  WHERE id='".$_GET["id"]."'";
         
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveRequestPM") {
        $sql = "UPDATE mfg_work_order_hdr SET pm_store_status='".$input["status"]."',
         pm_store_status_entry_by='".$_GET["emp_id"]."', 
         pm_store_remarks = '".$input["remarks"]."',
         pm_store_status_entry_date='$entry_date'
        WHERE id='".$_GET["id"]."'";
         
        if ($conn->query($sql)) {
            $r_materials = $input["materials"];
            for ($i = 0; $i <count($r_materials); $i++) {
                $material = $r_materials[$i];
                $sql = "Update mfg_work_order_dtl set physical_stock ='".$material['physical_stock_status']."' 
                        where work_order_id = '".$_GET["id"]."' and id = '".$material["id"]."' ";
                         
                $conn->query($sql);
                
            }
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveRequestPM_saipro") {
         $sql = "UPDATE sp_bmr_sifting SET pm_store_status='".$input["status"]."',
         pm_store_status_entry_by='".$_GET["emp_id"]."', 
         pm_store_remarks = '".$input["remarks"]."',
         pm_store_status_entry_date='$entry_date'
        WHERE id='".$_GET["sift_id"]."'";
         
        if ($conn->query($sql)) {
          $r_materials = $input["materials"];
            for ($i = 0; $i <count($r_materials); $i++) {
                $material = $r_materials[$i];
                     "Debug: id = " . $_GET["id"] . ", material_id = " . $material["id"] . ", stock_status = " . $material['physical_stock_status'] . "<br>";
                  $sql1 = "Update mfg_work_order_dtl_pm_lots set physical_stock ='".$material['physical_stock_status']."' 
                        where sp_bmr_sifting_id = '".$_GET["sift_id"]."' and id = '".$material["id"]."' ";
                         
                $conn->query($sql1);
                
            }
            $check = $input["checklist"];
            for ($i = 0; $i <count($check); $i++) {
                $checks = $check[$i];
                 $sql1 = "INSERT INTO pm_dispensing_checklist_transaction(sp_bmr_sifting_id, checkpoint, remarkss,work_order_id)VALUES( '".$_GET["sift_id"]."','".$checks["checkpoint"]."','".$checks["remarkss"]."','".$input["work_order_id"]."')";
                         
                $conn->query($sql1);
                
            }
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    // else if ($_GET["type"] == "saveRequestPM_saipro") {
    //     $sql = "UPDATE mfg_work_order_hdr SET pm_store_status='".$input["status"]."',
    //      pm_store_status_entry_by='".$_GET["emp_id"]."', 
    //      pm_store_remarks = '".$input["remarks"]."',
    //      pm_store_status_entry_date='$entry_date'
    //     WHERE id='".$_GET["id"]."'";
         
    //     if ($conn->query($sql)) {
    //       $r_materials = $input["materials"];
    //         for ($i = 0; $i <count($r_materials); $i++) {
    //             $material = $r_materials[$i];
    //                  "Debug: id = " . $_GET["id"] . ", material_id = " . $material["id"] . ", stock_status = " . $material['physical_stock_status'] . "<br>";
    //              $sql1 = "Update mfg_work_order_dtl set physical_stock ='".$material['physical_stock_status']."' 
    //                     where work_order_id = '".$_GET["id"]."' and id = '".$material["id"]."' ";
                         
    //             $conn->query($sql1);
                
    //         }
    //         $check = $input["checklist"];
    //         for ($i = 0; $i <count($check); $i++) {
    //             $checks = $check[$i];
    //             $sql1 = "INSERT INTO pm_dispensing_checklist_transaction( checkpoint, remarkss,work_order_id)VALUES('".$checks["checkpoint"]."','".$checks["remarkss"]."','".$input["work_order_id"]."')";
                         
    //             $conn->query($sql1);
                
    //         }
            
            
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    else if ($_GET["type"] == "saveCheckRequestPM_saipro") {
        $sql = "UPDATE mfg_work_order_hdr SET pm_store_status='".$input["status"]."'
        WHERE id='".$input["work_order_id"]."'";
        if ($conn->query($sql)) {
           $date = date('d-m-y');
           if($input["checklist"]!=''){
                 $check = $input["checklist"];
            for ($i = 0; $i <count($check); $i++) {
                $checks = $check[$i];
                $sql1 = "update pm_dispensing_checklist_transaction set checked_by='".$_GET["emp_id"]."' ,checked_date='$date'  where  sp_bmr_sifting_id='".$input["sift_id"]."'";
                $conn->query($sql1);
            }
           }
          
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    // else if ($_GET["type"] == "saveCheckRequestPM_saipro") {
    //     $sql = "UPDATE mfg_work_order_hdr SET pm_store_status='".$input["status"]."'
      
    //     WHERE id='".$_GET["id"]."'";
         
    //     if ($conn->query($sql)) {
    //       $date = date('d-m-y');
    //         $check = $input["checklist"];
    //         for ($i = 0; $i <count($check); $i++) {
    //             $checks = $check[$i];
    //             $sql1 = "update pm_dispensing_checklist_transaction set checked_by='".$_GET["emp_id"]."' ,checked_date='$date'  where  work_order_id='".$input["work_order_id"]."'";
                         
    //             $conn->query($sql1);
                
    //         }
            
            
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    else if ($_GET["type"] == "saveApproveRequestPM_saipro") {
        $sql = "UPDATE mfg_work_order_hdr SET pm_store_status='".$input["status"]."',pm_store_disp_date='$entry_date'
      
     WHERE id='".$input["work_order_id"]."'";
         
        if ($conn->query($sql)) {
           $date = date('d-m-y');
        //   if($input["work_order_id"]!=''){
        //       $check = $input["checklist"];
        //     for ($i = 0; $i <count($check); $i++) {
        //         $checks = $check[$i];
        //         $sql1 = "update pm_dispensing_checklist_transaction set verified_by='".$_GET["emp_id"]."' ,verified_date='$date'   where  sp_bmr_sifting_id='".$input["sift_id"]."'";
                         
        //         $conn->query($sql1);
                
        //     }
        //   }
            
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    // else if ($_GET["type"] == "saveApproveRequestPM_saipro") {
    //     $sql = "UPDATE mfg_work_order_hdr SET pm_store_status='".$input["status"]."',pm_store_disp_date='$entry_date'
      
    //     WHERE id='".$_GET["id"]."'";
         
    //     if ($conn->query($sql)) {
    //       $date = date('d-m-y');
    //         $check = $input["checklist"];
    //         for ($i = 0; $i <count($check); $i++) {
    //             $checks = $check[$i];
    //             $sql1 = "update pm_dispensing_checklist_transaction set verified_by='".$_GET["emp_id"]."' ,verified_date='$date'  where  work_order_id='".$input["work_order_id"]."'";
                         
    //             $conn->query($sql1);
                
    //         }
            
            
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    
    else if ($_GET["type"] == "getAcceptedRequests") {
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='Accept' GROUP BY d.product_code";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $flag = 0;
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        /*$sql2 = "SELECT lower_limit FROM spec_tests WHERE specification_no=(SELECT specification_no FROM specification WHERE spec_type='Raw Material' AND material_code='".$row1["material_code"]."')";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["assay_per"] = +$row2["lower_limit"];
                            }
                        } else {
                            $row1["assay_per"] = 0;
                        }
                        $row1["assay_qty"] = (+$row1["qty"] * +$row1["assay_per"]) / 100;*/
                        
                        $output2 = array();
                        $sql2 = "SELECT s.ar_no, s.qty, s.unit FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code WHERE s.material_code='".$row1["material_code"]."' AND s.status='Approved'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["stocks"] = $output2;
                        $row1["required_qty"] = +$row1["qty"];
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        $output1[] = $row1;
                    }
                }
                if ($flag == 0) {
                    $row["dispensing_status"] = "done";
                } else {
                    $row["dispensing_status"] = "pending";
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if($_GET["type"] == "save_rm_line_clearance_activity"){
         
         
        $sql = "insert into lineclearance(prev_product,plant_id,work_order_id,bfr_no,mfr_no,pleasure_diff_reading,laf_eqip_code,balance_eqip_code,
                material_no,batch_no,entry_by,checkpoints,line_clearance_type,line_clearance_remarks,product_code,request_date,activity)values(
                '".json_encode($input["prevdate"])."','".$_GET["plant_id"]."','".$input["work_order_id"]."','".$input["bfr_no"]."' ,'".$input["mfr_no"]."' ,'".$input["pleasure_diff_reading"]."',
                '".$input["laf_eqip_code"]."'  ,'".$input["balance_eqip_code"]."' ,
                '".$input["material_no"]."' ,'".$input["batch_no"]."' ,'".$_GET["emp_id"]."',
                '".json_encode($input["checklist"])."','".$input["line_clearance_type"]."','".$input["line_clearance_remarks"]."','".$_GET["product_code"]."','".$entry_date."','Dispense Request')";
                
          if ($conn->query($sql)) {
              $sql = "update mfg_work_order_hdr set  rm_store_lc_status='Approved', rm_dispensing_lc_by = '".$_GET["emp_id"]."' , rm_dispensing_lc_date='".$entry_date."'
                      where id = '".$input["work_order_id"]."' ";
              $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
     }
     else if($_GET["type"] == "save_pm_line_clearance_activity"){
        $sql = "insert into lineclearance(plant_id,work_order_id,bfr_no,mfr_no,pleasure_diff_reading,laf_eqip_code,balance_eqip_code,
                material_no,batch_no,entry_by,checkpoints,line_clearance_type,line_clearance_remarks)values(
                '".$_GET["plant_id"]."','".$input["work_order_id"]."','".$input["bfr_no"]."' ,'".$input["mfr_no"]."' ,'".$input["pleasure_diff_reading"]."',
                '".$input["laf_eqip_code"]."'  ,'".$input["balance_eqip_code"]."' ,
                '".$input["material_no"]."' ,'".$input["batch_no"]."' ,'".$_GET["emp_id"]."',
                '".json_encode($input["checkpoints"])."','".$input["line_clearance_type"]."','".$input["line_clearance_remarks"]."')";
                
          if ($conn->query($sql)) {
               $sql = "update mfg_work_order_hdr  set  pm_store_lc_status='Approved', pm_dispensing_lc_by = '".$_GET["emp_id"]."' , pm_dispensing_lc_date='".$entry_date."'
                      where id = '".$input["work_order_id"]."' ";
              $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if($_GET["type"] == "startRLAF"){
        $sql = "UPDATE dispensing SET laf_id ='".$input["rlaf_id"]."' , status='RLAF STARTED' , dispensing_date='$entry_date', laf_start_time ='".$input["laf_start_date"]."' ,laf_pressure ='".$input["laf_pressure"]."' WHERE id ='".$_GET["id"]."' ";
          if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }  else if ($_GET["type"] == "getStartedDispensing") {
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.status='RLAF STARTED' GROUP BY d.product_code";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $flag = 0;
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = array();
                        $sql2 = "SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,tare_wt FROM stock_book WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $received_qty = 0;
                                $issued_qty = 0;
                                $balance_qty = 0;
                                
                                $received_qty = $row2["qty"];
                                
                                $sql3 = "SELECT IFNULL(SUM(qty), 0) as qty FROM material_issue WHERE ar_no='".$row2["ar_no"]."'";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        $issued_qty = $row3["qty"];
                                    }
                                }
                                $balance_qty = $received_qty - $issued_qty;
                                $row2["received_qty"] = $received_qty;
                                $row2["issue_qty"] = $issued_qty;
                                $row2["balance_qty"] = $balance_qty;
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                        $output1[] = $row1;
                    }
                }
                if ($flag == 0) {
                    $row["dispensing_status"] = "done";
                } else {
                    $row["dispensing_status"] = "pending";
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "dispensing_save") {
        
      
        $sql = " update batch_planning_materials set disp_id='1' where id='".$_GET["pm_hdr_id"]."'";
        //'".$input["id"]."'";
       if ($conn->query($sql)) {
               $sql = "INSERT INTO dispensing_details_hdr(plant_id, material_code, batch_no, containers, ars, gross_total, net_total, tare_total, 
        unit, dispensing_room, done_by, entry_by, lot_id,pressure_reading,rlaf_start,qa_checking,prod_checking,pm_hdr_id) values(
        '".$_GET["plant_id"]."','".$input["material_code"]."','".$input["batch_no"]."' ,'".json_encode($input["containers"])."' ,'".json_encode($input["available_ars"])."' ,
        '".$input["gross_total"]."' ,'".$input["net_total"]."' ,'".$input["tare_total"]."','".$input["unit"]."','".$input["dispensing_room"]."',
        '".$input["done_by"]."' ,'".$_GET["emp_id"]."','".$input["lot_id"]."' ,'".$input["pressure_reading"]."' ,'".$input["rlaf_start"]."',
        '".$input["qa_checking"]."' ,'".$input["prod_checking"]."','".$input["pm_hdr_id"]."')";
              $conn->query($sql);
               $sql = "update work_order_batch_lots set status='dispensed' WHERE id='".$input["lot_id"]."'";
            $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "dispensing_save_saipro") {
        
        //packing
        
           $sql = "INSERT INTO dispensing_details_hdr(plant_id, material_code, batch_no, containers, ars, gross_total, net_total, tare_total, 
        unit, dispensing_room, done_by, entry_by, lot_id,pressure_reading,rlaf_start,qa_checking,prod_checking,pm_hdr_id) values(
        '".$_GET["plant_id"]."','".$input["material_code"]."','".$input["batch_no"]."' ,'".json_encode($input["containers"])."' ,'".json_encode($input["available_ars"])."' ,
        '".$input["gross_total"]."' ,'".$input["net_total"]."' ,'".$input["tare_total"]."','".$input["unit"]."','".$input["dispensing_room"]."',
        '".$input["done_by"]."' ,'".$_GET["emp_id"]."','".$input["lot_id"]."' ,'".$input["pressure_reading"]."' ,'".$input["rlaf_start"]."',
        '".$input["qa_checking"]."' ,'".$input["prod_checking"]."' ,'".$input["pm_hdr_id"]."')";
       
        if ($conn->query($sql)) {
             $ars = $input["available_ars"];
            for ($i = 0; $i < count($ars); $i++) {
                $ar = $ars[$i];
                $sql1 = "INSERT INTO material_issue (plant_id,work_order_id,ar_no,grn_no, material_code, batch_no, product_code, prod_batch_code, issue_for, qty, unit, entry_by, entry_date,lot)
                VALUES ( '".$_GET["plant_id"]."', '".$input["work_order_id"]."','".$ar["ar_no"]."', '".$ar["grn_no"]."',
                '".$input["material_code"]."', '".$ar["batch_no"]."', '".$input["product_code"]."', '".$input["prod_batch_code"]."', 
                'DISPENSING', '".$ar["despensedQty"]."', '".$input["unit"]."', '".$_GET["emp_id"]."', '$entry_date', '".$input["lot"]."')";
                //echo $sql1;
                $conn->query($sql1);
            }
            
            $sql = "update mfg_work_order_dtl_pm_lots set despensing_status='dispensed' WHERE id='".$_GET["upid"]."'";
            $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
      
    } 
    
        
    
    else if ($_GET["type"] == "saveDispensingForm_old") {
      
        $sql = "UPDATE dispensing_materials SET status='done', containers='".json_encode($input['containers'])."', ars='".json_encode($input["ars"])."',
        operator_name ='".$input["operator_name"]."', done_by='".$input["done_by"]."', check_by='".$_GET["emp_id"]."', 
        entry_date='$entry_date', gross_wt='".$input["gross_total"]."', tare_wt='".$input["tare_total"]."',
         container_number='".$input["container_number"]."', descripancy_observed='".$input["descripancy_observed"]."', descripancy_remarks='".$input["descripancy_remarks"]."',
        net_wt='".$input["net_total"]."' WHERE id=0";//'".$input["id"]."'";
        if ($conn->query($sql)) {
             $ars = $input["ars"];
            for ($i = 0; $i < count($ars); $i++) {
                $ar = $ars[$i];
                $sql1 = "INSERT INTO material_issue (ar_no, material_code, batch_no, product_code, prod_batch_code, issue_for, qty, unit, entry_by, entry_date) VALUES ('".$ar["ar_no"]."', '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["product_code"]."', '".$input["prod_batch_code"]."', 'DISPENSING', '".$ar["qty"]."', '".$input["unit"]."', '".$_GET["emp_id"]."', '$entry_date')";
                $conn->query($sql1);
            }
            
            $sql = "SELECT id FROM dispensing_materials WHERE status='pending' AND dispensing_no='".$_GET["dispensing_no"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows == 0) {
                $sql = "UPDATE dispensing SET status='Active' WHERE id='".$_GET["dispensing_no"]."'";
                $conn->query($sql);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    // else if ($_GET["type"] == "saveDispensingForm") {
  
  
    // $sql = "INSERT INTO dispensing_details_hdr(plant_id, material_code, batch_no, containers, ars, gross_total, net_total, tare_total, 
    //     unit, dispensing_room, done_by, entry_by, lot_id,pressure_reading,rlaf_start,qa_checking,prod_checking) values(
    //     '".$_GET["plant_id"]."','".$input["material_code"]."','".$input["batch_no"]."' ,'".json_encode($input["containers"])."' ,'".json_encode($input["available_ars"])."' ,
    //     '".$input["gross_total"]."' ,'".$input["net_total"]."' ,'".$input["tare_total"]."','".$input["unit"]."','".$input["dispensing_room"]."',
    //     '".$input["done_by"]."' ,'".$_GET["emp_id"]."','".$input["lot_id"]."' ,'".$input["pressure_reading"]."' ,'".$input["rlaf_start"]."',
    //     '".$input["qa_checking"]."' ,'Yes')";
     
    //     if ($conn->query($sql)) {
    //          $ars = $input["containers"];
    //         for ($i = 0; $i < count($ars); $i++) {
    //             $ar = $ars[$i];
    //             $sql1 = "INSERT INTO material_issue (plant_id,work_order_id,ar_no,grn_no, material_code, batch_no, product_code, prod_batch_code, issue_for, qty, unit, entry_by, entry_date,lot)
    //             VALUES ( '".$_GET["plant_id"]."', '".$input["work_order_id"]."','".$ar["ar_no"]."', '".$ar["grn_no"]."',
    //             '".$input["material_code"]."', '".$ar["batch_no"]."', '".$input["product_code"]."', '".$input["prod_batch_code"]."', 
    //             'DISPENSING', '".$ar["net_weight"]."', '".$input["unit"]."', '".$_GET["emp_id"]."', '$entry_date', '".$input["lot_no"]."')";
    //             //echo $sql1;
    //             $conn->query($sql1);
    //         }
            
    //         $sql = "update work_order_batch_lots set status='dispensed' WHERE id='".$input["lot_id"]."'";
    //         $conn->query($sql);
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    else if ($_GET["type"] == "saveDispensingForm") {
  
  
    $sql = "INSERT INTO dispensing_details_hdr(plant_id, material_code, batch_no, containers, ars, gross_total, net_total, tare_total, 
        unit, dispensing_room, done_by, entry_by, lot_id,pressure_reading,rlaf_start,qa_checking,prod_checking,dtl_id) values(
        '".$_GET["plant_id"]."','".$input["material_code"]."','".$input["batch_no"]."' ,'".json_encode($input["containers"])."' ,'".json_encode($input["available_ars"])."' ,
        '".$input["gross_total"]."' ,'".$input["net_total"]."' ,'".$input["tare_total"]."','".$input["unit"]."','".$input["dispensing_room"]."',
        '".$input["done_by"]."' ,'".$_GET["emp_id"]."','".$input["lot_id"]."' ,'".$input["pressure_reading"]."' ,'".$input["rlaf_start"]."',
        '".$input["qa_checking"]."' ,'".$input["prod_checking"]."','".$input["lot_id"]."' )";
     
        if ($conn->query($sql)) {
             $ars = $input["containers"];
            for ($i = 0; $i < count($ars); $i++) {
                $ar = $ars[$i];
                $sql1 = "INSERT INTO material_issue (plant_id,work_order_id,ar_no,grn_no, material_code, batch_no, product_code, prod_batch_code, issue_for, qty, unit, entry_by, entry_date,lot)
                VALUES ( '".$_GET["plant_id"]."', '".$input["work_order_id"]."','".$ar["ar_no"]."', '".$ar["grn_no"]."',
                '".$input["material_code"]."', '".$ar["batch_no"]."', '".$input["product_code"]."', '".$input["prod_batch_code"]."', 
                'DISPENSING', '".$ar["net_weight"]."', '".$input["unit"]."', '".$_GET["emp_id"]."', '$entry_date', '".$input["lot_no"]."')";
                $conn->query($sql1);
            }
            
            $check_sql = "SELECT id FROM work_order_batch_lots WHERE work_order_id='".$input["work_order_id"]."' AND lot_no='".$input["lot_no"]."' AND material_code='".$input["material_code"]."' AND plant_id='".$_GET["plant_id"]."'";
            $check_result = $conn->query($check_sql);
            
            $batch_qty = isset($input["batch_qty"]) ? $input["batch_qty"] : $input["net_total"];
            $lot_type = isset($input["lot_type"]) ? $input["lot_type"] : (isset($input["material_subtype"]) ? $input["material_subtype"] : 'Raw Material');
            
            if ($check_result && $check_result->num_rows > 0) {
                $row = $check_result->fetch_assoc();
                $sql = "UPDATE work_order_batch_lots SET status='dispensed', dispense_recd_status='Received', batch_qty='".$batch_qty."', entry_by='".$_GET["emp_id"]."', checked_by='".$input["done_by"]."', checked_date='$entry_date' WHERE id='".$row["id"]."'";
            } else {
                $sql = "INSERT INTO work_order_batch_lots(plant_id, work_order_id, lot_no, lot_type, material_code, batch_qty, status, dispense_recd_status, entry_by, checked_by, checked_date) 
                VALUES('".$_GET["plant_id"]."', '".$input["work_order_id"]."', '".$input["lot_no"]."', '".$lot_type."', '".$input["material_code"]."', '".$batch_qty."', 'dispensed', 'Received', '".$_GET["emp_id"]."', '".$input["done_by"]."', '$entry_date')";
            }
            $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveDispensingForm_saipro") {
       //  echo json_encode($input);
       
       
    //   $sql=" update dispensing_details_hdr set dispence_id='1',ars='".json_encode($input["available_ars"])."',gross_total='".$input["gross_total"]."',net_total=''".$input["net_total"]."'' where id=''";
     if($_GET["plant_id"]=='67'){
         
        $sql = "INSERT INTO dispensing_details_hdr(plant_id, material_code, batch_no, containers, ars, gross_total, net_total, tare_total, 
        unit, dispensing_room, done_by, entry_by, lot_id,pressure_reading,rlaf_start,qa_checking,prod_checking) values(
        '".$_GET["plant_id"]."','".$input["material_code"]."','".$input["batch_no"]."' ,'".json_encode($input["containers"])."' ,'".json_encode($input["available_ars"])."' ,
        '".$input["gross_total"]."' ,'".$input["net_total"]."' ,'".$input["tare_total"]."','".$input["unit"]."','".$input["dispensing_room"]."',
        '".$input["done_by"]."' ,'".$_GET["emp_id"]."','".$input["lot_id"]."' ,'".$input["pressure_reading"]."' ,'".$input["rlaf_start"]."',
        'Yes' ,'Yes')";
     }
     else{
        $sql = "INSERT INTO dispensing_details_hdr(plant_id, material_code, batch_no, containers, ars, gross_total, net_total, tare_total, 
        unit, dispensing_room, done_by, entry_by, lot_id,pressure_reading,rlaf_start,qa_checking,prod_checking) values(
        '".$_GET["plant_id"]."','".$input["material_code"]."','".$input["batch_no"]."' ,'".json_encode($input["containers"])."' ,'".json_encode($input["available_ars"])."' ,
        '".$input["gross_total"]."' ,'".$input["net_total"]."' ,'".$input["tare_total"]."','".$input["unit"]."','".$input["dispensing_room"]."',
        '".$input["done_by"]."' ,'".$_GET["emp_id"]."','".$input["lot_id"]."' ,'".$input["pressure_reading"]."' ,'".$input["rlaf_start"]."',
        '".$input["qa_checking"]."' ,'".$input["prod_checking"]."')";
         
     }
       
        if ($conn->query($sql)) {
             $ars = $input["containers"];
            for ($i = 0; $i < count($ars); $i++) {
                $ar = $ars[$i];
                $sql1 = "INSERT INTO material_issue (plant_id,work_order_id,ar_no,grn_no, material_code, batch_no, product_code, prod_batch_code, issue_for, qty, unit, entry_by, entry_date)
                VALUES ( '".$_GET["plant_id"]."', '".$input["work_order_id"]."','".$ar["ar_no"]."', '".$ar["grn_no"]."',
                '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["product_code"]."', '".$input["prod_batch_code"]."', 
                'DISPENSING', '".$ar["net_weight"]."', '".$input["unit"]."', '".$_GET["emp_id"]."', '$entry_date')";
                //echo $sql1;
                $conn->query($sql1);
            }
            
            $sql = "update mfg_work_order_dtl set despensing_status='dispensed' WHERE id='".$input["lot_id"]."'";
            $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveDispensingActivity") {
        $sql = "UPDATE dispensing SET balance_id='".$input["balance_id"]."', laf_id='".$input["laf_id"]."',laf_start_time='".$input["laf_start_time"]."',laf_stop_time='".$input["laf_stop_time"]."', pressure='".$input["pressure"]."', complete_date='$entry_date', status='complete' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "getActiveDispensing") {
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date,p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.status='Accept' GROUP BY d.product_code";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $flag = 0;
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    // else if ($_GET["type"] == "getDispensingLog") {
    //     $output = array();
    //     $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN
    //     product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='complete' AND p.product_type
    //     LIKE '%".$_GET["product_type"]."%' AND DATE(d.request_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  
    //     GROUP BY d.product_code";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $output1 = array();
    //             $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                     $row1["containers"] = json_decode($row1["containers"]);
    //                     $row1["ars"] = json_decode($row1["ars"]);
    //                     $output1[] = $row1;
    //                 }
    //             }
    //             $row["materials"] = $output1;
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }
    
    else if ($_GET["type"] == "downloadDispensingLog") {
        $_GET['filename'] = 'Dispensing Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Dispensing Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 4%; text-align:center;">Sr.</td>
                    <td style="width: 12%; text-align:center;">Date</td>
                    <td style="width: 12%; text-align:center;">Receiving no</td>
                    <td style="width: 12%; text-align:center;">Company Unit</td>
                    <td style="width: 10%; text-align:center;">Product Name</td>
                    <td style="width: 10%; text-align:center;">Product Type</td>
                    <td style="width: 10%; text-align:center;">Product Code</td>
                    <td style="width: 10%; text-align:center;">Batch Size</td>
                    <td style="width: 10%; text-align:center;">Batch No.</td>
                    <td style="width: 10%; text-align:center;">Request By	</td>
                </tr>
            </thead>';
            $output = array();
                $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.dosage_form, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='complete'  ORDER BY id DESC";
                $result = $conn->query($sql);
                $i=1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                    
                $html.='<tr nobr="true">
                        <td style="width: 4%; text-align:center;">'.$i.'.</td>
                        <td style="width: 12%; text-align:center;">'.date('d-m-Y',strtotime($row[''])).'</td>
                        <td style="width: 12%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 12%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 10%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 10%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 10%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 10%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 10%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 10%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 10%; text-align:center;">'.$row[''].'</td>
                        
                       
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Dispensing.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadDispensingRecord") {
        $_GET['filename'] = 'Dispensing Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.dosage_form, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='complete'  ORDER BY id DESC";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
         while ($row = $result->fetch_assoc()) {
            
         $html.='<h2 style=tex-align:center>Product Details:</h2>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:25%;">Product Code</td>
                        <td style="width:25%;">'.$row['product_code'].'</td>
                        <td style="width:25%;">Product Name</td>
                        <td style="width:25%;">'.$row['product_name'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;">Product Type</td>
                        <td style="width:25%;">'.$row['dosage_form'].'</td>
                        <td style="width:25%;">Grade</td>
                        <td style="width:25%;">'.$row['grade'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;">Grade</td>
                        <td style="width:25%;">'.$row['grade'].'</td>
                        <td style="width:25%;">Request Date</td>
                        <td style="width:25%;">'.$row['request_date'].'</td>
                    </tr>';
        $html.="</table>";
        
        $html.='<h2>Material Details</h2>
                <table border="1" cellpadding="5">
                    <thead>
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width: 5%; text-align:center;">Sr.</td>
                            <td style="width: 10%; text-align:center;">Dispensing No</td>
                            <td style="width: 10%; text-align:center;">Material Subtype</td>
                            <td style="width: 10%; text-align:center;">Material Code</td>
                            <td style="width: 10%; text-align:center;">Material Name</td>
                            <td style="width: 10%; text-align:center;">Grade</td>
                            <td style="width: 10%; text-align:center;">Qty</td>
                            <td style="width: 10%; text-align:center;">Overages</td>
                            <td style="width: 10%; text-align:center;">Role</td>
                            <td style="width: 5%; text-align:center;">Gross Wt</td>
                            <td style="width: 5%; text-align:center;">Tare Wt</td>
                            <td style="width: 5%; text-align:center;">Net Wt</td>
                        </tr>
                    </thead>';
                    $output1 = array();
                    $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                         
                        
                    $html.='<tr nobr="true">
                                <td style="width: 5%; text-align:center;">'.$i.'.</td>
                                <td style="width: 10%; text-align:center;">'.$row1['dispensing_no'].'</td>
                                <td style="width: 10%; text-align:center;">'.$row1['material_subtype'].'</td>
                                <td style="width: 10%; text-align:center;">'.$row1['material_code'].'</td>
                                <td style="width: 10%; text-align:center;">'.$row1['material_name'].'</td>
                                <td style="width: 10%; text-align:center;">'.$row1['grade'].'</td>
                                <td style="width: 10%; text-align:center;">'.$row1['qty'].'</td>
                                <td style="width: 10%; text-align:center;">'.$row1['overages'].'</td>
                                <td style="width: 10%; text-align:center;">'.$row1['role'].'</td>
                                <td style="width: 5%; text-align:center;">'.$row1['gross_wt'].'</td>
                                <td style="width: 5%; text-align:center;">'.$row1['tare_wt'].'</td>
                                <td style="width: 5%; text-align:center;">'.$row1['net_wt'].'</td>
                            </tr>';
                            $i++;
                        }
                    }
            $html.="</table><br><br>";
            
            $html.='<table border="1" cellpadding="5">
                    <tr>
                        <td style="width:25%;">Balance ID</td>
                        <td style="width:25%;">'.$row['balance_id'].'</td>
                        <td style="width:25%;">LAF ID</td>
                        <td style="width:25%;">'.$row['laf_id'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;">LAF Start Time</td>
                        <td style="width:25%;">'.$row['laf_start_time'].'</td>
                        <td style="width:25%;">LAF Stop Time</td>
                        <td style="width:25%;">'.$row['laf_stop_time'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;">Pressure Differential Reading</td>
                        <td style="width:75%;">'.$row['pressure'].'</td>
                    </tr>
                        ';
            $html.="</table>";
    
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Dispensing Record.pdf', 'I');
            
           }
        }
    }
    else if ($_GET["type"] == "downloadDispensingReport") {
        
     if($_GET["plant_id"] == 59){ // Amardeep
                
           $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='';
              
           
    	    
    	    $html.='';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }else if($_GET["plant_id"] == 64){//Novo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='<table border="1">
   

    <tr>
    <td style="width: 100px;"> Format Title :</td>
    <td style="width: 440px;">  Sampling & Dispensing Both Record </td>
    </tr>
   
    <tr>
    <td style="width: 100px;"> Format No.:</td>
    <td style="width: 170px;"> F/SOP/WR/004/00-01</td>
    <td style="width: 100px;"> Page No.:</td>
    <td style="width: 170px;"></td>
   
    </tr>
    <tr>
    <td style="width: 100px;"> Ref. SOP No.:</td>
    <td style="width: 440px;">SOP/WR/004</td>
    </tr>
</table><div></div>

   <table>
   <tr>
    <td style="width: 300px;"></td>
    <td style="width: 240px;"> Booth  ID No.</td>
   </tr>
   </table>
    <table border="1">
        <tr>
        <td style="width: 20px;px; text-align:center;font-size:8px" rowspan="2">Sr. No </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Date </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">RLAF Start time </td>
        <td style="width:72px; text-align:center;font-size:8px" rowspan="2">Magnehelic Gauges Reading for RLAF       Limit  6 to 16 mm </td>
        <td style="width:46px; text-align:center;font-size:8px" rowspan="2">Product Name </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2"> Batch No
         </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Activity </td>
        <td style="width:62px; text-align:center;font-size:8px" colspan="2">Sampling/ Dispensing Time</td>

        <td style="width:31px; text-align:center;font-size:8px" rowspan="2">Done By </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Checked By </td>
        <td style="width:57px; text-align:center;font-size:8px" colspan="2">Cleaning Time </td>

        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Done By </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Checked By </td>
        </tr>
        <tr>
        <td style="width: 31px; text-align:center;font-size:8px">From </td>
        <td style="width:31px; text-align:center;font-size:8px">To </td>
        <td style="width: 31px; text-align:center;font-size:8px">From </td>
        <td style="width: 26px; text-align:center;font-size:8px">To </td>
        </tr>
        </table>
        ';
              
           $sql = "SELECT d.*, e.firstname,DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.dispensing_for='PACKING' AND d.status='Active' AND DATE(d.request_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    // $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <table border="1">
                        <tr>
        <td style="width: 20px;"> </td>
        <td style="width:36px"> </td>
        <td style="width:36px"> </td>
        <td style="width: 72px;"> </td>
        <td style="width:46px"> </td>
        <td style="width:36px"> </td>
        <td style="width:36px"> </td>
        <td style="width:31px"> </td>
        <td style="width:31px"> </td>
        <td style="width:31px"> </td>
        <td style="width:36px"> </td>
        <td style="width:31px"> </td>
        <td style="width:26px"> </td>
        <td style="width:36px"> </td>
        <td style="width:36px"> </td>
        </tr>
    </table>
       
                    ';
    		    }
    	        
    	    }
    		    $html.=' <div></div>
        
        


<table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY : </td>
    <td style="text-align:center;width: 146.6px;">Approved By:  </td>
    <td style="text-align:center;width: 146.6px;">Authorized By: </td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Signature</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>

</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }
            else if($_GET["plant_id"] == 28){//DEMO
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='<table border="1">
   

    <tr>
    <td style="width: 100px;"> Format Title :</td>
    <td style="width: 440px;">  Sampling & Dispensing Both Record </td>
    </tr>
   
    <tr>
    <td style="width: 100px;"> Format No.:</td>
    <td style="width: 170px;"> F/SOP/WR/004/00-01</td>
    <td style="width: 100px;"> Page No.:</td>
    <td style="width: 170px;"></td>
   
    </tr>
    <tr>
    <td style="width: 100px;"> Ref. SOP No.:</td>
    <td style="width: 440px;">SOP/WR/004</td>
    </tr>
</table><div></div>

   <table>
   <tr>
    <td style="width: 300px;"></td>
    <td style="width: 240px;"> Booth  ID No.</td>
   </tr>
   </table>
    <table border="1">
        <tr>
        <td style="width: 20px;px; text-align:center;font-size:8px" rowspan="2">Sr. No </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Date </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">RLAF Start time </td>
        <td style="width:72px; text-align:center;font-size:8px" rowspan="2">Magnehelic Gauges Reading for RLAF       Limit  6 to 16 mm </td>
        <td style="width:46px; text-align:center;font-size:8px" rowspan="2">Product Name </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2"> Batch No
         </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Activity </td>
        <td style="width:62px; text-align:center;font-size:8px" colspan="2">Sampling/ Dispensing Time</td>

        <td style="width:31px; text-align:center;font-size:8px" rowspan="2">Done By </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Checked By </td>
        <td style="width:57px; text-align:center;font-size:8px" colspan="2">Cleaning Time </td>

        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Done By </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Checked By </td>
        </tr>
        <tr>
        <td style="width: 31px; text-align:center;font-size:8px">From </td>
        <td style="width:31px; text-align:center;font-size:8px">To </td>
        <td style="width: 31px; text-align:center;font-size:8px">From </td>
        <td style="width: 26px; text-align:center;font-size:8px">To </td>
        </tr>
        </table>
        ';
              
           $sql = "SELECT d.*, e.firstname,DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.dispensing_for='PACKING' AND d.status='Active' AND DATE(d.request_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    // $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <table border="1">
                        <tr>
        <td style="width: 20px;"> </td>
        <td style="width:36px"> </td>
        <td style="width:36px"> </td>
        <td style="width: 72px;"> </td>
        <td style="width:46px"> </td>
        <td style="width:36px"> </td>
        <td style="width:36px"> </td>
        <td style="width:31px"> </td>
        <td style="width:31px"> </td>
        <td style="width:31px"> </td>
        <td style="width:36px"> </td>
        <td style="width:31px"> </td>
        <td style="width:26px"> </td>
        <td style="width:36px"> </td>
        <td style="width:36px"> </td>
        </tr>
    </table>
       
                    ';
    		    }
    	        
    	    }
    		    $html.=' <div></div>
        
        


<table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY : <br>(Warehouse)</td>
    <td style="text-align:center;width: 146.6px;">Approved By:  <br>(Quality Assurance Head)</td>
    <td style="text-align:center;width: 146.6px;">Authorized By: <br>(Plant Head)</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Signature</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>

</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }
}
    else if ($_GET["type"] == "printLabel") {
         class MYPDF extends TCPDF {
                    public function Header() {}
                    public function Footer() {}
                }
                  $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetMargins(10, 10, 10, 10);
                $pdf->SetAutoPageBreak(TRUE, 10);
                $pdf->AddPage('P', 'A4');
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                
    
                $ar_no = '';
                $sql2 = "SELECT containers FROM dispensing_details_hdr a left join material b on a.material_code=b.material_code WHERE a.id = '".$_GET["disp_id"]."'";
$result2 = $conn->query($sql2);

if ($result2->num_rows > 0) {
    $row = $result2->fetch_assoc(); // Fetch the first row

    // Assuming 'containers' is a JSON array in the database
    $containers = json_decode($row['containers'], true);
    $ar_no = isset($containers[0]['ar_no']) ? $containers[0]['ar_no'] : 'N/A';
    $net_weight = isset($containers[0]['net_weight']) ? $containers[0]['net_weight'] : 'N/A';

    $uom = $row['uom'];

                
                        $html.='
                                                        
                                  

                    <table style="border: 1px solid black; background-color: white;">
                        <tr>
                            <td style="width: 300px;  height: 30px;  font-weight: bold;font-weight: bold;font-size: 20px;  text-align: center;"><br>Dispensing Label</td>
                        </tr>
                    </table>
                        <table  style="border: 1px solid black; background-color: white;">
                            <tr>
                                <td style="width:150px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Material Name:'.$_GET["material_name"].' </td>
                                <td style="width:150px; height: 20px; font-size: 10px;">Material Grade: '.$_GET["material_grade"].' </td>
                            </tr>
                              <tr>
                                <td style="width:150px; height: 20px; font-size: 10px; border-right: 1px solid black;">A.R.No: ' . $ar_no . ' </td>
                                <td style="width:150px; height: 20px; font-size: 10px;"> Qty:' . $net_weight . ' </td>
                            </tr>
                            <tr>
                                <td style="width:150px; height: 20px; font-size: 10px; border-right: 1px solid black;">For Product:'.$_GET["product_name"].'</td>
                                <td style="width:150px; height: 20px; font-size: 10px;"> Batch No :'.$_GET["batch_number"].' </td>
                            </tr>
                            <tr>
                                <td style="width:300px; height: 20px; font-size: 10px;text-align:center;">Weighing Details</td>
                            </tr>
                        </table>
                        <table  style="border: 1px solid black; background-color: white;">
                            <tr>
                                <td style="width:100px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Gross Wt: </td>
                                <td style="width:100px; height: 20px; font-size: 10px; border-right: 1px solid black;">Tare Wt:</td>
                                <td style="width:100px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Net Wt: </td>
                            </tr>
                          
                       
                        
                            ';
                           
}
  
   $sql2 = "SELECT containers FROM dispensing_details_hdr a left join material b on a.material_code=b.material_code WHERE a.id = '".$_GET["disp_id"]."'";
$result2 = $conn->query($sql2);

if ($result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        // Assuming 'containers' is a JSON array in the database
        $containers = json_decode($row['containers'], true);
          $ar_no = ''; // Initialize $ar_no
        $uom = $row['uom'];

        if (is_array($containers)) {
            foreach ($containers as $container) {
                $tare_wt = isset($container['tare_wt']) ? $container['tare_wt'] : 'N/A';
                $gross_wt = isset($container['gross_wt']) ? $container['gross_wt'] : 'N/A';
                $balance_id = isset($container['net_weight']) ? $container['net_weight'] : 'N/A';
                $ar_no = isset($container['ar_no']) ? $container['ar_no'] : 'N/A';

                $html .= '
                    <tr>
                        <td style="width:100px; height: 20px; font-size: 10px; border-right: 1px solid black;">'.$gross_wt.' '.$uom.'</td>
                        <td style="width:100px; height: 20px; font-size: 10px; border-right: 1px solid black;">'.$tare_wt.'</td>
                        <td style="width:100px; height: 20px; font-size: 10px; border-right: 1px solid black;">'.$balance_id.'</td>
                    </tr>';
           
                // $output2 = $row2;
            
            }
        }

    }
}
   $html.='</table>
    <table  style="border: 1px solid black; background-color: white;">
                            <tr>
                                <td style="width:100px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Weighed By </td>
                                <td style="width:100px; height: 20px; font-size: 10px;border-right: 1px solid black;">Checked By </td>
                                <td style="width:100px; height: 20px; font-size: 10px;border-right: 1px solid black;">Verified By </td>
                            </tr>';
                    
$sql2 = "SELECT done_by FROM dispensing_details_hdr WHERE id = '".$_GET["disp_id"]."'";
$result2 = $conn->query($sql2);

if ($result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        $done_by = $row['done_by'];
        $qa_status = $_GET["qa_status"];
        $prod_status = $_GET["prod_status"];

        $html .= '<tr>
            <td style="width:100px; height: 20px; font-size: 10px; border-right: 1px solid black;">'.$done_by.'</td>
            <td style="width:100px; height: 20px; font-size: 10px;border-right: 1px solid black;">'.$done_by.'</td>
            <td style="width:100px; height: 20px; font-size: 10px;border-right: 1px solid black;">'.$done_by.'</td>
        </tr>';
    }
}
                         
                           $html.=' 
                            
                           
                        </table>
                       
                        
                     
    <div></div>
                       
                                 ';
                              
                           $html.=' ';      
                              
                              
                              
                              
                              
                              
                                 $html .= $html;
                                 
                                 
                
                
                  $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('labels.pdf', 'I');
        
    }


}

$conn->close();

function isWeekend($date) {
    $weekDay = date('w', strtotime($date));
    return ($weekDay == 0);
}
}
catch(Exception $e) {
  echo 'Message: ' .$e->getMessage();
}
?>