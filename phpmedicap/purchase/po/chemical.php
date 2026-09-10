<?php
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
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
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveDirectPO") {
          
          if($_GET["purchase_type"]=='Imported')
        {
            $Po_Group = "IMPORT";
        }else{
            $Po_Group = "LCH";
        }
        $last_id=0;
        $sql = "Select count(*) as count from purchaseorder where Po_Group = '".$Po_Group."' ";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['count'];
        }
        if($last_id==0){
            $last_id=1;
        }
        $length = 4;
        $number = substr(str_repeat(0, $length).$last_id, - $length);
        $PO_NO = $Po_Group."/PURHO/05".$last_id."/2022-23";
        $total_value = round($input["total"]);
        $round_value = round(round($input["total"])-$input["total"],2);
        $total_value =  floatval($input["total"])+floatval($round_value);
            
        $sql = "INSERT INTO purchaseorder (user_no, po_no,po_type, vendor_no,broker_name,broker_per, gross_total, gst_total, other_charges, net_total, terms_conditions, entry_by, 
        entry_date, discount, requirement_days, delivery_schedule_date, delivery_address, delivery_type, booking_location, disc_amt, final_total,department,transport,billcompany_code,
        shipcompany_code,purchase_type,currancy,currancy_rate,term_local_transport,Po_Group,Fin_Year,rounding,shipping_handling) VALUES ('".$_GET["user_no"]."','".$PO_NO."', 'Chemical', '".$input["vendor_no"]."',
        '".$input["broker_name"]."', '".$input["broker_per"]."', '".$input["gross_total"]."', '".$input["gst_total"]."', '".$input["other_charges"]."', '".$input["net_total"]."', 
        '".json_encode($input["terms_conditions"])."', '".$_GET["emp_id"]."', '$entry_date', '".$input["discount"]."', '".$input["requirement_days"]."', 
        '".$input["delivery_schedule_date"]."', '".$input["delivery_address"]."', '".$input["delivery_type"]."', '".$input["booking_location"]."', '".$input["dis_amt"]."',
        '".$total_value."','".$input["department"]."','".$input["transport"]."','".$input["billcompany_code"]."','".$input["shipcompany_code"]."','".$input["purchase_type"]."',
        '".$input["currancy"]."','".$input["currancy_rate"]."','".$input["term_local_transport"]."','".$Po_Group."','2002-23','".$round_value."','".$input["shipping_handling"]."')";
        
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;

            $materials = $input["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                if ($material["required_for"] == "Own") {
                    $material["client_code"] = "";
                }
                $sql1 = "INSERT INTO po_material (user_no, po_no,po_type, chemical_no, qty, unit, requirement, quotation_amt, gst, gross_total,
                tax_total, net_total, required_for, client_code,delivery_schedule_date) VALUES ('".$_GET["user_no"]."', '".$last_id."','Chemical',
                '".$material["chemical_no"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["requirement"]."', 
                '".$material["rate"]."', '".$material["gst"]."', '".$material["gross_total"]."', '".$material["tax_total"]."', 
                '".$material["net_total"]."', '".$material["required_for"]."', '".$material["client_code"]."','".$material["schedule_date"]."')";
                $conn->query($sql1);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveIndendPO") {
          if($_GET["purchase_type"]=='Imported')
        {
            $Po_Group = "IMPORT";
        }else{
            $Po_Group = "LCH";
        }
        $sql = "INSERT INTO purchaseorder (user_no, po_type, vendor_no, gross_total, gst_total, net_total, terms_conditions, entry_by, entry_date, discount, requirement_days, delivery_schedule_date, delivery_address,department) VALUES ('".$_GET["user_no"]."', 'Chemical', '".$input["vendor_no"]."', '".$input["gross_total"]."', '".$input["gst_total"]."', '".$input["net_total"]."', '".json_encode($input["terms_conditions"])."', '".$_GET["emp_id"]."', '$entry_date', '".$input["discount"]."', '".$input["requirement_days"]."', '".$input["delivery_schedule_date"]."', '".$input["delivery_address"]."','".$input["department"]."')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            echo "{\"status\":\"success\"}";

            $materials = $input["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                if ($material["required_for"] == "Own") {
                    $material["client_code"] = "";
                }
                $sql1 = "INSERT INTO po_material (user_no, po_no,po_type, chemical_no, qty, unit, requirement,quotation_no, quotation_amt, gst, gross_total, tax_total, net_total, required_for, client_code, indend_no,po_indend) VALUES ('".$_GET["user_no"]."', '".$last_id."','Chemical', '".$material["chemical_no"]."', '".$material["order_qty"]."', '".$material["unit"]."', '".$material["requirement"]."','".$material["quotation_no"]."', '".$material["quotation_amt"]."', '".$material["gst"]."', '".$material["gross_total"]."', '".$material["gst_total"]."', '".$material["net_total"]."', '".$material["required_for"]."', '".$material["client_code"]."', '".$material["indend_no"]."','Approve')";
                // echo $sql1;
                $conn->query($sql1);
                
                $sql2 = "UPDATE indend_chemical SET po_indend='Approve' WHERE id='".$material["id"]."'";
                $conn->query($sql2); 
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "saveIndendPONew") {//Modified by devaraj
        $datas = $input["data"];
        $pos = json_decode(json_encode($datas));
       
          if($_GET["purchase_type"]=='Imported')
        {
            $Po_Group = "IMPORT";
        }else{
            $Po_Group = "LCH";
        }
        $last_id=0;
    
        foreach ($pos as $po) {
                $sql = "Select count(*) as count from purchaseorder where Po_Group = '".$Po_Group."' ";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['count'];
        }
            if($last_id==0){
                $last_id=1;
            } 
            $length = 4;
            
            $total_value = round($po->net_total);
            $round_value = round(round($po->net_total)-$po->net_total,2);
            $total_value =  floatval($po->net_total)+floatval($round_value);
            
            
            $number = substr(str_repeat(0, $length).$last_id, - $length);
            $PO_NO = $Po_Group."/PURHO/05".$last_id."/2022-23";
            $sql = "INSERT INTO purchaseorder (user_no, po_no, po_type, vendor_no, gross_total, gst_total, net_total, terms_conditions, entry_by,
            entry_date, discount, requirement_days, delivery_schedule_date, delivery_address,department,billcompany_code,shipcompany_code, transport,
            local_transport,purchase_type,currancy,currancy_rate,term_local_transport,Po_Group,Fin_Year,indent_no,rounding,final_total,disc_amt) 
                    VALUES ('".$_GET["user_no"]."','".$PO_NO."', 'Chemical', '".$po->vendor_no."', '".$po->gross_total."', '".$po->gst_total."', 
            '".$po->net_total."', '".json_encode($po->terms_conditions)."', '".$_GET["emp_id"]."', '$entry_date', '".$po->discount."', 
            '".$po->requirement_days."', '".$po->delivery_schedule_date."', '".$po->delivery_address."','".$po->department."',
            '".$po->billcompany_code."','".$po->shipcompany_code."','".$po->transport."','".$po->local_transport."','".$po->purchase_type."',
            '".$po->currancy."','".$po->currancy_rate."','".$po->term_local_transport."','".$Po_Group."','2002-23','".$po->indent_no."',
            '".$round_value."','".$total_value."','".$po->disc_amt."')";
 
             if ($conn->query($sql)) {
                 $last_id = $conn->insert_id;
                 $materials =  json_decode(json_encode($po->materials));
                 foreach ($materials as $mat) {
                     //echo $mat->material_code." ".$mat->required_for, "\n";
                     if ($mat->required_for == "Own") {
                        $mat->client_code = "";
                    }
                    $sql1 = "INSERT INTO po_material (user_no, po_no,po_type, chemical_no, qty, unit, requirement,quotation_no, quotation_amt, gst,
                    gross_total, tax_total, net_total, required_for, client_code, indend_no,po_indend,disc_per,disc_amt,delivery_schedule_date) VALUES
                    ('".$_GET["user_no"]."', '".$last_id."','Chemical', '".$mat->chemical_no."', '".$mat->order_qty."',
                    '".$mat->unit."', '".$mat->requirement."','".$mat->quotation_no."', '".$mat->quotation_amt."',
                    '".$mat->gst."', '".$mat->gross_total."', '".$mat->gst_total."', '".$mat->net_total."',
                    '".$mat->required_for."', '".$mat->client_code."', '".$mat->indend_no."','Approve','".$mat->disc_per."', 
                    '".$mat->disc_amt."', '".$mat->delivery_schedule_date."')";
                    $conn->query($sql1);    
                  //  echo $sql1;
                    $sql2 = "UPDATE indend_chemical SET po_indend='Approve' WHERE id='".$mat->id."'";
                    $conn->query($sql2);
                 }
             }
        } 
    
            echo "{\"status\":\"success\"}";
    } 
    
    else if ($_GET["type"] == "getPendingIndend") {
        $output = Array();
       //$sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name, m.chemical_name FROM indend_chemical i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN chemical m ON i.chemical_no=m.chemical_no WHERE i.user_no='gmpdemo1' AND i.status='pending' AND DATE(i.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY i.id DESC";
        $sql="SELECT max(i.id) as id, i.entry_by,i.entry_date,i.indend_no FROM indend_chemical i WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve'  AND i.po_indend='pending' AND DATE(i.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  Group by i.entry_by,i.entry_date,i.indend_no ORDER BY 1 DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $gross_total = 0;
                $gst_total = 0;
                $net_total = 0;
                
                $output1 = Array();
                $sql1 = "SELECT i.*, m.chemical_name,v.vendor_name,'' as delivery_schedule_date, 0 as disc_amt,0 as disc_per FROM indend_chemical i LEFT JOIN chemical m ON i.chemical_no=m.chemical_no LEFT JOIN vendor v ON i.vendor_no=v.vendor_no WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve' AND i.po_indend='pending' AND i.indend_no='".$row["indend_no"]."'";

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
        $sql = "SELECT p.*, v.vendor_name, v.address, v.gst_no, v.city, v.state_code, v.state_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN state s ON v.state_code= s.state_code WHERE p.user_no='gmpdemo1' AND p.po_type='Chemical' AND p.status='Pending' AND p.department LIKE '%%' AND DATE(p.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY p.id desc";
        //$sql="SELECT p.*, v.vendor_name, v.address, v.gst_no, v.city, v.state_code, s.state_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN state s ON v.state_code= s.state_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_type='Chemical' AND p.status='Pending'  AND p.department LIKE '%".$_GET["department_name"]."%' AND DATE(p.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT p.*, m.chemical_name FROM po_material p LEFT JOIN chemical m ON p.chemical_no=m.chemical_no WHERE p.po_no='".$row["id"]."'AND   (p.po_indend='Approve' OR p.material_status='pending') ";
                
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
    } else if ($_GET["type"] == "checkPO") {
        $sql = "UPDATE purchaseorder SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPOLog") {
        $output = array();
        $sql = "SELECT p.*, v.vendor_name,v.address, v.address_corporate, v.address_factory, v.gst_no, v.location, 
        v.state_code,v.state_name, v.pincode, c.company_name, c.mobile_no, c1.company_name as company_name1 , 
        c1.mobile_no as mobile_no1,a.agent_name  FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
        LEFT JOIN state s ON v.state_code= s.state_code LEFT JOIN company c ON p.billcompany_code=c.company_code 
        LEFT JOIN company c1 ON p.shipcompany_code=c1.company_code LEFT JOIN agent a ON p.broker_name=a.agent_no 
        WHERE p.user_no='".$_GET["user_no"]."' AND p.po_type='Chemical Material' AND p.vendor_no LIKE '%".$_GET["vendor_no"]."' AND DATE(p.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY p.id DESC";
        //echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                $sql1 = "SELECT p.*, m.material_name,m.material_subtype FROM po_material p LEFT JOIN Chemical Material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."'";
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
    } else if ($_GET["type"] == "getRejectedPO") {
        $output = array();
        $sql = "SLELECT * FROM purchaseorder WHERE user_no='".$_GET["user_no"]."' AND po_type='Packing Material' AND status='Rejected'";
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
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "ammendPO") {
        $sql = "INSERT INTO purchaseorder (user_no, po_type, vendor_no, requirement_days, gross_total, gst_total, net_total, terms_conditions, entry_by, entry_date, discount, delivery_schedule_date, delivery_address) VALUES ('".$_GET["user_no"]."', 'Chemicals', '".$input["vendor_no"]."', '".$input["requirement_days"]."', '".$input["gross_total"]."', '".$input["gst_total"]."', '".$input["net_total"]."', '".json_encode($input["terms_conditions"])."', '".$_GET["emp_id"]."', '$entry_date', '".$input["discount"]."', '".$input["delivery_schedule_date"]."', '".$input["delivery_address"]."')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            echo "{\"status\":\"success\"}";

            $sql = "UPDATE purchaseorder SET status='ammendment' WHERE id='".$input["id"]."'";
            $conn->query($sql);

            $materials = $input["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO po_material (user_no, po_no, material_code, qty, unit, requirement, quotation_amt, gst, gross_total, tax_total, net_total, required_for, client_code) VALUES ('".$_GET["user_no"]."', '".$last_id."', '".$material["material_code"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["requirement"]."', '".$material["quotation_amt"]."', '".$material["gst"]."', '".$material["gross_total"]."', '".$material["tax_total"]."', '".$material["net_total"]."', '".$material["required_for"]."', '".$material["client_code"]."')";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPODetails") {
        $sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no WHERE p.user_no='".$_GET["user_no"]."' AND p.po_type='Chemicals' AND p.status='approve' AND p.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT p.*, m.chemical_name, m.molecular_wt, m.grade, p.material_code as chemical_no FROM po_material p LEFT JOIN chemical m ON p.material_code=m.chemical_no WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
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
        } else {
            echo "{}";
        }
    
    }else if($_GET['type'] == 'downloadPOLog'){
        $_GET['filename'] = 'Chemical Material Report'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Chemical Material Report</h2>
        <table cellpadding="5" style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
                <td style="width:5%;">sr.</td>
                <td style="width:10%;">Date</td>
                <td style="width:10%;">PO No.</td>
                <td style="width:10%;">Vendor Name</td>
                <td style="width:10%;">Broker Name</td>
                <td style="width:10%;">Broker Percentage</td>
                <td style="width:9%;">Discount</td>
                <td style="width:9%;">Gross Total</td>
                <td style="width:9%;">Tax Total</td>
                <td style="width:9%;">Net Total</td>
                <td style="width:9%;">status</td>
            </tr>';
           $output = array();
        $sql = "SELECT p.*, v.vendor_name, v.address, v.gst_no, v.city, v.state_code, v.state_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN state s ON v.state_code= s.state_code WHERE p.user_no='gmpdemo1' AND p.po_type='Chemical' AND p.department LIKE '%%' AND DATE(p.entry_date) BETWEEN '2022-05-01' AND '2022-05-21' ORDER BY p.id DESC";
        //$sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no WHERE p.user_no='".$_GET["user_no"]."' AND p.po_type='Chemicals' AND p.vendor_no LIKE '%".$_GET["vendor_no"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = array();
                $sql1 = "SELECT p.*, m.chemical_name, m.molecular_wt, m.grade, p.material_code as chemical_no FROM po_material p LEFT JOIN chemical m ON p.material_code=m.chemical_no WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
                
                 $html.='
                    <tr nobr="true">
                        <td>'.$i++.'</td>
                        <td>'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td>'.$row["po_no"].'</td>
                        <td>'.$row["vendor_name"].'</td>
                        <td>'.$row['broker_name'].'</td>
                        <td>'.$row['broker_per'].'</td>
                        <td>'.$row['discount'].'</td>
                        <td>'.$row["gross_total"].'</td>
                        <td>'.$row["gst_total"].'</td>
                        <td>'.$row['net_total'].'</td>
                        <td>'.$row['entry_by'].'</td>
                        <td>'.$row['status'].'</td>
                    </tr>';
                
            }
        }
            $html.='
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PurchaseOrder.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadPOReport") {
        $_GET['filename'] = 'Purchase Order'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        
      //  $sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no WHERE p.id='".$_GET["id"]."'";
         
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
                                    <td style="width:40%;"><b>State:</b></td>
                                    <td style="width:60%;">'.$row['state_name'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Pin:</b></td>
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
                                    <td style="width:40%;"><b>State:</b></td>
                                    <td style="width:60%;">'.$row['state_name'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Pin:</b></td>
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
                                     $html.='<td style="width:20%; colspan=2 ">Material Name</td>';
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
        
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>