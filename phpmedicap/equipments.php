<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

  require './db.php';
require './token.php';
require './tcpdf/tcpdf.php';
 
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

function eq_usage_log_sql($conn) {
    $from = isset($_GET['from_date']) ? $conn->real_escape_string(trim((string)$_GET['from_date'])) : '';
    $to = isset($_GET['to_date']) ? $conn->real_escape_string(trim((string)$_GET['to_date'])) : '';
    $equipmentName = isset($_GET['equipment_name']) ? $conn->real_escape_string(trim((string)$_GET['equipment_name'])) : '';
    $equipmentType = isset($_GET['equipment_type']) ? $conn->real_escape_string(trim((string)$_GET['equipment_type'])) : '';
    $plantId = isset($_GET['plant_id']) ? $conn->real_escape_string(trim((string)$_GET['plant_id'])) : '';

    $sql = "SELECT e.*, e1.equipment_name, e1.equipment_type,
            TRIM(CONCAT(IFNULL(op.firstname,''), ' ', IFNULL(op.lastname,''))) AS operator_name,
            TRIM(CONCAT(IFNULL(ent.firstname,''), ' ', IFNULL(ent.lastname,''))) AS entry_by_name,
            TRIM(CONCAT(IFNULL(ap.firstname,''), ' ', IFNULL(ap.lastname,''))) AS approve_by_name
            FROM equipment_usages e
            LEFT JOIN equipment e1 ON e.equipment_code = e1.equipment_code
            LEFT JOIN employee op ON CAST(op.emp_id AS CHAR) = CAST(e.operator AS CHAR)
            LEFT JOIN employee ent ON CAST(ent.emp_id AS CHAR) = CAST(e.entry_by AS CHAR)
            LEFT JOIN employee ap ON CAST(ap.emp_id AS CHAR) = CAST(e.approve_by AS CHAR)
            WHERE 1=1";
    if ($from !== '' && $to !== '') {
        $sql .= " AND DATE(e.entry_date) BETWEEN '".$from."' AND '".$to."'";
    }
    if ($equipmentName !== '') {
        $sql .= " AND e1.equipment_name = '".$equipmentName."'";
    }
    if ($equipmentType !== '') {
        $sql .= " AND e1.equipment_type LIKE '%".$equipmentType."%'";
    }
    if ($plantId !== '') {
        $sql .= " AND e1.plant_id = '".$plantId."'";
    }
    $sql .= " ORDER BY e.entry_date DESC, e.id DESC";
    return $sql;
}

function eq_bmr_equipment_table_exists($conn) {
    $res = $conn->query("SHOW TABLES LIKE 'bmr_Equipment_data'");
    return $res && $res->num_rows > 0;
}

function eq_cleaning_log_where_sql($conn) {
    $from = $conn->real_escape_string(trim((string)($_GET['from_date'] ?? '')));
    $to = $conn->real_escape_string(trim((string)($_GET['to_date'] ?? '')));
    $equipmentCode = $conn->real_escape_string(trim((string)($_GET['equipment_id'] ?? $_GET['equipment_code'] ?? '')));
    $equipmentName = $conn->real_escape_string(trim((string)($_GET['equipment_name'] ?? '')));
    $equipmentType = $conn->real_escape_string(trim((string)($_GET['equipment_type'] ?? '')));
    $plantId = $conn->real_escape_string(trim((string)($_GET['plant_id'] ?? '')));

    $sql = " FROM equipment_cleaning e
             LEFT JOIN equipment e1 ON e.equipment_code = e1.equipment_code
             WHERE (
                 LOWER(TRIM(IFNULL(e.status, ''))) IN ('approve', 'approved')
                 OR (e.approve_by IS NOT NULL AND TRIM(e.approve_by) <> '')
             )";
    if ($plantId !== '') {
        $sql .= " AND (e.plant_id IS NULL OR TRIM(e.plant_id) = '' OR e.plant_id = '".$plantId."')";
    }
    if ($from !== '' && $to !== '') {
        $sql .= " AND (
            (e.entry_date IS NOT NULL AND TRIM(e.entry_date) <> '' AND DATE(e.entry_date) BETWEEN '".$from."' AND '".$to."')
            OR (e.clean_from IS NOT NULL AND TRIM(e.clean_from) <> '' AND DATE(REPLACE(SUBSTRING(e.clean_from, 1, 10), 'T', ' ')) BETWEEN '".$from."' AND '".$to."')
        )";
    }
    if ($equipmentCode !== '') {
        $sql .= " AND e.equipment_code LIKE '%".$equipmentCode."%'";
    }
    if ($equipmentName !== '') {
        $sql .= " AND e1.equipment_name LIKE '%".$equipmentName."%'";
    }
    if ($equipmentType !== '') {
        $sql .= " AND e1.equipment_type LIKE '%".$equipmentType."%'";
    }
    return $sql;
}

