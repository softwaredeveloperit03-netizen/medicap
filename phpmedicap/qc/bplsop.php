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

if ($_GET["type"] == "getMaterials") {
    $output = Array();
    $sql = "SELECT * FROM material WHERE status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getQcPersons") {
    $output = Array();
    $sql = "SELECT * FROM employee WHERE department='Quality Control' AND isuser='true'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingAllocation") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            if ($row["specification_no"] == "") {
                $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."' AND spec_type='Raw Material Specification' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["specification_no"] = $row1["specification_no"];
                        
                        $sql1 = "UPDATE sampling SET specification_no='".$row["specification_no"]."' WHERE id='".$row["id"]."'";
                        $conn->query($sql1);
                        break;
                    }
                }
            }
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "allocatePerson") {
    $sql = "UPDATE sampling SET sampling_person='".$input["sampling_person"]."', status='inprocess' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "saveSamplingRequest") {
    $sql1 = "SELECT * FROM specification WHERE material_code='".$input["material_code"]."' AND spec_type='Raw Material Specification' AND status='approve'";
    $result1 = $conn->query($sql1);
    if ($result1->num_rows > 0) {
        while ($row1 = $result1->fetch_assoc()) {
            $input["specification_no"] = $row1["specification_no"];
            break;
        }
    } else {
        $input["specification_no"] = "";
    }

    $sql = "INSERT INTO sampling (material_code, batch_no, containers, grn_no, grn_date, mfg_date, exp_date, sampling_person, request_by, request_date, specification_no) VALUES ('".$input["material_code"]."', '".$input["batch_no"]."', '".$input["containers"]."', '".$input["grn_no"]."', '".$input["grn_date"]."','".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["sampling_person"]."', '".$_GET["emp_id"]."', '$entry_date', '".$input["specification_no"]."')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getSamplingRecords") {
    $output = Array();
    $sql = "SELECT * FROM sampling";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingSamplings") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status = 'inprocess' AND sampling_person='".$_GET["emp_id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            if ($row["area_status"] == "complete") {
                $row["area_details"] = json_decode($row["area_details"]);
            }

            if ($row["clearance_status"] !== 'pending') {
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];

                        if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                            $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                            $conn->query($sql2);
                            $row["clearance_status"] = "complete";
                        }
                        break;
                    }
                }

                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["material_code"] = $row1["material_no"];
                        $temp["batch_no"] = $row1["batch_no"];
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
            }

            if ($row["specification_no"] == "") {
                $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."' AND spec_type='Raw Material Specification' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["specification_no"] = $row1["specification_no"];
                        
                        $sql1 = "UPDATE sampling SET specification_no='".$row["specification_no"]."' WHERE id='".$row["id"]."'";
                        $conn->query($sql1);
                        break;
                    }
                }
            }

            $sql1 = "SELECT * FROM specification WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["specification_no"] = $row1["specification_no"];
                    $row["composite_qty"] = +$row1["sample_qty"];
                    $row["unit"] = $row1["unit"];

                    $sql2 = "SELECT IFNULL(SUM(sample_qty), 0) as identication_qty FROM spec_tests WHERE specification_no='".$row1["specification_no"]."' AND test='Identification'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["identication_qty"] = +$row2["identication_qty"];
                        }
                    } else {
                        $row["identication_qty"] = 0;
                    }
                    $row["actual_indentification"] = number_format(+$row["identication_qty"] * 2, 2);
                    $row["actual_composite"] = number_format(+$row["composite_qty"] * 2, 2);
                    break;
                }
            }

            if ($row["specification_no"] == "") {
                $row["current_status"] = "Specification not Available";
            } else if ($row["area_status"] == 'pending') {
                $row["current_status"] = 'Area Checkpoints';
            } else if ($row["area_status"] == 'complete' && $row["clearance_status"] == 'pending') {
                $row["current_status"] = 'Line Clearance';
            } else if ($row["area_status"] == 'complete' && $row["clearance_status"] == 'inprocess') {
                $row["current_status"] = 'Line Clearance';
            } else if ($row["clearance_status"] == 'complete' && $row["area_status"] == 'complete' && $row["sample_status"] == 'pending') {
                $row["current_status"] = 'Sampling Information';
                $output1 = Array();
                for ($i = 0; $i < +$row["containers"]; $i++) {
                    $temp = Array();
                    $temp['container_no'] = $i + 1;
                    $temp['identication_qty'] = +$row["identication_qty"];
                    $temp['composite_qty'] = +$row["composite_qty"];
                    $temp['status'] = "pending";
                    $output1[] = $temp;
                }
                $row["container_details"] = $output1;
            }

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingAreaCheckpoints") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status = 'inprocess' AND sampling_person='".$_GET["emp_id"]."' AND area_status IN ('pending', 'inprocess')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            if ($row["area_status"] !== "pending") {
                $row["area_details"] = json_decode($row["area_details"]);
            }

            if ($row["specification_no"] == "") {
                $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."' AND spec_type='Raw Material Specification' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["specification_no"] = $row1["specification_no"];
                        
                        $sql1 = "UPDATE sampling SET specification_no='".$row["specification_no"]."' WHERE id='".$row["id"]."'";
                        $conn->query($sql1);
                        break;
                    }
                }
            }

            if ($row["specification_no"] == "") {
                $row["current_status"] = "Specification not Available";
            } else if ($row["area_status"] == 'pending') {
                $row["current_status"] = 'Area Checkpoints';
            } else if ($row["area_status"] !== 'complete') {
                $row["current_status"] = 'Area Checkpoints';
            }

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingLineClearance") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status = 'inprocess' AND sampling_person='".$_GET["emp_id"]."' AND area_status='complete' AND (clearance_status='pending' OR clearance_status='inprocess')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $row["area_details"] = json_decode($row["area_details"]);

            if ($row["clearance_status"] !== 'pending') {
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];

                        if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                            $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                            $conn->query($sql2);
                            $row["clearance_status"] = "complete";
                        }
                        break;
                    }
                }

                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["material_code"] = $row1["material_no"];
                        $temp["batch_no"] = $row1["batch_no"];
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
            }

            $row["current_status"] = 'Line Clearance';

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingSamplingForm") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status = 'inprocess' AND sampling_person='".$_GET["emp_id"]."' AND clearance_status='complete' AND sample_status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $row["area_details"] = json_decode($row["area_details"]);

            if ($row["clearance_status"] !== 'pending') {
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];

                        if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                            $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                            $conn->query($sql2);
                            $row["clearance_status"] = "complete";
                        }
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
            }

            $sql1 = "SELECT * FROM specification WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["specification_no"] = $row1["specification_no"];
                    $row["composite_qty"] = +$row1["sample_qty"];
                    $row["unit"] = $row1["unit"];

                    $sql2 = "SELECT IFNULL(SUM(sample_qty), 0) as identication_qty FROM spec_tests WHERE specification_no='".$row1["specification_no"]."' AND test='Identification'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["identication_qty"] = +$row2["identication_qty"];
                        }
                    } else {
                        $row["identication_qty"] = 0;
                    }
                    $row["actual_indentification"] = number_format(+$row["identication_qty"] * 2, 2);
                    $row["actual_composite"] = number_format(+$row["composite_qty"] * 2, 2);
                    break;
                }
            }

            $output1 = Array();
            for ($i = 0; $i < +$row["containers"]; $i++) {
                $temp = Array();
                $temp['container_no'] = $i + 1;
                $temp['identication_qty'] = +$row["identication_qty"];
                $temp['composite_qty'] = +$row["composite_qty"];
                $temp['status'] = "pending";
                $output1[] = $temp;
            }
            $row["container_details"] = $output1;

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "callforclearance") {
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
        
    $sql = "INSERT INTO lineclearance (clearance_no, department,section,activity,material_no,grn_no,batch_no, checkpoints,request_by,request_date, cid) VALUES ('$clearance_no','".$_GET["department"]."','Sampling','".$input["material_type"]." Sampling','".$input["material_code"]."','".$input["grn_no"]."','".$input["batch_no"]."','".json_encode($input["checkpoints"])."','".$_GET["emp_id"]."','$entry_date', $c_id)";
    if ($conn->query($sql)) {
        $sql = "UPDATE sampling SET clearance_no='".$clearance_no."', clearance_status='inprocess' WHERE id='".$input["id"]."'";
        $conn->query($sql);
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "saveAreaCheckpoints") {
    $sql = "UPDATE sampling SET area_details='".json_encode($input)."', area_status='complete' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getLaminars") {
    $output = Array();
    $sql = "SELECT * FROM equipment";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveSamplingInfo") {
    $sql = "UPDATE sampling SET sampling_details='".json_encode($input)."', sample_status='complete', status='active', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getActiveSamplings") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $temp = Array();
            $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                    $temp["area_cleaned"] = $row1["area_cleaned"];
                    $temp["product_traces"] = $row1["product_traces"];
                    $temp["temperature"] = $row1["temperature"];
                    $temp["humidity"] = $row1["humidity"];
                    $temp["entry_by"] = $row1["entry_by"];
                    $temp["entry_date"] = $row1["entry_date"];
                    $temp["status"] = $row1["status"];

                    if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                        $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                        $conn->query($sql2);
                        $row["clearance_status"] = "complete";
                    }
                    break;
                }
            }

            $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $temp["material_code"] = $row1["material_no"];
                    $temp["batch_no"] = $row1["batch_no"];
                    break;
                }
            }
            $row["clearance_details"] = $temp;

            $row["area_details"] = json_decode($row["area_details"]);
            $row["sample_details"] = json_decode($row["sampling_details"]);

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateActiveSampling") {
    $sql = "UPDATE sampling SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getCheckedSamplings") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status='checked'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $temp = Array();
            $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                    $temp["area_cleaned"] = $row1["area_cleaned"];
                    $temp["product_traces"] = $row1["product_traces"];
                    $temp["temperature"] = $row1["temperature"];
                    $temp["humidity"] = $row1["humidity"];
                    $temp["entry_by"] = $row1["entry_by"];
                    $temp["entry_date"] = $row1["entry_date"];
                    $temp["status"] = $row1["status"];

                    if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                        $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                        $conn->query($sql2);
                        $row["clearance_status"] = "complete";
                    }
                    break;
                }
            }

            $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $temp["material_code"] = $row1["material_no"];
                    $temp["batch_no"] = $row1["batch_no"];
                    break;
                }
            }
            $row["clearance_details"] = $temp;

            $row["area_details"] = json_decode($row["area_details"]);
            $row["sample_details"] = json_decode($row["sampling_details"]);

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateCheckedSampling") {
    $sql = "UPDATE sampling SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";

        $t_no1 = 0;
        $t_no = "";
        $sql = "SELECT IFNULL(MAX(t_no1), 0) as t_no1 FROM testing";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $t_no1 = $row["t_no1"];
            }
        }
        $t_no1++;
        $no = strlen($t_no1);
        if ($no == 1) {
            $t_no = "SBT-00".$t_no1;
        } else if ($no == 2) {
            $t_no = "SBT-0".$t_no1;
        } else if ($no >= 3) {
            $t_no = "SBT-".$t_no1;
        }

        $ar_no = "AR-".$t_no1;

        $sql = "INSERT INTO testing (testing_no, sampling_no, grn_no, ar_no, specification_no, material_code, entry_by, entry_date, t_no1) VALUES ('$t_no','".$input["sampling_no"]."', '".$input["grn_no"]."','$ar_no','".$input["specification_no"]."','".$input["material_code"]."','".$_GET["emp_id"]."','".$entry_date."','$t_no1')";
        if ($conn->query($sql)) {
            $sql = "UPDATE sampling SET testing_status='active' WHERE id='".$_GET["id"]."'";
            $conn->query($sql);
        }
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getSamplings") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status NOT IN ('pending', 'inprocess')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $temp = Array();
            $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                    $temp["area_cleaned"] = $row1["area_cleaned"];
                    $temp["product_traces"] = $row1["product_traces"];
                    $temp["temperature"] = $row1["temperature"];
                    $temp["humidity"] = $row1["humidity"];
                    $temp["entry_by"] = $row1["entry_by"];
                    $temp["entry_date"] = $row1["entry_date"];
                    $temp["status"] = $row1["status"];

                    if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                        $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                        $conn->query($sql2);
                        $row["clearance_status"] = "complete";
                    }
                    break;
                }
            }

            $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $temp["material_code"] = $row1["material_no"];
                    $temp["batch_no"] = $row1["batch_no"];
                    break;
                }
            }
            $row["clearance_details"] = $temp;

            $row["area_details"] = json_decode($row["area_details"]);
            $row["sample_details"] = json_decode($row["sampling_details"]);

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