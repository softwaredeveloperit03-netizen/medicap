<?php
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

function cal_emp_name($conn, $emp_id) {
    if (!$emp_id) {
        return '';
    }
    $emp_id = $conn->real_escape_string($emp_id);
    $sql = "SELECT CONCAT(firstname, ' ', lastname) AS nm FROM employee WHERE emp_id='".$emp_id."' LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return trim($row['nm']) !== '' ? trim($row['nm']) : $emp_id;
    }
    return $emp_id;
}

function cal_format_date($date) {
    if (!$date || $date === '0000-00-00') {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date('d-m-Y', $ts) : $date;
}

function cal_schedule_summary($conn, $equipment_id, $due_type) {
    $equipment_id = $conn->real_escape_string($equipment_id);
    $due_type = $conn->real_escape_string($due_type);
    $summary = array(
        'generated' => false,
        'next_due' => '',
        'next_due_fmt' => '',
        'calendar_till' => '',
        'calendar_till_fmt' => '',
        'frequency' => '',
        'generated_by' => '',
        'generated_on' => '',
        'generated_on_fmt' => '',
    );
    $sql = "SELECT COUNT(*) AS cnt,
                   MIN(CASE WHEN due_date >= CURDATE() THEN due_date END) AS next_due,
                   MIN(due_date) AS first_due,
                   MAX(due_date) AS calendar_till,
                   MIN(NULLIF(frequency, '')) AS frequency,
                   MIN(NULLIF(entry_by, '')) AS entry_by,
                   MIN(NULLIF(entry_date, '')) AS entry_date
            FROM equipment_calibration
            WHERE equipment_id='".$equipment_id."' AND due_type='".$due_type."'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ((int)$row['cnt'] > 0) {
            $summary['generated'] = true;
            $nextDue = $row['next_due'] ? $row['next_due'] : $row['first_due'];
            $summary['next_due'] = $nextDue;
            $summary['next_due_fmt'] = cal_format_date($nextDue);
            $summary['calendar_till'] = $row['calendar_till'];
            $summary['calendar_till_fmt'] = cal_format_date($row['calendar_till']);
            $summary['frequency'] = $row['frequency'];
            $summary['generated_by'] = cal_emp_name($conn, $row['entry_by']);
            $summary['generated_on'] = $row['entry_date'];
            $summary['generated_on_fmt'] = cal_format_date(substr((string)$row['entry_date'], 0, 10));
        }
    }
    return $summary;
}

function cal_has_frequency($freq) {
    if (!is_array($freq)) {
        return false;
    }
    foreach ($freq as $item) {
        if (!is_array($item)) {
            continue;
        }
        if (!empty($item['__cal_type_meta'])) {
            continue;
        }
        if (!empty($item['checked'])) {
            return true;
        }
    }
    return false;
}

function cal_type_from_meta($freq) {
    if (!is_array($freq)) {
        return '';
    }
    foreach ($freq as $item) {
        if (is_array($item) && !empty($item['__cal_type_meta']) && !empty($item['calibration_type'])) {
            return trim($item['calibration_type']);
        }
    }
    return '';
}

function cal_strip_type_meta($freq) {
    if (!is_array($freq)) {
        return array();
    }
    return array_values(array_filter($freq, function ($item) {
        return !(is_array($item) && !empty($item['__cal_type_meta']));
    }));
}

function cal_with_type_meta($freq, $calType) {
    $freq = cal_strip_type_meta(is_array($freq) ? $freq : array());
    if ($calType !== '' && !cal_has_frequency($freq)) {
        $freq[] = array(
            'id' => 0,
            '__cal_type_meta' => true,
            'calibration_type' => $calType,
            'checked' => false,
        );
    }
    return $freq;
}

function cal_resolve_type($row) {
    if (!empty($row['calibration_type'])) {
        return trim($row['calibration_type']);
    }
    $metaType = cal_type_from_meta(isset($row['calibration_frequency_inhouse']) ? $row['calibration_frequency_inhouse'] : array());
    if ($metaType === '') {
        $metaType = cal_type_from_meta(isset($row['calibration_frequency_external']) ? $row['calibration_frequency_external'] : array());
    }
    if ($metaType !== '') {
        return $metaType;
    }
    $hasIn = cal_has_frequency($row['calibration_frequency_inhouse']);
    $hasEx = cal_has_frequency($row['calibration_frequency_external']);
    if ($hasIn && $hasEx) {
        return 'Both';
    }
    if ($hasIn) {
        return 'Inhouse';
    }
    if ($hasEx) {
        return 'External';
    }
    return '';
}

