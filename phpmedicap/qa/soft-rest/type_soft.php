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
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "getSoftware_restrication") {
        $sql = "SELECT * FROM software_restrication";
        $result = $conn->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    }if ($_GET["type"] == "getSoftware_restrication") {
        $sql = "SELECT * FROM software_restrication";
        $result = $conn->query($sql);
        $data = Array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              $data[] = $row;  
            }
        }
        echo json_encode($data);
    } else if ($_GET["type"] == "saveSoftware_restrication") {
        $training_no = 1;
        $sql = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'training_needs'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $training_no = $row["AUTO_INCREMENT"];
            }
        }
        $sql = "INSERT INTO software_restrication (plant_id, software_type, software_master, approved_by, date_of_approved, date_of_edited_by, software_type_edit) VALUES ('".$_GET["plant_id"]."','".$input["software_type"]."','".$input["software_master"]."', '".$input["approved_by"]."','".$input["date_of_approved"]."','".$input["software_type_edit"]."')";
        if ($conn->query($sql) === TRUE) {
            $data = $input["employees"];
            for ($i = 0; $i < count($data); $i++) {
                $temp = $data[$i];
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
else if ($_GET["type"] == "saveSoftware_restrication") {
    $sql = "UPDATE software_restrication SET status='".$input["status"]."', date_of_edited_by='$entry_date' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}  

}else {
    echo "Invalid Token";
}

$conn->close();
?>