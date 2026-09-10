<?php
    require 'db.php';
    require 'token.php';
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

if($_GET["type"]=="getStabilityProducts") {
    $sql = "SELECT * FROM product WHERE status='active'";
    $result = $conn->query($sql);
    $output = Array();
    if($result->num_rows > 0){
	    while($row = $result->fetch_assoc()) {
	        $sql1 = "SELECT * FROM specification WHERE product_code='".$row["product_code"]."'";
	        $result1 = $conn->query($sql1);
	        if ($result1->num_rows > 0) {
	            $row["isspecification"] = true;
	            while ($row1 = $result1->fetch_assoc()) {
	                $row["specification_no"] = $row1["specification_no"];
	                $row["sample_qty"] = $row1["sample_qty"];
	                $row["unit"] = $row1["unit"];
	            }
	        } else {
	            $row["isspecification"] = false;
	        }
		    $output[] = $row;
    	}
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveStabilityStudy") {
    $sql = "INSERT INTO stability_study (product_code, packing, market, batches, batch_type,stability_chember,sample_qty, entry_by, entry_date) VALUES ('".$input["product_code"]."', '".$input["packing"]."', '".$input["market"]."', '".$input["batch"]."', '".$input["batch_type"]."', '".$input["stability_chember"]."', '".$input["sample_qty"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql) === TRUE) {
        $last_id = $conn->insert_id;
        echo "{\"status\":\"success\"}";
        $data = $input["batches"];
        for ($i = 0; $i < count($data); $i++) {
            $temp = $data[$i];
            $sql1 = "INSERT INTO stability_batch (stability_no, batch_no, mfg_date, exp_date, purpose) VALUES ('$last_id', '".$temp["batch_no"]."', '".$temp["mfg_date"]."', '".$temp["exp_date"]."', '".$temp["purpose"]."')";
            $conn->query($sql1);
        }
        
        $data = $input["conditions"];
        for ($i = 0; $i < count($data); $i++) {
            $temp = $data[$i];
            if ($temp["id"] == 1 && $temp["selected"]) {
                $sql1 = "INSERT INTO stability_conditions (stability_no, conditions, interval_0,interval_3,interval_6,interval_9,interval_12,interval_24,interval_36,interval_48) VALUES ('$last_id', '".$temp["condition"]."', 'true', 'true', 'true', 'true', 'true', 'true', 'true', 'true')";
                $conn->query($sql1);
            }
            if ($temp["id"] == 2 && $temp["selected"]) {
                $sql1 = "INSERT INTO stability_conditions (stability_no, conditions, interval_0,interval_3,interval_6,interval_9,interval_12,interval_24,interval_36,interval_48) VALUES ('$last_id', '".$temp["condition"]."', 'true', 'true', 'true', 'false', 'false', 'false', 'false', 'false')";
                $conn->query($sql1);
            }
            if ($temp["id"] == 3 && $temp["selected"]) {
                $sql1 = "INSERT INTO stability_conditions (stability_no, conditions, interval_0,interval_3,interval_6,interval_9,interval_12,interval_24,interval_36,interval_48) VALUES ('$last_id', '".$temp["condition"]."', 'true', 'true', 'true', 'true', 'true', 'false', 'false', 'false')";
                $conn->query($sql1);
            }
            if ($temp["id"] == 4 && $temp["selected"]) {
                $sql1 = "INSERT INTO stability_conditions (stability_no, conditions, interval_0,interval_3,interval_6,interval_9,interval_12,interval_24,interval_36,interval_48) VALUES ('$last_id', '".$temp["condition"]."', 'true', 'true', 'true', 'true', 'true', 'true', 'true', 'false')";
                $conn->query($sql1);
            }
            if ($temp["id"] == 5 && $temp["selected"]) {
                $sql1 = "INSERT INTO stability_conditions (stability_no, conditions, interval_0,interval_3,interval_6,interval_9,interval_12,interval_24,interval_36,interval_48) VALUES ('$last_id', '".$temp["condition"]."', 'true', 'true', 'true', 'true', 'true', 'true', 'true', 'true')";
                $conn->query($sql1);
            }
            if ($temp["id"] == 6 && $temp["selected"]) {
                $sql1 = "INSERT INTO stability_conditions (stability_no, conditions, interval_0,interval_3,interval_6,interval_9,interval_12,interval_24,interval_36,interval_48) VALUES ('$last_id', '".$temp["condition"]."', 'true', 'false', 'false', 'false', 'false', 'false', 'false', 'false')";
                $conn->query($sql1);
            }
            if ($temp["id"] == 7 && $temp["selected"]) {
                $sql1 = "INSERT INTO stability_conditions (stability_no, conditions, interval_0,interval_3,interval_6,interval_9,interval_12,interval_24,interval_36,interval_48) VALUES ('$last_id', '".$temp["condition"]."', 'true', 'false', 'false', 'false', 'false', 'false', 'false', 'false')";
                $conn->query($sql1);
            }
        }
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingStabilities") {
    $output = Array();
    $sql = "SELECT * FROM stability_study WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getStabilityChembers") {
    $output = Array();
    $sql = "SELECT * FROM equipment WHERE category='Stability Chember'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if($_GET["type"]=="addRawSpecification"){
	$input = json_decode(file_get_contents('php://input'),true);
	
	$id = 0;
	$sql = "SELECT MAX(id) as id FROM specification";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
	    while ($row = $result->fetch_assoc()) {
	        $id = $row["id"];
	    }
	}
	$id++;
	$spec_no = "RM-0".$id;
	
	if ($input["specification"] == "Existing") {
	    $spec_no = $input["spec_no"];
	}
	
	$sql = "INSERT INTO specification (spec_type, material_code, chemical_name, specification_no, version_no, supersede_no, sample_qty, shelf_life, storage, safety_precaution, entry_by, entry_date, unit, review_date, retest_period) VALUES ('".$input["specification"]."','".$input["material_code"]."','".$input["chemical_name"]."','".$spec_no."','".$input["version_no"]."','".$input["supersede_no"]."','".$input["sample_qty"]."','".$input["shelf_life"]."','".$input["storage"]."','".$input["safety_precaution"]."','".$_GET["emp_id"]."','".$entry_date."','".$input["unit"]."', '".$input["next_review_date"]."', '".$input["retest_period"]."')";
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
	    $len = count($input["revisionHistory"]);
	    $revisionHistory = $input["revisionHistory"];
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
} else if ($_GET["type"]=="getPendingLineClearance") {
    $sql = "SELECT * FROM lineclearance WHERE status='pending'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_name"] = $row1["material_name"];
                    $row["material_code"] = $row1["material_code"];
                    $row["material_grade"] = $row1["grade"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingOOS") {
    $sql = "SELECT * FROM oos";
} else if ($_GET["type"] == "updateOOS") {
    $sql = "";
} else if ($_GET["type"] == "getDispensingRequests") {
    $output = Array();
    $sql = "SELECT * FROM dispensing WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getDispensingRequestStatus") {
    $output = Array();
    $sql = "SELECT * FROM dispensing";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateDispensingRequest") {
    $c_id = 0;
    $sql = "SELECT MAX(cid) as cid FROM lineclearance";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $c_id = $row["cid"];
        }
    }
    $c_id++;
    $clearance_no = "LC-".$c_id;
	    
    $sql = "UPDATE dispensing SET status='inprocess', clearance_status='inprocess', clearance_no='".$clearance_no."' WHERE id=".$input["id"];
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
        $sql = "INSERT INTO lineclearance (clearance_no, department,section,activity,material_no,grn_no,batch_no,request_by,request_date, cid) VALUES('$clearance_no','".$_GET["department"]."','Dispensing','".$input["material_type"]." Dispensing','".$input["product_code"]."','','".$input["batch_no"]."','".$_GET["emp_id"]."','$entry_date', $c_id)";
        $conn->query($sql);
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if($_GET["type"]=="getLineClearanceRequest"){
	$sql = "SELECT * FROM lineclearance'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $row['checkpoints'] = json_decode($row['checkpoints']);
		    if ($row["section"] == "Sampling") {
		        $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_no"]."'";
    		    $result1 = $conn->query($sql1);
    		    if ($result1->num_rows > 0) {
    		        while ($row1 = $result1->fetch_assoc()) {
    		            $row["material_name"] = $row1["material_name"];
    		            $row["material_grade"] = $row1["grade"];
    		        }
    		    }
		    } else if ($row["section"] == "Dispensing") {
		        $sql1 = "SELECT * FROM product WHERE product_code='".$row["material_no"]."'";
    		    $result1 = $conn->query($sql1);
    		    if ($result1->num_rows > 0) {
    		        while ($row1 = $result1->fetch_assoc()) {
    		            $row["product_name"] = $row1["product_name"];
    		            $row["grade"] = $row1["grade"];
    		        }
    		    }
		    }
		    
		    $sql1 = "SELECT * FROM lineclearance WHERE status='active' AND department='".$row["department"]."' AND section='".$row["section"]."' ORDER BY id DESC";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_no"]."'";
        		    $result2 = $conn->query($sql2);
        		    if ($result2->num_rows > 0) {
        		        while ($row2 = $result2->fetch_assoc()) {
        		            $row["previous_material_name"] = $row2["material_name"];
        		        }
        		    }
        		    $row["previous_grn_no"] = $row1["grn_no"];
        		    $row["previous_batch_no"] = $row1["batch_no"];
        		    $row["previous_lot_no"] = $row1["lot_no"];
        		    $row["previous_cleaned_by"] = $row1["entry_by"];
        		    $row["previous_cleaning_date"] = $row1["entry_date"];
        		    $row["previous_activity_by"] = $row1["request_by"];
        		    $row["previous_activity_date"] = $row1["request_date"];
        		    break;
		        }
		    } else {
		        $row["previous_material_name"] = "-";
		        $row["previous_grn_no"] = "-";
    		    $row["previous_batch_no"] = "-";
    		    $row["previous_lot_no"] = "-";
    		    $row["previous_cleaned_by"] = "-";
    		    $row["previous_cleaning_date"] = "-";
    		    $row["previous_activity_by"] = "-";
    		    $row["previous_activity_date"] = "-";
		    }
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "getInprocessDispensings") {
    $output = Array();
    $sql = "SELECT * FROM dispensing WHERE status='inprocess' AND clearance_status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                    $row["generic_name"] = $row1["generic_name"];
                    $row["label_claim"] = $row1["label_claim"];
                    $row["shelf_life"] = $row1["shelf_life"];
                }
            }
            
            $output1 = Array();
            $sql1 = "SELECT * FROM mf_material WHERE mf_no = (SELECT mfr_no FROM batch_planning WHERE no='".$row["bmr_no"]."')";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $std_qty = $row1["qty"] * $row["batch_size"];
                    if ($row1["unit"] == 'mg') {
                        $row1["std_qty"] = number_format(($std_qty / 1000000), 2);
                        $row1["std_unit"] = "kg";
                    } else {
                        $row1["std_unit"] = $row1["unit"];
                    }
                    
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["material_name"] = $row2["material_name"];
                        }
                    }
                    
                    $std_qty = $row1["std_qty"];
                    $qty = $std_qty;
                    $sql2 = "SELECT SUM(qty) as qty FROM stock_book WHERE material_code='".$row1["material_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $output2 = Array();
                            $row1["available_qty"] = $row2["qty"];
                            if ($qty > $row2["qty"]) {
                                $row1["isShortage"] = "yes";
                                $row1["approved_qty"] = number_format(0, 2);
                                $row1["po_qty"] = number_format(0, 2);
                                $row1["under_test"] = number_format($row2["qty"], 2);
                                $row1["shortage_qty"] = number_format((+$row1["std_qty"] - +$row1["available_qty"]), 2);
                                $row1["status"] = "Not Available";
                                
                                $sql3 = "SELECT * FROM stock_book WHERE material_code='".$row1["material_code"]."'";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        $output2[] = $row3;
                                    }
                                }
                            } else {
                                $row["isShortage"] = "no";
                                $sql3 = "SELECT SUM(qty) as qty FROM stock_book WHERE material_code='".$row1["material_code"]."'";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        if ($qty <= $row3["qty"]) {
                                            $row1["approved_qty"] = number_format($row3["qty"], 2);
                                            $row1["po_qty"] = number_format(0, 2);
                                            $row1["under_test"] = number_format(0, 2);
                                            $row1["status"] = "Available";
                                            $sql4 = "SELECT * FROM stock_book WHERE material_code='".$row1["material_code"]."' AND status ='Approved'";
                                            $result4 = $conn->query($sql4);
                                            if ($result4->num_rows > 0) {
                                                while ($row4 = $result4->fetch_assoc()) {
                                                    // get assay value
                                                    $sql5 = "SELECT result FROM testing_tests WHERE testing_no=(SELECT testing_no FROM testing WHERE ar_no='".$row4["ar_no"]."' AND iscoa='active') AND status='active' AND test='Assay'";
                                                    $result5 = $conn->query($sql5);
                                                    if ($result5->num_rows > 0) {
                                                        while ($row5 = $result5->fetch_assoc()) {
                                                            $row4["assay"] = $row5["result"];
                                                            break;
                                                        }
                                                    } else {
                                                        $row4["assay"] = 1;
                                                    }
                                                    
                                                    $assay_qty = 0;
                                                    if ($row4["qty"] >= $qty) {
                                                        $row4["available_qty"] = $qty + (($qty / 100) * $row4["assay"]);
                                                        $assay_qty = round($qty, 2);
                                                        $row4["available_qty"] = round($row4["available_qty"], 2);
                                                        $qty = 0;
                                                    } else {
                                                        $assay_qty = ($row4["qty"] * $row4["assay"]) / 100;
                                                        $assay_qty = round($assay_qty, 2);
                                                        $qty = $qty - $assay_qty;
                                                        $row4["available_qty"] = $row4["qty"];
                                                    }
                                                    $row4["assay_qty"] = $assay_qty;
                                                    $output2[] = $row4;
                                                    if ($qty == 0) {
                                                        break;
                                                    }
                                                }
                                                if ($qty != 0) {
                                                    $row1["isShortage"] = "yes";
                                                    $row1["available_qty"] = round($std_qty - $qty, 2);
                                                    $row1["shortage_qty"] = number_format((+$row1["std_qty"] - +$row1["available_qty"]), 2);
                                                }
                                            }
                                        } else {
                                            $row1["approved_qty"] = number_format(0, 2);
                                            $row1["po_qty"] = number_format(0, 2);
                                            $row1["under_test"] = number_format($row3["qty"], 2);
                                            $row1["status"] = "Under Test";
                                            $sql4 = "SELECT * FROM stock_book WHERE material_code='".$row1["material_code"]."' AND status NOT IN ('Approved')";
                                            $result4 = $conn->query($sql4);
                                            if ($result4->num_rows > 0) {
                                                while ($row4 = $result4->fetch_assoc()) {
                                                    // get assay value
                                                    $sql5 = "SELECT result FROM testing_tests WHERE testing_no=(SELECT testing_no FROM testing WHERE ar_no='".$row4["ar_no"]."' AND iscoa='yes') AND status='active' AND test='Assay'";
                                                    $result5 = $conn->query($sql5);
                                                    if ($result5->num_rows > 0) {
                                                        while ($row5 = $result5->fetch_assoc()) {
                                                            $row4["assay"] = $row5["result"];
                                                            break;
                                                        }
                                                    } else {
                                                        $row4["assay"] = 0;
                                                    }
                                                    $output2[] = $row4;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            $row1["materials"] = $output2;
                        }
                    }
                    $output1[] = $row1;
                }
            }
            $row["materials"] = $output1;
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getDispensingLog") {
    $output = Array();
    $sql = "SELECT * FROM dispensing WHERE status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["manufactured_type"] = $row1["manufactured_type"];
                    $row["grade"] = $row1["grade"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
    } else if ($_GET["type"] == "getDosageForms") {
    $output = Array();
    $sql = "SELECT * FROM dosage_form";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getProductByDosage") {
    $output = Array();
    $sql = "SELECT * FROM product WHERE dosage_form='".$_GET["dosage_form"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getProductGrade") {
    $output = Array();
    $sql = "SELECT * FROM product WHERE product_name='".$_GET["product_name"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getProductMFR") {
    $output = Array();
    $sql = "SELECT * FROM masterformula WHERE product_code='".$_GET["product_code"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = Array();
            $sql1 = "SELECT * FROM mf_material WHERE mf_no='".$row["mfr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["material_name"] = $row2["material_name"];
                        }
                    }
                    $output1[] = $row1;
                }
            }
            $row["materials"] = $output1;
            
            $output1 = Array();
            $sql1 = "SELECT * FROM mf_equipments WHERE mf_no='".$row["mfr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["equipments"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
    
} else if ($_GET["type"] == "getUnits") {
    $output = Array();
    $sql = "SELECT * FROM unit_materials Where  plant_id='".$_GET["plant_id"]."'  order by id desc";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getTestsByClassification") {
    $output = Array();
    $sql = "SELECT DISTINCT(test) as test FROM test WHERE classification='".$_GET["classification"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = Array();
            $sql1 = "SELECT DISTINCT(subtest) as subtest FROM subtest WHERE classification='".$_GET["classification"]."' AND test='".$row["test"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["subtests"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getManufacturingProcesses") {
    $output = Array();
    $sql = "SELECT * FROM manufacturing_process";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveManufacturingProcess") {
    $sql = "INSERT INTO manufacturing_process (dosage_form, process_type, step, entry_by, entry_date) VALUES ('".$input["dosage_form"]."', '".$input["process_type"]."', '".$input["steps"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "saveInprocessSpec") {
    $id = 0;
	$sql = "SELECT MAX(id) as id FROM specification";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
	    while ($row = $result->fetch_assoc()) {
	        $id = $row["id"];
	    }
	}
	$id++;
	$spec_no = "IS-0".$id;
	
	$sql = "INSERT INTO specification (spec_type, specification_no, version_no, supersede_no, sample_qty, unit, product_code, entry_by, entry_date) VALUES ('Inprocess Specification','".$spec_no."','".$input["version_no"]."','".$input["supersede_no"]."','".$input["sample_qty"]."','".$input["unit"]."','".$input["product_code"]."','".$_GET["emp_id"]."','".$entry_date."')";
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
			$sql="INSERT INTO spec_tests (specification_no, test, subtest,description,reference_type,limit_type,lower_limit,upper_limit,lessthan,morethan, unit, stage) VALUES ('".$spec_no."','".$data["test"]."','".$data["subtest"]."','".$data["descr"]."','".$data["ref_type"]."','".$data["limit"]."','".$data["lower_limit"]."','".$data["upper_limit"]."','".$data["lessthan"]."','".$data["morethan"]."', '".$data["unit"]."', '".$data["stage"]."')";
			$conn->query($sql);
	    } 
	    $len = count($input["revisionHistory"]);
	    $revisionHistory = $input["revisionHistory"];
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
} else if ($_GET["type"] == "getInprocessSpecification") {
    $output = Array();
    $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                    $row["generic_name"] = $row1["generic_name"];
                }
            }
            
            $output1 = Array();
            $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["tests"] = $output1;
            
            $output1 = Array();
            $sql1 = "SELECT * FROM spec_revision WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["revision"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getInprocessSamplingRequest") {
    $output = Array();
    $sql = "SELECT * FROM inprocess_sampling WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getInprocessesSamplingReport") {
    $output = Array();
    $sql = "SELECT * FROM inprocess_sampling";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingRetestRawSpecification") {
    $output = Array();
    $sql = "SELECT * FROM specification WHERE spec_type='Raw Material' AND retest='pending' order by id desc";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }
            
            $sql1 = "SELECT * FROM spec_tests WHERE retest != 'Not Applicable' AND specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM testing_tests WHERE test='".$row1["test"]."' AND subtest!='' 
                    AND specification_no='".$row1["specification_no"]."'";
                    $result2 = $conn->query($sql2);
                    $output2 = Array();
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $output2[] = $row2;
                        }
                    }
                    $row1["subtests"] = $output2;
                    $output1[] = $row1;
                }
            }
            $row['spec_tests'] = $output1;
            
            $output1 = array();
            $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row['revision_history'] = $output1;
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
    else if ($_GET["type"] == "approveSpecification") {
        if($_GET["status"]=='qa_reviewed'){
                    $sql = "UPDATE specification SET status='".$_GET["status"]."', qa_reviewed_by='".$_GET["emp_id"]."', qa_review_on='$entry_date' WHERE id='".$_GET["id"]."'";
        }else if($_GET["status"]=='qa_approved'){
                    $sql = "UPDATE specification SET status='".$_GET["status"]."', qa_approved_by='".$_GET["emp_id"]."', qa_approved_on='$entry_date' WHERE id='".$_GET["id"]."'";
        }
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
else if ($_GET["type"] == "getCheckedSpecifications") {
        $output = Array();

             $sql = "select s.*,p.product_type,p.product_name,p.grade as p_grade,p.product_code, m.material_type, m.material_subtype, m.material_name, m.grade as m_grade  FROM specification s 
        LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN product p on s.product_code=p.product_code WHERE s.plant_id= '".$_GET["plant_id"]."'  
        AND s.spec_type ='Finish Product' AND  s.status='".$_GET["status"]."'  ORDER BY s.id DESC";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE user_no='".$_GET["user_no"]."' 
                AND specification_no='".$row["specification_no"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['tests'] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_revision WHERE user_no='".$_GET["user_no"]."' 
                AND spec_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['revision_history'] = $output1;
                
                
                $sql1 = "SELECT * FROM product_other_information_api 
                WHERE product_code='".$_GET["material_code"]."' limit 1  ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row['product_other_info'] = $row1;
                    }
                }
                
                
                 $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['m_grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
else if ($_GET["type"] == "getPendingStabilitySpecifications") {
    $output = Array();
    $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND stability='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["chemical_name"] = $row1["upac_name"];
                    $row["grade"] = $row1["grade"];
                    $row["dosage_form"] = $row1["dosage_form"];
                    $row["generic_name"] = $row1["generic_name"];
                }
            }
            
            $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                    $result2 = $conn->query($sql2);
                    $output2 = Array();
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $output2[] = $row2;
                        }
                    }
                    $row1["subtests"] = $output2;
                    $output1[] = $row1;
                }
            }
            $row['spec_tests'] = $output1;
            
            $output1 = array();
            $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row['revision_history'] = $output1;
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveRetestSpecification") {
    $id = 0;
	$sql = "SELECT MAX(id) as id FROM specification";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
	    while ($row = $result->fetch_assoc()) {
	        $id = $row["id"];
	    }
	}
	$id++;
	$spec_no = "SS-0".$id;
	        $samQty = 0;

	 $tests = $input["spec_tests"];
        for ($i = 0; $i < count($tests); $i++) {
            $data = $tests[$i];
     
			        $samQty =         $samQty + $data["sample_qty"];


 			
        }
        
        $sam = $samQty *2;
	
        $sql = "INSERT INTO specification (plant_id,molecular_weight,control_sample,special_grade,chemical_qty,micro_qty,specification_no, spec_type, material_code, version_no, supersede_no, sample_qty, unit, storage, shelf_life, review_date, 
        retest_period, safety_precaution, entry_by, entry_date) VALUES ('".$_GET["plant_id"]."','".$input["molecular_weight"]."','$sam','".$input["special_grade"]."','".$input["chemical_qty"]."','".$input["micro_qty"]."','$spec_no', 'Retest Specification', '".$input["material_code"]."', '".$input["version_no"]."', 
        '".$input["supersede_no"]."', '".$input["sample_qty"]."', '".$input["unit"]."', '".$input["storage"]."', '".$input["shelf_life"]."', '".$input["review_date"]."', 
        '".$input["retest_period"]."', '".$input["safety_precaution"]."', '".$_GET["emp_id"]."', '$entry_date')";
    
    
    if ($conn->query($sql)) {
        
        $control_sample = 0;
        
        $tests = $input["spec_tests"];
        for ($i = 0; $i < count($tests); $i++) {
            $data = $tests[$i];
            $sql="INSERT INTO spec_tests (plant_id , specification_no,limits, test_type, test, subtest,description,reference_type,limit_type,lower_limit,upper_limit,lessthan,morethan, 
            unit, sample_qty, retest) VALUES ('".$_GET["plant_id"]."','".$spec_no."','".$data["limits"]."','".$data["test_type"]."','".$data["test"]."','".$data["subtest"]."','".$data["description"]."','".$data["reference_type"]."','".$data["limit_type"]."','".$data["lower_limit"]."','".$data["upper_limit"]."','".$data["lessthan"]."','".$data["morethan"]."', '".$data["unit"]."',
            '".$data["sample_qty"]."', '".$data["retest"]."')";
			$conn->query($sql);
			
 			
        }
        
          $len = count($input["revision_history"]);
    	    $revisionHistory = $input["revision_history"];
    	    for ($i =0; $i < $len; $i++) {
                // print_r($i);
    	        $data = $revisionHistory[$i];
                // print_r($data);
    	        $sql = "INSERT INTO spec_revision (user_no, spec_no, specification_no, version_no, change_mode, reason, effective_date) 
                VALUES ('".$_GET["user_no"]."','".$spec_no."','".$data["spec_no"]."','".$data["ver_no"]."','".$data["change_mode"]."','".$data["change_reason"]."', '".$data["effective_date"]."')";
    	        $conn->query($sql);
    	    }
        
        
        echo "{\"status\":\"success\"}";
        
        $sql = "UPDATE specification SET retest='done' WHERE specification_no='".$input["specification_no"]."'";
        $conn->query($sql);
    } else {
        echo "{\"status\":\"failed\"}";
    }
    
    
    
} else if ($_GET["type"] == "saveStabilitySpecification") {
    $id = 0;
	$sql = "SELECT MAX(id) as id FROM specification";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
	    while ($row = $result->fetch_assoc()) {
	        $id = $row["id"];
	    }
	}
	$id++;
	$spec_no = "RS-0".$id;
	
    $sql = "INSERT INTO specification (specification_no, spec_type, version_no, supersede_no, sample_qty, unit, retest_period, product_code, entry_by, entry_date) VALUES ('$spec_no', 'Stability Specification', '".$input["version_no"]."', '".$input["supersede_no"]."', '".$input["sample_qty"]."', '".$input["unit"]."', '".$input["retest_period"]."', '".$input["product_code"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        // $tests = $input["spec_tests"];
        $tests = $input["tests"];
        for ($i = 0; $i < count($tests); $i++) {
            $data = $tests[$i];
            $sql="INSERT INTO spec_tests (specification_no, test, subtest,description,reference_type,limit_type,lower_limit,upper_limit,lessthan,morethan, unit, sample_qty, release_stability,limits,test_type) VALUES ('".$spec_no."','".$data["test"]."','".$data["subtest"]."','".$data["description"]."','".$data["reference_type"]."','".$data["limit_type"]."','".$data["lower_limit"]."','".$data["upper_limit"]."','".$data["lessthan"]."','".$data["morethan"]."', '".$data["unit"]."', '".$data["sample_qty"]."', '".$data["release_stability"]."', '".$data["limits"]."', '".$data["test_type"]."')";
			$conn->query($sql);
        }
        
        
                 $len = count($input["revision_history"]);
    	    $revisionHistory = $input["revision_history"];
    	    for ($i =0; $i < $len; $i++) {
                // print_r($i);
    	        $data = $revisionHistory[$i];
                // print_r($data);
    	        $sql = "INSERT INTO spec_revision (user_no, spec_no, specification_no, version_no, change_mode, reason, effective_date) 
                VALUES ('".$_GET["user_no"]."','".$spec_no."','".$data["spec_no"]."','".$data["ver_no"]."','".$data["change_mode"]."','".$data["change_reason"]."', '".$data["effective_date"]."')";
    	        $conn->query($sql);
    	    }
        
        
        
        echo "{\"status\":\"success\"}";
        
        $sql = "UPDATE specification SET stability='done' WHERE specification_no='".$input["specification_no"]."'";
        $conn->query($sql);
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getUserPendingSamplings") {
    $query_string = "SELECT * FROM sampling WHERE person_allocation='Completed' AND line_clearance='Current' AND clearance_status='pending'";
    $output = Array();
    $result = $conn->query($query_string);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["standard_qty"] = $row1["standard_qty"];
                    $row["unit"] = $row1["unit"];
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }
            
            $sql1 = "SELECT * FROM grn_material WHERE grn_no='".$row["grn_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["containers"] = $row1["containers"];
                    $row["received_qty"] = $row1["accept_qty"];
                    $row["unit"] = $row1["unit"];
                }
            }
            
            $sql1 = "SELECT * FROM grn WHERE grn_no='".$row["grn_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["grn_date"] = $row1["entry_date"];
                }
            }

            $sql1 = "SELECT * FROM section WHERE section_code='".$row["location"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["area_name"] = $row1["section_name"];
                }
            }
            
            $sql1 = "SELECT * FROM grn WHERE grn_no='".$row["grn_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM material_received WHERE receiving_no='".$row1["receiving_no"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["mfg_date"] = $row2["mfg_date"];
                            $row["exp_date"] = $row2["exp_date"];
                            $row["manufacturer"] = $row2["manufacturer"];
                        }
                    }
                }
            }
            
            $sql1 = "SELECT * FROM lineclearance WHERE grn_no='".$row["grn_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["lineclearance"] = $row1;
                    break;
                }
            }
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getUserInprocessSamplings") {
    $query_string = "SELECT * FROM sampling WHERE line_clearance='Completed' AND equipment_log_no=''";
    $output = Array();
    $result = $conn->query($query_string);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row["line_clearance"] == 'Current') {
                $row["current_status"] = "Line Clearance Pending in QA Department";
            } else if ($row["area_checkpoint"] == 'Current' || $row["area_checkpoint"] == 'Not started') {
                if ($row["area_status"] == 'reject') {
                    $row["current_status"] = "Area Parameters not complying";
                } else {
                    $row["current_status"] = "Area Checkpoint is Pending";
                }
            } else if ($row["sampled_info"] == 'Current' || $row["sampled_info"] == 'Not started') {
                $row["current_status"] = "Sampling to be started";
            } else if ($row["equipment_log"] == 'Current' || $row["sampled_info"] == 'Not started') {
                $row["current_status"] = "Equipment Cleaning Log is Pending";
            }
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["standard_qty"] = $row1["standard_qty"];
                    $row["unit"] = $row1["unit"];
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }
            
            $sql1 = "SELECT * FROM grn_material WHERE grn_no='".$row["grn_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["containers"] = $row1["containers"];
                    $row["received_qty"] = $row1["accept_qty"];
                    $row["unit"] = $row1["unit"];
                }
            }
            
            $sql1 = "SELECT * FROM grn WHERE grn_no='".$row["grn_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["grn_date"] = $row1["entry_date"];
                }
            }
            
            $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["composite_qty"] = number_format($row1["sample_qty"], 2);
                    $row["unit"] = $row1["unit"];
                    
                    $sql2 = "SELECT IFNULL(SUM(sample_qty), 0) as qty FROM spec_tests WHERE specification_no='".$row1["specification_no"]."' AND test='Identification'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["indentification_qty"] = $row2["qty"];
                        }
                    } else {
                        $row["indentification_qty"] = 0;
                    }
                }
                $row["actual_composite"] = number_format($row["composite_qty"] * 2, 2);
                $row["actual_indentification"] = number_format($row["indentification_qty"] * 2, 2);
                $row["reserve_composite"] = number_format($row["composite_qty"] * 2, 2);
                
                $row["withdrawal_composite"] = number_format(($row["composite_qty"] + $row["reserve_composite"]) / $row["total_containers"], 2);
                
                if ($row["material_subtype"] == "API") {
                    $row["total_identification"] = number_format($row["indentification_qty"] * $row["total_containers"], 2);
                    $row["total_composite"] = number_format($row["composite_qty"] * $row["total_containers"], 2);
                    $row["total_qty"] = number_format($row["total_composite"] + $row["total_identification"], 2);
                } else if ($row["material_subtype"] == "Excipient") {
                    $row["total_identification"] = $row["indentification_qty"] * $row["total_containers"];
                    $row["total_composite"] = $row["composite_qty"] * $row["total_containers"];
                }
                
                $row["specification"] = 'yes';
            } else {
                $row["specification"] = 'no';
            }

            $sql1 = "SELECT * FROM section WHERE section_code='".$row["location"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["area_name"] = $row1["section_name"];
                }
            }
            
            $sql1 = "SELECT * FROM grn WHERE grn_no='".$row["grn_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM material_received WHERE receiving_no='".$row1["receiving_no"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["mfg_date"] = $row2["mfg_date"];
                            $row["exp_date"] = $row2["exp_date"];
                            $row["manufacturer"] = $row2["manufacturer"];
                        }
                    }
                }
            }
            
            $sql1 = "SELECT * FROM lineclearance WHERE grn_no='".$row["grn_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["lineclearance"] = $row1;
                    break;
                }
            }
            
            $output1 = Array();
            $sql1 = "SELECT * FROM equipment_uses WHERE log_no='".$row["equipment_log_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["equipments"] = $output1;
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingSamplings") {
    $query_string = "SELECT * FROM sampling WHERE status = 'pending'";
    $output = Array();
    $result = $conn->query($query_string);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row["person_allocation"] == 'Current') {
                $row["current_status"] = "Sampling person not allocated";
            } else if ($row["line_clearance"] == 'Current') {
                $row["current_status"] = "Line Clearance Pending in QA Department";
            } else if ($row["area_checkpoint"] == 'Current' || $row["area_checkpoint"] == 'Not started') {
                if ($row["area_status"] == 'reject') {
                    $row["current_status"] = "Area Parameters not complying";
                } else {
                    $row["current_status"] = "Area Checkpoint is Pending";
                }
            } else if ($row["sampled_info"] == 'Current' || $row["sampled_info"] == 'Not started') {
                $row["current_status"] = "Sampling to be started";
            } else if ($row["equipment_log"] == 'Current' || $row["sampled_info"] == 'Not started') {
                $row["current_status"] = "Equipment Cleaning Log is Pending";
            } else if ($row["checking"] == 'Current') {
                $row["current_status"] = "Sampling Checklist Approval pending in Executive";
            } else if ($row["approval"] == 'Current') {
                $row["current_status"] = "Sampling Checklist Approval pending in Manager";
            }
            
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["standard_qty"] = $row1["standard_qty"];
                    $row["unit"] = $row1["unit"];
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }
            
            $sql1 = "SELECT * FROM grn_material WHERE grn_no='".$row["grn_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["containers"] = $row1["containers"];
                    $row["received_qty"] = $row1["accept_qty"];
                    $row["unit"] = $row1["unit"];
                }
            }
            
            $sql1 = "SELECT * FROM grn WHERE grn_no='".$row["grn_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["grn_date"] = $row1["entry_date"];
                    
                    $sql2 = "SELECT * FROM material_received WHERE receiving_no='".$row1["receiving_no"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["mfg_date"] = $row2["mfg_date"];
                            $row["exp_date"] = $row2["exp_date"];
                            $row["manufacturer"] = $row2["manufacturer"];
                        }
                    }
                }
            }
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getRetestSpecifications") {
    $output = Array();
    $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                    $row["chemical_name"] = $row1["upac_name"];
                }
            }
            
            $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row['spec_tests'] = $output1;
            
            $output1 = array();
            $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row['revision_history'] = $output1;
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingMedicalEmployees") {
    $output = Array();
    $sql = "SELECT * FROM employee WHERE medical_checkup='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveMedicalCheckup") {
    $sql = "INSERT INTO medicalcheckup (emp_id,phisician_name,entry_by,entry_date) VALUES ('".$input["emp_id"]."', '".$input["phisician"]."', '".$_GET["emp_id"]."', '".$entry_date."')";
    if ($conn->query($sql)) {
        $last_id = $conn->insert_id;
        if ($input["general_checkup"] == true) {
            $sql = "INSERT INTO medical_tests (checkup_no, test) VALUES ('$last_id', 'General Checkup')";
            $conn->query($sql);
        }
        if ($input["bp"] == true) {
            $sql = "INSERT INTO medical_tests (checkup_no, test) VALUES ('$last_id', 'BP')";
            $conn->query($sql);
        }
        if ($input["sugar"] == true) {
            $sql = "INSERT INTO medical_tests (checkup_no, test) VALUES ('$last_id', 'Sugar')";
            $conn->query($sql);
        }
        if ($input["ecg"] == true) {
            $sql = "INSERT INTO medical_tests (checkup_no, test) VALUES ('$last_id', 'ECG')";
            $conn->query($sql);
        }
        if ($input["blood_test"] == true) {
            $sql = "INSERT INTO medical_tests (checkup_no, test) VALUES ('$last_id', 'Blood Test')";
            $conn->query($sql);
        }
        if ($input["hb"] == true) {
            $sql = "INSERT INTO medical_tests (checkup_no, test) VALUES ('$last_id', 'HB')";
            $conn->query($sql);
        }
        if ($input["eye_checkup"] == true) {
            $sql = "INSERT INTO medical_tests (checkup_no, test) VALUES ('$last_id', 'Eye Checkup')";
            $conn->query($sql);
        }
        if ($input["color_blindness"] == true) {
            $sql = "INSERT INTO medical_tests (checkup_no, test) VALUES ('$last_id', 'Color Blindness')";
            $conn->query($sql);
        }
        if ($input["hiv"] == true) {
            $sql = "INSERT INTO medical_tests (checkup_no, test) VALUES ('$last_id', 'HIV')";
            $conn->query($sql);
        }
        if ($input["other_test"] !== '') {
            $sql = "INSERT INTO medical_tests (checkup_no, test) VALUES ('$last_id', '".$input["other_test"]."')";
            $conn->query($sql);
        }
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getMedicalCheckupReport") {
    $output = Array();
    $sql = "SELECT * FROM medicalcheckup";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            $sql1 = "SELECT * FROM employee WHERE emp_id='".$row["emp_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["emp_name"] = $row1["emp_name"];
                }
            }
            
            $output1 = Array();
            $sql1 = "SELECT * FROM medical_tests WHERE checkup_no='".$row["id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["tests"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getSpecificationIndex") {
    $output = Array();
    $sql = "SELECT * FROM specification";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                    $row["chemical_name"] = $row1["upac_name"];
                }
            }
            
            $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row['spec_tests'] = $output1;
            
            $output1 = array();
            $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row['revision_history'] = $output1;
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getEquipmentNames") {
    $output = Array();
    $sql = "SELECT DISTINCT(equipment_name) as equipment_name FROM equipment";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getEquipmentMake") {
    $output = Array();
    $sql = "SELECT DISTINCT(make) as make FROM equipment WHERE equipment_name = '".$_GET["equipment_name"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if($_GET["type"]=="saveMasterFormula") {
    error_reporting(0);
	$sql = "SELECT IFNULL(MAX(mf_id1), 0) as mf_id1 FROM masterformula";
	$mf_id1 = 0;
	$mf_id = '';
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
	    while ($row = $result->fetch_assoc()) {
	        $mf_id1 = $row["mf_id1"];
	        break;
	    }
	}
	
	$mf_id1++;
	$no = strlen($mf_id1);
	if ($no == 1) {
	    $mf_id = 'MF-00'.$mf_id1;
	} else if ($no == 2) {
	    $mf_id = 'MF-0'.$mf_id1;
	} else if ($no >= 3) {
	    $mf_id = 'MF-'.$mf_id1;
	}
	
	$sql = "INSERT INTO masterformula (mfr_no,product_code,ownership_type,client_name, shelf_life,average_weight,unit,packing_type, entry_by, entry_date, mf_id1) VALUES ('$mf_id','".$input["product_name"]."','".$input["ownership_type"]."', '".$input["client_name"]."','".$input["shelfLife"]."','".$input["average_weight"]."', '".$input["unit"]."','".$input["packing_type"]."','".$_GET["emp_id"]."','$entry_date',$mf_id1)"; 
	if($conn->query($sql)===TRUE){
		$experience_company = $input["materials"];
		$len = count($experience_company);
		for($i = 0; $i<$len; $i++) {
			$data = $experience_company[$i];
			$sql="INSERT INTO mf_material (material_type, mf_no, material_code, qty, unit, overage, role) VALUES ('Raw Material','$mf_id','".$data["material_code"]."','".$data["qty"]."','".$data["unit"]."','".$data["overages"]."','".$data["role"]."')";
			$conn->query($sql);
		}
		
		$equipments = $input["equipments"];
		$len = count($equipments);
		for($i = 0; $i<$len; $i++) {
			$data = $equipments[$i];
			$sql="INSERT INTO mf_equipments (mfr_no, equipment_name, capacity) VALUES ('$mf_id','".$data["equipment_name"]."','".$data["capacity"]."')";
			$conn->query($sql);
		}
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
} else if ($_GET["type"] == "getPendingQAInprocessChecks") {
    $output = Array();
    $sql = "SELECT * FROM batch_planning";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["current_stage"] = "MILLING";
            
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["generic_name"] = $row1["generic_name"];
                    $row["label_claim"] = $row1["label_claim"];
                    $row["shelf_life"] = $row1["shelf_life"];
                     $row["dosage_form"] = $row1["dosage_form"];
                    break;
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingReviewMeetings") {
    $output = Array();
    $sql = "SELECT * FROM meeting_managers WHERE department='".$_GET["department"]."' AND status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $sql = "SELECT * FROM review_meeting WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM meeting_managers WHERE meeting_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["managers"] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT * FROM meeting_agendas WHERE meeting_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["agendas"] = $output1;
                $output[] = $row;
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getReviewMeetings") {
    $output = Array();
    $sql = "SELECT * FROM review_meeting";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = Array();
            $sql1 = "SELECT * FROM meeting_managers WHERE meeting_no='".$row["id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["manager_no"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["emp_name"] = $row2["emp_name"];
                        }
                    }
                    $output1[] = $row1;
                }
            }
            $row["managers"] = $output1;
            
            $output1 = Array();
            $sql1 = "SELECT * FROM meeting_agendas WHERE meeting_no='".$row["id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["agendas"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getManagers") {
    $output = Array();
    $sql = "SELECT * FROM employee WHERE designation='manager' AND status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveManagementMeetingAnnoucement") {
    $sql = "INSERT INTO review_meeting (meeting_to, department, meeting_time, representative, entry_by, entry_date) VALUES ('".$input["to"]."', '".$input["department"]."', '".$input["meeting_time"]."', '".$input["meeting_representative"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        $last_id = $conn->insert_id;
        $managers = $input["managers"];
        for ($i = 0; $i < count($managers); $i++) {
            $data = $managers[$i];
            $sql1 = "INSERT INTO meeting_managers (meeting_no, manager_no, department) VALUES ('$last_id', '".$data["emp_id"]."', '".$data["department"]."')";
            $conn->query($sql1);
        }
        $agendas = $input["agendas"];
        for ($i = 0; $i < count($agendas); $i++) {
            $data = $agendas[$i];
            $sql1 = "INSERT INTO meeting_agendas (meeting_no, agenda, details) VALUES ('$last_id', '".$data["agenda"]."', '".$data["detail"]."')";
            $conn->query($sql1);
        }
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingRawMOA") {
    $output = Array();
    $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                    $row["chemical_name"] = $row1["upac_name"];
                }
            }
            
            $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output9 = Array();
                    $sql2 = "SELECT * FROM test_method WHERE test='".$row1["test"]."' AND subtest='".$row1["subtest"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        $row1["ismethod"] = "yes";
                        while ($row2 = $result2->fetch_assoc()) {
                            $row2["descriptions"] = json_decode($row2["descriptions"]);
                            $output9[] = $row2;
                            break;
                        }
                    } else {
                        $row1["ismethod"] = "no";
                    }
                    $row1["methods"] = $output9;
                    $output1[] = $row1;
                }
            }
            $row['spec_tests'] = $output1;
            
            $output1 = array();
            $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row['revision_history'] = $output1;
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveVendorIdentification") {
    $sql = "INSERT INTO vendor_identification (material_name, specification, product_name, specific_parameters, specific_manufacturer, isapproved, trail_sample, entry_by, entry_date) VALUES ('".$input["material_name"]."', '".$input["specification"]."', '".$input["product_name"]."', '".$input["specific_parameters"]."', '".$input["specific_manufacturer"]."', '".$input["isapproved"]."', '".$input["trail_sample"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getVendorIdentification") {
    $output = Array();
    $sql = "SELECT * FROM vendor_identification";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getVendorApprovalChecklist") {
    $output = Array();
    $sql = "SELECT * FROM vendor_checklist";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getEquipmentVendor") {
    $output = Array();
    $sql = "SELECT * FROM equipment_vendor";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getVendors") {
    $output = Array();
    $sql = "SELECT * FROM vendor";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveUserRequirementSpecification") {
    $sql = "INSERT INTO equipment_requirement (vendor_no, equipment_name, capacity, expected_output, equipments_no, entry_by, entry_date) VALUES ('".$input["vendor_no"]."', '".$input["equipment_name"]."', '".$input["capacity"]."', '".$input["expected_output"]."', '".$input["equipments_no"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        $last_id = $conn->insert_id;
        $data = $input["specs"];
        for ($i = 0; $i < count($data); $i++) {
            $temp = $data[$i];
            $sql1 = "INSERT INTO equipment_specification (equip_no, requirement, specification, remark) VALUES ('".$last_id."', '".$temp["requirement"]."', '".$temp["specification"]."', '".$temp['remark']."')";
            $conn->query($sql1);
        }
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingDesignQualifications") {
    $output = Array();
    $sql = "SELECT * FROM equipment_requirement WHERE dq_status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["vendor_name"] = $row1["vendor_name"];
                }
            }
            
            $output1 = Array();
            $sql1 = "SELECT * FROM equipment_specification WHERE equip_no='".$row["id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["specs"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveDesignQualification") {
    if(isset($_FILES["file"]["name"])){
    	$target_file = "upload/dq/".basename($_FILES["file"]["name"]);
    	$file3 = basename($_FILES["file"]["name"]);
    	move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
    	$sql = "UPDATE equipment_requirement SET dq_status='inprocess', dq_file='$file3' WHERE id='".$_POST["id"]."'";
    	if ($conn->query($sql)) {
    	    echo "{\"status\":\"success\"}";
    	} else {
    	    echo "{\"status\":\"failed\"}";
    	}
	} else {
	    echo "{\"status\":\"failed\"}";
	}
} else if ($_GET["type"] == "saveBOMRequest") {
    $sql = "INSERT INTO bom_request (dosage_form, product_code, mfr_no, batch_size, lots, entry_by, entry_date) VALUES ('".$input["dosage_form"]."', '".$input["product_code"]."', '".$input["mfr_no"]."', '".$input["batch_size"]."', '".$input["lots"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getStabilityTestingReport") {
    $output = Array();
    $sql = "SELECT * FROM stability_testing";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getApprovedBatches") {
    $output = Array();
    $sql = "SELECT * FROM batch_planning WHERE status='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = Array();
            $sql1 = "SELECT * FROM mf_material WHERE mf_no='".$row["mfr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["material_name"] = $row2["material_name"];
                            $row1["grade"] = $row2["grade"];
                            break;
                        }
                    }
                    $output1[] = $row1;
                }
            }
            $row["materials"] = $output1;
            
            $output1 = Array();
            $sql1 = "SELECT * FROM mf_equipments WHERE mfr_no='".$row["mfr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["equipments"] = $output1;
            
            $output1 = Array();
            $sql1 = "SELECT * FROM bp_stages WHERE bp_no='".$row["no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["stages"] = $output1;
            
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["generic_name"] = $row1["generic_name"];
                    $row["grade"] = $row1["grade"];
                    $row["packing_style"] = $row1["packing_style"];
                    $row["dosage_form"] = $row1["dosage_form"];
                    $row["excipient"] = $row1["excipient"];
                    $row["label_claim"] = $row1["label_claim"];
                    $row["shelf_life"] = $row1["shelf_life"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingMasterFormula") {
    $sql = "SELECT * FROM masterformula WHERE status='pending'";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM mf_material WHERE mf_no='".$row["mfr_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["material_name"] = $row2["material_name"];
                            $row1["grade"] = $row2["grade"];
                        }
                    }
                    $output1[] = $row1;
                }
            }
            $row["materials"] = $output1;
            
            $output1 = Array();
            $sql1 = "SELECT * FROM mf_equipments WHERE mfr_no='".$row["mfr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["equipments"] = $output1;
            
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["dosage_form"] = $row1["dosage_form"];
                    $row["grade"] = $row1["grade"];
                    $row["label_claim"] = $row1["label_claim"];
                }
            }
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getCheckedMasterFormula") {
    $sql = "SELECT * FROM masterformula WHERE status='checked'";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM mf_material WHERE mf_no='".$row["mfr_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["material_name"] = $row2["material_name"];
                            $row1["grade"] = $row2["grade"];
                        }
                    }
                    $output1[] = $row1;
                }
            }
            $row["materials"] = $output1;
            
            $output1 = Array();
            $sql1 = "SELECT * FROM mf_equipments WHERE mfr_no='".$row["mfr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["equipments"] = $output1;
            
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["dosage_form"] = $row1["dosage_form"];
                    $row["grade"] = $row1["grade"];
                    $row["label_claim"] = $row1["label_claim"];
                }
            }
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingBatchFormulas") {
    $output = Array();
    $sql = "SELECT * FROM batch_formula WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["generic_name"] = $row1["generic_name"];
                    $row["grade"] = $row1["grade"];
                    $row["packing_style"] = $row1["packing_style"];
                    $row["dosage_form"] = $row1["dosage_form"];
                    $row["grade"] = $row1["grade"];
                    $row["color_used"] = $row1["color_used"];
                    $row["label_claim"] = $row1["label_claim"];
                    $row["shelf_life"] = $row1["shelf_life"];
                    break;
                }
            }
            
            $output1 = Array();
            $sql1 = "SELECT * FROM mf_material WHERE mf_no='".$row["mf_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $std_qty = $row1["qty"] * $row["batch_size"];
                    if ($row1["unit"] == 'mg') {
                        $row1["std_qty"] = number_format(($std_qty / 1000000), 2);
                        $row1["std_unit"] = "kg";
                    } else {
                        $row1["std_unit"] = $row1["unit"];
                    }
                    
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["material_name"] = $row2["material_name"];
                            $row1["grade"] = $row2["grade"];
                        }
                    }
                    
                    $output1[] = $row1;
                }
            }
            $row["materials"] = $output1;
            
            $lots = $row["lots"];
            $materials = $row["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $data = $materials[$i];
                $lot_qty = +$data["std_qty"] / +$lots;
                $data["lot_qty"] = $lot_qty;
                $materials[$i] = $data;
            }
            
            $output1 = Array();
            for ($i = 0; $i < $lots; $i++) {
                $output1[] = $materials;
            }
            $row["lot_materials"] = $output1;
            
            $output1 = Array();
            $sql1 = "SELECT * FROM mf_equipments WHERE mfr_no='".$row["mfr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["equipments"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getCheckedBatchFormulas") {
    $output = Array();
    $sql = "SELECT * FROM batch_formula WHERE status='checked'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["generic_name"] = $row1["generic_name"];
                    $row["grade"] = $row1["grade"];
                    $row["packing_style"] = $row1["packing_style"];
                    $row["dosage_form"] = $row1["dosage_form"];
                    $row["grade"] = $row1["grade"];
                    $row["color_used"] = $row1["color_used"];
                    $row["label_claim"] = $row1["label_claim"];
                    $row["shelf_life"] = $row1["shelf_life"];
                    break;
                }
            }
            
            $output1 = Array();
            $sql1 = "SELECT * FROM mf_material WHERE mf_no='".$row["mf_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $std_qty = $row1["qty"] * $row["batch_size"];
                    if ($row1["unit"] == 'mg') {
                        $row1["std_qty"] = number_format(($std_qty / 1000000), 2);
                        $row1["std_unit"] = "kg";
                    } else {
                        $row1["std_unit"] = $row1["unit"];
                    }
                    
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["material_name"] = $row2["material_name"];
                            $row1["grade"] = $row2["grade"];
                        }
                    }
                    
                    $output1[] = $row1;
                }
            }
            $row["materials"] = $output1;
            
            $lots = $row["lots"];
            $materials = $row["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $data = $materials[$i];
                $lot_qty = +$data["std_qty"] / +$lots;
                $data["lot_qty"] = $lot_qty;
                $materials[$i] = $data;
            }
            
            $output1 = Array();
            for ($i = 0; $i < $lots; $i++) {
                $output1[] = $materials;
            }
            $row["lot_materials"] = $output1;
            
            $output1 = Array();
            $sql1 = "SELECT * FROM mf_equipments WHERE mfr_no='".$row["mfr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["equipments"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="checkBatchFormula") {
    $sql = "UPDATE batch_formula SET status='".$_GET["action"]."', check_by='".$_GET["emp_id"]."', check_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="approveBatchFormula") {
    $sql = "UPDATE batch_formula SET status='".$_GET["action"]."', approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "saveChemicalReceiving") {
    $sql = "INSERT INTO chemicals (vendor_no, chemical_no, batch_no, received_qty, unit, mfg_date, exp_date, entry_by, entry_date) VALUES ('".$input["vendor_no"]."','".$input["chemical_no"]."', '".$input["batch_no"]."', '".$input["received_qty"]."', '".$input["unit"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getChemicals") {
    $output = Array();
    $sql = "SELECT * FROM chemical_master";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getChemicalStock") {
    $output = Array();
    $sql = "SELECT * FROM chemicals";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["vendor_name"] = $row1["vendor_name"];
                }
            }

            $sql1 = "SELECT * FROM chemical_master WHERE id='".$row["chemical_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["chemical_name"] = $row1["chemical_name"];
                }
            }

            $row["balance_qty"] = number_format(($row["received_qty"] - $row["issue_qty"]), 2);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if($_GET["type"]=="getApprovedEquipments") {
    $output = Array();
	$sql = "SELECT * FROM equipment WHERE status='active'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if($_GET["type"]=="getTests") {
    $output = Array();
	$sql = "SELECT * FROM test WHERE status='active' AND classification='".$_GET["classification"]."' AND dosage_form='".$_GET["dosage_form"]."'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if($_GET["type"]=="getSubtests") {
    $output = Array();
	$sql = "SELECT * FROM subtest WHERE status='active' AND classification='".$_GET["classification"]."' AND dosage_form='".$_GET["dosage_form"]."' AND test='".$_GET["test"]."'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "saveTestMethodMaster") {
    $t_no1 = 0;
    $tm_no = "";
    $sql = "SELECT MAX(t_no1) as t_no1 FROM test_method";
    $result = $conn->query($sql);
    if ($result-> num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $t_no1 = $row["t_no1"];
        }
    }
    $t_no1++;
    $tm_no = "TM-".$t_no1;
    
    $sql = "INSERT INTO test_method (tm_no ,classification, dosage_form, test, subtest, test_grade, descriptions, entry_by, entry_date, t_no1) VALUES ('$tm_no','".$input["classification"]."', '".$input["dosage_form"]."','".$input["test"]."', '".$input["subtest"]."', '".$input["test_grade"]."', '".json_encode($input["description"])."', '".$_GET["emp_id"]."', '$entry_date', $t_no1)";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="getTestMethods") {
    $output = Array();
    $sql = "SELECT * FROM test_method";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["descriptions"] = json_decode($row["descriptions"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getIssuedChemicals") {
    $output = Array();
    $sql = "SELECT * FROM chemical_issue";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM chemical_master WHERE id='".$row["chemical_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["chemical_name"] = $row1["chemical_name"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getChemicalDestruction") {
    $output = Array();
    $sql = "SELECT * FROM chemicals WHERE exp_date < CURDATE() AND status != 'distroy'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM chemical_master WHERE id='".$row["chemical_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["chemical_name"] = $row1["chemical_name"];
                }
            }

            $row["balance_qty"] = number_format(($row["received_qty"] - $row["issue_qty"]), 2);

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "destroyChemical") {
    $sql = "UPDATE chemicals SET status='destroy', destroy_by='".$_GET["emp_id"]."', destroy_date='".$entry_date."' WHERE id='".$_GET["chemical_no"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getConsumptionChemicals") {
    $output = Array();
    $sql = "SELECT * FROM chemicals";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM chemical_master WHERE id='".$row["chemical_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["chemical_name"] = $row1["chemical_name"];
                }
            }

            $received_qty = +$row["received_qty"];
            $sql1 = "SELECT * FROM chemical_issue WHERE chemical_no='".$row["chemical_no"]."' AND batch_no='".$row["batch_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {

                    $row1["chemical_name"] = $row["chemical_name"];
                    $row1["available_qty"] = $received_qty;
                    $row1["issue_qty"] = $row1["qty"];
                    $row1["balance_qty"] = number_format(($received_qty - $row1["qty"]), 2);
                    $received_qty = +$row1["balance_qty"];

                    $output[] = $row1;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingAllocationTestings") {
    $output = Array();
    $sql = "SELECT * FROM testing WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }
            
            if ($row["status"] == 'pending') {
                $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["specification_no"] = $row1["specification_no"];
                        $output1 = Array();
                        $sql2 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output1[] = $row2;
                            }
                        }
                        $row["spec_tests"] = $output1;
                    }
                } else {
                    $row["specification_no"] = "";
                }
            } else {
                $output1 = Array();
                $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["testing_tests"] = $output1;
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getMasterFormulaReports") {
    $output = Array();
    $sql = "SELECT * FROM masterformula";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                    $row["dosage_form"] = $row1["dosage_form"];
                }
            }

            $output1 = Array();
            $sql1 = "SELECT * FROM mf_material WHERE mf_no='".$row["mfr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["materials"] = $output1;

            $output1 = Array();
            $sql1 = "SELECT * FROM mf_equipments WHERE mfr_no='".$row["mfr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["equipments"] = $output1;

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateProduct") {
    $sql = "UPDATE product SET status='".$_GET["action"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingDocuments") {
    
    $output = array();
    $sql = "SELECT pd.*, e.emp_name, e.designation FROM pendingdocument pd JOIN employee e ON pd.entry_by = e.emp_id WHERE pd.status='pending' OR pd.status='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            $now = time();
            $datediff = $now - strtotime($row["entry_date"]);
            $days =  round($datediff / (60 * 60 * 24));

            $row["no_days"] = $days;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getSops") {
    $output = Array();
    $sql = "SELECT * FROM sops";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}


} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>