<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
 $currentUrl =$_GET["description"];



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
   $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "getProductsByDosage") {
        $output = array();
        $sql = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND status='approve' AND dosage_form='".$_GET["dosage_form"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM manufacturing_process WHERE dosage_form='".$row["dosage_form"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["stages"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveMaster") {
        $sql = "INSERT INTO mfr (user_no, dosage_form, process_type, product_code, average_wt, unit, color, description, packing_type, status, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$input["dosage_form"]."', '".$input["process_type"]."', '".$input["product_code"]."', '".$input["average_wt"]."', '".$input["unit"]."', '".$input["color"]."', '".$input["description"]."', '".$input["packing_type"]."', 'pending', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getInprocessMFRs") {
        $output = array();
        $sql = "SELECT m.*, p.product_name, p.grade, p.dosage_form, p.generic_name, p.shelf_life, p.label_claim FROM mfr m LEFT JOIN product p ON m.product_code=p.product_code WHERE m.user_no='".$_GET["user_no"]."' AND m.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM specification WHERE user_no='".$_GET["user_no"]."' AND spec_type LIKE 'Finish Product%' AND product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $row["isspecification"] = 'done';
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql2 = "SELECT * FROM spec_tests WHERE specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                if ($row2["limit_type"] == "Limits") {
                                    $row2["limit"] = $row2["lower_limit"]." to ".$row2["upper_limit"];
                                } else if ($row2["limit_type"] == "LessThan") {
                                    $row2["limit"] = "NMT ".$row2["lessthan"];
                                } else if ($row2["limit_type"] == "MoreThan") {
                                    $row2["limit"] = "NLT ".$row2["morethan"];
                                } else if ($row2["limit_type"] == "Complies") {
                                    $row2["limit"] = "Complies";
                                }
                                $output2[] = $row2;
                            }
                        }
                        $row1['tests'] = $output2;
                        
                        $output2 = Array();
                        $sql2 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result1->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1['revisions'] = $output2;
                        $row["specification"] = $row1;
                    }
                } else {
                    $row["isspecification"] = 'pending';
                }
                
                $sql1 = "SELECT * FROM specification WHERE user_no='".$_GET["user_no"]."' AND spec_type LIKE 'Inprocess%' AND product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $row["isinprocess"] = 'done';
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql2 = "SELECT * FROM spec_tests WHERE specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                if ($row2["limit_type"] == "Limits") {
                                    $row2["limit"] = $row2["lower_limit"]." to ".$row2["upper_limit"];
                                } else if ($row2["limit_type"] == "LessThan") {
                                    $row2["limit"] = "NMT ".$row2["lessthan"];
                                } else if ($row2["limit_type"] == "MoreThan") {
                                    $row2["limit"] = "NLT ".$row2["morethan"];
                                } else if ($row2["limit_type"] == "Complies") {
                                    $row2["limit"] = "Complies";
                                }
                                $output2[] = $row2;
                            }
                        }
                        $row1['tests'] = $output2;
                        
                        $output2 = Array();
                        $sql2 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result1->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1['revisions'] = $output2;
                        $row["specification1"] = $row1;
                    }
                } else {
                    $row["isinprocess"] = 'pending';
                }
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                $row["additional_materials"] = json_decode($row["additional_materials"]);
                $row["packing_materials"] = json_decode($row["packing_materials"]);
                $row["equipments"] = json_decode($row["equipments"]);
                
                if ($row["isbatch"] == "done") {
                    $row["batch_materials"] = json_decode($row["batch_materials"]);
                }
                
                if ($row["isstages"] == "pending") {
                    $output1 = array();
                    $sql1 = "SELECT * FROM manufacturing_process WHERE dosage_form='".$row["dosage_form"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            if ($row1["step"] == "") {
                                $row1["process_stage"] = $row1["stage"];
                            } else {
                                $row1["process_stage"] = $row1["stage"]." - ".$row1["step"];
                            }
                            $output1[] = $row1;
                        }
                    }
                    $row["stages"] = $output1;
                } else {
                    $row["stages"] = json_decode($row["stages"]);
                    $row["steps"] = json_decode($row["steps"]);
                    
                    $stages = $row["steps"];
                    for ($i = 0; $i < count($stages); $i++) {
                        $stage = $stages[$i];
                        
                        $temp = array();
                        $equipments = $row["equipments"];
                        for ($j = 0; $j < count($equipments); $j++) {
                            $equipment = $equipments[$j];
                            if ($stage->process_stage == $equipment->process_stage) {
                                $temp[] = $equipment;
                            }
                        }
                        $stage->equipments = $temp;
                        
                        $temp = array();
                        $raw_materials = $row["raw_materials"];
                        for ($j = 0; $j < count($raw_materials); $j++) {
                            $equipment = $raw_materials[$j];
                            if ($stage->process_stage == $equipment->process_stage) {
                                $temp[] = $equipment;
                            }
                        }
                        $packing_materials = $row["packing_materials"];
                        for ($j = 0; $j < count($packing_materials); $j++) {
                            $equipment = $packing_materials[$j];
                            if ($stage->process_stage == $equipment->process_stage) {
                                $temp[] = $equipment;
                            }
                        }
                        $stage->materials = $temp;
                        
                        $stages[$i] = $stage;
                    }
                    $row["steps"] = $stages;
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveFinishSpec") {
        $id = 0;
    	$sql = "SELECT MAX(id) as id FROM specification";
    	$result = $conn->query($sql);
    	if ($result->num_rows > 0) {
    	    while ($row = $result->fetch_assoc()) {
    	        $id = $row["id"];
    	    }
    	}
    	$id++;
    	$spec_no = "FP-0".$id;
    	
    	$sql = "INSERT INTO specification (spec_type, product_code, specification_no, version_no, supersede_no, sample_qty, shelf_life, storage, safety_precaution, entry_by, entry_date, unit, review_date, retest_period) VALUES ('Finish Product','".$input["product_code"]."','".$spec_no."','".$input["version_no"]."','".$input["supersede_no"]."','".$input["sample_qty"]."','".$input["shelf_life"]."','".$input["storage"]."','".$input["safety_precaution"]."','".$_GET["emp_id"]."','".$entry_date."','".$input["unit"]."', '".$input["next_review_date"]."', '".$input["retest_period"]."')";
    	if($conn->query($sql)===TRUE){
    		$experience_company = $input["tests"];
    		$len = count($experience_company);
    		for($i = 0; $i<$len; $i++) {
    			$data = $experience_company[$i];
    			if($data["limit"] == 'Limits'){
    				$data["lessthan"] = ''; 
    				$data["morethan"] = ''; 
    			}else if($data["limit"] == 'LessThan'){
    				$data["lower_limit"] = ''; 
    				$data["upper_limit"] = ''; 
    				$data["morethan"] = ''; 
    			}else if($data["limit"] == 'MoreThan'){
    				$data["lower_limit"] = ''; 
    				$data["upper_limit"] = ''; 
    				$data["lessthan"] = ''; 
    			}else if($data["limit"] == 'Compliances'){
    				$data["lower_limit"] = ''; 
    				$data["upper_limit"] = ''; 
    				$data["lessthan"] = '';
    				$data["morethan"] = '';  
    			}
    			if ($data['retest_applicable'] == true) {
    			    $data['retest_applicable'] = 'yes';
    			} else {
    			    $data['retest_applicable'] = 'no';
    			}
    			$sql="INSERT INTO spec_tests (specification_no, test, subtest,description,reference_type,limit_type,lower_limit,upper_limit,lessthan,morethan, unit, sample_qty, retest) VALUES ('".$spec_no."','".$data["test"]."','".$data["subtest"]."','".$data["descr"]."','".$data["ref_type"]."','".$data["limit"]."','".$data["lower_limit"]."','".$data["upper_limit"]."','".$data["lessthan"]."','".$data["morethan"]."', '".$data["unit"]."', '".$data["sample_qty"]."', '".$data["retest_applicable"]."')";
    			$conn->query($sql);
    	    } 
    	    $len = count($input["revisions"]);
    	    $revisionHistory = $input["revisions"];
    	    for ($i =0; $i < $len; $i++) {
    	        $data = $revisionHistory[$i];
    	        $sql = "INSERT INTO spec_revision (spec_no, specification_no, version_no, change_mode, reason, effective_date) VALUES ('".$spec_no."','".$data["spec_no"]."','".$data["ver_no"]."','".$data["change_mode"]."','".$data["change_reason"]."', '".$data["effective_date"]."')";
    	        $conn->query($sql);
    	    }
    		echo "{\"status\":\"success\"}";
    	}
    	else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } else if ($_GET["type"] == "saveRawUnitFormula") {
        $sql = "UPDATE mfr SET raw_materials='".json_encode($input["raw_materials"])."', additional_materials='".json_encode($input["additional_materials"])."', israw='done' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "savePackingUnitFormula") {
        $sql = "UPDATE mfr SET packing_materials='".json_encode($input)."', ispacking='done' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getEquipments") {
        $output = array();
        $sql = "SELECT equipment_name, MIN(min_capacity) as min_capacity, MAX(capacity) as max_capacity, make FROM equipment WHERE user_no='".$_GET["user_no"]."' AND status='approve' AND department='Production' GROUP BY equipment_name, make";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveEquipment") {
        $sql = "UPDATE mfr SET equipments='".json_encode($input)."', isequipment='done' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveStage") {
        $temp = array();
        
        for ($i = 0; $i < count($input); $i++) {
            $data = $input[$i];
            if ($data["status1"] == true) {
                $data["stauts"] = "pending";
                $temp[] = $data;
            }
        }
        /*for ($i = 0; $i < count($input); $i++) {
            $data = $input[$i];
            if ($data["isinstruction"] == true) {
                $data["isinstruction"] = 'yes';
            } else {
                $data["isinstruction"] = 'no';
            }
            if ($data["isclearance"] == true) {
                $data["isclearance"] = 'yes';
            } else {
                $data["isclearance"] = 'no';
            }
            if ($data["isprocedure"] == true) {
                $data["isprocedure"] = 'yes';
            } else {
                $data["isprocedure"] = 'no';
            }
            if ($data["isweighing"] == true) {
                $data["isweighing"] = 'yes';
            } else {
                $data["isweighing"] = 'no';
            }
            if ($data["ischecks"] == true) {
                $data["ischecks"] = 'yes';
            } else {
                $data["ischecks"] = 'no';
            }
            if ($data["istesting"] == true) {
                $data["istesting"] = 'yes';
            } else {
                $data["istesting"] = 'no';
            }
            if ($data["isenviornmental"] == true) {
                $data["isenviornmental"] = 'yes';
            } else {
                $data["isenviornmental"] = 'no';
            }
            if ($data["isreconciliation"] == true) {
                $data["isreconciliation"] = 'yes';
            } else {
                $data["isreconciliation"] = 'no';
            }
            $temp[] = $data;
        }*/
        $sql = "UPDATE mfr SET stages='".json_encode($temp)."', isstages='done' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveStep") {
        $temp = array();
        
        $dispensing = array();
        $dispensing["process_stage"] = "DISPENSING";
        $dispensing["stage"] = "DISPENSING";
        $dispensing["step"] = "";
        $dispensing["isinstruction"] = "yes";
        $dispensing["isclearance"] = "yes";
        $dispensing["isprocedure"] = "no";
        $dispensing["isweighing"] = "yes";
        $dispensing["ischecks"] = "no";
        $dispensing["iscalculation"] = "no";
        $dispensing["isenviornmental"] = "no";
        $dispensing["isreconciliation"] = "no";
        $temp[] = $dispensing;
        
        for ($i = 0; $i < count($input); $i++) {
            $data = $input[$i];
            if ($data["isinstruction"] == true) {
                $data["isinstruction"] = 'yes';
            } else {
                $data["isinstruction"] = 'no';
            }
            if ($data["isclearance"] == true) {
                $data["isclearance"] = 'yes';
            } else {
                $data["isclearance"] = 'no';
            }
            if ($data["isenviornmental"] == true) {
                $data["isenviornmental"] = 'yes';
            } else {
                $data["isenviornmental"] = 'no';
            }
            if ($data["isprocedure"] == true) {
                $data["isprocedure"] = 'yes';
            } else {
                $data["isprocedure"] = 'no';
            }
            if ($data["iscalculation"] == true) {
                $data["iscalculation"] = 'yes';
            } else {
                $data["iscalculation"] = 'no';
            }
            if ($data["ischecks"] == true) {
                $data["ischecks"] = 'yes';
            } else {
                $data["ischecks"] = 'no';
            }
            if ($data["isweighing"] == true) {
                $data["isweighing"] = 'yes';
            } else {
                $data["isweighing"] = 'no';
            }
            if ($data["isreconciliation"] == true) {
                $data["isreconciliation"] = 'yes';
            } else {
                $data["isreconciliation"] = 'no';
            }
            $temp[] = $data;
        }
        $sql = "UPDATE mfr SET steps='".json_encode($temp)."', isstep='done' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveStandardBatchSize") {
        $sql = "UPDATE mfr SET batch_size='".$_GET["batch_size"]."', lots='".$_GET["lots"]."', raw_materials='".json_encode($input['raw_materials'])."', additional_materials='".json_encode($input['additional_materials'])."', packing_materials='".json_encode($input['packing_materials'])."', isbatch='done' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveStageDetails") {
        $flag = 0;
        $sql = "SELECT * FROM mfr WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $steps = json_decode($row["steps"]);
                for ($i = 0; $i < count($steps); $i++) {
                    $step = $steps[$i];
                    if ($step->process_stage == $input["process_stage"]) {
                        $input["status"] = "done";
                        $step = $input;
                        $steps[$i] = $step;
                        
                        $sql1 = "UPDATE mfr SET steps='".json_encode($steps)."' WHERE id='".$_GET["id"]."'";
                        $conn->query($sql1);
                        break;
                    } else if ($step->status == "pending") {
                        $flag = 1;
                    }
                }
            }
        }
        
        if ($flag == 0) {
            $sql = "UPDATE mfr SET status='active' WHERE id='".$_GET["id"]."'";
            $conn->query($sql);
            
            $sql = "SELECT * FROM mfr WHERE id='".$_GET["id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql = "INSERT INTO batch_formula (user_no, product_code, mfr_no, batch_size, lots, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["product_code"]."', '".$input["mfr_no"]."', '".$input["batch_size"]."', '".$input["lots"]."', '".$_GET["emp_id"]."', '$entry_date')";
                    if ($conn->query($sql)) {
                        $last_id = $conn->insert_id;
                        $materials = $input["materials"];
                        for ($i = 0; $i < count($materials); $i++) {
                            $material = $materials[$i];
                            $sql1 = "INSERT INTO batch_materials (no, material_code, overages, qty, unit, batch_qty, batch_unit, lot_qty, lot_unit, role, process) VALUES ('$last_id', '".$material["material_code"]."', '".$material["overages"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["batch_qty"]."', '".$material["batch_unit"]."', '".$material["lot_qty"]."', '".$material["lot_unit"]."', '".$material["role"]."', '".$material["process"]."')";
                            $conn->query($sql1);
                        }
                        
                        echo "{\"status\":\"success\",\"count\":\"count($materials)\"}";
                    } else {
                        echo "{\"status\":\"".$conn->error."\"}";
                    }
                }
            }
        }
    } else if ($_GET["type"] == "getMFRs") {
        $output = array();
        $sql = "SELECT m.*, DATE(m.entry_date) as entry_date, p.product_name, p.grade, p.dosage_form, p.generic_name, p.shelf_life, p.label_claim FROM mfr m LEFT JOIN product p ON m.product_code=p.product_code WHERE m.user_no='".$_GET["user_no"]."' AND m.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND m.status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                $row["additional_materials"] = json_decode($row["additional_materials"]);
                $row["packing_materials"] = json_decode($row["packing_materials"]);
                $row["equipments"] = json_decode($row["equipments"]);
                
                if ($row["isstages"] == "pending") {
                    $output1 = array();
                    $sql1 = "SELECT * FROM manufacturing_process WHERE dosage_form='".$row["dosage_form"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            if ($row1["step"] == "") {
                                $row1["process_stage"] = $row1["stage"];
                            } else {
                                $row1["process_stage"] = $row1["stage"]." - ".$row1["step"];
                            }
                            $output1[] = $row1;
                        }
                    }
                    $row["stages"] = $output1;
                } else {
                    $row["stages"] = json_decode($row["stages"]);
                    $row["steps"] = json_decode($row["steps"]);
                    
                    $stages = $row["steps"];
                    for ($i = 0; $i < count($stages); $i++) {
                        $stage = $stages[$i];
                        
                        $temp = array();
                        $equipments = $row["equipments"];
                        for ($j = 0; $j < count($equipments); $j++) {
                            $equipment = $equipments[$j];
                            if ($stage->process_stage == $equipment->process_stage) {
                                $temp[] = $equipment;
                            }
                        }
                        $stage->equipments = $temp;
                        
                        $temp = array();
                        $raw_materials = $row["raw_materials"];
                        for ($j = 0; $j < count($raw_materials); $j++) {
                            $equipment = $raw_materials[$j];
                            if ($stage->process_stage == $equipment->process_stage) {
                                $temp[] = $equipment;
                            }
                        }
                        $packing_materials = $row["packing_materials"];
                        for ($j = 0; $j < count($packing_materials); $j++) {
                            $equipment = $packing_materials[$j];
                            if ($stage->process_stage == $equipment->process_stage) {
                                $temp[] = $equipment;
                            }
                        }
                        $stage->materials = $temp;
                        
                        $stages[$i] = $stage;
                    }
                    $row["steps"] = $stages;
                }
                
                $output1 = array();
                $sql1 = "SELECT * FROM batch_formula WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveBatchSize") {
        $sql = "INSERT INTO batch_formula (user_no, product_code, mfr_no, batch_size, lots, entry_by, entry_date, status) VALUES ('".$_GET["user_no"]."','".$input["product_code"]."', '".$input["mfr_no"]."', '".$input["new_batch_size"]."', '".$input["lots"]."', '".$_GET["emp_id"]."', '$entry_date', 'approve')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $last_id = $conn->insert_id;
            $materials = $input["raw_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO batch_materials (no, material_code, overages, qty, unit, batch_qty, batch_unit, lot_qty, lot_unit, role, process) VALUES ('$last_id', '".$material["material_code"]."', '".$material["overages"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["batch_qty"]."', '".$material["batch_unit"]."', '".$material["lot_qty"]."', '".$material["lot_unit"]."', '".$material["role"]."', '".$material["process"]."')";
                $conn->query($sql1);
            }
            $materials = $input["additional_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO batch_materials (no, material_code, overages, qty, unit, batch_qty, batch_unit, lot_qty, lot_unit, role, process) VALUES ('$last_id', '".$material["material_code"]."', '".$material["overages"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["batch_qty"]."', '".$material["batch_unit"]."', '".$material["lot_qty"]."', '".$material["lot_unit"]."', '".$material["role"]."', '".$material["process"]."')";
                $conn->query($sql1);
            }
            
            $materials = $input["packing_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO batch_materials (no, material_code, overages, qty, unit, batch_qty, batch_unit, lot_qty, lot_unit, role, process) VALUES ('$last_id', '".$material["material_code"]."', '".$material["overages"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["batch_qty"]."', '".$material["batch_unit"]."', '".$material["lot_qty"]."', '".$material["lot_unit"]."', '".$material["role"]."', '".$material["process"]."')";
                $conn->query($sql1);
            }
            
            $stages = $input["steps"];
            for ($i = 0; $i < count($stages); $i++) {
                $stage = $stages[$i];
                $j = $i + 1;
                
                $procedures = array();
                if ($stage["isprocedure"] == "yes") {
                    $procedures = $stage["procedures"];
                }
                
                $instructions = array();
                if ($stage["isinstruction"] == "yes") {
                    $instructions = $stage["instructions"];
                }
                
                $clearances = array();
                if ($stage["isclearance"] == "yes") {
                    $clearances = $stage["clearances"];
                }
                
                $clearances = array();
                if ($stage["isclearance"] == "yes") {
                    $clearances = $stage["clearances"];
                }
                
                $checks = array();
                if ($stage["ischecks"] == "yes") {
                    $checks = $stage["checks"];
                }
                $sql1 = "INSERT INTO batch_stages (no, stage_no, stage_for, stage, instructions, isequipment, isclearance, isenviornmental, isinprocess, equipments, clearances, isweighing, istest, procedures, checks) VALUES ('$last_id', '".$j."', '', '".$stage["stage"]."', '".json_encode($instructions)."', '".$stage["isequipment"]."', '".$stage["isclearance"]."', '".$row1["isenviornmental"]."', '".$row1["ischecks"]."', '".$row1["equipments"]."', '".json_encode($clearances)."', '".$row1["isweighing"]."', '".$row1["istest"]."', '".json_encode($procedures)."', '".json_encode($checks)."')";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "downloadMFR") {
        $_GET['filename'] = 'MFR'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style="width:30%;"><b>Label Claim:</b></td>
                    <td style="width:70%;"><b>Each ml contains:</b></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Primary pack description:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Storage Condition:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Product Appearance:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Effective Date:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Issued By (QA) Sign/Date:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Received By (Production) Sign/Date:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Batch Commencement Date:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Batch Completion Date:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                ';
        $html.='</table><div></div>';
        
        $html.='<table border="1" cellpadding="5" style=" font-size:12px;">
                <tr>
                    <td rowspan="2" style="width:20%;"><b></b></td>
                    <td style="width:15%;"><b>Prepared By</b></td>
                    <td style="width:15%;"><b>Checked By</b></td>
                    <td colspan="2" style="width:30%; text-align:center;"><b>Reviewed By</b></td>
                    <td style="width:20%;"><b>Approved By</b></td>
                </tr>
                <tr>
                    <td style="width:15%;"><b>QA</b></td>
                    <td style="width:15%;"><b>Production</b></td>
                    <td style="width:15%;"><b>Production</b></td>
                    <td style="width:15%;"><b>QA</b></td>
                    <td style="width:20%;"><b>Head QA</b></td>
                </tr>
                <tr >
                    <td style="width:20%;"><b>Name</b></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:20%;"><b>Sign & Date</b></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:20%;"></td>
                </tr>';
        $html.='</table><div></div>';
        
        $html.='<h3>STAGE 1.0 GENERAL INSTRUCTION</h3>
                <ul type="square" style="font-size:12px;">
                    <li>Do not alter or over write letters and numbers.</li>
                    <li>All the entries should be correct and legible.</li>
                    <li>Do not use staples/paper clip in packing material.</li>
                    <li>Follow GDP practices in case of wrong entry, cut single line on entry error and write the correct data. Write the entry error remark along with signature and date.</li>
                    <li>“Checked by” or “Reviewed by” cannot be signed prior to the “Done by/performed by”.</li>
                    <li>Check the availability of packing materials of specified batch before packing process.</li>
                    <li>Before starting the packing activity check the cleanliness of areas and equipment’s as per the current version SOP’s practices.</li>
                    <li>Line clearance shall be performed by QA before operation of the each & every stage as mentioned in BPR.</li>
                    <li>Follow Good Documentation Practice (GDP) during execution of BPR</li>
                    <li>Do not keep blank page, Strike the blank space & Put “NA” acknowledge with signature /Date. </li>
                    <li>Record all data by using blue ball pen for production person & green ball pen for IPQA person.</li>
                    <li>Record time as HH:MM format or HH:MM:SS in 24 hours format.</li>
                    <li>Record Date in DD/MM/YYYY or DD/MM/YY or DD-MM-YYYY or DD-MM-YY format. Do not leave any column in document unfilled. If any column in a document is not applicable, write ‘Not Applicable’ (NA) along with sign & date. If any column used for recording quantity write the number, if the quantity is zero then write the number “00”.</li>
                    <li>Encircle the correct choice, if choice is given.</li>
                    <li>Personnel signing the document shall put the ‘Date’ along with the signature and remark for better clarity. </li>
                    <li>Record discrepancies and deviation in defined summary place.</li>
                    <li>All operation must be performed in accordance with current Good Manufacturing Practices.</li>
                    <li>Any deviation observed during batch processing should be informed to Production Head, QA Head and duly recorded.</li>
                    <li>Machine breakdown pertaining to packing equipment’s during processing assessed for its impact on product quality by Production Head & to be logged under deviation, if required.</li>
                    <li>Record the details of following activities along with the date & time in BPR.<br> &nbsp;&nbsp;a) Trial taken b) Unusual observation c) If any correction done.</li>
                    <li>Quality Control Department must approve all packing materials before dispensing.</li>
                    <li>Equipment’s to be suitably labeled indicating the current status with date.</li>
                    <li>In- process control must be strictly followed and ensured the data must be recorded at regular interval in the batch packing record.</li>
                    <li>All entries should be legible, correct and signatures are with their corresponding dates.</li>
                    <li>After Completion of BMR, Reviewed by the concerned HOD & Submitted to QA.</li>
                   
                </ul>
                <div></div>';
                
        $html.='<h3>STAGE: 2.0 DISPENSING OF PACKING MATERIAL</h3>
                <span style="font-size:12px;"> &nbsp;&nbsp;2.1 Take line clearance of packing material dispensing area as per SOP No. BPL/GEN/QAI/004.</span><br>
                <div></div>
                <table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style=" width:100%; text-align:center;"><b>Table Number : 2.1</b></td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:10%; text-align:center;"><b>Sr. No.</b></td>
                    <td rowspan="2" style="width:50%; text-align:center;"><b>Checks Points</b></td>
                    <td style="width:20%;"><b>Date:</b></td>
                    <td style="width:20%;"><b>Time:</b></td>
                </tr>
                <tr>
                    <td style="width:20%;"><b>Date:</b></td>
                    <td style="width:20%;"><b>Time:</b></td>
                </tr>
                <tr>
                    <td style="width:10%;">01</td>
                    <td style="width:50%;">Previous Product Name</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%;">02</td>
                    <td style="width:50%;">Previous Product Batch .No.</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%;">03</td>
                    <td style="width:50%;">Record the temperature of dispensing areaTemperature (NMT 27°C)  </td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%"><b>Sr. No.</b></td>
                    <td style="width:50%"><b>Checks Points: YES / NO</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                </tr>
                <tr>
                    <td style="width:10%">01</td>
                    <td style="width:50%">Ensure the materials of previous products removed from area.</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td style="width:10%">02</td>
                    <td style="width:50%">Check the QC approve label of Packing material to be dispense.</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:10%;"></td>
                    <td style="width:50%;"><b>Checked By (Store)</b></td>
                    <td colspan="2" style="width:20%;"></td>
                    <td colspan="2"style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Verified By (QA)</b></td>
                    <td colspan="2" style="width:20%;"></td>
                    <td colspan="2" style="width:20%;"></td>
                </tr>
                </table>
                <div></div>
                <span style="font-size:12px;">&nbsp;&nbsp;Note: Physically check the packaging materials code, A.R. No, Quantity as per the dispensing slips and attach the dispensed labels to BMR. </span>
                <div></div>
                <span style="font-size:12px;">&nbsp;&nbsp;Attached By Prod. Sign & Date _____________________. </span>
                <div></div>
                <div></div>
                <div></div>
                ';
                
        $html.='<h2>STAGE: 3.0 DISPENSING AND VERIFICATION OF PACKAGING MATERIALS </h2>
                <h3>&nbsp;&nbsp;3.1	Packaging Material Details:</h3>
                <div></div>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 3.1</b></td>
                </tr>
                <tr>
                    <td style="width:5%;"><b>Sr.No</b></td>
                    <td style="width:10%;"><b>Item Code</b></td>
                    <td style="width:10%;"><b>Packing Material</b></td>
                    <td style="width:5%;"><b>Spec</b></td>
                    <td style="width:5%;"><b>Unit</b></td>
                    <td style="width:10%;"><b>Standard  Batch Size-120 Lit.</b></td>
                    <td style="width:5%;"><b>OA %</b></td>
                    <td style="width:10%;"><b>Actual qty. per batch including % O.A</b></td>
                    <td style="width:10%;"><b>Qty. received from store</b></td>
                    <td style="width:5%;"><b>A.R. NO</b></td>
                    <td style="width:9%;"><b>Issued by(Store)</b></td>
                    <td style="width:8%;"><b>Checked by (Prod.)</b></td>
                    <td style="width:8%;"><b>Verify by (QA)</b></td>
                </tr>
                <tr>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:9%;"><b></b></td>
                    <td style="width:8%;"><b></b></td>
                    <td style="width:8%;"><b></b></td>
                </tr>
                </table>
                ';
        
        $html.='<h2>3.2	Dispensing of Additional Packing Materials:</h2>
                <span style="font-size:12px;"> &nbsp;&nbsp;Dispense additional packaging materials required as per current version of SOP No: SPK/OP/04.  & enter the details in following table.</span><br>
                <div></div>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 3.2</b></td>
                </tr>
                <tr >
                    <td style="width:5%;"><b>Sr.No</b></td>
                    <td style="width:10%;"><b>Item Code</b></td>
                    <td style="width:10%;"><b>Packing Material Name</b></td>
                    <td style="width:10%;"><b>Spec</b></td>
                    <td style="width:5%;"><b>Unit.( Nos.)</b></td>
                    <td style="width:10%;"><b>Req. Additional qty.</b></td>
                    <td style="width:10%;"><b>Qty. Issued By store</b></td>
                    <td style="width:10%;"><b>A.R. NO</b></td>
                    <td style="width:10%;"><b>Issued by(Store)</b></td>
                    <td style="width:10%;"><b>Checked by (Prod.)</b></td>
                    <td style="width:10%;"><b>Verify by (QA)</b></td>
                </tr>
                <tr>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                </tr>
                </table>
                <div></div>
                ';
                
        $html.='<h2>STAGE: 4.0  PACK STYLE PHOTO VIEW:</h2>
                    <div></div>
                    <div></div>
                    <div></div>';
                    
        $html.='<h2>4.1 PACKING PROCEDURE:</h2>
                <table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style=" width:100%; text-align:center;";><b>Table Number : 4.1</b></td>
                </tr>
                 <tr>
                    <td style="width:10%;"><b>Sr.No</b></td>
                    <td style="width:90%;"><b>Packing profile                             ( Pack style : 20x10x10x2ml )</b></td>
                </tr>
                <tr>
                    <td style="width:10%;">1.</td>
                    <td style="width:90%;">Affix overprinted label on each filled ampoule.</td>
                </tr>
                <tr>
                    <td style="width:10%;">2.</td>
                    <td style="width:90%;">Pack such 10 proper labelled ampoules in a transparent PVC tray. </td>
                </tr>
                <tr>
                    <td style="width:10%;">3.</td>
                    <td style="width:90%;">Check the overprinting of carton specimen details. Pack one filled ampoule tray with one leaflet in a carton and close it properly.</td>
                </tr>
                <tr>
                    <td style="width:10%;">4.</td>
                    <td style="width:90%;">Pack 10 filled cartons in a shrink sleeve and Pass through the hot tunnel.</td>
                </tr>
                <tr>
                    <td style="width:10%;">5.</td>
                    <td style="width:90%;">Pack the 20 nos. of such shrink sleeves cartons in to a shipper and check the shipper weight.</td>
                </tr>
                <tr>
                    <td style="width:10%;">6.</td>
                    <td style="width:90%;">Affix one handle with care label and shipper label on each 5-ply shipper boxes.</td>
                </tr>
                <tr>
                    <td style="width:10%;">7.</td>
                    <td style="width:90%;">Close and seal the 5-ply shipper boxes with the help of BOPP BPL Logo Printed tape.</td>
                </tr>
                <tr>
                    <td style="width:10%;">8.</td>
                    <td style="width:90%;">After seal , strapping the 5-ply shipper boxes with the help of strapping machine.</td>
                </tr>
                <tr>
                    <td style="width:10%;">9.</td>
                    <td style="width:90%;">Numbers the each 5- ply shippers sequence wise. Record the shipper’s weight in BPR log sheet and on that same shipper label.</td>
                </tr>
                
                </table>
                ';
                
        $html.='<h2>STAGE: 5.0 Details of specimen printing and frequency check of Product.</h2>
                <div></div>
                <table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 5.0</b></td>
                </tr>
                <tr>
                    <td style="width:10%; text-align:center;"><b>Sr. No.</b></td>
                    <td style="width:70%; text-align:center;"><b>Checks Points</b></td>
                    <td style="width:20%; text-align:center;"><b>Checked by(Prod.)</b></td>
                </tr>
                <tr>
                    <td style="width:10%; ">1.</td>
                    <td style="width:70%; "><b>Label specimen details:</b><br>
                                            B. No.<br>
                                            Mfg. Date: <br>
                                            Exp. Date:<br>
                                            <b>Frequency:</b> Check and attach the specimen details of label, start of every roll and start & end of the day. In case of label roll A.R.No. Changes attach the specimen detail. The specimen should be sign duly (QA & production officer) during printing activity.
                    </td>
                    <td style="width:20%; "></td>
                </tr>
                <tr>
                    <td style="width:10%; ">2.</td>
                    <td style="width:70%; "><b>Barcode print on carton:</b><br>
                                            GTIN No.<br>
                                            Exp. Date :<br>
                                            B. No.<br>
                                            Serial No. _______________________ to _________________________.<br>
                                            Before start of packing activity generate the barcode label form PD department with reference of requisition slip of product. Print the barcode details on carton after specimen verify by QA.<br>
                    </td>
                    <td style="width:20%; "></td>
                </tr>
                <tr>
                    <td style="width:10%; ">3.</td>
                    <td style="width:70%; "><b>Carton specimen details:</b>
                                            B. No.<br>
                                            Mfg. Date:<br>
                                            Exp. Date:<br>
                                            <b>Frequency:</b> Check and attach the specimen details of carton, start & end of the over printing of the day. In case of change in A.R. No of cartons attach the specimen details of same. The specimen should be sign duly (QA& production officer) during printing activity.

                    </td>
                    <td style="width:20%; "></td>
                </tr>
                <tr>
                    <td style="width:10%; ">4.</td>
                    <td style="width:70%; "><b>Leaflet</b><br>
                                            <b>Frequency:</b> Check the text matter details of leaflet before start & end of the day. In case of change in A.R. No of leaflet attach the specimen details of same to verify any change in text matter, color, folding size etc. The specimen should be sign duly (QA& production officer) before attachment.

                    </td>
                    <td style="width:20%; "></td>
                </tr>
                <tr>
                    <td style="width:10%; ">4.</td>
                    <td style="width:70%; "><b>Shipper label specimen details:</b><br>
                                            Pack profile :<br>
                                            B. No.<br>
                                            Mfg. date:<br>
                                            Exp. Date:<br>
                                            <b>Frequency:</b> At the start of packing activity checks the printed specimen details of shipper label as per the BPR and duly sign on same shipper label both Production and QA officer. Attach the specimen signed shipper label start and end of the batch.

                    </td>
                    <td style="width:20%; "></td>
                </tr>
                </table>
                <div></div>';
                
        $html.='<h3>STAGE 6.0: LABEL OVERPRINTING AND LABELING OPERATION: </h3>
                <span style="font-size:12px;">&nbsp;&nbsp;6.1 Line clearance of Ampoule labeling area.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.1.1 Take line clearance of labeling area as per SOP No.: BPL/GEN/QAI/04</span><br>
                <div></div>
                <table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style=" width:100%; text-align:center;"><b>Table Number : 6.1</b></td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:10%; text-align:center;"><b>Sr. No.</b></td>
                    <td rowspan="2" style="width:30%; text-align:center;"><b>Checks Points</b></td>
                    <td style="width:20%;"><b>Date:</b></td>
                    <td style="width:20%;"><b>Time:</b></td>
                    <td style="width:20%;"><b>Date:</b></td>
                </tr>
                <tr>
                    <td style="width:20%;"><b>Date:</b></td>
                    <td style="width:20%;"><b>Time:</b></td>
                    <td style="width:20%;"><b>Date:</b></td>
                </tr>
                <tr>
                    <td style="width:10%;">01</td>
                    <td style="width:30%;">Previous Product Name</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%;">02</td>
                    <td style="width:30%;">Previous Product Batch .No.</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%;">03</td>
                    <td style="width:30%;">Labeling machine ID No.</td>
                    <td style="width:20%;"><b>ID No.___________</b></td>
                    <td style="width:20%;"><b>ID No.___________</b></td>
                    <td style="width:20%;"><b>ID No.___________</b></td>
                </tr>
                <tr>
                    <td style="width:10%;">04</td>
                    <td style="width:30%;">Record the temperature and relative humidity of labeling area.Temperature NMT 27°C.</td>
                    <td style="width:20%;">______°C</td>
                    <td style="width:20%;">______°C</td>
                    <td style="width:20%;">______°C</td>
                </tr>
                
                <tr>
                    <td style="width:10%"><b>Sr. No.</b></td>
                    <td style="width:30%"><b>Checks Points: YES / NO</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                </tr>
                <tr>
                    <td style="width:10%">01</td>
                    <td style="width:30%">Ensure the area should be absence of previous product materials.</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td style="width:10%">02</td>
                    <td style="width:30%">Check the cleanness of labeling machine and surrounding area</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td style="width:10%">03</td>
                    <td style="width:30%">Ensure the machine changeover is done as per the ampoule size</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td style="width:10%">04</td>
                    <td style="width:30%">Check the cleanness of waste bin</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td style="width:10%">05</td>
                    <td style="width:30%">Update the product details on status board.</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td style="width:10%">06</td>
                    <td style="width:30%">Check the received labels quantity from store as per the requisition slip.</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:10%;"></td>
                    <td style="width:30%;"><b>Checked By (Prod.)</b></td>
                    <td colspan="2" style="width:20%;"></td>
                    <td colspan="2"style="width:20%;"></td>
                    <td colspan="2"style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Verified By (QA)</b></td>
                    <td colspan="2" style="width:20%;"></td>
                    <td colspan="2" style="width:20%;"></td>
                    <td colspan="2" style="width:20%;"></td>
                </tr>
                </table><div></div>';
                
        $html.='<h3>6.2 Sticker label Overprinting & Labeling operation:</h3>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.1 Check the quantity & any damage of sticker label roll before labeling activity.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.2 Check and attach the specimen details of label start of every roll and start & end of the day.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.3 The specimen should be sign duly (QA & production officer) start of printing & labeling activity.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.4During specimen signature verification check the artwork code of label & write the time along with date on same label.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.5 After initial specimen signature sign by QA, start the labeling activity as per SOP No.: BPL/GEN/PAR/070 & 074.</span><br>
                <div></div>
                <h3>6.3	Attach the specimen of overprinted Labels: </h3>
                <span style="font-size:12px;">&nbsp;&nbsp;6.3.1 Labeling start Date &time: ____________________      End Date &Time: ___________________  </span><br>
                <div></div>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 6.2</b></td>
                </tr>
                <tr>
                    <td style="width:100%;"><div></div><div></div><div></div></td>
                </tr>
                </table>
                <div></div>
                <h3>6.3	Attach the specimen of overprinted Labels:</h3>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 6.3</b></td>
                </tr>
                <tr>
                    <td style="width:100%;"><div></div><div></div><div></div></td>
                </tr>
                </table>
                <div></div>
                ';
        $html.='<h3>6.4 In-process checks during label overprinting and labeling: </h3>
                <span style="font-size:12px;">&nbsp;&nbsp;Check 05-10 Ampoules randomly during in-process.<br>(Frequency- Hourly for Production persons and after every two hours for Q.A person’s)
                 </span><br>
                 <div></div>
                 <table border="1" cellpadding="5">
                 <tr>
                    <td style="width:100%; text-align:center;">Table Number : 6.4</td>
                 </tr>
                 <tr>
                    <td style="width:11%;"><b>Date</b></td>
                    <td style="width:11%;"><b>Time</b></td>
                    <td style="width:11%;"><b>Crack / Unclean ampoules</b></td>
                    <td style="width:11%;"><b>Ampoule identification(2ml Clear Glass Ampoule With White C/B Snep Off.)</b></td>
                    <td style="width:11%;"><b>Quality of labels.(Cross, Smudge, folding, Without label, Double labels)</b></td>
                    <td style="width:12%;"><b>Correctness of specimens (B. No., Mfg. Date, Exp. Date)</b></td>
                    <td style="width:11%;"><b>Legible of Overprinting Details and without print labels</b></td>
                    <td style="width:11%;"><b>Checked By(Prod.)</b></td>
                    <td style="width:11%;"><b>Checked By(QA)</b></td>
                 </tr>
                 <tr>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:12%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                 </tr>
                 <tr>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:12%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                 </tr>
                 </table>
                 
                 <span style="width:100%;">“√” mark means nil defects and “×” mark means defects identify during in-process checks and do the needful corrective action and put the remark with proper justification.</span>
                 <div></div>';
                 
        $html.= '<h3>6.5 Reconciliation of ampoules after labelling activity:</h3>
                 <table border="1" cellpadding="5">
                 <tr>
                    <td style="width:100%; text-align:center;">Table Number : 6.5</td>
                 </tr>
                 <tr>
                    <td rowspan="2" style="width:11%;"><b>Date</b></td>
                    <td rowspan="2" style="width:20%;"><b>Inspected good ampoules received from inspection</b></td>
                    <td style="width:20%;"><b>Labelling Started</b></td>
                    <td rowspan="2" style="width:10%;"><b>No.of ampoules labelled</b></td>
                    <td rowspan="2" style="width:20%;"><b>No.of ampoules rejected during labelling</b></td>
                    <td rowspan="2" style="width:9%;"><b>Done By(Operator)</b></td>
                    <td rowspan="2" style="width:10%;"><b>Checked By</b></td>
                 </tr>
                 <tr>
                    <td style="width:10%;"><b>From</b></td>
                    <td style="width:10%;"><b>To</b></td>
                 </tr>
                 <tr>
                    <td style="width:11%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:10%;"></td>
                 </tr>
                 <tr>
                    <td style="width:11%;">Total</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:10%;"></td>
                 </tr>
                 </table>
                 <div></div>
                 <table>
                    <tr>
                        <td style="width:35%; border:none;">Rejection amps. destruction Done By(Prod.)</td>
                        <td style="width:10%; border:none;"></td>
                        <td style="width:15%; border:none;">Checked By.(Prod.): </td>
                        <td style="width:15%; border:none;"></td>
                        <td style="width:15%; border:none;">Verify By (QA):</td>
                        <td style="width:10%; border:none;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%; border:none;">(Sign / Date)</td>
                        <td style="width:15%; border:none;"></td>
                        <td style="width:10%; border:none;">(Sign / Date)</td>
                        <td style="width:10%; border:none;"></td>
                        <td style="width:15%; border:none;">(Sign / Date)</td>
                        <td style="width:10%; border:none;"></td>
                    </tr>
                 </table>
                 <div></div>
                 ';
        $html.='<h3>6.6 Reconciliation of labels after labelling activity:</h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center;"><b>Table Number : 6.6</b></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Date</td>
                        <td style="width:10%;">Received labels quantity from store(A)</td>
                        <td style="width:10%;">Additional labels taken during labelling (B)</td>
                        <td style="width:10%;">Labels used in finished product (C)</td>
                        <td style="width:10%;">Labels used for specimen(D)</td>
                        <td style="width:10%;">Labels reject during Labelling (E)</td>
                        <td style="width:10%;">Excess (without print) labels return to store (F)</td>
                        <td style="width:10%;">Rejection percentage (G) =E ÷ (A + B) – F x 100NMT 3%</td>
                        <td style="width:10%;">Checked By</td>
                        <td style="width:10%;">Verified By (QA)</td>
                    </tr>
                    <tr>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                </table>
                <div></div>
                <table>
                    <tr>
                        <td style="width:100%; border:none;">In case of labels returned, Note the return slip Number:</td>
                    </tr>
                    <tr>
                        <td style="width:100%; border:none;">Return By (Prod.) Sign & Date: _____________</td>
                    </tr>
                </table>
                <div></div>
                ';
                
        $html.='<h3>STAGE: 7.0 CARTON OVERPRINTING OPERATION:</h3>
                &nbsp;&nbsp;<h3>7.1 Line clearance for carton Overprinting area. </h3>
                &nbsp;&nbsp;<h4>7.1.1.	Take line clearance of area as per SOP No.: BPL/GEN/QAI/04.</h4>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center;"><b></b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;"><b>Sr. No</b></td>
                        <td style="width:35%;"><b>Table Number : 7.1</b></td>
                        <td style="width:20%;"><b>Date:__________</b><br><b>Time:___________</b></td>
                        <td style="width:20%;"><b>Date:__________</b><br><b>Time:___________</b></td>
                        <td style="width:20%;"><b>Date:__________</b><br><b>Time:___________</b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Previous Product Name</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">02</td>
                        <td style="width:35%;">Previous Product Batch .No.</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">03</td>
                        <td style="width:35%;">Carton over printing machine ID No.</td>
                        <td style="width:20%;"><b>ID No.___________</b></td>
                        <td style="width:20%;"><b>ID No.___________</b></td>
                        <td style="width:20%;"><b>ID No.___________</b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">04</td>
                        <td style="width:35%;">Record the temperature of  carton overprinting area Temperature. NMT 27°C  </td>
                        <td style="width:20%;">___°C</td>
                        <td style="width:20%;">___°C</td>
                        <td style="width:20%;">___°C</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:5%;"><b>Sr No</b></td>
                        <td style="width:35%;"><b>Checks Points: YES (√) / NO (X)</b></td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Ensure the area should be absence of previous product materials</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">02</td>
                        <td style="width:35%;">Check the cleanness of carton overprinting machine and surrounding area.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Ensure the machine setting is done as per the carton size.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Check the cleanness of waste bin.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Status board of area updated.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Check the received cartons quantity from store as per the requisition slip.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;" rowspan="2"></td>
                        <td style="width:35%;">Checked By (Prod.)</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:35%;">Verified By (QA)</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                </table>
                <h3>7.2 Carton Overprinting operation:</h3>
                <table>
                    <tr>
                        <td style="width:100%; border:none;">7.2.1 Check the quantity & any damage of carton before printing activity.</td>
                    </tr>
                    <tr>
                        <td style="width:100%; border:none;">7.2.2 Check and attach the specimen details of carton start & end of the day and in case of A.R No. change.</td>
                    </tr>
                    <tr>
                        <td style="width:100%; border:none;">7.2.3 The specimen should be sign duly (QA & production officer) before printing activity.</td>
                    </tr>
                    <tr>
                        <td style="width:100%; border:none;">7.2.4 During specimen signature verification check the artwork code of carton & write the time along with date.</td>
                    </tr>
                    <tr>
                        <td style="width:100%; border:none;">7.2.5 After initial specimen signature sign from QA, start the printing activity as per carton overprinting SOP.</td>
                    </tr>
                </table>
                <div></div>';
                
        $html.='<h3>7.3 Attach the specimen of overprinted Cartons. : </h3>
                <table cellpadding="5">
                    <tr>
                        <td style="width:60%; border:none;">7.3.1 Carton overprinting start Date &time: </td>
                        <td style="width:40%; border:none;">End Date &Time: </td>
                    </tr>
                </table>
                <div></div>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center;font-weight:bold;">Table Number : 7.3</td>
                    </tr>
                    <tr>
                        <td style="width:100%; "></td>
                    </tr>
                </table>';
                
         $html.=' <h3>7.4 In-process checks during carton overprinting: </h3>
                <table cellpadding="5">
                    <tr>
                        <td style="100%; font-weight:bold;">7.4.1 Check 05-10 cartons randomly during in-process checks</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">(Frequency- Hourly for Production persons and after every two hours for Q.A persons) </td> 
                    </tr>
                <table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 7.4</td>
                    </tr>
                    <tr>
                        <td style="width:11%; font-weight:bold;">Date</td>
                        <td style="width:14%; font-weight:bold;">Time</td>
                        <td style="width:15%; font-weight:bold;">Correct Art work number of Carton</td>
                        <td style="width:15%; font-weight:bold;">Correctness of specimens(B. No., Mfg. Date, Exp. Date)</td>
                        <td style="width:15%; font-weight:bold;">Legible of Overprinting details and without print cartons.</td>
                        <td style="width:15%; font-weight:bold;">Checked By(Prod.)</td>
                        <td style="width:15%; font-weight:bold;">Checked By(QA)</td>
                    </tr>
                    <tr>
                        <td style="width:11%;"></td>
                        <td style="width:14%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                </table>
                <span style="width:100%">“√” mark means nil defects and “×” mark means defects identify. If any discrepancy observes during in-process checks do the needful corrective action and put the remark with proper justification.</span>
                ';
        
        $html.='<h3>7.5 Reconciliation of Cartons after overprinting: </h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 7.5</td>
                    </tr>
                    <tr>
                        <td style="width:10%; font-weight:bold;">Date</td>
                        <td style="width:10%; font-weight:bold;">Received cartons quantity from store (A)</td>
                        <td style="width:10%; font-weight:bold;">Additional cartons taken during printing (B)</td>
                        <td style="width:10%; font-weight:bold;">Cartons use in batch (C)</td>
                        <td style="width:10%; font-weight:bold;">Cartons use for specimen (D)</td>
                        <td style="width:10%; font-weight:bold;">Cartons reject during overprinting (E)</td>
                        <td style="width:10%; font-weight:bold;">Excess (without print) cartons return to store (F)</td>
                        <td style="width:10%; font-weight:bold;">Rejection percentage (G) =E x100 ÷ (A + B) - F(NMT 2%)</td>
                        <td style="width:10%; font-weight:bold;">Done By </td>
                        <td style="width:10%; font-weight:bold;">Checked By</td>
                    </tr>
                    <tr>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                </table>';
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:70%;">In case cartons returned, Note the return slip Number </td>
                        <td style="width:30%;">& Date:___________</td>
                    </tr>
                    <tr>
                        <td style="width:50%;">Rejection carton destruction Done By (Prod.) </td>
                        <td style="width:25%;">Checked By. (Prod.): </td>
                        <td style="width:25%;">Verify By (QA):_________</td>
                    </tr>
                    <tr>
                        <td style="width:50%;">(Sign / Date)</td>
                        <td style="width:25%;">(Sign / Date)</td>
                        <td style="width:25%;">(Sign / Date)</td>
                    </tr>
                </table>
                <br pagebreak="true"/>';
                
        $html.='<h3>STAGE: 8.0 PACKING OPERATION: </h3>
                <table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.1 Line clearance of packing area:</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.1.1 Take line clearance of packing operation as per Ref. SOP Number: BPL/GEN/QAI/04.</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center;"><b>Table Number : 8.1</b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;"><b>Sr. No</b></td>
                        <td style="width:35%;"><b>Table Number : 7.1</b></td>
                        <td style="width:20%;"><b>Date:__________</b><br><b>Time:___________</b></td>
                        <td style="width:20%;"><b>Date:__________</b><br><b>Time:___________</b></td>
                        <td style="width:20%;"><b>Date:__________</b><br><b>Time:___________</b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Previous Product Name</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">02</td>
                        <td style="width:35%;">Previous Product Batch .No.</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">03</td>
                        <td style="width:35%;">Packing Line ID No.</td>
                        <td style="width:20%;"><b>ID No.___________</b></td>
                        <td style="width:20%;"><b>ID No.___________</b></td>
                        <td style="width:20%;"><b>ID No.___________</b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">04</td>
                        <td style="width:35%;">Record the temperature of  packing area.Temperature NMT 27°C</td>
                        <td style="width:20%;">___°C</td>
                        <td style="width:20%;">___°C</td>
                        <td style="width:20%;">___°C</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:5%;"><b>Sr No</b></td>
                        <td style="width:35%;"><b>Checks Points: YES (√) / NO (X)</b></td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Ensure the removal of previous product material from area. </td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">02</td>
                        <td style="width:35%;">Check the cleanliness of conveyor belt and surrounding area. </td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">03</td>
                        <td style="width:35%;">Check the cleanliness of waste bin</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">04</td>
                        <td style="width:35%;">Status board of area updated.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;" rowspan="2"></td>
                        <td style="width:35%;">Checked By (Prod.)</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:35%;">Verified By (QA)</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                </table>
                <table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.2   Packing process: </td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.2.1 Perform Packing operation as per SOP No. BPL/GEN/PAR/053</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.2.2	Packing started date & time__________________ and end date & time _____________ </td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.2.3	Record the in-process check details as mention in table.</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.2.4	Check the packing activity is carry on as per pack profile instruction in BPR.</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.2.5   Record the person’s name involve in different packing activity in below Table Number. 8.2</td>
                    </tr>
                </table>';
                
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.3 Name of person involve in packing activity:</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; font-weight:bold; text-align:center;">Table Number : 8.2</td>
                    </tr>
                    <tr>
                        <td style="width:40%; font-weight:bold;">Date</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%; font-weight:bold;">Packing activity details</td>
                        <td style="width:60%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Labelled ampoules checking</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Ampoules fill in trays</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Filled tray and leaflet pack in carton.</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Packed cartons weighing on balance</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Packed cartons fill in shrink sleeves</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Filled shrink sleeves packs in shipper.</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Shipper weighing, shipper label sticking and strapping of shippers</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">No. of Shippers packed.</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Checked by<br>(Sign & Date)</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                </table>';
                
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.4 Attach the specimen of leaflet:</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.4.1 Attach the specimen details of leaflet start & end of the day and in case of A.R No. change.</td>
                    </tr>
                </table>
                <table border="1" cellpading="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 8.4</td>
                    </tr>
                    <tr>
                        <td style="width:100%;"></td>
                    </tr>
                </table>
                <span style="width:100%; font-weight:bold;">(Remark: start of packing activity check the art work number as per the BPR BOM page & duly sign on same leaflet both Production and QA officer along with date & time.)</span>
                <br><br>';
    
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.5 Attach the specimen of shipper label:</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.5.1 Attach the specimen signed shipper label start & end of the batch. (Duly sign on same label both Production and QA officer along with date & time.)</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 8.5</td>
                    </tr>
                    <tr>
                        <td style="width:100%;"></td>
                    </tr>
                </table>';
        
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.6 (A) Lower weight and higher weight calculation of packed carton:</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">05 Nos. individual weight of leaflet.</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;"></td>
                        <td style="width:40%;font-weight:bold;">1) _________ gm.</td>
                        <td style="width:50%;font-weight:bold;">Average wt. of ampoule (b) = ________ gm. (a) / 5</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;"></td>
                        <td style="width:40%;font-weight:bold;">2) _________ gm.</td>
                        <td style="width:50%;font-weight:bold;">Half of average wt. (c) = _______ gm. (b) / 2</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;"></td>
                        <td style="width:40%;font-weight:bold;">3) _________ gm.</td>
                        <td style="width:50%;font-weight:bold;">Lower limit = Ave. wt. pack carton - (c) = __________ gm.</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;"></td>
                        <td style="width:40%;font-weight:bold;">4) _________ gm. </td>
                        <td style="width:50%;font-weight:bold;">Higher limit = Ave. wt. pack carton + (c) = __________ gm.</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;"></td>
                        <td style="width:40%;font-weight:bold;">5) _________ gm. </td>
                        <td style="width:50%;font-weight:bold;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;"></td>
                        <td style="width:90%;font-weight:bold;">Total wt. of ampoule (a) = _______ gm.</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.6(B)Lower weight and higher weight calculation of packed shipper:</td>
                    </tr>
                </table>';
                
        $html.='<table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 8.6</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;">Sr. No.</td>
                        <td style="width:15%;font-weight:bold;">Weight of  packed carton</td>
                        <td style="width:15%;font-weight:bold;">Weight By(sign / date)</td>
                        <td style="width:15%;font-weight:bold;">Weight of Empty Shipper</td>
                        <td style="width:15%;font-weight:bold;">Weight By(sign / date)</td>
                        <td style="width:15%;font-weight:bold;">Weight of Filled Shipper</td>
                        <td style="width:15%;font-weight:bold;">Weight By(sign / date)</td>
                    </tr>
                    <tr>
                        <td style="width:10%;">01</td>
                        <td style="width:15%;"></td>
                        <td rowspan="6" style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td rowspan="6" style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td rowspan="6" style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">02</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">03</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">04</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">05</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                     <tr>
                        <td style="width:10%;">Total</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Avg. wt. of pack carton________ gm.</td>
                        <td style="width:30%;">Avg. wt. of empty shipper_______Kg.</td>
                        <td style="width:30%;">Avg. wt.  filled shipper________Kg.</td>
                    </tr>
                    <tr>
                        <td style="width:100%;">Lower Limit of filled Shipper = Average wt. of filled shipper – average weight of one packed carton</td>
                    </tr>
                    <tr>
                        <td style="width:100%;">Upper Limit of filled shipper = Average wt. of filled shipper + average weight of one packed carton.</td>
                    </tr>
                </table>
                ';
                
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;"><b>8.7	In-process checks during packing:</b> Check 05-10 unit packs randomly during in-process checks.(Frequency- Hourly for Production persons and after every two hours for Q.A person’s.) </td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 8.8</td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:10%;">Defect Time</td>
                        <td style="width:30%;">Date</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Time</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:10%;">Product</td>
                        <td style="width:30%;">Identification (Amber Amp. white C/B snap off)</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Seal/cracked of Ampoules</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="3" style="width:10%;">Label</td>
                        <td style="width:30%;">Defective printing / text missing</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Improper sticking / dirty/ folded label</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Wrong / Missing Batch. No., Mfg. Dt. Exp. Dt. /  Correct Art work No.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Amps. Trays</td>
                        <td style="width:30%;"> Dirty / Damaged trays, Less Qty. of Ampoules in tray.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:10%;">Cartons</td>
                        <td style="width:30%;">Defective /Correct Art work No./without print/Printing smudge/weight of pack unit. </td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;"> Text missing (Batch. No., Mfg. Dt. Exp. Dt.)If any additional details specify.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Leaflet</td>
                        <td style="width:30%;">Dirty / Moist / Torn /  Text missing / Improper folding / Correct Art work No.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Shrink sleeves</td>
                        <td style="width:30%;">Dirty /Torn /  Improper folding </td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">5 ply Shipper</td>
                        <td style="width:30%;">Dirty / Moist / Torn / Improper sealing</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Shipper Label</td>
                        <td style="width:30%;">Legible printing (B. No., Mfg., Exp. Date,  If any additional details specify.) / Pack qty. / Address</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="3" style="width:10%;"></td>
                        <td style="width:30%;">Signature of Packing officer</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Signature of QA officer</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Remarks</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                </table>
                <span style="width:100%;"><b>(Remark: In case of online labeling and packing strike out the product and label in-process rows)</b> “√” mark means nil defects and “×” mark means defects identify. If any discrepancy identify during in-process checks do the needful corrective action and put the remark with proper justification.</span><br>
                <br>';
                
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;"><b>8.9    In-process checks during packing:</b> check 05-10 unit packs randomly during in-process checks.
                         (Frequency- Hourly for Production persons and after every two hours for Q.A person’s.)  
                        </td>
                    </tr>
                </table>';
        $html.='<table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 8.9</td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:10%;">Defect Time</td>
                        <td style="width:30%;">Date</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Time</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:10%;">Product</td>
                        <td style="width:30%;">Identification (Amber Amp. white C/B snap off)</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Seal/cracked of Ampoules</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="3" style="width:10%;">Label</td>
                        <td style="width:30%;">Defective printing / text missing</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Improper sticking / dirty/ folded label</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Wrong / Missing Batch. No., Mfg. Dt. Exp. Dt. /  Correct Art work No.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Amps. Trays</td>
                        <td style="width:30%;"> Dirty / Damaged trays, Less Qty. of Ampoules in tray.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:10%;">Cartons</td>
                        <td style="width:30%;">Defective /Correct Art work No./without print/Printing smudge/weight of pack unit. </td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;"> Text missing (Batch. No., Mfg. Dt. Exp. Dt.)If any additional details specify.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Leaflet</td>
                        <td style="width:30%;">Dirty / Moist / Torn /  Text missing / Improper folding / Correct Art work No.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Shrink sleeves</td>
                        <td style="width:30%;">Dirty /Torn /  Improper folding </td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">5 ply Shipper</td>
                        <td style="width:30%;">Dirty / Moist / Torn / Improper sealing</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Shipper Label</td>
                        <td style="width:30%;">Legible printing (B. No., Mfg., Exp. Date,  If any additional details specify.) / Pack qty. / Address</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="3" style="width:10%;"></td>
                        <td style="width:30%;">Signature of Packing officer</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Signature of QA officer</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Remarks</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                </table>
                <span style="width:100%;"><b>(Remark: In case of online labeling and packing strike out the product and label in-process rows)</b> “√” mark means nil defects and “×” mark means defects identify. If any discrepancy identify during in-process checks do the needful corrective action and put the remark with proper justification.</span><br>
                <br pagebreak="true"/>';
                
        $html.='<h3>STAGE: 9.0 Reconciliation of Secondary packing materials.</h3>
                <table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">9.1	After completion of batch packing process calculate the quantity of used packing material as per sop no.  BPL/GEN/PAR/109.</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">9.2	Count the rejection quantity & unused printed packing material and segregate. </td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">9.3	Note down the received quantity, used qty., return qty., rejected qty. in BPR.</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">9.4	Discard the rejected & unused printed material by tear/cut under supervision of Packing and QA officer. </td>
                    </tr>
                </table>
                <div></div>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MFR.pdf', 'I');
    }

}

$conn->close();
?>