function eq_usage_log_row($row) {
    $operator = trim(isset($row['operator_name']) ? $row['operator_name'] : '');
    if ($operator === '') {
        $operator = isset($row['operator']) ? $row['operator'] : '';
    }
    $checkBy = trim(isset($row['approve_by_name']) ? $row['approve_by_name'] : '');
    if ($checkBy === '') {
        $checkBy = trim(isset($row['entry_by_name']) ? $row['entry_by_name'] : '');
    }
    if ($checkBy === '') {
        $checkBy = isset($row['entry_by']) ? $row['entry_by'] : '';
    }
    $row['operator'] = $operator;
    $row['entry_by'] = $checkBy;
    return $row;
}

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

  
      
   if ($_GET["type"] == "getDepartments") {
        $output = Array();
        $sql = "SELECT * FROM department WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
 
    else if ($_GET["type"] == "update_equipment_usage_cleaning_record") {
        $sql = "update equipment_usage_cleaning_record set status='".$input['status']."',checked_by='".$_GET['emp_id']."',checked_date='$entry_date' where id ='".$input['id']."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
            
        }
        
    } 
    
 
 
 else if($_GET["type"] == "getBacterialIncubators"){
         $output=Array();
        $sql="SELECT * FROM bactorial";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
      
 }else if($_GET["type"] == "getColonyCounters"){
         $output=Array();
        $sql="SELECT * FROM colony_calibration"; 
        //WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
       
        
        
 }else if($_GET["type"] == "getAutoclaves"){
        $output=Array();
          $sql="SELECT * FROM equipment where equipment_type LIKE '%Autoclave%' AND plant_id =  '".$_GET["plant_id"]."'";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
 }else if($_GET["type"] == "getBODIncubators"){
        $output=Array();
        $sql="SELECT * FROM equipment where department='Microbiology' and equipment_type='Incubator' ";
        // $sql="SELECT * FROM incubator_bod_usage ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
    } 
    
    else if($_GET['type'] == 'getAHU'){
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE equipment_type='AHU'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if($_GET['type'] == 'getDepartmentEquiments'){
        $output = Array();
        $sql = "SELECT * FROM equipments WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    
    else if ($_GET["type"] == "getEquipments1") {
        $output = Array();
        $sql = "SELECT * FROM equipment";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveEquipment") {
        $sql = "INSERT INTO equipments1 (department, equipment_code, equipment_name, equipment_sr_no, capacity,unit, location, description, make) VALUES ('".$input["department"]."', '".$input["equipment_code"]."', '".$input["equipment_name"]."', '".$input["equipment_sr_no"]."', '".$input["capacity"]."','".$input["unit"]."', '".$input["location"]."', '".$input["description"]."', '".$input["make"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
            
        }
        
    } else if ($_GET["type"] == "saveBODIncubators") {
        $sql = "INSERT INTO incubator_bod_usage (equipment_code, activity, batch_no, date_on,time_on, remark, ) 
        VALUES ('".$input["equipment_code"]."', '".$input["activity"]."', '".$input["batch_no"]."',
        '".$input["date_on"]."', '".$input["time_on"]."','".$input["remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
            
        }
    } else if ($_GET["type"] == "saveAutoclaves") {
        $sql = "INSERT INTO autoclave (cycle_no,material,cycle_start ,achieved_time , steam_pressure,cycle_end,
        hold_time,indicator,remark,done_by,entry_by,entry_date) VALUES
        ('".$input["cycle_no"]."', '".$input["media_name"]."', '".$input["cycle_start"]."', '".$input["achieved_time"]."' ,
        '".$input["steam_pressure"]."', '".$input["cycle_end"]."', '".$input["hold_time"]."', '".$input["indicator"]."',
        '".$input["remark"]."' , '".$input["done_by"]."',  '".$_GET["emp_id"]."' ,  '$entry_date')";
        if ($conn->query($sql)) {
           
          echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    } else if ($_GET["type"] == "saveMicroscopes") {
        $sql = "INSERT INTO culture_identification (organism_name, atcc_no, feature, feature_gram, 
        microscope,done_by) VALUES ('".$input["organism_name"]."', '".$input["atcc_no"]."', 
        '".$input["feature"]."', '".$input["feature_gram"]."', '".$input["microscope"]."','".$input["done_by"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }   
    }else if ($_GET["type"] == "getQCMicroEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipments1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }else if ($_GET["type"] == "getMicroscopes") {
        $output = Array();
         $sql = "SELECT * FROM equipment WHERE status='Active' AND equipment_type LIKE '%Microscope%' AND plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
    } else if ($_GET["type"] == "getDepartmentEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE status='Active' AND department='".$_GET["department_name"]."' AND plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getSectionEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipments WHERE status='approve' AND department='".$_GET["department"]."' AND section='".$_GET["section"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getUnderMaintenanceEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipments WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM maintainance_note WHERE equipment_code='".$row["equipment_code"]."' AND status IN ('pending', 'inprocess')";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["breakdown_time"] = $row1["breakdown_time"];
                        $output[] = $row;
                    }
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getLAFEquipments") {
        $output = Array();
        $sql1 = "SELECT * FROM equipment WHERE department='Quality Control'";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $sql2 = "SELECT id FROM equipment_usages WHERE equipment_code='".$row1["equipment_code"]."' ORDER BY id DESC LIMIT 1 ";
               
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $sql3 = "SELECT * FROM equipment e  WHERE e.department='Quality Control' AND (e.equipment_name LIKE '%RLAF%' or e.equipment_type = 'LAF') 
                               ";
                                                       
                        $result3 = $conn->query($sql3);
                        if ($result3->num_rows > 0) {
                            while ($row3 = $result3->fetch_assoc()) {
                           
                                $row1["clean_by"] = $row3["clean_by"];
                                $row1["clean_check_by"] = $row3["approve_by"];
                                $row1["clean_date"] = $row3["entry_date"];
                                $row1["clean"] = "yes";
                            }
                        } else {
                            $row1["clean_by"] = "";
                            $row1["clean_date"] = "";
                            $row1["clean_check_by"] = "";
                            $row1["clean"] = "no";
                        }
                    }
                } else {
                    $sql3 = "SELECT * FROM equipment_cleaning WHERE equipment_code='".$row1["equipment_code"]."' AND status='approve' ORDER BY id DESC LIMIT 1";
                    $result3 = $conn->query($sql3);
                    if ($result3->num_rows > 0) {
                        while ($row3 = $result3->fetch_assoc()) {
                            $row1["clean_by"] = $row3["clean_by"];
                            $row1["clean_check_by"] = $row3["approve_by"];
                            $row1["clean_date"] = $row3["entry_date"];
                            $row1["clean"] = "yes";
                        }
                    } else {
                        $row1["clean_by"] = "";
                        $row1["clean_date"] = "";
                        $row1["clean_check_by"] = "";
                        $row1["clean"] = "no";
                    }
                }

                $q = "select * from activity where equipment_code='".$row1["equipment_code"]."'    order by id desc limit 1";
                $r = $conn->query($q);
                $activityData = $r->fetch_assoc() ;
              
                if(!isset($activityData["end_date"]) || $activityData["end_date"] !="")
                {

                    $row1["activity_start_date"]="";
                }
                else{
                    $row1["activity_start_date"]= $activityData["start_date"];;
                }
                $output[] = $row1;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveGeneralEquipmentUsages") {
        
        $sql = "INSERT INTO equipment_usages (equipment_code, operator, activity, usage_from, usage_to, entry_by, entry_date) VALUES ('".$input["equipment_code"]."', '".$input["operator"]."', '".$input["activity"]."', '".$input["usage_from"]."', '".$input["usage_to"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getPendingGeneralEquipmentUsages") {
        $output = Array();
        $sql = "SELECT e.*, e1.equipment_name FROM equipment_usages e LEFT JOIN equipment e1 ON e.equipment_code=e1.equipment_code WHERE e.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateEquipmentUsage") {
        $sql = "UPDATE equipment_usages SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getEquipmentUsagesLog") {
        $output = Array();
        $sql = eq_usage_log_sql($conn);
        $result = @$conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = eq_usage_log_row($row);
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveGeneralEquipmentCleaning") {
           
        $sql = "INSERT INTO equipment_cleaning (equipment_code, clean_by, cleaning_type, clean_from, clean_to, entry_by, entry_date) VALUES ('".$input["equipment_code"]."', '".$input["clean_by"]."', '".$input["cleaning_type"]."', '".$input["clean_from"]."', '".$input["clean_to"]."', '".$_GET["emp_id"]."', '$entry_date')";
       
        if ($conn->query($sql)) {
            if (eq_bmr_equipment_table_exists($conn)) {
                $code = $conn->real_escape_string((string)($input['equipment_code'] ?? ''));
                $cleanFrom = $conn->real_escape_string((string)($input['clean_from'] ?? ''));
                $cleanTo = $conn->real_escape_string((string)($input['clean_to'] ?? ''));
                $cleanBy = $conn->real_escape_string((string)($input['clean_by'] ?? ''));
                $empId = $conn->real_escape_string((string)($_GET['emp_id'] ?? ''));
                $bmrSql = "UPDATE bmr_Equipment_data SET clean_from='".$cleanFrom."', clean_to='".$cleanTo."', clean_by='".$cleanBy."', done_by='".$empId."', dony_by_date='$entry_date', cleaning_type='".$cleanFrom."' WHERE equipment_code='".$code."'";
                $conn->query($bmrSql);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getPendingGeneralEquipmentCleaning") {
        $output = Array();
        $sql = "SELECT e.*, e1.equipment_name FROM equipment_cleaning e LEFT JOIN equipment e1 ON e.equipment_code=e1.equipment_code WHERE e.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateEquipmentCleaning") {
        $sql = "UPDATE equipment_cleaning SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            if (eq_bmr_equipment_table_exists($conn)) {
                $code = $conn->real_escape_string((string)($_GET['equipment_code'] ?? ''));
                $empId = $conn->real_escape_string((string)($_GET['emp_id'] ?? ''));
                $bmrSql = "UPDATE bmr_Equipment_data SET checked_by='".$empId."', checked_date='$entry_date' WHERE equipment_code='".$code."'";
                $conn->query($bmrSql);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getEquipmentCleaningLog") {
        $output = Array();
        $sql = " SELECT e.*, e1.equipment_name, e1.capacity, e1.equipment_type".eq_cleaning_log_where_sql($conn)." ORDER BY e.entry_date DESC, e.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "downloadEquipmentCleaningLog") {
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE);
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (trim((string)($_GET['from_date'] ?? '')) === '') {
            $_GET['from_date'] = date('Y-m-d', strtotime('-30 days'));
        }
        if (trim((string)($_GET['to_date'] ?? '')) === '') {
            $_GET['to_date'] = date('Y-m-d');
        }

        $_GET['filename'] = 'Equipment Cleaning Log';
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdfpage'] = 'L';
        include('pdfimp2.php');

        $html .= '
            <h2 style="text-align:center; font-size:12px;">Equipment Cleaning Log</h2>
            <table cellpadding="2" cellspacing="0" border="1" width="100%" style="border-collapse:collapse;">
                <tr style="text-align:center; background-color:#DDDAD9; font-weight:bold;">
                    <td width="6%" style="font-size:7px;">Sr.</td>
                    <td width="18%" style="font-size:7px;">Equipment Name</td>
                    <td width="14%" style="font-size:7px;">Equipment Code</td>
                    <td width="14%" style="font-size:7px;">Clean To</td>
                    <td width="14%" style="font-size:7px;">Clean From</td>
                    <td width="14%" style="font-size:7px;">Clean By</td>
                    <td width="20%" style="font-size:7px;">Cleaning Type</td>
                </tr>';

        $sql = " SELECT e.*, e1.equipment_name, e1.equipment_type".eq_cleaning_log_where_sql($conn)." ORDER BY e.entry_date DESC, e.id DESC";

        $i = 1;
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $cleanBy = trim((string)($row['clean_by'] ?? ''));
                if ($cleanBy === '') {
                    $cleanBy = trim((string)($row['entry_by'] ?? ''));
                }
                $html .= '<tr>
                    <td style="font-size:7px;">'.$i.'.</td>
                    <td style="font-size:7px;">'.htmlspecialchars((string)($row['equipment_name'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td style="font-size:7px;">'.htmlspecialchars((string)($row['equipment_code'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td style="font-size:7px;">'.htmlspecialchars((string)($row['clean_to'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td style="font-size:7px;">'.htmlspecialchars((string)($row['clean_from'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td style="font-size:7px;">'.htmlspecialchars($cleanBy, ENT_QUOTES, 'UTF-8').'</td>
                    <td style="font-size:7px;">'.htmlspecialchars((string)($row['cleaning_type'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                </tr>';
                $i++;
            }
        } else {
            $html .= '<tr><td colspan="7" style="font-size:7px; text-align:center;">No records found for selected period.</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Equipment_Cleaning_Log.pdf', 'I');
        exit;
    } 
    else if ($_GET["type"] == "getEquipmentCleaningLog_today") {
        $output = Array();
       $sql = " SELECT e.*, e1.equipment_name,e1.capacity FROM equipment_cleaning e LEFT JOIN equipment e1 ON e.equipment_code=e1.equipment_code WHERE  e.entry_date like '%".$_GET["to_date"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_prodEquipments_data") {
        
        
	$output = Array();

     	  $sql = "select * from bmr_Equipment_data";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    
        
    }
    else if ($_GET["type"] == "get_prodEquipments_dataMeha") {
        
        
	$output = Array();

     	  $sql = "select * from equipment where department='Production'";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    
        
    }
    else if ($_GET["type"] == "getLabours") {
        
        
	$output = Array();

     	  $sql = "SELECT * FROM employee WHERE department='".$_GET["department1"]."' and operator_category='".$_GET["operator_category"]."'";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    
        
    }
    else if ($_GET["type"] == "getEquipments") {
        $output = Array();
        $plantId = isset($_GET['plant_id']) ? $conn->real_escape_string(trim((string)$_GET['plant_id'])) : '';
        $userNo = isset($_GET['user_no']) ? $conn->real_escape_string(trim((string)$_GET['user_no'])) : '';
        $statusOk = "(e.status IS NULL OR e.status='' OR e.status='approve' OR e.status='Approved' OR e.status='Active' OR e.status='active')";

        $nameSql = "SELECT DISTINCT equipment_name FROM equipment e WHERE ".$statusOk." AND equipment_name IS NOT NULL AND TRIM(equipment_name) <> ''";
        if ($plantId !== '') {
            $nameSql .= " AND e.plant_id='".$plantId."'";
        }
        if ($userNo !== '') {
            $nameSql .= " AND e.user_no='".$userNo."'";
        }
        $nameSql .= " ORDER BY equipment_name";

        $result = @$conn->query($nameSql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $nameEsc = $conn->real_escape_string($row['equipment_name']);
                $output1 = Array();
                $sql1 = "SELECT e.* FROM equipment e WHERE e.equipment_name='".$nameEsc."' AND ".$statusOk;
                if ($plantId !== '') {
                    $sql1 .= " AND e.plant_id='".$plantId."'";
                }
                if ($userNo !== '') {
                    $sql1 .= " AND e.user_no='".$userNo."'";
                }
                $result1 = @$conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1['clean'] = 'yes';
                        $output1[] = $row1;
                    }
                }
                if (count($output1) > 0) {
                    $output[] = array('equipment_name' => $row['equipment_name'], 'equipments' => $output1);
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEquipmentCategories") {
        $output = Array();
        $sql = "SELECT equipment_type FROM equipment_names GROUP BY equipment_type";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM equipment_names WHERE equipment_type='".$row["equipment_type"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["equipments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEquipmentsByType") {
        $output = Array();
          $sql = "SELECT DISTINCT(equipment_name) FROM equipment_names";
       // $sql = "SELECT equipment_name FROM equipment_names WHERE equipment_type='".$_GET["equipment_type"]."'";
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEquipmentTypes") {
        $output = Array();
        $sql = "SELECT DISTINCT(equipment_type) FROM equipment_names";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getDeptAllEquipments") {
        $output = Array();
        $department = $conn->real_escape_string(isset($_GET["department"]) ? $_GET["department"] : '');
        $equipmentType = $conn->real_escape_string(isset($_GET["equipment_type"]) ? $_GET["equipment_type"] : '');
        $status = isset($_GET["status"]) ? trim($_GET["status"]) : '';
        $plantId = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : '';

        $sql = "SELECT * FROM equipment WHERE department='".$department."'";
        if ($equipmentType !== '') {
            $sql .= " AND equipment_type LIKE '%".$equipmentType."%'";
        }
        if ($status !== '') {
            if (strcasecmp($status, 'Active') === 0) {
                $sql .= " AND (status IS NULL OR status='' OR status IN ('Active','active','Approved','approve','Approved'))";
            } else {
                $sql .= " AND status LIKE '%".$conn->real_escape_string($status)."%'";
            }
        }
        if ($plantId !== '') {
            $sql .= " AND plant_id='".$plantId."'";
        }
        $sql .= " ORDER BY equipment_name, equipment_code";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (empty($row['calibration']) && !empty($row['calibration_required'])) {
                    $row['calibration'] = $row['calibration_required'];
                }
                if (empty($row['section']) && !empty($row['location'])) {
                    $row['section'] = $row['location'];
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getAllEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE status='approve' ORDER BY equipment_type, equipment_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getallassets") {
        $output = Array(); 
        $sql = "SELECT * FROM equipment where assets_status = '0'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getassets_eqp") {
        $output = Array();
        $sql = "SELECT * FROM equipment where assets_status = '1'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getStoreVacuums") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE department='Store' AND equipment_type  LIKE '%Vaccum%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }  else if ($_GET["type"] == "getQCLAfRAF") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE department='Quality Control' AND (equipment_name LIKE '%RLAF%' or equipment_type = 'LAF')        ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getStoreBalance") {
        $output = Array();
        //  $sql = "SELECT * FROM equipment WHERE department='store' AND equipment_name LIKE '%Balance%'";
        $sql="select serial_no as equipment_code,status,equipment_name from equipment where department='store' AND equipment_name LIKE '%Balance%'";
        //  $sql = "SELECT * FROM equipment WHERE department='store' AND equipment_name LIKE '%Balance%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEquipmentNames") {
        $output = Array();
        $sql = "SELECT equipment_type, equipment_name,department,capacity,equipment_code FROM equipment where department='".$_GET["department1"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getInstruments") {
        $output = array();
        $sql = "SELECT equipment_name FROM equipment_names WHERE equipment_type='Instrument'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["name"] = $row["equipment_name"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
   
 
    }else  if ($_GET["type"] == "downloadAllEquipments") {
      $_GET['filename'] = 'equipments'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
       $html= "";
         $html.='<table cellpadding="5" border="1">
       <tr>
            <td style="width:10%; text-align:center;"><b>Sr.</b></td>
            <td style="width:20%; text-align:center;"><b>Equipment Type</b></td>
            <td style="width:20%; text-align:center;"><b>Equipment Name</b></td>
            <td style="width:15%; text-align:center;"><b>Equipment code</b></td>
            <td style="width:10%; text-align:center;"><b>Category</b></td>
            <td style="width:15%; text-align:center;"><b>Make</b></td>
            <td style="width:10%; text-align:center;"><b>capacity</b></td>
        </tr>';
        
        $sql = "SELECT * FROM equipment WHERE status='approve' ORDER BY equipment_type, equipment_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
                
         $html.=' <tr>
            <td style="width:10%;">'.$i.'</td>
            <td style="width:20%;">'.$row['equipment_type'].'</td>
            <td style="width:20%;">'.$row['equipment_name'].'</td>
            <td style="width:15%;">'.$row['equipment_code'].'</td>
            <td style="width:10%;">'.$row['equipment_category'].'</td>
            <td style="width:15%;">'.$row['make'].'</td>
            <td style="width:10%;">'.$row['capacity'].'</td>
        </tr>';
          $i++;
        }
        }
         $html.='  </table>';
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Equipment Log.pdf', 'I');
        
        
        
     }else if ($_GET["type"] == "downloadEquipmentUsagesLog") {
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE);
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $_GET['filename'] = 'Equipment Usages Log';
        $_GET['pdftype'] = 'onlyheader';
        include("pdfimp2.php");

        $esc = function ($value) {
            $text = trim((string)$value);
            return $text === '' ? '-' : htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        };

        $html = '
        <h2 style="text-align:center;">Equipment Usage Log</h2>
        <table cellpadding="4" border="1" width="100%">
            <tr style="background-color:#DDDAD9;font-weight:bold;">
                <td width="6%" align="center">Sr.</td>
                <td width="14%">Equipment Name</td>
                <td width="12%">Equipment Code</td>
                <td width="12%">Activity</td>
                <td width="14%">Usage From</td>
                <td width="14%">Usage To</td>
                <td width="14%">Operator</td>
                <td width="14%">Check By</td>
            </tr>';

        $sql = eq_usage_log_sql($conn);
        $result = @$conn->query($sql);
        $i = 1;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = eq_usage_log_row($row);
                $html .= '<tr>
                    <td width="6%" align="center">'.$i.'</td>
                    <td width="14%">'.$esc(isset($row['equipment_name']) ? $row['equipment_name'] : '').'</td>
                    <td width="12%">'.$esc(isset($row['equipment_code']) ? $row['equipment_code'] : '').'</td>
                    <td width="12%">'.$esc(isset($row['activity']) ? $row['activity'] : '').'</td>
                    <td width="14%">'.$esc(isset($row['usage_from']) ? $row['usage_from'] : '').'</td>
                    <td width="14%">'.$esc(isset($row['usage_to']) ? $row['usage_to'] : '').'</td>
                    <td width="14%">'.$esc(isset($row['operator']) ? $row['operator'] : '').'</td>
                    <td width="14%">'.$esc(isset($row['entry_by']) ? $row['entry_by'] : '').'</td>
                </tr>';
                $i++;
            }
        } else {
            $html .= '<tr><td colspan="8" align="center">No records found</td></tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('EquipmentUsagesLog.pdf', 'I');
        exit;
}    else if ($_GET["type"] == "update_Temprature_humidty_record") {
        $sql = "update temperhumrec set status='".$input['status']."',checked_by='".$_GET['emp_id']."',CheckedbyDate='$entry_date' where id ='".$input['id']."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
            
        }
        
    }
    else if ($_GET["type"] == "get_save_equipment_usage_cleaning_record") {
        
         $output = Array();
        $sql = "SELECT * FROM equipment_usage_cleaning_record where status='pending' and department='".$_GET['depart']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "savetemperhumrec") {
        $sql = "INSERT INTO temperhumrec  (plant_id,department,month ,room_no,date ,time ,temperature ,humidity,done_by ,donebyDate ,entry_by  ,entry_date ,remark) 
                VALUES ('".$_GET['plant_id']."','".$input['department']."','".$input['tempmon']."','".$input['section']."','".$input['date']."','".$input['timeinhr']."','".$input['temp']."',
                '".$input['humidity']."','".$input['done_by']."','".$input['donebyDate']."','".$_GET['emp_id']."','$entry_date','".$input['remark']."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if ($_GET["type"] == "save_area_ceaningRecord") {
        $sql = "INSERT INTO temperhumrec  (plant_id,department,month ,room_no,date ,time ,done_by ,donebyDate ,entry_by  ,entry_date ,remark) 
                VALUES ('".$input['plant_id']."','".$input['department']."','".$input['tempmon']."','".$input['section']."','".$input['date']."','".$input['timeinhr']."','".$input['done_by']."','".$input['donebyDate']."','".$_GET['emp_id']."','$entry_date','".$input['remark']."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    
    else if ($_GET["type"] == "get_area_cleaningRecordLog") {

    header('Content-Type: application/json; charset=utf-8');

    $plant_id   = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : '';
    $department = isset($_GET["depart"]) ? $conn->real_escape_string($_GET["depart"]) : '';
    $month      = isset($_GET["month"]) ? $conn->real_escape_string($_GET["month"]) : '';

    $sql = "SELECT id, plant_id, department, month, room_no, date, time, temperature, humidity,
                   done_by, donebyDate, entry_by, entry_date, remark, status, checked_by, CheckedbyDate
            FROM temperhumrec
            WHERE 1=1";

    // Only apply filter if non-empty (empty plant_id in DB must still show when you don't filter)
    if ($plant_id !== '') {
        $sql .= " AND (plant_id = '".$plant_id."' OR plant_id IS NULL OR plant_id = '')";
    }
    if ($department !== '') {
        $sql .= " AND department = '".$department."'";
    }
    if ($month !== '') {
        $sql .= " AND month = '".$month."'";
    }

    $sql .= " ORDER BY id DESC";

    $rows = [];
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        echo json_encode($rows);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}
    else if ($_GET["type"] == "get_area_cleaningRecord") {

    header('Content-Type: application/json; charset=utf-8');

    $plant_id   = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : '';
    $department = isset($_GET["depart"]) ? $conn->real_escape_string($_GET["depart"]) : '';
    $month      = isset($_GET["month"]) ? $conn->real_escape_string($_GET["month"]) : '';

    $sql = "SELECT id, plant_id, department, month, room_no, date, time, temperature, humidity,
                   done_by, donebyDate, entry_by, entry_date, remark, status, checked_by, CheckedbyDate
            FROM temperhumrec
            WHERE 1=1";

    // Only apply filter if non-empty (empty plant_id in DB must still show when you don't filter)
    if ($plant_id !== '') {
        $sql .= " AND (plant_id = '".$plant_id."' OR plant_id IS NULL OR plant_id = '')";
    }
    if ($department !== '') {
        $sql .= " AND department = '".$department."'";
    }
    if ($month !== '') {
        $sql .= " AND month = '".$month."'";
    }

    $sql .= " ORDER BY id DESC";

    $rows = [];
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        echo json_encode($rows);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}
    
if ($_GET['type'] == 'get_Equipments') {
    $output = array();
    $sql = "SELECT * FROM equipment WHERE plant_id='" . $_GET['plant_id'] . "' AND   department='" . $_GET['depart'] . "'  Order By equipment_name";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET['type'] == 'getSECTIONS') {
    $output = array();
    $sql = "SELECT * FROM section WHERE department='" . $_GET['department1'] . "' order by section_name";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET['type'] == 'getproductName') {
    $output = array();
    $sql = "SELECT product_name,product_code,grade FROM product where  plant_id='" . $_GET['plant_id'] . "'   ";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET['type'] == 'get_Eqgetemployee_byDeptipments') {
    $output = array();
    $sql = "SELECT   firstname  as emp_name,emp_id,department,designation FROM employee WHERE status='Active'
               AND plant_id='" . $_GET['plant_id'] . "' and department='" . $_GET['depart'] . "' ORDER BY firstname asc";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET['type'] == 'savePressurediff') {
    $sql = "INSERT INTO pressurediffrential   (plant_id,department,month ,room_no,date ,time ,temperature ,done_by ,donebyDate ,entry_by  ,entry_date ,remark)
                VALUES ('" . $input['plant_id'] . "','" . $input['department'] . "','" . $input['tempmon'] . "','" . $input['section'] . "','" . $input['date'] . "','" . $input['timeinhr'] . "',
                '" . $input['pressure'] . "',
             '" . $input['done_by'] . "','" . $input['donebyDate'] . "','" . $_GET['emp_id'] . "','$entry_date','" . $input['remark'] . "')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET['type'] == 'get_Equipments_byCode') {
    $output = array();
    $sql = "SELECT * FROM equipment WHERE plant_id='" . $_GET['plant_id'] . "' AND   equipment_code='" . $_GET['equipment_code'] . "'  Order By equipment_name";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row);
    } else {
        echo '{}';
    }
} else if ($_GET['type'] == 'save_equipment_usage_cleaning_record') {
    $sql = "INSERT INTO equipment_usage_cleaning_record (department,
            plant_id, equipment_name, equipment_code, date, activity, productYesNo, productName, cleaningType, batchNo, startTime, endTime, doneBy, doneOn, entry_by, entry_date
        ) VALUES (
            '" . $conn->real_escape_string($input['department']) . "',
            '" . $conn->real_escape_string($_GET['plant_id']) . "',
            '" . $conn->real_escape_string($input['equipment_name']) . "',
            '" . $conn->real_escape_string($input['equipment_code']) . "',
            '" . $conn->real_escape_string($input['date']) . "',
            '" . $conn->real_escape_string($input['activity']) . "',
            '" . $conn->real_escape_string($input['productYesNo']) . "',
            '" . $conn->real_escape_string($input['productName']) . "',
            '" . $conn->real_escape_string($input['cleaningType']) . "',
            '" . $conn->real_escape_string($input['batchNo']) . "',
            '" . $conn->real_escape_string($input['startTime']) . "',
            '" . $conn->real_escape_string($input['endTime']) . "',
            '" . $conn->real_escape_string($input['doneBy']) . "',
            '" . $conn->real_escape_string($input['doneOn']) . "',
            '" . $conn->real_escape_string($_GET['emp_id']) . "',
            '" . $conn->real_escape_string($entry_date) . "'
        )";

    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\", \"error\":\"" . $conn->error . "\"}";
    }
} else if ($_GET['type'] == 'get_area_cleaning_Checking') {
    $output = array();
    $sql = "SELECT * FROM temperhumrec where status='pending' and department='" . $_GET['depart'] . "'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET['type'] == 'get_PressureDiff_Checking') {
    $output = array();
    $sql = "SELECT * FROM pressurediffrential  where status='pending' and department='" . $_GET['depart'] . "'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET['type'] == 'update_Pressure_record') {
    $sql = "update pressurediffrential  set status='" . $input['status'] . "',checked_by='" . $_GET['emp_id'] . "',checked_date='$entry_date' where id ='" . $input['id'] . "'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET['type'] == 'getTempratureHumidityChecking') {
    $output = array();
    $sql = "SELECT * FROM temperhumrec WHERE department= '" . $_GET['depart'] . "' and status= 'pending' and  plant_id= '" . $_GET['plant_id'] . "'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET['type'] == 'update_area_cleaning_record') {
    $sql = "update area_cleaning  set status='" . $input['status'] . "',checked_by='" . $_GET['emp_id'] . "',checked_date='$entry_date' where id ='" . $input['id'] . "'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>