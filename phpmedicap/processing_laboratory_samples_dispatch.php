    if ($action == 'getNextFormNo') {
        echo json_encode(array('form_no' => pls_next_form_no($conn, $_GET['plant_id'] ?? '')));
    }
    else if ($action == 'getNextLabNumber') {
    $scheme = $_GET['scheme'] ?? 'qc';
    $controlled = ($_GET['controlled'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
    $version = $_GET['version'] ?? '.00';
    echo json_encode(array(
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
    echo json_encode($output);
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
    echo json_encode($output);
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
        echo json_encode(array('status' => 'success', 'intake' => $res->fetch_assoc()));
    } else {
        echo json_encode(array('status' => 'invalid', 'message' => 'Batch not found'));
    }
}
    else if ($action == 'saveSample' || $action == 'submitSample') {
    if (!$input) {
        echo json_encode(array('status' => 'invalid'));
        exit;
    }
    $id = intval($input['id'] ?? 0);
    $flowType = ($input['flow_type'] ?? 'raw_material') === 'other' ? 'other' : 'raw_material';
    $jsonFields = array('section_a', 'section_b', 'section_c', 'section_d');
    $data = array(
        'form_no' => $input['form_no'] ?? pls_next_form_no($conn, $_GET['plant_id'] ?? ''),
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
            echo json_encode(array('status' => 'invalid', 'message' => 'Only draft records can be updated'));
            exit;
        }
        $sets = array();
        foreach ($data as $k => $v) {
            if ($k === 'form_no') {
                continue;
            }
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
            echo json_encode(array('status' => 'success', 'id' => $id));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    } else {
        unset($data['form_no']);
        $formNo = $input['form_no'] ?? pls_next_form_no($conn, $_GET['plant_id'] ?? '');
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
            echo json_encode(array('status' => 'success', 'id' => $newId, 'form_no' => $formNo));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }
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
    echo json_encode($output);
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
    echo json_encode($output);
}
    else if ($action == 'getSampleById') {
    $id = intval($_GET['id'] ?? 0);
    $row = pls_fetch_sample($conn, $id, $_GET['plant_id'] ?? '');
    if (!$row) {
        echo json_encode(array('status' => 'invalid', 'message' => 'Not found'));
        exit;
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
    echo json_encode(array('sample' => $row, 'workflow_log' => $logs));
}
    else if ($action == 'submitSectionA') {
    if (!$input || empty($input['id'])) {
        echo json_encode(array('status' => 'invalid'));
        exit;
    }
    $id = intval($input['id']);
    $existing = pls_fetch_sample($conn, $id, $_GET['plant_id'] ?? '');
    if (!$existing || $existing['status'] !== 'pending_section_a') {
        echo json_encode(array('status' => 'invalid', 'message' => 'Not pending Section A'));
        exit;
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
        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => $conn->error));
    }
}
    else if ($action == 'submitSectionB') {
    if (!$input || empty($input['id'])) {
        echo json_encode(array('status' => 'invalid'));
        exit;
    }
    $id = intval($input['id']);
    $existing = pls_fetch_sample($conn, $id, $_GET['plant_id'] ?? '');
    if (!$existing || $existing['status'] !== 'pending_section_b') {
        echo json_encode(array('status' => 'invalid', 'message' => 'Not pending Section B'));
        exit;
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
    $existing = pls_fetch_sample($conn, $id, $_GET['plant_id'] ?? '');
    if (!$existing || $existing['status'] !== 'pending_lab_receive') {
        echo json_encode(array('status' => 'invalid', 'message' => 'Not pending lab receiving'));
        exit;
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
        echo json_encode(array('status' => 'success', 'lab_number' => $labNumber));
    } else {
        echo json_encode(array('status' => $conn->error));
    }
}
    else if ($action == 'submitSectionC') {
    if (!$input || empty($input['id'])) {
        echo json_encode(array('status' => 'invalid'));
        exit;
    }
    $id = intval($input['id']);
    $existing = pls_fetch_sample($conn, $id, $_GET['plant_id'] ?? '');
    if (!$existing || $existing['status'] !== 'pending_section_c') {
        echo json_encode(array('status' => 'invalid', 'message' => 'Not pending Section C'));
        exit;
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
        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => $conn->error));
    }
}
    else {
        echo json_encode(array('status' => 'invalid', 'message' => 'Unknown action'));
    }
