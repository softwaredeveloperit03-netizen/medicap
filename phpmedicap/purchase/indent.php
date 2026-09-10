<?php


//  ini_set('display_errors', 1);
//   error_reporting(E_ALL);
 
 
 
    require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    if (!is_array($input)) {
        $input = array();
    }

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

    /** SQL fragment: RM/PM indent lines (Raw, Packing, or legacy RM/PM Material). */
    function indentRmpmMaterialWhere($alias = 'i') {
        $a = $alias;
        return "($a.material_type IN ('Raw Material','Packing Material','RM/PM Material')
                OR LOWER($a.material_type) LIKE '%raw material%'
                OR LOWER($a.material_type) LIKE '%packing material%'
                OR LOWER($a.material_type) LIKE '%rm/pm%')";
    }

    function indentEnsurePrNotesColumn($conn) {
        $r = @$conn->query("SHOW COLUMNS FROM indend_raw LIKE 'pr_notes'");
        if (!$r || $r->num_rows === 0) {
            @$conn->query("ALTER TABLE indend_raw ADD pr_notes TEXT NULL DEFAULT NULL");
        }
    }

    /** Store Raw/Packing on indent lines; resolve RM/PM Material from master. */
    function normalizeIndentMaterialType($conn, $matType, $materialCode) {
        $matType = trim((string)$matType);
        if ($matType === 'RM') {
            return 'Raw Material';
        }
        if ($matType === 'PM') {
            return 'Packing Material';
        }
        if ($matType !== '' && strcasecmp($matType, 'RM/PM Material') !== 0) {
            return $matType;
        }
        $codeEsc = mysqli_real_escape_string($conn, trim((string)$materialCode));
        if ($codeEsc === '') {
            return 'Raw Material';
        }
        foreach (array(
            "SELECT material_type FROM my_view WHERE material_code='$codeEsc' LIMIT 1",
            "SELECT material_type FROM material WHERE material_code='$codeEsc' LIMIT 1",
            "SELECT material_type FROM others_material WHERE material_code='$codeEsc' LIMIT 1",
        ) as $sql) {
            $res = @$conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $t = trim((string)(($res->fetch_assoc())['material_type'] ?? ''));
                if ($t === 'RM') {
                    $t = 'Raw Material';
                } else if ($t === 'PM') {
                    $t = 'Packing Material';
                }
                $tLower = strtolower($t);
                if (strpos($tLower, 'packing') !== false) {
                    return 'Packing Material';
                }
                if (strpos($tLower, 'raw') !== false) {
                    return 'Raw Material';
                }
                if ($t !== '' && strcasecmp($t, 'RM/PM Material') !== 0) {
                    return $t;
                }
            }
        }
        return 'Raw Material';
    }
    
    if ($_GET["type"] == "saveIndent") { 
        $no = date("YmdHis", $timestamp);
        $flag = 0;
         $last_id;
        $sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['request_no'];
        }
        if($last_id==null){ 
            $last_id=1;
        }
        else {
            $int_var = (int)filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
             $last_id = $int_var+1;
        }
        
        
        $number = substr(str_repeat(0, 4).$last_id, - 4);
         
        $ind_no = "RQ".$number;
        
         
         for ($i = 0; $i < count($input); $i++) {
            $temp = $input[$i];
            $temp["required_for"] = "Own";
            
               $sql = "INSERT INTO indend_raw (plant_id,user_no,request_no,no, material_type,material_subtype,material_id,material_code, req_qty, unit,
             requirement, purpose, 
             required_for, client_code,vendor_type,specific_vendor,entry_by, entry_date, 
             department,status ) VALUES( '".$_GET["plant_id"]."','".$_GET["user_no"]."','$ind_no','$no','".$temp["material_type"]."',
             '".$temp["material_subtype"]."','".$temp["id"]."','".$temp["material_code"]."','".$temp["qty"]."','".$temp["unit"]."',
             '".$temp["requirement"]."','".$temp["purpose"]."','".$temp["required_for"]."','".$temp["client_code"]."','".$temp["vendor_type"]."',
            '".$temp["vendor_no"]."','".$_GET["emp_id"]."','".$entry_date."','".$temp["department"]."' ,'TO_HOD')";

          $conn->query($sql);
           
        }
       
        echo "{\"status\":\"success\"}";
        
        
    }
    if ($_GET["type"] == "saveIndentPurchase") { 
        
        $no = date("YmdHis", $timestamp);
        
        $flag = 0;
        
        $last_id;
        
        $sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        
        // Initialize variable
        $last_id = null;
        
        // Check if a result was found
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_id = $row['request_no'];
        }
        
        // Generate the next request number
        if (empty($last_id)) {
            // First entry — start from 1
            $next_number = 1;
        } else {
            // Extract the numeric part from the existing request number
            $int_var = (int) filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
            $next_number = $int_var + 1;
        }
        
        // Pad the number with leading zeros (e.g. 1 → 0001)
        $number = str_pad($next_number, 4, '0', STR_PAD_LEFT);
        
        // Final request number format: RQ0001, RQ0002, etc.
        $request_no = "RQ" . $number;
        
         
        for ($i = 0; $i < count($input); $i++) {
            $temp = $input[$i];
            $temp["required_for"] = "Own";

            // Always send to Dept Head approval queue (/hrfordepthead/indent).
            // RM/PM Material (and Raw/Packing) -> To_HOD_RMPM; all other materials -> TO_HOD.
            $matType = isset($temp["material_type"]) ? trim($temp["material_type"]) : '';
            $materialCode = isset($temp["material_code"]) ? trim((string)$temp["material_code"]) : '';
            $matType = normalizeIndentMaterialType($conn, $matType, $materialCode);
            $matTypeLower = strtolower($matType);
            $isRmpm = in_array($matType, array('Raw Material', 'Packing Material', 'RM/PM Material'), true)
                || strpos($matTypeLower, 'raw material') !== false
                || strpos($matTypeLower, 'packing material') !== false
                || strpos($matTypeLower, 'rm/pm') !== false;
            $hodStatus = $isRmpm ? 'To_HOD_RMPM' : 'TO_HOD';

            // Prefer selected department; fall back to token department.
            $deptName = isset($temp["department"]) ? trim($temp["department"]) : '';
            if ($deptName === '' && !empty($_GET["department"])) {
                $deptName = $_GET["department"];
            }

            $materialCode = isset($temp["material_code"]) ? trim((string)$temp["material_code"]) : '';
            $materialName = isset($temp["material_name"]) ? trim((string)$temp["material_name"]) : '';
            if (strcasecmp($materialName, 'null') === 0 || strcasecmp($materialName, 'undefined') === 0) {
                $materialName = '';
            }
            // Resolve from my_view first (same source as indent material dropdown), then masters.
            if ($materialName === '' && $materialCode !== '') {
                $codeEsc = mysqli_real_escape_string($conn, $materialCode);
                foreach (array(
                    "SELECT material_name FROM my_view WHERE material_code='$codeEsc' LIMIT 1",
                    "SELECT material_name FROM material WHERE material_code='$codeEsc' LIMIT 1",
                    "SELECT material_name FROM others_material WHERE material_code='$codeEsc' LIMIT 1",
                ) as $matSql) {
                    $matRes = @$conn->query($matSql);
                    if ($matRes && $matRes->num_rows > 0) {
                        $candidate = trim((string)(($matRes->fetch_assoc())['material_name'] ?? ''));
                        if ($candidate !== '' && strcasecmp($candidate, 'null') !== 0 && strcasecmp($candidate, $materialCode) !== 0) {
                            $materialName = $candidate;
                            break;
                        }
                    }
                }
            }
            $materialNameEsc = mysqli_real_escape_string($conn, $materialName);
            $materialCodeEsc = mysqli_real_escape_string($conn, $materialCode);
            $materialSubtypeEsc = mysqli_real_escape_string($conn, isset($temp["material_subtype"]) ? (string)$temp["material_subtype"] : '');
            $materialTypeEsc = mysqli_real_escape_string($conn, $matType);
            $deptNameEsc = mysqli_real_escape_string($conn, $deptName);
            
            $sql = "INSERT INTO indend_raw (plant_id,request_no, no, material_type, material_subtype, material_code, material_name, req_qty, unit, requirement, purpose, required_for, client_code, vendor_type, specific_vendor, entry_by, entry_date, department, status ) VALUES( 
            '".$_GET["plant_id"]."', '$request_no', '$no', '".$materialTypeEsc."','".$materialSubtypeEsc."', '".$materialCodeEsc."', '".$materialNameEsc."', '".$temp["qty"]."', '".$temp["unit"]."', '".$temp["requirement"]."', '".$temp["purpose"]."', '".$temp["required_for"]."',
            '".$temp["client_code"]."', '".$temp["vendor_type"]."', '".$temp["vendor_no"]."', '".$_GET["emp_id"]."', '".$entry_date."', '".$deptNameEsc."' , '".$hodStatus."')";

            if (!$conn->query($sql)) {
                $flag++;
            }
           
        }
       
        echo $flag == 0 ? "{\"status\":\"success\"}" : "{\"status\":\"error\",\"message\":\"".str_replace('"','',$conn->error)."\"}";
        
        
    }
    if ($_GET["type"] == "saveIndentStore") { 
        
        $no = date("YmdHis", $timestamp);
        
        $flag = 0;
        
        $last_id;
        
        $sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        
        // Initialize variable
        $last_id = null;
        
        // Check if a result was found
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_id = $row['request_no'];
        }
        
        // Generate the next request number
        if (empty($last_id)) {
            // First entry — start from 1
            $next_number = 1;
        } else {
            // Extract the numeric part from the existing request number
            $int_var = (int) filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
            $next_number = $int_var + 1;
        }
        
        // Pad the number with leading zeros (e.g. 1 → 0001)
        $number = str_pad($next_number, 4, '0', STR_PAD_LEFT);
        
        // Final request number format: RQ0001, RQ0002, etc.
        $request_no = "RQ" . $number;
        
         
        for ($i = 0; $i < count($input); $i++) {
            $temp = $input[$i];
            $temp["required_for"] = "Own";
            
            $sql = "INSERT INTO indend_raw (plant_id,request_no, no, material_type, material_subtype, material_code, req_qty, unit, requirement, purpose, required_for, client_code, vendor_type, specific_vendor, entry_by, entry_date, department, status ) VALUES( 
            '".$_GET["plant_id"]."', '$request_no', '$no', '".$temp["material_type"]."','".$temp["material_subtype"]."', '".$temp["material_code"]."', '".$temp["qty"]."', '".$temp["unit"]."', '".$temp["requirement"]."', '".$temp["purpose"]."', '".$temp["required_for"]."',
            '".$temp["client_code"]."', '".$temp["vendor_type"]."', '".$temp["vendor_no"]."', '".$_GET["emp_id"]."', '".$entry_date."', '".$temp["department"]."' , 'To_HOD_RMPM')";

            $conn->query($sql);
           
        }
       
        echo "{\"status\":\"success\"}";
        
        
    }
//     if ($_GET["type"] == "savePlanningIndentStore ") {
        
       

// $input = json_decode(file_get_contents("php://input"), true);
// date_default_timezone_set('Asia/Kolkata');
// $entry_date = date("Y-m-d H:i:s");
// $timestamp = time();
// $no = date("YmdHis", $timestamp);

// //---------------------------------------
// // FUNCTION: UOM Conversion
// //---------------------------------------
// function convert_qty($qty, $unit) {

//     switch (strtoupper(trim($unit))) {

//         case "KG":
//             return $qty;  // Base unit

//         case "GM":
//         case "G":
//             return $qty * 1000;   // GM → KG

//         case "L":
//         case "LTR":
//         case "LITRE":
//             return $qty;  // Litre = KG approx (density 1)

//         case "ML":
//             return $qty * 1000;   // ML → L → KG approx

//         default:
//             return $qty;  // Unknown UOM → return as-is
//     }
// }


// //---------------------------------------
// // GENERATE REQUEST NO (RQ0001, RQ0002...)
// //---------------------------------------
// $sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
// $result = $conn->query($sql);

// $last_id = null;

// if ($result && $result->num_rows > 0) {
//     $row = $result->fetch_assoc();
//     $last_id = $row['request_no'];
// }

// if (empty($last_id)) {
//     $next_number = 1;
// } else {
//     $int_var = (int) filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
//     $next_number = $int_var + 1;
// }

// $number = str_pad($next_number, 4, '0', STR_PAD_LEFT);
// $request_no = "RQ" . $number;


// //---------------------------------------
// // INSERT MULTIPLE WOs BASED ON payload.wos
// //---------------------------------------
// foreach ($input['wos'] as $temp) {

//     //-------------------------
//     // Qty selection
//     //-------------------------
//     if ($input['category'] == 'Client') {
//         $raw_qty = $temp["Client_code_Indent"] ?? 0;
//     } else {
//         $raw_qty = $temp["Mother_code_Indent"] ?? 0;
//     }
//     if ($input['indent_type'] == 'Client Code') {
//         $material_code     = $temp["material_code"] ?? '';
//     } else {
//         $material_code     = $temp["MotherCode"] ?? '';
//     }

//     $unit = $temp["Matunit"] ?? "";   

//     //-------------------------
//     // Qty Conversion
//     //-------------------------
//     $req_qty = convert_qty($raw_qty, $unit);


//     //-------------------------
//     // SAFE FIELD EXTRACTION
//     //-------------------------
//     $material_type     = $temp["material_type"] ?? '';
//     $material_subtype  = $temp["material_subtype"] ?? '';
   
 
   
//     $purpose           = 'Work Order';
//     $required_for      = $temp["required_for"] ?? '';
//     $client_code       = $temp["client_code"] ?? '';
//     $vendor_type       = $temp["vendor_type"] ?? '';
//     $specific_vendor   = $temp["vendor_no"] ?? '';
//     $department        = $temp["department"] ?? '';
//     $WO_deductions_id        = $temp["workorder_no"] ?? '';


//     //---------------------------------------
//     // INSERT INTO indend_raw
//     //---------------------------------------
//     $sql = "
//         INSERT INTO indend_raw (
//             plant_id,
//             request_no,
//             no,
//             material_type,
//             material_subtype,
//             material_code,
//             req_qty,
//             unit,
            
//             purpose,
//             required_for,
//             client_code,
//             vendor_type,
//             specific_vendor,
//             entry_by,
//             entry_date,
//             department,
//             status,
//             WO_deductions_id
//         ) VALUES (
//             '" . $_GET["plant_id"] . "',
//             '$request_no',
//             '$no',
//             '$material_type',
//             '$material_subtype',
//             '$material_code',
//             '$req_qty',
//             '$unit',
          
