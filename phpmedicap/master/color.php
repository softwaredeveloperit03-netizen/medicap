<?php 
require '../db.php';
require '../token.php';
header('response_token: test123456');

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
    
    if ($_GET["type"] == "saveColor") {
        $color = isset($_GET["color"]) ? mysqli_real_escape_string($conn, $_GET["color"]) : '';
        $plant_id = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        $sql = "INSERT INTO color (plant_id, color) VALUES ('".$plant_id."','".$color."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getColors") {
        $output = Array();
        $plant_id = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        if ($plant_id !== '') {
            $conn->query("UPDATE color SET plant_id='".$plant_id."' WHERE IFNULL(plant_id,'')=''");
        }
        // Show all colors for this plant (legacy empty plant_id already backfilled above)
        if ($plant_id !== '') {
            $sql = "SELECT id, color, plant_id FROM color WHERE plant_id='".$plant_id."' AND color IS NOT NULL AND color <> '' ORDER BY color ASC";
        } else {
            $sql = "SELECT id, color, plant_id FROM color WHERE color IS NOT NULL AND color <> '' ORDER BY color ASC";
        }
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $seen = Array();
            while ($row = $result->fetch_assoc()) {
                $name = trim($row["color"]);
                $key = strtolower($name);
                if ($name === '' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $row["color"] = $name;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "savePacking") {
        $sql = "INSERT INTO packing_configuration (packing_type) VALUES ('".$_GET["pack_type"]."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "getpacking_type") {
        $output = Array();
        $sql = "SELECT packing_type FROM packing_configuration GROUP BY packing_type";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "saveprimary_packing_type") {
        $ppt = isset($_GET["primary_packing_type"]) ? trim($_GET["primary_packing_type"]) : '';
        $ppt = $conn->real_escape_string($ppt);
        if ($ppt === '') {
            echo "{\"status\":\"invalid\"}";
            exit;
        }
        $sql = "INSERT INTO packing_configuration (primary_packing_type) VALUES ('".$ppt."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "getprimary_packing_type") {
        $output = Array();
        $sql = "SELECT primary_packing_type FROM packing_configuration GROUP BY primary_packing_type";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "save_secondary_packings") {
        $sql = "INSERT INTO packing_configuration (secondary_packings ) VALUES ('".$_GET["save_secondary_packings"]."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "getsecondary_packings") {
        $output = Array();
        $sql = "SELECT secondary_packings FROM packing_configuration GROUP BY secondary_packings";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "save_pack_size") {
        $sql = "INSERT INTO packing_configuration (pack_size  ) VALUES ('".$_GET["add_pack_size"]."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "getpack_size") {
        $output = Array();
        $sql = "SELECT pack_size FROM packing_configuration GROUP BY pack_size";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "saveadd_nos_pouch") {
        $sql = "INSERT INTO packing_configuration (nos_pouch  ) VALUES ('".$_GET["add_nos_pouch"]."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "getnos_pouch") {
        $output = Array();
        $sql = "SELECT nos_pouch  FROM packing_configuration GROUP BY nos_pouch ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "add_capsule_size") {
        $sql = "INSERT INTO packing_configuration ( capsule_size  ) VALUES ('".$_GET["add_capsule_size"]."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "get_capsule_size") {
        $output = Array();
        $sql = "SELECT capsule_size  FROM packing_configuration GROUP BY capsule_size ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "add_mono_cartains") {
        $sql = "INSERT INTO packing_configuration ( mono_cartains  ) VALUES ('".$_GET["add_mono_cartains"]."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "get_add_mono_cartains") {
        $output = Array();
        $sql = "SELECT mono_cartains  FROM packing_configuration GROUP BY mono_cartains  ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "saveadd_mono_qty") {
        $sql = "INSERT INTO packing_configuration ( mono_qty   ) VALUES ('".$_GET["add_mono_qtys"]."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "get_add_mono_qtys") {
        $output = Array();
        $sql = "SELECT mono_qty   FROM packing_configuration GROUP BY mono_qty   ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "add_master_mono_qtys") {
        $sql = "INSERT INTO packing_configuration ( master_mono_qty   ) VALUES ('".$_GET["add_master_mono_qtys"]."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "get_add_master_mono_qtys") {
        $output = Array();
        $sql = "SELECT master_mono_qty   FROM packing_configuration GROUP BY master_mono_qty   ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "save_tertiary_packing") {
        $sql = "INSERT INTO packing_configuration ( tertiary_packing   ) VALUES ('".$_GET["save_tertiary_packing"]."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "get_save_tertiary_packing") {
        $output = Array();
        $sql = "SELECT tertiary_packing   FROM packing_configuration GROUP BY tertiary_packing   ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveColors") {
        
        $sql = "INSERT INTO color (plant_id,color) VALUES ('".$_GET["plant_id"]."','".$input["color"]."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }  
    else if($_GET["type"] == "getFregrence") {
        $output = Array();
        $sql = "SELECT * FROM fregrence ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveFregrence") {
        
        $sql = "INSERT INTO fregrence (fregrenceName,entryBy,entryOn) VALUES ('".$input["fregrenceName"]."','".$_GET["emp_id"]."','$entry_date')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }  

}

$conn->close();
?>