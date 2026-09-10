<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
    require '../db.php';
    require '../token.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
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

      if ($_GET["type"] == "autoPurchaseSaveIndent") {
          
      
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Decode JSON payload
$input = json_decode(file_get_contents("php://input"), true);
if (!$input || !is_array($input)) {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit();
}

$timestamp = time();
$no = date("YmdHis", $timestamp);
$last_id = null;
$entry_date = date("Y-m-d H:i:s");

// Get last request number
$sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $last_id = $row['request_no'];
}

$last_id = $last_id ? (int)filter_var($last_id, FILTER_SANITIZE_NUMBER_INT) + 1 : 1;
$number = substr(str_repeat("0", 4) . $last_id, -4);
$ind_no = "IN0" . $number;
$RQ_no = "RQ" . $number;
$lastJaduLogic = 1;

        foreach ($input as $temp) {
            $temp["required_for"] = "Client";
        
            $sql = "INSERT INTO indend_raw (
                        plant_id, user_no, indend_no, no, material_type, material_subtype, material_id, material_code,
                        req_qty, unit, requirement, purpose, required_for, client_code, vendor_type, specific_vendor,
                        entry_by, entry_date, department, status,request_no
                    ) VALUES (
                        '{$_GET["plant_id"]}', '{$_GET["user_no"]}', '$ind_no', '$no', '{$temp["material_type"]}',
                        '{$temp["material_subtype"]}', '{$temp["id"]}', '{$temp["material_code"]}', '{$temp["totalOrderQty"]}', '{$temp["required_qty_unit"]}',
                        '{$temp["requirement"]}', '{$temp["purpose"]}', '{$temp["required_for"]}', '{$temp["client_code"]}', '{$temp["vendor_type"]}',
                        '{$temp["vendor_code"]}', '{$_GET["emp_id"]}', '$entry_date', 'Store', 'pending','$RQ_no')";
        
            if ($conn->query($sql)) {
                $inserted_id = $conn->insert_id;
        
                // Optional echo for success
               
        
                foreach ($temp["splistCal"] ?? [] as $material) {
                    if (!empty($material["check"])) {
                        $sql1 = "INSERT INTO mrp_raised_indnd_qty (
                                    indend_id, material_code, material_name, ordered_qty, material_type,
                                    month, year, responsiblePerson, apprxIndend, appxPurchase, appxDelivery
                                ) VALUES (
                                    '$inserted_id', '{$temp["material_code"]}', '{$temp["material_name"]}', '{$material["calculatedValue"]}', '{$temp["material_type"]}',
                                    '{$material["month"]}', '{$material["year"]}', '{$temp["responsiblePerson"]}', '{$temp["apprxIndend"]}', '{$temp["appxPurchase"]}', '{$temp["appxDelivery"]}'
                                )";
                        $conn->query($sql1);
                    }
                }
                
            } else {
                $lastJaduLogic = 0;
                 
            }
        }
        
        
        if($lastJaduLogic == 1){
             echo json_encode(["status" => "success"]);
        }else{
             echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        
        

     
 } 
 
 
          else if ($_GET["type"] == "get_products_formulation_shortage_calculation") {
          
              
          $output=Array();
          
          
          
          $sql = "SELECT a.product_code,a.product_type,a.product_name, pack_sizes FROM product a 
        where    a.status='Approved' and a.plant_id='".$_GET["plant_id"]."' and a.product_code='".$_GET["product_code"]."' ";
        
       
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
                 $sql2="Select id,mfr_no,average_weight as batch_size from unitformula where product_code = 
                 '".$row["product_code"]."'  and status='Approve' ";
                
                 $result1 = $conn->query($sql2);
                 if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql3="Select id,mfr_no,bfr_no,batch_formula_weight,raw_materials,packing_materials ,batch_formula_weight_unit as uni
                        from batch_formula_info  where mfr_no = '".$row1["mfr_no"]."' and status='Approve' ";
                        $result2 = $conn->query($sql3);
                              if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        
                                                        $packs = array(); // packSizes array

                                                        // --- Get all pack sizes for this batch_formula_info record ---
                                                         $sql_pack = "SELECT * FROM batch_formula_info_packSizes 
                                                                     WHERE batch_formula_info_id = '{$row2["id"]}'";
                                                        $result_pack = $conn->query($sql_pack);
                                                
                                                        if ($result_pack && $result_pack->num_rows > 0) {
                                                            while ($pack_row = $result_pack->fetch_assoc()) {
                                                
                                                                             $raw_materials = array();
                                                                            $packing_materials = array();
                                                                            
                                                                            // --- Get all materials for this pack size --- 
                                                                             $sql_mat = "SELECT a.*,c.material_subtype,c.material_name ,
                                                                            ROUND(IFNULL(b.available_qty, 0), 2) AS avbl_stock FROM batch_materials  a   
                                                                              LEFT JOIN vw_available_stock b 
                                                                                        ON a.material_code = b.material_code left
                                                                                     
                                                                            join material c on a.material_code=c.material_code
                                                                                        WHERE a.batch_formula_info_packSizes_id = '{$pack_row["id"]}'";
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
                                                
                                                        // Add packs under each main record
                                                        $row2['Packs'] = $packs;
                                        
                                        
                                        
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
 
 
 
 if ($_GET['type'] == 'autoPurchaseWorkorder') {

    
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !is_array($data)) {
        echo json_encode(["status" => "error", "message" => "Invalid data"]);
        exit;
    }

    foreach ($data as $batch) {

        // =========================
        // Insert into parent table
        // =========================
        $plant_id = 182; // change if needed
        $material_type = $batch['category'];
        $plan_no = "";
        $plan_for = "";
        $plan_based_on = "";
        $plan_type = "";
        $plan_for_market = "";
        $plan_client_name = $batch['mainGroupName']; 
        $plan_client_code = ""; // Provide client code if needed
        $client_po_no = $batch['order_no'];
        $client_po_date = date("Y-m-d");
        $client_po_qty = $batch['order_qty'];
        $client_po_unit = $batch['ord_unit'];
        $product_type = $batch['category'];
        $product_code = $batch['product_code'] ?? ''; 
        $product_name = $batch['product_name'] ?? '';
        $country_specific = "";
        $pack_multiple_countries = "";
        $grade = "";
        $bfr_no = $batch['bfr_no'] ?? '';
        $mfr_no = $batch['mfr_no'];
        $batch_size = $batch['batch_formula_weight'];
        $batch_no = $batch['batchIndex'];
        $pack_size = $batch['pack_size'] ?? '';
        $pack_unit = $batch['ord_unit'];
        $total_batches = 1;
        $dispatch_qty = "";
        $planned_qty = $batch['batchQty'];
        $qty_can_planned = "";
        $no_of_batches_can_planned = "";
        $status = "pending";
        $entry_by = "system";
        $entry_date = date("Y-m-d H:i:s");
        $approve_by = "";
        $approve_date = "";

        $sql = "INSERT INTO batch_planning (
            plant_id, material_type, plan_no, plan_for, plan_based_on, plan_type,
            plan_for_market, plan_client_name, plan_client_code, client_po_no, 
            client_po_date, client_po_qty, client_po_unit, product_type, product_code, 
            product_name, country_specific, pack_multiple_countries, grade, 
            bfr_no, mfr_no, batch_size, batch_no, pack_size, pack_unit, total_batches, 
            dispatch_qty, planned_qty, qty_can_planned, no_of_batches_can_planned, 
            status, entry_by, entry_date, approve_by, approve_date
        ) VALUES (
            '$plant_id', '$material_type', '$plan_no', '$plan_for', '$plan_based_on', '$plan_type',
            '$plan_for_market', '$plan_client_name', '$plan_client_code', '$client_po_no',
            '$client_po_date', '$client_po_qty', '$client_po_unit', '$product_type', '$product_code',
            '$product_name', '$country_specific', '$pack_multiple_countries', '$grade',
            '$bfr_no', '$mfr_no', '$batch_size', '$batch_no', '$pack_size', '$pack_unit', '$total_batches',
            '$dispatch_qty', '$planned_qty', '$qty_can_planned', '$no_of_batches_can_planned',
            '$status', '$entry_by', '$entry_date', '$approve_by', '$approve_date'
        )";

        if ($conn->query($sql)) {
            $batch_plan_id = $conn->insert_id;
        } else {
            echo json_encode(["status" => "error", "message" => "Parent Insert Failed"]);
            exit;
        }

        // ==========================================
        // Insert CHILD RAW MATERIALS
        // ==========================================
        foreach ($batch['raw_materials'] as $rm) {

            $rm_sql = "INSERT INTO batch_planning_materials (
                plant_id, batch_plan_id, material_type, pack_size, pack_unit, 
                mf_batch_size, bfr_no, stage, material_code, overages, qty, unit, grade, 
                batch_qty, total_qty, plan_qty, batches_can_plan, shortage_qty, 
                disp_id, dispensing_status
            ) VALUES (
                '$plant_id', '$batch_plan_id', '{$rm['material_type']}', '{$rm['pack_size']}', 
                '{$rm['unit']}', '{$batch['batch_formula_weight']}', '{$batch['bfr_no']}',
                '{$rm['stage']}', '{$rm['material_name']}', '{$rm['overages']}', '{$rm['batch_qty']}',
                '{$rm['unit']}', '{$rm['grade']}', '{$rm['required_qty']}', '{$rm['required_qty']}',
                '{$rm['plan_qty']}', '{$batch['batchQty']}', '{$rm['short_qty']}', '', 'Pending'
            )";

            $conn->query($rm_sql);
        }

        // ==========================================
        // Insert CHILD PACKING MATERIALS
        // ==========================================
        foreach ($batch['packing_materials'] as $pm) {

            $pm_sql = "INSERT INTO batch_planning_materials (
                plant_id, batch_plan_id, material_type, pack_size, pack_unit, 
                mf_batch_size, bfr_no, stage, material_code, overages, qty, unit, grade, 
                batch_qty, total_qty, plan_qty, batches_can_plan, shortage_qty, 
                disp_id, dispensing_status
            ) VALUES (
                '$plant_id', '$batch_plan_id', '{$pm['material_type']}', '{$pm['pack_size']}', 
                '{$pm['unit']}', '{$batch['batch_formula_weight']}', '{$batch['bfr_no']}',
                '{$pm['stage']}', '{$pm['material_name']}', '{$pm['overages']}', '{$pm['batch_qty']}',
                '{$pm['unit']}', '{$pm['grade']}', '{$pm['required_qty']}', '{$pm['required_qty']}',
                '{$pm['plan_qty']}', '{$batch['batchQty']}', '{$pm['short_qty']}', '', 'Pending'
            )";

            $conn->query($pm_sql);
        }
    }

    echo json_encode(["status" => "success", "message" => "Batch plan saved"]);

    
}

 
 
 
//           else if ($_GET["type"] == "sendOrdersForSegrigation") {
              