//             '$purpose',
//             '$required_for',
//             '$client_code',
//             '$vendor_type',
//             '$specific_vendor',
//             '" . $_GET["emp_id"] . "',
//             '$entry_date',
//             '$department',
//             'pending',
//             '$WO_deductions_id'
//         )
//     ";

//     $conn->query($sql);
//     $input['indend_id']= $conn->insert_id;
    
//     $sql1 = "INSERT INTO mrp_raised_indnd_qty 
// (indend_id,  material_code, material_name, ordered_qty, unit, material_type, WO_deductions_id, entry_by, entry_date) 
// VALUES (
// '".$input['indend_id']."',

//   '$material_code',
// '".$input['material_name']."',
//  '$req_qty',
//  '$unit',
// '$material_type',
//     '$WO_deductions_id',
// '".$input['entry_by']."',
//   '$entry_date'
// )";
// $conn->query($sql1);
//     $sql2 = "update wo_deductions set indent_status='Raised',indent_id=".$input['indend_id']."',indent_raised_by='".$input['entry_by']."',indent_raised_on=  '$entry_date' where id='$WO_deductions_id' ";
// $conn->query($sql2);




    
    
    
// }

// echo "{\"status\":\"success\"}";

 

//     }
//     if ($_GET["type"] == "savePlanningIndentStore") {
      
       
// $input = json_decode(file_get_contents("php://input"), true);
// date_default_timezone_set('Asia/Kolkata');
// $entry_date = date("Y-m-d H:i:s");
// $timestamp = time();
// $no = date("YmdHis", $timestamp);

// /* ----------------------------------------------
//   FUNCTION: UOM Conversion
// ---------------------------------------------- */
// function convert_qty($qty, $unit) {
//     switch (strtoupper(trim($unit))) {
//         case "KG": return $qty;
//         case "GM":
//         case "G":  return $qty * 1000;
//         case "L":
//         case "LTR":
//         case "LITRE": return $qty;
//         case "ML": return $qty * 1000;
//         default: return $qty;
//     }
// }

// /* ----------------------------------------------
//   GENERATE REQUEST NO
// ---------------------------------------------- */
// $sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
// $result = $conn->query($sql);

// $last_id = null;
// if ($result && $result->num_rows > 0) {
//     $row = $result->fetch_assoc();
//     $last_id = $row['request_no'];
// }

// $next_number = empty($last_id) ? 1 : ((int) filter_var($last_id, FILTER_SANITIZE_NUMBER_INT) + 1);
// $request_no = "RQ" . str_pad($next_number, 4, '0', STR_PAD_LEFT);

// /* ----------------------------------------------
//   START TRANSACTION
// ---------------------------------------------- */
// $conn->begin_transaction();
// $all_good = true;
// $error_msg = "";

// try {

//     /* ----------------------------------------------
//       PARENT INSERT
//     ---------------------------------------------- */
//     $raw_qty = ($input['category'] == 'Client') 
//         ? ($input["total_rm_shortage"] ?? 0) 
//         : ($input["total_mc_shortage"] ?? 0);


//     $material_code = ($input['indent_type'] == 'Client Code')
//         ? ($input["material_code"] ?? '') 
//         : ($input["MotherCode"] ?? '');

//     $unit = $input["Matunit"] ?? "";
//     $req_qty = convert_qty($raw_qty, $unit);

// if($input['material_type']=='RM'){
//     $input['material_type']='Raw Material';
// }else if($input['material_type']=='PM'){
//     $input['material_type']='Packing Material';
// }
//       $sql = "
//         INSERT INTO indend_raw (
//             plant_id, request_no, no, material_type, material_subtype, 
//             material_code, req_qty, unit, purpose, required_for, 
//             client_code, entry_by, entry_date, department, status
//         ) VALUES (
//             '" . $_GET["plant_id"] . "',
//             '$request_no',
//             '$no',
//             '".$input['material_type']."',
//             '".$input['material_subtype']."',
//             '$material_code',
//             '$req_qty',
//             '$unit',
//             'Work Order',
//             '".json_encode($input["required_for"])."',
//             '".$input['client_code']."',
//             '".$_GET['emp_id']."',
//             '$entry_date',
//             'Store',
//             'pending'
//         )
//     ";

//     if (!$conn->query($sql)) {
//         throw new Exception("Parent Insert Error: " . $conn->error);
//     }

//     $indend_id = $conn->insert_id;

//     /* ----------------------------------------------
//       CHILD INSERT LOOP
//     ---------------------------------------------- */
//     foreach ($input['wos'] as $temp) {

//         $raw_qty = ($input['category'] == 'Client')
//             ? ($temp["rm_shortage"] ?? 0)
//             : ($temp["mc_shortage"] ?? 0);

//         $material_code = ($input['indent_type'] == 'Client Code')
//             ? ($temp["material_code"] ?? '') 
//             : ($temp["MotherCode"] ?? '');
            
            
//             if($temp["rm_shortage"]=='0' && $temp["mc_shortage"]=='0'){
//     $qtStatus='Booked';
//     $INStatus='Not Raised';
    
// }else{
//     $qtStatus='Not Booked';
//     $INStatus='Raised';
// }

//         $unit = $temp["Matunit"] ?? "";
//         $req_qty = convert_qty($raw_qty, $unit);

//         $indexData    = $temp ?? '';
//         $material_subtype = $temp["material_subtype"] ?? '';
//         $material_name    = $temp["material_name"] ?? '';
//         $required_for     = $temp["required_for"] ?? '';
//         $client_code      = $temp["client_code"] ?? '';
//         $workorder_no = $temp["workorder_no"] ?? '';
//         $WO_deductions_ID = $temp["workorder_ID"] ?? '';
//         $entry_by         = $_GET["emp_id"];
//         $deducted_from_RM         = $temp["used_from_RM"];
//         $deducted_from_MC         = $temp["used_from_MC"];

//         // INSERT CHILD
//         if($INStatus=='Raised'){
//             $sql1 = "
//             INSERT INTO mrp_raised_indnd_qty 
//             (indend_id, material_code, material_name, ordered_qty, unit, material_type, WO_deductions_id, entry_by, entry_date) 
//             VALUES (
//                 '$indend_id',
//                 '$material_code',
//                 '$material_name',
//                 '$req_qty',
//                 '$unit',
//                 '$material_type',
//                 '$WO_deductions_ID',
//                 '$entry_by',
//                 '$entry_date'
                
//             )
//         ";
//         if (!$conn->query($sql1)) {
//             throw new Exception("Child Insert Error: " . $conn->error);
//         }
//         }
         

// if ($temp['mc_shortage'] == $temp['rm_shortage']) {

//     $shotages = $temp['rm_shortage'];

// } else if ($temp['mc_shortage'] == 0) {

//     $shotages = $temp['rm_shortage'];

// } else if ($temp['rm_shortage'] == 0) {

//     $shotages = $temp['mc_shortage'];

// }

// $indexDataJSON = json_encode($indexData, JSON_UNESCAPED_UNICODE);
// $finalQtyStatus = $INStatus; // or $qtStatus as per your logic
//         // UPDATE WO_Deductions
//           $sql2 = "
//             UPDATE WO_deductions
//             SET 
//                 indent_status='Raised',
//                 indent_id='$indend_id',
//                 indent_no='$no',
//                 indent_raised_by='$entry_by',
//                 indent_raised_on='$entry_date',
//                 deducted_from_RM='$deducted_from_RM',
//                 deducted_from_MC='$deducted_from_MC',
//                 status='Indent Sent',
//                 indexData='$indexDataJSON ',
//                 shortage='$shotages',
//                 qty_status='$qtStatus',
//                 indent_status='$INStatus'
//             WHERE id='$WO_deductions_ID'
//         ";
//         if (!$conn->query($sql2)) {
//             throw new Exception("Update Error: " . $conn->error);
//         }
//     }

//     /* ----------------------------------------------
//       COMMIT TRANSACTION
//     ---------------------------------------------- */
//     $conn->commit();
//     echo json_encode(["status" => "success"]);

// } catch (Exception $e) {
//     $conn->rollback();
//     echo json_encode([
//         "status" => "error",
//         "message" => $e->getMessage()
//     ]);
// }

//     }


