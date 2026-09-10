<?php

//  ini_set('display_errors', 1);
//   error_reporting(E_ALL);
 
   

    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    try{
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
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    function poRawRmpmMaterialWhere($alias = 'i') {
        $a = $alias;
        return "($a.material_type IN ('Raw Material','Packing Material','RM/PM Material')
                OR LOWER($a.material_type) LIKE '%raw material%'
                OR LOWER($a.material_type) LIKE '%packing material%'
                OR LOWER($a.material_type) LIKE '%rm/pm%')";
    }

    function normalizePoTypeForOrder($headerType, $materials = array()) {
        $header = trim((string)$headerType);
        if ($header === 'Raw Material' || $header === 'Packing Material') {
            return $header;
        }
        if (is_array($materials)) {
            foreach ($materials as $mat) {
                $lineType = isset($mat['material_type']) ? trim((string)$mat['material_type']) : '';
                if ($lineType === 'Raw Material' || $lineType === 'Packing Material') {
                    return $lineType;
                }
            }
            foreach ($materials as $mat) {
                $lineType = isset($mat['material_type']) ? strtolower(trim((string)$mat['material_type'])) : '';
                if ($lineType !== '' && strpos($lineType, 'packing') !== false) {
                    return 'Packing Material';
                }
                if ($lineType !== '' && (strpos($lineType, 'raw') !== false || strpos($lineType, 'rm/pm') !== false)) {
                    return 'Raw Material';
                }
            }
        }
        $lower = strtolower($header);
        if ($lower !== '' && strpos($lower, 'packing') !== false) {
            return 'Packing Material';
        }
        if ($lower !== '' && (strpos($lower, 'raw') !== false || strpos($lower, 'rm/pm') !== false)) {
            return 'Raw Material';
        }
        return $header !== '' ? $header : 'Raw Material';
    }

    function poTypeFilterSql($conn, $po_type_filter) {
        $po_type_filter = trim((string)$po_type_filter);
        if ($po_type_filter === '' || strcasecmp($po_type_filter, 'All') === 0) {
            return '';
        }
        $esc = $conn->real_escape_string($po_type_filter);
        if ($po_type_filter === 'Raw Material') {
            return " AND (p.po_type = 'Raw Material' OR p.po_type = 'RM/PM Material' OR LOWER(TRIM(p.po_type)) LIKE '%raw%')";
        }
        if ($po_type_filter === 'Packing Material') {
            return " AND (p.po_type = 'Packing Material' OR p.po_type = 'RM/PM Material' OR LOWER(TRIM(p.po_type)) LIKE '%packing%')";
        }
        return " AND p.po_type = '".$esc."'";
    }

    function poRawSqlVal($conn, $val) {
        if ($val === null) {
            return '';
        }
        if (is_array($val) || is_object($val)) {
            $val = json_encode($val, JSON_UNESCAPED_UNICODE);
        }
        return mysqli_real_escape_string($conn, (string)$val);
    }

    /** po_material.unit is varchar(10) — prefer short master unit over long indent/grade text. */
    function poRawResolveUnit($conn, $materialCode, $fallbackUnit = '', $maxLen = 10) {
        $maxLen = (int)$maxLen;
        if ($maxLen < 1) {
            $maxLen = 10;
        }
        $codeEsc = poRawSqlVal($conn, $materialCode);
        $masterUnit = '';
        if ($codeEsc !== '') {
            foreach (array(
                "SELECT unit FROM chemical WHERE chemical_no='".$codeEsc."' LIMIT 1",
                "SELECT unit FROM others_material WHERE material_code='".$codeEsc."' LIMIT 1",
                "SELECT COALESCE(NULLIF(TRIM(unit), ''), NULLIF(TRIM(uom), ''), NULLIF(TRIM(order_unit), '')) AS unit FROM material WHERE material_code='".$codeEsc."' LIMIT 1",
                "SELECT uom AS unit FROM my_view WHERE material_code='".$codeEsc."' LIMIT 1",
                "SELECT unit FROM general_material WHERE material_code='".$codeEsc."' LIMIT 1",
            ) as $sql) {
                $res = @$conn->query($sql);
                if ($res && $res->num_rows > 0) {
                    $u = trim((string)(($res->fetch_assoc())['unit'] ?? ''));
                    if ($u !== '' && strlen($u) <= $maxLen) {
                        return $u;
                    }
                    if ($u !== '' && $masterUnit === '') {
                        $masterUnit = $u;
                    }
                }
            }
        }
        $fallback = trim((string)$fallbackUnit);
        if ($fallback !== '' && strlen($fallback) <= $maxLen) {
            return $fallback;
        }
        if ($masterUnit !== '') {
            return substr($masterUnit, 0, $maxLen);
        }
        if ($fallback !== '') {
            return substr($fallback, 0, $maxLen);
        }
        return 'NA';
    }

    function poRawClampField($val, $maxLen = 10) {
        $maxLen = (int)$maxLen;
        if ($maxLen < 1) {
            $maxLen = 10;
        }
        $text = trim((string)$val);
        if ($text === '') {
            return '';
        }
        if (strlen($text) <= $maxLen) {
            return $text;
        }
        return substr($text, 0, $maxLen);
    }

    /** Lines still waiting for PO creation (exclude rows already converted to PO). */
    function poIndendPendingWhere($alias = 'i') {
        $a = $alias;
        return "(
            {$a}.status IN ('approve','Approve')
            AND {$a}.indend_no IS NOT NULL AND TRIM({$a}.indend_no) <> ''
            AND (
                {$a}.po_indend IS NULL
                OR TRIM({$a}.po_indend) = ''
                OR LOWER(TRIM({$a}.po_indend)) = 'pending'
            )
            AND (
                {$a}.po_indend IS NULL
                OR TRIM({$a}.po_indend) = ''
                OR LOWER(TRIM({$a}.po_indend)) NOT IN ('approve', 'approved')
            )
        )";
    }
    
    if ($_GET["type"] == "saveIndendPONew1") {
        header('Content-Type: application/json; charset=UTF-8');
        try {
        if (empty($input) || !is_array($input)) {
            echo json_encode(array('status' => 'error', 'message' => 'Invalid request body'));
            exit;
        }

            $materials = json_decode(json_encode(isset($input["materials"]) ? $input["materials"] : array()), true);
            if (!is_array($materials) || count($materials) === 0) {
                echo json_encode(array('status' => 'error', 'message' => 'No materials to save'));
                exit;
            }
     
            $today = date('Y-m-d');
            $currentMonth = date('n'); // Numeric month (1–12)
            $currentYear = date('Y');
            if ($currentMonth >= 4) { $financialYear = $currentYear . '-' . ($currentYear + 1); } else { $financialYear = ($currentYear - 1) . '-' . $currentYear; }
      
            $mat_type = normalizePoTypeForOrder(isset($input["po_type"]) ? $input["po_type"] : '', $materials);
            $input["po_type"] = $mat_type;
     
            $Po_Group = '';
            $yearPart = date('y');
     
            if($mat_type=='Raw Material' || $mat_type=='Packing Material'){
                $poPrefix = "POG" . $yearPart;
            }else if($mat_type == 'Equipment' ){
                $poPrefix = "POCAPG" . $yearPart;
            }else{
                $poPrefix = "POOT" . $yearPart;
            }
            
            $plantEsc = poRawSqlVal($conn, $_GET["plant_id"]);
            $sql = "SELECT po_no FROM purchaseorder WHERE plant_id = '".$plantEsc."' ORDER BY id DESC LIMIT 1";
            $result = mysqli_query($conn, $sql);
            
            $nextSerial = "001"; // default starting number
            
            if ($row = mysqli_fetch_assoc($result)) {
                $lastPoNumber = $row['po_no']; // e.g., "POCAPG25/007"
                $parts = explode("/", $lastPoNumber);
            
                if (isset($parts[1])) {
                    $lastSerial = (int)$parts[1];
                    $nextSerial = str_pad($lastSerial + 1, 3, '0', STR_PAD_LEFT);
                }
            }
            
            $newPoNumber = $poPrefix . "/" . $nextSerial;    
            
            $PSB = true;
            $poTypeEsc = poRawSqlVal($conn, $input["po_type"]);
            $poDateEsc = poRawSqlVal($conn, isset($input["po_date"]) ? $input["po_date"] : $today);
      
            $sql = "INSERT INTO `purchaseorder`(`plant_id`,`marketType`,`po_type`,`Po_Group`, `po_no`, `indent_no`, `vendor_no`, `gross_total`,`taxable_total`,`disc_amt`, `gst_total`,`net_total`,`sgst_total`,`cgst_total`,`igst_total`,`shipping_type`,`shipping_handling`,`shipping_gst`,
             `shipping_Gst_amt`,`shipping_total`, `final_total`, `rounding`,`terms_conditions`, `shipcompany_code`,`billcompany_code`,`gstSplitData`,`schedule_data`,`paymentTerms`,`narration`,`purchase_type`,`taxType`,`currency`,`Fin_Year`,`selectedBill`,`selectedShip`,
             `status`, `entry_by`, `entry_date`)  VALUES ('".$plantEsc."','".poRawSqlVal($conn, $input["marketType"] ?? '')."','".$poTypeEsc."','".$poTypeEsc."','".poRawSqlVal($conn, $newPoNumber)."', '".poRawSqlVal($conn, $input["indent_no"] ?? '')."', '".poRawSqlVal($conn, $input["vendor_no"] ?? '')."', '".poRawSqlVal($conn, $input["gross_total"] ?? 0)."', '".poRawSqlVal($conn, $input["taxable_total"] ?? 0)."', 
             '".poRawSqlVal($conn, $input["disc_total"] ?? 0)."', '".poRawSqlVal($conn, $input["gst_total"] ?? 0)."', '".poRawSqlVal($conn, $input["net_total"] ?? 0)."', '".poRawSqlVal($conn, $input["sgst_total"] ?? 0)."', '".poRawSqlVal($conn, $input["cgst_total"] ?? 0)."', '".poRawSqlVal($conn, $input["igst_total"] ?? 0)."', '".poRawSqlVal($conn, $input["shipping_type"] ?? '')."','".poRawSqlVal($conn, $input["shipping_handling"] ?? 0)."', '".poRawSqlVal($conn, $input["shipping_gst"] ?? 0)."', '".poRawSqlVal($conn, $input["shipping_Gst_amt"] ?? 0)."',
             '".poRawSqlVal($conn, $input["shipping_total"] ?? 0)."', '".poRawSqlVal($conn, $input["final_total"] ?? 0)."', '".poRawSqlVal($conn, $input["rounding"] ?? 0)."', '".poRawSqlVal($conn, json_encode(isset($input["terms_conditions"]) ? $input["terms_conditions"] : array()))."', '".poRawSqlVal($conn, $input["shipcompany_code"] ?? '')."', '".poRawSqlVal($conn, $input["billcompany_code"] ?? '')."', '".poRawSqlVal($conn, json_encode(isset($input["gstSplitData"]) ? $input["gstSplitData"] : array()))."',
             '".poRawSqlVal($conn, json_encode(isset($input["schedule_data"]) ? $input["schedule_data"] : array()))."','".poRawSqlVal($conn, json_encode(isset($input["paymentTerms"]) ? $input["paymentTerms"] : array()))."', '".poRawSqlVal($conn, $input["narration"] ?? '')."', '".poRawSqlVal($conn, $input["purchase_type"] ?? '')."', '".poRawSqlVal($conn, $input["taxType"] ?? '')."', '".poRawSqlVal($conn, $input["currency"] ?? '')."', '".poRawSqlVal($conn, $financialYear)."', '".poRawSqlVal($conn, json_encode(isset($input["selectedBill"]) ? $input["selectedBill"] : array()))."',
             '".poRawSqlVal($conn, json_encode(isset($input["selectedShip"]) ? $input["selectedShip"] : array()))."', 'Pending', '".poRawSqlVal($conn, $_GET["emp_id"])."', '".$poDateEsc."' )"; 
        
             if ($conn->query($sql)) {
                 $last_id = $conn->insert_id;

                 foreach ($materials as $mat) {
                    $matVendorNo = isset($mat["vendor_no"]) && $mat["vendor_no"] !== '' ? $mat["vendor_no"] : ($input["vendor_no"] ?? '');
                    $unitVal = poRawResolveUnit($conn, $mat["material_code"] ?? '', $mat["unit"] ?? '');
                    $quotPerVal = poRawClampField($mat["quotation_per"] ?? '', 10);
                    if ($quotPerVal === '' || strlen(trim((string)($mat["quotation_per"] ?? ''))) > 10) {
                        $quotPerVal = $unitVal;
                    }
                
                     $sql1 = "INSERT INTO `po_material`(`plant_id`, `po_no`, `po_date`, `material_code`, `qty`, `unit`, `required_for`, `client_code`, `vendor_no`, `quotation_no`, `quotation_amt`, `quotation_per`, `disc_per`, `disc_amt`, 
                    `gst`, `gross_total`, `taxable_amt`, `tax_total`, `net_total`, `cgst`, `cgstPer`, `sgst`, `sgstPer`, `igst`, `igstPer`, `indend_no`,`po_type`,`po_indend`,`entryBy`,`entryOn`,`clientGrpCode`,`clientSubGrpCode`) VALUES ('".$plantEsc."', '".$last_id."', 
                    '".$poDateEsc."', '".poRawSqlVal($conn, $mat["material_code"] ?? '')."', '".poRawSqlVal($conn, $mat["order_qty"] ?? 0)."', '".poRawSqlVal($conn, $unitVal)."', '".poRawSqlVal($conn, $mat["required_for"] ?? '')."', '".poRawSqlVal($conn, $mat["client_code"] ?? '')."', '".poRawSqlVal($conn, $matVendorNo)."', '".poRawSqlVal($conn, $mat["quotation_no"] ?? '')."', '".poRawSqlVal($conn, $mat["quotation_amt"] ?? 0)."', 
                    '".poRawSqlVal($conn, $quotPerVal)."', '".poRawSqlVal($conn, $mat["disc_per"] ?? 0)."', '".poRawSqlVal($conn, $mat["disc_amt"] ?? 0)."', '".poRawSqlVal($conn, $mat["gst"] ?? 0)."', '".poRawSqlVal($conn, $mat["gross_total"] ?? 0)."', '".poRawSqlVal($conn, $mat["taxable_amt"] ?? 0)."', '".poRawSqlVal($conn, $mat["tax_total"] ?? 0)."', '".poRawSqlVal($conn, $mat["net_total"] ?? 0)."', '".poRawSqlVal($conn, $mat["cgst"] ?? 0)."', 
                    '".poRawSqlVal($conn, $mat["cgstPer"] ?? 0)."', '".poRawSqlVal($conn, $mat["sgst"] ?? 0)."','".poRawSqlVal($conn, $mat["sgstPer"] ?? 0)."','".poRawSqlVal($conn, $mat["igst"] ?? 0)."','".poRawSqlVal($conn, $mat["igstPer"] ?? 0)."','".poRawSqlVal($conn, $mat["indend_no"] ?? '')."','".$poTypeEsc."','Approve', '".poRawSqlVal($conn, $_GET["emp_id"])."', '".poRawSqlVal($conn, $entry_date)."' ,'".poRawSqlVal($conn, $mat["clientGrpCode"] ?? '')."','".poRawSqlVal($conn, $mat["clientSubGrpCode"] ?? '')."')";
                   
                    if ($conn->query($sql1)) {
                        $matIdEsc = poRawSqlVal($conn, $mat["id"] ?? '');
                        if ($matIdEsc !== '') {
                            $conn->query("UPDATE indend_raw SET po_indend = 'Approve' WHERE id = '".$matIdEsc."'");
                        }
                    } else {
                        $PSB = false;
                    }
                }
                 
                 
                if ($PSB) {
                    echo json_encode(array('status' => 'success'));
                } else {
                    $conn->query("DELETE FROM po_material WHERE po_no='".$last_id."' AND plant_id='".$plantEsc."'");
                    $conn->query("DELETE FROM purchaseorder WHERE id='".$last_id."' AND plant_id='".$plantEsc."'");
                    echo json_encode(array('status' => 'error', 'message' => $conn->error ? $conn->error : 'Failed to save PO materials'));
                }
                  
             } else {
                echo json_encode(array('status' => 'error', 'message' => $conn->error ? $conn->error : 'Failed to save purchase order'));
            }
        } catch (\Throwable $e) {
            echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
        exit;
     
    } 
 
    
  
    
    else if ($_GET["type"] == "get_sales_order") {
         
        $output = Array();
        $sql = "SELECT p.*,c.TrdNm FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE
        p.user_no='".$_GET["user_no"]."' AND p.status='pending' ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
                $output1 = array();
                 $sql1 = "SELECT o.order_no,o.product_code,o.order_qty,o.pack_size,o.details, p.product_name, 
                 p.product_type, p.grade FROM order_materials o LEFT JOIN product p ON
                 o.product_code=p.product_code WHERE o.order_no='".$row["order_no"]."' ";
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                          $row1["details"] = json_decode($row1["details"]);
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
         
     }
     else if ($_GET["type"] == "get_sales_order_analysis") {
      
        $output = Array();
        
      $sql = "SELECT a.*,b.*,c.*,d.*, e.raw_materials,e.id as unit_formula_id, a.packing_style as p_style, b.valid_till as v_till FROM order_materials a LEFT JOIN po_entry b 
      ON a.order_no = b.order_no LEFT JOIN client c ON b.client_code = c.client_code LEFT JOIN product d ON a.product_code = d.product_code LEFT JOIN unitformula e
      ON a.product_code=e.product_code WHERE b.status = 'Approve' and a.plant_id='".$_GET["plant_id"]."' ORDER BY a.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
                            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["unit_formula_id"]."' ";
               
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
                // $row["raw_materials"] = json_decode($row["raw_materials"]); 
         ///////////////////////////////////////////////////////////////////////       
         ///////////////////////////////////////////////////////////////////////       
         ///////////////////////////////////////////////////////////////////////       
         ///////////////////////////////////////////////////////////////////////       
           $pak_configuration = $row["packing_configuration"];

        // Calculate and add EOU_STOCK and Issue for each packing material
        foreach ($pak_configuration as &$pak_data) {
            foreach ($pak_data["packing_materials"] as &$pak_data2) {
                $received_qty = 0;
                $issue_qty = 0;
                $balance_qty = 0;

                $material_code = $pak_data2["material_code"];

                $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='$material_code'";
                $result1 = $conn->query($sql1);

                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $pak_data2["qty1"] = $row1["qty"];
                    }
                }

                $sql1 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='$material_code'";
                $result1 = $conn->query($sql1);

                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $pak_data2["issue_qty1"] = $row1["issue_qty"];
                    }
                }

                $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                         (SELECT ar_no FROM stock_book WHERE material_code='$material_code')";
                $result1 = $conn->query($sql1);

                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $pak_data2["m_qty"] = $row1["m_qty"];
                    }
                }

                $pak_data2["EOU_STOCK11"] = $pak_data2["qty1"] - $pak_data2["issue_qty1"] - $pak_data2["m_qty"];
                $pak_data2["Issue11"] = $pak_data2["issue_qty1"] + $pak_data2["m_qty"];
            }
        }

        $row["packing_configuration"] = $pak_configuration;
         ///////////////////////////////////////////////////////////////////////       
         ///////////////////////////////////////////////////////////////////////       
         ///////////////////////////////////////////////////////////////////////       
         ///////////////////////////////////////////////////////////////////////       
                
                
         $rawMaterials = json_decode($row["raw_materials"], true);
        foreach ($rawMaterials as &$rawMaterial) {
            
            $received_qty = 0;
            $issue_qty = 0;
            $balance_qty = 0;

            $material_code = $rawMaterial["material_code"];

            $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='$material_code'";
            $result1 = $conn->query($sql1);

            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $rawMaterial["qty1"] = $row1["qty"];
                }
            }

            $sql1 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='$material_code'";
            $result1 = $conn->query($sql1);

            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $rawMaterial["issue_qty1"] = $row1["issue_qty"];
                }
            }

            $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                     (SELECT ar_no FROM stock_book WHERE material_code='$material_code')";
            $result1 = $conn->query($sql1);

            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $rawMaterial["m_qty"] = $row1["m_qty"];
                }
            }

            $rawMaterial["EOU_STOCK"] = $rawMaterial["qty1"] - $rawMaterial["issue_qty1"] - $rawMaterial["m_qty"];
            $rawMaterial["Issue"] = $rawMaterial["issue_qty1"] + $rawMaterial["m_qty"];
        }        
                
                
             $row["raw_materials"] = $rawMaterials;  
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                
         ///////////////////////////////////////////////////////////////////////       
         ///////////////////////////////////////////////////////////////////////       
         ///////////////////////////////////////////////////////////////////////       
                $row["product_name2"] = json_decode($row["product_name"][0]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    //  else if ($_GET["type"] == "get_sales_order_analysis") {
      
    //     $output = Array();
        
    //   $sql = "SELECT a.*,b.*,c.*,d.*, e.raw_materials,e.id as unit_formula_id, a.packing_style as p_style, b.valid_till as v_till FROM order_materials a LEFT JOIN po_entry b 
    //   ON a.order_no = b.order_no LEFT JOIN client c ON b.client_code = c.client_code LEFT JOIN product d ON a.product_code = d.product_code LEFT JOIN unitformula e
    //   ON a.product_code=e.product_code WHERE b.status = 'Approve' ORDER BY a.id DESC";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //                         $output1 = Array();

    //         while ($row = $result->fetch_assoc()) {
    //              $output1 = Array();
    //             $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,
    //             pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["unit_formula_id"]."' ";
               
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
                        
    //                     $output2 = Array();
    //                     $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
    //                     $result2 = $conn->query($sql2);
    //                     if ($result2->num_rows > 0) {
    //                         while ($row2 = $result2->fetch_assoc()) {
                                
    //                               $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row2["grade"]."')";
    //          $resQ = $conn->query($q);
    //           $prodLatest = $resQ->fetch_assoc(); 
         
    //       $row2['gradeName'] = $prodLatest['gradeName']; 
                    
                                
    //                              $output2[] = $row2;
    //                         }
    //                     }
    //                     $row1['packing_materials'] = $output2;

    //                     $output1[] = $row1;
    //                 }
                    
    //             }
                
    //              $output3 = Array();
    //             $sql3 = "select product_name from product where product_code ='".$row["product_code"]."' ";
               
    //             $result3 = $conn->query($sql3);
    //             if ($result3->num_rows > 0) {
    //                 while ($row3 = $result3->fetch_assoc()) {
    //                     $output3 = $row3;
    //                 }
    //             }
                
                
    //             $row['packing_configuration'] =$output1;
    //             $row['product_name1'] =$output3;
    //             $row["raw_materials"] = json_decode($row["raw_materials"]); 
    //             $row["product_name2"] = json_decode($row["product_name"][0]); 
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }
    
    else if ($_GET["type"] == "getPendingIndend") {
        
        $output = Array();
        header('Content-Type: application/json; charset=UTF-8');
        try {
        $material_type = isset($_GET["material_type"]) ? $_GET["material_type"] : '';
        $forQueue = isset($_GET["For"]) ? strtoupper(trim($_GET["For"])) : '';
        $material_type_sql = '';
        if ($material_type !== '' && $material_type !== 'All') {
            $material_type_sql = " AND i.material_type = '".$conn->real_escape_string($material_type)."'";
        }
        if ($forQueue === 'RMPM') {
            $material_type_sql .= ' AND '.poRawRmpmMaterialWhere('i');
        } else if ($forQueue === 'GEN') {
            $material_type_sql .= ' AND NOT '.poRawRmpmMaterialWhere('i');
        }
 
        $plant_id = $conn->real_escape_string($_GET["plant_id"]);
        $poIndendPendingFilter = poIndendPendingWhere('i');
        $sql="SELECT MAX(i.id) as id,MAX(i.department) as department,MAX(i.amendment) as amendment,
        IFNULL(MAX(NULLIF(TRIM(CONCAT(IFNULL(e.firstname,''), ' ', IFNULL(e.lastname,''))), '')), MAX(i.entry_by)) as entry_by,
        MAX(i.entry_date) as entry_date,MAX(i.request_no) as request_no,MAX(i.currency) as currency,
        i.indend_no, i.vendor_no, v.vendor_name, v.country, MAX(i.material_type) as material_type ,MAX(i.purchase_type) as purchase_type
        FROM indend_raw i
        LEFT JOIN vendor v ON i.vendor_no=v.vendor_no
        LEFT JOIN employee e ON i.entry_by=e.emp_id AND e.plant_id=i.plant_id
        WHERE i.plant_id = '".$plant_id."'".$material_type_sql." AND ".$poIndendPendingFilter."
        GROUP BY i.indend_no, i.vendor_no, v.vendor_name, v.country
        HAVING TRIM(i.indend_no) <> ''
        ORDER BY MAX(i.id) DESC";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $gross_total = 0;
                $gst_total = 0;
                $net_total = 0; 
                
               $output1 = Array();
               
              
      if($row['material_type'] == 'Raw Material' || $row['material_type'] == 'Packing Material'){
                
                
                $sql1 = "SELECT i.*, v.vendor_name, v.country, v.scode,
                    COALESCE(NULLIF(TRIM(m.material_type), ''), mm.material_type) as material_type,
                    COALESCE(NULLIF(TRIM(m.material_subtype), ''), mm.material_subtype) as material_subtype,
                    COALESCE(NULLIF(TRIM(m.hsn), ''), mm.hsn) as hsn,
                    COALESCE(
                        NULLIF(TRIM(i.material_name), ''),
                        NULLIF(TRIM(m.material_name), ''),
                        NULLIF(TRIM(mm.material_name), ''),
                        NULLIF(TRIM(om.material_name), ''),
                        NULLIF(TRIM(ch.chemical_name), ''),
                        NULLIF(TRIM(gm.material_name), ''),
                        i.material_code
                    ) as material_name,
                    vm.clientGrpCode, vm.clientSubGrpCode,
                    ea.valid_till_date AS auth_valid_till, mm.Purchase_prepare_date
                FROM indend_raw i
                LEFT JOIN my_view m ON i.material_code=m.material_code
                LEFT JOIN material mm ON i.material_code=mm.material_code
                LEFT JOIN others_material om ON i.material_code=om.material_code
                LEFT JOIN chemical ch ON i.material_code=ch.chemical_no
                LEFT JOIN general_material gm ON i.material_code=gm.material_code
                LEFT JOIN vendor v ON i.vendor_no=v.vendor_no
                LEFT JOIN mst_vendor_materials vm ON i.vendor_no=vm.supplier_code AND i.material_code=vm.material_code
                LEFT JOIN export_authorization ea
                    ON ea.product_name LIKE CONCAT('%', i.material_code, '%')
                   AND ea.authorization_type = 'Import License'
                   AND ea.id = (
                        SELECT MAX(e2.id)
                        FROM export_authorization e2
                        WHERE e2.product_name LIKE CONCAT('%', i.material_code, '%')
                          AND e2.authorization_type = 'Import License'
                   )
                WHERE i.plant_id='".$plant_id."'
                AND ".$poIndendPendingFilter."
                AND i.indend_no='".$conn->real_escape_string($row["indend_no"])."'
                AND i.vendor_no='".$conn->real_escape_string($row["vendor_no"])."'
                ".$material_type_sql."
                ORDER BY i.id DESC";
                
            }else{
                
                $sql1 = "SELECT i.*, v.vendor_name, v.country, v.scode,
                    COALESCE(NULLIF(TRIM(m.material_type), ''), mm.material_type) as material_type,
                    COALESCE(NULLIF(TRIM(m.material_subtype), ''), mm.material_subtype) as material_subtype,
                    COALESCE(NULLIF(TRIM(m.hsn), ''), mm.hsn) as hsn,
                    COALESCE(
                        NULLIF(TRIM(i.material_name), ''),
                        NULLIF(TRIM(m.material_name), ''),
                        NULLIF(TRIM(mm.material_name), ''),
                        NULLIF(TRIM(om.material_name), ''),
                        NULLIF(TRIM(ch.chemical_name), ''),
                        NULLIF(TRIM(gm.material_name), ''),
                        i.material_code
                    ) as material_name,
                    vm.clientGrpCode, vm.clientSubGrpCode,
                    'NA' AS auth_valid_till
                FROM indend_raw i
                LEFT JOIN my_view m ON i.material_code=m.material_code
                LEFT JOIN material mm ON i.material_code=mm.material_code
                LEFT JOIN others_material om ON i.material_code=om.material_code
                LEFT JOIN chemical ch ON i.material_code=ch.chemical_no
                LEFT JOIN general_material gm ON i.material_code=gm.material_code
                LEFT JOIN vendor v ON i.vendor_no=v.vendor_no
                LEFT JOIN mst_vendor_materials vm ON i.vendor_no=vm.supplier_code AND i.material_code=vm.material_code
                WHERE i.plant_id='".$plant_id."'
                AND ".$poIndendPendingFilter."
                AND i.indend_no='".$conn->real_escape_string($row["indend_no"])."'
                AND i.vendor_no='".$conn->real_escape_string($row["vendor_no"])."'
                ".$material_type_sql."
                ORDER BY i.id DESC";
                
            }
              
                
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $lineCountry = isset($row1['country']) ? $row1['country'] : '';
                        if($lineCountry != '' && $lineCountry != 'India'){
                             $row1["auth_valid_till"]  = $row1["auth_valid_till"]  ;
                             $row1["Purchase_prepare_date"]  = $row1["Purchase_prepare_date"]  ;
                             
                        }else{
                             $row1["auth_valid_till"]  ='NA';
                             $row1["Purchase_prepare_date"]  = $row1["Purchase_prepare_date"]  ;
                        }
                        
                        
                            $order_qty      = isset($row1["order_qty"]) ? floatval($row1["order_qty"]) : 0;
                            $quotation_amt  = isset($row1["quotation_amt"]) ? floatval($row1["quotation_amt"]) : 0;
                            $disc_per       = 0;
                            $gst            = isset($row1["gst"]) ? floatval($row1["gst"]) : 0;
                            $gross_total = $order_qty * $quotation_amt;
                            $disc_amt = 0;
                            $taxable_amt = round($gross_total - $disc_amt, 2);
                            $tax_total = round($taxable_amt * ($gst / 100), 2);
                            $net_total = round($taxable_amt + $tax_total, 2);
                            $row1["gross_total"]           = $gross_total;
                            $row1["disc_per"]              = $disc_per;
                            $row1["disc_amt"]              = $disc_amt;
                            $row1["taxable_amt"]           = $taxable_amt;
                            $row1["tax_total"]             = $tax_total;
                            $row1["net_total"]             = $net_total;
                            $row1["delivery_schedule_date"] = '';
                            $row1['unit'] = poRawResolveUnit($conn, $row1['material_code'] ?? '', $row1['unit'] ?? '');
                            $quotPerRaw = trim((string)($row1['quotation_per'] ?? ''));
                            if ($quotPerRaw === '' || strlen($quotPerRaw) > 10) {
                                $row1['quotation_per'] = $row1['unit'];
                            } else {
                                $row1['quotation_per'] = poRawClampField($quotPerRaw, 10);
                            }
                            $row1["check"] = false;
                            $row1["sgst"] = 0;
                            $row1["cgst"] = 0;
                            $row1["igst"] = 0;
                            $row1["splitQty"] = $row1["order_qty"];

                       $output1[] = $row1;
                    } 
                }
                
                if (count($output1) > 0) {
                    $row["indends"] = $output1;
                    $output[] = $row;
                }
            }
        }
        $jsonFlags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        echo json_encode($output, $jsonFlags);
        } catch (\Throwable $e) {
            echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
        exit;
        
    } 
    
    
    else if ($_GET["type"] == "getPendingIndend_legacy") {
        
        $output = Array();
        
        if($_GET["apitype"] == '1'){
            
        $sql="SELECT MAX(id) as id,MAX(department) as department,MAX(amendment) as amendment, MAX(entry_by) as entry_by,MAX(entry_date) as entry_date,request_no,
        MAX(indend_no)as indend_no , 'Miscellaneous' as material_type,MAX(purchase_type) as purchase_type,MAX(i.material_type) as material_type2
        FROM indend_raw i  WHERE i.plant_id = '".$_GET["plant_id"]."'AND status='approve'  AND po_indend='pending' 
        AND  i.material_type != 'Raw Material' AND i.material_type != 'Packing Material'  Group by request_no 
        ORDER BY id DESC";
            
        }else if($_GET["apitype"] == '2'){
             
            
            $sql="SELECT MAX(id) as id,MAX(department) as department,MAX(amendment) as amendment, MAX(entry_by) as entry_by,MAX(entry_date) as entry_date,MAX(request_no) as request_no,
            indend_no ,   MAX(material_type) as material_type ,MAX(purchase_type) as purchase_type
            FROM indend_raw i  WHERE i.plant_id = '".$_GET["plant_id"]."'AND status='approve'  AND po_indend='pending' 
            AND  ( i.material_type = 'Raw Material' OR i.material_type = 'Packing Material')  Group by indend_no 
            ORDER BY id DESC";
            
        } 
        
   
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $gross_total = 0;
                $gst_total = 0;
                $net_total = 0; 
                
               $output1 = Array();
               
             
            
    
          
                   if($row["material_type"] == 'Raw Material'){         
                   
                $sql1 = "SELECT distinct i.indend_no , i.*, v.vendor_name,v.scode,m.material_type, m.material_subtype, m.material_name,0 as delivery_schedule_date, 0 
                as disc_amt,0 as disc_per ,(select hsn from material z where z.material_code=i.material_code limit 1) as hsn
                
                FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code  LEFT JOIN
                vendor v ON i.vendor_no=v.vendor_no 
              WHERE   i.status='approve' AND i.po_indend='pending' AND
              i.indend_no='".$row["indend_no"]."' AND  m.material_type = 'Raw Material'  order by i.vendor_no";
                   
               }else if($row["material_type"] == 'Packing Material'){
                   
                                      
                 $sql1 = "SELECT distinct i.indend_no , i.*, v.vendor_name,v.scode,m.material_type, m.material_subtype, m.material_name,0 as delivery_schedule_date, 0 
                as disc_amt,0 as disc_per FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code  LEFT JOIN
                vendor v ON i.vendor_no=v.vendor_no 
              WHERE   i.status='approve' AND i.po_indend='pending' AND
              i.indend_no='".$row["indend_no"]."' AND   m.material_type = 'Packing Material' order by i.vendor_no";
                   
               }
               
               else{
                   
            $sql1 = "SELECT distinct i.indend_no , i.*, v.vendor_name,v.scode,'Miscellaneous' as material_type, m.material_subtype, m.material_name,0 as delivery_schedule_date, 0 
            as disc_amt,0 as disc_per ,(select hsn from others_material z where z.material_code=i.material_code limit 1) as hsn
            
            FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code  LEFT JOIN
            vendor v ON i.vendor_no=v.vendor_no 
            WHERE   i.status='approve' AND i.po_indend='pending' AND
            i.request_no='".$row["request_no"]."'  AND i.material_type != 'Raw Material' AND 
            i.material_type != 'Packing Material' order by i.vendor_no";
                   
               }
         
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                   while ($row1 = $result1->fetch_assoc()) {
                       
                        $gross_total += floatval($row1["gross_total"]);
                        $gst_total += floatval($row1["gst_total"]);
                        $net_total += floatval($row1["net_total"]);
                        if ($row["required_for"] !== 'Own') {
                            $sql2 = "SELECT * FROM client WHERE client_code='".$row1["client_code"]."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                   $row1["client_name"] = $row2["company"];
                                }
                            }
                        } else {
                            $row1["client_code"] = "NA";
                        }
                        $row1["taxable_amt"] = '0';
                       $output1[] = $row1;
                    } 
                }
                $row["gross_total"] = $gross_total;
                $row["gst_total"] = $gst_total;
                $row["net_total"] = $net_total;
                $row["indends"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
        
        
        
        
        
    } 
    
    else if ($_GET["type"] == "getPendingMehaIndend") {
        
        
        
        
        $output = Array();
  
        
        
     
               
        if($_GET["apitype"] == '1'){
            
        $sql="SELECT MAX(id) as id,MAX(department) as department,MAX(amendment) as amendment, MAX(entry_by) as entry_by,MAX(entry_date) as entry_date,request_no,
        MAX(indend_no)as indend_no , 'Miscellaneous' as material_type,MAX(purchase_type) as purchase_type,MAX(i.material_type) as material_type2
        FROM indend_raw i  WHERE i.plant_id = '".$_GET["plant_id"]."'AND status='approve'  AND po_indend='pending' 
        AND  i.material_type != 'Raw Material' AND i.material_type != 'Packing Material'  Group by request_no 
        ORDER BY id DESC";
            
        }else if($_GET["apitype"] == '2'){
            
     
            
            
            
       $sql="SELECT MAX(id) as id,MAX(department) as department,MAX(amendment) as amendment, MAX(entry_by) as entry_by,MAX(entry_date) as entry_date,MAX(request_no) as request_no,
        indend_no ,   MAX(material_type) as material_type ,MAX(purchase_type) as purchase_type
        FROM indend_raw i  WHERE i.plant_id = '".$_GET["plant_id"]."'AND status='approve'  AND po_indend='pending' 
        AND  ( i.material_type = 'Raw Material' OR i.material_type = 'Packing Material')  Group by indend_no 
        ORDER BY id DESC";
 
            
        } 
        
  
     
        
         
     
     
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $gross_total = 0;
                $gst_total = 0;
                $net_total = 0; 
                
               $output1 = Array();
               
             
            
    
          
                   if($row["material_type"] == 'Raw Material'){         
                   
                $sql1 = "SELECT distinct i.indend_no , i.*, v.vendor_name,v.scode,m.material_type, m.material_subtype, m.material_name,0 as delivery_schedule_date, 0 
                as disc_amt,0 as disc_per ,(select hsn from material z where z.material_code=i.material_code limit 1) as hsn
                
                FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code  LEFT JOIN
                vendor v ON i.vendor_no=v.vendor_no 
              WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve' AND i.po_indend='pending' AND
              i.indend_no='".$row["indend_no"]."' AND  m.material_type = 'Raw Material'  order by i.vendor_no";
                   
               }else if($row["material_type"] == 'Packing Material'){
                   
                                      
                 $sql1 = "SELECT distinct i.indend_no , i.*, v.vendor_name,v.scode,m.material_type, m.material_subtype, m.material_name,0 as delivery_schedule_date, 0 
                as disc_amt,0 as disc_per FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code  LEFT JOIN
                vendor v ON i.vendor_no=v.vendor_no 
              WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve' AND i.po_indend='pending' AND
              i.indend_no='".$row["indend_no"]."' AND   m.material_type = 'Packing Material' order by i.vendor_no";
                   
               }
               
               else{
                   
            $sql1 = "SELECT distinct i.indend_no , i.*, v.vendor_name,v.scode,'Miscellaneous' as material_type,m.hsn, m.material_subtype, m.material_name,0 as delivery_schedule_date, 0 
            as disc_amt,0 as disc_per 
            
            FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code  LEFT JOIN
            vendor v ON i.vendor_no=v.vendor_no 
            WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve' AND i.po_indend='pending' AND
            i.request_no='".$row["request_no"]."'  AND i.material_type != 'Raw Material' AND 
            i.material_type != 'Packing Material' order by i.vendor_no";
                   
               }
         
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                   while ($row1 = $result1->fetch_assoc()) {
                       
                        $gross_total += floatval($row1["gross_total"]);
                        $gst_total += floatval($row1["gst_total"]);
                        $net_total += floatval($row1["net_total"]);
                        if ($row["required_for"] !== 'Own') {
                            $sql2 = "SELECT * FROM client WHERE user_no='".$_GET["user_no"]."' AND client_code='".$row1["client_code"]."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                   $row1["client_name"] = $row2["company"];
                                }
                            }
                        } else {
                            $row1["client_code"] = "NA";
                        }
                        $row1["taxable_amt"] = '0';
                       $output1[] = $row1;
                    } 
                }
                $row["gross_total"] = $gross_total;
                $row["gst_total"] = $gst_total;
                $row["net_total"] = $net_total;
                $row["indends"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
        
        
        
        
        
    } 
    
    
    else if ($_GET["type"] == "HogetPendingIndend") {
        
        
        
        
        $output = Array();
  
         
               
        if($_GET["apitype"] == '1'){
            
        $sql="SELECT MAX(id) as id,MAX(department) as department,MAX(amendment) as amendment, MAX(entry_by) as entry_by,MAX(entry_date) as entry_date,request_no,
        MAX(indend_no)as indend_no , 'Miscellaneous' as material_type,MAX(purchase_type) as purchase_type
        FROM indend_raw i  WHERE i.plant_id = '".$_GET["plantID"]."'AND status='approve'  AND po_indend='pending' 
        AND  i.material_type != 'Raw Material' AND i.material_type != 'Packing Material'  Group by request_no 
        ORDER BY id DESC";
            
        }else if($_GET["apitype"] == '2'){
            
        // $sql="SELECT MAX(id) as id,MAX(department) as department, MAX(entry_by) as entry_by,MAX(entry_date) as entry_date,MAX(request_no) as request_no,
        // indend_no ,   MAX(material_type) as material_type ,MAX(purchase_type) as purchase_type
        // FROM indend_raw i  WHERE i.plant_id = '".$_GET["plant_id"]."'AND status='approve'  AND po_indend='pending' 
        // AND  ( i.material_type = 'Raw Material' OR i.material_type = 'Packing Material')  Group by indend_no 
        // ORDER BY id DESC";
            
            
            
        $sql="SELECT MAX(id) as id,MAX(department) as department,MAX(amendment) as amendment, MAX(entry_by) as entry_by,MAX(entry_date) as entry_date,MAX(request_no) as request_no,
        indend_no ,   MAX(material_type) as material_type ,MAX(purchase_type) as purchase_type
        FROM indend_raw i  WHERE i.plant_id = '".$_GET["plantID"]."'AND status='approve'  AND po_indend='pending' 
        AND  ( i.material_type = 'Raw Material' OR i.material_type = 'Packing Material')  Group by indend_no 
        ORDER BY id DESC";
 
            
        } 
        
  
     
        
         
     
     
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $gross_total = 0;
                $gst_total = 0;
                $net_total = 0; 
                
               $output1 = Array();
               
             
            
    
          
                   if($row["material_type"] == 'Raw Material'){         
                   
                 $sql1 = "SELECT distinct i.indend_no , i.*, v.vendor_name,v.scode,m.material_type, m.material_subtype, m.material_name,0 as delivery_schedule_date, 0 
                as disc_amt,0 as disc_per FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code  LEFT JOIN
                vendor v ON i.vendor_no=v.vendor_no 
              WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve' AND i.po_indend='pending' AND
              i.indend_no='".$row["indend_no"]."' AND  m.material_type = 'Raw Material'  order by i.vendor_no";
                   
               }else if($row["material_type"] == 'Packing Material'){
                   
                                      
                 $sql1 = "SELECT distinct i.indend_no , i.*, v.vendor_name,v.scode,m.material_type, m.material_subtype, m.material_name,0 as delivery_schedule_date, 0 
                as disc_amt,0 as disc_per FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code  LEFT JOIN
                vendor v ON i.vendor_no=v.vendor_no 
              WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve' AND i.po_indend='pending' AND
              i.indend_no='".$row["indend_no"]."' AND   m.material_type = 'Packing Material' order by i.vendor_no";
                   
               }
               
               else{
                   
            $sql1 = "SELECT distinct i.indend_no , i.*, v.vendor_name,v.scode,'Miscellaneous' as material_type, m.material_subtype, m.material_name,0 as delivery_schedule_date, 0 
            as disc_amt,0 as disc_per FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code  LEFT JOIN
            vendor v ON i.vendor_no=v.vendor_no 
            WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve' AND i.po_indend='pending' AND
            i.request_no='".$row["request_no"]."'  AND i.material_type != 'Raw Material' AND 
            i.material_type != 'Packing Material' order by i.vendor_no";
                   
               }
         
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                   while ($row1 = $result1->fetch_assoc()) {
                       
                        $gross_total += floatval($row1["gross_total"]);
                        $gst_total += floatval($row1["gst_total"]);
                        $net_total += floatval($row1["net_total"]);
                        if ($row["required_for"] !== 'Own') {
                            $sql2 = "SELECT * FROM client WHERE user_no='".$_GET["user_no"]."' AND client_code='".$row1["client_code"]."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                   $row1["client_name"] = $row2["company"];
                                }
                            }
                        } else {
                            $row1["client_code"] = "NA";
                        }
                        $row1["taxable_amt"] = '0';
                       $output1[] = $row1;
                    } 
                }
                $row["gross_total"] = $gross_total;
                $row["gst_total"] = $gst_total;
                $row["net_total"] = $net_total;
                $row["indends"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
        
        
        
        
        
    } 
    
    else if ($_GET["type"] == "send_re1_vendor") {
          $materials = $input["indend_list"];
          
          
          $flag=0;
            
            for ($i = 0; $i < count($materials); $i++) {
                
                $material = $materials[$i];
                
             
                
                
                $sql = "INSERT INTO vendor_material_request (plant_id,material_code,vendor_no,vendor_name,order_qty,material_name,unit ) VALUES 
                        ('".$_GET["plant_id"]."', '".$material["material_code"]."',  '".$material["vendor_no"]."','".$material["vendor_name"]."',
                        '".$material["order_qty"]."', '".$material["material"]."', '".$material["unit"]."')";
                        
                if ($conn->query($sql)) {    
                    
                     
                } else {
                  $flag++;  
                      
                }
                        
                        
                        
            }
              
                if ($flag == 0) {    
                    echo "{\"status\":\"success\"}";
                } else {
                     echo "{\"status\":\"".$conn->error."\"}";
                }
    }
    else if ($_GET["type"] == "send_for_bulk_po") {
        if($input["selection"]=="From Quotation"){
         $materials = $input["material_list"];
          
          
          $flag=0;
            
            for ($i = 0; $i < count($materials); $i++) {
                
                $material = $materials[$i];
                

$jsonString_Vendor = $material["vendor"];
// $jsonString_quote_amt = $material["quote_amt"];

// Decode the JSON string into an associative array
$decodedData_Vendor = $jsonString_Vendor;
// $decodedData_quote_amt = $jsonString_quote_amt;

// Access the "name" property
$name = $decodedData_Vendor['vendor_name'];
$Vendor_id = $decodedData_Vendor['vendor_id'];
$quotation_amt = $decodedData_Vendor['quotation_amt'];
$quotation_per = $decodedData_Vendor['quotation_per'];
$quotation_hdr_id = $decodedData_Vendor['quote_hdr_id'];
 



                
                $sql = "INSERT INTO bulk_po_material( plant_id, material_code, material_name, material_grade, request_qty, po_qty, schedule_date, vendor_id,
                        quote_rate, quote_per, quote_hdr_id, order_unit, vendor_name) VALUES ('".$_GET["plant_id"]."','".$material["material_code"]."',
                        '".$material["material_name"]."','".$material["gradeName"]."','".$material["v_order_qty"]."','".$material["po_qty"]."',
                        '".$material["schedule_date"]."','".$Vendor_id."','".$quotation_amt."','".$quotation_per."',
                        '".$quotation_hdr_id."','".$material["order_unit"]."','".$name."')";
                        
                        
                        
                        
                if ($conn->query($sql)) {    
                    
                    
                    
                     
                } else {
                  $flag++;  
                      
                }
                
                $sql2="update vendor_material_request set status='Send For Bulk PO' where id='".$material["vendor_material_request_id"]."'";
                        $conn->query($sql2);
                        
                        
            }
              
                if ($flag == 0) {    
                    echo "{\"status\":\"success\"}";
                } else {
                     echo "{\"status\":\"".$conn->error."\"}";
                }
            
            
            
            
        }else if($input["selection"]=="From Vendor"){
          $materials = $input["material_list"];
          
        
          $flag=0;
            
            for ($i = 0; $i < count($materials); $i++) {
                
                $material = $materials[$i];
                

$jsonString_Vendor = $material["vendor"];
$jsonString_quote_amt = $material["quote_amt"];

// Decode the JSON string into an associative array
$decodedData_Vendor = $jsonString_Vendor;
$decodedData_quote_amt = $jsonString_quote_amt;

// Access the "name" property
$name = $decodedData_Vendor['name'];
$Vendor_id = $decodedData_Vendor['id'];
$quotation_amt = $decodedData_quote_amt['quotation_amt'];
$quotation_per = $decodedData_quote_amt['quotation_per'];
$quotation_hdr_id = $decodedData_quote_amt['quotation_hdr_id'];



                
                 $sql = "INSERT INTO bulk_po_material( plant_id, material_code, material_name, material_grade, request_qty, po_qty, schedule_date, vendor_id,
                        quote_rate, quote_per, quote_hdr_id, order_unit, vendor_name) VALUES ('".$_GET["plant_id"]."','".$material["material_code"]."',
                        '".$material["material_name"]."','".$material["gradeName"]."','".$material["require_qty"]."','".$material["po_qty"]."',
                        '".$material["schedule_date"]."','".$Vendor_id."','".$quotation_amt."','".$quotation_per."',
                        '".$quotation_hdr_id."','".$material["order_unit"]."','".$name."')";
                        
                        
                        
                        
                if ($conn->query($sql)) {    
                    
                    
                    
                     
                } else {
                  $flag++;  
                      
                }
                
                $sql2="update vendor_material_request set status='Send For Bulk PO' where id='".$material["vendor_material_request_id"]."'";
                        $conn->query($sql2);
                        
                        
            }
              
                if ($flag == 0) {    
                    echo "{\"status\":\"success\"}";
                } else {
                     echo "{\"status\":\"".$conn->error."\"}";
                }
        }
          
    }
    

    else if ($_GET["type"] == "getPendingIndendWO") {
        
        
        
        
        $output = Array();
    
        
   
                  $sql="SELECT max(i.id) as id, i.entry_by,i.entry_date,i.indend_no,i.material_type,i.purchase_type 
        FROM indend_raw i WHERE i.plant_id = '".$_GET["plant_id"]."' and i.user_no='".$_GET["user_no"]."' 
        AND i.status='approve'  AND i.po_indend='pending' AND i.order_type = 'Work Order'   Group by i.entry_by,i.entry_date,i.indend_no,i.material_type,i.purchase_type 
        ORDER BY 1 DESC";
            
       
     
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $gross_total = 0;
                $gst_total = 0;
                $net_total = 0; 
                
               $output1 = Array();
                $sql1 = "SELECT i.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve' AND i.po_indend='pending' AND i.vendor_no='".$row["vendor_no"]."'";
              $sql1 = "SELECT distinct i.indend_no , i.*, v.vendor_name,v.scode,m.material_type, m.material_subtype, m.material_name,0 as delivery_schedule_date, 0 
                as disc_amt,0 as disc_per FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code  LEFT JOIN
                vendor v ON i.vendor_no=v.vendor_no LEFT JOIN general_material g ON i.material_code=g.material_code
              WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve' AND i.po_indend='pending' AND
              i.indend_no='".$row["indend_no"]."'  order by i.vendor_no";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                   while ($row1 = $result1->fetch_assoc()) {
                        $gross_total += +$row1["gross_total"];
                        $gst_total += +$row1["gst_total"];
                        $net_total += +$row1["net_total"];
                        if ($row["required_for"] !== 'Own') {
                            $sql2 = "SELECT * FROM client WHERE user_no='".$_GET["user_no"]."' AND client_code='".$row1["client_code"]."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                   $row1["client_name"] = $row2["company"];
                                }
                            }
                        } else {
                            $row1["client_code"] = "NA";
                        }
                        $row1["taxable_amt"] = '0';
                       $output1[] = $row1;
                    } 
                }
                $row["gross_total"] = $gross_total;
                $row["gst_total"] = $gst_total;
                $row["net_total"] = $net_total;
                $row["indends"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
        
        
        
        
        
    } 
    
    
    else if ($_GET["type"] == "getAllPendingIndend") {
        $output = Array();
        //$sql = "SELECT i.*, v.vendor_name, v.email, v.gst_no, v.address_corporate as address FROM indend_raw i LEFT JOIN vendor v ON i.expected_vendor=v.vendor_no WHERE i.status='approve' AND i.indend_no NOT IN (SELECT indend_no FROM po_material WHERE indend_no !='')";
        $sql = "SELECT i.*, v.vendor_name, v.email, v.gst_no, v.address_corporate as address FROM indend_raw i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve' AND i.indend_no NOT IN (SELECT indend_no FROM po_material WHERE user_no='".$_GET["user_no"]."' AND indend_no !='') GROUP BY i.vendor_no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $gross_total = 0;
                $gst_total = 0;
                $net_total = 0;
                
                $output1 = Array();
                $sql1 = "SELECT i.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve' AND i.po_indend='pending' AND i.vendor_no='".$row["vendor_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $gross_total += +$row1["gross_total"];
                        $gst_total += +$row1["gst_total"];
                        $net_total += +$row1["net_total"];
                        
                        if ($row["required_for"] !== 'Own') {
                            $sql2 = "SELECT * FROM client WHERE user_no='".$_GET["user_no"]."' AND client_code='".$row1["client_code"]."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $row1["client_name"] = $row2["company"];
                                }
                            }
                        } else {
                            $row1["client_code"] = "NA";
                        }
                        
                        $output1[] = $row1;
                    }
                }
                $row["gross_total"] = $gross_total;
                $row["gst_total"] = $gst_total;
                $row["net_total"] = $net_total;
                $row["indends"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingPO") {
        $output = array();
        $sql="SELECT p.*, v.vendor_name, v.address, v.gst_no, v.city, v.state_code, v.state_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
        LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' AND p.user_no='".$_GET["user_no"]."' AND p.status='Pending' 
        AND p.department LIKE '%".$_GET["department_name"]."%' AND DATE(p.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' 
        and p.plant_id='".$_GET["plant_id"]."' ORDER BY p.id DESC";
        //$sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code, v.pincode, a.agent_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN agent a ON p.broker_name=a.agent_no WHERE p.user_no='".$_GET["user_no"]."' AND p.po_type='Raw Material' AND p.status='Pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                 $sql1 = "SELECT distinct p.id , p.*, m.material_name, '' as grade,m.material_subtype, m.material_subtype as msub_type,ir.department FROM po_material p LEFT JOIN master_material m 
                 ON p.material_code=m.material_code and p.plant_id = m.plant_id left join indend_raw ir on p.indend_no = ir.indend_no  and ir.material_code = p.material_code 
                 WHERE p.po_no='".$row["id"]."' AND (p.po_indend='Approve' OR p.material_status='pending')  ";
                 
               
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                           $row["material_category"] = $row["material_subtype"];
                        $output1[] = $row1;
                    }
                }
              
                $row["materials"] = $output1;
                // $row["additional_term"] = $output2;
                $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                $row["additional_term"]=json_decode($row["additional_terms"]);

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingPONew") {//Added By Devaraj
        $output = array();
        $sql="SELECT p.*, v.vendor_name, v.address, v.gst_no, v.city, v.state_code, v.state_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN state s ON v.state_code= s.state_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_type='Raw Material' AND p.status='Pending'  AND p.department LIKE '%".$_GET["department_name"]."%' AND DATE(p.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY p.id DESC";
        //$sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code, v.pincode, a.agent_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN agent a ON p.broker_name=a.agent_no WHERE p.user_no='".$_GET["user_no"]."' AND p.po_type='Raw Material' AND p.status='Pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                 $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.po_no='".$row["id"]."'AND  p.po_type='Raw Material' AND (p.po_indend='Approve' OR p.material_status='pending') GROUP BY p.id";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                           $row["material_category"] = $row["material_subtype"];
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
 

    
    
    else if ($_GET["type"] == "getAllPendingPOForNotification") {
       $output = array();
     
        $sql="SELECT count(*) as Pending_Po  FROM purchaseorder  WHERE  plant_id= '".$_GET["plant_id"]."' AND status='Pending' ORDER by id desc";
        
        $result = $conn->query($sql);
        // print_r($result);exit;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = $row;
            }
        }
        
        
        $test = "You Have '".$output['Pending_Po']."' Po Pending For Approval";
        
        $output['text'] = $test;
    
        echo json_encode($output);
    
    }

    else if ($_GET["type"] == "getAllPendingPO") {
            
        $output = array();
        header('Content-Type: application/json; charset=UTF-8');
     
        $po_type_filter = isset($_GET["po_type"]) ? trim($_GET["po_type"]) : '';
        $po_type_sql = poTypeFilterSql($conn, $po_type_filter);
        $pendingStatusFilter = "(p.status = 'Pending' OR LOWER(TRIM(p.status)) = 'pending')";
        $plantEsc = $conn->real_escape_string((string)$_GET["plant_id"]);
     
        $sql="SELECT p.*, v.vendor_name FROM purchaseorder p
        LEFT JOIN vendor v ON p.vendor_no = v.vendor_no
        WHERE p.plant_id = '".$plantEsc."'
        ".$po_type_sql." AND ".$pendingStatusFilter."
        AND EXISTS (
            SELECT 1 FROM po_material pm
            WHERE pm.po_no = p.id AND pm.plant_id = p.plant_id
        )
        ORDER BY p.id DESC";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $poNoEsc = $conn->real_escape_string((string)$row["id"]);
                
                $sql1 = "SELECT p.*,
                    COALESCE(
                        NULLIF(TRIM(m.material_name), ''),
                        NULLIF(TRIM(mat.material_name), ''),
                        NULLIF(TRIM(om.material_name), ''),
                        NULLIF(TRIM(ch.chemical_name), ''),
                        NULLIF(TRIM(gm.material_name), ''),
                        p.material_code
                    ) AS material_name,
                    COALESCE(NULLIF(TRIM(m.grade), ''), mat.grade) AS grade,
                    COALESCE(NULLIF(TRIM(m.material_subtype), ''), mat.material_subtype, om.material_subtype, gm.material_subtype) AS material_subtype,
                    COALESCE(NULLIF(TRIM(m.material_type), ''), mat.material_type, om.material_type, gm.material_type, p.po_type) AS material_type,
                    COALESCE(NULLIF(TRIM(m.hsn), ''), mat.hsn, om.hsn, gm.hsn) AS hsn
                    FROM po_material p
                    LEFT JOIN my_view m ON p.material_code = m.material_code
                    LEFT JOIN material mat ON p.material_code = mat.material_code
                    LEFT JOIN others_material om ON p.material_code = om.material_code
                    LEFT JOIN chemical ch ON p.material_code = ch.chemical_no
                    LEFT JOIN general_material gm ON p.material_code = gm.material_code
                    WHERE p.po_no = '".$poNoEsc."' AND p.plant_id = '".$plantEsc."'
                    ORDER BY p.id ASC";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1; 
                    }
                }

                if (count($output1) === 0) {
                    continue;
                }
                
                $final_total = $row["final_total"];
                $po_type     = $row["po_type"];

                $sql_approval = "SELECT * FROM purchaseApprovalMatrix WHERE $final_total BETWEEN poValueFrom AND poValueTo AND po_type = '$po_type'  ORDER BY (poValueTo - poValueFrom)  ASC LIMIT 1"; 
                $result11 = $conn->query($sql_approval);
                if ($result11 && $result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $row["poValueFrom"] = $row11["poValueFrom"];
                        $row["poValueTo"] = $row11["poValueTo"];
                        $row["firstApprover"] = $row11["firstApprover"];
                    }
                }else{
                        $row["poValueFrom"] = 'NA';
                        $row["poValueTo"] = 'NA';
                        $row["firstApprover"] = 'TO_PURCHASE_HEAD';
                }
                
                $row["materials"] = $output1;
                
                $row["terms_conditions"] = json_decode($row["terms_conditions"]);
                $row["gstSplitData"] = json_decode($row["gstSplitData"]);
                $row["paymentTerms"] = json_decode($row["paymentTerms"]);
                $row["schedule_data"] = json_decode($row["schedule_data"]);
                $row["selectedBill"] = json_decode($row["selectedBill"]);
                $row["selectedShip"] = json_decode($row["selectedShip"]);
                
                $output[] = $row;
            }
        }
        $jsonFlags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        echo json_encode($output, $jsonFlags);
    }
    else if ($_GET["type"] == "getAllPendingPOForPlantHeadApproval") {
            
        $output = array();
     
     
        $sql="SELECT p.*, v.vendor_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no = v.vendor_no WHERE  p.plant_id= '".$_GET["plant_id"]."'
        AND  p.po_type = '".$_GET["po_type"]."' AND p.status = 'TO_PLANT_HEAD' ORDER by p.id desc";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                
                $sql1 = "SELECT p.*, m.grade, m.material_subtype, m.material_name, m.material_type, m.hsn FROM po_material p LEFT JOIN my_view m ON p.material_code = m.material_code WHERE p.po_no = '".$row["id"]."'AND p.plant_id= '".$_GET["plant_id"]."'  ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1; 
                    }
                }
                
                
                $final_total = $row["final_total"]; // value coming from your purchase order table
                $po_type     = $row["po_type"];   // or whichever variable you use

                $sql_approval = "SELECT * FROM purchaseApprovalMatrix WHERE $final_total BETWEEN poValueFrom AND poValueTo AND po_type = '$po_type' AND secAppReq = 'YES' ORDER BY (poValueTo - poValueFrom)  ASC LIMIT 1"; 
                $result11 = $conn->query($sql_approval);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $row["poValueFrom"] = $row11["poValueFrom"];
                        $row["poValueTo"] = $row11["poValueTo"];
                        $row["secondtApprover"] = $row11["secondtApprover"];
                    }
                }else{
                        $row["poValueFrom"] = 'NA';
                        $row["poValueTo"] = 'NA';
                        $row["secondtApprover"] = 'TO_PURCHASE_HEAD';
                }
                
                $row["materials"] = $output1;
                
                $row["terms_conditions"] = json_decode($row["terms_conditions"]);
                $row["gstSplitData"] = json_decode($row["gstSplitData"]);
                $row["paymentTerms"] = json_decode($row["paymentTerms"]);
                $row["schedule_data"] = json_decode($row["schedule_data"]);
                $row["selectedBill"] = json_decode($row["selectedBill"]);
                $row["selectedShip"] = json_decode($row["selectedShip"]);
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getAllPendingPOForDirectorApproval") {
            
        $output = array();
     
     
        $sql="SELECT p.*, v.vendor_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no = v.vendor_no WHERE  p.plant_id= '".$_GET["plant_id"]."'
        AND  p.po_type = '".$_GET["po_type"]."' AND p.status = 'TO_DIRECTOR' ORDER by p.id desc";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                
                $sql1 = "SELECT p.*, m.grade, m.material_subtype, m.material_name, m.material_type, m.hsn FROM po_material p LEFT JOIN my_view m ON p.material_code = m.material_code WHERE p.po_no = '".$row["id"]."'AND p.plant_id= '".$_GET["plant_id"]."'  ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1; 
                    }
                }
                
                
                $final_total = $row["final_total"]; // value coming from your purchase order table
                $po_type     = $row["po_type"];   // or whichever variable you use

                $sql_approval = "SELECT * FROM purchaseApprovalMatrix WHERE $final_total BETWEEN poValueFrom AND poValueTo AND po_type = '$po_type' AND secAppReq = 'YES' ORDER BY (poValueTo - poValueFrom)  ASC LIMIT 1"; 
                $result11 = $conn->query($sql_approval);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $row["poValueFrom"] = $row11["poValueFrom"];
                        $row["poValueTo"] = $row11["poValueTo"];
                        $row["secondtApprover"] = $row11["secondtApprover"];
                    }
                }else{
                        $row["poValueFrom"] = 'NA';
                        $row["poValueTo"] = 'NA';
                        $row["secondtApprover"] = 'TO_PURCHASE_HEAD';
                }
                
                $row["materials"] = $output1;
                
                $row["terms_conditions"] = json_decode($row["terms_conditions"]);
                $row["gstSplitData"] = json_decode($row["gstSplitData"]);
                $row["paymentTerms"] = json_decode($row["paymentTerms"]);
                $row["schedule_data"] = json_decode($row["schedule_data"]);
                $row["selectedBill"] = json_decode($row["selectedBill"]);
                $row["selectedShip"] = json_decode($row["selectedShip"]);
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getAllPendingPOForCFOApproval") {
            
        $output = array();
     
     
        $sql="SELECT p.*, v.vendor_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no = v.vendor_no WHERE  p.plant_id= '".$_GET["plant_id"]."'
        AND  p.po_type = '".$_GET["po_type"]."' AND p.status = 'TO_CFO' ORDER by p.id desc";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                
                $sql1 = "SELECT p.*, m.grade, m.material_subtype, m.material_name, m.material_type, m.hsn FROM po_material p LEFT JOIN my_view m ON p.material_code = m.material_code WHERE p.po_no = '".$row["id"]."'AND p.plant_id= '".$_GET["plant_id"]."'  ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1; 
                    }
                }
                
                
                $final_total = $row["final_total"]; // value coming from your purchase order table
                $po_type     = $row["po_type"];   // or whichever variable you use

                $sql_approval = "SELECT * FROM purchaseApprovalMatrix WHERE $final_total BETWEEN poValueFrom AND poValueTo AND po_type = '$po_type' AND secAppReq = 'YES' ORDER BY (poValueTo - poValueFrom)  ASC LIMIT 1"; 
                $result11 = $conn->query($sql_approval);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $row["poValueFrom"] = $row11["poValueFrom"];
                        $row["poValueTo"] = $row11["poValueTo"];
                        $row["secondtApprover"] = $row11["secondtApprover"];
                        
                        if($row["status"] == $row["secondtApprover"]){
                            $row["secondtApprover"] = 'TO_PURCHASE_HEAD';
                        }
                        
                    }
                }else{
                        $row["poValueFrom"] = 'NA';
                        $row["poValueTo"] = 'NA';
                        $row["secondtApprover"] = 'TO_PURCHASE_HEAD';
                }
                
                $row["materials"] = $output1;
                
                $row["terms_conditions"] = json_decode($row["terms_conditions"]);
                $row["gstSplitData"] = json_decode($row["gstSplitData"]);
                $row["paymentTerms"] = json_decode($row["paymentTerms"]);
                $row["schedule_data"] = json_decode($row["schedule_data"]);
                $row["selectedBill"] = json_decode($row["selectedBill"]);
                $row["selectedShip"] = json_decode($row["selectedShip"]);
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getAllPendingPOForPurchaseHeadApproval") {
            
        $output = array();
        header('Content-Type: application/json; charset=UTF-8');

        $po_type_filter = isset($_GET["po_type"]) ? trim($_GET["po_type"]) : '';
        $po_type_sql = poTypeFilterSql($conn, $po_type_filter);
        $plantEsc = $conn->real_escape_string((string)$_GET["plant_id"]);
        
        $sql="SELECT p.*, v.vendor_name FROM purchaseorder p
        LEFT JOIN vendor v ON p.vendor_no = v.vendor_no
        WHERE p.plant_id = '".$plantEsc."'
        ".$po_type_sql." AND p.status = 'TO_PURCHASE_HEAD'
        AND EXISTS (
            SELECT 1 FROM po_material pm
            WHERE pm.po_no = p.id AND pm.plant_id = p.plant_id
        )
        ORDER BY p.id DESC";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $poNoEsc = $conn->real_escape_string((string)$row["id"]);
                
                $sql1 = "SELECT p.*,
                    COALESCE(
                        NULLIF(TRIM(m.material_name), ''),
                        NULLIF(TRIM(mat.material_name), ''),
                        NULLIF(TRIM(om.material_name), ''),
                        NULLIF(TRIM(ch.chemical_name), ''),
                        NULLIF(TRIM(gm.material_name), ''),
                        p.material_code
                    ) AS material_name,
                    COALESCE(NULLIF(TRIM(m.grade), ''), mat.grade) AS grade,
                    COALESCE(NULLIF(TRIM(m.material_subtype), ''), mat.material_subtype, om.material_subtype, gm.material_subtype) AS material_subtype,
                    COALESCE(NULLIF(TRIM(m.material_type), ''), mat.material_type, om.material_type, gm.material_type, p.po_type) AS material_type,
                    COALESCE(NULLIF(TRIM(m.hsn), ''), mat.hsn, om.hsn, gm.hsn) AS hsn,
                (SELECT MIN(a.quotation_amt) FROM po_material a WHERE a.material_code = p.material_code) AS minQuatRate,
                (SELECT MAX(a.quotation_amt) FROM po_material a WHERE a.material_code = p.material_code) AS maxQuatRate,
                (SELECT v.vendor_name FROM po_material x JOIN vendor v ON v.vendor_no = x.vendor_no WHERE x.material_code = p.material_code ORDER BY x.quotation_amt ASC LIMIT 1) AS minVendorName,
                (SELECT v.vendor_name FROM po_material x JOIN vendor v ON v.vendor_no = x.vendor_no WHERE x.material_code = p.material_code ORDER BY x.quotation_amt DESC LIMIT 1) AS maxVendorName
                FROM po_material p
                LEFT JOIN my_view m ON p.material_code = m.material_code
                LEFT JOIN material mat ON p.material_code = mat.material_code
                LEFT JOIN others_material om ON p.material_code = om.material_code
                LEFT JOIN chemical ch ON p.material_code = ch.chemical_no
                LEFT JOIN general_material gm ON p.material_code = gm.material_code
                WHERE p.po_no = '".$poNoEsc."' AND p.plant_id = '".$plantEsc."'";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1; 
                    }
                }

                if (count($output1) === 0) {
                    continue;
                }
                
                $row["materials"] = $output1;
                
                $row["terms_conditions"] = json_decode($row["terms_conditions"]);
                $row["gstSplitData"] = json_decode($row["gstSplitData"]);
                $row["paymentTerms"] = json_decode($row["paymentTerms"]);
                $row["schedule_data"] = json_decode($row["schedule_data"]);
                $row["selectedBill"] = json_decode($row["selectedBill"]);
                $row["selectedShip"] = json_decode($row["selectedShip"]);
                
                $output[] = $row;
            }
        }
        $jsonFlags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        echo json_encode($output, $jsonFlags);
    }
    
    else if ($_GET["type"] == "approvePO"){
        
        $sql = "UPDATE purchaseorder SET status = '".$input["status"]."', approve_by = '".$_GET["emp_id"]."', approve_date = '".$entry_date."' WHERE id = '".$input["id"]."' ";
        
        if ($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
        
    }
    else if ($_GET["type"] == "approvePOByPlantHead"){
        
        $sql = "UPDATE purchaseorder SET status = '".$input["status"]."', ph_approve_by = '".$_GET["emp_id"]."', ph_approve_on = '".$entry_date."' WHERE id = '".$input["id"]."' ";
        
        if ($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
        
    }
    else if ($_GET["type"] == "directorApproval"){
        
        $sql = "UPDATE purchaseorder SET status = '".$input["status"]."', director_approve_by = '".$_GET["emp_id"]."', director_approve_date = '".$entry_date."' WHERE id = '".$input["id"]."' ";
        
        if ($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
        
    }
    else if ($_GET["type"] == "cfoApproval"){
        
        $sql = "UPDATE purchaseorder SET status = '".$input["status"]."', vp_approve_by = '".$_GET["emp_id"]."', vp_approve_on = '".$entry_date."' WHERE id = '".$input["id"]."' ";
        
        if ($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
        
    }
    else if ($_GET["type"] == "approvePOFromPurchaseHead"){
        
        $sql = "UPDATE purchaseorder SET status = '".$input["status"]."', purHeadAppveBy = '".$_GET["emp_id"]."', purHeadAppveOn = '".$entry_date."' WHERE id = '".$input["id"]."' ";
        
        if ($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
        
    }

    
    else if ($_GET["type"] == "checkPO") {
        $final_total = floatval($_GET["shipping_handling"])+floatval($_GET["other_charges"]);
        $sql = "UPDATE purchaseorder SET status='".$_GET["status"]."',remark='".$_GET["remark"]."', 
        transport='".$_GET["dispatch_through"]."',shipping_handling='".$_GET["shipping_handling"]."',other_charges='".$_GET["other_charges"]."', 
        final_total=final_total+".$final_total.",
        approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
       // echo $sql;
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
    else if ($_GET["type"] == "savebulkDirectPO") {
        $final_total = floatval($input["final_total"])+floatval($input["shipping_total"]);
         $sql = "UPDATE purchaseorder SET status='pending', billcompany_code='".$input["billcompany_code"]."', shipcompany_code='".$input["shipcompany_code"]."',
      shipping_handling='".$input["shipping_total"]."', shipping_gst='".$input["shipping_charges_gst"]."', additional_term='".json_encode($input["add_term"])."',
      gstSplitData='".json_encode($input["gstSplitData"])."', terms_conditions='".json_encode($input["terms_conditions"])."',final_total='".$final_total."'
      
      WHERE po_no='".$_GET["po_no"]."'";
       // echo $sql;
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
    else if ($_GET["type"] == "getPOLog") {
        $output = array();
        $sql = "SELECT p.*, v.vendor_name, v.address, v.gst_no, v.city, v.state_code, v.state_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."'ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."'";
                //echo $sql1;                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                            $row["material_category"] = $row["material_subtype"];
                        $output1[] = $row1;
                    }
                }
                  $row["materials"] = $output1;
                $output[] = $row;
             
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveTransportCost") {
    
         $sql = "UPDATE purchaseorder SET isLanding = 'Done', transportCost='".$input["transportCost"]."', transportTaxAmt='".$input["transportTaxAmt"]."', 
        transportCosts = '".json_encode($input["transportCosts"])."',totalTransportationCose='".$input["totalTransportationCose"]."'
        WHERE id='".$input["poId"]."'";
    
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    
    
    else if ($_GET["type"] == "getAllPOLog") {
            
        $output = array();
        header('Content-Type: application/json; charset=UTF-8');
     
        $po_type_filter = isset($_GET["po_type"]) ? trim($_GET["po_type"]) : '';
        $po_type_sql = poTypeFilterSql($conn, $po_type_filter);
        $plantEsc = $conn->real_escape_string((string)$_GET["plant_id"]);

        $sql="SELECT p.*, v.vendor_name FROM purchaseorder p
        LEFT JOIN vendor v ON p.vendor_no = v.vendor_no
        WHERE p.plant_id = '".$plantEsc."'
        ".$po_type_sql."
        AND EXISTS (
            SELECT 1 FROM po_material pm
            WHERE pm.po_no = p.id AND pm.plant_id = p.plant_id
        )
        ORDER BY p.id DESC";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $poNoEsc = $conn->real_escape_string((string)$row["id"]);
                
                $sql1 = "SELECT p.*,
                    COALESCE(
                        NULLIF(TRIM(m.material_name), ''),
                        NULLIF(TRIM(mat.material_name), ''),
                        NULLIF(TRIM(om.material_name), ''),
                        NULLIF(TRIM(ch.chemical_name), ''),
                        NULLIF(TRIM(gm.material_name), ''),
                        p.material_code
                    ) AS material_name,
                    COALESCE(NULLIF(TRIM(m.grade), ''), mat.grade) AS grade,
                    COALESCE(NULLIF(TRIM(m.material_subtype), ''), mat.material_subtype, om.material_subtype, gm.material_subtype) AS material_subtype,
                    COALESCE(NULLIF(TRIM(m.material_type), ''), mat.material_type, om.material_type, gm.material_type, p.po_type) AS material_type,
                    COALESCE(NULLIF(TRIM(m.hsn), ''), mat.hsn, om.hsn, gm.hsn) AS hsn
                    FROM po_material p
                    LEFT JOIN my_view m ON p.material_code = m.material_code
                    LEFT JOIN material mat ON p.material_code = mat.material_code
                    LEFT JOIN others_material om ON p.material_code = om.material_code
                    LEFT JOIN chemical ch ON p.material_code = ch.chemical_no
                    LEFT JOIN general_material gm ON p.material_code = gm.material_code
                    WHERE p.po_no = '".$poNoEsc."' AND p.plant_id = '".$plantEsc."'
                    ORDER BY p.id ASC";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1; 
                    }
                }

                if (count($output1) === 0) {
                    continue;
                }
                
                $row["materials"] = $output1;
                
                $row["terms_conditions"] = json_decode($row["terms_conditions"]);
                $row["gstSplitData"] = json_decode($row["gstSplitData"]);
                $row["paymentTerms"] = json_decode($row["paymentTerms"]);
                $row["schedule_data"] = json_decode($row["schedule_data"]);
                $row["selectedBill"] = json_decode($row["selectedBill"]);
                $row["selectedShip"] = json_decode($row["selectedShip"]);
                
                $output[] = $row;
            }
        }
        $jsonFlags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        echo json_encode($output, $jsonFlags);
        
    }
    else if ($_GET["type"] == "getApprovedPOForBarcodePrinting") {
            
        $output = array();
     
     
        $sql="SELECT p.*, v.vendor_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no = v.vendor_no WHERE  p.plant_id= '".$_GET["plant_id"]."'
        AND  p.po_type = '".$_GET["po_type"]."' ORDER by p.id desc";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                
                $sql1 = "SELECT p.*, m.grade, m.material_subtype, m.material_name, m.material_type, m.hsn FROM po_material p LEFT JOIN my_view m ON p.material_code = m.material_code WHERE p.po_no = '".$row["id"]."'AND p.plant_id= '".$_GET["plant_id"]."'  ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1['NoOfBarcode'] = 1;
                        $matCode = mysqli_real_escape_string($conn, $row1['material_code']);
                        $poNo = mysqli_real_escape_string($conn, $row['po_no']);
                        $trackingId = '';

                        if (!empty($row1['trackingId'])) {
                            $trackingId = trim($row1['trackingId']);
                        } else {
                            $sqlTrack = "SELECT sb.trackingId FROM sampling_batches sb
                                INNER JOIN challan_materials cm ON cm.material_code = sb.material_code AND cm.challan_no = sb.challan_no
                                INNER JOIN challan ch ON (ch.challan_no = cm.challan_no OR ch.ch_no = cm.challan_no)
                                WHERE cm.material_code = '".$matCode."' AND ch.po_no = '".$poNo."'
                                AND IFNULL(sb.trackingId, '') != ''
                                ORDER BY sb.id DESC LIMIT 1";
                            $resTrack = $conn->query($sqlTrack);
                            if ($resTrack && $resTrack->num_rows > 0) {
                                $trackRow = $resTrack->fetch_assoc();
                                $trackingId = trim($trackRow['trackingId']);
                            }
                        }

                        if ($trackingId === '') {
                            $trackingId = $row['po_no'].'-'.$row1['material_code'];
                        }

                        $row1['trackingId'] = $trackingId;
                        $output1[] = $row1;
                    }
                }
                
                $row["materials"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "HogetAllPOLog") {
        $output = array();
                $sql="SELECT distinct p.id , p.*,p.additional_term as aterm, v.vendor_name,v.contact_email, v.address, v.address_factory,v2.vendor_name as manufacturar, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no 
          LEFT JOIN indend_raw i ON i.indend_no=p.indent_no left join vendor v2 on i.manufacturer_no=v2.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plantID"]."'  AND p.status != 'Sent_To_Amendment'
          ORDER BY p.id DESC";
        
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                  $sql1 = "SELECT distinct p.id , p.*,m.material_type, m.material_name, m.grade as mgradeName, 
                 m.material_subtype  FROM po_material p 
                 LEFT JOIN my_view m ON p.material_code=m.material_code 
                 WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' ";
             
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                            if($row1['material_type'] == 'Raw Material' || $row1['material_type'] == 'Packing Material'){
                
                                if (!empty($row1['mgradeName'])) {
                                    $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade where id in (" . $row1['mgradeName'] . ")";
                                    $resQ = $conn->query($q);
                                
                                    if ($resQ !== false) {
                                        $prodLatest = $resQ->fetch_assoc();
                                        $row1['gradeName'] = $prodLatest['gradeName'];
                                    } else {
                                        // Handle the query error
                                        echo "Query Error: " . mysqli_error($conn);
                                    }
                
                                }else{
                                     $row1['gradeName'] = $prodLatest['mgradeName'];
                                }
            
                            }
                            
                                $output1[] = $row1;
                    }
                }
               $row["materials"] = $output1;
                 $row["gstSplitData"]=json_decode($row["gstSplitData"]);
                                 $row["schedule_data"]=json_decode($row["schedule_data"]);

                 $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                 $row["additional_term"]=json_decode($row["aterm"]);
         
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "getAllRevisedPOLog") {
        $output = array();
                $sql="SELECT distinct p.id , p.*,p.additional_term as aterm, v.vendor_name,v.contact_email, v.address, v.address_factory,v2.vendor_name as manufacturar, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no 
          LEFT JOIN indend_raw i ON i.indend_no=p.indent_no left join vendor v2 on i.manufacturer_no=v2.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."'  AND p.status = 'Revised'
          ORDER BY p.id DESC";
        
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                
                
                
         
                    
                 $sql1 = "SELECT distinct p.id , p.*,m.material_type, m.material_name, m.grade as mgradeName, 
                 m.material_subtype  FROM po_material p 
                 LEFT JOIN my_view m ON p.material_code=m.material_code 
                 WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' ";
             
                
                 
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
          while ($row1 = $result1->fetch_assoc()) {
              
              

                if($row1['material_type'] == 'Raw Material' || $row1['material_type'] == 'Packing Material'){
    
                        if (!empty($row1['mgradeName'])) {
                    $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade where id in (" . $row1['mgradeName'] . ")";
                    $resQ = $conn->query($q);
                
                    if ($resQ !== false) {
                        $prodLatest = $resQ->fetch_assoc();
                        $row1['gradeName'] = $prodLatest['gradeName'];
                    } else {
                        // Handle the query error
                        echo "Query Error: " . mysqli_error($conn);
                    }
                    
    
                }else{
                     $row1['gradeName'] = $prodLatest['mgradeName'];
                }

                }
                
                    $output1[] = $row1;
                }
                }
               $row["materials"] = $output1;
                 $row["gstSplitData"]=json_decode($row["gstSplitData"]);
                                 $row["schedule_data"]=json_decode($row["schedule_data"]);

                 $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                 $row["additional_term"]=json_decode($row["aterm"]);
         
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPoForAmendment") {
        $output = array();
        // Minimal select for amendment list - avoid missing plant-specific columns
        $plantId = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : '');
        $sql = "SELECT DISTINCT
                    p.id AS po_id,
                    p.id,
                    p.po_no,
                    p.po_type,
                    p.indent_no,
                    p.vendor_no,
                    p.status,
                    p.entry_date,
                    p.approve_date,
                    v.vendor_name
                FROM purchaseorder p
                LEFT JOIN vendor v ON p.vendor_no = v.vendor_no
                WHERE p.plant_id = '".$plantId."'
                  AND p.status IN ('Sent_To_Amendment','TO_AMENDMENNT')
                ORDER BY p.id DESC";

        $result = @$conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
     else if ($_GET["type"] == "getAllPOApproveLog") {
        $output = array();
                $sql="SELECT distinct p.id , p.*,p.additional_term as aterm, v.vendor_name,v.contact_email, v.address, v.address_factory,v2.vendor_name as manufacturar, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no 
          LEFT JOIN indend_raw i ON i.indend_no=p.indent_no left join vendor v2 on i.manufacturer_no=v2.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."'   AND
         p.status in('Approved','Rejected','pending') ORDER BY p.id DESC";
        
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                
                
                
         
                    
                 $sql1 = "SELECT distinct p.id , p.*,m.material_type, m.material_name, m.grade as mgradeName, 
                 m.material_subtype  FROM po_material p 
                 LEFT JOIN my_view m ON p.material_code=m.material_code 
                 WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' ";
             
                
                 
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
          while ($row1 = $result1->fetch_assoc()) {
              
              

                if($row1['material_type'] == 'Raw Material' || $row1['material_type'] == 'Packing Material'){
    
                        if (!empty($row1['mgradeName'])) {
                    $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade where id in (" . $row1['mgradeName'] . ")";
                    $resQ = $conn->query($q);
                
                    if ($resQ !== false) {
                        $prodLatest = $resQ->fetch_assoc();
                        $row1['gradeName'] = $prodLatest['gradeName'];
                    } else {
                        // Handle the query error
                        echo "Query Error: " . mysqli_error($conn);
                    }
                    
    
                }else{
                     $row1['gradeName'] = $prodLatest['mgradeName'];
                }


                    
                    
                    
                    
                    
                    
                }
                
                    $output1[] = $row1;
                }
                }
               $row["materials"] = $output1;
                 $row["gstSplitData"]=json_decode($row["gstSplitData"]);
                                 $row["schedule_data"]=json_decode($row["schedule_data"]);

                 $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                 $row["additional_term"]=json_decode($row["aterm"]);
         
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getAllPOLog1") {
       $output = array();
     
           $sql="SELECT distinct p.id, p.*, v.vendor_name, v.address, v.gst_no, v.city, v.state_code, v.state_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
          LEFT JOIN state s ON v.state_code= s.state_code WHERE  p.vendor_no= '".$_GET["vendor_no"]."'  ORDER by p.id desc";
        
        $result = $conn->query($sql);
      
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                
                $sql1 = "SELECT distinct p.id , p.*, m.grade as graName, m.material_subtype, m.material_name,m.material_type  FROM po_material p LEFT JOIN my_view m ON p.material_code=m.material_code LEFT JOIN general_material g ON p.material_code=g.material_code WHERE p.po_no='".$row["id"]."'AND  (p.po_indend='Approve' OR p.material_status='pending') ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                           $row["material_category"] = $row["material_subtype"];
                    $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row1['gradeName']."')";
                     $resQ = $conn->query($q);
                      $prodLatest = $resQ->fetch_assoc(); 
         
                      $row1['gradeName'] = $prodLatest['gradeName']; 
                        $output1[] = $row1;
                        
                    }
                }
                $row["materials"] = $output1;
                $row["schedule_data"]=json_decode($row["schedule_data"]);
                $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                  $row["additional_term"]=json_decode($row["additional_term"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "search_po") {
        $output = array();
           $sql="SELECT distinct p.id , p.*,p.additional_term as aterm, v.vendor_name, v.address, v.address_factory,v2.vendor_name as manufacturar, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no 
          LEFT JOIN indend_raw i ON i.indend_no=p.indent_no left join vendor v2 on i.manufacturer_no=v2.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' AND ( p.po_no LIKE '%".$_GET["value"]."%' OR p.indent_no LIKE '".$_GET["value"]."%' OR v.vendor_name LIKE '".$_GET["value"]."%')
         AND p.status in('approved','Cancelled','on hold','Rejected') ORDER BY p.id DESC";
        
    
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                 $sql1 = "SELECT distinct p.id , p.*, m.material_name, m.grade as mgradeName, 
                 m.material_subtype, m.material_subtype as msub_type,g.material_subtype as material_subtype1 FROM po_material p 
                 LEFT JOIN master_material m ON p.material_code=m.material_code 
             LEFT JOIN general_material g ON p.material_code=g.material_code WHERE p.user_no='".$_GET["user_no"]."' 
             AND p.po_no='".$row["id"]."' ";
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
          while ($row1 = $result1->fetch_assoc()) {

    if (!empty($row1['mgradeName'])) {
    $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade where id in (" . $row1['mgradeName'] . ")";
    $resQ = $conn->query($q);

    if ($resQ !== false) {
        $prodLatest = $resQ->fetch_assoc();
        $row1['gradeName'] = $prodLatest['gradeName'];
    } else {
        // Handle the query error
        echo "Query Error: " . mysqli_error($conn);
    }
}

    $output1[] = $row1;
}
                }
               $row["materials"] = $output1;
                 $row["gstSplitData"]=json_decode($row["gstSplitData"]);
                 $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                 $row["additional_term"]=json_decode($row["aterm"]);
                 //$row["additional_term"]=json_decode($row["aterm"]);
        
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    }
        else if($_GET['type'] == 'getpurcheslog') {
        $_GET['filename'] = 'Purches Report'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.='
        <h2 style="text-align:center"> Purchase Report</h2>
        
        <table style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
             <td style="width: 20px;font-size: 9px" colspan="10">Sr.No</td>
             <td style="width: 75px;font-size: 9px">Po Type</td>
             <td style="width: 60px;font-size: 9px">Approvel Date.</td>
             <td style="width: 60px;font-size: 9px">Po date</td>
             <td style="width: 60px;font-size: 9px">PO No.</td>
             <td style="width: 55px;font-size: 9pxfont-size: 10px">Indend No</td>
             <td style="width: 40px;font-size: 9px">Vendor Name</td>
             <td style="width: 40px;font-size: 9px">Gross Total</td>
             <td style="width: 40px;font-size: 9px">Total Tax</td>
             <td style="width: 40px;font-size: 9px">Net Total</td>
             <td style="width: 55px;font-size: 9px">Status</td>
              
         </tr>';
        
       $sql="SELECT distinct p.id , p.*,p.additional_term as aterm, v.vendor_name, v.address, v.address_factory,v2.vendor_name as manufacturar, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no 
          LEFT JOIN indend_raw i ON i.indend_no=p.indent_no left join vendor v2 on i.manufacturer_no=v2.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         p.status in('approved','Cancelled','on hold','Rejected') ORDER BY p.id DESC";
      
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                
                $html.='
                <tr>
                     <td style="width: 20px;font-size: 9px">'.$i.'</td>
                     <td style="width: 75px;font-size: 9px">'.$row["vendor_name"].'</td>
                     <td style="width: 60px;font-size: 9px">'.$row["po_type"].'</td>
                     <td style="width: 60px;font-size: 9px">'. date('d-m-Y', strtotime($row["approve_date"])).'</td>
                     <td style="width: 60px;font-size: 9px">'.$row["p_no1"].'</td>
                     <td style="width: 55px;font-size: 9px">'.$row["indent_no"].'</td>
                     <td style="width: 40px;font-size: 9px">'.$row["gross_total"].'</td>
                     <td style="width: 40px;font-size: 9px">'.$row["gst_total"].'</td>
                     <td style="width: 40px;font-size: 9px">'.$row["other_charges"].'</td>
                     <td style="width: 40px;font-size: 9px">'.$row["net_total"].'</td>
                     <td style="width: 55px;font-size: 9px">'.$row["status"].'</td>
                     
                </tr>';
                $i++;
            }
        }  
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('puercheslog.pdf', 'I');
    }
    
    
    
    else if ($_GET["type"] == "getAllPOLog2") {
        $output = array();
             $sql="SELECT p.*,p.additional_term as aterm, v.vendor_name, v.address, v.address_factory, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         p.status='Rejected' ORDER BY p.id DESC";
        
       /* $sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_name, v.state_code, v.pincode, c.company_name,
        c.mobile_no, v.address, c1.company_name as company_name1 , c1.mobile_no as mobile_no1, c1.address as address1  FROM purchaseorder 
        p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN company c ON p.billcompany_code=c.company_code LEFT JOIN company 
        c1 ON p.shipcompany_code=c1.company_code WHERE   p.plant_id='".$_GET["plant_id"]."' and p.status ='approve' ORDER BY p.id DESC";
    echo $sql;*/
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                 $sql1 = "SELECT distinct p.id , p.*, m.material_name, m.grade as gradeName, m.material_subtype, m.material_subtype as msub_type,g.material_subtype as material_subtype1 FROM po_material p 
                 LEFT JOIN master_material m ON p.material_code=m.material_code 
             LEFT JOIN general_material g ON p.material_code=g.material_code WHERE p.user_no='".$_GET["user_no"]."' 
             AND p.po_no='".$row["id"]."' ";
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['gradeName']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
                        
                        $output1[] = $row1;
                    }
                }
               $row["materials"] = $output1;
                 $row["gstSplitData"]=json_decode($row["gstSplitData"]);
                 $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                 $row["additional_term"]=json_decode($row["aterm"]);
                 //$row["additional_term"]=json_decode($row["aterm"]);
        
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "postatus") {
        $output = array();
             $sql="SELECT p.entry_date, p.status, p.po_type,p.po_no , c1.receiving ,s.grn_no,s.status as sample_status , t.status as qc_status FROM purchaseorder p left Join challan c ON c.po_no = p.po_no 
             LEFT JOIN challan_materials c1 ON c1.challan_no = c.challan_no LEFT JOIN sampling s ON c1.grn_no = s.grn_no
             LEFT JOIN testing t ON c1.grn_no = t.grn_no WHERE   p.plant_id= '".$_GET["plant_id"]."'   ORDER BY p.id DESC";
     
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getMisLog") {
        
	$output = Array();
         
    //   	$sql = "SELECT *, a.po_no as po_no12 FROM purchaseorder a left join challan b on a.po_no=b.po_no left JOIN vendor c on b.vendor_no=c.vendor_no LEFT JOIN po_material d on a.id=d.po_no LEFT
    //   	     JOIN vendor v ON a.vendor_no=v.vendor_no  JOIN material e on d.material_code=e.material_code where a.po_no  IN (SELECT po_no FROM challan where plant_id= '".$_GET["plant_id"]."') order by a.id desc"; 
      	  $sql = "SELECT a.*,b.*,c.*,e.*,d.material_id, d.gst_id, d.material_code, d.qty, d.unit, d. requirement, d.days, d.quotation, d.required_for, d.client_code, d.vendor_no, d.quotation_no, d.quotation_amt,d.quotation_per, d.disc_per, d.disc_amt, d.gst, d.gross_total, d.tax_total, d.net_total,d.indend_no, d.isreceive, d.po_indend, d.po_type, d.material_status, d.chemical_no, 
 d.delivery_schedule_date, d.descriptions_list   FROM purchaseorder a left join challan b on a.po_no=b.po_no left JOIN vendor c on b.vendor_no=c.vendor_no LEFT JOIN po_material d on a.id=d.po_no   JOIN material e on d.material_code=e.material_code where a.po_no  IN (SELECT po_no FROM challan where plant_id= '".$_GET["plant_id"]."') order by a.id desc"; 
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        // $output = array();
        //   $sql="SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_name, v.state_code, v.pincode, c.tax_invoice_date,
        //   c.tax_invoice,c.material_type FROM purchaseorder p LEFT JOIN challan c ON c.po_no=p.po_no LEFT JOIN vendor v ON p.vendor_no=v.vendor_no 
        //   LEFT JOIN state s ON v.state_code= s.state_code
        //   WHERE p.plant_id= '".$_GET["plant_id"]."' ORDER BY p.id DESC";
       
        // $result = $conn->query($sql);
        // if ($result->num_rows > 0) {
        //     while ($row = $result->fetch_assoc()) {

        //         $output1 = array();
        //         $sql1 = "SELECT distinct p.id , p.*, m.material_name, m.grade as gradeName, m.material_subtype FROM po_material p LEFT JOIN master_material m ON p.material_code=m.material_code
        //         WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' ";
                 
        //         $result1 = $conn->query($sql1);
        //         if ($result1->num_rows > 0) {
        //             while ($row1 = $result1->fetch_assoc()) {
        // //                  $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['gradeName']."')";
        // //      $resQ = $conn->query($q);
        // //       $prodLatest = $resQ->fetch_assoc(); 
         
        // //   $row1['gradeName'] = $prodLatest['gradeName']; 
                        
        //                 $output1[] = $row1;
        //             }
        //         }
        //       $row["materials"] = $output1;
        //          $row["terms_conditions"]=json_decode($row["terms_conditions"]);
        //          $row["additional_term"]=json_decode($row["additional_term"]);
        
        //         $output[] = $row;
                
        //     }
        // }
        // echo json_encode($output);
    }
    else if ($_GET["type"] == "getMisLogByMonth") {
        
	$output = Array();
 
          
    //  	  $sql = "SELECT *, a.po_no as po_no12 FROM purchaseorder a left join challan b on a.po_no=b.po_no left JOIN vendor c on b.vendor_no=c.vendor_no LEFT JOIN po_material d on a.id=d.po_no LEFT JOIN vendor v ON a.vendor_no=v.vendor_no JOIN material e on d.material_code=e.material_code where 
    // d.po_date LIKE '%".$_GET['mont']."%' AND a.po_no  IN (SELECT po_no FROM challan where plant_id= '".$_GET["plant_id"]."')    order by a.id desc";
    
    	$sql = "SELECT a.*,b.*,c.*,e.*,d.material_id, d.gst_id, d.material_code, d.qty, d.unit, d. requirement, d.days, d.quotation, d.required_for, d.client_code, d.vendor_no, d.quotation_no, d.quotation_amt,d.quotation_per, d.disc_per, d.disc_amt, d.gst, d.gross_total, d.tax_total, d.net_total,d.indend_no, d.isreceive, d.po_indend, d.po_type, d.material_status, d.chemical_no, 
 d.delivery_schedule_date, d.descriptions_list   FROM purchaseorder a left join challan b on a.po_no=b.po_no left JOIN vendor c on b.vendor_no=c.vendor_no LEFT JOIN po_material d on a.id=d.po_no   JOIN material e on d.material_code=e.material_code where b.po_date LIKE '%".$_GET['mont']."%' AND a.po_no  IN (SELECT po_no FROM challan where plant_id= '".$_GET["plant_id"]."') order by a.id desc"; 
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

    }
    else if ($_GET["type"] == "getRawlog") {   
        $output = array();
           $sql="SELECT DISTINCT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         po_type='Raw Material' ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                $sql1 = "SELECT distinct p.id , p.*, m.material_name, m.grade as gradeName, m.material_subtype FROM po_material p LEFT JOIN master_material m ON p.material_code=m.material_code
                WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' ";
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['gradeName']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
                        
                        $output1[] = $row1;
                    }
                }
               $row["materials"] = $output1;
                 $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                 $row["additional_term"]=json_decode($row["additional_term"]);
        
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getPackinglog") {   
        $output = array();
           $sql="SELECT DISTINCT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         po_type='Packing Material' ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                $sql1 = "SELECT distinct p.id , p.*, m.material_name, m.grade as gradeName, m.material_subtype FROM po_material p LEFT JOIN master_material m ON p.material_code=m.material_code
                WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' ";
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['gradeName']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
                        
                        $output1[] = $row1;
                    }
                }
               $row["materials"] = $output1;
                 $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                 $row["additional_term"]=json_decode($row["additional_term"]);
        
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getGenerallog") {   
        $output = array();
           $sql="SELECT DISTINCT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         po_type='General Material' ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                $sql1 = "SELECT distinct p.id , p.*, m.material_name, m.grade as gradeName, m.material_subtype FROM po_material p LEFT JOIN master_material m ON p.material_code=m.material_code
                WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' ";
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['gradeName']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
                        
                        $output1[] = $row1;
                    }
                }
               $row["materials"] = $output1;
                 $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                 $row["additional_term"]=json_decode($row["additional_term"]);
        
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getChemicallog") {    
        $output = array();
           $sql="SELECT DISTINCT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         po_type='Chemical Material' ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                $sql1 = "SELECT distinct p.id , p.*, m.material_name, m.grade as gradeName, m.material_subtype FROM po_material p LEFT JOIN master_material m ON p.material_code=m.material_code
                WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' ";
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['gradeName']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
                        
                        $output1[] = $row1;
                    }
                }
               $row["materials"] = $output1;
                 $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                 $row["additional_term"]=json_decode($row["additional_term"]);
        
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getGlasswarelog") {   
        $output = array();
           $sql="SELECT DISTINCT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         po_type='Glassware Material' ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                $sql1 = "SELECT distinct p.id , p.*, m.material_name, m.grade as gradeName, m.material_subtype FROM po_material p LEFT JOIN master_material m ON p.material_code=m.material_code
                WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' ";
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['gradeName']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row1['gradeName'] = $prodLatest['gradeName']; 
                        
                        $output1[] = $row1;
                    }
                }
               $row["materials"] = $output1;
                 $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                 $row["additional_term"]=json_decode($row["additional_term"]);
        
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getAllPOLog12") {
        
        $output = array();
          $sql="SELECT distinct p.id , p.*,p.additional_term as aterm, v.vendor_name, v.address, v.address_factory,v2.vendor_name as manufacturar, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no 
          LEFT JOIN indend_raw i ON i.indend_no=p.indent_no left join vendor v2 on i.manufacturer_no=v2.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         p.status in('approved','Cancelled','on hold','Rejected') ORDER BY p.id DESC";
        
       /* $sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_name, v.state_code, v.pincode, c.company_name,
        c.mobile_no, v.address, c1.company_name as company_name1 , c1.mobile_no as mobile_no1, c1.address as address1  FROM purchaseorder 
        p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN company c ON p.billcompany_code=c.company_code LEFT JOIN company 
        c1 ON p.shipcompany_code=c1.company_code WHERE   p.plant_id='".$_GET["plant_id"]."' and p.status ='approve' ORDER BY p.id DESC";
    echo $sql;*/
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                 $sql1 = "SELECT distinct p.id , p.*, m.material_name, m.grade as mgradeName, 
                 m.material_subtype, m.material_subtype as msub_type,g.material_subtype as material_subtype1 FROM po_material p 
                 LEFT JOIN master_material m ON p.material_code=m.material_code 
             LEFT JOIN general_material g ON p.material_code=g.material_code WHERE p.user_no='".$_GET["user_no"]."' 
             AND p.po_no='".$row["id"]."' ";
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
          while ($row1 = $result1->fetch_assoc()) {

    if (!empty($row1['mgradeName'])) {
    $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade where id in (" . $row1['mgradeName'] . ")";
    $resQ = $conn->query($q);

    if ($resQ !== false) {
        $prodLatest = $resQ->fetch_assoc();
        $row1['gradeName'] = $prodLatest['gradeName'];
    } else {
        // Handle the query error
        echo "Query Error: " . mysqli_error($conn);
    }
}

    $output1[] = $row1;
}
                }
               $row["materials"] = $output1;
                 $row["gstSplitData"]=json_decode($row["gstSplitData"]);
                 $row["terms_conditions"]=json_decode($row["terms_conditions"]);
                 $row["additional_term"]=json_decode($row["aterm"]);
                 //$row["additional_term"]=json_decode($row["aterm"]);
        
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    
    } else if ($_GET["type"] == "getRejectedPO") {
        $output = array();
        $sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no WHERE p.user_no='".$_GET["user_no"]."' AND p.po_type='Raw Material' AND p.status='reject'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN 
                material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$_GET["id"]."' GROUP BY p.id";
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
    } else if ($_GET["type"] == "ammendPO") {
        $sql = "INSERT INTO purchaseorder (user_no, po_type, vendor_no, requirement_days, gross_total, gst_total, net_total, terms_conditions,
        entry_by, entry_date, discount, delivery_schedule_date, delivery_address) VALUES ('".$_GET["user_no"]."', 'Raw Material',
        '".$input["vendor_no"]."', '".$input["requirement_days"]."', '".$input["gross_total"]."', '".$input["gst_total"]."',
        '".$input["net_total"]."', '".json_encode($input["terms_conditions"])."', '".$_GET["emp_id"]."', '$entry_date', 
        '".$input["discount"]."', '".$input["delivery_schedule_date"]."', '".$input["delivery_address"]."')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            echo "{\"status\":\"success\"}";

            $sql = "UPDATE purchaseorder SET status='ammendment' WHERE id='".$input["id"]."'";
            $conn->query($sql);

            $materials = $input["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO po_material (user_no, po_no, material_code, qty, unit, requirement, 
                quotation_amt, gst, gross_total, tax_total, net_total, required_for, client_code) VALUES ('".$_GET["user_no"]."', '".$last_id."', '".$material["material_code"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["requirement"]."', '".$material["quotation_amt"]."', '".$material["gst"]."', '".$material["gross_total"]."', '".$material["tax_total"]."', '".$material["net_total"]."', '".$material["required_for"]."', '".$material["client_code"]."')";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPODetails") {
        $sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no WHERE p.user_no='".$_GET["user_no"]."' AND p.po_type='Raw Material' AND p.status='approve' AND p.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$_GET["id"]."' GROUP BY p.id";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $row["terms_conditions"] = json_decode($row["terms_conditions"]);
                echo json_encode($row);
            }
        }
   else {
            echo "{}";
        }
    }
    else if($_GET['type'] == "getMaterialsByType"){
         $sql = "SELECT * FROM master_material WHERE plant_id='".$_GET["plant_id"]."' AND material_subtype LIKE '%".$_GET["material_subtype"]."'
         AND material_nature LIKE '%".$_GET["material_nature"]."'   ORDER BY material_name";
        
         $result = $conn->query($sql);
         if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $row["equivalent"] = json_decode($row["equivalent"]);
                     $output1 = Array();
                     $sql1 = "SELECT * FROM quotation_rates_by_product where material_code='".$row["material_code"]."' and vendor_id='".$_GET["vendor_id"]."'";
                     $result1 = $conn->query($sql1);
                     if($result->num_rows > 0){
                         while($row1 = $result1->fetch_assoc())
                         {
                             $output1[] = $row1;
                         }
                     }
                     $row["quotations"] = $output1;
                    
                    $output[] = $row;
            }
         }
            echo json_encode($output);
    
    }
    else if ($_GET["type"] == "saveMaterials_by_vendor") {
        $sql = "INSERT INTO master_material (material_type, material_code,material_sub_type_id, material_subtype, 
        material_name) 
        VALUES ('".$input["material_type"]."','".$input["material_code"]."', '".$input["material_sub_type_id"]."',
        
        '".$input["material_subtype"]."',  '".$input["material_name"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
    
   }
    else if ($_GET["type"] == "save_work_order") {
        
        
        
        $selectedTermCon = [] ;
        foreach($input["terms_conditions"] as $addTerm){
            
            if(isset($addTerm["selected"]) && $addTerm["selected"]==true){
                $selectedTermCon[] = $addTerm;
            }
        }
        
              $sql = "INSERT INTO work_order_table (plant_id,subtotal,gst1,unit,discount_amt,after_dic_amount,gst_amt,total_amt,vendor_no,material_list, note, terms,entry_date)VALUES 
        ('".$_GET["plant_id"]."','".$input["subtotal"]."','".$input["gst1"]."','".$input["unit"]."','".$input["discount_amt"]."','".$input["after_dic_amount"]."','".$input["gst_amt"]."','".$input["total_amt"]."',
        '".$input["vendor_no"]."','".json_encode($input["material_list"])."', '".json_encode($input["note"])."','".json_encode($selectedTermCon)."','$entry_date')";
   
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
    
   }
    else if ($_GET["type"] == "approve_work_order") {
         
        $sql = "UPDATE work_order_table SET status = 'approve' where id = '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   }
    else if ($_GET["type"] == "getWork_order") {
        $output1 = Array();
        $sql = " select w.*,v.pincode,v.address,v.mobile_no,v.vendor_name from work_order_table w left join vendor v On w.vendor_no = v.vendor_no
        where w.status = 'pending' AND w.plant_id =  '".$_GET["plant_id"]."'  order by id desc";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                
                $row['material_list']   = json_decode($row['material_list']);
                $row['note']   = json_decode($row['note']);
                $row['terms']   = json_decode($row['terms']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
   }
    else if ($_GET["type"] == "get_workorder_log") {
        $output1 = Array();
        $sql = " select w.*,v.pincode,v.address,v.mobile_no,v.vendor_name from work_order_table w left join vendor v On w.vendor_no = v.vendor_no
        where w.status = 'approve' AND w.plant_id =  '".$_GET["plant_id"]."'  order by id desc";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                
                $row['material_list']   = json_decode($row['material_list']);
                $row['note']   = json_decode($row['note']);
                $row['terms']   = json_decode($row['terms']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
   }
   else if ($_GET["type"] == "purchaseOrderLogActionForStatusUpdate") {
        $status = isset($input["status"]) ? $input["status"] : '';
        // Normalize legacy / typo statuses to the canonical value used by getPoForAmendment
        if ($status === 'TO_AMENDMENNT' || $status === 'TO_AMENDMENT' || $status === 'Amendment') {
            $status = 'Sent_To_Amendment';
        }
        $updateRemark = isset($input["updateRemark"]) ? $conn->real_escape_string($input["updateRemark"]) : '';
        $id = isset($input["id"]) ? $conn->real_escape_string($input["id"]) : '';
        $statusEsc = $conn->real_escape_string($status);

        $sql = "UPDATE purchaseorder SET status = '".$statusEsc."', updateRemark = '".$updateRemark."', lastUpdatedBy = '".$_GET["emp_id"]."', lastUpdatedOn = '$entry_date' WHERE id = '".$id."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
   }  
 
   
    else  if ($_GET["type"] == "getMaterials_by_vendor1") {
        //   $sql = "SELECT * FROM general_material WHERE plant_id='".$_GET["plant_id"]."' 
        // and general_material_type='".$_GET["material_type"]."' order by 1 desc";
    $sql="Select *, a.material_name from material_type b LEFT JOIN general_material a on b.material_subtype=a.material_subtype where b.material_subtype = '".$_GET["material_subtype"]."'  order by 1 desc";

        //   echo $sql = "Select * from material_type where material_subtype = '".$_GET["material_subtype"]."' AND plant_id = '".$_GET["plant_id"]."' order by 1 desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalents"] = json_decode($row["equivalent"]);
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   
   
    
   
   else if($_GET['type'] == "getMaterials_by_vendor"){
       
      // print_r($_GET); exit;
      
      $sql = " ";
      
      if(empty($_GET["matType"]) || $_GET["matType"]=="Raw Material" || $_GET["matType"]=="Packing Material"){
        //   $sql = "SELECT DISTINCT m.id, m. *,g.grade as gradename FROM material m left join grade g on m.grade LEFT JOIN mst_vendor_materials vm on m.material_code = vm.material_code WHERE m.plant_id ='".$_GET["plant_id"]."' AND material_subtype LIKE '%".$_GET["material_subtype"]."'
        //  AND material_nature LIKE '%".$_GET["material_nature"]."' AND supplier_code = '".$_GET["vendor_no"]."' or manufacturer_code  = '".$_GET["vendor_no"]."' ";}
           $sql = "select * from(SELECT material.* , grade.grade as gradeName FROM material
          LEFT JOIN grade on  material.grade=grade.id WHERE  material.plant_id='".$_GET["plant_id"]."' AND material.status='approve'
         AND material_subtype LIKE '%".$_GET["material_subtype"]."%'
         AND material_nature LIKE '%".$_GET["material_nature"]."') a where material_code in
         (select  material_code from mst_vendor_materials
         where supplier_code  = '".$_GET["vendor_no"]."' or manufacturer_code  = '".$_GET["vendor_no"]."')  ";
          
      }
  //vivek   //        $sql = "select * from(SELECT material.* , grade.grade as gradeName FROM material
        //   LEFT JOIN grade on  material.grade=grade.id WHERE  material.plant_id='".$_GET["plant_id"]."' 
        //  AND material_subtype LIKE '%".$_GET["material_subtype"]."'
        //  AND material_nature LIKE '%".$_GET["material_nature"]."') a where material_code in
        //  (select  material_code from mst_vendor_materials
        //  where supplier_code  = '".$_GET["vendor_no"]."' or manufacturer_code  = '".$_GET["vendor_no"]."') group by id";}
         else{
             
               $sql = "select * from(SELECT * FROM general_material  WHERE  plant_id='".$_GET["plant_id"]."' 
         AND material_subtype LIKE '%".$_GET["material_subtype"]."') a";
             
             
         }
         //ORDER BY material_name";
      
    //  echo $sql;exit;
      
         $result = $conn->query($sql);
         if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $row["equivalent"] = json_decode($row["equivalent"]);
                     $output1 = Array();
                    //  $sql1 = "SELECT * FROM quotation_rates_by_product where
                    //  material_code='".$row["material_code"]."' and vendor_no='".$_GET["vendor_no"]."'";
                      $sql1 = "SELECT * FROM quotation_dtl qd 
                     LEFT JOIN quotation_hdr qh ON qd.quotation_hdr_id=qh.id 
                     LEFT JOIN vendor on vendor.id= qh.vendor_id
                     WHERE qd.material_code ='".$row["material_code"]."' and vendor.vendor_no='".$_GET["vendor_no"]."'";
                     
                     $result1 = $conn->query($sql1);
                     if($result->num_rows > 0){
                         while($row1 = $result1->fetch_assoc())
                         {
                             $output1[] = $row1;
                         }
                     }
                     $row["quotations"] = $output1;
                     
                      $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
                    
                    $output[] = $row;
            }
         }
            echo json_encode($output);
    }
   else if($_GET['type'] == "getMaterials_by_vendor_gm"){
       

              $sql = "select * from(SELECT * FROM general_material  WHERE  plant_id='".$_GET["plant_id"]."' 
         AND material_subtype LIKE '%".$_GET["material_subtype"]."') a";
             
             
         
         //ORDER BY material_name";
      
    
      
         $result = $conn->query($sql);
         if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $row["equivalent"] = json_decode($row["equivalent"]);
                     $output1 = Array();
                    //  $sql1 = "SELECT * FROM quotation_rates_by_product where
                    //  material_code='".$row["material_code"]."' and vendor_no='".$_GET["vendor_no"]."'";
                      $sql1 = "SELECT * FROM quotation_dtl qd 
                     LEFT JOIN quotation_hdr qh ON qd.quotation_hdr_id=qh.id 
                     LEFT JOIN vendor on vendor.id= qh.vendor_id
                     WHERE qd.material_code ='".$row["material_code"]."' and vendor.vendor_no='".$_GET["vendor_no"]."'";
                     
                     $result1 = $conn->query($sql1);
                     if($result->num_rows > 0){
                         while($row1 = $result1->fetch_assoc())
                         {
                             $output1[] = $row1;
                         }
                     }
                     $row["quotations"] = $output1;
                    
                    $output[] = $row;
            }
         }
            echo json_encode($output);
    }
    else if($_GET['type'] == 'get_quotation_rate_by_product'){
        $sql = "SELECT * FROM quotation_rates_by_product where material_code='".$_GET["material_code"]."' and vendor_id='".$_GET["vendor_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'downloadPOLog') {
        $_GET['filename'] = 'Purchase Order Report'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Purchase Order Report</h2>
        <table cellpadding="5" style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
                <td style="width:7%;">Sr.No</td>
                <td style="width:13%;">Date</td>
                <td style="width:16%;">Po No</td>
                <td style="width:18%;">vendor Name</td>
                <td style="width:13%;">Gross Total</td>
                <td style="width:10%;">Tax Total</td>
                <td style="width:10%;">Net Total</td>
                <td style="width:13%;">Status</td>
            </tr>';
        $i=1;
        $output = array();
  //$sql="SELECT c.id, c.material_type ,c.po_no,c.po_date,c.status,p.status as po_status,cm.grn_no, s.sampling_no, 
      //  s.sample_status, v.vendor_name,cm.material_subtype,c.challan_no,c.challan_date,cm.status as mat_recd_status
      //  FROM challan c left join challan_materials cm on c.challan_no = cm.challan_no left join 
      //  vendor v on cm.vendor_no = v.vendor_no left join sampling s on cm.grn_no = s.grn_no left join purchaseorder p 
      //  on c.po_no = p.po_no where p.plant_id = '".$_GET["plant_id"]."' ORDER BY p.id DESC";        //$sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code, v.pincode, c.company_name, c.mobile_no, c.address, c1.company_name as company_name1 , c1.mobile_no as mobile_no1, c1.address as address1  FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN company c ON p.billcompany_code=c.company_code LEFT JOIN company c1 ON p.shipcompany_code=c1.company_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_type='Raw Material'ORDER BY p.id DESC";
          //$sql="SELECT p.*, v.vendor_name, v.address, v.gst_no, v.city, v.state_code, s.state_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN state s ON v.state_code= s.state_code WHERE  p.po_type='Raw Material' AND v.vendor_name LIKE '%".$_GET["name"]."%' ORDER BY p.id DESC";
         // $sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code, v.pincode, c.company_name, c.mobile_no, c.address, c1.company_name as company_name1 , c1.mobile_no as mobile_no1, c1.address as address1  FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN company c ON p.billcompany_code=c.company_code LEFT JOIN company c1 ON p.shipcompany_code=c1.company_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_type='Raw Material'";
        //$sql = "SELECT p.*, v.vendor_name,v.address, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code,s.state_name, v.pincode, c.company_name, c.mobile_no, c1.company_name as company_name1 , c1.mobile_no as mobile_no1,a.agent_name  FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN state s ON v.state_code= s.state_code LEFT JOIN company c ON p.billcompany_code=c.company_code LEFT JOIN company c1 ON p.shipcompany_code=c1.company_code LEFT JOIN agent a ON p.broker_name=a.agent_no WHERE p.user_no='".$_GET["user_no"]."' AND p.po_type='Raw Material' AND p.vendor_no LIKE '%".$_GET["vendor_no"]."' AND DATE(p.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY p.id DESC";
       // $sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code, v.pincode  FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no  WHERE p.user_no='".$_GET["user_no"]."' AND p.po_type='Raw Material' AND p.vendor_no LIKE '%".$_GET["vendor_no"]."' ";
      //echo $sql;
  $sql="SELECT distinct p.id , p.*,p.additional_term as aterm, v.vendor_name, v.address, v.address_factory,v2.vendor_name as manufacturar, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no 
          LEFT JOIN indend_raw i ON i.indend_no=p.indent_no left join vendor v2 on i.manufacturer_no=v2.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         p.status in('approved','Cancelled','on hold','Rejected') ORDER BY p.id DESC";
        
      
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $output1 = array();
                // $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.po_no='".$row["id"]."' GROUP BY p.id";
                // $result1 = $conn->query($sql1);
                // if ($result1->num_rows > 0) {
                //     while ($row1 = $result1->fetch_assoc()) {
                //         $output1[] = $row1;
                //     }
                // }
                // $row["materials"] = $output1;
                // $output[] = $row;
                
                $html.='
                <tr nobr="true">
                    <td style="width:7%;">'.$i.'</td>
                    <td style="width:13%;">'. date('d-m-Y', strtotime($row['entry_date'])).'</td>
                    <td style="width:16%;">'.$row["po_no"].'</td>
                    <td style="width:18%;">'.$row["vendor_name"].'</td>
                    <td style="width:13%;">'.$row["gross_total"].'</td>
                    <td style="width:10%;">'.$row["gst_total"].'</td>
                    <td style="width:10%;">'.$row['net_total'].'</td>
                    <td style="width:13%;">'.$row['status'].'</td>
                </tr>';
                $i++;
            }
        }  
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PurchaseOrder.pdf', 'I');
    }   
    else if($_GET['type'] == 'downloadAllPOLog') {
        $_GET['filename'] = 'Purchase Order Report'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.='
        <h2 style="text-align:cenetr">Purchase Order Report</h2>
        <table cellpadding="5" style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
               <td>Sr. </td>
                <td>Po Type</td>
                <td>Approval Date</td>
                <td>P.O Date</td>
                <td>Po No</td>
                <td>Indent No</td>
                <td >Vendor Name</td>
                <td >Status</td>
            </tr>';
        
      $sql="SELECT distinct p.id , p.*,p.additional_term as aterm, v.vendor_name,v.contact_email, v.address, v.address_factory,v2.vendor_name as manufacturar, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no 
          LEFT JOIN indend_raw i ON i.indend_no=p.indent_no left join vendor v2 on i.manufacturer_no=v2.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."'   AND
         p.status in('approved','Cancelled','on hold','Rejected') ORDER BY p.id DESC";
         
           $result = $conn->query($sql);
            $i=1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                
                $html.='<tr nobr="true">
                    <td >'.$i.'.</td>
                    <td >'.$row['po_type'].'</td>
                    <td >'.date('d-m-y',strtotime($row['entry_date'])).'</td>
                    <td>'.date('d-m-y',strtotime($row['entry_date'])).'</td>
                    <td>'.$row['po_no'].'</td>
                    <td>'.$row['indent_no'].'</td>
                    <td>'.$row['vendor_name'].'</td>
                    <td>'.$row['status'].'</td>
                </tr>';
                $i++;
            }
            
        }
  
           $html.='</table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output(' ', 'I');
    }
    
    else if($_GET['type'] == 'downloadMislog') {
        $_GET['filename'] = 'Monthly Purchase Report'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Monthly Purchase Report-'. date('F Y', strtotime($_GET['month'])).'</h2>

        
        <table style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
             <td style="width: 20px;font-size: 9px" colspan="10">Sr.No</td>
             <td style="width: 75px;font-size: 9px">Supplier Name</td>
             <td style="width: 50px;font-size: 9px">Invoice no.</td>
             <td style="width: 50px;font-size: 9px">Invoice date</td>
             <td style="width: 50px;font-size: 9px">PO No.</td>
             <td style="width: 55px;font-size: 9pxfont-size: 10px">Material Description</td>
             <td style="width: 30px;font-size: 9px">Qty</td>
             <td style="width: 30px;font-size: 9px">Unit</td>
             <td style="width: 30px;font-size: 9px">Rate</td>
             <td style="width: 40px;font-size: 9px">Amount</td>
             <td style="width: 55px;font-size: 9px">Transport Charges/Freight Charges</td>
             <td style="width: 35px;font-size: 9px">CGST/SGST</td>
             <td style="width: 35px;font-size: 9px">Total Amount</td>
         </tr>';
        
       $sql="SELECT * FROM purchaseorder a left join challan b on a.po_no=b.po_no left JOIN vendor c on b.vendor_no=c.vendor_no LEFT JOIN po_material d on a.id=d.po_no LEFT
      	     JOIN vendor v ON a.vendor_no=v.vendor_no  JOIN material e on d.material_code=e.material_code where a.po_no  IN (SELECT po_no FROM challan where plant_id= '".$_GET["plant_id"]."') order by a.id desc";
   
      
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                
                $html.='
                <tr>
                     <td style="width: 20px;font-size: 9px">'.$i.'</td>
                     <td style="width: 75px;font-size: 9px">'.$row["vendor_name"].'</td>
                     <td style="width: 50px;font-size: 9px">'.$row["tax_invoice"].'</td>
                     <td style="width: 50px;font-size: 9px">'. date('d-m-Y', strtotime($row["tax_invoice_date"])).'</td>
                     <td style="width: 50px;font-size: 9px">'.$row["po_no"].'</td>
                     <td style="width: 55px;font-size: 9px">'.$row["material_name"].'</td>
                     <td style="width: 30px;font-size: 9px">'.$row["qty"].'</td>
                     <td style="width: 30px;font-size: 9px">'.$row["uom"].'</td>
                     <td style="width: 30px;font-size: 9px">'.$row["quotation_amt"].'</td>
                     <td style="width: 40px;font-size: 9px">'.$row["gross_total"].'</td>
                     <td style="width: 55px;font-size: 9px">'.$row["gst_total"].'</td>
                     <td style="width: 35px;font-size: 9px">'.$row["other_charges"].'</td>
                     <td style="width: 35px;font-size: 9px">'.$row['final_total'].'</td>
                </tr>';
                $i++;
            }
        }  
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MIS.pdf', 'I');
    } 
    else if ($_GET["type"] == "work_orderdownload") {
        
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        $sql = " select w.*,v.gst_no,v.pincode,v.address,v.mobile_no,v.vendor_name,w.entry_date as entry from work_order_table w left join vendor v On w.vendor_no = v.vendor_no
        where w.status = 'approve' AND w.plant_id =  '".$_GET["plant_id"]."' AND w.wo_no='".$_GET["work_order"]."' order by id desc";
          $result = $conn->query($sql);
        $j=1;
 
        if ($result->num_rows > 0) {
            while ($row1 = $result->fetch_assoc()) {
                     $originalWoNo = $row1['wo_no'];
                    $numericWoNo = preg_replace('/[^0-9]/', '', $originalWoNo);
             
                $html.='<h3 style="text-align:center;">WORK ORDER</h3>
                        <table border="1" cellpadding="2">
                            <tr>
                                <td style="width:100%;">
                                    <table>
                                         <tr>
                                            <td style="width:50%;text-align:left"><b>NEPL/PUR/WO/2023-24/'.$numericWoNo.'</b></td>
                                            <td style="width:50%;text-align:right"><b>'.date('j F Y').'</b> </td>
                                        </tr>
                                        <BR>
                                        <tr>
                                         <td style="width:50%;text-align:left"><b>TO :</b>'.$row1['vendor_name'].'<br>'.$row1['address_factory'].'</td>
                                            <td style="width:50%;text-align:right"></td>
                                        </tr>
                                        <tr>
                                            <td style="width:20%;"><b>Address : </b>'.$row1['address'].'.</td>
                                        </tr>
                                        <tr>
                                            <td style="width:50%;"><b>GST No. : </b>'.$row1['gst_no'].'.</td>
                                        </tr>
                                    
                                        <tr>
                                            <td style="width:100%;"><b>Mobile No :</b>'.$row1['mobile_no'].'</td>
                                        </tr>
                                        <tr>
                                            <td style="width:100%;"><b>Postal Code : </b>'.$row1['pincode'].'</td>
                                        </tr>
                                        
                                    </table>
                                </td>
                                
                            </tr>
                            <tr>
                                <td style="width:100%;"><b>ATTN: :</b>  Please Supply us the Following on the term and Conditions Mentioned hereinafter. </td>
                            </tr>
                            <tr>
                                <td style="width:100%;">MODIFICATION:<br> </td>
                            </tr>';
                            
                            
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:10%;text-align:center"><b>Sr No.</b></td>
          <td style="width:40%;text-align:left"><b>Description</b></td>
           <td style="width:15%;text-align:center"><b>Qty</b></td>
            <td style="width:15%;text-align:center"><b>Rate</b></td>
             <td style="width:20%;text-align:right"><b>Total Amount</b></td>
           
         </tr>';
           
         
                $json_obj = $row1['material_list'];
                $array = json_decode($json_obj, true);
                $i = 1;
                 foreach ($array as $values)
                {

             $html.='<tr>
                        <td style="width:10%;text-align:center">'.$i.'</td>
                        <td style="width:40%;text-align:left">'.$values['material_name'].'</td>
                        <td style="width:15%;text-align:center">'.$values['qty'].'</td>
                        <td style="width:15%;text-align:center">'.number_format($values['rate'], 2).'</td>
                        <td style="width:20%;text-align:right">'.number_format($values['total'], 2).'</td>   
                    </tr>';
                
                $i++;
                }
   
                        
            function convertNumberToWords($number) {
                $words = '';
                $units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
                $teens = ['', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
                $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
            
                if ($number == 0) {
                    $words = 'Zero';
                } elseif ($number < 0) {
                    $words = 'Negative ' . convertNumberToWords(abs($number));
                } else {
                    if (($number / 10000000) >= 1) {
                        $words .= convertNumberToWords(intval($number / 10000000)) . ' Crore ';
                        $number %= 10000000;
                    }
                    if (($number / 100000) >= 1) {
                        $words .= convertNumberToWords(intval($number / 100000)) . ' Lakh ';
                        $number %= 100000;
                    }
                    if (($number / 1000) >= 1) {
                        $words .= convertNumberToWords(intval($number / 1000)) . ' Thousand ';
                        $number %= 1000;
                    }
                    if (($number / 100) >= 1) {
                        $words .= convertNumberToWords(intval($number / 100)) . ' Hundred ';
                        $number %= 100;
                    }
            
                    if ($number > 0) {
                        if ($words != '') {
                            $words .= 'and ';
                        }
            
                        if ($number < 10) {
                            $words .= $units[$number];
                        } elseif ($number < 20) {
                            $words .= $teens[$number - 10];
                        } else {
                            $words .= $tens[intval($number / 10)];
                            $remainder = $number % 10;
            
                            if ($remainder > 0) {
                                $words .= ' ' . $units[$remainder];
                            }
                        }
                    }
                }
            
                return $words;
            }
      
      
            $number = $row1['total_amt'];
            $words = convertNumberToWords($number);

      
           $html.='
                            <tr>
                                <td style="width:80%;text-align:right;font-weight:bold;">SubTotal</td>
                                <td style="width:20%; text-align:right;font-weight:bold;"> '.number_format($row1['subtotal'], 2).'</td>
                            </tr>
                            <tr>
                                <td style="width:80%;text-align:right;font-weight:bold;">Discount Amount</td>
                                <td style="width:20%; text-align:right;font-weight:bold;">'.number_format($row1['discount_amt'], 2).'</td>
                            </tr>
                            <tr>
                                <td style="width:80%;text-align:right;font-weight:bold;">After Discounted Amount</td>
                                <td style="width:20%; text-align:right;font-weight:bold;">'.number_format($row1['after_dic_amount'], 2).'</td>
                            </tr>
                            <tr>
                                <td style="width:80%;text-align:right;font-weight:bold;">GST@'.$row1['gst1'].' %</td>
                                <td style="width:20%; text-align:right;font-weight:bold;">'.number_format($row1['gst_amt'], 2).'</td>
                            </tr>
                            <tr>
                                <td style="width:80%;text-align:right;font-weight:bold;"> Total Amount</td>
                                <td style="width:20%; text-align:right;font-weight:bold;"> '.number_format($row1['total_amt'], 2).'</td>
                            </tr>
                            <tr>
                                <td style="width:100%;">TOTAL PRICE RS. <b style="color:green"> '.number_format($row1['total_amt'], 2).'/- </b><b> ( '.$words.' )</b></td>
                            </tr>
                           
                            
                            
                            
                        </table>
                        
                        <table>

                            
                            <tr>
                                <td style="width:100%;"><b>Note:-</b><br> </td>
                            </tr>';
                           
                            $json_obj1 = $row1['note'];
                            $array1 = json_decode($json_obj1, true);
                            $i = 1;
                             foreach ($array1 as $values1)
                            {
            
                         $html.='<tr>
                                     <td style="width:100%;text-align:left"> <b>'.$i.')</b>  '.$values1['note'].'</td>
                                </tr>';
                            
                            $i++;
                            }
               
                            
                            
             $html.='<br><br>
                        <tr>
                            <td style="width:100%;"><b>Terms And Conditions:-</b><br> </td>
                        </tr>';
                            
                            
                            
                            $json_obj1 = $row1['terms'];
                            $array1 = json_decode($json_obj1, true);
                            $i = 1;
                             foreach ($array1 as $values1)
                            {
            
                         $html.='<tr>
                                     <td style="width:100%;text-align:left"> <b>'.$i.')</b>  '.$values1['additional_term'].'</td>
                                </tr>';
                            
                            $i++;
                            }
                            
                     
                $html.='
                
                
                    <tr>
                        <td style="width:100%;text-align:left;"><b>PLEASE MAKE TAX INVOICE AS UNDER : </b></td>
                    </tr>
                    <br>
                    <tr>
                        <td style="width:100%;text-align:left;"><b>NOVO EXCIPIENTS PVT. LTD.</b></td>
                    </tr>
                    <br><br>
                    <tr>
                        <td style="width:20%;text-align:left;"><b> BILLING ADDRESS  :</b>   </td>
                        <td style="width:80%;text-align:left;">A/374, TTC Industrial estate ,Mahape, Navi Mumbai-400710<b>
                        <br> GST PROV ID:27AACCC3785B1ZU ,</b>&nbsp;&nbsp;<b>PAN NO: AACCC3785B.</b></td>
                    </tr>
                    <br>
                    <tr>
                        <td style="width:20%;text-align:left;"><b>DELIVERY ADDRESS:</b>   </td>
                        <td style="width:80%;text-align:left;">A/374, TTC Industrial estate ,Mahape, Navi Mumbai-400710<b>
                        <br> GST PROV ID:27AACCC3785B1ZU ,</b>&nbsp;&nbsp;<b>PAN NO: AACCC3785B.</b></td>
                    </tr>
                        
                
                
                <div>
                </div>
                </table>
                <br>
                <br>
                <br>';
                
                $html .= '<br><br><table border="1" cellpadding="3">
                    <tr style="background-color:black; color:white;">
                        <td style="width:50%;text-align:center;"><b>Checked By & Digital Signed By</b></td>
                        <td style="width:50%;text-align:center;"><b>Approved By & Digital Signed By</b></td>
                    </tr>
                    <tr>
                        <td style="width:50%;" >
                            <table>
                                <tr>
                                    <td style="width:25%;">Name</td>
                                    <td style="width:25%;">:Master</td>
                                    <td rowspan="3" style="width:50%;text-align:right;"><img src="../../upload/pdf/sign.jpg" style="width:50px;height:50px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">ID</td>
                                    <td style="width:25%;">:Master</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Department</td>
                                    <td style="width:25%;">:Purchase</td>
                                </tr>
                              
                                <tr>
                                    <td style="width:25%;">Date</td>
                                    <td style="width:35%;">:' . date('d-m-Y', strtotime($row['entry_date'])) . '</td>
                                    <td style="width:20%;">Time</td>
                                    <td style="width:20%;">:' . date('H:i:s', strtotime($row['entry_date'])) . '</td>
                                </tr>
                                   <tr>
                                  <td style="width:100%; font-size: 12px;"><b>FOR ,NOVO EXCIPIENTS PVT.LTD</b></td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:50%;">
                            <table>
                                <tr>
                                    <td style="width:25%;">Name</td>
                                    <td style="width:25%;">:Master</td>
                                    <td rowspan="3" style="width:50%;text-align:right;"><img src="../../upload/pdf/sign.jpg" style="width:50px;height:50px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">ID</td>
                                    <td style="width:25%;">:Master</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Department</td>
                                    <td style="width:25%;">:Purchase</td>
                                </tr>
                             
                                <tr>
                                    <td style="width:25%;">Date</td>
                                    <td style="width:35%;">:' . date('d-m-Y', strtotime($row['entry_date'])) . '</td>
                                    <td style="width:20%;">Time</td>
                                    <td style="width:20%;">:' . date('H:i:s', strtotime($row['entry_date'] . '+1 minute')) . '</td>

                                </tr>
                                   <tr>
                            <td style="width:100%; font-size: 12px;"><b>FOR ,NOVO EXCIPIENTS PVT.LTD</b></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                                <td style="width:100%;text-align:center">A-374,TTC Industrial Area, Mahape, Navi Mumbai-400710,Tel:+91-22-27788401/02 Email:info@novoexipients.com
Website:www.novomix.in Regd.Office : 5/C, Shree Laxmi Industrial Estate,Ne</td>
                                </tr>
                </table>   ';
                
                
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('Po Report.pdf', 'I');
                break;  
            }
        }
    }
    else if ($_GET["type"] == "downloadPO") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        //$sql = "SELECT p.*,q.quotation_no,q.approve_date FROM purchaseorder p LEFT JOIN quotation q ON p.vendor_no=q.vendor_no WHERE p.po_type='Raw Material'  AND p.id='".$_GET["id"]."'";
        $sql = "SELECT p.*, DATE_FORMAT(DATE(p.entry_date),'%d-%m-%Y') as entry_date, v.vendor_name,v.tel_no1,v.email,v.mobile_no, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code, v.pincode, v.state_name ,q.quotation_no,q.approve_date FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN quotation q ON p.vendor_no=q.vendor_no LEFT JOIN state s ON v.state_code=s.state_code WHERE p.user_no='".$_GET["user_no"]."' AND  p.po_type='Raw Material'  ";
        $result = $conn->query($sql);
        $j=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                $html.='<h3 style="text-align:center;">PurchaseOrder</h3>
                        <table border="1" cellpadding="2">
                            <tr>
                                <td style="width:60%;">
                                    <table>
                                        <tr>
                                            <td style="width:100%;"><b>TO</b><br>'.$row['vendor_name'].'<br>'.$row['address_factory'].'</td>
                                        </tr>
                                        <tr>
                                            <td style="width:100%;"><b>Phone No:</b>'.$row['tel_no1'].'</td>
                                        </tr>
                                        <tr>
                                            <td style="width:100%;"><b>Email:</b>'.$row['email'].'</td>
                                        </tr>
                                        <tr>
                                            <td style="width:100%;"><b>Mobile:</b>'.$row['mobile_no'].'</td>
                                        </tr>
                                        <tr>
                                            <td style="width:100%;"><b>GST No:</b>'.$row['gst_no'].'</td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="width:40%;">
                                    <table>
                                        <tr>
                                            <td style="width:100%;"><b>Purchase Ord. No.:</b>'.$row['po_no'].'</td>
                                        </tr>
                                        <tr>
                                            <td style="width:100%;"><b>Purchase Ord. Date:</b>'.$row['entry_date'].'</td>
                                        </tr>
                                        <tr>
                                            <td style="width:100%;"><b>Quotation Ref. No.:</b>'.$row['quotation_no'].'</td>
                                        </tr>
                                        <tr>
                                            <td style="width:100%;"><b>Quotation Date:</b>'.$row['approve_date'].'</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:100%;"><b>IMPORTANT :</b>  PLS. MENTION OUR G.S.T. & C.S.T. NO. IN YOUR INVOICE/CHALLANS. MATERIAL SH\'D BE DELIVERED TO OUR GOTA-FACTORY BEFORE 4.00 P.M. </td>
                            </tr>
                            <tr>
                                <td style="width:100%;">Dear Sir,<br>Please supply the following item(s) as per your quotation reffered to above and as per terms and condition stated overleaf</td>
                            </tr>
                            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                                <td style="width:15%;">Material Name</td>
                                <td style="width:10%;">HSN No</td>
                                <td style="width:10%;">Design No</td>
                                <td style="width:15%;">Delivery Date</td>
                                <td style="width:10%;">Qty</td>
                                <td style="width:10%;">Rate</td>
                                <td style="width:15%;">IGST</td>
                                <td style="width:15%;">Total</td>
                            </tr>
                            <tr>
                                <td style="width:15%;"></td>
                                <td style="width:10%;"></td>
                                <td style="width:10%;"></td>
                                <td style="width:15%;"></td>
                                <td style="width:10%;"></td>
                                <td style="width:10%;"></td>
                                <td style="width:15%;"></td>
                                <td style="width:15%;"></td>
                            </tr>
                            <tr>
                                <td style="width:85%;text-align:right;font-weight:bold;">SubTotal</td>
                                <td style="width:15%;"></td>
                            </tr>
                            <tr>
                                <td style="width:85%;text-align:right;font-weight:bold;">Discount</td>
                                <td style="width:15%;">'.$row['discount'].'</td>
                            </tr>
                            <tr>
                                <td style="width:85%;text-align:right;font-weight:bold;">Total</td>
                                <td style="width:15%;"></td>
                            </tr>
                            <tr>
                                <td style="width:15%;border:none;">Order Place By </td>
                                <td style="width:15%;">:</td>
                                <td style="width:15%;border:none;">Advance amount</td>
                                <td style="width:25%;">:</td>
                                <td style="width:15%;border:none;">Credit Days</td>
                                <td style="width:15%;">:</td>
                            </tr>
                             <tr>
                                <td style="width:15%;border:none;">Booking At</td>
                                <td style="width:15%;">:</td>
                                <td style="width:15%;border:none;">Bill In Favour of</td>
                                <td style="width:25%;">:</td>
                                <td style="width:15%;border:none;">Dispatch By</td>
                                <td style="width:15%;">:</td>
                            </tr>
                             <tr>
                                <td style="width:15%;border:none;">Range</td>
                                <td style="width:15%;">:</td>
                                <td style="width:15%;border:none;">Payment Terms</td>
                                <td style="width:25%;">:</td>
                                <td style="width:15%;border:none;">Delivery At</td>
                                <td style="width:15%;">:</td>
                            </tr>
                             <tr>
                                <td style="width:15%;border:none;">Division</td>
                                <td style="width:15%;">:</td>
                                <td style="width:15%;border:none;">Mode of Trans.</td>
                                <td style="width:25%;">:</td>
                                <td style="width:15%;border:none;">Remarks </td>
                                <td style="width:15%;">:</td>
                            </tr>
                             <tr>
                                <td style="width:15%;border:none;">Drug Lic No</td>
                                <td style="width:15%;">:</td>
                                <td style="width:15%;border:none;">CST No:</td>
                                <td style="width:25%;">:</td>
                                <td style="width:15%;border:none;">E.C.C. No.</td>
                                <td style="width:15%;">:</td>
                            </tr>
                            <tr>
                                <td style="width:100%;">
                                    <ol>
                                        <li>Our TIN No.:24075200479 Dt. 14.09.2005 , C.S.T.No. 24575200479: Dt. 14.09.2005, GST No: 24AAACW7013Q1Z2 Dt.  Pan No: AAACW7013Q</li>
                                        <li>Drug License No: 20B-GJ-AD2-76997 (Dt.03/07/2018), New E.C.C.No.: AAACW7013QXM001</li>
                                        <li>Don�t supply if our PO is not signed by authorized signatory.</li>
                                        <li>Please confirm above order & mention our P.O. No. in all Correspondence & documents.</li>
                                        <li>You are responsible for goods delivery to our Godown in proper packing.</li>
                                        <li>We will not pay any charge for extra packing, postage or Forwarding.</li>
                                        <li>Material should be delivered to our GOTA-FACTORY before 4.00 P.M.</li>
                                        <li>Supply of Raw Material should be of latest Mfg Batch with Less No. Of Batches.</li>
                                        <li>Deliver the goods along with Delivery Challans, Gate Pass and Invoice..</li>
                                        <li>If Delivery of goods from outside state the Octroi limit, enclose the Octroi Receipts with the Invoice.</li>
                                        <li>The test report, COA, other valid documents of the batches supplied will be required with the invoice.</li>
                                        <li>Send MTR & other documents only to our factory address.</li>
                                        <li>Kindly mention Manufacturing Date, Expiry Date, Name of Manufacturer in Invoice.</li>
                                        <li>Kindly mentioned our G.S.T & C.S.T No in your invoices/challans.</li>
                                        <li>Kindly update on the minimum available packing for future purchase.</li>
                                        <li>If Quantity of goods supplied will be more than 10% to the PO then we will raise Debit Note.</li>
                                        <li>Supplier is responsible up to the complete testing done in laboratory.</li>
                                        <li>We will raise Debit Note if rates are higher as per mutually confirmed rate.</li>
                                        <li>Supply of Raw Material should be of latest Mfg. Batch, 3 Month old Mfg.Material not Accepted.</li>
                                        <li>Any Raw Material Self Life Minimum 4 Years.</li>
                                    </ol>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:100%;">
                                    <table border="1" cellpadding="2" style="width:50%;">
                                        <tr>
                                            <td style="text-align:center;">Purchase For</td>
                                        </tr>
                                        <tr>
                                            <td style="width:20%;"></td>
                                            <td style="width:30%;"></td>
                                            <td style="width:20%;"></td>
                                            <td style="width:30%;"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr><td style="width:100%;">Thanking You<br>Yours Faithfully,<br>WEST-COAST PHRMACEUTICAL WORKS LTD</td></tr>
                            <tr>
                                <td style="width:100%;">
                                    <table>
                                        <tr>
                                            <td style="width:100%;">Purchase Department</td>
                                        </tr>
                                        <tr>
                                            <td style="width:100%;">Mobile </td>
                                        </tr>
                                        <tr>
                                            <td style="width:100%;">Email</td>
                                        </tr>
                                        <tr>
                                            <td style="width:100%;">For any further inquiry please contact on above stated no.</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>';
                $html.="</table>";
                
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('Po Report.pdf', 'I');
                break;  
            }
        }
    }else if ($_GET["type"] == "downloadPOReport") {
        $_GET['filename'] = 'Purchase Order'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
       
        $i=1;
        $sql = "SELECT p.*, v.vendor_name, v.address, v.address_factory, v.gst_no, v.location, v.state_code,v.state_name, v.pincode, c.company_name, c.mobile_no, c.area, c1.company_name as company_name1 , c1.mobile_no as mobile_no1, c1.address as address1, c1.area as area1,e.department,e1.department as dept,e.firstname,e1.firstname as firstname1 FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN state s ON v.state_code= s.state_code LEFT JOIN company c ON p.billcompany_code=c.company_code LEFT JOIN employee e ON p.entry_by=e.emp_id LEFT JOIN employee e1 ON p.entry_by=e1.emp_id LEFT JOIN company c1 ON p.shipcompany_code=c1.company_code WHERE p.id='".$_GET["id"]."' ";
       // echo $sql;
      //$sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
     // $sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code, v.pincode, a.agent_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN agent a ON p.broker_name=a.agent_no WHERE p.id='".$_GET["id"]."'";
         $result = $conn->query($sql);
      
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {   
            $term ="" ; 
            $term_heading ="";
            $currancy= $row['currancy'];
            $symbol ="";
            if($currancy == "USD"){
                 $symbol ="$";
            }else  if($currancy == "EUR"){
                 $symbol ="�";
            }
            /*$json_obj= $row['terms_conditions'];
            $array = json_decode($json_obj, true);
            foreach($array as $values) {
                $term = $values['term'];
                $term_heading =$values['term_heading'];
                }*/
                //echo $heading;     
             $html.='
             <h2 style="text-align:center">Purchase Order</h2>
                
                <table border="1" cellpadding="2">
                    <tr style="background-color:black; color:white;">
                        <td style="width:100%;"><b>Supplier Details</b></td>
                    </tr>
                    
                    
                    <tr>
                        <td style="width:8%;">Sr.No.</td>
                         <td style="width:25%;text-align:center:">Material Name</td>
                          <td style="width:10%;">Qty</td>
                           <td style="width:7%;">Unit</td>
                            <td style="width:10%;">Rate(/Nr)</td>
                             <td style="width:10%;">Gst(%)</td>
                              <td style="width:10%;">Net Ammount</td>
                              <td style="width:10%;">Tax Ammount</td>
                              <td style="width:10%;">Total Ammount</td>
                       
                    </tr>
                     <tr>
                        <td style="width:8%;"></td>
                         <td style="width:25%;"></td>
                          <td style="width:10%;"></td>
                           <td style="width:7%;"></td>
                            <td style="width:10%;"></td>
                             <td style="width:10%;"></td>
                              <td style="width:10%;"></td>
                              <td style="width:10%;"></td>
                              <td style="width:10%;"></td>
                       
                    </tr>
                   
                </table>
                <div></div>
            
                    <table border="1" cellpadding="2">
                    <tr style="background-color:black; color:white;">
                        <td style="width:33%;text-align:center;"><b>Bill To</b></td>
                        <td style="width:33%;text-align:center;"><b>SHIP TO</b></td>
                        <td style="width:34%;text-align:center;"><b>P.O NUMBER</b></td>
                    </tr>
                    <tr>
                        <td style="width:33%;">
                            <table>
                                <tr>
                                    <td style="width:50%;"><b>Name OfCompany:</b></td>
                                    <td style="width:50%;">'.$row['company_name'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Address:</b></td>
                                    <td style="width:60%;">'.$row['address1'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Email:</b></td>
                                    <td style="width:60%;">'.$row['website'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Mobile No:</b></td>
                                    <td style="width:60%;">'.$row['mobile_no1'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>GST No</b></td>
                                    <td style="width:60%;">'.$row['gst_no'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Location:</b></td>
                                     <td style="width:60%;">'.$row['area'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>State/Province:</b></td>
                                    <td style="width:60%;">'.$row['state_name'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Postal Code:</b></td>
                                    <td style="width:60%;">'.$row['bill_pin'].'</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:34%;">
                            <table>
                                <tr>
                                    <td style="width:50%;"><b>Name Of Company:</b></td>
                                    <td style="width:50%;">'.$row['company_name1'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Address:</b></td>
                                    <td style="width:60%;">'.$row['address1'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Email:</b></td>
                                    <td style="width:60%;">'.$row['website'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Mobile No:</b></td>
                                    <td style="width:60%;">'.$row['mobile_no1'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>GST No:</b></td>
                                    <td style="width:60%;">'.$row['gst_no'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Location:</b></td>
                                    <td style="width:60%;">'.$row['area'].'</td>
                                </tr>
                                 <tr>
                                    <td style="width:40%;"><b>State/Province:</b></td>
                                    <td style="width:60%;">'.$row['state_name'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Postal Code:</b></td>
                                    <td style="width:60%;">'.$row['ship_pin'].'</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:33%;">
                            <table>
                                <tr>
                                    <td style="width:20%;font-weight:bold;">PO No:</td>
                                    <td style="width:80%;">'.$row['po_no'].'</td>
                                </tr><br>
                                <tr>
                                    <td style="width:30%;font-weight:bold;">PO Type:</td>
                                    <td style="width:70%;">'.$row['po_type'].'</td>
                                </tr><br>
                                <tr>
                                    <td style="width:30%;font-weight:bold;">PO Date:</td>
                                    <td style="width:70%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                                </tr><br>
                                <tr>
                                    <td style="width:30%;font-weight:bold;">Transport:</td>
                                    <td style="width:70%;">'.$row['transport_company'].'</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <div></div>  '; 
                 $html.='<table border="1" cellpadding="2">
                                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                                    <td style="width:10%;">Sr. No.</td>';
                                    if($po_type='General Material'){
                                     $html.='<td style="width:20%; colspan=2 ">Matrial Name</td>';
                                    }else{
                                         $html.='<td style="width:30%;">Material Name</td>';
                                    }
                                    $html.='<td style="width:10%; text-align: center;">Qty</td>
                                    <td style="width:10%; text-align: center">Unit</td>
                                    <td style="width:10%; text-align: center">Rate (INR) </td>
                                    <td style="width:10%; text-align: center">GST(%)</td>
                                    <td style="width:10%; text-align: center">Taxable Amt </td>
                                    <td style="width:10%; text-align: center">Tax Amt </td>
                                    <td style="width:10%; text-align: center">Total</td>
                                </tr>';
                                
                switch($po_type){
                    case 'General Material':
                    $sql1 = "SELECT p.*, m.material_type, m.material_name,m.hsn,m.entry_date,m.part_size FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.po_no='".$row["id"]."'";
                    break;
                    case 'Raw Material':
                    $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
                    break;
                    case 'Packing Material':
                    $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
                    break;
                    case 'Chemical':
                    $sql1 = "SELECT p.*, m.chemical_name FROM po_material p LEFT JOIN chemical m ON p.chemical_no=m.chemical_no WHERE p.po_no='".$row["id"]."'AND  p.po_type='Chemical' AND (p.po_indend='Approve' OR p.material_status='pending')  ";
                }        
                            //echo $sql1;        
                 $result1 = $conn->query($sql1);
                    $idx=1;
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
           
                        
                                     $html.='<tr>
                                      <td style="width:10%;text-align: center;">'.$idx.'</td>';
                                        if($po_type== 'General Material'){
                                         $html.='<td style="width:20%;">'.$row1['material_name'].'</td>';
                                        }else{
                                             $html.='<td style="width:10%;">'.$row1['material_name'].'</td>';
                                        }
                                        
                                        $html.='<td style="width:10%;text-align: right;" >'.$row1['qty'].'</td>
                                        <td style="width:10%;text-align: center;">'.$row1['unit'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['quotation_amt'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['gst'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['gross_total'].'</td>
                                           <td style="width:10%;text-align: right;">'.$row1['tax_total'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['net_total'].'</td>
                                    </tr>';
                                    $idx+=1;
                        }            
                    }                
                                
                                
                /*switch($po_type){
                    case 'General Material':
                                        echo $sql1;
                    $sql1 = "SELECT p.*, m.material_type, m.material_name,m.hsn,m.entry_date,m.part_size FROM po_material p LEFT JOIN general_material m ON p.material_code=m.material_code WHERE p.po_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    $idx=1;
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
           
                        
                                     $html.='<tr>
                                      <td style="width:10%;">'.$idx.'</td>
                                        <td style="width:20%;">'.$row1['material_name'].'</td>
                                        <td style="width:10%;">'.$row1['part_size'].'</td>
                                        <td style="width:10%;text-align: right;" >'.$row1['qty'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['unit'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['quotation_amt'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gst'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gross_total'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['tax_total'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['net_total'].'</td>
                                    </tr>';
                                    $idx+=1;
                        }            
                    }
                    break;
                    case 'Raw Material':
                    $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
                                echo $sql1;
                    $result1 = $conn->query($sql1);
                    $idx=1;
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
           
                        
                                     $html.='<tr>
                                      <td style="width:10%;">'.$idx.'</td>
                                        <td style="width:20%;">'.$row1['material_name'].'</td>
                                        <td style="width:10%;text-align: right;" >'.$row1['qty'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['unit'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['quotation_amt'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['gst'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['gross_total'].'</td>
                                           <td style="width:10%;text-align: right;">'.$row1['tax_total'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['net_total'].'</td>
                                    </tr>';
                                    $idx+=1;
                        }            
                    }
                    break;
                    case 'Packing Material':
                    $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
                                   echo $sql1;
                    $result1 = $conn->query($sql1);
                    $idx=1;
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
           
                        
                                     $html.='<tr>
                                      <td style="width:10%;">'.$idx.'</td>
                                        <td style="width:30%;">'.$row1['material_name'].'</td>
                                        <td style="width:10%;text-align: right;" >'.$row1['qty'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['unit'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['quotation_amt'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gst'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gross_total'].'</td>
                                           <td style="width:15%;text-align: right;">'.$row1['tax_total'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['net_total'].'</td>
                                    </tr>';
                                    $idx+=1;
                        }            
                    }
                    break;
                    case 'GlassWare':
                    $sql1 = "SELECT p.*, m.name,m.glassware_class FROM po_material p LEFT JOIN glassware m ON p.material_code=m.glassware_no  WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    $idx=1;
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
           
                        
                                     $html.='<tr>
                                      <td style="width:10%;">'.$idx.'</td>
                                        <td style="width:30%;">'.$row1['name'].'</td>
                                        <td style="width:10%;text-align: right;" >'.$row1['qty'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['unit'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['quotation_amt'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gst'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gross_total'].'</td>
                                           <td style="width:15%;text-align: right;">'.$row1['tax_total'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['net_total'].'</td>
                                    </tr>';
                                    $idx+=1;
                        }            
                    }
                    break;
                    case 'Chemical':
                    $sql1 = "SELECT p.*, m.chemical_name FROM po_material p LEFT JOIN chemical m ON p.chemical_no=m.chemical_no WHERE p.po_no='".$row["id"]."'AND  p.po_type='Chemical' AND (p.po_indend='Approve' OR p.material_status='pending')  ";
                                    echo $sql1;
                    $result1 = $conn->query($sql1);
                    $idx=1;
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
           
                        
                                     $html.='<tr>
                                      <td style="width:10%;">'.$idx.'</td>
                                        <td style="width:30%;">'.$row1['chemical_name'].'</td>
                                        <td style="width:10%;text-align: right;" >'.$row1['qty'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['unit'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['quotation_amt'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gst'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gross_total'].'</td>
                                           <td style="width:15%;text-align: right;">'.$row1['tax_total'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['net_total'].'</td>
                                    </tr>';
                                    $idx+=1;
                        }            
                    }
                    break;
                }      */       

                                             
                $html.='  <tr>
                                    <td style="width:85%;text-align:right;font-weight:bold;">SUB Total</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">'.$row['gross_total'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:85%;text-align:right;font-weight:bold;">Discount</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">'.$row['discount'].'</td>
                                </tr>
                                   <tr><td style="width:70%;">';
            $sql2="SELECT p.gst,sum(p.tax_total) as tax_total from po_material p WHERE p.po_no='".$row["id"]."' GROUP by p.gst"  ; 
             $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $html.='<table><tr>
                                               <td style="width:15%;font-weight:bold;border-left: 1px solid black;">'.$row2['gst'].'%</td>
                                               <td style="width:15%;font-weight:bold;border-left: 1px solid black;text-align:right">'.$row2['tax_total'].'</td>
                                               </tr></table>';
                                        
                                    }} 
                                     $html.='</td>  <td style="width:15%;text-align:right;font-weight:bold;">GST Total(18%)</td>
                                    <td style="width:15%;text-align: right;font-weight:bold;">'.$row['gst_total'].'</td>
                                </tr>
                                <tr>
                        <td style="width:85%;text-align:right;font-weight:bold;">Shipping & Handling</td>
                        <td style="width:15%;text-align:right">'.$row['shipping_handling'].'</td>
                    </tr>
                    <tr>
                         <td style="width:85%;text-align:right;font-weight:bold;">Other</td>
                        <td style="width:15%;text-align:right">'.$row['other_charges'].'</td>
                    </tr>
                                <tr>
                                  
                                    <td style="width:85%;text-align:right;font-weight:bold;">Total</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">'.$row['net_total'].'</td>
                                </tr>  
                                <tr>
                                  <td style="width:70%;text-align:left;font-weight:bold;">Value in words</td>
                                    <td style="width:15%;text-align:right;font-weight:bold;">Round off</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">'.$row['rounding'].'</td>
                                </tr>
                                <tr>
                                      <td style="width:70%;text-align:left;font-weight:bold;">'.$value_in_words.'</td>
                                    <td style="width:15%;text-align:right;font-weight:bold;">Net Total</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">'.$row['final_total'].'</td>
                                </tr>';
                            $html.='
                </table>
                <div></div><br/>.
                 <table border="1" cellpadding="2">
                  <tr style="background-color:black; color:white;">
                            <td>    Terms & Conditions </td>
                        </tr>
                        <tr>
                            <td>
                                <ul>
                                    <li>Please send two copies of your invoice.</li>
                                    <li>Enter this order in accordance with the prices, terms, delivery method, and specifications listed above</li>
                                    <li>Please notify us immediately if you are unable to ship as specified.</li>
                                    <li>Send all correspondence to:</li>
                                </ul>
                            </td>
                            
                        </tr> 
                    <tr style="background-color:black; color:white;">
                        <td style="width:10%;text-align:center;"><b>Sr.No</b></td>
                        <td style="width:30%;text-align:left;"><b>Term Heading</b></td>
                        <td style="width:60%;text-align:left;"><b>Terms</b></td>
                    </tr>
                    
                    
                    
                    ';
               
               
                    
               
               
                 $term_heading ="";
                 $hdr_printed=false;
            $json_obj= $row['terms_conditions'];
            $array = json_decode($json_obj, true);
            foreach($array as $values) {
                $term = $values['term'];
                $term_heading =$values['term_heading'];
                     $html.=' <tr>
                        <td style="width:10%;">'.$i++.'</td>
                        <td style="width:30%;text-align:left;">'.$term_heading.'</td>
                        <td style="width:60%;text-align:left;">'.$term.'</td>
                    </tr>';
            }   
                    
                 $html.=' </table>';
           
        $html.='<br><br><table border="1" cellpadding="3">
                    <tr style="background-color:black; color:white;">
                        <td style="width:50%;text-align:center;"><b>Checked By & Digital Signed By</b></td>
                        <td style="width:50%;text-align:center;"><b>Approved By & Digital Signed By</b></td>
                    </tr>
                    <tr>
                        <td style="width:50%;" >
                            <table>
                                <tr>
                                    <td style="width:25%;">Name</td>
                                    <td style="width:25%;">:'.$row['entry_by'].'</td>
                                    <td rowspan="3" style="width:50%;text-align:right;"><img src="../../upload/pdf/sign.jpg" style="width:50px;height:50px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">ID</td>
                                    <td style="width:25%;">:'.$row['firstname'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Department</td>
                                    <td style="width:25%;">:Purchase</td>
                                </tr>
                                <tr>
                                <td style="width:100%;">For, AMARDEEP CHEMICAL INDUSTRIES PVT. LTD </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Date</td>
                                    <td style="width:35%;">:'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                                    <td style="width:20%;">Time</td>
                                    <td style="width:20%;">:'.date('H:i:s',strtotime($row['entry_date'])).'</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:50%;">
                            <table>
                                <tr>
                                    <td style="width:25%;">Name</td>
                                    <td style="width:25%;">:'.$row['approve_by'].'</td>
                                    <td rowspan="3" style="width:50%;text-align:right;"><img src="../../upload/pdf/sign.jpg" style="width:50px;height:50px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">ID</td>
                                    <td style="width:25%;">:'.$row['firstname1'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Department</td>
                                    <td style="width:25%;">:Purchase</td>
                                </tr>
                                <tr>
                                <td style="width:100%;">For, AMARDEEP CHEMICAL INDUSTRIES PVT. LTD </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Date</td>
                                    <td style="width:35%;">:'.date('d-m-Y',strtotime($row['approve_date'])).'</td>
                                    <td style="width:20%;">Time</td>
                                    <td style="width:20%;">:'.date('H:i:s',strtotime($row['approve_date'])).'</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                                <td style="width:100%;text-align:center">Regd. Office :Plot No: A2/8, 1St Phase, G.I.D.C, Vapi, Dist. Valsad - 396195, CIN No.U99999GJ1971PTC109282</td>
                                </tr>
                </table>';    
                    //$html.="</table>";
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('Po Report.pdf', 'I');
               // break;  
            }
        }
    }

} else {
    echo "{\"status\":\"invalid\"}";
}
 }catch (\Throwable $e) {
               echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
            }
$conn->close();
?>