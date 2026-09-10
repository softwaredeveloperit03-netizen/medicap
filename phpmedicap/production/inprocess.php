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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "startProduction") {
        $sql = "UPDATE bmr_stages SET status='start', start_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getInprocessBatches") {
        $output = Array();
        $sql = "SELECT b.*, DATE(b.start_date) as start_date, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='start'";
        // $sql = "SELECT b.*, DATE(b.start_date) as start_date, p.product_name, p.grade, p.generic_name, 
        // p.dosage_form, p.shelf_life, p.label_claim ,u.instructions ,u.abbreviation,u.raw_materials ,
        // u.packing_materials ,u.bmr_checklist FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code 
        // LEFT JOIN unitformula u ON b.mfr_no = u.mfr_no WHERE b.status='start'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['abbreviation'] = json_decode($row['abbreviation']);
                $row['instructions'] = json_decode($row['instructions']);
                $row['raw_materials'] = json_decode($row['raw_materials']);
                $row['packing_materials'] = json_decode($row['packing_materials']);
                $row['bmr_checklist'] = json_decode($row['bmr_checklist']);

                $output1 = Array();
                $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                $row["current_stage"] = "";
                $row["last_stage"] = "";
                
                $output1 = array();                
                $equipments = array();
                
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."' AND status ='COMPLETED'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["yields"] = json_decode($row1["yields"]);
                        if ($row1["isprocedure"] == 'YES') {
                            $row1["procedures"] = json_decode($row1["procedures"]);
                        }
                        if ($row1["isinstruction"] == 'YES') {
                            $row1["instructions"] = json_decode($row1["instructions"]);
                        }
                        // if ($row1["isequipment"] == 'YES') {
                        //   $row1["equipments"] = json_decode($row1["equipments"]);
                        //     $equip = $row1["equipments"];
                        //     for ($i = 0; $i < count($equip); $i++) {
                        //         $temp = $equip[$i];
                        //         $temp->stage = $row1["stage"];
                        //         $equipments[] = $temp;
                        //     }
                            
                        // }
                         if ($row1["isequipment"] == 'YES') {
                                $row1["equipments"] = json_decode($row1["equipments"]);
                                
                                $equipments = $row1["equipments"];
                                for ($i = 0; $i < count($equipments); $i++) {
                                    $equipment = $equipments[$i];
                                    
                                    $output2 = array();
                                    $sql2 = "SELECT * FROM equipment WHERE equipment_name='".$equipment->equipment_name."' AND equipment_code NOT IN (SELECT equipment_code FROM equipment_usages WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."' AND stage='".$row1["stage"]."')";
                                    $result2 = $conn->query($sql2);
                                    if ($result2->num_rows > 0) {
                                        while ($row2 = $result2->fetch_assoc()) {
                                            $output2[] = $row2;
                                        }
                                    }
                                    $equipment->equipments = $output2;
                                    
                                    $equipments[$i] = $equipment;
                                }
                                $row1["equipments"] = $equipments;
                                
                                $output2 = array();
                                $sql2 = "SELECT u.*, e.equipment_name FROM equipment_usages u LEFT JOIN equipment e ON u.equipment_code=e.equipment_code WHERE u.bmr_no='".$row["bmr_no"]."' AND u.batch_no='".$row["batch_no"]."' AND u.stage='".$row1["stage"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $output2[] = $row2;
                                    }
                                }
                                $row1["equipment_usages"] = $output2;
                        }
                        if ($row1["isclerance"] == 'YES') {
                            $row1["clearances"] = json_decode($row1["clearances"]);
                            $sql2 = "SELECT * FROM lineclearance WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."' AND stage='".$row1["stage"]."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $row1["clearance_no"] = $row2["clearance_no"];
                                    $row1["clearance_status"] = $row2["status"];
                                    $row1["product_clearance_by"] = $row2["request_by"];
                                    $row1["qa_clearance_by"] = $row2["entry_by"];
                                    $row1["clearances"] = json_decode($row2["checkpoints"]);
                                    break;
                                }
                            } else {
                                $row1["clearance_no"] = "";
                                $row1["clearance_status"] = '';
                            }
                        }
                        if ($row1["isweighing"] == 'YES') {
                            $row1["weighings"] = json_decode($row1["weighings"]);
                            
                             $output2 = array();
                                $sql2 = "SELECT * FROM weight_check WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."' AND stage='".$row1["stage"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row2["checkpoints"] = json_decode($row2["checkpoints"]);
                                        $output2[] = $row2;
                                    }
                                }
                                $row1["weighings_checks"] = $output2;
                        }
                        if ($row1["isenvironment"] == 'YES') {
                            $row1["environments"] = json_decode($row1["environments"]);
                            
                            $output2 = array();
                            $sql2 = "SELECT * FROM environment_check WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."' AND stage='".$row1["stage"]."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $row2["checkpoints"] = json_decode($row2["checkpoints"]);
                                    $output2[] = $row2;
                                }
                            }
                            $row1["environment_checks"] = $output2;
                        }
                        
                        if ($row1["ischeck"] == 'YES') {
                            $row1["checks"] = json_decode($row1["checks"]);
                            
                             $output2 = array();
                                $sql2 = "SELECT * FROM inprocess_check WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."' AND stage='".$row1["stage"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row2["checkpoints"] = json_decode($row2["checkpoints"]);
                                        $output2[] = $row2;
                                    }
                                }
                                $row1["inprocess_checks"] = $output2;
                        }
                        $output1[] = $row1;
                    }
                }
                $row["complete_stages"] = $output1;
                $row1["equipments"] = $equipments;
                
                if ($row["current_stage"] == "") {
                    $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."' AND status IN ('pending','INPROCESS') LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            if ($row["current_stage"] == "" && $row1["status"] !== "COMPLETED") {
                                $row["current_stage"] = $row1["stage"];
                            }
                            $row["last_stage"] = $row1["stage"];
                            
                            if ($row1["isprocedure"] == 'YES') {
                                $row1["procedures"] = json_decode($row1["procedures"]);
                            }
                            if ($row1["isinstruction"] == 'YES') {
                                $row1["instructions"] = json_decode($row1["instructions"]);
                            }
                            if ($row1["isequipment"] == 'YES') {
                                $row1["equipments"] = json_decode($row1["equipments"]);
                                
                                $equipments = $row1["equipments"];
                                for ($i = 0; $i < count($equipments); $i++) {
                                    $equipment = $equipments[$i];
                                    
                                    $output2 = array();
                                    $sql2 = "SELECT * FROM equipment WHERE equipment_name='".$equipment->equipment_name."' AND equipment_code NOT IN (SELECT equipment_code FROM equipment_usages WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."' AND stage='".$row1["stage"]."')";
                                    $result2 = $conn->query($sql2);
                                    if ($result2->num_rows > 0) {
                                        while ($row2 = $result2->fetch_assoc()) {
                                            $output2[] = $row2;
                                        }
                                    }
                                    $equipment->equipments = $output2;
                                    
                                    $equipments[$i] = $equipment;
                                }
                                $row1["equipments"] = $equipments;
                                
                                $output2 = array();
                                $sql2 = "SELECT u.*, e.equipment_name FROM equipment_usages u LEFT JOIN equipment e ON u.equipment_code=e.equipment_code WHERE u.bmr_no='".$row["bmr_no"]."' AND u.batch_no='".$row["batch_no"]."' AND u.stage='".$row1["stage"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $output2[] = $row2;
                                    }
                                }
                                $row1["equipment_usages"] = $output2;
                            }
                            
                          
                            if ($row1["isweighing"] == 'YES') {
                                $row1["weighings"] = json_decode($row1["weighings"]);
                                
                                $output2 = array();
                                $sql2 = "SELECT * FROM weight_check WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."' AND stage='".$row1["stage"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row2["checkpoints"] = json_decode($row2["checkpoints"]);
                                        $output2[] = $row2;
                                    }
                                }
                                $row1["weighings_checks"] = $output2;
                            }
                            if ($row1["isenvironment"] == 'YES') {
                                $row1["environments"] = json_decode($row1["environments"]);
                                
                                $output2 = array();
                                $sql2 = "SELECT * FROM environment_check WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."' AND stage='".$row1["stage"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row2["checkpoints"] = json_decode($row2["checkpoints"]);
                                        $output2[] = $row2;
                                    }
                                }
                                $row1["environment_checks"] = $output2;
                            }
                            if ($row1["ischeck"] == 'YES') {
                                $row1["checks"] = json_decode($row1["checks"]);
                                
                                $output2 = array();
                                $sql2 = "SELECT * FROM inprocess_check WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."' AND stage='".$row1["stage"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row2["checkpoints"] = json_decode($row2["checkpoints"]);
                                        $output2[] = $row2;
                                    }
                                }
                                $row1["inprocess_checks"] = $output2;
                            }
                            if ($row1["isclerance"] == 'YES') {
                                $row1["clearances"] = json_decode($row1["clearances"]);
                                
                                $sql2 = "SELECT * FROM lineclearance WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."' AND stage='".$row1["stage"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row1["clearance_no"] = $row2["clearance_no"];
                                        $row1["clearance_status"] = $row2["status"];
                                        $row1["product_clearance_by"] = $row2["request_by"];
                                        $row1["qa_clearance_by"] = $row2["entry_by"];
                                        $row1["clearances"] = json_decode($row2["checkpoints"]);
                                        break;
                                    }
                                } else {
                                    $row1["clearance_no"] = "";
                                    $row1["clearance_status"] = '';
                                }
                            }
                            
                            $row1["yields"] = json_decode($row1["yields"]);
                            
                            $sql2 = "SELECT stage FROM spec_tests WHERE stage='".$row1["stage"]."' AND specification_no IN (SELECT specification_no FROM specification WHERE product_code='".$row["product_code"]."' AND spec_type='Inprocess Specification')";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                $row1["isInprocessSpec"] = 'YES';
                                 ////INprocess Specification/////////////////
                                $output3 = array();
                                $sql3 = "SELECT t.*, p.product_name, p.grade FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code WHERE t.status='done'";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        $row3["tests"] = json_decode($row3["tests"]);
                                        $output3[] = $row3;
                                    }
                                }
                                $row1["technical"] = $output3;
                            } else {
                                $row1["isInprocessSpec"] = 'NO';
                            }
                            
                            $row["stage"] = $row1;
                            break;
                        }
                    }
                }
                
                $output1 = array();
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."' AND status ='pending' AND stage !='".$row["current_stage"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["last_stage"] = $row1["stage"];
                        if ($row1["isprocedure"] == 'YES') {
                            $row1["procedures"] = json_decode($row1["procedures"]);
                        }
                        if ($row1["isinstruction"] == 'YES') {
                            $row1["instructions"] = json_decode($row1["instructions"]);
                        }
                        // if ($row1["isequipment"] == 'YES') {
                        //     $row1["equipments"] = json_decode($row1["equipments"]);
                        // }
                        if ($row1["isequipment"] == 'YES') {
                            $row1["equipments"] = json_decode($row1["equipments"]);
                            $equip = $row1["equipments"];
                            for ($i = 0; $i < count($equip); $i++) {
                                $temp = $equip[$i];
                                $temp->stage = $row1["stage"];
                                $equipments[] = $temp;
                            }
                        }
                        if ($row1["isclerance"] == 'YES') {
                            $row1["clearances"] = json_decode($row1["clearances"]);
                        }
                        if ($row1["isweighing"] == 'YES') {
                            $row1["weighings"] = json_decode($row1["weighings"]);
                        }
                        if ($row1["isenvironment"] == 'YES') {
                            $row1["environments"] = json_decode($row1["environments"]);
                        }
                        if ($row1["ischeck"] == 'YES') {
                            $row1["checks"] = json_decode($row1["checks"]);
                        }
                        $output1[] = $row1;
                    }
                }
                 $row["equipments"] = $equipments;
                $row["pending_stages"] = $output1;
                
                $output2 = array();
                $sql2 = "SELECT u.*, e.equipment_name FROM equipment_usages u LEFT JOIN equipment e ON u.equipment_code=e.equipment_code WHERE u.bmr_no='".$row["bmr_no"]."' AND u.batch_no='".$row["batch_no"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2[] = $row2;
                    }
                }
                $row["equipment_usages"] = $output2;
                
                $output2 = array();
                $sql2 = "SELECT * FROM environment_check WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row2["checkpoints"] = json_decode($row2["checkpoints"]);
                        $output2[] = $row2;
                    }
                }
                $row["environment_checks"] = $output2;
                
                ///Dispesing Array/////
                $output2 = array();
                $sql2 = "SELECT d.*, DATE(d.request_date) as request_date, p.dosage_form, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.document_no='".$row["bmr_no"]."' ORDER BY id DESC";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row2["checkpoints"] = json_decode($row2["checkpoints"]);
                        $output3 = array();
                        $sql3 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row2["id"]."'";
                        $result3 = $conn->query($sql3);
                        if ($result3->num_rows > 0) {
                            while ($row3 = $result3->fetch_assoc()) {
                                $row3["containers"] = json_decode($row3["containers"]);
                                $row3["ars"] = json_decode($row3["ars"]);
                                $output3[] = $row3;
                            }
                        }
                        $row2["materials"] = $output3;
                        $output2= $row2;
                    }
                }
              
                $row["dispensing"] = $output2;
                
              $output2 = array();
              $sql2 = "SELECT t.*, p.product_name, p.grade FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code WHERE t.status='done'";
              $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row2["tests"] = json_decode($row2["tests"]);
                            $output2[] = $row2;
                            }
                        }
             $row["inprocessSpecification"] = $output2;              
                
                
                
                $output[] = $row;
                
                /*$flag = 0;
                $output1 = Array();
                $sql1 = "SELECT *, DATE(start_date) as start_date, TIME(start_date) as start_time FROM bmr_stages WHERE bmr_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["stage"] == "DISPENSING" && $row1["status"] == 'receive') {
                        } else {
                            if ($flag == 0 && ($row1["status"] !== 'complete')) {
                                $row["current_stage"] = $row1["stage"];
                                $row["stage_no"] = $row1["stage_no"];
                                $flag = 1;
                            }
                             $row1["clearances"] = json_decode($row1["clearances"]);
                             $row1["equipments"] = json_decode($row1["equipments"]);
                             $row1["instructions"] = json_decode($row1["instructions"]);
                            $output1[] = $row1;
                        }
                     
                    }
                }
                if ($row["current_stage"] !== "DISPENSING") {
                    $row["stages"] = $output1;
                    $output[] = $row;
                }*/
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveStageDetails") {
        $sql = "UPDATE bmr_stages SET details='".json_encode($input)."', complete_date='$entry_date', status='complete', yield_qty='".$_GET["yield_qty"]."', yield_per='".$_GET["yield_per"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            $sql = "SELECT * FROM bmr_stages WHERE bmr_no='".$_GET["bmr_no"]."' AND status !='complete'";
            $result = $conn->query($sql);
            if ($result->num_rows == 0) {
                
                $yield_qty = 0;
                $yield_per = 0;
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."' isweighing='true' ORDER BY id DESC LIMIT 1";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $yield_qty = $row1["yeild_qty"];
                        $yield_per = $row1["yeild_per"];
                    }
                } else {
                    $yield_qty = $row["batch_size"];
                    $yield_per = 100;
                }
                
                $sql = "UPDATE bmr SET status='active', complete_by='".$_GET["emp_id"]."', complete_date='$entry_date', yield_qty='$yield_qty', yield_per='$yield_per' WHERE id='".$_GET["bmr_no"]."'";
                $conn->query($sql);
            }
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getStageInprocessCheckes") {
        $sql = "SELECT * FROM bmr_stages WHERE status='start' AND ischeck='yes' AND id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo $row["checks"];
            }
        } else {
            echo "[]";
        }
    } else if ($_GET["type"] == "saveInprocessCheck") {
        $sql = "SELECT * FROM bmr_stages WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $checks = json_decode($row["checks"]);
                
                $checks[count($checks)] = $input;
                
                $sql = "UPDATE bmr_stages SET checks='".json_encode($checks)."' WHERE id='".$_GET["id"]."'";
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
            }
        }
    } else if ($_GET["type"] == "getActiveBatches") {
        $output = array();
        $sql = "SELECT b.*, DATE(b.start_date) as start_date, DATE(b.complete_date) as complete_date, p.product_name, p.grade, p.generic_name FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='active'";
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
                
                $output1 = array();
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["details"] = json_decode($row1["details"]);
                        $row1["instructions"] = json_decode($row1["instructions"]);
                        $row1["clearances"] = json_decode($row1["clearances"]);
                        
                        if ($row1["isclearance"] == "inprocess") {
                            $sql2 = "SELECT * FROM lineclearance WHERE status='active' AND id='".$row1["clearance_no"]."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $row1["isclearance"] = "active";
                                    $row1["clearances"] = json_decode($row2["checkpoints"]);
                                    $row1["clearance_request_by"] = $row2["request_by"];
                                    $row1["clearance_request_date"] = $row2["request_date"];
                                    $row1["clearance_by"] = $row2["entry_by"];
                                    $row1["clearance_date"] = $row2["entry_date"];
                                }
                            }
                        }
                        
                        $output1[] = $row1;
                    }
                }
                $row["stages"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "checkBatch") {
        $sql = "UPDATE bmr SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getCheckedBatches") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name 
                FROM bmr b 
                LEFT JOIN product p ON b.product_code = p.product_code 
                WHERE b.status = 'checked' 
                GROUP BY b.id, p.product_name, p.grade, p.generic_name
";
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
                
                $output1 = array();
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["details"] = json_decode($row1["details"]);
                        $row1["instructions"] = json_decode($row1["instructions"]);
                        $row1["clearances"] = json_decode($row1["clearances"]);
                        
                        if ($row1["isclearance"] == "inprocess") {
                            $sql2 = "SELECT * FROM lineclearance WHERE status='active' AND id='".$row1["clearance_no"]."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $row1["isclearance"] = "active";
                                    $row1["clearances"] = json_decode($row2["checkpoints"]);
                                    $row1["clearance_request_by"] = $row2["request_by"];
                                    $row1["clearance_request_date"] = $row2["request_date"];
                                    $row1["clearance_by"] = $row2["entry_by"];
                                    $row1["clearance_date"] = $row2["entry_date"];
                                }
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["stages"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "approveBatch") {
        $sql = "UPDATE bmr SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }


}

$conn->close();
?>