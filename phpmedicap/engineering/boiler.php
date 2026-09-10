<?php
//   ini_set('display_errors', 1);
//     error_reporting(E_ALL);
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

    if($_GET["type"] == "getboilerChecklistMehaApproval"){
        $output = Array();
        $sql = "SELECT * FROM BoilerChecklist where status='".$_GET["status"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else  if($_GET["type"] == "getboilerChecklistMehaLog"){
        $output = Array();
        $sql = "SELECT * FROM BoilerChecklist";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else  if($_GET["type"] == "getBoilerLofMeha"){
        $output = Array();
        $sql = "SELECT * FROM BOILEROPERATION ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   $row["Checklist"] = json_decode($row["Checklist"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"]=="saveboilerChecklistMeha"){
        
               $json_obj = json_encode($input["questions"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
         $sql="INSERT INTO BoilerChecklist (checkpoint,remark,entry_by,entry_date)VALUES( '".$values["question"]."','".$values["answer"]."', '".$_GET["emp_id"]."','$entry_date' )";

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
    else if($_GET["type"]=="saveBoilerMeha"){
        
             
         $sql="insert into BOILEROPERATION (BoilerStart,BoilerStop,entry_by,WaterHardness,BoilerCoilBackPressure,GasPressure,
            SteamTemp,SteamPressure,TotalRunningHours,entry_date,checklist)
        values('".$input['BoilerStart']."','".$input['BoilerStop']."','".$input['Started_By']."',
        '".$input['WaterHardness']."','".$input['BoilerCoilBackPressure']."','".$input['GasPressure']."',
        '".$input['SteamTemp']."','".$input['SteamPressure']."','".$input['TotalRunningHours']."','$entry_date','".json_encode($input["questions"])."' 
        )";

        if ($conn->query($sql)) {
   
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
 
    } 
        else if ($_GET["type"] == "UpdateboilerChecklistMehaStatus") {
        
        $sql = "UPDATE BoilerChecklist SET status='".$input["status"]."', approved_by='".$_GET["emp_id"]."' , approve_date='$entry_date'   WHERE id = '".$input["id"]."'";
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
        else if ($_GET["type"] == "UpdateBoilerMehaStatus") {
        
        $sql = "UPDATE BOILEROPERATION   SET status='".$input["status"]."'  WHERE id = '".$input["id"]."'";
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
 
    
    
}

$conn->close();
?>