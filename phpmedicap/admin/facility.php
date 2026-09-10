<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

$output = Array();
$token = $_GET["token"];
 $currentUrl =$_GET["description"];



$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
 $entry_date = date("Y-m-d h:i:s", $timestamp);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0)
{
    while($row = $result->fetch_assoc())
    {
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "save_repair_record") 
    {
          $sql = "INSERT INTO repair_record (plant_id , record_date, record_id, facility_name, facility_type, maintenance_team, 
          maintance_requester,repair_desc,requested_date,complted_date,ext_maintainance,in_maintainance,ele_system,hvac_system,equp_machine,cle_sani,grounds_maintainance,remarks) 
     VALUES ('".$_GET["plant_id"]."','".$input["record_date"]."', '".$input["record_id"]."', '".$input["facility_name"]."', '".$input["facility_type"]."', '".$input["maintenance_team"]."',
        '".$input["maintance_requester"]."', '".$input["repair_desc"]."', '".$input["requested_date"]."', '".$input["complted_date"]."', '".$input["ext_maintainance"]."', '".$input["in_maintainance"]."',
        '".$input["ele_system"]."', '".$input["hvac_system"]."', '".$input["equp_machine"]."', '".$input["cle_sani"]."', '".$input["grounds_maintainance"]."', '".$input["remarks"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
      else if ($_GET["type"] == "get_repair_record") {
    
            $sql = "SELECT * FROM repair_record order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    
    else if ($_GET["type"] == "save_paint_wall") 
    { 
         $input = $_POST;
        $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["attachment"])) {
            $file_tmp =$_FILES['attachment']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachment']['name'])));
            $file_name = $emp_id."attachment.".$file_ext;
            $attachment = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
    
          $sql = "INSERT INTO paint_wall (plant_id , maintainance_date, maint_id, facility_name, area, maint_cat, 
          maint_desc,maint_team,products,hours_spent,complete_date,inspect_repairs,cracks_check,clean_shutters,inspect_walls,water_check,
          proper_verify,total_cost,parts,attachment) 
     VALUES ('".$_GET["plant_id"]."','".$input["maintainance_date"]."', '".$input["maint_id"]."', '".$input["facility_name"]."', '".$input["area"]."', '".$input["maint_cat"]."',
        '".$input["maint_desc"]."', '".$input["maint_team"]."', '".$input["products"]."', '".$input["hours_spent"]."', '".$input["complete_date"]."', '".$input["inspect_repairs"]."',
        '".$input["cracks_check"]."', '".$input["clean_shutters"]."', '".$input["inspect_walls"]."', '".$input["water_check"]."', 
        '".$input["proper_verify"]."', '".$input["total_cost"]."','".$input["parts"]."','$attachment')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "get_paint_wall") {
    
            $sql = "SELECT * FROM paint_wall order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    
    else if ($_GET["type"] == "save_electronics") 
    { 
         $input = $_POST;
        $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["attachment"])) {
            $file_tmp =$_FILES['attachment']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachment']['name'])));
            $file_name = $emp_id."attachment.".$file_ext;
            $attachment = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
    
          $sql = "INSERT INTO electrical_maintainance (plant_id , maint_id, maint_date, facility_name, area, equp_name, 
          equp_id,equp_desc,maint_cat,maint_desc,maint_team,materials_used,hour_spent,completed_date,inspect_wiring,test_rest,
          proper_ensure,total_cost,parts,attachment) 
     VALUES ('".$_GET["plant_id"]."','".$input["maint_id"]."', '".$input["maint_date"]."', '".$input["facility_name"]."', '".$input["area"]."', '".$input["equp_name"]."',
        '".$input["equp_id"]."', '".$input["equp_desc"]."', '".$input["maint_cat"]."', '".$input["maint_desc"]."', '".$input["maint_team"]."', '".$input["materials_used"]."',
        '".$input["hour_spent"]."', '".$input["completed_date"]."', '".$input["inspect_wiring"]."', '".$input["test_rest"]."', 
        '".$input["proper_ensure"]."', '".$input["total_cost"]."','".$input["parts"]."','$attachment')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
     else if ($_GET["type"] == "get_electrical") {
    
            $sql = "SELECT * FROM electrical_maintainance order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    
     else if ($_GET["type"] == "save_major_maintainance") 
    { 
         $input = $_POST;
        $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["attachment"])) {
            $file_tmp =$_FILES['attachment']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachment']['name'])));
            $file_name = $emp_id."attachment.".$file_ext;
            $attachment = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
    
          $sql = "INSERT INTO major_maintainance (plant_id , maint_id, maint_date, facility_name, area, maint_cat, 
          main_desc,maint_team,team_leader,regular_schedule,replace_air,unsual_check,regulatory_inspect,lubricate,prev_schedule,parts_used,
          equp_use,total_cost,budget,attachment,maint_status) 
     VALUES ('".$_GET["plant_id"]."','".$input["maint_id"]."', '".$input["maint_date"]."', '".$input["facility_name"]."', '".$input["area"]."', '".$input["maint_cat"]."',
        '".$input["main_desc"]."', '".$input["maint_team"]."', '".$input["team_leader"]."', '".$input["regular_schedule"]."', '".$input["replace_air"]."', '".$input["unsual_check"]."',
        '".$input["regulatory_inspect"]."', '".$input["lubricate"]."', '".$input["prev_schedule"]."', '".$input["parts_used"]."', 
        '".$input["equp_use"]."', '".$input["total_cost"]."','".$input["budget"]."','$attachment','".$input["maint_status"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
     else if ($_GET["type"] == "get_major_maintainance") {
    
            $sql = "SELECT * FROM major_maintainance order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    
     else if ($_GET["type"] == "save_cleaning") 
    { 
         $input = $_POST;
        $emp_id=$_GET["emp_id"];
        	if(isset($_FILES["attachment"])) {
            $file_tmp =$_FILES['attachment']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachment']['name'])));
            $file_name = $emp_id."attachment.".$file_ext;
            $attachment = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qa/".$file_name);
        }
    
          $sql = "INSERT INTO cleaning (plant_id , cleaning_id, cleaning_date, facility_name, area, cleaning_category, 
          cleaning_desc,cleaning_staff,team_leader,establish,proper_ensure,clean_sanitize,trim_trees,any_check,inspect_maintain,cleaning_products,
          quantity,attachment,maint_status) 
     VALUES ('".$_GET["plant_id"]."','".$input["cleaning_id"]."', '".$input["cleaning_date"]."', '".$input["facility_name"]."', '".$input["area"]."', '".$input["cleaning_category"]."',
        '".$input["cleaning_desc"]."', '".$input["cleaning_staff"]."', '".$input["team_leader"]."', '".$input["establish"]."', '".$input["proper_ensure"]."', '".$input["clean_sanitize"]."',
        '".$input["trim_trees"]."', '".$input["any_check"]."', '".$input["inspect_maintain"]."', '".$input["cleaning_products"]."', 
        '".$input["quantity"]."', '$attachment','".$input["maint_status"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
     else if ($_GET["type"] == "get_cleaning") {
    
            $sql = "SELECT * FROM cleaning order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    
    else if ($_GET["type"] == "save_disater") 
    {
        $input = $_POST;
$emp_id = $_GET["emp_id"];
$attachment = "";

if(isset($_FILES["attachment"]) && $_FILES["attachment"]["name"] != "") {
    $file_tmp = $_FILES['attachment']['tmp_name'];
    $file_ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
    $file_name = $emp_id . "_attachment." . $file_ext;
    $attachment = $file_name;

    move_uploaded_file($file_tmp, "../../../upload/qa/" . $file_name);
}

$sql = "INSERT INTO dieaster_management 
(plant_id, plan_date, facility_name, contact_info, dieaster_type, 
dieaster_desc, emergency_member, mo_no, `procedure`, evacuation_route, 
emergency_list, emergency_supplies, any_check, attachment)

VALUES 
('".$_GET["plant_id"]."', '".$input["plan_date"]."', '".$input["facility_name"]."', '".$input["contact_info"]."', '".$input["dieaster_type"]."',
'".$input["dieaster_desc"]."', '".$input["emergency_member"]."', '".$input["mo_no"]."', '".$input["procedure"]."',
'".$input["evacuation_route"]."', '".$input["emergency_list"]."', '".$input["emergency_supplies"]."',
'".$input["any_check"]."', '".$attachment."')";
      
if ($conn->query($sql)) {
    echo "{\"status\":\"success\"}";
} else {
    echo "{\"status\":\"".$conn->error."\"}";
}}
    
     else if ($_GET["type"] == "get_disaster") {
    
            $sql = "SELECT * FROM dieaster_management order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
}
$conn->close();
?>