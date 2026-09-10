<?php
    require '../db.php';
    require '../token.php';
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
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "getPendingBatches") {
        $output = array();
        $sql = "SELECT b.*,e.firstname, p.product_name, p.grade, p.product_type, b1.bom_type, b1.raw_materials, b1.packing_materials FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code LEFT JOIN unitformula b1 ON b.bom_no=b1.mfr_no LEFT JOIN employee e ON b.entry_by=e.emp_id WHERE  b.status='pending'  GROUP BY p.product_code;";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row = array_map('utf8_encode', $row);
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                $materials = $row["raw_materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    
                    $material->recovery_qty = 0;
                    if ($material->material_subtype == "Solvents") {
                        $sql2 = "SELECT IFNULL(SUM(balance_qty), 0) as qty FROM recovery_stock WHERE material_code='".$material->material_code."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row2 = array_map('utf8_encode', $row2);
                                $material->recovery_stock = $row2["qty"];
                                $material->recovery_qty = 0;
                            }
                        } else {
                            $material->recovery_stock = 0;
                            $material->recovery_qty = 0;
                        }
                    }
                    $material->dispensing_qty = +$material->qty;
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
                            $row1 = array_map('utf8_encode', $row1);
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
    } if ($_GET["type"] == "getPendingQABatches") {
        $output = array();
        $sql = "SELECT b.*,e.firstname, p.product_name, p.grade, p.product_type, b1.bom_type, b1.raw_materials, b1.packing_materials FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code LEFT JOIN unitformula b1 ON b.bom_no=b1.mfr_no LEFT JOIN employee e ON b.entry_by=e.emp_id WHERE b.company_unit='".$_GET["department"]."' AND b.status='pending' AND b.issuance='pending' ORDER BY b.entry_date DESC;";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                
                $materials = $row["raw_materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    
                    $material->recovery_qty = 0;
                    if ($material->material_subtype == "Solvents") {
                        $sql2 = "SELECT IFNULL(SUM(balance_qty), 0) as qty FROM recovery_stock WHERE material_code='".$material->material_code."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $material->recovery_stock = $row2["qty"];
                                $material->recovery_qty = 0;
                            }
                        } else {
                            $material->recovery_stock = 0;
                            $material->recovery_qty = 0;
                        }
                    }
                    $material->dispensing_qty = +$material->qty;
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
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } if ($_GET["type"] == "getBatchStatus") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.product_type, b1.bom_type, b1.raw_materials, b1.packing_materials FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code LEFT JOIN unitformula b1 ON b.bom_no=b1.mfr_no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                
                $materials = $row["raw_materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    
                    $material->recovery_qty = 0;
                    if ($material->material_subtype == "Solvents") {
                        $sql2 = "SELECT IFNULL(SUM(balance_qty), 0) as qty FROM recovery_stock WHERE material_code='".$material->material_code."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $material->recovery_stock = $row2["qty"];
                                $material->recovery_qty = 0;
                            }
                        } else {
                            $material->recovery_stock = 0;
                            $material->recovery_qty = 0;
                        }
                    }
                    $material->dispensing_qty = +$material->qty;
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
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "startProduction") {
        $batch_no = "";
        $product_code = $input["product_code"];
        if ($product_code == "FM026") {
            $id1 = 330;
            $id = 0;
            $sql = "SELECT IFNULL(COUNT(id), 0) as id FROM bmr WHERE product_code='FM026' AND status !='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $id = +$row["id"];
                    $id++;
                }
            }
            $id1 = +$id1 + +$id;
            $id2 = '';
            if (strlen($id1) == 1) {
                $id2 = '000'.$id1;
            } else if (strlen($id1) == 2) {
                $id2 = '00'.$id1;
            } else if (strlen($id1) == 3) {
                $id2 = '0'.$id1;
            } else if (strlen($id1) >= 4) {
                $id2 = ''.$id1;
            }
            $batch_no = "CB-".$id2.date("m", $timestamp).date("y", $timestamp);
        } else if ($product_code == "FM096") {
            $sql = "SELECT IFNULL(COUNT(id), 0) as id FROM bmr WHERE product_code='FM096' AND status !='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $id = +$row["id"];
                    $id1 = 10921;
                    $id++;
                    $id2 = +$id1 + +$id;
                    $batch_no = "LTHB-000".$id2;
                }
            }
        } else {
            $sql = "SELECT IFNULL(COUNT(id), 0) as id FROM bmr WHERE product_code !='FM026'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $id = +$row["id"];
                    $id1 = 3100921;
                    $id++;
                    $id2 = +$id1 + +$id;
                    $batch_no = "BATCH-0".$id2;
                }
            }
        }
        $sql = "UPDATE bmr SET status='Start', batch_no='".$batch_no."' WHERE bmr_no='".$input["bmr_no"]."'";
        if ($conn->query($sql)) {
            $materials = $input["raw_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql = "INSERT INTO bmr_materials (bmr_no, material_type, material_subtype, material_code, material_name, grade, req_qty, dispensing_qty, recovery_qty, unit, variation, islod) VALUES ('".$input["bmr_no"]."', 'Raw Material', '".$material["material_subtype"]."', '".$material["material_code"]."', '".$material["material_name"]."', '".$material["grade"]."', '".$material["qty"]."', '".$material["dispensing_qty"]."', '".$material["recovery_qty"]."', '".$material["unit"]."', '".$material["variation"]."', '".$material["islod"]."')";
                $conn->query($sql);
            }
            
            echo json_encode(array("status"=>"success", "msg"=>"Batch Started Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "getActiveBatches") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.product_type FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND b.status !='Completed'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getStartedBatches") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.product_type, u.dispatch_qty FROM bmr b 
        LEFT JOIN product p ON b.product_code=p.product_code LEFT JOIN unitformula u ON b.bom_no=u.mfr_no 
        WHERE b.company_unit='PLANT-09' OR b.company_unit='Master' AND b.status ='start' GROUP BY b.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                $solvents = array();
                $materials = $row["raw_materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1 = array_map('utf8_encode', $row1);
                            $material->material_subtype = $row1["material_subtype"];
                            $material->material_name = $row1["material_name"];
                            $material->grade = $row1["grade"];
                        }
                    }
                    if ($material->material_subtype == 'Solvents') {
                        
                        $sql2 = "SELECT * FROM recovery_process WHERE product_code='".$row["product_code"]."' AND batch_no='".$row["batch_no"]."' AND material_code='".$material->material_code."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row2 = array_map('utf8_encode', $row2);
                                $material->status = 'DONE';
                                $material->send_by = $row2["entry_by"];
                                $material->send_date = $row2["entry_date"];
                                $material->received_qty = $row2["received_qty"];
                            }
                        } else {
                            $material->status = 'PENDING';
                        }
                        
                        $solvents[] = $material;
                    }
                    $materials[$i] = $material;
                }
                $row["solvents"] = $solvents;
                $row["raw_materials"] = $materials;
                
                $row["packing_materials"] = json_decode($row["packing_materials"]);
                
                $materials = $row["packing_materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1 = array_map('utf8_encode', $row1);
                            $material->material_name = $row1["material_name"];
                            $material->grade = $row1["grade"];
                        }
                    }
                    $materials[$i] = $material;
                }
                $row["packing_materials"] = $materials;
                
                $row["current_stage"] = "";
                $ouptut1 = array();
                $sql1 = "SELECT stages FROM specification WHERE product_code='".$row["product_code"]."' AND spec_type='Inprocess Specification'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        $stages = json_decode($row1["stages"]);
                        for ($i = 0; $i < count($stages); $i++) {
                            $stage = $stages[$i];
                            
                            if ($stage->stage == 'Loss on Drying. And Related substance by HPLC' || $stage->stage == 'Pure Chlorhexidine Base (Dry Powder)') {
                                $sql2 = "SELECT COUNT(id) as id FROM technical_info WHERE batch_no='".$row["batch_no"]."' AND stage='".$stage->stage."' AND status!='DELETED'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row2 = array_map('utf8_encode', $row2);
                                        $stage->ti_count = +$row2["id"];
                                    }
                                }
                            }
                            $sql2 = "SELECT * FROM technical_info WHERE batch_no='".$row["batch_no"]."' AND stage='".$stage->stage."' AND status !='DELETED'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $row2 = array_map('utf8_encode', $row2);
                                    $stage->technical_info = $row2;
                                    $stage->ar_no = $row2["ar_no"];
                                    $stage->ti_no = $row2["ti_no"];
                                    $stage->entry_date = $row2["entry_date"];
                                    $stage->ti_status = $row2["remark"];
                                    if ($row2["status"] !== "done") {
                                        $stage->ti_remark = "INPROCESS";
                                        // $row["current_stage"] = $stage->stage;
                                    } else {
                                        $stage->ti_remark = "DONE";
                                    }
                                }
                            } else {
                                if ($row["current_stage"] == '') {
                                    $row["current_stage"] = $stage->stage;
                                }
                                $stage->ti_remark = "PENDING";
                            }
                            $stages[$i] = $stage;
                        }
                        
                        $output1 = $stages;
                        break;
                    }
                }
                $row["stages"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
        }
        else if ($_GET["type"] == "getFreshSolvents") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.product_type FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.company_unit='".$_GET["department"]."' AND b.status IN ('start','inprocess')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM bmr_materials WHERE bmr_no='".$row["bmr_no"]."' AND material_subtype='Solvents' AND material_code !='RM-019'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = array();
                        $sql2 = "SELECT * FROM solvent_requisition WHERE bmr_no='".$row["bmr_no"]."' AND product_code='".$row["product_code"]."' AND batch_no='".$row["batch_no"]."' AND material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($row2 = $result2->fetch_assoc()) {
                            $row2["containers"] = json_decode($row2["containers"]);
                            $output2[] = $row2;
                        }
                        $row1["requisitions"] = $output2;
                        
                        $sql2 = "SELECT IFNULL(SUM(issue_qty), 0) as issue_qty FROM solvent_requisition WHERE bmr_no='".$row["bmr_no"]."' AND product_code='".$row["product_code"]."' AND batch_no='".$row["batch_no"]."' AND material_code='".$row1["material_code"]."' AND status='RECEIVED'";
                        $result2 = $conn->query($sql2);
                        if ($row2 = $result2->fetch_assoc()) {
                            $row1["dispatch_qty"] = +$row2["issue_qty"];
                        }
                        
                        $sql2 = "SELECT IFNULL(SUM(req_qty), 0) as req_qty FROM solvent_requisition WHERE bmr_no='".$row["bmr_no"]."' AND product_code='".$row["product_code"]."' AND batch_no='".$row["batch_no"]."' AND material_code='".$row1["material_code"]."' AND status !='RECEIVED'";
                        $result2 = $conn->query($sql2);
                        if ($row2 = $result2->fetch_assoc()) {
                            $row1["requested_qty"] = +$row2["req_qty"];
                        }
                        $row1["balance_qty"] = $row1["dispensing_qty"] - $row1["dispatch_qty"] - $row1["requested_qty"];
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "completeBatch") {
        $sql = "UPDATE bmr SET status='Complete', complete_by='".$_GET["emp_id"]."', complete_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            $sql1 = "INSERT INTO bmr_packing(bmr_no, plant_name, bom_no, product_code,batch_no,qty, dispatch_qty,unit ,entry_by,entry_date)VALUES('".$input["bmr_no"]."', 'PLANT-09', '".$input["bom_no"]."', '".$input["product_code"]."' ,'".$input["batch_no"]."', '".$input["qty"]."', '".$input["dispatch_qty"]."','kg' , '".$_GET["emp_id"]."' , '$entry_date')";
            echo "$sql1";
            $conn->query($sql1);
            echo json_encode(array("status"=>"success","msg"=>"Batch Completed Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
        
    }
    // else if ($_GET["type"] == "completeBatch"){
    //   $sql = "UPDATE bmr SET status='Complete', complete_by='".$_GET["emp_id"]."', complete_date='".$entry_date."' WHERE id ='".$_GET["id"]."'";
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    
    else if ($_GET["type"] == "getRecoverySolvents") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.product_type FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.company_unit='".$_GET["department"]."' AND b.status IN ('start','inprocess')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM bmr_materials WHERE bmr_no='".$row["bmr_no"]."' AND material_subtype='Solvents' AND material_code !='RM-019'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = array();
                        $sql2 = "SELECT * FROM recovery_requisition WHERE bmr_no='".$row["bmr_no"]."' AND product_code='".$row["product_code"]."' AND batch_no='".$row["batch_no"]."' AND material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($row2 = $result2->fetch_assoc()) {
                            $row2["containers"] = json_decode($row2["containers"]);
                            $output2[] = $row2;
                        }
                        $row1["requisitions"] = $output2;
                        
                        $sql2 = "SELECT IFNULL(SUM(dispatch_qty), 0) as dispatch_qty FROM recovery_requisition WHERE bmr_no='".$row["bmr_no"]."' AND product_code='".$row["product_code"]."' AND batch_no='".$row["batch_no"]."' AND material_code='".$row1["material_code"]."' AND status='SEND'";
                        $result2 = $conn->query($sql2);
                        if ($row2 = $result2->fetch_assoc()) {
                            $row1["dispatch_qty"] = +$row2["dispatch_qty"];
                        }
                        
                        $sql2 = "SELECT IFNULL(SUM(req_qty), 0) as req_qty FROM recovery_requisition WHERE bmr_no='".$row["bmr_no"]."' AND product_code='".$row["product_code"]."' AND batch_no='".$row["batch_no"]."' AND material_code='".$row1["material_code"]."' AND status='PENDING'";
                        $result2 = $conn->query($sql2);
                        if ($row2 = $result2->fetch_assoc()) {
                            $row1["requested_qty"] = +$row2["req_qty"];
                        }
                        $row1["balance_qty"] = $row1["recovery_qty"] - $row1["dispatch_qty"] - $row1["requested_qty"];
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveRecoveryRequisition") {
        $sql = "INSERT INTO recovery_requisition (bmr_no, product_code, batch_no, material_code, req_qty, request_by, request_date,company_unit) VALUES ('".$input["bmr_no"]."','".$input["product_code"]."', '".$input["batch_no"]."', '".$input["material_code"]."', '".$input["required_qty"]."', '".$_GET["emp_id"]."', '$entry_date','".$input["company_unit"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveSolventRequisition") {
        $sql = "INSERT INTO solvent_requisition (bmr_no, bom_for, product_code, batch_no, material_code, req_qty, request_by, request_date,company_unit) VALUES ('".$input["bmr_no"]."', '".$input["bom_for"]."','".$input["product_code"]."', '".$input["batch_no"]."', '".$input["material_code"]."', '".$input["required_qty"]."', '".$_GET["emp_id"]."', '$entry_date','".$input["company_unit"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "receiveFreshSolvent") {
        $sql = "UPDATE solvent_requisition SET status='RECEIVED', receive_by='".$_GET["emp_id"]."', receive_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "downloadSolventRequisitionSlip") {
        require '../../tcpdf/tcpdf.php';
        $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= '<h3 style="text-align: center">MATERIAL REQUISITION SLIP</h3>';
        $output = array();
        $sql = "SELECT d.*, p.product_name, m.material_name, e.firstname as done_by, e1.firstname as request_by, e2.firstname as send_by, e3.firstname as receive_by FROM solvent_requisition d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN material m ON d.material_code=m.material_code LEFT JOIN employee e ON d.done_by=e.emp_id LEFT JOIN employee e1 ON d.request_by=e1.emp_id LEFT JOIN employee e2 ON d.send_by=e2.emp_id LEFT JOIN employee e3 ON d.receive_by=e3.emp_id WHERE d.id='".$_GET["id"]."' ORDER BY d.id DESC";
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
                        <td style="width: 34%;">Raised By: '.$row["request_by"].'</td>
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
                    
                    $html.='<tr nobr="true">
                        <td style="width: 4%; text-align:center;">1.</td>
                        <td style="width: 34%; text-align:left;">'.$row['material_name'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row["ar_no"].'</td>
                        <td style="width: 12%; text-align:center;">'.$row['req_qty'].' '.$row["unit"].'</td>
                        <td style="width: 12%; text-align:center;">'.$row['issue_qty'].' '.$row["unit"].'</td>
                        <td style="width: 12%; text-align:center;">NA</td>
                        <td style="width: 11%; text-align:center;"></td>
                    </tr>';
                    
                    $html.='<tr>
                        <td style="width: 100%;">Note: Container, Weight and Surrounding area of the balance should be cleaned before and after Weighing.</td>
                    </tr>
                    <tr>
                        <td style="width: 100%;">Remark: '.$row["remark"].'</td>
                    </tr>
                    <tr>
                        <td style="width: 25%;">Weight By: '.$row["done_by"].'</td>
                        <td style="width: 25%;">Checked and Issue By: '.$row["send_by"].'</td>
                        <td style="width: 25%;">Date: '.date('d-m-Y', strtotime($row["send_date"])).'</td>
                        <td style="width: 25%;">Received By: '.$row["receive_by"].'</td>
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