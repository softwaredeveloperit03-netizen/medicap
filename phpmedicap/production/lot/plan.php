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

    if ($_GET["type"] == "getProducts") {
        $output = array();
        $sql = "SELECT b.product_code, p.product_name, p.grade FROM batch_formula b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND p.product_type='".$_GET["product_type"]."' GROUP BY b.product_code";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM batch_formula WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["raw_materials"] = json_decode($row1["raw_materials"]);
                
                        $materials = $row1["raw_materials"];
                        for ($i = 0; $i < count($materials); $i++) {
                            $material = $materials[$i];
                            $sql2 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $material->material_name = $row2["material_name"];
                                    $material->grade = $row2["grade"];
                                }
                            }
                            $materials[$i] = $material;
                        }
                        $row1["raw_materials"] = $materials;
                        
                        $row1["packing_materials"] = json_decode($row1["packing_materials"]);
                        
                        $materials = $row1["packing_materials"];
                        for ($i = 0; $i < count($materials); $i++) {
                            $material = $materials[$i];
                            $sql2 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $material->material_name = $row2["material_name"];
                                    $material->grade = $row2["grade"];
                                }
                            }
                            $materials[$i] = $material;
                        }
                        $row1["packing_materials"] = $materials;
                        $row1["stages"] = json_decode($row1["stages"]);
                        $output1[] = $row1;
                    }
                }
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "savePlan") {
        $stages = $input["stages"];
        for ($i = 0; $i < count($stages); $i++) {
            $stage = $stages[$i];
            $stage["status"] = "pending";
            $stages[$i] = $stage;
        }
        $input["stages"] = $stages;
        
        $sql = "INSERT INTO lmr (user_no, company_unit, product_code, bom_no, raw_materials, packing_materials, stages, batch_size, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$input["company_unit"]."', '".$input["product_code"]."', '".$input["bom_no"]."', '".json_encode($input["raw_materials"])."', '".json_encode($input["packing_materials"])."', '".json_encode($input["stages"])."', '".$input["batch_size"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPlans") {
        $output = array();
        $sql = "SELECT l.*, DATE(l.entry_date) as entry_date, p.product_type, p.product_name, p.grade FROM lmr l LEFT JOIN product p ON l.product_code=p.product_code WHERE l.user_no='".$_GET["user_no"]."' AND p.product_type LIKE '%".$_GET["product_type"]."%' AND DATE(l.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
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
    } else if ($_GET["type"] == "downloadPlans") {
        $_GET['filename'] = 'Plans Log'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Plans Log</h2>
        <table border="1" cellpadding="5">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">Sr</td>
                    <td style="width:15%;">Date</td>
                    <td style="width:15%;">Plan No</td>
                    <td style="width:15%;">Product Type</td>
                    <td style="width:15%;">Product code</td>
                    <td style="width:15%;">Product Name</td>
                    <td style="width:15%;">BOM No</td>
               
                </tr>';
                  $output = array();
                    $sql = "SELECT l.*, DATE(l.entry_date) as entry_date, p.product_type, p.product_name, p.grade FROM lmr l LEFT JOIN product p ON l.product_code=p.product_code WHERE l.user_no='".$_GET["user_no"]."' AND p.product_type LIKE '%".$_GET["product_type"]."%' AND DATE(l.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
                    $result = $conn->query($sql);
                    $i=1;
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $row["raw_materials"] = json_decode($row["raw_materials"]);
                            $row["packing_materials"] = json_decode($row["packing_materials"]);
                            $row["stages"] = json_decode($row["stages"]);
                            $output[] = $row;
                    
                    $html.='<tr>
                                <td style="width:10%;">'.$i.'</td>
                                <td style="width:15%;">'.$row['entry_date'].'</td>
                                <td style="width:15%;">'.$row['plan_no'].'</td>
                                <td style="width:15%;">'.$row['product_type'].'</td>
                                <td style="width:15%;">'.$row['product_code'].'</td>
                                <td style="width:15%;">'.$row['product_name'].'</td>
                                <td style="width:15%;">'.$row['bom_no'].'</td>
                            
                            </tr>';
                            $i++;
                            }
                        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Plans Log.pdf', 'I');
    }

}

$conn->close();
?>