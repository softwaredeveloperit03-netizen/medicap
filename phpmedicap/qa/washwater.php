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
    
    if ($_GET["type"] == "saveWash") {
        $sql="INSERT INTO washWater(equipment_code,product_code ,clean_by,entry_by,entry_date)VALUES('".$input["equipment_code"]."' ,'".$input["product_code"]."' ,'".$input["clean_by"]."' ,'".$_GET["emp_id"]."' ,'$entry_date')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getWashwater"){
        $output=array();
        $sql="SELECT w.*,p.product_name ,e.equipment_name FROM washWater w LEFT JOIN product p ON w.product_code=p.product_code LEFT JOIN equipment e ON w.equipment_code=e.equipment_code WHERE w.status='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
         echo json_encode($output);
    }else if($_GET["type"] == "updateWashwater"){
        $sql="UPDATE washWater SET status='sample_collected' ,result='".$input["result"]."' ,remark = '".$input["remark"]."', sample_collect_by='".$_GET["emp_id"]."' ,sample_date='$entry_date' WHERE id='".$_GET["id"]."' ";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }else if($_GET["type"] == "getsampleWashWater"){
        $output=array();
        $sql="SELECT w.*,p.product_name ,e.equipment_name FROM washWater w LEFT JOIN product p ON w.product_code=p.product_code LEFT JOIN equipment e ON w.equipment_code=e.equipment_code WHERE w.status='sample_collected'";
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