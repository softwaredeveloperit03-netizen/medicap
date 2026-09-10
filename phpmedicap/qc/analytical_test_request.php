<?php
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
require '../db.php';
require '../token.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!is_array($input)) {
    $input = array();
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
$action = isset($_GET['type']) ? $_GET['type'] : '';

function atr_run_query($conn, $sql) {
    try {
        return $conn->query($sql);
    } catch (Exception $e) {
        return false;
    }
}

function atr_json_decode($str) {
    if ($str === null || $str === '') {
        return array();
    }
    $decoded = json_decode($str, true);
    return is_array($decoded) ? $decoded : array();
}

function atr_esc($conn, $val) {
    return $conn->real_escape_string((string)$val);
}

function atr_emp_name($conn, $empId) {
    if (!$empId) {
        return '';
    }
    $res = $conn->query("SELECT CONCAT(firstname, ' ', lastname) AS nm FROM employee WHERE emp_id = '".atr_esc($conn, $empId)."' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $nm = trim($row['nm'] ?? '');
        if ($nm !== '') {
            return $nm;
        }
    }
    return $empId;
}

function atr_production_categories() {
    return array('In-process Product', 'Bulk Product', 'Semi - Finished Product', 'Finished Product', 'Pilot Bio batch');
}

function atr_is_production_request($categories) {
    if (!is_array($categories)) {
        return false;
    }
    $prod = atr_production_categories();
    foreach ($categories as $cat) {
        if (in_array($cat, $prod, true)) {
            return true;
        }
    }
    return false;
}

function atr_next_status_after_submit($categories) {
    if (atr_is_production_request($categories)) {
        return 'pending_qa_verify';
    }
    return 'pending_lab_receive';
}

