<?php
/**
 * EBMR/BPR Master — endpoint handlers.
 * Included from ebmr_bpr.php inside the authenticated block.
 * In scope: $conn, $input, $type, $plant_id, $emp_id, $entry_date
 */

/* ===========================================================
   CFR PART 11 — ELECTRONIC SIGNATURE / RE-AUTHENTICATION
   Verifies the logged-in user's password before any commit and
   records a signature manifest (meaning, module, record, time).
=========================================================== */
if ($type == "verifyEsign") {
    require_once __DIR__ . '/../shared/auth_helper.php';
    $password = ebmrbpr_in($input, 'password');
    // Prefer session emp_id; allow body override only when session is empty
    $signEmp = trim((string) $emp_id);
    if ($signEmp === '') {
        $signEmp = trim((string) ebmrbpr_in($input, 'emp_id', ''));
    }
    if ($signEmp === '' || $password === '') {
        echo json_encode(array('status' => 'error', 'message' => 'Password or 4-digit authorization PIN is required to sign.'));
    } else {
        $auth = zuma_auth_verify_employee($conn, $signEmp, $password);
        if (!$auth['ok']) {
            echo json_encode(array('status' => 'error', 'message' => $auth['message']));
        } else {
            $row = $auth['row'];
            $name = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
            if ($name === '') $name = $row['emp_id'];
            $designation = trim((string)($row['designation'] ?? ''));
            if ($designation === '') {
                $designation = trim((string)($row['department'] ?? 'Production'));
            }
            $authMethod = zuma_auth_method_label($auth['method']);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $signedAt = date('Y-m-d H:i:s');
            $meaning = ebmrbpr_in($input, 'meaning');
            $module = ebmrbpr_in($input, 'module');
            $recordRef = ebmrbpr_in($input, 'record_ref');
            $detail = ebmrbpr_in($input, 'detail');
            $reason = ebmrbpr_in($input, 'reason');
            $dept = trim((string)($row['department'] ?? ''));
            $token = hash('sha256', implode('|', array(
                $row['emp_id'], $meaning, $module, $recordRef, $signedAt, $ip, $auth['method']
            )));
            $conn->query("INSERT INTO ebmrbpr_esign (plant_id, emp_id, emp_name, meaning, module, record_ref, detail, reason, ip,
                signature_token, auth_method, auth_type, department, designation)
                VALUES ('" . $plant_id . "', '" . ebmrbpr_esc($conn, $row['emp_id']) . "', '" . ebmrbpr_esc($conn, $name) . "',
                '" . ebmrbpr_esc($conn, $meaning) . "', '" . ebmrbpr_esc($conn, $module) . "',
                '" . ebmrbpr_esc($conn, $recordRef) . "', '" . ebmrbpr_esc($conn, $detail) . "',
                '" . ebmrbpr_esc($conn, $reason) . "', '" . ebmrbpr_esc($conn, $ip) . "',
                '" . ebmrbpr_esc($conn, $token) . "', '" . ebmrbpr_esc($conn, $authMethod) . "',
                '" . ebmrbpr_esc($conn, $auth['method']) . "', '" . ebmrbpr_esc($conn, $dept) . "',
                '" . ebmrbpr_esc($conn, $designation) . "')");
            $esignInsertId = (int)$conn->insert_id;
            // Mirror e-sign into CFR batch audit trail when record_ref is a batch id
            $batchRef = (int)$recordRef;
            if ($batchRef > 0 && (stripos($module, 'execution') !== false || stripos($module, 'ebmr') !== false || stripos($module, 'batch') !== false)) {
                ebmrbpr_log($conn, $batchRef, 'ESIGN_' . strtoupper(preg_replace('/\s+/', '_', $meaning ?: 'SIGN')), $detail ?: ('Electronic signature: ' . $meaning), $row['emp_id'], array(
                    'emp_name' => $name,
                    'department' => $dept,
                    'designation' => $designation,
                    'module' => $module,
                    'record_ref' => $recordRef,
                    'meaning' => $meaning,
                    'auth_method' => $authMethod,
                    'auth_type' => $auth['method'],
                    'signature_token' => $token,
                    'esign_id' => $esignInsertId,
                    'reason' => $reason,
                    'plant_id' => $plant_id,
                ));
            }
            echo json_encode(array(
                'status' => 'success',
                'emp_id' => $row['emp_id'],
                'emp_name' => $name,
                'designation' => $designation,
                'department' => $dept,
                'meaning' => $meaning,
                'signed_at' => $signedAt,
                'esign_id' => $esignInsertId,
                'auth_method' => $authMethod,
                'auth_type' => $auth['method'],
                'signature_token' => $token,
            ));
        }
    }
}
elseif ($type == "getEsignLog") {
    $module = ebmrbpr_esc($conn, $_GET['module'] ?? '');
    $ref = ebmrbpr_esc($conn, $_GET['record_ref'] ?? '');
    $where = "1=1";
    if ($module !== '') $where .= " AND module='$module'";
    if ($ref !== '') $where .= " AND record_ref='$ref'";
    $output = array();
    $res = $conn->query("SELECT * FROM ebmrbpr_esign WHERE $where ORDER BY id ASC");
    if ($res) { while ($r = $res->fetch_assoc()) { $output[] = $r; } }
    echo json_encode($output);
}

/* ===========================================================
   BMR CONFIGURATION MASTER  (BMR no, dosage form, process type,
   approval matrix + workflow + product binding)
=========================================================== */
elseif ($type == "getProcessTypes") {
    echo json_encode(array('Manufacturing (BMR)', 'Packing (BPR)', 'Manufacturing & Packing', 'Reprocessing', 'Rework', 'Sterile / Aseptic', 'Lyophilization'));
}
elseif ($type == "getConfigs") {
    $output = array();
    $where = "1=1";
    if (!empty($_GET['dosage_form'])) $where .= " AND dosage_form='" . ebmrbpr_esc($conn, $_GET['dosage_form']) . "'";
    if (!empty($_GET['status'])) $where .= " AND status='" . ebmrbpr_esc($conn, $_GET['status']) . "'";
    $res = $conn->query("SELECT * FROM ebmrbpr_config WHERE $where ORDER BY id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['matrix'] = json_decode($row['matrix_json'], true);
            $map = isset($row['stage_map_json']) ? json_decode($row['stage_map_json'], true) : array();
            $mapCount = is_array($map) ? count($map) : 0;
            $sc = ebmrbpr_config_stage_counts($conn, (int)$row['id']);
            $stepCount = (int)($sc['step_count'] ?? 0);
            $stageCount = (int)($sc['stage_count'] ?? 0);
            // Prefer mapped stage/step master rows; fall back to legacy stage_map_json
            $row['stage_count'] = $stageCount > 0 ? $stageCount : ($stepCount > 0 ? $stepCount : $mapCount);
            $row['step_count'] = $stepCount;
            $cnt = $conn->query("SELECT COUNT(*) AS c FROM ebmrbpr_config_product WHERE config_id=" . (int)$row['id'] . " AND status='active'");
            $row['product_count'] = $cnt ? (int)$cnt->fetch_assoc()['c'] : 0;
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
/* ===========================================================
   CONFIGURE STAGE & STEP  (stages/steps bound to a Configuration =
   process_type + dosage_form). Independent of per-product masters.
=========================================================== */
elseif ($type == "saveConfigStageStep") {
    $configId = (int)ebmrbpr_in($input, 'config_id', 0);
    if ($configId <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Please select a configuration first.'));
    } else {
        // Resolve the config's process_type / dosage_form / bmr_no (authoritative).
        $cres = $conn->query("SELECT bmr_no, process_type, dosage_form FROM ebmrbpr_config WHERE id=$configId LIMIT 1");
        if (!$cres || $cres->num_rows == 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Configuration not found.'));
        } else {
            $cfg = $cres->fetch_assoc();
            $bmrNo = ebmrbpr_esc($conn, $cfg['bmr_no']);
            $pType = ebmrbpr_esc($conn, $cfg['process_type']);
            $dForm = ebmrbpr_esc($conn, $cfg['dosage_form']);
            $rows = ebmrbpr_in($input, 'rows', array());
            if (is_string($rows)) { $rows = json_decode($rows, true); }
            if (!is_array($rows)) { $rows = array(); }

            $productCode = trim((string)ebmrbpr_in($input, 'product_code', ''));
            $productName = trim((string)ebmrbpr_in($input, 'product_name', ''));
            $productCodeEsc = ebmrbpr_esc($conn, $productCode);
            $productNameEsc = ebmrbpr_esc($conn, $productName);

            $conn->begin_transaction();
            try {
                // Replace-on-save: clear current binding for this config (+ product when mapping per product).
                if ($productCode !== '') {
                    $conn->query("DELETE FROM ebmrbpr_config_stage_step WHERE config_id=$configId AND product_code='$productCodeEsc'");
                } else {
                    $conn->query("DELETE FROM ebmrbpr_config_stage_step WHERE config_id=$configId AND (product_code IS NULL OR product_code='')");
                }
                $stageSeq = 0;
                $lastStage = null;
                foreach ($rows as $r) {
                    $stageName = trim((string)ebmrbpr_in($r, 'stage_name', ''));
                    if ($stageName === '') { continue; }
                    if ($lastStage !== $stageName) { $stageSeq++; $lastStage = $stageName; }
                    $stepSeq = (int)ebmrbpr_in($r, 'step_seq', 0);
                    $timeStamp = function_exists('ebmrbpr_normalize_applicable_flag')
                        ? ebmrbpr_normalize_applicable_flag(ebmrbpr_in($r, 'time_stamp', 'Not Applicable'))
                        : 'Not Applicable';
                    $yieldRecon = function_exists('ebmrbpr_normalize_applicable_flag')
                        ? ebmrbpr_normalize_applicable_flag(ebmrbpr_in($r, 'yield_reconciliation', 'Not Applicable'))
                        : 'Not Applicable';
                    $equipPoint = function_exists('ebmrbpr_normalize_applicable_flag')
                        ? ebmrbpr_normalize_applicable_flag(ebmrbpr_in($r, 'equipment_point', 'Not Applicable'))
                        : 'Not Applicable';
                    $sql = "INSERT INTO ebmrbpr_config_stage_step
                        (plant_id, config_id, bmr_no, process_type, dosage_form, product_code, product_name,
                         stage_seq, stage_name, step_seq, step_name, ipqc_testing, sampling_by, time_stamp, yield_reconciliation, equipment_point, instruction, remark, entry_by)
                        VALUES (
                            '" . $plant_id . "', $configId, '$bmrNo', '$pType', '$dForm',
                            " . ($productCode !== '' ? "'$productCodeEsc'" : "NULL") . ",
                            " . ($productName !== '' ? "'$productNameEsc'" : "NULL") . ",
                            " . (int)(ebmrbpr_in($r, 'stage_seq', 0) ?: $stageSeq) . ",
                            '" . ebmrbpr_esc($conn, $stageName) . "',
                            $stepSeq,
                            '" . ebmrbpr_esc($conn, ebmrbpr_in($r, 'step_name', '')) . "',
                            '" . ebmrbpr_esc($conn, ebmrbpr_in($r, 'ipqc_testing', 'No')) . "',
                            '" . ebmrbpr_esc($conn, ebmrbpr_in($r, 'sampling_by', 'Production')) . "',
                            '" . ebmrbpr_esc($conn, $timeStamp) . "',
                            '" . ebmrbpr_esc($conn, $yieldRecon) . "',
                            '" . ebmrbpr_esc($conn, $equipPoint) . "',
                            '" . ebmrbpr_esc($conn, ebmrbpr_in($r, 'instruction', '')) . "',
                            '" . ebmrbpr_esc($conn, ebmrbpr_in($r, 'remark', '')) . "',
                            '" . $emp_id . "'
                        )";
                    if (!$conn->query($sql)) { throw new Exception($conn->error); }
                }
                $conn->commit();
                ebmrbpr_ok(array('config_id' => $configId, 'count' => count($rows)));
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
            }
        }
    }
}
elseif ($type == "getConfigStageStep") {
    $configId = (int)($_GET['config_id'] ?? 0);
    $output = array();
    if ($configId > 0) {
        $where = "config_id=$configId";
        $scope = trim($_GET['scope'] ?? '');
        $productCode = trim($_GET['product_code'] ?? '');
        if ($productCode !== '') {
            $where .= " AND product_code='" . ebmrbpr_esc($conn, $productCode) . "'";
        } else {
            // Default / scope=config: config-level template rows only
            $where .= " AND (product_code IS NULL OR product_code='')";
        }
        $res = $conn->query("SELECT * FROM ebmrbpr_config_stage_step WHERE $where
                             ORDER BY stage_seq ASC, step_seq ASC, id ASC");
        if ($res) { while ($r = $res->fetch_assoc()) { $output[] = $r; } }
    }
    echo json_encode($output);
}
elseif ($type == "getConfigProductStageCounts") {
    // Per config + product: { "configId|productCode": { stage_count, step_count } }
    $output = array();
    $res = $conn->query("SELECT config_id, product_code,
                                COUNT(DISTINCT stage_name) AS stage_count,
                                SUM(CASE WHEN TRIM(IFNULL(step_name,'')) <> '' THEN 1 ELSE 0 END) AS step_count
                         FROM ebmrbpr_config_stage_step
                         WHERE product_code IS NOT NULL AND product_code <> ''
                         GROUP BY config_id, product_code");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $key = (string)$r['config_id'] . '|' . (string)$r['product_code'];
            $output[$key] = array(
                'stage_count' => (int)$r['stage_count'],
                'step_count' => (int)$r['step_count'],
            );
        }
    }
    echo json_encode($output);
}
elseif ($type == "getConfigStageStepCounts") {
    // Per-config summary { config_id: {stage_count, step_count} } for the configuration log.
    $output = array();
    $res = $conn->query("SELECT config_id,
                                COUNT(DISTINCT stage_name) AS stage_count,
                                SUM(CASE WHEN TRIM(IFNULL(step_name,'')) <> '' THEN 1 ELSE 0 END) AS step_count
                         FROM ebmrbpr_config_stage_step
                         WHERE product_code IS NULL OR product_code=''
                         GROUP BY config_id");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $output[(string)$r['config_id']] = array(
                'stage_count' => (int)$r['stage_count'],
                'step_count' => (int)$r['step_count'],
            );
        }
    }
    echo json_encode($output);
}
elseif ($type == "getConfig") {
    $id = (int)($_GET['id'] ?? 0);
    $res = $conn->query("SELECT * FROM ebmrbpr_config WHERE id=$id LIMIT 1");
    if (!$res || $res->num_rows == 0) { echo json_encode(array('ok' => false, 'status' => 'error', 'message' => 'Not found')); }
    else {
        $row = $res->fetch_assoc();
        $row['matrix'] = json_decode($row['matrix_json'], true);
        $row['stage_map'] = isset($row['stage_map_json']) ? json_decode($row['stage_map_json'], true) : array();
        $products = array();
        $pres = $conn->query("SELECT * FROM ebmrbpr_config_product WHERE config_id=$id AND status='active' ORDER BY id ASC");
        if ($pres) { while ($p = $pres->fetch_assoc()) { $products[] = $p; } }
        $row['products'] = $products;
        $row['ok'] = true;
        echo json_encode($row);
    }
}
elseif ($type == "saveConfig") {
    $bmr_no = ebmrbpr_next_code($conn, 'ebmrbpr_config', 'bmr_no', 'BMR');
    $matrix = ebmrbpr_in($input, 'matrix_json');
    $matrix = is_string($matrix) ? $matrix : json_encode($matrix);
    $sql = "INSERT INTO ebmrbpr_config (plant_id, bmr_no, dosage_form, process_type, title, matrix_json, status, prepared_emp, prepared_at, entry_by)
        VALUES (
            '" . $plant_id . "',
            '" . $bmr_no . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'process_type')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'title')) . "',
            '" . ebmrbpr_esc($conn, $matrix) . "',
            'Draft',
            '" . $emp_id . "',
            '" . $entry_date . "',
            '" . $emp_id . "'
        )";
    if ($conn->query($sql)) { ebmrbpr_ok(array('id' => $conn->insert_id, 'bmr_no' => $bmr_no)); }
    else { ebmrbpr_err($conn); }
}
elseif ($type == "updateConfig") {
    $id = (int)($_GET['id'] ?? 0);
    $matrix = ebmrbpr_in($input, 'matrix_json');
    $matrix = is_string($matrix) ? $matrix : json_encode($matrix);
    $sql = "UPDATE ebmrbpr_config SET
        dosage_form='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
        process_type='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'process_type')) . "',
        title='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'title')) . "',
        matrix_json='" . ebmrbpr_esc($conn, $matrix) . "'
        WHERE id=$id";
    if ($conn->query($sql)) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "deleteConfig") {
    $id = (int)($_GET['id'] ?? 0);
    $conn->query("DELETE FROM ebmrbpr_config WHERE id=$id");
    $conn->query("DELETE FROM ebmrbpr_config_product WHERE config_id=$id");
    ebmrbpr_ok();
}
elseif ($type == "configWorkflow") {
    $id = (int)ebmrbpr_in($input, 'id', 0);
    $action = ebmrbpr_in($input, 'action');
    $remark = ebmrbpr_esc($conn, ebmrbpr_in($input, 'remark'));
    $cur = $conn->query("SELECT status FROM ebmrbpr_config WHERE id=$id LIMIT 1");
    if (!$cur || $cur->num_rows == 0) { echo json_encode(array('status' => 'error', 'message' => 'Configuration not found')); }
    else {
        $status = $cur->fetch_assoc()['status'];
        $set = '';
        $newStatus = $status;
        if ($action == 'submit') {
            if ($status != 'Draft' && $status != 'Rejected') { echo json_encode(array('status' => 'error', 'message' => 'Only a Draft can be submitted for review')); $action = ''; }
            else { $newStatus = 'Under Review'; $set = "prepared_emp='" . $emp_id . "', prepared_at='" . $entry_date . "', prepared_remark='$remark'"; }
        } elseif ($action == 'review') {
            if ($status != 'Under Review') { echo json_encode(array('status' => 'error', 'message' => 'Only an item Under Review can be reviewed')); $action = ''; }
            else { $newStatus = 'Reviewed'; $set = "reviewed_emp='" . $emp_id . "', reviewed_at='" . $entry_date . "', reviewed_remark='$remark'"; }
        } elseif ($action == 'approve') {
            if ($status != 'Reviewed') { echo json_encode(array('status' => 'error', 'message' => 'Configuration must be Reviewed before approval')); $action = ''; }
            else { $newStatus = 'Approved'; $set = "approved_emp='" . $emp_id . "', approved_at='" . $entry_date . "', approved_remark='$remark'"; }
        } elseif ($action == 'reject') {
            $newStatus = 'Rejected'; $set = "approved_remark='$remark'";
        } else {
            if ($action !== '') { echo json_encode(array('status' => 'error', 'message' => 'Unknown action')); $action = ''; }
        }
        if ($action !== '') {
            if ($conn->query("UPDATE ebmrbpr_config SET status='$newStatus', $set WHERE id=$id")) {
                ebmrbpr_ok(array('new_status' => $newStatus));
            } else { ebmrbpr_err($conn); }
        }
    }
}
elseif ($type == "getConfigProducts") {
    $id = (int)($_GET['id'] ?? 0);
    $output = array();
    $res = $conn->query("SELECT * FROM ebmrbpr_config_product WHERE config_id=$id AND status='active' ORDER BY id ASC");
    if ($res) { while ($r = $res->fetch_assoc()) { $output[] = $r; } }
    echo json_encode($output);
}
elseif ($type == "bindConfigProduct") {
    $cid = (int)ebmrbpr_in($input, 'config_id', 0);
    $pcode = ebmrbpr_esc($conn, ebmrbpr_in($input, 'product_code'));
    $pname = ebmrbpr_esc($conn, ebmrbpr_in($input, 'product_name'));
    $ptype = ebmrbpr_esc($conn, ebmrbpr_in($input, 'product_type', 'Generic'));
    $info = $conn->query("SELECT bmr_no, dosage_form FROM ebmrbpr_config WHERE id=$cid LIMIT 1");
    $bmr_no = ''; $df = '';
    if ($info && $info->num_rows) { $r = $info->fetch_assoc(); $bmr_no = ebmrbpr_esc($conn, $r['bmr_no']); $df = ebmrbpr_esc($conn, $r['dosage_form']); }
    $chk = $conn->query("SELECT id FROM ebmrbpr_config_product WHERE config_id=$cid AND product_code='$pcode' AND status='active' LIMIT 1");
    if ($chk && $chk->num_rows > 0) { ebmrbpr_ok(array('message' => 'Already bound')); }
    else {
        $sql = "INSERT INTO ebmrbpr_config_product (plant_id, config_id, bmr_no, dosage_form, product_code, product_name, product_type, entry_by)
            VALUES ('" . $plant_id . "', $cid, '$bmr_no', '$df', '$pcode', '$pname', '$ptype', '" . $emp_id . "')";
        if ($conn->query($sql)) { ebmrbpr_ok(array('id' => $conn->insert_id)); } else { ebmrbpr_err($conn); }
    }
}
elseif ($type == "unbindConfigProduct") {
    $id = (int)($_GET['id'] ?? 0);
    $conn->query("UPDATE ebmrbpr_config_product SET status='deleted' WHERE id=$id");
    ebmrbpr_ok();
}
elseif ($type == "saveConfigStageMap") {
    // Map stages + their steps (from Stage/Step master) to a BMR configuration.
    $cid = (int)ebmrbpr_in($input, 'config_id', 0);
    $map = ebmrbpr_in($input, 'stage_map', array());
    $json = is_string($map) ? $map : json_encode($map);
    if ($conn->query("UPDATE ebmrbpr_config SET stage_map_json='" . ebmrbpr_esc($conn, $json) . "' WHERE id=$cid")) { ebmrbpr_ok(); }
    else { ebmrbpr_err($conn); }
}
elseif ($type == "getConfigStageMap") {
    $cid = (int)($_GET['id'] ?? 0);
    $res = $conn->query("SELECT stage_map_json FROM ebmrbpr_config WHERE id=$cid LIMIT 1");
    $map = array();
    if ($res && $res->num_rows) { $map = json_decode($res->fetch_assoc()['stage_map_json'], true); }
    echo json_encode(is_array($map) ? $map : array());
}