function cal_insert_schedule($conn, $equipment_id, $due_type, $freq_array) {
    if (!is_array($freq_array) || count($freq_array) === 0) {
        return;
    }
    $equipment_id = $conn->real_escape_string($equipment_id);
    $due_type = $conn->real_escape_string($due_type);
    $values = array();
    $entryBy = $conn->real_escape_string($_GET["emp_id"]);
    $entryDate = date('Y-m-d h:i:s');
    foreach ($freq_array as $value) {
        if (empty($value['last_cali_date'])) {
            continue;
        }
        $particular = trim($value['particular']);
        $date = $value['last_cali_date'];
        if ($particular == 'Daily') {
            for ($i = 0; $i < 365; $i++) {
                $date = date('Y-m-d', strtotime($date . ' + 1 days'));
                $values[] = "('".$equipment_id."','".$date."','".$due_type."','Daily','".$entryBy."','".$entryDate."')";
            }
        } else if ($particular == 'Weekly') {
            for ($i = 0; $i < 52; $i++) {
                $date = date('Y-m-d', strtotime($date . ' + 7 days'));
                $values[] = "('".$equipment_id."','".$date."','".$due_type."','Weekly','".$entryBy."','".$entryDate."')";
            }
        } else if ($particular == 'FortNightly') {
            for ($i = 0; $i < 26; $i++) {
                $date = date('Y-m-d', strtotime($date . ' + 15 days'));
                $values[] = "('".$equipment_id."','".$date."','".$due_type."','FortNightly','".$entryBy."','".$entryDate."')";
            }
        } else if ($particular == 'Monthly') {
            for ($i = 0; $i < 12; $i++) {
                $date = date('Y-m-d', strtotime($date . ' + 30 days'));
                $values[] = "('".$equipment_id."','".$date."','".$due_type."','Monthly','".$entryBy."','".$entryDate."')";
            }
        } else if ($particular == 'Quarterly') {
            for ($i = 0; $i < 4; $i++) {
                $date = date('Y-m-d', strtotime($date . ' + 90 days'));
                $values[] = "('".$equipment_id."','".$date."','".$due_type."','Quarterly','".$entryBy."','".$entryDate."')";
            }
        } else if ($particular == 'Half-Yearly') {
            for ($i = 0; $i < 2; $i++) {
                $date = date('Y-m-d', strtotime($date . ' + 180 days'));
                $values[] = "('".$equipment_id."','".$date."','".$due_type."','Half-Yearly','".$entryBy."','".$entryDate."')";
            }
        } else if ($particular == 'Annually') {
            for ($i = 0; $i < 1; $i++) {
                $date = date('Y-m-d', strtotime($date . ' + 364 days'));
                $values[] = "('".$equipment_id."','".$date."','".$due_type."','Annually','".$entryBy."','".$entryDate."')";
            }
        }
    }
    if (count($values) === 0) {
        return;
    }
    $statusCol = '';
    if (cal_calibration_has_column($conn, 'cali_status')) {
        $statusCol = ',cali_status';
        foreach ($values as $i => $value) {
            $values[$i] = rtrim($value, ')') . ",'Pending')";
        }
    }
    $sql1 = "INSERT INTO equipment_calibration(equipment_id,due_date,due_type,frequency,entry_by,entry_date".$statusCol.") VALUES ".implode(',', $values);
    $conn->query($sql1);
}

function cal_calibration_has_column($conn, $column) {
    if (!isset($GLOBALS['cal_calibration_column_cache'])) {
        $GLOBALS['cal_calibration_column_cache'] = array();
    }
    $cache = &$GLOBALS['cal_calibration_column_cache'];
    if (!isset($cache[$column])) {
        $columnEsc = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM equipment_calibration LIKE '".$columnEsc."'");
        $cache[$column] = ($result && $result->num_rows > 0);
    }
    return $cache[$column];
}

