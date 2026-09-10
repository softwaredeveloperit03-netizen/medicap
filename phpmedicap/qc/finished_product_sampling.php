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

function fps_json_out($data) {
    echo json_encode($data);
    exit;
}

function fps_esc($conn, $val) {
    return $conn->real_escape_string((string)$val);
}

function fps_emp_name($conn, $empId) {
    if (!$empId) {
        return '';
    }
    $res = $conn->query("SELECT CONCAT(firstname, ' ', lastname) AS nm FROM employee WHERE emp_id = '".fps_esc($conn, $empId)."' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $nm = trim($row['nm'] ?? '');
        if ($nm !== '') {
            return $nm;
        }
    }
    return $empId;
}

function fps_json_decode($str) {
    if ($str === null || $str === '') {
        return array();
    }
    $decoded = json_decode($str, true);
    return is_array($decoded) ? $decoded : array();
}

function fps_default_test_rows() {
    return array(
        array('key' => 'description_id', 'label' => 'Description/Identification', 'required_qty' => '', 'sampled_qty' => ''),
        array('key' => 'assay_impurities', 'label' => 'Assay and Organic Impurities', 'required_qty' => '', 'sampled_qty' => ''),
        array('key' => 'uniformity', 'label' => 'Uniformity', 'required_qty' => '', 'sampled_qty' => ''),
        array('key' => 'water_content', 'label' => 'Water Content', 'required_qty' => '', 'sampled_qty' => ''),
        array('key' => 'dissolution', 'label' => 'Dissolution', 'required_qty' => '', 'sampled_qty' => ''),
        array('key' => 'microbial_limits', 'label' => 'Microbial Limits (where applicable)', 'required_qty' => '', 'sampled_qty' => ''),
        array('key' => 'total_qty', 'label' => 'Total Quantity Required for Full testing (excluding microbial limits)', 'required_qty' => '', 'sampled_qty' => ''),
    );
}

function fps_merge_test_rows($inputRows) {
    $defaults = fps_default_test_rows();
    if (!is_array($inputRows)) {
        return $defaults;
    }
    $map = array();
    foreach ($inputRows as $row) {
        if (!empty($row['key'])) {
            $map[$row['key']] = $row;
        }
    }
    foreach ($defaults as $i => $def) {
        if (isset($map[$def['key']])) {
            $defaults[$i]['required_qty'] = $map[$def['key']]['required_qty'] ?? '';
            $defaults[$i]['sampled_qty'] = $map[$def['key']]['sampled_qty'] ?? '';
        }
    }
    return $defaults;
}

