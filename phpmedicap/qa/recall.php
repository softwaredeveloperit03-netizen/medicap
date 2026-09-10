<?php 
require '../db.php';
require '../token.php';
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
    
     if ($_GET["type"] == "saveProductRecall") {
        $sql = "INSERT INTO product_recall (training,training1,capa,product_name, batch_no, mfg_date, exp_date, coordinator, company_person,
        email,mobile,mock_recall,public_recall,professional_recall,enforcement_auth_recall,
        television,radio,newspaper,cfagents,stockiest,distributors,
        mfg_error,packing_error,side_effect,abnormal_stability,degradation,contamination_product,release_change,
        packing_defect,
        overprint_error_price,overprint_error_batch,overprint_error_exp,other_reason,product_receive,
        received_quantity,examination_report,destruction_report) 
        VALUES ('".$_POST["training"]."','".$_POST["training1"]."','".$_POST["capa"]."','".$_POST["product_name"]."','".$_POST["batch_no"]."', '".$_POST["mfg_date"]."', '".$_POST["exp_date"]."', 
        '".$_POST["coordinator"]."', '".$_POST["company_person"]."', '".$_POST["email"]."','".$_POST["mobile"]."',
        '".$_POST["mock_recall"]."','".$_POST["public_recall"]."','".$_POST["professional_recall"]."',
        '".$_POST["enforcement_auth_recall"]."','".$_POST["television"]."','".$_POST["radio"]."',
        '".$_POST["newspaper"]."','".$_POST["cfagents"]."','".$_GET["stockiest"]."','".$_GET["distributors"]."',
        '".$_GET["mfg_error"]."','".$_GET["packing_error"]."','".$_GET["side_effect"]."','".$_GET["abnormal_stability"]."',
        '".$_GET["degradation"]."','".$_GET["contamination_product"]."','".$_GET["release_change"]."',
        '".$_GET["packing_defect"]."','".$_GET["overprint_error_price"]."','".$_GET["overprint_error_batch"]."',
        '".$_GET["overprint_error_exp"]."',
        '".$_GET["other_reason"]."','".$_GET["product_receive"]."','".$_GET["received_quantity"]."',
        '".$_GET["examination_report"]."','".$_GET["destruction_report"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        } else if ($_GET["type"] == "getProductRecall") {
        $output = Array();
        $sql = "SELECT * FROM product_recall";
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