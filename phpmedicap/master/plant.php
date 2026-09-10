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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    
       if ($_GET["type"] == "getPlant") {
             $show_corporate = $_GET["show_corporate"];
        $output = array();
       $sql = "SELECT * FROM plant";
      // echo $sql;
       if($show_corporate==1){
      // $sql="SELECT * FROM plant";
       }else{
           $sql="SELECT * FROM plant where is_corporate=0";
       }
       //echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveIndendPO") {
        $sql = "INSERT INTO plant (plant_id, plant_code, plant_name, entry_by, entry_date, status, is_corporate) 
        VALUES ('".$_GET["plant_id"]."', '".$input["plant_code"]."', '".$input["plant_name"]."', '".$_GET["emp_id"]."','$entry_date', '".$input["status"]."', '".$input["is_corporate"]."')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            echo "{\"status\":\"success\"}";

          
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
$conn->close();
?>