function cal_ensure_calibration_schedule_columns($conn) {
    if (!cal_calibration_has_column($conn, 'remark')) {
        $conn->query("ALTER TABLE equipment_calibration ADD COLUMN remark TEXT NULL DEFAULT NULL");
        unset($GLOBALS['cal_calibration_column_cache']['remark']);
    }
    if (!cal_calibration_has_column($conn, 'cali_status')) {
        $conn->query("ALTER TABLE equipment_calibration ADD COLUMN cali_status VARCHAR(32) NULL DEFAULT 'Pending'");
        unset($GLOBALS['cal_calibration_column_cache']['cali_status']);
    }
}

function cal_equipment_has_column($conn, $column) {
    if (!isset($GLOBALS['cal_equipment_column_cache'])) {
        $GLOBALS['cal_equipment_column_cache'] = array();
    }
    $cache = &$GLOBALS['cal_equipment_column_cache'];
    if (!isset($cache[$column])) {
        $columnEsc = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM equipment LIKE '".$columnEsc."'");
        $cache[$column] = ($result && $result->num_rows > 0);
    }
    return $cache[$column];
}

function cal_ensure_calibration_columns($conn) {
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $columns = array(
        'calibration_type' => "VARCHAR(32) NULL DEFAULT NULL",
        'calibration_frequency_inhouse' => "LONGTEXT NULL",
        'calibration_frequency_external' => "LONGTEXT NULL",
        'inhouse_department' => "VARCHAR(128) NULL DEFAULT NULL",
        'external_department' => "VARCHAR(128) NULL DEFAULT NULL",
    );
    foreach ($columns as $column => $definition) {
        if (!cal_equipment_has_column($conn, $column)) {
            $colEsc = $conn->real_escape_string($column);
            if (@$conn->query("ALTER TABLE equipment ADD COLUMN `".$colEsc."` ".$definition)) {
                $GLOBALS['cal_equipment_column_cache'][$column] = true;
            }
        }
    }
    $ensured = true;
}

function cal_json_response($payload) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload);
    exit;
}

