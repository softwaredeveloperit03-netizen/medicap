<?php 
require '../db.php';
require '../token.php';
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
                $sql1 = "";
                if ($_GET["dosage_form"] == "TABLET") {
                    $sql1 = "SELECT * FROM mfr WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row["product_code"]."' AND process_type='".$_GET["process_type"]."'";
                } else {
                    $sql1 = "SELECT * FROM mfr WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row["product_code"]."'";
                }
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["raw_materials"] = json_encode($row["raw_materials"]);
                        $row["additional_materials"] = json_encode($row["additional_materials"]);
                        $row["packing_materials"] = json_encode($row["packing_materials"]);
                        $row["equipments"] = json_encode($row["equipments"]);
                        $output1[] = $row1;
                    }
                }
                $row["mfrs"] = $output1;
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
                        if ($conn->query($sql1)) {
                            echo "{\"status\":\"success\"}";
                        } else {
                            echo "{\"status\":\"".$conn->error."\"}";
                        }
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
        }
    } else if ($_GET["type"] == "getMFRs") {
        $output = array();
        $sql = "SELECT m.*, p.product_name, p.grade, p.dosage_form, p.generic_name, p.shelf_life, p.label_claim FROM mfr m LEFT JOIN product p ON m.product_code=p.product_code WHERE m.user_no='".$_GET["user_no"]."' AND m.status='active'";
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
    }

}

$conn->close();
?>