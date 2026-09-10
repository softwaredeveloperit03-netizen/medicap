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


if ($_GET["type"] == "savePlan") {
    $flag = 0;
    for ($i = 0; $i < count($input); $i++) {
        $data = $input[$i];
        $sql = "INSERT INTO batch_plan (dosage_form, product_code, batch_size, batches, entry_by, entry_date) VALUES ('".$data["dosage_form"]."', '".$data["product_code"]."', '".$data["batch_size"]."', '".$data["batches"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $flag = 1;
        } else {
            $flag = 0;
            break;
        }
    }
    if ($flag == 1) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
}/* else if ($_GET["type"] == "getPendingPlans") {
    $output = Array();
    $sql = "SELECT * FROM batch_plan WHERE status='pending'";
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
}*/ else if ($_GET["type"] == "updatePlan") {
    $sql = "UPDATE batch_plan SET status='approve', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "getPlansLog") {
    $output = Array();
    $sql = "SELECT * FROM batch_plan";
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
} else if ($_GET["type"] == "getPendingMicroPlans") {
    $output = array();
    $sql = "SELECT * FROM batch_plan";
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
} else if ($_GET["type"] == "getPendingPlans") {
    $output = array();
    $sql = "SELECT * FROM production_plan WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["dosage_form"] = $row1["dosage_form"];
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveBMRPlan") {
    $sql = "SELECT * FROM batch_formula WHERE user_no='".$_GET["user_no"]."' AND product_code='".$input["product_code"]."' AND batch_size='".$input["batch_size"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = array();
            $sql1 = "SELECT * FROM batch_materials WHERE no='".$row["id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            
            $batches = $input["batchList"];
            for ($i = 0; $i < count($batches); $i++) {
                $sql = "INSERT INTO bmr_plan (user_no,planning_no, product_code, batch_size, mfr_no, bom_no, materials, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$input["plan_no"]."','".$input["product_code"]."', '".$input["batch_size"]."', '".$row["mfr_no"]."', '".$row["id"]."', '".json_encode($output1)."', '".$_GET["emp_id"]."', '$entry_date')";
                $conn->query($sql);
            }
            echo "{\"status\":\"success\"}";
            
            $sql = "UPDATE production_plan SET status='done' WHERE id='".$input["id"]."'";
            $conn->query($sql);
            break;
        }
    } else {
        echo "{\"status\":\"failed\"}";
    }
}


}

$conn->close();
?>