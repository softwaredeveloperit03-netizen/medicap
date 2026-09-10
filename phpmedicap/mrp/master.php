<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// // also log errors to a file
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/mrp-error.log');



require '../db.php';
require '../token.php';
// require '../tcpdf/tcpdf.php';
 

header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
 
 
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
    
    
          if ($_GET["type"] == "GetProducts_WithStages") {
      
      $output = [];
 

$sql = "select * from product;";

// $sql = "select * from product where product_type='Branded'";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
         $output1 = Array();
            $sql1="SELECT *,b.stages as stageName FROM   manufacturing_process a left join manufacturing_process_stages b on a.id=b.manufacturing_process_id where a.product_code='".$row['product_code']."'  ";
          $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                 $row["stages_status"]='Procced';
                                $output1[] = $row1;
                            }
                        }else{
                            $row["stages_status"]='Cannot Procced';
                        }


                     $row["stages"] = $output1;
         $output2 = Array();
            $sql2="SELECT *
                    FROM product_stage_line_master
                    WHERE product_code = '".$row['product_code']."'
                      AND plant_id = '".$_GET['plant_id']."'
                      AND version = (
                          SELECT MAX(version)
                          FROM product_stage_line_master
                          WHERE product_code = '".$row['product_code']."'
                      AND plant_id = '".$_GET['plant_id']."'
                      );
 ";
          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                 $row["stages_Prepared"]='Yes';
                                $output2[] = $row2;
                            }
                        }else{
                            $row["stages_Prepared"]='No';
                        }


                     $row["Prepaed_data"] = $output2;
                     $row["stages"] = $output1;
            $output[] = $row;
       
}
}

 

echo json_encode($output);

  }
  
  
            else if ($_GET["type"] == "SaveStageMasterForProduct") {
                $json_obj = json_encode($input["stages"]);
                $array = json_decode($json_obj, true);
            
                $status1 = false;
            
                // Get the current max version for this product & plant
                $result = $conn->query("SELECT MAX(version) as max_version FROM product_stage_line_master WHERE product_code='" . $_GET['product_code'] . "' AND plant_id='" . $_GET['plant_id'] . "'");
                $row = $result->fetch_assoc();
                $nextVersion = $row['max_version'] ? $row['max_version'] + 1 : 1;
            
                foreach ($array as $values) {
                    $sql = "INSERT INTO `product_stage_line_master` (`plant_id`, `product_code`, `stageName`, `duration`, `version`) 
                            VALUES ('".$_GET['plant_id']."', '".$values['product_code']."', '".$values['stageName']."', '".$values['duration']."', '".$nextVersion."')";
            
                    if ($conn->query($sql)) {
                        $status1 = true;
                    } else {
                        $status1 = false;
                        break; // stop on first failure
                    }
                }
            
                if ($status1) {
                    echo json_encode(["status" => "success", "version" => $nextVersion]);
                } else {
                    echo json_encode(["status" => "error", "message" => $conn->error]);
                }
            }

      


}

$conn->close();
?>