function cal_input_array() {
    global $input;
    if (is_array($input)) {
        return $input;
    }
    $raw = file_get_contents('php://input');
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return array();
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
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
// if ($_GET["type"] == "getEquipments") {
//     $output = Array();
//     $sql = "SELECT * FROM equipment WHERE user_no='".$_GET["user_no"]."' AND calibration='Yes' AND department LIKE '%".$_GET["department_name"]."%' AND equipment_type LIKE '%".$_GET["equipment_type"]."%'";
//     $result = $conn->query($sql);
//     if ($result->num_rows > 0) {
//         while ($row = $result->fetch_assoc()) {
//             $current = date('Y-m-d');
//             $begin = new DateTime($row["entry_date"]);
//             $end = new DateTime(date('Y-12-31'));
            
//             if ($row["calibration_frequency"] == "Daily") {
//                 $interval = DateInterval::createFromDateString('1 day');
//             } else if ($row["calibration_frequency"] == "Weekly") {
//                 $interval = DateInterval::createFromDateString('7 day');
//             } else if ($row["calibration_frequency"] == "Monthly") {
//                 $interval = DateInterval::createFromDateString('30 day');
//             } else if ($row["calibration_frequency"] == "Quaterly") {
//                 $interval = DateInterval::createFromDateString('120 day');
//             } else if ($row["calibration_frequency"] == "Yearly") {
//                 $interval = DateInterval::createFromDateString('365 day');
//             }
//             $period = new DatePeriod($begin, $interval, $end);
            
//             $days_list = array();
//             foreach ($period as $dt) {
                
                
//                 $firstDate = $dt->format("Y-m-d");
                
//                 if ($dt->format("Y") == date("Y", $timestamp)) {
//                     $temp = array();
//                     $temp["day"] = $firstDate;
//                     $temp["entry_by"] = "";
//                     $temp["entry_time"] = "";
//                     $temp["status"] = "";
//                     $days_list[] = $temp;
//                 }
//             }
            
//             $row["calender"] = $days_list;
//             $output[] = $row;
//         }
//     }
//     echo json_encode($output);
// } 
if ($_GET["type"] == "getEquipments") {
    $output = Array();
    $sql = "SELECT * FROM equipment WHERE plant_id='".$_GET["plant_id"]."' AND status = 'Active' AND calibration_required='Applicable' ORDER BY equipment_code";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
                $row['calibration_frequency_inhouse'] = json_decode($row['calibration_frequency_inhouse']);
                $row['calibration_frequency_external'] = json_decode($row['calibration_frequency_external']);
            if (!is_array($row['calibration_frequency_inhouse'])) {
                $row['calibration_frequency_inhouse'] = array();
            }
            if (!is_array($row['calibration_frequency_external'])) {
                $row['calibration_frequency_external'] = array();
            }
            $row['calibration_type'] = cal_resolve_type($row);
            $row['inhouse_summary'] = cal_schedule_summary($conn, $row['id'], 'Inhouse');
            $row['external_summary'] = cal_schedule_summary($conn, $row['id'], 'External');
            $row['has_inhouse_frequency'] = cal_has_frequency($row['calibration_frequency_inhouse']);
            $row['has_external_frequency'] = cal_has_frequency($row['calibration_frequency_external']);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
  else if($_GET["type"] == "get_monthly_schedule_external") {
        $output = Array();
          $sql = "SELECT a.*,b.equipment_code ,b.equipment_name ,b.tag_no,b.description,b.department,b.location,b.status,
                         DATEDIFF(a.due_date, CURDATE()) AS remaining_days

         FROM equipment_calibration a JOIN equipment b on a.equipment_id= b.id WHERE month(due_date)='".$_GET["month"]."' and year(due_date)='".$_GET["year"]."'
         and a.due_type = 'External' AND  b.external_department = '".$_GET["external_department"]."'  order by due_date"; 
        $result = $conn->query($sql);
    
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
         else if($_GET["type"]=="update_calibration_frequency") {
        cal_ensure_calibration_columns($conn);
        $payload = cal_input_array();
        if (empty($payload["id"])) {
            cal_json_response(array("status" => "Invalid request: equipment id missing."));
        }
        $id = $conn->real_escape_string($payload["id"]);
        $sql = "SELECT id FROM equipment_calibration WHERE equipment_id='".$id."' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            cal_json_response(array("status" => "Frequency Already Set and Schedule prepared for this Equipment."));
        }

        $freqIn = $conn->real_escape_string(json_encode(isset($payload["calibration_frequency_inhouse"]) ? $payload["calibration_frequency_inhouse"] : array()));
        $freqEx = $conn->real_escape_string(json_encode(isset($payload["calibration_frequency_external"]) ? $payload["calibration_frequency_external"] : array()));
        $inDept = $conn->real_escape_string(isset($payload["inhouse_department"]) ? $payload["inhouse_department"] : '');
        $exDept = $conn->real_escape_string(isset($payload["external_department"]) ? $payload["external_department"] : '');

        $sets = array();
        if (cal_equipment_has_column($conn, 'calibration_frequency_external')) {
            $sets[] = "calibration_frequency_external='".$freqEx."'";
        }
        if (cal_equipment_has_column($conn, 'calibration_frequency_inhouse')) {
            $sets[] = "calibration_frequency_inhouse='".$freqIn."'";
        }
        if (cal_equipment_has_column($conn, 'inhouse_department')) {
            $sets[] = "inhouse_department='".$inDept."'";
        }
        if (cal_equipment_has_column($conn, 'external_department')) {
            $sets[] = "external_department='".$exDept."'";
        }
        if (!empty($payload["calibration_type"]) && cal_equipment_has_column($conn, 'calibration_type')) {
            $sets[] = "calibration_type='".$conn->real_escape_string($payload["calibration_type"])."'";
        }
        if (count($sets) === 0) {
            cal_json_response(array("status" => "Equipment table is missing calibration frequency columns."));
        }
        $sql = "UPDATE equipment SET ".implode(',', $sets)." WHERE id='".$id."'";
        if ($conn->query($sql)) {
            cal_json_response(array("status" => "success"));
        }
        cal_json_response(array("status" => $conn->error ? $conn->error : "Update failed."));
    }

    
    
    else if($_GET["type"] == "get_monthly_schedule") {
        $output = Array();
        $plantId = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));
        $plantFilter = $plantId !== '' ? " AND b.plant_id='".$plantId."'" : "";
        $dueType = $conn->real_escape_string((string)($_GET["due_type"] ?? 'Inhouse'));
        $deptFilter = '';
        if ($dueType === 'Inhouse' && !empty($_GET["inhouse_department"])) {
            $deptEsc = $conn->real_escape_string(trim((string)$_GET["inhouse_department"]));
            if ($deptEsc !== '') {
                $deptFilter = " AND b.inhouse_department='".$deptEsc."'";
            }
        } elseif ($dueType === 'External' && !empty($_GET["external_department"])) {
            $deptEsc = $conn->real_escape_string(trim((string)$_GET["external_department"]));
            if ($deptEsc !== '') {
                $deptFilter = " AND b.external_department='".$deptEsc."'";
            }
        }
        $statusFilter = '';
        if (!empty($_GET["for_engineering"])) {
            $statusFilter = " AND b.inhouse_department='Engineering'"
                ." AND (LOWER(TRIM(COALESCE(a.cali_status,''))) = 'pending engineering'"
                ." OR LOWER(TRIM(COALESCE(a.cali_status,''))) IN ('', 'pending'))";
        } elseif (!empty($_GET["pending_only"])) {
            $statusFilter = " AND LOWER(TRIM(COALESCE(a.cali_status,''))) IN ('', 'pending')";
        } elseif (!empty($_GET["cali_status"])) {
            $statusEsc = $conn->real_escape_string(trim((string)$_GET["cali_status"]));
            $statusFilter = " AND LOWER(TRIM(COALESCE(a.cali_status,''))) = '".strtolower($statusEsc)."'";
        }
          $sql = "SELECT a.*,b.equipment_code ,b.equipment_name ,b.tag_no,b.description,b.department,b.location,b.status,
                         b.inhouse_department,
                         DATEDIFF(a.due_date, CURDATE()) AS remaining_days

         FROM equipment_calibration a JOIN equipment b on a.equipment_id= b.id WHERE month(due_date)='".$_GET["month"]."' and year(due_date)='".$_GET["year"]."'
         and a.due_type = '".$dueType."'".$plantFilter.$deptFilter.$statusFilter."  order by due_date"; 
        $result = $conn->query($sql);
    
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "get_equipment_calibration_log") {
        $output = Array();
        $performDept = trim((string)($_GET["perform_department"] ?? ''));
        $performDeptEsc = $conn->real_escape_string($performDept);
        $fromDate = trim((string)($_GET["from_date"] ?? ''));
        $toDate = trim((string)($_GET["to_date"] ?? ''));
        $plantId = trim((string)($_GET["plant_id"] ?? ''));
        if ($plantId === '') {
            $plantId = '1126';
        }
        $plantEsc = $conn->real_escape_string($plantId);
        $plantFilter = " AND (IFNULL(b.plant_id,'')='' OR b.plant_id='".$plantEsc."')";
        $deptFilter = '';
        if ($performDeptEsc !== '') {
            $deptFilter = " AND (
                b.inhouse_department='".$performDeptEsc."'
                OR b.external_department='".$performDeptEsc."'
                OR (IFNULL(b.inhouse_department,'')='' AND IFNULL(b.external_department,'')='' AND b.department='".$performDeptEsc."')
            )";
        }
        $dateFilter = '';
        if ($fromDate !== '' && $toDate !== '') {
            $fromEsc = $conn->real_escape_string($fromDate);
            $toEsc = $conn->real_escape_string($toDate);
            $dateFilter = " AND DATE(COALESCE(NULLIF(a.calibration_date,''), NULLIF(a.due_date,''))) BETWEEN '".$fromEsc."' AND '".$toEsc."'";
        }
        // Show any performed calibration (date saved), including Pending Dept Head / Pending Engineering / Done.
        $statusFilter = " AND NULLIF(a.calibration_date,'') IS NOT NULL
            AND a.calibration_date NOT IN ('', '0000-00-00', '0000-00-00 00:00:00')
            AND LOWER(TRIM(COALESCE(a.cali_status,''))) NOT IN ('pending', '')";
        $sql = "SELECT a.*, b.equipment_code, b.equipment_name, b.department, b.location, b.inhouse_department, b.external_department
            FROM equipment_calibration a
            JOIN equipment b ON a.equipment_id = b.id
            WHERE 1=1".$statusFilter.$plantFilter.$deptFilter.$dateFilter."
            ORDER BY a.calibration_date DESC, a.id DESC
            LIMIT 500";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['entry_by'] = cal_emp_name($conn, $row['entry_by'] ?? '');
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 else if($_GET["type"] == "get_dept_head_pending_calibrations") {
        cal_ensure_calibration_schedule_columns($conn);
        $output = Array();
        $dept = $conn->real_escape_string(trim((string)($_GET["department"] ?? '')));
        $plantId = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));
        $plantFilter = $plantId !== '' ? " AND b.plant_id='".$plantId."'" : "";
        $deptFilter = $dept !== '' ? " AND b.inhouse_department='".$dept."'" : "";
        $sql = "SELECT a.*, b.equipment_code, b.equipment_name, b.tag_no, b.description, b.department, b.location,
                       b.inhouse_department, DATEDIFF(a.due_date, CURDATE()) AS remaining_days
                FROM equipment_calibration a
                JOIN equipment b ON a.equipment_id = b.id
                WHERE a.due_type = 'Inhouse'
                  AND LOWER(TRIM(COALESCE(a.cali_status,''))) = 'pending dept head'".$plantFilter.$deptFilter."
                ORDER BY a.due_date ASC, a.id DESC
                LIMIT 500";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['performed_by'] = cal_emp_name($conn, $row['entry_by'] ?? '');
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 else if($_GET["type"] == "approveDeptHeadCalibration") {
        cal_ensure_calibration_schedule_columns($conn);
        $id = $conn->real_escape_string($_GET["id"] ?? '');
        $dept = $conn->real_escape_string(trim((string)($_GET["department"] ?? '')));
        if ($id === '') {
            echo json_encode(array("status" => "Invalid calibration record."));
            exit;
        }
        $deptFilter = $dept !== '' ? " AND b.inhouse_department='".$dept."'" : "";
        $check = $conn->query("SELECT a.id, b.inhouse_department FROM equipment_calibration a JOIN equipment b ON a.equipment_id=b.id
            WHERE a.id='".$id."' AND LOWER(TRIM(COALESCE(a.cali_status,'')))='pending dept head'".$deptFilter." LIMIT 1");
        if (!$check || $check->num_rows === 0) {
            echo json_encode(array("status" => "Calibration is not pending dept head approval."));
            exit;
        }
        $checkRow = $check->fetch_assoc();
        $performDept = trim((string)($checkRow["inhouse_department"] ?? ''));
        $newStatus = (strcasecmp($performDept, 'Engineering') === 0) ? 'Pending Engineering' : 'Done';
        $empEsc = $conn->real_escape_string($_GET["emp_id"] ?? '');
        $sets = array(
            "cali_status='".$newStatus."'",
            "entry_by='".$empEsc."'",
            "entry_date='".$entry_date."'"
        );
        $sql = "UPDATE equipment_calibration SET ".implode(', ', $sets)." WHERE id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success", "cali_status" => $newStatus));
        } else {
            echo json_encode(array("status" => $conn->error ? $conn->error : "Update failed."));
        }
    }
 else if($_GET["type"] == "saveInhouseCalibration") {
        cal_ensure_calibration_schedule_columns($conn);
        $id = $conn->real_escape_string($_GET["id"] ?? '');
        $calDate = trim((string)($_POST["calibration_date"] ?? ''));
        $remark = trim((string)($_POST["remark"] ?? ''));
        $workflow = strtolower(trim((string)($_GET["workflow"] ?? $_POST["workflow"] ?? 'engineering')));
        if ($id === '') {
            echo json_encode(array("status" => "Invalid calibration record."));
            exit;
        }
        if ($calDate === '') {
            echo json_encode(array("status" => "Calibration date is required."));
            exit;
        }
        $calDateEsc = $conn->real_escape_string($calDate);
        $remarkEsc = $conn->real_escape_string($remark);
        $empEsc = $conn->real_escape_string($_GET["emp_id"] ?? '');
        $newStatus = ($workflow === 'dept_perform') ? 'Pending Dept Head' : 'Done';
        $sets = array(
            "calibration_date='".$calDateEsc."'",
            "cali_status='".$newStatus."'",
            "entry_by='".$empEsc."'",
            "entry_date='".$entry_date."'"
        );
        if (cal_calibration_has_column($conn, 'remark')) {
            $sets[] = "remark='".$remarkEsc."'";
        }
        $sql = "UPDATE equipment_calibration SET ".implode(', ', $sets)." WHERE id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success", "cali_status" => $newStatus));
        } else {
            echo json_encode(array("status" => $conn->error ? $conn->error : "Update failed."));
        }
    }
 else if($_GET["type"] == "saveExternalCalibration") {
        
        $target_dir = "../../../upload/calibration/";
           
        $plant_id = $_GET["plant_id"];
        $last_id = $_GET["id"];
        
           
       if(isset($_FILES["structure_file"]["name"])) {
        	$target_file = $target_dir.$plant_id.$last_id."_".basename($_FILES["structure_file"]["name"]);
        	$structure_file = $plant_id.$last_id."_".basename($_FILES["structure_file"]["name"]);
    	    move_uploaded_file($_FILES["structure_file"]["tmp_name"], $target_file);
    	     
       }
        
        
       $sql = "UPDATE equipment_calibration SET calibration_date ='".$_POST["calibration_date"]."',cali_status = 'Done', agency_name = '".$_POST["agency_name"]."' ,report_file ='$structure_file'  ,
      entry_by ='".$_GET["emp_id"]."'  , entry_date ='$entry_date'  where id = '".$_GET["id"]."'"; 
        
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if($_GET["type"]=="update_equipment_Calibration_schedule") {
        cal_ensure_calibration_columns($conn);
        $payload = cal_input_array();
        if (empty($payload["id"])) {
            cal_json_response(array("status" => "Invalid request: equipment id missing."));
        }
        $id = $conn->real_escape_string($payload["id"]);
        $scheduleFor = isset($payload["schedule_for"]) ? $payload["schedule_for"] : 'Both';
        $dueTypes = array();
        if ($scheduleFor === 'Inhouse' || $scheduleFor === 'Both') {
            $dueTypes[] = 'Inhouse';
        }
        if ($scheduleFor === 'External' || $scheduleFor === 'Both') {
            $dueTypes[] = 'External';
        }
        foreach ($dueTypes as $dueType) {
            $check = $conn->query("SELECT id FROM equipment_calibration WHERE equipment_id='".$id."' AND due_type='".$dueType."' LIMIT 1");
            if ($check && $check->num_rows > 0) {
                cal_json_response(array("status" => $dueType." schedule already prepared for this equipment."));
            }
        }

        $updates = array();
        if ($scheduleFor === 'Inhouse' || $scheduleFor === 'Both') {
            $updates[] = "calibration_frequency_inhouse='".$conn->real_escape_string(json_encode(isset($payload["calibration_frequency_inhouse"]) ? $payload["calibration_frequency_inhouse"] : array()))."'";
        }
        if ($scheduleFor === 'External' || $scheduleFor === 'Both') {
            $updates[] = "calibration_frequency_external='".$conn->real_escape_string(json_encode(isset($payload["calibration_frequency_external"]) ? $payload["calibration_frequency_external"] : array()))."'";
        }
        $sql = "UPDATE equipment SET ".implode(',', $updates)." WHERE id='".$id."'";

        if ($conn->query($sql)) {
            if ($scheduleFor === 'Inhouse' || $scheduleFor === 'Both') {
                cal_insert_schedule($conn, $payload["id"], 'Inhouse', isset($payload["calibration_frequency_inhouse"]) ? $payload["calibration_frequency_inhouse"] : array());
            }
            if ($scheduleFor === 'External' || $scheduleFor === 'Both') {
                cal_insert_schedule($conn, $payload["id"], 'External', isset($payload["calibration_frequency_external"]) ? $payload["calibration_frequency_external"] : array());
            }
            cal_json_response(array("status" => "success"));
        }
        cal_json_response(array("status" => $conn->error ? $conn->error : "Update failed."));
    }
    else if($_GET["type"]=="update_calibration_type") {
        cal_ensure_calibration_columns($conn);
        $payload = cal_input_array();
        if (empty($payload["id"])) {
            cal_json_response(array("status" => "Invalid request: equipment id missing."));
        }
        $id = $conn->real_escape_string($payload["id"]);
        $calType = isset($payload["calibration_type"]) ? trim($payload["calibration_type"]) : '';
        $inhouseDept = isset($payload["inhouse_department"]) ? trim($payload["inhouse_department"]) : '';
        $externalDept = isset($payload["external_department"]) ? trim($payload["external_department"]) : '';
        if ($inhouseDept === '' && isset($payload["perform_department"])) {
            $inhouseDept = trim($payload["perform_department"]);
        }
        if ($externalDept === '' && $inhouseDept !== '') {
            $externalDept = $inhouseDept;
        }
        if ($inhouseDept === '' || $externalDept === '') {
            $eqRes = $conn->query("SELECT department, inhouse_department, external_department FROM equipment WHERE id='".$id."' LIMIT 1");
            if ($eqRes && $eqRes->num_rows > 0) {
                $eqRow = $eqRes->fetch_assoc();
                if ($inhouseDept === '') {
                    $inhouseDept = trim((string)($eqRow["inhouse_department"] ?? $eqRow["department"] ?? ''));
                }
                if ($externalDept === '') {
                    $externalDept = trim((string)($eqRow["external_department"] ?? $eqRow["department"] ?? $inhouseDept));
                }
            }
        }
        $inhouseDeptEsc = $conn->real_escape_string($inhouseDept);
        $externalDeptEsc = $conn->real_escape_string($externalDept);
        $calTypeEsc = $conn->real_escape_string($calType);

        $sets = array();
        if ($calType !== '' && cal_equipment_has_column($conn, 'calibration_type')) {
            $sets[] = "calibration_type='".$calTypeEsc."'";
        }
        if ($inhouseDept !== '' && ($calType === 'Inhouse' || $calType === 'Both' || $calType === '')) {
            if (cal_equipment_has_column($conn, 'inhouse_department')) {
                $sets[] = "inhouse_department='".$inhouseDeptEsc."'";
            }
        }
        if ($externalDept !== '' && ($calType === 'External' || $calType === 'Both' || $calType === '')) {
            if (cal_equipment_has_column($conn, 'external_department')) {
                $sets[] = "external_department='".$externalDeptEsc."'";
            }
        }

        if ($calType !== '') {
            $eqFreqRes = $conn->query("SELECT calibration_frequency_inhouse, calibration_frequency_external FROM equipment WHERE id='".$id."' LIMIT 1");
            $freqIn = array();
            $freqEx = array();
            if ($eqFreqRes && $eqFreqRes->num_rows > 0) {
                $eqFreqRow = $eqFreqRes->fetch_assoc();
                $freqIn = json_decode($eqFreqRow['calibration_frequency_inhouse'], true);
                $freqEx = json_decode($eqFreqRow['calibration_frequency_external'], true);
                if (!is_array($freqIn)) {
                    $freqIn = array();
                }
                if (!is_array($freqEx)) {
                    $freqEx = array();
                }
            }
            if ($calType === 'Inhouse' || $calType === 'Both') {
                if (cal_equipment_has_column($conn, 'calibration_frequency_inhouse') && !cal_has_frequency($freqIn)) {
                    $freqIn = cal_with_type_meta($freqIn, $calType);
                    $sets[] = "calibration_frequency_inhouse='".$conn->real_escape_string(json_encode($freqIn))."'";
                }
            }
            if ($calType === 'External' || $calType === 'Both') {
                if (cal_equipment_has_column($conn, 'calibration_frequency_external') && !cal_has_frequency($freqEx)) {
                    $freqEx = cal_with_type_meta($freqEx, $calType);
                    $sets[] = "calibration_frequency_external='".$conn->real_escape_string(json_encode($freqEx))."'";
                }
            }
        }
        if (count($sets) === 0) {
            cal_json_response(array("status" => "success", "calibration_type" => $calType));
        }
        $sql = "UPDATE equipment SET ".implode(',', $sets)." WHERE id='".$id."'";
        if ($conn->query($sql)) {
            cal_json_response(array(
                "status" => "success",
                "calibration_type" => $calType,
                "inhouse_department" => $inhouseDept,
                "external_department" => $externalDept,
            ));
        }
        cal_json_response(array("status" => $conn->error ? $conn->error : "Update failed."));
    }
else if ($_GET["type"] == "getEquipmentypes") {
    $output = Array();
    $sql = "SELECT DISTINCT(equipment_type) as equipment_type FROM equipment_names";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>