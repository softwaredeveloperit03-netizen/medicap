<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);
    
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

    
    

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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "getPendingDispensing") {
        $output = array();
        $sql = "SELECT DISTINCT l.*,e.firstname, p.product_type, p.product_name, b1.raw_materials, 
        b1.packing_materials FROM bmr l LEFT JOIN product p ON l.product_code=p.product_code 
        LEFT JOIN unitformula b1 ON l.bom_no=b1.mfr_no LEFT JOIN employee e ON l.entry_by=e.emp_id 
        WHERE l.status='start' ";
        //AND l.bmr_no NOT IN (SELECT document_no FROM dispensing WHERE dispensing_for='BMR') GROUP BY l.product_code";
        //  $sql = "SELECT l.*,e.firstname, p.product_type, p.product_name, p.grade, b1.raw_materials, b1.packing_materials FROM bmr l LEFT JOIN product p ON l.product_code=p.product_code LEFT JOIN unitformula b1 ON l.bom_no=b1.mfr_no LEFT JOIN employee e ON l.entry_by=e.emp_id WHERE l.status='start' AND l.company_unit='PLANT-09'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                
                $materials1 = array();
                $materials = $row["raw_materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["material_name"];
                            $material->grade = $row1["grade"];
                            
                            $sql3 = "SELECT t.lower_limit FROM spec_tests t LEFT JOIN specification s 
                            ON t.specification_no=s.specification_no 
                            WHERE s.material_code='".$material->material_code."' 
                            AND s.spec_type IN ('Raw Material Specification', 'Packing Material Specification') 
                            AND t.test='LOD'";
                            $result3 = $conn->query($sql3);
                            if ($result3->num_rows > 0) {
                                while ($row3 = $result3->fetch_assoc()) {
                                    $material->lod_per = $row3["lower_limit"];
                                }
                            } else {
                                $material->lod_per = 0;
                            }
                            $material->qty = +$material->qty;
                            $material->std_qty = round(+$material->qty + ((+$material->batch_qty * +$material->overages) / 100), 2);
                            $material->lod_qty = round((+$material->std_qty * +$material->lod_per) / 100, 2);
                            $material->batch_qty = round((+$material->std_qty + +$material->lod_qty), 2);
                            if ($row1['material_subtype'] !== 'Solvents') {
                                $materials1[] = $material;
                            }
                        }
                    }
                }
                $row["raw_materials"] = $materials1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "sendDispensingRequest") {
        $sql = "INSERT INTO dispensing (user_no,dispensing_for, document_no, product_code, batch_no, batch_size, min_output_qty, max_output_qty, request_by, request_date) VALUES ('".$_GET["user_no"]."','BMR', '".$input["bmr_no"]."', '".$input["product_code"]."', '".$input["batch_no"]."', '".$input["batch_size"]."', '".$input["min_output_qty"]."', '".$input["max_output_qty"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            echo "{\"status\":\"success\"}";
            $materials = $input["raw_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO dispensing_materials (user_no, dispensing_no, material_code, qty,unit, overages) VALUES ('".$_GET["user_no"]."', '$last_id', '".$material["material_code"]."', '".$material["batch_qty"]."', '".$material["unit"]."', '".$material["overages"]."')";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getDispensingActivity") {
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name 
        FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' 
        AND d.dispensing_for='BMR' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade 
                FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code 
                WHERE d.dispensing_no='".$row["id"]."'";
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
    } else if ($_GET["type"] == "getDispensingReceivings") {
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, TIME(d.request_date) as request_time, p.product_type, p.product_name FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.dispensing_for='BMR' AND d.status='complete' AND d.receiving='pending' AND d.dispensing_for='BMR' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
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
    else if ($_GET["type"] == "receive_material") {
         $sql = "UPDATE work_order_batch_lots set received_on='".$entry_date."', 
         received_by='".$_GET["emp_id"]."', dispense_recd_status='".$_GET["status"]."'  WHERE id='".$_GET["id"]."'";
         
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
 
     else if ($_GET["type"] == "update_material_receiving_status") {
         $plant=$_GET["plant_id"];
         $id=$_GET["id"];
         $bmr='BMR'.$plant.$id;
         $sql = "UPDATE  mfg_work_order_hdr set rm_receiving_remarks = '".$input["remarks"]."' ,
         rm_receiving_status = '".$input["rm_status"]."',
         rm_received_date='".$entry_date."', 
         rm_received_by='".$_GET["emp_id"]."',bmr_no='$bmr'  WHERE id='".$_GET["id"]."'";
         
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "receiveMaterial_old") {
        $sql = "UPDATE dispensing SET receiving='done', receive_by='".$_GET["emp_id"]."', receive_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            $sql = "SELECT * FROM bmr WHERE plan_no='".$_GET["document_no"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $stages = json_decode($row["stages"]);
                    for ($i = 0; $i < count($stages); $i++) {
                        $stage = $stages[$i];
                        if (strtoupper($stage->stage) == 'DISPENSING') {
                            $stage->start_date = $_GET["request_date"];
                            $stage->start_time = $_GET["request_time"];
                            $stage->complete_date = date("Y-m-d", $timestamp);
                            $stage->complete_time = date("H:i:s", $timestamp);
                            $stage->status = 'complete';
                        }
                        $stages[$i] = $stage;
                    }
                    $sql = "UPDATE bmr SET stages='".json_encode($stages)."', status='inprocess' WHERE plan_no='".$_GET["document_no"]."'";
                    $conn->query($sql);
                }
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getDispensingLog") {
        $output = array();
        $sql = "SELECT *,p.product_name,a.work_order_id as work_order_id FROM `work_order_batch_lots` a JOIN mfg_work_order_hdr b on a.work_order_id = b.id
        JOIN batch_planning c on b.batch_plan_id = c.id JOIN product p on c.product_code = p.product_code
        WHERE a.status='dispensed'
        ORDER BY a.id DESC";
        // $sql = "SELECT a.*,p.product_name FROM `work_order_batch_lots` a JOIN mfg_work_order_hdr b on a.work_order_id = b.id
        // JOIN batch_planning c on b.batch_plan_id = c.id JOIN product p on c.product_code = p.product_code
        // WHERE a.status='dispensed'
        // ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
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
               $output2 = array();
                $sql2 = "select * from lineclearance WHERE work_order_id='".$row["work_order_id"]."' order by id desc limit 1 ";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        
                        $output2[] = $row2;
                    }
                }
               
                $row["instruction"] = $output2;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "downloadDispensingActivity") {
        $_GET['filename'] = 'Dispensing Requisition Sheet'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        
         $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
        
            

             $html.=' 
             <h2 style="text-align:center">Dispensing Requisition Sheet</h2>
             <h3 style="width:100%; text-align:Left;">Product Details:</h3>
                    <table  border="1" cellpadding="5;">
                    <tr>
                        <td style="width:25%;font-weight:bold; border: solid 1px black;">Product Code</td>
                        <td style="width:25%;">'.$row['product_code'].'</td>
                        <td style="width:25%;font-weight:bold; border: solid 1px black;">Product Name</td>
                        <td style="width:25%;">'.$row['product_name'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold; border: solid 1px black;">Product Type</td>
                        <td style="width:25%;">'.$row['product_type'].'</td>
                        <td style="width:25%;font-weight:bold; border: solid 1px black;">Grade</td>
                        <td style="width:25%;">'.$row['grade'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold; border: solid 1px black;">Grade</td>
                        <td style="width:25%;">'.$row['grade'].'</td>
                        <td style="width:25%;font-weight:bold; border: solid 1px black;">Request Date</td>
                        <td style="width:25%;">'.$row['request_date'].'</td>
                    </tr>';
                  
                }
            
            
        }
               $html.=' </table>
             <div></div>';
             
             
             $html.='<h3 style="width:100%; text-align:Left;">Material Details:</h3>
             <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%; text-align:center;">Material Code.</td>
                    <td style="width: 20%; text-align:center;">Material Name</td>
                    <td style="width: 15%; text-align:center;">Grade</td>
                    <td style="width: 10%; text-align:center;">Qty</td>
                    <td style="width: 15%; text-align:center;">Overages</td>
                    <td style="width: 20%; text-align:center;">Role</td>
                </tr>
            </thead>';
             $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code ";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                
                     $html.='   <tr nobr="true">
                        <td style="width: 20%; text-align:center;">'.$row1['material_code'].'</td>
                        <td style="width: 20%; text-align:center;">'.$row1['material_name'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row1['grade'].'</td>
                        <td style="width: 10%; text-align:center;">'.$row1['qty'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row1['overages'].'</td>
                        <td style="width: 20%; text-align:center;">'.$row1['role'].'</td>
                    </tr>';
                }
            }       
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Dispensing Requisition Sheet.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadRecordDispensingLog") {
        
        $_GET['filename'] = 'Record Dispensing Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Record Dispensing Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%; text-align:center;">Sr.</td>
                    <td style="width: 10%; text-align:center;">BMR No.</td>
                    <td style="width: 10%; text-align:center;">Dosage Form</td>
                    <td style="width: 10%; text-align:center;">Product Code</td>
                    <td style="width: 10%; text-align:center;">Product Name</td>
                    <td style="width: 10%; text-align:center;">MFR No</td>
                    <td style="width: 10%; text-align:center;">Batch No</td>
                    <td style="width: 10%; text-align:center;">batch Size</td>
                    <td style="width: 10%; text-align:center;">Lots</td>
                    <td style="width: 10%; text-align:center;">status</td>
                </tr>
            </thead>';
        $output = Array();
        $sql = "SELECT b.*, DATE(b.start_date) as start_date, p.product_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["materials"] = json_decode($row["materials"]);
                
                $flag = 0;
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["id"]."' AND stage='DISPENSING'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["instructions"] = json_decode($row1["instructions"]);
                        $row["clearances"] = json_decode($row1["clearances"]);
                        $output1 = $row1;
                    }
                    $row["stage"] = $output1;
                    $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width: 10%; text-align:center;">'.$i.'.</td>
                        <td style="width: 10%; text-align:center;">'.$row['bmr_no'].'</td>
                        <td style="width: 10%; text-align:center;">'.$row['dosage_form'].'</td>
                        <td style="width: 10%; text-align:center;">'.$row['product_code'].'</td>
                        <td style="width: 10%; text-align:center;">'.$row['product_name'].'</td>
                        <td style="width: 10%; text-align:center;">'.$row['mfr_no'].'</td>
                        <td style="width: 10%; text-align:center;">'.$row['batch_no'].'</td>
                        <td style="width: 10%; text-align:center;">'.$row['batch_size'].'</td>
                        <td style="width: 10%; text-align:center;">'.$row['lots'].'</td>
                        <td style="width: 10%; text-align:center;">'.$row['status'].'</td>
                    </tr>';
                }$i++;
            }
        }
        
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Record Dispensing Log.pdf', 'I');
    }else if ($_GET["type"] == "downloadDispensingLog") {
        
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";

        $html.='<h3 style="text-align:center;">Fresh Raw Material Dispensing Report</h3>
        <h3>Format No:</h3>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; ">Sr.</td>
                    <td style="width: 11%; ">Date.</td>
                    <td style="width: 11%; ">Document No</td>
                    <td style="width: 11%; ">Product type</td>
                    <td style="width: 11%; ">Product Code</td>
                    <td style="width: 15%; ">Product Name</td>
                    <td style="width: 11%; ">Batch No</td>
                    <td style="width: 15%; ">Output Quantity Range</td>
                    <td style="width: 10%; ">Request By</td>
                </tr>
            </thead>';
            $i=1;
        $sql = "SELECT d.*,e.firstname, DATE(d.request_date) as request_date, p.product_type, p.product_name FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.user_no='".$_GET["user_no"]."' AND d.status='complete' AND d.receiving='done' AND p.product_type LIKE '%".$_GET["product_type"]."%' AND DATE(d.receive_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $html.='<tr nobr="true">
                        <td style="width: 5%; ">'.$i.'</td>
                        <td style="width: 11%; ">'.date('d-m-Y',strtotime($row['complete_date'])).'</td>
                        <td style="width: 11%; ">'.$row['document_no'].'</td>
                        <td style="width: 11%; ">'.$row['product_type'].'</td>
                        <td style="width: 11%; ">'.$row['product_code'].'</td>
                        <td style="width: 15%; ">'.$row['product_name'].'</td>
                        <td style="width: 11%; ">'.$row['batch_no'].'</td>
                        <td style="width: 15%; ">'.$row['min_output_qty'].'-'.$row['max_output_qty'].'</td>
                        <td style="width: 10%; ">'.$row['firstname'].'</td>
                    </tr>';
                    }$i++;
                }   
            }
        }
        
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Record Dispensing Log.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadDispensingReport") {
        $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= '<h3 style="text-align: center">MATERIAL REQUISITION SLIP</h3>';
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, d.dispensing_date, d.receive_by, p.product_type, p.product_name, e.firstname FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.id='".$_GET["id"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
         while ($row = $result->fetch_assoc()) {
            
         $html.='<table border="1" cellpadding="5">
                    <tr>
                        <td style="width: 100%;">Format No.: WH012/F/01/01</td>
                    </tr>
                    <tr>
                        <td style="width: 33%;">From: '.$row["company_unit"].'</td>
                        <td style="width: 33%;">Date: '.date('d-m-Y', strtotime($row["request_date"])).'</td>
                        <td style="width: 34%;">Raised By: '.$row["firstname"].'</td>
                    </tr>
                    <tr>
                        <td style="width: 70%;">Name of Product: '.$row["product_name"].'</td>
                        <td style="width: 30%;">Batch No.: '.$row["batch_no"].'</td>
                    </tr>
                    <tr style="border: solid 1px black">
                        <td style="width: 4%; text-align:center;">Sr.</td>
                        <td style="width: 34%; text-align:center;">Description</td>
                        <td style="width: 15%; text-align:center;">A. R. No.</td>
                        <td style="width: 12%; text-align:center;">Required Quantity Kg./Lit./Nos.</td>
                        <td style="width: 12%; text-align:center;">Issued Quantity Kg./Lit./Nos.</td>
                        <td style="width: 12%; text-align:center;">No. of Container & Quantity</td>
                        <td style="width: 11%; text-align:center;">Ledger Folio No.</td>
                    </tr>';
                    $output1 = array();
                    
                    $check_by='';
                    $done_by = '';
                    $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $check_by = $row1["check_by"];
                            $done_by = $row1["done_by"];
                            $ars = json_decode($row1["ars"]);
                            $container = json_decode($row1["containers"]);
                            for ($j = 0; $j < count($ars); $j++) {
                                $ar = $ars[$j];
                                $html.='<tr nobr="true">
                                    <td style="width: 4%; text-align:center;">'.$i.'.</td>
                                    <td style="width: 34%; text-align:left;">'.$row1['material_name'].' '.$row1['grade'].'</td>
                                    <td style="width: 15%; text-align:center;">'.$ar->ar_no.'</td>
                                    <td style="width: 12%; text-align:center;">'.$row1['batch_qty'].' '.$row1["unit"].'</td>
                                    <td style="width: 12%; text-align:center;">'.$row1['qty'].' '.$row1["unit"].'</td>
                                    <td style="width: 12%; text-align:center;">'.count($container).'</td>
                                    <td style="width: 11%; text-align:center;"></td>
                                </tr>';
                                $i++;
                            }
                        }
                    }
                    
                    $weight_by = '';
                    $sql1 = "SELECT firstname FROM employee WHERE emp_id='$done_by'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $weight_by = $row1["firstname"];
                        }
                    }
                    
                    $check_person = '';
                    $sql1 = "SELECT firstname FROM employee WHERE emp_id='$check_by'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $check_person = $row1["firstname"];
                        }
                    }
                    
                    $receive_person = '';
                    $sql1 = "SELECT firstname FROM employee WHERE emp_id='".$row["receive_by"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $receive_person = $row1["firstname"];
                        }
                    }
                    
                    $html.='<tr>
                        <td style="width: 100%;">Note: Container, Weight and Surrounding area of the balance should be cleaned before and after Weighing.</td>
                    </tr>
                    <tr>
                        <td style="width: 100%;">Remark:</td>
                    </tr>
                    <tr>
                        <td style="width: 25%;">Weight By: '.$weight_by.'</td>
                        <td style="width: 25%;">Checked and Issue By: '.$check_person.'</td>
                        <td style="width: 25%;">Date: '.date('d-m-Y', strtotime($row["dispensing_date"])).'</td>
                        <td style="width: 25%;">Received By: '.$receive_person.'</td>
                    </tr>';
            $html.="</table>";
    
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Dispensing Record.pdf', 'I');
            
           }
        }
    } else if ($_GET["type"] == "getPendingPackingDispensing") {
        $output = array();
        $sql = "SELECT l.*, p.product_type, p.product_name, b1.packing_materials, CONCAT(e.firstname,'', e.emp_id) as entry_by FROM bmr l LEFT JOIN product p ON l.product_code=p.product_code LEFT JOIN unitformula b1 ON l.bom_no=b1.mfr_no LEFT JOIN employee e ON l.entry_by=e.emp_id WHERE l.status='start' AND l.company_unit='PLANT-09' AND l.bmr_no NOT IN (SELECT document_no FROM dispensing WHERE dispensing_for='PACKING')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["packing_materials"] = json_decode($row["packing_materials"]);
                
                $materials = $row["packing_materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["material_name"];
                            $material->grade = $row1["grade"];
                        }
                    }
                    $materials[$i] = $material;
                }
                $row["packing_materials"] = $materials;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "sendPackingDispensingRequest") {
        $sql = "INSERT INTO dispensing (user_no,company_unit, dispensing_for, document_no, product_code, batch_no, batch_size, min_output_qty, max_output_qty, request_by, request_date) VALUES ('".$_GET["user_no"]."', '".$_GET["department"]."', 'PACKING', '".$input["bmr_no"]."', '".$input["product_code"]."', '".$input["batch_no"]."', '".$input["batch_size"]."', '".$input["min_output_qty"]."', '".$input["max_output_qty"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            
            $materials = $input["packing_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO dispensing_materials (user_no, dispensing_no, material_code, qty, batch_qty, unit) VALUES ('".$_GET["user_no"]."', '$last_id', '".$material["material_code"]."', '".$material["qty"]."', '".$material["qty"]."', '".$material["unit"]."')";
                $conn->query($sql1);
            }
            echo json_encode(array("status"=>"success","msg"=>"Packing Material Dispensing Request has been send to Store Department!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "getPackingDispensingReceivings") {
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, TIME(d.request_date) as request_time, p.product_type, p.product_name FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.company_unit='PLANT-09' AND d.dispensing_for='PACKING' AND d.status='Active' AND d.receiving='pending' ORDER BY id DESC";
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
    } else if ($_GET["type"] == "receiveMaterial") {
        $sql = "UPDATE dispensing SET receiving='done', receive_by='".$_GET["emp_id"]."', receive_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPackingDispensingLog") {
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.company_unit='PLANT-09' AND d.dispensing_for='PACKING' AND d.status='Active' AND d.receiving='done' AND DATE(d.receive_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
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
    }
    else if ($_GET["type"] == "downloadPackingDispensingReport") {
        $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= '<h3 style="text-align: center">MATERIAL REQUISITION SLIP</h3>';
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, d.dispensing_date, d.receive_by, p.product_type, p.product_name, e.firstname FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.id='".$_GET["id"]."'  ORDER BY id DESC";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
         while ($row = $result->fetch_assoc()) {
            
         $html.='<table border="1" cellpadding="5">
                    <tr>
                        <td style="width: 100%;">Format No.: WH012/F/01-02</td>
                    </tr>
                    <tr>
                        <td style="width: 33%;">From: '.$row["company_unit"].'</td>
                        <td style="width: 33%;">Date: '.date('d-m-Y',strtotime($row["request_date"])).'</td>
                        <td style="width: 34%;">Raised By: '.$row["firstname"].'</td>
                    </tr>
                    <tr>
                        <td style="width: 70%;">Name of Product: '.$row["product_name"].'</td>
                        <td style="width: 30%;">Batch No.: '.$row["batch_no"].'</td>
                    </tr>
                    <tr style="border: solid 1px black">
                        <td style="width: 4%; text-align:center;">Sr.</td>
                        <td style="width: 34%; text-align:center;">Description</td>
                        <td style="width: 15%; text-align:center;">A. R. No.</td>
                        <td style="width: 12%; text-align:center;">Required Quantity Kg./Lit./Nos.</td>
                        <td style="width: 12%; text-align:center;">Issued Quantity Kg./Lit./Nos.</td>
                        <td style="width: 12%; text-align:center;">No. of Container & Quantity</td>
                        <td style="width: 11%; text-align:center;">Ledger Folio No.</td>
                    </tr>';
                    $output1 = array();
                    
                    $check_by='';
                    $done_by = '';
                    $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $check_by = $row1["check_by"];
                            $done_by = $row1["done_by"];
                            
                            $html.='<tr nobr="true">
                                <td style="width: 4%; text-align:center;">'.$i.'.</td>
                                <td style="width: 34%; text-align:left;">'.$row1['material_name'].' '.$row1['grade'].'</td>
                                <td style="width: 15%; text-align:center;">'.$row1['ar_no'].'</td>
                                <td style="width: 12%; text-align:center;">'.$row1['qty'].' '.$row1["unit"].'</td>
                                <td style="width: 12%; text-align:center;">'.$row1['issue_qty'].''.$row1["unit"].'</td>
                                <td style="width: 12%; text-align:center;">'.$row1['issue_qty'].'</td>
                                <td style="width: 11%; text-align:center;"></td>
                            </tr>';
                            $i++;
                        }
                    }
                    $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $weight_by = '';
                    $sql1 = "SELECT firstname FROM employee WHERE emp_id='$done_by'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $weight_by = $row1["firstname"];
                        }
                    }
                    
                    $check_person = '';
                    $sql1 = "SELECT firstname FROM employee WHERE emp_id='".$row1["check_by"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $check_person = $row1["firstname"];
                        }
                    }
                    
                    $receive_person = '';
                    $sql1 = "SELECT firstname FROM employee WHERE emp_id='".$row["receive_by"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $receive_person = $row1["firstname"];
                        }
                    }
                    
                    $html.='<tr>
                        <td style="width: 100%;">Note: Container, Weight and Surrounding area of the balance should be cleaned before and after Weighing.</td>
                    </tr>
                    <tr>
                        <td style="width: 100%;">Remark:</td>
                    </tr>
                    <tr>
                        <td style="width: 25%;">Weight By: '.$weight_by.'</td>
                        <td style="width: 25%;">Checked and Issue By: '.$check_person.'</td>
                        <td style="width: 25%;">Date: '.date('d-m-Y', strtotime($row["dispensing_date"])).'</td>
                        <td style="width: 25%;">Received By: '.$receive_person.'</td>
                    </tr>';
            $html.="</table>";
    
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Dispensing Record.pdf', 'I');
            
           }
        }
    }

}

$conn->close();
?>