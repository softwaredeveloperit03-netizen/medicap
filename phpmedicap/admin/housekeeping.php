<?php


// error_reporting(E_ALL);
// ini_set('display_errors', 1);

    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php'; 

    function ensureOutdoorDutyColumns($conn) {
        $cols = array(
            'status' => "VARCHAR(50) NULL DEFAULT 'PENDING_DEPT_HEAD'",
            'createdOn' => 'DATETIME NULL',
            'deptHeadApprovalBy' => 'VARCHAR(50) NULL',
            'deptHeadApprovalOn' => 'DATETIME NULL',
            'deptHeadRejectedBy' => 'VARCHAR(50) NULL',
            'deptHeadRejectedOn' => 'DATETIME NULL',
            'hrHeadApprovalBy' => 'VARCHAR(50) NULL',
            'hrHeadApprovalOn' => 'DATETIME NULL',
            'hrHeadRejectedBy' => 'VARCHAR(50) NULL',
            'hrHeadRejectedOn' => 'DATETIME NULL',
            'securityExitBy' => 'VARCHAR(50) NULL',
            'securityExitOn' => 'DATETIME NULL'
        );
        foreach ($cols as $col => $def) {
            $chk = $conn->query("SHOW COLUMNS FROM outdoor_duty LIKE '".$col."'");
            if ($chk && $chk->num_rows === 0) {
                @$conn->query("ALTER TABLE outdoor_duty ADD `".$col."` ".$def);
            }
        }
        @$conn->query("UPDATE outdoor_duty SET status='PENDING_DEPT_HEAD' WHERE status IN ('pending','Checked','') OR status IS NULL");
        @$conn->query("UPDATE outdoor_duty SET status='APPROVED' WHERE status='PENDING_SECURITY_EXIT'");
    }

    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
     $currentUrl =$_GET["description"];



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
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);

    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "get_office") {
        $output = Array();
        $sql = "SELECT * FROM canteen_office";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
             else  if ($_GET["type"] == "cabin_passage") {
            
             $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        
        $html.= ' 
 
 

 <table border="1">
        
      
          <table border="1">
         <tr>
         <td style="width: 81px; font-size: 11; font-weight: bold; text-align: center;"></td>

             <td style="width: 704px; font-size: 11; font-weight: bold; text-align: center;">ETP LOG SHEET&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;             DOC NO:SIPL/SOP/AD/08</td>
         </tr>
      
         </table>
         <table border="1">
        
            <tr>
                <td style="width: 66px; font-size: 9; font-weight: bold; text-align: center;">Date</td>
                <td style="width: 60px; font-size: 9; font-weight: bold; text-align: center;">Day checked</td>
                <td style="width: 81px; font-size: 9; font-weight: bold; text-align: center;">Total Backwash & Rinse start Time</td>
                <td style="width: 81px; font-size: 9; font-weight: bold; text-align: center;">Total Backwash & Rinse start Time</td>
                <td style="width: 71px; font-size: 9; font-weight: bold; text-align: center;">Process Start Time</td>
                <td style="width: 71px; font-size: 9; font-weight: bold; text-align: center;">250 gm Line Add Time(15 min)</td>
                <td style="width: 71px; font-size: 9; font-weight: bold; text-align: center;">250 gm Alam Add Time(15 min)</td>
                <td style="width: 71px; font-size: 9; font-weight: bold; text-align: center;">500 ml poly Add Time(15 min)</td>
                <td style="width: 71px; font-size: 9; font-weight: bold; text-align: center;">Holding Time 1 hr</td>
                <td style="width: 71px;  font-size: 9; font-weight: bold; text-align: center;">Filter Water Transfer time</td>
                <td style="width: 71px;  font-size: 9; font-weight: bold; text-align: center;">Salary Transfer time</td>
            </tr>
';


   $sql = "SELECT * FROM  etplog where   plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
           
        $html.='
           <tr>
                 <td style="width: 66px; font-size: 10;  text-align: center;">'.$row["etp_date"].'</td>
                <td style="width: 60px; font-size: 10;  text-align: center;" >'.$row["etp_day"].'</td>
                <td style="width: 81px; font-size: 10;  text-align: center;" >'.$row["backWash"].'</td>
                <td style="width: 81px; font-size: 10;  text-align: center;">'.$row["rinse"].'</td>
                <td style="width: 71px; font-size: 10;  text-align: center;">'.$row["process_time"].'</td>
                <td style="width: 71px; font-size: 10;  text-align: center;">'.$row["add_time"].'</td>
                <td style="width: 71px; font-size: 10;  text-align: center;">'.$row["alam_time"].'</td>
                <td style="width: 71px; font-size: 10;  text-align: center;">'.$row["poly_time"].'</td>
                <td style="width: 71px; font-size: 10;  text-align: center;">'.$row["holding_time"].'</td>
                <td style="width: 71px;  font-size: 10;  text-align: center;">'.$row["water_time"].'</td>
                <td style="width: 71px;  font-size: 10;  text-align: center;">'.$row["transfer_time"].'</td>
             </tr>
         ';
        $i++;
            }
        }

      
        $html.='

         </table>
       
         <table border="1">
         <tr>
         <td style="width: 785px;height: 60px; font-size: 11; font-weight: bold; text-align: center;"colspan="2"></td>

         </tr>
         
      
         
         </table>
         <table border="1">
         <tr>
         <td style="width: 81px; font-size: 11; font-weight: bold; text-align: center;"></td>

             <td style="width: 340px;height: 20px;font-size: 11; font-weight: bold; text-align: center;">HYGENE SUPERVISOR</td>
             <td style="width: 364px;height: 20px;font-size: 11; font-weight: bold; text-align: center;">QUALITY HEAD</td>



         </tr>
      
         <br>
         </table> ';
         
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ETP Log Sheet.pdf', 'I');
       
         }

    else if ($_GET["type"] == "saveOffice") {
        $sql = "INSERT INTO canteen_office(plant_id, date, time, sweeping, table_chair_stool, washbasin, done_by, checked_by, remark)VALUES('".$_GET['plant_id']."','".$input["date"]."',
        '".$input["time"]."','".$input["sweeping"]."','".$input["table"]."','".$input["washbasin"]."','".$input["done_by"]."','".$input["checked_by"]."','".$input["remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    else if ($_GET["type"] == "savereview") {
        $sql = "INSERT INTO reviewcolabrate(plant_id,specification, date, test, observation, acce_criteria, Coated,)VALUES('".$_GET['plant_id']."','".$input["specification"]."',
        '".$input["date"]."','".$input["test"]."','".$input["observation"]."','".$input["acce_criteria"]."','".$input["Coated"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    else if ($_GET["type"] == "get_cabin") {
        $output = Array();
        $sql = "SELECT * FROM cabin_passage";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_dust_bin_data") {
        $output = Array();
        $sql = "SELECT * FROM dustbindata where   plant_id = '".$_GET['plant_id']."' order by bid desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_factory_clean_data") {
        $output = Array();
        $sql = "SELECT * FROM faccleandate where   plant_id = '".$_GET['plant_id']."' order by fid desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_toilet_clean_data") {
        $output = Array();
        $sql = "SELECT * FROM  toiletcleandata where   plant_id = '".$_GET['plant_id']."' order by fid desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_etp_log_data") {
        $output = Array();
        $sql = "SELECT * FROM  etplog where   plant_id = '".$_GET['plant_id']."' order by etpid desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_ro_log_data") {
        $output = Array();
        $sql = "SELECT * FROM  rolog where   plant_id = '".$_GET['plant_id']."' order by roid desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_scrap_data") {
        $output = Array();
        $sql = "SELECT * FROM  scrap_record where   plant_id = '".$_GET['plant_id']."' order by sid desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "west_disp_data") {
        $output = Array();
        $sql = "SELECT * FROM  wastedata where   plant_id = '".$_GET['plant_id']."' order by fid desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_pest_control_data") {
        $output = Array();
        $sql = "SELECT * FROM  pest_control where  service_name= '".$_GET['service_type']."' AND   plant_id = '".$_GET['plant_id']."' order by pid desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "personal_hygiene_report") {
        $output = Array();
        $sql = "SELECT p.*,e.firstname,e.middlename,e.lastname FROM personal_hygiene p 
        LEFT JOIN employee e ON p.emp_id = e.emp_id where p.plant_id = '".$_GET['plant_id']."' order by p.id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveCabin") {
        $sql = "INSERT INTO cabin_passage(plant_id, date, time, sweeping, table_chair_stool, cupboard, done_by, checked_by, remark)VALUES('".$_GET['plant_id']."','".$input["date"]."',
        '".$input["time"]."','".$input["sweeping"]."','".$input["table"]."','".$input["cupboard"]."','".$input["done_by"]."','".$input["checked_by"]."','".$input["remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "faccleandate") {
        $sql = "INSERT INTO faccleandate( plant_id, floor, clean_date, clean_day, hwasher_clean, curtain_clean, wall_clean, 
        pallet_clean, storage_clean, wtank_clean, file_clean, rodent_clean, machine_clean, utencils_clean, flycather_clean, 
        corner_clean, surrounding_clean, clean_remark) VALUES ('".$_GET['plant_id']."','".$input["floor"]."','".$input["clening_date"]."',
        '".$input["cleaning_day"]."','".$input["hwasher_clean"]."','".$input["curtain_clean"]."','".$input["wall_clean"]."','".$input["pallet_clean"]."',
        '".$input["storage_clean"]."','".$input["wtank_clean"]."','".$input["file_clean"]."','".$input["rodent_clean"]."','".$input["machine_clean"]."',
        '".$input["utencils_clean"]."','".$input["flycather_clean"]."','".$input["corner_clean"]."','".$input["surrounding_clean"]."',
        '".$input["clean_remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "toiletcleandata") {
        $sql = "INSERT INTO toiletcleandata(plant_id, floor, clean_date, clean_day, wbasin_clean, upot_clean, tdorrs_clean,
        tblock_clean, gtiles_clean, soapd_clean, efan_clean, pwpipel_clean, tclean_remark) VALUES ('".$_GET['plant_id']."',
        '".$input["floor"]."','".$input["clening_date"]."','".$input["cleaning_day"]."','".$input["wbasin_clean"]."',
        '".$input["upot_clean"]."','".$input["tdorrs_clean"]."','".$input["tblock_clean"]."','".$input["gtiles_clean"]."',
        '".$input["soapd_clean"]."','".$input["efan_clean"]."','".$input["pwpipel_clean"]."','".$input["tclean_remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "wastedata") {
        $sql = "INSERT INTO wastedata(plant_id, clean_date, clean_day, gf_dust, ff_dust,
         o_dust, label_prop, segregated_prop, not_leaking, storage_limit, collected_by_agency)  VALUES ('".$_GET['plant_id']."',
       '".$input["clening_date"]."','".$input["cleaning_day"]."','".$input["gf_dust"]."',
        '".$input["ff_dust"]."','".$input["o_dust"]."','".$input["label_prop"]."','".$input["segregated_prop"]."',
        '".$input["not_leaking"]."','".$input["storage_limit"]."','".$input["collected_by_agency"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    else if ($_GET["type"] == "get_bath") {
        $output = Array();
        $sql = "SELECT * FROM toilet_bathroom";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_dust_nums") {
        $output = Array();
         $sql = "SELECT * FROM dust_bin";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "add_dustnumber") {
        
         $sql = "INSERT INTO dust_bin(dust_bin_no,plant_id)VALUES('".$_GET["add_dust_no"]."','".$_GET['plant_id']."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "savebindata") {
        
        $sql = "INSERT INTO `dustbindata`(`plant_id`, `floor`, `date_of_cleaning`, `day_of_cleaning`, `dust_bin_check_data`) VALUES 
        ('".$_GET['plant_id']."','".$input["floor"]."','".$input["clening_date"]."','".$input["cleaning_day"]."','".json_encode($input["bindata"])."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "savebindata") {
        
        $sql = "INSERT INTO `dustbindata`(`plant_id`, `floor`, `date_of_cleaning`, `day_of_cleaning`, `dust_bin_check_data`) VALUES 
        ('".$_GET['plant_id']."','".$input["floor"]."','".$input["clening_date"]."','".$input["cleaning_day"]."','".json_encode($input["bindata"])."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "etpsave") {
        
        $sql = "INSERT INTO etplog(plant_id, etp_date, etp_day, backWash, rinse, process_time,
        add_time, alam_time, poly_time, holding_time, water_time, transfer_time)  
        VALUES ('".$_GET['plant_id']."','".$input["clening_date"]."','".$input["cleaning_day"]."','".$input["backWash"]."',
        '".$input["rinse"]."','".$input["process_time"]."','".$input["add_time"]."','".$input["alam_time"]."',
        '".$input["poly_time"]."','".$input["holding_time"]."','".$input["water_time"]."',
        '". $input["transfer_time"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "saveRolog") {
        
        $sql = "INSERT INTO rolog(plant_id, ro_clean_date, ro_clean_day, ro_backwash, ro_rinse, ro_carban_back,
        ro_carban_rinse, ro_water_level, filter_process_time,filter_process_end_time,total_process_time)  
        VALUES ('".$_GET['plant_id']."','".$input["clening_date"]."','".$input["cleaning_day"]."','".$input["ro_backwash"]."',
        '".$input["ro_rinse"]."','".$input["ro_carban_back"]."','".$input["ro_carban_rinse"]."',
        '".$input["ro_water_level"]."','".$input["filter_process_time"]."','".$input["filter_process_end_time"]."','".$input["total_process_time"]."')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "save_scrap") {
        
        $sql = "INSERT INTO scrap_record( plant_id, scrap_date, scrap_type, type_wastage,
                    scrap_rate_kg, scrap_total_kg, total_amount)  
        VALUES ('".$_GET['plant_id']."','".$input["scrap_date"]."','".$input["scrap_type"]."',
        '".$input["type_wastage"]."','".$input["scrap_rate_kg"]."','".$input["scrap_total_kg"]."',
        '".$input["total_amount"]."')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "save_pest_record") {
        
         $sql = "INSERT INTO pest_control( plant_id, date_of_service, service_name, pest_status_obv, no_of_found, 
        technician_name, cust_rep_name, cust_Sign) 
        VALUES ('".$_GET['plant_id']."','".$input["date_of_service"]."','".$input["service_name"]."',
        '".$input["pest_status_obv"]."','".$input["no_of_found"]."','".$input["technician_name"]."',
        '".$input["cust_rep_name"]."','".$input["cust_Sign"]."')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "save_personal_hygiene") {
        // jewelry, cuts_wound, beard,
        //'".$input["jewelry"]."','".$input["cuts_wound"]."','".$input["beard"]."',
        
       
         $sql = "INSERT INTO personal_hygiene( plant_id,personal_hygiene_date, department_name, emp_id, c_pocket,
                    cap, nails, jewelry, cuts_wound, beard, footware, medical_device, any_alergy,overall_remark,labour_name)   
        VALUES ('".$_GET['plant_id']."','".$input["personal_hygiene_date"]."','".$input["department_name"]."'
        ,'".$input["emp_name"]."','".$input["c_pocket"]."','".$input["cap"]."','".$input["nails"]."','".$input["jewelry"]."'
        ,'".$input["cuts_wound"]."','".$input["beard"]."','".$input["footware"]."','".$input["medical_device"]."','".$input["any_alergy"]."','".$input["overall_remark"]."','".$input["labour_name"]."')";
        //  $sql = "INSERT INTO personal_hygiene( plant_id, personal_hygiene_date, department_name, emp_id, clothing, 
        // hairs, nails,  body_clean, teeth, footware, mask, cap, gloves, appron, 
        // overall_remark)   
        // VALUES ('".$_GET['plant_id']."','".$input["personal_hygiene_date"]."','".$input["department_name"]."'
        // ,'".$input["emp_name"]."','".$input["clothing"]."','".$input["hairs"]."'
        // ,'".$input["nails"]."','".$input["body_clean"]."','".$input["teeth"]."','".$input["footware"]."'
        // ,'".$input["mask"]."','".$input["cap"]."','".$input["gloves"]."','".$input["appron"]."','".$input["overall_remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "scrap_types") {
        
        $sql = "INSERT INTO scrap_types(scrap_type, plant_id)  
        VALUES ('".$_GET['save_scrap']."','".$_GET['plant_id']."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "get_scrap_types") {
        
     $output = Array();
         $sql = "SELECT * FROM scrap_types where plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_floor_nums") {
        $output = Array();
         $sql = "SELECT * FROM floors";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "add_floornumber") {
        
        $sql = "INSERT INTO floors(floor,plant_id)VALUES('".$_GET["floor"]."','".$_GET['plant_id']."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "saveBath") {
        
        $sql = "INSERT INTO toilet_bathroom(plant_id, date, time, staff, director, ladies, worker, done_by, checked_by, remark)VALUES('".$_GET['plant_id']."','".$input["date"]."',
        '".$input["time"]."','".$input["staff"]."','".$input["director"]."','".$input["ladies"]."','".$input["worker"]."','".$input["done_by"]."','".$input["checked_by"]."','".$input["remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    else if ($_GET["type"] == "get_factory") {
        $output = Array();
        $sql = "SELECT * FROM factory_primises";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveFactory") {
        $sql = "INSERT INTO factory_primises(plant_id, date, road, wall, drainage, done_by, checked_by, remark)VALUES('".$_GET['plant_id']."','".$input["date"]."',
        '".$input["road"]."','".$input["wall"]."','".$input["drainage"]."','".$input["done_by"]."','".$input["checked_by"]."','".$input["remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    
     else if ($_GET["type"] == "get_general") {
        $output = Array();
        $sql = "SELECT * FROM general_primises";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveGeneral") {
        $sql = "INSERT INTO general_primises(plant_id, date, ceilings, windows, doors, done_by, checked_by, remark)VALUES('".$_GET['plant_id']."','".$input["date"]."',
        '".$input["ceilings"]."','".$input["windows"]."','".$input["doors"]."','".$input["done_by"]."','".$input["checked_by"]."','".$input["remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    if ($_GET["type"] == "getMyOutdoorDuty") {
        ensureOutdoorDutyColumns($conn);
        $output = array();
        $empId = mysqli_real_escape_string($conn, $_GET["emp_id"] ?? '');
        $plant = mysqli_real_escape_string($conn, $_GET["plant_id"] ?? '');
        if ($empId === '') {
            echo json_encode($output);
        } else {
            $sql = "SELECT * FROM outdoor_duty WHERE plant_id='".$plant."' AND emp_id='".$empId."' ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
    }
    else if ($_GET["type"] == "get_Outdoor_duty") {
        $output = array();
        $plant = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        $status = isset($_GET["status"]) ? trim($_GET["status"]) : '';
        $empId = isset($_GET["emp_id"]) ? trim($_GET["emp_id"]) : '';
        $where = "1=1";
        if ($plant !== '') {
            $where .= " AND (plant_id='".$plant."' OR plant_id='' OR plant_id IS NULL)";
        }
        if ($empId !== '') {
            $where .= " AND emp_id='".mysqli_real_escape_string($conn, $empId)."'";
        }
        if ($status !== '' && strtolower($status) !== 'undefined' && strtolower($status) !== 'null') {
            if (strtolower($status) === 'pending') {
                $where .= " AND (status IN ('PENDING_DEPT_HEAD','pending','Checked') OR status IS NULL OR TRIM(IFNULL(status,'')) = '')";
            } else {
                $where .= " AND status='".mysqli_real_escape_string($conn, $status)."'";
            }
        }
        $sql = "SELECT * FROM outdoor_duty WHERE ".$where." ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getOutdoorDutyForDeptHead") {
        ensureOutdoorDutyColumns($conn);
        $output = array();
        $plant = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        $dept = isset($_GET["deptName"]) ? trim($_GET["deptName"]) : '';
        if ($dept === '' && !empty($_GET["department"])) {
            $dept = trim($_GET["department"]);
        }
        $sql = "SELECT * FROM outdoor_duty WHERE plant_id='".$plant."' AND status='PENDING_DEPT_HEAD'";
        if ($dept !== '') {
            $sql .= " AND department='".mysqli_real_escape_string($conn, $dept)."'";
        }
        $sql .= " ORDER BY createdOn DESC, id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getOutdoorDutyForHrHead") {
        ensureOutdoorDutyColumns($conn);
        $output = array();
        $plant = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        $sql = "SELECT * FROM outdoor_duty WHERE plant_id='".$plant."' AND status='PENDING_HR_HEAD' ORDER BY deptHeadApprovalOn DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getOutdoorDutyDeptLog") {
        ensureOutdoorDutyColumns($conn);
        $output = array();
        $plant = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        $dept = isset($_GET["deptName"]) ? trim($_GET["deptName"]) : '';
        if ($dept === '' && !empty($_GET["department"])) {
            $dept = trim($_GET["department"]);
        }
        $sql = "SELECT * FROM outdoor_duty WHERE plant_id='".$plant."'";
        if ($dept !== '') {
            $sql .= " AND department='".mysqli_real_escape_string($conn, $dept)."'";
        }
        $sql .= " ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getOutdoorDutyHrLog") {
        ensureOutdoorDutyColumns($conn);
        $output = array();
        $plant = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        $sql = "SELECT * FROM outdoor_duty WHERE plant_id='".$plant."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "deptHeadApproveOutdoorDuty") {
        ensureOutdoorDutyColumns($conn);
        $id = isset($_GET["id"]) ? mysqli_real_escape_string($conn, trim($_GET["id"])) : '';
        $empId = isset($_GET["emp_id"]) ? mysqli_real_escape_string($conn, $_GET["emp_id"]) : '';
        if ($id === '') {
            echo "{\"status\":\"error\",\"message\":\"id required\"}";
            exit;
        }
        $action = (isset($_GET["action"]) && $_GET["action"] === 'reject') ? 'reject' : 'approve';
        $now = date("Y-m-d H:i:s");
        if ($action === 'approve') {
            $sql = "UPDATE outdoor_duty SET status='PENDING_HR_HEAD', deptHeadApprovalBy='".$empId."', deptHeadApprovalOn='".$now."' WHERE id='".$id."' AND status='PENDING_DEPT_HEAD'";
        } else {
            $sql = "UPDATE outdoor_duty SET status='REJECTED_DEPT_HEAD', deptHeadRejectedBy='".$empId."', deptHeadRejectedOn='".$now."' WHERE id='".$id."' AND status='PENDING_DEPT_HEAD'";
        }
        if ($conn->query($sql) && $conn->affected_rows > 0) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"error\",\"message\":\"Failed or already processed\"}";
        }
    }
    else if ($_GET["type"] == "hrHeadApproveOutdoorDuty") {
        ensureOutdoorDutyColumns($conn);
        $id = isset($_GET["id"]) ? mysqli_real_escape_string($conn, trim($_GET["id"])) : '';
        $empId = isset($_GET["emp_id"]) ? mysqli_real_escape_string($conn, $_GET["emp_id"]) : '';
        if ($id === '') {
            echo "{\"status\":\"error\",\"message\":\"id required\"}";
            exit;
        }
        $action = (isset($_GET["action"]) && $_GET["action"] === 'reject') ? 'reject' : 'approve';
        $now = date("Y-m-d H:i:s");
        if ($action === 'approve') {
            $sql = "UPDATE outdoor_duty SET status='APPROVED', hrHeadApprovalBy='".$empId."', hrHeadApprovalOn='".$now."' WHERE id='".$id."' AND status='PENDING_HR_HEAD'";
        } else {
            $sql = "UPDATE outdoor_duty SET status='REJECTED_HR_HEAD', hrHeadRejectedBy='".$empId."', hrHeadRejectedOn='".$now."' WHERE id='".$id."' AND status='PENDING_HR_HEAD'";
        }
        if ($conn->query($sql) && $conn->affected_rows > 0) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"error\",\"message\":\"Failed or already processed\"}";
        }
    }
    else if ($_GET["type"] == "getOutdoorDutyForSecurity") {
        ensureOutdoorDutyColumns($conn);
        $output = array();
        $plant = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        $fromDate = isset($_GET["from_date"]) ? trim($_GET["from_date"]) : '';
        $toDate = isset($_GET["to_date"]) ? trim($_GET["to_date"]) : '';
        $where = "status IN ('PENDING_SECURITY_EXIT','EXIT')";
        if ($plant !== '') {
            $where .= " AND plant_id='".$plant."'";
        }
        if ($fromDate !== '' && $toDate !== '') {
            $fromDate = mysqli_real_escape_string($conn, $fromDate);
            $toDate = mysqli_real_escape_string($conn, $toDate);
            $where .= " AND (DATE(from_date) BETWEEN '".$fromDate."' AND '".$toDate."' OR DATE(hrHeadApprovalOn) BETWEEN '".$fromDate."' AND '".$toDate."' OR DATE(securityExitOn) BETWEEN '".$fromDate."' AND '".$toDate."' OR DATE(createdOn) BETWEEN '".$fromDate."' AND '".$toDate."')";
        }
        $sql = "SELECT * FROM outdoor_duty WHERE ".$where." ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "securityExitOutdoorDuty") {
        ensureOutdoorDutyColumns($conn);
        $id = isset($_GET["id"]) ? mysqli_real_escape_string($conn, trim($_GET["id"])) : '';
        $empId = isset($_GET["emp_id"]) ? mysqli_real_escape_string($conn, $_GET["emp_id"]) : '';
        if ($id === '') {
            echo "{\"status\":\"error\",\"message\":\"id required\"}";
            exit;
        }
        $now = date("Y-m-d H:i:s");
        $sql = "UPDATE outdoor_duty SET status='EXIT', securityExitBy='".$empId."', securityExitOn='".$now."' WHERE id='".$id."' AND status='PENDING_SECURITY_EXIT'";
        if ($conn->query($sql) && $conn->affected_rows > 0) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"error\",\"message\":\"Failed or already processed\"}";
        }
    } 
    else if ($_GET["type"] == "saveOutdoor_duty" || $_GET["type"] == "saveOutdoorDuty") {
        ensureOutdoorDutyColumns($conn);
        $plant = isset($_GET['plant_id']) ? mysqli_real_escape_string($conn, $_GET['plant_id']) : '';
        $empId = isset($input["emp_id"]) ? trim($input["emp_id"]) : '';
        $empName = isset($input["emp_name"]) ? trim($input["emp_name"]) : '';
        $department = isset($input["department"]) ? trim($input["department"]) : '';
        $designation = isset($input["designation"]) ? mysqli_real_escape_string($conn, $input["designation"]) : '';
        $dutyDetails = isset($input["duty_details"]) ? mysqli_real_escape_string($conn, $input["duty_details"]) : '';
        $checker = isset($input["checker"]) ? mysqli_real_escape_string($conn, $input["checker"]) : '';
        $approver = isset($input["approver"]) ? mysqli_real_escape_string($conn, $input["approver"]) : '';
        $fromDate = isset($input["from_date"]) ? mysqli_real_escape_string($conn, $input["from_date"]) : '';
        $toDate = isset($input["to_date"]) ? mysqli_real_escape_string($conn, $input["to_date"]) : '';
        if ($empId === '' && !empty($_GET['emp_id'])) {
            $empId = trim($_GET['emp_id']);
        }
        if ($empName === '' && !empty($_GET['username'])) {
            $empName = trim($_GET['username']);
        }
        if (!empty($_GET['department'])) {
            $department = trim($_GET['department']);
        } else if ($department === '') {
            $department = isset($input["department"]) ? trim($input["department"]) : '';
        }
        $empId = mysqli_real_escape_string($conn, $empId);
        $empName = mysqli_real_escape_string($conn, $empName);
        $department = mysqli_real_escape_string($conn, $department);
        $totalDays = '';
        if (isset($input["total_days"]) && $input["total_days"] !== '') {
            $totalDays = mysqli_real_escape_string($conn, $input["total_days"]);
        } else if (isset($input["no_day"]) && $input["no_day"] !== '') {
            $totalDays = mysqli_real_escape_string($conn, $input["no_day"]);
        }
        $createdOn = date("Y-m-d H:i:s");
        $sql = "INSERT INTO outdoor_duty(plant_id, emp_id, emp_name, department, designation, duty_details, checker, approver, from_date, to_date, total_days, status, createdOn)
            VALUES('".$plant."','".$empId."','".$empName."','".$department."','".$designation."','".$dutyDetails."','".$checker."','".$approver."','".$fromDate."','".$toDate."','".$totalDays."','PENDING_DEPT_HEAD','".$createdOn."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\",\"id\":" . $conn->insert_id . "}";
        } else {
            echo "{\"status\":\"error\",\"message\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "update_oudate_status") {
        $sql = "update outdoor_duty set status='".$input['status']."' where id='".$input['id']."' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "download_personal_hygiene") {
        
        
        if ($_GET["report"] == "Personal Hygiene Report") {
            
             $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        
        $html.='
 <table border="1" cellpadding="2">
 
                    <tr>
                        <td style="  width: 430px; font-size: 8;  text-align: left; "> DOCUMENT NAME :- PERSONAL HYGIENE REPORT</td>
                        <td style="  width: 350px; font-size: 8;  "> DOCUMENT NO :- SIPL/SOP/AD/01</td>

                    </tr>

                   <tr>
                        <td style="width: 30px; font-size: 8; font-weight: bold; text-align: center; ">Sr. No.</td>
                        <td style="width: 50px; font-size: 8; font-weight: bold; text-align: center; ">Date</td>
                        <td style="width: 100px;font-size: 8; font-weight: bold; text-align: center; ">NAME</td>
                        <td style="width: 70px; font-size: 8; font-weight: bold; text-align: center; ">Closed pocket Apron worn properly</td>
                         <td style="width: 70px; font-size: 8; font-weight: bold; text-align: center; ">Cap/ Mak worn prpoerly</td>
                        <td style="width: 70px; font-size: 8;  font-weight: bold;text-align: center; ">Nails trimmed propely</td>
                        <td style="width: 50px; font-size: 8;  font-weight: bold;text-align: center; ">Worn Jewelery</td>
                        <td style="width: 70px; font-size: 8; font-weight: bold; text-align: center; ">Open Cuts & wound</td>
                        <td style="width: 50px; font-size: 8; font-weight: bold; text-align: center; ">Found Beard</td>
                        <td style="width: 50px; font-size: 8; font-weight: bold; text-align: center; ">Footware</td>
                        <td style="width: 50px; font-size: 8; font-weight: bold; text-align: center; ">Any Medical device</td>
                        <td style="width: 50px; font-size: 8; font-weight: bold; text-align: center; ">Any allergy</td>
                        <td style="width: 35px; font-size: 8; font-weight: bold; text-align: center; ">Labour Name</td>
                        <td style="width: 35px; font-size: 8; font-weight: bold; text-align: center; ">Overall Remark</td>
                   </tr>

';
         
             $sql = "SELECT p.*,e.firstname,e.middlename,e.lastname FROM personal_hygiene p 
        LEFT JOIN employee e ON p.emp_id = e.emp_id where p.plant_id = '".$_GET['plant_id']."'";
           $i=1;
            $result1 = $conn->query($sql);
            if ($result1->num_rows > 0) {
                while ($row = $result1->fetch_assoc()) {

                    $html.='<tr>
                         <td style="width: 30px; font-size: 8;text-align: center; ">'.$i.'</td>
                         <td style="width: 50px; font-size: 8;text-align: center; ">'.date('d-m-Y', strtotime($row['personal_hygiene_date'])).'</td>
                        <td style="width: 100px;font-size: 9 ; "> '.$row["firstname"].' '.$row["middlename"].' '.$row["lastname"].'</td>
                        <td style="width: 70px; font-size: 8; text-align: center;">
                        ';
                        if($row["c_pocket"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                         </td>
                         
                           <td style="width: 70px; font-size: 8; text-align: center;">
                          ';
                        if($row["cap"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                         
                        <td style="width: 70px; font-size: 8; text-align: center;">
                          ';
                        if($row["nails"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                         <td style="width: 50px; font-size: 8; text-align: center;">
                          ';
                        if($row["jewelry"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                          <td style="width: 70px; font-size: 8; text-align: center;">
                          ';
                        if($row["cuts_wound"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                          <td style="width: 50px; font-size: 8; text-align: center;">
                          ';
                        if($row["beard"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        
                    
                  
                        <td style="width: 50px; font-size: 8;text-align: center; ">
                          ';
                        if($row["footware"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        
                        <td style="width: 50px; font-size: 8; text-align: center;">
                         ';
                        if($row["any_alergy"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        
                        <td style="width: 50px; font-size: 8;text-align: center; ">
                          ';
                        if($row["any_alergy"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        
                        <td style="width: 35px; font-size: 8; "> '.$row["labour_name"].'</td>
                        <td style="width: 35px; font-size: 8; "> '.$row["overall_remark"].'</td>

                   </tr>';
                    $i++;
                }
            } 
        
        $html.='</table>
                           <div></div>
      <table border="1" cellpadding="4">

                <tr>
                    <td style="width: 260px; font-size: 9;text-align: center;"> </td>
                    <td style="width: 260px; font-size: 9;text-align: center;"> </td>
                    <td style="width: 260px; font-size: 9;text-align: center;"> </td>

                </tr>
                <tr>
                    <td style="width: 260px; font-size: 9;text-align: center;"> HYGIENE SUPERVISOR</td>
                    <td style="width: 260px; font-size: 9;text-align: center;">QUALITY MANAGER</td>
                    <td style="width: 260px; font-size: 9;text-align: center;"> PLANT MANAGER</td>

                </tr>
         </table> ';
          
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Personal Hygiene Report.pdf', 'I');
       
         } 
         else  if ($_GET["report"] == "Factory Hygiene Report") {
            
             $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        
        $html.= '<table style="border: 1px solid black; ">

                    <tr>
                        <td style=" border: 1px solid black; width: 784px; font-size: 10 ; font-weight: bold; height: 10px; text-align: left; ">DOCUMENT NAME:-FACTORY HYGIENE REPORT &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Document No:-SIPL/SOP/AD/01</td>
                        
                    </tr>
                   <tr>
                   <td style=" border: 1px solid black; width: 30px; font-size: 7;  font-weight: bold; height: 20px; text-align: center; ">Sr.No.</td>
                   <td style=" border: 1px solid black; width: 70px; font-size: 7;  font-weight: bold; height: 20px; text-align: center; ">DATE</td>
                   <td style=" border: 1px solid black; width: 55px; font-size: 7;  font-weight: bold; height: 20px; text-align: center; ">FLOOR</td>
                   <td style=" border: 1px solid black; width: 40px; font-size: 7;  font-weight: bold; height: 20px; text-align: center; ">HAND WASHER</td>
                   <td style=" border: 1px solid black; width: 40px; font-size: 7;  font-weight: bold; height: 20px; text-align: center; ">AIR CURTAIN</td>
                   <td style=" border: 1px solid black; width: 40px; font-size: 7;  font-weight: bold; height: 10px; text-align: center; ">WALLS</td>
                   <td style=" border: 1px solid black; width: 43px; font-size: 7;  font-weight: bold; height: 10px; text-align: center; ">PALLET</td>
                   <td style=" border: 1px solid black; width: 44px; font-size: 7;  font-weight: bold; height: 10px; text-align: center; ">STORAGE</td>
                   <td style=" border: 1px solid black; width: 34px; font-size: 7;  font-weight: bold; height: 10px; text-align: center; ">WATER TANKS</td>
                   <td style=" border: 1px solid black; width: 29px; font-size: 7;  font-weight: bold; height: 10px; text-align: center; ">FLIES</td>
                   <td style=" border: 1px solid black; width: 39px; font-size: 7;  font-weight: bold; height: 10px; text-align: center; ">RODENT BOXES</td>
                   <td style=" border: 1px solid black; width: 45px; font-size: 7;  font-weight: bold; height: 10px; text-align: center; ">MACHINE</td>
                   <td style=" border: 1px solid black; width: 45px; font-size: 7;  font-weight: bold; height: 10px; text-align: center; ">UTENCILS</td>
                   <td style=" border: 1px solid black; width: 60px; font-size: 7;  font-weight: bold; height: 10px; text-align: center; ">FLYCATCHER TUBE CHECK</td>
                   <td style=" border: 1px solid black; width: 45px; font-size: 7;  font-weight: bold; height: 10px; text-align: center; ">ALL INSIDE CORNER</td>
                   <td style=" border: 1px solid black; width: 70px; font-size: 7;  font-weight: bold; height: 10px; text-align: center; "> SURROUNDING OF FACTORY </td>
                    <td style=" border: 1px solid black; width: 55px; font-size: 7;  font-weight: bold; height: 10px; text-align: center; ">REMARK</td>
                   </tr>';
                   
                    $sql = "SELECT * FROM faccleandate where   plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
        
                   
                    $html.= '
                   <tr>
                       <td style=" border: 1px solid black; width: 30px; font-size: 7;height: 20px; text-align: center; ">'.$i.'</td>
                       <td style=" border: 1px solid black; width: 70px; font-size: 7;height: 20px; text-align: center; ">'.$row["clean_date"].'</td>
                       <td style=" border: 1px solid black; width: 55px; font-size: 7;height: 20px; text-align: center; ">'.$row["floor"].'</td>
                       <td style=" border: 1px solid black; width: 40px; font-size: 7;height: 20px;  text-align: center;">
                                                ';
                        if($row["hwasher_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                       </td>
                       <td style=" border: 1px solid black; width: 40px; font-size: 7;height: 20px;  text-align: center;">
                         ';
                        if($row["curtain_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='                      
                       </td>
                       <td style=" border: 1px solid black; width: 40px; font-size: 7;height: 10px; text-align: center; ">
                                              ';
                        if($row["wall_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='  
                       </td>
                       <td style=" border: 1px solid black; width: 43px; font-size: 7;height: 10px; text-align: center; ">
                                                ';
                        if($row["pallet_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                       </td>
                       <td style=" border: 1px solid black; width: 44px; font-size: 7;height: 10px;  text-align: center;">
                                                ';
                        if($row["storage_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                       </td>
                       <td style=" border: 1px solid black; width: 34px; font-size: 7;height: 10px;  text-align: center;">
                        ';
                        if($row["wtank_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                       </td>
                       <td style=" border: 1px solid black; width: 29px; font-size: 7;height: 10px;  text-align: center;">
                                                ';
                        if($row["file_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                       </td>
                       <td style=" border: 1px solid black; width: 39px; font-size: 7;height: 10px;  text-align: center;">
                                                ';
                        if($row["rodent_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                       </td>
                       <td style=" border: 1px solid black; width: 45px; font-size: 7;height: 10px; text-align: center; ">
                                                ';
                        if($row["machine_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                       </td>
                       <td style=" border: 1px solid black; width: 45px; font-size: 7;height: 10px; text-align: center;  ">
                                                ';
                        if($row["utencils_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                       </td>
                       <td style=" border: 1px solid black; width: 60px; font-size: 7;height: 10px; text-align: center;  ">
                                                ';
                        if($row["flycather_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                       </td>
                       <td style=" border: 1px solid black; width: 45px; font-size: 7;height: 10px; text-align: center; ">
                                                ';
                        if($row["corner_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                       </td>
                       <td style=" border: 1px solid black; width: 70px; font-size: 7;height: 10px; text-align: center;  ">
                                                ';
                        if($row["surrounding_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                       </td>
                        <td style=" border: 1px solid black; width: 55px; font-size: 8;height: 10px;  "> '.$row["clean_remark"].'</td>
                   </tr>';
                   $i++;
            }
        }
                   $html.='</table>
                           <div></div>
      <table border="1" cellpadding="4">

                <tr>
                    <td style="width: 260px; font-size: 9;text-align: center;"> </td>
                    <td style="width: 260px; font-size: 9;text-align: center;"> </td>
                    <td style="width: 260px; font-size: 9;text-align: center;"> </td>

                </tr>
                <tr>
                    <td style="width: 260px; font-size: 9;text-align: center;"> HYGIENE SUPERVISOR</td>
                    <td style="width: 260px; font-size: 9;text-align: center;">QUALITY MANAGER</td>
                    <td style="width: 260px; font-size: 9;text-align: center;"> PLANT MANAGER</td>

                </tr>
         </table> ';
         
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Attaendence.pdf', 'I');
       
         }else  if ($_GET["report"] == "Toilet Cleaning Report") {
            
             $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        
        $html.= ' 
           <table border="1"  >

                    <tr>
                         <td style="width: 780px; font-weight: bold;text-align: center;">SAIPRO INDUSTRIES PVT.LTD.</td>
                    </tr>
                    <tr>
                         <td style=" border: 1px solid black; width: 475px; font-size: 10;  text-align:center; font-weight: bold; ">Daily Check List For Toilet cleaning</td>
                        <td style=" border: 1px solid black; width: 305px; font-size: 10; text-align:left; "> DOC NO: SIPL/SOP/AD/01</td>
                    </tr>
                    <tr>
                        <td style="width: 50px; text-align: center; font-size: 9; font-weight: bold; ">Date</td>
                        <td style="width: 65px; text-align: center; font-size: 9; font-weight: bold; ">Wash Basin</td>
                        <td style="width: 65px; text-align: center; font-size: 9; font-weight: bold; ">Urinal Pot</td>
                        <td style="width: 65px; text-align: center; font-size: 9; font-weight: bold; ">Urinal Gutter</td>
                        <td style="width: 65px; text-align: center; font-size: 9; font-weight: bold; ">Toilet Door</td>
                        <td style="width: 65px; text-align: center; font-size: 9; font-weight: bold; ">Toilet Block</td>
                        <td style="width: 70px; text-align: center; font-size: 9; font-weight: bold; ">Glaiz Tiles(Common Place)</td>
                        <td style="width: 55px; text-align: center; font-size: 9; font-weight: bold; ">Soap Dispenser</td>
                        <td style="width: 65px; text-align: center; font-size: 9; font-weight: bold; ">Exaust Fan</td>
                        <td style="width: 75px; text-align: center; font-size: 9; font-weight: bold; ">Pumbing/Water Pipe line</td>
                        <td style="width: 75px; text-align: center; font-size: 9; font-weight: bold; ">Checked By(Hygiene Superviser)</td>
                        <td style="width: 65px; text-align: center; font-size: 9; font-weight: bold; ">Verify By (Quality COntrol)</td>
                    </tr>
        
        
        ';
        
        
          $sql = "SELECT * FROM  toiletcleandata where   plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
        
        
          $html.= ' 
     
                     <tr>
                        <td style="width: 50px; text-align: center; font-size: 9;  ">'.$row["clean_date"].'</td>
                        <td style="width: 65px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["wbasin_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        <td style="width: 65px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["upot_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        <td style="width: 65px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["uguttar_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                         <td style="width: 65px; text-align: center; font-size: 9;  "> 
                             ';
                        if($row["tdorrs_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                         </td>
                        <td style="width: 65px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["tblock_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        <td style="width: 70px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["gtiles_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        <td style="width: 55px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["soapd_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        <td style="width: 65px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["efan_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        <td style="width: 75px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["pwpipel_clean"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        <td style="width: 75px; text-align: center; font-size: 9;  "> </td>
                        <td style="width: 65px; text-align: center; font-size: 9;  "> </td>
                    </tr>  
        
           ';
         
         
             }
        }
         
         
              $html.= '  </table>';
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Attaendence.pdf', 'I');
       
         }
         
         else  if ($_GET["report"] == "ETP Log Sheet") {
            
             $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        
        $html.= ' 
 
 

 <table border="1">
        
      
          <table border="1">
         <tr>
         <td style="width: 81px; font-size: 11; font-weight: bold; text-align: center;"></td>

             <td style="width: 704px; font-size: 11; font-weight: bold; text-align: center;">ETP LOG SHEET&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;             DOC NO:SIPL/SOP/AD/08</td>
         </tr>
      
         </table>
         <table border="1">
        
            <tr>
                <td style="width: 66px; font-size: 9; font-weight: bold; text-align: center;">Date</td>
                <td style="width: 60px; font-size: 9; font-weight: bold; text-align: center;">Day checked</td>
                <td style="width: 81px; font-size: 9; font-weight: bold; text-align: center;">Total Backwash & Rinse start Time</td>
                <td style="width: 81px; font-size: 9; font-weight: bold; text-align: center;">Total Backwash & Rinse start Time</td>
                <td style="width: 71px; font-size: 9; font-weight: bold; text-align: center;">Process Start Time</td>
                <td style="width: 71px; font-size: 9; font-weight: bold; text-align: center;">250 gm Line Add Time(15 min)</td>
                <td style="width: 71px; font-size: 9; font-weight: bold; text-align: center;">250 gm Alam Add Time(15 min)</td>
                <td style="width: 71px; font-size: 9; font-weight: bold; text-align: center;">500 ml poly Add Time(15 min)</td>
                <td style="width: 71px; font-size: 9; font-weight: bold; text-align: center;">Holding Time 1 hr</td>
                <td style="width: 71px;  font-size: 9; font-weight: bold; text-align: center;">Filter Water Transfer time</td>
                <td style="width: 71px;  font-size: 9; font-weight: bold; text-align: center;">Salary Transfer time</td>
            </tr>
';


 $sql = "SELECT * FROM  etplog where   plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
           
        $html.='
           <tr>
                 <td style="width: 66px; font-size: 10;  text-align: center;">'.$row["etp_date"].'</td>
                <td style="width: 60px; font-size: 10;  text-align: center;" >'.$row["etp_day"].'</td>
                <td style="width: 81px; font-size: 10;  text-align: center;" >'.$row["backWash"].'</td>
                <td style="width: 81px; font-size: 10;  text-align: center;">'.$row["rinse"].'</td>
                <td style="width: 71px; font-size: 10;  text-align: center;">'.$row["process_time"].'</td>
                <td style="width: 71px; font-size: 10;  text-align: center;">'.$row["add_time"].'</td>
                <td style="width: 71px; font-size: 10;  text-align: center;">'.$row["alam_time"].'</td>
                <td style="width: 71px; font-size: 10;  text-align: center;">'.$row["poly_time"].'</td>
                <td style="width: 71px; font-size: 10;  text-align: center;">'.$row["holding_time"].'</td>
                <td style="width: 71px;  font-size: 10;  text-align: center;">'.$row["water_time"].'</td>
                <td style="width: 71px;  font-size: 10;  text-align: center;">'.$row["transfer_time"].'</td>
             </tr>
         ';
        $i++;
            }
        }

      
        $html.='

         </table>
       
         <table border="1">
         <tr>
         <td style="width: 785px;height: 60px; font-size: 11; font-weight: bold; text-align: center;"colspan="2"></td>

         </tr>
         
      
         
         </table>
         <table border="1">
         <tr>
         <td style="width: 81px; font-size: 11; font-weight: bold; text-align: center;"></td>

             <td style="width: 340px;height: 20px;font-size: 11; font-weight: bold; text-align: center;">HYGENE SUPERVISOR</td>
             <td style="width: 364px;height: 20px;font-size: 11; font-weight: bold; text-align: center;">QUALITY HEAD</td>



         </tr>
      
         <br>
         </table> ';
         
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ETP Log Sheet.pdf', 'I');
       
         }
         else  if ($_GET["report"] == "Waste Disposal Sheet") {
            
             $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        
        $html.= ' 
 
 

 <table border="1">
        
      
          <table border="1">
         <tr>
         <td style="width: 81px; font-size: 11; font-weight: bold; text-align: center;"></td>

             <td style="width: 704px; font-size: 11; font-weight: bold; text-align: center;">Waste Disposal Log Sheet&nbsp;&nbsp;&nbsp;
             &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;             DOC NO:SIPL/SOP/AD/08-F04 </td>
         </tr>
      
         </table>
         <table border="1">
        
             <tr>
                <td style="width: 60px; font-size: 9; height: 20px; font-weight: bold; text-align: center;">Date</td>
                <td style="width: 65px; font-size: 9; font-weight: bold; text-align: center;">Day  Checked</td>
                <td style="width: 90px; font-size: 9; font-weight: bold; text-align: center;"> Ground Floor Dusbeen clened</td>
                <td style="width: 90px; font-size: 9; font-weight: bold; text-align: center;">Firsh Floor Dusbeen clened</td>
                <td style="width: 90px; font-size: 9; font-weight: bold; text-align: center;"> Outside Dusbeen clened</td>
                <td style="width: 70px; font-size: 9; font-weight: bold; text-align: center;">Lable property</td>
                <td style="width: 70px; font-size: 9; font-weight: bold; text-align: center;">Segerated property</td>
                <td style="width: 70px; font-size: 9; font-weight: bold; text-align: center;">Not Leaking</td>
                <td style="width: 80px; font-size: 9; font-weight: bold; text-align: center;">Storage limit Not Exceeded</td>
                <td style="width: 100px; font-size: 9; font-weight: bold; text-align: center;"> Wave disposel collected by the angency</td>
             </tr>
';


 $sql = "SELECT * FROM  wastedata where   plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
           
        $html.='
           <tr>
                        <td style="width: 60px; text-align: center; font-size: 9;  ">'.$row["clean_date"].'</td>
                        <td style="width: 65px; text-align: center; font-size: 9;  ">'.$row["clean_day"].'</td>
                      
                        <td style="width: 90px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["gf_dust"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        <td style="width: 90px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["ff_dust"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                         <td style="width: 90px; text-align: center; font-size: 9;  "> 
                             ';
                        if($row["o_dust"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                         </td>
                        <td style="width: 70px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["label_prop"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        <td style="width: 70px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["segregated_prop"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        <td style="width: 70px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["not_leaking"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        <td style="width: 80px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["storage_limit"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        <td style="width: 100px; text-align: center; font-size: 9;  "> 
                            ';
                        if($row["collected_by_agency"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                        else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                        $html.='
                        </td>
                        
                    </tr>  
        
         ';
        $i++;
            }
        }

      
        $html.='

         </table>
       
          
         <div></div>
         
         <table border="1">
         <tr>
         <td style="width: 81px; font-size: 11; font-weight: bold; text-align: center;"></td>

             <td style="width: 340px;height: 20px;font-size: 11; font-weight: bold; text-align: center;">HYGENE SUPERVISOR</td>
             <td style="width: 364px;height: 20px;font-size: 11; font-weight: bold; text-align: center;">QUALITY HEAD</td>



         </tr>
      
         </table> ';
         
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ETP Log Sheet.pdf', 'I');
       
         }
         else  if ($_GET["report"] == "RO Log Sheet") {
            
             $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        
        $html.= ' 
 
 

       
       
         <table border="1" cellpadding="3">
         <tr>
 
             <td style="width: 390px;  font-size: 9; font-weight: bold; "> RO LOG SHEET </td>
             <td style="width: 390px;  font-size: 9; font-weight: bold; "> DOC NO : SIPL/SOP/AD/08</td>
         </tr>
      
   
            <tr>
                <td style="width: 60px; font-size: 9; font-weight: bold; text-align: center;">Date</td>
                <td style="width: 60px; font-size: 9; font-weight: bold; text-align: center;">Day Checked</td>
                <td style="width: 90px; font-size: 9; font-weight: bold; text-align: center;">Raw Water lavel check</td>
                <td style="width: 90px; font-size: 9; font-weight: bold; text-align: center;">Sand filter backwash time</td>
                <td style="width: 90px; font-size: 9; font-weight: bold; text-align: center;">Sand filter Rines Time</td>
                <td style="width: 100px; font-size: 9; font-weight: bold; text-align: center;">Carbon filter backwash Time</td>
                <td style="width: 100px; font-size: 9; font-weight: bold; text-align: center;">Carbon filter Rinse Time</td>
                <td style="width: 100px; font-size: 9; font-weight: bold; text-align: center;">filter process Start time</td>
                <td style="width: 90px; font-size: 9; font-weight: bold; text-align: center;">filter process End time</td>
            </tr>
';
             
                $sql = "SELECT * FROM  rolog where   plant_id = '".$_GET['plant_id']."'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                       
                     $html.= ' 
                   <tr>
                        <td style="width: 60px; font-size: 10;   text-align: center;" >'.$row["ro_clean_date"].'</td>
                        <td style="width: 60px; font-size: 10;   text-align: center;" >'.$row["ro_clean_day"].'</td>
                        <td style="width: 90px; font-size: 10;  text-align: center;"> 
                            ';
                       if($row["ro_water_level"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                       else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                       $html.='
                        </td>
                        <td style="width: 90px; font-size: 10;  text-align: center;">'.$row["ro_backwash"].'</td>
                        <td style="width: 90px; font-size: 10; text-align: center;">'.$row["ro_rinse"].'</td>
                        <td style="width: 100px; font-size: 10; text-align: center;">'.$row["ro_carban_back"].'</td>
                        <td style="width: 100px; font-size: 10; text-align: center;">'.$row["ro_carban_rinse"].'</td>
                        <td style="width: 100px; font-size: 10; font-weight: bold; text-align: center;">'.$row["filter_process_time"].'</td>
                        <td style="width: 90px; font-size: 10; font-weight: bold; text-align: center;">'.$row["filter_process_end_time"].'</td>
                     </tr>
                     ';
             
             
                    }
                }
             
             
             $html.= ' 
             
            </table>
            <div></div>
       <table border="1" cellpadding="3">

                <tr>
                    <td style="width: 260px; font-size: 9;text-align: center;"> </td>
                    <td style="width: 260px; font-size: 9;text-align: center;"> </td>
                    <td style="width: 260px; font-size: 9;text-align: center;"> </td>

                </tr>
                <tr>
                    <td style="width: 260px; font-size: 9;text-align: center; font-weight: bold;"> HYGIENE SUPERVISOR</td>
                    <td style="width: 260px; font-size: 9;text-align: center; font-weight: bold;">QUALITY CONTROL</td>
                    <td style="width: 260px; font-size: 9;text-align: center; font-weight: bold;"> HR MANAGER</td>

                </tr>
         </table> 
            
       
         ';
                     
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('RO LOG SHEET.pdf', 'I');
       
         }
         else  if ($_GET["report"] == "Floor Dust Bins Cleaning") {
            
             $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        
        $html.= ' 
 
 

       
       
         <table border="1" cellpadding="3">
         <tr>
 
             <td style="width: 390px;  font-size: 9; font-weight: bold; "> RO LOG SHEET </td>
             <td style="width: 390px;  font-size: 9; font-weight: bold; "> DOC NO : SIPL/SOP/AD/08</td>
         </tr>
      
   
            <tr>
                <td style="width: 60px; font-size: 9; font-weight: bold; text-align: center;">Date</td>
                <td style="width: 60px; font-size: 9; font-weight: bold; text-align: center;">Day Checked</td>
                <td style="width: 90px; font-size: 9; font-weight: bold; text-align: center;">Raw Water lavel check</td>
                <td style="width: 90px; font-size: 9; font-weight: bold; text-align: center;">Sand filter backwash time</td>
                <td style="width: 90px; font-size: 9; font-weight: bold; text-align: center;">Sand filter Rines Time</td>
                <td style="width: 100px; font-size: 9; font-weight: bold; text-align: center;">Carbon filter backwash Time</td>
                <td style="width: 100px; font-size: 9; font-weight: bold; text-align: center;">Carbon filter Rinse Time</td>
                <td style="width: 100px; font-size: 9; font-weight: bold; text-align: center;">filter process Start time</td>
                <td style="width: 90px; font-size: 9; font-weight: bold; text-align: center;">filter process End time</td>
            </tr>
';
             
                $sql = "SELECT * FROM  rolog where   plant_id = '".$_GET['plant_id']."'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                       
                     $html.= ' 
                   <tr>
                        <td style="width: 60px; font-size: 10;   text-align: center;" >'.$row["ro_clean_date"].'</td>
                        <td style="width: 60px; font-size: 10;   text-align: center;" >'.$row["ro_clean_day"].'</td>
                        <td style="width: 90px; font-size: 10;  text-align: center;"> 
                            ';
                       if($row["ro_water_level"]=='1'){ $html.=' <span style="font-family:zapfdingbats;">4</span>'; }
                       else{ $html.=' <span style="font-family:zapfdingbats;">6</span>';}
                       $html.='
                        </td>
                        <td style="width: 90px; font-size: 10;  text-align: center;">'.$row["ro_backwash"].'</td>
                        <td style="width: 90px; font-size: 10; text-align: center;">'.$row["ro_rinse"].'</td>
                        <td style="width: 100px; font-size: 10; text-align: center;">'.$row["ro_carban_back"].'</td>
                        <td style="width: 100px; font-size: 10; text-align: center;">'.$row["ro_carban_rinse"].'</td>
                        <td style="width: 100px; font-size: 10; font-weight: bold; text-align: center;">'.$row["filter_process_time"].'</td>
                        <td style="width: 90px; font-size: 10; font-weight: bold; text-align: center;">'.$row["filter_process_end_time"].'</td>
                     </tr>
                     ';
             
             
                    }
                }
             
             
             $html.= ' 
             
            </table>
            <div></div>
       <table border="1" cellpadding="3">

                <tr>
                    <td style="width: 260px; font-size: 9;text-align: center;"> </td>
                    <td style="width: 260px; font-size: 9;text-align: center;"> </td>
                    <td style="width: 260px; font-size: 9;text-align: center;"> </td>

                </tr>
                <tr>
                    <td style="width: 260px; font-size: 9;text-align: center; font-weight: bold;"> HYGIENE SUPERVISOR</td>
                    <td style="width: 260px; font-size: 9;text-align: center; font-weight: bold;">QUALITY CONTROL</td>
                    <td style="width: 260px; font-size: 9;text-align: center; font-weight: bold;"> HR MANAGER</td>

                </tr>
         </table> 
            
       
         ';
                     
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Floor Dust Bins Cleaning pdf', 'I');
       
         }
         else  if ($_GET["report"] == "Pest Control Service Record") {
            
             $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        
       $html.='
         <table>
      
            <tr>
 
                <td style="width: 260px; font-size: 9; font-weight: bold; text-align: left;">  Name Of Company :- </td>
            </tr>
            <br>
            <tr>
                <td style="width: 780px; font-size: 9; font-weight: bold; text-align: left;"> Address  :- </td>
            </tr>
            <br> 
            <tr>
                <td style="width: 780px; font-size: 9; font-weight: bold; text-align: left;"> Service Name :-  '.$_GET['serviceName'].'</td>
            </tr>
        </table>

<div></div>

            <table border="1">
                <tr>
                    <td style="width: 126px;  font-size: 9;  font-weight: bold;text-align: center;">Date of Service</td>
                    <td style="width: 126px;  font-size: 9;  font-weight: bold;text-align: center;">pest Status/observation</td>
                    <td style="width: 126px;  font-size: 9;  font-weight: bold;text-align: center;">No of found</td>
                    <td style="width: 126px;  font-size: 9;  font-weight: bold;text-align: center;">Technician name</td>
                    <td style="width: 126px;  font-size: 9;  font-weight: bold;text-align: center;">Customer / Representative Name</td>
                    <td style="width: 140px;  font-size: 9;  font-weight: bold;text-align: center;"> Sign of Customer / Representative</td>
                </tr>';

              $sql = "SELECT * FROM  pest_control where  service_name LIKE '%".$_GET['serviceName']."%' AND   plant_id = '".$_GET['plant_id']."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                $html.='
                <tr>
                    <td style="width: 126px;   font-size: 10; text-align: center;">'.$row["date_of_service"].'</td>
                    <td style="width: 126px;   font-size: 10; text-align: center;">'.$row["pest_status_obv"].'</td>
                    <td style="width: 126px;   font-size: 10; text-align: center;">'.$row["no_of_found"].'</td>
                    <td style="width: 126px;   font-size: 10; text-align: center;">'.$row["technician_name"].'</td>
                    <td style="width: 126px;   font-size: 10; text-align: center;">'.$row["cust_rep_name"].'</td>
                    <td style="width: 140px;   font-size: 10; text-align: center;">'.$row["cust_Sign"].'</td>
                </tr> ';

                 }
            }

      $html.='
            </table>
                ';
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Pest_Control.pdf', 'I');
       
}
         else  if ($_GET["report"] == "Scrap Management Record") {
            
             $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        
        $html.= ' 
 
 
    
    <table  border="1" cellpadding="3">
    
     
         <tr>
             <td style="width: 780px; font-weight: bold;text-align: center;">Scrap Management Record</td>
         </tr>

 
         <tr> 
             <td style="width: 40px; font-size: 9; font-weight: bold;text-align: center;">Sr.No</td>
             <td style="width: 90px; font-size: 9; font-weight: bold;text-align: center;"> Date </td>
             <td style="width: 170px;font-size: 9; font-weight: bold;text-align: center;">Scrap Type(Cartons Box,paper waste,iron waste,plastic waste,Drum)</td>
             <td style="width: 120px;font-size: 9; font-weight: bold;text-align: center;">Type of wastage</td>
             <td style="width: 90px;font-size: 9; font-weight: bold;text-align: center;">Rate/Kg</td>
             <td style="width: 70px;font-size: 9; font-weight: bold;text-align: center;">Total Kg.</td>
             <td style="width: 60px;font-size: 9; font-weight: bold;text-align: center;">Total Amount</td>
             <td style="width: 140px; font-size: 9; font-weight: bold;text-align: center;">Scrap receivers Sign/Mob No.</td>
         </tr>
         
         ';
         
         $sql = "SELECT * FROM  scrap_record where   plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
             
         
                     $html.='
                     <tr>
                        <td style="width: 40px;  font-size: 10; text-align: center; " >'.$i.'</td>
                        <td style="width: 90px;  font-size: 10; text-align: center;">'.$row["scrap_date"].'</td>
                        <td style="width: 170px;  font-size: 10; text-align: center;">'.$row["scrap_type"].'</td>
                        <td style="width: 120px;  font-size: 10; text-align: center;">'.$row["type_wastage"].'</td>
                        <td style="width: 90px;  font-size: 10; text-align: center;">'.$row["scrap_rate_kg"].'</td>
                        <td style="width: 70px;  font-size: 10; text-align: center;">'.$row["scrap_total_kg"].'</td>
                        <td style="width: 60px;  font-size: 10; text-align: center;">'.$row["total_amount"].'</td>
                        <td style="width: 140px;  font-size: 10; text-align: center;"></td>
                    </tr>';
 
                $i++;
            }
        }
 
 
 
   $html.='
  </table>
  <div></div>
    <table border="1" cellpadding="2">
                <tr>
                    <td style="width: 390px; font-size: 9; font-weight: bold;text-align: center;">For Saipro Industries Pvt. Ltd.</td>
                    <td style="width: 390px; font-size: 9; font-weight: bold;text-align: center;">For Saipro Industries Pvt. Ltd.</td>
                </tr>
                <tr>
                    <td style="width: 390px; font-size: 9; font-weight: bold;text-align: center; "> Given By   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;              Verified By (G.M. Sir)</td>
                     <td style="width: 390px; font-size: 9; font-weight: bold;text-align: center;">  Verified By (H.R.)</td>
                </tr>
         </table>
        <table border="1" cellpadding="2">
            <tr>
                <td style="width: 100px; font-size: 9; font-weight: bold;text-align: center; ">Sign </td>
                <td style="width: 290px; font-size: 9; font-weight: bold; "> </td>
                <td style="width: 100px; font-size: 9; font-weight: bold;text-align: center; ">Sign</td>
                <td style="width: 290px; font-size: 9; font-weight: bold; "> </td>
            </tr>
        </table>

      ';
         
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Attaendence.pdf', 'I');
       
         }
         
       
    }
    


}

$conn->close();
?>