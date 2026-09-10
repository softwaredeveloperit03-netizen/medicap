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

function pls_json_out($data) {
    echo json_encode($data);
    exit;
}

function pls_run_query($conn, $sql) {
    try {
        return $conn->query($sql);
    } catch (Exception $e) {
        return false;
    }
}

function pls_esc($conn, $val) {
    return $conn->real_escape_string((string)$val);
}

function pls_json_decode($str) {
    if ($str === null || $str === '') {
        return array();
    }
    $decoded = json_decode($str, true);
    return is_array($decoded) ? $decoded : array();
}

function pls_emp_name($conn, $empId) {
    if (!$empId) {
        return '';
    }
    $res = $conn->query("SELECT CONCAT(firstname, ' ', lastname) AS nm FROM employee WHERE emp_id = '".pls_esc($conn, $empId)."' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $nm = trim($row['nm'] ?? '');
        if ($nm !== '') {
            return $nm;
        }
    }
    return $empId;
}

function ensure_pls_schema($conn) {
    pls_run_query($conn, "CREATE TABLE IF NOT EXISTS `processing_laboratory_sample` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `plant_id` varchar(50) DEFAULT NULL,
        `form_no` varchar(50) DEFAULT NULL,
        `lab_number` varchar(50) DEFAULT NULL,
        `lab_number_scheme` varchar(20) DEFAULT 'qc',
        `controlled_substance` varchar(5) DEFAULT 'No',
        `flow_type` varchar(30) DEFAULT 'raw_material',
        `sample_category` varchar(100) DEFAULT NULL,
        `sample_type_label` varchar(255) DEFAULT NULL,
        `sampling_batch_id` int(11) DEFAULT NULL,
        `sampling_id` int(11) DEFAULT NULL,
        `chemical_name` varchar(255) DEFAULT NULL,
        `commercial_name` varchar(255) DEFAULT NULL,
        `manufacturer` varchar(255) DEFAULT NULL,
        `manufacturer_lot` varchar(100) DEFAULT NULL,
        `medicap_code` varchar(100) DEFAULT NULL,
        `medicap_lot` varchar(100) DEFAULT NULL,
        `mfg_retest_applicable` varchar(5) DEFAULT 'No',
        `mfg_retest_date` date DEFAULT NULL,
        `exp_applicable` varchar(5) DEFAULT 'No',
        `exp_date` date DEFAULT NULL,
        `containers_received` varchar(50) DEFAULT NULL,
        `fmm_attached` varchar(5) DEFAULT 'No',
        `section_a` text DEFAULT NULL,
        `section_a_by` varchar(100) DEFAULT NULL,
        `section_a_by_emp` varchar(50) DEFAULT NULL,
        `section_a_date` date DEFAULT NULL,
        `section_b` text DEFAULT NULL,
        `section_b_by` varchar(100) DEFAULT NULL,
        `section_b_by_emp` varchar(50) DEFAULT NULL,
        `section_b_date` date DEFAULT NULL,
        `lab_received_by` varchar(100) DEFAULT NULL,
        `lab_received_by_emp` varchar(50) DEFAULT NULL,
        `lab_received_date` date DEFAULT NULL,
        `section_c` text DEFAULT NULL,
        `section_c_by` varchar(100) DEFAULT NULL,
        `section_c_by_emp` varchar(50) DEFAULT NULL,
        `section_c_date` date DEFAULT NULL,
        `section_d` text DEFAULT NULL,
        `is_retest` varchar(5) DEFAULT 'No',
        `status` varchar(50) DEFAULT 'draft',
        `entry_by` varchar(50) DEFAULT NULL,
        `entry_date` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

    pls_run_query($conn, "CREATE TABLE IF NOT EXISTS `processing_laboratory_sample_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sample_id` int(11) NOT NULL,
        `action` varchar(100) DEFAULT NULL,
        `from_status` varchar(50) DEFAULT NULL,
        `to_status` varchar(50) DEFAULT NULL,
        `remark` text DEFAULT NULL,
        `action_by` varchar(50) DEFAULT NULL,
        `action_date` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");
}

function pls_log_action($conn, $sampleId, $actionName, $fromStatus, $toStatus, $remark, $empId, $entryDate) {
    $sql = "INSERT INTO processing_laboratory_sample_log
            (sample_id, action, from_status, to_status, remark, action_by, action_date)
            VALUES (
                '".intval($sampleId)."',
                '".pls_esc($conn, $actionName)."',
                '".pls_esc($conn, $fromStatus)."',
                '".pls_esc($conn, $toStatus)."',
                '".pls_esc($conn, $remark)."',
                '".pls_esc($conn, $empId)."',
                '".pls_esc($conn, $entryDate)."'
            )";
    pls_run_query($conn, $sql);
}

function pls_row_to_output($row) {
    if (!$row) {
        return null;
    }
    $row['section_a'] = pls_json_decode($row['section_a'] ?? '');
    $row['section_b'] = pls_json_decode($row['section_b'] ?? '');
    $row['section_c'] = pls_json_decode($row['section_c'] ?? '');
    $row['section_d'] = pls_json_decode($row['section_d'] ?? '');
    return $row;
}

function pls_fetch_sample($conn, $id, $plantId) {
    $id = intval($id);
    $plantEsc = pls_esc($conn, $plantId);
    $res = $conn->query("SELECT * FROM processing_laboratory_sample WHERE id = '".$id."' AND plant_id = '".$plantEsc."' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        return pls_row_to_output($res->fetch_assoc());
    }
    return null;
}

function pls_lab_prefix($scheme, $yy) {
    if ($scheme === 'qc_con') {
        return 'QC-CON-'.$yy.'-';
    }
    if ($scheme === 'lab') {
        return 'LAB-'.$yy.'-';
    }
    return 'QC-'.$yy.'-';
}

function pls_next_form_no($conn, $plantId) {
    $year = date('Y');
    $prefix = 'FQC-'.$year.'-';
    $prefixEsc = pls_esc($conn, $prefix);
    $plantEsc = pls_esc($conn, $plantId);
    $res = $conn->query("SELECT form_no FROM processing_laboratory_sample
        WHERE plant_id = '".$plantEsc."' AND form_no LIKE '".$prefixEsc."%'
        ORDER BY id DESC LIMIT 1");
    $next = 1;
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $last = $row['form_no'] ?? '';
        if (preg_match('/-(\d+)$/', $last, $m)) {
            $next = intval($m[1]) + 1;
        }
    }
    return $prefix.str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

function pls_next_lab_number($conn, $plantId, $scheme, $controlled, $versionSuffix) {
    $yy = date('y');
    $prefix = pls_lab_prefix($scheme, $yy);
    $prefixEsc = pls_esc($conn, $prefix);
    $plantEsc = pls_esc($conn, $plantId);
    $res = $conn->query("SELECT lab_number FROM processing_laboratory_sample
        WHERE plant_id = '".$plantEsc."' AND lab_number LIKE '".$prefixEsc."%'
        ORDER BY id DESC LIMIT 200");
    $maxSeq = 0;
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $num = $row['lab_number'] ?? '';
            if (preg_match('/'.preg_quote($prefix, '/').'(\d{4})\./', $num, $m)) {
                $seq = intval($m[1]);
                if ($seq > $maxSeq) {
                    $maxSeq = $seq;
                }
            }
        }
    }
    $nextSeq = $maxSeq + 1;
    $suffix = $versionSuffix !== '' ? $versionSuffix : '.00';
    if (strpos($suffix, '.') !== 0) {
        $suffix = '.'.$suffix;
    }
    $labNo = $prefix.str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT).$suffix;
    if (($controlled ?? 'No') === 'Yes') {
        $labNo .= 'CS';
    }
    return $labNo;
}

function pls_initial_status($flowType) {
    if ($flowType === 'other') {
        return 'pending_lab_receive';
    }
    return 'pending_section_a';
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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$action.'", "actiontime": "'.date('Y-m-d H:i:s').'", "department": "'.$_GET['department'].'", "emp_id": "'.$_GET['emp_id'].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    @file_put_contents('../logs.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);

    ensure_pls_schema($conn);
    $plantId = pls_esc($conn, $_GET['plant_id'] ?? '');
    $empId = pls_esc($conn, $_GET['emp_id']);
    $entry_date = date('Y-m-d H:i:s');

    if ($action == 'getNextFormNo') {
        pls_json_out(array('form_no' => pls_next_form_no($conn, $_GET['plant_id'] ?? '')));
    }
    else if ($action == 'getNextLabNumber') {
        $scheme = $_GET['scheme'] ?? 'qc';
        $controlled = ($_GET['controlled'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
        $version = $_GET['version'] ?? '.00';
        pls_json_out(array(
            'lab_number' => pls_next_lab_number($conn, $_GET['plant_id'] ?? '', $scheme, $controlled, $version)
        ));
    }
    else if ($action == 'getMaterials') {
        $output = array();
        $sql = "SELECT material_code, material_name, material_type, grade
                FROM material
                WHERE plant_id = '".$plantId."' AND status NOT IN ('In-Active','Absolute')
                ORDER BY material_name";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        pls_json_out($output);
    }
    else if ($action == 'getPendingIntakes') {
        $output = array();
        $materialType = pls_esc($conn, $_GET['material_type'] ?? 'Raw Material');
        $materialTypeFilter = '';
        if ($materialType !== '') {
            $materialTypeFilter = " AND COALESCE(m.material_type, mv.material_type) = '".$materialType."'";
        }
        $sql = "SELECT a.id, a.plant_id, a.trackingId, a.material_code, a.batch_no, a.ar_no, a.grn_no,
                a.mfg_date, a.exp_date, a.mfg_by, a.pack_size, a.total_containers, a.qty_received,
                a.status, a.isGrnReceive, a.grnReceiveBy, a.grnReceiveOn, a.challan_no,
                COALESCE(m.material_name, mv.material_name, '') AS material_name,
                COALESCE(m.material_type, mv.material_type, '') AS material_type,
                COALESCE(m.grade, mv.grade, '') AS grade,
                (SELECT vendor_name FROM vendor v WHERE v.vendor_no = a.mfg_by LIMIT 1) AS manufacturer_name,
                (SELECT c.receiving_no FROM challan_materials c WHERE c.challan_no = a.challan_no AND c.material_code = a.material_code LIMIT 1) AS receiving_no,
                (SELECT c.grn_date FROM challan_materials c WHERE c.challan_no = a.challan_no AND c.material_code = a.material_code LIMIT 1) AS grn_date,
                (SELECT c1.po_no FROM challan c1 WHERE c1.challan_no = a.challan_no LIMIT 1) AS po_no,
                CASE
                    WHEN a.isGrnReceive = 'YES' THEN 'in_quarantine'
                    ELSE 'pending_receive'
                END AS intake_stage
                FROM sampling_batches a
                LEFT JOIN material m ON a.material_code = m.material_code
                LEFT JOIN my_view mv ON a.material_code = mv.material_code AND mv.plant_id = a.plant_id
                WHERE a.plant_id = '".$plantId."'
                  AND a.status NOT IN ('Rejected','Cancelled','Closed','OPENING')
                  AND a.grn_no IS NOT NULL AND TRIM(a.grn_no) != ''
                  AND a.ar_no IS NOT NULL AND TRIM(a.ar_no) != ''
                  AND (
                    (
                        UPPER(TRIM(a.status)) IN ('APPROVED', 'APPROVE')
                        AND (a.isGrnReceive IS NULL OR a.isGrnReceive = '' OR a.isGrnReceive = 'NO')
                    )
                    OR a.isGrnReceive = 'YES'
                  )".$materialTypeFilter."
                ORDER BY a.id DESC
                LIMIT 300";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        pls_json_out($output);
    }
    else if ($action == 'getIntakeByBatchId') {
        $batchId = intval($_GET['batch_id'] ?? 0);
        $sql = "SELECT a.*,
                COALESCE(m.material_name, mv.material_name, '') AS material_name,
                COALESCE(m.material_type, mv.material_type, '') AS material_type,
                COALESCE(m.grade, mv.grade, '') AS grade,
                (SELECT vendor_name FROM vendor v WHERE v.vendor_no = a.mfg_by LIMIT 1) AS manufacturer_name,
                (SELECT c.receiving_no FROM challan_materials c WHERE c.challan_no = a.challan_no AND c.material_code = a.material_code LIMIT 1) AS receiving_no,
                (SELECT c.grn_date FROM challan_materials c WHERE c.challan_no = a.challan_no AND c.material_code = a.material_code LIMIT 1) AS grn_date
                FROM sampling_batches a
                LEFT JOIN material m ON a.material_code = m.material_code
                LEFT JOIN my_view mv ON a.material_code = mv.material_code AND mv.plant_id = a.plant_id
                WHERE a.id = '".$batchId."' AND a.plant_id = '".$plantId."' LIMIT 1";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            pls_json_out(array('status' => 'success', 'intake' => $res->fetch_assoc()));
        }
        pls_json_out(array('status' => 'invalid', 'message' => 'Batch not found'));
    }
    else if ($action == 'saveSample' || $action == 'submitSample') {
        if (count($input) === 0) {
            pls_json_out(array('status' => 'invalid', 'message' => 'Missing request body'));
        }
        $id = intval($input['id'] ?? 0);
        $flowType = ($input['flow_type'] ?? 'raw_material') === 'other' ? 'other' : 'raw_material';
        $jsonFields = array('section_a', 'section_b', 'section_c', 'section_d');
        $data = array(
            'flow_type' => $flowType,
            'sample_category' => $input['sample_category'] ?? '',
            'sample_type_label' => $input['sample_type_label'] ?? '',
            'sampling_batch_id' => intval($input['sampling_batch_id'] ?? 0),
            'sampling_id' => intval($input['sampling_id'] ?? 0),
            'lab_number_scheme' => $input['lab_number_scheme'] ?? 'qc',
            'controlled_substance' => ($input['controlled_substance'] ?? 'No') === 'Yes' ? 'Yes' : 'No',
            'chemical_name' => $input['chemical_name'] ?? '',
            'commercial_name' => $input['commercial_name'] ?? '',
            'manufacturer' => $input['manufacturer'] ?? '',
            'manufacturer_lot' => $input['manufacturer_lot'] ?? '',
            'medicap_code' => $input['medicap_code'] ?? '',
            'medicap_lot' => $input['medicap_lot'] ?? '',
            'mfg_retest_applicable' => ($input['mfg_retest_applicable'] ?? 'No') === 'Yes' ? 'Yes' : 'No',
            'mfg_retest_date' => $input['mfg_retest_date'] ?? '',
            'exp_applicable' => ($input['exp_applicable'] ?? 'No') === 'Yes' ? 'Yes' : 'No',
            'exp_date' => $input['exp_date'] ?? '',
            'containers_received' => $input['containers_received'] ?? '',
            'fmm_attached' => ($input['fmm_attached'] ?? 'No') === 'Yes' ? 'Yes' : 'No',
            'is_retest' => ($input['is_retest'] ?? 'No') === 'Yes' ? 'Yes' : 'No',
        );
        foreach ($jsonFields as $jf) {
            if (isset($input[$jf])) {
                $data[$jf] = json_encode($input[$jf], JSON_UNESCAPED_UNICODE);
            }
        }

        if ($id > 0) {
            $existing = pls_fetch_sample($conn, $id, $_GET['plant_id'] ?? '');
            if (!$existing || $existing['status'] !== 'draft') {
                pls_json_out(array('status' => 'invalid', 'message' => 'Only draft records can be updated'));
            }
            $sets = array();
            foreach ($data as $k => $v) {
                if (in_array($k, array('mfg_retest_date', 'exp_date'), true)) {
                    $sets[] = "`".$k."` = ".($v ? "'".pls_esc($conn, $v)."'" : "NULL");
                } else {
                    $sets[] = "`".$k."` = '".pls_esc($conn, $v)."'";
                }
            }
            $sql = "UPDATE processing_laboratory_sample SET ".implode(', ', $sets)." WHERE id = '".$id."' AND plant_id = '".$plantId."'";
            if ($conn->query($sql)) {
                if ($action == 'submitSample') {
                    $toStatus = pls_initial_status($flowType);
                    $conn->query("UPDATE processing_laboratory_sample SET status = '".pls_esc($conn, $toStatus)."' WHERE id = '".$id."'");
                    pls_log_action($conn, $id, 'submit', 'draft', $toStatus, '', $empId, $entry_date);
                }
                pls_json_out(array('status' => 'success', 'id' => $id));
            }
            pls_json_out(array('status' => 'error', 'message' => $conn->error));
        }

        $formNo = trim($input['form_no'] ?? '');
        if ($formNo === '') {
            $formNo = pls_next_form_no($conn, $_GET['plant_id'] ?? '');
        }
        $status = ($action == 'submitSample') ? pls_initial_status($flowType) : 'draft';
        $fields = array_keys($data);
        $values = array();
        foreach ($data as $k => $v) {
            if (in_array($k, array('mfg_retest_date', 'exp_date'), true)) {
                $values[] = $v ? "'".pls_esc($conn, $v)."'" : "NULL";
            } else {
                $values[] = "'".pls_esc($conn, $v)."'";
            }
        }
        $sql = "INSERT INTO processing_laboratory_sample
                (plant_id, form_no, status, entry_by, entry_date, ".implode(', ', $fields).")
                VALUES ('".$plantId."', '".pls_esc($conn, $formNo)."', '".pls_esc($conn, $status)."',
                '".$empId."', '".$entry_date."', ".implode(', ', $values).")";
        if ($conn->query($sql)) {
            $newId = $conn->insert_id;
            if ($action == 'submitSample') {
                pls_log_action($conn, $newId, 'submit', 'draft', $status, '', $empId, $entry_date);
            }
            pls_json_out(array('status' => 'success', 'id' => $newId, 'form_no' => $formNo));
        }
        pls_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'getSamplesByStatus') {
        $status = pls_esc($conn, $_GET['status'] ?? '');
        $where = "r.plant_id = '".$plantId."'";
        if ($status !== '' && $status !== 'all') {
            $where .= " AND r.status = '".$status."'";
        }
        $sql = "SELECT r.*,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = r.entry_by LIMIT 1) AS entry_by_name
                FROM processing_laboratory_sample r
                WHERE ".$where."
                ORDER BY r.id DESC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = pls_row_to_output($row);
            }
        }
        pls_json_out($output);
    }
    else if ($action == 'getSampleLog') {
        $from = pls_esc($conn, $_GET['from_date'] ?? '');
        $to = pls_esc($conn, $_GET['to_date'] ?? '');
        $status = pls_esc($conn, $_GET['status'] ?? '');
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
                FROM processing_laboratory_sample r
                WHERE ".$where."
                ORDER BY r.id DESC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = pls_row_to_output($row);
            }
        }
        pls_json_out($output);
    }
    else if ($action == 'getSampleById') {
        $id = intval($_GET['id'] ?? 0);
        $row = pls_fetch_sample($conn, $id, $_GET['plant_id'] ?? '');
        if (!$row) {
            pls_json_out(array('status' => 'invalid', 'message' => 'Not found'));
        }
        $logs = array();
        $logRes = $conn->query("SELECT l.*,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = l.action_by LIMIT 1) AS action_by_name
                FROM processing_laboratory_sample_log l
                WHERE l.sample_id = '".$id."'
                ORDER BY l.id ASC");
        if ($logRes && $logRes->num_rows > 0) {
            while ($lr = $logRes->fetch_assoc()) {
                $logs[] = $lr;
            }
        }
        pls_json_out(array('sample' => $row, 'workflow_log' => $logs));
    }
    else if ($action == 'submitSectionA') {
        if (empty($input['id'])) {
            pls_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = pls_fetch_sample($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_section_a') {
            pls_json_out(array('status' => 'invalid', 'message' => 'Not pending Section A'));
        }
        $name = pls_emp_name($conn, $_GET['emp_id']);
        $sectionA = json_encode($input['section_a'] ?? array(), JSON_UNESCAPED_UNICODE);
        $sql = "UPDATE processing_laboratory_sample SET
                status = 'pending_section_b',
                section_a = '".pls_esc($conn, $sectionA)."',
                section_a_by = '".pls_esc($conn, $name)."',
                section_a_by_emp = '".$empId."',
                section_a_date = '".date('Y-m-d')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            pls_log_action($conn, $id, 'section_a', 'pending_section_a', 'pending_section_b', $input['remark'] ?? '', $empId, $entry_date);
            pls_json_out(array('status' => 'success'));
        }
        pls_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'submitSectionB') {
        if (empty($input['id'])) {
            pls_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = pls_fetch_sample($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_section_b') {
            pls_json_out(array('status' => 'invalid', 'message' => 'Not pending Section B'));
        }
        $name = pls_emp_name($conn, $_GET['emp_id']);
        $sectionB = json_encode($input['section_b'] ?? array(), JSON_UNESCAPED_UNICODE);
        $sql = "UPDATE processing_laboratory_sample SET
                status = 'pending_lab_receive',
                section_b = '".pls_esc($conn, $sectionB)."',
                section_b_by = '".pls_esc($conn, $name)."',
                section_b_by_emp = '".$empId."',
                section_b_date = '".date('Y-m-d')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            pls_log_action($conn, $id, 'section_b', 'pending_section_b', 'pending_lab_receive', $input['remark'] ?? '', $empId, $entry_date);
            pls_json_out(array('status' => 'success'));
        }
        pls_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'labReceive') {
        if (empty($input['id'])) {
            pls_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = pls_fetch_sample($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_lab_receive') {
            pls_json_out(array('status' => 'invalid', 'message' => 'Not pending lab receiving'));
        }
        $name = pls_emp_name($conn, $_GET['emp_id']);
        $scheme = $input['lab_number_scheme'] ?? $existing['lab_number_scheme'] ?? 'qc';
        $controlled = ($input['controlled_substance'] ?? $existing['controlled_substance'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
        $labNumber = trim($input['lab_number'] ?? '');
        if ($labNumber === '') {
            $labNumber = pls_next_lab_number($conn, $_GET['plant_id'] ?? '', $scheme, $controlled, '.00');
        }
        $sql = "UPDATE processing_laboratory_sample SET
                status = 'pending_section_c',
                lab_number = '".pls_esc($conn, $labNumber)."',
                lab_number_scheme = '".pls_esc($conn, $scheme)."',
                controlled_substance = '".$controlled."',
                lab_received_by = '".pls_esc($conn, $name)."',
                lab_received_by_emp = '".$empId."',
                lab_received_date = '".date('Y-m-d')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            pls_log_action($conn, $id, 'lab_receive', 'pending_lab_receive', 'pending_section_c', $input['remark'] ?? '', $empId, $entry_date);
            pls_json_out(array('status' => 'success', 'lab_number' => $labNumber));
        }
        pls_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'submitSectionC') {
        if (empty($input['id'])) {
            pls_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = pls_fetch_sample($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_section_c') {
            pls_json_out(array('status' => 'invalid', 'message' => 'Not pending Section C'));
        }
        $name = pls_emp_name($conn, $_GET['emp_id']);
        $sectionC = json_encode($input['section_c'] ?? array(), JSON_UNESCAPED_UNICODE);
        $sectionD = json_encode($input['section_d'] ?? array(), JSON_UNESCAPED_UNICODE);
        $isRetest = ($input['is_retest'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
        $sql = "UPDATE processing_laboratory_sample SET
                status = 'closed',
                section_c = '".pls_esc($conn, $sectionC)."',
                section_d = '".pls_esc($conn, $sectionD)."',
                is_retest = '".$isRetest."',
                section_c_by = '".pls_esc($conn, $name)."',
                section_c_by_emp = '".$empId."',
                section_c_date = '".date('Y-m-d')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            pls_log_action($conn, $id, 'section_c', 'pending_section_c', 'closed', $input['remark'] ?? '', $empId, $entry_date);
            pls_json_out(array('status' => 'success'));
        }
        pls_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else {
        pls_json_out(array('status' => 'invalid', 'message' => 'Unknown action'));
    }
} else {
    pls_json_out(array('status' => 'invalid', 'message' => 'Invalid token'));
}
$conn->close();