/* ===========================================================
   BMR MASTER PREPARATION  (config-driven profile + dept workflow)
=========================================================== */
elseif ($type == "getPrepConfigs") {
    // Configurations that are ready for / under BMR master preparation, with their linked profile state.
    $output = array();
    $where = "1=1";
    if (!empty($_GET['dosage_form'])) $where .= " AND dosage_form='" . ebmrbpr_esc($conn, $_GET['dosage_form']) . "'";
    $res = $conn->query("SELECT * FROM ebmrbpr_config WHERE $where ORDER BY id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['matrix'] = json_decode($row['matrix_json'], true);
            $sc = ebmrbpr_config_stage_counts($conn, (int)$row['id']);
            $row['stage_count'] = $sc['stage_count'];
            $row['step_count'] = $sc['step_count'];
            if ($row['stage_count'] === 0) {
                $map = isset($row['stage_map_json']) ? json_decode($row['stage_map_json'], true) : array();
                $row['stage_count'] = is_array($map) ? count($map) : 0;
            }
            $row['profile_id'] = 0;
            $row['profile_status'] = 'Not started';
            $row['bmr_for'] = 'Generic';
            $row['draft_product_code'] = '';
            $pr = $conn->query("SELECT id, status, header_json, prep_emp, prep_at, review_emp, review_at, prod_emp, prod_at, qa_emp, qa_at
                FROM ebmrbpr_profile WHERE config_id=" . (int)$row['id'] . " AND status<>'Deleted' ORDER BY id DESC LIMIT 1");
            if ($pr && $pr->num_rows) {
                $p = $pr->fetch_assoc();
                $row['profile_id'] = (int)$p['id'];
                $row['profile_status'] = $p['status'];
                $row['prep_emp'] = $p['prep_emp']; $row['prep_at'] = $p['prep_at'];
                $row['review_emp'] = $p['review_emp']; $row['review_at'] = $p['review_at'];
                $row['prod_emp'] = $p['prod_emp']; $row['prod_at'] = $p['prod_at'];
                $row['qa_emp'] = $p['qa_emp']; $row['qa_at'] = $p['qa_at'];
                $hdr = isset($p['header_json']) ? json_decode($p['header_json'], true) : array();
                if (is_array($hdr)) {
                    $row['bmr_for'] = !empty($hdr['bmr_for']) ? $hdr['bmr_for'] : 'Generic';
                    $row['draft_product_code'] = isset($hdr['product_code']) ? $hdr['product_code'] : '';
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "getProductForBmrPrep") {
    $code = trim($_GET['product_code'] ?? '');
    $prod = ebmrbpr_fetch_product_for_bmr($conn, $code);
    if (!$prod) {
        echo json_encode(array('status' => 'error', 'message' => 'Product not found'));
    } else {
        echo json_encode(array('status' => 'success', 'product' => $prod));
    }
}
elseif ($type == "getUnitFormulaMasterList") {
    $product_code = trim($_GET['product_code'] ?? '');
    if ($product_code === '') {
        echo json_encode(array('status' => 'success', 'formulas' => array(), 'message' => 'Bind a product to this BMR first.'));
    } else {
        $sql = "SELECT DISTINCT u.id, u.mfr_no, u.product_code, u.batch_size, u.unit, u.formula_for, u.version_no,
            (SELECT product_name FROM product p WHERE p.product_code=u.product_code LIMIT 1) AS product_name,
            (SELECT dosage_form FROM product p WHERE p.product_code=u.product_code LIMIT 1) AS dosage_form
            FROM unitformula u
            WHERE u.checked_by!='' AND u.approve_by!=''
            AND u.plant_id='" . ebmrbpr_esc($conn, $plant_id) . "'
            AND u.product_code='" . ebmrbpr_esc($conn, $product_code) . "'
            ORDER BY u.id DESC";
        $formulas = array();
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $formulas[] = array(
                    'id' => (int)$row['id'],
                    'mfr_no' => $row['mfr_no'],
                    'product_code' => $row['product_code'],
                    'product_name' => $row['product_name'],
                    'dosage_form' => $row['dosage_form'],
                    'batch_size' => $row['batch_size'],
                    'unit' => $row['unit'],
                    'formula_for' => $row['formula_for'],
                    'version_no' => $row['version_no'],
                );
            }
        }
        echo json_encode(array('status' => 'success', 'formulas' => $formulas, 'product_code' => $product_code));
    }
}
elseif ($type == "getUnitFormulaMasterRows") {
    $id = (int)($_GET['id'] ?? 0);
    $product_code = trim($_GET['product_code'] ?? '');
    if ($id <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Unit formula id required'));
    } elseif ($product_code === '') {
        echo json_encode(array('status' => 'error', 'message' => 'Bound product code is required'));
    } else {
        $res = $conn->query("SELECT u.id, u.mfr_no, u.product_code, u.batch_size, u.unit, u.raw_materials,
            (SELECT product_name FROM product p WHERE p.product_code=u.product_code LIMIT 1) AS product_name
            FROM unitformula u
            WHERE u.id=$id AND u.checked_by!='' AND u.approve_by!=''
            AND u.plant_id='" . ebmrbpr_esc($conn, $plant_id) . "'
            AND u.product_code='" . ebmrbpr_esc($conn, $product_code) . "'
            LIMIT 1");
        if (!$res || $res->num_rows == 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Approved unit formula not found for the bound product'));
        } else {
            $row = $res->fetch_assoc();
            $raw = json_decode($row['raw_materials'], true);
            if (!is_array($raw)) $raw = array();
            $rows = array();
            foreach ($raw as $m) {
                if (!is_array($m)) continue;
                $qty = '';
                if (isset($m['total_qty']) && $m['total_qty'] !== '') $qty = $m['total_qty'];
                elseif (isset($m['qty']) && $m['qty'] !== '') $qty = $m['qty'];
                $fn = '';
                if (!empty($m['role'])) $fn = $m['role'];
                elseif (!empty($m['process'])) $fn = $m['process'];
                elseif (!empty($m['process_step'])) $fn = $m['process_step'];
                elseif (!empty($m['material_subtype'])) $fn = $m['material_subtype'];
                elseif (!empty($m['stage'])) $fn = $m['stage'];
                $rows[] = array(
                    'material' => isset($m['material_name']) ? $m['material_name'] : '',
                    'material_code' => isset($m['material_code']) ? $m['material_code'] : '',
                    'qty' => (string)$qty,
                    'uom' => isset($m['unit']) ? $m['unit'] : '',
                    'function' => $fn,
                    'stage' => isset($m['stage']) ? (string)$m['stage'] : '',
                    'process' => isset($m['process']) ? (string)$m['process'] : (isset($m['process_step']) ? (string)$m['process_step'] : ''),
                    'process_step' => isset($m['process_step']) ? (string)$m['process_step'] : '',
                    'role' => isset($m['role']) ? (string)$m['role'] : '',
                );
            }
            echo json_encode(array(
                'status' => 'success',
                'formula' => array(
                    'id' => (int)$row['id'],
                    'mfr_no' => $row['mfr_no'],
                    'product_code' => $row['product_code'],
                    'product_name' => $row['product_name'],
                    'batch_size' => $row['batch_size'],
                    'unit' => $row['unit'],
                ),
                'rows' => $rows,
            ));
        }
    }
}
elseif ($type == "getProductSpecMasterList") {
    $product_code = trim($_GET['product_code'] ?? '');
    if ($product_code === '') {
        echo json_encode(array('status' => 'success', 'specifications' => array(), 'message' => 'Bind a product to this BMR first.'));
    } else {
        $sql = "SELECT s.id, s.specification_no, s.stpNo, s.version_no, s.product_code, s.grade, s.status,
            p.product_name, p.dosage_form
            FROM specification s
            LEFT JOIN product p ON s.product_code = p.product_code
            WHERE s.plant_id='" . ebmrbpr_esc($conn, $plant_id) . "'
            AND s.status='approve'
            AND (s.spec_type LIKE 'Finish Product%' OR s.spec_type LIKE 'Finish%')
            AND s.product_code='" . ebmrbpr_esc($conn, $product_code) . "'
            ORDER BY s.id DESC";
        $specifications = array();
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $specifications[] = array(
                    'id' => (int)$row['id'],
                    'specification_no' => $row['specification_no'],
                    'stpNo' => $row['stpNo'],
                    'version_no' => $row['version_no'],
                    'product_code' => $row['product_code'],
                    'product_name' => $row['product_name'],
                    'dosage_form' => $row['dosage_form'],
                    'grade' => $row['grade'],
                );
            }
        }
        echo json_encode(array('status' => 'success', 'specifications' => $specifications, 'product_code' => $product_code));
    }
}
elseif ($type == "getProductSpecMasterRows") {
    $id = (int)($_GET['id'] ?? 0);
    $product_code = trim($_GET['product_code'] ?? '');
    if ($id <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Specification id required'));
    } elseif ($product_code === '') {
        echo json_encode(array('status' => 'error', 'message' => 'Bound product code is required'));
    } else {
        $res = $conn->query("SELECT s.id, s.specification_no, s.stpNo, s.version_no, s.product_code, s.grade,
            (SELECT product_name FROM product p WHERE p.product_code=s.product_code LIMIT 1) AS product_name
            FROM specification s
            WHERE s.id=$id AND s.status='approve'
            AND (s.spec_type LIKE 'Finish Product%' OR s.spec_type LIKE 'Finish%')
            AND s.plant_id='" . ebmrbpr_esc($conn, $plant_id) . "'
            AND s.product_code='" . ebmrbpr_esc($conn, $product_code) . "'
            LIMIT 1");
        if (!$res || $res->num_rows == 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Approved product specification not found for the bound product'));
        } else {
            $row = $res->fetch_assoc();
            $specNo = ebmrbpr_esc($conn, $row['specification_no']);
            $rows = array();
            $tres = $conn->query("SELECT * FROM spec_tests WHERE specification_no='$specNo' ORDER BY id ASC");
            if ($tres && $tres->num_rows > 0) {
                while ($t = $tres->fetch_assoc()) {
                    $param = trim($t['test'] ?? '');
                    $sub = trim($t['subtest'] ?? '');
                    if ($sub !== '' && $sub !== '-') {
                        $param = $param . ' (' . $sub . ')';
                    }
                    $specTxt = trim($t['limits'] ?? '');
                    if ($specTxt === '') {
                        $ll = trim($t['lower_limit'] ?? '');
                        $ul = trim($t['upper_limit'] ?? '');
                        $unit = trim($t['unit'] ?? '');
                        $lt = trim($t['limit_type'] ?? '');
                        if ($lt === 'LessThan' && $ll !== '') {
                            $specTxt = 'NMT ' . $ll . ($unit !== '' ? ' ' . $unit : '');
                        } elseif ($lt === 'MoreThan' && $ul !== '') {
                            $specTxt = 'NLT ' . $ul . ($unit !== '' ? ' ' . $unit : '');
                        } elseif ($ll !== '' && $ul !== '') {
                            $specTxt = $ll . ' - ' . $ul . ($unit !== '' ? ' ' . $unit : '');
                        } elseif (trim($t['description'] ?? '') !== '') {
                            $specTxt = trim($t['description']);
                        } elseif (trim($t['compliances'] ?? '') !== '') {
                            $specTxt = trim($t['compliances']);
                        }
                    }
                    $method = trim($t['refTestMethod'] ?? '');
                    if ($method === '') {
                        $method = trim($t['reference_type'] ?? '');
                    }
                    $rows[] = array(
                        'parameter' => $param,
                        'specification' => $specTxt,
                        'method' => $method,
                        'test_id' => $t['id'],
                    );
                }
            }
            echo json_encode(array(
                'status' => 'success',
                'specification' => array(
                    'id' => (int)$row['id'],
                    'specification_no' => $row['specification_no'],
                    'stpNo' => $row['stpNo'],
                    'version_no' => $row['version_no'],
                    'product_code' => $row['product_code'],
                    'product_name' => $row['product_name'],
                    'grade' => $row['grade'],
                ),
                'rows' => $rows,
            ));
        }
    }
}
elseif ($type == "getStoreDispensingMaster") {
    $product_code = trim($_GET['product_code'] ?? '');
    $headings = array(
        'Warehouse Dispensing(Line clearance Instruction)',
        'Dispensing Checklist',
    );
    $sections = array();
    $pid = ebmrbpr_esc($conn, $plant_id);
    $pc = ebmrbpr_esc($conn, $product_code);

    foreach ($headings as $heading) {
        $rows = array();
        if ($product_code !== '') {
            if ($heading === 'Dispensing Checklist') {
                $res = $conn->query("SELECT checkpoint AS check_point, remark AS evaluation_parameter
                    FROM bmr_dispensing_checklist
                    WHERE plant_id='$pid' AND product_code='$pc'
                    ORDER BY id");
                if ($res && $res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()) {
                        $rows[] = array(
                            'check_point' => $row['check_point'],
                            'description' => '',
                            'evaluation_parameter' => $row['evaluation_parameter'],
                        );
                    }
                }
            } elseif ($heading === 'Warehouse Dispensing(Line clearance Instruction)') {
                $res = $conn->query("SELECT checkpoint AS check_point, remark AS evaluation_parameter
                    FROM bmr_warehouse_dispensing_checklist
                    WHERE plant_id='$pid' AND product_code='$pc'
                    ORDER BY id");
                if ($res && $res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()) {
                        $rows[] = array(
                            'check_point' => $row['check_point'],
                            'description' => '',
                            'evaluation_parameter' => $row['evaluation_parameter'],
                        );
                    }
                }
            }
        }
        if (empty($rows)) {
            $h = ebmrbpr_esc($conn, $heading);
            $res = $conn->query("SELECT check_point, description, evaluation_parameter
                FROM bmr_checklist
                WHERE checklist_heading='$h'
                AND (plant_id='$pid' OR plant_id IS NULL OR plant_id='')
                ORDER BY id");
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $rows[] = array(
                        'check_point' => $row['check_point'],
                        'description' => $row['description'],
                        'evaluation_parameter' => $row['evaluation_parameter'],
                    );
                }
            }
        }
        if (!empty($rows)) {
            $sections[] = array('heading' => $heading, 'rows' => $rows);
        }
    }
    echo json_encode(array(
        'status' => 'success',
        'sections' => $sections,
        'product_code' => $product_code,
        'source' => 'store_dispensing_checklist',
    ));
}
elseif ($type == "getGeneralInstructionsMaster") {
    $pid = ebmrbpr_esc($conn, $plant_id);
    $heading = ebmrbpr_esc($conn, 'General Instructions');
    $rows = array();
    $res = $conn->query("SELECT check_point, description, evaluation_parameter
        FROM bmr_checklist
        WHERE checklist_heading='$heading'
        AND (plant_id='$pid' OR plant_id IS NULL OR plant_id='')
        ORDER BY id");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = array(
                'check_point' => $row['check_point'],
                'description' => $row['description'],
                'evaluation_parameter' => $row['evaluation_parameter'],
            );
        }
    }
    echo json_encode(array(
        'status' => 'success',
        'rows' => $rows,
        'source' => 'bmr_checklist',
    ));
}
elseif ($type == "proceedBmrPrep") {
    $cid = (int)ebmrbpr_in($input, 'config_id', 0);
    $bmrFor = ebmrbpr_in($input, 'bmr_for', 'Generic');
    $bmrFor = ($bmrFor === 'Product') ? 'Product' : 'Generic';
    $productCode = trim((string)ebmrbpr_in($input, 'product_code', ''));

    if ($bmrFor === 'Product' && $productCode === '') {
        echo json_encode(array('status' => 'error', 'message' => 'Select a product for Product-specific BMR.'));
    } else {
        $cfgRes = $conn->query("SELECT * FROM ebmrbpr_config WHERE id=$cid LIMIT 1");
        if (!$cfgRes || $cfgRes->num_rows == 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Configuration not found'));
        } else {
            $cfg = $cfgRes->fetch_assoc();
            $ex = $conn->query("SELECT id FROM ebmrbpr_profile WHERE config_id=$cid AND status<>'Deleted' ORDER BY id DESC LIMIT 1");
            if ($ex && $ex->num_rows) {
                ebmrbpr_ok(array('profile_id' => (int)$ex->fetch_assoc()['id'], 'created' => false));
            } elseif ($bmrFor === 'Product' && !ebmrbpr_fetch_product_for_bmr($conn, $productCode)) {
                echo json_encode(array('status' => 'error', 'message' => 'Product not found in master.'));
            } else {
                $rt = (stripos($cfg['process_type'], 'pack') !== false) ? 'eBPR' : 'eBMR';
                $pname = ($cfg['title'] !== '' && $cfg['title'] !== null) ? $cfg['title'] : ($cfg['bmr_no'] . ' — ' . $cfg['dosage_form']);
                $headerArr = ebmrbpr_build_prep_header($conn, $cfg, $bmrFor, $productCode);
                if ($bmrFor === 'Product' && !empty($headerArr['product_name'])) {
                    $pname = $headerArr['product_name'] . ' — ' . $cfg['bmr_no'];
                }
                $code = ebmrbpr_next_code($conn, 'ebmrbpr_profile', 'profile_code', 'EBMR');
                $header = json_encode($headerArr);
                $conn->query("INSERT INTO ebmrbpr_profile (plant_id, profile_code, profile_name, record_type, dosage_form, version, header_json, static_json, right_tabs_json, status, config_id, prep_emp, prep_at, entry_by)
                    VALUES ('" . $plant_id . "', '$code', '" . ebmrbpr_esc($conn, $pname) . "', '$rt', '" . ebmrbpr_esc($conn, $cfg['dosage_form']) . "', '1.0',
                    '" . ebmrbpr_esc($conn, $header) . "', '', '', 'Draft', $cid, '" . $emp_id . "', '" . $entry_date . "', '" . $emp_id . "')");
                $pid = $conn->insert_id;
                $seeded = ebmrbpr_seed_profile_stages($conn, $pid, $cid, $cfg['stage_map_json']);
                if ($seeded <= 0) {
                    $conn->query("DELETE FROM ebmrbpr_profile WHERE id=$pid");
                    echo json_encode(array('status' => 'error', 'message' => 'Configure at least one stage with steps before starting BMR preparation.'));
                } else {
                    if ($bmrFor === 'Product' && $productCode !== '') {
                        $pnameEsc = ebmrbpr_esc($conn, isset($headerArr['product_name']) ? $headerArr['product_name'] : '');
                        $conn->query("INSERT INTO ebmrbpr_profile_product (profile_id, plant_id, product_code, product_name, entry_by)
                            VALUES ($pid, '" . $plant_id . "', '" . ebmrbpr_esc($conn, $productCode) . "', '$pnameEsc', '" . $emp_id . "')");
                    }
                    ebmrbpr_ok(array('profile_id' => $pid, 'created' => true, 'stage_count' => $seeded, 'bmr_for' => $bmrFor));
                }
            }
        }
    }
}
elseif ($type == "profileWorkflow") {
    $pid = (int)ebmrbpr_in($input, 'profile_id', 0);
    $action = ebmrbpr_in($input, 'action');
    $remark = ebmrbpr_esc($conn, ebmrbpr_in($input, 'remark'));
    $cur = $conn->query("SELECT status FROM ebmrbpr_profile WHERE id=$pid LIMIT 1");
    if (!$cur || $cur->num_rows == 0) { echo json_encode(array('status' => 'error', 'message' => 'BMR not found')); }
    else {
        $status = $cur->fetch_assoc()['status'];
        $set = ''; $newStatus = $status; $ok = true;
        if ($action == 'submit_review') {
            if (!in_array($status, array('Draft', 'Rejected'))) { $ok = false; echo json_encode(array('status' => 'error', 'message' => 'Only a Draft BMR can be sent for review')); }
            else { $newStatus = 'Under Review'; $set = "prep_emp='" . $emp_id . "', prep_at='" . $entry_date . "'"; }
        } elseif ($action == 'review') {
            if ($status != 'Under Review') { $ok = false; echo json_encode(array('status' => 'error', 'message' => 'BMR must be Under Review')); }
            else { $newStatus = 'Reviewed'; $set = "review_emp='" . $emp_id . "', review_at='" . $entry_date . "', review_remark='$remark'"; }
        } elseif ($action == 'prod_approve') {
            if ($status != 'Reviewed') { $ok = false; echo json_encode(array('status' => 'error', 'message' => 'BMR must be Reviewed before Production approval')); }
            else { $newStatus = 'Production Approved'; $set = "prod_emp='" . $emp_id . "', prod_at='" . $entry_date . "', prod_remark='$remark'"; }
        } elseif ($action == 'qa_approve') {
            if ($status != 'Production Approved') { $ok = false; echo json_encode(array('status' => 'error', 'message' => 'Production approval is required before QA approval')); }
            else { $newStatus = 'Approved'; $set = "qa_emp='" . $emp_id . "', qa_at='" . $entry_date . "', qa_remark='$remark'"; }
        } elseif ($action == 'reject') {
            $newStatus = 'Rejected'; $set = "review_remark='$remark'";
        } else { $ok = false; echo json_encode(array('status' => 'error', 'message' => 'Unknown action')); }
        if ($ok) {
            if ($conn->query("UPDATE ebmrbpr_profile SET status='$newStatus', $set, updated_by='" . $emp_id . "', updated_date='" . $entry_date . "' WHERE id=$pid")) {
                ebmrbpr_ok(array('new_status' => $newStatus));
            } else { ebmrbpr_err($conn); }
        }
    }
}

/* ===========================================================
   LOOKUPS
=========================================================== */
elseif ($type == "getDosageForms") {
    // Pull distinct dosage forms from product / FG master, plus standard FG + injectable set.
    $forms = array(
        'Liquid Injection', 'Lyophilised Injection', 'Dry Powder Injection', 'Infusion', 'Ophthalmic',
        'Tablet', 'Capsule', 'Hard Gelatin Capsule', 'Soft Gelatin Capsule', 'Granules',
        'Powder for Oral Suspension', 'Effervescent Tablet', 'Chewable Tablet', 'Dispersible Tablet',
        'Sublingual Tablet', 'Orally Disintegrating Tablet (ODT)', 'Modified Release Tablet',
        'Enteric Coated Tablet', 'Pellets', 'Sachet (Oral Powder)', 'Lozenge', 'Vaginal Tablet', 'Dental Tablet',
        'Syrup', 'Oral Solution', 'Oral Suspension', 'Oral Liquid', 'Drops (Oral)', 'Elixir', 'Mixture',
        'Oral Rehydration Solution', 'Gargle', 'Mouth Wash', 'Enema (Liquid)',
        'Cream', 'Ointment', 'Ointment / Cream', 'Gel', 'Lotion', 'Paste', 'Suppository', 'Pessary',
        'Transdermal Patch', 'Liniment', 'Emulsion',
        'Metered Dose Inhaler (MDI)', 'Dry Powder Inhaler (DPI)', 'Nasal Spray', 'Aerosol',
        'Gas for Inhalation', 'Nebulizer Solution',
    );
    $queries = array(
        "SELECT DISTINCT dosage_form AS df FROM product WHERE dosage_form IS NOT NULL AND dosage_form <> ''",
        "SELECT DISTINCT dosage_form_type AS df FROM master_fg_types WHERE dosage_form_type IS NOT NULL AND dosage_form_type <> ''",
        "SELECT DISTINCT dosage_form AS df FROM ebmrbpr_config WHERE dosage_form IS NOT NULL AND dosage_form <> ''",
    );
    foreach ($queries as $q) {
        $res = @$conn->query($q);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $df = trim((string)($r['df'] ?? ''));
                if ($df !== '' && !in_array($df, $forms, true)) {
                    $forms[] = $df;
                }
            }
        }
    }
    sort($forms, SORT_NATURAL | SORT_FLAG_CASE);
    echo json_encode($forms);
}

