<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
 $entry_date = date("Y-m-d h:i:s", $timestamp);
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
    
 
    
    if ($_GET["type"] == "save_daily_form"){
          $sql = "INSERT INTO daily (plant_id , facility_name, location, date, cleaing_task, cleaning_method, 
        strt_time,end_time,cleaning_solution,cleaning_concentration,inspection,  
        obnormalities,action_taken,add_notes,cleaning_person_name,supervisor_name,type)
     VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["location"]."', '".$input["date"]."', '".$input["cleaing_task"]."', '".$input["cleaning_method"]."',
        '".$input["strt_time"]."', '".$input["end_time"]."', '".$input["cleaning_solution"]."', '".$input["cleaning_concentration"]."', '".$input["inspection"]."',
        '".$input["obnormalities"]."','".$input["action_taken"]."','".$input["add_notes"]."','".$input["cleaning_person_name"]."','".$input["supervisor_name"]."','Daily')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

    else if ($_GET["type"] == "get_daily_form") {
        
                $sql = "SELECT * FROM daily where type='Daily' order by id desc";
           	$result = $conn->query($sql);
        	$output = Array();
        	if($result->num_rows > 0){
        		while ($row = $result->fetch_assoc()) {
        		    $output[] = $row;
        		}
        	}
        	echo json_encode($output);
    }

else if ($_GET["type"] == "save_weekly_form") 
    {
          $sql = "INSERT INTO daily (plant_id , facility_name, location, date, cleaing_task, cleaning_method, 
        strt_time,end_time,cleaning_solution,cleaning_concentration,inspection,  
        obnormalities,action_taken,add_notes,cleaning_person_name,supervisor_name,type)
     VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["location"]."', '".$input["week_date"]."', '".$input["cleaing_task"]."', '".$input["cleaning_method"]."',
        '".$input["strt_time"]."', '".$input["end_time"]."', '".$input["cleaning_solution"]."', '".$input["cleaning_concentration"]."', '".$input["inspection"]."',
        '".$input["obnormalities"]."','".$input["action_taken"]."','".$input["add_notes"]."','".$input["cleaning_person_name"]."','".$input["supervisor_name"]."','Weekly')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
else if ($_GET["type"] == "get_weekly") {
    
            $sql = "SELECT * FROM daily where type='Weekly' order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}

else if ($_GET["type"] == "save_area_facility") 
    {
          $sql = "INSERT INTO area_facility (plant_id , facility_name, location, area_name, area_type, area_id, 
        area_desc,size,maxm_capacity,op_status,cleaning_status,  
        supervisor,emer_contact,add_contact,access_control,security_protocol,surveillance)
     VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["location"]."', '".$input["area_name"]."', '".$input["area_type"]."', '".$input["area_id"]."',
        '".$input["area_desc"]."', '".$input["size"]."', '".$input["maxm_capacity"]."', '".$input["op_status"]."', '".$input["cleaning_status"]."',
        '".$input["supervisor"]."','".$input["emer_contact"]."','".$input["add_contact"]."','".$input["access_control"]."','".$input["security_protocol"]."','".$input["surveillance"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "get_area") {
    
            $sql = "SELECT * FROM area_facility order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}

else if ($_GET["type"] == "save_utility") {
          $sql = "INSERT INTO utility (plant_id , facility_name, location, equp_name, equp_type, equp_id, 
        manufacturer,model,utility_type,consump_rate,measur_unit,  
        conn_status,last_maint_date,next_maint_date,usage_status,oper_notes,supervisor,emer_contact,add_contact)
     VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["location"]."', '".$input["equp_name"]."', '".$input["equp_type"]."', '".$input["equp_id"]."',
        '".$input["manufacturer"]."', '".$input["model"]."', '".$input["utility_type"]."', '".$input["consump_rate"]."', '".$input["measur_unit"]."',
        '".$input["conn_status"]."','".$input["last_maint_date"]."','".$input["next_maint_date"]."','".$input["usage_status"]."','".$input["oper_notes"]."','".$input["supervisor"]."','".$input["emer_contact"]."','".$input["add_contact"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "get_utility_equipment") {
    
            $sql = "SELECT * FROM utility order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}

else if ($_GET["type"] == "saveMeasuringOperation") 
    {
          $sql = "INSERT INTO measuring_device (plant_id , facility_name, location, dev_name, dev_type, dev_id, 
        manufacturer,model,last_cali_date,next_cali_date,cali_status,  
        area,sp_location,purpose,opert_note,last_maint_date,next_maint_date,maint_notes,dev_custodian,emer_contact,add_contact)
     VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["location"]."', '".$input["dev_name"]."', '".$input["dev_type"]."', '".$input["dev_id"]."',
        '".$input["manufacturer"]."', '".$input["model"]."', '".$input["last_cali_date"]."', '".$input["next_cali_date"]."', '".$input["cali_status"]."',
        '".$input["area"]."','".$input["sp_location"]."','".$input["purpose"]."','".$input["opert_note"]."','".$input["last_maint_date"]."','".$input["next_maint_date"]."','".$input["maint_notes"]."','".$input["dev_custodian"]."','".$input["emer_contact"]."','".$input["add_contact"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "get_measuring_device") {
    
            $sql = "SELECT * FROM measuring_device order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}
else if ($_GET["type"] == "savefliter_cleaning_form") {
    
          $sql = "INSERT INTO fliter_cleaning (plant_id , facility_name, dept_location, equipment_type, other_eqp, eqp_id,model,filter_type,other_filter,filter_id,replacement_date,  
           cleaning_date,cleaning_time,cleaning_method,cleaning_solution,initial_pressure,final_pressure,visual_inspection,observation,add_action,personnel_name,personnel_id,reviewed_by,date_review,approval_by,ap_date)
     VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["dept_location"]."', '".$input["equipment_type"]."', '".$input["other_eqp"]."', '".$input["eqp_id"]."',
        '".$input["model"]."', '".$input["filter_type"]."', '".$input["other_filter"]."', '".$input["filter_id"]."', '".$input["replacement_date"]."',
        '".$input["cleaning_date"]."','".$input["cleaning_time"]."','".$input["cleaning_method"]."','".$input["cleaning_solution"]."','".$input["initial_pressure"]."',
        '".$input["final_pressure"]."','".$input["visual_inspection"]."','".$input["observation"]."','".$input["add_action"]."','".$input["personnel_name"]."',
        '".$input["personnel_id"]."','".$input["reviewed_by"]."','".$input["date_review"]."','".$input["approval_by"]."','".$input["ap_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
}
else if ($_GET["type"] == "savecleaning_seheaduleform") {
    
          $sql = "INSERT INTO cleaning_seheadule (plant_id , facility_name, dept_location, equipment_type, other_eqp, eqp_id,model,filter_type,other_filter,filter_id,schedule_id,  
           last_cleaning_date,next_cleaning_date,c_interval,c_frequency,c_accigned,s_approval)
     VALUES ('".$_GET["plant_id"]."','".$input["facility_name"]."', '".$input["dept_location"]."', '".$input["equipment_type"]."', '".$input["other_eqp"]."', '".$input["eqp_id"]."',
        '".$input["model"]."', '".$input["filter_type"]."', '".$input["other_filter"]."', '".$input["filter_id"]."', '".$input["schedule_id"]."',
        '".$input["last_cleaning_date"]."','".$input["next_cleaning_date"]."','".$input["c_interval"]."','".$input["c_frequency"]."','".$input["c_accigned"]."',
        '".$input["s_approval"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
}
 

}

$conn->close();
?>