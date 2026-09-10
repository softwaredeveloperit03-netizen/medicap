<?php
 
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

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

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if($_GET["type"]=="saveContract") {
        
       
        // Check if contract already exists (same vendor, material, group, subgroup, and overlapping dates)
        $check_sql = "SELECT id FROM purchase_rate_contract 
                      WHERE vendor_no = '".$input["vendor"]."' 
                      AND material_code = '".$input["material"]."'
                      AND main_group = '".$input["main_group"]."'
                      AND sub_group = '".$input["sub_group"]."'
                      AND status = 'Active'
                      AND (
                          (valid_from_date <= '".$input["valid_from_date"]."' AND valid_to_date >= '".$input["valid_from_date"]."') OR
                          (valid_from_date <= '".$input["valid_to_date"]."' AND valid_to_date >= '".$input["valid_to_date"]."') OR
                          (valid_from_date >= '".$input["valid_from_date"]."' AND valid_to_date <= '".$input["valid_to_date"]."')
                      )";
        
        $check_result = $conn->query($check_sql);
        if($check_result && $check_result->num_rows > 0) {
            echo "{\"status\":\"A contract already exists for this vendor, material, and date range\"}";
        } else {
            
            $sql = "INSERT INTO `purchase_rate_contract`(`plant_id`, `main_group`, `main_group_name`, `sub_group`, `sub_group_name`,
                    `vendor_no`, `vendor_name`, `material_code`, `material_name`, `rate`, `rate_uom`, `delivery_delay_time`,
                    `valid_from_date`, `valid_to_date`, `status`, `entry_by`, `entry_date`) VALUES 
                    ('".$_GET["plant_id"]."', '".$input["main_group"]."', '".$main_group_name."', '".$input["sub_group"]."', '".$sub_group_name."',
                     '".$input["vendor"]."', '".$vendor_name."', '".$input["material"]."', '".$material_name."', 
                     '".$input["rate"]."', '".$input["rate_uom"]."', '".$input["delivery_delay_time"]."',
                     '".$input["valid_from_date"]."', '".$input["valid_to_date"]."', 'Active', '".$_GET["emp_id"]."', '$entry_date')";
            
            if($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        }
    }
    
    if($_GET["type"]=="getContracts") {
        
        $where = "1=1";
        if(!empty($_GET["plant_id"])) {
            $where .= " AND plant_id = '".$_GET["plant_id"]."'";
        }
        
        $sql = "SELECT * FROM purchase_rate_contract WHERE $where ORDER BY entry_date DESC";
        $result = $conn->query($sql);
        
        $contracts = array();
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $contracts[] = $row;
            }
        }
        
        echo json_encode($contracts);
    }
    
    if($_GET["type"]=="getContractById") {
        
        $id = $_GET["id"];
        $sql = "SELECT * FROM purchase_rate_contract WHERE id = '".$id."' LIMIT 1";
        $result = $conn->query($sql);
        
        if($result && $result->num_rows > 0) {
            $contract = $result->fetch_assoc();
            echo json_encode($contract);
        } else {
            echo "{\"status\":\"Contract not found\"}";
        }
    }
    
    if($_GET["type"]=="updateContract") {
        
        // Get main group name
        $main_group_name = '';
        if(!empty($input["main_group"])) {
            $sql_group = "SELECT LglNm FROM marketing_client WHERE client_code = '".$input["main_group"]."' LIMIT 1";
            $result_group = $conn->query($sql_group);
            if($result_group && $result_group->num_rows > 0) {
                $row_group = $result_group->fetch_assoc();
                $main_group_name = $row_group["LglNm"];
            }
        }
        
        // Get sub group name
        $sub_group_name = '';
        if(!empty($input["sub_group"])) {
            $sql_subgroup = "SELECT LglNm FROM marketing_client WHERE client_code = '".$input["sub_group"]."' LIMIT 1";
            $result_subgroup = $conn->query($sql_subgroup);
            if($result_subgroup && $result_subgroup->num_rows > 0) {
                $row_subgroup = $result_subgroup->fetch_assoc();
                $sub_group_name = $row_subgroup["LglNm"];
            }
        }
        
        // Get vendor name
        $vendor_name = '';
        if(!empty($input["vendor"])) {
            $sql_vendor = "SELECT vendor_name FROM purchase_vendor WHERE vendor_no = '".$input["vendor"]."' LIMIT 1";
            $result_vendor = $conn->query($sql_vendor);
            if($result_vendor && $result_vendor->num_rows > 0) {
                $row_vendor = $result_vendor->fetch_assoc();
                $vendor_name = $row_vendor["vendor_name"];
            }
        }
        
        // Get material name
        $material_name = '';
        if(!empty($input["material"])) {
            $sql_material = "SELECT material_name FROM master_material WHERE material_code = '".$input["material"]."' LIMIT 1";
            $result_material = $conn->query($sql_material);
            if($result_material && $result_material->num_rows > 0) {
                $row_material = $result_material->fetch_assoc();
                $material_name = $row_material["material_name"];
            }
        }
        
        $status = !empty($input["status"]) ? $input["status"] : 'Active';
        
        $sql = "UPDATE purchase_rate_contract SET
                main_group = '".$input["main_group"]."',
                main_group_name = '".$main_group_name."',
                sub_group = '".$input["sub_group"]."',
                sub_group_name = '".$sub_group_name."',
                vendor_no = '".$input["vendor"]."',
                vendor_name = '".$vendor_name."',
                material_code = '".$input["material"]."',
                material_name = '".$material_name."',
                rate = '".$input["rate"]."',
                rate_uom = '".$input["rate_uom"]."',
                delivery_delay_time = '".$input["delivery_delay_time"]."',
                valid_from_date = '".$input["valid_from_date"]."',
                valid_to_date = '".$input["valid_to_date"]."',
                status = '".$status."',
                updated_by = '".$_GET["emp_id"]."',
                updated_date = '$entry_date'
                WHERE id = '".$input["id"]."'";
        
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    if($_GET["type"]=="deleteContract") {
        
        $id = $_GET["id"];
        
        // Soft delete by setting status to Inactive
        $sql = "UPDATE purchase_rate_contract SET status = 'Inactive', updated_date = '$entry_date' WHERE id = '".$id."'";
        
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
}

$conn->close();
?>