/* ===========================================================
   STAGE MASTER
=========================================================== */
elseif ($type == "getStages") {
    $output = array();
    $where = "status='pending'";
    if (!empty($_GET['dosage_form'])) {
        $where .= " AND dosage_form='" . ebmrbpr_esc($conn, $_GET['dosage_form']) . "'";
    }
    $res = $conn->query("SELECT * FROM ebmrbpr_stage WHERE $where ORDER BY seq_no ASC, id ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "saveStage") {
    $code = ebmrbpr_next_code($conn, 'ebmrbpr_stage', 'stage_code', 'STG');
    $sql = "INSERT INTO ebmrbpr_stage (plant_id, stage_code, stage_name, dosage_form, seq_no, description, entry_by)
            VALUES (
                '" . $plant_id . "',
                '" . $code . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_name')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
                '" . (int)ebmrbpr_in($input, 'seq_no', 0) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'description')) . "',
                '" . $emp_id . "'
            )";
    if ($conn->query($sql)) {
        ebmrbpr_ok(array('id' => $conn->insert_id, 'stage_code' => $code));
    } else {
        ebmrbpr_err($conn);
    }
}
elseif ($type == "updateStage") {
    $id = (int)($_GET['id'] ?? 0);
    $sql = "UPDATE ebmrbpr_stage SET
                stage_name='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_name')) . "',
                dosage_form='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
                seq_no='" . (int)ebmrbpr_in($input, 'seq_no', 0) . "',
                description='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'description')) . "'
            WHERE id=$id";
    if ($conn->query($sql)) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "deleteStage") {
    $id = (int)($_GET['id'] ?? 0);
    if ($conn->query("UPDATE ebmrbpr_stage SET status='Deleted' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}

/* ===========================================================
   STEP MASTER
=========================================================== */
elseif ($type == "getSteps") {
    $output = array();
    $where = "s.status='pending'";
    if (!empty($_GET['stage_id'])) {
        $where .= " AND s.stage_id=" . (int)$_GET['stage_id'];
    }
    $res = $conn->query("SELECT s.*, st.stage_name FROM ebmrbpr_step s
        LEFT JOIN ebmrbpr_stage st ON st.id = s.stage_id
        WHERE $where ORDER BY s.seq_no ASC, s.id ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "saveStep") {
    $code = ebmrbpr_next_code($conn, 'ebmrbpr_step', 'step_code', 'STP');
    $sql = "INSERT INTO ebmrbpr_step (plant_id, stage_id, step_code, step_name, seq_no, description, entry_by)
            VALUES (
                '" . $plant_id . "',
                '" . (int)ebmrbpr_in($input, 'stage_id', 0) . "',
                '" . $code . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'step_name')) . "',
                '" . (int)ebmrbpr_in($input, 'seq_no', 0) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'description')) . "',
                '" . $emp_id . "'
            )";
    if ($conn->query($sql)) { ebmrbpr_ok(array('id' => $conn->insert_id, 'step_code' => $code)); } else { ebmrbpr_err($conn); }
}
elseif ($type == "updateStep") {
    $id = (int)($_GET['id'] ?? 0);
    $sql = "UPDATE ebmrbpr_step SET
                stage_id='" . (int)ebmrbpr_in($input, 'stage_id', 0) . "',
                step_name='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'step_name')) . "',
                seq_no='" . (int)ebmrbpr_in($input, 'seq_no', 0) . "',
                description='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'description')) . "'
            WHERE id=$id";
    if ($conn->query($sql)) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "deleteStep") {
    $id = (int)($_GET['id'] ?? 0);
    if ($conn->query("UPDATE ebmrbpr_step SET status='Deleted' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}

/* ===========================================================
   IN-PROCESS CHECK MASTER (dynamic variable limits)
=========================================================== */
elseif ($type == "getInprocessChecks") {
    $output = array();
    $where = "status='pending'";
    if (!empty($_GET['dosage_form'])) {
        $where .= " AND dosage_form='" . ebmrbpr_esc($conn, $_GET['dosage_form']) . "'";
    }
    $res = $conn->query("SELECT * FROM ebmrbpr_inprocess_check WHERE $where ORDER BY id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "saveInprocessCheck") {
    $code = ebmrbpr_next_code($conn, 'ebmrbpr_inprocess_check', 'check_code', 'IPC');
    $options = ebmrbpr_in($input, 'options_json', '');
    if (is_array($options)) { $options = json_encode($options); }
    $sql = "INSERT INTO ebmrbpr_inprocess_check
        (plant_id, check_code, check_name, dosage_form, stage_ref, check_type, uom, target_value, min_limit, max_limit,
         tolerance, options_json, frequency, sampling_plan, instrument, acceptance_criteria, is_critical, responsibility, remarks, entry_by)
        VALUES (
            '" . $plant_id . "',
            '" . $code . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'check_name')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_ref')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'check_type', 'numeric')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'uom')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'target_value')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'min_limit')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'max_limit')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'tolerance')) . "',
            '" . ebmrbpr_esc($conn, $options) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'frequency')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'sampling_plan')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'instrument')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'acceptance_criteria')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'is_critical', 'No')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_normalize_ipc_role(ebmrbpr_in($input, 'responsibility', 'Production'))) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "',
            '" . $emp_id . "'
        )";
    if ($conn->query($sql)) { ebmrbpr_ok(array('id' => $conn->insert_id, 'check_code' => $code)); } else { ebmrbpr_err($conn); }
}
elseif ($type == "updateInprocessCheck") {
    $id = (int)($_GET['id'] ?? 0);
    $options = ebmrbpr_in($input, 'options_json', '');
    if (is_array($options)) { $options = json_encode($options); }
    $sql = "UPDATE ebmrbpr_inprocess_check SET
                check_name='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'check_name')) . "',
                dosage_form='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
                stage_ref='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_ref')) . "',
                check_type='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'check_type', 'numeric')) . "',
                uom='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'uom')) . "',
                target_value='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'target_value')) . "',
                min_limit='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'min_limit')) . "',
                max_limit='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'max_limit')) . "',
                tolerance='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'tolerance')) . "',
                options_json='" . ebmrbpr_esc($conn, $options) . "',
                frequency='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'frequency')) . "',
                sampling_plan='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'sampling_plan')) . "',
                instrument='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'instrument')) . "',
                acceptance_criteria='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'acceptance_criteria')) . "',
                is_critical='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'is_critical', 'No')) . "',
                responsibility='" . ebmrbpr_esc($conn, ebmrbpr_normalize_ipc_role(ebmrbpr_in($input, 'responsibility', 'Production'))) . "',
                remarks='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "'
            WHERE id=$id";
    if ($conn->query($sql)) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "deleteInprocessCheck") {
    $id = (int)($_GET['id'] ?? 0);
    if ($conn->query("UPDATE ebmrbpr_inprocess_check SET status='Deleted' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}

/* ===========================================================
   CHECKPOINTS (line_clearance | department | qa)
=========================================================== */
elseif ($type == "getCheckpoints") {
    $output = array();
    $where = "status='pending'";
    if (!empty($_GET['category'])) {
        $where .= " AND category='" . ebmrbpr_esc($conn, $_GET['category']) . "'";
    }
    if (!empty($_GET['dosage_form'])) {
        $where .= " AND dosage_form='" . ebmrbpr_esc($conn, $_GET['dosage_form']) . "'";
    }
    $res = $conn->query("SELECT * FROM ebmrbpr_checkpoint WHERE $where ORDER BY seq_no ASC, id ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "saveCheckpoint") {
    $code = ebmrbpr_next_code($conn, 'ebmrbpr_checkpoint', 'checkpoint_code', 'CP');
    $sql = "INSERT INTO ebmrbpr_checkpoint
        (plant_id, checkpoint_code, category, checkpoint_text, dosage_form, stage_ref, expected_response, responsibility, seq_no, is_critical, remarks, entry_by)
        VALUES (
            '" . $plant_id . "',
            '" . $code . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'category', 'line_clearance')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'checkpoint_text')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_ref')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'expected_response', 'Yes/No')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'responsibility')) . "',
            '" . (int)ebmrbpr_in($input, 'seq_no', 0) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'is_critical', 'No')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "',
            '" . $emp_id . "'
        )";
    if ($conn->query($sql)) { ebmrbpr_ok(array('id' => $conn->insert_id, 'checkpoint_code' => $code)); } else { ebmrbpr_err($conn); }
}
elseif ($type == "updateCheckpoint") {
    $id = (int)($_GET['id'] ?? 0);
    $sql = "UPDATE ebmrbpr_checkpoint SET
                category='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'category', 'line_clearance')) . "',
                checkpoint_text='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'checkpoint_text')) . "',
                dosage_form='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
                stage_ref='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_ref')) . "',
                expected_response='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'expected_response', 'Yes/No')) . "',
                responsibility='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'responsibility')) . "',
                seq_no='" . (int)ebmrbpr_in($input, 'seq_no', 0) . "',
                is_critical='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'is_critical', 'No')) . "',
                remarks='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "'
            WHERE id=$id";
    if ($conn->query($sql)) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "deleteCheckpoint") {
    $id = (int)($_GET['id'] ?? 0);
    if ($conn->query("UPDATE ebmrbpr_checkpoint SET status='Deleted' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}

/* ===========================================================
   PROCEDURE MASTER  (hierarchical paragraphs 1.0 / 1.1.0 / 1.1.1.0)
=========================================================== */
elseif ($type == "getProcedures") {
    $output = array();
    $where = "status='pending'";
    if (!empty($_GET['dosage_form'])) {
        $where .= " AND dosage_form='" . ebmrbpr_esc($conn, $_GET['dosage_form']) . "'";
    }
    if (!empty($_GET['id'])) {
        $where .= " AND id=" . (int)$_GET['id'];
    }
    $res = $conn->query("SELECT * FROM ebmrbpr_procedure WHERE $where ORDER BY id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['paragraphs'] = json_decode($row['paragraphs_json'], true);
            if (!is_array($row['paragraphs'])) { $row['paragraphs'] = array(); }
            unset($row['paragraphs_json']);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "getProcedure") {
    $id = (int)($_GET['id'] ?? 0);
    $res = $conn->query("SELECT * FROM ebmrbpr_procedure WHERE id=$id AND status='pending' LIMIT 1");
    if (!$res || $res->num_rows == 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Procedure not found'));
    } else {
        $row = $res->fetch_assoc();
        $row['paragraphs'] = json_decode($row['paragraphs_json'], true);
        if (!is_array($row['paragraphs'])) { $row['paragraphs'] = array(); }
        unset($row['paragraphs_json']);
        echo json_encode(array('status' => 'success', 'procedure' => $row));
    }
}
elseif ($type == "saveProcedure") {
    $paragraphs = ebmrbpr_in($input, 'paragraphs', array());
    if (is_string($paragraphs)) { $paragraphs = json_decode($paragraphs, true); }
    if (!is_array($paragraphs)) { $paragraphs = array(); }
    $code = ebmrbpr_next_code($conn, 'ebmrbpr_procedure', 'procedure_code', 'PROC');
    $sql = "INSERT INTO ebmrbpr_procedure
        (plant_id, procedure_code, title, dosage_form, stage_ref, paragraphs_json, remarks, entry_by)
        VALUES (
            '" . $plant_id . "',
            '" . $code . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'title')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_ref')) . "',
            '" . ebmrbpr_esc($conn, json_encode($paragraphs)) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "',
            '" . $emp_id . "'
        )";
    if ($conn->query($sql)) { ebmrbpr_ok(array('id' => $conn->insert_id, 'procedure_code' => $code)); } else { ebmrbpr_err($conn); }
}
elseif ($type == "updateProcedure") {
    $id = (int)($_GET['id'] ?? 0);
    $paragraphs = ebmrbpr_in($input, 'paragraphs', array());
    if (is_string($paragraphs)) { $paragraphs = json_decode($paragraphs, true); }
    if (!is_array($paragraphs)) { $paragraphs = array(); }
    $sql = "UPDATE ebmrbpr_procedure SET
                title='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'title')) . "',
                dosage_form='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
                stage_ref='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_ref')) . "',
                paragraphs_json='" . ebmrbpr_esc($conn, json_encode($paragraphs)) . "',
                remarks='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "'
            WHERE id=$id";
    if ($conn->query($sql)) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "deleteProcedure") {
    $id = (int)($_GET['id'] ?? 0);
    if ($conn->query("UPDATE ebmrbpr_procedure SET status='Deleted' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}

/* ===========================================================
   FORM TABLE MASTER  (yield / weighing — dynamic columns & rows)
=========================================================== */
elseif ($type == "getFormTables") {
    $output = array();
    $tableType = ebmrbpr_esc($conn, $_GET['table_type'] ?? '');
    $where = "status='pending'";
    if ($tableType !== '') {
        $where .= " AND table_type='" . $tableType . "'";
    }
    if (!empty($_GET['dosage_form'])) {
        $where .= " AND dosage_form='" . ebmrbpr_esc($conn, $_GET['dosage_form']) . "'";
    }
    if (!empty($_GET['id'])) {
        $where .= " AND id=" . (int)$_GET['id'];
    }
    $res = $conn->query("SELECT * FROM ebmrbpr_form_table WHERE $where ORDER BY id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['columns'] = json_decode($row['columns_json'], true);
            $row['rows'] = json_decode($row['rows_json'], true);
            if (!is_array($row['columns'])) { $row['columns'] = array(); }
            if (!is_array($row['rows'])) { $row['rows'] = array(); }
            unset($row['columns_json'], $row['rows_json']);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "getFormTable") {
    $id = (int)($_GET['id'] ?? 0);
    $res = $conn->query("SELECT * FROM ebmrbpr_form_table WHERE id=$id AND status='pending' LIMIT 1");
    if (!$res || $res->num_rows == 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Table master not found'));
    } else {
        $row = $res->fetch_assoc();
        $row['columns'] = json_decode($row['columns_json'], true);
        $row['rows'] = json_decode($row['rows_json'], true);
        if (!is_array($row['columns'])) { $row['columns'] = array(); }
        if (!is_array($row['rows'])) { $row['rows'] = array(); }
        unset($row['columns_json'], $row['rows_json']);
        echo json_encode(array('status' => 'success', 'table' => $row));
    }
}
elseif ($type == "saveFormTable") {
    $tableType = ebmrbpr_esc($conn, ebmrbpr_in($input, 'table_type'));
    if ($tableType === '') {
        echo json_encode(array('status' => 'error', 'message' => 'table_type is required'));
        exit;
    }
    $prefix = ($tableType === 'weighing') ? 'WGT' : 'YLD';
    $columns = ebmrbpr_in($input, 'columns', array());
    $rows = ebmrbpr_in($input, 'rows', array());
    if (is_string($columns)) { $columns = json_decode($columns, true); }
    if (is_string($rows)) { $rows = json_decode($rows, true); }
    if (!is_array($columns)) { $columns = array(); }
    if (!is_array($rows)) { $rows = array(); }
    $code = ebmrbpr_next_code($conn, 'ebmrbpr_form_table', 'table_code', $prefix);
    $sql = "INSERT INTO ebmrbpr_form_table
        (plant_id, table_code, table_type, title, dosage_form, stage_ref, columns_json, rows_json, remarks, entry_by)
        VALUES (
            '" . $plant_id . "',
            '" . $code . "',
            '" . $tableType . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'title')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_ref')) . "',
            '" . ebmrbpr_esc($conn, json_encode($columns)) . "',
            '" . ebmrbpr_esc($conn, json_encode($rows)) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "',
            '" . $emp_id . "'
        )";
    if ($conn->query($sql)) { ebmrbpr_ok(array('id' => $conn->insert_id, 'table_code' => $code)); } else { ebmrbpr_err($conn); }
}
elseif ($type == "updateFormTable") {
    $id = (int)($_GET['id'] ?? 0);
    $columns = ebmrbpr_in($input, 'columns', array());
    $rows = ebmrbpr_in($input, 'rows', array());
    if (is_string($columns)) { $columns = json_decode($columns, true); }
    if (is_string($rows)) { $rows = json_decode($rows, true); }
    if (!is_array($columns)) { $columns = array(); }
    if (!is_array($rows)) { $rows = array(); }
    $sql = "UPDATE ebmrbpr_form_table SET
                title='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'title')) . "',
                dosage_form='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
                stage_ref='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_ref')) . "',
                columns_json='" . ebmrbpr_esc($conn, json_encode($columns)) . "',
                rows_json='" . ebmrbpr_esc($conn, json_encode($rows)) . "',
                remarks='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "'
            WHERE id=$id";
    if ($conn->query($sql)) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "deleteFormTable") {
    $id = (int)($_GET['id'] ?? 0);
    if ($conn->query("UPDATE ebmrbpr_form_table SET status='Deleted' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}

/* ===========================================================
   IPQC ANALYSIS SPECIFICATION
=========================================================== */
elseif ($type == "getIpqcSpecs") {
    $output = array();
    $where = "status='pending'";
    if (!empty($_GET['dosage_form'])) {
        $where .= " AND dosage_form='" . ebmrbpr_esc($conn, $_GET['dosage_form']) . "'";
    }
    $res = $conn->query("SELECT * FROM ebmrbpr_ipqc_spec WHERE $where ORDER BY id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "saveIpqcSpec") {
    $code = ebmrbpr_next_code($conn, 'ebmrbpr_ipqc_spec', 'spec_code', 'ISP');
    $sql = "INSERT INTO ebmrbpr_ipqc_spec
        (plant_id, spec_code, spec_name, dosage_form, stage_ref, parameter, test_method, specification, uom, min_limit, max_limit, frequency, is_critical, remarks, entry_by)
        VALUES (
            '" . $plant_id . "',
            '" . $code . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'spec_name')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_ref')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'parameter')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'test_method')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'specification')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'uom')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'min_limit')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'max_limit')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'frequency')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'is_critical', 'No')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "',
            '" . $emp_id . "'
        )";
    if ($conn->query($sql)) { ebmrbpr_ok(array('id' => $conn->insert_id, 'spec_code' => $code)); } else { ebmrbpr_err($conn); }
}
elseif ($type == "updateIpqcSpec") {
    $id = (int)($_GET['id'] ?? 0);
    $sql = "UPDATE ebmrbpr_ipqc_spec SET
                spec_name='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'spec_name')) . "',
                dosage_form='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
                stage_ref='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_ref')) . "',
                parameter='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'parameter')) . "',
                test_method='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'test_method')) . "',
                specification='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'specification')) . "',
                uom='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'uom')) . "',
                min_limit='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'min_limit')) . "',
                max_limit='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'max_limit')) . "',
                frequency='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'frequency')) . "',
                is_critical='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'is_critical', 'No')) . "',
                remarks='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "'
            WHERE id=$id";
    if ($conn->query($sql)) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "deleteIpqcSpec") {
    $id = (int)($_GET['id'] ?? 0);
    if ($conn->query("UPDATE ebmrbpr_ipqc_spec SET status='Deleted' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}

/* ===========================================================
   PROCESS WORK ALLOCATION
=========================================================== */
elseif ($type == "getWorkAllocations") {
    $output = array();
    $where = "status='pending'";
    if (!empty($_GET['dosage_form'])) {
        $where .= " AND dosage_form='" . ebmrbpr_esc($conn, $_GET['dosage_form']) . "'";
    }
    $res = $conn->query("SELECT * FROM ebmrbpr_work_allocation WHERE $where ORDER BY id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "saveWorkAllocation") {
    $code = ebmrbpr_next_code($conn, 'ebmrbpr_work_allocation', 'alloc_code', 'WA');
    $sql = "INSERT INTO ebmrbpr_work_allocation
        (plant_id, alloc_code, dosage_form, stage_ref, activity, designation, responsibility, manpower_count, skill_level, remarks, entry_by)
        VALUES (
            '" . $plant_id . "',
            '" . $code . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_ref')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'activity')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'designation')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'responsibility')) . "',
            '" . (int)ebmrbpr_in($input, 'manpower_count', 1) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'skill_level')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "',
            '" . $emp_id . "'
        )";
    if ($conn->query($sql)) { ebmrbpr_ok(array('id' => $conn->insert_id, 'alloc_code' => $code)); } else { ebmrbpr_err($conn); }
}
elseif ($type == "updateWorkAllocation") {
    $id = (int)($_GET['id'] ?? 0);
    $sql = "UPDATE ebmrbpr_work_allocation SET
                dosage_form='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
                stage_ref='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_ref')) . "',
                activity='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'activity')) . "',
                designation='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'designation')) . "',
                responsibility='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'responsibility')) . "',
                manpower_count='" . (int)ebmrbpr_in($input, 'manpower_count', 1) . "',
                skill_level='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'skill_level')) . "',
                remarks='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "'
            WHERE id=$id";
    if ($conn->query($sql)) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "deleteWorkAllocation") {
    $id = (int)($_GET['id'] ?? 0);
    if ($conn->query("UPDATE ebmrbpr_work_allocation SET status='Deleted' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}

/* ===========================================================
   BATCH WORK ALLOCATION (planning WO → stage personnel → execution)
=========================================================== */
elseif ($type == "getDispensingPipelineLog") {
    $output = array();
    $sql = "SELECT a.id AS work_order_id, a.batch_number, a.work_order_no, a.status AS wo_status,
            a.dispensing_status, a.dispense_request_sent_by, a.dispense_request_sent_on,
            a.rm_disp_completed_by, a.rm_disp_completed_date, a.rm_received_by, a.rm_received_date,
            a.rm_receiving_status, a.qa_person,
            b.id AS batch_plan_id, b.plan_no, b.batch_size, b.product_code, p.product_name
            FROM mfg_work_order_hdr a
            JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
            JOIN product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
            WHERE a.plant_id = '" . $plant_id . "'
              AND a.status = 'approved'
              AND a.batch_number IS NOT NULL AND a.batch_number <> ''
              AND a.qa_person IS NOT NULL AND a.qa_person <> ''
              AND a.material_type = 'RM'
            ORDER BY a.id DESC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['dispensing_pipeline_status'] = ebmrbpr_dispensing_pipeline_status($row);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "getBatchWorkAllocLog") {
    $output = array();
    $sql = "SELECT a.id AS work_order_id, a.batch_number, a.work_order_no, a.status AS wo_status,
            b.id AS batch_plan_id, b.plan_no, b.batch_size, b.product_code, p.product_name, p.dosage_form,
            h.id AS header_id, h.status AS alloc_status, h.profile_code, h.allocated_at,
            (SELECT eb.id FROM ebmrbpr_batch eb WHERE eb.work_order_id=a.id AND eb.status<>'Deleted' ORDER BY eb.id DESC LIMIT 1) AS ebmr_batch_id,
            (SELECT eb.status FROM ebmrbpr_batch eb WHERE eb.work_order_id=a.id AND eb.status<>'Deleted' ORDER BY eb.id DESC LIMIT 1) AS ebmr_exec_status
            FROM mfg_work_order_hdr a
            JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
            JOIN product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
            LEFT JOIN ebmrbpr_work_alloc_header h ON h.work_order_id = a.id
            WHERE a.plant_id = '" . $plant_id . "'
              AND a.status = 'approved'
              AND a.batch_number IS NOT NULL AND a.batch_number <> ''
              AND a.qa_person IS NOT NULL AND a.qa_person <> ''
              AND a.rm_received_by IS NOT NULL AND a.rm_received_by <> '' AND a.rm_received_by <> '0'
            ORDER BY a.id DESC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "getBatchWorkAllocDetail") {
    $wo_id = (int)($_GET['work_order_id'] ?? 0);
    if ($wo_id <= 0) { echo json_encode(array('status' => 'error', 'message' => 'work_order_id required')); exit; }
    $wres = $conn->query("SELECT a.id, a.batch_number, a.work_order_no, a.batch_plan_id,
            b.plan_no, b.batch_size, b.product_code, p.product_name, p.dosage_form
            FROM mfg_work_order_hdr a
            JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
            JOIN product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
            WHERE a.id = $wo_id AND a.plant_id = '" . $plant_id . "' LIMIT 1");
    if (!$wres || $wres->num_rows == 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Work order not found'));
        exit;
    }
    $wo = $wres->fetch_assoc();
    $hdr = null;
    $stages = array();
    $hres = $conn->query("SELECT * FROM ebmrbpr_work_alloc_header WHERE work_order_id=$wo_id LIMIT 1");
    if ($hres && $hres->num_rows > 0) {
        $hdr = $hres->fetch_assoc();
        $sres = $conn->query("SELECT * FROM ebmrbpr_batch_work_alloc WHERE work_order_id=$wo_id ORDER BY stage_seq ASC, id ASC");
        if ($sres) {
            while ($s = $sres->fetch_assoc()) { $stages[] = $s; }
        }
    }
    echo json_encode(array('status' => 'success', 'work_order' => $wo, 'header' => $hdr, 'stages' => $stages));
}
elseif ($type == "initBatchWorkAlloc") {
    $wo_id = (int)($_GET['work_order_id'] ?? ebmrbpr_in($input, 'work_order_id', 0));
    if ($wo_id <= 0) { echo json_encode(array('status' => 'error', 'message' => 'work_order_id required')); exit; }
    $wres = $conn->query("SELECT a.*, b.plan_no, b.product_code, p.product_name, p.dosage_form
            FROM mfg_work_order_hdr a
            JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
            JOIN product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
            WHERE a.id = $wo_id AND a.plant_id = '" . $plant_id . "' LIMIT 1");
    if (!$wres || $wres->num_rows == 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Work order not found'));
        exit;
    }
    $wo = $wres->fetch_assoc();
    if ($wo['status'] !== 'approved' || empty($wo['batch_number']) || empty($wo['qa_person'])) {
        echo json_encode(array('status' => 'error', 'message' => 'Work order must be Production-approved with QA batch number before allocation'));
        exit;
    }
    if (!ebmrbpr_wo_dispensing_received($wo)) {
        echo json_encode(array('status' => 'error', 'message' => 'Dispensed RM must be received in Production before work allocation'));
        exit;
    }
    $existing = $conn->query("SELECT id FROM ebmrbpr_work_alloc_header WHERE work_order_id=$wo_id LIMIT 1");
    if ($existing && $existing->num_rows > 0) {
        echo json_encode(array('status' => 'success', 'header_id' => (int)$existing->fetch_assoc()['id'], 'message' => 'Already initialized'));
        exit;
    }
    $profile_id = 0;
    $profile_code = '';
    $prow = $conn->query("SELECT pp.profile_id, pr.profile_code, pr.id
        FROM ebmrbpr_profile_product pp
        JOIN ebmrbpr_profile pr ON pr.id = pp.profile_id
        WHERE pp.product_code = '" . ebmrbpr_esc($conn, $wo['product_code']) . "'
          AND pp.status = 'active' AND pr.status = 'Approved' AND pr.record_type = 'eBMR'
        ORDER BY pp.id DESC LIMIT 1");
    if ($prow && $prow->num_rows > 0) {
        $p = $prow->fetch_assoc();
        $profile_id = (int)$p['id'];
        $profile_code = $p['profile_code'];
    }
    $conn->begin_transaction();
    try {
        $sql = "INSERT INTO ebmrbpr_work_alloc_header
            (plant_id, work_order_id, work_order_no, batch_plan_id, plan_no, batch_number,
             product_code, product_name, profile_id, profile_code, status, entry_by)
            VALUES (
                '" . $plant_id . "', $wo_id,
                '" . ebmrbpr_esc($conn, $wo['work_order_no']) . "',
                " . (int)$wo['batch_plan_id'] . ",
                '" . ebmrbpr_esc($conn, $wo['plan_no']) . "',
                '" . ebmrbpr_esc($conn, $wo['batch_number']) . "',
                '" . ebmrbpr_esc($conn, $wo['product_code']) . "',
                '" . ebmrbpr_esc($conn, $wo['product_name']) . "',
                $profile_id,
                '" . ebmrbpr_esc($conn, $profile_code) . "',
                'Pending', '" . $emp_id . "'
            )";
        if (!$conn->query($sql)) { throw new Exception($conn->error); }
        $header_id = $conn->insert_id;
        if ($profile_id > 0) {
            $sres = $conn->query("SELECT seq_no, stage_name FROM ebmrbpr_profile_stage
                WHERE profile_id=$profile_id ORDER BY seq_no ASC, id ASC");
            if ($sres) {
                while ($st = $sres->fetch_assoc()) {
                    $ins = "INSERT INTO ebmrbpr_batch_work_alloc
                        (header_id, work_order_id, stage_seq, stage_name, status)
                        VALUES ($header_id, $wo_id, " . (int)$st['seq_no'] . ",
                        '" . ebmrbpr_esc($conn, $st['stage_name']) . "', 'Pending')";
                    if (!$conn->query($ins)) { throw new Exception($conn->error); }
                }
            }
        }
        $conn->commit();
        ebmrbpr_ok(array('header_id' => $header_id));
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
    }
}
elseif ($type == "saveBatchWorkAlloc") {
    $wo_id = (int)ebmrbpr_in($input, 'work_order_id', 0);
    $stages = ebmrbpr_in($input, 'stages', array());
    $change_reason = trim((string)ebmrbpr_in($input, 'change_reason', ''));
    $action_in = trim((string)ebmrbpr_in($input, 'action', ''));
    if ($wo_id <= 0 || !is_array($stages) || !count($stages)) {
        echo json_encode(array('status' => 'error', 'message' => 'work_order_id and stages required'));
        exit;
    }
    $hdr = $conn->query("SELECT id, status, batch_number FROM ebmrbpr_work_alloc_header WHERE work_order_id=$wo_id LIMIT 1");
    if (!$hdr || $hdr->num_rows == 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Initialize work allocation first'));
        exit;
    }
    $hdrRow = $hdr->fetch_assoc();
    $header_id = (int)$hdrRow['id'];
    $prevStatus = trim((string)($hdrRow['status'] ?? 'Pending'));
    $action = $action_in !== '' ? $action_in : ($prevStatus === 'Allocated' ? 'Change' : 'Allocate');
    if ($action === 'Change' && $change_reason === '') {
        echo json_encode(array('status' => 'error', 'message' => 'Change reason is required for re-allocation'));
        exit;
    }
    $before = array();
    $bres = $conn->query("SELECT stage_seq, stage_name, operator_emp, operator_name, office_emp, office_name,
        alt_operator_emp, alt_operator_name, alt_officer_emp, alt_officer_name, reviewer_emp, reviewer_name, approver_emp, approver_name
        FROM ebmrbpr_batch_work_alloc WHERE work_order_id=$wo_id ORDER BY stage_seq ASC, id ASC");
    if ($bres) {
        while ($b = $bres->fetch_assoc()) { $before[] = $b; }
    }
    $conn->begin_transaction();
    try {
        $after = array();
        foreach ($stages as $st) {
            $id = (int)($st['id'] ?? 0);
            if ($id <= 0) continue;
            $sql = "UPDATE ebmrbpr_batch_work_alloc SET
                operator_emp='" . ebmrbpr_esc($conn, $st['operator_emp'] ?? '') . "',
                operator_name='" . ebmrbpr_esc($conn, $st['operator_name'] ?? '') . "',
                office_emp='" . ebmrbpr_esc($conn, $st['office_emp'] ?? '') . "',
                office_name='" . ebmrbpr_esc($conn, $st['office_name'] ?? '') . "',
                alt_operator_emp='" . ebmrbpr_esc($conn, $st['alt_operator_emp'] ?? '') . "',
                alt_operator_name='" . ebmrbpr_esc($conn, $st['alt_operator_name'] ?? '') . "',
                alt_officer_emp='" . ebmrbpr_esc($conn, $st['alt_officer_emp'] ?? '') . "',
                alt_officer_name='" . ebmrbpr_esc($conn, $st['alt_officer_name'] ?? '') . "',
                reviewer_emp='" . ebmrbpr_esc($conn, $st['reviewer_emp'] ?? '') . "',
                reviewer_name='" . ebmrbpr_esc($conn, $st['reviewer_name'] ?? '') . "',
                approver_emp='" . ebmrbpr_esc($conn, $st['approver_emp'] ?? '') . "',
                approver_name='" . ebmrbpr_esc($conn, $st['approver_name'] ?? '') . "',
                status='Allocated'
                WHERE id=$id AND work_order_id=$wo_id";
            if (!$conn->query($sql)) { throw new Exception($conn->error); }
            $after[] = array(
                'stage_seq' => (int)($st['stage_seq'] ?? 0),
                'stage_name' => $st['stage_name'] ?? '',
                'operator_emp' => $st['operator_emp'] ?? '',
                'operator_name' => $st['operator_name'] ?? '',
                'office_emp' => $st['office_emp'] ?? '',
                'office_name' => $st['office_name'] ?? '',
                'alt_operator_emp' => $st['alt_operator_emp'] ?? '',
                'alt_operator_name' => $st['alt_operator_name'] ?? '',
                'alt_officer_emp' => $st['alt_officer_emp'] ?? '',
                'alt_officer_name' => $st['alt_officer_name'] ?? '',
                'reviewer_emp' => $st['reviewer_emp'] ?? '',
                'reviewer_name' => $st['reviewer_name'] ?? '',
                'approver_emp' => $st['approver_emp'] ?? '',
                'approver_name' => $st['approver_name'] ?? '',
            );
        }
        $conn->query("UPDATE ebmrbpr_work_alloc_header SET status='Allocated', allocated_by='" . $emp_id . "',
            allocated_at='" . $entry_date . "' WHERE id=$header_id");
        $byName = '';
        $nres = @$conn->query("SELECT firstname, lastname FROM employee WHERE emp_id='" . ebmrbpr_esc($conn, $emp_id) . "' LIMIT 1");
        if ($nres && $nres->num_rows) {
            $n = $nres->fetch_assoc();
            $byName = trim(($n['firstname'] ?? '') . ' ' . ($n['lastname'] ?? ''));
        }
        $conn->query("INSERT INTO ebmrbpr_work_alloc_change_log
            (plant_id, header_id, work_order_id, batch_number, action, change_reason, before_json, after_json, changed_by, changed_by_name, changed_at)
            VALUES (
            '" . ebmrbpr_esc($conn, $plant_id) . "', $header_id, $wo_id,
            '" . ebmrbpr_esc($conn, $hdrRow['batch_number'] ?? '') . "',
            '" . ebmrbpr_esc($conn, $action) . "',
            '" . ebmrbpr_esc($conn, $change_reason) . "',
            '" . ebmrbpr_esc($conn, json_encode($before)) . "',
            '" . ebmrbpr_esc($conn, json_encode($after)) . "',
            '" . ebmrbpr_esc($conn, $emp_id) . "',
            '" . ebmrbpr_esc($conn, $byName) . "',
            '" . $entry_date . "')");
        $conn->commit();
        ebmrbpr_ok(array('action' => $action));
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
    }
}
elseif ($type == "getBatchWorkAllocHistory") {
    $output = array();
    $wo_id = (int)($_GET['work_order_id'] ?? 0);
    $where = "plant_id='" . ebmrbpr_esc($conn, $plant_id) . "'";
    if ($wo_id > 0) {
        $where .= " AND work_order_id=$wo_id";
    }
    $res = @$conn->query("SELECT * FROM ebmrbpr_work_alloc_change_log WHERE $where ORDER BY id DESC LIMIT 500");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['before_json']) && is_string($row['before_json'])) {
                $decoded = json_decode($row['before_json'], true);
                if (json_last_error() === JSON_ERROR_NONE) $row['before'] = $decoded;
            }
            if (!empty($row['after_json']) && is_string($row['after_json'])) {
                $decoded = json_decode($row['after_json'], true);
                if (json_last_error() === JSON_ERROR_NONE) $row['after'] = $decoded;
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "getProductionPersonnel") {
    $output = array();
    $dept = ebmrbpr_esc($conn, $_GET['department'] ?? 'Production');
    $res = $conn->query("SELECT emp_id, firstname, lastname, designation, department
        FROM employee WHERE LOWER(TRIM(status)) IN ('active','approve','approved') AND plant_id='" . $plant_id . "'
        AND department LIKE '%$dept%' ORDER BY firstname ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['display_name'] = trim($row['firstname'] . ' ' . ($row['lastname'] ?? '') . ' (' . $row['emp_id'] . ')');
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

/* ===========================================================
   eBMR / eBPR PROFILE
=========================================================== */
elseif ($type == "getProfiles") {
    $output = array();
    $where = "status<>'Deleted'";
    if (!empty($_GET['record_type'])) {
        $where .= " AND record_type='" . ebmrbpr_esc($conn, $_GET['record_type']) . "'";
    }
    $res = $conn->query("SELECT id, plant_id, profile_code, profile_name, record_type, dosage_form, version, status, entry_by, entry_date
        FROM ebmrbpr_profile WHERE $where ORDER BY id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cnt = $conn->query("SELECT COUNT(*) AS c FROM ebmrbpr_profile_product WHERE profile_id=" . (int)$row['id'] . " AND status='active'");
            $row['bound_products'] = $cnt ? (int)$cnt->fetch_assoc()['c'] : 0;
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "getProfile") {
    $id = (int)($_GET['id'] ?? 0);
    $res = $conn->query("SELECT * FROM ebmrbpr_profile WHERE id=$id LIMIT 1");
    if (!$res || $res->num_rows == 0) { echo json_encode(array('status' => 'error', 'message' => 'Not found')); }
    else {
        $profile = $res->fetch_assoc();
        $stages = array();
        $sres = $conn->query("SELECT * FROM ebmrbpr_profile_stage WHERE profile_id=$id ORDER BY seq_no ASC, id ASC");
        if ($sres) {
            while ($s = $sres->fetch_assoc()) {
                $steps = array();
                $tres = $conn->query("SELECT * FROM ebmrbpr_profile_step WHERE profile_stage_id=" . (int)$s['id'] . " ORDER BY seq_no ASC, id ASC");
                if ($tres) {
                    while ($t = $tres->fetch_assoc()) {
                        $t['config'] = json_decode($t['config_json'], true);
                        ebmrbpr_hydrate_profile_step_workflow($t);
                        $steps[] = $t;
                    }
                }
                $s['config'] = json_decode($s['config_json'], true);
                ebmrbpr_hydrate_profile_stage_workflow($s);
                $s['steps'] = $steps;
                $stages[] = $s;
            }
        }
        $products = array();
        $pres = $conn->query("SELECT * FROM ebmrbpr_profile_product WHERE profile_id=$id AND status='active'");
        if ($pres) {
            while ($p = $pres->fetch_assoc()) { $products[] = $p; }
        }
        $profile['header'] = json_decode($profile['header_json'], true);
        $profile['static'] = json_decode($profile['static_json'], true);
        $profile['right_tabs'] = json_decode($profile['right_tabs_json'], true);
        $profile['stages'] = $stages;
        $profile['products'] = $products;
        echo json_encode(array('status' => 'success', 'profile' => $profile));
    }
}
elseif ($type == "saveProfile") {
    $code = ebmrbpr_next_code($conn, 'ebmrbpr_profile', 'profile_code', 'EBMR');
    $header = ebmrbpr_in($input, 'header_json', '');
    if (is_array($header)) { $header = json_encode($header); }
    $static = ebmrbpr_in($input, 'static_json', '');
    if (is_array($static)) { $static = json_encode($static); }
    $sql = "INSERT INTO ebmrbpr_profile (plant_id, profile_code, profile_name, record_type, dosage_form, version, header_json, static_json, right_tabs_json, status, entry_by)
        VALUES (
            '" . $plant_id . "',
            '" . $code . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'profile_name')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'record_type', 'eBMR')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'version', '1.0')) . "',
            '" . ebmrbpr_esc($conn, $header) . "',
            '" . ebmrbpr_esc($conn, $static) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'right_tabs_json', '')) . "',
            'Draft',
            '" . $emp_id . "'
        )";
    if ($conn->query($sql)) { ebmrbpr_ok(array('id' => $conn->insert_id, 'profile_code' => $code)); } else { ebmrbpr_err($conn); }
}
elseif ($type == "updateProfile") {
    $id = (int)($_GET['id'] ?? 0);
    $header = ebmrbpr_in($input, 'header_json', '');
    if (is_array($header)) { $header = json_encode($header); }
    $static = ebmrbpr_in($input, 'static_json', '');
    if (is_array($static)) { $static = json_encode($static); }
    $rtabs = ebmrbpr_in($input, 'right_tabs_json', '');
    if (is_array($rtabs)) { $rtabs = json_encode($rtabs); }
    $sql = "UPDATE ebmrbpr_profile SET
                profile_name='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'profile_name')) . "',
                record_type='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'record_type', 'eBMR')) . "',
                dosage_form='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'dosage_form')) . "',
                version='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'version', '1.0')) . "',
                header_json='" . ebmrbpr_esc($conn, $header) . "',
                static_json='" . ebmrbpr_esc($conn, $static) . "',
                right_tabs_json='" . ebmrbpr_esc($conn, $rtabs) . "',
                status='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'status', 'Draft')) . "',
                updated_by='" . $emp_id . "',
                updated_date='" . $entry_date . "'
            WHERE id=$id";
    if ($conn->query($sql)) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "deleteProfile") {
    $id = (int)($_GET['id'] ?? 0);
    if ($conn->query("UPDATE ebmrbpr_profile SET status='Deleted' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "saveProfileStructure") {
    // Replaces full stage/step tree for a profile in one transaction.
    // Preserves master workflow signatures by matching stage_name|step_name when client omits them.
    $id = (int)ebmrbpr_in($input, 'profile_id', 0);
    if ($id <= 0) { echo json_encode(array('status' => 'error', 'message' => 'profile_id required')); }
    else {
        $prevStage = array();
        $prevStep = array();
        $ps = $conn->query("SELECT * FROM ebmrbpr_profile_stage WHERE profile_id=$id");
        if ($ps) {
            while ($row = $ps->fetch_assoc()) {
                $prevStage[strtolower(trim($row['stage_name']))] = $row;
            }
        }
        $pt = $conn->query("SELECT t.*, s.stage_name FROM ebmrbpr_profile_step t
            JOIN ebmrbpr_profile_stage s ON s.id=t.profile_stage_id WHERE t.profile_id=$id");
        if ($pt) {
            while ($row = $pt->fetch_assoc()) {
                $key = strtolower(trim($row['stage_name'])) . '|' . strtolower(trim($row['step_name']));
                $prevStep[$key] = $row;
            }
        }
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM ebmrbpr_profile_step WHERE profile_id=$id");
            $conn->query("DELETE FROM ebmrbpr_profile_stage WHERE profile_id=$id");
            $stages = ebmrbpr_in($input, 'stages', array());
            if (!is_array($stages)) { $stages = array(); }
            $sidx = 0;
            foreach ($stages as $stage) {
                $sidx++;
                $sConfig = isset($stage['config']) ? json_encode($stage['config']) : '';
                $sName = ebmrbpr_esc($conn, ebmrbpr_in($stage, 'stage_name'));
                $sMaster = (int)ebmrbpr_in($stage, 'stage_master_id', 0);
                $sSeq = (int)ebmrbpr_in($stage, 'seq_no', $sidx);
                $prevS = $prevStage[strtolower(trim(ebmrbpr_in($stage, 'stage_name')))] ?? null;
                $frozen = isset($stage['frozen']) ? (int)$stage['frozen'] : (int)($prevS['frozen'] ?? 0);
                $frozenAt = ebmrbpr_in($stage, 'frozen_at', $prevS['frozen_at'] ?? '');
                $frozenBy = ebmrbpr_in($stage, 'frozen_by', $prevS['frozen_by'] ?? '');
                $freezeJson = '';
                if (isset($stage['freeze_sign']) && is_array($stage['freeze_sign'])) {
                    $freezeJson = ebmrbpr_json_encode_sign($stage['freeze_sign']);
                } elseif (isset($stage['freeze_sign_json'])) {
                    $freezeJson = is_array($stage['freeze_sign_json']) ? json_encode($stage['freeze_sign_json']) : (string)$stage['freeze_sign_json'];
                } elseif ($prevS) {
                    $freezeJson = (string)($prevS['freeze_sign_json'] ?? '');
                }
                $conn->query("INSERT INTO ebmrbpr_profile_stage
                    (profile_id, stage_master_id, stage_name, seq_no, config_json, frozen, frozen_at, frozen_by, freeze_sign_json)
                    VALUES ($id, $sMaster, '$sName', $sSeq, '" . ebmrbpr_esc($conn, $sConfig) . "',
                    $frozen,
                    " . ($frozenAt !== '' ? "'" . ebmrbpr_esc($conn, $frozenAt) . "'" : "NULL") . ",
                    " . ($frozenBy !== '' ? "'" . ebmrbpr_esc($conn, $frozenBy) . "'" : "NULL") . ",
                    " . ($freezeJson !== '' ? "'" . ebmrbpr_esc($conn, $freezeJson) . "'" : "NULL") . ")");
                $stageRowId = $conn->insert_id;
                $steps = isset($stage['steps']) && is_array($stage['steps']) ? $stage['steps'] : array();
                $tidx = 0;
                foreach ($steps as $step) {
                    $tidx++;
                    $tConfig = isset($step['config']) ? json_encode($step['config']) : '';
                    $tNameRaw = ebmrbpr_in($step, 'step_name');
                    $tName = ebmrbpr_esc($conn, $tNameRaw);
                    $tMaster = (int)ebmrbpr_in($step, 'step_master_id', 0);
                    $tSeq = (int)ebmrbpr_in($step, 'seq_no', $tidx);
                    $pkey = strtolower(trim(ebmrbpr_in($stage, 'stage_name'))) . '|' . strtolower(trim($tNameRaw));
                    $prevT = $prevStep[$pkey] ?? null;
                    $wf = ebmrbpr_in($step, 'workflow_status', $prevT['workflow_status'] ?? 'Draft');
                    $prep = isset($step['prepared']) ? ebmrbpr_json_encode_sign($step['prepared'])
                        : (isset($step['prepared_json']) ? (is_array($step['prepared_json']) ? json_encode($step['prepared_json']) : (string)$step['prepared_json'])
                        : (string)($prevT['prepared_json'] ?? ''));
                    $chk = isset($step['checked']) ? ebmrbpr_json_encode_sign($step['checked'])
                        : (isset($step['checked_json']) ? (is_array($step['checked_json']) ? json_encode($step['checked_json']) : (string)$step['checked_json'])
                        : (string)($prevT['checked_json'] ?? ''));
                    $rev = isset($step['reviewed']) ? ebmrbpr_json_encode_sign($step['reviewed'])
                        : (isset($step['reviewed_json']) ? (is_array($step['reviewed_json']) ? json_encode($step['reviewed_json']) : (string)$step['reviewed_json'])
                        : (string)($prevT['reviewed_json'] ?? ''));
                    $apr = isset($step['approved']) ? ebmrbpr_json_encode_sign($step['approved'])
                        : (isset($step['approved_json']) ? (is_array($step['approved_json']) ? json_encode($step['approved_json']) : (string)$step['approved_json'])
                        : (string)($prevT['approved_json'] ?? ''));
                    $corr = ebmrbpr_in($step, 'correction_remark', $prevT['correction_remark'] ?? '');
                    $sbBy = ebmrbpr_in($step, 'sent_back_by', $prevT['sent_back_by'] ?? '');
                    $sbAt = ebmrbpr_in($step, 'sent_back_at', $prevT['sent_back_at'] ?? '');
                    $sbRole = ebmrbpr_in($step, 'sent_back_from_role', $prevT['sent_back_from_role'] ?? '');
                    $conn->query("INSERT INTO ebmrbpr_profile_step
                        (profile_id, profile_stage_id, step_master_id, step_name, seq_no, config_json,
                         workflow_status, prepared_json, checked_json, reviewed_json, approved_json,
                         correction_remark, sent_back_by, sent_back_at, sent_back_from_role)
                        VALUES ($id, $stageRowId, $tMaster, '$tName', $tSeq, '" . ebmrbpr_esc($conn, $tConfig) . "',
                        '" . ebmrbpr_esc($conn, $wf) . "',
                        " . ($prep !== '' ? "'" . ebmrbpr_esc($conn, $prep) . "'" : "NULL") . ",
                        " . ($chk !== '' ? "'" . ebmrbpr_esc($conn, $chk) . "'" : "NULL") . ",
                        " . ($rev !== '' ? "'" . ebmrbpr_esc($conn, $rev) . "'" : "NULL") . ",
                        " . ($apr !== '' ? "'" . ebmrbpr_esc($conn, $apr) . "'" : "NULL") . ",
                        '" . ebmrbpr_esc($conn, $corr) . "',
                        " . ($sbBy !== '' ? "'" . ebmrbpr_esc($conn, $sbBy) . "'" : "NULL") . ",
                        " . ($sbAt !== '' ? "'" . ebmrbpr_esc($conn, $sbAt) . "'" : "NULL") . ",
                        " . ($sbRole !== '' ? "'" . ebmrbpr_esc($conn, $sbRole) . "'" : "NULL") . ")");
                }
            }
            $conn->commit();
            ebmrbpr_ok();
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
    }
}
elseif ($type == "saveProfileStepConfig") {
    $stepId = (int)ebmrbpr_in($input, 'profile_step_id', 0);
    $profileId = (int)ebmrbpr_in($input, 'profile_id', 0);
    if ($stepId <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'profile_step_id required'));
    } else {
        $res = $conn->query("SELECT t.*, s.stage_name, s.frozen FROM ebmrbpr_profile_step t
            JOIN ebmrbpr_profile_stage s ON s.id=t.profile_stage_id WHERE t.id=$stepId LIMIT 1");
        if (!$res || $res->num_rows == 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Step not found'));
        } else {
            $step = $res->fetch_assoc();
            if ($profileId > 0 && (int)$step['profile_id'] !== $profileId) {
                echo json_encode(array('status' => 'error', 'message' => 'Step does not belong to this profile'));
            } elseif ((int)($step['frozen'] ?? 0) === 1) {
                echo json_encode(array('status' => 'error', 'message' => 'Stage is frozen — unlock via correction before editing'));
            } else {
                $wf = trim((string)($step['workflow_status'] ?? 'Draft'));
                if (!in_array($wf, array('Draft', 'Correction Required'), true)) {
                    echo json_encode(array('status' => 'error', 'message' => 'Step is locked at status: ' . $wf));
                } else {
                    $sign = ebmrbpr_in($input, 'sign', array());
                    if (!is_array($sign) || empty($sign['emp_id'])) {
                        echo json_encode(array('status' => 'error', 'message' => 'Prepared By e-sign required'));
                    } else {
                        $cfg = ebmrbpr_in($input, 'config', array());
                        if (!is_array($cfg)) $cfg = array();
                        $snap = ebmrbpr_signer_snapshot($sign, $emp_id);
                        $snap['meaning'] = 'Prepared By';
                        $from = $wf;
                        $to = 'Pending Check';
                        $sql = "UPDATE ebmrbpr_profile_step SET
                            config_json='" . ebmrbpr_esc($conn, json_encode($cfg)) . "',
                            workflow_status='Pending Check',
                            prepared_json='" . ebmrbpr_esc($conn, json_encode($snap)) . "',
                            checked_json=NULL, reviewed_json=NULL, approved_json=NULL,
                            correction_remark='', sent_back_by=NULL, sent_back_at=NULL, sent_back_from_role=NULL
                            WHERE id=$stepId";
                        if ($conn->query($sql)) {
                            ebmrbpr_log_master_step_workflow($conn, $plant_id, array(
                                'profile_id' => (int)$step['profile_id'],
                                'profile_stage_id' => (int)$step['profile_stage_id'],
                                'profile_step_id' => $stepId,
                                'stage_name' => $step['stage_name'],
                                'step_name' => $step['step_name'],
                                'action' => 'prepare',
                                'from_status' => $from,
                                'to_status' => $to,
                                'role_label' => 'Prepared By',
                                'remark' => $snap['reason'] ?? '',
                                'emp_id' => $snap['emp_id'],
                                'emp_name' => $snap['emp_name'],
                                'signature_token' => $snap['signature_token'],
                                'esign_id' => $snap['esign_id'],
                            ));
                            ebmrbpr_ok(array('workflow_status' => $to, 'prepared' => $snap));
                        } else {
                            ebmrbpr_err($conn);
                        }
                    }
                }
            }
        }
    }
}
elseif ($type == "masterStepWorkflow") {
    $action = trim((string)ebmrbpr_in($input, 'action', ''));
    $stepId = (int)ebmrbpr_in($input, 'profile_step_id', 0);
    $stageId = (int)ebmrbpr_in($input, 'profile_stage_id', 0);
    $sign = ebmrbpr_in($input, 'sign', array());
    if (!is_array($sign)) $sign = array();
    $remark = trim((string)ebmrbpr_in($input, 'remark', ''));

    if ($action === 'freeze_stage') {
        if ($stageId <= 0) {
            echo json_encode(array('status' => 'error', 'message' => 'profile_stage_id required'));
        } elseif (empty($sign['emp_id'])) {
            echo json_encode(array('status' => 'error', 'message' => 'E-sign required to freeze stage'));
        } elseif (!ebmrbpr_stage_steps_all_approved($conn, $stageId)) {
            echo json_encode(array('status' => 'error', 'message' => 'All steps must be Approved before freezing the stage'));
        } else {
            $sres = $conn->query("SELECT * FROM ebmrbpr_profile_stage WHERE id=$stageId LIMIT 1");
            if (!$sres || $sres->num_rows == 0) {
                echo json_encode(array('status' => 'error', 'message' => 'Stage not found'));
            } else {
                $stage = $sres->fetch_assoc();
                $snap = ebmrbpr_signer_snapshot($sign, $emp_id);
                $snap['meaning'] = 'Approved By';
                $conn->query("UPDATE ebmrbpr_profile_stage SET frozen=1, frozen_at='" . ebmrbpr_esc($conn, $snap['signed_at']) . "',
                    frozen_by='" . ebmrbpr_esc($conn, $snap['emp_id']) . "',
                    freeze_sign_json='" . ebmrbpr_esc($conn, json_encode($snap)) . "' WHERE id=$stageId");
                $conn->query("UPDATE ebmrbpr_profile_step SET workflow_status='Frozen' WHERE profile_stage_id=$stageId AND workflow_status='Approved'");
                ebmrbpr_log_master_step_workflow($conn, $plant_id, array(
                    'profile_id' => (int)$stage['profile_id'],
                    'profile_stage_id' => $stageId,
                    'profile_step_id' => 0,
                    'stage_name' => $stage['stage_name'],
                    'step_name' => '',
                    'action' => 'freeze_stage',
                    'from_status' => 'Approved',
                    'to_status' => 'Frozen',
                    'role_label' => 'Freeze',
                    'remark' => $remark !== '' ? $remark : ($snap['reason'] ?? ''),
                    'emp_id' => $snap['emp_id'],
                    'emp_name' => $snap['emp_name'],
                    'signature_token' => $snap['signature_token'],
                    'esign_id' => $snap['esign_id'],
                ));
                ebmrbpr_ok(array('frozen' => 1, 'freeze_sign' => $snap));
            }
        }
    } elseif ($stepId <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'profile_step_id required'));
    } else {
        $res = $conn->query("SELECT t.*, s.stage_name, s.frozen FROM ebmrbpr_profile_step t
            JOIN ebmrbpr_profile_stage s ON s.id=t.profile_stage_id WHERE t.id=$stepId LIMIT 1");
        if (!$res || $res->num_rows == 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Step not found'));
        } else {
            $step = $res->fetch_assoc();
            if ((int)($step['frozen'] ?? 0) === 1 && $action !== 'send_back') {
                echo json_encode(array('status' => 'error', 'message' => 'Stage is frozen'));
            } else {
                $wf = trim((string)($step['workflow_status'] ?? 'Draft'));
                $snap = ebmrbpr_signer_snapshot($sign, $emp_id);
                $ok = true;
                $msg = '';
                $to = $wf;
                $col = '';
                $role = '';
                if ($action === 'check') {
                    if ($wf !== 'Pending Check') { $ok = false; $msg = 'Step is not Pending Check'; }
                    else { $to = 'Pending Review'; $col = 'checked_json'; $role = 'Checked By'; $snap['meaning'] = $role; }
                } elseif ($action === 'review') {
                    if ($wf !== 'Pending Review') { $ok = false; $msg = 'Step is not Pending Review'; }
                    else { $to = 'Pending Approval'; $col = 'reviewed_json'; $role = 'Reviewed By'; $snap['meaning'] = $role; }
                } elseif ($action === 'approve') {
                    if ($wf !== 'Pending Approval') { $ok = false; $msg = 'Step is not Pending Approval'; }
                    else { $to = 'Approved'; $col = 'approved_json'; $role = 'Approved By'; $snap['meaning'] = $role; }
                } elseif ($action === 'send_back') {
                    if (!in_array($wf, array('Pending Check', 'Pending Review', 'Pending Approval', 'Approved', 'Frozen'), true)) {
                        $ok = false; $msg = 'Cannot send back from status: ' . $wf;
                    } elseif ($remark === '') {
                        $ok = false; $msg = 'Correction remark is required';
                    } else {
                        $roleMap = array(
                            'Pending Check' => 'Checked By',
                            'Pending Review' => 'Reviewed By',
                            'Pending Approval' => 'Approved By',
                            'Approved' => 'Approved By',
                            'Frozen' => 'Approved By',
                        );
                        $role = $roleMap[$wf] ?? 'Checker';
                        $to = 'Correction Required';
                        if ((int)($step['frozen'] ?? 0) === 1) {
                            $conn->query("UPDATE ebmrbpr_profile_stage SET frozen=0, frozen_at=NULL, frozen_by=NULL, freeze_sign_json=NULL WHERE id=" . (int)$step['profile_stage_id']);
                        }
                        $sql = "UPDATE ebmrbpr_profile_step SET workflow_status='Correction Required',
                            correction_remark='" . ebmrbpr_esc($conn, $remark) . "',
                            sent_back_by='" . ebmrbpr_esc($conn, $snap['emp_id'] ?: $emp_id) . "',
                            sent_back_at='" . ebmrbpr_esc($conn, $snap['signed_at']) . "',
                            sent_back_from_role='" . ebmrbpr_esc($conn, $role) . "'
                            WHERE id=$stepId";
                        if ($conn->query($sql)) {
                            ebmrbpr_log_master_step_workflow($conn, $plant_id, array(
                                'profile_id' => (int)$step['profile_id'],
                                'profile_stage_id' => (int)$step['profile_stage_id'],
                                'profile_step_id' => $stepId,
                                'stage_name' => $step['stage_name'],
                                'step_name' => $step['step_name'],
                                'action' => 'send_back',
                                'from_status' => $wf,
                                'to_status' => $to,
                                'role_label' => $role,
                                'remark' => $remark,
                                'emp_id' => $snap['emp_id'] ?: $emp_id,
                                'emp_name' => $snap['emp_name'],
                                'signature_token' => $snap['signature_token'],
                                'esign_id' => $snap['esign_id'],
                            ));
                            ebmrbpr_ok(array('workflow_status' => $to));
                        } else {
                            ebmrbpr_err($conn);
                        }
                        $ok = false; // already responded
                        $msg = '__done__';
                    }
                } else {
                    $ok = false; $msg = 'Unknown action';
                }

                if ($msg === '__done__') {
                    // already handled
                } elseif (!$ok) {
                    echo json_encode(array('status' => 'error', 'message' => $msg));
                } elseif (empty($sign['emp_id'])) {
                    echo json_encode(array('status' => 'error', 'message' => $role . ' e-sign required'));
                } else {
                    $sql = "UPDATE ebmrbpr_profile_step SET workflow_status='" . ebmrbpr_esc($conn, $to) . "',
                        `$col`='" . ebmrbpr_esc($conn, json_encode($snap)) . "' WHERE id=$stepId";
                    if ($conn->query($sql)) {
                        ebmrbpr_log_master_step_workflow($conn, $plant_id, array(
                            'profile_id' => (int)$step['profile_id'],
                            'profile_stage_id' => (int)$step['profile_stage_id'],
                            'profile_step_id' => $stepId,
                            'stage_name' => $step['stage_name'],
                            'step_name' => $step['step_name'],
                            'action' => $action,
                            'from_status' => $wf,
                            'to_status' => $to,
                            'role_label' => $role,
                            'remark' => $remark !== '' ? $remark : ($snap['reason'] ?? ''),
                            'emp_id' => $snap['emp_id'],
                            'emp_name' => $snap['emp_name'],
                            'signature_token' => $snap['signature_token'],
                            'esign_id' => $snap['esign_id'],
                        ));
                        ebmrbpr_ok(array('workflow_status' => $to, 'sign' => $snap));
                    } else {
                        ebmrbpr_err($conn);
                    }
                }
            }
        }
    }
}
elseif ($type == "getMasterStepApprovals") {
    $status = trim((string)($_GET['status'] ?? ebmrbpr_in($input, 'status', '')));
    $profileId = (int)($_GET['profile_id'] ?? ebmrbpr_in($input, 'profile_id', 0));
    $where = "t.profile_id=p.id AND t.profile_stage_id=s.id AND p.status<>'Deleted'";
    if ($plant_id !== '' && $plant_id !== '0') {
        $where .= " AND (p.plant_id='" . ebmrbpr_esc($conn, $plant_id) . "' OR p.plant_id IS NULL OR p.plant_id='')";
    }
    if ($profileId > 0) $where .= " AND t.profile_id=$profileId";
    if ($status !== '') {
        $where .= " AND t.workflow_status='" . ebmrbpr_esc($conn, $status) . "'";
    } else {
        $where .= " AND t.workflow_status IN ('Pending Check','Pending Review','Pending Approval','Correction Required')";
    }
    $out = array();
    $sql = "SELECT t.*, s.stage_name, s.seq_no AS stage_seq, s.frozen, p.profile_code, p.profile_name, p.record_type, p.dosage_form
        FROM ebmrbpr_profile_step t
        JOIN ebmrbpr_profile_stage s ON s.id=t.profile_stage_id
        JOIN ebmrbpr_profile p ON p.id=t.profile_id
        WHERE $where
        ORDER BY t.id DESC LIMIT 500";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $r['config'] = json_decode($r['config_json'], true);
            ebmrbpr_hydrate_profile_step_workflow($r);
            $out[] = $r;
        }
    }
    echo json_encode($out);
}
elseif ($type == "getMasterStepWorkflowLog") {
    $profileId = (int)($_GET['profile_id'] ?? 0);
    $stepId = (int)($_GET['profile_step_id'] ?? 0);
    $where = "1=1";
    if ($profileId > 0) $where .= " AND profile_id=$profileId";
    if ($stepId > 0) $where .= " AND profile_step_id=$stepId";
    if ($plant_id !== '' && $plant_id !== '0') {
        $where .= " AND (plant_id='" . ebmrbpr_esc($conn, $plant_id) . "' OR plant_id IS NULL OR plant_id='')";
    }
    $out = array();
    $res = $conn->query("SELECT * FROM ebmrbpr_master_step_workflow_log WHERE $where ORDER BY id DESC LIMIT 1000");
    if ($res) { while ($r = $res->fetch_assoc()) { $out[] = $r; } }
    echo json_encode($out);
}
elseif ($type == "getMasterStepCorrections") {
    $profileId = (int)($_GET['profile_id'] ?? 0);
    $where = "action='send_back'";
    if ($profileId > 0) $where .= " AND profile_id=$profileId";
    if ($plant_id !== '' && $plant_id !== '0') {
        $where .= " AND (plant_id='" . ebmrbpr_esc($conn, $plant_id) . "' OR plant_id IS NULL OR plant_id='')";
    }
    $out = array();
    $res = $conn->query("SELECT * FROM ebmrbpr_master_step_workflow_log WHERE $where ORDER BY id DESC LIMIT 1000");
    if ($res) { while ($r = $res->fetch_assoc()) { $out[] = $r; } }
    echo json_encode($out);
}
elseif ($type == "getProductsForBinding") {
    $output = array();
    $search = isset($_GET['search']) ? ebmrbpr_esc($conn, $_GET['search']) : '';
    $dosageForm = isset($_GET['dosage_form']) ? ebmrbpr_esc($conn, $_GET['dosage_form']) : '';
    $where = "1=1";
    if ($search !== '') {
        $where .= " AND (product_code LIKE '%$search%' OR product_name LIKE '%$search%')";
    }
    if ($dosageForm !== '') {
        $where .= " AND dosage_form='$dosageForm'";
    }
    $res = @$conn->query("SELECT product_code, product_name, dosage_form FROM product WHERE $where ORDER BY product_name ASC LIMIT 300");
    if ($res) {
        while ($row = $res->fetch_assoc()) { $output[] = $row; }
    }
    echo json_encode($output);
}
elseif ($type == "bindProduct") {
    $pid = (int)ebmrbpr_in($input, 'profile_id', 0);
    $pcode = ebmrbpr_esc($conn, ebmrbpr_in($input, 'product_code'));
    $pname = ebmrbpr_esc($conn, ebmrbpr_in($input, 'product_name'));
    $chk = $conn->query("SELECT id FROM ebmrbpr_profile_product WHERE profile_id=$pid AND product_code='$pcode' AND status='active' LIMIT 1");
    if ($chk && $chk->num_rows > 0) { ebmrbpr_ok(array('message' => 'Already bound')); }
    else {
        $sql = "INSERT INTO ebmrbpr_profile_product (profile_id, plant_id, product_code, product_name, entry_by)
            VALUES ($pid, '$plant_id', '$pcode', '$pname', '$emp_id')";
        if ($conn->query($sql)) { ebmrbpr_ok(array('id' => $conn->insert_id)); } else { ebmrbpr_err($conn); }
    }
}
elseif ($type == "unbindProduct") {
    $id = (int)($_GET['id'] ?? 0);
    if ($conn->query("UPDATE ebmrbpr_profile_product SET status='removed' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}

/* ===========================================================
   BATCH EXECUTION
=========================================================== */
elseif ($type == "getWorkOrdersForEbmr") {
    $output = array();
    $rt = ebmrbpr_esc($conn, $_GET['record_type'] ?? 'eBMR');
    $sql = "SELECT a.id, a.batch_number, a.work_order_no, a.status, a.dispensing_status,
            b.plan_no, b.batch_size, b.product_code, p.product_name, b.pack_unit
            FROM mfg_work_order_hdr a
            JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
            JOIN product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
            WHERE a.plant_id = '" . $plant_id . "'
              AND a.status = 'approved'
              AND a.batch_number IS NOT NULL AND a.batch_number <> ''
              AND NOT EXISTS (
                SELECT 1 FROM ebmrbpr_batch eb
                WHERE eb.work_order_id = a.id AND eb.status NOT IN ('Deleted','Released','Released for Packing','Cancelled')
              )
              AND EXISTS (
                SELECT 1 FROM ebmrbpr_work_alloc_header h
                WHERE h.work_order_id = a.id AND h.status = 'Allocated'
              )
            ORDER BY a.id DESC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $prow = $conn->query("SELECT pp.profile_id, pr.profile_code, pr.profile_name
                FROM ebmrbpr_profile_product pp
                JOIN ebmrbpr_profile pr ON pr.id = pp.profile_id
                WHERE pp.product_code = '" . ebmrbpr_esc($conn, $row['product_code']) . "'
                  AND pp.status = 'active' AND pr.status = 'Approved'
                  AND pr.record_type = '$rt'
                ORDER BY pp.id DESC LIMIT 1");
            if ($prow && $prow->num_rows > 0) {
                $p = $prow->fetch_assoc();
                $row['suggested_profile_id'] = $p['profile_id'];
                $row['suggested_profile_code'] = $p['profile_code'];
                $row['suggested_profile_name'] = $p['profile_name'];
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
elseif ($type == "getWorkOrderForEbmr") {
    $wo_id = (int)($_GET['work_order_id'] ?? 0);
    $wo_no = ebmrbpr_esc($conn, $_GET['work_order_no'] ?? '');
    $rt = ebmrbpr_esc($conn, $_GET['record_type'] ?? 'eBMR');
    $where = "a.plant_id = '" . $plant_id . "'";
    if ($wo_id > 0) { $where .= " AND a.id = $wo_id"; }
    elseif ($wo_no !== '') { $where .= " AND a.work_order_no = '$wo_no'"; }
    else { echo json_encode(array('status' => 'error', 'message' => 'work_order_id or work_order_no required')); exit; }
    $sql = "SELECT a.id, a.batch_number, a.work_order_no, a.status, a.dispensing_status, a.batch_plan_id,
            b.plan_no, b.batch_size, b.product_code, p.product_name, b.pack_unit
            FROM mfg_work_order_hdr a
            JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
            JOIN product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
            WHERE $where LIMIT 1";
    $res = $conn->query($sql);
    if (!$res || $res->num_rows == 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Work order not found'));
    } else {
        $wo = $res->fetch_assoc();
        $eb = $conn->query("SELECT id, status FROM ebmrbpr_batch WHERE work_order_id=" . (int)$wo['id'] . " AND status<>'Deleted' ORDER BY id DESC LIMIT 1");
        if ($eb && $eb->num_rows > 0) {
            $e = $eb->fetch_assoc();
            $wo['ebmr_batch_id'] = $e['id'];
            $wo['ebmr_exec_status'] = $e['status'];
        }
        $prow = $conn->query("SELECT pp.profile_id, pr.profile_code, pr.profile_name
            FROM ebmrbpr_profile_product pp
            JOIN ebmrbpr_profile pr ON pr.id = pp.profile_id
            WHERE pp.product_code = '" . ebmrbpr_esc($conn, $wo['product_code']) . "'
              AND pp.status = 'active' AND pr.status = 'Approved'
              AND pr.record_type = '$rt'
            ORDER BY pp.id DESC LIMIT 1");
        if ($prow && $prow->num_rows > 0) {
            $p = $prow->fetch_assoc();
            $wo['suggested_profile_id'] = $p['profile_id'];
            $wo['suggested_profile_code'] = $p['profile_code'];
        }
        echo json_encode(array('status' => 'success', 'work_order' => $wo));
    }
}
elseif ($type == "startBatch") {
    $profile_id = (int)ebmrbpr_in($input, 'profile_id', 0);
    $work_order_id = (int)ebmrbpr_in($input, 'work_order_id', 0);
    if ($profile_id <= 0) { echo json_encode(array('status' => 'error', 'message' => 'profile_id required')); }
    else {
        $wo = null;
        if ($work_order_id > 0) {
            $wres = $conn->query("SELECT a.*, b.plan_no, b.batch_size AS plan_batch_size, b.product_code AS plan_product_code,
                p.product_name AS plan_product_name, b.pack_unit
                FROM mfg_work_order_hdr a
                JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                JOIN product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
                WHERE a.id = $work_order_id AND a.plant_id = '" . $plant_id . "' LIMIT 1");
            if (!$wres || $wres->num_rows == 0) {
                echo json_encode(array('status' => 'error', 'message' => 'Linked work order not found'));
                exit;
            }
            $wo = $wres->fetch_assoc();
            if ($wo['status'] !== 'approved' || empty($wo['batch_number']) || empty($wo['qa_person'])) {
                echo json_encode(array('status' => 'error', 'message' => 'Work order must have Production approval and QA batch number before eBMR execution'));
                exit;
            }
            if (!ebmrbpr_wo_dispensing_received($wo)) {
                echo json_encode(array('status' => 'error', 'message' => 'Dispensed RM must be received in Production before starting eBMR'));
                exit;
            }
            $dup = $conn->query("SELECT id FROM ebmrbpr_batch WHERE work_order_id=$work_order_id AND status NOT IN ('Deleted','Released','Released for Packing','Cancelled') LIMIT 1");
            if ($dup && $dup->num_rows > 0) {
                echo json_encode(array('status' => 'error', 'message' => 'An active eBMR batch already exists for this work order'));
                exit;
            }
            $alloc = $conn->query("SELECT status FROM ebmrbpr_work_alloc_header WHERE work_order_id=$work_order_id LIMIT 1");
            $allocSt = ($alloc && $alloc->num_rows > 0) ? $alloc->fetch_assoc()['status'] : '';
            if ($allocSt !== 'Allocated') {
                echo json_encode(array('status' => 'error', 'message' => 'Complete stage-wise work allocation before starting eBMR execution'));
                exit;
            }
            if (!ebmrbpr_in($input, 'batch_no')) { $input['batch_no'] = $wo['batch_number']; }
            if (!ebmrbpr_in($input, 'product_code')) { $input['product_code'] = $wo['plan_product_code']; }
            if (!ebmrbpr_in($input, 'product_name')) { $input['product_name'] = $wo['plan_product_name']; }
            if (!ebmrbpr_in($input, 'batch_size')) { $input['batch_size'] = $wo['plan_batch_size']; }
            if (!ebmrbpr_in($input, 'batch_size_uom')) { $input['batch_size_uom'] = $wo['pack_unit']; }
        }
        $pres = $conn->query("SELECT * FROM ebmrbpr_profile WHERE id=$profile_id LIMIT 1");
        if (!$pres || $pres->num_rows == 0) { echo json_encode(array('status' => 'error', 'message' => 'Profile not found')); }
        else {
            $ready = ebmrbpr_profile_master_workflow_ready($conn, $profile_id);
            if (!$ready['ok']) {
                echo json_encode(array('status' => 'error', 'message' => $ready['message']));
            } else {
            $profile = $pres->fetch_assoc();
            $conn->begin_transaction();
            try {
                $wo_id_sql = $work_order_id > 0 ? $work_order_id : "NULL";
                $wo_no_sql = $wo ? "'" . ebmrbpr_esc($conn, $wo['work_order_no']) . "'" : "NULL";
                $bp_id_sql = $wo ? (int)$wo['batch_plan_id'] : "NULL";
                $plan_no_sql = $wo ? "'" . ebmrbpr_esc($conn, $wo['plan_no']) . "'" : "NULL";
                $sql = "INSERT INTO ebmrbpr_batch
                    (plant_id, profile_id, profile_code, record_type, batch_no, product_code, product_name, dosage_form,
                     batch_size, batch_size_uom, mfg_date, exp_date, header_json, static_json, right_tabs_json,
                     work_order_id, work_order_no, batch_plan_id, plan_no, status, entry_by)
                    VALUES (
                        '" . $plant_id . "',
                        $profile_id,
                        '" . ebmrbpr_esc($conn, $profile['profile_code']) . "',
                        '" . ebmrbpr_esc($conn, $profile['record_type']) . "',
                        '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'batch_no')) . "',
                        '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'product_code')) . "',
                        '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'product_name')) . "',
                        '" . ebmrbpr_esc($conn, $profile['dosage_form']) . "',
                        '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'batch_size')) . "',
                        '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'batch_size_uom')) . "',
                        " . (ebmrbpr_in($input, 'mfg_date') ? "'" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'mfg_date')) . "'" : "NULL") . ",
                        " . (ebmrbpr_in($input, 'exp_date') ? "'" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'exp_date')) . "'" : "NULL") . ",
                        '" . ebmrbpr_esc($conn, $profile['header_json']) . "',
                        '" . ebmrbpr_esc($conn, $profile['static_json']) . "',
                        '" . ebmrbpr_esc($conn, $profile['right_tabs_json']) . "',
                        $wo_id_sql,
                        $wo_no_sql,
                        $bp_id_sql,
                        $plan_no_sql,
                        'In Progress',
                        '" . $emp_id . "'
                    )";
                if (!$conn->query($sql)) { throw new Exception($conn->error); }
                $batch_id = $conn->insert_id;
                $staticSnap = json_decode($profile['static_json'], true);
                if (!is_array($staticSnap)) {
                    $staticSnap = array();
                }
                $startStoreRows = array();
                if ($work_order_id > 0) {
                    $startStoreRows = ebmrbpr_fetch_store_dispensing_rows($conn, $work_order_id, $plant_id);
                    if (count($startStoreRows) > 0) {
                        ebmrbpr_merge_store_dispensing_into_static($staticSnap, $startStoreRows);
                        $conn->query("UPDATE ebmrbpr_batch SET static_json='" . ebmrbpr_esc($conn, json_encode($staticSnap)) . "' WHERE id=$batch_id");
                    }
                }

                // snapshot stages/steps, resolving masters into self-contained templates
                $sres = $conn->query("SELECT * FROM ebmrbpr_profile_stage WHERE profile_id=$profile_id ORDER BY seq_no ASC, id ASC");
                if ($sres) {
                    while ($stage = $sres->fetch_assoc()) {
                        $sconf = json_decode($stage['config_json'], true);
                        $stageInstr = isset($sconf['instructions']) ? $sconf['instructions'] : '';
                        $tres = $conn->query("SELECT * FROM ebmrbpr_profile_step WHERE profile_stage_id=" . (int)$stage['id'] . " ORDER BY seq_no ASC, id ASC");
                        if ($tres) {
                            while ($step = $tres->fetch_assoc()) {
                                $cfg = json_decode($step['config_json'], true);
                                if (!is_array($cfg)) $cfg = array();
                                $stepMeta = ebmrbpr_lookup_step_meta($conn, (int)($profile['config_id'] ?? 0),
                                    ebmrbpr_in($input, 'product_code'), $stage['stage_name'], $step['step_name']);
                                $ipqcFlag = $stepMeta['ipqc_testing'] ?: (isset($cfg['ipqc_testing']) ? $cfg['ipqc_testing'] : 'No');
                                $samplingBy = $stepMeta['sampling_by'] ?: (isset($cfg['sampling_by']) ? $cfg['sampling_by'] : 'Production');
                                $tsApplicable = (isset($stepMeta['time_stamp']) && strcasecmp((string)$stepMeta['time_stamp'], 'Applicable') === 0);
                                $yrApplicable = (isset($stepMeta['yield_reconciliation']) && strcasecmp((string)$stepMeta['yield_reconciliation'], 'Applicable') === 0);
                                $eqApplicable = (isset($stepMeta['equipment_point']) && strcasecmp((string)$stepMeta['equipment_point'], 'Applicable') === 0);
                                $stepTimestamp = (isset($cfg['step_timestamp']) && is_array($cfg['step_timestamp']))
                                    ? $cfg['step_timestamp']
                                    : array('enabled' => false, 'capture_date' => true, 'capture_start_time' => true, 'capture_end_time' => true, 'substeps' => array());
                                // Configure Stage & Step master is authoritative for whether process time stamp runs in execution.
                                $stepTimestamp['enabled'] = $tsApplicable;
                                if (!isset($stepTimestamp['capture_date'])) $stepTimestamp['capture_date'] = true;
                                if (!isset($stepTimestamp['capture_start_time'])) $stepTimestamp['capture_start_time'] = true;
                                if (!isset($stepTimestamp['capture_end_time'])) $stepTimestamp['capture_end_time'] = true;
                                if (!isset($stepTimestamp['substeps']) || !is_array($stepTimestamp['substeps'])) $stepTimestamp['substeps'] = array();
                                $yieldReconTpl = (isset($cfg['yield_reconciliation']) && is_array($cfg['yield_reconciliation']))
                                    ? $cfg['yield_reconciliation']
                                    : array('enabled' => false, 'uom' => 'kg', 'title' => 'Yield Reconciliation');
                                $yieldReconTpl['enabled'] = $yrApplicable;
                                if (!isset($yieldReconTpl['uom']) || trim((string)$yieldReconTpl['uom']) === '') $yieldReconTpl['uom'] = 'kg';
                                if (!isset($yieldReconTpl['title']) || trim((string)$yieldReconTpl['title']) === '') $yieldReconTpl['title'] = 'Yield Reconciliation';
                                $equipmentRows = isset($cfg['equipment']) && is_array($cfg['equipment']) ? $cfg['equipment'] : array();
                                $equipmentRows = ebmrbpr_enrich_step_equipment($conn, $equipmentRows);
                                $masterForms = isset($cfg['master_forms']) && is_array($cfg['master_forms']) ? $cfg['master_forms'] : array();
                                if ($eqApplicable) {
                                    $masterForms['equipment'] = true;
                                }
                                $checkpointSeq = isset($cfg['checkpoint_sequence']) && is_array($cfg['checkpoint_sequence'])
                                    ? $cfg['checkpoint_sequence']
                                    : array();
                                if ($tsApplicable && !in_array('step_timestamp', $checkpointSeq, true)) {
                                    $checkpointSeq[] = 'step_timestamp';
                                }
                                if (!$tsApplicable) {
                                    $checkpointSeq = array_values(array_filter($checkpointSeq, function ($k) {
                                        return $k !== 'step_timestamp';
                                    }));
                                }
                                if ($yrApplicable && !in_array('yield_reconciliation', $checkpointSeq, true)) {
                                    $checkpointSeq[] = 'yield_reconciliation';
                                }
                                if (!$yrApplicable) {
                                    $checkpointSeq = array_values(array_filter($checkpointSeq, function ($k) {
                                        return $k !== 'yield_reconciliation';
                                    }));
                                }
                                if ($eqApplicable && !in_array('equipment', $checkpointSeq, true)) {
                                    $checkpointSeq[] = 'equipment';
                                }
                                if (!$eqApplicable) {
                                    $checkpointSeq = array_values(array_filter($checkpointSeq, function ($k) {
                                        return $k !== 'equipment';
                                    }));
                                    if (empty($equipmentRows)) {
                                        $masterForms['equipment'] = false;
                                    }
                                }
                                $template = array(
                                    'procedure' => isset($cfg['procedure']) ? $cfg['procedure'] : '',
                                    'inprocess_checks' => ebmrbpr_merge_inprocess_setup(
                                        ebmrbpr_fetch_by_ids($conn, 'ebmrbpr_inprocess_check', isset($cfg['inprocess_checks']) ? $cfg['inprocess_checks'] : array()),
                                        isset($cfg['inprocess_check_setup']) ? $cfg['inprocess_check_setup'] : array()
                                    ),
                                    'line_clearance' => ebmrbpr_fetch_by_ids($conn, 'ebmrbpr_checkpoint', isset($cfg['line_clearance']) ? $cfg['line_clearance'] : array()),
                                    'dept_checks' => ebmrbpr_fetch_by_ids($conn, 'ebmrbpr_checkpoint', isset($cfg['dept_checks']) ? $cfg['dept_checks'] : array()),
                                    'qa_checks' => ebmrbpr_fetch_by_ids($conn, 'ebmrbpr_checkpoint', isset($cfg['qa_checks']) ? $cfg['qa_checks'] : array()),
                                    'ipqc' => ebmrbpr_fetch_by_ids($conn, 'ebmrbpr_ipqc_spec', isset($cfg['ipqc']) ? $cfg['ipqc'] : array()),
                                    'equipment' => $equipmentRows,
                                    'holding' => isset($cfg['holding']) ? $cfg['holding'] : array(),
                                    'step_timestamp' => $stepTimestamp,
                                    'yield_reconciliation' => $yieldReconTpl,
                                    'equipment_point' => array('enabled' => $eqApplicable),
                                    'checkpoint_sequence' => $checkpointSeq,
                                    'master_forms' => $masterForms,
                                    'yield_table' => isset($cfg['yield_table']) ? $cfg['yield_table'] : array(),
                                    'weighing_table' => isset($cfg['weighing_table']) ? $cfg['weighing_table'] : array(),
                                    'yield' => isset($cfg['yield']) ? $cfg['yield'] : array(),
                                    'custom_blocks' => isset($cfg['custom_blocks']) ? $cfg['custom_blocks'] : array(),
                                );
                                $ins = "INSERT INTO ebmrbpr_batch_step
                                    (batch_id, stage_seq, stage_name, stage_instructions, step_seq, step_name,
                                     ipqc_testing, sampling_by, template_json, data_json, status)
                                    VALUES (
                                        $batch_id,
                                        " . (int)$stage['seq_no'] . ",
                                        '" . ebmrbpr_esc($conn, $stage['stage_name']) . "',
                                        '" . ebmrbpr_esc($conn, $stageInstr) . "',
                                        " . (int)$step['seq_no'] . ",
                                        '" . ebmrbpr_esc($conn, $step['step_name']) . "',
                                        '" . ebmrbpr_esc($conn, $ipqcFlag) . "',
                                        '" . ebmrbpr_esc($conn, $samplingBy) . "',
                                        '" . ebmrbpr_esc($conn, json_encode($template)) . "',
                                        '{}',
                                        'Pending'
                                    )";
                                if (!$conn->query($ins)) { throw new Exception($conn->error); }
                            }
                        }
                    }
                }
                ebmrbpr_log($conn, $batch_id, 'BATCH_STARTED', 'Batch ' . ebmrbpr_in($input, 'batch_no') . ' started from ' . $profile['profile_code'], $emp_id);
                if ($work_order_id > 0) {
                    $mpRows = array();
                    $ares = $conn->query("SELECT stage_name, operator_name, office_name, alt_operator_name, alt_officer_name, reviewer_name, approver_name
                        FROM ebmrbpr_batch_work_alloc WHERE work_order_id=$work_order_id ORDER BY stage_seq ASC");
                    if ($ares) {
                        while ($ar = $ares->fetch_assoc()) {
                            $mpRows[] = array(
                                'stage' => $ar['stage_name'],
                                'operator' => $ar['operator_name'],
                                'officer' => $ar['office_name'],
                                'alt_operator' => $ar['alt_operator_name'],
                                'alt_officer' => $ar['alt_officer_name'],
                                'reviewer' => $ar['reviewer_name'],
                                'approver' => $ar['approver_name'],
                            );
                        }
                    }
                    if (count($mpRows) > 0) {
                        $conn->query("UPDATE ebmrbpr_batch SET exec_tabs_json='" . ebmrbpr_esc($conn, json_encode(array('manpower' => array('rows' => $mpRows)))) . "' WHERE id=$batch_id");
                    }
                }
                ebmrbpr_init_batch_stages($conn, $batch_id, $profile_id);
                $bSnap = array(
                    'id' => $batch_id,
                    'work_order_id' => $work_order_id,
                    'plant_id' => $plant_id,
                    'product_code' => ebmrbpr_in($input, 'product_code'),
                    'mfg_date' => null,
                    'exp_date' => null,
                    'header' => json_decode($profile['header_json'], true),
                    'static' => $staticSnap,
                );
                ebmrbpr_sync_batch_dates($conn, $bSnap, $startStoreRows);
                if ($work_order_id > 0 && !empty($bSnap['mfg_date'])) {
                    $mfg = "'" . ebmrbpr_esc($conn, substr((string)$bSnap['mfg_date'], 0, 10)) . "'";
                    $conn->query("UPDATE mfg_work_order_hdr SET bmr_status='start', batch_commence_date=$mfg WHERE id=$work_order_id");
                }
                $conn->commit();
                ebmrbpr_ok(array('id' => $batch_id));
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
            }
            }
        }
    }
}
elseif ($type == "getBatches") {
    $output = array();
    $where = "b.status<>'Deleted'";
    if ($plant_id !== '') {
        $where .= " AND b.plant_id='" . ebmrbpr_esc($conn, $plant_id) . "'";
    }
    if (!empty($_GET['record_type'])) { $where .= " AND b.record_type='" . ebmrbpr_esc($conn, $_GET['record_type']) . "'"; }
    if (!empty($_GET['status'])) {
        $st = ebmrbpr_esc($conn, $_GET['status']);
        // Completed BMR list: treat legacy Released and Released for Packing together
        if ($st === 'Released' || $st === 'Released for Packing') {
            $where .= " AND b.status IN ('Released','Released for Packing')";
        } else {
            $where .= " AND b.status='$st'";
        }
    }
    // Under Production shows active execution; exclude terminal releases / cancelled
    if (empty($_GET['status']) && empty($_GET['include_released'])) {
        $where .= " AND b.status NOT IN ('Released','Released for Packing','Cancelled','Deleted')";
    }
    // List columns only — never SELECT * (header_json/static_json blow up memory with many batches)
    $cols = "b.id, b.plant_id, b.profile_id, b.profile_code, b.record_type, b.batch_no, b.product_code, b.product_name,
                   b.dosage_form, b.batch_size, b.batch_size_uom, b.mfg_date, b.exp_date, b.status,
                   b.work_order_id, b.work_order_no, b.batch_plan_id, b.plan_no, b.entry_by, b.entry_date";
    // Optional columns (added by ensure_tables) — include when present
    foreach (array('prod_signoff', 'qa_signoff', 'released_by', 'released_at') as $optCol) {
        $chk = @$conn->query("SHOW COLUMNS FROM ebmrbpr_batch LIKE '$optCol'");
        if ($chk && $chk->num_rows > 0) {
            $cols .= ", b.$optCol";
        }
    }
    // One aggregated join instead of N+1 COUNT queries (200+ under-production batches)
    $sql = "SELECT $cols,
            COALESCE(sc.steps_total, 0) AS steps_total,
            COALESCE(sc.steps_done, 0) AS steps_done,
            COALESCE(sc.steps_checked, 0) AS steps_checked,
            COALESCE(sc.correction_count, 0) AS correction_count
            FROM ebmrbpr_batch b
            LEFT JOIN (
                SELECT batch_id,
                    COUNT(*) AS steps_total,
                    SUM(CASE WHEN status IN ('Done','Checked') THEN 1 ELSE 0 END) AS steps_done,
                    SUM(CASE WHEN status='Checked' THEN 1 ELSE 0 END) AS steps_checked,
                    SUM(CASE WHEN status='Correction Required' THEN 1 ELSE 0 END) AS correction_count
                FROM ebmrbpr_batch_step
                GROUP BY batch_id
            ) sc ON sc.batch_id = b.id
            WHERE $where
            ORDER BY b.id DESC
            LIMIT 500";
    $res = @$conn->query($sql);
    if (!$res) {
        // Fallback minimal list without step aggregates
        $res = $conn->query("SELECT id, profile_code, record_type, batch_no, product_code, product_name,
            dosage_form, batch_size, batch_size_uom, status, work_order_id, work_order_no, plan_no
            FROM ebmrbpr_batch WHERE " . str_replace('b.', '', $where) . " ORDER BY id DESC LIMIT 500");
    }
    if (!$res) {
        echo json_encode(array());
        exit;
    }
    while ($row = $res->fetch_assoc()) {
        $row['steps_total'] = (int)($row['steps_total'] ?? 0);
        $row['steps_done'] = (int)($row['steps_done'] ?? 0);
        $row['steps_checked'] = (int)($row['steps_checked'] ?? 0);
        $row['correction_count'] = (int)($row['correction_count'] ?? 0);
        $row['stage_access_mode'] = 'full';
        $output[] = $row;
    }
    echo json_encode($output);
}
elseif ($type == "getBatch") {
    $id = (int)($_GET['id'] ?? 0);
    try {
        $res = $conn->query("SELECT * FROM ebmrbpr_batch WHERE id=$id LIMIT 1");
        if (!$res || $res->num_rows == 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Batch not found'));
            exit;
        }
        $batch = $res->fetch_assoc();
        $batch['header'] = json_decode($batch['header_json'], true);
        $batch['static'] = json_decode($batch['static_json'], true);
        if (!is_array($batch['header'])) { $batch['header'] = array(); }
        if (!is_array($batch['static'])) { $batch['static'] = array(); }
        $batch['right_tabs'] = json_decode($batch['right_tabs_json'], true);
        $batch['exec_tabs'] = json_decode($batch['exec_tabs_json'], true);
        $batch['yield'] = isset($batch['yield_json']) ? json_decode($batch['yield_json'], true) : null;
        // corrections grouped per step
        $corrByStep = array();
        $cres = @$conn->query("SELECT * FROM ebmrbpr_correction WHERE batch_id=$id ORDER BY id ASC");
        if ($cres) {
            while ($c = $cres->fetch_assoc()) {
                $sid = (int)$c['batch_step_id'];
                if (!isset($corrByStep[$sid])) $corrByStep[$sid] = array();
                $corrByStep[$sid][] = $c;
            }
        }
        $steps = array();
        $sres = $conn->query("SELECT * FROM ebmrbpr_batch_step WHERE batch_id=$id ORDER BY stage_seq ASC, step_seq ASC, id ASC");
        if ($sres) {
            while ($s = $sres->fetch_assoc()) {
                $s['template'] = json_decode($s['template_json'], true);
                $s['data'] = json_decode($s['data_json'], true);
                if (!is_array($s['template'])) { $s['template'] = array(); }
                if (!is_array($s['data'])) { $s['data'] = array(); }
                $s['corrections'] = isset($corrByStep[(int)$s['id']]) ? $corrByStep[(int)$s['id']] : array();
                $steps[] = $s;
            }
        }
        $batch['steps'] = $steps;
        $dept = $_GET['department'] ?? '';
        $access = ebmrbpr_batch_stage_access($conn, $batch, $emp_id, $dept);
        if (($access['mode'] ?? '') === 'denied') {
            echo json_encode(array('status' => 'error', 'message' => 'You are not allocated to any stage for this batch. Contact Work Allocation.'));
            exit;
        }
        $batch['steps'] = ebmrbpr_filter_steps_by_access($steps, $access);
        $batch['stage_access'] = $access;
        $storeRows = array();
        if (!empty($batch['work_order_id'])) {
            try {
                $storeRows = ebmrbpr_fetch_store_dispensing_rows($conn, (int)$batch['work_order_id'], $plant_id);
                if (count($storeRows) > 0) {
                    ebmrbpr_merge_store_dispensing_into_static($batch['static'], $storeRows);
                }
            } catch (Throwable $dispEx) {
                // Never block Open BMR if store dispensing columns/schema differ
                $storeRows = array();
            }
        }
        try {
            ebmrbpr_sync_batch_dates($conn, $batch, $storeRows);
        } catch (Throwable $dateEx) {
            // keep existing batch dates
        }
        try {
            $batch['batch_stages'] = ebmrbpr_get_batch_stages($conn, $id);
        } catch (Throwable $stgEx) {
            $batch['batch_stages'] = array();
        }
        try {
            $batch['equipment_pm'] = ebmrbpr_fetch_equipment_pm_status($conn, $plant_id, $batch);
        } catch (Throwable $pmEx) {
            $batch['equipment_pm'] = array('by_key' => array(), 'items' => array());
        }
        $payload = array('status' => 'success', 'batch' => $batch);
        $json = json_encode($payload);
        if ($json === false) {
            echo json_encode(array('status' => 'error', 'message' => 'Failed to encode batch: ' . json_last_error_msg()));
            exit;
        }
        echo $json;
        exit;
    } catch (Throwable $e) {
        echo json_encode(array('status' => 'error', 'message' => 'Failed to load batch: ' . $e->getMessage()));
        exit;
    }
}
elseif ($type == "saveStepData") {
    $step_id = (int)ebmrbpr_in($input, 'step_id', 0);
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $batchMsg = ebmrbpr_batch_mutable_message($conn, $batch_id);
    if ($batchMsg !== '') {
        echo json_encode(array('status' => 'error', 'message' => $batchMsg));
        exit;
    }
    $access = ebmrbpr_batch_access_from_id($conn, $batch_id, $emp_id, $_GET['department'] ?? '');
    if (($access['mode'] ?? '') === 'denied' || !ebmrbpr_assert_step_mutable($conn, $step_id, $access, true)) {
        echo json_encode(array('status' => 'error', 'message' => 'You can only edit steps in your allocated stage'));
        exit;
    }
    $own = $conn->query("SELECT id, status, template_json FROM ebmrbpr_batch_step WHERE id=$step_id AND batch_id=$batch_id LIMIT 1");
    if (!$own || $own->num_rows === 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Step does not belong to this batch'));
        exit;
    }
    $stepMeta = $own->fetch_assoc();
    $st = trim((string)($stepMeta['status'] ?? ''));
    if (!in_array($st, array('Pending', 'Correction Required', 'Done'), true)) {
        echo json_encode(array('status' => 'error', 'message' => 'Step status ' . $st . ' cannot be edited'));
        exit;
    }
    // If client sends an e-sign, verify it; interim auto-saves (timestamps) may omit it
    $hasSig = trim((string)ebmrbpr_in($input, 'signature_token', '')) !== '' || (int)ebmrbpr_in($input, 'esign_id', 0) > 0;
    if ($hasSig) {
        $esignMsg = ebmrbpr_require_valid_esign($conn, $input, $emp_id);
        if ($esignMsg !== '') {
            echo json_encode(array('status' => 'error', 'message' => $esignMsg));
            exit;
        }
    }
    $template = json_decode($stepMeta['template_json'], true);
    if (!is_array($template)) {
        $template = array();
    }
    $batchRes = $conn->query("SELECT static_json FROM ebmrbpr_batch WHERE id=$batch_id LIMIT 1");
    $batchStub = array('static' => array(), 'steps' => array());
    if ($batchRes && $batchRes->num_rows > 0) {
        $batchStub['static'] = json_decode($batchRes->fetch_assoc()['static_json'], true) ?: array();
    }
    $pmMsg = ebmrbpr_step_pm_blocked_message($conn, $plant_id, $batchStub, $template);
    if ($pmMsg) {
        $dataIn = ebmrbpr_in($input, 'data', array());
        $usage = is_array($dataIn) ? ($dataIn['equipment_usage'] ?? array()) : array();
        if (is_array($usage) && count($usage) > 0) {
            foreach ($usage as $u) {
                if (!is_array($u)) {
                    continue;
                }
                if (!empty($u['start_date']) || !empty($u['start_time']) || !empty($u['end_date']) || !empty($u['end_time'])) {
                    echo json_encode(array('status' => 'error', 'message' => $pmMsg));
                    exit;
                }
            }
        }
    }
    $data = ebmrbpr_in($input, 'data', array());
    if (is_array($data)) { $data = json_encode($data); }
    $sql = "UPDATE ebmrbpr_batch_step SET data_json='" . ebmrbpr_esc($conn, $data) . "',
            remarks='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "'
            WHERE id=$step_id AND batch_id=$batch_id AND status IN ('Pending','Correction Required','Done')";
    if ($conn->query($sql) && $conn->affected_rows >= 0) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "saveBatchStatic") {
    $id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $batchMsg = ebmrbpr_batch_mutable_message($conn, $id);
    if ($batchMsg !== '') {
        echo json_encode(array('status' => 'error', 'message' => $batchMsg));
        exit;
    }
    $access = ebmrbpr_batch_access_from_id($conn, $id, $emp_id, $_GET['department'] ?? '');
    if (($access['mode'] ?? '') === 'denied') {
        echo json_encode(array('status' => 'error', 'message' => 'Access denied'));
        exit;
    }
    $static = ebmrbpr_in($input, 'static', array());
    if (is_string($static)) { $static = json_decode($static, true); }
    if (!is_array($static)) { $static = array(); }
    $sql = "UPDATE ebmrbpr_batch SET static_json='" . ebmrbpr_esc($conn, json_encode($static)) . "' WHERE id=$id";
    if ($conn->query($sql)) {
        ebmrbpr_log($conn, $id, 'STATIC_SAVED', 'Batch static pages updated', $emp_id);
        ebmrbpr_ok();
    } else { ebmrbpr_err($conn); }
}
elseif ($type == "completeStep") {
    $step_id = (int)ebmrbpr_in($input, 'step_id', 0);
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $batchMsg = ebmrbpr_batch_mutable_message($conn, $batch_id);
    if ($batchMsg !== '') {
        echo json_encode(array('status' => 'error', 'message' => $batchMsg));
        exit;
    }
    $access = ebmrbpr_batch_access_from_id($conn, $batch_id, $emp_id, $_GET['department'] ?? '');
    if (($access['mode'] ?? '') === 'denied' || !ebmrbpr_assert_step_mutable($conn, $step_id, $access, true)) {
        echo json_encode(array('status' => 'error', 'message' => 'You can only complete steps in your allocated stage'));
        exit;
    }
    $esignMsg = ebmrbpr_require_valid_esign($conn, $input, $emp_id);
    if ($esignMsg !== '') {
        echo json_encode(array('status' => 'error', 'message' => $esignMsg));
        exit;
    }
    $own = $conn->query("SELECT id, status, template_json, ipqc_testing FROM ebmrbpr_batch_step WHERE id=$step_id AND batch_id=$batch_id LIMIT 1");
    if (!$own || $own->num_rows === 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Step does not belong to this batch'));
        exit;
    }
    $stepMeta = $own->fetch_assoc();
    $st = trim((string)($stepMeta['status'] ?? ''));
    if (!in_array($st, array('Pending', 'Correction Required'), true)) {
        echo json_encode(array('status' => 'error', 'message' => 'Only Pending or Correction Required steps can be completed (current: ' . $st . ')'));
        exit;
    }
    if (!ebmrbpr_step_ipqc_approved($conn, $step_id)) {
        echo json_encode(array('status' => 'error', 'message' => 'IPQC sampling must be completed and approved by QC before completing this step'));
        exit;
    }
    $template = json_decode($stepMeta['template_json'], true);
    if (!is_array($template)) {
        $template = array();
    }
    $dataIn = ebmrbpr_in($input, 'data', array());
    if (!is_array($dataIn)) {
        $dataIn = array();
    }
    $vMsg = ebmrbpr_validate_step_for_complete($template, $dataIn);
    if ($vMsg !== '') {
        echo json_encode(array('status' => 'error', 'message' => $vMsg));
        exit;
    }
    $batchRes = $conn->query("SELECT static_json FROM ebmrbpr_batch WHERE id=$batch_id LIMIT 1");
    $batchStub = array('static' => array(), 'steps' => array());
    if ($batchRes && $batchRes->num_rows > 0) {
        $batchStub['static'] = json_decode($batchRes->fetch_assoc()['static_json'], true) ?: array();
    }
    $pmMsg = ebmrbpr_step_pm_blocked_message($conn, $plant_id, $batchStub, $template);
    if ($pmMsg) {
        echo json_encode(array('status' => 'error', 'message' => $pmMsg));
        exit;
    }
    $data = json_encode($dataIn);
    $sql = "UPDATE ebmrbpr_batch_step SET data_json='" . ebmrbpr_esc($conn, $data) . "',
            remarks='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "',
            status='Done', done_by='" . $emp_id . "', done_at='" . $entry_date . "'
            WHERE id=$step_id AND batch_id=$batch_id AND status IN ('Pending','Correction Required')";
    if ($conn->query($sql) && $conn->affected_rows > 0) {
        ebmrbpr_log($conn, $batch_id, 'STEP_COMPLETED', 'Step #' . $step_id . ' completed', $emp_id);
        ebmrbpr_ok();
    } else {
        echo json_encode(array('status' => 'error', 'message' => $conn->error ?: 'Step could not be completed'));
    }
}
elseif ($type == "reopenStep") {
    $step_id = (int)ebmrbpr_in($input, 'step_id', 0);
    $row = ebmrbpr_step_stage_seq($conn, $step_id);
    $access = ebmrbpr_batch_access_from_id($conn, (int)($row['batch_id'] ?? 0), $emp_id, $_GET['department'] ?? '');
    if (($access['mode'] ?? '') === 'denied' || !ebmrbpr_assert_step_mutable($conn, $step_id, $access, true)) {
        echo json_encode(array('status' => 'error', 'message' => 'You can only reopen steps in your allocated stage'));
        exit;
    }
    if ($conn->query("UPDATE ebmrbpr_batch_step SET status='Pending', checked_by=NULL, checked_at=NULL WHERE id=$step_id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "verifyStep") {
    $step_id = (int)ebmrbpr_in($input, 'step_id', 0);
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $access = ebmrbpr_batch_access_from_id($conn, $batch_id, $emp_id, $_GET['department'] ?? '');
    if (($access['mode'] ?? '') === 'denied' || !ebmrbpr_assert_step_mutable($conn, $step_id, $access, true)) {
        echo json_encode(array('status' => 'error', 'message' => 'You can only verify steps in your allocated stage'));
        exit;
    }
    $sql = "UPDATE ebmrbpr_batch_step SET status='Checked', checked_by='" . $emp_id . "', checked_at='" . $entry_date . "',
            remarks='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remarks')) . "'
            WHERE id=$step_id AND status='Done'";
    if ($conn->query($sql)) {
        if ($conn->affected_rows == 0) { echo json_encode(array('status' => 'error', 'message' => 'Step must be completed before verification')); }
        else {
            $b = (int)ebmrbpr_in($input, 'batch_id', 0);
            if ($b) ebmrbpr_log($conn, $b, 'STEP_VERIFIED', 'Step #' . $step_id . ' verified', $emp_id);
            ebmrbpr_ok();
        }
    } else { ebmrbpr_err($conn); }
}
elseif ($type == "saveExecTabs") {
    $id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $tabs = ebmrbpr_in($input, 'exec_tabs', array());
    if (is_array($tabs)) { $tabs = json_encode($tabs); }
    if ($conn->query("UPDATE ebmrbpr_batch SET exec_tabs_json='" . ebmrbpr_esc($conn, $tabs) . "' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "submitForApproval") {
    $id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $chk = $conn->query("SELECT COUNT(*) AS total, SUM(status='Checked') AS checked FROM ebmrbpr_batch_step WHERE batch_id=$id");
    $c = $chk ? $chk->fetch_assoc() : array('total' => 0, 'checked' => 0);
    if ((int)$c['total'] > 0 && (int)$c['checked'] < (int)$c['total']) {
        echo json_encode(array('status' => 'error', 'message' => 'All steps must be verified before submission (' . (int)$c['checked'] . '/' . (int)$c['total'] . ' verified)'));
    } else {
        if ($conn->query("UPDATE ebmrbpr_batch SET status='Submitted for Approval' WHERE id=$id")) {
            ebmrbpr_log($conn, $id, 'SUBMITTED', 'Batch submitted for approval', $emp_id);
            ebmrbpr_ok();
        } else { ebmrbpr_err($conn); }
    }
}
elseif ($type == "signoff") {
    $id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $role = ebmrbpr_in($input, 'role'); // 'prod' or 'qa'
    $action = ebmrbpr_in($input, 'action', 'Approved'); // Approved / Rejected
    $remark = ebmrbpr_esc($conn, ebmrbpr_in($input, 'remark'));
    $action = ($action == 'Rejected') ? 'Rejected' : 'Approved';
    if ($role != 'prod' && $role != 'qa') {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid role'));
    } else {
        if ($role == 'prod') {
            $conn->query("UPDATE ebmrbpr_batch SET prod_signoff='$action', prod_signoff_by='$emp_id', prod_signoff_at='$entry_date', prod_remark='$remark' WHERE id=$id");
        } else {
            $conn->query("UPDATE ebmrbpr_batch SET qa_signoff='$action', qa_signoff_by='$emp_id', qa_signoff_at='$entry_date', qa_remark='$remark' WHERE id=$id");
        }
        $row = $conn->query("SELECT prod_signoff, qa_signoff FROM ebmrbpr_batch WHERE id=$id")->fetch_assoc();
        $newStatus = 'Submitted for Approval';
        if ($row['prod_signoff'] == 'Rejected' || $row['qa_signoff'] == 'Rejected') {
            $newStatus = 'Rejected';
        } elseif ($row['prod_signoff'] == 'Approved' && $row['qa_signoff'] == 'Approved') {
            $newStatus = 'Approved';
        }
        $conn->query("UPDATE ebmrbpr_batch SET status='$newStatus' WHERE id=$id");
        ebmrbpr_log($conn, $id, 'SIGNOFF_' . strtoupper($role), $action . ' - ' . ebmrbpr_in($input, 'remark'), $emp_id);
        ebmrbpr_ok(array('batch_status' => $newStatus));
    }
}
elseif ($type == "getBatchLog") {
    $id = (int)($_GET['id'] ?? 0);
    $output = array();
    $res = $conn->query("SELECT * FROM ebmrbpr_batch_log WHERE batch_id=$id ORDER BY id DESC");
    if ($res) { while ($r = $res->fetch_assoc()) { $output[] = $r; } }
    echo json_encode($output);
}
elseif ($type == "getBmrAuditTrail") {
    // CFR Part 11 — plant-scoped, filterable, read-only audit trail for Production
    $plantFilter = $plant_id !== '' ? " AND (l.plant_id='" . ebmrbpr_esc($conn, $plant_id) . "' OR l.plant_id='' OR l.plant_id IS NULL OR b.plant_id='" . ebmrbpr_esc($conn, $plant_id) . "')" : '';
    $where = "1=1" . $plantFilter;
    $batchId = (int)($_GET['batch_id'] ?? 0);
    if ($batchId > 0) {
        $where .= " AND l.batch_id=$batchId";
    }
    $batchNo = trim((string)($_GET['batch_no'] ?? ''));
    if ($batchNo !== '') {
        $where .= " AND (l.batch_no LIKE '%" . ebmrbpr_esc($conn, $batchNo) . "%' OR b.batch_no LIKE '%" . ebmrbpr_esc($conn, $batchNo) . "%')";
    }
    $product = trim((string)($_GET['product'] ?? ''));
    if ($product !== '') {
        $where .= " AND (l.product_code LIKE '%" . ebmrbpr_esc($conn, $product) . "%' OR l.product_name LIKE '%" . ebmrbpr_esc($conn, $product) . "%' OR b.product_code LIKE '%" . ebmrbpr_esc($conn, $product) . "%' OR b.product_name LIKE '%" . ebmrbpr_esc($conn, $product) . "%')";
    }
    $action = trim((string)($_GET['action'] ?? ''));
    if ($action !== '') {
        $where .= " AND l.action LIKE '%" . ebmrbpr_esc($conn, $action) . "%'";
    }
    $emp = trim((string)($_GET['emp'] ?? ''));
    if ($emp !== '') {
        $where .= " AND (l.by_emp LIKE '%" . ebmrbpr_esc($conn, $emp) . "%' OR l.emp_name LIKE '%" . ebmrbpr_esc($conn, $emp) . "%')";
    }
    $ip = trim((string)($_GET['ip'] ?? ''));
    if ($ip !== '') {
        $where .= " AND l.ip_address LIKE '%" . ebmrbpr_esc($conn, $ip) . "%'";
    }
    $from = trim((string)($_GET['from_date'] ?? ''));
    $to = trim((string)($_GET['to_date'] ?? ''));
    if ($from !== '') {
        $where .= " AND DATE(l.at_time) >= '" . ebmrbpr_esc($conn, $from) . "'";
    }
    if ($to !== '') {
        $where .= " AND DATE(l.at_time) <= '" . ebmrbpr_esc($conn, $to) . "'";
    }
    $q = trim((string)($_GET['q'] ?? ''));
    if ($q !== '') {
        $qe = ebmrbpr_esc($conn, $q);
        $where .= " AND (l.detail LIKE '%$qe%' OR l.action LIKE '%$qe%' OR l.batch_no LIKE '%$qe%' OR l.record_hash LIKE '%$qe%' OR l.signature_token LIKE '%$qe%')";
    }
    $limit = (int)($_GET['limit'] ?? 500);
    if ($limit < 50) {
        $limit = 50;
    }
    if ($limit > 2000) {
        $limit = 2000;
    }

    $rows = array();
    $sql = "SELECT l.*,
            COALESCE(NULLIF(l.batch_no,''), b.batch_no) AS batch_no_disp,
            COALESCE(NULLIF(l.product_code,''), b.product_code) AS product_code_disp,
            COALESCE(NULLIF(l.product_name,''), b.product_name) AS product_name_disp,
            b.profile_code, b.record_type, b.status AS batch_status
        FROM ebmrbpr_batch_log l
        LEFT JOIN ebmrbpr_batch b ON b.id = l.batch_id
        WHERE $where
        ORDER BY l.at_time DESC, l.id DESC
        LIMIT $limit";
    $res = @$conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $r['event_source'] = 'batch_log';
            $r['batch_no'] = $r['batch_no_disp'] ?? $r['batch_no'];
            $r['product_code'] = $r['product_code_disp'] ?? $r['product_code'];
            $r['product_name'] = $r['product_name_disp'] ?? $r['product_name'];
            unset($r['batch_no_disp'], $r['product_code_disp'], $r['product_name_disp']);
            $rows[] = $r;
        }
    }

    // Optional: merge e-sign rows not yet mirrored (legacy)
    $includeEsign = strtolower(trim((string)($_GET['include_esign'] ?? '1'))) !== '0';
    if ($includeEsign && $batchId > 0) {
        $es = @$conn->query("SELECT e.*, b.batch_no, b.product_code, b.product_name, b.profile_code, b.record_type, b.status AS batch_status
            FROM ebmrbpr_esign e
            LEFT JOIN ebmrbpr_batch b ON b.id = CAST(e.record_ref AS UNSIGNED)
            WHERE e.record_ref='" . ebmrbpr_esc($conn, (string)$batchId) . "'
            ORDER BY e.id DESC LIMIT 200");
        if ($es) {
            $existingTokens = array();
            foreach ($rows as $rr) {
                if (!empty($rr['signature_token'])) {
                    $existingTokens[$rr['signature_token']] = true;
                }
                if (!empty($rr['esign_id'])) {
                    $existingTokens['id:' . $rr['esign_id']] = true;
                }
            }
            while ($e = $es->fetch_assoc()) {
                $tok = $e['signature_token'] ?? '';
                if ($tok !== '' && isset($existingTokens[$tok])) {
                    continue;
                }
                if (!empty($e['id']) && isset($existingTokens['id:' . $e['id']])) {
                    continue;
                }
                $rows[] = array(
                    'id' => 'es-' . $e['id'],
                    'batch_id' => $batchId,
                    'action' => 'ESIGN_' . strtoupper(preg_replace('/\s+/', '_', $e['meaning'] ?? 'SIGN')),
                    'detail' => $e['detail'] ?? '',
                    'by_emp' => $e['emp_id'] ?? '',
                    'emp_name' => $e['emp_name'] ?? '',
                    'at_time' => $e['signed_at'] ?? '',
                    'department' => $e['department'] ?? '',
                    'designation' => $e['designation'] ?? '',
                    'ip_address' => $e['ip'] ?? '',
                    'module' => $e['module'] ?? '',
                    'record_ref' => $e['record_ref'] ?? '',
                    'meaning' => $e['meaning'] ?? '',
                    'auth_method' => $e['auth_method'] ?? '',
                    'auth_type' => $e['auth_type'] ?? '',
                    'signature_token' => $tok,
                    'esign_id' => (int)$e['id'],
                    'reason' => $e['reason'] ?? '',
                    'batch_no' => $e['batch_no'] ?? '',
                    'product_code' => $e['product_code'] ?? '',
                    'product_name' => $e['product_name'] ?? '',
                    'profile_code' => $e['profile_code'] ?? '',
                    'record_type' => $e['record_type'] ?? '',
                    'batch_status' => $e['batch_status'] ?? '',
                    'event_source' => 'esign',
                    'record_hash' => $tok ? hash('sha256', $tok) : '',
                );
            }
            usort($rows, function ($a, $b) {
                return strcmp((string)($b['at_time'] ?? ''), (string)($a['at_time'] ?? ''));
            });
        }
    }

    $actions = array();
    $ares = @$conn->query("SELECT DISTINCT action FROM ebmrbpr_batch_log WHERE action IS NOT NULL AND action<>'' ORDER BY action ASC LIMIT 200");
    if ($ares) {
        while ($a = $ares->fetch_assoc()) {
            $actions[] = $a['action'];
        }
    }

    echo json_encode(array(
        'status' => 'success',
        'cfr' => '21 CFR Part 11 / EU Annex 11',
        'generated_at' => date('Y-m-d H:i:s'),
        'count' => count($rows),
        'actions' => $actions,
        'rows' => $rows,
    ));
}
elseif ($type == "getBatchReport") {
    $id = (int)($_GET['id'] ?? 0);
    $res = $conn->query("SELECT * FROM ebmrbpr_batch WHERE id=$id LIMIT 1");
    if (!$res || $res->num_rows == 0) { echo json_encode(array('status' => 'error', 'message' => 'Batch not found')); }
    else {
        $batch = $res->fetch_assoc();
        $batch['header'] = json_decode($batch['header_json'], true);
        $batch['static'] = json_decode($batch['static_json'], true);
        $batch['right_tabs'] = json_decode($batch['right_tabs_json'], true);
        $batch['exec_tabs'] = json_decode($batch['exec_tabs_json'], true);
        $batch['yield'] = isset($batch['yield_json']) ? json_decode($batch['yield_json'], true) : null;
        $corrByStep = array();
        $cres = $conn->query("SELECT * FROM ebmrbpr_correction WHERE batch_id=$id ORDER BY id ASC");
        $allCorr = array();
        if ($cres) {
            while ($c = $cres->fetch_assoc()) {
                $sid = (int)$c['batch_step_id'];
                if (!isset($corrByStep[$sid])) $corrByStep[$sid] = array();
                $corrByStep[$sid][] = $c;
                $allCorr[] = $c;
            }
        }
        $steps = array();
        $sres = $conn->query("SELECT * FROM ebmrbpr_batch_step WHERE batch_id=$id ORDER BY stage_seq ASC, step_seq ASC, id ASC");
        if ($sres) {
            while ($s = $sres->fetch_assoc()) {
                $s['template'] = json_decode($s['template_json'], true);
                $s['data'] = json_decode($s['data_json'], true);
                $s['corrections'] = isset($corrByStep[(int)$s['id']]) ? $corrByStep[(int)$s['id']] : array();
                $steps[] = $s;
            }
        }
        $batch['steps'] = $steps;
        $batch['batch_stages'] = ebmrbpr_get_batch_stages($conn, $id);
        $deviations = array();
        $dres = $conn->query("SELECT * FROM ebmrbpr_deviation WHERE batch_id=$id AND status<>'Cancelled' ORDER BY id ASC");
        if ($dres) { while ($r = $dres->fetch_assoc()) { $deviations[] = $r; } }
        $incidents = array();
        $ires = $conn->query("SELECT * FROM ebmrbpr_incident WHERE batch_id=$id AND status<>'Cancelled' ORDER BY id ASC");
        if ($ires) { while ($r = $ires->fetch_assoc()) { $incidents[] = $r; } }
        $log = array();
        $lres = $conn->query("SELECT * FROM ebmrbpr_batch_log WHERE batch_id=$id ORDER BY id ASC");
        if ($lres) { while ($r = $lres->fetch_assoc()) { $log[] = $r; } }
        $esign = array();
        $eres = $conn->query("SELECT * FROM ebmrbpr_esign WHERE module='execution' AND record_ref='$id' ORDER BY id ASC");
        if ($eres) { while ($r = $eres->fetch_assoc()) { $esign[] = $r; } }
        echo json_encode(array(
            'status' => 'success',
            'batch' => $batch,
            'corrections' => $allCorr,
            'deviations' => $deviations,
            'incidents' => $incidents,
            'audit' => $log,
            'esign' => $esign,
        ));
    }
}
elseif ($type == "deleteBatch") {
    $id = (int)($_GET['id'] ?? 0);
    if ($conn->query("UPDATE ebmrbpr_batch SET status='Deleted' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}

/* ===========================================================
   GMP CORRECTION WORKFLOW (checker -> operator -> checker)
   Preserves the original (wrong) entry; never overwrites audit.
=========================================================== */
elseif ($type == "raiseCorrection") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $step_id = (int)ebmrbpr_in($input, 'batch_step_id', 0);
    $sql = "INSERT INTO ebmrbpr_correction
        (batch_id, batch_step_id, stage_name, step_name, field_key, field_label, old_value,
         action_type, reason, checker_remark, status, raised_by, raised_at)
        VALUES (
            $batch_id, $step_id,
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_name')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'step_name')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'field_key')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'field_label')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'old_value')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'action_type', 'Correction')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'reason')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'checker_remark')) . "',
            'Raised', '" . $emp_id . "', '" . $entry_date . "'
        )";
    if ($conn->query($sql)) {
        $cid = $conn->insert_id;
        if ($step_id) $conn->query("UPDATE ebmrbpr_batch_step SET status='Correction Required' WHERE id=$step_id");
        // if it was sent back during approval review, flag the batch
        $conn->query("UPDATE ebmrbpr_batch SET status='Correction Required' WHERE id=$batch_id AND status='Submitted for Approval'");
        ebmrbpr_log($conn, $batch_id, 'CORRECTION_RAISED', ebmrbpr_in($input, 'action_type', 'Correction') . ' on "' . ebmrbpr_in($input, 'field_label') . '": ' . ebmrbpr_in($input, 'reason'), $emp_id);
        ebmrbpr_ok(array('id' => $cid));
    } else { ebmrbpr_err($conn); }
}
elseif ($type == "sendBackStep") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $step_id = (int)ebmrbpr_in($input, 'step_id', 0);
    if ($conn->query("UPDATE ebmrbpr_batch_step SET status='Correction Required', checked_by=NULL, checked_at=NULL,
        remarks='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remark')) . "' WHERE id=$step_id")) {
        ebmrbpr_log($conn, $batch_id, 'STEP_SENT_BACK', 'Step #' . $step_id . ' sent back: ' . ebmrbpr_in($input, 'remark'), $emp_id);
        ebmrbpr_ok();
    } else { ebmrbpr_err($conn); }
}
elseif ($type == "submitCorrection") {
    $id = (int)ebmrbpr_in($input, 'id', 0);
    $sql = "UPDATE ebmrbpr_correction SET
            new_value='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'new_value')) . "',
            reason='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'reason')) . "',
            ref_no='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'ref_no')) . "',
            action_type='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'action_type', 'Correction')) . "',
            status='Corrected', corrected_by='" . $emp_id . "', corrected_at='" . $entry_date . "'
            WHERE id=$id";
    if ($conn->query($sql)) {
        $b = (int)ebmrbpr_in($input, 'batch_id', 0);
        if ($b) ebmrbpr_log($conn, $b, 'CORRECTION_SUBMITTED', 'Correction #' . $id . ' corrected to "' . ebmrbpr_in($input, 'new_value') . '"', $emp_id);
        ebmrbpr_ok();
    } else { ebmrbpr_err($conn); }
}
elseif ($type == "verifyCorrection") {
    $id = (int)ebmrbpr_in($input, 'id', 0);
    $sql = "UPDATE ebmrbpr_correction SET status='Verified', verified_by='" . $emp_id . "', verified_at='" . $entry_date . "',
            checker_remark='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'checker_remark')) . "'
            WHERE id=$id AND status='Corrected'";
    if ($conn->query($sql)) {
        if ($conn->affected_rows == 0) { echo json_encode(array('status' => 'error', 'message' => 'Correction must be submitted by the operator before verification')); }
        else {
            $b = (int)ebmrbpr_in($input, 'batch_id', 0);
            if ($b) ebmrbpr_log($conn, $b, 'CORRECTION_VERIFIED', 'Correction #' . $id . ' verified', $emp_id);
            ebmrbpr_ok();
        }
    } else { ebmrbpr_err($conn); }
}
elseif ($type == "getCorrections") {
    $output = array();
    $where = '1=1';
    if (!empty($_GET['id'])) { $where = "batch_id=" . (int)$_GET['id']; }
    if (!empty($_GET['step_id'])) { $where = "batch_step_id=" . (int)$_GET['step_id']; }
    $res = $conn->query("SELECT * FROM ebmrbpr_correction WHERE $where ORDER BY id DESC");
    if ($res) { while ($r = $res->fetch_assoc()) { $output[] = $r; } }
    echo json_encode($output);
}

/* ===========================================================
   DEVIATION REGISTER
=========================================================== */
elseif ($type == "saveDeviation") {
    $id = (int)ebmrbpr_in($input, 'id', 0);
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    if ($id > 0) {
        $sql = "UPDATE ebmrbpr_deviation SET
            title='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'title')) . "',
            description='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'description')) . "',
            classification='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'classification', 'Minor')) . "',
            root_cause='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'root_cause')) . "',
            capa='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'capa')) . "',
            impact='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'impact')) . "'
            WHERE id=$id";
        if ($conn->query($sql)) { ebmrbpr_ok(array('id' => $id)); } else { ebmrbpr_err($conn); }
    } else {
        $dev_no = ebmrbpr_next_code($conn, 'ebmrbpr_deviation', 'dev_no', 'DEV');
        $sql = "INSERT INTO ebmrbpr_deviation
            (plant_id, batch_id, batch_step_id, dev_no, title, description, classification, root_cause, capa, impact, status, raised_by, raised_at)
            VALUES (
                '" . $plant_id . "', $batch_id, " . (int)ebmrbpr_in($input, 'batch_step_id', 0) . ",
                '" . ebmrbpr_esc($conn, $dev_no) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'title')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'description')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'classification', 'Minor')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'root_cause')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'capa')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'impact')) . "',
                'Open', '" . $emp_id . "', '" . $entry_date . "'
            )";
        if ($conn->query($sql)) {
            ebmrbpr_log($conn, $batch_id, 'DEVIATION_RAISED', $dev_no . ': ' . ebmrbpr_in($input, 'title'), $emp_id);
            ebmrbpr_ok(array('id' => $conn->insert_id, 'dev_no' => $dev_no));
        } else { ebmrbpr_err($conn); }
    }
}
elseif ($type == "getDeviations") {
    $output = array();
    $res = $conn->query("SELECT * FROM ebmrbpr_deviation WHERE batch_id=" . (int)($_GET['id'] ?? 0) . " AND status<>'Cancelled' ORDER BY stage_seq ASC, id DESC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            ebmrbpr_enrich_deviation_row($conn, $r);
            if (!empty($r['qms_form_json'])) {
                $r['qms_form'] = json_decode($r['qms_form_json'], true);
            }
            $output[] = $r;
        }
    }
    echo json_encode($output);
}
elseif ($type == "linkEbmrQmsDeviation") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $qms_id = (int)ebmrbpr_in($input, 'qms_deviation_id', 0);
    if (!$batch_id) {
        echo json_encode(array('status' => 'error', 'message' => 'batch_id required'));
    } else {
        if ($qms_id <= 0) {
            $qres = $conn->query("SELECT id FROM deviation WHERE entryBy='" . $emp_id . "' ORDER BY id DESC LIMIT 1");
            $qms_id = ($qres && $qres->num_rows) ? (int)$qres->fetch_assoc()['id'] : 0;
        }
        if ($qms_id <= 0) {
            echo json_encode(array('status' => 'error', 'message' => 'QMS deviation id not found'));
        } else {
            $dev_no = 'QMS-DEV-' . str_pad((string)$qms_id, 5, '0', STR_PAD_LEFT);
            $stage_seq = (int)ebmrbpr_in($input, 'stage_seq', 0);
            $stage_name = ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_name'));
            $title = ebmrbpr_esc($conn, substr(ebmrbpr_in($input, 'detailsOfDev', ''), 0, 120));
            $formJson = ebmrbpr_esc($conn, json_encode($input));
            $conn->query("INSERT INTO ebmrbpr_deviation (plant_id, batch_id, batch_step_id, stage_seq, stage_name, dev_no, title,
                description, classification, status, qms_deviation_id, qms_status, qms_form_json, raised_by, raised_at)
                VALUES ('" . $plant_id . "', $batch_id, " . (int)ebmrbpr_in($input, 'batch_step_id', 0) . ", $stage_seq, '$stage_name',
                '$dev_no', '$title', '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'detailsOfDev')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'classification', 'Minor')) . "',
                'In QMS Workflow', $qms_id, 'Pending', '$formJson', '" . $emp_id . "', '" . $entry_date . "')");
            ebmrbpr_log($conn, $batch_id, 'QMS_DEVIATION_INITIATED', $dev_no . ' stage ' . ebmrbpr_in($input, 'stage_name'), $emp_id);
            ebmrbpr_ok(array('id' => $conn->insert_id, 'dev_no' => $dev_no, 'qms_deviation_id' => $qms_id));
        }
    }
}
elseif ($type == "linkEbmrQmsIncident") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $qms_id = (int)ebmrbpr_in($input, 'qms_incident_id', 0);
    if (!$batch_id || $qms_id <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'batch_id and qms_incident_id required'));
    } else {
        $inc_no = ebmrbpr_esc($conn, ebmrbpr_in($input, 'INR_No', 'INR-' . $qms_id));
        $stage_seq = (int)ebmrbpr_in($input, 'stage_seq', 0);
        $stage_name = ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_name'));
        $title = ebmrbpr_esc($conn, substr(ebmrbpr_in($input, 'INR_Details', ''), 0, 120));
        $formJson = ebmrbpr_esc($conn, json_encode($input));
        $conn->query("INSERT INTO ebmrbpr_incident (plant_id, batch_id, batch_step_id, stage_seq, stage_name, inc_no, title,
            description, severity, status, qms_incident_id, qms_status, qms_form_json, raised_by, raised_at)
            VALUES ('" . $plant_id . "', $batch_id, " . (int)ebmrbpr_in($input, 'batch_step_id', 0) . ", $stage_seq, '$stage_name',
            '$inc_no', '$title', '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'INR_Details')) . "',
            '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'classification_inr', 'Minor')) . "',
            'In QMS Workflow', $qms_id, 'send for review', '$formJson', '" . $emp_id . "', '" . $entry_date . "')");
        ebmrbpr_log($conn, $batch_id, 'QMS_INCIDENT_INITIATED', $inc_no . ' stage ' . ebmrbpr_in($input, 'stage_name'), $emp_id);
        ebmrbpr_ok(array('id' => $conn->insert_id, 'inc_no' => ebmrbpr_in($input, 'INR_No'), 'qms_incident_id' => $qms_id));
    }
}
elseif ($type == "linkEbmrBreakdown") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $bd_id = (int)ebmrbpr_in($input, 'breakdown_id', 0);
    if (!$batch_id || $bd_id <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'batch_id and breakdown_id required'));
    } else {
        $stage_seq = (int)ebmrbpr_in($input, 'stage_seq', 0);
        $stage_name = ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_name'));
        $intNo = ebmrbpr_esc($conn, ebmrbpr_in($input, 'intimation_no'));
        $machine = ebmrbpr_esc($conn, ebmrbpr_in($input, 'machine_name'));
        $machineId = ebmrbpr_esc($conn, ebmrbpr_in($input, 'machine_id'));
        $area = ebmrbpr_esc($conn, ebmrbpr_in($input, 'area_location'));
        $nature = ebmrbpr_esc($conn, ebmrbpr_in($input, 'nature_of_breakdown'));
        $desc = ebmrbpr_esc($conn, ebmrbpr_in($input, 'description'));
        $formJson = ebmrbpr_esc($conn, json_encode($input));
        $conn->query("INSERT INTO ebmrbpr_breakdown (plant_id, batch_id, stage_seq, stage_name, breakdown_id, intimation_no,
            machine_name, machine_id, area_location, nature_of_breakdown, description, workflow_status, form_json, raised_by, raised_at)
            VALUES ('" . $plant_id . "', $batch_id, $stage_seq, '$stage_name', $bd_id, '$intNo', '$machine', '$machineId',
            '$area', '$nature', '$desc', 'pending_qa_review', '$formJson', '" . $emp_id . "', '" . $entry_date . "')");
        ebmrbpr_log($conn, $batch_id, 'BREAKDOWN_INITIATED', $intNo . ' — ' . ebmrbpr_in($input, 'machine_name'), $emp_id);
        ebmrbpr_ok(array('id' => $conn->insert_id, 'breakdown_id' => $bd_id, 'intimation_no' => ebmrbpr_in($input, 'intimation_no')));
    }
}
elseif ($type == "initiateQmsDeviation") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $bres = $conn->query("SELECT * FROM ebmrbpr_batch WHERE id=$batch_id LIMIT 1");
    if (!$bres || $bres->num_rows === 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Batch not found'));
    } elseif (!ebmrbpr_in($input, 'detailsOfDev')) {
        echo json_encode(array('status' => 'error', 'message' => 'Details of deviation required'));
    } else {
        $batch = $bres->fetch_assoc();
        $form = is_array($input) ? $input : array();
        $form['identifiedBy'] = ebmrbpr_in($form, 'identifiedBy', $emp_id);
        $form['devOccuredDept'] = ebmrbpr_in($form, 'devOccuredDept', $_GET['department'] ?? 'Production');
        if (!ebmrbpr_in($form, 'scopeItem') && !empty($batch['product_code'])) {
            $form['scopeItem'] = ($batch['product_name'] ?? '') . ' (' . $batch['product_code'] . ')';
        }
        $ins = ebmrbpr_insert_qms_deviation($conn, $plant_id, $emp_id, $entry_date, $form, $batch);
        if (!$ins['ok']) {
            echo json_encode(array('status' => 'error', 'message' => $ins['message']));
        } else {
            $qms_id = (int)$ins['id'];
            $dev_no = 'QMS-DEV-' . str_pad((string)$qms_id, 5, '0', STR_PAD_LEFT);
            $stage_seq = (int)ebmrbpr_in($input, 'stage_seq', 0);
            $stage_name = ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_name'));
            $title = ebmrbpr_esc($conn, ebmrbpr_in($input, 'title', substr(ebmrbpr_in($input, 'detailsOfDev'), 0, 120)));
            $formJson = ebmrbpr_esc($conn, json_encode($form));
            $conn->query("INSERT INTO ebmrbpr_deviation (plant_id, batch_id, batch_step_id, stage_seq, stage_name, dev_no, title,
                description, classification, status, qms_deviation_id, qms_status, qms_form_json, raised_by, raised_at)
                VALUES ('" . $plant_id . "', $batch_id, " . (int)ebmrbpr_in($input, 'batch_step_id', 0) . ", $stage_seq, '$stage_name',
                '$dev_no', '$title', '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'detailsOfDev')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'classification', 'Minor')) . "',
                'In QMS Workflow', $qms_id, 'Pending', '$formJson', '" . $emp_id . "', '" . $entry_date . "')");
            ebmrbpr_log($conn, $batch_id, 'QMS_DEVIATION_INITIATED', $dev_no . ' stage ' . ebmrbpr_in($input, 'stage_name'), $emp_id);
            ebmrbpr_ok(array('id' => $conn->insert_id, 'dev_no' => $dev_no, 'qms_deviation_id' => $qms_id));
        }
    }
}
elseif ($type == "saveDeviationProdHeadRemark") {
    $id = (int)ebmrbpr_in($input, 'id', 0);
    $continue = ebmrbpr_esc($conn, ebmrbpr_in($input, 'prod_head_continue', 'No'));
    $remark = ebmrbpr_esc($conn, ebmrbpr_in($input, 'prod_head_remark'));
    if ($id <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid deviation'));
    } elseif ($remark === '') {
        echo json_encode(array('status' => 'error', 'message' => 'Production Head remark required'));
    } else {
        $sql = "UPDATE ebmrbpr_deviation SET prod_head_continue='$continue', prod_head_remark='$remark',
            prod_head_by='" . $emp_id . "', prod_head_at='" . $entry_date . "' WHERE id=$id";
        if ($conn->query($sql)) {
            $bid = (int)ebmrbpr_in($input, 'batch_id', 0);
            ebmrbpr_log($conn, $bid, 'DEV_PROD_HEAD_REMARK', 'Continue next stage: ' . $continue . ' — ' . ebmrbpr_in($input, 'prod_head_remark'), $emp_id);
            ebmrbpr_ok();
        } else {
            ebmrbpr_err($conn);
        }
    }
}
elseif ($type == "closeDeviation") {
    $id = (int)ebmrbpr_in($input, 'id', 0);
    if ($conn->query("UPDATE ebmrbpr_deviation SET status='Closed', closed_by='" . $emp_id . "', closed_at='" . $entry_date . "',
        capa='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'capa')) . "' WHERE id=$id")) {
        ebmrbpr_log($conn, (int)ebmrbpr_in($input, 'batch_id', 0), 'DEVIATION_CLOSED', 'Deviation #' . $id . ' closed', $emp_id);
        ebmrbpr_ok();
    } else { ebmrbpr_err($conn); }
}
elseif ($type == "deleteDeviation") {
    $id = (int)($_GET['id'] ?? 0);
    if ($conn->query("UPDATE ebmrbpr_deviation SET status='Cancelled' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}

/* ===========================================================
   INCIDENT REGISTER
=========================================================== */
elseif ($type == "saveIncident") {
    $id = (int)ebmrbpr_in($input, 'id', 0);
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    if ($id > 0) {
        $sql = "UPDATE ebmrbpr_incident SET
            title='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'title')) . "',
            description='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'description')) . "',
            immediate_action='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'immediate_action')) . "',
            investigation='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'investigation')) . "',
            severity='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'severity', 'Low')) . "'
            WHERE id=$id";
        if ($conn->query($sql)) { ebmrbpr_ok(array('id' => $id)); } else { ebmrbpr_err($conn); }
    } else {
        $inc_no = ebmrbpr_next_code($conn, 'ebmrbpr_incident', 'inc_no', 'INC');
        $sql = "INSERT INTO ebmrbpr_incident
            (plant_id, batch_id, batch_step_id, inc_no, title, description, immediate_action, investigation, severity, status, raised_by, raised_at)
            VALUES (
                '" . $plant_id . "', $batch_id, " . (int)ebmrbpr_in($input, 'batch_step_id', 0) . ",
                '" . ebmrbpr_esc($conn, $inc_no) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'title')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'description')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'immediate_action')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'investigation')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'severity', 'Low')) . "',
                'Open', '" . $emp_id . "', '" . $entry_date . "'
            )";
        if ($conn->query($sql)) {
            ebmrbpr_log($conn, $batch_id, 'INCIDENT_RAISED', $inc_no . ': ' . ebmrbpr_in($input, 'title'), $emp_id);
            ebmrbpr_ok(array('id' => $conn->insert_id, 'inc_no' => $inc_no));
        } else { ebmrbpr_err($conn); }
    }
}
elseif ($type == "getIncidents") {
    $output = array();
    $res = $conn->query("SELECT * FROM ebmrbpr_incident WHERE batch_id=" . (int)($_GET['id'] ?? 0) . " AND status<>'Cancelled' ORDER BY stage_seq ASC, id DESC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            ebmrbpr_enrich_incident_row($conn, $r);
            if (!empty($r['qms_form_json'])) {
                $r['qms_form'] = json_decode($r['qms_form_json'], true);
            }
            $output[] = $r;
        }
    }
    echo json_encode($output);
}
elseif ($type == "initiateQmsIncident") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $bres = $conn->query("SELECT * FROM ebmrbpr_batch WHERE id=$batch_id LIMIT 1");
    if (!$bres || $bres->num_rows === 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Batch not found'));
    } elseif (!ebmrbpr_in($input, 'INR_Details')) {
        echo json_encode(array('status' => 'error', 'message' => 'Incident details required'));
    } else {
        $batch = $bres->fetch_assoc();
        $form = is_array($input) ? $input : array();
        $form['Name_of_Department'] = ebmrbpr_in($form, 'Name_of_Department', $_GET['department'] ?? 'Production');
        $ins = ebmrbpr_insert_qms_incident($conn, $emp_id, $entry_date, $form, $batch);
        if (!$ins['ok']) {
            echo json_encode(array('status' => 'error', 'message' => $ins['message']));
        } else {
            $qms_id = (int)$ins['id'];
            $inc_no = ebmrbpr_esc($conn, $ins['inr_no']);
            $stage_seq = (int)ebmrbpr_in($input, 'stage_seq', 0);
            $stage_name = ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_name'));
            $title = ebmrbpr_esc($conn, ebmrbpr_in($input, 'title', substr(ebmrbpr_in($input, 'INR_Details'), 0, 120)));
            $formJson = ebmrbpr_esc($conn, json_encode($form));
            $conn->query("INSERT INTO ebmrbpr_incident (plant_id, batch_id, batch_step_id, stage_seq, stage_name, inc_no, title,
                description, severity, status, qms_incident_id, qms_status, qms_form_json, raised_by, raised_at)
                VALUES ('" . $plant_id . "', $batch_id, " . (int)ebmrbpr_in($input, 'batch_step_id', 0) . ", $stage_seq, '$stage_name',
                '$inc_no', '$title', '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'INR_Details')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'classification_inr', 'Minor')) . "',
                'In QMS Workflow', $qms_id, 'send for review', '$formJson', '" . $emp_id . "', '" . $entry_date . "')");
            ebmrbpr_log($conn, $batch_id, 'QMS_INCIDENT_INITIATED', $inc_no . ' stage ' . ebmrbpr_in($input, 'stage_name'), $emp_id);
            ebmrbpr_ok(array('id' => $conn->insert_id, 'inc_no' => $ins['inr_no'], 'qms_incident_id' => $qms_id));
        }
    }
}
elseif ($type == "saveIncidentNextStageRemark") {
    $id = (int)ebmrbpr_in($input, 'id', 0);
    $remark = ebmrbpr_esc($conn, ebmrbpr_in($input, 'next_stage_remark'));
    if ($id <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid incident'));
    } elseif ($remark === '') {
        echo json_encode(array('status' => 'error', 'message' => 'Next stage remark required'));
    } else {
        $sql = "UPDATE ebmrbpr_incident SET next_stage_remark='$remark', next_stage_remark_by='" . $emp_id . "',
            next_stage_remark_at='" . $entry_date . "' WHERE id=$id";
        if ($conn->query($sql)) {
            $bid = (int)ebmrbpr_in($input, 'batch_id', 0);
            ebmrbpr_log($conn, $bid, 'INCIDENT_NEXT_STAGE_REMARK', ebmrbpr_in($input, 'next_stage_remark'), $emp_id);
            ebmrbpr_ok();
        } else {
            ebmrbpr_err($conn);
        }
    }
}
elseif ($type == "closeIncident") {
    $id = (int)ebmrbpr_in($input, 'id', 0);
    if ($conn->query("UPDATE ebmrbpr_incident SET status='Closed', closed_by='" . $emp_id . "', closed_at='" . $entry_date . "' WHERE id=$id")) {
        ebmrbpr_log($conn, (int)ebmrbpr_in($input, 'batch_id', 0), 'INCIDENT_CLOSED', 'Incident #' . $id . ' closed', $emp_id);
        ebmrbpr_ok();
    } else { ebmrbpr_err($conn); }
}
elseif ($type == "deleteIncident") {
    $id = (int)($_GET['id'] ?? 0);
    if ($conn->query("UPDATE ebmrbpr_incident SET status='Cancelled' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}

/* ===========================================================
   BREAKDOWN MAINTENANCE (Engineering intimation linked to batch/stage)
=========================================================== */
elseif ($type == "getBreakdowns") {
    $output = array();
    $res = $conn->query("SELECT * FROM ebmrbpr_breakdown WHERE batch_id=" . (int)($_GET['id'] ?? 0) . " ORDER BY stage_seq ASC, id DESC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            ebmrbpr_enrich_breakdown_row($conn, $r);
            if (!empty($r['form_json'])) {
                $r['form'] = json_decode($r['form_json'], true);
            }
            $output[] = $r;
        }
    }
    echo json_encode($output);
}
elseif ($type == "initiateBreakdown") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    if (!$batch_id || !ebmrbpr_in($input, 'machine_name') || !ebmrbpr_in($input, 'description')) {
        echo json_encode(array('status' => 'error', 'message' => 'Equipment and description required'));
    } else {
        $dept = ebmrbpr_esc($conn, ebmrbpr_in($input, 'department', $_GET['department'] ?? 'Production'));
        $year = date('Y');
        $prefix = 'BIO/ENG/BD/' . $year . '/';
        $like = ebmrbpr_esc($conn, $prefix) . '%';
        $nq = $conn->query("SELECT intimation_no FROM breakdown_intimation WHERE plant_id='" . $plant_id . "' AND intimation_no LIKE '$like' ORDER BY id DESC LIMIT 1");
        $next = 1;
        if ($nq && $nq->num_rows > 0) {
            $last = (string)$nq->fetch_assoc()['intimation_no'];
            if (preg_match('/(\d+)\s*$/', $last, $m)) {
                $next = (int)$m[1] + 1;
            }
        }
        $intNo = $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
        $bdDt = ebmrbpr_esc($conn, ebmrbpr_in($input, 'breakdown_datetime', $entry_date));
        $machine = ebmrbpr_esc($conn, ebmrbpr_in($input, 'machine_name'));
        $machineId = ebmrbpr_esc($conn, ebmrbpr_in($input, 'machine_id'));
        $area = ebmrbpr_esc($conn, ebmrbpr_in($input, 'area_location'));
        $nature = ebmrbpr_esc($conn, ebmrbpr_in($input, 'nature_of_breakdown', 'Mechanical'));
        $desc = ebmrbpr_esc($conn, ebmrbpr_in($input, 'description'));
        $conn->query("INSERT INTO breakdown_intimation (plant_id, intimation_no, department, machine_name, machine_id,
            area_location, breakdown_datetime, nature_of_breakdown, description, initiated_name, initiated_sign, initiated_at,
            workflow_status, entry_by, entry_date)
            VALUES ('" . $plant_id . "','$intNo','$dept','$machine','$machineId','$area','$bdDt','$nature','$desc',
            '" . $emp_id . "','" . $emp_id . "','$entry_date','pending_qa_review','" . $emp_id . "','$entry_date')");
        if (!$conn->insert_id) {
            echo json_encode(array('status' => 'error', 'message' => $conn->error));
        } else {
            $bd_id = (int)$conn->insert_id;
            $stage_seq = (int)ebmrbpr_in($input, 'stage_seq', 0);
            $stage_name = ebmrbpr_esc($conn, ebmrbpr_in($input, 'stage_name'));
            $formJson = ebmrbpr_esc($conn, json_encode($input));
            $conn->query("INSERT INTO ebmrbpr_breakdown (plant_id, batch_id, stage_seq, stage_name, breakdown_id, intimation_no,
                machine_name, machine_id, area_location, nature_of_breakdown, description, workflow_status, form_json, raised_by, raised_at)
                VALUES ('" . $plant_id . "', $batch_id, $stage_seq, '$stage_name', $bd_id, '$intNo', '$machine', '$machineId',
                '$area', '$nature', '$desc', 'pending_qa_review', '$formJson', '" . $emp_id . "', '" . $entry_date . "')");
            ebmrbpr_log($conn, $batch_id, 'BREAKDOWN_INITIATED', $intNo . ' — ' . ebmrbpr_in($input, 'machine_name'), $emp_id);
            ebmrbpr_ok(array('id' => $conn->insert_id, 'breakdown_id' => $bd_id, 'intimation_no' => $intNo));
        }
    }
}

/* ===========================================================
   IPQC / SAMPLING (eBMR ↔ IPQA ↔ IPQC ↔ QC Testing)
=========================================================== */
elseif ($type == "getSamplings") {
    $batch_id = (int)($_GET['id'] ?? 0);
    $step_id = (int)($_GET['step_id'] ?? 0);
    $output = array();
    $where = "batch_id=$batch_id";
    if ($step_id > 0) {
        $where .= " AND batch_step_id=$step_id";
    }
    $res = $conn->query("SELECT * FROM ebmrbpr_sampling WHERE $where ORDER BY stage_seq ASC, step_seq ASC, id DESC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            ebmrbpr_sync_sampling_from_technical($conn, $r);
            if (!empty($r['spec_json'])) {
                $r['specs'] = json_decode($r['spec_json'], true);
            }
            $output[] = $r;
        }
    }
    echo json_encode($output);
}
elseif ($type == "getIpqaSamplingQueue") {
    $output = array();
    $res = $conn->query("SELECT s.*, b.batch_no, b.product_name, b.product_code, b.profile_code
        FROM ebmrbpr_sampling s
        JOIN ebmrbpr_batch b ON b.id = s.batch_id
        WHERE s.plant_id='" . $plant_id . "'
          AND (s.status='pending_ipqa' OR (s.sampling_by='Production' AND s.ipqa_notified=1 AND s.status NOT IN ('approved','cancelled')))
        ORDER BY s.id DESC LIMIT 200");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            if (!empty($r['spec_json'])) {
                $r['specs'] = json_decode($r['spec_json'], true);
            }
            $output[] = $r;
        }
    }
    echo json_encode($output);
}
elseif ($type == "getEbmrQcSamplingQueue") {
    $output = array();
    $res = $conn->query("SELECT s.*, b.batch_no, b.product_name, b.product_code, b.profile_code, t.status AS ti_status, t.ar_no AS ti_ar_no
        FROM ebmrbpr_sampling s
        JOIN ebmrbpr_batch b ON b.id = s.batch_id
        LEFT JOIN technical_info t ON t.id = s.technical_info_id
        WHERE s.plant_id='" . $plant_id . "'
          AND s.technical_info_id > 0
          AND s.status IN ('pending_qc_receive','qc_accepted','in_testing','approved')
        ORDER BY s.id DESC LIMIT 200");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            ebmrbpr_sync_sampling_from_technical($conn, $r);
            if (!empty($r['spec_json'])) {
                $r['specs'] = json_decode($r['spec_json'], true);
            }
            $output[] = $r;
        }
    }
    echo json_encode($output);
}
elseif ($type == "raiseSamplingIntimation") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $step_id = (int)ebmrbpr_in($input, 'batch_step_id', 0);
    if (!$batch_id || !$step_id || !ebmrbpr_in($input, 'sample_qty')) {
        echo json_encode(array('status' => 'error', 'message' => 'Batch, step and sample quantity required'));
        exit;
    }
    if (!trim((string)ebmrbpr_in($input, 'sample_by'))) {
        echo json_encode(array('status' => 'error', 'message' => 'Sampled by is required'));
        exit;
    }
    $qtyRaw = trim((string)ebmrbpr_in($input, 'sample_qty'));
    if (!is_numeric($qtyRaw) || (float)$qtyRaw <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Sample quantity must be a positive number'));
        exit;
    }
    $batchMsg = ebmrbpr_batch_mutable_message($conn, $batch_id);
    if ($batchMsg !== '') {
        echo json_encode(array('status' => 'error', 'message' => $batchMsg));
        exit;
    }
    $esignMsg = ebmrbpr_require_valid_esign($conn, $input, $emp_id);
    if ($esignMsg !== '') {
        echo json_encode(array('status' => 'error', 'message' => $esignMsg));
        exit;
    }
    $access = ebmrbpr_batch_access_from_id($conn, $batch_id, $emp_id, $_GET['department'] ?? '');
    if (($access['mode'] ?? '') === 'denied' || !ebmrbpr_assert_step_mutable($conn, $step_id, $access, true)) {
        echo json_encode(array('status' => 'error', 'message' => 'You can only raise sampling for your allocated stage'));
        exit;
    }
    $bres = $conn->query("SELECT * FROM ebmrbpr_batch WHERE id=$batch_id LIMIT 1");
    $sres = $conn->query("SELECT * FROM ebmrbpr_batch_step WHERE id=$step_id AND batch_id=$batch_id LIMIT 1");
    if (!$bres || $bres->num_rows === 0 || !$sres || $sres->num_rows === 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Batch or step not found'));
        exit;
    }
    $batch = $bres->fetch_assoc();
    $stepRow = $sres->fetch_assoc();
    if (strtoupper(trim((string)($stepRow['ipqc_testing'] ?? 'No'))) !== 'YES') {
        echo json_encode(array('status' => 'error', 'message' => 'This step is not configured for IPQC testing'));
        exit;
    }
    $dup = $conn->query("SELECT id FROM ebmrbpr_sampling WHERE batch_step_id=$step_id AND status NOT IN ('cancelled','rejected') LIMIT 1");
    if ($dup && $dup->num_rows > 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Sampling intimation already exists for this step'));
        exit;
    }
    $template = json_decode($stepRow['template_json'], true);
    $specs = ebmrbpr_fetch_step_ipqc_specs($conn, $batch['product_code'], $stepRow['stage_name'], isset($template['ipqc']) ? $template['ipqc'] : array());
    $samplingBy = trim((string)($stepRow['sampling_by'] ?? 'Production'));
    if (!in_array($samplingBy, array('IPQA', 'Production'), true)) {
        $samplingBy = 'Production';
    }
    $specJson = ebmrbpr_esc($conn, json_encode($specs));
    $formJson = ebmrbpr_esc($conn, json_encode($input));
    $status = ($samplingBy === 'IPQA') ? 'pending_ipqa' : 'pending_qc_receive';
    $ipqaNotified = 1;
    $qcNotified = ($samplingBy === 'Production') ? 1 : 0;
    $conn->begin_transaction();
    try {
        $sql = "INSERT INTO ebmrbpr_sampling (plant_id, batch_id, batch_step_id, stage_seq, stage_name, step_seq, step_name,
            product_code, batch_no, sampling_by, sample_qty, unit, sample_id, equipment_code, sample_by, sampled_at, spec_json, status,
            ipqa_notified, qc_notified, form_json, raised_by, raised_at)
            VALUES (
                '" . $plant_id . "', $batch_id, $step_id,
                " . (int)$stepRow['stage_seq'] . ",
                '" . ebmrbpr_esc($conn, $stepRow['stage_name']) . "',
                " . (int)$stepRow['step_seq'] . ",
                '" . ebmrbpr_esc($conn, $stepRow['step_name']) . "',
                '" . ebmrbpr_esc($conn, $batch['product_code']) . "',
                '" . ebmrbpr_esc($conn, $batch['batch_no']) . "',
                '" . ebmrbpr_esc($conn, $samplingBy) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'sample_qty')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'unit')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'sample_id')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'equipment_code')) . "',
                '" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'sample_by')) . "',
                '$entry_date',
                '$specJson', '$status', $ipqaNotified, $qcNotified, '$formJson', '" . $emp_id . "', '$entry_date'
            )";
        if (!$conn->query($sql)) {
            throw new Exception($conn->error);
        }
        $sampling_id = (int)$conn->insert_id;
        $ti_id = 0;
        if ($samplingBy === 'Production') {
            $ti_id = ebmrbpr_create_technical_info_for_sampling($conn, $plant_id, $emp_id, $entry_date, $batch, $stepRow, $sampling_id, $input);
            if ($ti_id <= 0) {
                throw new Exception('Failed to create QC sample intimation');
            }
            $conn->query("UPDATE ebmrbpr_sampling SET technical_info_id=$ti_id, qc_notified=1 WHERE id=$sampling_id");
        }
        $conn->commit();
        $logMsg = 'Sample sent to QC — ' . $stepRow['stage_name'] . ' / ' . $stepRow['step_name'] . ' (' . $samplingBy . ') by ' . ebmrbpr_in($input, 'sample_by');
        ebmrbpr_log($conn, $batch_id, 'SAMPLING_RAISED', $logMsg, $emp_id);
        ebmrbpr_ok(array(
            'id' => $sampling_id,
            'technical_info_id' => $ti_id,
            'status' => $status,
            'sampling_by' => $samplingBy,
            'specs' => $specs,
        ));
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
    }
}
elseif ($type == "getStepIpqcPreview") {
    $batch_id = (int)($_GET['batch_id'] ?? ebmrbpr_in($input, 'batch_id', 0));
    $step_id = (int)($_GET['step_id'] ?? ebmrbpr_in($input, 'batch_step_id', 0));
    if (!$batch_id || !$step_id) {
        echo json_encode(array('status' => 'error', 'message' => 'batch_id and step_id required'));
        exit;
    }
    $bres = $conn->query("SELECT * FROM ebmrbpr_batch WHERE id=$batch_id LIMIT 1");
    $sres = $conn->query("SELECT * FROM ebmrbpr_batch_step WHERE id=$step_id AND batch_id=$batch_id LIMIT 1");
    if (!$bres || $bres->num_rows === 0 || !$sres || $sres->num_rows === 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Batch or step not found'));
        exit;
    }
    $batch = $bres->fetch_assoc();
    $stepRow = $sres->fetch_assoc();
    $template = json_decode($stepRow['template_json'], true);
    $specs = ebmrbpr_fetch_step_ipqc_specs(
        $conn,
        $batch['product_code'],
        $stepRow['stage_name'],
        isset($template['ipqc']) ? $template['ipqc'] : array()
    );
    $data = json_decode($stepRow['data_json'], true);
    if (!is_array($data)) {
        $data = array();
    }
    echo json_encode(array(
        'status' => 'success',
        'ipqc_testing' => $stepRow['ipqc_testing'] ?? 'No',
        'sampling_by' => $stepRow['sampling_by'] ?? 'Production',
        'stage_name' => $stepRow['stage_name'],
        'step_name' => $stepRow['step_name'],
        'specs' => $specs,
        'results' => isset($data['ipqc_results']) && is_array($data['ipqc_results']) ? $data['ipqc_results'] : array(),
        'ipqc_status' => $data['ipqc_status'] ?? '',
        'ipqc_ar_no' => $data['ipqc_ar_no'] ?? '',
        'ipqc_hold_released' => !empty($data['ipqc_hold_released']),
    ));
}
elseif ($type == "getSamplingPrintPack") {
    $id = (int)($_GET['id'] ?? ebmrbpr_in($input, 'id', 0));
    if ($id <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Sampling id required'));
        exit;
    }
    $res = $conn->query("SELECT s.*, b.product_name, b.product_code, b.batch_no, b.batch_size, b.profile_code
        FROM ebmrbpr_sampling s
        JOIN ebmrbpr_batch b ON b.id = s.batch_id
        WHERE s.id=$id LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Sampling not found'));
        exit;
    }
    $row = $res->fetch_assoc();
    ebmrbpr_sync_sampling_from_technical($conn, $row);
    if (!empty($row['spec_json'])) {
        $row['specs'] = json_decode($row['spec_json'], true);
    }
    ebmrbpr_ok(array('sampling' => $row));
}

elseif ($type == "ipqaAcceptSampling") {
    $id = (int)ebmrbpr_in($input, 'id', 0);
    if ($id <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Sampling id required'));
        exit;
    }
    $res = $conn->query("SELECT s.*, b.batch_no, b.product_code, b.batch_size, b.product_name
        FROM ebmrbpr_sampling s JOIN ebmrbpr_batch b ON b.id=s.batch_id WHERE s.id=$id LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Sampling record not found'));
        exit;
    }
    $row = $res->fetch_assoc();
    if ($row['status'] !== 'pending_ipqa') {
        echo json_encode(array('status' => 'error', 'message' => 'This sampling is not awaiting IPQA action'));
        exit;
    }
    $batch = array('id' => $row['batch_id'], 'batch_no' => $row['batch_no'], 'product_code' => $row['product_code'], 'batch_size' => $row['batch_size']);
    $stepRow = array('stage_name' => $row['stage_name'], 'step_name' => $row['step_name']);
    $form = json_decode($row['form_json'], true);
    if (!is_array($form)) {
        $form = array();
    }
    $form['sample_qty'] = ebmrbpr_in($input, 'sample_qty', $row['sample_qty']);
    $form['unit'] = ebmrbpr_in($input, 'unit', $row['unit']);
    $form['sample_id'] = ebmrbpr_in($input, 'sample_id', $row['sample_id']);
    $form['equipment_code'] = ebmrbpr_in($input, 'equipment_code', $row['equipment_code']);
    $ti_id = ebmrbpr_create_technical_info_for_sampling($conn, $plant_id, $emp_id, $entry_date, $batch, $stepRow, $id, $form);
    if ($ti_id <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Failed to send sample to QC'));
        exit;
    }
    $conn->query("UPDATE ebmrbpr_sampling SET technical_info_id=$ti_id, status='pending_qc_receive', qc_notified=1,
        sample_qty='" . ebmrbpr_esc($conn, $form['sample_qty']) . "',
        unit='" . ebmrbpr_esc($conn, $form['unit']) . "',
        ipqa_accepted_by='" . $emp_id . "', ipqa_accepted_at='$entry_date'
        WHERE id=$id");
    ebmrbpr_log($conn, (int)$row['batch_id'], 'IPQA_SAMPLING_SENT', 'IPQA sent sample to QC — step ' . $row['step_name'], $emp_id);
    ebmrbpr_ok(array('technical_info_id' => $ti_id, 'status' => 'pending_qc_receive'));
}

/* ===========================================================
   YIELD STATEMENT + BATCH RELEASE
=========================================================== */
elseif ($type == "saveYieldStatement") {
    $id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $y = ebmrbpr_in($input, 'yield', array());
    if (is_array($y)) { $y = json_encode($y); }
    if ($conn->query("UPDATE ebmrbpr_batch SET yield_json='" . ebmrbpr_esc($conn, $y) . "' WHERE id=$id")) { ebmrbpr_ok(); } else { ebmrbpr_err($conn); }
}
elseif ($type == "releaseBatch") {
    $id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $row = $conn->query("SELECT status, work_order_id, record_type FROM ebmrbpr_batch WHERE id=$id LIMIT 1");
    $batchRow = $row && $row->num_rows ? $row->fetch_assoc() : null;
    $st = $batchRow ? $batchRow['status'] : '';
    if ($st !== 'Approved') {
        echo json_encode(array('status' => 'error', 'message' => 'Batch must be Approved by both Production & QA before release (current: ' . $st . ')'));
    } else {
        $recordType = trim((string)($batchRow['record_type'] ?? 'eBMR'));
        // eBMR → packing; eBPR → FG / transfer release
        $newStatus = ($recordType === 'eBPR') ? 'Released' : 'Released for Packing';
        if ($conn->query("UPDATE ebmrbpr_batch SET status='" . ebmrbpr_esc($conn, $newStatus) . "', released_by='" . $emp_id . "', released_at='" . $entry_date . "',
            release_remark='" . ebmrbpr_esc($conn, ebmrbpr_in($input, 'remark')) . "' WHERE id=$id")) {
            ebmrbpr_log($conn, $id, 'BATCH_RELEASED', $newStatus . '. ' . ebmrbpr_in($input, 'remark'), $emp_id);
            ebmrbpr_sync_work_order_on_release($conn, $id, $emp_id, $entry_date, $plant_id);
            ebmrbpr_ok(array('batch_status' => $newStatus));
        } else { ebmrbpr_err($conn); }
    }
}
elseif ($type == "holdBatch") {
    $id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $remark = trim((string)ebmrbpr_in($input, 'remark', ''));
    $row = $conn->query("SELECT status FROM ebmrbpr_batch WHERE id=$id LIMIT 1");
    $st = ($row && $row->num_rows) ? $row->fetch_assoc()['status'] : '';
    $allowed = array('In Progress', 'Correction Required', 'Submitted for Approval', 'Rejected', 'Approved');
    if (!in_array($st, $allowed, true)) {
        echo json_encode(array('status' => 'error', 'message' => 'Cannot put batch On Hold from status: ' . $st));
    } else {
        $rmkSql = $remark !== '' ? ", release_remark='" . ebmrbpr_esc($conn, $remark) . "'" : '';
        if ($conn->query("UPDATE ebmrbpr_batch SET status='On Hold', hold_prev_status='" . ebmrbpr_esc($conn, $st) . "'$rmkSql WHERE id=$id")) {
            ebmrbpr_log($conn, $id, 'BATCH_ON_HOLD', 'Batch put On Hold (was ' . $st . '). ' . $remark, $emp_id);
            ebmrbpr_ok(array('batch_status' => 'On Hold'));
        } else { ebmrbpr_err($conn); }
    }
}
elseif ($type == "resumeBatch") {
    $id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $row = $conn->query("SELECT status, hold_prev_status, prod_signoff, qa_signoff FROM ebmrbpr_batch WHERE id=$id LIMIT 1");
    $b = ($row && $row->num_rows) ? $row->fetch_assoc() : null;
    $st = $b ? $b['status'] : '';
    if ($st !== 'On Hold') {
        echo json_encode(array('status' => 'error', 'message' => 'Only On Hold batches can be resumed (current: ' . $st . ')'));
    } else {
        $resume = trim((string)($b['hold_prev_status'] ?? ''));
        if ($resume === '' || $resume === 'On Hold') {
            if (($b['prod_signoff'] ?? '') === 'Approved' && ($b['qa_signoff'] ?? '') === 'Approved') {
                $resume = 'Approved';
            } else {
                $resume = 'In Progress';
            }
        }
        if ($conn->query("UPDATE ebmrbpr_batch SET status='" . ebmrbpr_esc($conn, $resume) . "', hold_prev_status=NULL WHERE id=$id")) {
            ebmrbpr_log($conn, $id, 'BATCH_RESUMED', 'Batch resumed to ' . $resume, $emp_id);
            ebmrbpr_ok(array('batch_status' => $resume));
        } else { ebmrbpr_err($conn); }
    }
}
elseif ($type == "cancelBatch") {
    $id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $remark = trim((string)ebmrbpr_in($input, 'remark', ''));
    if ($remark === '') {
        echo json_encode(array('status' => 'error', 'message' => 'Cancellation remark is required'));
        exit;
    }
    $row = $conn->query("SELECT status FROM ebmrbpr_batch WHERE id=$id LIMIT 1");
    $st = ($row && $row->num_rows) ? $row->fetch_assoc()['status'] : '';
    $blocked = array('Released', 'Released for Packing', 'Cancelled', 'Deleted');
    if (in_array($st, $blocked, true)) {
        echo json_encode(array('status' => 'error', 'message' => 'Cannot cancel batch in status: ' . $st));
    } else {
        if ($conn->query("UPDATE ebmrbpr_batch SET status='Cancelled', release_remark='" . ebmrbpr_esc($conn, $remark) . "' WHERE id=$id")) {
            ebmrbpr_log($conn, $id, 'BATCH_CANCELLED', 'Batch cancelled. ' . $remark, $emp_id);
            ebmrbpr_ok(array('batch_status' => 'Cancelled'));
        } else { ebmrbpr_err($conn); }
    }
}
elseif ($type == "getYieldReconciliationLog") {
    $output = array();
    $dosage = ebmrbpr_esc($conn, $_GET['dosage_form'] ?? '');
    $product = ebmrbpr_esc($conn, $_GET['product_code'] ?? '');
    $from = ebmrbpr_esc($conn, $_GET['from_date'] ?? '');
    $to = ebmrbpr_esc($conn, $_GET['to_date'] ?? '');
    $where = "eb.status IN ('Released','Released for Packing') AND eb.record_type='eBMR' AND eb.plant_id='" . $plant_id . "'";
    if ($dosage !== '') {
        $where .= " AND p.dosage_form LIKE '%$dosage%'";
    }
    if ($product !== '') {
        $where .= " AND eb.product_code LIKE '%$product%'";
    }
    if ($from !== '' && $to !== '') {
        $where .= " AND DATE(IFNULL(eb.released_at, eb.entry_date)) BETWEEN '$from' AND '$to'";
    }
    $sql = "SELECT eb.id AS ebmr_batch_id, eb.batch_no AS batch_number, eb.product_code, eb.product_name, eb.plan_no,
            eb.batch_size, eb.batch_size_uom, eb.released_at, eb.yield_json, eb.mfg_date,
            p.grade, p.dosage_form,
            a.batch_commence_date, a.batch_complete_date, a.actual_yeild, a.yeild_percentage
            FROM ebmrbpr_batch eb
            LEFT JOIN mfg_work_order_hdr a ON a.id = eb.work_order_id
            LEFT JOIN product p ON p.product_code = eb.product_code AND p.plant_id = eb.plant_id
            WHERE $where
            ORDER BY eb.released_at DESC, eb.id DESC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $ys = ebmrbpr_extract_yield_summary($conn, (int)$row['ebmr_batch_id']);
            $row['stage_yield'] = $row['actual_yeild'] !== '' && $row['actual_yeild'] !== null ? $row['actual_yeild'] : $ys['actual_qty'];
            $row['stage_yield_percent'] = $row['yeild_percentage'] !== '' && $row['yeild_percentage'] !== null ? $row['yeild_percentage'] : $ys['yield_pct'];
            $row['stage_yield_unit'] = $ys['uom'] !== '' ? $ys['uom'] : ($row['batch_size_uom'] ?? '');
            $row['yeild_entry_date'] = $row['batch_commence_date'] ?: $row['mfg_date'];
            $row['source'] = 'eBMR';
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

/* ===========================================================
   STAGE WORKFLOW — Send for Checking / Check / Approve / QA
=========================================================== */
elseif ($type == "sendStageForChecking") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $stage_seq = (int)ebmrbpr_in($input, 'stage_seq', 0);
    $signerName = ebmrbpr_esc($conn, ebmrbpr_in($input, 'signer_name', ''));
    $signerDesig = ebmrbpr_esc($conn, ebmrbpr_in($input, 'signer_designation', ''));
    $stg = ebmrbpr_batch_stage_by_seq($conn, $batch_id, $stage_seq);
    if (!$stg) {
        echo json_encode(array('status' => 'error', 'message' => 'Stage not found'));
    } else {
        $pending = $conn->query("SELECT COUNT(*) AS c FROM ebmrbpr_batch_step WHERE batch_id=$batch_id AND stage_seq=$stage_seq AND status NOT IN ('Done','Checked')");
        $pc = $pending ? (int)$pending->fetch_assoc()['c'] : 0;
        if ($pc > 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Complete all steps in this stage before sending for checking'));
        } else {
            if ($signerName === '') {
                $signerName = ebmrbpr_esc($conn, $emp_id);
            }
            $conn->query("UPDATE ebmrbpr_batch_stage SET status='Awaiting Check', sent_for_checking_by='" . $emp_id . "',
                sent_for_checking_at='" . $entry_date . "', check_status='Pending',
                doer_name='$signerName', doer_designation='$signerDesig'
                WHERE id=" . (int)$stg['id']);
            ebmrbpr_log($conn, $batch_id, 'STAGE_SENT_FOR_CHECKING', 'Stage ' . $stg['stage_name'] . ' sent for checking by ' . $signerName, $emp_id);
            ebmrbpr_ok();
        }
    }
}
elseif ($type == "checkStage") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $stage_seq = (int)ebmrbpr_in($input, 'stage_seq', 0);
    $action = ebmrbpr_in($input, 'action', 'Approved');
    $remark = ebmrbpr_esc($conn, ebmrbpr_in($input, 'remark'));
    $signerName = ebmrbpr_esc($conn, ebmrbpr_in($input, 'signer_name', ''));
    $signerDesig = ebmrbpr_esc($conn, ebmrbpr_in($input, 'signer_designation', ''));
    if ($signerName === '') {
        $signerName = ebmrbpr_esc($conn, $emp_id);
    }
    $stg = ebmrbpr_batch_stage_by_seq($conn, $batch_id, $stage_seq);
    if (!$stg) {
        echo json_encode(array('status' => 'error', 'message' => 'Stage not found'));
    } elseif ($stg['status'] !== 'Awaiting Check' && $stg['check_status'] === 'Approved') {
        echo json_encode(array('status' => 'error', 'message' => 'Stage is not awaiting checking'));
    } else {
        if ($action === 'Rejected') {
            $conn->query("UPDATE ebmrbpr_batch_stage SET check_status='Rejected', checked_by='" . $emp_id . "', checked_at='" . $entry_date . "',
                checker_remark='$remark', reject_remark='$remark', status='Correction Required',
                checker_name='$signerName', checker_designation='$signerDesig'
                WHERE id=" . (int)$stg['id']);
            $conn->query("UPDATE ebmrbpr_batch_step SET status='Correction Required', checker_remark='$remark'
                WHERE batch_id=$batch_id AND stage_seq=$stage_seq AND status IN ('Done','Checked')");
            ebmrbpr_log($conn, $batch_id, 'STAGE_CHECK_REJECTED', 'Stage ' . $stg['stage_name'] . ' rejected: ' . $remark, $emp_id);
        } else {
            $conn->query("UPDATE ebmrbpr_batch_stage SET check_status='Approved', checked_by='" . $emp_id . "', checked_at='" . $entry_date . "',
                checker_remark='$remark', status='Checked',
                checker_name='$signerName', checker_designation='$signerDesig'
                WHERE id=" . (int)$stg['id']);
            $conn->query("UPDATE ebmrbpr_batch_step SET status='Checked', checked_by='" . $emp_id . "', checked_at='" . $entry_date . "',
                checker_remark='$remark' WHERE batch_id=$batch_id AND stage_seq=$stage_seq AND status='Done'");
            if ((int)$stg['approval_required'] === 1) {
                $conn->query("UPDATE ebmrbpr_batch_stage SET status='Awaiting Approval', approval_status='Pending' WHERE id=" . (int)$stg['id']);
            } elseif ((int)$stg['qa_check_required'] === 1) {
                $conn->query("UPDATE ebmrbpr_batch_stage SET status='Awaiting QA Check', qa_check_status='Pending' WHERE id=" . (int)$stg['id']);
            } else {
                $conn->query("UPDATE ebmrbpr_batch_stage SET status='Approved' WHERE id=" . (int)$stg['id']);
            }
            ebmrbpr_log($conn, $batch_id, 'STAGE_CHECKED', 'Stage ' . $stg['stage_name'] . ' checked by ' . $signerName, $emp_id);
        }
        ebmrbpr_ok();
    }
}
elseif ($type == "approveStage") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $stage_seq = (int)ebmrbpr_in($input, 'stage_seq', 0);
    $action = ebmrbpr_in($input, 'action', 'Approved');
    $remark = ebmrbpr_esc($conn, ebmrbpr_in($input, 'remark'));
    $signerName = ebmrbpr_esc($conn, ebmrbpr_in($input, 'signer_name', ''));
    $signerDesig = ebmrbpr_esc($conn, ebmrbpr_in($input, 'signer_designation', ''));
    if ($signerName === '') {
        $signerName = ebmrbpr_esc($conn, $emp_id);
    }
    $stg = ebmrbpr_batch_stage_by_seq($conn, $batch_id, $stage_seq);
    if (!$stg || (int)$stg['approval_required'] !== 1) {
        echo json_encode(array('status' => 'error', 'message' => 'Stage approval not applicable'));
    } elseif ($stg['check_status'] !== 'Approved') {
        echo json_encode(array('status' => 'error', 'message' => 'Stage must be checked before approval'));
    } else {
        if ($action === 'Rejected') {
            $conn->query("UPDATE ebmrbpr_batch_stage SET approval_status='Rejected', approved_by='" . $emp_id . "', approved_at='" . $entry_date . "',
                approver_remark='$remark', reject_remark='$remark', status='Correction Required',
                approver_name='$signerName', approver_designation='$signerDesig'
                WHERE id=" . (int)$stg['id']);
            $conn->query("UPDATE ebmrbpr_batch_step SET status='Correction Required' WHERE batch_id=$batch_id AND stage_seq=$stage_seq");
        } else {
            $conn->query("UPDATE ebmrbpr_batch_stage SET approval_status='Approved', approved_by='" . $emp_id . "', approved_at='" . $entry_date . "',
                approver_remark='$remark',
                approver_name='$signerName', approver_designation='$signerDesig'
                WHERE id=" . (int)$stg['id']);
            if ((int)$stg['qa_check_required'] === 1) {
                $conn->query("UPDATE ebmrbpr_batch_stage SET status='Awaiting QA Check', qa_check_status='Pending' WHERE id=" . (int)$stg['id']);
            } else {
                $conn->query("UPDATE ebmrbpr_batch_stage SET status='Approved' WHERE id=" . (int)$stg['id']);
            }
        }
        ebmrbpr_ok();
    }
}
elseif ($type == "qaCheckStage") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $stage_seq = (int)ebmrbpr_in($input, 'stage_seq', 0);
    $action = ebmrbpr_in($input, 'action', 'Approved');
    $remark = ebmrbpr_esc($conn, ebmrbpr_in($input, 'remark'));
    $stg = ebmrbpr_batch_stage_by_seq($conn, $batch_id, $stage_seq);
    if (!$stg || (int)$stg['qa_check_required'] !== 1) {
        echo json_encode(array('status' => 'error', 'message' => 'QA checking not applicable for this stage'));
    } else {
        if ($action === 'Rejected') {
            $conn->query("UPDATE ebmrbpr_batch_stage SET qa_check_status='Rejected', qa_checked_by='" . $emp_id . "', qa_checked_at='" . $entry_date . "',
                qa_check_remark='$remark', status='Correction Required' WHERE id=" . (int)$stg['id']);
            $conn->query("UPDATE ebmrbpr_batch_step SET status='Correction Required' WHERE batch_id=$batch_id AND stage_seq=$stage_seq");
        } else {
            $conn->query("UPDATE ebmrbpr_batch_stage SET qa_check_status='Approved', qa_checked_by='" . $emp_id . "', qa_checked_at='" . $entry_date . "',
                qa_check_remark='$remark' WHERE id=" . (int)$stg['id']);
            if ((int)$stg['qa_approval_required'] === 1) {
                $conn->query("UPDATE ebmrbpr_batch_stage SET status='Awaiting QA Approval', qa_approval_status='Pending' WHERE id=" . (int)$stg['id']);
            } else {
                $conn->query("UPDATE ebmrbpr_batch_stage SET status='Approved' WHERE id=" . (int)$stg['id']);
            }
        }
        ebmrbpr_ok();
    }
}
elseif ($type == "qaApproveStage") {
    $batch_id = (int)ebmrbpr_in($input, 'batch_id', 0);
    $stage_seq = (int)ebmrbpr_in($input, 'stage_seq', 0);
    $action = ebmrbpr_in($input, 'action', 'Approved');
    $remark = ebmrbpr_esc($conn, ebmrbpr_in($input, 'remark'));
    $stg = ebmrbpr_batch_stage_by_seq($conn, $batch_id, $stage_seq);
    if (!$stg || (int)$stg['qa_approval_required'] !== 1) {
        echo json_encode(array('status' => 'error', 'message' => 'QA approval not applicable'));
    } else {
        if ($action === 'Rejected') {
            $conn->query("UPDATE ebmrbpr_batch_stage SET qa_approval_status='Rejected', qa_approved_by='" . $emp_id . "',
                qa_approval_status='Rejected', status='Correction Required' WHERE id=" . (int)$stg['id']);
        } else {
            $conn->query("UPDATE ebmrbpr_batch_stage SET qa_approval_status='Approved', qa_approved_by='" . $emp_id . "',
                qa_approved_at='" . $entry_date . "', status='Approved' WHERE id=" . (int)$stg['id']);
        }
        ebmrbpr_ok();
    }
}
elseif ($type == "seedAurenyxInjDemoPipeline") {
    $target = ebmrbpr_in($input, 'target', $_GET['target'] ?? 'production_report');
    $allowed = array('dispensing', 'allocation', 'execution', 'production_report');
    if (!in_array($target, $allowed, true)) {
        $target = 'production_report';
    }
    $result = ebmrbpr_seed_aurenyx_inj_demo($conn, $plant_id, $emp_id, $entry_date, $target);
    echo json_encode($result);
}
elseif ($type == "seedDemoEbmr" || $type == "ensureDemoEbmr" || $type == "ensureRealisticEbmr") {
    // Lookup only — never create demo at runtime. Pipeline data is DB-seeded via migrations.
    $ex = $conn->query("SELECT id, batch_no, profile_id, profile_code, status FROM ebmrbpr_batch
        WHERE plant_id='" . ebmrbpr_esc($conn, $plant_id) . "'
          AND record_type='eBMR'
          AND status<>'Deleted'
          AND (
            profile_code LIKE 'PIPE-%'
            OR profile_code LIKE 'RBMR-%'
            OR batch_no LIKE '%/PIPE%'
            OR entry_by='Cyclone_Seed'
          )
        ORDER BY
          CASE WHEN status='In Progress' THEN 0 WHEN status='Released' THEN 2 ELSE 1 END,
          CASE WHEN profile_code LIKE 'PIPE-%' THEN 0 ELSE 1 END,
          id DESC
        LIMIT 1");
    if ($ex && $ex->num_rows > 0) {
        $row = $ex->fetch_assoc();
        echo json_encode(array(
            'status' => 'success',
            'created' => false,
            'profile_id' => (int)$row['profile_id'],
            'batch_id' => (int)$row['id'],
            'batch_no' => $row['batch_no'],
            'batch_status' => $row['status'],
            'message' => 'Seeded pipeline BMR ready (no runtime create)',
            'open_url' => '/fproduction/ebmr/execution/' . (int)$row['id'] . '?mode=open&returnUrl=%2Ffproduction%2Febmr',
        ));
    } else {
        echo json_encode(array(
            'status' => 'error',
            'created' => false,
            'message' => 'No seeded pipeline BMR in database. Run migrations/seed_ebmrbpr_pipeline.php once.',
        ));
    }
}

else {
    echo json_encode(array('status' => 'error', 'message' => 'Unknown type: ' . $type));
}
?>
