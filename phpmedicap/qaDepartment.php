<?php




//   ini_set('display_errors', 1);
// error_reporting(E_ALL);

    require 'db.php';
    require 'token.php';
    require 'tcpdf/tcpdf.php';
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

    $sql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR) VALUES ('FRONTEND', '".$token."', '".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);

    if($_GET["type"]=="getManufacturers") {
	    $sql = "SELECT * FROM clientcompany";
	    $result = $conn->query($sql);
	    $output = array();
	    if($result->num_rows > 0){
		    while($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getProducts"){
	$sql = "SELECT * FROM product";
	$result = $conn->query($sql);
	$output = array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}}
else if($_GET["type"]=="getQAPersons"){
	$sql = "SELECT * FROM employee";
	$result = $conn->query($sql);
	$output = array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}}
else if($_GET["type"]=="getProducts"){
	$sql = "SELECT * FROM product";
	$result = $conn->query($sql);
	$output = array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
} else if($_GET["type"]=="getProductsByDosage"){
	$sql = "SELECT * FROM product WHERE dosage_form='".$_GET["dosage_form"]."' AND status='active'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $output1 = Array();
		    $sql1 = "SELECT * FROM batch_formula WHERE product_code='".$row["product_code"]."' AND status='approve'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $output2 = Array();
		            $sql2 = "SELECT * FROM mf_material WHERE mf_no='".$row1["mf_no"]."'";
        		    $result2 = $conn->query($sql2);
        		    if ($result2->num_rows > 0) {
        		        while ($row2 = $result2->fetch_assoc()) {
        		            $sql3 = "SELECT * FROM material WHERE material_code='".$row2["material_code"]."'";
                		    $result3 = $conn->query($sql3);
                		    if ($result3->num_rows > 0) {
                		        while ($row3 = $result3->fetch_assoc()) {
                		            $row2["material_name"] = $row3["material_name"];
                		            $row2["grade"] = $row3["grade"];
                		        }
                		    }
                		    $std_qty = $row2["qty"] * $row1["batch_size"];
                            if ($row2["unit"] == 'mg') {
                                $row2["std_qty"] = number_format(($std_qty / 1000000), 2);
                                $row2["std_unit"] = 'kg';
                            } else {
                                $row2["std_unit"] = $row2["unit"];
                            }
        		            $output2[] = $row2;
        		        }
        		    }
        		    $row1["materials"] = $output2;
        		    
        		    $output2 = Array();
		            $sql2 = "SELECT * FROM mf_equipments WHERE mfr_no='".$row1["mf_no"]."'";
        		    $result2 = $conn->query($sql2);
        		    if ($result2->num_rows > 0) {
        		        while ($row2 = $result2->fetch_assoc()) {
        		            $output2[] = $row2;
        		        }
        		    }
        		    $row1["equipments"] = $output2;
		            $output1[] = $row1;
		        }
		    }
		    $row["batches"] = $output1;
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getApprovedProducts"){
	$sql = "SELECT * FROM product WHERE status='Approved'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getPendingProducts"){
	$sql = "SELECT * FROM product WHERE status='pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingApprovalProducts"){
	$sql = "SELECT * FROM product WHERE isapprove='No' and ischeck='Yes'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="saveProduct"){
	$target_dir = "productPermission/";
	$file1 = "";
	$file2 = "";
	$file3 = "";
	$file4 = "";
	if(isset($_FILES["productLic"]["name"])){
		$target_file = $target_dir . basename($_FILES["productLic"]["name"]);
		$file1 = basename($_FILES["productLic"]["name"]);
		move_uploaded_file($_FILES["productLic"]["tmp_name"], $target_file);
	}
	if(isset($_FILES["FSC"]["name"])){
		$target_file = $target_dir . basename($_FILES["FSC"]["name"]);
		$file2 = basename($_FILES["FSC"]["name"]);
		move_uploaded_file($_FILES["FSC"]["tmp_name"], $target_file);
	}
	
	if(isset($_FILES["Copp"]["name"])){
		$target_file = $target_dir . basename($_FILES["Copp"]["name"]);
		$file3 = basename($_FILES["Copp"]["name"]);
		move_uploaded_file($_FILES["Copp"]["tmp_name"], $target_file);
	}
	
	if(isset($_FILES["Artwork"]["name"])){
		$target_file = $target_dir . basename($_FILES["Artwork"]["name"]);
		$file4 = basename($_FILES["Artwork"]["name"]);
		move_uploaded_file($_FILES["Artwork"]["tmp_name"], $target_file);
	}
	
	$sql = 'INSERT INTO product (product_code,product_name,generic_name,packing_style, dosage_type, dosage_form, manufactured_under,
	manufactured_for, color_used,product_lic,fsc,copp,artwork,manufactured_type,entry_by,entry_date, excipient, label_claim, grade) VALUES ("'.$_POST["product_code"].'","'.$_POST["product_name"].'",
	"'.$_POST["generic_name"].'","'.$_POST["packing_style"].'","'.$_POST["dosage_type"].'","'.$_POST["dosage_form"].'",
	"'.$_POST["manufactured_under"].'","'.$_POST["manufactured_for"].'","'.$_POST["color_used"].'","'.$file1.'","'.$file2.'",
	"'.$file3.'","'.$file4.'","'.$_POST["manufactured_type"].'","'.$_GET["emp_id"].'","'.$entry_date.'", "'.$_POST["excipient"].'", "'.$_POST["label_claim"].'", "'.$_POST["grade"].'")';
	if($conn->query($sql)===TRUE){
		$api = json_decode($_POST["api"], true);
		for ($i = 0; $i < count($api); $i++) {
			$data = $api[$i];
			$sql1 = "INSERT INTO product_api (product_code, api, molicular_wt, strength, unit, grade, equivalent_to, equivalent_molicular, factor) VALUES ('".$_POST["product_code"]."', '".$data["api"]."', ".$data["molicular_wt"].", '".$data["strength"]."', '".$data["unit"]."', '".$data["grade"]."', '".$data["equivalent_to"]."', ".$data["equivalent_molicular"].", ".$data["factor"].")";
			$conn->query($sql1);
		}
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"falied\"}";
	}
}
else if($_GET["type"]=="addTest"){
    $sql = "SELECT IFNULL(MAX(t_no1), 0) as t_no1 FROM test";
    $t_no1 = 0;
    $t_no = "";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $t_no1 = $row["t_no1"];
            break;
        }
    }
    $t_no1++;
    $no = strlen($t_no1);
    if ($no == 1) {
        $t_no = "SBT00".$t_no1;
    } else if ($no == 2) {
        $t_no = "SBT0".$t_no1;
    } else if ($no >= 3) {
        $t_no = "SBT".$t_no1;
    }
	$sql = "INSERT INTO test (test_no, classification, dosage_form, test,entry_by,entry_date, status, t_no1) VALUES ('$t_no','".$input["classification"]."','".$input["dosage_form"]."','".$input["test"]."','".$_GET["emp_id"]."','".$entry_date."', 'active','$t_no1')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"error\"}";
	}
}
else if ($_GET["type"]=="addSubtest") {
    $sql = "SELECT IFNULL(MAX(t_no1), 0) as t_no1 FROM subtest";
    $t_no1 = 0;
    $t_no = "";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $t_no1 = $row["t_no1"];
            break;
        }
    }
    $t_no1++;
    $no = strlen($t_no1);
    if ($no == 1) {
        $t_no = "SBST00".$t_no1;
    } else if ($no == 2) {
        $t_no = "SBT0".$t_no1;
    } else if ($no >= 3) {
        $t_no = "SBST".$t_no1;
    }
    $sql = "INSERT INTO subtest (classification, dosage_form, test, subtest, entry_by, entry_date) VALUES ('".$input["classification"]."','".$input["dosage_form"]."','".$input["test"]."','".$input["subtest"]."','".$_GET["emp_id"]."','".$entry_date."')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if($_GET["type"]=="getSubtests"){
	$sql = "SELECT * FROM subtest";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getTests"){
	$sql = "SELECT * FROM test";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getTests1"){
	$sql = "SELECT * FROM test WHERE status='active' AND classification='".$_GET["classification"]."' AND dosage_form='".$_GET["dosage_form"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getSubtests1"){
	$sql = "SELECT * FROM subtest WHERE status='active' AND classification='".$_GET["classification"]."' AND dosage_form='".$_GET["dosage_form"]."' AND test='".$_GET["test"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getPendingTests"){
	$sql = "SELECT * FROM test WHERE status='pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingSubtests"){
	$sql = "SELECT * FROM subtest WHERE status='pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="addUnit"){
	$sql = "INSERT INTO unit (unit,entry_by,entry_date) VALUES ('".$_GET["unit"]."','".$_GET["emp_id"]."','".$entry_date."')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getUnits"){
	$sql = "SELECT * FROM unit";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getApprovedUnits"){
	$sql = "SELECT * FROM unit WHERE status='active'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getGrades"){
	$sql = "SELECT * FROM grade WHERE status='active'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="addGrade"){
	$sql = "INSERT INTO grade (grade,entry_by,entry_date) VALUES ('".$_GET["grade"]."','".$_GET["emp_id"]."','$entry_date')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="addMaterial"){
	$input = json_decode(file_get_contents('php://input'),true);
	if(!isset($input["material_subtype"])) {
		$input["material_subtype"] = "";
	}
	if(!isset($input["cas_name"])) {
		$input["cas_name"] = "";
	}
	if(!isset($input["upac_name"])) {
		$input["upac_name"] = "";
	}
	if(!isset($input["grade"])) {
		$input["grade"] = "";
	}
	$material_type = $input["material_type"];
	$value1 = $material_type[0];
	$material_subtype = $input["material_subtype"];
	$value2 = $material_subtype[0];
	$material_name = $input["material_name"];
	$value3 = $material_name[0] . $material_name[1];
	
	$m_no = "";
	$m_id1 = 0;
	$input["upac_name"] = str_replace("'","\'",$input["upac_name"]);
	$sql = "SELECT IFNULL(MAX(m_id1), 0) as m_id1 FROM material WHERE material_type='".$input["material_type"]."' AND material_subtype='".$input["material_subtype"]."'";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
	    while ($row = $result->fetch_assoc()) {
	        $m_id1 = $row["m_id1"];
	        break;
	    }
	}
	$m_id1++;
	$val1 = (int)(strlen($m_id1));
	if ($val1 ==1) {
	    $m_no = $value1."".$value2."".$value3."00".$m_id1;
	} else if ($val1 ==2) {
	    $m_no = $value1."".$value2."".$value3."0".$m_id1;
	} else if ($val1 >= 3) {
	    $m_no = $value1."".$value2."".$value3."".$m_id1;
	}
	$m_no = strtoupper($m_no);
	$sql = "INSERT INTO material (material_type,material_subtype,material_code,material_name,cas_name,upac_name,unit,grade,material_nature,entry_by,entry_date, m_id1) VALUES ('".$input["material_type"]."', '".$input["material_subtype"]."','".$m_no."','".$input["material_name"]."','".$input["cas_name"]."','".$input["upac_name"]."','".$input["unit"]."','".$input["grade"]."','".$input["material_nature"]."','".$_GET["emp_id"]."','$entry_date','$m_id1')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getMaterials"){
	$sql = "SELECT * FROM material";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
} else if($_GET["type"]=="getApprovedMaterials") {
    $output = Array();
	$sql = "SELECT * FROM material WHERE status='active'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getPendingMaterials"){
	$sql = "SELECT * FROM material WHERE status='pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="addEquipment"){
	$input = json_decode(file_get_contents('php://input'),true);
	$e_no = '';
	$e_no1 = 0;
	$dept_code = '';
	$sql = "SELECT IFNULL(MAX(e_no1), 0) as e_no1 FROM equipment WHERE department='".$input["department"]."'";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
	    while ($row = $result->fetch_assoc()) {
	        $e_no1 = $row["e_no1"];
	        break;
	    }
	}
	
	$sql = "SELECT department_code FROM department WHERE department_name='".$input["department"]."'";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
	    while ($row = $result->fetch_assoc()) {
	        $dept_code = $row["department_code"];
	        break;
	    }
	}
	
	$e_no1++;
	if (strlen($e_no1) == 1) {
	    $e_no = "SBP/".$dept_code."/".$input["equipment_code"]."/0".$e_no1;
	}else if (strlen($e_no1) >= 2) {
	    $e_no = "SBP/".$dept_code."/".$input["equipment_code"]."/".$e_no1;
	}
	$sql = "INSERT INTO equipment (equipment_code,equipment_name,make,equipment_type,capacity, min_capacity, max_capacity, department,section,purchase_date,installation_date,entry_by,entry_date, e_no1, model, equipment_sr_no, equipment_used, calibration, daily, fourthnightly, monthly, quarterly, half_yearly, yearly, category) VALUES ('".$e_no."','".$input["equipment_name"]."','".$input["make"]."','".$input["equipment_type"]."','".$input["capacity"]."', '".$input["min_capacity"]."', '".$input["max_capacity"]."','".$input["department"]."','".$input["section"]."','".$input["purchase_date"]."','".$input["installation_date"]."','".$_GET["emp_id"]."','".$entry_date."','$e_no1','".$input["model"]."','".$input["equipment_sr_no"]."', '".$input["equipment_used"]."', '".$input["calibration"]."', '".$input["daily"]."', '".$input["fourthnightly"]."', '".$input["monthly"]."', '".$input["quarterly"]."', '".$input["half_yearly"]."', '".$input["yearly"]."', '".$input["category"]."')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if ($_GET["type"]=="approveEquipment") {
    $sql = "UPDATE equipment SET status='active' WHERE id='".$_GET["equipment_id"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if($_GET["type"]=="getEquipments"){
	$sql = "SELECT * FROM equipment WHERE status='active'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getApprovedEquipments"){
// 	 $sql = "SELECT * FROM equipment WHERE status='active'";
	 $sql = "SELECT * FROM equipment WHERE status='approve'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getDistinctEquipments"){
	$sql = "SELECT equipment_name FROM equipment WHERE status='active' GROUP BY equipment_name";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getEquipmentID"){
	$sql = "SELECT equipment_code FROM equipment WHERE status='active' AND equipment_name='".$_GET["equipment_name"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getPendingEquipments"){
	$sql = "SELECT * FROM equipment WHERE status IN ('pending','rejected')";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="addTrainer"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "INSERT INTO externaltrainer (trainer_name,address,gender,qualification,organisation,mobile_no,email,expertSkills,approver,created_by,status) VALUES ('".$input["trainer_name"]."','".$input["address"]."','".$input["gender"]."','".$input["qualification"]."','".$input["organisation"]."','".$input["mobile_no"]."','".$input["email"]."','".$input["expertSkills"]."','".$input["approver"]."','".$input["created_by"]."','pending')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="getTrainers"){
	$sql = "SELECT * FROM externaltrainer WHERE status='active'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingTrainers"){
	$sql = "SELECT * FROM externaltrainer WHERE status IN ('pending','rejected')";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="addBatch"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "INSERT INTO batchsize (batch_size,unit,approver,created_by,status) VALUES ('".$input["batch_size"]."','".$input["unit"]."','".$input["approver"]."','".$input["created_by"]."','pending')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="getBatches"){
	$sql = "SELECT * FROM batchsize";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="addMasterFormulaRecord"){
	$input = json_decode(file_get_contents('php://input'),true);
	if($input["preparation_date"]!=""){
		$temp = explode('/',$input["preparation_date"]);
		$input["preparation_date"] = $temp[2]."-".$temp[0]."-".$temp[1];
	}
	if($input["last_review_date"]!=""){
		$temp = explode('/',$input["last_review_date"]);
		$input["last_review_date"] = $temp[2]."-".$temp[0]."-".$temp[1];
	}
	$sql = "INSERT INTO masterformula (MRF_no,version_no,product,preparation_date,label_claim,last_review_date,dosage_form,MRF_for,shelf_life,product_code,MRF_type,approver,created_by,status) VALUES ('".$input["MRF_no"]."','".$input["version_no"]."','".$input["product"]."','".$input["preparation_date"]."','".$input["label_claim"]."','".$input["last_review_date"]."','".$input["dosage_form"]."','".$input["MRF_for"]."','".$input["shelf_life"]."','".$input["product_code"]."','".$input["MRF_type"]."','".$input["approver"]."','".$input["created_by"]."','pending')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="getMasterFormula"){
	$sql = "SELECT * FROM masterformula";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingDamageMaterials"){
    $output = Array();
	$sql = "SELECT * FROM material_received where damage_status='inprocess'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    
		    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["material_name"] = $row1["material_name"];
		            $row["material_type"] = $row1["material_type"];
		            $row["material_grade"] = $row1["grade"];
		        }
		    }
		    
			$sql1 = "SELECT * FROM damage_inspection WHERE material_code='".$row["material_code"]."' AND receiving_no='".$row["receiving_no"]."' AND qa_status='pending'";
			$result1 = $conn->query($sql1);
			$output1 = Array();
			if($result1->num_rows > 0){
				while($row1 = $result1->fetch_assoc()){
					$output1[] = $row1;
				}
			}
			$row['damage_report'] = $output1; 
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="saveDamageInspection") {
	$flag = 0;
    $input = json_decode(file_get_contents('php://input'),true);
	$data = $input["containerweighing"];
	for($i=0;$i<$input["total_damage"];$i++) {
		$sql = "UPDATE damage_inspection SET status='active',qa_remark='".$data["remark".$i]."',qa_status='".$data["status".$i]."',seperate_grn='".$data["seperate_grn".$i]."',qa_entry_by='".$_GET["emp_id"]."',qa_entry_date='$entry_date' WHERE material_code='".$input["material_code"]."' AND receiving_no='".$input["receiving_no"]."' AND container_no='".$data["container".$i]."'";
		if($conn->query($sql)===TRUE){
			$flag = 0;
		} else {
			$flag = 1;
			break;
		}
	}

    if($flag == 0) {
		echo "{\"status\":\"success\"}";
		
		$sql = "SELECT qa_status FROM damage_inspection WHERE material_code='".$input["material_code"]."' AND receiving_no='".$input["receiving_no"]."'";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
		    while ($row = $result->fetch_assoc()) {
		        if ($row["qa_status"] == "reject") {
		            $flag = 1;
		            break;
		        }
		    }
		}
		if ($flag == 0) {
		    $sql = "UPDATE material_received SET damage_status='active' WHERE material_code='".$input["material_code"]."' AND receiving_no='".$input["receiving_no"]."'";
		    $conn->query($sql);
		}
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getLineClearanceRequest"){
	$sql = "SELECT * FROM lineclearance WHERE status='inprocess'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_no"]."'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["material_name"] = $row1["material_name"];
		            $row["material_grade"] = $row1["grade"];
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
}
else if($_GET["type"]=="saveCleaningForm"){
    error_reporting(0);
	if($input["department"]=="Quality Control" && $input["section"]=="Sampling") {
    	$sql = "UPDATE lineclearance SET equipment_id='".$input["equipment_id"]."', area_cleaned='".$input["area_cleaned"]."', product_traces='".$input["product_traces"]."', sanitization_done='".$input["sanitization_done"]."', sanitization_time='".$input["sanitization_time"]."', temperature='".$input["temperature"]."', humidity='".$input["humidity"]."', status_label='".$input["status_label"]."', balance_cleaned='".$input["balance_cleaned"]."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date', status='active', remark='".$input["remark"]."' WHERE id='".$input["id"]."'";
    	echo $sql;
    	if($conn->query($sql)===TRUE){
    		echo "{\"status\":\"success\"}";
    		$sql1 = "SELECT * FROM lineclearance WHERE id='".$input["id"]."'";
    		$result1 = $conn->query($sql1);
    		if ($result1->num_rows > 0) {
    		    while ($row1 = $result1->fetch_assoc()) {
    		        $sql = "UPDATE sampling SET clearance_status='complete' WHERE clearance_no='".$row1["clearance_no"]."'";
            	echo $sql;
            		$conn->query($sql);
    		    }
    		}
    	} else {
    	    echo "{\"status\":\"failed\"}";
    	}
	} else if($input["department"]=="Store" && $input["section"]=="Dispensing") {
	    $sql = "UPDATE lineclearance SET equipment_id='".$input["equipment_id"]."', area_cleaned='".$input["area_cleaned"]."', product_traces='".$input["product_traces"]."', sanitization_done='".$input["sanitization_done"]."', sanitization_time='".$input["sanitization_time"]."', temperature='".$input["temperature"]."', humidity='".$input["humidity"]."', status_label='".$input["status_label"]."', balance_cleaned='".$input["balance_cleaned"]."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date', status='active', remark='".$input["remark"]."' WHERE id='".$input["id"]."'";
    	if($conn->query($sql)===TRUE){
    		echo "{\"status\":\"success\"}";
    		$sql = "UPDATE batch_planning SET dispensing_lineclearance='active' WHERE clearance_no='".$input["clearance_no"]."'";
    		$conn->query($sql);
    	} else {
    	    echo "{\"status\":\"".$conn->error."\"}";
    	}
	} else {
		$sql = "UPDATE lineclearance SET checkpoints ='".json_encode($input['checkpoints'])."' , equipment_id='".$input["equipment_id"]."', area_cleaned='".$input["area_cleaned"]."', product_traces='".$input["product_traces"]."', sanitization_done='".$input["sanitization_done"]."', sanitization_time='".$input["sanitization_time"]."', temperature='".$input["temperature"]."', humidity='".$input["humidity"]."', status_label='".$input["status_label"]."', balance_cleaned='".$input["balance_cleaned"]."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date', status='active', remark='".$input["remark"]."' WHERE id='".$input["id"]."'";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    	    echo "{\"status\":\"".$conn->error."\"}";
    	}
	}
}
else if($_GET["type"]=="getCodeDefinition"){
	$sql = "SELECT * FROM code_definition WHERE status='active'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="saveDefinition"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "INSERT INTO code_definition (type, code, entry_date) VALUES ('".$input["type"]."','".$input["code"]."','$entry_date')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="getSubtypes"){
	$sql = "SELECT DISTINCT(material_subtype) FROM material";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
} else if ($_GET["type"]=="approveTest") {
    $sql = "UPDATE test SET status='active' WHERE id='".$_GET["id"]."'";
    if($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="approveSubtest") {
    $sql = "UPDATE subtest SET status='active' WHERE id='".$_GET["id"]."'";
    if($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="approveMaterial") {
    $sql = "UPDATE material SET status='active' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="updateMaterialCode") {
    $sql = "UPDATE material SET material_code='".$_GET["material_code"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="getActiveLineClearance") {
    $sql = "SELECT * FROM lineclearance WHERE status='active'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="getPendingLineClearance") {
    $sql = "SELECT * FROM lineclearance";
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
} else if ($_GET["type"]=="updateLineClearance") {
    $sql = "UPDATE lineclearance SET status='".$_GET["action"]."', accept_remark='".$_GET["remark"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="getMaterialsApilist") {
    $sql = "SELECT * FROM material WHERE material_type='Raw Material' AND material_subtype='API' AND status='active'";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="addClient") {
    $sql = "INSERT INTO clientcompany (client_code, company_name, address, email, contact_person, phone_no, client_type, entry_by, entry_date) VALUES ('".$input["client_code"]."','".$input["company_name"]."','".$input["address"]."','".$input["email"]."','".$input["contact_person"]."','".$input["phone_no"]."','".$input["client_type"]."','".$_GET["emp_id"]."','".$entry_date."')";
    if ($conn->query($sql)==TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="getAllClients") {
    $sql = "SELECT * FROM clientcompany";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="getApprovedClients") {
    $sql = "SELECT * FROM clientcompany WHERE status='active'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getOperators") {
    $sql = "SELECT * FROM labour WHERE category='Operator' AND status='active'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getSOPDetails") {
    $sql = "SELECT * FROM z_forms WHERE id='".$_GET["id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $row["sop_file"] = "sops/".$row["sop_file"];
            if ($row["sop_no"] == "") {
                $row["sop_no"] = "NA";
            }
            if ($row["format_no"] == "") {
                $row["format_no"] = "NA";
            }
            $row["flowchart"] = "upload/".$row["flowchart"];
            echo json_encode($row);
            break;
        }
    } else {
        echo "[]";
    }
} else if ($_GET["type"] == "getDepartmentForms") {
    $sql = "SELECT form_name FROM sop_forms WHERE UPPER(department) = UPPER('".$_GET["depart"]."') AND status='approved' GROUP BY department, form_name";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "uploadSOP") {
    $department = $_POST["department"];
	$sop_no = $_POST["sop_no"];
	
    $target_dir = "sops/";
    if(isset($_FILES["sop"]["name"])){
    	$target_file = $target_dir."sop-".basename($_FILES["sop"]["name"]);
    	$sop_file = "sop-".basename($_FILES["sop"]["name"]);
    	$file3 = basename($_FILES["sop"]["name"]);
    	move_uploaded_file($_FILES["sop"]["tmp_name"], $target_file);
	}
	
    $sql = "INSERT INTO sop_forms (department, form_name, sop_no, form_no, version_no, title, effective_date, review_date, annexture_no, sop_file, entry_by, entry_date) VALUES (UPPER('".$department."'), '".$_POST["form_name"]."', '".$sop_no."', '".$_POST["format_no"]."', '".$_POST["version_no"]."', '".$_POST["title"]."', '".$_POST["effective_date"]."', '".$_POST["review_date"]."', '".$_POST["annexure_no"]."', '$sop_file', '".$_GET["emp_id"]."', '".$entry_date."')";
    if ($conn->query($sql) == TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getUploadedSOPs") {
    $sql = "SELECT * FROM sop_forms";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["sop_file"] = "https://paperlessgmp.com/gmptotal/sops/".$row["sop_file"];
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingUploadedSOPs") {
    $sql = "SELECT * FROM sop_forms WHERE status='pending'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["sop_file"] = "https://paperlessgmp.com/gmptotal/sops/".$row["sop_file"];
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateUploadedSOP") {
    $sql = "UPDATE sop_forms SET status='".$_GET["action"]."', approve_by='".$emp_id."', approve_date='$entry_date' WHERE sop_no='".$_GET["sop_no"]."' AND form_no='".$_GET["form_no"]."'";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getAPIMaterials") {
	$sql = "SELECT * FROM material WHERE status='active' AND material_type='Raw Material' AND material_subtype='API' GROUP BY material_name";
	$result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getDosages") {
	$sql = "SELECT * FROM dosage_form";
	$result = $conn->query($sql);
	$output = Array();
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "getDosagesByType") {
	$sql = "SELECT * FROM dosage_form WHERE type='".$_GET["dosage_type"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "getMaterialGradeByName") {
	$sql = "SELECT grade FROM material WHERE material_name='".$_GET["material_name"]."' AND status='active'";
	$output = Array();
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "getPendingVendorAnnexure1") {
    $output = Array();
    $sql = "SELECT * FROM pre_assessment WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM assessment_questions WHERE assessment_no='".$row["assessment_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row[$row1["particular_no"]] = $row1["comment"];
                    $row[$row1["particular_no"]."_remark"] = $row1["remark"];
                    $row[$row1["particular_no"]."_attachment"] = $row1["attachment"];
                }
            }
            
            $sql1 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["vendor_name"] = $row1["vendor_name"];
                    break;
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateVendorAnnexure1") {
    $sql = "UPDATE pre_assessment SET status='".$_GET["action"]."' WHERE assessment_no='".$_GET["assessment_no"]."'";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getDosageForms") {
    $sql = "SELECT dosage_form FROM dosage_form";
	$output = Array();
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "getLineClearanceReport") {
    $output = Array();
    $sql = "SELECT * FROM lineclearance";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_name"] = $row1["material_name"];
                    $row["material_grade"] = $row1["grade"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "checkLineClearance") {
    $sql = "UPDATE grn_material SET lineclerance='active' SET grn_no='".$_GET["grn_no"]."' AND material_code='".$_GET["material_code"]."'";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingLineClearances") {
    $sql = "SELECT * FROM lineclearance WHERE status='active'";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getLineClearanceApproved") {
    $output = Array();
    $sql = "SELECT * FROM lineclearance WHERE status='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_name"] = $row1["material_name"];
                    $row["material_grade"] = $row1["grade"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getMaterialslist") {
    $sql = "SELECT * FROM general_material WHERE material_type = '".$_GET['material_type']."'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }else{
        echo "[]";
    }
} else if ($_GET["type"] == "saveGeneralMaterial") {
    $entry_date = date("Y-m-d", $timestamp);
    
    $m_id1 = 0;
    $m_no = "";
    $sql = "SELECT IFNULL(MAX(m_id1), 0) as m_id1 FROM general_material";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $m_id1 = $row["m_id1"];
        }
    }
    $m_id1++;
    $material_no = "GM-".$m_id1;
    
    $sql = "INSERT INTO general_material (material_no, material_name, material_type, entry_by, entry_date) VALUES ('".$material_no."','".$input["material_name"]."', '".$input["material_type"]."', '".$_GET["emp_id"]."', '".$entry_date."')";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getGeneralMaterials") {
    $output = Array();
    $sql = "SELECT * FROM general_material WHERE material_type='".$_GET["material_type"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getDamageContainersReport") {
    $output = Array();
    $sql = "SELECT * FROM damage_inspection WHERE qa_status !='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_name"] = $row1["material_name"];
                    $row["material_type"] = $row1["material_type"];
                    $row["material_grade"] = $row1["grade"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveSOPIndex") {
    $target_dir = "upload/";
    $sop_file = "";
    $flowchart = "";
    if(isset($_FILES["sop"]["name"])){
    	$target_file = $target_dir."sop-".$_POST["form_name"].".pdf";
    	$sop_file = "sop-".$_POST["form_name"].".pdf";
    	move_uploaded_file($_FILES["sop"]["tmp_name"], $target_file);
	}
	if(isset($_FILES["flowchart"]["name"])){
	    $ext = pathinfo(basename($_FILES["flowchart"]["name"]), PATHINFO_EXTENSION);
    	$target_file = $target_dir."flowchart-".$_POST["form_name"].".".$ext;
    	$flowchart = "flowchart-".$_POST["form_name"].".".$ext;
    	move_uploaded_file($_FILES["flowchart"]["tmp_name"], $target_file);
	}
    $sql = "UPDATE z_forms SET sop_name='".$_POST["sop_name"]."', sop_no='".$_POST["sop_no"]."', format_no='".$_POST["format_no"]."', flowchart='$flowchart', sop_file='$sop_file', status='active' WHERE id=".$_POST["form_name"];
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
    
} else if ($_GET["type"] == "getSops") {
    $sql = "SELECT * FROM z_forms ORDER BY id DESC";
    $result = $conn->query($sql);
    $data = array();
    
    if ($result-> num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode($data);
    
} else if ($_GET["type"] == "getPendingTrainings") {
    $output = Array();
    $sql = "SELECT * FROM training_needs WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveScheduleTraining") {
    $sql = "UPDATE training_needs SET training_date='".$input["training_date"]."',training_time='".$input["training_time"]."', venue='".$input["venue"]."', trainer_name='".$input["trainer_name"]."', approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."', status='active' WHERE id='".$input["id"]."'";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 

 else if ($_GET["type"] == "updateComplaint") {
    $complaint_no = mysqli_real_escape_string($conn, $_GET["complaint_no"]);
    $sql = "UPDATE market_complaint
        SET status = 'Approved',
            check_by = '".$_GET["emp_id"]."',
            check_date = '$entry_date'
        WHERE complaint_no = '".$complaint_no."'";

    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"] == "createMarketComplaintV2") {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload) || empty($payload)) {
        $payload = $_POST;
    }

    $nextId = 1;
    $result = $conn->query("SELECT IFNULL(MAX(id), 0) as id FROM market_complaint");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nextId = ((int)$row["id"]) + 1;
    }
    $complaint_no = "MC-".$nextId;

    $complaintent_type = mysqli_real_escape_string($conn, $payload["complaintent_type"] ?? "");
    $email = mysqli_real_escape_string($conn, $payload["email"] ?? "");
    $received_from = mysqli_real_escape_string($conn, $payload["received_from"] ?? "");
    $received_date = mysqli_real_escape_string($conn, $payload["received_date"] ?? "");
    $forward = mysqli_real_escape_string($conn, $payload["forward"] ?? "No");
    $physcian_email = mysqli_real_escape_string($conn, $payload["physcian_email"] ?? "");
    $complaint_sample = mysqli_real_escape_string($conn, $payload["complaint_sample"] ?? "");
    $sendsample_to = mysqli_real_escape_string($conn, $payload["sendsample_to"] ?? "");
    $contact_no = mysqli_real_escape_string($conn, $payload["contact_no"] ?? "");
    $severity = mysqli_real_escape_string($conn, $payload["severity"] ?? "");
    $complaint_nature = mysqli_real_escape_string($conn, $payload["complaint_nature"] ?? "");
    $product_name = mysqli_real_escape_string($conn, $payload["product_name"] ?? "");
    $batch_no = mysqli_real_escape_string($conn, $payload["batch_no"] ?? "");
    $mfg_date = mysqli_real_escape_string($conn, $payload["mfg_date"] ?? "");
    $exp_date = mysqli_real_escape_string($conn, $payload["exp_date"] ?? "");
    $complaint_remark = mysqli_real_escape_string($conn, $payload["complaint_remark"] ?? "");
    $control_remark = mysqli_real_escape_string($conn, $payload["control_remark"] ?? "");
    $observation = mysqli_real_escape_string($conn, $payload["observation"] ?? "");
    $primary_observation = mysqli_real_escape_string($conn, $payload["primary_observation"] ?? "");
    $assessment = mysqli_real_escape_string($conn, $payload["assessment"] ?? "");

    $departmentsRaw = $payload["departments"] ?? [];
    if (!is_array($departmentsRaw)) { $departmentsRaw = []; }
    $departments = mysqli_real_escape_string($conn, json_encode($departmentsRaw));

    $documentsRaw = $payload["documents"] ?? [];
    if (!is_array($documentsRaw)) { $documentsRaw = []; }
    $documents = mysqli_real_escape_string($conn, json_encode($documentsRaw));

    $sql = "INSERT INTO market_complaint (
        complaintent_type,email,received_from,received_date,forward,physcian_email,complaint_sample,sendsample_to,
        contact_no,severity,complaint_no,complaint_nature,product_name,batch_no,mfg_date,exp_date,complaint_remark,control_remark,entry_by,
        entry_date,departments,documents,observation,primary_observation,assessment,status
    ) VALUES (
        '".$complaintent_type."','".$email."','".$received_from."','".$received_date."','".$forward."','".$physcian_email."','".$complaint_sample."','".$sendsample_to."',
        '".$contact_no."','".$severity."','".$complaint_no."','".$complaint_nature."','".$product_name."','".$batch_no."','".$mfg_date."','".$exp_date."','".$complaint_remark."','".$control_remark."','".$_GET["emp_id"]."',
        '".$entry_date."','".$departments."','".$documents."','".$observation."','".$primary_observation."','".$assessment."','pending'
    )";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(array("status" => "success", "complaint_no" => $complaint_no, "id" => $conn->insert_id));
    } else {
        echo json_encode(array("status" => "failed", "error" => $conn->error));
    }
}
else if ($_GET["type"] == "reviewMarketComplaint") {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload) || empty($payload)) {
        $payload = $_POST;
    }
    $complaintId = (int)($_GET["id"] ?? $payload["id"] ?? 0);
    if ($complaintId <= 0) {
        echo "{\"status\":\"failed\",\"error\":\"invalid_id\"}";
    } else {
        $primary_observation = mysqli_real_escape_string($conn, $payload["primary_observation"] ?? "");
        $observation = mysqli_real_escape_string($conn, $payload["observation"] ?? "");
        $assessment = mysqli_real_escape_string($conn, $payload["assessment"] ?? "");
        $documentsRaw = $payload["documents"] ?? [];
        if (!is_array($documentsRaw)) { $documentsRaw = []; }
        $documents = mysqli_real_escape_string($conn, json_encode($documentsRaw));

        $sql = "UPDATE market_complaint SET
            primary_observation='".$primary_observation."',
            observation='".$observation."',
            assessment='".$assessment."',
            documents='".$documents."',
            status='Send For approval'
            WHERE id=".$complaintId." LIMIT 1";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
}
else if ($_GET["type"] == "approveMarketComplaint") {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload) || empty($payload)) {
        $payload = $_POST;
    }
    $complaintId = (int)($_GET["id"] ?? $payload["id"] ?? 0);
    $approvalRemark = mysqli_real_escape_string($conn, $payload["approval_remark"] ?? "");
    if ($complaintId <= 0) {
        echo "{\"status\":\"failed\",\"error\":\"invalid_id\"}";
    } else {
        $sql = "UPDATE market_complaint SET
            status='Approved',
            check_by='".$_GET["emp_id"]."',
            check_date='".$entry_date."'
            WHERE id=".$complaintId." LIMIT 1";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
}
else if ($_GET["type"] == "getMarketComplaintSummary") {
    $output = array(
        "pending" => 0,
        "approval" => 0,
        "approved" => 0,
        "total" => 0
    );
    $sql = "SELECT
        SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status='Send For approval' THEN 1 ELSE 0 END) AS approval_count,
        SUM(CASE WHEN status='Approved' THEN 1 ELSE 0 END) AS approved_count,
        COUNT(*) AS total_count
        FROM market_complaint";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $output["pending"] = (int)$row["pending_count"];
        $output["approval"] = (int)$row["approval_count"];
        $output["approved"] = (int)$row["approved_count"];
        $output["total"] = (int)$row["total_count"];
    }
    echo json_encode($output);
}
  else if ($_GET["type"] == "getPendingComplaints") {
        $output = Array();
        $sql = "SELECT * FROM market_complaint
        WHERE status='pending'"; 
         $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row['committeeList'] = json_decode($row['committeeList']);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}  
 else if ($_GET["type"] == "getComplaints") {
        $output = Array();
        $sql = "SELECT * FROM market_complaint"; 
         $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row['committeeList'] = json_decode($row['committeeList']);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}  


