<?php


// ini_set('display_errors', 1);
// error_reporting(E_ALL);


    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

    function ensureChallanMaterialPendingColumns($conn) {
        $columns = array(
            'status' => "VARCHAR(50) NULL DEFAULT 'pending'",
            'receiving' => "VARCHAR(50) NULL DEFAULT 'pending'",
            'material_name' => "VARCHAR(255) NULL"
        );
        foreach ($columns as $name => $definition) {
            $check = $conn->query("SHOW COLUMNS FROM challan_materials LIKE '".$conn->real_escape_string($name)."'");
            if ($check && $check->num_rows === 0) {
                @$conn->query("ALTER TABLE challan_materials ADD `".$name."` ".$definition);
                // invalidate column cache so later checks see new columns
                challanMaterialsHasColumn($conn, $name, true);
            }
        }
    }

    function ensureChallanMaterialNameColumn($conn) {
        $check = $conn->query("SHOW COLUMNS FROM challan LIKE 'material_name'");
        if ($check && $check->num_rows === 0) {
            @$conn->query("ALTER TABLE challan ADD `material_name` VARCHAR(255) NULL");
        }
    }

    function challanMaterialsHasColumn($conn, $column, $refresh = false) {
        static $cache = array();
        $key = strtolower($column);
        if ($refresh) {
            unset($cache[$key]);
        }
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        $check = $conn->query("SHOW COLUMNS FROM challan_materials LIKE '".$conn->real_escape_string($column)."'");
        $cache[$key] = ($check && $check->num_rows > 0);
        return $cache[$key];
    }

    function getDirectChallanMaterials($conn, $row, $userNo = '') {
        $materials = array();
        ensureChallanMaterialPendingColumns($conn);
        ensureChallanMaterialNameColumn($conn);

        $challanNo = $conn->real_escape_string(isset($row["challan_no"]) ? (string)$row["challan_no"] : '');
        $chNo = $conn->real_escape_string(isset($row["ch_no"]) ? (string)$row["ch_no"] : '');
        $headerName = isset($row["material_name"]) ? trim((string)$row["material_name"]) : '';
        $headerEsc = $conn->real_escape_string($headerName);
        $plantEsc = $conn->real_escape_string(isset($row["plant_id"]) ? (string)$row["plant_id"] : '');
        $matType = isset($row["material_type"]) ? trim((string)$row["material_type"]) : '';
        $hasSavedName = challanMaterialsHasColumn($conn, 'material_name');
        $viewPlantJoin = $plantEsc !== ''
            ? " AND (mv.plant_id = '".$plantEsc."' OR IFNULL(mv.plant_id,'') = '')"
            : "";
        $viewTypeJoin = $matType !== ''
            ? " AND (IFNULL(mv.material_type,'') = '' OR mv.material_type = '".$conn->real_escape_string($matType)."')"
            : "";

        $sql1 = "SELECT cm.id, cm.challan_no, cm.ch_no, cm.material_code, cm.qty, cm.unit, cm.status,
                NULLIF(TRIM(mv.material_name),'') AS material_name
            FROM challan_materials cm
            LEFT JOIN my_view mv ON cm.material_code = mv.material_code AND IFNULL(cm.material_code,'') <> ''".$viewPlantJoin.$viewTypeJoin."
            WHERE (
                (IFNULL(cm.challan_no,'') <> '' AND (cm.challan_no = '".$challanNo."' OR cm.challan_no = '".$chNo."'))
                OR (IFNULL(cm.ch_no,'') <> '' AND (cm.ch_no = '".$challanNo."' OR cm.ch_no = '".$chNo."'))
            )
            ORDER BY cm.id ASC";

        $result1 = @$conn->query($sql1);
        if ($result1 && $result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                if (empty($row1["material_name"]) && !empty($row1["chemical_name"])) {
                    $row1["material_name"] = $row1["chemical_name"];
                }
                $materials[] = $row1;
            }
        } else {
            // Fallback plain select
            $sql2 = "SELECT * FROM challan_materials
                WHERE (
                    (IFNULL(challan_no,'') <> '' AND (challan_no = '".$challanNo."' OR challan_no = '".$chNo."'))
                    OR (IFNULL(ch_no,'') <> '' AND (ch_no = '".$challanNo."' OR ch_no = '".$chNo."'))
                )
                ORDER BY id ASC";
            $result2 = @$conn->query($sql2);
            if ($result2 && $result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    if (empty($row2["material_name"]) && $headerName !== '') {
                        $row2["material_name"] = $headerName;
                    }
                    $materials[] = $row2;
                }
            }
        }

        // If no line rows but challan header has material_name, expose it
        if (count($materials) === 0 && usableMaterialName($headerName)) {
            $materials[] = array('material_name' => $headerName);
        }
        $plantRaw = isset($row["plant_id"]) ? (string)$row["plant_id"] : '';
        $tmp = array(array(
            'material_type' => $matType,
            'materials' => $materials,
            'material_name' => $headerName,
        ));
        applyMaterialNamesFromMaster($conn, $plantRaw, $tmp);
        return isset($tmp[0]['materials']) ? $tmp[0]['materials'] : $materials;
    }

    function usableMaterialName($name, $code = '') {
        $n = trim((string)$name);
        $c = trim((string)$code);
        if ($n === '' || $n === '0' || $n === '-' || strcasecmp($n, 'NA') === 0 || strcasecmp($n, 'N/A') === 0) {
            return false;
        }
        if (preg_match('/^-?\d+(\.\d+)?$/', $n)) {
            return false;
        }
        if ($c !== '' && strcasecmp($n, $c) === 0) {
            return false;
        }
        if (!preg_match('/[A-Za-z]/', $n)) {
            return false;
        }
        return true;
    }

    function loadMaterialNameMap($conn, $plant, $codes) {
        $map = array();
        $clean = array();
        foreach ((array)$codes as $code) {
            $code = trim((string)$code);
            if ($code !== '' && $code !== '0') {
                $clean[$code] = true;
            }
        }
        $codes = array_keys($clean);
        if (count($codes) === 0) {
            return $map;
        }
        $in = array();
        foreach ($codes as $code) {
            $in[] = "'".$conn->real_escape_string($code)."'";
        }
        $inList = implode(',', $in);
        $plantEsc = $conn->real_escape_string((string)$plant);

        $queries = array(
            "SELECT material_code, material_name, material_type FROM my_view WHERE material_code IN (".$inList.") AND (plant_id = '".$plantEsc."' OR IFNULL(plant_id,'') = '')",
        );
        foreach ($queries as $sql) {
            $res = @$conn->query($sql);
            if (!$res) {
                continue;
            }
            while ($r = $res->fetch_assoc()) {
                $code = trim((string)$r['material_code']);
                $name = isset($r['material_name']) ? trim((string)$r['material_name']) : '';
                $type = isset($r['material_type']) ? trim((string)$r['material_type']) : '';
                if (!usableMaterialName($name, $code)) {
                    continue;
                }
                if (!isset($map[$code])) {
                    $map[$code] = array();
                }
                $map[$code]['_any'] = $name;
                if ($type !== '') {
                    $map[$code][$type] = $name;
                }
            }
        }
        return $map;
    }

    function pickMappedMaterialName($map, $code, $matType = '') {
        $code = trim((string)$code);
        if ($code === '' || !isset($map[$code])) {
            return '';
        }
        $matType = trim((string)$matType);
        if ($matType !== '' && !empty($map[$code][$matType]) && usableMaterialName($map[$code][$matType], $code)) {
            return $map[$code][$matType];
        }
        if ($matType === 'Raw Material' || $matType === 'Packing Material') {
            $other = $matType === 'Raw Material' ? 'Packing Material' : 'Raw Material';
            $any = !empty($map[$code]['_any']) ? $map[$code]['_any'] : '';
            $otherName = !empty($map[$code][$other]) ? $map[$code][$other] : '';
            if (usableMaterialName($any, $code) && ($otherName === '' || strcasecmp($any, $otherName) !== 0)) {
                return $any;
            }
            return '';
        }
        if (!empty($map[$code]['_any']) && usableMaterialName($map[$code]['_any'], $code)) {
            return $map[$code]['_any'];
        }
        return '';
    }

    function applyMaterialNamesFromMaster($conn, $plant, &$rows) {
        $codes = array();
        foreach ($rows as $row) {
            if (!empty($row['materials']) && is_array($row['materials'])) {
                foreach ($row['materials'] as $mat) {
                    if (!empty($mat['material_code'])) {
                        $codes[] = $mat['material_code'];
                    }
                }
            }
            if (!empty($row['material_code'])) {
                $codes[] = $row['material_code'];
            }
        }
        $map = loadMaterialNameMap($conn, $plant, $codes);
        foreach ($rows as &$row) {
            $type = isset($row['material_type']) ? $row['material_type'] : '';
            if (!empty($row['materials']) && is_array($row['materials'])) {
                foreach ($row['materials'] as &$mat) {
                    $code = isset($mat['material_code']) ? $mat['material_code'] : '';
                    $current = isset($mat['material_name']) ? $mat['material_name'] : '';
                    $mapped = pickMappedMaterialName($map, $code, $type);
                    if ($mapped !== '') {
                        $mat['material_name'] = $mapped;
                    } else if (!usableMaterialName($current, $code)) {
                        $mat['material_name'] = '';
                    }
                }
                unset($mat);
            }
            $row['material_name'] = flattenDirectMaterialName($row);
        }
        unset($row);
        return $map;
    }

    function flattenDirectMaterialName($row) {
        $names = array();
        $seenIds = array();
        if (!empty($row["materials"]) && is_array($row["materials"])) {
            foreach ($row["materials"] as $mat) {
                $lineId = isset($mat["id"]) ? (string)$mat["id"] : '';
                if ($lineId !== '') {
                    if (isset($seenIds[$lineId])) {
                        continue;
                    }
                    $seenIds[$lineId] = true;
                }
                $n = '';
                if (!empty($mat["material_name"])) {
                    $n = trim((string)$mat["material_name"]);
                } else if (!empty($mat["chemical_name"])) {
                    $n = trim((string)$mat["chemical_name"]);
                } else if (!empty($mat["name"])) {
                    $n = trim((string)$mat["name"]);
                }
                $c = isset($mat["material_code"]) ? trim((string)$mat["material_code"]) : '';
                if (usableMaterialName($n, $c)) {
                    $names[] = $n;
                }
            }
        }
        if (count($names) > 0) {
            return implode(', ', array_values(array_unique($names)));
        }
        $header = isset($row["material_name"]) ? trim((string)$row["material_name"]) : '';
        return usableMaterialName($header) ? $header : '';
    }

    function createInwardTcpdf($orientation = 'P') {
        $pdf = new TCPDF($orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Medicap');
        $pdf->SetAuthor('Security Inward');
        $pdf->SetTitle('Inward');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage($orientation);
        $pdf->SetFont('helvetica', '', 9);
        return $pdf;
    }

    function inwardPdfFmtDate($d) {
        if (empty($d) || $d === '0000-00-00' || $d === '0000-00-00 00:00:00') {
            return 'NA';
        }
        $ts = strtotime($d);
        return $ts ? date('d-m-Y', $ts) : 'NA';
    }

    function inwardPdfVal($v, $fallback = 'NA') {
        $s = trim((string)$v);
        return $s !== '' ? htmlspecialchars($s, ENT_QUOTES, 'UTF-8') : $fallback;
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
    
    if ($_GET["type"] == "getPendingPO") {
         $output = Array();
         header('Content-Type: application/json; charset=UTF-8');
        $plantEsc = $conn->real_escape_string((string)$_GET["plant_id"]);
        $sql = "SELECT p.*, v.vendor_name,v.gst_no, v.address,v.contact_email  FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no = v.vendor_no 
        WHERE p.plant_id = '".$plantEsc."' AND p.status = 'Approved' AND p.is_security_receive != 'Yes' ORDER BY p.id DESC";
        
    	$result = $conn->query($sql);
    	if($result && $result->num_rows > 0) {
    		while($row = $result->fetch_assoc()) {
    		    
    		    $output1 = Array();
                $poNoEsc = $conn->real_escape_string((string)$row["id"]);
                
                $sql1 = "SELECT p.*, p.id as purMatId, p.quotation_amt as rate, p.tax_total as gst_total,
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
                    COALESCE(NULLIF(TRIM(m.material_type), ''), mat.material_type, om.material_type, gm.material_type, p.po_type) AS material_type
                FROM po_material p
                LEFT JOIN my_view m ON p.material_code = m.material_code
                LEFT JOIN material mat ON p.material_code = mat.material_code
                LEFT JOIN others_material om ON p.material_code = om.material_code
                LEFT JOIN chemical ch ON p.material_code = ch.chemical_no
                LEFT JOIN general_material gm ON p.material_code = gm.material_code
                WHERE p.po_no = '".$poNoEsc."' AND p.plant_id = '".$plantEsc."' AND p.isMatIn != 'YES'";
                
                $isOpen = false;
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if($row1['openQty'] != 'NO'){
                            $isOpen = true;
                        }
                        $output1[] = $row1;
                    }
                }
                
                if (count($output1) === 0) {
                    continue;
                }
                
                if($isOpen){
                    $row['isOpenPo'] = 'OPEN';
                }else{
                    $row['isOpenPo'] = 'NA';
                }
                
                $row["coaReceived"] = 'NA';
                $row["materials"] = $output1;
                $output[] = $row;
    		}
    	}
    	
    	$jsonFlags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
    	echo json_encode($output, $jsonFlags);
    
    } 
  
    
    else if ($_GET["type"] == "saveChallan") { 
             
        
        $sql = "INSERT INTO `challan`( `material_type`,`ch_no`, `challan_date`, `vendor_no`, `po_no`,`po_date`, `driver_name`, `driver_contact`, `transport`, `transport_company`, `vehicle_no`, `lrNo`,`lrDate`, 
        `status`, `entry_by`, `entry_date`,  `inward_date`, `inward_type`,  `type`, `plant_id`,  `is_tanker`, `vehicle_type`, `entry_time` ,`coaReceived`) VALUES ('".$input["po_type"]."', '".$input["challan_no"]."',
        '".$input["challan_date"]."','".$input["vendor_no"]."', '".$input["po_no"]."', '".$input["po_date"]."', '".$input["driver_name"]."', '".$input["driver_contact"]."',  '".$input["transport"]."',  
        '".$input["transport_company"]."', '".$input["vehicle_no"]."','".$input["lrNo"]."','".$input["lrDate"]."', 'Pending', '".$_GET["emp_id"]."','$entry_date','$entry_date', '".$input["po_type"]."',
        'From PO', '".$_GET["plant_id"]."', '".$input["is_tanker"]."', '".$input["vehicle_type"]."', '".$input["entry_time"]."', '".$input["coaReceived"]."' )"; 
        
       
        if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
            $sql1 = "UPDATE purchaseorder SET is_security_receive='Yes' WHERE po_no = '".$input["po_no"]."'";
		    $conn->query($sql1);
		    
        } else {
            
            echo "{\"status\":\"".$conn->error."\"}";
            
        }
    
         
        
    }
    else if ($_GET["type"] == "saveChallanToReceiving") {

        ensureChallanMaterialPendingColumns($conn);

        $materials = isset($input["materials"]) ? $input["materials"] : array();
        $documentsChecklist = isset($input["documentsChecklist"]) ? json_encode($input["documentsChecklist"]) : '[]';
        $vehicleChecklist = isset($input["vehicleChecklist"]) ? json_encode($input["vehicleChecklist"]) : '[]';
        $notSelectedCount = isset($input["notSelectedCount"]) ? (int)$input["notSelectedCount"] : 0;
        $remark = isset($input["remark"]) ? $input["remark"] : '';
        $eway_bill_no = isset($input["eway_bill_no"]) ? $input["eway_bill_no"] : '';
        $e_way = isset($input["e_way"]) ? $input["e_way"] : '';
        $weighing_procedure = isset($input["weighing_procedure"]) ? $input["weighing_procedure"] : '';

        $sql = "INSERT INTO `challan`(`material_type`,`ch_no`, `challan_no`, `challan_date`, `vendor_no`, `po_no`,`po_date`, `driver_name`, `driver_contact`, `transport`, `transport_company`, `vehicle_no`, `lrNo`,`lrDate`,
        `status`, `next_stage`, `entry_by`, `entry_date`, `inward_date`, `inward_type`, `type`, `plant_id`, `is_tanker`, `vehicle_type`, `entry_time`, `coaReceived`,
        `documentsChecklist`, `vehicleChecklist`, `e_way`, `eway_bill_no`, `weighing_procedure`, `remark`, `approve_by`, `approve_date`)
        VALUES ('".$input["po_type"]."', '".$input["challan_no"]."', '".$input["challan_no"]."', '".$input["challan_date"]."', '".$input["vendor_no"]."', '".$input["po_no"]."', '".$input["po_date"]."',
        '".$input["driver_name"]."', '".$input["driver_contact"]."', '".$input["transport"]."', '".$input["transport_company"]."', '".$input["vehicle_no"]."', '".$input["lrNo"]."', '".$input["lrDate"]."',
        'approve', 'Receiving', '".$_GET["emp_id"]."', '$entry_date', '$entry_date', '".$input["po_type"]."', 'From PO', '".$_GET["plant_id"]."', '".$input["is_tanker"]."', '".$input["vehicle_type"]."', '".$input["entry_time"]."', '".$input["coaReceived"]."',
        '".$documentsChecklist."', '".$vehicleChecklist."', '".$e_way."', '".$eway_bill_no."', '".$weighing_procedure."', '".$remark."', '".$_GET["emp_id"]."', '$entry_date')";

        if ($conn->query($sql)) {
            $challan_id = $conn->insert_id;
            $inward_no = '';
            $challan_no = isset($input["challan_no"]) ? $input["challan_no"] : '';

            $sqlInward = "SELECT inward_no,challan_no FROM challan WHERE id='".$challan_id."'";
            $resultInward = $conn->query($sqlInward);
            if ($resultInward && $resultInward->num_rows > 0) {
                while ($rowInward = $resultInward->fetch_assoc()) {
                    $inward_no = $rowInward["inward_no"];
                    if (!empty($rowInward["challan_no"])) {
                        $challan_no = $rowInward["challan_no"];
                    }
                }
            }

            $gross_total = 0;
            $taxable_total = 0;
            $gst_total = 0;
            $net_total = 0;
            $sgst_total = 0;
            $cgst_total = 0;
            $igst_total = 0;
            $materialsSaved = 0;
            $materialError = '';

            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $coaReceived = isset($material["coaReceived"]) ? $material["coaReceived"] : $input["coaReceived"];

                if($material['openQty'] != "NO"){
                    $sql1 = "INSERT INTO `challan_materials`(`plant_id`,`inward_no`, `inward_date`, `challan_no`, `ch_no`, `material_subtype`, `material_code`, `vendor_no`, `qty`, `unit`, `gst`, `quotation_no`,
                    `quotation_amt`, `rate`, `received_rate`, `diff`, `tax_invoice`, `tax_invoice_date`, `required_for`, `clientGrpCode`, `clientSubGrpCode`,`gross_total`, `taxable_amt`, `gst_total`, `net_total`,
                    `cgst`, `cgstPer`, `sgst`, `sgstPer`, `igst`, `igstPer`, `isOPenPO`,`coaReceived`, `status`, `receiving`) VALUES ('".$_GET["plant_id"]."','$inward_no','$entry_date','".$challan_no."','".$input["challan_no"]."',
                    '".$material["material_subtype"]."', '".$material["material_code"]."','".$material["vendor_no"]."','".$material["openQty"]."', '".$material["unit"]."', '".$material["gst"]."',
                    '".$material["quotation_no"]."', '".$material["quotation_amt"]."','".$material["quotation_amt"]."', '".$material["received_rate"]."', '".$material["diff"]."','".$material["tax_invoice"]."',
                    '".$material["tax_invoice_date"]."','".$material["required_for"]."','".$material["clientGrpCode"]."','".$material["clientSubGrpCode"]."','".$material["gross_total"]."','".$material["taxable_amt"]."','".$material["gst_total"]."',
                    '".$material["net_total"]."','".$material["cgst"]."','".$material["cgstPer"]."','".$material["sgst"]."' ,'".$material["sgstPer"]."','".$material["igst"]."','".$material["igstPer"]."','YES','".$coaReceived."', 'pending', 'pending')";
                } else {
                    $sql1 = "INSERT INTO `challan_materials`(`plant_id`,`inward_no`, `inward_date`, `challan_no`, `ch_no`, `material_subtype`, `material_code`, `vendor_no`, `qty`, `unit`, `gst`, `quotation_no`,
                    `quotation_amt`, `rate`, `received_rate`, `diff`, `tax_invoice`, `tax_invoice_date`, `required_for`, `clientGrpCode`, `clientSubGrpCode`, `gross_total`, `taxable_amt`, `gst_total`, `net_total`,
                    `cgst`, `cgstPer`, `sgst`, `sgstPer`, `igst`, `igstPer`, `isOPenPO`,`coaReceived`, `status`, `receiving`) VALUES ('".$_GET["plant_id"]."',  '$inward_no','$entry_date','".$challan_no."','".$input["challan_no"]."',
                    '".$material["material_subtype"]."', '".$material["material_code"]."','".$material["vendor_no"]."','".$material["qty"]."', '".$material["unit"]."', '".$material["gst"]."',
                    '".$material["quotation_no"]."', '".$material["quotation_amt"]."','".$material["quotation_amt"]."', '".$material["received_rate"]."', '".$material["diff"]."','".$material["tax_invoice"]."',
                    '".$material["tax_invoice_date"]."','".$material["required_for"]."','".$material["clientGrpCode"]."','".$material["clientSubGrpCode"]."','".$material["gross_total"]."','".$material["taxable_amt"]."','".$material["gst_total"]."',
                    '".$material["net_total"]."','".$material["cgst"]."','".$material["cgstPer"]."','".$material["sgst"]."' ,'".$material["sgstPer"]."','".$material["igst"]."','".$material["igstPer"]."','NO','".$coaReceived."', 'pending', 'pending')";
                }

                $gross_total  += (float) ($material['gross_total']  ?? 0);
                $taxable_total += (float) ($material['taxable_amt'] ?? 0);
                $gst_total    += (float) ($material['gst_total']    ?? 0);
                $net_total    += (float) ($material['net_total']    ?? 0);
                $sgst_total   += (float) ($material['sgst']         ?? 0);
                $cgst_total   += (float) ($material['cgst']         ?? 0);
                $igst_total   += (float) ($material['igst']         ?? 0);

                if ($conn->query($sql1)) {
                    $materialsSaved++;
                    $sql10 = "UPDATE po_material SET isMatIn = 'YES' WHERE id='".$material["purMatId"]."'";
                    $conn->query($sql10);
                } else {
                    $materialError = $conn->error;
                }
            }

            if ($materialsSaved === 0 && count($materials) > 0) {
                echo "{\"status\":\"".($materialError !== '' ? $materialError : 'Failed to save challan materials')."\"}";
                return;
            }

            $sql001 = "UPDATE challan SET gross_total = '$gross_total', taxable_total = '$taxable_total', gst_total = '$gst_total',
            net_total = '$net_total', sgst_total = '$sgst_total', cgst_total = '$cgst_total', igst_total = '$igst_total' WHERE id='".$challan_id."'";
            $conn->query($sql001);

            if($notSelectedCount > 0){
                $sqlPo = "UPDATE purchaseorder SET is_security_receive='No' WHERE po_no='".$input["po_no"]."'";
                $conn->query($sqlPo);
            } else {
                $sqlPo = "UPDATE purchaseorder SET is_security_receive='Yes' WHERE po_no='".$input["po_no"]."'";
                $conn->query($sqlPo);
            }

            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }

    }
    else if ($_GET["type"] == "saveOldChallan") { 
         
         
      echo  $sql = "INSERT INTO challan (plant_id,user_no, material_type, gate_inward_no,ch_no, challan_date, vendor_no, po_no, po_date, 
        driver_name, driver_contact, tax_invoice,tax_invoice_date, transport_company, vehicle_no, materials, entry_by, entry_date, 
        gross_total, gst_total, net_total, transport, challan_file, status,inward_date,inward_type,type,is_tanker,vehicle_type,entry_time,invoice_data) 
        VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."', '".$input["po_type"]."','".$input["gate_inward_no"]."','".$input["challan_no"]."', 
        '".$input["challan_date"]."', '".$input["vendor_no"]."', '".$input["po_no"]."', '".$input["po_date"]."',  '".$input["driver_name"]."', '".$input["driver_contact"]."', 
        '".$input["tax_invoice"]."','".$input["tax_invoice_date"]."', '".$input["transport_company"]."','".$input["vehicle_no"]."',
        '".json_encode($materials)."', '".$_GET["emp_id"]."', '".$entry_date."', '".$input["gross_total"]."',
        '".$input["gst_total"]."', '".$input["net_total"]."', '".$input["transport"]."', '".$upload_challan."', 'pending',
        '".$entry_date."', '".$input["po_type"]."','From PO','".$input["is_tanker"]."','".$input["vehicle_type"]."','".$input["entry_time"]."', 
        '".json_encode($input["invoice_data"])."')"; 
        
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
          
            
            $sql1 = "UPDATE purchaseorder SET is_security_receive='Yes' WHERE po_no='".$input["po_no"]."'";
		    $conn->query($sql1);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
         
        
    } 
    



    
    else if ($_GET["type"] == "getPendingChallansFromSecurity") {
        $output = Array();
         if ($_GET["vendor_no"] == 'undefined') {
            $_GET['vendor_no'] = '';
        }
        $sql = "SELECT p.*,c.transport_company,c.challan_no,c.challan_date,c.tax_invoice,c.material_type, v.vendor_name, v.email, v.gst_no, v.address_factory as address FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN challan c ON c.po_no=p.po_no  WHERE p.is_security_receive='Yes' ORDER BY p.approve_date";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0) {
    		while($row = $result->fetch_assoc()) {
    		    
    		    $output1 = Array();
                $sql1 = "";
                if ($row["po_type"] == "Raw Material" || $row["po_type"] == "Packing Material") {
                    $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype, m.material_type FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.po_no='".$row["id"]."' GROUP BY p.id";
                } else if ($row["po_type"] == "General Material") {
                    $sql1 = "SELECT p.*, m.material_type, m.material_name FROM po_material p LEFT JOIN general_material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
                } else if ($row["po_type"] == "Chemicals") {
                    $sql1 = "SELECT p.*, m.chemical_name, m.molecular_wt, m.grade, p.material_code as chemical_no FROM po_material p LEFT JOIN chemical m ON p.material_code=m.chemical_no WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
                } else if ($row["po_type"] == "Glasswares") {
                    $sql1 = "SELECT p.*, m.name, m.capacity, m.unit, m.glassware_class, m.description, p.material_code as glassware_no FROM po_material p LEFT JOIN glassware m ON p.material_code=m.glassware_no WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
                }
                if ($sql1 !== "") {
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
    	}
    	echo json_encode($output);
    }
 
    else if ($_GET["type"] == "saveDirectChallan") {
        header('Content-Type: application/json; charset=utf-8');
        $esc = function($conn, $value) {
            if ($value === null || $value === false) {
                return '';
            }
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            return mysqli_real_escape_string($conn, (string)$value);
        };

        $input = $_POST;
        if ((!is_array($input) || count($input) === 0) && is_array($GLOBALS['input'] ?? null)) {
            $input = $GLOBALS['input'];
        }
        if (!is_array($input)) {
            $input = array();
        }

        $plantId = $esc($conn, isset($_GET["plant_id"]) ? $_GET["plant_id"] : '');
        $userNo = $esc($conn, isset($_GET["user_no"]) ? $_GET["user_no"] : '');
        $empId = $esc($conn, isset($_GET["emp_id"]) ? $_GET["emp_id"] : '');
        $courier = $esc($conn, isset($input["courier"]) ? $input["courier"] : '');
        $po = $esc($conn, isset($input["po"]) ? $input["po"] : '');
        $inward = $esc($conn, isset($input["inward"]) ? $input["inward"] : '');
        $inwardType = $esc($conn, isset($input["inward_type"]) ? $input["inward_type"] : '');
        $materialType = $esc($conn, isset($input["material_type"]) ? $input["material_type"] : '');
        $challanNo = $esc($conn, isset($input["challan_no"]) ? $input["challan_no"] : '');
        $challanDate = $esc($conn, isset($input["challan_date"]) ? $input["challan_date"] : '');
        $taxInvoice = $esc($conn, isset($input["tax_invoice"]) ? $input["tax_invoice"] : '');
        $poNo = $esc($conn, isset($input["po_no"]) ? $input["po_no"] : '');
        $transport = $esc($conn, isset($input["transport"]) ? $input["transport"] : '');
        $transportFrieght = $esc($conn, isset($input["transport_frieght"]) ? $input["transport_frieght"] : '');
        $transportCompany = $esc($conn, isset($input["transport_company"]) ? $input["transport_company"] : '');
        $vehicleType = $esc($conn, isset($input["vehicle_type"]) ? $input["vehicle_type"] : '');
        $entryTime = $esc($conn, isset($input["entry_time"]) ? $input["entry_time"] : '');
        $person = $esc($conn, isset($input["person"]) ? $input["person"] : '');
        $vendorNo = $esc($conn, isset($input["vendor_no"]) ? $input["vendor_no"] : '');
        $grossTotal = $esc($conn, isset($input["gross_total"]) ? $input["gross_total"] : '0');
        $gstTotal = $esc($conn, isset($input["gst_total"]) ? $input["gst_total"] : '0');
        $netTotal = $esc($conn, isset($input["net_total"]) ? $input["net_total"] : '0');
        $headerMaterialName = isset($input["material_name"]) ? trim((string)$input["material_name"]) : '';

        $materials = array();
        if (isset($input["materials"]) && $input["materials"] !== '') {
            $decoded = is_string($input["materials"]) ? json_decode($input["materials"], true) : $input["materials"];
            if (is_array($decoded)) {
                $materials = $decoded;
            }
        }
        if (count($materials) === 0 && $headerMaterialName !== '') {
            $materials[] = array(
                'material_name' => $headerMaterialName,
                'material_code' => '',
                'qty' => '0',
                'rate' => '0',
                'gst' => '0',
                'gross_total' => '0',
                'gst_total' => '0',
                'net_total' => '0',
                'vendor_no' => $vendorNo
            );
        }

        // Ensure challan.status exists for new → approval → log flow
        $chkStatus = $conn->query("SHOW COLUMNS FROM challan LIKE 'status'");
        if ($chkStatus && $chkStatus->num_rows === 0) {
            $conn->query("ALTER TABLE challan ADD `status` VARCHAR(50) NULL DEFAULT 'pending'");
        }
        ensureChallanMaterialPendingColumns($conn);
        ensureChallanMaterialNameColumn($conn);

        // Detect which challan_materials columns exist on this plant DB (after ensure)
        $cmCols = array();
        $colRes = $conn->query("SHOW COLUMNS FROM challan_materials");
        if ($colRes) {
            while ($colRow = $colRes->fetch_assoc()) {
                $cmCols[strtolower($colRow['Field'])] = true;
            }
        }
        // Force material_name if missing (retry once)
        if (!isset($cmCols['material_name'])) {
            @$conn->query("ALTER TABLE challan_materials ADD `material_name` VARCHAR(255) NULL");
            $cmCols = array();
            $colRes2 = $conn->query("SHOW COLUMNS FROM challan_materials");
            if ($colRes2) {
                while ($colRow = $colRes2->fetch_assoc()) {
                    $cmCols[strtolower($colRow['Field'])] = true;
                }
            }
        }
        $hasCmCol = function($name) use ($cmCols) {
            return isset($cmCols[strtolower($name)]);
        };

        $headerMatNameEsc = $esc($conn, $headerMaterialName);
        $hasChallanMatName = false;
        $chkChallanMatName = $conn->query("SHOW COLUMNS FROM challan LIKE 'material_name'");
        if ($chkChallanMatName && $chkChallanMatName->num_rows > 0) {
            $hasChallanMatName = true;
        }

        if ($hasChallanMatName) {
            $sql = "INSERT INTO challan (plant_id, user_no, courier, po, inward, inward_date, inward_type, material_type, material_name, ch_no, challan_no,
            challan_date, tax_invoice, po_no, po_date, transport, transport_frieght, transport_company, entry_by, entry_date, is_tanker, vehicle_type,
            entry_time, person, type, vendor_no, gross_total, gst_total, net_total, status) VALUES
            ('".$plantId."','".$userNo."','".$courier."','".$po."','".$inward."','$entry_date','".$inwardType."','".$materialType."','".$headerMatNameEsc."',
            '".$challanNo."','".$challanNo."','".$challanDate."','".$taxInvoice."','".$poNo."','$entry_date','".$transport."','".$transportFrieght."',
            '".$transportCompany."','".$empId."','$entry_date','NO','".$vehicleType."','".$entryTime."','".$person."','Local','".$vendorNo."',
            '".$grossTotal."','".$gstTotal."','".$netTotal."','pending')";
        } else {
            $sql = "INSERT INTO challan (plant_id, user_no, courier, po, inward, inward_date, inward_type, material_type, ch_no, challan_no,
            challan_date, tax_invoice, po_no, po_date, transport, transport_frieght, transport_company, entry_by, entry_date, is_tanker, vehicle_type,
            entry_time, person, type, vendor_no, gross_total, gst_total, net_total, status) VALUES
            ('".$plantId."','".$userNo."','".$courier."','".$po."','".$inward."','$entry_date','".$inwardType."','".$materialType."',
            '".$challanNo."','".$challanNo."','".$challanDate."','".$taxInvoice."','".$poNo."','$entry_date','".$transport."','".$transportFrieght."',
            '".$transportCompany."','".$empId."','$entry_date','NO','".$vehicleType."','".$entryTime."','".$person."','Local','".$vendorNo."',
            '".$grossTotal."','".$gstTotal."','".$netTotal."','pending')";
        }

        try {
            $challanOk = $conn->query($sql);
        } catch (Throwable $e) {
            echo json_encode(array('status' => $e->getMessage()));
            return;
        }

        if ($challanOk) {
            $materialError = '';
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                if (!is_array($material)) {
                    continue;
                }
                $matCode = $esc($conn, isset($material["material_code"]) ? $material["material_code"] : '');
                $matQty = $esc($conn, isset($material["qty"]) ? $material["qty"] : '0');
                $matRate = $esc($conn, isset($material["rate"]) ? $material["rate"] : '0');
                $matGst = $esc($conn, isset($material["gst"]) ? $material["gst"] : '0');
                $matGross = $esc($conn, isset($material["gross_total"]) ? $material["gross_total"] : '0');
                $matGstTotal = $esc($conn, isset($material["gst_total"]) ? $material["gst_total"] : '0');
                $matNet = $esc($conn, isset($material["net_total"]) ? $material["net_total"] : '0');
                $matVendorNo = $esc($conn, isset($material["vendor_no"]) ? $material["vendor_no"] : $vendorNo);
                $matName = $esc($conn, isset($material["material_name"]) ? $material["material_name"] : $headerMaterialName);
                $matSubtype = $esc($conn, isset($material["material_subtype"]) ? $material["material_subtype"] : $materialType);

                // Build INSERT using only columns that exist in this DB
                $fields = array();
                $values = array();
                $addField = function($col, $val) use (&$fields, &$values, $hasCmCol) {
                    if ($hasCmCol($col)) {
                        $fields[] = "`".$col."`";
                        $values[] = "'".$val."'";
                    }
                };

                $addField('plant_id', $plantId);
                $addField('user_no', $userNo);
                $addField('inward_date', $entry_date);
                $addField('challan_no', $challanNo);
                $addField('ch_no', $challanNo);
                $addField('material_subtype', $matSubtype);
                $addField('material_code', $matCode);
                $addField('material_name', $matName);
                $addField('vendor_no', $matVendorNo);
                $addField('qty', $matQty);
                $addField('rate', $matRate);
                $addField('gst', $matGst);
                $addField('gross_total', $matGross);
                $addField('gst_total', $matGstTotal);
                $addField('net_total', $matNet);
                $addField('status', 'pending');
                $addField('receiving', 'pending');

                if (count($fields) === 0) {
                    $materialError = 'No valid challan_materials columns found';
                    continue;
                }

                $sqlMat = "INSERT INTO challan_materials (".implode(',', $fields).") VALUES (".implode(',', $values).")";
                try {
                    if (!$conn->query($sqlMat) && $materialError === '') {
                        $materialError = $conn->error;
                    }
                } catch (Throwable $e) {
                    if ($materialError === '') {
                        $materialError = $e->getMessage();
                    }
                }
            }
            if ($materialError !== '') {
                echo json_encode(array('status' => 'success', 'warning' => $materialError));
            } else {
                echo json_encode(array('status' => 'success'));
            }
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }

    else if ($_GET["type"] == "getPendingDirectChallans") {
        $output = array();
        ensureChallanMaterialPendingColumns($conn);
        ensureChallanMaterialNameColumn($conn);
        $plant = mysqli_real_escape_string($conn, $_GET["plant_id"]);
        $sql = "SELECT c.*, v.vendor_name, v.gst_no, v.address_factory as address, v.email as contact_email
            FROM challan c
            LEFT JOIN vendor v ON c.vendor_no = v.vendor_no
            WHERE c.plant_id = '".$plant."'
              AND c.type = 'Local'
              AND (c.status = 'pending' OR c.status IS NULL OR c.status = '')
            ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["materials"] = getDirectChallanMaterials($conn, $row, isset($_GET["user_no"]) ? $_GET["user_no"] : '');
                $row["material_name"] = flattenDirectMaterialName($row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if ($_GET["type"] == "updateDirectChallanStatus") {
        $id = mysqli_real_escape_string($conn, isset($_GET["id"]) ? $_GET["id"] : '');
        $status = mysqli_real_escape_string($conn, isset($_GET["status"]) ? $_GET["status"] : '');
        if ($id === '' || ($status !== 'approve' && $status !== 'reject')) {
            echo "{\"status\":\"error\",\"message\":\"Invalid request\"}";
        } else {
            $chkStatus = $conn->query("SHOW COLUMNS FROM challan LIKE 'status'");
            if ($chkStatus && $chkStatus->num_rows === 0) {
                $conn->query("ALTER TABLE challan ADD `status` VARCHAR(50) NULL DEFAULT 'pending'");
            }
            $chkApproveBy = $conn->query("SHOW COLUMNS FROM challan LIKE 'approve_by'");
            if ($chkApproveBy && $chkApproveBy->num_rows === 0) {
                $conn->query("ALTER TABLE challan ADD `approve_by` VARCHAR(100) NULL");
            }
            $chkApproveDate = $conn->query("SHOW COLUMNS FROM challan LIKE 'approve_date'");
            if ($chkApproveDate && $chkApproveDate->num_rows === 0) {
                $conn->query("ALTER TABLE challan ADD `approve_date` DATETIME NULL");
            }
            ensureChallanMaterialPendingColumns($conn);
            $sql = "UPDATE challan SET status='".$status."', approve_by='".mysqli_real_escape_string($conn, $_GET["emp_id"])."', approve_date='$entry_date'
                WHERE id='".$id."' AND type='Local'";
            if ($conn->query($sql)) {
                $challanNo = '';
                $q = $conn->query("SELECT challan_no, ch_no FROM challan WHERE id='".$id."' LIMIT 1");
                if ($q && $q->num_rows > 0) {
                    $r = $q->fetch_assoc();
                    $challanNo = $r["challan_no"] ? $r["challan_no"] : $r["ch_no"];
                }
                if ($challanNo !== '') {
                    $conn->query("UPDATE challan_materials SET status='".$status."'
                        WHERE challan_no='".$conn->real_escape_string($challanNo)."'");
                }
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"error\",\"message\":\"".$conn->error."\"}";
            }
        }
    }

    else if ($_GET["type"] == "getDirectChallansLog") {
        $output = array();
        ensureChallanMaterialPendingColumns($conn);
        ensureChallanMaterialNameColumn($conn);
        $plant = mysqli_real_escape_string($conn, $_GET["plant_id"]);
        $search = isset($_GET["search_text"]) ? trim($_GET["search_text"]) : '';
        $searchEsc = mysqli_real_escape_string($conn, $search);
        $whereSearch = "";
        if ($search !== '') {
            $like = "%".$searchEsc."%";
            $whereSearch = " AND (
                IFNULL(c.challan_no,'') LIKE '".$like."' OR IFNULL(c.ch_no,'') LIKE '".$like."'
                OR IFNULL(c.po_no,'') LIKE '".$like."' OR IFNULL(v.vendor_name,'') LIKE '".$like."'
                OR IFNULL(c.tax_invoice,'') LIKE '".$like."' OR IFNULL(c.material_name,'') LIKE '".$like."'
            )";
        }
        $sql = "SELECT c.*, v.vendor_name, v.gst_no, v.address_factory as address, v.email as contact_email
            FROM challan c
            LEFT JOIN vendor v ON c.vendor_no = v.vendor_no
            WHERE c.plant_id = '".$plant."'
              AND c.type = 'Local'
              AND c.status = 'approve'
              ".$whereSearch."
            ORDER BY c.id DESC
            LIMIT 1500";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["materials"] = getDirectChallanMaterials($conn, $row, isset($_GET["user_no"]) ? $_GET["user_no"] : '');
                $row["material_name"] = flattenDirectMaterialName($row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    else if ($_GET["type"] == "getChallansLog") {
        $output = Array();
     
               
        $sql="SELECT c.*,m.material_type,m.material_name FROM   challan_materials c left join my_view m on c.material_code = m.material_code WHERE m.plant_id  = '".$_GET["plant_id"]."'";

         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row['invoice_data'] = json_decode($row['invoice_data']);
                $output[] = $row;
                    
            }
        }
        echo json_encode($output);
     
    } 
    else if ($_GET["type"] == "getChallansLogNootan") {
        $output = array();
        ensureChallanMaterialPendingColumns($conn);
        ensureChallanMaterialNameColumn($conn);
        $search = isset($_GET["search_text"]) ? trim($_GET["search_text"]) : '';
        $searchEsc = mysqli_real_escape_string($conn, $search);
        $plant = mysqli_real_escape_string($conn, $_GET["plant_id"]);

        $hasCmMatName = challanMaterialsHasColumn($conn, 'material_name');
        $hasChallanMatName = false;
        $chkChallanMatName = $conn->query("SHOW COLUMNS FROM challan LIKE 'material_name'");
        if ($chkChallanMatName && $chkChallanMatName->num_rows > 0) {
            $hasChallanMatName = true;
        }

        $lineMatNameExpr = $hasCmMatName
            ? "CASE WHEN TRIM(IFNULL(b.material_name,'')) REGEXP '[A-Za-z]' THEN NULLIF(TRIM(b.material_name),'') ELSE NULL END"
            : "NULL";
        $headerMatNameExpr = $hasChallanMatName
            ? "NULLIF(TRIM(a.material_name),'')"
            : "NULL";
        $resolvedMatName = "NULLIF(TRIM(c.material_name),'')";

        $whereSearch = "";
        if ($search !== '') {
            $like = "%".$searchEsc."%";
            $whereSearch = " AND (
                IFNULL(a.challan_no,'') LIKE '".$like."' OR IFNULL(a.ch_no,'') LIKE '".$like."' OR IFNULL(a.po_no,'') LIKE '".$like."'
                OR IFNULL(a.vehicle_no,'') LIKE '".$like."' OR IFNULL(v.vendor_name,'') LIKE '".$like."' OR IFNULL(b.material_code,'') LIKE '".$like."'
                OR IFNULL(c.material_name,'') LIKE '".$like."' OR IFNULL(b.tax_invoice,'') LIKE '".$like."'
                OR IFNULL(".$resolvedMatName.",'') LIKE '".$like."'
            )";
        }

        $sql = "SELECT
            a.id AS challan_id,
            a.challan_no,
            a.ch_no,
            a.vendor_no,
            a.po_no,
            a.po_date,
            a.challan_date,
            a.inward_date,
            a.material_type AS header_material_type,
            ".($hasChallanMatName ? "a.material_name AS header_material_name," : "NULL AS header_material_name,")."
            a.vehicle_no,
            a.transport,
            a.transport_company,
            a.driver_name,
            a.driver_contact,
            a.entry_time,
            a.entry_by,
            a.status AS header_status,
            a.coaReceived,
            a.lrNo,
            a.lrDate,
            v.vendor_name,
            v.email AS contact_email,
            v.gst_no,
            v.address_factory AS address,
            b.id AS line_id,
            b.qty AS line_qty,
            b.unit AS line_unit,
            b.material_subtype AS line_subtype,
            b.tax_invoice AS line_tax_invoice,
            ".($hasCmMatName ? "b.material_name AS line_material_name," : "NULL AS line_material_name,")."
            ".$resolvedMatName." AS material_name,
            c.material_type AS master_material_type,
            COALESCE(NULLIF(TRIM(b.material_code),''), c.material_code) AS material_code,
            c.grade
        FROM challan a
        LEFT JOIN vendor v ON a.vendor_no = v.vendor_no
        LEFT JOIN challan_materials b ON (
            (IFNULL(b.challan_no,'') <> '' AND (b.challan_no = a.challan_no OR b.challan_no = a.ch_no))
            OR (IFNULL(b.ch_no,'') <> '' AND (b.ch_no = a.challan_no OR b.ch_no = a.ch_no))
        )
        LEFT JOIN my_view c ON b.material_code = c.material_code AND IFNULL(b.material_code,'') <> ''
            AND (c.plant_id = a.plant_id OR IFNULL(c.plant_id,'') = '')
            AND (IFNULL(c.material_type,'') = '' OR c.material_type = a.material_type)
        WHERE a.plant_id = '".$plant."' ".$whereSearch."
        ORDER BY a.id DESC, b.id ASC
        LIMIT 1500";

        $result = $conn->query($sql);
        $byId = array();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $cid = $row['challan_id'];
                if (!isset($byId[$cid])) {
                    $byId[$cid] = array(
                        'challan_id' => $cid,
                        'challan_no' => $row['challan_no'],
                        'ch_no' => $row['ch_no'],
                        'vendor_no' => $row['vendor_no'],
                        'vendor_name' => $row['vendor_name'],
                        'contact_email' => $row['contact_email'],
                        'gst_no' => $row['gst_no'],
                        'address' => $row['address'],
                        'po_no' => $row['po_no'],
                        'po_date' => $row['po_date'],
                        'challan_date' => $row['challan_date'],
                        'inward_date' => $row['inward_date'],
                        'material_type' => $row['header_material_type'],
                        'material_name' => '',
                        'transport' => $row['transport'],
                        'vehicle_no' => $row['vehicle_no'],
                        'transport_company' => $row['transport_company'],
                        'driver_name' => $row['driver_name'],
                        'driver_contact' => $row['driver_contact'],
                        'entry_time' => $row['entry_time'],
                        'entry_by' => $row['entry_by'],
                        'status' => $row['header_status'],
                        'coaReceived' => $row['coaReceived'],
                        'lrNo' => $row['lrNo'],
                        'lrDate' => $row['lrDate'],
                        'materials' => array(),
                        '_seen_lines' => array(),
                    );
                }
                $code = isset($row['material_code']) ? trim((string)$row['material_code']) : '';
                $lineName = isset($row['material_name']) ? trim((string)$row['material_name']) : '';
                if (!usableMaterialName($lineName, $code)) {
                    $lineName = '';
                }
                $lineId = isset($row['line_id']) ? (string)$row['line_id'] : '';
                if ($lineId !== '' && isset($byId[$cid]['_seen_lines'][$lineId])) {
                    continue;
                }
                if ($lineId !== '') {
                    $byId[$cid]['_seen_lines'][$lineId] = true;
                }
                if (!empty($row['material_code']) || $lineName !== '' || !empty($row['line_id'])) {
                    if ($lineName !== '' || !empty($row['material_code']) || !empty($row['line_qty'])) {
                        $byId[$cid]['materials'][] = array(
                            'material_code' => $row['material_code'],
                            'material_name' => $lineName,
                            'material_type' => $row['master_material_type'],
                            'material_subtype' => $row['line_subtype'],
                            'grade' => $row['grade'],
                            'qty' => $row['line_qty'],
                            'unit' => $row['line_unit'],
                            'tax_invoice' => $row['line_tax_invoice'],
                        );
                    }
                }
                if ($lineName !== '' && empty($byId[$cid]['material_name'])) {
                    $byId[$cid]['material_name'] = $lineName;
                }
            }
        }

        foreach ($byId as $grp) {
            $names = array();
            if (!empty($grp['materials'])) {
                foreach ($grp['materials'] as $m) {
                    $n = isset($m['material_name']) ? trim((string)$m['material_name']) : '';
                    $c = isset($m['material_code']) ? trim((string)$m['material_code']) : '';
                    if (usableMaterialName($n, $c)) {
                        $names[] = $n;
                    }
                }
            }
            if (count($names) > 0) {
                $grp['material_name'] = implode(', ', array_values(array_unique($names)));
            }
            unset($grp['_seen_lines']);
            $output[] = $grp;
        }
        applyMaterialNamesFromMaster($conn, $plant, $output);
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getVendors") {
        $output = Array();
        $sql = "SELECT * FROM vendor WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getReturnableList") {
        $output = Array();
        // $sql = "SELECT * FROM challan WHERE challan_no LIKE '%".$_GET["challan_no"]."%' AND po_no LIKE '%".$_GET["po_no"]."%' AND vendor_no LIKE '%".$_GET["vendor_no"]."%'";
        $sql = "SELECT * FROM challan WHERE inward_type='Returnable Outwards' ";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["vendor_name"] = $row1["vendor_name"];
                        $row["email"] = $row1["email"];
                        $row["gst_no"] = $row1["gst_no"];
                    }
                }
                
                $output1 = Array();

                $sql1 = "";
                if ($row["material_type"] == "Raw Material" || $row["material_type"] == "Packing Material") {
                    $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype, m.material_type FROM challan_materials p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
                } else if ($row["material_type"] == "General Material") {
                    $sql1 = "SELECT p.*, m.material_type, m.material_name FROM challan_materials p LEFT JOIN general_material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
                } else if ($row["material_type"] == "Chemicals") {
                    $sql1 = "SELECT p.*, m.chemical_name, m.molecular_wt, m.grade, p.material_code as chemical_no FROM challan_materials p LEFT JOIN chemical m ON p.material_code=m.chemical_no WHERE p.user_no='".$_GET["user_no"]."' AND p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
                } else if ($row["material_type"] == "Glasswares") {
                    $sql1 = "SELECT p.*, m.name, m.capacity, m.unit, m.glassware_class, m.description, p.material_code as glassware_no FROM challan_materials p LEFT JOIN glassware m ON p.material_code=m.glassware_no WHERE p.user_no='".$_GET["user_no"]."' AND p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
                }
                if ($sql1 !== "") {
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
        }
        echo json_encode($output);
    
    
        
    
   } else if($_GET["type"] == "downloadInwardChallanPdf"){
        // View entry → Download PDF (standard pdfimp2 / TCPDF flow)
        $id = isset($_GET["id"]) ? mysqli_real_escape_string($conn, $_GET["id"]) : '';
        $plant = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        if ($id === '') {
            echo "Invalid challan id";
            exit;
        }

        $sql = "SELECT c.*, v.vendor_name, v.email AS contact_email, v.gst_no, v.address_factory AS address
            FROM challan c
            LEFT JOIN vendor v ON c.vendor_no = v.vendor_no
            WHERE c.id = '".$id."'
              AND (c.plant_id = '".$plant."' OR '".$plant."' = '')
            LIMIT 1";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            echo "Challan not found";
            exit;
        }
        $row = $result->fetch_assoc();
        $row["materials"] = getDirectChallanMaterials($conn, $row, isset($_GET["user_no"]) ? $_GET["user_no"] : '');
        $row["material_name"] = flattenDirectMaterialName($row);

        $_GET['filename'] = 'Inward Challan';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $slip = !empty($row['challan_no']) ? $row['challan_no'] : $row['ch_no'];
        $html = "";
        $html .= '
        <table cellpadding="5" border="0.1">
          <tr>
            <td style="width:100%; text-align:center; font-weight:bold; background-color:#DDDAD9;">Inward Payment Slip</td>
          </tr>
        </table>
        <div></div>
        <table cellpadding="5" border="0.1">
          <tr>
            <td style="width:25%;"><b>Vendor name</b></td>
            <td style="width:75%;" colspan="3">'.inwardPdfVal($row['vendor_name']).'</td>
          </tr>
          <tr>
            <td style="width:25%;"><b>PO no.</b></td>
            <td style="width:25%;">'.inwardPdfVal($row['po_no']).'</td>
            <td style="width:25%;"><b>PO date</b></td>
            <td style="width:25%;">'.inwardPdfFmtDate($row['po_date']).'</td>
          </tr>
          <tr>
            <td style="width:25%;"><b>Packaging Slip</b></td>
            <td style="width:25%;">'.inwardPdfVal($slip).'</td>
            <td style="width:25%;"><b>Packaging Slip date</b></td>
            <td style="width:25%;">'.inwardPdfFmtDate($row['challan_date']).'</td>
          </tr>
          <tr>
            <td style="width:25%;"><b>Inward date</b></td>
            <td style="width:25%;">'.inwardPdfFmtDate($row['inward_date']).'</td>
            <td style="width:25%;"><b>COA received</b></td>
            <td style="width:25%;">'.inwardPdfVal(isset($row['coaReceived']) ? $row['coaReceived'] : '').'</td>
          </tr>
          <tr>
            <td style="width:25%;"><b>Transport</b></td>
            <td style="width:25%;">'.inwardPdfVal($row['transport']).'</td>
            <td style="width:25%;"><b>Entry by</b></td>
            <td style="width:25%;">'.inwardPdfVal($row['entry_by']).'</td>
          </tr>
          <tr>
            <td style="width:25%;"><b>Status</b></td>
            <td style="width:25%;">'.inwardPdfVal(strtoupper((string)$row['status'])).'</td>
            <td style="width:25%;"><b>Material type</b></td>
            <td style="width:25%;">'.inwardPdfVal($row['material_type']).'</td>
          </tr>
          <tr>
            <td style="width:25%;"><b>Material Name</b></td>
            <td style="width:75%;" colspan="3">'.inwardPdfVal(isset($row['material_name']) ? $row['material_name'] : '').'</td>
          </tr>';

        if (!empty($row['lrNo']) || !empty($row['lrDate'])) {
            $html .= '
          <tr>
            <td style="width:25%;"><b>L.R. no.</b></td>
            <td style="width:25%;">'.inwardPdfVal($row['lrNo']).'</td>
            <td style="width:25%;"><b>L.R. date</b></td>
            <td style="width:25%;">'.inwardPdfFmtDate($row['lrDate']).'</td>
          </tr>';
        }

        $html .= '
        </table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $fileName = 'Inward_Challan_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', ($slip ? $slip : $id)).'.pdf';
        $pdf->Output($fileName, 'I');
        exit;

   } else if($_GET["type"] == "downloadInwordLog"){
        // Log list → Download PDF (same columns as grid, standard pdfimp2 flow)
        $plant = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        $search = isset($_GET["search_text"]) ? trim($_GET["search_text"]) : '';
        $searchEsc = mysqli_real_escape_string($conn, $search);

        $whereSearch = "";
        if ($search !== '') {
            $like = "%".$searchEsc."%";
            $whereSearch = " AND (
                IFNULL(c.challan_no,'') LIKE '".$like."' OR IFNULL(c.ch_no,'') LIKE '".$like."'
                OR IFNULL(c.po_no,'') LIKE '".$like."' OR IFNULL(v.vendor_name,'') LIKE '".$like."'
                OR IFNULL(c.vendor_no,'') LIKE '".$like."' OR IFNULL(c.material_type,'') LIKE '".$like."'
                OR IFNULL(c.tax_invoice,'') LIKE '".$like."'
            )";
        }

        $_GET['filename'] = 'Inward Log';
        $_GET['pdftype'] = 'landscape';
        include('../pdfimp2.php');

        $html = "";
        $html .= '
        <table cellpadding="5" border="0.1">
          <tr>
            <td style="width:100%; text-align:center; font-weight:bold; background-color:#DDDAD9;">INWARD LOG</td>
          </tr>
        </table>
        <div></div>
        <table cellpadding="4" border="0.1">
          <tr style="text-align:center; background-color:#DDDAD9;">
            <td style="width:4%;"><b>Sr.</b></td>
            <td style="width:10%;"><b>Material type</b></td>
            <td style="width:14%;"><b>Material Name</b></td>
            <td style="width:11%;"><b>Packaging Slip</b></td>
            <td style="width:9%;"><b>Ch. ref</b></td>
            <td style="width:9%;"><b>PO no.</b></td>
            <td style="width:8%;"><b>PO dt.</b></td>
            <td style="width:15%;"><b>Vendor</b></td>
            <td style="width:8%;"><b>Inward date</b></td>
            <td style="width:6%;"><b>Transport</b></td>
            <td style="width:6%;"><b>Status</b></td>
          </tr>';

        ensureChallanMaterialPendingColumns($conn);
        ensureChallanMaterialNameColumn($conn);
        $hasChallanMatName = false;
        $chkChallanMatName = $conn->query("SHOW COLUMNS FROM challan LIKE 'material_name'");
        if ($chkChallanMatName && $chkChallanMatName->num_rows > 0) {
            $hasChallanMatName = true;
        }

        $sql = "SELECT c.id, c.plant_id, c.material_type, c.challan_no, c.ch_no, c.po_no, c.po_date,
                c.inward_date, c.transport, c.status, c.vendor_no, v.vendor_name
                ".($hasChallanMatName ? ", c.material_name" : ", NULL AS material_name")."
            FROM challan c
            LEFT JOIN vendor v ON c.vendor_no = v.vendor_no
            WHERE c.plant_id = '".$plant."' ".$whereSearch."
            ORDER BY c.id DESC
            LIMIT 1500";
        $result = $conn->query($sql);
        $sr = 0;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["materials"] = getDirectChallanMaterials($conn, $row, isset($_GET["user_no"]) ? $_GET["user_no"] : '');
                $tmp = array($row);
                applyMaterialNamesFromMaster($conn, $plant, $tmp);
                $row = $tmp[0];
                $lineNames = array();
                if (!empty($row['materials']) && is_array($row['materials'])) {
                    foreach ($row['materials'] as $mat) {
                        $n = isset($mat['material_name']) ? trim((string)$mat['material_name']) : '';
                        $c = isset($mat['material_code']) ? trim((string)$mat['material_code']) : '';
                        if (usableMaterialName($n, $c)) {
                            $lineNames[] = $n;
                        }
                    }
                }
                if (count($lineNames) === 0) {
                    $lineNames[] = '';
                }
                $vendor = inwardPdfVal($row['vendor_name'], '—');
                if (!empty($row['vendor_no'])) {
                    $vendor .= ' ('.htmlspecialchars($row['vendor_no'], ENT_QUOTES, 'UTF-8').')';
                }
                foreach ($lineNames as $matName) {
                    $sr++;
                    $html .= '
          <tr>
            <td style="width:4%; text-align:center;">'.$sr.'</td>
            <td style="width:10%;">'.inwardPdfVal($row['material_type'], '—').'</td>
            <td style="width:14%;">'.inwardPdfVal($matName, '—').'</td>
            <td style="width:11%;">'.inwardPdfVal($row['challan_no'], '—').'</td>
            <td style="width:9%;">'.inwardPdfVal($row['ch_no'], '—').'</td>
            <td style="width:9%;">'.inwardPdfVal($row['po_no'], '—').'</td>
            <td style="width:8%; text-align:center;">'.inwardPdfFmtDate($row['po_date']).'</td>
            <td style="width:15%;">'.$vendor.'</td>
            <td style="width:8%; text-align:center;">'.inwardPdfFmtDate($row['inward_date']).'</td>
            <td style="width:6%;">'.inwardPdfVal($row['transport'], 'NA').'</td>
            <td style="width:6%; text-align:center;">'.inwardPdfVal(strtoupper((string)$row['status']), '—').'</td>
          </tr>';
                }
            }
        } else {
            $html .= '
          <tr>
            <td colspan="11" style="width:100%; text-align:center;">No Record Found!</td>
          </tr>';
        }

        $html .= '
        </table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Inward_Log.pdf', 'I');
        exit;

   } else if($_GET["type"] == "downloadInwordStamp1"){
        $_GET['filename'] = ' INWARD STAMP'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp6.php');
        $html.="";
         $html.='
         <div></div> <div></div> <div></div> <div></div>
         <table cellpadding="8" border="1">
         <tr>
         <td style="width:20%"></td>
         <td style="width:80%;text-align:center"><b>AMGIS LIFESCIENCE LTD. PANOLI (Unit-2)</b></td>
         </tr>
        </table>
       <table cellpadding="5" border="01" >
         <h3 style="text-align:center;color:#330C00">SECURITY INWARD</h3>';
         
        $html.=' ';
        $sql = "SELECT c. *, o.out_time FROM challan c LEFT JOIN outword o ON o.plant_id=c.plant_id WHERE c.challan_no
        LIKE '%".$_GET["search_text"]."%' OR  c.po_no 
        LIKE '%".$_GET["search_text"]."%' OR c.vendor_no LIKE '%".$_GET["search_text"]."%' ";
        $result = $conn->query($sql);
         $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
         $html.='<tr>
       <td style="width:100%"><b>  Sr No. </b> : '.$i.'.</td>
        </tr>
          <tr>
        <td style="width:100%"><b>  Challan No & Date </b> : '.$row['challan_no'].' &
        '.date('d-m-Y',strtotime($row['challan_date'])).'</td>
       </tr>
          <tr>
        <td style="width:100%"><b>  Vehicle No</b> :  '.$row['vehicle_no'].'</td>
       </tr>
          <tr>
        <td style="width:100%"><b>  In Time </b> : '.$row['entry_time'].'</td>
      </tr>
          <tr>
        <td style="width:100%"><b>  Out Time</b> : '.$row['out_time'].'</td>
        </tr>
          <tr>
        <td style="width:100%"><b>  Date</b> : '.date('d-m-Y',strtotime($row['entry_date'])).'</td>
         </tr>
           <tr>
        <td style="width:100%"><b> Security In-charge Signature</b> :</td>
         </tr>
           <tr>
        <td style="width:100%;text-align:right"><b> Format No</b>: HR/015/FM-002/00</td>
         </tr>';
         
         
    
      
      $html.=' </table>
      
      
      <div></div>  <div></div><div></div>  <div></div> <div></div><div></div> <div></div><div></div>
        
        <table cellpadding="5" border="0.1">
       <tr style="text-align: center; background-color:#DDDAD9;">
        <td style="width:35%;"><b>Sign/Date</b></td>
         <td style="width:30%;"><b>Sign/Date</b></td>
          <td style="width:35%;"><b>Sign/Date</b></td>
          </tr>
           <tr >
        <td style="width:35%;"></td>
         <td style="width:30%;"></td>
          <td style="width:35%;"></td>
          </tr>
         <tr style="text-align: center; background-color:#DDDAD9;">
           <td style="width:35%;"><b>Prepared By</b></td>
            <td style="width:30%;"><b>Checked By</b></td>
             <td style="width:35%;"><b>Approved By</b></td>
              </tr>
               <tr  >
        <td style="width:35%;"></td>
         <td style="width:30%;"></td>
          <td style="width:35%;"></td>
          </tr>';
           $i++;
           
           
         $html.='</table>';
  $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadInwordStamp','I');
            }
        }
        
        
   } else if($_GET["type"] == "downloadInwordStamp2"){
        $_GET['filename'] = ' INWARD STAMP
'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp6.php');
        $html.="";
         $html.='
         <div></div> <div></div> <div></div> <div></div>
         <table cellpadding="8" border="1">
         <tr>
         <td style="width:20%"></td>
         <td style="width:80%;text-align:center"><b>AMGIS LIFESCIENCE LTD. PANOLI (Unit-2)</b></td>
         </tr>
        </table>
       <table cellpadding="5" border="01" >
         <h3 style="text-align:center">SECURITY INWARD</h3>
        <tr>
       <td style="width:100%"><b>  Sr No. </b> :</td>
        </tr>
          <tr>
        <td style="width:100%"><b>  Challan No & Date </b> :</td>
       </tr>
          <tr>
        <td style="width:100%"><b>  Vehicle No</b> :</td>
       </tr>
          <tr>
        <td style="width:100%"><b>  In Time </b> :</td>
      </tr>
          <tr>
        <td style="width:100%"><b>  Out Time</b> :</td>
        </tr>
          <tr>
        <td style="width:100%"><b>  Date</b> :</td>
         </tr>
           <tr>
        <td style="width:100%"><b> Security In-charge Signature</b> :</td>
         </tr>
           <tr>
        <td style="width:100%;text-align:right"><b> Format No</b>: HR/015/FM-002/00</td>
         </tr>
      </table><div></div><div></div><div></div><div></div>
      
      </table>
      <div></div><div></div><div></div> 
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:35%;"><b>Sign/Date</b></td>
         <td style="width:30%"><b>Sign/Date</b></td>
          <td style="width:35%;"><b>Sign/Date</b></td>
          </tr>
          <tr>
           <td style="width:35%;"><b>Prepared By</b></td>
            <td style="width:30%;"><b>Checked By</b></td>
             <td style="width:35%;"><b>Approved By</b></td>
              </tr>
        </table>';
  $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadInwordStamp','I');
        
        
        
}else if($_GET["type"] == "downloadInwordLog_old"){
         include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Inward PO Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp6.php");
      $html.="";
        $html.='
        <h2 style="text-align:center">Inward PO Log</h2>
        <table cellpadding="5">
            <tr>
                <td style="width:10%;">Sr.No</td>
                <td style="width:15%;">Entry Date</td>
                <td style="width:15%;">Challan No.</td>
                <td style="width:15%;">Tax Invoice No.</td>
                <td style="width:15%;">Challan Date</td>
                <td style="width:15%;">Inward By</td>
               
            </tr>';
            $i=1;
            $sql = "SELECT * FROM challan WHERE user_no='".$_GET["user_no"]."' AND challan_no LIKE '%".$_GET["challan_no"]."%' AND po_no LIKE '%".$_GET["po_no"]."%' AND vendor_no LIKE '%".$_GET["vendor_no"]."%'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                 $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
    		    $result1 = $conn->query($sql1);
    		    if ($result1->num_rows > 0) {
    		        while ($row1 = $result1->fetch_assoc()) {
    		            $row["vendor_name"] = $row1["vendor_name"];
    		            $row["email"] = $row1["email"];
    		            $row["gst_no"] = $row1["gst_no"];
    		        }
    		    }
    		    
                $output1 = Array();

                $sql1 = "";
                if ($row["material_type"] == "Raw Material" || $row["material_type"] == "Packing Material") {
                    $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype, m.material_type FROM challan_materials p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
                } else if ($row["material_type"] == "General Material") {
                    $sql1 = "SELECT p.*, m.material_type, m.material_name FROM challan_materials p LEFT JOIN general_material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
                } else if ($row["material_type"] == "Chemicals") {
                    $sql1 = "SELECT p.*, m.chemical_name, m.molecular_wt, m.grade, p.material_code as chemical_no FROM challan_materials p LEFT JOIN chemical m ON p.material_code=m.chemical_no WHERE p.user_no='".$_GET["user_no"]."' AND p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
                } else if ($row["material_type"] == "Glasswares") {
                    $sql1 = "SELECT p.*, m.name, m.capacity, m.unit, m.glassware_class, m.description, p.material_code as glassware_no FROM challan_materials p LEFT JOIN glassware m ON p.material_code=m.glassware_no WHERE p.user_no='".$_GET["user_no"]."' AND p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
                }
                if ($sql1 !== "") {
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["materials"] = $output1;
                    $output[] = $row;
                $html.='
                <tr>
                    <td style="width:10%;">'.$i++.'</td>
                 
                    <td style="width:15%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                    <td style="width:15%;">'.$row['challan_no'].'</td>
                    <td style="width:15%;">'.$row['tax_invoice'].'</td>
                    <td style="width:15%;">'.date('d-m-Y',strtotime($row['challan_date'])).'</td>
                    <td style="width:15%;">'.$row['entry_by'].$row['firstname'].'</td>
                </tr>';
                }
            }
        }
        $html.='
        </table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadInwordLog','I');
}
} else {
    echo "{\"status\":\"invalid\"}";
}






