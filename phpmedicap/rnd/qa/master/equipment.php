<?php
    require '../../../db.php';
    require '../../../token.php';
    require '../../../tcpdf/tcpdf.php';
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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);


    if ($_GET["type"] == "saveEquipment") {
    	$sql = "INSERT INTO rnd_equipment (user_no, equipment_code,equipment_name,make,equipment_type,capacity, min_capacity, max_capacity, department,section,purchase_date,installation_date,entry_by,entry_date, e_no1, model, equipment_sr_no, equipment_used, category, from_range, from_range_uom, to_range, to_range_uom) VALUES ('".$_GET["user_no"]."','".$input["equipment_code"]."','".$input["equipment_name"]."','".$input["make"]."','".$input["equipment_type"]."','".$input["capacity"]."', '".$input["min_capacity"]."', '".$input["max_capacity"]."','".$input["department"]."','".$input["section"]."','".$input["purchase_date"]."','".$input["installation_date"]."','".$_GET["emp_id"]."','".$entry_date."','$e_no1','".$input["model"]."','".$input["equipment_sr_no"]."', '".$input["equipment_used"]."', '".$input["category"]."', '".$input["from_range"]."', '".$input["from_range_uom"]."', '".$input["to_range"]."', '".$input["to_range_uom"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } else if ($_GET["type"] == "getEquipmentsLog") {
        $output = Array();
        $sql = "SELECT * FROM rnd_equipment WHERE user_no='".$_GET["user_no"]."' AND equipment_type LIKE '%".$_GET["category"]."%' AND equipment_name LIKE '%".$_GET["equipment_name"]."%' AND status LIKE '%".$_GET["status"]."%' AND department LIKE '%".$_GET["department_name"]."%' ORDER BY equipment_type, equipment_name, equipment_code";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[]= $row;
            }
        }
        echo json_encode($output);
    }

}

$conn->close();
?>