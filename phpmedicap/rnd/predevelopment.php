<?php
    require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    
//      ini_set('display_errors', 1);
//  error_reporting(E_ALL);
 
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
    
    if ($_GET["type"] == "savePredevelopment") {
        
        $sql = "INSERT INTO `rnd_predev`(`feasibilityFormNo`, `product_name`, `proposed_route`, `dosage_form`, `delivery_system`, `strength`, `unit`, `container_closure`, `quality_criteria`, `sterility`, `stability`, `temp`, `humidity`, 
        `status`, `entry_by`, `entry_date`, `plant_id`) VALUES ('".$input["feasibilityFormNo"]."','".$input["product_name"]."','".$input["proposed_route"]."','".$input["dosage_form"]."','".$input["delivery_system"]."',
        '".$input["strength"]."','".$input["unit"]."','".$input["container_closure"]."','".$input["quality_criteria"]."','".$input["sterility"]."','".$input["stability"]."','".$input["temp"]."','".$input["humidity"]."', 'Pending',
        '".$_GET["emp_id"]."', '$entry_date','".$_GET["plant_id"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "submitRequirementForDevelopementReques1t") {
        
        
        $rawMaterialList = $input["rawMaterialList"];
        
        foreach($rawMaterialList as $values){
        

 
            $sql = "INSERT INTO `requirementMaterialFromRnd`(`plant_id`,`feasibilityFormNo`,  `status`, `material_id`, `requestTo`, `material_type`, `material_code`, `requestQty`, `reqUnit`, `requestBy`, `requestOn`) 
            VALUES ('".$_GET["plant_id"]."','".$input["feasibilityFormNo"]."','Pending', '".$values["id"]."','STORE','".$values["material_type"]."','".$values["material_code"]."', '".$values["requestQty"]."',
            '".$values["reqUnit"]."','".$_GET["emp_id"]."', '$entry_date' )";
            
            if ($conn->query($sql)) {
                $status = "success";
            } else {
                $status = $conn->error;
            }
            
            
            if($values['is'] == 'NEW'){
                
                $mat_sql = "INSERT INTO `tentitiveUnitFormulaMaterial`(`plant_id`, `mfr_no`, `material_type`, `material_subtype`, `material_code`, `qty`, `unit`, 
                `overages_per`, `total_qty`,`contriToYield`,`formulaPer`, `entryOn`) VALUES ('".mysqli_real_escape_string($conn, $_GET["plant_id"])."', '".mysqli_real_escape_string($conn, $values["mfr_no"])."',
                '".mysqli_real_escape_string($conn, $values["material_type"])."', '".mysqli_real_escape_string($conn, $values["material_subtype"])."', '".mysqli_real_escape_string($conn, $values["material_code"])."',
                '".mysqli_real_escape_string($conn, $values["qty"])."', '".mysqli_real_escape_string($conn, $values["unit"])."', '".mysqli_real_escape_string($conn, $values["overages_per"])."',
                '".mysqli_real_escape_string($conn, $values["total_qty"])."', '".mysqli_real_escape_string($conn, $values["contriToYield"])."', '".mysqli_real_escape_string($conn, $values["formulaPer"])."', '$entry_date')";
                    
                $conn->query($mat_sql);
                
            } 

        
            
        }
        
        $packingMaterialList = $input["packingMaterialList"];
        
        foreach($packingMaterialList as $values1){
            
            
            if($values1["newPackMatDevelopment"] == 'YES'){
                $requestTo = 'NPD';
            }else{
                $requestTo = 'STORE';
            }

            $sql = "INSERT INTO `requirementMaterialFromRnd`(`plant_id`,`feasibilityFormNo`, `status`, `material_id`, `requestTo`, `material_type`, `material_code`,  `requestQty`, `reqUnit`, `requestBy`, `requestOn`) 
            VALUES ('".$_GET["plant_id"]."','".$input["feasibilityFormNo"]."', 'Pending', '".$values1["id"]."','STORE','".$values1["material_type"]."','".$values1["material_code"]."', '".$values1["requestQty"]."','".$values1["reqUnit"]."',
            '".$_GET["emp_id"]."', '$entry_date' )";
            
            if ($conn->query($sql)) {
                $status1 = "success";
            } else {
                $status1 = $conn->error;
            }
            
        }
        
        if ($status == 'success' && $status1 == 'success') {
            
            $sql = "UPDATE `tentitiveUnitformula` SET  `status` = 'Requirement_Sent' WHERE id = '".$input["formulaId"]."' ";
            
            if ($conn->query($sql)) {
                 echo "{\"status\":\"success\"}";
            } else {
                $status = $conn->error;
            }
           
        } else {
            $status = $conn->error;
        }

    } 
    else if ($_GET["type"] == "getPredevelopments") {
        
        $output = array();
        $sql = "SELECT * FROM rnd_predev WHERE user_no = '".$_GET["user_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
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