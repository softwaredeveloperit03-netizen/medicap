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


if($_GET["type"]=="getProducts") {
    $output = Array();
    $sql = "SELECT * FROM product WHERE status='active'";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){

            $output1 = Array();
            $sql1 = "SELECT * FROM manufacturing_process WHERE dosage_form='".$row["dosage_form"]."' AND step !='DISPENSING'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1["step"];
                }
            }
            $row["stages"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if($_GET["type"]=="getMaterials") {
    $output = Array();
	$sql = "SELECT * FROM material WHERE status='active'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "getClients") {
    $output = Array();
    $sql = "SELECT * FROM client WHERE status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if($_GET["type"]=="getEquipments") {
    $output = Array();
	$sql = "SELECT * FROM equipment WHERE status='active'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
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
	
	$sql = "INSERT INTO masterformula (mfr_no,product_code,ownership_type,client_name,batch_size, entry_by, entry_date, mf_id1, materials, stages) VALUES ('$mf_id','".$input["product_code"]."','".$input["ownership_type"]."', '".$input["client_name"]."','".$input["batch_size"]."','".$_GET["emp_id"]."','$entry_date',$mf_id1, '".json_encode($input["materials"])."', '".json_encode($input["stages"])."')"; 
	if($conn->query($sql)===TRUE) {
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
} else if ($_GET["type"] == "getMasterFormulaReports") {
    $output = Array();
    $sql = "SELECT * FROM masterformula";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $row["materials"] = json_decode($row["materials"]);
            $row["stages"] = json_decode($row["stages"]);

            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                    $row["dosage_form"] = $row1["dosage_form"];
                    $row["label_claim"] = $row1["label_claim"];
                    $row["shelf_life"] = $row1["shelf_life"];
                }
            }

            $materials = $row["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $material->material_name = $row1["material_name"];
                        $material->grade = $row1["grade"];
                    }
                }
                $materials[$i] = $material;
            }
            $row["materials"] = $materials;

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updatePendingMFR") {
    $sql = "UPDATE masterformula SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "updateCheckingMFR") {
    $sql = "UPDATE masterformula SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}


} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>