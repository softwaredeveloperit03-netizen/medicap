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


if ($_GET["type"] == "getChemicals") {
    $output = Array();
    $sql = "SELECT * FROM chemical_master WHERE status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getEquipments") {
    $output = Array();
    $sql = "SELECT DISTINCT(equipment_name) as equipment_name FROM equipments WHERE status='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if($_GET["type"]=="saveSolution") {
    $equipments = $input["equipments"];
    for ($i = 0; $i < count($equipments); $i++) {
        $equipment = $equipments[$i];
        $equipment["calibration"] = "";
        $equipment["calibration_due_date"] = "";
        $equipment["calibration_reference_id"] = "";
        $equipment["validation_due_date"] = "";
        $equipment["used_from"] = "";
        $equipment["used_to"] = "";
        $equipment["used_by"] = "";
        $equipment["cleaned_from"] = "";
        $equipment["cleaned_to"] = "";
        $equipment["cleaned_by"] = "";
        $equipment["room_temp"] = "";
        $equipment["temp_range"] = "";
        $equipments[$i] = $equipment;
    }
    $sql = "INSERT INTO solution (solution_name, strength,solution_type,molicular_wt,unit,preparation_time,procedures, contents,equipments, stages, entry_by, entry_date) VALUES ('".$input["solution_name"]."', '".$input["strength"]."','".$input["solution_type"]."','".$input["molicular_wt"]."','".$input["unit"]."', '".$input["preparation_time"]."','".$input["procedures"]."','".json_encode($input["contents"])."','".json_encode($equipments)."', '".json_encode($input["stages"])."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getSolutionLog") {
    $output = Array();
    $sql = "SELECT * FROM solution";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $row["stages"] = json_decode($row["stages"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getSolutionStages") {
    $output = Array();
    $sql = "SELECT * FROM solution";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $row["stages"] = json_decode($row["stages"]);
            $stages = $row["stages"];
            for ($i = 0; $i < count($stages); $i++) {
                $stage = $stages[$i];
                if ($stage->status !== 'approve') {
                    foreach ($stage as $key => $value) {
                        $row[$key] = $value;
                    }
                    $output[] = $row;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveStep") {
    $sql = "SELECT * FROM solution WHERE id='".$_GET["id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["stages"] = json_decode($row["stages"]);
            $stages = $row["stages"];
            for ($i = 0; $i < count($stages); $i++) {
                $stage = $stages[$i];
                if (+$stage->stage_no == +$_GET["stage_no"]) {
                    $stage->steps = $input;
                    $stage->status = 'inprocess';
                    $stage->step_by = $_GET["emp_id"];
                    $stage->step_date = $entry_date;
                }
                $stages[$i] = $stage;
            }
            $sql = "UPDATE solution SET stages = '".json_encode($stages)."' WHERE id='".$_GET["id"]."'";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"failed\"}";
            }
            break;
        }
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "saveStepDetails") {
    $sql = "SELECT * FROM solution WHERE id='".$_GET["id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["stages"] = json_decode($row["stages"]);
            $stages = $row["stages"];
            for ($i = 0; $i < count($stages); $i++) {
                $stage = $stages[$i];
                if (+$stage->stage_no == +$_GET["stage_no"]) {
                    $stage->steps = $input;
                    $stage->status = 'active';
                    $stage->details_by = $_GET["emp_id"];
                    $stage->details_date = $entry_date;
                }
                $stages[$i] = $stage;
            }
            $sql = "UPDATE solution SET stages = '".json_encode($stages)."' WHERE id='".$_GET["id"]."'";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"failed\"}";
            }
            break;
        }
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingSolutions") {
    $output = Array();
    $sql = "SELECT * FROM solution WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $row["stages"] = json_decode($row["stages"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateSolution") {
    $sql = "UPDATE solution SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getApprovedSolutions") {
    $output = Array();
    $sql = "SELECT * FROM solution WHERE status='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveSolutionPlan") {
    $sql = "INSERT INTO solution_preparation (solution_no, volume, unit, contents, equipments, entry_by, entry_date) VALUES ('".$input["solution_no"]."', '".$input["volume"]."', '".$input["unit"]."', '".json_encode($input["contents"])."', '".json_encode($input["equipments"])."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingSolutionPlans") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateSolutionPlan") {
    $sql = "UPDATE solution_preparation SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getSolutionPlansLog") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingDispensingRequests") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                    $row["stages"] = json_decode($row1["stages"]);
                    $stages = $row["stages"];
                    for ($i = 0; $i < count($stages); $i++) {
                        $stage = $stages[$i];
                        if ($stage->stage == 'DISPENSING') {
                            $row["dispensing_details"] = $stage;
                            break;
                        }
                    }
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "sendDispensingRequest") {
    $dispensing = $input["dispensing_details"];
    $dispensing["status"] = "pending";
    $dispensing["request_by"] = $_GET["emp_id"];
    $dispensing["request_date"] = $entry_date;
    $sql = "UPDATE solution_preparation SET dispensing='request', dispensing_details='".json_encode($dispensing)."' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getDispensingLog") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE status='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            
            if ($row["dispensing"] !== 'pending') {
                $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            } else {
                $row["dispensing_details"] = [];
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingDispensingAllocation") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='request'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "allocateDispensingPerson") {
    $input["allocate_by"] = $_GET["emp_id"];
    $input["allocate_date"] = $entry_date;
    $input["status"] = "pending";
    $input["clearance_status"] = "pending";
    $sql = "UPDATE solution_preparation SET dispensing_details='".json_encode($input)."', dispensing='allocate' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingDispensingLineClearance") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='allocate'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $dispensing_details = $row["dispensing_details"];
            if ($dispensing_details->status == 'pending' && $dispensing_details->clearance_status == 'pending') {
                $output[] = $row;
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveDispensingClearance") {
    $input["clearance_status"] = "inprocess";
    $input["clearance_request_by"] = $_GET["emp_id"];
    $input["clearance_request_date"] = $entry_date;
    $sql = "UPDATE solution_preparation SET dispensing_details='".json_encode($input)."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getInprocessDispensingLineClearance") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='allocate'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $dispensing_details = $row["dispensing_details"];
            if ($dispensing_details->status == 'pending' && $dispensing_details->clearance_status == 'inprocess') {
                $output[] = $row;
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateDispensingClearance") {
    $input["clearance_status"] = "approve";
    $input["clearance_by"] = $_GET["emp_id"];
    $input["clearance_date"] = $entry_date;
    $sql = "UPDATE solution_preparation SET dispensing_details='".json_encode($input)."', dispensing='inprocess' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getDispensingForm") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $dispensing_details = $row["dispensing_details"];
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveDispensingForm") {
    $input["dispensing_by"] = $_GET["emp_id"];
    $input["dispensing_date"] = $entry_date;
    $sql = "UPDATE solution_preparation SET dispensing_details='".json_encode($input)."', dispensing='active' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getActiveDispensingForm") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $dispensing_details = $row["dispensing_details"];
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "checkDispensingForm") {
    $input["dispensing_check_by"] = $_GET["emp_id"];
    $input["dispensing_check_date"] = $entry_date;
    $sql = "UPDATE solution_preparation SET dispensing_details='".json_encode($input)."', dispensing='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getCheckedDispensingForm") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='checked'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $dispensing_details = $row["dispensing_details"];
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "approveDispensingForm") {
    $input["dispensing_approve_by"] = $_GET["emp_id"];
    $input["dispensing_approve_date"] = $entry_date;
    $sql = "UPDATE solution_preparation SET dispensing_details='".json_encode($input)."', dispensing='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getApprovedDispensingForm") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $dispensing_details = $row["dispensing_details"];
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "receiveDispensingForm") {
    $input["dispensing_receive_by"] = $_GET["emp_id"];
    $input["dispensing_receive_date"] = $entry_date;
    $sql = "UPDATE solution_preparation SET dispensing_details='".json_encode($input)."', dispensing='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getpendingSolutionPreparation") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='receive' AND preparation='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                    $row["procedures"] = $row1["procedures"];
                    $row["stages"] = json_decode($row1["stages"]);
                    $stages = $row["stages"];
                    for ($i = 0; $i < count($stages); $i++) {
                        $stage = $stages[$i];
                        if ($stage->stage == 'PREPARATION') {
                            $row["preparation_details"] = $stage;
                            break;
                        }
                    }
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "startSolutionPreparation") {
    $input["clearance_status"] = "pending";
    $input["start_by"] = $_GET["emp_id"];
    $input["start_date"] = $entry_date;
    $sql = "UPDATE solution_preparation SET preparation_details='".json_encode($input)."', preparation='start' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getpendingSolutionPreparationClearance") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='receive' AND preparation='start'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                    $row["procedures"] = $row1["procedures"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $row["preparation_details"] = json_decode($row["preparation_details"]);
            $preparation_details = $row["preparation_details"];
            if ($preparation_details->status == 'pending' && $preparation_details->clearance_status == 'pending') {
                $output[] = $row;
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "savePreparationClearance") {
    $input["clearance_status"] = "inprocess";
    $input["clearance_request_by"] = $_GET["emp_id"];
    $input["clearance_request_date"] = $entry_date;
    $sql = "UPDATE solution_preparation SET preparation_details='".json_encode($input)."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getInprocessSolutionPreparationClearance") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='receive' AND preparation='start'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                    $row["procedures"] = $row1["procedures"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["equipments"] = json_decode($row["equipments"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $row["preparation_details"] = json_decode($row["preparation_details"]);
            $preparation_details = $row["preparation_details"];
            if ($preparation_details->status == 'pending' && $preparation_details->clearance_status == 'inprocess') {
                $output[] = $row;
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updatePreparationClearance") {
    $input["clearance_status"] = "approve";
    $input["clearance_by"] = $_GET["emp_id"];
    $input["clearance_date"] = $entry_date;
    $sql = "UPDATE solution_preparation SET preparation_details='".json_encode($input)."', preparation='inprocess' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getInprocessSolutionPreparation") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='receive' AND preparation='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                    $row["procedures"] = $row1["procedures"];
                    $row["equipments"] = json_decode($row1["equipments"]);
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $row["preparation_details"] = json_decode($row["preparation_details"]);
            $equipments = $row["equipments"];
            for ($i = 0; $i < count($equipments); $i++) {
                $equipment = $equipments[$i];
                $output2 = Array();
                $sql2 = "SELECT equipment_code FROM equipments WHERE equipment_name='".$equipment->equipment_name."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2[] = $row2;
                    }
                }
                $equipment->equipments = $output2;
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveSolutionPreparation") {
    $input["status"] = "active";
    $input["prepare_by"] = $_GET["emp_id"];
    $input["prepare_date"] = $entry_date;
    $sql = "UPDATE solution_preparation SET preparation_details='".json_encode($input)."', preparation='active' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getActiveSolutionPreparation") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='receive' AND preparation='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                    $row["procedures"] = $row1["procedures"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $row["preparation_details"] = json_decode($row["preparation_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "checkSolutionPreparation") {
    $input["status"] = $_GET["status"];
    $input["check_by"] = $_GET["emp_id"];
    $input["check_date"] = $entry_date;
    $sql = "UPDATE solution_preparation SET preparation_details='".json_encode($input)."', preparation='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getCheckedSolutionPreparation") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='receive' AND preparation='checked'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                    $row["procedures"] = $row1["procedures"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $row["preparation_details"] = json_decode($row["preparation_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "approveSolutionPreparation") {
    $input["status"] = $_GET["status"];
    $input["approve_by"] = $_GET["emp_id"];
    $input["approve_date"] = $entry_date;
    $sql = "UPDATE solution_preparation SET preparation_details='".json_encode($input)."', preparation='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPreparationLog") {
    $output = Array();
    $sql = "SELECT * FROM solution_preparation WHERE dispensing='receive' AND preparation='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM solution WHERE id='".$row["solution_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["solution_name"] = $row1["solution_name"];
                    $row["strength"] = $row1["strength"];
                    $row["solution_type"] = $row1["solution_type"];
                    $row["molicular_wt"] = $row1["molicular_wt"];
                    $row["unit"] = $row1["unit"];
                    $row["preparation_time"] = $row1["preparation_time"];
                    $row["procedures"] = $row1["procedures"];
                }
            }
            $row["contents"] = json_decode($row["contents"]);
            $row["dispensing_details"] = json_decode($row["dispensing_details"]);
            $row["preparation_details"] = json_decode($row["preparation_details"]);
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