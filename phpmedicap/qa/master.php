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
    
    if ($_GET["type"] == "saveShelf") {
        
        $sql = "INSERT INTO `shelf_life`(`plant_id`, `shelf_life`, `entry_by`, `entry_date`) VALUES  
        ('".$_GET["plant_id"]."','".$input["shelf_life"]."','".$_GET["emp_id"]."','$entry_date')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
      else if ($_GET["type"] == "saveboiler") {
  $sql = "INSERT INTO boiler_new ( plant_id,file,reson,compilance_status,inspection_due_date,inspection_date,
  mentance,out_service,operational,safty_features,relief_value,safty_value,emg_shutdown,install_date,mfg,capcity,
  boiler_type,f_name,l_name,boiler_code) VALUES ( '".$_GET["plant_id"]."','".$input["file"]."', 
        '".$input["reson"]."', '".$input["compilance_status"]."', '".$input["inspection_due_date"]."', '".$input["inspection_date"]."', 
        '".$input["mentance"]."', '".$input["out_service"]."', '".$input["operational"]."', '".$input["safty_features"]."', '".$input["relief_value"]."', 
        '".$input["safty_value"]."', '".$input["emg_shutdown"]."', '".$input["install_date"]."', '".$input["mfg"]."', '".$input["capcity"]."', 
        '".$input["boiler_type"]."', '".$input["f_name"]."', '".$input["l_name"]."', '".$input["boiler_code"]."')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
      else if ($_GET["type"] == "savelocation") {
  $sql = "INSERT INTO boiler_location ( plant_id,boiler_code,l_name,f_name,boiler_type,capcity,mfg,
  install_date,emg_shutdown,safty_value,relief_value,safty_features,operational,out_service,mentance,inspection_date,
  inspection_due_date,compilance_status,reson,file) VALUES 
  ( '".$_GET["plant_id"]."','".$input["reson"]."',  '".$input["file"]."' , 
  '".$input["l_name"]."', '".$input["f_name"]."', '".$input["boiler_type"]."', '".$input["capcity"]."', 
  '".$input["mfg"]."', '".$input["install_date"]."', '".$input["emg_shutdown"]."','".$input["safty_value"]."', 
  '".$input["relief_value"]."', '".$input["safty_features"]."', '".$input["operational"]."', '".$input["out_service"]."', 
        '".$input["mentance"]."', '".$input["inspection_date"]."', '".$input["inspection_due_date"]."', '".$input["compilance_status"]."')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getShelfs") {
        $output = array();
        $sql = "SELECT *, shelf_life as shelf FROM shelf_life
                WHERE TRIM(IFNULL(shelf_life,'')) <> ''
                ORDER BY CAST(NULLIF(REGEXP_REPLACE(shelf_life, '[^0-9]', ''), '') AS UNSIGNED) ASC, id ASC";
        $result = @$conn->query($sql);
        if (!$result) {
            // Fallback if REGEXP_REPLACE unavailable
            $sql = "SELECT *, shelf_life as shelf FROM shelf_life WHERE TRIM(IFNULL(shelf_life,'')) <> '' ORDER BY id ASC";
            $result = @$conn->query($sql);
        }
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $shelf = trim((string)($row['shelf'] ?? $row['shelf_life'] ?? ''));
                if ($shelf === '') {
                    continue;
                }
                $row['shelf'] = $shelf;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveThera") {
        $sql = "INSERT INTO thera (thera) VALUES ('".$_GET["thera"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getTheras") {
        $output = array();
        $sql = "SELECT * FROM thera";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveSpecialGrade") {
        $sql = "INSERT INTO special_grade (grade) VALUES ('".$_GET["grade"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getSpecialGrades") {
        $output = array();
        $sql = "SELECT * FROM special_grade";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveStorageCondition") {
        $sql = "INSERT INTO storage_condition (storage_condition) VALUES ('".$_GET["storage_condition"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getStorageConditions") {
        $output = array();
        $plant_id = isset($_GET["plant_id"]) ? trim($_GET["plant_id"]) : '';
        if ($plant_id !== '') {
            $pid = $conn->real_escape_string($plant_id);
            $sql = "SELECT * FROM storage_conditions WHERE CAST(plant_id AS CHAR)='".$pid."' ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
        if (count($output) === 0) {
            $sql = "SELECT * FROM storage_conditions ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveRoom") {
        $sql = "INSERT INTO room (material_type, room) VALUES ('".$_GET["material_type"]."','".$_GET["room"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getRoom") {
        $output = array();
        $sql = "SELECT * FROM room WHERE material_type='".$_GET["material_type"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "saveStyle") {
        $sql = "INSERT INTO packing_style (style) VALUES ('".$_GET["style"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getStyles") {
        $output = array();
        $sql = "SELECT * FROM packing_style";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

} else {
    echo "Invalid Token";
}

$conn->close();
?>