<?php

 


header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
require '../../db.php';
require '../../token.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!is_array($input)) {  
    $input = array();
}
     
$output = array();
$token = isset($_GET['token']) ? $_GET['token'] : '';
$action = isset($_GET['type']) ? $_GET['type'] : '';
  
function bm_table_columns($conn, $table, $forceRefresh = false) {
    static $cache = array();
    if ($forceRefresh) {
        $cache = array();
    }
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    $cols = array();
    $tableEsc = $conn->real_escape_string($table);
    $res = @$conn->query("SHOW COLUMNS FROM `".$tableEsc."`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cols[$row['Field']] = true;
        }
    }
    $cache[$table] = $cols;
    return $cols;
}

function bm_run_query($conn, $sql) {
    try {
        return $conn->query($sql);
    } catch (Exception $e) {
        return false;
    }
}

function ensure_booth_monitoring_schema($conn) {
    bm_run_query($conn, "CREATE TABLE IF NOT EXISTS `sampling_booth_monitoring_sheet` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `plant_id` varchar(50) DEFAULT NULL,
        `sheet_no` varchar(50) DEFAULT NULL,
        `log_date` date DEFAULT NULL,
        `status` varchar(30) DEFAULT 'pending',
        `entry_by` varchar(50) DEFAULT NULL,
        `entry_date` datetime DEFAULT NULL,
        `reviewed_by` varchar(50) DEFAULT NULL,
        `reviewed_on` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

    bm_run_query($conn, "CREATE TABLE IF NOT EXISTS `sampling_booth_monitoring_entry` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sheet_id` int(11) NOT NULL,
        `entry_date` date DEFAULT NULL,
        `instrument_id` varchar(100) DEFAULT NULL,
        `equipment_name` varchar(255) DEFAULT NULL,
        `calibration_due` date DEFAULT NULL,
        `air_velocity` varchar(50) DEFAULT NULL,
        `magnehelic_reading_1` varchar(50) DEFAULT NULL,
        `magnehelic_reading_2` varchar(50) DEFAULT NULL,
        `magnehelic_reading_3` varchar(50) DEFAULT NULL,
        `performed_by` varchar(100) DEFAULT NULL,
        `reviewed_by` varchar(100) DEFAULT NULL,
        `row_order` int(11) DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

    $entryCols = bm_table_columns($conn, 'sampling_booth_monitoring_entry');
    if (!isset($entryCols['equipment_name'])) {
        bm_run_query($conn, "ALTER TABLE `sampling_booth_monitoring_entry` ADD `equipment_name` varchar(255) DEFAULT NULL");
    }
    bm_table_columns($conn, 'sampling_booth_monitoring_entry', true);
}

function bm_insert_log_entry($conn, $sheetId, $entry, $rowOrder) {
    ensure_booth_monitoring_schema($conn);

    $row = array(
        'sheet_id' => intval($sheetId),
        'entry_date' => $entry['entry_date'] ?? '',
        'instrument_id' => $entry['instrument_id'] ?? '',
        'equipment_name' => $entry['equipment_name'] ?? '',
        'calibration_due' => $entry['calibration_due'] ?? '',
        'air_velocity' => $entry['air_velocity'] ?? '',
        'magnehelic_reading_1' => $entry['magnehelic_reading_1'] ?? '',
        'magnehelic_reading_2' => $entry['magnehelic_reading_2'] ?? '',
        'magnehelic_reading_3' => $entry['magnehelic_reading_3'] ?? '',
        'performed_by' => $entry['performed_by'] ?? '',
        'reviewed_by' => $entry['reviewed_by'] ?? '',
        'row_order' => intval($rowOrder),
    );

    $tableCols = bm_table_columns($conn, 'sampling_booth_monitoring_entry');
    $fields = array();
    $values = array();

    foreach ($row as $field => $val) {
        if (!isset($tableCols[$field])) {
            continue;
        }
        $fields[] = "`".$field."`";
        if (in_array($field, array('sheet_id', 'row_order'), true)) {
            $values[] = "'".intval($val)."'";
        } else if (in_array($field, array('entry_date', 'calibration_due'), true) && $val === '') {
            $values[] = 'NULL';
        } else {
            $values[] = "'".$conn->real_escape_string((string)$val)."'";
        }
    }

    if (count($fields) === 0) {
        return array('ok' => false, 'error' => 'No matching columns in sampling_booth_monitoring_entry table');
    }

    $sql = "INSERT INTO `sampling_booth_monitoring_entry` (".implode(', ', $fields).") VALUES (".implode(', ', $values).")";
    try {
        $ok = $conn->query($sql);
        if ($ok) {
            return array('ok' => true);
        }
        return array('ok' => false, 'error' => $conn->error);
    } catch (Exception $e) {
        return array('ok' => false, 'error' => $e->getMessage());
    }
}

$sql = "SELECT * FROM token WHERE token='".$conn->real_escape_string($token)."'";
$result = $conn->query($sql);
$_GET['emp_id'] = '';
$_GET['department'] = '';
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $token, $row['key1'], $row['key2']);
        $string = explode('$', $string);
        $_GET['emp_id'] = $string[0];
        $_GET['department'] = $string[1];
        break;
    }

    ensure_booth_monitoring_schema($conn);

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$action.'", "actiontime": "'.$entry_date.'", "department": "'.$_GET['department'].'", "emp_id": "'.$_GET['emp_id'].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = @file_put_contents('../logs.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);

    $plantId = $conn->real_escape_string(isset($_GET['plant_id']) ? $_GET['plant_id'] : '');

    if ($action == 'getNextSheetNo') {
        $year = date('Y');
        $prefix = 'SBM-'.$year.'-';
        $sql = "SELECT sheet_no FROM sampling_booth_monitoring_sheet
                WHERE plant_id = '".$plantId."' AND sheet_no LIKE '".$prefix."%'
                ORDER BY id DESC LIMIT 1";
        $res = $conn->query($sql);
        $next = 1;
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $parts = explode('-', $row['sheet_no']);
            $last = intval(end($parts));
            $next = $last + 1;
        }
        echo json_encode(array('sheet_no' => $prefix.str_pad($next, 3, '0', STR_PAD_LEFT)));
    }
    else if ($action == 'getSamplingEquipments') {
        $sql = "SELECT e.id, e.plant_id, e.equipment_code, e.equipment_name, e.department, e.location, e.status,
                (SELECT ec.due_date FROM equipment_calibration ec
                 WHERE ec.equipment_id = e.id AND ec.due_date >= CURDATE()
                 ORDER BY ec.due_date ASC LIMIT 1) AS calibration_due
                FROM equipment e
                WHERE e.status = 'Active'
                AND e.department = 'Quality Control'
                AND e.equipment_type LIKE '%samp%'
                AND e.plant_id = '".$plantId."'
                ORDER BY e.equipment_name ASC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                if (empty($row['calibration_due'])) {
                    $eqId = intval($row['id']);
                    $dueRes = $conn->query("SELECT due_date FROM equipment_calibration
                                            WHERE equipment_id = '".$eqId."'
                                            ORDER BY due_date DESC LIMIT 1");
                    if ($dueRes && $dueRes->num_rows > 0) {
                        $dueRow = $dueRes->fetch_assoc();
                        $row['calibration_due'] = $dueRow['due_date'];
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($action == 'saveBoothMonitoringLog') {
        if (!$input) {
            echo json_encode(array('status' => 'invalid', 'message' => 'No data received'));
            exit;
        }

        ensure_booth_monitoring_schema($conn);

        $sheetNo = $conn->real_escape_string($input['sheet_no']);
        $logDate = $conn->real_escape_string($input['log_date']);
        $entryBy = $conn->real_escape_string($input['entry_by']);

        $sql = "INSERT INTO sampling_booth_monitoring_sheet
                (plant_id, sheet_no, log_date, status, entry_by, entry_date)
                VALUES ('".$plantId."', '".$sheetNo."', '".$logDate."', 'pending', '".$entryBy."', '".$entry_date."')";

        try {
            if (!$conn->query($sql)) {
                echo json_encode(array('status' => $conn->error));
                exit;
            }
        } catch (Exception $e) {
            echo json_encode(array('status' => $e->getMessage()));
            exit;
        }

        $sheetId = $conn->insert_id;
        $entries = isset($input['entries']) ? $input['entries'] : array();
        $rowOrder = 0;

        foreach ($entries as $entry) {
            if (empty($entry['instrument_id']) && empty($entry['air_velocity'])) {
                continue;
            }
            $rowOrder++;
            $insertResult = bm_insert_log_entry($conn, $sheetId, $entry, $rowOrder);
            if (!$insertResult['ok']) {
                $conn->query("DELETE FROM sampling_booth_monitoring_entry WHERE sheet_id = '".$sheetId."'");
                $conn->query("DELETE FROM sampling_booth_monitoring_sheet WHERE id = '".$sheetId."'");
                echo json_encode(array('status' => $insertResult['error']));
                exit;
            }
        }

        if ($rowOrder === 0) {
            $conn->query("DELETE FROM sampling_booth_monitoring_sheet WHERE id = '".$sheetId."'");
            echo json_encode(array('status' => 'invalid', 'message' => 'No log rows to save'));
            exit;
        }

        echo json_encode(array('status' => 'success', 'sheet_id' => $sheetId));
    }
    else if ($action == 'getBoothMonitoringLogs') {
        $fromDate = isset($_GET['from_date']) ? $conn->real_escape_string($_GET['from_date']) : '';
        $toDate = isset($_GET['to_date']) ? $conn->real_escape_string($_GET['to_date']) : '';
        $where = "s.plant_id = '".$plantId."'";
        if ($fromDate) {
            $where .= " AND s.log_date >= '".$fromDate."'";
        }
        if ($toDate) {
            $where .= " AND s.log_date <= '".$toDate."'";
        }

        $sql = "SELECT s.*,
                (SELECT COUNT(*) FROM sampling_booth_monitoring_entry e WHERE e.sheet_id = s.id) AS entry_count,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = s.entry_by LIMIT 1) AS entry_by_name
                FROM sampling_booth_monitoring_sheet s
                WHERE ".$where."
                ORDER BY s.log_date DESC, s.id DESC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($action == 'getBoothMonitoringLogById') {
        $sheetId = intval($_GET['id']);
        $sql = "SELECT s.*,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = s.entry_by LIMIT 1) AS entry_by_name,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = s.reviewed_by LIMIT 1) AS reviewed_by_name
                FROM sampling_booth_monitoring_sheet s
                WHERE s.id = '".$sheetId."' AND s.plant_id = '".$plantId."' LIMIT 1";
        $res = $conn->query($sql);
        $sheet = null;
        if ($res && $res->num_rows > 0) {
            $sheet = $res->fetch_assoc();
        }

        $entries = array();
        if ($sheet) {
            $sql2 = "SELECT e.*,
                     (SELECT CONCAT(em.firstname, ' ', em.lastname) FROM employee em WHERE em.emp_id = e.reviewed_by LIMIT 1) AS reviewed_by_name
                     FROM sampling_booth_monitoring_entry e
                     WHERE e.sheet_id = '".$sheetId."'
                     ORDER BY e.row_order ASC, e.id ASC";
            $res2 = $conn->query($sql2);
            if ($res2 && $res2->num_rows > 0) {
                while ($row = $res2->fetch_assoc()) {
                    if (empty($row['reviewed_by_name']) && !empty($row['reviewed_by'])) {
                        $row['reviewed_by_name'] = $row['reviewed_by'];
                    }
                    $entries[] = $row;
                }
            }
        }

        echo json_encode(array('sheet' => $sheet, 'entries' => $entries));
    }
    else if ($action == 'verifyBoothMonitoringLogEntry') {
        if (!$input || empty($input['sheet_id']) || empty($input['entry_id'])) {
            echo json_encode(array('status' => 'invalid', 'message' => 'Missing entry data'));
            exit;
        }
        $sheetId = intval($input['sheet_id']);
        $entryId = intval($input['entry_id']);
        $reviewedByEmp = $conn->real_escape_string($input['reviewed_by'] ?? $_GET['emp_id']);
        $reviewedByDisplay = trim($input['reviewed_by_name'] ?? '');
        if ($reviewedByDisplay === '') {
            $reviewedByDisplay = $reviewedByEmp;
            $nameRes = $conn->query("SELECT CONCAT(firstname, ' ', lastname) AS nm FROM employee WHERE emp_id = '".$reviewedByEmp."' LIMIT 1");
            if ($nameRes && $nameRes->num_rows > 0) {
                $nameRow = $nameRes->fetch_assoc();
                $nm = trim($nameRow['nm'] ?? '');
                if ($nm !== '') {
                    $reviewedByDisplay = $nm;
                }
            }
        }
        $reviewedByName = $conn->real_escape_string($reviewedByDisplay);

        $sqlEntry = "UPDATE sampling_booth_monitoring_entry
                     SET reviewed_by = '".$reviewedByName."'
                     WHERE id = '".$entryId."' AND sheet_id = '".$sheetId."'";
        if (!$conn->query($sqlEntry)) {
            echo json_encode(array('status' => $conn->error));
            exit;
        }

        $pendingRes = $conn->query("SELECT COUNT(*) AS cnt FROM sampling_booth_monitoring_entry
                                    WHERE sheet_id = '".$sheetId."'
                                    AND (reviewed_by IS NULL OR TRIM(reviewed_by) = '')");
        $pendingCount = 0;
        if ($pendingRes && $pendingRes->num_rows > 0) {
            $pendingCount = intval($pendingRes->fetch_assoc()['cnt']);
        }

        $sheetReviewed = false;
        if ($pendingCount === 0) {
            $conn->query("UPDATE sampling_booth_monitoring_sheet
                          SET status = 'reviewed', reviewed_by = '".$reviewedByEmp."', reviewed_on = '".$entry_date."'
                          WHERE id = '".$sheetId."' AND plant_id = '".$plantId."'");
            $sheetReviewed = true;
        }

        echo json_encode(array(
            'status' => 'success',
            'reviewed_by_name' => $reviewedByDisplay,
            'sheet_reviewed' => $sheetReviewed,
            'pending_count' => $pendingCount
        ));
    }
} else {
    echo json_encode(array('status' => 'invalid', 'message' => 'Invalid token'));
}
$conn->close();
?>
