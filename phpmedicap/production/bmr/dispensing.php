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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "getPendingDispensing") {
        $output = array();
        $sql = "SELECT l.*, p.product_type, p.product_name, p.grade FROM bmr l LEFT JOIN product p ON l.product_code=p.product_code WHERE l.user_no='".$_GET["user_no"]."' AND l.status='start' AND l.plan_no NOT IN (SELECT document_no FROM dispensing WHERE user_no='".$_GET["user_no"]."' AND dispensing_for='BMR')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                
                $materials = $row["raw_materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["material_name"];
                            $material->grade = $row1["grade"];
                            
                            $sql3 = "SELECT t.lower_limit FROM spec_tests t LEFT JOIN specification s ON t.specification_no=s.specification_no WHERE s.material_code='".$material->material_code."' AND s.spec_type IN ('Raw Material Specification', 'Packing Material Specification') AND t.test='LOD'";
                            $result3 = $conn->query($sql3);
                            if ($result3->num_rows > 0) {
                                while ($row3 = $result3->fetch_assoc()) {
                                    $material->lod_per = $row3["lower_limit"];
                                }
                            } else {
                                $material->lod_per = 0;
                            }
                            
                            $material->qty = +$material->batch_qty;
                            $material->std_qty = round(+$material->qty + ((+$material->batch_qty * +$material->overages) / 100), 2);
                            $material->lod_qty = round((+$material->std_qty * +$material->lod_per) / 100, 2);
                            $material->batch_qty = round((+$material->std_qty + +$material->lod_qty), 2);
                        }
                    }
                    $materials[$i] = $material;
                }
                $row["raw_materials"] = $materials;
                
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
                            
                            $sql3 = "SELECT t.lower_limit FROM spec_tests t LEFT JOIN specification s ON t.specification_no=s.specification_no WHERE s.material_code='".$material->material_code."' AND s.spec_type IN ('Raw Material Specification', 'Packing Material Specification') AND t.test='LOD'";
                            $result3 = $conn->query($sql3);
                            if ($result3->num_rows > 0) {
                                while ($row3 = $result3->fetch_assoc()) {
                                    $material->lod_per = $row3["lower_limit"];
                                }
                            } else {
                                $material->lod_per = 0;
                            }
                            
                            $material->qty = +$material->batch_qty;
                            $material->std_qty = round(+$material->qty + ((+$material->batch_qty * +$material->overages) / 100), 2);
                            $material->lod_qty = round((+$material->std_qty * +$material->lod_per) / 100, 2);
                            $material->batch_qty = round((+$material->std_qty + +$material->lod_qty), 2);
                        }
                    }
                    $materials[$i] = $material;
                }
                $row["packing_materials"] = $materials;
                $row["stages"] = json_decode($row["stages"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "sendDispensingRequest") {
        $sql = "INSERT INTO dispensing (user_no, dispensing_for, document_no, product_code, request_by, request_date) VALUES ('".$_GET["user_no"]."', 'BMR', '".$input["plan_no"]."', '".$input["product_code"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            echo "{\"status\":\"success\"}";
            $materials = $input["raw_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO dispensing_materials (user_no, dispensing_no, material_code, qty, unit, overages, role) VALUES ('".$_GET["user_no"]."', '$last_id', '".$material["material_code"]."', '".$material["batch_qty"]."', '".$material["unit"]."', '".$material["overages"]."', '".$material["role"]."')";
                $conn->query($sql1);
            }
            
            $materials = $input["packing_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO dispensing_materials (user_no, dispensing_no, material_code, qty, unit, overages, role) VALUES ('".$_GET["user_no"]."', '$last_id', '".$material["material_code"]."', '".$material["batch_qty"]."', '".$material["unit"]."', '".$material["overages"]."', '".$material["role"]."')";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getDispensingActivity") {
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.dispensing_for='BMR' ORDER BY id DESC ";
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
    } else if ($_GET["type"] == "getDispensingReceivings") {
        $output = array();
        $sql = "SELECT d.*,e.firstname,e1.firstname as cleaned, DATE(d.request_date) as request_date, 
        TIME(d.request_date) as request_time, p.product_type, p.product_name, p.grade FROM dispensing d 
        LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id 
        LEFT JOIN employee1 e1 ON d.cleaned_by=e1.emp_id  WHERE d.dispensing_for='BMR' AND d.status='complete' 
        AND d.receiving='pending' AND d.dispensing_for='BMR' ORDER BY d.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT d.*,e.firstname, m.material_type, m.material_subtype, m.material_name, m.grade 
                FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code 
                LEFT JOIN employee e ON d.done_by=e.emp_id WHERE d.dispensing_no='".$row["id"]."'";
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
    } else if ($_GET["type"] == "receiveMaterial") {
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
        $sql = "SELECT d.*,e.firstname, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade 
        FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e 
        ON d.request_by=e.emp_id WHERE d.status='complete' AND d.receiving='done' AND p.product_type 
        LIKE '%".$_GET["product_type"]."%' AND DATE(d.receive_date) BETWEEN '".$_GET["from_date"]."' 
        AND '".$_GET["to_date"]."' ORDER BY id DESC";
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
    } else if ($_GET["type"] == "downloadDispensingActivity") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<h3 style="text-align:center;">Dispensing Requisition Sheet</h3>
                <h3>Format No:</h3>
                <h3 style="width:100%; text-align:Left;">Product Details:</h3>
                <table  border="1" cellpadding="5">
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
                        <td style="width:25%;">'.date('d-m-Y',strtotime($row['request_date'])).'</td>
                    </tr>';
                $html.='
                </table>
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
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $html.='
                    <tr nobr="true">
                        <td style="width: 20%; text-align:center;">'.$row1['material_code'].'</td>
                        <td style="width: 20%; text-align:center;">'.$row1['material_name'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row1['grade'].'</td>
                        <td style="width: 10%; text-align:center;">'.$row1['qty'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row1['overages'].'</td>
                        <td style="width: 20%; text-align:center;">'.$row1['role'].'</td>
                    </tr>';
                    }
                }
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Dispensing Requisition Sheet.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadRecordDispensingLog") {
        
        $_GET['filename'] = 'Record Dispensing Log'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
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
        $sql = "SELECT b.*, DATE(b.start_date) as start_date, p.product_name, p.grade, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code";
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
    }

}

$conn->close();
?>