<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];

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
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveInvestigationSampling") {
        $sql = "INSERT INTO investigation_report (plant_id,material_code,investigation_type ,initiation_date , 
        investigation_reportno ,sample_type ,sample_date,sampling_point,area ,grade, water_type ,ar_no,standard_limit ,
        alter_limit ,action_limit ,result ,product_details,product_code ,product_type ,batch_no,investigaton_date ,
        mfg_date ,in_startdate ,description,ass_batch_no,ass_item_code ,ass_material ,ass_event,ass_product,classification,
        regular_impact ,ext_impact ,impact_ass) VALUES ('".	$_GET["plant_id"]."', '".$input["product_code"]."','".$input["investigation_type"]."','".$input["initiation_date"]."','".$input["investigation_reportno"]."','".json_encode($input["sample_type"])."','".$input["sample_date"]."','".$input["sampling_point"]."','".$input["area"]."','".$input["water_type"]."', '".$input["ar_no"]."','".$input["standard_limit"]."' ,'".$input["alter_limit"]."','".$input["action_limit"]."' ,'".$input["action_limit"]."' ,'".$input["result"]."' ,'".$input["product_details"]."' ,'".$input["product_code"]."' ,'".$input["product_type"]."' ,'".$input["batch_no"]."' ,
        '".$input["investigaton_date"]."' ,'".$input["mfg_date"]."' ,'".$input["in_startdate"]."' , '".$input["description"]."' ,'".$input["ass_batch_no"]."' ,'".$input["ass_item_code"]."' ,'".$input["ass_material"]."' ,'".$input["ass_event"]."' ,'".$input["ass_product"]."' ,'".$input["classification"]."' ,'".$input["regular_impact"]."' ,'".$input["ext_impact"]."' ,'".$input["impact_ass"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getInvestigationSampling"){
        $output=Array();
        $sql="SELECT distinct  i.*,p.product_name,m.material_name FROM investigation_report i LEFT JOIN product p ON i.product_code=p.product_code LEFT JOIN material m ON i.material_code=m.material_code";
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