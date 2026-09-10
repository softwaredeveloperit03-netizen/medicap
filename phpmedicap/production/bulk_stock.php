<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

 
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

 
 
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
    
    if($_GET["type"]=="saveBulkStock") {
        $bulkname = $input['bulkname'];
        $bulkcode = $input['bulkcode'];
        $arno = $input['arno'];
        $mfg_date = $input['mfg_date'];
        $exp_date = $input['exp_date'];
        $mfg_qty = $input['mfg_qty'];
        $plant_id = $_GET["plant_id"];
        
        $sql = "INSERT INTO bulk_stock (bulkname, bulkcode, arno, mfg_date, exp_date, mfg_qty, plant_id, created_by, created_date, status) 
                VALUES ('".$bulkname."', '".$bulkcode."', '".$arno."', '".$mfg_date."', '".$exp_date."', '".$mfg_qty."', '".$plant_id."', '".$_GET["emp_id"]."', '".$entry_date."', 'Active')";
        
        if($conn->query($sql) === TRUE) {
            $output = array("status" => "success", "message" => "Bulk stock saved successfully");
        } else {
            $output = array("status" => "error", "message" => "Error: " . $conn->error);
        }
        echo json_encode($output);
    } 
    else if($_GET["type"]=="getBulkStockList") {
        $output = array();
        $plant_id = $_GET["plant_id"];
        
        $sql = "SELECT bs.*, 
                COALESCE(SUM(bsu.used_qty), 0) as used_qty 
                FROM bulk_stock bs 
                LEFT JOIN bulk_stock_uses bsu ON bs.id = bsu.bulk_stock_id 
                WHERE bs.plant_id = '".$plant_id."' AND bs.status = 'Active' 
                GROUP BY bs.id 
                ORDER BY bs.id DESC";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"]=="getApprovedBulk") {
        $output = array();
        $id = $_GET["id"];
        $plant_id = $_GET["plant_id"];
        
        $sql = "SELECT * FROM bulkMaster";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output = $row;
                break;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"]=="getBulkStockDetails") {
        $output = array();
        $id = $_GET["id"];
        $plant_id = $_GET["plant_id"];
        
        $sql = "SELECT * FROM bulk_stock WHERE id = '".$id."' AND plant_id = '".$plant_id."' AND status = 'Active'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output = $row;
                break;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"]=="getBulkStockUses") {
        $output = array();
        $id = $_GET["id"];
        $plant_id = $_GET["plant_id"];
        
        $sql = "SELECT bsu.*, bs.bulkname, bs.bulkcode 
                FROM bulk_stock_uses bsu 
                LEFT JOIN bulk_stock bs ON bsu.bulk_stock_id = bs.id 
                WHERE bsu.bulk_stock_id = '".$id."' AND bsu.plant_id = '".$plant_id."' AND bsu.status = 'Active' 
                ORDER BY bsu.use_date DESC, bsu.id DESC";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else {
        $output = array("status" => "error", "message" => "Invalid type");
        echo json_encode($output);
    }
    
} else {
    $output = array("status" => "error", "message" => "Invalid token");
    echo json_encode($output);
}

?>

