<?php 

function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } else if (is_string($mixed)) {
        return utf8_encode($mixed);
    }
    return $mixed;
}
require '../db.php';
require '../token.php';
header('response_token: test123456');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
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
   
    if ($_GET["type"] == "saveAssetMaster") {
        
            $lastId = 1;
            $sql = "SELECT id FROM assetMaster WHERE plant_id = '" . $_GET["plant_id"] . "' ORDER BY id DESC LIMIT 1";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $lastId = (int)$row['id'] + 1;
                }
            }
            
            // Pad ID correctly (4 digits → 0001, 0002, ...)
            $paddedId = str_pad($lastId, 3, "0", STR_PAD_LEFT);
            
            $assetPrifix = $input["assetPrifix"];
            $assetNo = $assetPrifix . $paddedId;
            
            // Check if asset already exists
            $sql1 = "SELECT * FROM assetMaster WHERE plant_id = '" . $_GET["plant_id"] . "' AND assetName = '" . $input["assetName"] . "' AND assetPrifix = '" . $input["assetPrifix"] . "'";
            
            $result1 = $conn->query($sql1);
            
            if ($result1->num_rows > 0) {
                echo "{\"status\":\"success\",\"msg\":\"Asset Is Already Saved!!!!!!!\"}";
            } else {
            
                $sql = "INSERT INTO `assetMaster` (`plant_id`, `assetNo`, `assetName`, `assetPrifix`, `status`, `entryBy`, `entryOn`) 
                        VALUES ('" . $_GET["plant_id"] . "','$assetNo','" . $input["assetName"] . "','" . $input["assetPrifix"] . "','Approved','" . $_GET["emp_id"] . "','$entry_date')";
            
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\",\"msg\":\"Asset Saved Successfully....\"}";
                } else {
                    echo "{\"status\":\"error\",\"msg\":\"" . $conn->error . "\"}";
                }
            }
            
    }  
    else if ($_GET["type"] == "getAssetsMAster") {
        
            $output = Array();
            $sql = "SELECT * FROM assetMaster WHERE status = 'Approved' AND plant_id = '".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    }    
    else if ($_GET["type"] == "saveAssetRecord") {
        
        $cost = floatval($input["costCenter"] ?? 0);
        $qty  = intval($input["noOfAssets"] ?? 1);
        
        $perPieceCost = ($qty > 0) ? ($cost / $qty) : 0;

        $sql = " INSERT INTO `assetRecord`(`plant_id`, `assetNo`, `department`,`location`,`descOfAsset` , `conditionOfAsset` , `noOfAssets` ,`costCenter` ,`perPeiceCost` , `status`,`entryBy`, `entryOn`) VALUES ('".$_GET["plant_id"]."','".$input["assetNo"]."', 
        '".$input["department"]."', '".$input["location"]."', '".$input["descOfAsset"]."', '".$input["conditionOfAsset"]."','".$input["noOfAssets"]."','".$input["costCenter"]."', '$perPieceCost', 'Approved', '".$_GET["emp_id"]."','".$entry_date."')";
                     
        if($conn->query($sql)){ 
            echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    
    
    else if ($_GET["type"] == "getAssetRecord") {
        
            $output = Array();
            
            if($_GET['dept'] == 'ALL'){
                
                $sql = "SELECT a.*,ass.assetName FROM assetRecord a  left join assetMaster ass ON a.assetNo = ass.assetNo WHERE a.status = 'Approved' AND a.plant_id = '".$_GET["plant_id"]."' ";
                    
            }else{
                
                $sql = "SELECT a.*,ass.assetName FROM assetRecord a  left join assetMaster ass ON a.assetNo = ass.assetNo WHERE a.status = 'Approved' AND a.plant_id = '".$_GET["plant_id"]."'  AND a.department = '".$_GET["dept"]."' ";
                    
            }
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    else if ($_GET["type"] == "getAssetByDepartmentAndLocaton") {
        
            $output = Array();
            
            $sql = "SELECT a.*,ass.assetName FROM assetRecord a  left join assetMaster ass ON a.assetNo = ass.assetNo WHERE a.status = 'Approved' AND a.plant_id = '".$_GET["plant_id"]."'  AND a.department = '".$_GET["dept"]."' AND a.location = '".$_GET["location"]."' ";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    
    
    
    else if ($_GET["type"] == "getDepartmentsWithSections") {
        
            $output = Array();
            
            $sql = "SELECT department_name FROM department order by department_name asc";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    $output1 = Array();
                    $sql1 = "SELECT department,section_name FROM section where  department = '".$row["department_name"]."'  order by section_name asc";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    
                    $row['locations'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    }


    else if ($_GET["type"] == "updateMasterMaterialPurchaseStatus") {
        
          
            
       $sql="UPDATE material SET indend_prepare_date='".$input["indend_prepare_date"]."',Purchase_prepare_date='".$input["Purchase_prepare_date"]."',moisture='".$input["moisture"]."',Sampling_prepare_date='".$input["Sampling_prepare_date"]."',release_prepare_date='".$input["release_prepare_date"]."',PurchaseDeliveryTime='".$input["PurchaseDeliveryTime"]."',ForPayment='".$input["ForPayment"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
     else if ($_GET["type"] == "saveMaintenanceAssetRecord") {

    // Ensure input array exists
    if(!$input){
        $input = [];
    }

    // Convert empty values to integer (prevents MySQL error)
    $noOfAssets = isset($input["noOfAssets"]) && $input["noOfAssets"] != "" ? intval($input["noOfAssets"]) : 0;
    $noOfScrapAssets = isset($input["noOfScrapAssets"]) && $input["noOfScrapAssets"] != "" ? intval($input["noOfScrapAssets"]) : 0;

    // Optional: prevent undefined index warnings
    $assetNo = isset($input["assetNo"]) ? $input["assetNo"] : '';
    $department = isset($input["department"]) ? $input["department"] : '';
    $location = isset($input["location"]) ? $input["location"] : '';
    $descOfmain = isset($input["descOfmain"]) ? $input["descOfmain"] : '';
    $remark = isset($input["remark"]) ? $input["remark"] : '';
    $scrapAsset = isset($input["scrapAsset"]) ? $input["scrapAsset"] : '';
    $scrapRemark = isset($input["scrapRemark"]) ? $input["scrapRemark"] : '';

    $sql = "INSERT INTO `assetMaintenance`
            (`plant_id`, `assetNo`, `department`, `location`, `no_of_assets`,
             `main_desc`, `remark`, `make_scrap`, `scrap_remark`,
             `scrap_asset_no`, `entryBy`, `entryOn`)
            VALUES
            ('".$_GET["plant_id"]."',
             '".$assetNo."',
             '".$department."',
             '".$location."',
             '".$noOfAssets."',
             '".$descOfmain."',
             '".$remark."',
             '".$scrapAsset."',
             '".$scrapRemark."',
             '".$noOfScrapAssets."',
             '".$_GET["emp_id"]."',
             '".$entry_date."')";

    if($conn->query($sql)){
        echo json_encode(["status"=>"success"]);
    }else{
        echo json_encode(["status"=>$conn->error]);
    }
}
    
    else if ($_GET["type"] == "getMaintenanceAssetRecord") {
        
            $output = Array();
            
            if($_GET['dept'] == 'ALL'){
                
                $sql = "SELECT a.*,ass.assetName FROM assetMaintenance a  left join assetMaster ass ON a.assetNo = ass.assetNo WHERE  a.plant_id = '".$_GET["plant_id"]."' ";
                    
            }else{
                
                $sql = "SELECT a.*,ass.assetName FROM assetMaintenance a  left join assetMaster ass ON a.assetNo = ass.assetNo WHERE  a.plant_id = '".$_GET["plant_id"]."'  AND a.department = '".$_GET["dept"]."' ";
                    
            }
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getScrapAssetRecord") {
        
            $output = Array();
            
            if($_GET['dept'] == 'ALL'){
                
                $sql = "SELECT a.*,ass.assetName FROM assetMaintenance a  left join assetMaster ass ON a.assetNo = ass.assetNo WHERE a.make_scrap = 'Yes' AND  a.plant_id = '".$_GET["plant_id"]."' ";
                    
            }else{
                
                $sql = "SELECT a.*,ass.assetName FROM assetMaintenance a  left join assetMaster ass ON a.assetNo = ass.assetNo WHERE a.make_scrap = 'Yes' AND a.plant_id = '".$_GET["plant_id"]."'  AND a.department = '".$_GET["dept"]."' ";
                    
            }
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    }
 
}

$conn->close();
?>