else if ($_GET["type"] == "savemarketComplaintMeha") {

        $target_dir = "../upload/qa/";

        $id = date("YmdHis", $timestamp);
      $file_name = "";
    	if(isset($_FILES["document"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['document']['name'])));
        	$file_name = 'Stability-'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["document"]["tmp_name"], $target_dir.$file_name);
    	}
    
    
    $complaint_no = "";
    $sql = "SELECT IFNULL(MAX(id), 0) as id FROM market_complaint";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row["id"];
            $id++;
            $complaint_no = "MC-".$id;
        }
    }
    
    $sql = "INSERT INTO market_complaint (
    complaint_report_no,
    complaint_report_date,
    compltNO,
    complaint_no,
    received_from,
    complaint_reference,
    product_name,
    batch_no,
    mfg_date,
    exp_date,
    complaint_details,
    preliminary_evaluation,
    root_cause,
    committeeList,
    investigation_started,
    investigation_completed,
    investigation_method,
    observations_findings,
    conclusion,
    qms,
    tcd_action_taken,
    entry_by,
    entry_date
) VALUES (
    '".$input["complaint_report_no"]."',
    '".$input["complaint_report_date"]."',
    '".$input["complaint_no"]."',
    '$complaint_no',
    '".$input["received_from"]."',
    '".$input["complaint_reference"]."',
    '".$input["product_name"]."',
    '".$input["batch_no"]."',
    '".$input["mfg_date"]."',
    '".$input["exp_date"]."',
    '".$input["complaint_details"]."',
    '".$input["preliminary_evaluation"]."',
    '".$input["root_cause"]."' ,
    '".json_encode($input["committeeList"])."',
    '".$input["investigation_started"]."',
    '".$input["investigation_completed"]."',
    '".$input["investigation_method"]."',
    '".$input["observations_findings"]."',
    '".$input["conclusion"]."',
    '".$input["qms"]."',
    '".$input["tcd_action_taken"]."', 
    '".$_GET["emp_id"]."',
    '$entry_date'
)";

    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}"; 
    }
}