function ensure_atr_schema($conn) {
    atr_run_query($conn, "CREATE TABLE IF NOT EXISTS `analytical_test_request` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `plant_id` varchar(50) DEFAULT NULL,
        `request_no` varchar(50) DEFAULT NULL,
        `lab_sample_number` varchar(100) DEFAULT NULL,
        `product_description` text DEFAULT NULL,
        `product_code` varchar(100) DEFAULT NULL,
        `lot_number` varchar(100) DEFAULT NULL,
        `mfg_date` date DEFAULT NULL,
        `sample_type` varchar(255) DEFAULT NULL,
        `sampling_site` varchar(255) DEFAULT NULL,
        `num_of_samples` varchar(255) DEFAULT NULL,
        `sample_categories` text DEFAULT NULL,
        `sample_category_other` varchar(255) DEFAULT NULL,
        `tests_requested` text DEFAULT NULL,
        `processing_start_date` date DEFAULT NULL,
        `holding_time` varchar(100) DEFAULT NULL,
        `comments` text DEFAULT NULL,
        `requestor` varchar(100) DEFAULT NULL,
        `requestor_emp_id` varchar(50) DEFAULT NULL,
        `requestor_date` date DEFAULT NULL,
        `date_results_required` date DEFAULT NULL,
        `qa_verified_by` varchar(100) DEFAULT NULL,
        `qa_verified_by_emp` varchar(50) DEFAULT NULL,
        `qa_verified_date` date DEFAULT NULL,
        `production_verified_by` varchar(100) DEFAULT NULL,
        `production_verified_by_emp` varchar(50) DEFAULT NULL,
        `production_verified_date` date DEFAULT NULL,
        `lab_received_by` varchar(100) DEFAULT NULL,
        `lab_received_by_emp` varchar(50) DEFAULT NULL,
        `lab_received_date` date DEFAULT NULL,
        `analyst_tests` text DEFAULT NULL,
        `analyst_remark` text DEFAULT NULL,
        `analyst_by` varchar(100) DEFAULT NULL,
        `analyst_by_emp` varchar(50) DEFAULT NULL,
        `analyst_date` date DEFAULT NULL,
        `alternative_method` varchar(5) DEFAULT 'No',
        `alternative_justification` text DEFAULT NULL,
        `alt_method_qa_by` varchar(100) DEFAULT NULL,
        `alt_method_qa_by_emp` varchar(50) DEFAULT NULL,
        `alt_method_qa_date` date DEFAULT NULL,
        `binder_forward` text DEFAULT NULL,
        `binder_forward_other` varchar(255) DEFAULT NULL,
        `dept_forward` text DEFAULT NULL,
        `dept_forward_other` varchar(255) DEFAULT NULL,
        `completed_by` varchar(100) DEFAULT NULL,
        `completed_by_emp` varchar(50) DEFAULT NULL,
        `completed_date` date DEFAULT NULL,
        `lab_manager_by` varchar(100) DEFAULT NULL,
        `lab_manager_by_emp` varchar(50) DEFAULT NULL,
        `lab_manager_date` date DEFAULT NULL,
        `lab_manager_remark` text DEFAULT NULL,
        `qa_disposition_by` varchar(100) DEFAULT NULL,
        `qa_disposition_by_emp` varchar(50) DEFAULT NULL,
        `qa_disposition_date` date DEFAULT NULL,
        `qa_disposition_remark` text DEFAULT NULL,
        `status` varchar(50) DEFAULT 'draft',
        `entry_by` varchar(50) DEFAULT NULL,
        `entry_date` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

    atr_run_query($conn, "CREATE TABLE IF NOT EXISTS `analytical_test_request_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `request_id` int(11) NOT NULL,
        `action` varchar(100) DEFAULT NULL,
        `from_status` varchar(50) DEFAULT NULL,
        `to_status` varchar(50) DEFAULT NULL,
        `remark` text DEFAULT NULL,
        `action_by` varchar(50) DEFAULT NULL,
        `action_date` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");
}

function atr_log_action($conn, $requestId, $action, $fromStatus, $toStatus, $remark, $empId, $entryDate) {
    $sql = "INSERT INTO analytical_test_request_log
            (request_id, action, from_status, to_status, remark, action_by, action_date)
            VALUES (
                '".intval($requestId)."',
                '".atr_esc($conn, $action)."',
                '".atr_esc($conn, $fromStatus)."',
                '".atr_esc($conn, $toStatus)."',
                '".atr_esc($conn, $remark)."',
                '".atr_esc($conn, $empId)."',
                '".atr_esc($conn, $entryDate)."'
            )";
    atr_run_query($conn, $sql);
}

function atr_row_to_output($row) {
    if (!$row) {
        return null;
    }
    $row['sample_categories'] = atr_json_decode($row['sample_categories'] ?? '');
    $row['tests_requested'] = atr_json_decode($row['tests_requested'] ?? '');
    $row['analyst_tests'] = atr_json_decode($row['analyst_tests'] ?? '');
    $row['binder_forward'] = atr_json_decode($row['binder_forward'] ?? '');
    $row['dept_forward'] = atr_json_decode($row['dept_forward'] ?? '');
    $row['is_production'] = atr_is_production_request($row['sample_categories']);
    return $row;
}

function atr_fetch_request($conn, $id, $plantId) {
    $sql = "SELECT r.*,
            (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = r.entry_by LIMIT 1) AS entry_by_name
            FROM analytical_test_request r
            WHERE r.id = '".intval($id)."' AND r.plant_id = '".atr_esc($conn, $plantId)."' LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        return atr_row_to_output($res->fetch_assoc());
    }
    return null;
}

function atr_build_insert_update_fields($conn, $data, $isUpdate = false) {
    $jsonFields = array('sample_categories', 'tests_requested', 'analyst_tests', 'binder_forward', 'dept_forward');
    $dateFields = array('mfg_date', 'processing_start_date', 'requestor_date', 'date_results_required',
        'qa_verified_date', 'production_verified_date', 'lab_received_date', 'analyst_date',
        'alt_method_qa_date', 'completed_date', 'lab_manager_date', 'qa_disposition_date');
    $fields = array();

    $allowed = array(
        'request_no', 'lab_sample_number', 'product_description', 'product_code', 'lot_number', 'mfg_date',
        'sample_type', 'sampling_site', 'num_of_samples', 'sample_categories', 'sample_category_other',
        'tests_requested', 'processing_start_date', 'holding_time', 'comments', 'requestor', 'requestor_emp_id',
        'requestor_date', 'date_results_required', 'qa_verified_by', 'qa_verified_by_emp', 'qa_verified_date',
        'production_verified_by', 'production_verified_by_emp', 'production_verified_date',
        'lab_received_by', 'lab_received_by_emp', 'lab_received_date', 'analyst_tests', 'analyst_remark',
        'analyst_by', 'analyst_by_emp', 'analyst_date', 'alternative_method', 'alternative_justification',
        'alt_method_qa_by', 'alt_method_qa_by_emp', 'alt_method_qa_date', 'binder_forward', 'binder_forward_other',
        'dept_forward', 'dept_forward_other', 'completed_by', 'completed_by_emp', 'completed_date',
        'lab_manager_by', 'lab_manager_by_emp', 'lab_manager_date', 'lab_manager_remark',
        'qa_disposition_by', 'qa_disposition_by_emp', 'qa_disposition_date', 'qa_disposition_remark', 'status'
    );

    foreach ($allowed as $field) {
        if (!array_key_exists($field, $data)) {
            continue;
        }
        $val = $data[$field];
        if (in_array($field, $jsonFields, true)) {
            $val = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val;
        }
        if (in_array($field, $dateFields, true) && ($val === '' || $val === null)) {
            $fields[] = "`".$field."` = NULL";
        } else {
            $fields[] = "`".$field."` = '".atr_esc($conn, $val)."'";
        }
    }
    return $fields;
}

function atr_compute_holding_time($processingStart, $holdingTime) {
    if ($holdingTime !== '' && $holdingTime !== null) {
        return $holdingTime;
    }
    if (!$processingStart) {
        return '30 days';
    }
    return '30 days';
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

    ensure_atr_schema($conn);

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$action.'", "actiontime": "'.$entry_date.'", "department": "'.$_GET['department'].'", "emp_id": "'.$_GET['emp_id'].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    @file_put_contents('../logs.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);

    $plantId = atr_esc($conn, isset($_GET['plant_id']) ? $_GET['plant_id'] : '');
    $empId = atr_esc($conn, $_GET['emp_id']);

    if ($action == 'getNextRequestNo') {
        $year = date('Y');
        $prefix = 'ATR-'.$year.'-';
        $res = $conn->query("SELECT request_no FROM analytical_test_request
                             WHERE plant_id = '".$plantId."' AND request_no LIKE '".$prefix."%'
                             ORDER BY id DESC LIMIT 1");
        $next = 1;
        if ($res && $res->num_rows > 0) {
            $r = $res->fetch_assoc();
            $parts = explode('-', $r['request_no']);
            $next = intval(end($parts)) + 1;
        }
        echo json_encode(array('request_no' => $prefix.str_pad($next, 3, '0', STR_PAD_LEFT)));
    }
    else if ($action == 'getProducts') {
        $output = array();
        $sql = "SELECT product_code, product_name, generic_name, strength, dosage_form
                FROM product WHERE plant_id = '".$plantId."' AND product_code IS NOT NULL
                ORDER BY product_name ASC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($action == 'getMaterials') {
        $output = array();
        $sql = "SELECT material_code, material_name, material_type, grade
                FROM material WHERE plant_id = '".$plantId."' AND status NOT IN ('In-Active','Absolute')
                ORDER BY material_name ASC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($action == 'getNextLabSampleNumber') {
        $year = date('Y');
        $prefix = 'LS-'.$year.'-';
        $res = $conn->query("SELECT lab_sample_number FROM analytical_test_request
                             WHERE plant_id = '".$plantId."' AND lab_sample_number LIKE '".$prefix."%'
                             ORDER BY id DESC LIMIT 1");
        $next = 1;
        if ($res && $res->num_rows > 0) {
            $r = $res->fetch_assoc();
            $parts = explode('-', $r['lab_sample_number']);
            $next = intval(end($parts)) + 1;
        }
        echo json_encode(array('lab_sample_number' => $prefix.str_pad($next, 4, '0', STR_PAD_LEFT)));
    }
    else if ($action == 'saveRequest') {
        if (!$input) {
            echo json_encode(array('status' => 'invalid', 'message' => 'No data received'));
            exit;
        }
        $data = $input;
        $data['holding_time'] = atr_compute_holding_time($data['processing_start_date'] ?? '', $data['holding_time'] ?? '');
        $data['status'] = 'draft';
        $data['entry_by'] = $_GET['emp_id'];

        if (!empty($data['id'])) {
            $id = intval($data['id']);
            $fields = atr_build_insert_update_fields($conn, $data, true);
            if (count($fields) === 0) {
                echo json_encode(array('status' => 'invalid', 'message' => 'Nothing to update'));
                exit;
            }
            $sql = "UPDATE analytical_test_request SET ".implode(', ', $fields)." WHERE id = '".$id."' AND plant_id = '".$plantId."' AND status = 'draft'";
            if ($conn->query($sql)) {
                echo json_encode(array('status' => 'success', 'id' => $id));
            } else {
                echo json_encode(array('status' => $conn->error));
            }
        } else {
            $requestNo = atr_esc($conn, $data['request_no'] ?? '');
            unset($data['request_no'], $data['entry_by'], $data['entry_date']);
            $fields = atr_build_insert_update_fields($conn, $data);
            $fields[] = "`plant_id` = '".$plantId."'";
            $fields[] = "`request_no` = '".$requestNo."'";
            $fields[] = "`entry_by` = '".$empId."'";
            $fields[] = "`entry_date` = '".atr_esc($conn, $entry_date)."'";
            $sql = "INSERT INTO analytical_test_request SET ".implode(', ', $fields);
            if ($conn->query($sql)) {
                echo json_encode(array('status' => 'success', 'id' => $conn->insert_id));
            } else {
                echo json_encode(array('status' => $conn->error));
            }
        }
    }
    else if ($action == 'submitRequest') {
        if (!$input || empty($input['id'])) {
            echo json_encode(array('status' => 'invalid', 'message' => 'Request ID required'));
            exit;
        }
        $id = intval($input['id']);
        $existing = atr_fetch_request($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing) {
            echo json_encode(array('status' => 'invalid', 'message' => 'Request not found'));
            exit;
        }
        $data = $input;
        $data['holding_time'] = atr_compute_holding_time($data['processing_start_date'] ?? '', $data['holding_time'] ?? '');
        $categories = isset($data['sample_categories']) ? $data['sample_categories'] : $existing['sample_categories'];
        if (!is_array($categories)) {
            $categories = atr_json_decode($categories);
        }
        $nextStatus = atr_next_status_after_submit($categories);
        $data['status'] = $nextStatus;
        $fields = atr_build_insert_update_fields($conn, $data, true);
        $sql = "UPDATE analytical_test_request SET ".implode(', ', $fields)." WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            atr_log_action($conn, $id, 'submit', $existing['status'], $nextStatus, '', $_GET['emp_id'], $entry_date);
            echo json_encode(array('status' => 'success', 'next_status' => $nextStatus));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }
    else if ($action == 'getRequestsByStatus') {
        $status = atr_esc($conn, $_GET['status'] ?? '');
        $fromDate = atr_esc($conn, $_GET['from_date'] ?? '');
        $toDate = atr_esc($conn, $_GET['to_date'] ?? '');
        $where = "r.plant_id = '".$plantId."'";
        if ($status !== '') {
            $where .= " AND r.status = '".$status."'";
        }
        if ($fromDate !== '') {
            $where .= " AND r.requestor_date >= '".$fromDate."'";
        }
        if ($toDate !== '') {
            $where .= " AND r.requestor_date <= '".$toDate."'";
        }
        $sql = "SELECT r.id, r.request_no, r.lab_sample_number, r.product_description, r.product_code,
                r.lot_number, r.sample_type, r.sampling_site, r.status, r.requestor, r.requestor_date,
                r.date_results_required, r.entry_by, r.entry_date,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = r.entry_by LIMIT 1) AS entry_by_name
                FROM analytical_test_request r
                WHERE ".$where."
                ORDER BY r.id DESC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($action == 'getRequestById') {
        $id = intval($_GET['id'] ?? 0);
        $row = atr_fetch_request($conn, $id, $_GET['plant_id'] ?? '');
        if (!$row) {
            echo json_encode(array('status' => 'invalid', 'message' => 'Not found'));
            exit;
        }
        $logs = array();
        $logRes = $conn->query("SELECT l.*,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = l.action_by LIMIT 1) AS action_by_name
                FROM analytical_test_request_log l
                WHERE l.request_id = '".$id."'
                ORDER BY l.id ASC");
        if ($logRes && $logRes->num_rows > 0) {
            while ($lr = $logRes->fetch_assoc()) {
                $logs[] = $lr;
            }
        }
        echo json_encode(array('request' => $row, 'workflow_log' => $logs));
    }
    else if ($action == 'qaVerify') {
        if (!$input || empty($input['id'])) {
            echo json_encode(array('status' => 'invalid'));
            exit;
        }
        $id = intval($input['id']);
        $existing = atr_fetch_request($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_qa_verify') {
            echo json_encode(array('status' => 'invalid', 'message' => 'Not pending QA verification'));
            exit;
        }
        $name = atr_emp_name($conn, $_GET['emp_id']);
        $mfgDate = atr_esc($conn, $input['mfg_date'] ?? $existing['mfg_date'] ?? '');
        $remark = atr_esc($conn, $input['remark'] ?? '');
        $toStatus = 'pending_production_verify';
        $sql = "UPDATE analytical_test_request SET
                status = '".$toStatus."',
                mfg_date = ".($mfgDate ? "'".$mfgDate."'" : "NULL").",
                qa_verified_by = '".atr_esc($conn, $name)."',
                qa_verified_by_emp = '".$empId."',
                qa_verified_date = '".date('Y-m-d')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            atr_log_action($conn, $id, 'qa_verify', 'pending_qa_verify', $toStatus, $remark, $_GET['emp_id'], $entry_date);
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }
    else if ($action == 'productionVerify') {
        if (!$input || empty($input['id'])) {
            echo json_encode(array('status' => 'invalid'));
            exit;
        }
        $id = intval($input['id']);
        $existing = atr_fetch_request($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_production_verify') {
            echo json_encode(array('status' => 'invalid', 'message' => 'Not pending production verification'));
            exit;
        }
        $name = atr_emp_name($conn, $_GET['emp_id']);
        $remark = atr_esc($conn, $input['remark'] ?? '');
        $toStatus = 'pending_lab_receive';
        $sql = "UPDATE analytical_test_request SET
                status = '".$toStatus."',
                production_verified_by = '".atr_esc($conn, $name)."',
                production_verified_by_emp = '".$empId."',
                production_verified_date = '".date('Y-m-d')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            atr_log_action($conn, $id, 'production_verify', 'pending_production_verify', $toStatus, $remark, $_GET['emp_id'], $entry_date);
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }
    else if ($action == 'labReceive') {
        if (!$input || empty($input['id'])) {
            echo json_encode(array('status' => 'invalid'));
            exit;
        }
        $id = intval($input['id']);
        $existing = atr_fetch_request($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_lab_receive') {
            echo json_encode(array('status' => 'invalid', 'message' => 'Not pending lab receiving'));
            exit;
        }
        $name = atr_emp_name($conn, $_GET['emp_id']);
        $labSampleNo = atr_esc($conn, $input['lab_sample_number'] ?? '');
        if ($labSampleNo === '') {
            echo json_encode(array('status' => 'invalid', 'message' => 'Lab sample number required'));
            exit;
        }
        $toStatus = 'pending_analyst';
        $sql = "UPDATE analytical_test_request SET
                status = '".$toStatus."',
                lab_sample_number = '".$labSampleNo."',
                lab_received_by = '".atr_esc($conn, $name)."',
                lab_received_by_emp = '".$empId."',
                lab_received_date = '".date('Y-m-d')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            atr_log_action($conn, $id, 'lab_receive', 'pending_lab_receive', $toStatus, '', $_GET['emp_id'], $entry_date);
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }
    else if ($action == 'saveAnalystWork') {
        if (!$input || empty($input['id'])) {
            echo json_encode(array('status' => 'invalid'));
            exit;
        }
        $id = intval($input['id']);
        $existing = atr_fetch_request($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || !in_array($existing['status'], array('pending_analyst', 'pending_alt_method_qa'), true)) {
            echo json_encode(array('status' => 'invalid', 'message' => 'Not in analyst stage'));
            exit;
        }
        $data = $input;
        $fields = atr_build_insert_update_fields($conn, $data, true);
        $sql = "UPDATE analytical_test_request SET ".implode(', ', $fields)." WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }
    else if ($action == 'submitAnalyst') {
        if (!$input || empty($input['id'])) {
            echo json_encode(array('status' => 'invalid'));
            exit;
        }
        $id = intval($input['id']);
        $existing = atr_fetch_request($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_analyst') {
            echo json_encode(array('status' => 'invalid', 'message' => 'Not pending analyst'));
            exit;
        }
        $name = atr_emp_name($conn, $_GET['emp_id']);
        $altMethod = ($input['alternative_method'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
        $analystTests = isset($input['analyst_tests']) ? json_encode($input['analyst_tests'], JSON_UNESCAPED_UNICODE) : json_encode(array());
        $binderForward = isset($input['binder_forward']) ? json_encode($input['binder_forward'], JSON_UNESCAPED_UNICODE) : json_encode(array());
        $deptForward = isset($input['dept_forward']) ? json_encode($input['dept_forward'], JSON_UNESCAPED_UNICODE) : json_encode(array());
        $toStatus = ($altMethod === 'Yes') ? 'pending_alt_method_qa' : 'pending_lab_manager';
        $sql = "UPDATE analytical_test_request SET
                status = '".$toStatus."',
                analyst_tests = '".atr_esc($conn, $analystTests)."',
                analyst_remark = '".atr_esc($conn, $input['analyst_remark'] ?? '')."',
                analyst_by = '".atr_esc($conn, $name)."',
                analyst_by_emp = '".$empId."',
                analyst_date = '".date('Y-m-d')."',
                alternative_method = '".$altMethod."',
                alternative_justification = '".atr_esc($conn, $input['alternative_justification'] ?? '')."',
                binder_forward = '".atr_esc($conn, $binderForward)."',
                binder_forward_other = '".atr_esc($conn, $input['binder_forward_other'] ?? '')."',
                dept_forward = '".atr_esc($conn, $deptForward)."',
                dept_forward_other = '".atr_esc($conn, $input['dept_forward_other'] ?? '')."',
                completed_by = '".atr_esc($conn, $name)."',
                completed_by_emp = '".$empId."',
                completed_date = '".date('Y-m-d')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            atr_log_action($conn, $id, 'analyst_submit', 'pending_analyst', $toStatus, '', $_GET['emp_id'], $entry_date);
            echo json_encode(array('status' => 'success', 'next_status' => $toStatus));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }
    else if ($action == 'approveAltMethod') {
        if (!$input || empty($input['id'])) {
            echo json_encode(array('status' => 'invalid'));
            exit;
        }
        $id = intval($input['id']);
        $existing = atr_fetch_request($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_alt_method_qa') {
            echo json_encode(array('status' => 'invalid', 'message' => 'Not pending alternative method QA approval'));
            exit;
        }
        $name = atr_emp_name($conn, $_GET['emp_id']);
        $approved = ($input['approved'] ?? 'Yes') === 'Yes';
        $toStatus = $approved ? 'pending_lab_manager' : 'pending_analyst';
        $sql = "UPDATE analytical_test_request SET
                status = '".$toStatus."',
                alt_method_qa_by = '".atr_esc($conn, $name)."',
                alt_method_qa_by_emp = '".$empId."',
                alt_method_qa_date = '".date('Y-m-d')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            atr_log_action($conn, $id, 'alt_method_qa', 'pending_alt_method_qa', $toStatus, atr_esc($conn, $input['remark'] ?? ''), $_GET['emp_id'], $entry_date);
            echo json_encode(array('status' => 'success', 'next_status' => $toStatus));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }
    else if ($action == 'labManagerApprove') {
        if (!$input || empty($input['id'])) {
            echo json_encode(array('status' => 'invalid'));
            exit;
        }
        $id = intval($input['id']);
        $existing = atr_fetch_request($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_lab_manager') {
            echo json_encode(array('status' => 'invalid', 'message' => 'Not pending lab manager review'));
            exit;
        }
        $name = atr_emp_name($conn, $_GET['emp_id']);
        $toStatus = 'pending_qa_disposition';
        $sql = "UPDATE analytical_test_request SET
                status = '".$toStatus."',
                lab_manager_by = '".atr_esc($conn, $name)."',
                lab_manager_by_emp = '".$empId."',
                lab_manager_date = '".date('Y-m-d')."',
                lab_manager_remark = '".atr_esc($conn, $input['remark'] ?? '')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            atr_log_action($conn, $id, 'lab_manager_approve', 'pending_lab_manager', $toStatus, atr_esc($conn, $input['remark'] ?? ''), $_GET['emp_id'], $entry_date);
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }
    else if ($action == 'qaDisposition') {
        if (!$input || empty($input['id'])) {
            echo json_encode(array('status' => 'invalid'));
            exit;
        }
        $id = intval($input['id']);
        $existing = atr_fetch_request($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_qa_disposition') {
            echo json_encode(array('status' => 'invalid', 'message' => 'Not pending QA disposition'));
            exit;
        }
        $name = atr_emp_name($conn, $_GET['emp_id']);
        $toStatus = 'closed';
        $sql = "UPDATE analytical_test_request SET
                status = '".$toStatus."',
                qa_disposition_by = '".atr_esc($conn, $name)."',
                qa_disposition_by_emp = '".$empId."',
                qa_disposition_date = '".date('Y-m-d')."',
                qa_disposition_remark = '".atr_esc($conn, $input['remark'] ?? '')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            atr_log_action($conn, $id, 'qa_disposition', 'pending_qa_disposition', $toStatus, atr_esc($conn, $input['remark'] ?? ''), $_GET['emp_id'], $entry_date);
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }
} else {
    echo json_encode(array('status' => 'invalid', 'message' => 'Invalid token'));
}
$conn->close();
?>
