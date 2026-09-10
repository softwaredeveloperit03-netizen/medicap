<?php
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