if ($_GET["type"] == "savePlanningIndentStore") {
      
    $input = json_decode(file_get_contents("php://input"), true);
    date_default_timezone_set('Asia/Kolkata');
    $entry_date = date("Y-m-d H:i:s");
    $timestamp = time();
    $no = date("YmdHis", $timestamp);
    $currentUrl = $_SERVER['REQUEST_URI'] ?? '';

    /* ----------------------------------------------
       CHECK IF INDENT IS ALREADY RAISED - PREVENT DUPLICATE INDENT
    ---------------------------------------------- */
    $workorder_nos = [];
    $material_codes = [];

    // Extract work order numbers and material codes from input
    if (isset($input['required_for']) && is_array($input['required_for'])) {
        foreach ($input['required_for'] as $req) {
            $wo_no = $req['work_order_no'] ?? $req['workorder_no'] ?? '';
            $mat_code = $req['material_code'] ?? '';
            
            if (!empty($wo_no)) {
                $workorder_nos[] = mysqli_real_escape_string($conn, $wo_no);
            }
            if (!empty($mat_code)) {
                $material_codes[] = mysqli_real_escape_string($conn, $mat_code);
            }
        }
    }

    // Also check main material_code if provided
    if (isset($input['material_code']) && !empty($input['material_code'])) {
        $material_codes[] = mysqli_real_escape_string($conn, $input['material_code']);
    }

    // Remove duplicates
    $workorder_nos = array_unique($workorder_nos);
    $material_codes = array_unique($material_codes);

    // Check if indent is already raised for any of these materials
    if (!empty($workorder_nos) && !empty($material_codes)) {
        $alreadyRaised = [];
        
        // CHECK 1: Check WO_deductions table
        $checkSql = "SELECT DISTINCT 
                        workorder_no, 
                        material_code, 
                        indent_status, 
                        indent_no, 
                        indent_id,
                        indent_raised_by,
                        indent_raised_on
                     FROM WO_deductions 
                     WHERE workorder_no IN ('" . implode("','", $workorder_nos) . "') 
                     AND material_code IN ('" . implode("','", $material_codes) . "')
                     AND shortage > 0
                     AND (indent_status = 'Raised' OR indent_status = 'Indent Sent')";
                     
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            while ($row = $checkResult->fetch_assoc()) {
                $alreadyRaised[] = [
                    'workorder_no' => $row['workorder_no'],
                    'material_code' => $row['material_code'],
                    'indent_status' => $row['indent_status'],
                    'indent_no' => $row['indent_no'],
                    'indent_id' => $row['indent_id'],
                    'indent_raised_by' => $row['indent_raised_by'],
                    'indent_raised_on' => $row['indent_raised_on'],
                    'source' => 'WO_deductions'
                ];
            }
        }
        
        // CHECK 2: Check indend_raw table
        foreach ($workorder_nos as $wo_no) {
            foreach ($material_codes as $mat_code) {
                $indentRawSql = "SELECT id, no, request_no, status, entry_date, entry_by
                               FROM indend_raw 
                               WHERE material_code = '".mysqli_real_escape_string($conn, $mat_code)."'
                               AND required_for LIKE '%\"work_order_no\":\"".mysqli_real_escape_string($conn, $wo_no)."\"%'
                               AND status != 'Rejected'
                               ORDER BY id DESC LIMIT 1";
                $indentRawResult = $conn->query($indentRawSql);
                
                if ($indentRawResult && $indentRawResult->num_rows > 0) {
                    $indentRawRow = $indentRawResult->fetch_assoc();
                    
                    $found = false;
                    foreach ($alreadyRaised as $existing) {
                        if ($existing['workorder_no'] == $wo_no && $existing['material_code'] == $mat_code) {
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $alreadyRaised[] = [
                            'workorder_no' => $wo_no,
                            'material_code' => $mat_code,
                            'indent_status' => 'Raised',
                            'indent_no' => $indentRawRow['no'] ?? '',
                            'indent_id' => $indentRawRow['id'] ?? '',
                            'request_no' => $indentRawRow['request_no'] ?? '',
                            'indent_raised_by' => $indentRawRow['entry_by'] ?? '',
                            'indent_raised_on' => $indentRawRow['entry_date'] ?? '',
                            'source' => 'indend_raw'
                        ];
                    }
                }
            }
        }
        
        // If indent is already raised, return error
        if (!empty($alreadyRaised)) {
            $errorMessages = [];
            foreach ($alreadyRaised as $raised) {
                $source = $raised['source'] ?? 'Unknown';
                $indentNo = $raised['indent_no'] ?? $raised['request_no'] ?? 'N/A';
                $errorMessages[] = "WO: {$raised['workorder_no']}, Material: {$raised['material_code']}, Indent No: {$indentNo} (Found in: {$source})";
            }
            
            $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR, frontend_url) 
                       VALUES ('FRONTEND', '".$token."', 'savePlanningIndentStore_DUPLICATE_BLOCKED', '".$entry_date."', '".$_GET["department"]."', '".$_GET["emp_id"]."', 'POST', '".$_SERVER['REMOTE_ADDR']."', '".$currentUrl."')";
            $conn->query($logSql);
            
            echo json_encode([
                'status' => 'error',
                'message' => 'Indent is already raised for the following materials. Cannot raise duplicate indent.',
                'already_raised' => $alreadyRaised,
                'details' => implode(' | ', $errorMessages),
                'error_code' => 'DUPLICATE_INDENT'
            ]);
            exit;
        }
    }

    /* ----------------------------------------------
       FUNCTION: UOM Conversion
    ---------------------------------------------- */
    function convert_qty($qty, $unit) {
        switch (strtoupper(trim($unit))) {
            case "KG": return $qty;
            case "GM":
            case "G":  return $qty * 1000;
            case "L":
            case "LTR":
            case "LITRE": return $qty;
            case "ML": return $qty * 1000;
            default: return $qty;
        }
    }

    /* ----------------------------------------------
       GENERATE REQUEST NO
    ---------------------------------------------- */
    $sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);

    $last_id = null;
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_id = $row['request_no'];
    }

    $next_number = empty($last_id) ? 1 : ((int) filter_var($last_id, FILTER_SANITIZE_NUMBER_INT) + 1);
    $request_no = "RQ" . str_pad($next_number, 4, '0', STR_PAD_LEFT);

    /* ----------------------------------------------
       START TRANSACTION
    ---------------------------------------------- */
    $conn->begin_transaction();
    $all_good = true;
    $error_msg = "";

    try {
        /* ----------------------------------------------
           PARENT INSERT - Calculate total req_qty from required_for array
        ---------------------------------------------- */
        $total_req_qty = 0;
        $unit = '';
        $material_code = '';
        $material_name = '';
        $material_type = $input['material_type'] ?? '';
        $material_subtype = $input['material_subtype'] ?? '';
        $client_code = $input['client_code'] ?? '';
        
        // Calculate total from required_for array (uses adjusted shortage from frontend)
        if (isset($input['required_for']) && is_array($input['required_for'])) {
            foreach ($input['required_for'] as $req) {
                // Use reqQty which is already the adjusted shortage (shortage - booked)
                $reqQty = (float)($req['reqQty'] ?? $req['Client_code_Indent'] ?? $req['Mother_code_Indent'] ?? 0);
                $reqUnit = $req['Matunit'] ?? '';
                
                if ($reqQty > 0) {
                    $total_req_qty += convert_qty($reqQty, $reqUnit);
                    if (empty($unit) && !empty($reqUnit)) {
                        $unit = $reqUnit;
                    }
                }
            }
        }
        
        // Fallback to material-level totals if required_for is empty
        if ($total_req_qty == 0) {
            $raw_qty = ($input['category'] == 'Client') 
                ? ($input["total_rm_shortage"] ?? 0) 
                : ($input["total_mc_shortage"] ?? 0);
            $unit = $input["Matunit"] ?? "";
            $total_req_qty = convert_qty($raw_qty, $unit);
        }
        
        // Determine material_code based on indent_type
        if (isset($input['indent_type']) && $input['indent_type'] == 'Mother Code') {
            $material_code = $input["MotherCode"] ?? '';
        } else {
            $material_code = $input["material_code"] ?? '';
        }
        
        // Get material_name and material_type from material/others_material tables if not provided
        if (!empty($material_code)) {
            // Try material table first
            $matSql = "SELECT material_name, material_type FROM material WHERE material_code = '".mysqli_real_escape_string($conn, $material_code)."' LIMIT 1";
            $matResult = $conn->query($matSql);
            if ($matResult && $matResult->num_rows > 0) {
                $matRow = $matResult->fetch_assoc();
                if (empty($material_name)) {
                    $material_name = $matRow['material_name'] ?? '';
                }
                if (empty($material_type)) {
                    $material_type = $matRow['material_type'] ?? '';
                }
            }
            
            // Try others_material if not found in material table
            if (empty($material_name) || empty($material_type)) {
                $matSql2 = "SELECT material_name, material_type FROM others_material WHERE material_code = '".mysqli_real_escape_string($conn, $material_code)."' LIMIT 1";
                $matResult2 = $conn->query($matSql2);
                if ($matResult2 && $matResult2->num_rows > 0) {
                    $matRow2 = $matResult2->fetch_assoc();
                    if (empty($material_name)) {
                        $material_name = $matRow2['material_name'] ?? '';
                    }
                    if (empty($material_type)) {
                        $material_type = $matRow2['material_type'] ?? '';
                    }
                }
            }
        }
        
        // Use input values if available (override fetched values)
        if (!empty($input['material_name'])) {
            $material_name = $input['material_name'];
        }
        if (!empty($input['material_type'])) {
            $material_type = $input['material_type'];
        }
        
        // Normalize material_type
        if ($material_type == 'RM') {
            $material_type = 'Raw Material';
        } else if ($material_type == 'PM') {
            $material_type = 'Packing Material';
        }
        
        // Ensure material_type is set
        if (empty($material_type)) {
            $material_type = 'Raw Material'; // Default
        }

        // INSERT INTO indend_raw with ALL columns filled including indend_type2 = material_type
        $sql = "
            INSERT INTO indend_raw (
                plant_id, 
                request_no, 
                no, 
                material_type, 
                material_subtype, 
                material_code, 
                material_name,
                req_qty, 
                unit, 
                purpose, 
                required_for, 
                client_code, 
                entry_by, 
                entry_date, 
                department, 
                status,
                requirement,
                expected_vendor,
                indend_type2
            ) VALUES (
                '" . mysqli_real_escape_string($conn, $_GET["plant_id"] ?? '') . "',
                '$request_no',
                '$no',
                '" . mysqli_real_escape_string($conn, $material_type) . "',
                '" . mysqli_real_escape_string($conn, $material_subtype) . "',
                '" . mysqli_real_escape_string($conn, $material_code) . "',
                '" . mysqli_real_escape_string($conn, $material_name) . "',
                '$total_req_qty',
                '" . mysqli_real_escape_string($conn, $unit) . "',
                'Work Order',
                '" . mysqli_real_escape_string($conn, json_encode($input["required_for"] ?? [], JSON_UNESCAPED_UNICODE)) . "',
                '" . mysqli_real_escape_string($conn, $client_code) . "',
                '" . mysqli_real_escape_string($conn, $_GET['emp_id'] ?? '') . "',
                '$entry_date',
                'Store',
                'pending',
                'Planning MRP',
                '',
                '" . mysqli_real_escape_string($conn, $material_type) . "'
            )
        ";

        if (!$conn->query($sql)) {
            throw new Exception("Parent Insert Error: " . $conn->error);
        }

        $indend_id = $conn->insert_id;

        /* ----------------------------------------------
           CHILD INSERT LOOP - Process each work order
        ---------------------------------------------- */
        if (isset($input['required_for']) && is_array($input['required_for'])) {
            foreach ($input['required_for'] as $req) {
                // Use reqQty which is already adjusted shortage (shortage - booked)
                $raw_qty = (float)($req['reqQty'] ?? 0);
                
                // Determine material_code based on indent_type
                if (isset($input['indent_type']) && $input['indent_type'] == 'Mother Code') {
                    $mat_code = $req["MotherCode"] ?? '';
                } else {
                    $mat_code = $req["material_code"] ?? '';
                }
                
                // Get material_name and material_type for child record from material/others_material
                $mat_name = $req["material_name"] ?? $material_name ?? '';
                $mat_type = $req["material_type"] ?? $material_type ?? '';
                
                if (!empty($mat_code)) {
                    // Try material table first
                    $matSql = "SELECT material_name, material_type FROM material WHERE material_code = '".mysqli_real_escape_string($conn, $mat_code)."' LIMIT 1";
                    $matResult = $conn->query($matSql);
                    if ($matResult && $matResult->num_rows > 0) {
                        $matRow = $matResult->fetch_assoc();
                        if (empty($mat_name)) {
                            $mat_name = $matRow['material_name'] ?? '';
                        }
                        if (empty($mat_type)) {
                            $mat_type = $matRow['material_type'] ?? '';
                        }
                    }
                    
                    // Try others_material if not found
                    if (empty($mat_name) || empty($mat_type)) {
                        $matSql2 = "SELECT material_name, material_type FROM others_material WHERE material_code = '".mysqli_real_escape_string($conn, $mat_code)."' LIMIT 1";
                        $matResult2 = $conn->query($matSql2);
                        if ($matResult2 && $matResult2->num_rows > 0) {
                            $matRow2 = $matResult2->fetch_assoc();
                            if (empty($mat_name)) {
                                $mat_name = $matRow2['material_name'] ?? '';
                            }
                            if (empty($mat_type)) {
                                $mat_type = $matRow2['material_type'] ?? '';
                            }
                        }
                    }
                }
                
                // Normalize material_type
                if ($mat_type == 'RM') {
                    $mat_type = 'Raw Material';
                } else if ($mat_type == 'PM') {
                    $mat_type = 'Packing Material';
                }
                
                // Use parent material_type if still empty
                if (empty($mat_type)) {
                    $mat_type = $material_type;
                }
                
                $unit = $req["Matunit"] ?? $unit ?? "";
                $req_qty = convert_qty($raw_qty, $unit);
                
                // Determine status
                $rm_shortage = (float)($req['rm_shortage'] ?? $req['base_shortage'] ?? 0);
                $mc_shortage = (float)($req['mc_shortage'] ?? 0);
                
                if ($rm_shortage == 0 && $mc_shortage == 0) {
                    $qtStatus = 'Booked';
                    $INStatus = 'Not Raised';
                } else {
                    $qtStatus = 'Not Booked';
                    $INStatus = 'Raised';
                }
                
                $workorder_no = $req["work_order_no"] ?? $req["workorder_no"] ?? '';
                $WO_deductions_ID = $req["workorder_ID"] ?? $req["wo_deduction_id"] ?? '';
                $deducted_from_RM = (float)($req["deducted_from_RM"] ?? $req["used_from_RM"] ?? 0);
                $deducted_from_MC = (float)($req["deducted_from_MC"] ?? $req["used_from_MC"] ?? 0);
                
                // Calculate shortage (use adjusted shortage if available, otherwise calculate)
                if (isset($req['base_shortage']) && isset($req['booked_qty_for_wo_product'])) {
                    $shotages = max(0, (float)$req['base_shortage'] - (float)$req['booked_qty_for_wo_product']);
                } else if (isset($req['mc_shortage']) && isset($req['rm_shortage'])) {
                    if ($req['mc_shortage'] == $req['rm_shortage']) {
                        $shotages = (float)($req['rm_shortage'] ?? 0);
                    } else if ($req['mc_shortage'] == 0) {
                        $shotages = (float)($req['rm_shortage'] ?? 0);
                    } else if ($req['rm_shortage'] == 0) {
                        $shotages = (float)($req['mc_shortage'] ?? 0);
                    } else {
                        $shotages = $raw_qty; // Use the reqQty which is already adjusted
                    }
                } else {
                    $shotages = $raw_qty; // Use the reqQty which is already adjusted
                }
                
                $indexData = $req;
                $indexDataJSON = json_encode($indexData, JSON_UNESCAPED_UNICODE);

                // INSERT CHILD (mrp_raised_indnd_qty)
                if ($INStatus == 'Raised' && $req_qty > 0) {
                    $sql1 = "
                        INSERT INTO mrp_raised_indnd_qty 
                        (indend_id, material_code, material_name, ordered_qty, unit, material_type, WO_deductions_id, entry_by, entry_date) 
                        VALUES (
                            '$indend_id',
                            '" . mysqli_real_escape_string($conn, $mat_code) . "',
                            '" . mysqli_real_escape_string($conn, $mat_name) . "',
                            '$req_qty',
                            '" . mysqli_real_escape_string($conn, $unit) . "',
                            '" . mysqli_real_escape_string($conn, $mat_type) . "',
                            '" . mysqli_real_escape_string($conn, $WO_deductions_ID) . "',
                            '" . mysqli_real_escape_string($conn, $_GET['emp_id'] ?? '') . "',
                            '$entry_date'
                        )
                    ";
                    if (!$conn->query($sql1)) {
                        throw new Exception("Child Insert Error: " . $conn->error);
                    }
                }

                // UPDATE WO_Deductions
                if (!empty($WO_deductions_ID)) {
                    $sql2 = "
                        UPDATE WO_deductions
                        SET 
                            indent_status = '$INStatus',
                            indent_id = '$indend_id',
                            indent_no = '$no',
                            indent_raised_by = '" . mysqli_real_escape_string($conn, $_GET['emp_id'] ?? '') . "',
                            indent_raised_on = '$entry_date',
                            deducted_from_RM = '$deducted_from_RM',
                            deducted_from_MC = '$deducted_from_MC',
                            status = 'Indent Sent',
                            indexData = '" . mysqli_real_escape_string($conn, $indexDataJSON) . "',
                            shortage = '$shotages',
                            qty_status = '$qtStatus'
                        WHERE id = '" . mysqli_real_escape_string($conn, $WO_deductions_ID) . "'
                    ";
                    if (!$conn->query($sql2)) {
                        throw new Exception("Update Error: " . $conn->error);
                    }
                }
            }
        }

        /* ----------------------------------------------
           COMMIT TRANSACTION
        ---------------------------------------------- */
        $conn->commit();
        echo json_encode([
            "status" => "success",
            "indent_id" => $indend_id,
            "indent_no" => $no,
            "request_no" => $request_no,
            "message" => "Indent raised successfully"
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
}


    if ($_GET["type"] == "saveIndentfromdept") { 
        $no = date("YmdHis", $timestamp);
        $flag = 0;
         $last_id;
        $sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['request_no'];
        }
        if($last_id==null){ 
            $last_id=1;
        }
        else {
            $int_var = (int)filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
             $last_id = $int_var+1;
        }
        
        
        $number = substr(str_repeat(0, 4).$last_id, - 4);
         
        $ind_no = "RQ".$number;
        
         
         for ($i = 0; $i < count($input); $i++) {
            $temp = $input[$i];
            $temp["required_for"] = "Own";
            
               $sql = "INSERT INTO indend_raw (plant_id,user_no,request_no,no, material_type,material_subtype,material_id,material_code, req_qty, unit,
             requirement, purpose, 
             required_for, client_code,vendor_type,specific_vendor,entry_by, entry_date, 
             department,status ) VALUES( '".$_GET["plant_id"]."','".$_GET["user_no"]."','$ind_no','$no','".$temp["material_type"]."',
             '".$temp["material_subtype"]."','".$temp["id"]."','".$temp["material_code"]."','".$temp["qty"]."','".$temp["unit"]."',
             '".$temp["requirement"]."','".$temp["purpose"]."','".$temp["required_for"]."','".$temp["client_code"]."','".$temp["vendor_type"]."',
            '".$temp["vendor_no"]."','".$_GET["emp_id"]."','".$entry_date."','".$temp["department"]."' ,'TO_HOD')";

          $conn->query($sql);
           
        }
       
        echo "{\"status\":\"success\"}";
        
        
    }
 
        else if ($_GET["type"] == "ReviseapproveIndend") {
            
            
            
        $no = date("YmdHis", $timestamp);
        
         $last_id;
        $sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['request_no'];
        }
        if($last_id==null){ 
            $last_id=1;
        }
        else {
            $int_var = (int)filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
             $last_id = $int_var+1;
        }
        
        
        $number = substr(str_repeat(0, 4).$last_id, - 4);
         
        $rqnop = "RQ".$number;
            
             
                $flag = 0;
                for($k = 0; $k < count($input); $k++) {
                    $mat_type = $input[$k]['material_type'];
                  
                    $materials = $input[$k]['materials'];
                    $sql = "SELECT max(indend_no) as indend_no FROM indend_raw   ORDER BY id DESC";
                    
                    $result = $conn->query($sql);
                    while($row = $result->fetch_assoc()){
                          $last_id = $row['indend_no'];
                    }
                    if($last_id==null){
                        $last_id=1;
                    }else{
                        $int_var = (int)filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
                        //  echo $int_var;
                        $last_id = $int_var+1;
                    }
                    $number = substr(str_repeat(0, 4).$last_id, - 4);
                    
                    $ind_no = "IN".$number;    
               
                    
                    for($i = 0; $i < count($materials); $i++) {
                         $indend = $materials[$i];
                         
            $sql = "INSERT INTO indend_raw (plant_id,user_no,request_no,no, material_type,material_subtype,material_id,material_code, 
            req_qty, unit,requirement, purpose,required_for, client_code,vendor_type,specific_vendor,entry_by, entry_date,department,
            indend_no,vendor_no,quotation_no,currency,quotation_amt,quotation_per,order_qty,gst,gross_total,gst_total,net_total,
            status,approve_by,approve_date,amendment,vp_approved_on,vp_approved_by,director_approved_on,director_approved_by,approve_plantHead_on
            ,approve_plantHead_by,po_no_revised) VALUES( '".$_GET["plant_id"]."','".$_GET["user_no"]."','$rqnop','$no',
              '".$indend["material_type"]."','".$indend["material_subtype"]."','".$indend["material_id"]."','".$indend["material_code"]."',
            '".$indend["req_qty"]."','".$indend["unit"]."','".$indend["requirement"]."','".$indend["purpose"]."','".$indend["required_for"]."',
            '".$indend["client_code"]."','".$indend["vendor_type"]."','".$indend["specific_vendor"]."','".$_GET["emp_id"]."','".$entry_date."',
            '".$indend["department"]."' , '".$ind_no."','".$indend["vendor_no"]."','".$indend["quotation_no"]."',
            '".$indend["currency"]."','".$indend["quotation_amt"]."','".$indend["quotation_per"]."','".$indend["order_qty"]."',
            '".$indend["gst_per"]."','".$indend["gross_total"]."', '".$indend["gst_total"]."', '".$indend["net_total"]."',
            '".$_GET["status"]."','".$_GET["emp_id"]."','$entry_date','YES','$entry_date',
            '".$indend["vp_approved_by"]."','$entry_date','".$indend["director_approved_by"]."','$entry_date','".$indend["approve_plantHead_by"]."','".$_GET["po_no"]."')";
                         
                       
                          
                         if ($conn->query($sql)) {
                            $flag =0;
                        } else {
                            $flag ++;
                        }
                    }
                     
                    
                }
                if ($flag == 0) {
                    echo "{\"status\":\"success\"}";
                    
                    $sql1 = "UPDATE purchaseorder  SET status ='Revised' WHERE id='".$_GET["po_id"]."' ";
                    $conn->query($sql1);
                    
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
        
        
        
    } 

   else if ($_GET["type"] == "HosaveIndent") { 
        $no = date("YmdHis", $timestamp);
        $flag = 0;
         $last_id;
        $sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['request_no'];
        }
        if($last_id==null){ 
            $last_id=1;
        }
        else {
            $int_var = (int)filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
             $last_id = $int_var+1;
        }
        
        
        $number = substr(str_repeat(0, 4).$last_id, - 4);
         
        $ind_no = "RQ".$number;
        
         
         for ($i = 0; $i < count($input); $i++) {
            $temp = $input[$i];
            $temp["required_for"] = "Own";
            
               $sql = "INSERT INTO indend_raw (plant_id,user_no,request_no,no, material_type,material_subtype,material_id,material_code, req_qty, unit,
             requirement, purpose, 
             required_for, client_code,vendor_type,specific_vendor,entry_by, entry_date, 
             department,status ) VALUES( '".$_GET["plantID"]."','".$_GET["user_no"]."','$ind_no','$no','".$temp["material_type"]."',
             '".$temp["material_subtype"]."','".$temp["id"]."','".$temp["material_code"]."','".$temp["qty"]."','".$temp["unit"]."',
             '".$temp["requirement"]."','".$temp["purpose"]."','".$temp["required_for"]."','".$temp["client_code"]."','".$temp["vendor_type"]."',
            '".$temp["vendor_no"]."','".$_GET["emp_id"]."','".$entry_date."','".$temp["department"]."' ,'pending')";

          $conn->query($sql);
           
        }
       
        echo "{\"status\":\"success\"}";
        
        
    }
   else if ($_GET["type"] == "HosaveIndentNootan") { 
        $no = date("YmdHis", $timestamp);
        $flag = 0;
         $last_id;
        $sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['request_no'];
        }
        if($last_id==null){ 
            $last_id=1;
        }
        else {
            $int_var = (int)filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
             $last_id = $int_var+1;
        }
        
        
        $number = substr(str_repeat(0, 4).$last_id, - 4);
         
        $ind_no = "RQ".$number;
        
         
         for ($i = 0; $i < count($input); $i++) {
            $temp = $input[$i];
            $temp["required_for"] = "Own";
            
               $sql = "INSERT INTO indend_raw (plant_id,user_no,request_no,no, material_type,material_subtype,material_id,material_code, req_qty, unit,
             requirement, purpose, 
             required_for, client_code,vendor_type,specific_vendor,entry_by, entry_date, 
             department,status,indentDate ) VALUES( '".$_GET["plantID"]."','".$_GET["user_no"]."','$ind_no','$no','".$temp["material_type"]."',
             '".$temp["material_subtype"]."','".$temp["id"]."','".$temp["material_code"]."','".$temp["qty"]."','".$temp["unit"]."',
             '".$temp["requirement"]."','".$temp["purpose"]."','".$temp["required_for"]."','".$temp["client_code"]."','".$temp["vendor_type"]."',
            '".$temp["vendor_no"]."','".$_GET["emp_id"]."','".$entry_date."','".$temp["department"]."' ,'pending','".$temp["indentDate"]."' )";

          $conn->query($sql);
           
        }
       
        echo "{\"status\":\"success\"}";
        
        
    }
    else if ($_GET["type"] == "autoPurchaseSaveIndent") { 
        $no = date("YmdHis", $timestamp);
        $flag = 0;
         $last_id;
        $sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['request_no'];
        }
        if($last_id==null){ 
            $last_id=1;
        }
        else {
            $int_var = (int)filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
             $last_id = $int_var+1;
        }
        
        
        $number = substr(str_repeat(0, 4).$last_id, - 4);
         
        $ind_no = "RQ".$number;
        
         
         for ($i = 0; $i < count($input); $i++) {
            $temp = $input[$i];
            $temp["required_for"] = "Client";
            
               $sql = "INSERT INTO indend_raw (plant_id,user_no,request_no,no, material_type,material_subtype,material_id,material_code, req_qty, unit,
             requirement, purpose, 
             required_for, client_code,vendor_type,specific_vendor,entry_by, entry_date, 
             department,status ) VALUES( '".$_GET["plant_id"]."','".$_GET["user_no"]."','$ind_no','$no','".$temp["material_type"]."',
             '".$temp["material_subtype"]."','".$temp["id"]."','".$temp["material_code"]."','".$temp["required_qty1"]."','".$temp["required_qty_unit"]."',
             '".$temp["requirement"]."','".$temp["purpose"]."','".$temp["required_for"]."','".$temp["client_code"]."','".$temp["vendor_type"]."',
            '".$temp["vendor_no"]."','".$_GET["emp_id"]."','".$entry_date."','Store' ,'To_Store_Head')";

           $conn->query($sql);
           
        }
       
        echo "{\"status\":\"success\"}";
        
        
    }
    
     if ($_GET["type"] == "getShortageIndent") {
        $output = Array();
        $sql = "SELECT a.*,b.material_name FROM indend_raw a left join material b on a.material_code=b.material_code where a.required_for!='Own'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["required_for"] = json_decode($row["required_for"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "CompletIndentReqAnalyasis") {
      
      $status1 = false;
      
          $json_obj = json_encode($input["filtersFO"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                $sql = "UPDATE order_materials  SET reqStatus = 'Complete' where id = '".$values['pid']."' ";
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
    
    
    else if ($_GET["type"] == "saveIndentMerge") { 
        $no = date("YmdHis", $timestamp);
        $flag = 0;
         $last_id;
        $sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['request_no'];
        }
        if($last_id==null){ 
            $last_id=1;
        }
        else {
            $int_var = (int)filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
             $last_id = $int_var+1;
        }
        
        
        $number = substr(str_repeat(0, 4).$last_id, - 4);
         
        $ind_no = "RQ".$number;
         $jadugar = 0;
         
         for ($i = 0; $i < count($input['final_material']); $i++) {
            $temp = $input['final_material'][$i];
            $temp["required_for"] = "Own";
            
               $sql = "INSERT INTO indend_raw (plant_id,user_no,request_no,no, material_type,material_subtype,material_code, req_qty, unit,
             requirement, purpose, 
             required_for, mergeFrom,vendor_type,specific_vendor,entry_by, entry_date, 
             department ) VALUES( '".$_GET["plant_id"]."','".$_GET["user_no"]."','$ind_no','$no','".$temp["material_type"]."',
             '".$temp["material_subtype"]."','".$temp["material_code"]."','".$temp["req_qty"]."','".$temp["unit"]."',
             '".$temp["requirement"]."','".$temp["purpose"]."','".$temp["required_for"]."','MergeFromIndent','".$temp["vendor_type"]."',
            '".$temp["specific_vendor"]."','".$_GET["emp_id"]."','".$entry_date."','".$temp["department"]."' )";

                if($conn->query($sql)){
            	        $jadugar++;
            	} else {
            	    $jadugar = 0;
            	}
           
        }
        
        
        if($jadugar>0){
            for ($i = 0; $i < count($input['meargeList']); $i++) {
                $temp = $input['meargeList'][$i];
              $sql = "UPDATE indend_raw SET status = 'Merge' , mergeIndNo = '$ind_no' where id = '".$temp["id"]."'";
              $conn->query($sql);
            }
           
            echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    
    else if ($_GET["type"] == "getCheckedIndendsForMerge") {
 
        $output = Array();
        
          $sql = "SELECT i.specific_vendor,m.material_subtype,i.entry_by,i.entry_date,i.id,i.no,i.request_no,i.indend_no,i.material_code,i.req_qty,i.unit,i.status,
          i.department,m.material_name,m.material_subtype,m.material_type FROM indend_raw i left join my_view m on m.material_code = i.material_code
          WHERE i.status ='pending' and i.plant_id='".$_GET["plant_id"]."' order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                  
            $sql8 = "SELECT id FROM indend_raw   WHERE  material_code='".$row["material_code"]."' AND status='pending' ";
            
                 $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $output8[] = $row8;
                    }
                }
                
                if(count($output8) > 1){
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
        else if ($_GET["type"] == "getCheckedIndendsForMergeLog") {
 
        $output = Array();
        
          $sql = "SELECT i.specific_vendor,m.material_subtype,i.entry_by,i.entry_date,i.id,i.no,i.request_no,i.indend_no,i.material_code,i.req_qty,i.unit,i.status,
          i.department,m.material_name,m.material_subtype,m.material_type FROM indend_raw i left join my_view m on m.material_code = i.material_code
          WHERE i.mergeFrom ='MergeFromIndent' and i.plant_id='".$_GET["plant_id"]."' order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                 
            $output8 = array();
                  
                 $sql8 = "SELECT i.specific_vendor,m.material_subtype,i.entry_by,i.entry_date,i.id,i.no,i.request_no,i.indend_no,i.material_code,i.req_qty,i.unit,i.status,
          i.department,m.material_name,m.material_subtype,m.material_type FROM indend_raw i left join my_view m on m.material_code = i.material_code
          WHERE i.status ='Merge' and i.mergeIndNo='".$row["request_no"]."' order by id desc";
            
                 $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $output8[] = $row8;
                    }
                }
                 
                    $row['oldIndent'] = $output8;
                
                    $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    
    else if ($_GET["type"] == "correcetion_indend") {
        $temp=$input;
          $no = date("YmdHis", $timestamp);

        $sql = "INSERT INTO indend_raw (plant_id,user_no,request_no,no, material_type,material_subtype,material_id,material_code, req_qty, unit, requirement, purpose, 
             required_for, client_code,vendor_type,vendor_no,entry_by, entry_date, 
             department,tax_percent,tax,purchase_type,order_type ) VALUES( '".$_GET["plant_id"]."','".$_GET["user_no"]."','".$temp["request_no"]."','$no','".$temp["material_type"]."',
             '".$temp["material_subtype"]."','".$temp["material_code"]["id"]."','".$temp["material_code"]["material_code"]."','".$temp["qty"]."','".$temp["unit"]."',
             '".$temp["requirement"]."','".$temp["purpose"]."','".$temp["required_for"]."','".$temp["client_code"]."','".$temp["vendor_type"]."',
            '".$temp["vendor_no"]."','".$_GET["emp_id"]."','".$entry_date."','".$temp["department"]."','".$temp["tax_percent"]."','".$temp["tax"]."','".$temp["purchase_type"]."','".$temp["order_type"]."' )";

        
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
     
}
    else if ($_GET["type"] == "getPendingIndends") {
        $output = array();
    //   $sql = "SELECT i.*, m.material_subtype, m.material_name, m.grade FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.department='".$_GET["department"]."' AND i.status='approve'";
        $sql = "SELECT i.*, m.material_subtype, m.material_name, m.grade FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.department='".$_GET["department"]."' AND i.status='pending'";
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateIndend") {
        $sql = "UPDATE indend_raw SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getIndendsLog") {
        $output = array();
        $sql = "SELECT i.*, m.material_subtype, m.material_name, m.grade FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getMaterials") {
        $output = array();
        $sql = "SELECT i.*, m.material_subtype, m.material_name, m.grade FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code GROUP BY i.material_code";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "DelIndend") {
    $sql = "DELETE FROM indend_raw   WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
 }
 
 
 
    else if ($_GET["type"] == "getCheckedIndends") {
 
        $output = Array();
        
          $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
          max(entry_by) as entry_by, max(status) as status, request_no  FROM indend_raw i
        WHERE status ='pending' and i.plant_id='".$_GET["plant_id"]."'  AND NOT ".indentRmpmMaterialWhere('i')."
        GROUP BY request_no order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                
                  
             $sql8 = "SELECT distinct v.vendor_name, i.*,m.material_name,m.grade  FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code
            LEFT JOIN vendor v ON i.specific_vendor=v.vendor_no WHERE i.no='".$row["no"]."' and i.status='pending' and (i.material_type!='' or i.material_type!=NULL) limit 20";
            
                
                
                 $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $lowest = array();
                        
                        
            $q= "SELECT GROUP_CONCAT(grade)  as grade FROM    grade where id in ('".  $row8['grade']."')";
            $resQ = $conn->query($q);
            $prodLatest = $resQ->fetch_assoc();
            $row8['grade'] = $prodLatest['grade']; 
                        
                        
                        $highest = array();
        
                        $lowest["rate"] = 0;
                        $highest["rate"] = 0;
                        $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row8["material_code"]."'"; //ORDER BY id DESC";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.challan_no='".$row1["challan_no"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row["last_purchase"] = $row2["vendor_no"];
                                        
                                        $flag = 0;
                                        if ($lowest["rate"] < $lowest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $lowest["rate"] = $row1["rate"];
                                            $lowest["vendor_name"] = $row2["vendor_name"];
                                            $lowest["vendor_no"] = $row2["vendor_no"];
                                            $lowest["challan_no"] = $row1["challan_no"];
                                            $lowest["challan_date"] = $row2["challan_date"];
                                        }
                                        
                                        $flag = 0;
                                        if ($highest["rate"] < $highest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $highest["rate"] = $row1["rate"];
                                            $highest["vendor_name"] = $row2["vendor_name"];
                                            $highest["vendor_no"] = $row2["vendor_no"];
                                            $highest["challan_no"] = $row1["challan_no"];
                                            $highest["challan_date"] = $row2["challan_date"];
                                        }
                                    }
                                }
                            }
                        } else {
                            $row8["last_purchase"] = "";
                        }
                        
                        $row8["lowest"] = $lowest;
                        $row8["highest"] = $highest;
                        
                        $output1 = Array();
                       // $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["material_code"]."%' AND q.status='approve'";
                        $sql1 = "SELECT a.id,a.quotation_no,c.vendor_no,c.vendor_name,b.gst_per,
                        b.material_code, b.pack_size,b.quotation_type,b.quotation_amt,b.quotation_per from quotation_dtl b
                        JOIN quotation_hdr a on b.quotation_hdr_id = a.id LEFT JOIN vendor c on a.vendor_id = c.id 
                         where a.status='approve' and b.material_code = '".$row8["material_code"]."' ";    
                        // echo $sql1;
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                if ($row1["vendor_no"] == $row["expected_vendor"]) {
                                    $row1["last_purchase"] = "yes";
                                } else {
                                    $row1["last_purchase"] = "no";
                                }
                                $output1[] = $row1;
                                 
                            }
                        }
                      
                        
                        $row8["vendors"] = $output1;
                        
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 
    else if ($_GET["type"] == "getIndentForAmendments") {
 
        $output = Array();
        
           $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
          max(entry_by) as entry_by, max(status) as status, request_no  FROM indend_raw i
        WHERE   i.indend_no='".$_GET["indend_no"]."'
        GROUP BY request_no order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                
                
                
                
                $eq = "Select pm.material_code From po_material pm left join purchaseorder p ON p.id = pm.po_no where  p.po_no='".$_GET["po_no"]."'";
                
        $res = $conn->query($eq);
        if($res->num_rows > 0) {
            while($ro = $res->fetch_assoc()) {
                
                                  
            $sql8 = "SELECT distinct v.vendor_name, i.*,m.material_name,m.grade  FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code
            LEFT JOIN vendor v ON i.specific_vendor=v.vendor_no WHERE i.no='".$row["no"]."' AND  i.material_code = '".$ro["material_code"]."'   and (i.material_type!='' or i.material_type!=NULL) limit 20";
                
                 $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $lowest = array();
                        
                        
                        
                        $highest = array();
        
                        $lowest["rate"] = 0;
                        $highest["rate"] = 0;
                        $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row8["material_code"]."'"; //ORDER BY id DESC";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.challan_no='".$row1["challan_no"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row["last_purchase"] = $row2["vendor_no"];
                                        
                                        $flag = 0;
                                        if ($lowest["rate"] < $lowest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $lowest["rate"] = $row1["rate"];
                                            $lowest["vendor_name"] = $row2["vendor_name"];
                                            $lowest["vendor_no"] = $row2["vendor_no"];
                                            $lowest["challan_no"] = $row1["challan_no"];
                                            $lowest["challan_date"] = $row2["challan_date"];
                                        }
                                        
                                        $flag = 0;
                                        if ($highest["rate"] < $highest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $highest["rate"] = $row1["rate"];
                                            $highest["vendor_name"] = $row2["vendor_name"];
                                            $highest["vendor_no"] = $row2["vendor_no"];
                                            $highest["challan_no"] = $row1["challan_no"];
                                            $highest["challan_date"] = $row2["challan_date"];
                                        }
                                    }
                                }
                            }
                        } else {
                            $row8["last_purchase"] = "";
                        }
                        
                        $row8["lowest"] = $lowest;
                        $row8["highest"] = $highest;
                        
                        $output1 = Array();
                       // $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["material_code"]."%' AND q.status='approve'";
                        $sql1 = "SELECT a.id,a.quotation_no,c.vendor_no,c.vendor_name,b.gst_per,
                        b.material_code, b.pack_size,b.quotation_type,b.quotation_amt,b.quotation_per from quotation_dtl b
                        JOIN quotation_hdr a on b.quotation_hdr_id = a.id LEFT JOIN vendor c on a.vendor_id = c.id 
                         where a.status='approve' and b.material_code = '".$row8["material_code"]."' ";    
                        // echo $sql1;
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                if ($row1["vendor_no"] == $row["expected_vendor"]) {
                                    $row1["last_purchase"] = "yes";
                                } else {
                                    $row1["last_purchase"] = "no";
                                }
                                $output1[] = $row1;
                                 
                            }
                        }
                      
                        
                        $row8["vendors"] = $output1;
                        
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                
                
            }
        }
        
                 
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "getCheckedIndendsRMPM") {
 
        $output = Array();
        
        
        indentEnsurePrNotesColumn($conn);

        $notesSelect = '';
        $notesCheck = @$conn->query("SHOW COLUMNS FROM indend_raw LIKE 'pr_notes'");
        if ($notesCheck && $notesCheck->num_rows > 0) {
            $notesSelect = ", max(pr_notes) as pr_notes";
        }

        $rmpm_status_filter = "(i.status IN ('pending','To_HOD_RMPM','To_Store_Head') OR (i.status = 'approve' AND (i.indend_no IS NULL OR i.indend_no = '')))";
        $gen_status_filter = "(i.status IN ('pending','TO_HOD','TO_PlantHead') OR (i.status = 'approve' AND (i.indend_no IS NULL OR i.indend_no = '')))";

        if($_GET['For'] == 'RMPM'){
            $statusFilter = $rmpm_status_filter;
            $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
            max(entry_by) as entry_by, max(status) as status".$notesSelect.", request_no  FROM indend_raw i
            WHERE ".$statusFilter." and i.plant_id='".$_GET["plant_id"]."' AND ".indentRmpmMaterialWhere('i')."  GROUP BY request_no ORDER BY MAX(i.id) DESC";
            
        }else{
            $statusFilter = $gen_status_filter;
            $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
            max(entry_by) as entry_by, max(status) as status".$notesSelect.", request_no  FROM indend_raw i
            WHERE ".$statusFilter." and i.plant_id='".$_GET["plant_id"]."' AND NOT ".indentRmpmMaterialWhere('i')."  GROUP BY request_no ORDER BY MAX(i.id) DESC";
            
        }

        
        $result = $conn->query($sql);
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                 
                $output8 = array();
                $reqEsc = mysqli_real_escape_string($conn, (string)$row["request_no"]);
                $noEsc = mysqli_real_escape_string($conn, (string)$row["no"]);
                  
             $sql8 = "SELECT v.vendor_name, i.*,
                    COALESCE(
                        NULLIF(TRIM(i.material_name), ''),
                        NULLIF(TRIM(m.material_name), ''),
                        NULLIF(TRIM(mat.material_name), ''),
                        NULLIF(TRIM(om.material_name), ''),
                        NULLIF(TRIM(ch.chemical_name), ''),
                        NULLIF(TRIM(gm.material_name), ''),
                        i.material_code
                    ) AS material_name,
                    m.grade AS view_grade, mat.grade AS mat_grade, om.grade AS om_grade, ch.grade AS ch_grade
                    FROM indend_raw i
                    LEFT JOIN my_view m ON i.material_code = m.material_code
                    LEFT JOIN material mat ON i.material_code = mat.material_code
                    LEFT JOIN others_material om ON i.material_code = om.material_code
                    LEFT JOIN chemical ch ON i.material_code = ch.chemical_no
                    LEFT JOIN general_material gm ON i.material_code = gm.material_code
                    LEFT JOIN vendor v ON i.specific_vendor = v.vendor_no
                    WHERE i.plant_id='".$_GET["plant_id"]."' AND ".$statusFilter."
                    AND (i.request_no = '".$reqEsc."'".($noEsc !== '' ? " OR i.no = '".$noEsc."'" : "").")";
            
                $result8 = $conn->query($sql8);
                if ($result8 && $result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $gradeRaw = isset($row8['view_grade']) ? trim((string)$row8['view_grade']) : '';
                        if ($gradeRaw === '' && isset($row8['mat_grade'])) {
                            $gradeRaw = trim((string)$row8['mat_grade']);
                        }
                        if ($gradeRaw === '' && isset($row8['om_grade'])) {
                            $gradeRaw = trim((string)$row8['om_grade']);
                        }
                        if ($gradeRaw === '' && isset($row8['ch_grade'])) {
                            $gradeRaw = trim((string)$row8['ch_grade']);
                        }
                        if ($gradeRaw === '' && isset($row8['grade'])) {
                            $gradeRaw = trim((string)$row8['grade']);
                        }
                        $row8['grade'] = '';
                        if ($gradeRaw !== '') {
                            $idPart = preg_replace('/[^0-9,]/', '', $gradeRaw);
                            if ($idPart !== '' && preg_match('/^\d+(,\d+)*$/', $idPart)) {
                                $resQ = $conn->query("SELECT GROUP_CONCAT(grade) AS grade FROM grade WHERE id IN (".$idPart.")");
                                if ($resQ && ($g = $resQ->fetch_assoc()) && !empty($g['grade'])) {
                                    $row8['grade'] = $g['grade'];
                                }
                            }
                            if ($row8['grade'] === '') {
                                $row8['grade'] = $gradeRaw;
                            }
                        }
                        unset($row8['view_grade'], $row8['mat_grade'], $row8['om_grade'], $row8['ch_grade']);
                        $output1 = Array();
                        $sql1 = "SELECT a.id as quotId,a.material_code,a.quotation_amt,a.quotation_per,a.currency,a.gst_per,a.pack_size,a.pack_unit,b.quotation_no,b.vendor_quotation_no,b.vendor_id,c.vendor_no,c.vendor_name FROM quotation_dtl a 
                        left join quotation_hdr b on a.quotation_hdr_id = b.id LEFT JOIN vendor c on b.vendor_id = c.id   WHERE b.status = 'approve' AND a.material_code = '".$row8["material_code"]."' "; 
                          
                        $result1 = $conn->query($sql1);
                        if ($result1 && $result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                        
                          $row8["required_for"] = json_decode($row8["required_for"]);    
                        $row8["vendors"] = $output1;
                        
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                $st = strtoupper(trim((string)($row['status'] ?? '')));
                if ($st === 'TO_PLANTHEAD' || $st === 'TO_HOD' || $st === 'TO_HOD_RMPM' || $st === 'TO_STORE_HEAD') {
                    $row['status'] = 'pending';
                }
                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "HOgetCheckedIndends") {
 
        $output = Array();
        
        
        
          $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
          max(entry_by) as entry_by, max(status) as status, request_no  FROM indend_raw i
        WHERE status ='pending' and i.plant_id='".$_GET["plantID"]."'   GROUP BY request_no order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                 
                $output8 = array();
                
                  
             $sql8 = "SELECT distinct v.vendor_name, i.*,m.material_name,m.grade  FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code
            LEFT JOIN vendor v ON i.specific_vendor=v.vendor_no WHERE i.no='".$row["no"]."' and i.status='pending' and (i.material_type!='' or i.material_type!=NULL) limit 20";
            
                
                
                 $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $lowest = array();
                        
                                                
            $q= "SELECT GROUP_CONCAT(grade)  as grade FROM    grade where id in ('".  $row8['grade']."')";
            $resQ = $conn->query($q);
            $prodLatest = $resQ->fetch_assoc();
            $row8['grade'] = $prodLatest['grade']; 
                        
                        $highest = array();
        
                        $lowest["rate"] = 0;
                        $highest["rate"] = 0;
                        $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row8["material_code"]."'"; //ORDER BY id DESC";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.challan_no='".$row1["challan_no"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row["last_purchase"] = $row2["vendor_no"];
                                        
                                        $flag = 0;
                                        if ($lowest["rate"] < $lowest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $lowest["rate"] = $row1["rate"];
                                            $lowest["vendor_name"] = $row2["vendor_name"];
                                            $lowest["vendor_no"] = $row2["vendor_no"];
                                            $lowest["challan_no"] = $row1["challan_no"];
                                            $lowest["challan_date"] = $row2["challan_date"];
                                        }
                                        
                                        $flag = 0;
                                        if ($highest["rate"] < $highest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $highest["rate"] = $row1["rate"];
                                            $highest["vendor_name"] = $row2["vendor_name"];
                                            $highest["vendor_no"] = $row2["vendor_no"];
                                            $highest["challan_no"] = $row1["challan_no"];
                                            $highest["challan_date"] = $row2["challan_date"];
                                        }
                                    }
                                }
                            }
                        } else {
                            $row8["last_purchase"] = "";
                        }
                        
                        $row8["lowest"] = $lowest;
                        $row8["highest"] = $highest;
                        
                        $output1 = Array();
                       // $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["material_code"]."%' AND q.status='approve'";
                        $sql1 = "SELECT a.id,a.quotation_no,c.vendor_no,c.vendor_name,b.gst_per,
                        b.material_code, b.pack_size,b.quotation_type,b.quotation_amt,b.quotation_per from quotation_dtl b
                        JOIN quotation_hdr a on b.quotation_hdr_id = a.id LEFT JOIN vendor c on a.vendor_id = c.id 
                         where a.status='approve' and b.material_code = '".$row8["material_code"]."' ";    
                        // echo $sql1;
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                if ($row1["vendor_no"] == $row["expected_vendor"]) {
                                    $row1["last_purchase"] = "yes";
                                } else {
                                    $row1["last_purchase"] = "no";
                                }
                                $output1[] = $row1;
                                 
                            }
                        }
                      
                        
                        $row8["vendors"] = $output1;
                        
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                                
                 $sql12="SELECT  plant_name  FROM plant where plant_id = '".$_GET['plantID']."' ";
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
    
    
    else if ($_GET["type"] == "getIndentByDept") {
        if($_GET["department_name"]=='R AND D'){
            $_GET["department_name"]='Product Development';
        }
 
        $output = Array();
        
         $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
          max(entry_by) as entry_by, max(status) as status, request_no  FROM indend_raw i
        WHERE   i.plant_id='".$_GET["plant_id"]."' AND  i.department='".$_GET["department_name"]."'
        GROUP BY request_no order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
            
                
            $output8 = array();
           $sql8 = "SELECT distinct v.vendor_name, i.*,m.material_name,m.grade FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code
            LEFT JOIN vendor v ON i.specific_vendor=v.vendor_no WHERE i.no='".$row["no"]."' and  (i.material_type!='' or i.material_type!=NULL) limit 20";
                
                 $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        
           $q= "SELECT GROUP_CONCAT(grade)  as grade FROM grade where id in ('".  $row8['grade']."')";
            $resQ = $conn->query($q);
            $prodLatest = $resQ->fetch_assoc();
            $row8['grade'] = $prodLatest['grade']; 
                        
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getIndentByplantHead") {
 
        $output = Array();
        
          $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
          max(entry_by) as entry_by, max(status) as status, request_no  FROM indend_raw i
        WHERE   i.plant_id='".$_GET["plant_id"]."'  
        GROUP BY request_no order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                
                
                
                
            
                
                
                
                
                
                
            $output8 = array();
            $noEsc = mysqli_real_escape_string($conn, (string)$row["no"]);
            $sql8 = "SELECT DISTINCT v.vendor_name, i.*,
                COALESCE(
                    NULLIF(TRIM(i.material_name), ''),
                    NULLIF(TRIM(m.material_name), ''),
                    NULLIF(TRIM(mat.material_name), ''),
                    NULLIF(TRIM(om.material_name), ''),
                    NULLIF(TRIM(ch.chemical_name), ''),
                    NULLIF(TRIM(gm.material_name), ''),
                    i.material_code
                ) AS material_name,
                m.grade AS view_grade, mat.grade AS mat_grade, om.grade AS om_grade, ch.grade AS ch_grade
                FROM indend_raw i
                LEFT JOIN my_view m ON i.material_code = m.material_code
                LEFT JOIN material mat ON i.material_code = mat.material_code
                LEFT JOIN others_material om ON i.material_code = om.material_code
                LEFT JOIN chemical ch ON i.material_code = ch.chemical_no
                LEFT JOIN general_material gm ON i.material_code = gm.material_code
                LEFT JOIN vendor v ON i.specific_vendor = v.vendor_no
                WHERE i.no='".$noEsc."' LIMIT 20";
                
                 $result8 = $conn->query($sql8);
                if ($result8 && $result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $gradeRaw = isset($row8['view_grade']) ? trim((string)$row8['view_grade']) : '';
                        if ($gradeRaw === '' && isset($row8['mat_grade'])) {
                            $gradeRaw = trim((string)$row8['mat_grade']);
                        }
                        if ($gradeRaw === '' && isset($row8['om_grade'])) {
                            $gradeRaw = trim((string)$row8['om_grade']);
                        }
                        if ($gradeRaw === '' && isset($row8['ch_grade'])) {
                            $gradeRaw = trim((string)$row8['ch_grade']);
                        }
                        if ($gradeRaw === '' && isset($row8['grade'])) {
                            $gradeRaw = trim((string)$row8['grade']);
                        }
                        $row8['grade'] = '';
                        if ($gradeRaw !== '') {
                            $idPart = preg_replace('/[^0-9,]/', '', $gradeRaw);
                            if ($idPart !== '' && preg_match('/^\d+(,\d+)*$/', $idPart)) {
                                $resQ = $conn->query("SELECT GROUP_CONCAT(grade) AS grade FROM grade WHERE id IN (".$idPart.")");
                                if ($resQ && ($g = $resQ->fetch_assoc()) && !empty($g['grade'])) {
                                    $row8['grade'] = $g['grade'];
                                }
                            }
                            if ($row8['grade'] === '') {
                                $row8['grade'] = $gradeRaw;
                            }
                        }
                        unset($row8['view_grade'], $row8['mat_grade'], $row8['om_grade'], $row8['ch_grade']);
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getIndentForApproval") {
 
        $output = Array();
        
          $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
          max(entry_by) as entry_by, max(status) as status, request_no  FROM indend_raw i
        WHERE   i.plant_id='".$_GET["plant_id"]."' AND  i.department= '".$_GET["department_name"]."' AND  status = '".$_GET["status"]."'
        GROUP BY request_no order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
            $output8 = array();
            $sql8 = "SELECT distinct v.vendor_name, i.*,m.material_name,m.grade  FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code
            LEFT JOIN vendor v ON i.specific_vendor=v.vendor_no WHERE i.no='".$row["no"]."' AND i.status = '".$_GET["status"]."' and  (i.material_type!='' or i.material_type!=NULL) limit 20";
                
                 $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
            $q= "SELECT GROUP_CONCAT(grade)  as grade FROM    grade where id in ('".  $row8['grade']."')";
            $resQ = $conn->query($q);
            $prodLatest = $resQ->fetch_assoc();
            $row8['grade'] = $prodLatest['grade']; 
                        
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getIndentForApprovalDeptApproval") {
 
        $output = Array();

        
        if($_GET["department_name"] == 'Quality Assurance'){
            
        $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
        max(purpose) as plant_head_remark,
        max(entry_by) as entry_by, max(status) as status, request_no  FROM indend_raw i
        WHERE   i.plant_id='".$_GET["plant_id"]."'   
         AND ( i.status = '".$_GET["status"]."' &&  i.department = 'Quality Assurance' ) OR (  i.department = 'Quality Assurance' OR  i.status = 'Revert_To_QA' )
        GROUP BY request_no order by id desc";
            
        }
        else if($_GET["department_name"] == 'Engineering'){
            
                      $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
                      max(purpose) as plant_head_remark,
          max(entry_by) as entry_by, max(status) as status, request_no  FROM indend_raw i
        WHERE   i.plant_id='".$_GET["plant_id"]."'   
        AND ( i.status = '".$_GET["status"]."' &&  i.department = 'Engineering' ) OR (  i.department = 'Engineering' OR  i.status = 'Revert_To_Engg' ) or (  i.material_type = 'Equipments')
        GROUP BY request_no order by id desc";
            
        }else{
            
                      $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,max(purpose) as plant_head_remark,
          max(entry_by) as entry_by, max(status) as status, request_no  FROM indend_raw i
        WHERE   i.plant_id='".$_GET["plant_id"]."' AND  i.department= '".$_GET["department_name"]."' AND ( status = '".$_GET["status"]."' OR  status = 'Revert_To_Dept_Head' ) AND i.material_type != 'Equipments'
        GROUP BY request_no order by id desc";
            
        }
        
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
            $output8 = array();
            
            
        if($_GET["department_name"] == 'Quality Assurance'){
            
            
        $sql8 = "SELECT distinct v.vendor_name, i.*,m.material_name,m.grade  FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code
        LEFT JOIN vendor v ON i.specific_vendor=v.vendor_no WHERE i.no='".$row["no"]."' AND ( i.status = '".$_GET["status"]."' OR  i.status = 'Revert_To_QA' ) and  (i.material_type!='' or i.material_type!=NULL) limit 20";

    
            
        }
        else if($_GET["department_name"] == 'Engineering'){
            
               $sql8 = "SELECT distinct v.vendor_name, i.*,m.material_name,m.grade  FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code
            LEFT JOIN vendor v ON i.specific_vendor=v.vendor_no WHERE i.no='".$row["no"]."' AND ( i.status = '".$_GET["status"]."' OR  i.status = 'Revert_To_Engg' ) and  (i.material_type!='' or i.material_type!=NULL) limit 20";

            
        }else{
             
                        $sql8 = "SELECT distinct v.vendor_name, i.*,m.material_name,m.grade  FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code
            LEFT JOIN vendor v ON i.specific_vendor=v.vendor_no WHERE i.no='".$row["no"]."' AND ( i.status = '".$_GET["status"]."' OR  i.status = 'Revert_To_Dept_Head' ) and  (i.material_type!='' or i.material_type!=NULL) limit 20";

        }
            

                
                 $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
            $q= "SELECT GROUP_CONCAT(grade)  as grade FROM  grade where id in ('".  $row8['grade']."')";
            $resQ = $conn->query($q);
            $prodLatest = $resQ->fetch_assoc();
            $row8['grade'] = $prodLatest['grade']; 
                        
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getIndentForStoreApproval") {
 
        $output = Array();
        
         $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
          max(entry_by) as entry_by, max(status) as status, request_no  FROM indend_raw i
        WHERE   i.plant_id='".$_GET["plant_id"]."' AND   status = '".$_GET["status"] ."'
        GROUP BY request_no order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
            $output8 = array();
            $sql8 = "SELECT distinct v.vendor_name, i.*,m.material_name,pr.grade  FROM indend_raw i LEFT JOIN my_view m ON 
            i.material_code=m.material_code LEFT JOIN vendor v ON i.specific_vendor=v.vendor_no  LEFT JOIN material pr ON pr.material_code = i.material_code   LEFT JOIN rnd_material rm ON rm.material_code = i.material_code WHERE i.no='".$row["no"]."' AND 
            i.status =  '".$_GET["status"] ."' and  (i.material_type!='' or i.material_type!=NULL) limit 20";
                
                 $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        
                        
            $q= "SELECT GROUP_CONCAT(grade)  as grade FROM    grade where id in ('".  $row8['grade']."')";
            $resQ = $conn->query($q);
            $prodLatest = $resQ->fetch_assoc();
            $row8['grade'] = $prodLatest['grade']; 
                        
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getIndentForApprovalPlantHead") {
 
        $output = Array();
        $plantId = mysqli_real_escape_string($conn, (string)$_GET["plant_id"]);
        $plantHeadStatuses = "('TO_PlantHead','To_PlantHead')";
        
          $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date ,
          max(entry_by) as entry_by, max(status) as status,max(purpose) as plant_head_remark,max(dept_head_remark) as dept_head_remark, request_no  FROM indend_raw i
        WHERE i.plant_id='".$plantId."' AND (i.status IN ".$plantHeadStatuses." OR UPPER(TRIM(i.status))='TO_PLANTHEAD')
        AND NOT ".indentRmpmMaterialWhere('i')."
        GROUP BY request_no order by max(id) desc";
        
        $result = $conn->query($sql);
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
            $output8 = array();
            $requestNoEsc = mysqli_real_escape_string($conn, (string)$row["request_no"]);
            $sql8 = "SELECT DISTINCT v.vendor_name, i.*,
                COALESCE(NULLIF(TRIM(i.material_name), ''), NULLIF(TRIM(m.material_name), ''), NULLIF(TRIM(mat.material_name), ''), NULLIF(TRIM(om.material_name), ''), NULLIF(TRIM(ch.chemical_name), ''), NULLIF(TRIM(gm.material_name), ''), i.material_code) AS material_name,
                m.grade AS view_grade, mat.grade AS mat_grade, om.grade AS om_grade, ch.grade AS ch_grade
FROM indend_raw i
LEFT JOIN my_view m ON i.material_code = m.material_code
LEFT JOIN material mat ON i.material_code = mat.material_code
LEFT JOIN others_material om ON i.material_code = om.material_code
LEFT JOIN chemical ch ON i.material_code = ch.chemical_no
LEFT JOIN general_material gm ON i.material_code = gm.material_code
LEFT JOIN vendor v ON i.specific_vendor = v.vendor_no
WHERE i.request_no = '".$requestNoEsc."'
  AND (i.status IN ".$plantHeadStatuses." OR UPPER(TRIM(i.status))='TO_PLANTHEAD')
  AND NOT ".indentRmpmMaterialWhere('i')."
LIMIT 50";
                
                 $result8 = $conn->query($sql8);
                if ($result8 && $result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $gradeRaw = isset($row8['view_grade']) ? trim((string)$row8['view_grade']) : '';
                        if ($gradeRaw === '' && isset($row8['mat_grade'])) {
                            $gradeRaw = trim((string)$row8['mat_grade']);
                        }
                        if ($gradeRaw === '' && isset($row8['om_grade'])) {
                            $gradeRaw = trim((string)$row8['om_grade']);
                        }
                        if ($gradeRaw === '' && isset($row8['ch_grade'])) {
                            $gradeRaw = trim((string)$row8['ch_grade']);
                        }
                        if ($gradeRaw === '' && isset($row8['grade'])) {
                            $gradeRaw = trim((string)$row8['grade']);
                        }
                        $row8['grade'] = '';
                        if ($gradeRaw !== '') {
                            $idPart = preg_replace('/[^0-9,]/', '', $gradeRaw);
                            if ($idPart !== '' && preg_match('/^\d+(,\d+)*$/', $idPart)) {
                                $resQ = $conn->query("SELECT GROUP_CONCAT(grade) AS grade FROM grade WHERE id IN (".$idPart.")");
                                if ($resQ && ($g = $resQ->fetch_assoc()) && !empty($g['grade'])) {
                                    $row8['grade'] = $g['grade'];
                                }
                            }
                            if ($row8['grade'] === '') {
                                $row8['grade'] = $gradeRaw;
                            }
                        }
                        $output8[] = $row8;
                    }
                }
                if (count($output8) === 0) {
                    continue;
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getCheckedIndendsForNotification") {
 
        $output = Array();
        
          $sql = "SELECT count(request_no) as Pending_indent   FROM indend_raw WHERE status ='pending' and plant_id='".$_GET["plant_id"]."'";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
   
                $output = $row;
            }
        }
         $test = "You Have '".$output['Pending_indent']."' Indent Pending For Approval";
            $output['text'] = $test;
    
    echo json_encode($output);
    }

    else if ($_GET["type"] == "getCheckedIndendForCorrection") {
 
        $output = Array();
        
          $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(department) as department,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
          max(entry_by) as entry_by,        max(approve_by) as approve_by, max(approve_date) as approve_date,
        max(approve_plantHead_by) as approve_plantHead_by, max(approve_plantHead_on) as approve_plantHead_on,  max(status) as status, request_no  FROM indend_raw i
        WHERE status ='Rejected' and i.plant_id='".$_GET["plant_id"]."'
        GROUP BY request_no order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                
                  
            $sql8 = "SELECT distinct v.vendor_name, i.*,m.material_name,m.grade  FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code
            LEFT JOIN vendor v ON i.specific_vendor=v.vendor_no WHERE i.no='".$row["no"]."' and i.status='Rejected' and (i.material_type!='' or i.material_type!=NULL) limit 20";
            
                
                
                 $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $lowest = array();
                        
                        
                        
                        $highest = array();
        
                        $lowest["rate"] = 0;
                        $highest["rate"] = 0;
                        $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row8["material_code"]."'"; //ORDER BY id DESC";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.challan_no='".$row1["challan_no"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row["last_purchase"] = $row2["vendor_no"];
                                        
                                        $flag = 0;
                                        if ($lowest["rate"] < $lowest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $lowest["rate"] = $row1["rate"];
                                            $lowest["vendor_name"] = $row2["vendor_name"];
                                            $lowest["vendor_no"] = $row2["vendor_no"];
                                            $lowest["challan_no"] = $row1["challan_no"];
                                            $lowest["challan_date"] = $row2["challan_date"];
                                        }
                                        
                                        $flag = 0;
                                        if ($highest["rate"] < $highest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $highest["rate"] = $row1["rate"];
                                            $highest["vendor_name"] = $row2["vendor_name"];
                                            $highest["vendor_no"] = $row2["vendor_no"];
                                            $highest["challan_no"] = $row1["challan_no"];
                                            $highest["challan_date"] = $row2["challan_date"];
                                        }
                                    }
                                }
                            }
                        } else {
                            $row8["last_purchase"] = "";
                        }
                        
                        $row8["lowest"] = $lowest;
                        $row8["highest"] = $highest;
                        
                        $output1 = Array();
                       // $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["material_code"]."%' AND q.status='approve'";
                        $sql1 = "SELECT a.id,a.quotation_no,c.vendor_no,c.vendor_name,b.gst_per,
                        b.material_code, b.pack_size,b.quotation_type,b.quotation_amt,b.quotation_per from quotation_dtl b
                        JOIN quotation_hdr a on b.quotation_hdr_id = a.id LEFT JOIN vendor c on a.vendor_id = c.id 
                         where a.status='approve' and b.material_code = '".$row8["material_code"]."' ";    
                        // echo $sql1;
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                if ($row1["vendor_no"] == $row["expected_vendor"]) {
                                    $row1["last_purchase"] = "yes";
                                } else {
                                    $row1["last_purchase"] = "no";
                                }
                                $output1[] = $row1;
                                 
                            }
                        }
                      
                        
                        $row8["vendors"] = $output1;
                        
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    
    else if ($_GET["type"] == "getRejectedIndents") {
        $output = Array();
        $sql = "SELECT max(i.id) as id,i.indend_no,i.no,i.entry_date,i.entry_by,i.status FROM indend_raw i
        WHERE i.user_no='".$_GET["user_no"]."' AND i.status='reject'
        GROUP BY i.indend_no,i.no,i.entry_date,i.entry_by ,i.status ORDER BY max(i.id) DESC";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                //$sql8 = "SELECT i.*,m.material_name FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.no='".$row["no"]."' ";
                $sql8 = "SELECT i.*,m.material_name FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.no='".$row["no"]."' and i.status='reject' ";
                $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $lowest = array();
                        $highest = array();
        
                        $lowest["rate"] = 0;
                        $highest["rate"] = 0;
                        $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row8["material_code"]."' ORDER BY id DESC";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.challan_no='".$row1["challan_no"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row["last_purchase"] = $row2["vendor_no"];
                                        
                                        $flag = 0;
                                        if ($lowest["rate"] < $lowest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $lowest["rate"] = $row1["rate"];
                                            $lowest["vendor_name"] = $row2["vendor_name"];
                                            $lowest["vendor_no"] = $row2["vendor_no"];
                                            $lowest["challan_no"] = $row1["challan_no"];
                                            $lowest["challan_date"] = $row2["challan_date"];
                                        }
                                        
                                        $flag = 0;
                                        if ($highest["rate"] < $highest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $highest["rate"] = $row1["rate"];
                                            $highest["vendor_name"] = $row2["vendor_name"];
                                            $highest["vendor_no"] = $row2["vendor_no"];
                                            $highest["challan_no"] = $row1["challan_no"];
                                            $highest["challan_date"] = $row2["challan_date"];
                                        }
                                    }
                                }
                            }
                        } else {
                            $row8["last_purchase"] = "";
                        }
                        
                        $row8["lowest"] = $lowest;
                        $row8["highest"] = $highest;
                        
                        $output1 = Array();
                       // $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["material_code"]."%' AND q.status='approve'";
                        $sql1 = "SELECT a.id,a.quotation_no,c.vendor_no,c.vendor_name,b.gst_per,b.material_code, b.quotation_type,b.quotation_amt,b.quotation_per from quotation_dtl b
                        JOIN quotation_hdr a on b.quotation_hdr_id = a.id LEFT JOIN vendor c on a.vendor_id = c.id and a.plant_id = c.plant_id
                         where a.status='approve' and b.material_code = '".$row8["material_code"]."' ";    
                       //  echo $sql1
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                if ($row1["vendor_no"] == $row["expected_vendor"]) {
                                    $row1["last_purchase"] = "yes";
                                } else {
                                    $row1["last_purchase"] = "no";
                                }
                                $output1[] = $row1;
                                 
                            }
                        }
                        
                        $row8["vendors"] = $output1;
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "approveIndend") {
        
      
                $flag = 0;
                indentEnsurePrNotesColumn($conn);
                $prNotes = '';
                if (isset($input[0]['notes'])) {
                    $prNotes = mysqli_real_escape_string($conn, trim((string)$input[0]['notes']));
                }
                for($k = 0; $k < count($input); $k++) {
                    $mat_type = $input[$k]['material_type'];
                    if (!isset($input[$k]['materials'])) {
                        continue;
                    }
                  
                    $materials = $input[$k]['materials'];
                    $sql = "SELECT max(indend_no) as indend_no FROM indend_raw   ORDER BY id DESC";
                    
                    $result = $conn->query($sql);
                    while($row = $result->fetch_assoc()){
                          $last_id = $row['indend_no'];
                    }
                    if($last_id==null){
                        $last_id=1;
                    }else{
                        $int_var = (int)filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
                        $last_id = $int_var+1;
                    }
                    $number = substr(str_repeat(0, 4).$last_id, - 4);
                    $ind_no = "IN".$number;    
               
                    
                    for($i = 0; $i < count($materials); $i++) {
                        $indend = $materials[$i];
                        $approveStatus = $_GET["status"];
                        $poIndendSql = '';
                        if ($approveStatus === 'approve') {
                            $poIndendSql = ", po_indend='pending'";
                        } else if ($approveStatus === 'Rejected') {
                            $poIndendSql = ", po_indend=''";
                        }
                                    
                        $sql = "UPDATE indend_raw SET indend_no = '".$ind_no."', vendor_no = '".$indend["vendor_no"]."', quotation_no = '".$indend["quotation_no"]."', currency = '".$indend["currency"]."', 
                        quotation_amt = '".$indend["quotation_amt"]."', quotation_per = '".$indend["quotation_per"]."', order_qty = '".$indend["order_qty"]."', gst = '".$indend["gst_per"]."', gross_total = '".$indend["gross_total"]."', 
                        gst_total = '".$indend["gst_total"]."', net_total = '".$indend["net_total"]."',status = '".$approveStatus."', approve_by = '".$_GET["emp_id"]."', approve_date = '$entry_date'".$poIndendSql." WHERE id = '".$indend["id"]."'";
                        if ($prNotes !== '') {
                            $sqlNotes = "UPDATE indend_raw SET pr_notes = '".$prNotes."' WHERE id = '".$indend["id"]."'";
                            @$conn->query($sqlNotes);
                        }
                        
                        if ($conn->query($sql)) {
                            $flag =0;
                        } else {
                            $flag ++;
                        }
                    }
                }
                
                
                if ($flag == 0) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
        
        
        
    } 
    
    
    else if ($_GET["type"] == "approveIndendOLD") {
        
      
                $flag = 0;
                for($k = 0; $k < count($input); $k++) {
                    $mat_type = $input[$k]['material_type'];
                  
                    $materials = $input[$k]['materials'];
                    $sql = "SELECT max(indend_no) as indend_no FROM indend_raw   ORDER BY id DESC";
                    
                    $result = $conn->query($sql);
                    while($row = $result->fetch_assoc()){
                          $last_id = $row['indend_no'];
                    }
                    if($last_id==null){
                        $last_id=1;
                    }else{
                        $int_var = (int)filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
                        //  echo $int_var;
                        $last_id = $int_var+1;
                    }
                    $number = substr(str_repeat(0, 4).$last_id, - 4);
                    
                    
                    
                  
                    $ind_no = "IN".$number;    
               
                    
                    for($i = 0; $i < count($materials); $i++) {
                         $indend = $materials[$i];
                         
                if($indend['jadu'] == 'YES'){
                             
                             
                     $sql1 = "INSERT INTO indend_raw (plant_id,user_no,indend_no,request_no,no, material_type,material_subtype,material_id,
                    material_code, req_qty, unit,requirement, purpose,required_for, client_code,vendor_type,specific_vendor,entry_by, entry_date, 
                    department,status,vendor_no ,quotation_no,currency,quotation_amt,quotation_per,gst_total,net_total,approve_by,approve_date,
                    order_qty,gst,gross_total) VALUES( '".$_GET["plant_id"]."','".$_GET["user_no"]."','$ind_no','".$indend["request_no"]."','".$indend["no"]."',
                    '".$indend["material_type"]."','".$indend["material_subtype"]."','".$indend["material_id"]."','".$indend["material_code"]."',
                    '".$indend["req_qty"]."','".$indend["unit"]."','".$indend["requirement"]."','".$indend["purpose"]."','".$indend["required_for"]."',
                    '".$indend["client_code"]."','".$indend["vendor_type"]."','".$indend["specific_vendor"]."','".$_GET["emp_id"]."','".$entry_date."',
                    '".$indend["department"]."' ,'".$_GET["status"]."','".$indend["vendor_no"]."','".$indend["quotation_no"]."',
                    '".$indend["currency"]."','".$indend["quotation_amt"]."','".$indend["quotation_per"]."','".$indend["gst_total"]."',
                    '".$indend["net_total"]."','".$_GET["emp_id"]."','$entry_date' ,'".$indend["order_qty"]."','".$indend["gst"]."','".$indend["gross_total"]."')";
                    
                              $conn->query($sql1);
                             
                             
                }else{
                             
                                    
                        $sql = "UPDATE indend_raw SET indend_no='".$ind_no."', vendor_no='".$indend["vendor_no"]."',
                        quotation_no='".$indend["quotation_no"]."', currency='".$indend["currency"]."', 
                        quotation_amt='".$indend["quotation_amt"]."',
                        quotation_per='".$indend["quotation_per"]."', order_qty='".$indend["order_qty"]."', gst='".$indend["gst_per"]."', gross_total='".$indend["gross_total"]."', 
                        gst_total='".$indend["gst_total"]."', net_total='".$indend["net_total"]."',status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', 
                        approve_date='$entry_date' WHERE id='".$indend["id"]."'";
                             
               }
                         
                    
                       // echo $sql;
                         
                         if ($conn->query($sql)) {
                            $flag =0;
                        } else {
                            $flag ++;
                        }
                    }
                }
                if ($flag == 0) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
        
        
        
    } 
    
    
    else if ($_GET["type"] == "approveIndentFromdept") {
        
           $flag = 0;
                $json_obj = json_encode($input["materials"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                    $status ='';
                    
                    if($_GET['status'] == 'Rejected'){
                        $status ='Rejected';
                    }else{
                        $matType = isset($values['material_type']) ? trim($values['material_type']) : '';
                        $matTypeLower = strtolower($matType);
                        $isRmpm = in_array($matType, array('Raw Material', 'Packing Material', 'RM/PM Material'), true)
                            || strpos($matTypeLower, 'raw material') !== false
                            || strpos($matTypeLower, 'packing material') !== false
                            || strpos($matTypeLower, 'rm/pm') !== false;
                        if($isRmpm){
                            $status = 'pending';
                        }else if($_GET['status']=='TO_QA_MANAGER'){
                            $status = 'TO_QA_MANAGER';
                        }else {
                            $status = 'TO_PlantHead';
                        }
                        
                    }
                             $sql = "UPDATE indend_raw SET status='".$status."',dept_head_remark='".$input["hodRemark"]."',req_qty='".$values["req_qty"]."', approve_hod_by='".$_GET["emp_id"]."', approve_hod_on='$entry_date' 
                            WHERE id='".$values["id"]."'";
                         
                         if ($conn->query($sql)) {
                            $flag =0;
                        } else {
                            $flag ++;
                        }
                    
                }
                if ($flag == 0) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
    } 
    else if ($_GET["type"] == "approveIndentFromdeptmisll") {
        
           $flag = 0;
                $json_obj = json_encode($input["materials"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                     
                    
                             $sql = "UPDATE indend_raw SET status='".$_GET['status']."',dept_head_remark='".$input['dept_head_remark']."', approve_hod_by='".$_GET["emp_id"]."', approve_hod_on='$entry_date' 
                            WHERE id='".$values["id"]."'";
                         
                         if ($conn->query($sql)) {
                            $flag =0;
                        } else {
                            $flag ++;
                        }
                    
                }
                if ($flag == 0) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
    } 
    else if ($_GET["type"] == "approveIndentFromStoreDeptHead") {
        
           $flag = 0;
                $json_obj = json_encode($input["materials"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                             $sql = "UPDATE indend_raw SET status='".$_GET['status']."', approve_hod_by='".$_GET["emp_id"]."', approve_hod_on='$entry_date' ,req_qty='".$values["req_qty"]."'
                            WHERE id='".$values["id"]."'";
                         
                         if ($conn->query($sql)) {
                            $flag =0;
                        } else {
                            $flag ++;
                        }
                }
                if ($flag == 0) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
    } 
    else if ($_GET["type"] == "approveIndentFromQAManager") {
        
           $flag = 0;
                $json_obj = json_encode($input["materials"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                             $sql = "UPDATE indend_raw SET status='".$_GET['status']."', qa_manager_by='".$_GET["emp_id"]."', qa_manager_on='$entry_date' 
                            WHERE id='".$values["id"]."'";
                         
                         if ($conn->query($sql)) {
                            $flag =0;
                        } else {
                            $flag ++;
                        }
                }
                if ($flag == 0) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
    } 
  
    else if ($_GET["type"] == "approveIndentFromplandHead") {
        
           $flag = false;
           $materials = (isset($input["materials"]) && is_array($input["materials"])) ? $input["materials"] : array();
           if (count($materials) === 0) {
               echo "{\"status\":\"error\",\"message\":\"No materials to approve\"}";
               exit;
           }
           $remark = isset($input["remark"]) ? mysqli_real_escape_string($conn, (string)$input["remark"]) : '';
           $approveStatus = mysqli_real_escape_string($conn, (string)$_GET["status"]);
              $array = json_decode(json_encode($materials), true);
                foreach ($array as $values)
                {
                    $sql = "UPDATE indend_raw SET status='".$approveStatus."',req_qty='".$values["req_qty"]."',purpose='".$remark."', approve_plantHead_by='".$_GET["emp_id"]."', approve_plantHead_on='$entry_date' 
                    WHERE id='".$values["id"]."'";
                         
                    if ($conn->query($sql)) {
                        $flag = true;
                    } else {
                        $flag = false;
                    }
                    
                }
                
                
                if ($flag) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
        
        
        
    } 
    else if ($_GET["type"] == "sendIndentForPoPreparation") {
        
        $flag = false;
        $json_obj = json_encode($input["materials"]);
        $array = json_decode($json_obj, true);
 
        foreach ($array as $values){
            
            $sql = "UPDATE indend_raw SET status = 'pending', req_qty = '".$values["req_qty"]."', qa_manager_by = '".$_GET["emp_id"]."', qa_manager_on = '$entry_date'  WHERE id='".$values["id"]."'";
                 
            if ($conn->query($sql)) {
                $flag = true;
            } else {
                $flag = false;
            }
            
        }
        
        
        if ($flag) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
                
    } 
    else if ($_GET["type"] == "approveIndentFromDirector") {
        
           $flag = 0;
                $json_obj = json_encode($input["materials"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                             $sql = "UPDATE indend_raw SET status='".$_GET["status"]."', director_approved_by='".$_GET["emp_id"]."',
                             director_approved_on='$entry_date' WHERE id='".$values["id"]."'";
                         
                         if ($conn->query($sql)) {
                            $flag =0;
                        } else {
                            $flag ++;
                        }
                    
                }
                
                
                if ($flag == 0) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
        
        
        
    } 
    else if ($_GET["type"] == "approveIndentFromVp") {
        
           $flag = 0;
                $json_obj = json_encode($input["materials"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                             $sql = "UPDATE indend_raw SET status='".$_GET["status"]."', vp_approved_by='".$_GET["emp_id"]."',
                             vp_approved_on='$entry_date' 
                            WHERE id='".$values["id"]."'";
                         
                         if ($conn->query($sql)) {
                            $flag =0;
                        } else {
                            $flag ++;
                        }
                    
                }
                
                
                if ($flag == 0) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
        
        
        
    } 
                      
           
    else if ($_GET["type"] == "CorrectionapproveIndend") {
        

                $flag = 0;
                for($k = 0; $k < count($input); $k++) {
                    $mat_type = $input[$k]['material_type'];
                  
                    $materials = $input[$k]['materials_list'];
                 
                    
                    for($i = 0; $i < count($materials); $i++) {
                         $indend = $materials[$i];
                      $sql = "UPDATE indend_raw SET status='pending' WHERE id='". $indend["id"] . "'";

                     
                         $conn->query($sql);
                    }
                }
                if ($flag == 0) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
        
        
        
    
        
    } 
    
    else if ($_GET["type"] == "getRejectedIndends") {
        $output = Array();
        
       //$sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_name FROM indend_raw i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.status='reject'  GROUP BY i.no";
       $sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_name FROM indend_raw i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.status='reject'ORDER BY id DESC";
       // echo $sql;
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                $sql8 = "SELECT i.*,m.material_name FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code 
                WHERE i.no='".$row["no"]."' ";
                // $sql8 = "SELECT * FROM indend_raw WHERE no='".$row["no"]."'";
                $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $lowest = array();
                        $highest = array();
        
                        $lowest["rate"] = 0;
                        $highest["rate"] = 0;
                        $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row8["material_code"]."' ORDER BY id DESC";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.challan_no='".$row1["challan_no"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row["last_purchase"] = $row2["vendor_no"];
                                        
                                        $flag = 0;
                                        if ($lowest["rate"] < $lowest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $lowest["rate"] = $row1["rate"];
                                            $lowest["vendor_name"] = $row2["vendor_name"];
                                            $lowest["vendor_no"] = $row2["vendor_no"];
                                            $lowest["challan_no"] = $row1["challan_no"];
                                            $lowest["challan_date"] = $row2["challan_date"];
                                        }
                                        
                                        $flag = 0;
                                        if ($highest["rate"] < $highest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $highest["rate"] = $row1["rate"];
                                            $highest["vendor_name"] = $row2["vendor_name"];
                                            $highest["vendor_no"] = $row2["vendor_no"];
                                            $highest["challan_no"] = $row1["challan_no"];
                                            $highest["challan_date"] = $row2["challan_date"];
                                        }
                                    }
                                }
                            }
                        } else {
                            $row8["last_purchase"] = "";
                        }
                        
                        $row8["lowest"] = $lowest;
                        $row8["highest"] = $highest;
                        
                        $output1 = Array();
                        $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["material_code"]."%' AND q.status='approve'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                if ($row1["vendor_no"] == $row["expected_vendor"]) {
                                    $row1["last_purchase"] = "yes";
                                } else {
                                    $row1["last_purchase"] = "no";
                                }
                                
                                $materials = json_decode($row1["materials"]);
                                for ($i = 0; $i < count($materials); $i++) {
                                    $material = $materials[$i];
                                    if ($material->material_code == $row8["material_code"]) {
                                        $row1["quotation_amt"] = $material->quotation_amt;
                                        $row1["quotation_per"] = $material->quotation_per;
                                        $output1[] = $row1;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        $row8["vendors"] = $output1;
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getPurchaseIndendsLog") {
 
        $output = Array();
        $plantId = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));

        $where = "i.plant_id='".$plantId."' AND i.status IN ('approve','pending','TO_HOD','To_HOD_RMPM','TO_PlantHead','To_Store_Head','Rejected','Revert_To_Dept_Head','Revert_To_Engg','Revert_To_QA')";

        $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,
        max(entry_date) as entry_date, max(entry_by) as entry_by, 
        max(approve_by) as approve_by, max(approve_date) as approve_date,
        max(approve_plantHead_by) as approve_plantHead_by, max(approve_plantHead_on) as approve_plantHead_on, 
        max(status) as status, request_no  FROM indend_raw i
        WHERE ".$where."
        GROUP BY request_no ORDER BY MAX(i.id) DESC";
        
        $result = $conn->query($sql);
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                 
                $output8 = array();
                $reqEsc = mysqli_real_escape_string($conn, (string)$row["request_no"]);
                $noEsc = mysqli_real_escape_string($conn, (string)$row["no"]);
                $sql8 = "SELECT v.vendor_name, i.*, m.grade AS view_grade, mat.grade AS mat_grade,
                    m.material_name AS view_material_name,
                    mat.material_name AS master_material_name,
                    om.material_name AS others_material_name
                    FROM indend_raw i
                    LEFT JOIN my_view m ON i.material_code = m.material_code
                    LEFT JOIN material mat ON i.material_code = mat.material_code
                    LEFT JOIN others_material om ON i.material_code = om.material_code
                    LEFT JOIN vendor v ON i.specific_vendor = v.vendor_no
                    WHERE i.plant_id='".$plantId."' AND (i.request_no = '".$reqEsc."'".($noEsc !== '' ? " OR i.no = '".$noEsc."'" : "").")";
            
                $result8 = $conn->query($sql8);
                if ($result8 && $result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $pick = '';
                        foreach (array(
                            $row8['material_name'] ?? '',
                            $row8['view_material_name'] ?? '',
                            $row8['master_material_name'] ?? '',
                            $row8['others_material_name'] ?? '',
                            $row8['gm_material'] ?? '',
                        ) as $candidate) {
                            $candidate = trim((string)$candidate);
                            if ($candidate === '' || strcasecmp($candidate, 'null') === 0 || strcasecmp($candidate, 'undefined') === 0) {
                                continue;
                            }
                            if (!empty($row8['material_code']) && strcasecmp($candidate, (string)$row8['material_code']) === 0) {
                                continue;
                            }
                            $pick = $candidate;
                            break;
                        }
                        $row8['material_name'] = $pick;
                        if (empty($row8['grade']) && !empty($row8['view_grade'])) {
                            $row8['grade'] = $row8['view_grade'];
                        } else if (empty($row8['grade']) && !empty($row8['mat_grade'])) {
                            $row8['grade'] = $row8['mat_grade'];
                        }
                        unset($row8['view_material_name'], $row8['master_material_name'], $row8['others_material_name'], $row8['view_grade'], $row8['mat_grade']);
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
 
    
    
    else if ($_GET["type"] == "getAllPurchaseIndendsLog") {
       $output = Array();
        $sql = "SELECT i.*, v.vendor_name, m.material_type, m.material_name FROM indend_raw i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve'GROUP BY i.no";
        $result = $conn->query($sql);

        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                 $sql8 = "SELECT i.*,m.material_name FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.no='".$row["no"]."' ";
                $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $lowest = array();
                        $highest = array();
        
                        $lowest["rate"] = 0;
                        $highest["rate"] = 0;
                        $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row8["material_code"]."' ORDER BY id DESC";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.challan_no='".$row1["challan_no"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row["last_purchase"] = $row2["vendor_no"];
                                        
                                        $flag = 0;
                                        if ($lowest["rate"] < $lowest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $lowest["rate"] = $row1["rate"];
                                            $lowest["vendor_name"] = $row2["vendor_name"];
                                            $lowest["vendor_no"] = $row2["vendor_no"];
                                            $lowest["challan_no"] = $row1["challan_no"];
                                            $lowest["challan_date"] = $row2["challan_date"];
                                        }
                                        
                                        $flag = 0;
                                        if ($highest["rate"] < $highest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $highest["rate"] = $row1["rate"];
                                            $highest["vendor_name"] = $row2["vendor_name"];
                                            $highest["vendor_no"] = $row2["vendor_no"];
                                            $highest["challan_no"] = $row1["challan_no"];
                                            $highest["challan_date"] = $row2["challan_date"];
                                        }
                                    }
                                }
                            }
                        } else {
                            $row8["last_purchase"] = "";
                        }
                        
                        $row8["lowest"] = $lowest;
                        $row8["highest"] = $highest;
                        
                        $output1 = Array();
                        $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["material_code"]."%' AND q.status='approve'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                if ($row1["vendor_no"] == $row["expected_vendor"]) {
                                    $row1["last_purchase"] = "yes";
                                } else {
                                    $row1["last_purchase"] = "no";
                                }
                                
                                $materials = json_decode($row1["materials"]);
                                for ($i = 0; $i < count($materials); $i++) {
                                    $material = $materials[$i];
                                    if ($material->material_code == $row["material_code"]) {
                                        $row1["quotation_amt"] = $material->quotation_amt;
                                        $row1["quotation_per"] = $material->quotation_per;
                                        $output1[] = $row1;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        $row8["vendors"] = $output1;
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET['type'] == 'downloadIndendsLog'){
        require '../../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Indend Of Raw Material'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.= "";

        $html.='
        <h2 style="text-align:center">Indend Of Raw Material</h2>
        <table cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Indend No.</td>
                    <td style="width: 10%;">vendor No</td>
                    <td style="width: 10%;">Material Type</td>
                    <td style="width: 10%;">Material code</td>
                    <td style="width: 10%;">Material Name</td>
                    <td style="width: 10%;">req. qty</td>
                    <td style="width: 10%;">Required For</td>
                    <td style="width: 15%;">Requirement</td>
                    <td style="width: 10%;">Status</td>
                </tr>
            </thead>
            <tbody>';
            $i=1;
        $sql = "SELECT i.*, m.material_subtype, m.material_name, m.grade FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code WHERE  i.user_no='".$_GET["user_no"]."' AND i.material_code LIKE '%".$_GET["material_code"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'</td>
                        <td style="width: 10%;">'.$row['indend_no'].'</td>
                        <td style="width: 10%;">'.$row['expected_vendor'].'</td>
                        <td style="width: 10%;">'.$row['material_subtype'].'</td> 
                        <td style="width: 10%;">'.$row['material_code'].'</td>
                        <td style="width: 10%;">'.$row['material_name'].'</td>
                        <td style="width: 10%;text-align:right;">'.$row['req_qty'].'</td>
                        <td style="width: 10%;">'.$row['required_for'].'</td>
                        <td style="width: 15%;">'.$row['requirement'].'</td>
                        <td style="width: 10%;">'.$row['status'].'</td>
                    </tr>
                </tbody>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Raw Material Indend Log.pdf', 'I');
    }
   else if ($_GET['type'] == 'downloadPurchaseIndendsLog'){
        require '../../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Indent / Requisition Of Raw Material'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.= "";

        $html.='
        <h2 style="text-align:center">Indent / Requisition Of Raw Material</h2>
        <table cellpadding="5" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%;"><b>Sr.</b></td>
                    <td style="width: 20%;"><b>Date</b></td>
                    <td style="width: 20%;"><b>Indend No.</b></td>
                    <td style="width: 20%;"><b>No Of Items</b></td>
                    <td style="width: 20%;"><b>Entry By</b></td>
                </tr>';
                $i=1;
        //$sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name,m.material_name,m.material_subtype,m.material_type FROM indend_raw i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' ORDER BY i.id DESC";
        //$sql = "SELECT i.*, v.vendor_name, m.material_type, m.material_name FROM indend_raw i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.department LIKE '%".$_GET["department_name"]."%' AND i.status='approve' AND DATE(i.approve_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY i.no";
         $sql = "select a.*,b.no_items from ( SELECT i.entry_date,i.indend_no,no,i.entry_by 
         FROM indend_raw i WHERE DATE(entry_date) between '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' 
         group by i.entry_date,i.indend_no,no,i.entry_by )a left join (select indend_no as id,count(indend_no) 
         as no_items 
         FROM indend_raw i WHERE DATE(entry_date) between '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  
         group by indend_no) b on a.indend_no = b.id order by a.indend_no desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
            $html.='
            <tr>
                <td style="width: 20%;">'.$i.'.</td>
                <td style="width: 20%;">'.date('d-m-Y', strtotime($row['entry_date'])).'</td>
                <td style="width: 20%;">'.$row['indend_no'].'</td>
                <td style="width: 20%;">'.$row['no_items'].'</td>
                <td style="width: 20%;">'.$row['entry_by'].'</td>
            </tr>';
            $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
      $pdf->Output('Raw Material Indend Log.pdf', 'I');
    }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>