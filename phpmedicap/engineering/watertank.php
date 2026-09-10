<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];


    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveTankMaster") {
        $sql = "INSERT INTO water_tank (tank_name, tank_id, capacity, location, moc, type, frequency, previous_date, format_no, entry_by, entry_date) VALUES ('".$input["tank_name"]."', '".$input["tank_id"]."', '".$input["capacity"]."', '".$input["location"]."', '".$input["moc"]."', '".$input["type"]."', '".$input["frequency"]."', '".$input["previous_date"]."', '".$input["format_no"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "updateTank") {
        $sql = "UPDATE water_tank SET tank_name='".$input["tank_name"]."', capacity='".$input["capacity"]."', location='".$input["location"]."', moc='".$input["moc"]."', type='".$input["type"]."', frequency='".$input["frequency"]."', previous_date='".$input["previous_date"]."', format_no='".$input["format_no"]."' WHERE tank_id='".$input["tank_id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "approveSlutionPreparationforSoftWater") {
       $sql = "UPDATE softwater_generation_system_solution_preparation SET checked_by='".$_GET["emp_id"]."'   where id ='".$_GET["ID"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveWaterHardnessRecord") {
        $sql = "INSERT INTO water_hardness (date,sampling_point, obr, waterHardness, plant_id, entry_by, entry_date) VALUES ('".$input["date"]."', '".$input["samplingpoint"]."', '".$input["obr"]."', '".$input["hardness"]."', '".$_GET["plant_id"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
     else if ($_GET["type"] == "saveSolutionPreparation") {
       $sql = "INSERT INTO softwater_generation_system_solution_preparation (date,activity, alum_solution_water_quantity, alum_solution_alum_quantity
        , naocl_solution_water_quantity, naocl_solution_naocl_quantity,regeneration_solution_for_water_quantity,regeneration_solution_for_salt,regeneration_solution1_for_water_quantity,
        regeneration_solution1_for_salt,entry_date,entry_by,plant_id) 
        VALUES ('".$input["date"]."', '".$input["activity"]."', '".$input["alum_solution_water_quantity"]."',
       '".$input["alum_solution_alum_quantity"]."', '".$input["naocl_solution_water_quantity"]."', '".$input["naocl_solution_naocl_quantity"]."',
       '".$input["regeneration_solution_for_water_quantity"]."', '".$input["regeneration_solution_for_salt"]."', '".$input["regeneration_solution1_for_water_quantity"]."',
       '".$input["regeneration_solution1_for_salt"]."', '".$_GET["emp_id"]."', '$entry_date', '".$_GET["plant_id"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "saveWfiLog") {
       $sql = "INSERT INTO wfi_system (plant_id,time,date,activity,entry_by,entry_date ) 
        VALUES ('".$_GET["plant_id"]."' , '".$input["time"]."','".$input["date"]."' ,'".$input["activity"]."' ,'".$_GET["emp_id"]."' ,'$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if ($_GET["type"] == "getWfiLog") {
        $output = array();
        $sql = "SELECT * FROM wfi_system";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
        else if ($_GET["type"] == "saveWaterPurifiedPlant") {
       $sql = "INSERT INTO purified_water_plant (timer,raw_water_inlet, free_cholrination, mgf_inlet_pressure, mgf_outlet_pressure, softener_outlet_pressure,
        software_hardness_ppm,ro_inlet_pressure,ro_outlet_pressure,product_flow,reject_flow,mixed_inlet_pressure,mixed_outlet_pressure,mixed_bed_flow,
        mixed_bed_conductivity,loop_pump_pressure,loop_return_flow,entry_by, entry_date,plant_id) VALUES ('".$input["timer"]."', '".$input["raw_water_inlet"]."', 
        '".$input["free_cholrination"]."', '".$input["software_hardness_ppm"]."', '".$input["mgf_inlet_pressure"]."','".$input["mgf_outlet_pressure"]."', 
        '".$input["softener_outlet_pressure"]."','".$input["ro_inlet_pressure"]."','".$input["ro_outlet_pressure"]."','".$input["product_flow"]."',
        '".$input["reject_flow"]."','".$input["mixed_inlet_pressure"]."','".$input["mixed_outlet_pressure"]."','".$input["mixed_bed_flow"]."',
        '".$input["mixed_bed_conductivity"]."','".$input["loop_pump_pressure"]."','".$input["loop_return_flow"]."','".$_GET["emp_id"]."', 
      '$entry_date', '".$_GET["plant_id"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
    else if ($_GET["type"] == "saveCleaningRecord") {
        $sql = "INSERT INTO water_cleaning_record (cleaning_date,cleaning_due_date, cleaning_done_by, remark, plant_id, entry_by, entry_date) VALUES ('".$input["cleaning_date"]."', '".$input["cleaning_due_date"]."', '".$input["cleaning_done_by"]."', '".$input["remark"]."', '".$_GET["plant_id"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
       else if ($_GET["type"] == "getwater_cleaning_records") {
        $output = array();
        $sql = "SELECT * FROM water_cleaning_record";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "saveWaterPurifiedData") {
       $sql = "INSERT INTO water_purified_storage (date, start_time, supply_temp, return_temp, supply_temp_unit, return_temp_unit, stop_time, plant_id, entry_by, entry_date) VALUES ('$input[date]', '$input[start_time]', '$input[supply_temp]', '$input[return_temp]', '$input[supply_temp_unit]', '$input[return_temp_unit]', '$input[stop_time]', '$_GET[plant_id]', '$_GET[emp_id]', '$entry_date')";        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveOperationDetails") {
       $sql = "INSERT INTO operation_log_book (date, start_time, stop_time, inlet_pressure, inlet_pressure_unit, outlet_pressure, outlet_pressure_unit,rm_flow_rate,fit_flow_rate,obr,remark, plant_id, entry_by, entry_date) VALUES ('$input[date]', '$input[start_time]', '$input[stop_time]', '$input[inlet_pressure]', '$input[inlet_pressure_unit]', '$input[outlet_pressure]', '$input[outlet_pressure_unit]',
       '$input[rm_flow_rate]','$input[fit_flow_rate]','$input[obr]','$input[remark]','$_GET[plant_id]', '$_GET[emp_id]', '$entry_date')";      
       if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveDailyChecksDetails") {
       $sql = "INSERT INTO daily_logbook_software_plant (time, DMF_back_wash_done, DMF_back_wash_due, DMF_back_wash_done_by, DMF_inlet_pressure, 
       DMF_inlet_pressure_unit, software_inlet_pressure,Software_inlet_pressure_unit,software_outlet_pressure,software_outlet_pressure_unit,
       software_regeneration_done, software_regeneration_due,software_regeneration_done_by,Hardness_of_soft_water,
       plant_id, entry_by, entry_date) VALUES ('$input[time]', '$input[DMF_back_wash_done]', '$input[DMF_back_wash_due]', 
       '$input[DMF_back_wash_done_by]', '$input[DMF_inlet_pressure]','$input[DMF_inlet_pressure_unit]','$input[software_inlet_pressure]', 
       '$input[Software_inlet_pressure_unit]','$input[software_outlet_pressure]','$input[software_outlet_pressure_unit]',
       '$input[software_regeneration_done]', '$input[software_regeneration_due]',  '$input[software_regeneration_done_by]',
         '$input[Hardness_of_soft_water]','$_GET[plant_id]',
       '$_GET[emp_id]', '$entry_date')";      
       if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveWaterChlorniation") {
       $sql = "INSERT INTO water_chiorination (date, sodium_qty,sodium_qty_unit, SH_dosing_starting, SH_dosing_completion, no_of_strokes, free_chlorine, plant_id, entry_by, entry_date) VALUES ('$input[date]', '$input[sodium_qty]', '$input[sodium_qty_unit]', '$input[SH_dosing_starting]', '$input[SH_dosing_completion]', '$input[no_of_strokes]', '$input[free_chlorine]', '$_GET[plant_id]', '$_GET[emp_id]', '$entry_date')";        
       if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getTanks") {
        $output = array();
        $sql = "SELECT * FROM water_tank WHERE status='approve' ORDER BY tank_id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getSolutionPreparationLog") {
        $output = array();
        $sql = "SELECT * FROM softwater_generation_system_solution_preparation";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPurifiedWaterData") {
        $output = array();
        $sql = "SELECT * FROM water_purified_storage";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPurifiedWaterplantData") {
        $output = array();
        $sql = "SELECT * FROM purified_water_plant";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getWaterPurifiedPlant") {
        $output = array();
        $sql = "SELECT * FROM water_purified_storage";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "getDailyChecksData") {
        $output = array();
        $sql = "SELECT * FROM daily_logbook_software_plant";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getOperationLog") {
        $output = array();
        $sql = "SELECT * FROM operation_log_book";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getWaterChilorinationRecords") {
        $output = array();
        $sql = "SELECT * FROM water_chiorination";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "getWaterHardnessRecords") {
        $output = array();
        $sql = "SELECT * FROM water_hardness";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTankLocations") {
        $output = array();
        $sql = "SELECT DISTINCT(location) FROM water_tank";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM water_tank WHERE location='".$row["location"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["tanks"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getTankCleaningSchedule") {
        $CURRENT_YEAR = date("Y", $timestamp);
        $today = date("Y-m-d", $timestamp);
        $output = array();
        $sql = "SELECT tank_name, tank_id, entry_date, frequency FROM water_tank WHERE type='Cleaning'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["JAN_PLANNED"] = "";
                $row["JAN_EXECUTE"] = "";
                $row["FEB_PLANNED"] = "";
                $row["FEB_EXECUTE"] = "";
                $row["MAR_PLANNED"] = "";
                $row["MAR_EXECUTE"] = "";
                $row["APR_PLANNED"] = "";
                $row["APR_EXECUTE"] = "";
                $row["MAY_PLANNED"] = "";
                $row["MAY_EXECUTE"] = "";
                $row["JUN_PLANNED"] = "";
                $row["JUN_EXECUTE"] = "";
                $row["JUL_PLANNED"] = "";
                $row["JUL_EXECUTE"] = "";
                $row["AUG_PLANNED"] = "";
                $row["AUG_EXECUTE"] = "";
                $row["SEP_PLANNED"] = "";
                $row["SEP_EXECUTE"] = "";
                $row["OCT_PLANNED"] = "";
                $row["OCT_EXECUTE"] = "";
                $row["NOV_PLANNED"] = "";
                $row["NOV_EXECUTE"] = "";
                $row["DEC_PLANNED"] = "";
                $row["DEC_EXECUTE"] = "";
                
                $ADD_MONTHS = 0;
                $ADD_DAYS = 0;
                if ($row["frequency"] == 'Monthly') {
                    $ADD_MONTHS = 1;
                    $ADD_DAYS = 2;
                } else if ($row["frequency"] == 'Quaterly') {
                    $ADD_MONTHS = 3;
                    $ADD_DAYS = 7;
                } else if ($row["frequency"] == 'Yearly') {
                    $ADD_MONTHS = 12;
                    $ADD_DAYS = 30;
                } else if ($row["frequency"] == 'Half Yearly') {
                    $ADD_MONTHS = 6;
                    $ADD_DAYS = 15;
                }
                
                $effectiveDate = $row["entry_date"];
                $beforeDate = "";
                
                $EFFECTIVE_YEAR = date('Y', strtotime($effectiveDate));
                
                $MIN_YEAR = $CURRENT_YEAR - 1;
                $ADD_YEAR = $MIN_YEAR - $EFFECTIVE_YEAR;
                if ($ADD_YEAR > 0) {
                    $temp = $ADD_YEAR * 12 * $ADD_MONTHS;
                    $effectiveDate = date('Y-m-d', strtotime('+'.$ADD_MONTHS.' months', strtotime($effectiveDate)));
                    $EFFECTIVE_YEAR = date('Y', strtotime($effectiveDate));
                }
                
                
                while ($CURRENT_YEAR >= $EFFECTIVE_YEAR) {
                    $effectiveDate = date('Y-m-d', strtotime('+'.$ADD_MONTHS.' months', strtotime($effectiveDate)));
                    $beforeDate = date('Y-m-d', strtotime('-'.$ADD_DAYS.' day', strtotime($effectiveDate)));
                    
                    $EFFECTIVE_YEAR = date('Y', strtotime($effectiveDate));
                    $EFFECTIVE_MONTH = strtoupper(date('M', strtotime($effectiveDate)));
                    
                    if ($CURRENT_YEAR == $EFFECTIVE_YEAR) {
                        $row[$EFFECTIVE_MONTH.'_PLANNED'] = $effectiveDate;
                        
                        if (($today >= $beforeDate) && ($today <= $effectiveDate)){
                            $row[$EFFECTIVE_MONTH."_ALERT"] = "ALERT";
                        } else {
                            $row[$EFFECTIVE_MONTH."_ALERT"] = "NO";
                        }
                        
                        $sql1 = "SELECT DATE(entry_date) as entry_date FROM tank_sanitization WHERE DATE(entry_date) BETWEEN '".$beforeDate."' AND '$effectiveDate'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $row[$EFFECTIVE_MONTH.'_EXECUTE'] = $row1["entry_date"];
                            }
                        }
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getTankSanitizationSchedule") {
        $CURRENT_YEAR = date("Y", $timestamp);
        $output = array();
        $sql = "SELECT tank_name, tank_id, entry_date, frequency FROM water_tank WHERE type='Sanitization'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["JAN_PLANNED"] = "";
                $row["JAN_EXECUTE"] = "";
                $row["FEB_PLANNED"] = "";
                $row["FEB_EXECUTE"] = "";
                $row["MAR_PLANNED"] = "";
                $row["MAR_EXECUTE"] = "";
                $row["APR_PLANNED"] = "";
                $row["APR_EXECUTE"] = "";
                $row["MAY_PLANNED"] = "";
                $row["MAY_EXECUTE"] = "";
                $row["JUN_PLANNED"] = "";
                $row["JUN_EXECUTE"] = "";
                $row["JUL_PLANNED"] = "";
                $row["JUL_EXECUTE"] = "";
                $row["AUG_PLANNED"] = "";
                $row["AUG_EXECUTE"] = "";
                $row["SEP_PLANNED"] = "";
                $row["SEP_EXECUTE"] = "";
                $row["OCT_PLANNED"] = "";
                $row["OCT_EXECUTE"] = "";
                $row["NOV_PLANNED"] = "";
                $row["NOV_EXECUTE"] = "";
                $row["DEC_PLANNED"] = "";
                $row["DEC_EXECUTE"] = "";
                
                $ADD_MONTHS = 0;
                $ADD_DAYS = 0;
                if ($row["frequency"] == 'Monthly') {
                    $ADD_MONTHS = 1;
                    $ADD_DAYS = 2;
                } else if ($row["frequency"] == 'Quaterly') {
                    $ADD_MONTHS = 3;
                    $ADD_DAYS = 7;
                } else if ($row["frequency"] == 'Yearly') {
                    $ADD_MONTHS = 12;
                    $ADD_DAYS = 30;
                } else if ($row["frequency"] == 'Half Yearly') {
                    $ADD_MONTHS = 6;
                    $ADD_DAYS = 15;
                }
                
                $effectiveDate = $row["entry_date"];
                $beforeDate = "";
                
                $EFFECTIVE_YEAR = date('Y', strtotime($effectiveDate));
                
                $MIN_YEAR = $CURRENT_YEAR - 1;
                $ADD_YEAR = $MIN_YEAR - $EFFECTIVE_YEAR;
                if ($ADD_YEAR > 0) {
                    $temp = $ADD_YEAR * 12 * $ADD_MONTHS;
                    $effectiveDate = date('Y-m-d', strtotime('+'.$ADD_MONTHS.' months', strtotime($effectiveDate)));
                    $EFFECTIVE_YEAR = date('Y', strtotime($effectiveDate));
                }
                
                
                while ($CURRENT_YEAR >= $EFFECTIVE_YEAR) {
                    $effectiveDate = date('Y-m-d', strtotime('+'.$ADD_MONTHS.' months', strtotime($effectiveDate)));
                    $beforeDate = date('Y-m-d', strtotime('-'.$ADD_DAYS.' day', strtotime($effectiveDate)));
                    
                    $EFFECTIVE_YEAR = date('Y', strtotime($effectiveDate));
                    $EFFECTIVE_MONTH = strtoupper(date('M', strtotime($effectiveDate)));
                    
                    if ($CURRENT_YEAR == $EFFECTIVE_YEAR) {
                        $row[$EFFECTIVE_MONTH.'_PLANNED'] = $effectiveDate;
                        
                        $sql1 = "SELECT DATE(entry_date) as entry_date FROM tank_sanitization WHERE DATE(entry_date) BETWEEN '".$beforeDate."' AND '$effectiveDate'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $row[$EFFECTIVE_MONTH.'_EXECUTE'] = $row1["entry_date"];
                            }
                        }
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getAwaitingCleaningProcess") {
        $CURRENT_YEAR = date("Y", $timestamp);
        $today = date("Y-m-d", $timestamp);
        $output = array();
        $sql = "SELECT tank_name, tank_id, entry_date, frequency, capacity, location, moc FROM water_tank WHERE type='Cleaning'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $ADD_MONTHS = 0;
                $ADD_DAYS = 0;
                if ($row["frequency"] == 'Monthly') {
                    $ADD_MONTHS = 1;
                    $ADD_DAYS = 2;
                } else if ($row["frequency"] == 'Quaterly') {
                    $ADD_MONTHS = 3;
                    $ADD_DAYS = 7;
                } else if ($row["frequency"] == 'Yearly') {
                    $ADD_MONTHS = 12;
                    $ADD_DAYS = 30;
                } else if ($row["frequency"] == 'Half Yearly') {
                    $ADD_MONTHS = 6;
                    $ADD_DAYS = 15;
                }
                
                $effectiveDate = $row["entry_date"];
                $beforeDate = "";
                
                $EFFECTIVE_YEAR = date('Y', strtotime($effectiveDate));
                
                $MIN_YEAR = $CURRENT_YEAR - 1;
                $ADD_YEAR = $MIN_YEAR - $EFFECTIVE_YEAR;
                if ($ADD_YEAR > 0) {
                    $temp = $ADD_YEAR * 12 * $ADD_MONTHS;
                    $effectiveDate = date('Y-m-d', strtotime('+'.$ADD_MONTHS.' months', strtotime($effectiveDate)));
                    $EFFECTIVE_YEAR = date('Y', strtotime($effectiveDate));
                }
                
                
                while ($CURRENT_YEAR >= $EFFECTIVE_YEAR) {
                    $effectiveDate = date('Y-m-d', strtotime('+'.$ADD_MONTHS.' months', strtotime($effectiveDate)));
                    $beforeDate = date('Y-m-d', strtotime('-'.$ADD_DAYS.' day', strtotime($effectiveDate)));
                    
                    $EFFECTIVE_YEAR = date('Y', strtotime($effectiveDate));
                    $EFFECTIVE_MONTH = strtoupper(date('M', strtotime($effectiveDate)));
                    
                    if ($CURRENT_YEAR == $EFFECTIVE_YEAR) {
                        $row["next_cleaning_date"] = $effectiveDate;
                        
                        if ($today >= $beforeDate && $today <= $effectiveDate) {
                            $sql = "SELECT DATE(entry_date) as entry_date FROM tank_sanitization WHERE type='Cleaning' AND DATE(entry_date) BETWEEN '".$beforeDate."' AND '$effectiveDate'";
                            $result1 = $conn->query($sql);
                            if ($result1->num_rows == 0) {
                                $output[] = $row;
                            }
                        }
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveTankCleaning") {
        $sql = "INSERT INTO tank_sanitization (type, tank_id,checklist, start_time, stop_time, cleaning_by, cleaning_date, supervised_by, cleaning_agent, entry_by, entry_date) VALUES ('Cleaning', '".$input["tank_id"]."', '".json_encode($input["checklist"])."', '".$input["start_time"]."', '".$input["stop_time"]."', '".$input["cleaning_by"]."', '".$input["cleaning_date"]."', '".$input["supervised_by"]."', '".$input["cleaning_agent"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getAwaitingSanitizationProcess") {
        $CURRENT_YEAR = date("Y", $timestamp);
        $today = date("Y-m-d", $timestamp);
        $output = array();
        $sql = "SELECT tank_name, tank_id, entry_date, frequency, capacity, location, moc FROM water_tank WHERE type='Sanitization'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $ADD_MONTHS = 0;
                $ADD_DAYS = 0;
                if ($row["frequency"] == 'Monthly') {
                    $ADD_MONTHS = 1;
                    $ADD_DAYS = 2;
                } else if ($row["frequency"] == 'Quaterly') {
                    $ADD_MONTHS = 3;
                    $ADD_DAYS = 7;
                } else if ($row["frequency"] == 'Yearly') {
                    $ADD_MONTHS = 12;
                    $ADD_DAYS = 30;
                } else if ($row["frequency"] == 'Half Yearly') {
                    $ADD_MONTHS = 6;
                    $ADD_DAYS = 15;
                }
                
                $effectiveDate = $row["entry_date"];
                $beforeDate = "";
                
                $EFFECTIVE_YEAR = date('Y', strtotime($effectiveDate));
                
                $MIN_YEAR = $CURRENT_YEAR - 1;
                $ADD_YEAR = $MIN_YEAR - $EFFECTIVE_YEAR;
                if ($ADD_YEAR > 0) {
                    $temp = $ADD_YEAR * 12 * $ADD_MONTHS;
                    $effectiveDate = date('Y-m-d', strtotime('+'.$ADD_MONTHS.' months', strtotime($effectiveDate)));
                    $EFFECTIVE_YEAR = date('Y', strtotime($effectiveDate));
                }
                
                
                while ($CURRENT_YEAR >= $EFFECTIVE_YEAR) {
                    $effectiveDate = date('Y-m-d', strtotime('+'.$ADD_MONTHS.' months', strtotime($effectiveDate)));
                    $beforeDate = date('Y-m-d', strtotime('-'.$ADD_DAYS.' day', strtotime($effectiveDate)));
                    
                    $EFFECTIVE_YEAR = date('Y', strtotime($effectiveDate));
                    $EFFECTIVE_MONTH = strtoupper(date('M', strtotime($effectiveDate)));
                    
                    if ($CURRENT_YEAR == $EFFECTIVE_YEAR) {
                        $row["next_sanitization_date"] = $effectiveDate;
                        
                        if ($today >= $beforeDate && $today <= $effectiveDate) {
                            $sql = "SELECT DATE(entry_date) as entry_date FROM tank_sanitization WHERE type='Cleaning' AND DATE(entry_date) BETWEEN '".$beforeDate."' AND '$effectiveDate'";
                            $result1 = $conn->query($sql);
                            if ($result1->num_rows == 0) {
                                $output[] = $row;
                            }
                        }
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveTankSanitization") {
        $sql = "INSERT INTO tank_sanitization (type, tank_id,checklist, start_time, stop_time, cleaning_by, cleaning_date, supervised_by, cleaning_agent, entry_by, entry_date) VALUES ('Sanitization', '".$input["tank_id"]."', '".json_encode($input["checklist"])."', '".$input["start_time"]."', '".$input["stop_time"]."', '".$input["cleaning_by"]."', '".$input["cleaning_date"]."', '".$input["supervised_by"]."', '".$input["cleaning_agent"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getTankCleaningLog") {
        $output = array();
        $sql = "SELECT t.*, DATE(t.entry_date) as entry_date, w.tank_name, w.frequency, w.capacity, w.location, w.moc FROM tank_sanitization t LEFT JOIN water_tank w ON t.tank_id=w.tank_id WHERE w.type='Cleaning' AND DATE(t.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY w.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["checklist"]=json_decode($row["checklist"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getTankSanitizationLog") {
        $output = array();
        $sql = "SELECT t.*, DATE(t.entry_date) as entry_date, w.tank_name, w.frequency, w.capacity, w.location, w.moc FROM tank_sanitization t LEFT JOIN water_tank w ON t.tank_id=w.tank_id WHERE w.type='Sanitization' AND DATE(t.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY w.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["checklist"]=json_decode($row["checklist"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadTankSanitizationLog") {
        $_GET['filename'] = 'Water Tank Sanitization Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 15%;">Tank Name	</td>
                    <td style="width: 10%;">Tank Id	</td>
                    <td style="width: 10%;">Capacity</td>
                    <td style="width: 10%;">MOC</td>
                    <td style="width: 15%;">Location</td>
                    <td style="width: 15%;">Frequency</td>
                    <td style="width: 10%;">Sanitization By	</td>
                    <td style="width: 10%;">Sanitization Date	</td>
                </tr>
            </thead>';
        $sql = "SELECT t.*, DATE(t.entry_date) as entry_date, w.tank_name, w.frequency, w.capacity, w.location, w.moc FROM tank_sanitization t LEFT JOIN water_tank w ON t.tank_id=w.tank_id WHERE w.type='Sanitization' AND DATE(t.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY w.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Water Tank Sanitization Log.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadViewTankSanitizationLog") {
        $_GET['filename'] = 'Tank Sanitization'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $sql = "SELECT * FROM tank_sanitization WHERE id='".$_GET["id"]."'" ;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["descriptions"]=json_decode($row["descriptions"]);
                 $descriptions=$row["descriptions"];
        $html.='<table border="1" cellpadding="5">
                    <tr>
                        <td style="width:25%;font-weight:bold;">Cleaning Agent</td>
                        <td style="width:25%;">'.$row['cleaning_agent'].'</td>
                        <td style="width:25%;font-weight:bold;">Cleaning By</td>
                        <td style="width:25%;">'.$row['cleaning_by'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold;">Entry By</td>
                        <td style="width:25%;">'.$row['entry_by'].'</td>
                        <td style="width:25%;font-weight:bold;">Entry Date</td>
                        <td style="width:25%;">'.$row['entry_date'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold;">Start Time</td>
                        <td style="width:25%;">'.$row['start_time'].'</td>
                        <td style="width:25%;font-weight:bold;">Stop Time</td>
                        <td style="width:25%;">'.$row['stop_time'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold;">Supervised By</td>
                        <td style="width:25%;">'.$row['supervised_by'].'</td>
                        <td style="width:25%;font-weight:bold;">Tank No</td>
                        <td style="width:25%;">'.$row['tank_no'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold;">Location</td>
                        <td style="width:75%;">'.$row['location'].'</td>
                       
                    </tr>
                </table>
                <div></div>';
                
        $html.='<table border="1" cellpadding="5">
                    <tr>
                        <td style="width:5%; font-weight:bold;">Sr</td>
                        <td style="width:45%; font-weight:bold;">Description</td>
                        <td style="width:25%; font-weight:bold;">Status</td>
                        <td style="width:25%; font-weight:bold;">Done By</td>
                    </tr>';
                    $j=1;
                    for($i=1;$i<count($descriptions);$i++){
                       $description= $descriptions[$i]; 
                    
            $html.='<tr>
                        <td style="width:5%;">'.$j++.'</td>
                        <td style="width:45%;">'.$description->description.'</td>
                        <td style="width:25%;">'.$description->status.'</td>
                        <td style="width:25%;">'.$row['cleaning_by'].'</td>
                    </tr>';
                    }
        $html.="</table>";
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Tank Sanitization.pdf', 'I');
    }else if ($_GET["type"] == "downloadTankCleaningLog") {
        $_GET['filename'] = ' Cleaning Process Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Tank Name</td>
                    <td style="width: 10%;">Tank Id</td>
                    <td style="width: 10%;">Capacity</td>
                    <td style="width: 10%;">Moc</td>
                    <td style="width: 10%;">Location</td>
                    <td style="width: 15%;">Frequency	</td>
                     <td style="width: 15%;">Cleaning By		</td>
                      <td style="width: 15%;">Cleaning Date			</td>
                     
                </tr>
            </thead>';
        $sql = "SELECT t.*, DATE(t.entry_date) as entry_date, w.tank_name, w.frequency, w.capacity, w.location, 
        w.moc FROM tank_sanitization t LEFT JOIN water_tank w ON 
        t.tank_id=w.tank_id WHERE w.type='Cleaning' AND DATE(t.entry_date) BETWEEN '".$_GET["from_date"]."' 
        AND '".$_GET["to_date"]."' ORDER BY w.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                         <td style="width: 15%;">'.$row[''].'</td>
                          <td style="width: 15%;">'.$row[''].'</td>
                          
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Water Tank Master.pdf', 'I');
    
    }
    
    else if ($_GET["type"] == "downloadTanks") {
        $_GET['filename'] = 'Water Tank Master'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Tank Name</td>
                    <td style="width: 10%;">Tank Id</td>
                    <td style="width: 10%;">Capacity</td>
                    <td style="width: 10%;">Moc</td>
                    <td style="width: 10%;">Location</td>
                    <td style="width: 10%;">Type	</td>
                     <td style="width: 10%;">Frequency	</td>
                      <td style="width: 10%;">Format No		</td>
                       <td style="width: 15%;">Previous Date		</td>
                </tr>
            </thead>';
        $sql = "SELECT * FROM water_tank WHERE type='Cleaning'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 10%;">'.$row['tank_name'].'</td>
                        <td style="width: 10%;">'.$row['tank_id'].'</td>
                        <td style="width: 10%;">'.$row['capacity'].'</td>
                        <td style="width: 10%;">'.$row['moc'].'</td>
                        <td style="width: 10%;">'.$row['location'].'</td>
                        <td style="width: 10%;">'.$row['type'].'</td>
                         <td style="width: 10%;">'.$row['frequency'].'</td>
                          <td style="width: 10%;">'.$row['format_no'].'</td>
                           <td style="width: 15%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Water Tank Master.pdf', 'I');
    }else if ($_GET["type"] == "downloadTankSanitizationSchedule") {
        $_GET['filename'] = 'Tank Cleanings Schedule'; $_GET['pdftype'] = 'landscape'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width: 8%;">Tank ID</td>
                    <td rowspan="2" style="width: 10%;">Tank Name</td>
                    <td style="width: 8%;">Status</td>
                    <td style="width: 6%;">JAN</td>
                    <td style="width: 6%;">FEB</td>
                    <td style="width: 7%;">MAR</td>
                    <td style="width: 6%;">APR</td>
                    <td style="width: 7%;">MAY</td>
                    <td style="width: 6%;">JUN</td>
                    <td style="width: 6%;">JUL</td>
                    <td style="width: 6%;">AUG</td>
                    <td style="width: 6%;">SEP</td>
                    <td style="width: 6%;">OCT</td>
                    <td style="width: 6%;">NOV</td>
                    <td style="width: 6%;">DEC</td>
                </tr>
            </thead>';
            
        $sql = "SELECT tank_name, tank_id, entry_date, frequency FROM water_tank WHERE type='Sanitization'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $planned = array();
                $execute = array();
                $planned["JAN"] = "";
                $planned["FEB"] = "";
                $planned["MAR"] = "";
                $planned["APR"] = "";
                $planned["MAY"] = "";
                $planned["JUN"] = "";
                $planned["JUL"] = "";
                $planned["AUG"] = "";
                $planned["SEP"] = "";
                $planned["OCT"] = "";
                $planned["NOV"] = "";
                $planned["DEC"] = "";
                $execute = $planned;
                $effectiveDate = $row["entry_date"];
                $beforeDate = "";
                
                while (date("Y", $timestamp) >= date('Y', strtotime($effectiveDate))) {
                    if ($row["frequency"] == 'Monthly') {
                        $effectiveDate = date('d-m-Y', strtotime("+1 months", strtotime($effectiveDate)));
                        $beforeDate = date('d-m-Y', strtotime('-2 day', strtotime($effectiveDate)));
                    } else if ($row["frequency"] == 'Quaterly') {
                        $effectiveDate = date('Y-m-d', strtotime("+3 months", strtotime($effectiveDate)));
                        $beforeDate = date('Y-m-d', strtotime('-7 day', strtotime($effectiveDate)));
                    } else if ($row["frequency"] == 'Yearly') {
                        $effectiveDate = date('Y-m-d', strtotime("+12 months", strtotime($effectiveDate)));
                        $beforeDate = date('Y-m-d', strtotime('-30 day', strtotime($effectiveDate)));
                    }
                    
                    if (date("Y", $timestamp) == date('Y', strtotime($effectiveDate))) {
                        $planned[strtoupper(date('M', strtotime($effectiveDate)))] = $effectiveDate;
                        $planned[strtoupper(date('Y-m', strtotime($effectiveDate)))] = date('d', strtotime($effectiveDate));
                        
                        $sql = "SELECT DATE(entry_date) as entry_date FROM tank_sanitization WHERE DATE(entry_date) BETWEEN '".$beforeDate."' AND '$effectiveDate'";
                        $result1 = $conn->query($sql);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $execute[strtoupper(date('M', strtotime($effectiveDate)))] = $row1["entry_date"];
                            }
                        }
                    }
                }
                $row["planned"] = $planned;
                $row["execute"] = $execute;
                
                $html.='<tr nobr="true">
                    <td rowspan="2" style="width: 8%;">'.$row['tank_id'].'</td>
                    <td rowspan="2" style="width: 10%;">'.$row['tank_name'].'</td>
                    <td style="width: 8%;">Planned</td>
                    <td style="width: 6%;">'.$planned['JAN'].'</td>
                    <td style="width: 6%;">'.$planned['FEB'].'</td>
                    <td style="width: 7%;">'.$planned['MAR'].'</td>
                    <td style="width: 6%;">'.$planned['APR'].'</td>
                    <td style="width: 7%;">'.$planned['MAY'].'</td>
                    <td style="width: 6%;">'.$planned['JUN'].'</td>
                    <td style="width: 6%;">'.$planned['JUL'].'</td>
                    <td style="width: 6%;">'.$planned['AUG'].'</td>
                    <td style="width: 6%;">'.$planned['SEP'].'</td>
                    <td style="width: 6%;">'.$planned['OCT'].'</td>
                    <td style="width: 6%;">'.$planned['NOV'].'</td>
                    <td style="width: 6%;">'.$planned['DEC'].'</td>
                </tr>';
                
                $html.='<tr nobr="true">
                    <td style="width: 8%;">Execute</td>
                    <td style="width: 6%;">'.$execute['JAN'].'</td>
                    <td style="width: 6%;">'.$execute['FEB'].'</td>
                    <td style="width: 7%;">'.$execute['MAR'].'</td>
                    <td style="width: 6%;">'.$execute['APR'].'</td>
                    <td style="width: 7%;">'.$execute['MAY'].'</td>
                    <td style="width: 6%;">'.$execute['JUN'].'</td>
                    <td style="width: 6%;">'.$execute['JUL'].'</td>
                    <td style="width: 6%;">'.$execute['AUG'].'</td>
                    <td style="width: 6%;">'.$execute['SEP'].'</td>
                    <td style="width: 6%;">'.$execute['OCT'].'</td>
                    <td style="width: 6%;">'.$execute['NOV'].'</td>
                    <td style="width: 6%;">'.$execute['DEC'].'</td>
                </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Tank Cleanings Schedule.pdf', 'I');
}
    else if ($_GET["type"] == "downloadTankCleaningSchedule") {
        $_GET['filename'] = 'Tank Cleanings Schedule'; $_GET['pdftype'] = 'landscape'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width: 8%;">Tank ID</td>
                    <td rowspan="2" style="width: 10%;">Tank Name</td>
                    <td style="width: 8%;">Status</td>
                    <td style="width: 6%;">JAN</td>
                    <td style="width: 6%;">FEB</td>
                    <td style="width: 7%;">MAR</td>
                    <td style="width: 6%;">APR</td>
                    <td style="width: 7%;">MAY</td>
                    <td style="width: 6%;">JUN</td>
                    <td style="width: 6%;">JUL</td>
                    <td style="width: 6%;">AUG</td>
                    <td style="width: 6%;">SEP</td>
                    <td style="width: 6%;">OCT</td>
                    <td style="width: 6%;">NOV</td>
                    <td style="width: 6%;">DEC</td>
                </tr>
            </thead>';
            
        $sql = "SELECT * FROM water_tank WHERE type='Cleaning' LIMIT 2";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $planned = array();
                $execute = array();
                $planned["JAN"] = "";
                $planned["FEB"] = "";
                $planned["MAR"] = "";
                $planned["APR"] = "";
                $planned["MAY"] = "";
                $planned["JUN"] = "";
                $planned["JUL"] = "";
                $planned["AUG"] = "";
                $planned["SEP"] = "";
                $planned["OCT"] = "";
                $planned["NOV"] = "";
                $planned["DEC"] = "";
                $execute = $planned;
                $effectiveDate = $row["entry_date"];
                $beforeDate = "";
                
                while (date("Y", $timestamp) >= date('Y', strtotime($effectiveDate))) {
                    if ($row["frequency"] == 'Monthly') {
                        $effectiveDate = date('Y-m-d', strtotime("+1 months", strtotime($effectiveDate)));
                        $beforeDate = date('Y-m-d', strtotime('-2 day', strtotime($effectiveDate)));
                    } else if ($row["frequency"] == 'Quaterly') {
                        $effectiveDate = date('Y-m-d', strtotime("+3 months", strtotime($effectiveDate)));
                        $beforeDate = date('Y-m-d', strtotime('-7 day', strtotime($effectiveDate)));
                    } else if ($row["frequency"] == 'Yearly') {
                        $effectiveDate = date('Y-m-d', strtotime("+12 months", strtotime($effectiveDate)));
                        $beforeDate = date('Y-m-d', strtotime('-30 day', strtotime($effectiveDate)));
                    }
                    
                    if (date("Y", $timestamp) == date('Y', strtotime($effectiveDate))) {
                        $planned[strtoupper(date('M', strtotime($effectiveDate)))] = $effectiveDate;
                        $planned[strtoupper(date('Y-m', strtotime($effectiveDate)))] = date('d', strtotime($effectiveDate));
                        
                        $sql = "SELECT DATE(entry_date) as entry_date FROM tank_sanitization WHERE DATE(entry_date) BETWEEN '".$beforeDate."' AND '$effectiveDate'";
                        $result1 = $conn->query($sql);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $execute[strtoupper(date('M', strtotime($effectiveDate)))] = $row1["entry_date"];
                            }
                        }
                    }
                }
                $row["planned"] = $planned;
                $row["execute"] = $execute;
                
                $html.='<tr nobr="true">
                    <td rowspan="2" style="width: 8%;">'.$row['tank_id'].'</td>
                    <td rowspan="2" style="width: 10%;">'.$row['tank_name'].'</td>
                    <td style="width: 8%;">Planned</td>
                    <td style="width: 6%;">'.$planned['JAN'].'</td>
                    <td style="width: 6%;">'.$planned['FEB'].'</td>
                    <td style="width: 7%;">'.$planned['MAR'].'</td>
                    <td style="width: 6%;">'.$planned['APR'].'</td>
                    <td style="width: 7%;">'.$planned['MAY'].'</td>
                    <td style="width: 6%;">'.$planned['JUN'].'</td>
                    <td style="width: 6%;">'.$planned['JUL'].'</td>
                    <td style="width: 6%;">'.$planned['AUG'].'</td>
                    <td style="width: 6%;">'.$planned['SEP'].'</td>
                    <td style="width: 6%;">'.$planned['OCT'].'</td>
                    <td style="width: 6%;">'.$planned['NOV'].'</td>
                    <td style="width: 6%;">'.$planned['DEC'].'</td>
                </tr>';
                
                $html.='<tr nobr="true">
                    <td style="width: 8%;">Execute</td>
                    <td style="width: 6%;">'.$execute['JAN'].'</td>
                    <td style="width: 6%;">'.$execute['FEB'].'</td>
                    <td style="width: 7%;">'.$execute['MAR'].'</td>
                    <td style="width: 6%;">'.$execute['APR'].'</td>
                    <td style="width: 7%;">'.$execute['MAY'].'</td>
                    <td style="width: 6%;">'.$execute['JUN'].'</td>
                    <td style="width: 6%;">'.$execute['JUL'].'</td>
                    <td style="width: 6%;">'.$execute['AUG'].'</td>
                    <td style="width: 6%;">'.$execute['SEP'].'</td>
                    <td style="width: 6%;">'.$execute['OCT'].'</td>
                    <td style="width: 6%;">'.$execute['NOV'].'</td>
                    <td style="width: 6%;">'.$execute['DEC'].'</td>
                </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Tank Cleanings Schedule.pdf', 'I');
    }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>