<?php 
require '../db.php';
require '../token.php'; 
require '../tcpdf/tcpdf.php';

if (!function_exists('master_utf8ize')) {
    function master_utf8ize($mixed) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = master_utf8ize($value);
            }
        } else if (is_string($mixed)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
            }
            return utf8_encode($mixed);
        }
        return $mixed;
    }
}

if (!function_exists('master_safe_json_echo')) {
    function master_safe_json_echo($data) {
        header('Content-Type: application/json; charset=UTF-8');
        $data = master_utf8ize($data);
        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $json = json_encode($data, $flags);
        echo ($json === false) ? '[]' : $json;
    }
}

header('response_token: test123456');
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);
try{
$output = Array();
$token = $_GET["token"];
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
    
    if($_GET["type"] == "getSamplingPlans") {
        $output = Array();
        $sql = "SELECT * FROM specification";
        //$sql = "SELECT DISTINCT(sampling_plan) as sampling_plan FROM specification";
       
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if($_GET["type"] == "getSampleDrawnFroms") {
        $output = Array();
        $sql = "SELECT * FROM specification";
        //$sql = "SELECT DISTINCT(sample_drawn_from) as sample_drawn_from FROM specification";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if($_GET["type"] == "getSampleDrawnWiths") {
        $output = Array();
        $sql = "SELECT * FROM specification";
        //$sql = "SELECT DISTINCT(sample_drawn_with) as sample_drawn_with FROM specification";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if($_GET["type"] == "getPrecautions") {
        $output = Array();
        $sql = "SELECT* FROM specification";
        //$sql = "SELECT DISTINCT(precautions) as precautions FROM specification";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else  if ($_GET["type"] == "saveGrade") {
        $sql="SELECT * FROM grade WHERE grade='".$input["grade"]."' AND plant_id ='".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Grade Already Exists. Duplicate Values are not allowed\"}";
        }
        else {
        
        $sql = "INSERT INTO grade (plant_id,grade,phamacopoeia_name,entry_by,entry_date,code) 
        VALUES ('".$_GET["plant_id"]."','".$input["grade"]."','".$input["phamacopoeia_name"]."','".$_GET["emp_id"]."','$entry_date','".$input["grade_code"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
            
        }
    }
    else  if ($_GET["type"] == "save_Spcl_Grade") {
        $sql="SELECT * FROM spcl_grade  WHERE spcl_grade ='".$input["spcl_grade"]."' AND plant_id ='".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Grade Already Exists. Duplicate Values are not allowed\"}";
        }
        else {
        
        $sql = "INSERT INTO spcl_grade  (plant_id,spcl_grade,phamacopoeia_name,entry_by,entry_date) 
        VALUES ('".$_GET["plant_id"]."','".$input["spcl_grade"]."','".$input["phamacopoeia_name"]."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
            
        }
    }
    else if ($_GET["type"] == "save_weight"){
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        
            $input=$_POST;
              $id = $input["box_no"];
              
              
              
                    //   $target_dir = "../../../upload/std_wt/";
    
       	if(isset($_FILES["certificate"])) {
            $file_tmp =$_FILES['certificate']['tmp_name'];
            // $file_ext=strtolower(end(explode('.',$_FILES['certificate']['name'])));
            $file_name_parts = explode('.', $_FILES['certificate']['name']);
            $file_ext = strtolower(end($file_name_parts));

            $file_name = $id."certificate.".$file_ext;
            $photo = $file_name;
            move_uploaded_file($file_tmp,"../../../../upload/std_wt/".$file_name);
        }
        
        //      if(isset($_FILES["certificate"])) {
        //     $file_tmp =$_FILES['certificate']['tmp_name'];
        //     $file_ext=strtolower(end(explode('.',$_FILES['certificate']['name'])));
        //     $file_name1 = $id."certificate.".$file_ext;
        //     $certificate = $file_name1;
        //     move_uploaded_file($file_tmp,"../../upload/std_wt/".$file_name1);
        // }
        
        
        
        
          $sql = "INSERT INTO std_weight ( plant_id, trolly, department, last_date, reverification_frequency, std_wt_dtl,Certificate) 
        VALUES ('".$_GET["plant_id"]."','".$input["box_no"]."','".$input["department"]."','".$input["last_date"]."','".$input["frequency"]."', 
    '".$input["result"]."','".$photo."')";
    
          if ($conn->query($sql) == TRUE) {
            //   $data =$input["result"];
             $std_wt_id = $conn->insert_id; 
        //  for ($i = 0; $i < count($data); $i++) {
              
        //         $row1 = $data[$i];
        //         $sql1 = "INSERT INTO std_weight_dtl( std_weight_id, moc, type, unit, weight_description, plant_id,department,trolly) VALUES
        //       ($std_wt_id,'".$row1["moc"]."','".$row1["type"]."'
        //       ,'".$row1["unit"]."','".$row1["weight_description"]."','".$_GET["plant_id"]."','".$input["department"]."','".$input["box_no"]."') ";
              
        //         $conn->query($sql1);
        //     }
        
        
            // $json_obj = json_encode($input["result"]);
              $array = json_decode($input["result"], true);
                 $k=1;
                foreach ($array as $row1)
                {
  $sql = "INSERT INTO std_weight_dtl( std_weight_id, moc, type, unit, weight_description, plant_id,department,trolly) VALUES
               ($std_wt_id,'".$row1["moc"]."','".$row1["type"]."'
               ,'".$row1["unit"]."','".$row1["weight_description"]."','".$_GET["plant_id"]."','".$input["department"]."','".$input["box_no"]."')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
        
        
        
        
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    
    else  if ($_GET["type"] == "get_save_weight") {
        $output = Array();
         $sql = "select * from std_weight where  plant_id='".$_GET["plant_id"]."' ORDER BY `id` DESC";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row["std_wt_dtl"] = json_decode($row["std_wt_dtl"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else  if ($_GET["type"] == "get_save_weight_department") {
        $output = Array();
        // $sql = "SELECT * FROM std_weight";
        $sql = "select * from std_weight_dtl where AND department='".$_GET["department1"]."'";
        //$sql = "SELECT DISTINCT(sampling_plan) as sampling_plan FROM specification";
       
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row["std_wt_dtl"] = json_decode($row["std_wt_dtl"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_fg_material") {
        $output = array();
        $plant_id = isset($_GET["plant_id"]) ? $conn->real_escape_string(trim($_GET["plant_id"])) : '';
        $sql = "SELECT * FROM material
                WHERE plant_id='".$plant_id."'
                AND (status = 'Approved' OR status = 'approve')
                AND material_type = 'Raw Material'
                AND (material_subtype = 'API' OR LOWER(TRIM(IFNULL(material_subtype,''))) = 'api')
                ORDER BY material_name ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $eq = isset($row['equivalent_to']) ? $row['equivalent_to'] : '';
                if (is_string($eq) && $eq !== '') {
                    $decoded = json_decode($eq, true);
                    $row['equivalent_to'] = is_array($decoded) ? $decoded : array();
                } elseif (!is_array($eq)) {
                    $row['equivalent_to'] = array();
                }
                $gradeRaw = trim((string)($row['grade'] ?? ''));
                $row['gradeName'] = '';
                if ($gradeRaw !== '') {
                    if (preg_match('/^\d+(\s*,\s*\d+)*$/', $gradeRaw)) {
                        $gq = @$conn->query("SELECT GROUP_CONCAT(grade SEPARATOR ', ') AS gn FROM grade WHERE id IN (".$gradeRaw.")");
                        if ($gq && ($grow = $gq->fetch_assoc()) && !empty($grow['gn'])) {
                            $row['gradeName'] = $grow['gn'];
                        }
                    } else {
                        // Already stored as grade name (e.g. USP)
                        $row['gradeName'] = $gradeRaw;
                    }
                }
                $output[] = $row;
            }
        }
        master_safe_json_echo($output);
    }
    else if ($_GET["type"] == "getApprovedGenericProducts") {
         
        if (!isset($_GET["dosage_type"])) {
            $_GET["dosage_type"] = "";
        }
        if (!isset($_GET["dosage_form"])) {
            $_GET["dosage_form"] = "";
        }
           
        $output = Array();
    //   echo  $sql = "SELECT * FROM generic_product WHERE status='approve' AND dosage_type LIKE '%".$_GET["dosage_type"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND GRADE LIKE '%".$_GET["grade"]."%' ORDER BY generic_name";
        // $sql = "SELECT * FROM product WHERE status='approve' AND dosage_type LIKE '%".$_GET["dosage_type"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND GRADE LIKE '%".$_GET["grade"]."%' AND brand_generic LIKE '%".$_GET["brand_generic"]."%'  ORDER BY generic_name";
        $sql = "Select dosage_type from  product";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["product_name"] = $row["label_claim"];
                $row["label_claim"] = json_decode($row["label_claim"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
        else  if ($_GET["type"] == "get_Grade") {
    //	$sql = "SELECT * from grade WHERE id='".$_GET["grade"]."' ";
    // Validate and sanitize input
    $ids = isset($_GET["grade"]) ? $_GET["grade"] : '';
    $ids_array = explode(',', $ids);
    $ids_array = array_map('intval', $ids_array); // Ensure all values are integers
    $ids_list = implode(',', $ids_array); // Convert back to comma-separated list
    
    // Query with IN clause
    $sql = "SELECT * FROM grade WHERE id IN ($ids_list)";
		$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
    else  if ($_GET["type"] == "get_Grade_fg") {
        $output = array();
        $plant_id = isset($_GET["plant_id"]) ? trim((string)$_GET["plant_id"]) : '';
        if ($plant_id === '' || strtolower($plant_id) === 'null' || strtolower($plant_id) === 'undefined') {
            $plant_id = '1126';
        }
        $pid = $conn->real_escape_string($plant_id);
        $sql = "SELECT * FROM grade WHERE CAST(plant_id AS CHAR)='".$pid."' ORDER BY id DESC";
        $result = @$conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        if (count($output) === 0) {
            $sql = "SELECT * FROM grade ORDER BY id DESC";
            $result = @$conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
        master_safe_json_echo($output);
}
    else if ($_GET["type"] == "get_storage_conditions") {
        $output = array();
        $plant_id = isset($_GET["plant_id"]) ? trim($_GET["plant_id"]) : '';
        if ($plant_id !== '') {
            $pid = $conn->real_escape_string($plant_id);
            $sql = "SELECT * FROM storage_conditions WHERE CAST(plant_id AS CHAR)='".$pid."' ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
        if (count($output) === 0) {
            $sql = "SELECT * FROM storage_conditions ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
        master_safe_json_echo($output);
    }
    else  if ($_GET["type"] == "save_storage_condition") 
    {
            $sql="SELECT * FROM storage_conditions WHERE (storage_condition='".$input["storage_condition"]."' 
            OR storage_display_name='".$input["storage_display_name"]."')  AND plant_id ='".$_GET["plant_id"]."' ";
        
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Storage Condition OR Display Name Already Exists. Duplicate Values are not allowed\"}";
        }
        else {
        $sql = "INSERT INTO storage_conditions (plant_id,storage_condition,temperature,humidity,storage_display_name,entry_by) 
        VALUES ('".$_GET["plant_id"]."','".$input["storage_condition"]."','".$input["temperature"]."',
        '".$input["humidity"]."','".$input["storage_display_name"]."','".$_GET["emp_id"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
            
        }
    }
    else  if ($_GET["type"] == "save_gst") {
  	
    $checkSql = "SELECT * FROM gst_per WHERE gst = '".$input["gst"]."' AND plant_id = '".$_GET["plant_id"]."'";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult->num_rows > 0) {
            echo "{\"status\":\"This Entry Already Exists. Duplicate Values are not allowed\"}";
        } else {
            $insertSql = "INSERT INTO gst_per  (heading,plant_id, gst,gst_type,igst,cgst,sgst,description,entry_by) VALUES ('".$input["heading"]."','".$_GET["plant_id"]."','".$input["gst"]."','".$input["gst_type"]."','".$input["igst"]."','".$input["cgst"]."',
        '".$input["sgst"]."','".$input["Description"]."','".$_GET["emp_id"]."')";
            if ($conn->query($insertSql) === TRUE) {
                echo "{\"status\":\"Record inserted successfully\"}";
            } else {
                echo "{\"status\":\"Error: " . $conn->error . "\"}";
            }
        }
    		
    } 
    else  if ($_GET["type"] == "savePurchaseApprovalMatrix") {
  	
        $checkSql = "SELECT * FROM purchaseApprovalMatrix WHERE po_type = '".$input["po_type"]."' AND poValueFrom = '".$input["poValueFrom"]."' AND poValueTo = '".$input["poValueTo"]."' AND plant_id = '".$_GET["plant_id"]."'";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult->num_rows > 0) {
            echo "{\"status\":\"This Approval Matrix Already Exists.\"}";
        } else {
            $insertSql = "INSERT INTO `purchaseApprovalMatrix`(`plant_id`, `po_type`, `poValueFrom`, `poValueTo`, `firstApprover`, `secAppReq`, `secondtApprover`, `status`, `entryBy`, `entryOn`) VALUES ('".$_GET["plant_id"]."',
            '".$input["po_type"]."','".$input["poValueFrom"]."','".$input["poValueTo"]."', '".$input["firstApprover"]."', '".$input["secAppReq"]."','".$input["secondtApprover"]."', 'Pending', '".$_GET["emp_id"]."','$entry_date')";
            
            if ($conn->query($insertSql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"Error: " . $conn->error . "\"}";
            }
        }
    		
    }
    else  if ($_GET["type"] == "approvePurchaseApprovalMatrix") {
        
         $insertSql = "UPDATE `purchaseApprovalMatrix` SET  `status` = 'Approved' , `approveBy` = '".$_GET["emp_id"]."' , `approveOn` = '$entry_date' where id = '".$input["id"]."' ";
         
        if ($conn->query($insertSql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"Error: " . $conn->error . "\"}";
        }
        
    		
    }
    else  if ($_GET["type"] == "getPurchaseApprovalMatrix") {
        
    	$sql = "SELECT * from purchaseApprovalMatrix where  plant_id = '".$_GET["plant_id"]."' order by po_type ASC";
        $result = $conn->query($sql);
    	if($result->num_rows > 0){
    		$output = Array();
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    }
    else  if ($_GET["type"] == "getMadeUpOfs") {
        
    	$sql = "SELECT * from madeUpOfs ORDER BY madeUpOf ASC";
        $result = $conn->query($sql);
    	if($result->num_rows > 0){
    		$output = Array();
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    }
    else  if ($_GET["type"] == "getCOntainerTypes") {
        
    	$sql = "SELECT * from containerTypes where  plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
    	if($result->num_rows > 0){
    		$output = Array();
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    }
    else  if ($_GET["type"] == "saveContainerType") {
  	
        $checkSql = "SELECT id FROM containerTypes WHERE container_type = '".$input["container_type"]."' AND plant_id = '".$_GET["plant_id"]."'";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult->num_rows > 0) {
            echo "{\"status\":\"This Container Type IS Already Exits!!!!!!!!.\"}";
        } else {
            $insertSql = "INSERT INTO `containerTypes`(`plant_id`, `container_type`, `entryBy`, `entryOn`) VALUES ('".$_GET["plant_id"]."', '".$input["container_type"]."', '".$_GET["emp_id"]."','$entry_date')";
            
            if ($conn->query($insertSql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"Error: " . $conn->error . "\"}";
            }
        }
    		
    }
    else  if ($_GET["type"] == "saveCOntainerSUbType") {
        
        $insertSql = "INSERT INTO `containerSubtypes`(`plant_id`, `container_type`, `container_subtype`, `madeUpOf`, `entryBy`, `entryOn`) VALUES  ('".$_GET["plant_id"]."', '".$input["container_type"]."',
        '".$input["container_subtype"]."', '".$input["madeUpOf"]."', '".$_GET["emp_id"]."','$entry_date')";
        
        if ($conn->query($insertSql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"Error: " . $conn->error . "\"}";
        }
        
    }
    else  if ($_GET["type"] == "getCOntainerSubtypes") {
        
    	$sql = "SELECT * from containerSubtypes where  plant_id = '".$_GET["plant_id"]."' order by container_type asc";
        $result = $conn->query($sql);
    	if($result->num_rows > 0){
    		$output = Array();
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    }
    else  if ($_GET["type"] == "getSubtypesByContainer_type") {
        
	    $sql1 = "SELECT * from containerSubtypes where  container_type = '".$_GET["container_type"]."' AND plant_id = '".$_GET["plant_id"]."' order by container_type asc";
        $result1 = $conn->query($sql1);
    	if($result1->num_rows > 0){
    		$output1 = Array();
    		while($row1 = $result1->fetch_assoc()){
    			$output1[] = $row1;
    		}
    	}
    	echo json_encode($output1);
    	
    }

    
    
    
    
    
    
    
    
    
    
    
    
    
    else if ($_GET["type"] == "save_pack_size") {
    $packSize = trim($input["pack_size"] ?? '');
    $unit = trim($input["unit"] ?? '');
    $forWhat = trim($input["forWhat"] ?? '');
    $dosageForm = trim($input["dosage_form"] ?? '');
    $packDescription = trim($input["pack_description"] ?? '');
    $plantId = $_GET["plant_id"];
    $empId = $_GET["emp_id"];

    if ($packSize === '' || $unit === '' || $forWhat === '' || $dosageForm === '') {
        echo "{\"status\":\"All fields are required\"}";
        exit;
    }

     $checkSql = "SELECT id FROM pack_size
                  WHERE plant_id = '$plantId'
                  AND dosage_form = '$dosageForm'
                  AND forWhat = '$forWhat'
                  AND pack_size = '$packSize'
                  AND (status IS NULL OR status = 'Active' OR status = '')";
    $checkResult = $conn->query($checkSql);

    if ($checkResult && $checkResult->num_rows > 0) {
          	echo "{\"status\":\"Duplicate pack size is not allowed for the same Dosage Form and Pack Size Type\"}";
    } else {
         $insertSql = "INSERT INTO pack_size (plant_id, dosage_form, forWhat, pack_size, pack_description, unit, entry_by)
                      VALUES ('$plantId', '$dosageForm', '$forWhat', '$packSize', '$packDescription', '$unit', '$empId')";

        if ($conn->query($insertSql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
}

    else  if ($_GET["type"] == "save_vendor_material") {
        try {
            // First of all, let's begin a transaction
            $conn->beginTransaction();
              $ars = $input;
            for ($i = 0; $i < count($input); $i++) {
                  $ar = $ars[$i];
                 $sql1 = "INSERT INTO material_issue (plant_id, vendor_code,material_code,entry_by, entry_date)
                VALUES ( '".$_GET["plant_id"]."','".$ar["vendor_code"]."', '".$ar["material_code"]."','".$_GET["emp_id"]."','$entry_date')";
                $conn->query($sql1);
            }
            $db->commit();
        } catch (\Throwable $e) {
            // An exception has been thrown
            // We must rollback the transaction
            $db->rollback();
            throw $e; // but the error must be handled anyway
        }
         
           
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
            
        
    }
    

}
 }catch (\Throwable $e) {
               echo "{\"statuse\":\"".$e."\"}";
             //	echo "{\"status\":\"exception\"}";
            }
$conn->close();
?>