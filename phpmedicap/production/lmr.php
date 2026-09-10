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

if ($_GET["type"] == "getDosageForms") {
    $output = Array();
    $sql = "SELECT * FROM dosage_form";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = Array();
            $sql1 = "SELECT * FROM product WHERE dosage_form='".$row["dosage_form"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output2 = Array();
                    $sql2 = "SELECT * FROM lmr WHERE product_code='".$row1["product_code"]."' AND status='approve'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row2["materials"] = json_decode($row2["materials"]);
                            $row2["stages"] = json_decode($row2["stages"]);
                            $output2[] = $row2;
                        }
                    }
                    $row1["lots"] = $output2;
                    $output1[] = $row1;
                }
            }
            $row["products"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveLotPlanning") {
    $sql = "INSERT INTO lot_planning (dosage_form, product_code, lmr_no, lots, start_date, entry_by, entry_date) VALUES ('".$input["dosage_form"]."', '".$input["product_code"]."', '".$input["lmr_no"]."', '".$input["lot_no"]."', '".$input["start_date"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingLotPlannings") {
    $output = Array();
    $sql = "SELECT * FROM lot_planning WHERE status='pending'";
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
            $sql1 = "SELECT * FROM lmr WHERE id='".$row["lmr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["lot_size"] = $row1["lot_size"];
                    $row["unit"] = $row1["unit"];
                    $row["mfr_no"] = $row1["mfr_no"];
                    $row["materials"] = json_decode($row1["materials"]);
                    $row["stages"] = json_decode($row1["stages"]);
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getCheckedLotPlannings") {
    $output = Array();
    $sql = "SELECT * FROM lot_planning WHERE status='checked'";
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
            $sql1 = "SELECT * FROM lmr WHERE id='".$row["lmr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["lot_size"] = $row1["lot_size"];
                    $row["unit"] = $row1["unit"];
                    $row["mfr_no"] = $row1["mfr_no"];
                    $row["materials"] = json_decode($row1["materials"]);
                    $row["stages"] = json_decode($row1["stages"]);
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "checkLotPlanning") {
    $output = Array();
    $sql = "UPDATE lot_planning SET status='".$_GET["action"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "approveLotPlanning") {
    $output = Array();
    $sql = "UPDATE lot_planning SET status='".$_GET["action"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        if ($_GET["action"] == "approve") {
            for ($i = 0; $i < +$input["lots"]; $i++) {
                $id = 0;
                $sql = "SELECT IFNULL(MAX(id), 0) as id FROM lot";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    $id = $row["id"];
                    break;
                }
                $id++;
                $lot_no = "LOT".$id;
                $sql1 = "INSERT INTO lot (plan_no,dosage_form, product_code, lmr_no, lot_no, mfr_no, materials, entry_by, entry_date) VALUES ('".$_GET["id"]."','".$input["dosage_form"]."', '".$input["product_code"]."', '".$input["lmr_no"]."', '$lot_no', '".$input["mfr_no"]."', '".json_encode($input["materials"])."', '".$_GET["emp_id"]."', '$entry_date')";
                $stages = $input["stages"];
                if ($conn->query($sql1)) {
                    for ($j = 0; $j < count($stages); $j++) {
                        $stage = $stages[$j];
                        $sql1 = "INSERT INTO lot_stages (lot_no, stage, details) VALUES ('$lot_no', '".$stage["stage"]."', '".json_encode($stage["steps"])."')";
                        $conn->query($sql1);
                    }
                }
            }
        }
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getLotPlannings") {
    $output = Array();
    $sql = "SELECT * FROM lot_planning";
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
            $sql1 = "SELECT * FROM lmr WHERE id='".$row["lmr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["lot_size"] = $row1["lot_size"];
                    $row["unit"] = $row1["unit"];
                    $row["materials"] = json_decode($row1["materials"]);
                    $row["stages"] = json_decode($row1["stages"]);
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingStartLots") {
    $output = Array();
    $sql = "SELECT * FROM lot WHERE status='pending'";
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
                    $row["shelf_life"] = $row1["shelf_life"];
                    $row["label_claim"] = $row1["label_claim"];
                    $row["packing_style"] = $row1["packing_style"];
                }
            }
            $sql1 = "SELECT * FROM lmr WHERE id='".$row["lmr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["lot_size"] = $row1["lot_size"];
                    $row["unit"] = $row1["unit"];
                    $row["mfr_no"] = $row1["mfr_no"];
                    $row["materials"] = json_decode($row1["materials"]);
                }
            }

            $output1 = Array();
            $sql1 = "SELECT * FROM lot_Stages WHERE lot_no='".$row["lot_no"]."'";
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
} else if ($_GET["type"] == "startLot") {
    $sql = "UPDATE lot SET status='inprocess', start_by='".$_GET["emp_id"]."', start_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getInprocessLots") {
    $output = Array();
    $sql = "SELECT * FROM lot WHERE status='inprocess'";
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
                    $row["shelf_life"] = $row1["shelf_life"];
                    $row["label_claim"] = $row1["label_claim"];
                    $row["packing_style"] = $row1["packing_style"];
                }
            }
            $sql1 = "SELECT * FROM lmr WHERE id='".$row["lmr_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["lot_size"] = $row1["lot_size"];
                    $row["unit"] = $row1["unit"];
                    $row["mfr_no"] = $row1["mfr_no"];
                    $row["materials"] = json_decode($row1["materials"]);
                }
            }

            $output1 = Array();
            $sql1 = "SELECT * FROM lot_Stages WHERE lot_no='".$row["lot_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["details"] = json_decode($row["details"]);
                    $output1[] = $row1;
                }
            }
            $row["stages"] = $output1;

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