<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Content-Type: application/json; charset=UTF-8');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

date_default_timezone_set("Asia/Kolkata");
require '../db.php';
require '../token.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!is_array($input)) {
    $input = array();
}

function ipps_json_out($data) {
    echo json_encode($data);
    exit;
}

function ipps_esc($conn, $val) {
    return $conn->real_escape_string((string)$val);
}

function ipps_emp_name($conn, $empId) {
    if (!$empId) {
        return '';
    }
    $res = $conn->query("SELECT CONCAT(firstname, ' ', lastname) AS nm FROM employee WHERE emp_id = '".ipps_esc($conn, $empId)."' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $nm = trim($row['nm'] ?? '');
        if ($nm !== '') {
            return $nm;
        }
    }
    return $empId;
}

function ipps_json_decode($str) {
    if ($str === null || $str === '') {
        return array();
    }
    $decoded = json_decode($str, true);
    return is_array($decoded) ? $decoded : array();
}

function ensure_ipps_schema($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `qc_in_process_sampling` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `plant_id` varchar(50) DEFAULT NULL,
        `form_no` varchar(50) DEFAULT NULL,
        `sample_type` varchar(30) DEFAULT 'in_process',
        `product_name` varchar(255) DEFAULT NULL,
        `product_code` varchar(100) DEFAULT NULL,
        `lot_number` varchar(100) DEFAULT NULL,
        `amount_requested` varchar(50) DEFAULT NULL,
        `amount_provided` varchar(50) DEFAULT NULL,
        `reason` text DEFAULT NULL,
        `testing_required` varchar(5) DEFAULT 'No',
        `provide_samples_to` varchar(255) DEFAULT NULL,
        `requested_by` varchar(100) DEFAULT NULL,
        `requested_by_emp` varchar(50) DEFAULT NULL,
        `requested_date` date DEFAULT NULL,
        `sampled_by` varchar(100) DEFAULT NULL,
        `sampled_by_emp` varchar(50) DEFAULT NULL,
        `sampled_date` date DEFAULT NULL,
        `lab_number` varchar(50) DEFAULT NULL,
        `receive_remark` text DEFAULT NULL,
        `received_by` varchar(100) DEFAULT NULL,
        `received_by_emp` varchar(50) DEFAULT NULL,
        `received_date` date DEFAULT NULL,
        `test_spec_ref` varchar(255) DEFAULT NULL,
        `testing_data` text DEFAULT NULL,
        `testing_by` varchar(100) DEFAULT NULL,
        `testing_by_emp` varchar(50) DEFAULT NULL,
        `testing_date` date DEFAULT NULL,
        `reviewed_by` varchar(100) DEFAULT NULL,
        `reviewed_by_emp` varchar(50) DEFAULT NULL,
        `reviewed_date` date DEFAULT NULL,
        `review_remark` text DEFAULT NULL,
        `results_entered_by` varchar(100) DEFAULT NULL,
        `results_entered_by_emp` varchar(50) DEFAULT NULL,
        `results_entered_date` date DEFAULT NULL,
        `results_data` text DEFAULT NULL,
        `approved_by` varchar(100) DEFAULT NULL,
        `approved_by_emp` varchar(50) DEFAULT NULL,
        `approved_date` date DEFAULT NULL,
        `approval_remark` text DEFAULT NULL,
        `status` varchar(50) DEFAULT 'draft',
        `entry_by` varchar(50) DEFAULT NULL,
        `entry_date` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS `qc_in_process_sampling_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `record_id` int(11) NOT NULL,
        `action` varchar(100) DEFAULT NULL,
        `from_status` varchar(50) DEFAULT NULL,
        `to_status` varchar(50) DEFAULT NULL,
        `remark` text DEFAULT NULL,
        `action_by` varchar(50) DEFAULT NULL,
        `action_date` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");
}

function ipps_log($conn, $recordId, $action, $from, $to, $remark, $empId, $entryDate) {
    $conn->query("INSERT INTO qc_in_process_sampling_log
        (record_id, action, from_status, to_status, remark, action_by, action_date)
        VALUES (
            '".intval($recordId)."',
            '".ipps_esc($conn, $action)."',
            '".ipps_esc($conn, $from)."',
            '".ipps_esc($conn, $to)."',
            '".ipps_esc($conn, $remark)."',
            '".ipps_esc($conn, $empId)."',
            '".ipps_esc($conn, $entryDate)."'
        )");
}

function ipps_next_form_no($conn, $plantId) {
    $year = date('Y');
    $prefix = 'IPPS-'.$year.'-';
    $prefixEsc = ipps_esc($conn, $prefix);
    $plantEsc = ipps_esc($conn, $plantId);
    $res = $conn->query("SELECT form_no FROM qc_in_process_sampling
        WHERE plant_id = '".$plantEsc."' AND form_no LIKE '".$prefixEsc."%'
        ORDER BY id DESC LIMIT 1");
    $next = 1;
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (preg_match('/-(\d+)$/', $row['form_no'] ?? '', $m)) {
            $next = intval($m[1]) + 1;
        }
    }
    return $prefix.str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

function ipps_next_lab_number($conn, $plantId) {
    $yy = date('y');
    $prefix = 'QC-'.$yy.'-';
    $prefixEsc = ipps_esc($conn, $prefix);
    $plantEsc = ipps_esc($conn, $plantId);
    $maxSeq = 0;
    $tables = array('qc_in_process_sampling', 'processing_laboratory_sample', 'analytical_test_request');
    foreach ($tables as $tbl) {
        $col = ($tbl === 'analytical_test_request') ? 'lab_sample_number' : 'lab_number';
        $res = @$conn->query("SELECT `".$col."` AS lab_no FROM `".$tbl."`
            WHERE plant_id = '".$plantEsc."' AND `".$col."` LIKE '".$prefixEsc."%'
            ORDER BY id DESC LIMIT 100");
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $num = $row['lab_no'] ?? '';
                if (preg_match('/'.preg_quote($prefix, '/').'(\d{4})/', $num, $m)) {
                    $seq = intval($m[1]);
                    if ($seq > $maxSeq) {
                        $maxSeq = $seq;
                    }
                }
            }
        }
    }
    return $prefix.str_pad((string)($maxSeq + 1), 4, '0', STR_PAD_LEFT).'.00';
}

function ipps_row_output($row) {
    if (!$row) {
        return null;
    }
    $row['testing_data'] = ipps_json_decode($row['testing_data'] ?? '');
    $row['results_data'] = ipps_json_decode($row['results_data'] ?? '');
    return $row;
}

function ipps_fetch($conn, $id, $plantId) {
    $id = intval($id);
    $plantEsc = ipps_esc($conn, $plantId);
    $res = $conn->query("SELECT r.*,
            (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = r.entry_by LIMIT 1) AS entry_by_name
            FROM qc_in_process_sampling r
            WHERE r.id = '".$id."' AND r.plant_id = '".$plantEsc."' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        return ipps_row_output($res->fetch_assoc());
    }
    return null;
}

function ipps_build_data($input) {
    return array(
        'sample_type' => in_array($input['sample_type'] ?? '', array('in_process', 'finished', 'stability'), true)
            ? $input['sample_type'] : 'in_process',
        'product_name' => $input['product_name'] ?? '',
        'product_code' => $input['product_code'] ?? '',
        'lot_number' => $input['lot_number'] ?? '',
        'amount_requested' => $input['amount_requested'] ?? '',
        'amount_provided' => $input['amount_provided'] ?? '',
        'reason' => $input['reason'] ?? '',
        'testing_required' => ($input['testing_required'] ?? 'No') === 'Yes' ? 'Yes' : 'No',
        'provide_samples_to' => $input['provide_samples_to'] ?? 'QC Laboratory',
        'requested_by' => $input['requested_by'] ?? '',
        'requested_date' => $input['requested_date'] ?? '',
        'sampled_by' => $input['sampled_by'] ?? '',
        'sampled_date' => $input['sampled_date'] ?? '',
    );
}

function ipps_initial_status($sampleType) {
    if ($sampleType === 'stability') {
        return 'pending_qc_receive';
    }
    return 'pending_qc_receive';
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
$action = isset($_GET['type']) ? $_GET['type'] : '';

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

    ensure_ipps_schema($conn);
    $plantId = ipps_esc($conn, $_GET['plant_id'] ?? '');
    $empId = ipps_esc($conn, $_GET['emp_id']);
    $entry_date = date('Y-m-d H:i:s');

    if ($action == 'getNextFormNo') {
        ipps_json_out(array('form_no' => ipps_next_form_no($conn, $_GET['plant_id'] ?? '')));
    }
    else if ($action == 'getNextLabNumber') {
        ipps_json_out(array('lab_number' => ipps_next_lab_number($conn, $_GET['plant_id'] ?? '')));
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
        ipps_json_out($output);
    }
    else if ($action == 'saveRecord' || $action == 'submitRecord') {
        if (count($input) === 0) {
            ipps_json_out(array('status' => 'invalid', 'message' => 'Missing request body'));
        }
        $id = intval($input['id'] ?? 0);
        $data = ipps_build_data($input);
        $name = ipps_emp_name($conn, $_GET['emp_id']);

        if ($id > 0) {
            $existing = ipps_fetch($conn, $id, $_GET['plant_id'] ?? '');
            if (!$existing || $existing['status'] !== 'draft') {
                ipps_json_out(array('status' => 'invalid', 'message' => 'Only draft records can be updated'));
            }
            $sets = array();
            foreach ($data as $k => $v) {
                if (in_array($k, array('requested_date', 'sampled_date'), true)) {
                    $sets[] = "`".$k."` = ".($v ? "'".ipps_esc($conn, $v)."'" : "NULL");
                } else {
                    $sets[] = "`".$k."` = '".ipps_esc($conn, $v)."'";
                }
            }
            if ($action == 'submitRecord') {
                if (trim($data['requested_by']) === '') {
                    $data['requested_by'] = $name;
                }
                if (trim($data['sampled_by']) === '') {
                    $data['sampled_by'] = $name;
                }
                if (empty($data['requested_date'])) {
                    $data['requested_date'] = date('Y-m-d');
                }
                if (empty($data['sampled_date'])) {
                    $data['sampled_date'] = date('Y-m-d');
                }
                $sets[] = "`status` = 'pending_qc_receive'";
                $sets[] = "`requested_by` = '".ipps_esc($conn, $data['requested_by'])."'";
                $sets[] = "`requested_by_emp` = '".$empId."'";
                $sets[] = "`requested_date` = '".ipps_esc($conn, $data['requested_date'])."'";
                $sets[] = "`sampled_by` = '".ipps_esc($conn, $data['sampled_by'])."'";
                $sets[] = "`sampled_by_emp` = '".$empId."'";
                $sets[] = "`sampled_date` = '".ipps_esc($conn, $data['sampled_date'])."'";
            }
            $sql = "UPDATE qc_in_process_sampling SET ".implode(', ', $sets)." WHERE id = '".$id."' AND plant_id = '".$plantId."'";
            if (!$conn->query($sql)) {
                ipps_json_out(array('status' => 'error', 'message' => $conn->error));
            }
            if ($action == 'submitRecord') {
                ipps_log($conn, $id, 'submit', 'draft', 'pending_qc_receive', '', $empId, $entry_date);
            }
            ipps_json_out(array('status' => 'success', 'id' => $id));
        }

        $formNo = trim($input['form_no'] ?? '');
        if ($formNo === '') {
            $formNo = ipps_next_form_no($conn, $_GET['plant_id'] ?? '');
        }
        $status = ($action == 'submitRecord') ? 'pending_qc_receive' : 'draft';
        $reqBy = trim($data['requested_by']) !== '' ? $data['requested_by'] : ($action == 'submitRecord' ? $name : '');
        $samBy = trim($data['sampled_by']) !== '' ? $data['sampled_by'] : ($action == 'submitRecord' ? $name : '');
        $reqDate = $data['requested_date'] ?: ($action == 'submitRecord' ? date('Y-m-d') : '');
        $samDate = $data['sampled_date'] ?: ($action == 'submitRecord' ? date('Y-m-d') : '');
        $reqEmp = ($action == 'submitRecord') ? $empId : '';
        $samEmp = ($action == 'submitRecord') ? $empId : '';

        $sql = "INSERT INTO qc_in_process_sampling
            (plant_id, form_no, status, entry_by, entry_date, sample_type, product_name, product_code, lot_number,
             amount_requested, amount_provided, reason, testing_required, provide_samples_to,
             requested_by, requested_by_emp, requested_date, sampled_by, sampled_by_emp, sampled_date)
            VALUES (
                '".$plantId."', '".ipps_esc($conn, $formNo)."', '".ipps_esc($conn, $status)."', '".$empId."', '".$entry_date."',
                '".ipps_esc($conn, $data['sample_type'])."', '".ipps_esc($conn, $data['product_name'])."',
                '".ipps_esc($conn, $data['product_code'])."', '".ipps_esc($conn, $data['lot_number'])."',
                '".ipps_esc($conn, $data['amount_requested'])."', '".ipps_esc($conn, $data['amount_provided'])."',
                '".ipps_esc($conn, $data['reason'])."', '".ipps_esc($conn, $data['testing_required'])."',
                '".ipps_esc($conn, $data['provide_samples_to'])."',
                '".ipps_esc($conn, $reqBy)."', '".ipps_esc($conn, $reqEmp)."',
                ".($reqDate ? "'".ipps_esc($conn, $reqDate)."'" : "NULL").",
                '".ipps_esc($conn, $samBy)."', '".ipps_esc($conn, $samEmp)."',
                ".($samDate ? "'".ipps_esc($conn, $samDate)."'" : "NULL")."
            )";
        if (!$conn->query($sql)) {
            ipps_json_out(array('status' => 'error', 'message' => $conn->error));
        }
        $newId = $conn->insert_id;
        if ($action == 'submitRecord') {
            ipps_log($conn, $newId, 'submit', 'draft', 'pending_qc_receive', '', $empId, $entry_date);
        }
        ipps_json_out(array('status' => 'success', 'id' => $newId, 'form_no' => $formNo));
    }
    else if ($action == 'getRecordsByStatus') {
        $status = ipps_esc($conn, $_GET['status'] ?? '');
        $where = "r.plant_id = '".$plantId."'";
        if ($status !== '' && $status !== 'all') {
            $where .= " AND r.status = '".$status."'";
        }
        $sql = "SELECT r.*,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = r.entry_by LIMIT 1) AS entry_by_name
                FROM qc_in_process_sampling r WHERE ".$where." ORDER BY r.id DESC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = ipps_row_output($row);
            }
        }
        ipps_json_out($output);
    }
    else if ($action == 'getRecordLog') {
        $from = ipps_esc($conn, $_GET['from_date'] ?? '');
        $to = ipps_esc($conn, $_GET['to_date'] ?? '');
        $status = ipps_esc($conn, $_GET['status'] ?? '');
        $where = "r.plant_id = '".$plantId."'";
        if ($from !== '') {
            $where .= " AND DATE(r.entry_date) >= '".$from."'";
        }
        if ($to !== '') {
            $where .= " AND DATE(r.entry_date) <= '".$to."'";
        }
        if ($status !== '' && $status !== 'all') {
            $where .= " AND r.status = '".$status."'";
        }
        $sql = "SELECT r.*,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = r.entry_by LIMIT 1) AS entry_by_name
                FROM qc_in_process_sampling r WHERE ".$where." ORDER BY r.id DESC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = ipps_row_output($row);
            }
        }
        ipps_json_out($output);
    }
    else if ($action == 'getRecordById') {
        $id = intval($_GET['id'] ?? 0);
        $row = ipps_fetch($conn, $id, $_GET['plant_id'] ?? '');
        if (!$row) {
            ipps_json_out(array('status' => 'invalid', 'message' => 'Not found'));
        }
        $logs = array();
        $logRes = $conn->query("SELECT l.*,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = l.action_by LIMIT 1) AS action_by_name
                FROM qc_in_process_sampling_log l WHERE l.record_id = '".$id."' ORDER BY l.id ASC");
        if ($logRes && $logRes->num_rows > 0) {
            while ($lr = $logRes->fetch_assoc()) {
                $logs[] = $lr;
            }
        }
        ipps_json_out(array('record' => $row, 'workflow_log' => $logs));
    }
    else if ($action == 'qcReceive') {
        if (empty($input['id'])) {
            ipps_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = ipps_fetch($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_qc_receive') {
            ipps_json_out(array('status' => 'invalid', 'message' => 'Not pending QC receiving'));
        }
        $name = ipps_emp_name($conn, $_GET['emp_id']);
        $labNumber = trim($input['lab_number'] ?? '');
        if ($labNumber === '') {
            $labNumber = ipps_next_lab_number($conn, $_GET['plant_id'] ?? '');
        }
        $remark = ipps_esc($conn, $input['receive_remark'] ?? '');
        $sql = "UPDATE qc_in_process_sampling SET
            status = 'pending_testing',
            lab_number = '".ipps_esc($conn, $labNumber)."',
            receive_remark = '".$remark."',
            received_by = '".ipps_esc($conn, $name)."',
            received_by_emp = '".$empId."',
            received_date = '".date('Y-m-d')."'
            WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            ipps_log($conn, $id, 'qc_receive', 'pending_qc_receive', 'pending_testing', $input['receive_remark'] ?? '', $empId, $entry_date);
            ipps_json_out(array('status' => 'success', 'lab_number' => $labNumber));
        }
        ipps_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'submitTesting') {
        if (empty($input['id'])) {
            ipps_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = ipps_fetch($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_testing') {
            ipps_json_out(array('status' => 'invalid', 'message' => 'Not pending testing'));
        }
        $name = ipps_emp_name($conn, $_GET['emp_id']);
        $testingData = json_encode($input['testing_data'] ?? array(), JSON_UNESCAPED_UNICODE);
        $testSpec = ipps_esc($conn, $input['test_spec_ref'] ?? '');
        $sql = "UPDATE qc_in_process_sampling SET
            status = 'pending_qc_review',
            test_spec_ref = '".$testSpec."',
            testing_data = '".ipps_esc($conn, $testingData)."',
            testing_by = '".ipps_esc($conn, $name)."',
            testing_by_emp = '".$empId."',
            testing_date = '".date('Y-m-d')."'
            WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            ipps_log($conn, $id, 'testing', 'pending_testing', 'pending_qc_review', $input['remark'] ?? '', $empId, $entry_date);
            ipps_json_out(array('status' => 'success'));
        }
        ipps_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'submitQcReview') {
        if (empty($input['id'])) {
            ipps_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = ipps_fetch($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_qc_review') {
            ipps_json_out(array('status' => 'invalid', 'message' => 'Not pending QC review'));
        }
        $name = ipps_emp_name($conn, $_GET['emp_id']);
        $sql = "UPDATE qc_in_process_sampling SET
            status = 'pending_analyst_entry',
            review_remark = '".ipps_esc($conn, $input['review_remark'] ?? '')."',
            reviewed_by = '".ipps_esc($conn, $name)."',
            reviewed_by_emp = '".$empId."',
            reviewed_date = '".date('Y-m-d')."'
            WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            ipps_log($conn, $id, 'qc_review', 'pending_qc_review', 'pending_analyst_entry', $input['review_remark'] ?? '', $empId, $entry_date);
            ipps_json_out(array('status' => 'success'));
        }
        ipps_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'submitAnalystEntry') {
        if (empty($input['id'])) {
            ipps_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = ipps_fetch($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_analyst_entry') {
            ipps_json_out(array('status' => 'invalid', 'message' => 'Not pending analyst entry'));
        }
        $name = ipps_emp_name($conn, $_GET['emp_id']);
        $resultsData = json_encode($input['results_data'] ?? array(), JSON_UNESCAPED_UNICODE);
        $sql = "UPDATE qc_in_process_sampling SET
            status = 'pending_qc_approval',
            results_data = '".ipps_esc($conn, $resultsData)."',
            results_entered_by = '".ipps_esc($conn, $name)."',
            results_entered_by_emp = '".$empId."',
            results_entered_date = '".date('Y-m-d')."'
            WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            ipps_log($conn, $id, 'analyst_entry', 'pending_analyst_entry', 'pending_qc_approval', $input['remark'] ?? '', $empId, $entry_date);
            ipps_json_out(array('status' => 'success'));
        }
        ipps_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'submitQcApproval') {
        if (empty($input['id'])) {
            ipps_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = ipps_fetch($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_qc_approval') {
            ipps_json_out(array('status' => 'invalid', 'message' => 'Not pending QC approval'));
        }
        $name = ipps_emp_name($conn, $_GET['emp_id']);
        $sql = "UPDATE qc_in_process_sampling SET
            status = 'closed',
            approval_remark = '".ipps_esc($conn, $input['approval_remark'] ?? '')."',
            approved_by = '".ipps_esc($conn, $name)."',
            approved_by_emp = '".$empId."',
            approved_date = '".date('Y-m-d')."'
            WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            ipps_log($conn, $id, 'qc_approval', 'pending_qc_approval', 'closed', $input['approval_remark'] ?? '', $empId, $entry_date);
            ipps_json_out(array('status' => 'success'));
        }
        ipps_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else {
        ipps_json_out(array('status' => 'invalid', 'message' => 'Unknown action'));
    }
} else {
    ipps_json_out(array('status' => 'invalid', 'message' => 'Invalid token'));
}
$conn->close();
