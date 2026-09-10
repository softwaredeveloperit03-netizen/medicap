<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
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
    
    if ($_GET["type"] == "save_culture") {
        $sql = "INSERT INTO master_culture (name_of_organism,atcc_no,macroscopic_features,macroscopic_features_gram_staining,maintenance_medium,
        temperature_incubation_time,gst_per,hsn_code,entry_by,entry_date) 
        VALUES ('".$input["name_of_organism"]."','".$input["atcc_no"]."','".$input["macroscopic_features"]."','".$input["macroscopic_features_gram_staining"]."',
        '".$input["maintenance_medium"]."', '".$input["temperature_incubation_time"]."', '".$input["gst_per"]."', '".$input["hsn_code"]."',
        '".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } else if ($_GET["type"] == "save_media") {
        $sql = "INSERT INTO master_media (media_name,media_code,media_description,gst_per,hsn_code,entry_by,entry_date) 
        VALUES ('".$input["media_name"]."','".$input["media_code"]."','".$input["media_description"]."','".$input["macroscopic_features_gram_staining"]."',
        '".$input["gst_per"]."', '".$input["hsn_code"]."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }

}

$conn->close();
?>