function getGrdeValue($grade , $conn){
    
            if ($grade == 'NA') {
                $grd = [0]; // Default value as an array containing 0
            } else {
                $grd = $grade;
            }
            
            // Ensure $grd is properly formatted as a comma-separated list
            if (!is_array($grd)) {
                $grd = explode(',', $grd); // Convert to an array if it is a string
            }
            
            // Validate $grd to contain only integers
            $grd = array_filter($grd, function($value) {
                return is_numeric($value) && intval($value) > 0; // Allow only positive integers
            });
            
            // Convert back to a comma-separated string for SQL
            $grdList = implode(',', $grd); 
            
            if (!empty($grdList)) {
                // Only execute the query if $grdList is not empty
                $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade WHERE id IN ($grdList)";
               // echo $q; // Debugging: Display the query
                
                $resQ = $conn->query($q);
                if ($resQ) {
                    $prodLatest = $resQ->fetch_assoc();
                    $gradeName = $prodLatest['gradeName'];
                } else {
                    // Handle SQL query errors
                    echo "SQL Error: " . $conn->error;
                }
            } else {
                // Handle case where $grdList is empty
                $gradeName = "NA"; // Set a default value or handle it appropriately
              //  echo "No valid grades to fetch.";
            }     
            
            
            return $gradeName;
            
            
}








$conn->close();
?>