else if ($_GET["type"] == "savemarketComplaint") {
       $input = json_decode(file_get_contents('php://input'), true);
       if (!is_array($input) || empty($input)) {
           $input = $_POST;
       }
        
        $target_dir = "../upload/qa/";

        $id = date("YmdHis", $timestamp);
      $file_name = "";
    	if(isset($_FILES["document"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['document']['name'])));
        	$file_name = 'Stability-'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["document"]["tmp_name"], $target_dir.$file_name);
    	}
    
    
    $complaint_no = "";
    $sql = "SELECT IFNULL(MAX(id), 0) as id FROM market_complaint";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row["id"];
            $id++;
            $complaint_no = "MC-".$id;
        }
    }
    
     $sql = "INSERT INTO market_complaint (complaintent_type,email,received_from,received_date,forward,physcian_email,complaint_sample,sendsample_to,
    contact_no,severity,complaint_no, complaint_nature,product_name, batch_no, mfg_date, exp_date, complaint_remark, control_remark,entry_by,
    entry_date,departments,documents,observation,primary_observation,assessment) VALUE ('".$input["complaintent_type"]."','".$input["email"]."',
    '".$input["received_from"]."','".$input["received_date"]."','".$input["forward"]."','".$input["physcian_email"]."','".$input["complaint_sample"]."',
    '".$input["sendsample_to"]."','".$input["contact_no"]."','".$input["severity"]."','$complaint_no', '".$input["complaint_nature"]."',
    '".$input["product_name"]."', '".$input["batch_no"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["complaint_remark"]."',
    '".$input["control_remark"]."', '".$_GET["emp_id"]."', '$entry_date','".json_encode($input["departments"])."','$file_name',
    '".$input["observation"]."','".$input["primary_observation"]."','".$input["assessment"]."')";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "saveCalibrationParameter") {
    for ($i = 0; $i < count($input); $i++) {
        $sql = "INSERT INTO calibration_methods (equipment_code, method) VALUES ('".$_GET["equipment_code"]."', '".$input[$i]."')";
        $conn->query($sql);
    }
    $sql = "UPDATE equipment SET calibration_parameter='done' WHERE equipment_code='".$_GET["equipment_code"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 
else if ($_GET["type"]=="updatesavemarketComplaint") {
     $input = $_POST; 
        
        $target_dir = "../upload/qa/";

        $id = date("YmdHis", $timestamp);
      $file_name = "";
    	if(isset($_FILES["document"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['document']['name'])));
        	$file_name = 'IR-'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["document"]["tmp_name"], $target_dir.$file_name);
    	} 
    	
    $sql = "UPDATE market_complaint SET inspection_report='$file_name',status='Send For approval' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"]=="updatesavemarketComplaintapprove") {
   
    	
    $sql = "UPDATE market_complaint SET  status='Approved' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"]=="updateLineClearence") {
    $sql = "UPDATE lineclearance SET status='approve' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"] == "getCalibrationReport") {
    $output = Array();
    $sql = "SELECT * FROM equipment WHERE calibration='Required'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getMarketComplaints_approval") {
    $output = Array();
    $sql = "SELECT * FROM market_complaint where status='Send For approval'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['documents'] = json_decode($row['documents'], true);
            $row['departments'] = json_decode($row['departments'], true);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getMarketComplaints") {
    $output = Array();
    $sql = "SELECT * FROM market_complaint where status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['documents'] = json_decode($row['documents'], true);
            $row['departments'] = json_decode($row['departments'], true);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getMarketComplaintslog") {
    $output = Array();
    $sql = "SELECT * FROM market_complaint where status='Approved'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['documents'] = json_decode($row['documents'], true);
            $row['departments'] = json_decode($row['departments'], true);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if($_GET['type'] == 'getmarketchart'){
    $output = Array();
    $series = Array();
    $lables = Array();
    $sql = "SELECT COUNT(id) as total, complaint_nature  FROM market_complaint GROUP BY complaint_nature";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $lables[] = $row["complaint_nature"];
            $series[] = +$row["total"];
        }
    }
    $output["series"] = $series;
    $output["lables"] = $lables;
    echo json_encode($output);
}
else if ($_GET["type"] == "getMedicalComplaints") {
    $output = Array();
    $sql = "SELECT * FROM medical_complaint";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if($_GET['type'] == 'getmedicalchart'){
    $output = Array();
    $series = Array();
    $lables = Array();
    $sql = "SELECT COUNT(id) as total, complaint_nature  FROM medical_complaint GROUP BY complaint_nature";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $lables[] = $row["complaint_nature"];
            $series[] = +$row["total"];
        }
    }
    $output["series"] = $series;
    $output["lables"] = $lables;
    echo json_encode($output);
}
else if ($_GET["type"] == "savemedicalComplaint") {
    $complaint_no = "";
    $sql = "SELECT IFNULL(MAX(id), 0) as id FROM medical_complaint";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row["id"];
            $id++;
            $complaint_no = "MC-".$id;
        }
    }
    
    $sql = "INSERT INTO medical_complaint (complaint_no, complaint_nature, complaint_sample, product_name, batch_no, mfg_date, exp_date, complaint_remark, control_remark, entry_by, entry_date) VALUE ('$complaint_no', '".$input["complaint_nature"]."','".$input["complaint_sample"]."','".$input["product_name"]."', '".$input["batch_no"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["complaint_remark"]."', '".$input["control_remark"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 

else if ($_GET["type"] == "saveStabilityStudy") {
    $sql = "INSERT INTO stability_study (product_name, generic_name, code, grade, packing, market, batch_total, batch_type, entry_by, entry_date) VALUES ('".$input["product_name"]."', '".$input["generic_name"]."', '".$input["code"]."', '".$input["grade"]."', '".$input["packing"]."', '".$input["market"]."', '".$input["total_batches"]."', '".$input["batch_type"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql) === TRUE) {
        $last_id = $conn->insert_id;
        echo "{\"status\":\"success\"}";
        $data = $input["batches"];
        for ($i = 0; $i < count($data); $i++) {
            $temp = $data[$i];
            $sql1 = "INSERT INTO stability_batch (stability_no, batch_no, mfg_date, exp_date, purpose) VALUES ('$last_id', '".$temp["batch_no"]."', '".$temp["mfg_date"]."', '".$temp["exp_date"]."', '".$temp["purpose"]."')";
            $conn->query($sql1);
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
            $sql1 = "SELECT * FROM stability_batch WHERE stability_no=".$row["id"];
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row1["product_name"] = $row["product_name"];
                    $row1["code"] = $row["code"];
                    $row1["grade"] = $row["grade"];
                    $output[] = $row1;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveStabilityChargining") {
    $sql = "UPDATE stability_batch SET status='inprocess',batch_status='".$input["batch_status"]."', charging_reason='".$input["charging_reason"]."', shelf_life='".$input["shelf_life"]."', specification_no='".$input["specification_no"]."', instruction='".$input["instruction"]."' WHERE id=".$input["id"];
    if ($conn->query($sql) == TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getAuditTrails") {
    $output = Array();
    $dept = $conn->real_escape_string(isset($_GET["dept"]) ? $_GET["dept"] : '');
    $sql = "SELECT l.*, e.firstname, e.middlename, e.lastname,
            TRIM(CONCAT(COALESCE(e.firstname,''), ' ', COALESCE(e.middlename,''), ' ', COALESCE(e.lastname,''))) AS emp_name
            FROM log l
            LEFT JOIN employee e ON l.emp_id = e.emp_id
            WHERE l.department='".$dept."'
            ORDER BY l.actiontime DESC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (trim($row['emp_name']) === '') {
                $row['emp_name'] = $row['emp_id'];
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateQABatchPlan") {
    $batch_id = 0;
    $code = "";
    $sql = "SELECT * FROM code_definition WHERE type='BATCH'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $batch_id = $row["current_no"];
            $code = $row["code"];
            break;
        }
    }
    $batch_id++;
    $batch_no = $code.$batch_id;
    
    $sql = "UPDATE batch_planning SET batch_no='".$batch_no."', qa_status='".$_GET["status"]."', batch_status='planned', qa_approve_by='".$_GET["emp_id"]."', qa_approve_date='$entry_date' WHERE id=".$_GET["plan_no"];
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getSamplingEquipments") {
    $output = Array();
    $sql = "SELECT * FROM equipment WHERE section='Sampling' AND status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveSOPRevisionRequest") {
    $sql = "INSERT INTO sop_revision(department, sop_title, sop_no, due_date, revision_reason, cc_no, change_required, post_review_comment, depthead_comment, qahead_comment, change_made, reviewed_no_change, next_review_date)
    VALUES ('".$_POST["department"]."', '".$_POST["sop_title"]."', '".$_POST["sop_no"]."', '".$_POST["due_date"]."', '".$_POST["revision_reason"]."', '".$_POST["cc_no"]."', '".$_POST["change_required"]."', '".$_POST["post_review_comment"]."','".$_POST["depthead_comment"]."','".$_POST["qahead_comment"]."','".$_POST["change_made"]."','".$_POST["reviewed_no_change"]."','".$_POST["next_review_date"]."')";
    
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    } 
} else if ($_GET["type"] === "getSOPRevisions") {
    $sql = "SELECT sr.*, sg.form_name FROM sop_revision sr JOIN sop_gen sg ON sg.sop_no = sr.sop_no";
    $result = $conn->query($sql);
    $data = array();
    
    if ($result-> num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode($data);
    
} else if ($_GET["type"] == "saveGMPMonitoring") {
    
    $sql = "SELECT IFNULL(MAX(id), 0) as id FROM gmp_monitoring_checklist LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result -> num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row["id"];
        }
    }
    $id++;
    $checkpoint_id = "C00".$id;
    $gmp_id = "GMP00".$id;
    
    
    
   echo $sql = "INSERT INTO gmp_monitoring_checklist(gmp_id, department, area, section, checkpoint, entry_date, created_by) VALUES 
    ('$gmp_id', ".$_POST["department"]."', '".$_POST["area"]."', '".$_POST["section"]."','".$checkpoint_id."', '$entry_date', '".$_GET["emp_id"]."')";
    if ($conn->query($sql) === TRUE) {
        
        $data = explode(',', $_POST["checkpoint"]);
        $flag = 0;
        
        for ($i = 0; $i < count($data); $i++) {
            $temp = $data[$i];
            $sql3 = "INSERT INTO gmp_monitoring_checkpoints(checkpoint_id, checkpoint) VALUES ('$checkpoint_id', '$temp')";
            if ($conn->query($sql3) === TRUE) {
                $flag = 0;
            } else {
                $flag = 1;
            }
        }
        if ($flag == 0) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";   
        }
    
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "getGMPMonitoring") {
    if (isset($_GET["status"])) {
        $sql = "SELECT * FROM gmp_monitoring_checklist WHERE status='".$_GET["status"]."'";
    } else {
        $sql = "SELECT * FROM gmp_monitoring_checklist";
    }
    
    $result = $conn->query($sql);
    $data = array();
    
    if ($result -> num_rows > 0) {
        while ($row = $result -> fetch_assoc()) {
            $sql2 = "SELECT * FROM gmp_monitoring_checkpoints checkpoints WHERE checkpoint_id = '".$row["checkpoint"]."'";
            $result2 = $conn->query($sql2);
            $output = array();
            
            if ($result2-> num_rows > 0) {
                while ($row2 = $result2 -> fetch_assoc()) {
                    $output[] = $row2;
                }
            }
            
            $row["checkpoints"] = $output;
            $data[] = $row;
        }
    }
    echo json_encode($data);
} else if ($_GET["type"] == "getGMPMonitoringChecklistByDepartment") {
    $sql = "SELECT checkpoints.* FROM gmp_monitoring_checklist gc 
    JOIN gmp_monitoring_checkpoints checkpoints ON gc.checkpoint = checkpoints.checkpoint_id
    WHERE gc.department='".$_POST["department"]."' AND gc.section = '".$_POST["section"]."'
    ";
    $result = $conn->query($sql);
    $data = array();
    
    if ($result -> num_rows > 0) {
        while ($row = $result -> fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode($data);
} else if ($_GET["type"] == "getGMPMonitoringChecklist") {
    $sql = "SELECT checkpoints.* FROM gmp_monitoring_checklist gc 
    JOIN gmp_monitoring_checkpoints checkpoints ON gc.checkpoint = checkpoints.checkpoint_id
    ";
    $result = $conn->query($sql);
    $data = array();
    
    if ($result -> num_rows > 0) {
        while ($row = $result -> fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode($data);
} else if ($_GET["type"] == "approveGMPMonitoring") {
    $sql = "UPDATE gmp_monitoring_checklist SET status='active' WHERE id='".$_GET["id"]."' LIMIT 1";
    
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "saveCheckpoint") {
    $sql = "UPDATE gmp_monitoring_checkpoints SET status='active', observation='".$_POST["observation"]."',
    remark = '".$_POST["remark"]."', informed_to = '".$_POST["informed_to"]."', comment = '".$_POST["comment"]."' 
    WHERE id='".$_POST["id"]."' LIMIT 1";
    
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "saveGMPMemo") {
    $sql = "INSERT INTO gmp_memo(emp_id, department, area, section, shift, repeated_failure, failure_title, failure_description, remark) VALUES 
    ('".$_POST["emp_id"]."', '".$_POST["department"]."', '".$_POST["area"]."', '".$_POST["section"]."', '".$_POST["shift"]."', '".$_POST["repeated_failure"]."', '".$_POST["failure_title"]."', '".$_POST["failure_description"]."', '".$_POST["remark"]."')";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "getGMPMemo") {
    $sql = "SELECT * FROM gmp_memo ORDER BY id DESC";
    $result = $conn->query($sql);
    $data = array();
    
    if ($result -> num_rows > 0) {
        while ($row = $result -> fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode($data);
} else if ($_GET["type"] == "saveProductRecall") {
    
    $sql = "SELECT IFNULL(MAX(id), 0) as id FROM product_recall";
    $result = $conn->query($sql);
    $no = "";
    $id = 0;
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row["id"];
        }
    }
    $id++;
    $num = strlen($id);
    if ($num == 1) {
        $received_quantity = "RQ0".$id;
        $examination_report = "ER0".$id;
        $destruction_report = "DR0".$id;
    } else if ($num == 2) {
        $received_quantity = "RQ".$id;
        $examination_report = "ER".$id;
        $destruction_report = "DR".$id;
    }
    
    $file1 = $_FILES['received_quantity']['tmp_name'];
    $file2 = $_FILES['examination_report']['tmp_name'];
    $file3 = $_FILES['destruction_report']['tmp_name'];
    
    
    $file_loc1 = "upload/recall/".$received_quantity.".pdf";
    $file_loc2 = "upload/recall/".$examination_report.".pdf";
    $file_loc3 = "upload/recall/".$destruction_report.".pdf";
    
    
    if((move_uploaded_file($file1,$file_loc1)) && (move_uploaded_file($file2,$file_loc2)) && (move_uploaded_file($file3,$file_loc3))) {
    
        $sql = "INSERT INTO product_recall(
        product_name, batch_no, mfg_date, exp_date, coordinator, company_person, clients_person, email, mobile, mock_recall, public_recall, professional_recall, enforcement_auth_recall,
        television, radio, newspaper, cfagents, stockiest, distributors, mfg_error, packing_error, side_effect, abnormal_stability,
        degradation, contamination_product, release_change, packing_defect, overprint_error_price, overprint_error_batch, overprint_error_exp,
        other_reason, product_receive, received_quantity, examination_report, destruction_report) VALUES 
        ('".$_POST["product_name"]."', '".$_POST["batch_no"]."', '".$_POST["mfg_date"]."', '".$_POST["exp_date"]."', '".$_POST["coordinator"]."',
        '".$_POST["company_person"]."', '".$_POST["clients_person"]."', '".$_POST["email"]."', '".$_POST["mobile"]."', '".$_POST["mock_recall"]."',
        '".$_POST["public_recall"]."', '".$_POST["professional_recall"]."', '".$_POST["enforcement_auth_recall"]."', '".$_POST["television"]."',
        '".$_POST["radio"]."', '".$_POST["newspaper"]."', '".$_POST["cfagents"]."', '".$_POST["stockiest"]."', '".$_POST["distributors"]."',
        '".$_POST["mfg_error"]."', '".$_POST["packing_error"]."', '".$_POST["side_effect"]."', '".$_POST["abnormal_stability"]."', '".$_POST["degradation"]."',
        '".$_POST["contamination_product"]."', '".$_POST["release_change"]."', '".$_POST["packing_defect"]."', '".$_POST["overprint_error_price"]."',
        '".$_POST["overprint_error_batch"]."', '".$_POST["overprint_error_exp"]."', '".$_POST["other_reason"]."', '".$_POST["product_receive"]."', 
        '".$file_loc1."','".$file_loc2."', '".$file_loc3."')";
        
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}"; 
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else {
        echo "{\"status\":\"file_failed\"}";
    }
} else if ($_GET["type"] == "getProductRecall") {
    $sql = "SELECT * FROM product_recall ORDER BY id DESC";
    $result = $conn->query($sql);
    $data = array();
    
    if ($result -> num_rows > 0) {
        while ($row = $result -> fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode($data);
} else if ($_GET["type"] == "saveAdvice") {
    $sql = "INSERT INTO marketing_advice( plant_id,training,capa,name, address, batch_no, product_name, date, status) VALUES 
    ('".$_GET["plant_id"]."','".$_POST["training"]."','".$_POST["capa"]."','".$_POST["name"]."', '".$_POST["address"]."', '".$_POST["batch_no"]."', '".$_POST["product_name"]."', '".$_POST["date"]."', 'pending')";
    
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "getAdvice") {
    $sql = "SELECT * FROM marketing_advice ORDER BY id DESC";
    $result = $conn->query($sql);
    $data = array();
    
    if ($result -> num_rows > 0) {
        while ($row = $result-> fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode($data);
    
} else if ($_GET["type"] == "approveAdvice") {
    $sql = "UPDATE marketing_advice SET status='approve' WHERE id = ".$_POST["id"]." LIMIT 1";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "saveGMPRevision") {
    
    $sql = "SELECT COUNT(*) as count FROM gmp_revision WHERE gmp_id='".$_POST["gmp_id"]."' LIMIT 1";
    $result = $conn->query($sql);
    if ($result -> num_rows > 0) {
        while ($row = $result -> fetch_assoc()) {
            $revision_no = $row["count"];
        }
    }
    $revision_no++;
    $revision_no = "REV00".$revision_no;
    
    $sql = "INSERT INTO gmp_revision (gmp_id, revision_no, revision_reason, change_required) VALUES 
    ('".$_POST["gmp_id"]."', '$revision_no', '".$_POST["revision_reason"]."', '".$_POST["change_required"]."')";
    if ($conn->query($sql) === TRUE) {
        
        $sql = "UPDATE gmp_monitoring_checklist SET revision = 'true' WHERE gmp_id = '".$_POST["gmp_id"]."' LIMIT 1";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
} else if ($_GET["type"] == "getGMPRevision") {
    $sql = "SELECT * FROM gmp_revision ORDER BY id DESC";
    $result = $conn->query($sql);
    $data = array();
    if ($result -> num_rows > 0) {
        while ($row = $result -> fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    echo json_encode($data);
} else if ($_GET["type"] == "saveGMPChangeControl") {
    
    $sql = "SELECT IFNULL(MAX(id), 0) as id FROM changecontrol";
    $result = $conn->query($sql);
    $id = 0;
    
    if ($result -> num_rows > 0) {
        while ($row = $result -> fetch_assoc()) {
            $id = $row["id"];
        }
    }
    $id++;
    $ctrl_no = "CC-".$id;
    
    $sql = "INSERT INTO changecontrol (ctrl_no, department, change_related, change_title, existing_procedure, proposed_change,
    reason_for_changes, product_name, market_detail, description, c_no1, entry_by, entry_date) VALUES 
    ('$ctrl_no', '".$_POST["department"]."', '".$_POST["change_related"]."', '".$_POST["change_title"]."', '".$_POST["existing_procedure"]."',
    '".$_POST["proposed_change"]."', '".$_POST["reason_for_changes"]."', '".$_POST["product_name"]."', '".$_POST["market_detail"]."',
    '".$_POST["description"]."', $id, '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql) === TRUE) {
        
        $sql = "UPDATE gmp_revision SET cc_no = '$ctrl_no' WHERE gmp_id = '".$_POST["gmp_id"]."' LIMIT 1";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
    
} else if ($_GET["type"] == "approveRevision") {
    $sql = "UPDATE gmp_revision SET status='active' WHERE gmp_id = '".$_POST["gmp_id"]."' LIMIT 1";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
}

 else if ($_GET["type"] == "ComplaintsLogMehaPdf") {
    $_GET['filename'] = 'MARKET COMPLAINT INVESTIGATION REPORT';$_GET['sop']="SOP/QAD/C/044-F01/00";$_GET['annexure']='Annexure1';$_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
    $html= "";
    $sql = "SELECT * FROM market_complaint";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $html.='
    <table border="1" width="100%" cellpadding="5" cellspacing="0">
        <tr><th colspan="4" style="text-align:center;"><h3>Complaint Report Details</h3></th></tr>
        <tr><td>Complaint Report No:</td><td  style="color: blue;">'.$row['complaint_no'].' ('.$row['complaint_report_no'].')</td><td>Complaint Report Date:</td><td style="color: blue;">'.$row['complaint_report_date'].'</td></tr>
        
        <tr><th colspan="4" style="text-align:center;"><h3>Complaint Details</h3></th></tr>
        <tr><td>Complaint No.:</td><td  style="color: blue;">'.$row['complaint_no'].' ('.$row['compltNO'].')</td><td>Complaint received from/Customer:</td><td  style="color: blue;">'.$row['received_from'].'</td></tr>
        <tr><td>Complaint Reference:</td><td  style="color: blue;">'.$row['complaint_reference'].'</td><td>Name of Product:</td><td  style="color: blue;">'.$row['product_name'].'</td></tr>
        <tr><td>Batch No.:</td><td  style="color: blue;">'.$row['batch_no'].'</td><td>Mfg. Dt:</td><td   style="color: blue;">'.$row['mfg_date'].'</td></tr>
        <tr><td>Exp. Dt:</td><td  style="color: blue;">'.$row['exp_date'].'</td></tr><tr><td>Details of Complaint:</td><td colspan="3"  style="color: blue;">'.$row['complaint_details'].'</td></tr>
        
        <tr><th colspan="4" style="text-align:center;"><h3>Complaint Investigation</h3></th></tr>
        <tr><td>Preliminary Evaluation:</td><td colspan="3"  style="color: blue;">'.$row['preliminary_evaluation'].'</td></tr>
        <tr><td>Root Cause Investigation:</td><td colspan="3"  style="color: blue;">'.$row['root_cause'].'</td></tr>
        
        <tr><th colspan="4" style="text-align:center;"><h3>Complaint Investigation Committee</h3></th></tr>
       <tr>
        <td  style="padding:5px;" colspan="4">
            <table width="100%" cellpadding="3">
               <tr>
                <th style="border:1px solid black; padding:5px; text-align:center;">Sr.</th>
                <th style="border:1px solid black; padding:5px; text-align:center;">Name</th>
                <th style="border:1px solid black; padding:5px; text-align:center;">Designation</th>
            </tr>';

         
                $json_obj = $row['committeeList'];
                $array = json_decode($json_obj, true);
                $k = 1;
               
                foreach ($array as $values) {
                     $name = $values['name'];
                $designation = $values['designation'];
                        $html.='   <tr>
                            <td  style="border:1px solid black; padding:5px; text-align:center;color: blue;">' .$k.'</td>
                            <td  style="border:1px solid black; padding:5px; text-align:center;color: blue;">' .$name.'</td>
                            <td  style="border:1px solid black; padding:5px; text-align:center;color: blue;">' .$designation.'</td>
                          </tr>';
                    $k++;
                }
       
    $html.='  </table>
        </td>
    </tr>
        
        <tr><th colspan="4" style="text-align:center;"><h3>Investigation Details</h3></th></tr>
        <tr><td>Investigation Started On:</td><td  style="color: blue;">'.$row['investigation_started'].'</td><td>Investigation Completed On:</td><td  style="color: blue;">'.$row['investigation_completed'].'</td></tr>
        <tr><td>Investigation Method:</td><td colspan="3"  style="color: blue;">'.$row['investigation_method'].'</td></tr>
        <tr><td>Observations & Findings:</td><td colspan="3" style="color: blue;">'.$row['observations_findings'].'</td></tr>
        <tr><td>Conclusion:</td><td colspan="3"  style="color: blue;">'.$row['conclusion'].'</td></tr>
        <tr><td>QMS (If any):</td><td  style="color: blue;" >'.$row['qms'].'</td><td>TCD for the Action taken:</td><td  style="color: blue;" >'.$row['tcd_action_taken'].'</td></tr>
    </table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('MarketComplaint.pdf', 'I');
}


 else if ($_GET["type"] == "downloadViewMarketComplaints") {
    $_GET['filename'] = 'MARKET COMPLAINT INVESTIGATION REPORT';$_GET['sop']="SOP/QAD/C/044-F01/00";$_GET['annexure']='Annexure1';$_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
    $html= "";
    $sql = "SELECT * FROM market_complaint";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $html.='
    <table cellpadding="3" border="1">
        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
            <td style="width:100%;text-align:center;">Market Complaint Investigation Report </td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold;">Complaint No</td>
            <td style="width:25%;">'.$row['complaint_no'].'</td>
            <td style="width:25%; font-weight:bold;">Received On</td>
            <td style="width:25%;">'.$row['received_from'].'</td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold;">Complaint Received By </td>
            <td style="width:25%;">'.$row['received_from'].'</td>
            <td style="width:25%; font-weight:bold;">Forwarded to QA Department On</td>
            <td style="width:25%;"></td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold;">Nature of Complaint</td>
            <td style="width:75%;">
                <table>
                    <tr><td style="width:100%;">'.$row['complaint_nature'].'</td></tr>
                    <tr><td style="width:100%;"><b>Complaint Sample Receive :</b>'.$row['complaint_sample'].' </td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold;">Compliant Details</td>
            <td style="width:75%;">
                <table>
                    <tr><td style="width:50%;"><b>Product Name:</b>'.$row['product_name'].'</td><td style="width:50%;"><b>Batch No:</b>'.$row['batch_no'].'</td></tr>
                    <tr><td style="width:50%;"><b>Mfg Date:</b>'.date('d-m-Y',strtotime($row['mfg_date'])).'</td><td style="width:50%;"><b>Exp Date:</b>'.date('d-m-Y',strtotime($row['exp_date'])).'</td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold;">Inspection of Complaint and Control Sample</td>
            <td style="width:75%;">
               <table>
                    <tr><td style="width:100%;"><b>Inspection Remark Complaint Sample:</b>'.$row['complaint_remark'].'</td></tr>
                    <tr><td style="width:100%;"><b>Inspection Remark Control Sample:</b>'.$row['control_remark'].'</td></tr>
                    <tr><td style="width:50%;"></td><td style="width:50%;text-align:right;font-weight:bold;">Head QA/Designee Sign & Date </td></tr>
                    <tr><td style="width:50%;"></td><td style="width:50%;">'.$row['entry_by'].''.date('d-m-Y',strtotime($row['entry_date'])).'</td></tr>
               </table>
            </td>
        </tr>
        <tr>
            <td style="width:25%; font-weight:bold;">Primary Observation by Head QA</td>
            <td style="width:75%;">
                <table>
                    <tr><td style="width:100%;"><b>Remark:</b></td></tr>
                    <tr><td style="width:50%;"></td><td style="width:50%;font-weight:bold;"> Head QA/Designee Sign & Date</td></tr>
                    <tr><td style="width:50%;"></td><td style="width:50%;"></td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold;">Product Review</td>
            <td style="width:75%;">
                <table>
                    <tr><td style="width:100%;font-weight:bold;">Following Manufacturing and Analytical Documents Reviewed:</td></tr>
                    <tr><td style="width:100%;"><b>Test Report</b></td></tr>
                    <tr><td style="width:100%;"><b>Observation:</b>'.$row['observation'].'</td></tr>
                    <tr><td style="width:50%;font-weight:bold;">Executive QA Sign/Date</td><td style="width:50%;font-weight:bold;">Head QA Sign/Date </td></tr>
                    <tr><td style="width:50%;"></td><td style="width:50%;"></td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td rowspan="4" style="width:25%; font-weight:bold;">Cross Functional Technical Review and Remark </td>
            <td style="width:25%;font-weight:bold;">Department</td>
            <td style="width:25%;font-weight:bold;">Remark</td>
            <td style="width:25%;font-weight:bold;">Sign/Date</td>
        </tr>
        <tr>
            <td style="width:25%;">Production</td>
            <td style="width:25%;"></td>
            <td style="width:25%;"></td>
        </tr>
        <tr>
            <td style="width:25%;">Quality Control</td>
            <td style="width:25%;"></td>
            <td style="width:25%;"></td>
        </tr>
        <tr>
            <td style="width:25%;">Research and Development</td>
            <td style="width:25%;"></td>
            <td style="width:25%;"></td>
        </tr>
        <tr>
            <td rowspan="2" style="width:50%;font-weight:bold;">Cross Functional Technical Review and Remark </td>
            <td style="width:50%;"><b>Complaint is:</b></td>
        </tr>
        <tr>
            <td style="width:50%;"><b>Further Investigation:</b></td>
        </tr>
        <tr>
            <td style="width:100%;">
                <table>
                    <tr><td style="width:50%;"><b>Complaint Closed:</b></td><td style="width:50%;"><b>Recommended For Further Investigation:</b></td></tr>
                    <tr><td style="width:100%;"><b>Remark:</b></td></tr>
                    <tr><td style="width:50%;"></td><td style="width:50%;font-weight:bold;">Head QA/Designee </td></tr>
                    <tr><td style="width:50%;"></td><td style="width:50%;"></td></tr>
                </table>
            </td>
        </tr>
        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
            <td style="width:100%;text-align:center;">Market Complaint Investigation Report (PART II)</td>
        </tr>
        <tr>
            <td style="width:25%;"></td>
            <td style="width:25%;font-weight:bold;">Impact Element</td>
            <td style="width:50%;font-weight:bold;">Impact Nature </td>
        </tr>
        <tr>
            <td rowspan="5" style="width:25%;font-weight:bold;">Impact Assessment </td>
            <td style="width:25%;font-weight:bold;">Previous Batches </td>
            <td style="width:50%;"></td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold;">Next batches </td>
            <td style="width:50%;"></td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold;">Similar Product</td>
            <td style="width:50%;"></td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold;">Health and Safety</td>
            <td style="width:50%;"></td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold;">Environmental Impact</td>
            <td style="width:50%;"></td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold">Risk Assessment (If Any)</td>
            <td style="width:75%;"></td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold">Immediate Corrective Action Taken </td>
            <td style="width:75%;"></td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold">Preventive Action</td>
            <td style="width:75%;"></td>
        </tr>
        <tr>
            <td style="width:25%;font-weight:bold"><b>Remark of Medical Department /Expert</b></td>
            <td style="width:75%;"></td>
        </tr>
        <tr>
            <td style="width:100%;">
                <table>
                    <tr><td style="width:100%;"><b>Complaint Closed</b></td></tr>
                    <tr><td style="width:100%;"><b>Remark:</b></td></tr>
                    <tr><td style="width:50%;"></td><td style="width:50%;"><b>Head QA/Designee </b></td></tr>
                </table>
            </td>
        </tr>
    </table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('MarketComplaint.pdf', 'I');
}

else if ($_GET["type"] == "downloadMarketComplaintsLog") {
    $_GET['filename'] = 'MARKET COMPLAINT REGISTER'; $_GET['annexure']='Annexure 02';$_GET['pdftype'] = 'landscape'; include("pdfimp.php");
    $html= "";
    $html.='
    <table cellpadding="3" border="1">
       <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
            <td style="width:8%;">Complaint No.</td>
            <td style="width:12%;">Name of Product</td>
            <td style="width:8%;">B. No.</td>
            <td style="width:8%;">Mfg. Date</td>
            <td style="width:8%;">Exp. Date</td>
            <td style="width:10%;">Nature of Complaint</td>
            <td style="width:8%;">Received from</td>
            <td style="width:10%;">Investigation Started On</td>
            <td style="width:10%;">Investigation Completed On</td>
            <td style="width:10%;">Complaint Status Closed /Open</td>
            <td style="width:8%;">Sign</td>
       </tr>';
    $sql = "SELECT * FROM market_complaint";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
        $html.='
        <tr>
            <td style="width:8%;">'.$row['complaint_no'].'</td>
            <td style="width:12%;">'.$row['product_name'].'</td>
            <td style="width:8%;">'.$row['batch_no'].'</td>
            <td style="width:8%;">'.$row['mfg_date'].'</td>
            <td style="width:8%;">'.$row['exp_date'].'</td>
            <td style="width:10%;">'.$row['complaint_nature'].'</td>
            <td style="width:8%;">'.$row['received_from'].'</td>
            <td style="width:10%;">'.$row['investigation_start'].'</td>
            <td style="width:10%;">'.$row['investigation_complete'].'</td>
            <td style="width:10%;">'.$row['status'].'</td>
            <td style="width:8%;">'.$row['entry_by'].'</td>
       </tr>';
        }
    }
    $html.='
    </table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('MarketComplaintLog.pdf', 'I');
}

else if ($_GET["type"] == "downloadMarketComplaintsLogMaha") {
    $_GET['filename'] = 'MARKET COMPLAINT REGISTER'; $_GET['annexure']='Annexure 02';$_GET['pdftype'] = 'landscape'; include("pdfimp2.php");
    $html= "";
    $html.='
    <table cellpadding="3" border="1">
       <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
            <td style="width:8%;">Complaint No.</td>
            <td style="width:12%;">Name of Product</td>
            <td style="width:8%;">Complaint Date.</td>
            <td style="width:8%;">Mfg. Date</td>
            <td style="width:8%;">Exp. Date</td>
            <td style="width:12%;">Received from</td>
            <td style="width:10%;">Approve By</td>
            <td style="width:10%;">Approve On</td>
            <td style="width:16%;">Complaint Status Closed /Open</td>
            <td style="width:8%;">Sign</td>
       </tr>';
    $sql = "SELECT * FROM market_complaint";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
        $html.='
        <tr>
            <td style="width:8%;">'.$row['complaint_no'].'</td>
            <td style="width:12%;">'.$row['product_name'].'</td>
            <td style="width:8%;">'.$row['complaint_report_date'].'</td>
            <td style="width:8%;">'.$row['mfg_date'].'</td>
            <td style="width:8%;">'.$row['exp_date'].'</td>
            <td style="width:12%;">'.$row['received_from'].'</td>
            <td style="width:10%;">'.$row['check_by'].'</td>
            <td style="width:10%;">'.$row['check_date'].'</td>
            <td style="width:16%;">'.$row['status'].'</td>
            <td style="width:8%;">'.$row['entry_by'].'</td>
       </tr>';
        }
    }
    $html.='
    </table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('MarketComplaintLog.pdf', 'I');
}

} else {
    echo "{\"status\":\"invalid\"}";
}











$conn->close();
?>