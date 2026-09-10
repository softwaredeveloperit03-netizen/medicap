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
    
    if ($_GET["type"] == "getPendingBMRs") {
        $output = array();
        $sql = "SELECT b.*, DATE(b.start_date) as start_date, DATE(b.complete_date) as complete_date, p.product_name, p.grade, p.generic_name,
        p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='COMPLETED' 
        AND b.packing='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "transferMaterial") {
        $sql = "INSERT INTO bpr (user_no, product_code, bmr_no, batch_no, batch_size, yield_qty, yield_per, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$input["product_code"]."', '".$input["bmr_no"]."', '".$input["batch_no"]."', '".$input["batch_size"]."', '".$input["yield_qty"]."','".$input["yeild_per"]."','".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $sql = "UPDATE bmr SET packing='done' WHERE id='".$input["id"]."'";
            $conn->query($sql);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getTransferLog") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.dosage_form FROM bpr b LEFT JOIN product p ON b.product_code=p.product_code WHERE DATE(b.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY b.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingReceiving") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.dosage_form FROM bpr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND b.status='pending' GROUP BY b.id ORDER BY b.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "receiveProduct") {
        $sql = "UPDATE bpr SET status='receive', receive_by='".$_GET["emp_id"]."', receive_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getReceivingLog") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.dosage_form FROM bpr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND DATE(b.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY b.id ORDER BY b.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }


}

$conn->close();
?>