//                       $json_obj = json_encode($input["selectedPOs"]);
//               $array = json_decode($json_obj, true);
//                  $k=1;
//                 foreach ($array as $values)
//                 {
//                     //   $sql = "update order_materials set status ='send for client wise order' where id ='".$values['id']."'";
// //   $sql = "update  Forcast_order_materials  set status ='Inprocess' where id ='".$values['id']."'";
//   $sql = "update order_materials set status ='Inprocess' where id ='".$values['id']."'";
//         if ($conn->query($sql)) {
//              $status1 = true;
//         } else {
//             $status1 = false;
//         }
                    
//                 }
        
//          if ($status1) {
//             echo "{\"status\":\"success\"}";
//         } else {
//             echo "{\"status\":\"".$conn->error."\"}";
//         }
        
//           }
          else if ($_GET["type"] == "sendOrdersForWorkorder") {
              
                      $json_obj = json_encode($input["selectedPOs"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                    
                    
                      $sql = "insert into work_order(order_no,product_code,batch_qty,plan_date)values(
                          '".$values['order_no']."','".$values['product_code']."','".$values['batch_qty']."','".$values['plan_date']."'";
//   $sql = "update  Forcast_order_materials  set status ='Inprocess' where id ='".$values['id']."'";
//   $sql = "update order_materials set status ='Inprocess' where id ='".$values['id']."'";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
          }
 
          else if ($_GET["type"] == "sendOrdersForShotages") {
              
                      $json_obj = json_encode($input["selectedPOs"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
   $sql = "update order_materials set status ='send for Shotages' where order_no ='".$values['order_no']."'";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
          }
       
          else if ($_GET["type"] == "getDataMrpIndend") {
              
               
        
// Example materials array
$materials = $input;

// Your database connection

// Loop over materials
foreach ($materials as $mKey => $material) {
    $material_code = $conn->real_escape_string($material['material_code']);
    $material_name = $conn->real_escape_string($material['material_name']);
    $material_type = $conn->real_escape_string($material['material_type']);

    // Check if splistCal exists
    if (isset($material['splistCal']) && is_array($material['splistCal'])) {
        foreach ($material['splistCal'] as $sKey => $splist) {
            
            $year = $conn->real_escape_string($splist['year']);
            $month = $conn->real_escape_string($splist['month']);
            if($material['balance_qty']=='0'){
            
            // SQL Query
            $sql = "SELECT *, 
                        (SELECT SUM(ordered_qty) 
                         FROM mrp_raised_indnd_qty b 
                         WHERE a.material_code = b.material_code 
                           AND (status = 'indend Created' OR status = 'Approve')) AS booked_qty ,
                        (
                         IFNULL(b.indend_prepare_date, 0) +
                         IFNULL(b.Purchase_prepare_date, 0) +
                         IFNULL(b.ForPayment, 0) +
                         IFNULL(b.PurchaseDeliveryTime, 0) +
                         IFNULL(b.Sampling_prepare_date, 0) +
                         IFNULL(b.release_prepare_date, 0)
                       ) AS total_preparation_days
                FROM mrp_raised_indnd_qty a
                LEFT JOIN material b ON a.material_code = b.material_code
                    WHERE a.year = '$year'
                      AND a.month = '$month'
                      AND a.material_type = '$material_type'
                      AND a.material_name = '$material_name'
                      AND a.material_code = '$material_code'";

            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();

                $ordered_qty = isset($row['ordered_qty']) ? (float)$row['ordered_qty'] : 0;
                $booked_qty = isset($row['booked_qty']) ? (float)$row['booked_qty'] : 0;
                $balance_qty = isset($row['balance_qty']) ? (float)$row['balance_qty'] : 0;

                // Ensure balance_qty is not less than 0
                $balance_qty = max(0, $balance_qty);

                $calculatedValue = (float)$materials[$mKey]['splistCal'][$sKey]['calculatedValue'];
                $newCalculatedValue = $calculatedValue - $ordered_qty;

                // Update data
                $materials[$mKey]['splistCal'][$sKey]['calculatedValue'] = $newCalculatedValue;
                $materials[$mKey]['splistCal'][$sKey]['found'] = 1;
                $materials[$mKey]['booked_qty'] = $booked_qty;
                $materials[$mKey]['balance_qty'] = $balance_qty;
                  $total_preparation_days = isset($row['total_preparation_days']) ? (float)$row['total_preparation_days'] : 0;
$materials[$mKey]['total_preparation_days'] = $total_preparation_days;
   if(  $materials[$mKey]['required_qty_unit'] == 'ml' ){
                //   $materials[$mKey]['required_qty']=$materials[$mKey]['required_qty']/1000;
                  $materials[$mKey]['required_qty_unit'] = 'Ltr';
                  
              }
            } else {
                $materials[$mKey]['splistCal'][$sKey]['found'] = 0;
                $materials[$mKey]['booked_qty'] = 0;
                $materials[$mKey]['balance_qty'] = 0;
                  $total_preparation_days = isset($row['total_preparation_days']) ? (float)$row['total_preparation_days'] : 0;
$materials[$mKey]['total_preparation_days'] = $total_preparation_days;
   if(  $materials[$mKey]['required_qty_unit'] == 'ml' ){
                //   $materials[$mKey]['required_qty']=$materials[$mKey]['required_qty']/1000;
                  $materials[$mKey]['required_qty_unit'] = 'Ltr';
                  
              }
            }
        
            
            
            
        }else{
            
                $sql = "SELECT *, 
                        (SELECT SUM(ordered_qty) 
                         FROM mrp_raised_indnd_qty b 
                         WHERE a.material_code = b.material_code 
                           AND (status = 'indend Created' OR status = 'Approve')) AS booked_qty ,
                        (
                         IFNULL(b.indend_prepare_date, 0) +
                         IFNULL(b.Purchase_prepare_date, 0) +
                         IFNULL(b.ForPayment, 0) +
                         IFNULL(b.PurchaseDeliveryTime, 0) +
                         IFNULL(b.Sampling_prepare_date, 0) +
                         IFNULL(b.release_prepare_date, 0)
                       ) AS total_preparation_days
                FROM mrp_raised_indnd_qty a
                LEFT JOIN material b ON a.material_code = b.material_code
                    WHERE a.year = '$year'
                      AND a.month = '$month'
                      AND a.material_type = '$material_type'
                      AND a.material_name = '$material_name'
                      AND a.material_code = '$material_code'";
                       $result = $conn->query($sql);

          
                $row = $result->fetch_assoc();
                  $booked_qty = isset($row['booked_qty']) ? (float)$row['booked_qty'] : 0;
                  $total_preparation_days = isset($row['total_preparation_days']) ? (float)$row['total_preparation_days'] : 0;
                
                $materials[$mKey]['splistCal'][$sKey]['calculatedValue'] = (float)$materials[$mKey]['splistCal'][$sKey]['calculatedValue'];;
                $materials[$mKey]['splistCal'][$sKey]['found'] = 1;
                $materials[$mKey]['booked_qty'] = $booked_qty;
                $materials[$mKey]['total_preparation_days'] = $total_preparation_days;
                $materials[$mKey]['total_preparation_days'] = $total_preparation_days;
                
                
              if(  $materials[$mKey]['required_qty_unit'] == 'ml' ){
                //   $materials[$mKey]['required_qty']=$materials[$mKey]['required_qty']/1000;
                  $materials[$mKey]['required_qty_unit'] = 'Ltr';
              }
                
            
        }
    
        }
        }
}

// echo json_encode($materials);
$filtered = array_filter($materials, function($mat) {
    $required = isset($mat['required_qty']) ? (float)$mat['required_qty'] : 0;
    $balance  = isset($mat['balance_qty']) ? (float)$mat['balance_qty'] : 0;
    return $required > $balance;
});

// Reindex array (optional, for clean JSON)
$filtered = array_values($filtered);

echo json_encode($filtered);

              }

 
 
 
// --------------- Prepare Work Order ---------------

     else if ($_GET["type"] == "prepare_workorder") {
    
  $data = json_decode(file_get_contents('php://input'), true);    
    $output = [];
    echo('hi');
    foreach($data as $order) {
    echo('bi');    
        $order_no = $order['order_no'];
        $product_code = $order['product_code'];
        $order_qty = $order['order_qty'];
        $batch_size = $order['batch_size'];
        $num_batches = ceil($order_qty / $batch_size);

        for($i=1; $i<=$num_batches; $i++) {
            $batch_qty = ($i < $num_batches) ? $batch_size : ($order_qty - $batch_size*($num_batches-1));
            
            // Insert work order
         echo   $stmt = $conn->prepare("INSERT INTO work_orders (order_no, product_code, batch_no, batch_qty) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $order_no, $product_code, $i, $batch_qty);
            $stmt->execute();
            $work_order_id = $stmt->insert_id;
            
            // Insert RM/PM details
            foreach($order['raw_materials'] as $rm) {
                $stmt2 = $conn->prepare("INSERT INTO work_order_materials (work_order_id, material_code, required_qty, balance_qty, material_type) VALUES (?, ?, ?, ?, ?)");
                $stmt2->bind_param("isdss", $work_order_id, $rm['material_code'], $rm['required_qty'], $rm['balance_qty'], $rm['material_type']);
                $stmt2->execute();
            }
            
            foreach($order['packing_configuration'] as $pm) {
                $stmt3 = $conn->prepare("INSERT INTO work_order_materials (work_order_id, material_code, required_qty, balance_qty, material_type) VALUES (?, ?, ?, ?, ?)");
                $stmt3->bind_param("isdss", $work_order_id, $pm['material_code'], $pm['required_qty'], $pm['balance_qty'], $pm['material_type']);
                $stmt3->execute();
            }
            
            $output[] = [
                'work_order_id' => $work_order_id,
                'order_no' => $order_no,
                'batch_no' => $i,
                'batch_qty' => $batch_qty
            ];
        }
    }
    
    echo json_encode(['status'=>'success','data'=>$output]);
 }
// --------------- Get Pending Work Orders (Preview) ---------------
else if($type == 'get_pending_workorders') {
    $plant_id = $_GET['plant_id'];
    $sql = "SELECT * FROM work_orders WHERE status='Pending' ORDER BY id DESC";
    $result = $conn->query($sql);
    $orders = [];
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $orders[] = $row;
        }
    }
    echo json_encode($orders);
}

// --------------- Get Shortage Analysis ---------------
else if($type == 'get_shortage') {
    $work_order_id = $_GET['work_order_id'];
    $sql = "SELECT * FROM work_order_shortage WHERE work_order_id='$work_order_id'";
    $result = $conn->query($sql);
    $shortages = [];
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $shortages[] = $row;
        }
    }
    echo json_encode($shortages);
}




 
 
 
}

$conn->close();
?>