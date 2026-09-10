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
    
    if ($_GET["type"] == "getDosages") {
        $output = array();
        $sql = "SELECT * FROM dosage_form";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND status='approve' AND dosage_form='".$row["dosage_form"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql2 = "SELECT * FROM batch_formula WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row1["product_code"]."' AND status='approve'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row2["shortage"] = "no";
                                $output3 = Array();
                                $sql3 = "SELECT b.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM batch_materials b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row2["id"]."'";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        $sql4 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row3["material_code"]."' AND status='Approved'";
                                        $result4 = $conn->query($sql4);
                                        if ($result4->num_rows > 0) {
                                            $row3["status"] = "available";
                                            while ($row4 = $result4->fetch_assoc()) {
                                                if (+$row4["qty"] == 0) {
                                                    $row3["avl_qty"] = 0.00;
                                                    $row3["status"] = "na";
                                                } else if (+$row4["qty"] < +$row3["batch_qty"]) {
                                                    $row3["avl_qty"] = +$row3["qty"];
                                                    $row3["status"] = "short";
                                                } else if (+$row4["qty"] >= +$row3["batch_qty"]) {
                                                    $row3["avl_qty"] = +$row3["batch_qty"];
                                                    $row3["status"] = "available";
                                                }
                                                
                                                if ($row2["shortage"] == "no" && $row3["status"] !== 'available') {
                                                    $row2["shortage"] = "yes";
                                                }
                                            }
                                        } else {
                                            $row3["avl_qty"] = 0;
                                            $row3["status"] = "not available";
                                            $row2["shortage"] = "yes";
                                        }
                                        $output3[] = $row3;
                                    }
                                }
                                $row2["materials"] = $output3;
                                
                                $output2[] = $row2;
                            }
                        }
                        $row1["batches"] = $output2;
                        
                        $output2 = array();
                        $sql2 = "SELECT stage FROM stages WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row1["product_code"]."' AND status='approve'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["stages"] = $output2;
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getProducts") {
        $output1 = Array();
        $sql1 = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND status='approve'";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output2 = Array();
                $sql2 = "SELECT * FROM batch_formula WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row1["product_code"]."' AND status='approve'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row2["shortage"] = "no";
                        $output3 = Array();
                        $sql3 = "SELECT b.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM batch_materials b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row2["id"]."'";
                        $result3 = $conn->query($sql3);
                        if ($result3->num_rows > 0) {
                            while ($row3 = $result3->fetch_assoc()) {
                                $sql4 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row3["material_code"]."' AND status='Approved'";
                                $result4 = $conn->query($sql4);
                                if ($result4->num_rows > 0) {
                                    $row3["status"] = "available";
                                    while ($row4 = $result4->fetch_assoc()) {
                                        if (+$row4["qty"] == 0) {
                                            $row3["avl_qty"] = 0.00;
                                            $row3["status"] = "na";
                                        } else if (+$row4["qty"] < +$row3["batch_qty"]) {
                                            $row3["avl_qty"] = +$row3["qty"];
                                            $row3["status"] = "short";
                                        } else if (+$row4["qty"] >= +$row3["batch_qty"]) {
                                            $row3["avl_qty"] = +$row3["batch_qty"];
                                            $row3["status"] = "available";
                                        }
                                        
                                        if ($row2["shortage"] == "no" && $row3["status"] !== 'available') {
                                            $row2["shortage"] = "yes";
                                        }
                                    }
                                } else {
                                    $row3["avl_qty"] = 0;
                                    $row3["status"] = "not available";
                                    $row2["shortage"] = "yes";
                                }
                                $output3[] = $row3;
                            }
                        }
                        $row2["materials"] = $output3;
                        
                        $output2[] = $row2;
                    }
                }
                $row1["batches"] = $output2;
                
                $output2 = array();
                $sql2 = "SELECT stage FROM stages WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row1["product_code"]."' AND status='approve'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2[] = $row2;
                    }
                }
                $row1["stages"] = $output2;
                $output1[] = $row1;
            }
        }
        echo json_encode($output1);
    } else if ($_GET["type"] == "saveBatchPlan") {
        $sql = "INSERT INTO bmr_plan (user_no,product_code, batch_size, mfr_no, bom_no, materials, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["product_code"]."', '".$input["batch_size"]."', '".$input["mfr_no"]."', '".$input["id"]."', '".json_encode($input["materials"])."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingBatchPlan") {
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr_plan b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $row["materials"] = json_decode($row["materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getCheckedBatchPlan") {
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr_plan b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='checked'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["materials"] = json_decode($row["materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getBatchPlanLog") {
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr_plan b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.product_code LIKE '%".$_GET["product_code"]."' AND DATE(b.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["materials"] = json_decode($row["materials"]);
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingBatchNos") {
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr_plan b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='approve' AND b.bno='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["materials"] = json_decode($row["materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "allocateBatchNo") {
        $sql = "UPDATE bmr_plan SET batch_no='".$_GET["batch_no"]."', bno='allocate', allocate_by='".$_GET["emp_id"]."', allocate_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            $sql = "SELECT * FROM bmr_plan WHERE id='".$_GET["id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql = "INSERT INTO bmr (user_no, bmr_no, product_code, batch_no, batch_size, lots, bom_no, mfr_no, entry_by, entry_date) VALUES ('".$row["user_no"]."', '".$row["bmr_no"]."', '".$row["product_code"]."', '".$_GET["batch_no"]."', '".$row["batch_size"]."', '".$row["lots"]."', '".$row["bom_no"]."', '".$row["mfr_no"]."', '".$row["entry_by"]."', '".$row["entry_date"]."')";
                    $conn->query($sql);
                    $last_id = $conn->insert_id;
                    
                    $sql = "SELECT * FROM batch_materials WHERE no='".$row["bom_no"]."'";
                    $result1 = $conn->query($sql);
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql = "INSERT INTO bmr_material (no, material_code, overages, qty, unit, batch_qty, batch_unit, lot_qty, lot_unit, role, process) VALUES 
                        ('$last_id', '".$row1["material_code"]."', '".$row1["overages"]."', '".$row1["qty"]."', '".$row1["unit"]."', '".$row1["batch_qty"]."', '".$row1["batch_unit"]."', '".$row1["lot_qty"]."', '".$row1["lot_unit"]."', '".$row1["role"]."', '".$row1["process"]."')";
                        $conn->query($sql);
                    }
                    
                    $sql = "SELECT * FROM mfr WHERE product_code='".$row["product_code"]."' AND mfr_no='".$row["mfr_no"]."'";
                    $result1 = $conn->query($sql);
                    while ($row1 = $result1->fetch_assoc()) {
                        $stages = json_decode($row1["steps"]);
                        for ($i = 0; $i < count($stages); $i++) {
                            $stage = $stages[$i];
                            $sql = "INSERT INTO bmr_stages (bmr_no, stage_no, stage_for, stage, isinstruction, instructions, isequipment, isclearance, isenviornmental, isinprocess, equipments, clearances, isweighing, istest, procedures) VALUES ('$last_id', '".$stage->stage."', '".$stage->step."', '".$stage->process_stage."', '".$stage->isinstruction."', '".json_encode($stage->instructions)."', '".$stage->isequipment."', '".$stage->isclerance."', '".$stage->isenviornmental."', '".$stage->isinprocess."', '".json_encode($stage->equipments)."', '".json_encode($stage->clearances)."', '".$stage->isweighing."', '".$stage->istest."', '".json_encode($stage->procedures)."')";
                            $conn->query($sql);
                        }
                    }
                    break;
                }
            }
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getBatchNosLog") {
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr_plan b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='approve' AND b.bno='allocate' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND b.product_code LIKE'%".$_GET["product_code"]."' AND DATE(b.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["materials"] = json_decode($row["materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getApprovedBatchPlans") {
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.product_code LIKE '%".$_GET["product_code"]."' AND b.batch_size LIKE '%".$_GET["batch_size"]."' AND b.status LIKE '%".$_GET["status"]."%' GROUP By b.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["stages"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getCompletedBatches") {
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='complete' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.product_code LIKE '%".$_GET["product_code"]."' AND b.batch_size LIKE '%".$_GET["batch_size"]."' AND b.status LIKE '%".$_GET["status"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "checkBMRPlan") {
        $sql = "UPDATE bmr_plan SET status='approve', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "approveBMRPlan") {
        $sql = "UPDATE bmr_plan SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }else if ($_GET["type"] == "downloadBatchPlanLog") {
        $_GET['filename'] = 'Batch Plan Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
    
        $html.='
        <h2 style="text-align:center">Batch Plan Log</h2>
            <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%; text-align:center;">Sr.</td>
                    <td style="width: 15%; text-align:center;">Dosage Form</td>
                    <td style="width: 15%; text-align:center;">Product Code</td>
                    <td style="width: 15%; text-align:center;">Product Name</td>
                    <td style="width: 15%; text-align:center;">Grade</td>
                    <td style="width: 15%; text-align:center;">Batch Size</td>
                    <td style="width: 15%; text-align:center;">Prepared By</td>
                </tr>
            </thead>';
       $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr_plan b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.product_code LIKE '%".$_GET["product_code"]."' AND DATE(b.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["materials"] = json_decode($row["materials"]);
                
                $output[] = $row;
            
                $html.='<tr nobr="true">
                        <td style="width: 10%; text-align:center;">'.$i.'.</td>
                        <td style="width: 15%; text-align:center;">'.$row['dosage_form'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row['product_code'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row['product_name'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row['grade'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row['batch_size'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row['check_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Batch Pan Log.pdf', 'I');
    }else if ($_GET["type"] == "downloadPlans") {
        $_GET['filename'] = ' Plan Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
    
        $html.='
        <h2 style="text-align:center">Plan Log</h2>
            <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; ">Sr.</td>
                    <td style="width: 15%; ">Date</td>
                    <td style="width: 10%; ">Unit</td>
                    <td style="width: 10%; ">Plan For</td>
                    <td style="width: 10%; ">Product Type</td>
                    <td style="width: 15%; ">Product Code</td>
                    <td style="width: 15%; ">Product Name</td>
                    <td style="width: 10%; ">Batch Size</td>
                    <td style="width: 10%; ">NO of Batches</td>
                </tr>
            </thead>';
       $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr_plan b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.product_code LIKE '%".$_GET["product_code"]."' AND DATE(b.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["materials"] = json_decode($row["materials"]);
                
                $output[] = $row;
            
                $html.='<tr nobr="true">
                        <td style="width: 5%; ">'.$i.'.</td>
                        <td style="width: 15%; ">'.$row['date'].'</td>
                        <td style="width: 10%; ">'.$row['unit'].'</td>
                        <td style="width: 10%; ">'.$row['plan_for'].'</td>
                        <td style="width: 10%; ">'.$row['product_type'].'</td>
                        <td style="width: 15%; ">'.$row['product_code'].'</td>
                        <td style="width: 15%; ">'.$row['product_name'].'</td>
                        <td style="width: 10%; ">'.$row['Batch Size'].'</td>
                        <td style="width: 10%; ">'.$row['No of Batches'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Batch Pan Log.pdf', 'I');
    }

}

$conn->close();
?>