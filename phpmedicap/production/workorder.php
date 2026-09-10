<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);


require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
// header("Access-Control-Allow-Origin: https://www.gmpsoftwareindia.com");
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
//     ini_set('display_errors', 1);
// error_reporting(E_ALL);


    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
  
  
    if ($_GET["type"] == "save_work_order") {
         
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
        
       
        
        $sql = "INSERT INTO mfg_work_order_hdr (plant_id,batch_plan_id,batch_id,work_order_no,lod_status,assay_status,
        batch_overages,overages_percent,ebmr_status,calculation_type,no_of_lots,entry_by,material_type)
        values('".$_GET["plant_id"]."','".$input["batch_plan_id"]."','".$input["selected_batch_index"]."','".$work_order_no."','".$input["lod_criteria"]."',
               '".$input["assay_criteria"]."','".$input["batch_overages"]."','".$input["overages_percent"]."',
               '".$input["ebmr_status"]."','".$input["calculation_type"]."','".$input["no_of_lots"]."','".$_GET["emp_id"]."',
               '".$input["material_type"]."')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $r_materials = $input["raw_materials"];
            for ($i = 0; $i <count($r_materials); $i++) {
                $material = $r_materials[$i];
                $batch_qty = $material['batch_qty'];
                $formatted_qty = number_format((float)$batch_qty, 3, '.', '');
                
                $sql = "INSERT INTO  mfg_work_order_dtl(work_order_id,material_code,grade,unit_qty,unit,overages,total_unit_qty,batch_qty,
                batch_overages,total_batch_qty,lod_status,assay_status,stage)
                values('".$last_id."','".$material["material_code"]."','".$material["grade"]."',
                 '".$material["qty"]."','".$material["unit"]."', '".$material["overages"]."','".$material["total_qty"]."',
                 '".$formatted_qty."','".$material["batch_overages"]."',
                '".$material["total_final_qty"]."','".$material["lod_status"]."','".$material["assay_status"]."','".$material["stage"]."')";
                $conn->query($sql);
                
            }
            $p_materials = $input["packing_materials"];
            for ($i = 0; $i <count($p_materials); $i++) { 
                  $material = $p_materials[$i];
                  
                $batch_qty = $material['batch_qty'];
                $formatted_qty = number_format((float)$batch_qty, 3, '.', '');
                  
                  
                $sql = "INSERT INTO  mfg_work_order_dtl(work_order_id,material_code,grade,unit_qty,unit,overages,total_unit_qty,batch_qty,
                batch_overages,total_batch_qty,lod_status,assay_status)
                values('".$last_id."','".$material["material_code"]."','".$material["grade"]."',
                 '".$material["qty"]."','".$material["unit"]."', '".$material["overages"]."','".$material["total_qty"]."',
                 '".$formatted_qty."','".$material["batch_overages"]."',
                '".$material["total_final_qty"]."','".$material["lod_status"]."','".$material["assay_status"]."')";
                $conn->query($sql);
                 
            }
            $lots =  $input["lots"];
            
            for ($k = 0; $k <count($lots); $k++) { 
               // $sql ="SELECT concat('L',LPAD((select count(distinct(lot_no)) as lot_no from work_order_batch_lots) , 4, 0)) as lot_no";
                $sql ="select count(distinct(lot_no))+1 as lot_no from work_order_batch_lots where lot_type='Lot Materials' and plant_id = '".$_GET["plant_id"]."' ";
                $res = $conn->query($sql);
                $rowData = $res->fetch_assoc();
                $lot_no = $rowData['lot_no'];
                  $materials = $lots[$k]['lots']; 
                  for ($j = 0; $j <count($materials); $j++) { 
                      $material = $materials[$j]; 
                    $sql = "INSERT INTO  work_order_batch_lots(lot_type,plant_id, work_order_id, lot_no, material_code, batch_qty, entry_by)
                    values('Lot Materials','".$_GET["plant_id"]."','".$last_id."','".$lot_no."','".$material["material_code"]."',
                     '".$material["each_lot_qty"]."','".$_GET["emp_id"]."')";
                    $conn->query($sql);
                 
                } 
                   
            }
           
            $lots =  $input["coated_lots"];
            for ($k = 0; $k <count($lots); $k++) { 
                //$sql ="SELECT concat('L',LPAD((select count(distinct(lot_no)) as lot_no from work_order_batch_lots) , 4, 0)) as lot_no";
                $sql ="select count(distinct(lot_no))+1 as lot_no from work_order_batch_lots where lot_type='Coating Materials' and plant_id = '".$_GET["plant_id"]."' ";
                $res = $conn->query($sql);
                $rowData = $res->fetch_assoc();
                $lot_no = $rowData['lot_no'];
                  $materials = $lots[$k]['lots']; 
                  for ($j = 0; $j <count($materials); $j++) { 
                      $material = $materials[$j]; 
                    $sql = "INSERT INTO  work_order_batch_lots(lot_type,plant_id, work_order_id, lot_no, material_code, batch_qty, entry_by)
                    values('Coating Materials','".$_GET["plant_id"]."','".$last_id."','".$lot_no."','".$material["material_code"]."',
                     '".$material["each_lot_qty"]."','".$_GET["emp_id"]."')";
                     
                    $conn->query($sql);
                 
                } 
                   
            }
            
            $lots =  $input["common_materails"]; 
            
            for ($k = 0; $k <count($lots); $k++) { 
                $lot_no = '';
                  $material = $lots[$k];  
                     
                    $sql = "INSERT INTO  work_order_batch_lots(lot_type,plant_id, work_order_id, lot_no, material_code, batch_qty, entry_by)
                    values('Common Materials','".$_GET["plant_id"]."','".$last_id."','".$lot_no."','".$material["material_code"]."',
                     '".$material["each_lot_qty"]."','".$_GET["emp_id"]."')";
                       
                    $conn->query($sql);
                 
                 
                   
            } 
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }   
    else if ($_GET["type"] == "get_workorders_for_approval") {
       $sql="SELECT a.*, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,
        b.pack_size,b.pack_unit FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = b.id and
        a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and b.plant_id = p.plant_id
        where a.plant_id = '".$_GET["plant_id"]."' and a.status='pending' order by a.id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
                 $sql2="select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
                 FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id left join material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id where a.work_order_id = '".$row["id"]."') as a left join
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
                 
                 
                $output4 = Array(); 
                 $sql2="select distinct lot_type from work_order_batch_lots where work_order_id ='".$row["id"]."' and plant_id= '".$_GET["plant_id"]."'";
                 $result4 = $conn->query($sql2);
                 if ($result4->num_rows > 0) {
                    while ($row4 = $result4->fetch_assoc()) {
                        $output3 = Array();
                        $sql2="select lot_no from work_order_batch_lots where work_order_id ='".$row["id"]."' and lot_type = '".$row4["lot_type"]."'  and plant_id= '".$_GET["plant_id"]."' group by lot_no";
                     
                        $result1 = $conn->query($sql2);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2="select a.id,a.material_code,b.material_subtype,b.material_name,a.batch_qty from work_order_batch_lots a 
                                left join material b on a.material_code=b.material_code and a.plant_id = b.plant_id
                                where a.work_order_id = '".$row["id"]."' and a.lot_no ='".$row1["lot_no"]."' and a.plant_id= '".$_GET["plant_id"]."'  and lot_type = '".$row4["lot_type"]."' ";
                                   
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    $output2 = Array();
                                    while ($row2 = $result2->fetch_assoc()) {
                                            $output2[]=$row2;   
                                    }
                                   // $lot_info["lot_no"] = $row1["lot_no"];
                                    //$lot_info["lots_data"] = $output2;
                                      $row1['lots_list'] = $output2;
                                }
                                $output3[]=$row1;
                                
                               
                                
                            }
                            $row4["lots"] = $output3;
                        }
                    $output4[]=   $row4 ;
                    }    
                 }
                   $row["lots"] = $output4;
                 
                 
                 
                 
                 
                 
                 
                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "approve_work_order") {
        $sql ="update mfg_work_order_hdr set status = '".$_GET["status"]."' ,
               approved_by ='".$_GET["emp_id"]."',approved_date= '".$entry_date."'
               where  id ='".$_GET["id"]."' "; 
             
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    

    else if ($_GET["type"] == "approve_work_order_saipro") {
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
        $sql ="update mfg_work_order_hdr set status = '".$_GET["status"]."' ,
               approved_by ='".$_GET["emp_id"]."',approved_date= '".$entry_date."'
               ,batch_number='".$batch_number."', qa_person ='".$_GET["emp_id"]."',qa_date= '".$entry_date."', dispensing_status ='Request Sent',
                dispense_request_sent_by='".$_GET["emp_id"]."', 
                dispense_request_sent_on='$entry_date'
               where  id ='".$_GET["id"]."' "; 
             
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "get_approved_work_orders_for_qa_approval") {
        $sql ="SELECT a.*,p.dosage_form, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,
        b.pack_size,b.pack_unit FROM mfg_work_order_hdr a left JOIN batch_planning b on 
        a.batch_plan_id = b.id and
        a.plant_id = b.plant_id left JOIN product p on b.product_code = p.product_code and b.plant_id = p.plant_id
               where  a.plant_id ='".$_GET["plant_id"]."'   and qa_person='' and a.material_type ='RM' order by a.id desc "; 
        // $sql ="SELECT a.*, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,.p.product_type,
        // b.pack_size,b.pack_unit FROM mfg_work_order_hdr a left JOIN batch_planning b on 
        // a.batch_plan_id = b.id and
        // a.plant_id = b.plant_id left JOIN product p on b.product_code = p.product_code and b.plant_id = p.plant_id
        //       where  a.plant_id ='".$_GET["plant_id"]."' and a.status='approved'  and qa_person='' and a.material_type ='RM' "; 
                
        $result = $conn->query($sql);                   
       if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = Array();
                //  $sql2="SELECT a.*,b.material_type,b.material_subtype,b.material_name
                //  FROM mfg_work_order_dtl a left join mfg_work_order_hdr c on a.work_order_id = c.id left 
                //  join material b
                //  on a.material_code = b.material_code and c.plant_id = b.plant_id where a.work_order_id = '".$row["work_order_no"]."' ";
               
               
                            // old code
               
                 $sql2="select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
                 FROM mfg_work_order_dtl a left join mfg_work_order_hdr c on a.work_order_id = c.id left 
                 join material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id where a.work_order_id = '".$row["id"]."') as a left join
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
                 
                 
                 $output4 = Array(); 
                 $sql2="select distinct lot_type from work_order_batch_lots where work_order_id ='".$row["id"]."' and plant_id= '".$_GET["plant_id"]."'";
                 $result4 = $conn->query($sql2);
                 if ($result4->num_rows > 0) {
                    while ($row4 = $result4->fetch_assoc()) {
                        $output3 = Array();
                        $sql2="select lot_no from work_order_batch_lots where work_order_id ='".$row["id"]."' and lot_type = '".$row4["lot_type"]."'  and plant_id= '".$_GET["plant_id"]."' group by lot_no";
                      
                        $result1 = $conn->query($sql2);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2="select a.id,a.material_code,b.material_subtype,b.material_name,a.batch_qty from work_order_batch_lots a 
                                left join material b on a.material_code=b.material_code and a.plant_id = b.plant_id
                                where a.work_order_id ='".$row["id"]."' and a.lot_no ='".$row1["lot_no"]."' and a.plant_id= '".$_GET["plant_id"]."'  and lot_type = '".$row4["lot_type"]."' ";
                                  
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    $output2 = Array();
                                    while ($row2 = $result2->fetch_assoc()) {
                                            $output2[]=$row2;   
                                    }
                                   // $lot_info["lot_no"] = $row1["lot_no"];
                                    //$lot_info["lots_data"] = $output2;
                                      $row1['lots_list'] = $output2;
                                }
                                $output3[]=$row1;
                                
                               
                                
                            }
                            $row4["lots"] = $output3;
                        }
                    $output4[]=   $row4 ;
                    }    
                 }
                   $row["lots"] = $output4;
                 
                $output[] = $row;
        }
    }
            echo json_encode($output);
    }
    else if ($_GET["type"] == "get_qa_approved_work_orders_WO_BMR") {
        
        $material_type = $_GET["material_type"] ?? 'RM';
        
        $sql = "SELECT a.id, a.material_type, a.batch_number, a.work_order_no, a.entry_by as palnned_by, a.approved_by,
                a.stability, a.stability_reason, a.process_validation, a.qa_person, a.qa_date, a.dispensing_status,
                a.approved_by,
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT om.product_code FROM Work_order_materials wm 
                         LEFT JOIN order_materials om ON wm.order_no = om.order_no
                         WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        b.product_code
                END AS product_code,
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT p.product_name FROM Work_order_materials wm 
                         LEFT JOIN order_materials om ON wm.order_no = om.order_no
                         LEFT JOIN product p ON om.product_code = p.product_code AND wm.plant_id = p.plant_id
                         WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        p.product_name
                END AS product_name,
 
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT wm.batch_size FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        b.batch_size
                END AS batch_size,
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT p.product_type FROM Work_order_materials wm 
                         LEFT JOIN order_materials om ON wm.order_no = om.order_no
                         LEFT JOIN product p ON om.product_code = p.product_code AND wm.plant_id = p.plant_id
                         WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        p.product_type
                END AS product_type,
                p.dosage_form,
                b.plan_no, b.bfr_no, b.mfr_no, b.pack_size, b.pack_unit
                FROM mfg_work_order_hdr a 
                LEFT JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                LEFT JOIN product p ON (
                    CASE 
                        WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                            (SELECT om.product_code FROM Work_order_materials wm 
                             LEFT JOIN order_materials om ON wm.order_no = om.order_no
                             WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                        ELSE
                            b.product_code
                    END
                ) = p.product_code AND a.plant_id = p.plant_id
                WHERE a.plant_id = '".$_GET["plant_id"]."' 
                AND (a.qa_person IS NOT NULL AND a.qa_person != '')
                AND a.status = 'approved'
               
                ORDER BY a.id DESC"; 
        
        $result = $conn->query($sql);
        $output = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Set plan_no for canplan work orders (if not set)
                if (empty($row['plan_no'])) {
                    $row['plan_no'] = $row['work_order_no'] ?? '';
                }
                
                // Get materials from mfg_work_order_dtl
                $output1 = Array();
                $sql2 = "SELECT a.*, 
                        b.material_type, b.material_subtype, b.material_name
                        FROM mfg_work_order_dtl a 
                        LEFT JOIN mfg_work_order_hdr c ON a.work_order_id = c.id 
                        LEFT JOIN material b ON a.material_code = b.material_code AND c.plant_id = b.plant_id 
                        WHERE a.work_order_id = '".$row["id"]."'";
                
                $result1 = $conn->query($sql2);
                if ($result1 && $result1->num_rows > 0) {
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
    else if ($_GET["type"] == "get_qa_approved_work_orders_from_to") {
        $todate = $_GET["to_date"];

// Create a DateTime object from the input date
$date = new DateTime($todate);

// Increment the date by one day
$date->modify('+1 day');

// Format the date in your desired format
  $next_day = $date->format('Y-m-d');
  
             $sql ="SELECT  b.product_type,a.id,a.material_type,a.batch_number,a.work_order_no,a.entry_by as palnned_by,a.approved_by,
                         a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,dispensing_status,
                         a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
                        b.pack_size,b.pack_unit FROM mfg_work_order_hdr a
                        JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                        JOIN product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
                        WHERE
                            a.plant_id = '".$_GET["plant_id"]."'
                            AND a.status = 'approved'
                            AND a.material_type LIKE 'RM'
                            and p.product_name like '%".$_GET["product_name"]."%'
                            AND a.approved_date BETWEEN '".$_GET["from_date"]."' AND '$next_day' 
                        ORDER BY a.id DESC; "; 
        $result = $conn->query($sql);                   
       if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
             $output1 = Array();
                 $sql2="select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
                 FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id 
                 left join material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id ";
                // where a.mfg_work_order_dtl = '".$row["id"]."') as a join
                // WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                // GROUP by material_code) as b on a.material_code = b.material_code";
                 
                  
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
    else if ($_GET["type"] == "get_qa_approved_work_orders") {
        
         
        
                $sql ="SELECT a.*, 
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT om.product_code FROM Work_order_materials wm 
                         LEFT JOIN order_materials om ON wm.order_no = om.order_no
                         WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        b.product_code
                END AS product_code,
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT p.product_name FROM Work_order_materials wm 
                         LEFT JOIN order_materials om ON wm.order_no = om.order_no
                         LEFT JOIN product p ON om.product_code = p.product_code AND wm.plant_id = p.plant_id
                         WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        p.product_name
                END AS product_name,
   
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT wm.batch_size FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        b.batch_size
                END AS batch_size,
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT om.packingStyle FROM Work_order_materials wm 
                         LEFT JOIN order_materials om ON wm.order_no = om.order_no
                         WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        b.pack_size
                END AS pack_size,
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT om.packingUnit FROM Work_order_materials wm 
                         LEFT JOIN order_materials om ON wm.order_no = om.order_no
                         WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        b.pack_unit
                END AS pack_unit,
                p.dosage_form, p.product_type,
                b.bfr_no, b.mfr_no, b.plan_no,
                (SELECT CONCAT(IFNULL(firstname, ''), ' ', IFNULL(middlename, ''), ' ', IFNULL(lastname, ''))
                 FROM employee 
                 WHERE emp_id = a.entry_by
                ) AS entry_by_name
                FROM mfg_work_order_hdr a 
                LEFT JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                LEFT JOIN product p ON (
                    CASE 
                        WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                            (SELECT om.product_code FROM Work_order_materials wm 
                             LEFT JOIN order_materials om ON wm.order_no = om.order_no
                             WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                        ELSE
                            b.product_code
                    END
                ) = p.product_code AND a.plant_id = p.plant_id
                WHERE a.plant_id = '".$_GET["plant_id"]."' AND batch_number IS not NULL 
                ORDER BY a.id DESC "; 
            
             
             
        $result = $conn->query($sql);                   
       if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
             $output1 = Array();
                  $sql2="SELECT a.* 
                        FROM (
                            SELECT a.*,   b.material_name
                            FROM mfg_work_order_dtl a
                            JOIN mfg_work_order_hdr c ON a.work_order_id = c.id
                            LEFT JOIN material b ON a.material_code = b.material_code AND c.plant_id = b.plant_id
                            WHERE a.work_order_id =  '".$row["id"]."'
                        ) AS a
                        JOIN (
                            SELECT material_code 
                            FROM material
                            WHERE material_code IN (
                                SELECT material_code
                                FROM mfg_work_order_dtl
                                WHERE work_order_id =  '".$row["id"]."'
                            )
                            GROUP BY material_code
                        ) AS b ON a.material_code = b.material_code";
            //  echo    $sql2="select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
            //      FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id 
            //      left join material b
            //      on a.material_code = b.material_code and c.plant_id =
            //      b.plant_id    where a.mfg_work_order_dtl = '".$row["id"]."') as a join
            //      WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
            //   GROUP by material_code) as b on a.material_code = b.material_code";
        
                  
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
    else if ($_GET["type"] == "get_qa_approved_work_ordersMeha") {
        
         
        
               $sql ="SELECT  b.product_type,a.id,a.material_type,a.batch_number,a.work_order_no,a.entry_by as palnned_by,a.approved_by,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,dispensing_status,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             b.pack_size,b.pack_unit FROM mfg_work_order_hdr a 
             JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where    a.plant_id ='".$_GET["plant_id"]."' and   a.status='approved'  and a.material_type like '%".$_GET["material_type"]."%' ORDER by a.id DESC "; 
            
             
             
        $result = $conn->query($sql);                   
       if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
             $output1 = Array();
                  $sql2="SELECT a.* 
                        FROM (
                            SELECT a.*,   b.material_name
                            FROM mfg_work_order_dtl a
                            JOIN mfg_work_order_hdr c ON a.work_order_id = c.id
                            LEFT JOIN material b ON a.material_code = b.material_code AND c.plant_id = b.plant_id
                            WHERE a.work_order_id =  '".$row["id"]."'
                        ) AS a
                        JOIN (
                            SELECT material_code 
                            FROM material
                            WHERE material_code IN (
                                SELECT material_code
                                FROM mfg_work_order_dtl
                                WHERE work_order_id =  '".$row["id"]."'
                            )
                            GROUP BY material_code
                        ) AS b ON a.material_code = b.material_code";
            //  echo    $sql2="select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
            //      FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id 
            //      left join material b
            //      on a.material_code = b.material_code and c.plant_id =
            //      b.plant_id    where a.mfg_work_order_dtl = '".$row["id"]."') as a join
            //      WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
            //   GROUP by material_code) as b on a.material_code = b.material_code";
        
                  
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
    else if ($_GET["type"] == "get_despensing_requests") {
              $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.entry_by as palnned_by,a.approved_by,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where  a.plant_id ='".$_GET['plant_id']."'";
             //and dispense_request_sent_by!='' "; 
        $result = $conn->query($sql);                   
       if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = Array();
            
                  $sql2="select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
                 FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id left join material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id  
                
                 where a.work_order_id = '".$row["id"]."') as a join
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
    else if ($_GET["type"] == "get_dispensing_complted_requests_by_store") {
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
          $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,a.lod_status,a.calculation_type,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, a.rm_received_by,
             a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,rm_disp_completed_by, rm_disp_completed_date,
             (SELECT om.product_code FROM Work_order_materials wm 
              LEFT JOIN order_materials om ON wm.order_no = om.order_no
              WHERE wm.workorder_no = a.work_order_no LIMIT 1) AS product_code,
             (SELECT p.product_name FROM Work_order_materials wm 
              LEFT JOIN order_materials om ON wm.order_no = om.order_no
              LEFT JOIN product p ON om.product_code = p.product_code  
              WHERE wm.workorder_no = a.work_order_no LIMIT 1) AS product_name,
            
             (SELECT wm.batch_size FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1) AS batch_size,
             (SELECT p.product_type FROM Work_order_materials wm 
              LEFT JOIN order_materials om ON wm.order_no = om.order_no
              LEFT JOIN product p ON om.product_code = p.product_code AND wm.plant_id = p.plant_id
              WHERE wm.workorder_no = a.work_order_no LIMIT 1) AS product_type,
             a.work_order_no AS plan_no, NULL AS bfr_no, NULL AS mfr_no, NULL AS pack_size, NULL AS pack_unit
             FROM mfg_work_order_hdr a 
             WHERE a.plant_id ='".$_GET["plant_id"]."' and a.dispensing_status ='Request Sent' 
              AND (a.rm_disp_completed_by != '0' OR a.rm_disp_completed_by = '0')"; 
                        
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Set plan_no for canplan work orders (if not set)
                if (empty($row['plan_no'])) {
                    $row['plan_no'] = $row['work_order_no'] ?? '';
                }
                
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                 $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.category,b.material_subtype,b.grade as m_grade,b.material_name,
                wd.lod_status,wd.assay_status,wd.id as wediD,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on wd.id = dd.dtl_id
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
                        
                        
                          $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['m_grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
                      
                                      $output1[] = $row1;
                    }
                }
                $row["fifo_method"] =$fifo_method;
                $row["materials"] = $output1;
                $output[] = $row;
                
                $jadugar=0;
                
                     foreach ($output1 as $material) {
              
               if ($material["checked_by"] == '0'){
                    $jadugar++;
                    
                }
                
            }
               if( $jadugar == 0){
                  $plant=$_GET["plant_id"];
         $id=$row["id"];
         $bmr="BMR".$plant.$id;
         $sql = "UPDATE  mfg_work_order_hdr set rm_receiving_remarks = '".$input["remarks"]."' ,
         rm_receiving_status = 'discpensing_completed',
         rm_received_date='".$entry_date."', 
         rm_received_by='".$_GET["emp_id"]."',bmr_no='$bmr'  WHERE id='".$row["id"]."'";
         
     
       $conn->query($sql);
                
            } 
            
            
            
            }
        } 
        
        
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_dispensing_complted_requests_by_storeMeha") {
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
          $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,a.lod_status,a.calculation_type,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,a.rm_received_by,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,rm_disp_completed_by, rm_disp_completed_date
             FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."' and a.dispensing_status ='Request Sent' 
              AND (a.rm_disp_completed_by != '0' OR a.rm_disp_completed_by = '0') GROUP by a.id,a.batch_number,a.work_order_no,  palnned_by,a.approved_by,a.lod_status,a.calculation_type,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,a.rm_received_by,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,rm_disp_completed_by, rm_disp_completed_date;
               "; 
                        
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                // $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.grade as m_grade,b.material_name,wd.lod_status,wd.assay_status,
                // IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                //  join mfg_work_order_hdr c on a.work_order_id = c.id
                //  left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                //  left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                //  left join dispensing_details_hdr dd on a.id = dd.lot_id
                //  where a.work_order_id = '".$row["id"]."') as a left join
                //  (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                //  WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                //  GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                 
                  $sql1 = "select a.*,COALESCE(b.avbl_stock, c.avbl_stock) AS avbl_stock from (SELECT a.*, b.category,b.grade as m_grade,b.material_name,
                  wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."' and dd.prod_checking='Yes' ) as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots
                 where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='Raw Material'
                 left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM fg_stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots
                 where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as c on a.material_code = c.material_code and a.material_type='Intermediate'";
                      
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
                        
                        
                          $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['m_grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
                      
                                      $output1[] = $row1;
                    }
                }
                $row["fifo_method"] =$fifo_method;
                $row["materials"] = $output1;
                $output[] = $row;
                
                $jadugar=0;
                
                     foreach ($output1 as $material) {
              
               if ($material["checked_by"] == '0'){
                    $jadugar++;
                    
                }
                
            }
               if( $jadugar == 0){
                  $plant=$_GET["plant_id"];
         $id=$row["id"];
         $bmr="BMR".$plant.$id;
         $sql = "UPDATE  mfg_work_order_hdr set rm_receiving_remarks = '".$input["remarks"]."' ,
         rm_receiving_status = 'discpensing_completed',
         rm_received_date='".$entry_date."', 
         rm_received_by='".$_GET["emp_id"]."',bmr_no='$bmr'  WHERE id='".$row["id"]."'";
         
     
       $conn->query($sql);
                
            } 
            
            
            
            }
        } 
        
        
        echo json_encode($output);
    }
    else if ($_GET["type"] == "update_dispense_request") {
        $sql = "UPDATE mfg_work_order_hdr SET
                dispensing_status ='Request Sent',
                dispense_request_sent_by='".$_GET["emp_id"]."', 
                dispense_request_sent_on='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else  if ($_GET["type"] == "save_pm_work_order") {
         
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
        $work_order_no =  "PW".$number;
        
       
        
        $sql = "INSERT INTO mfg_work_order_hdr (plant_id,batch_plan_id,batch_id,work_order_no,
        batch_overages,overages_percent,entry_by,material_type,allocate_batch_no)
        values('".$_GET["plant_id"]."','".$input["batch_plan_id"]."','".$input["selected_batch_index"]."','".$work_order_no."'
        ,'".$input["batch_overages"]."','".$input["overages_percent"]."','".$_GET["emp_id"]."',  '".$input["material_type"]."', '".$input["allocate_batch_no"]."')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            if($_GET["plant_id"]==67){
                 $p_materials = $input["packing_materials"];
            for ($i = 0; $i <count($p_materials); $i++) { 
                  $material = $p_materials[$i];
                $sql = "INSERT INTO  mfg_work_order_dtl(work_order_id,material_code,grade,unit_qty,unit,overages,total_unit_qty,batch_qty,
                batch_overages,total_batch_qty,pack_size_id,actual_qtyy)
                values('".$last_id."','".$material["material_code"]."','".$material["grade"]."',
                 '".$material["b_qty"]."','".$material["unit"]."', '".$material["overages"]."','".$material["total_qty"]."',
                 '".$material["batch_qty"]."','".$material["batch_overages"]."',
                '".$material["total_final_qty"]."','".$material["pack_size_id"]."','".$material["actual_qtyy"]."')";
                $conn->query($sql);
                 
            } 
                
            }else{
                 $p_materials = $input["packing_materials"];
            for ($i = 0; $i <count($p_materials); $i++) { 
                  $material = $p_materials[$i];
                $sql = "INSERT INTO  mfg_work_order_dtl(work_order_id,material_code,grade,unit_qty,unit,overages,total_unit_qty,batch_qty,
                batch_overages,total_batch_qty,pack_size_id)
                values('".$last_id."','".$material["material_code"]."','".$material["grade"]."',
                 '".$material["b_qty"]."','".$material["unit"]."', '".$material["overages"]."','".$material["total_qty"]."',
                 '".$material["batch_qty"]."','".$material["batch_overages"]."',
                '".$material["total_final_qty"]."','".$material["pack_size_id"]."')";
                $conn->query($sql);
                 
            } 
            }
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
else if ($_GET["type"] == "get_pm_workorders_for_approval") {
      $sql = "SELECT a.*, b.id as batch_plan_id, b.bfr_no, b.mfr_no, b.product_code, b.batch_size, p.product_name, p.product_type,
            b.pack_size, b.pack_unit
            FROM mfg_work_order_hdr a
            JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
            JOIN product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
            LEFT JOIN mfg_work_order_dtl m ON a.id = m.work_order_id
            WHERE a.plant_id = '" . $_GET["plant_id"] . "'  AND a.status='pending' AND a.material_type='PM'   GROUP by a.id,b.id,p.id, m.actual_qtyy ORDER BY a.id DESC LIMIT 20" ; // Example: limit to 100 rows
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $output = array();
        while ($row = $result->fetch_assoc()) {
            $output1 = array();
            $sql2 = "SELECT * FROM batch_planing_raw_material_hdr WHERE batch_plan_id = '" . $row["batch_plan_id"] . "' limit 1";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $output2 = array();
                    $sql3 = "SELECT DISTINCT a.*, b.*, s.*, c.*, a.qty AS b_qty, s.qty AS avbl_stock,c.actual_qtyy as actual_qtyy FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code LEFT JOIN stock_book s ON a.material_code = s.material_code LEFT JOIN mfg_work_order_dtl c ON a.material_code = c.material_code 
                            WHERE a.material_type = 'Packing Material' AND a.pm_hdr_id='" . $row2["id"] . "' and a.batch_plan_id='" . $row["batch_plan_id"] . "' AND c.work_order_id='" . $row["id"] . "' "; // Example: limit to 50 rows
                            // WHERE a.material_type = 'Packing Material' AND a.pm_hdr_id='" . $row2["id"] . "' and a.batch_plan_id='" . $row["batch_plan_id"] . "' AND c.work_order_id='" . $row["id"] . "' and c.actual_qtyy!='' LIMIT 20"; // Example: limit to 50 rows
                    $result3 = $conn->query($sql3);
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
        echo json_encode($output);
    }

    else if ($_GET["type"] == "get_pm_approved_work_orders_for_qa_approval") {
        $sql ="SELECT a.*, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,
        b.pack_size,b.pack_unit FROM mfg_work_order_hdr a left JOIN batch_planning b on 
        a.batch_plan_id = b.id and
        a.plant_id = b.plant_id left JOIN product p on b.product_code = p.product_code and b.plant_id = p.plant_id
               where  a.plant_id ='".$_GET["plant_id"]."' and a.status='approved'  and qa_person='' and a.material_type ='PM'  order by a.id desc"; 
       $result = $conn->query($sql);                   
       if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output1 = array();
                $sql2 = "select * from batch_planing_raw_material_hdr where batch_plan_id = '".$row["batch_plan_id"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                     $output2 = Array();
                    //  $sql3="select a.*,IFNULL(b.avbl_stock,0) as avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
                    //  FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id left join material b
                    //  on a.material_code = b.material_code and c.plant_id = b.plant_id where a.work_order_id = '".$row["id"]."'
                    //  and a.pack_size_id='".$row2["id"]."') as a left join
                    //  (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                    //  WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                    //  GROUP by material_code) as b on a.material_code = b.material_code";
                     $sql3="SELECT
                                *
                            FROM
                                batch_planning_materials a
                            LEFT JOIN material b ON
                                a.material_code = b.material_code
                            LEFT JOIN stock_book c ON
                                b.material_code = c.material_code
                            
                            WHERE
                                a.material_type = 'Packing Material' and a.pm_hdr_id='".$row2["id"]."'";
                    
                     $result3 = $conn->query($sql3);
                     if ($result3->num_rows > 0) {
                        while ($row3 = $result3->fetch_assoc()) {
                                $output2[]=$row3;   
                        }
                         
                     }
                     $row2["packing_material"] = $output2;
                  $output1[] = $row2;
                    }
                    $row["pack_sizes"] = $output1;
                }
                 
                 
                $output[] = $row;
            }
        }
       
       
        echo json_encode($output);
    }
    
}
}
else {
    echo "{\"status\":\"invalid\"}";
}
}catch (\Throwable $e) {
               echo "{\"statuse\":\"".$e."\"}";
}
$conn->close();
 
?>