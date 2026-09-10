<?php 
require 'db.php';
require 'token.php';
require 'tcpdf/tcpdf.php';
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
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if($_GET["type"] == "save"){ 
        ini_set('display_errors', 1);
error_reporting(E_ALL);
        if($input['module']='MasterModule'){
        $json_obj = json_encode($input["Masatersubmodules"]);
        }
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
  $sql = "INSERT INTO software_restriction (plant_id, module, submodule, action, 
        entry_by,entry_date)
       VALUES ('".$_GET["plant_id"]."','".$values["Module"]."','".$values["name"]."','".$values["Action"]."','".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }

else if($_GET["type"] == "get_modules_list"){
    
        $sql = "SELECT module FROM  software_restriction group by  module";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
}
else if($_GET["type"] == "get_submodules_list"){
    
        $sql = "SELECT submodule FROM  software_restriction where module='".$_GET['module']."' group by  submodule";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
}












}







else {
    echo "Invalid Token";
}

$conn->close();
// // $qc->close();
// $store->close();
// $purchase->close();
// $security->close();
// $qa->close();
// $hr->close();
?>