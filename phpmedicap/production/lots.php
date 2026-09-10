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
if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "getPendingLots") {
        $output = Array();
        $sql = "SELECT * FROM bmr_lots WHERE status='start'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM bmr WHERE id='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["batch_size"] = $row1["batch_size"];
                        $row["mfr_no"] = $row1["mfr_no"];
                        $row["product_code"] = $row1["product_code"];
                        
                        $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row["product_name"] = $row2["product_name"];
                                $row["grade"] = $row2["grade"];
                                $row["generic_name"] = $row2["generic_name"];
                                $row["packing_style"] = $row2["packing_style"];
                                $row["dosage_type"] = $row2["dosage_type"];
                                $row["dosage_form"] = $row2["dosage_form"];
                                $row["excipient"] = $row2["excipient"];
                                $row["shelf_life"] = $row2["shelf_life"];
                                $row["color_used"] = $row2["color_used"];
                                $row["label_claim"] = $row2["label_claim"];
                            }
                        }
                    }
                }
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_material WHERE no='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
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
                $sql1 = "SELECT * FROM lot_stages WHERE bmr_no='".$row["bmr_no"]."' AND lot_no='".$row["lot_no"]."'";
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
    } else if ($_GET["type"] == "getPendingDispensing") {
        $output = Array();
        $sql = "SELECT * FROM bmr_lots WHERE status='start'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql9 = "SELECT * FROM lot_stages WHERE bmr_no='".$row["bmr_no"]."' AND lot_no='".$row["lot_no"]."' AND stage='DISPENSING' AND status='pending'";
                $result9 = $conn->query($sql9);
                if ($result9->num_rows > 0) {
                    $sql1 = "SELECT * FROM bmr WHERE id='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["batch_size"] = $row1["batch_size"];
                            $row["mfr_no"] = $row1["mfr_no"];
                            $row["product_code"] = $row1["product_code"];
                            
                            $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $row["product_name"] = $row2["product_name"];
                                    $row["grade"] = $row2["grade"];
                                    $row["generic_name"] = $row2["generic_name"];
                                    $row["packing_style"] = $row2["packing_style"];
                                    $row["dosage_type"] = $row2["dosage_type"];
                                    $row["dosage_form"] = $row2["dosage_form"];
                                    $row["excipient"] = $row2["excipient"];
                                    $row["shelf_life"] = $row2["shelf_life"];
                                    $row["color_used"] = $row2["color_used"];
                                    $row["label_claim"] = $row2["label_claim"];
                                }
                            }
                        }
                    }
                    $output1 = Array();
                    $sql1 = "SELECT * FROM bmr_material WHERE no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
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
                    
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "sendDispensingRequest") {
        $temp = Array();
        $temp["request_by"] = $_GET["emp_id"];
        $temp["request_date"] = $entry_date;
        $sql = "UPDATE lot_stages SET status='request', data='".json_encode($temp)."' WHERE bmr_no='".$_GET["bmr_no"]."' AND lot_no='".$_GET["lot_no"]."' AND stage='DISPENSING'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getDispensingRequests") {
        $output = Array();
        $sql = "SELECT * FROM lot_stages WHERE stage='DISPENSING' AND status='request'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM bmr_lots WHERE bmr_no='".$row["bmr_no"]."' AND lot_no='".$row["lot_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["lot_size"] = $row1["lot_size"];
                    }
                }
                
                $sql1 = "SELECT * FROM bmr WHERE id='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["batch_size"] = $row1["batch_size"];
                        $row["mfr_no"] = $row1["mfr_no"];
                        $row["product_code"] = $row1["product_code"];
                        
                        $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row["product_name"] = $row2["product_name"];
                                $row["grade"] = $row2["grade"];
                                $row["generic_name"] = $row2["generic_name"];
                                $row["packing_style"] = $row2["packing_style"];
                                $row["dosage_type"] = $row2["dosage_type"];
                                $row["dosage_form"] = $row2["dosage_form"];
                                $row["excipient"] = $row2["excipient"];
                                $row["shelf_life"] = $row2["shelf_life"];
                                $row["color_used"] = $row2["color_used"];
                                $row["label_claim"] = $row2["label_claim"];
                            }
                        }
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_material WHERE no='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
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
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "allocateDispensing") {
        $input["allocate_by"] = $_GET["emp_id"];
        $input["allocate_date"] = $entry_date;
        $sql = "UPDATE lot_stages SET data='".json_encode($input)."', status='allocate' WHERE bmr_no='".$_GET["bmr_no"]."' AND lot_no='".$_GET["lot_no"]."' AND stage='DISPENSING'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getAllocatedDispensings") {
        $output = Array();
        $sql = "SELECT * FROM lot_stages WHERE stage='DISPENSING' AND status='allocate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM bmr_lots WHERE bmr_no='".$row["bmr_no"]."' AND lot_no='".$row["lot_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["lot_size"] = $row1["lot_size"];
                    }
                }
                
                $sql1 = "SELECT * FROM bmr WHERE id='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["batch_size"] = $row1["batch_size"];
                        $row["mfr_no"] = $row1["mfr_no"];
                        $row["product_code"] = $row1["product_code"];
                        
                        $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row["product_name"] = $row2["product_name"];
                                $row["grade"] = $row2["grade"];
                                $row["generic_name"] = $row2["generic_name"];
                                $row["packing_style"] = $row2["packing_style"];
                                $row["dosage_type"] = $row2["dosage_type"];
                                $row["dosage_form"] = $row2["dosage_form"];
                                $row["excipient"] = $row2["excipient"];
                                $row["shelf_life"] = $row2["shelf_life"];
                                $row["color_used"] = $row2["color_used"];
                                $row["label_claim"] = $row2["label_claim"];
                            }
                        }
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_material WHERE no='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["material_name"] = $row2["material_name"];
                                $row1["grade"] = $row2["grade"];
                            }
                        }
                        
                        $sql3 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row1["material_code"]."' AND status='Approved'";
                        $result3 = $conn->query($sql3);
                        if ($result3->num_rows > 0) {
                            $row2["status"] = "available";
                            while ($row3 = $result3->fetch_assoc()) {
                                if (+$row3["qty"] == 0) {
                                    $row1["avl_qty"] = 0.00;
                                    $row1["status"] = "na";
                                } else if (+$row3["qty"] < +$row1["lot_qty"]) {
                                    $row1["avl_qty"] = +$row1["qty"];
                                    $row1["status"] = "short";
                                } else if (+$row3["qty"] >= +$row1["lot_qty"]) {
                                    $row1["avl_qty"] = +$row1["lot_qty"];
                                    $row1["status"] = "available";
                                }
                            }
                        } else {
                            $row1["avl_qty"] = 0;
                            $row1["status"] = "not available";
                        }
                        
                        $required_qty = +$row1["lot_qty"];
                        if ($row1["status"] == "available") {
                            $output3 = Array();
                            $sql3 = "SELECT * FROM stock_book WHERE material_code='".$row1["material_code"]."' AND status='Approved'";
                            $result3 = $conn->query($sql3);
                            if ($result3->num_rows > 0) {
                                while ($row3 = $result3->fetch_assoc()) {
                                    if ($required_qty == 0) {
                                        break;
                                    } else if ($required_qty > +$row3["qty"]) {
                                        $required_qty = $required_qty - +$row3["qty"];
                                        $row3["used_qty"] = +$row3["qty"];
                                    } else if ($required_qty <= +$row3["qty"]) {
                                        $row3["used_qty"] = $required_qty;
                                        $required_qty = 0;
                                    }
                                    $row3["gross_wt"] = $row3["used_qty"];
                                    $row3["tare_wt"] = 0;
                                    $row3["net_wt"] = $row3["used_qty"];
                                    $output3[] = $row3;
                                }
                            }
                            $row1["materials"] = $output3;
                        }
                        
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDispensingForm") {
        $data = "";
        $sql = "SELECT * FROM lot_stages WHERE bmr_no='".$_GET["bmr_no"]."' AND lot_no='".$_GET["lot_no"]."' AND stage='DISPENSING'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data = json_decode($row["data"]);
            }
        }
        $data->dispense_by = $_GET["emp_id"];
        $data->dispense_date = $entry_date;
        $data->materials = $input;
        $sql = "UPDATE lot_stages SET status='dispense', data='".json_encode($data)."' WHERE bmr_no='".$_GET["bmr_no"]."' AND lot_no='".$_GET["lot_no"]."' AND stage='DISPENSING'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            for ($i = 0; $i < count($input); $i++) {
                $material = $input[$i];
                
                $materials = $material["materials"];
                for ($j = 0; $j < count($materials); $j++) {
                    $temp = $materials[$j];
                    unset($temp["containers"]);
                    $temp["used_qty"] = number_format(+$temp["used_qty"], 2);
                    $temp["gross_wt"] = number_format(+$temp["gross_wt"], 2);
                    $temp["tare_wt"] = number_format(+$temp["tare_wt"], 2);
                    $temp["net_wt"] = number_format(+$temp["gross_wt"] + +$temp["tare_wt"], 2);
                    $materials[$j] = $temp;
                    
                    $sql1 = "INSERT INTO dispensing (bmr_no, lot_no, material_code, batch_no, ar_no, qty, unit, entry_by, entry_date) VALUES ('', '', '', '', '', '', '')";
                    $conn->query($sql1);
                }
                $sql1 = "UPDATE bmr_material SET materials='".json_encode($materials)."' WHERE no='".$_GET["bmr_no"]."' AND id='".$material["id"]."'";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getActiveDispensing") {
        $output = Array();
        $sql = "SELECT * FROM lot_stages WHERE stage='DISPENSING' AND status='dispense'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM bmr_lots WHERE bmr_no='".$row["bmr_no"]."' AND lot_no='".$row["lot_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["lot_size"] = $row1["lot_size"];
                    }
                }
                
                $sql1 = "SELECT * FROM bmr WHERE id='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["batch_size"] = $row1["batch_size"];
                        $row["mfr_no"] = $row1["mfr_no"];
                        $row["product_code"] = $row1["product_code"];
                        
                        $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row["product_name"] = $row2["product_name"];
                                $row["grade"] = $row2["grade"];
                                $row["generic_name"] = $row2["generic_name"];
                                $row["packing_style"] = $row2["packing_style"];
                                $row["dosage_type"] = $row2["dosage_type"];
                                $row["dosage_form"] = $row2["dosage_form"];
                                $row["excipient"] = $row2["excipient"];
                                $row["shelf_life"] = $row2["shelf_life"];
                                $row["color_used"] = $row2["color_used"];
                                $row["label_claim"] = $row2["label_claim"];
                            }
                        }
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_material WHERE no='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["material_name"] = $row2["material_name"];
                                $row1["grade"] = $row2["grade"];
                            }
                        }
                        $row1["materials"] = json_decode($row1["materials"]);
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "checkDispensingForm") {
        $data = "";
        $sql = "SELECT * FROM lot_stages WHERE bmr_no='".$_GET["bmr_no"]."' AND lot_no='".$_GET["lot_no"]."' AND stage='DISPENSING'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data = json_decode($row["data"]);
            }
        }
        $data->check_by = $_GET["emp_id"];
        $data->check_date = $entry_date;
        $sql = "UPDATE lot_stages SET data='".json_encode($data)."', status='".$_GET["status"]."' WHERE bmr_no='".$_GET["bmr_no"]."' AND lot_no='".$_GET["lot_no"]."' AND stage='DISPENSING'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getCheckedDispensing") {
        $output = Array();
        $sql = "SELECT * FROM lot_stages WHERE stage='DISPENSING' AND status='checked'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM bmr_lots WHERE bmr_no='".$row["bmr_no"]."' AND lot_no='".$row["lot_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["lot_size"] = $row1["lot_size"];
                    }
                }
                
                $sql1 = "SELECT * FROM bmr WHERE id='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["batch_size"] = $row1["batch_size"];
                        $row["mfr_no"] = $row1["mfr_no"];
                        $row["product_code"] = $row1["product_code"];
                        
                        $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row["product_name"] = $row2["product_name"];
                                $row["grade"] = $row2["grade"];
                                $row["generic_name"] = $row2["generic_name"];
                                $row["packing_style"] = $row2["packing_style"];
                                $row["dosage_type"] = $row2["dosage_type"];
                                $row["dosage_form"] = $row2["dosage_form"];
                                $row["excipient"] = $row2["excipient"];
                                $row["shelf_life"] = $row2["shelf_life"];
                                $row["color_used"] = $row2["color_used"];
                                $row["label_claim"] = $row2["label_claim"];
                            }
                        }
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_material WHERE no='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["material_name"] = $row2["material_name"];
                                $row1["grade"] = $row2["grade"];
                            }
                        }
                        $row1["materials"] = json_decode($row1["materials"]);
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "approveDispensingForm") {
        $data = "";
        $sql = "SELECT * FROM lot_stages WHERE bmr_no='".$_GET["bmr_no"]."' AND lot_no='".$_GET["lot_no"]."' AND stage='DISPENSING'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data = json_decode($row["data"]);
            }
        }
        $data->approve_by = $_GET["emp_id"];
        $data->approve_date = $entry_date;
        $sql = "UPDATE lot_stages SET data='".json_encode($data)."', status='".$_GET["status"]."' WHERE bmr_no='".$_GET["bmr_no"]."' AND lot_no='".$_GET["lot_no"]."' AND stage='DISPENSING'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getDispensingLog") {
        $output = Array();
        $sql = "SELECT * FROM lot_stages WHERE stage='DISPENSING' AND status !='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM bmr_lots WHERE bmr_no='".$row["bmr_no"]."' AND lot_no='".$row["lot_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["lot_size"] = $row1["lot_size"];
                    }
                }
                
                $sql1 = "SELECT * FROM bmr WHERE id='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["batch_size"] = $row1["batch_size"];
                        $row["mfr_no"] = $row1["mfr_no"];
                        $row["product_code"] = $row1["product_code"];
                        
                        $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row["product_name"] = $row2["product_name"];
                                $row["grade"] = $row2["grade"];
                                $row["generic_name"] = $row2["generic_name"];
                                $row["packing_style"] = $row2["packing_style"];
                                $row["dosage_type"] = $row2["dosage_type"];
                                $row["dosage_form"] = $row2["dosage_form"];
                                $row["excipient"] = $row2["excipient"];
                                $row["shelf_life"] = $row2["shelf_life"];
                                $row["color_used"] = $row2["color_used"];
                                $row["label_claim"] = $row2["label_claim"];
                            }
                        }
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_material WHERE no='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["material_name"] = $row2["material_name"];
                                $row1["grade"] = $row2["grade"];
                            }
                        }
                        $row1["materials"] = json_decode($row1["materials"]);
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingPreparations") {
        $output = Array();
        $sql1 = "SELECT * FROM bmr_lots WHERE status='start'";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $sql = "SELECT * FROM lot_stages WHERE status IN ('pending' || 'inprocess') AND bmr_no='".$row1["bmr_no"]."' AND lot_no='".$row1["lot_no"]."'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $row["lot_size"] = $row1["lot_size"];
                        
                        $sql1 = "SELECT * FROM bmr WHERE id='".$row["bmr_no"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $row["batch_size"] = $row1["batch_size"];
                                $row["mfr_no"] = $row1["mfr_no"];
                                $row["product_code"] = $row1["product_code"];
                                
                                $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row["product_name"] = $row2["product_name"];
                                        $row["grade"] = $row2["grade"];
                                        $row["generic_name"] = $row2["generic_name"];
                                        $row["packing_style"] = $row2["packing_style"];
                                        $row["dosage_type"] = $row2["dosage_type"];
                                        $row["dosage_form"] = $row2["dosage_form"];
                                        $row["excipient"] = $row2["excipient"];
                                        $row["shelf_life"] = $row2["shelf_life"];
                                        $row["color_used"] = $row2["color_used"];
                                        $row["label_claim"] = $row2["label_claim"];
                                    }
                                }
                            }
                        }
                        
                        $output1 = Array();
                        $sql1 = "SELECT * FROM bmr_material WHERE no='".$row["bmr_no"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row1["material_name"] = $row2["material_name"];
                                        $row1["grade"] = $row2["grade"];
                                    }
                                }
                                $row1["materials"] = json_decode($row1["materials"]);
                                $output1[] = $row1;
                            }
                        }
                        $row["materials"] = $output1;
                        
                        $output1 = Array();
                        $sql1 = "SELECT * FROM lot_stages WHERE bmr_no='".$row["bmr_no"]."' AND lot_no='".$row["lot_no"]."' AND stage !='DISPENSING'";
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
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getStageDetails") {
        $output = Array();
        $sql = "SELECT * FROM lot_stages WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM bmr WHERE id='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["mfr_no"] = $row1["mfr_no"];
                        $row["batch_size"] = $row1["batch_size"];
                        $row["lots"] = $row1["lots"];
                        $row["product_code"] = $row1["product_code"];
                    }
                }
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                        $row["packing_style"] = $row1["packing_style"];
                        $row["dosage_type"] = $row1["dosage_type"];
                        $row["dosage_form"] = $row1["dosage_form"];
                        $row["excipient"] = $row1["excipient"];
                        $row["shelf_life"] = $row1["shelf_life"];
                        $row["color_used"] = $row1["color_used"];
                        $row["label_claim"] = $row1["label_claim"];
                    }
                }
                // $row["data"] = json_decode($row["data"]);
                $details = json_decode($row["data"]);
                
                $procedures = Array();
                for ($i = 0; $i < count($details); $i++) {
                    $data = $details[$i];
                    if ($data->option == 'procedure') {
                        $procedures = $data->list;
                    }
                }
                
                for ($i = 0; $i < count($procedures); $i++) {
                    $procedure = $procedures[$i];
                    if ($procedure->status == 'pending') {
                        $row["procedure"] = $procedure;
                        break;
                    }
                }
                
                echo json_encode($row);
                break;
            }
        } else {
            echo "{}";
        }
    }

}

$conn->close();
?>