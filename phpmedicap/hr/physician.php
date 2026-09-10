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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);


// if ($_GET["type"] == "savePhysician") {
// 	$sql = "INSERT INTO physician (doctor_name,qualification,clinic_name,address,contact_no,email_id, password,entry_by,entry_date) VALUES ('".$input["doctor_name"]."','".$input["qualification"]."','".$input["clinic_name"]."','".$input["address"]."','".$input["contact_no"]."','".$input["email_id"]."','".$input["password"]."','".$_GET["emp_id"]."','$entry_date')";
// 	if($conn->query($sql)){
// 		echo "{\"status\":\"success\"}";
// 	} else {
// 		echo "{\"status\":\"".$conn->error."\"}";
// 	}
// } 
 if($_GET["type"]=="savePhysician") {
        $input = $_POST;
    	$target_dir = "../upload/physician/";
    // 	$emp_id = "";
    	$_POST["user"]= "false";
    	$_POST["checker"]= "false";
    	$_POST["approver"]= "false";
    
    	$password = encrypt('encrypt', $input["password"]);
    
    	if($_POST["telephone"]=="true"){
    		$_POST["telephone"]=true;
    	}
    	if($_POST["transport"]=="true"){
    		$_POST["transport"]=true;
    	}
    	if($_POST["cantine"]=="true"){
    		$_POST["cantine"]=true;
    	}
    
    	if(isset($_FILES["contract_agreement"]["name"])) {
        	$target_file = $target_dir."".$emp_id.basename($_FILES["contract_agreement"]["name"]);
        	$userphoto = $emp_id.basename($_FILES["contract_agreement"]["name"]);
        	$file3 = basename($_FILES["contract_agreement"]["name"]);
        	move_uploaded_file($_FILES["contract_agreement"]["tmp_name"], $target_file);
    	}

    	if(isset($_FILES["image"]["name"])) {
        	$target_file = $target_dir."".$emp_id.basename($_FILES["image"]["name"]);
        	$userphoto = $emp_id.basename($_FILES["image"]["name"]);
        	$file3 = basename($_FILES["image"]["name"]);
        	move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
    	}
    	$sql = "INSERT INTO physician (doctor_name,qualification,clinic_name,address,contact_no,email_id, password,contract_agreement,image,entry_by,entry_date) VALUES ('".$input["doctor_name"]."','".$input["qualification"]."','".$input["clinic_name"]."','".$input["address"]."','".$input["contact_no"]."','".$input["email_id"]."','".$input["password"]."','".$input["contract_agreement"]."','".$input["image"]."','".$_GET["emp_id"]."','$entry_date')";
    //	echo $sql;
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
else if ($_GET["type"] == "getPendingPhysicians") {
    $output = Array();
    $sql = "SELECT * FROM physician WHERE  status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updatePhysician") {
    $sql = "UPDATE physician SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPhysiciansLog") {
    $output = Array();
    $sql = "SELECT * FROM physician ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPhysicians") {
    $output = Array();
    $sql = "SELECT * FROM physician WHERE status='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}


 else {
    echo "{\"status\":\"invalid\"}";
}
}

$conn->close();
?>