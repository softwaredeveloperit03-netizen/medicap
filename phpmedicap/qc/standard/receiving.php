<?php
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d H:i:s", $timestamp);
    // db.php already parses php://input — do not overwrite with a second empty read
    if (!isset($input) || !is_array($input)) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        $input = is_array($decoded) ? $decoded : array();
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

   if ($_GET["type"] == "saveChallan") {
        if (!is_array($input)) {
            $input = array();
        }
        $standards = array();
        if (isset($input["standards"]) && is_array($input["standards"])) {
            $standards = $input["standards"];
        } elseif (isset($input["materials"]) && is_array($input["materials"])) {
            $standards = $input["materials"];
        }
        if (count($standards) === 0) {
            echo "{\"status\":\"error\",\"message\":\"Add at least one standard line before save\"}";
            exit;
        }
        for ($i = 0; $i < count($standards); $i++) {
            if (!is_array($standards[$i])) {
                $standards[$i] = array();
            }
            $standards[$i]["status"] = "pending";
        }
        $standardType = $conn->real_escape_string((string)($input["standard_type"] ?? ''));
        $challanNo = $conn->real_escape_string((string)($input["challan_no"] ?? ''));
        $challanDate = $conn->real_escape_string((string)($input["challan_date"] ?? ''));
        $taxInvoice = $conn->real_escape_string((string)($input["tax_invoice"] ?? ''));
        $poNo = $conn->real_escape_string((string)($input["po_no"] ?? ''));
        $poDate = $conn->real_escape_string((string)($input["po_date"] ?? ''));
        $vendorNo = $conn->real_escape_string((string)($input["vendor_no"] ?? ''));
        $grossTotal = $conn->real_escape_string((string)($input["gross_total"] ?? '0'));
        $gstTotal = $conn->real_escape_string((string)($input["gst_total"] ?? '0'));
        $netTotal = $conn->real_escape_string((string)($input["net_total"] ?? '0'));
        $plantId = $conn->real_escape_string((string)($_GET["plant_id"] ?? '1126'));
        $standardsJson = $conn->real_escape_string(json_encode($standards));
        $sql = "INSERT INTO receving (plant_id, standard_type, challan_no, challan_date, tax_invoice, po_no, po_date, vendor_no, entry_by, entry_date, gross_total, gst_total, net_total, standards, status) VALUES ('".$plantId."','".$standardType."','".$challanNo."','".$challanDate."', '".$taxInvoice."', '".$poNo."', '".$poDate."', '".$vendorNo."', '".$_GET["emp_id"]."', '$entry_date', '".$grossTotal."', '".$gstTotal."', '".$netTotal."','".$standardsJson."','pending')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"error\",\"message\":\"".$conn->real_escape_string($conn->error)."\"}";
        }
    } else if ($_GET["type"] == "getChallansLog") {
        @ini_set('display_errors', '0');
        header('Content-Type: application/json; charset=utf-8');
        $output = array();
        $vendorNo = isset($_GET["vendor_no"]) ? trim((string)$_GET["vendor_no"]) : '';
        $fromDate = isset($_GET["from_date"]) ? trim((string)$_GET["from_date"]) : '';
        $toDate = isset($_GET["to_date"]) ? trim((string)$_GET["to_date"]) : '';
        if ($fromDate === '' || $toDate === '') {
            $fromDate = date('Y-m-01');
            $toDate = date('Y-m-d');
        }
        $plantId = isset($_GET["plant_id"]) ? trim((string)$_GET["plant_id"]) : '';
        $vendorEsc = $conn->real_escape_string($vendorNo);
        $fromEsc = $conn->real_escape_string($fromDate);
        $toEsc = $conn->real_escape_string($toDate);
        // Avoid SELECT r.* — standards blob can break json_encode (empty UI while PDF still works)
        $sql = "SELECT r.id, r.plant_id, r.standard_type, r.challan_no, r.challan_date, r.tax_invoice,
                       r.po_no, r.po_date, r.vendor_no, r.entry_by, r.entry_date,
                       r.gross_total, r.gst_total, r.net_total, r.status, r.standards,
                       v.vendor_name
                FROM receving r
                LEFT JOIN vendor v ON v.vendor_no = r.vendor_no
                WHERE LOWER(TRIM(IFNULL(r.status,''))) IN ('pending','')
                AND IFNULL(r.vendor_no,'') LIKE '%".$vendorEsc."%'
                AND DATE(r.entry_date) BETWEEN '".$fromEsc."' AND '".$toEsc."'";
        if ($plantId !== '') {
            $plantEsc = $conn->real_escape_string($plantId);
            $sql .= " AND (IFNULL(r.plant_id,'') = '' OR r.plant_id = '".$plantEsc."')";
        }
        $sql .= " ORDER BY r.id DESC";
        $result = @$conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // List grid does not need standards lines; blob often breaks json_encode
                unset($row["standards"]);
                $row["standards"] = array();
                $output[] = $row;
            }
        }
        $jsonFlags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $json = json_encode($output, $jsonFlags);
        echo ($json !== false) ? $json : '[]';
    } else if ($_GET["type"] == "getPendingReceivings") {
        $output = Array();
        $sql = "SELECT r.*, v.vendor_name FROM receving r LEFT JOIN vendor v ON v.vendor_no=r.vendor_no WHERE r.status='pending' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["standards"] = json_decode($row["standards"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"]=="receiveStandards") {
        $flag = 0;
        $file = "";
        $dev_no = "";

        $dedusting = array();
        if ($_POST["dedustingmaterial"] == "Yes") {
            $dedusting = $_POST["dedusting"];
        }

        if ($_POST["coa_received"] == 'Yes') {
            if (isset($_FILES["coa"])) {
                $rand_no = date("YmdHis", $timestamp);
                $file = "../upload/coa/".$rand_no.basename($_FILES["coa"]["name"]).".pdf";
                move_uploaded_file($_FILES["coa"]["tmp_name"], $file);
                $file = $rand_no.basename($_FILES["coa"]["name"]).".pdf";
            } else {
                $flag = 1;
            }
        } else {
            $sql = "SELECT IFNULL(MAX(i_no), 0) as  i_no FROM deviation";
            $i_no = 0;
            $invoice_no = "";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $i_no = $row["i_no"];
                    break;
                }
            }
            $i_no++;
            $num = strlen($i_no);
            if($num == '1'){
                $dev_no = 'DEV-00'.$i_no;
            }else if($num == '2'){
                $dev_no = 'DEV-0'.$i_no;
            }else{
                $dev_no = 'DEV-'.$i_no;
            }
            $mfg_date = $_POST["mfg_date"];
            $expiry_date = $_POST["exp_date"];
    
            $deviation = json_decode($_POST["deviation"]);
            $product = Array();
            $product["product_code"] = $_POST["material_code"];
            $product["batch_no"] = $_POST["batch_no"];
            $product["mfg_date"] = $_POST["mfg_date"];
            $product["exp_date"] = $_POST["exp_date"];
    
            $sql = "INSERT INTO deviation (user_no, dev_no,i_no,department,related_to,category,type,deviation_date,justification,cause,description,entry_by,entry_date, product_details) VALUES ('".$_GET["user_no"]."','$dev_no','$i_no','".$_GET["department"]."','".$deviation->related_to."','".$deviation->deviation_category."', '".$deviation->type."', '".$entry_date."', '".$deviation->justification."','".$deviation->cause."','".$deviation->description."','".$_GET["emp_id"]."','$entry_date', '".json_encode($product)."')";
            if ($conn->query($sql)) {
                $departments = $deviation->departments;
                if(count($departments) > 0){
                    for ($i = 0; $i < count($departments); $i++) {
                        $temp = $departments[$i];
                        $sql = "INSERT INTO deviation_comments (dev_no,department) VALUES ('$dev_no','$temp->name')";
                        $conn->query($sql);
                    }
                }
            }
        }
    
        if($flag==0){
            $damange = "pending";
            $temp = Array();
            $temp["pack_size"] = $_POST["pack_size"];
            if ($_POST["isdamagecontainer"] == "Yes") {
                $temp["outer_damage"] = $_POST["outer_damage"];
                $temp["inner_damage"] = $_POST["inner_damage"];
                $temp["damage_status"] = "pending";
            } else {
                $damange = "no";
            }
            $temp["isdamagecontainer"] = $_POST["isdamagecontainer"];
            $temp["damange_remark"] = "";
            $temp["packing_condition"] = $_POST["packing_condition"];
            $temp["outer_packing"] = $_POST["outer_packing"];
            $temp["container_type"] = $_POST["container_type"];
            $temp["container_subtype"] = $_POST["container_subtype"];
            $temp["vehicle_condition"] = $_POST["vehicle_condition"];
            $temp["coa_received"] = $_POST["coa_received"];
            if ($_POST["coa_received"] == 'No') {
                $temp["deviation_no"] = $dev_no;
            } else {
                $temp["coa_file"] = $file;
            }
            $temp["received_by"] = $_GET["emp_id"];
            $temp["received_date"] = $entry_date;
            $sql = "UPDATE receving SET batches='".json_encode($input["batches"])."', received_qty='".$input["received_qty"]."', containerTotal='".$input["containerTotal"]."', pack_size='".$input["pack_size"]."', challan_qty='".$input["challan_qty"]."', remark='".$input["remark"]."', packing_condition='".$input["packing_condition"]."', outer_packing='".$input["outer_packing"]."',container_type='".$input["container_type"]."',container_subtype='".$input["container_subtype"]."',status='inprocess' WHERE id='".$_GET["id"]."'";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"failed\",\"\error\":\"".$conn->error."\"}";
            }
        } else{
            echo "{\"status\":\"failed\",\"reason\":\"Upload COA\"}"; 
        }
    }else if ($_GET["type"] == "getReceivingLog") {
        $output = Array();
        $sql = "SELECT r.*, v.vendor_name FROM receving r LEFT JOIN vendor v ON v.vendor_no=r.vendor_no WHERE r.status='inprocess' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["standards"] = json_decode($row["standards"]);
                $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    // } else if ($_GET["type"] == "getPendingWeighingMaterials") {
    //     $output = Array();
    //     // $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND m.material_type='Raw Material' AND receiving='approve' AND weighing='pending' GROUP BY c.id";
    //      $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.chemical_name, m.molecular_wt,c.material_code as chemical_no, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN chemical m ON c.material_code=m.chemical_no LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND c.material_type='Chemicals' AND receiving='approve'AND weighing='pending' GROUP BY c.id";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $row["batches"] = json_decode($row["batches"]);
    //             $row["receiving_details"] = json_decode($row["receiving_details"]);
    //             $damage = 0;
    //             if ($row["damage"] == "approve") {
    //                 $row["damage_details"] = json_decode($row["damage_details"]);

    //                 $damage_details = $row["damage_details"];
    //                 $damange_containers = $damage_details->containers;
    //                 $damage = +$damage_details->total_damage;
    //                 for ($i = 0; $i < count($damange_containers); $i++) {
    //                     $container = $damange_containers[$i];
    //                     $container->gross_wt = 0;
    //                     $container->tare_wt = 0;
    //                     $container->net_wt = 0;
    //                     $container->weight_by = "";
    //                     $container->check_by = "";
    //                     $damange_containers[$i]  = $container;
    //                 }
    //                 $row["damage_containers"] = $damange_containers;
    //             }else{
    //                 $row["damage_containers"] = [];
    //             }
    //             $output1 = Array();
    //             $containers = +$row["containers"] - $damage;
    //             for ($i = 1; $i <= $containers; $i++) {
    //                 $temp = Array();
    //                 $temp["container_no"] = $i;
    //                 $temp["gross_wt"] = 0;
    //                 $temp["tare_wt"] = 0;
    //                 $temp["net_wt"] = 0;
    //                 $temp['weight_by'] = "";
    //                 $temp['check_by'] = "";
    //                 $output1[] = $temp;
    //             }
                
    //             $row["weight_containers"] = $output1;

    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }else if ($_GET["type"] == "saveWeighingMaterials") {
    //     $sql = "UPDATE challan_materials SET weighing='approve', weighing_details='".json_encode($input)."' WHERE id='".$input["id"]."'";
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"failed\"}";
    //     }
    // }else if ($_GET["type"] == "getWeighingMaterials") {
    //     $output = Array();
    //     $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.chemical_name, m.molecular_wt,c.material_code as chemical_no, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN chemical m ON c.material_code=m.chemical_no LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND weighing !='pending' AND c.material_type='Chemicals' GROUP BY c.id";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $row["receiving_details"] = json_decode($row["receiving_details"]);
    //             $row["weighing_details"] = json_decode($row["weighing_details"]);
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    }else if ($_GET["type"] == "getPendingGRN") {
        $output = Array();
        $sql = "SELECT r.*, v.vendor_name FROM receving r LEFT JOIN vendor v ON v.vendor_no=r.vendor_no WHERE r.status='inprocess' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["standards"] = json_decode($row["standards"]);
                $row["batches"] = json_decode($row["batches"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $weighings = $row["weighing_details"];

                $accept_qty = 0;
                $reject_qty = 0;
                $containers = $weighings->containers;
                $damages = $weighings->damage_containers;

                for ($i = 0; $i < count($containers); $i++) {
                    $container = $containers[$i];
                    $accept_qty += +$container->net_wt;
                }

                for ($i = 0; $i < count($damages); $i++) {
                    $container = $damages[$i];
                    if ($container->status == "approve") {
                        $accept_qty += +$container->net_wt;
                    } else {
                        $reject_qty += +$container->net_wt;
                    }
                }
                $short_qty = number_format(+$row["order_qty"] - ($accept_qty + $reject_qty), 2);
                $row["accept_qty"] = $accept_qty;
                $row["reject_qty"] = $reject_qty;
                $row["short_qty"] = $short_qty;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "saveGRN") {
        $input["entry_by"] = $_GET["emp_id"];
        $input["entry_date"] = $entry_date;
        $sql = "UPDATE receving SET status='approve',remark='".$input["remark"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            $batches = $input["batches"];
            for ($i = 0; $i < count($batches); $i++) {
                $batch = $batches[$i];
                
                $grn_no = "GRN-".$input["id"];
                $ar_no = "AR-".$input["id"];
                $sql1 = "INSERT INTO stock_book (user_no, grn_no,ar_no,vendor_no,material_type,material_code, batch_no,qty,unit,mfg_date,exp_date,entry_by,entry_date, containers, receiving_no) VALUES ('".$_GET["user_no"]."','$grn_no', '$ar_no','".$input["vendor_no"]."','Chemicals','".$input["material_code"]."', '".$batch["batch_no"]."', '".$batch["qty_received"]."', '".$input["unit"]."', '".$batch["mfg_date"]."', '".$batch["exp_date"]."', '".$_GET["emp_id"]."', '$entry_date','".$batch["total_containers"]."', '".$input["id"]."')";
                $conn->query($sql1);
                
                $sql1 = "INSERT INTO sampling (user_no, material_code, batch_no, containers, grn_no, grn_date, mfg_date, exp_date) VALUES ('".$_GET["user_no"]."','".$input["material_code"]."', '".$batch["batch_no"]."', '".$batch["containers"]."','$grn_no', '$entry_date', '".$batch["mfg_date"]."', '".$batch["exp_date"]."')";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "getGRNLog") {
        $output = Array();
        $sql = "SELECT r.*, v.vendor_name FROM receving r LEFT JOIN vendor v ON v.vendor_no=r.vendor_no WHERE r.status='approve' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["standards"] = json_decode($row["standards"]);
                $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"]=="getStock") {
        $output = Array();
        $sql = "SELECT r.*, v.vendor_name FROM receving r LEFT JOIN vendor v ON v.vendor_no=r.vendor_no WHERE r.status='approve' ";

    	$result = $conn->query($sql);
    	
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		      $row["standards"] = json_decode($row["standards"]);
                  $row["batches"] = json_decode($row["batches"]);
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }else if ($_GET["type"] == "receivingMaterialLogPDF") {
        $_GET['filename'] = 'receivingMaterialLog'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:cenetr">receivingMaterialLog</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">vendor Name</td>
                    <td style="width: 10%;">Challan  No</td>
                    <td style="width: 10%;">Challan Date</td>
                    <td style="width: 15%;">Client Name</td>
                    <td style="width: 10%;">Grade</td>
                    <td style="width: 15%;">Chemical name</td>
                    <td style="width: 15%;">Receiving Date</td>
                    <td style="width: 10%;">Qty</td>
                </tr>
            </thead>';
                $output = Array();
                $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.chemical_name, m.molecular_wt,c.material_code as chemical_no, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN chemical m ON c.material_code=m.chemical_no LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.receiving !='pending' AND c.material_type='Chemicals' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' GROUP BY c.id";
                $result = $conn->query($sql);
                $j=1;
                if ($result->num_rows > 0) {
                 while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                 $receiving_details = $row["receiving_details"];
                        //for ($i = 0; $i < count($receiving_details); $i++) {
                            $receiving_detail = $receiving_details;
                   
                      $output[] = $row;
                   
                    
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$j.'.</td>
                        <td style="width: 10%;">'.$row['vendor_name'].'</td>
                        <td style="width: 10%;">'.$row['challan_no'].'</td>
                        <td style="width: 10%;">'.date('d-m-Y', strtotime($row['challan_date'])).'</td>
                        <td style="width: 15%;">'.$row['vendor_name'].'</td>
                        <td style="width: 10%;">'.$row['grade'].'</td>
                        <td style="width: 15%;">'.$row['chemical_name'].'</td>
                        <td style="width: 15%;">'.date('d-m-Y h:i:sa', strtotime($receiving_detail->received_date)).'</td>
                        <td style="width: 10%;">'.$row['qty'].'</td>
                    </tr>';
                            
                $j++;
                             
                        
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('receivingMaterialLog.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadWeighingMaterials") {
        $_GET['filename'] = 'WeighingMaterials'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">WeighingMaterials</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 10%;">Sr.</td>
                        <td style="width: 15%;">Challan No</td>
                        <td style="width: 15%;">Material Code</td>
                        <td style="width: 15%;">Chemical Name</td>
                        <td style="width: 15%;">Grade</td>
                        <td style="width: 15%;">Accepted By</td>
                        <td style="width: 15%;">Containers</td>
                    </tr>
                </thead>';
                $output = Array();
                $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.chemical_name, m.molecular_wt,c.material_code as chemical_no, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN chemical m ON c.material_code=m.chemical_no LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND weighing !='pending' AND c.material_type='Chemicals' GROUP BY c.id";
                $result = $conn->query($sql);
                $j=1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                    $row["receiving_details"] = json_decode($row["receiving_details"]);
                    $row["weighing_details"] = json_decode($row["weighing_details"]);
                     $receiving_details = $row["receiving_details"];
                        //for ($i = 0; $i < count($receiving_details); $i++) {
                            $receiving_detail = $receiving_details;
                    $output[] = $row;
                    
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$j.'.</td>
                        <td style="width: 15%;">'.$row['challan_no'].'</td>
                        <td style="width: 15%;">'.$row['material_code'].'</td>
                        <td style="width: 15%;">'.$row['chemical_name'].'</td>
                        <td style="width: 15%;">'.$row['grade'].'</td>
                        <td style="width: 15%;">'.$receiving_detail->received_by.'</td>
                        <td style="width: 15%;">'.$row['containers'].'</td>
                    </tr>';
                            
                $j++;
                             
                        
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('WeighingMaterials.pdf', 'I');
    }
    else if ($_GET["type"] == "GRNLogPDF") {
        $_GET['filename'] = 'GRNLog'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">GRNLog</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 5%;">Sr.</td>
                        <td style="width: 14%;">Date</td>
                        <td style="width: 10%;">Material Code</td>
                        <td style="width: 10%;">Chemical Name</td>
                        <td style="width: 10%;">Grade</td>
                        <td style="width: 15%;">Vendor Name</td>
                        <td style="width: 11%;">Containers</td>
                        <td style="width: 15%;">Receiving Date</td>
                        <td style="width: 10%;">Qty</td>
                    </tr>
                </thead>';
                 $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type, m.chemical_name, m.molecular_wt,c.material_code as chemical_no, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN chemical m ON c.material_code=m.chemical_no LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.grn !='pending' AND c.material_type='Chemicals' AND  DATE(c1.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        $j=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                     $receiving_details = $row["receiving_details"];
                        //for ($i = 0; $i < count($receiving_details); $i++) {
                            $receiving_detail = $receiving_details;
                     $grn_details=$row["grn_details"];
                     $grn_detail=$grn_details;
                    $output[] = $row;
                    
            $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$j.'.</td>
                        <td style="width: 14%;">'.date('d-m-Y', strtotime($grn_detail->entry_date)).'</td>
                        <td style="width: 10%;">'.$row['material_code'].'</td>
                        <td style="width: 10%;">'.$row['chemical_name'].'</td>
                        <td style="width: 10%;">'.$row['grade'].'</td>
                        <td style="width: 15%;">'.$row['vendor_name'].'</td>
                        <td style="width: 11%;">'.$row['containers'].'</td>
                        <td style="width: 15%;">'.date('d-m-Y', strtotime($receiving_detail->received_date)).'</td>
                        <td style="width: 10%;">'.$row['qty'].'</td>
                    </tr>';
                            
                $j++;
                             
                        
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('GRNLog.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadgetStock") {
        $_GET['filename'] = 'Stock'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Stock</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 7%;">Sr.</td>
                        <td style="width: 14%;">GRN No</td>
                        <td style="width: 18%;">Vendor Name</td>
                        <td style="width: 15%;">Chemical No</td>
                        <td style="width: 15%;">Chemical Name</td>
                        <td style="width: 16%;">Batch No</td>
                        <td style="width: 15%;">Available Qty </td>
                    </tr>
                </thead>';
                 $sql = "SELECT s.*, v.vendor_name, v.vendor_type, m.chemical_name,s.material_code as chemical_no FROM stock_book s LEFT JOIN chemical m ON s.material_code=m.chemical_no LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND s.material_type='Chemicals' AND m.grade LIKE '%".$_GET["grade"]."%'AND s.status LIKE '%".$_GET["status"]."%' AND DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
                    $j=1;
    	            $result = $conn->query($sql);
    	            $output = Array();
    	            if($result->num_rows > 0){
    		            while ($row = $result->fetch_assoc()) {
    		                $output[] = $row;
                    
            $html.='<tr nobr="true">
                        <td style="width: 7%;">'.$j.'.</td>
                        <td style="width: 14%;">'.$row['grn_no'].'</td>
                        <td style="width: 18%;">'.$row['vendor_name'].'</td>
                        <td style="width: 15%;">'.$row['chemical_no'].'</td>
                        <td style="width: 15%;">'.$row['chemical_name'].'</td>
                        <td style="width: 16%;">'.$row['batch_no'].'</td>
                        <td style="width: 15%;">'.$row['qty'].'</td>
                    </tr>';
                            
                $j++;
                             
                        
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Stock.pdf', 'I');
    }else if ($_GET["type"] == "downloadChallanLog") {
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE);
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $vendorNo = isset($_GET["vendor_no"]) ? trim((string)$_GET["vendor_no"]) : '';
        $fromDate = isset($_GET["from_date"]) ? trim((string)$_GET["from_date"]) : '';
        $toDate = isset($_GET["to_date"]) ? trim((string)$_GET["to_date"]) : '';
        if ($fromDate === '' || $toDate === '') {
            $fromDate = date('Y-m-01');
            $toDate = date('Y-m-d');
        }
        $plantId = isset($_GET["plant_id"]) ? trim((string)$_GET["plant_id"]) : '';
        $vendorEsc = $conn->real_escape_string($vendorNo);
        $fromEsc = $conn->real_escape_string($fromDate);
        $toEsc = $conn->real_escape_string($toDate);

        $_GET['filename'] = 'Direct Standard Inward PO';
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdfpage'] = 'L';
        include("../../pdfimp2.php");
        if (isset($pdf) && is_object($pdf)) {
            @$pdf->setPrintHeader(false);
            @$pdf->setPrintFooter(false);
        }

        $html = '
        <h2 style="text-align:center; font-size:14px;">Direct Standard Inward PO</h2>
        <table border="1" cellpadding="4" style="border-collapse:collapse; width:100%;">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; text-align:center;">
                        <td style="width: 5%; font-size:8px;">Sr.</td>
                        <td style="width: 12%; font-size:8px;">Standard Type</td>
                        <td style="width: 14%; font-size:8px;">Vendor Name</td>
                        <td style="width: 10%; font-size:8px;">Packaging Slip</td>
                        <td style="width: 10%; font-size:8px;">Slip Date</td>
                        <td style="width: 8%; font-size:8px;">PO No.</td>
                        <td style="width: 10%; font-size:8px;">PO Date</td>
                        <td style="width: 10%; font-size:8px;">Invoice No.</td>
                        <td style="width: 8%; font-size:8px;">Amount</td>
                        <td style="width: 7%; font-size:8px;">Prepared Date</td>
                        <td style="width: 6%; font-size:8px;">Prepared By</td>
                    </tr>
                </thead><tbody>';

        $sql = "SELECT r.*, v.vendor_name
                FROM receving r
                LEFT JOIN vendor v ON v.vendor_no = r.vendor_no
                WHERE LOWER(TRIM(IFNULL(r.status,''))) IN ('pending','')
                AND IFNULL(r.vendor_no,'') LIKE '%".$vendorEsc."%'
                AND DATE(r.entry_date) BETWEEN '".$fromEsc."' AND '".$toEsc."'";
        if ($plantId !== '') {
            $plantEsc = $conn->real_escape_string($plantId);
            $sql .= " AND (IFNULL(r.plant_id,'') = '' OR r.plant_id = '".$plantEsc."')";
        }
        $sql .= " ORDER BY r.id DESC";

        $result = @$conn->query($sql);
        $i = 1;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $challanDate = (!empty($row['challan_date']) && $row['challan_date'] !== '0000-00-00')
                    ? date('d-m-Y', strtotime($row['challan_date'])) : '-';
                $poDate = (!empty($row['po_date']) && $row['po_date'] !== '0000-00-00')
                    ? date('d-m-Y', strtotime($row['po_date'])) : '-';
                $entryDate = (!empty($row['entry_date']) && $row['entry_date'] !== '0000-00-00 00:00:00')
                    ? date('d-m-Y', strtotime($row['entry_date'])) : '-';
                $html .= '<tr nobr="true">
                        <td style="width: 5%; font-size:8px; text-align:center;">'.$i.'.</td>
                        <td style="width: 12%; font-size:8px;">'.htmlspecialchars($row['standard_type'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                        <td style="width: 14%; font-size:8px;">'.htmlspecialchars($row['vendor_name'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                        <td style="width: 10%; font-size:8px; text-align:center;">'.htmlspecialchars($row['challan_no'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                        <td style="width: 10%; font-size:8px; text-align:center;">'.$challanDate.'</td>
                        <td style="width: 8%; font-size:8px; text-align:center;">'.htmlspecialchars($row['po_no'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                        <td style="width: 10%; font-size:8px; text-align:center;">'.$poDate.'</td>
                        <td style="width: 10%; font-size:8px; text-align:center;">'.htmlspecialchars($row['tax_invoice'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                        <td style="width: 8%; font-size:8px; text-align:right;">'.htmlspecialchars($row['net_total'] ?? '0', ENT_QUOTES, 'UTF-8').'</td>
                        <td style="width: 7%; font-size:8px; text-align:center;">'.$entryDate.'</td>
                        <td style="width: 6%; font-size:8px; text-align:center;">'.htmlspecialchars($row['entry_by'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    </tr>';
                $i++;
            }
        } else {
            $html .= '<tr><td colspan="11" style="text-align:center; font-size:8px;">No records found.</td></tr>';
        }
        $html .= '</tbody></table>';

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Standard_Challan_Log.pdf', 'I');
        exit;
    }else if ($_GET["type"] == "weighingMaterialPDF") {
        $_GET['filename'] = 'Weighing of Material Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.chemical_name, m.molecular_wt,c.material_code as chemical_no, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN chemical m ON c.material_code=m.chemical_no LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND weighing !='pending' AND c.material_type='Chemicals' GROUP BY c.id";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='
        <h2 style="text-align:center">Weighing of Material Log</h2>
        <table border="1" cellpadding="5">
               
                    <tr>
                        <td style="width:40%;"><b>Material Name:</b></td>
                        <td style="width:60%;">'.$row['material_name'].'</td>
                    </tr>
                    <tr>
                        <td style="width:40%;"><b>Vendor Name:</b></td>
                        <td style="width:60%;">'.$row['vendor_name'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;"><b>Challan No.:</b></td>
                        <td style="width:25%;">'.$row['challan_no'].'</td>
                        <td style="width:25%;"><b>Challan Date:</b></td>
                        <td style="width:25%;">'.date('d-m-Y', strtotime($row['challan_date'])).'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;"><b>PO No.:</b></td>
                        <td style="width:25%;">'.$row['po_no'].'</td>
                        <td style="width:25%;"><b>PO Date:</b></td>
                        <td style="width:25%;">'.date('d-m-Y', strtotime($row['po_date'])).'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;"><b>Material Type:</b></td>
                        <td style="width:25%;">'.$row['material_type'].'</td>
                        <td style="width:25%;"><b>Material Subtype:</b></td>
                        <td style="width:25%;">'.$row['material_subtype'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;"><b>Material Code:</b></td>
                        <td style="width:25%;">'.$row['material_code'].'</td>
                        <td style="width:25%;"><b>Material Grade:</b></td>
                        <td style="width:25%;">'.$row['grade'].'</td>
                    </tr>
                     <tr>
                        <td style="width:25%;"><b>PO Qty:</b></td>
                        <td style="width:75%;">'.$row['qty'].''.$row['unit'].'</td>
                    </tr>
            </table>
            <div></div>
            <table cellpadding="2" border="1">
                <tr>
                    <td><b>Packing Details:</b></td>
                </tr>
                <tr >
                    <td style="width:25%;"><b>Packing Intactness / Condition:</b></td>
                    <td style="width:25%;"><b>Outer Packing:</b></td>
                    <td style="width:25%;"><b>Container type:</b></td>
                    <td style="width:25%;"><b>Container Subtype:</b></td>
                </tr>
                <tr >
                    <td style="width:25%;"></td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">'.$row['container_type'].'</td>
                    <td style="width:25%;"></td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="2" border="1">
                <tr>
                    <td style="width:20%;"><b>Sr.</b></td>
                    <td style="width:20%;"><b>Container No.</b></td>
                    <td style="width:20%;"><b>Gross Wt. (Tube)</b></td>
                    <td style="width:20%;"><b>Tare Wt. (Tube)</b></td>
                    <td style="width:20%;"><b>Net Wt. (Tube)</b></td>
                </tr>
                <tr>
                    <td style="width:20%;">'.$i.'</td>
                    <td style="width:20%;">'.$row['containers'].'</td>
                    <td style="width:20%;">'.$row['gross_wt'].'</td>
                    <td style="width:20%;">'.$row['tare_wt'].'</td>
                    <td style="width:20%;">'.$row['net_wt'].'</td>
                </tr>
            </table>
             <div></div>
            <table cellpadding="2" border="1">
                <tr>
                    <td><b>Damage Containers Weighing Details:</b></td>
                </tr>
                <tr >
                    <td style="width:20%;"><b>Sr.</b></td>
                    <td style="width:20%;"><b>Container No.</b></td>
                    <td style="width:20%;"><b>Gross Wt. (Tube)</b></td>
                    <td style="width:20%;"><b>Tare Wt. (Tube)</b></td>
                    <td style="width:20%;"><b>Net Wt. (Tube)</b></td>
                </tr>
                <tr>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>';
                $i++;
                }
                }
            $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('weighingMaterialPDF.pdf', 'I');
    }else if($_GET["type"] == "GRNPDF"){
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
         $sql = "SELECT c.*,c1.entry_by,c1.entry_date,c1.approve_by,c1.approve_date,e.firstname,e1.firstname as approved, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type, m.chemical_name, m.molecular_wt,c.material_code as chemical_no, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN chemical m ON c.material_code=m.chemical_no LEFT JOIN employee e ON m.entry_by=e.emp_id LEFT JOIN employee e1 ON m.approve_by=e1.emp_id LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $batch=$row["batches"];
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                $grn=$row["grn_details"];
                $remark=$grn->remark;
                $html.='
                <h3 style="text-align:center;">Good Receipt Note</h3>
                <h3>Challan Details:</h3>
                <table cellpadding="1" border="1">
                    <tr>
                        <td>Chemical Name</td>
                        <td>'.$row['chemical_name'].'</td>
                        <td>Vendor Name</td>
                       <td>'.$row['vendor_name'].'</td>
                    </tr>
                    <tr>
                        <td>Material Code:</td>
                        <td>'.$row['material_code'].'</td>
                        <td>Material Grade:</td>
                        <td>'.$row['grade'].'</td>
                    </tr>
                    <tr>
                        <td>Challan No.:</td>
                        <td>'.$row['challan_no'].'</td>
                        <td>Challan Date:</td>
                        <td>'.date('d-m-Y',strtotime($row['challan_date'])).'</td>
                    </tr>
                    <tr>
                        <td>PO No.:</td>
                        <td>'.$row['po_no'].'</td>
                        <td>PO Date:</td>
                        <td>'.date('d-m-Y',strtotime($row['po_date'])).'</td>
                    </tr>
                    <tr>
                        <td>Material Type:</td>
                       <td>'.$row['material_type'].'</td>
                        <td>Material Subtype</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>PO Qty:</td>
                        <td>'.$row['qty'].''.$row['unit'].'</td>
                    </tr>
                </table>
                <h3>Batch Details:</h3>
                <table cellpadding="2" border="1">
                    <tr>
                        <td style="width:10%">Sr.No.</td>
                        <td style="width:10%;">Batch No</td>
                        <td style="width:20%;">Received Qty</td>
                        <td style="width:20%">Container</td>
                        <td style="width:20%">MFG date</td>
                        <td style="width:20%">Exp Date</td>
                    </tr>';
                    $i=1;
                    for($i=0;$i<count($batch);$i++){
                        $batchs=$batch[$i];
                    $html.='
                    <tr>
                        <td>'.$i++.'</td>
                        <td>'.$batchs->batch_no.'</td>
                        <td>'.$batchs->qty_received.'</td>
                        <td>'.$batchs->total_containers.'</td>
                        <td>'.$batchs->mfg_date.'</td>
                        <td>'.$batchs->exp_date.'</td>
                    </tr>';
                }
                    
                $html.='</table>
                <h3>Material Details:</h3>
                <table cellpadding="1" style="text-align:center;">
                    <tr>
                       <td style="text-align: center; width: 20%;">Containers</td>
                        <td style="text-align: center; width: 20%;">Received Qty</td>
                        <td style="text-align: center; width: 20%;">Short Receipt Qty</td>
                        <td style="text-align: center; width: 20%;">Accepted Qty</td>
                        <td style="text-align: center; width: 20%;">Rejected Qty</td>
                    </tr>';
                    for($i=0;$i<count($batch);$i++){
                        $batchs=$batch[$i];
                    $html.='
                    <tr>
                        <td style="width: 20%;">'.$batchs->total_containers.'</td>
                        <td>'.$batchs->qty_received.'</td>
                        <td>'.$row['short_qty'].''.$row['unit'].'</td>
                        <td>'.$row['accept_qty'].''.$row['unit'].'</td>
                        <td>'.$row['reject_qty'].''.$row['unit'].'</td>
                    </tr>';
                    }
                $html.="</table>";
                
                $html.='
                <div></div>
                <table cellpadding="5" border="1">
                    <tr>
                        <td style="width:100%;"><b>Remark:</b>'.$remark.'</td>
                    </tr>
                </table>';
                
                $html.='
                <div></div>
                <table cellpadding="3">
                    <tr>
                        <td style="text-align: center;width:34%">'.$row['firstname'].'('.$row['entry_by'].')|'.date('d/m/Y H:i ',strtotime($row['entry_date'])).'</td>
                        <td style="text-align: center;width:33%">'.$row['firstname'].'('.$row['entry_by'].')|'.date('d/m/Y H:i ',strtotime($row['entry_date'])).'</td>
                        <td style="text-align: center;width:33%">'.$row['approved'].'('.$row['approve_by'].')|'.date('d/m/Y H:i ',strtotime($row['approve_date'])).'</td>
                    </tr>
                    <tr>
                        <td style="text-align: center;width:34%">Prepared By </td>
                        <td style="text-align: center;width:33%">Reviewed by</td>
                        <td style="text-align: center;width:33%">Approved By</td>
                    </tr>';
                $html.="</table>";
               
            }
        }
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output($file, 'I');
    }    
}
$conn->close();
?>