function ensure_fps_schema($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `qc_finished_product_sampling` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `plant_id` varchar(50) DEFAULT NULL,
        `form_no` varchar(50) DEFAULT NULL,
        `batch_type` varchar(30) DEFAULT 'commercial',
        `product_name` varchar(255) DEFAULT NULL,
        `strength` varchar(100) DEFAULT NULL,
        `lot_no` varchar(100) DEFAULT NULL,
        `code_no` varchar(100) DEFAULT NULL,
        `test_rows` text DEFAULT NULL,
        `atr_reference` varchar(100) DEFAULT NULL,
        `qc_manager_by` varchar(100) DEFAULT NULL,
        `qc_manager_by_emp` varchar(50) DEFAULT NULL,
        `qc_manager_date` date DEFAULT NULL,
        `qa_approved_by` varchar(100) DEFAULT NULL,
        `qa_approved_by_emp` varchar(50) DEFAULT NULL,
        `qa_approved_date` date DEFAULT NULL,
        `production_supervisor_by` varchar(100) DEFAULT NULL,
        `production_supervisor_by_emp` varchar(50) DEFAULT NULL,
        `production_supervisor_date` date DEFAULT NULL,
        `lab_number` varchar(50) DEFAULT NULL,
        `received_by` varchar(100) DEFAULT NULL,
        `received_by_emp` varchar(50) DEFAULT NULL,
        `received_date` date DEFAULT NULL,
        `receive_remark` text DEFAULT NULL,
        `test_spec_ref` varchar(255) DEFAULT NULL,
        `results_data` text DEFAULT NULL,
        `results_by` varchar(100) DEFAULT NULL,
        `results_by_emp` varchar(50) DEFAULT NULL,
        `results_date` date DEFAULT NULL,
        `coa_approved_by` varchar(100) DEFAULT NULL,
        `coa_approved_by_emp` varchar(50) DEFAULT NULL,
        `coa_approved_date` date DEFAULT NULL,
        `coa_remark` text DEFAULT NULL,
        `is_coa` varchar(5) DEFAULT 'No',
        `status` varchar(50) DEFAULT 'draft',
        `entry_by` varchar(50) DEFAULT NULL,
        `entry_date` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS `qc_finished_product_sampling_log` (
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

function fps_log($conn, $recordId, $action, $from, $to, $remark, $empId, $entryDate) {
    $conn->query("INSERT INTO qc_finished_product_sampling_log
        (record_id, action, from_status, to_status, remark, action_by, action_date)
        VALUES (
            '".intval($recordId)."',
            '".fps_esc($conn, $action)."',
            '".fps_esc($conn, $from)."',
            '".fps_esc($conn, $to)."',
            '".fps_esc($conn, $remark)."',
            '".fps_esc($conn, $empId)."',
            '".fps_esc($conn, $entryDate)."'
        )");
}

function fps_next_form_no($conn, $plantId) {
    $year = date('Y');
    $prefix = 'FPS-'.$year.'-';
    $prefixEsc = fps_esc($conn, $prefix);
    $plantEsc = fps_esc($conn, $plantId);
    $res = $conn->query("SELECT form_no FROM qc_finished_product_sampling
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

function fps_next_lab_number($conn, $plantId) {
    $yy = date('y');
    $prefix = 'QC-'.$yy.'-';
    $prefixEsc = fps_esc($conn, $prefix);
    $plantEsc = fps_esc($conn, $plantId);
    $maxSeq = 0;
    $tables = array(
        array('qc_finished_product_sampling', 'lab_number'),
        array('qc_in_process_sampling', 'lab_number'),
        array('processing_laboratory_sample', 'lab_number'),
        array('analytical_test_request', 'lab_sample_number'),
    );
    foreach ($tables as $t) {
        $res = @$conn->query("SELECT `".$t[1]."` AS lab_no FROM `".$t[0]."`
            WHERE plant_id = '".$plantEsc."' AND `".$t[1]."` LIKE '".$prefixEsc."%'
            ORDER BY id DESC LIMIT 100");
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                if (preg_match('/'.preg_quote($prefix, '/').'(\d{4})/', $row['lab_no'] ?? '', $m)) {
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

function fps_row_output($row) {
    if (!$row) {
        return null;
    }
    $row['test_rows'] = fps_merge_test_rows(fps_json_decode($row['test_rows'] ?? ''));
    $row['results_data'] = fps_json_decode($row['results_data'] ?? '');
    return $row;
}

function fps_fetch($conn, $id, $plantId) {
    $id = intval($id);
    $plantEsc = fps_esc($conn, $plantId);
    $res = $conn->query("SELECT r.*,
            (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = r.entry_by LIMIT 1) AS entry_by_name
            FROM qc_finished_product_sampling r
            WHERE r.id = '".$id."' AND r.plant_id = '".$plantEsc."' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        return fps_row_output($res->fetch_assoc());
    }
    return null;
}

function fps_build_header($input) {
    return array(
        'batch_type' => in_array($input['batch_type'] ?? '', array('commercial', 'stability'), true) ? $input['batch_type'] : 'commercial',
        'product_name' => $input['product_name'] ?? '',
        'strength' => $input['strength'] ?? '',
        'lot_no' => $input['lot_no'] ?? '',
        'code_no' => $input['code_no'] ?? '',
        'atr_reference' => $input['atr_reference'] ?? '',
    );
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

    ensure_fps_schema($conn);
    $plantId = fps_esc($conn, $_GET['plant_id'] ?? '');
    $empId = fps_esc($conn, $_GET['emp_id']);
    $entry_date = date('Y-m-d H:i:s');
    $name = fps_emp_name($conn, $_GET['emp_id']);

    if ($action == 'getNextFormNo') {
        fps_json_out(array('form_no' => fps_next_form_no($conn, $_GET['plant_id'] ?? '')));
    }
    else if ($action == 'getNextLabNumber') {
        fps_json_out(array('lab_number' => fps_next_lab_number($conn, $_GET['plant_id'] ?? '')));
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
        fps_json_out($output);
    }
    else if ($action == 'savePlan' || $action == 'submitPlan') {
        if (count($input) === 0) {
            fps_json_out(array('status' => 'invalid', 'message' => 'Missing request body'));
        }
        $id = intval($input['id'] ?? 0);
        $header = fps_build_header($input);
        $testRows = json_encode(fps_merge_test_rows($input['test_rows'] ?? array()), JSON_UNESCAPED_UNICODE);

        if ($id > 0) {
            $existing = fps_fetch($conn, $id, $_GET['plant_id'] ?? '');
            if (!$existing || $existing['status'] !== 'draft') {
                fps_json_out(array('status' => 'invalid', 'message' => 'Only draft plans can be updated'));
            }
            $statusSql = ($action == 'submitPlan') ? ", status = 'pending_qc_manager'" : '';
            $sql = "UPDATE qc_finished_product_sampling SET
                batch_type = '".fps_esc($conn, $header['batch_type'])."',
                product_name = '".fps_esc($conn, $header['product_name'])."',
                strength = '".fps_esc($conn, $header['strength'])."',
                lot_no = '".fps_esc($conn, $header['lot_no'])."',
                code_no = '".fps_esc($conn, $header['code_no'])."',
                atr_reference = '".fps_esc($conn, $header['atr_reference'])."',
                test_rows = '".fps_esc($conn, $testRows)."'
                ".$statusSql."
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
            if (!$conn->query($sql)) {
                fps_json_out(array('status' => 'error', 'message' => $conn->error));
            }
            if ($action == 'submitPlan') {
                fps_log($conn, $id, 'submit', 'draft', 'pending_qc_manager', '', $empId, $entry_date);
            }
            fps_json_out(array('status' => 'success', 'id' => $id));
        }

        $formNo = trim($input['form_no'] ?? '');
        if ($formNo === '') {
            $formNo = fps_next_form_no($conn, $_GET['plant_id'] ?? '');
        }
        $status = ($action == 'submitPlan') ? 'pending_qc_manager' : 'draft';
        $sql = "INSERT INTO qc_finished_product_sampling
            (plant_id, form_no, status, entry_by, entry_date, batch_type, product_name, strength, lot_no, code_no, atr_reference, test_rows)
            VALUES (
                '".$plantId."', '".fps_esc($conn, $formNo)."', '".fps_esc($conn, $status)."', '".$empId."', '".$entry_date."',
                '".fps_esc($conn, $header['batch_type'])."', '".fps_esc($conn, $header['product_name'])."',
                '".fps_esc($conn, $header['strength'])."', '".fps_esc($conn, $header['lot_no'])."',
                '".fps_esc($conn, $header['code_no'])."', '".fps_esc($conn, $header['atr_reference'])."',
                '".fps_esc($conn, $testRows)."'
            )";
        if (!$conn->query($sql)) {
            fps_json_out(array('status' => 'error', 'message' => $conn->error));
        }
        $newId = $conn->insert_id;
        if ($action == 'submitPlan') {
            fps_log($conn, $newId, 'submit', 'draft', 'pending_qc_manager', '', $empId, $entry_date);
        }
        fps_json_out(array('status' => 'success', 'id' => $newId, 'form_no' => $formNo));
    }
    else if ($action == 'getRecordsByStatus') {
        $status = fps_esc($conn, $_GET['status'] ?? '');
        $where = "r.plant_id = '".$plantId."'";
        if ($status !== '' && $status !== 'all') {
            $where .= " AND r.status = '".$status."'";
        }
        $sql = "SELECT r.*,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = r.entry_by LIMIT 1) AS entry_by_name
                FROM qc_finished_product_sampling r WHERE ".$where." ORDER BY r.id DESC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = fps_row_output($row);
            }
        }
        fps_json_out($output);
    }
    else if ($action == 'getRecordLog') {
        $from = fps_esc($conn, $_GET['from_date'] ?? '');
        $to = fps_esc($conn, $_GET['to_date'] ?? '');
        $status = fps_esc($conn, $_GET['status'] ?? '');
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
                FROM qc_finished_product_sampling r WHERE ".$where." ORDER BY r.id DESC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = fps_row_output($row);
            }
        }
        fps_json_out($output);
    }
    else if ($action == 'getRecordById') {
        $id = intval($_GET['id'] ?? 0);
        $row = fps_fetch($conn, $id, $_GET['plant_id'] ?? '');
        if (!$row) {
            fps_json_out(array('status' => 'invalid', 'message' => 'Not found'));
        }
        $logs = array();
        $logRes = $conn->query("SELECT l.*,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = l.action_by LIMIT 1) AS action_by_name
                FROM qc_finished_product_sampling_log l WHERE l.record_id = '".$id."' ORDER BY l.id ASC");
        if ($logRes && $logRes->num_rows > 0) {
            while ($lr = $logRes->fetch_assoc()) {
                $logs[] = $lr;
            }
        }
        fps_json_out(array('record' => $row, 'workflow_log' => $logs));
    }
    else if ($action == 'approveQcManager') {
        if (empty($input['id'])) {
            fps_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = fps_fetch($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_qc_manager') {
            fps_json_out(array('status' => 'invalid', 'message' => 'Not pending QC Manager approval'));
        }
        $testRows = json_encode(fps_merge_test_rows($input['test_rows'] ?? $existing['test_rows']), JSON_UNESCAPED_UNICODE);
        $sql = "UPDATE qc_finished_product_sampling SET
            status = 'pending_qa_approval',
            test_rows = '".fps_esc($conn, $testRows)."',
            qc_manager_by = '".fps_esc($conn, $name)."',
            qc_manager_by_emp = '".$empId."',
            qc_manager_date = '".date('Y-m-d')."'
            WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            fps_log($conn, $id, 'qc_manager', 'pending_qc_manager', 'pending_qa_approval', $input['remark'] ?? '', $empId, $entry_date);
            fps_json_out(array('status' => 'success'));
        }
        fps_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'approveQa') {
        if (empty($input['id'])) {
            fps_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = fps_fetch($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_qa_approval') {
            fps_json_out(array('status' => 'invalid', 'message' => 'Not pending QA approval'));
        }
        $sql = "UPDATE qc_finished_product_sampling SET
            status = 'pending_production',
            qa_approved_by = '".fps_esc($conn, $name)."',
            qa_approved_by_emp = '".$empId."',
            qa_approved_date = '".date('Y-m-d')."'
            WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            fps_log($conn, $id, 'qa_approval', 'pending_qa_approval', 'pending_production', $input['remark'] ?? '', $empId, $entry_date);
            fps_json_out(array('status' => 'success'));
        }
        fps_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'submitProduction') {
        if (empty($input['id'])) {
            fps_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = fps_fetch($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_production') {
            fps_json_out(array('status' => 'invalid', 'message' => 'Not pending production sampling'));
        }
        $testRows = json_encode(fps_merge_test_rows($input['test_rows'] ?? $existing['test_rows']), JSON_UNESCAPED_UNICODE);
        $atrRef = fps_esc($conn, $input['atr_reference'] ?? $existing['atr_reference'] ?? '');
        $sql = "UPDATE qc_finished_product_sampling SET
            status = 'pending_qc_receive',
            test_rows = '".fps_esc($conn, $testRows)."',
            atr_reference = '".$atrRef."',
            production_supervisor_by = '".fps_esc($conn, $name)."',
            production_supervisor_by_emp = '".$empId."',
            production_supervisor_date = '".date('Y-m-d')."'
            WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            fps_log($conn, $id, 'production', 'pending_production', 'pending_qc_receive', $input['remark'] ?? '', $empId, $entry_date);
            fps_json_out(array('status' => 'success'));
        }
        fps_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'qcReceive') {
        if (empty($input['id'])) {
            fps_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = fps_fetch($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_qc_receive') {
            fps_json_out(array('status' => 'invalid', 'message' => 'Not pending QC receiving'));
        }
        $labNumber = trim($input['lab_number'] ?? '');
        if ($labNumber === '') {
            $labNumber = fps_next_lab_number($conn, $_GET['plant_id'] ?? '');
        }
        $sql = "UPDATE qc_finished_product_sampling SET
            status = 'pending_results',
            lab_number = '".fps_esc($conn, $labNumber)."',
            receive_remark = '".fps_esc($conn, $input['receive_remark'] ?? '')."',
            received_by = '".fps_esc($conn, $name)."',
            received_by_emp = '".$empId."',
            received_date = '".date('Y-m-d')."'
            WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            fps_log($conn, $id, 'qc_receive', 'pending_qc_receive', 'pending_results', $input['receive_remark'] ?? '', $empId, $entry_date);
            fps_json_out(array('status' => 'success', 'lab_number' => $labNumber));
        }
        fps_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'submitResults') {
        if (empty($input['id'])) {
            fps_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = fps_fetch($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_results') {
            fps_json_out(array('status' => 'invalid', 'message' => 'Not pending results entry'));
        }
        $resultsData = json_encode($input['results_data'] ?? array(), JSON_UNESCAPED_UNICODE);
        $testSpec = fps_esc($conn, $input['test_spec_ref'] ?? '');
        $sql = "UPDATE qc_finished_product_sampling SET
            status = 'pending_coa_approval',
            test_spec_ref = '".$testSpec."',
            results_data = '".fps_esc($conn, $resultsData)."',
            results_by = '".fps_esc($conn, $name)."',
            results_by_emp = '".$empId."',
            results_date = '".date('Y-m-d')."'
            WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            fps_log($conn, $id, 'results', 'pending_results', 'pending_coa_approval', $input['remark'] ?? '', $empId, $entry_date);
            fps_json_out(array('status' => 'success'));
        }
        fps_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'approveCoa') {
        if (empty($input['id'])) {
            fps_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = fps_fetch($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_coa_approval') {
            fps_json_out(array('status' => 'invalid', 'message' => 'Not pending C of A approval'));
        }
        $sql = "UPDATE qc_finished_product_sampling SET
            status = 'closed',
            is_coa = 'Yes',
            coa_remark = '".fps_esc($conn, $input['coa_remark'] ?? '')."',
            coa_approved_by = '".fps_esc($conn, $name)."',
            coa_approved_by_emp = '".$empId."',
            coa_approved_date = '".date('Y-m-d')."'
            WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            fps_log($conn, $id, 'coa_approval', 'pending_coa_approval', 'closed', $input['coa_remark'] ?? '', $empId, $entry_date);
            fps_json_out(array('status' => 'success', 'message' => 'Approved — Test Specification Form is now Certificate of Analysis'));
        }
        fps_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else {
        fps_json_out(array('status' => 'invalid', 'message' => 'Unknown action'));
    }
} else {
    fps_json_out(array('status' => 'invalid', 'message' => 'Invalid token'));
}
$conn->close();
