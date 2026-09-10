<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
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
    
    if ($_GET["type"] == "saveStock") {
        
        $sql = "INSERT INTO `fg_stock_book`(`plant_id`, `ar_no`, `batch_no`, `packing_type`, `stock_type`, `material_code`,
        `pack_size`, `pack_size_unit`, `qty`, `qty_unit`, `mfg_date`, `exp_date`, `status`, `entry_by`, `entry_date`,
        `approve_by`, `approve_date`, `entry_for`, `total_containers`, `yield`, `batch_size`, `rel_date`,`container_type`)
        VALUES ('".$_GET["plant_id"]."', '".$input["ar_no"]."', '".$input["batch_no"]."', '".$input["packing_type"]."', 'Opening',
        '".$input["product_code"]."', '".$input["pack_size"]."', '".$input["pack_size_unit"]."', '".$input["qty"]."','".$input["qty_unit"]."',
        '".$input["mfg_date"]."','".$input["exp_date"]."','Approved','".$_GET["emp_id"]."','".$entry_date."',
        '".$_GET["emp_id"]."', '$entry_date', 'Opening Stock', '".$input["total_containers"]."', '".$input["yield"]."', 
        '".$input["batch_size"]."','".$input["rel_date"]."','".$input["container_type"]."')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "get_stock") {
        $sql = "Select * from finish_product order by 1 desc";
     $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
  
  
    else if ($_GET["type"] == "saveFgStock") {
 
         $sql = "  INSERT INTO fg_stock_book( plant_id,product_code, batch_size, total_containers, pack_size, release_date,exp_date,mfg_date, unit, 
      qty,material_code,product_type,market_type,stock_type,entry_by, entry_date,ar_no,batch_no) VALUES ('".$_GET["plant_id"]."','".$input["product_code"]."',
      '".$input["batch_size"]."','".$input["total_containers"]."','".$input["pack_size"]."','".$input["rel_date"]."','".$input["exp_date"]."',  '".$input["mfg_date"]."', 
        'Kg','".$input["batch_size"]."','".$input["product_code"]."','".$input["product_type"]."','".$input["market_type"]."',
        '".$input["stock_type"]."','".$_GET["emp_id"]."','$entry_date','".$input["ar_no"]."','".$input["batch_no"]."')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
         
    } 
    
    
    // else if ($_GET["type"] == "get_fg_stock") {
    //     $output = Array();
    //     $sql = "SELECT * FROM manual_fg_entry";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // } 
     else if ($_GET["type"] == "get_fg_stock") {
        $sql = "SELECT fb.id, p.product_name,batch_no, stock_type, fb.plant_id, market_type, fb.material_code, ar_no, rel_date, batch_size, yield, pack_size, total_containers, mfg_date,exp_date from fg_stock_book fb join product p on fb.material_code = p.product_code order by 1 desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
        
    else if ($_GET["type"] == "saveBatches") {
          $sql = "INSERT INTO batch_no (plant_id,plan_no,batch_id,bom_no,exp_start_date,exp_end_date)
           VALUES ('".$_GET["plant_id"]."','".$input["plan_no"]."', '".$input["batch_id"]."', '".$input["bom_no"]."', '".$input["exp_start_date"]."','".$input["exp_end_date"]."')";
        //echo $sql;
        if ($conn->query($sql)) {
            $sql = "update bmr set expected_start_date= '".$input["exp_start_date"]."' , expected_end_date = '".$input["exp_end_date"]."' where id= '".$input["batch_id"]."'";
             $result = $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveApprovedBatches") {
          $sql = "Update batch_no set issue_by= '".$input["issueby"]."',issue_to= '".$input["issueto"]."',issue_date= '".$input["issue_date"]."',allocate_batch_no = '".$input["batch_no"]."',hardcopy_issue= '".$input["hardcopy_issue"]."' where batch_id= '".$input["batch_id"]."'";
        echo $sql;
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "updateBatchforProduction") {
          $sql = "Update batch_no set bmr_received_by= '".$input["bmr_received_by"]."',bmr_received_on= '".$input["bmr_received_on"]."' where batch_id= '".$input["batch_id"]."'";
       // echo $sql;
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "getAllBatches") {
        $sql = "SELECT * from batch_no order by 1 desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        if ($conn->query($sql)) {
           echo json_encode($output);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
      
    }
}

$conn->close();
?>