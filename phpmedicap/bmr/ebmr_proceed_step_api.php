<?php
/**
 * eBMR Proceed — persist filled step JSON + batch approval.
 *
 * DEPLOY: place this file next to `bmr/process.php` on the server
 * (URL used by Angular: bmr/ebmr_proceed_step_api.php?type=...).
 *
 * Requires `db.php` one level up: phpDevelopCyclone/db.php
 * (adjust require path if your tree differs).
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');

require_once dirname(__DIR__) . '../db.php';

function ebmr_json_out($arr)
{
    echo json_encode($arr);
    exit;
}

function ebmr_escape($conn, $v)
{
    if ($v === null) {
        return '';
    }
    return mysqli_real_escape_string($conn, (string) $v);
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
$token_esc = ebmr_escape($conn, $token);
$sqlTok = "SELECT * FROM token WHERE token='" . $token_esc . "' LIMIT 1";
$resTok = $conn->query($sqlTok);
$_GET['emp_id'] = '';
if ($resTok && $resTok->num_rows > 0) {
    $rowT = $resTok->fetch_assoc();
    if (isset($rowT['emp_id']) && $rowT['emp_id'] !== '') {
        $_GET['emp_id'] = $rowT['emp_id'];
    }
} else {
    ebmr_json_out(['status' => 'error', 'msg' => 'Invalid or missing token']);
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$plant_id = isset($_GET['plant_id']) ? ebmr_escape($conn, $_GET['plant_id']) : '';
$emp_id = ebmr_escape($conn, isset($_GET['emp_id']) ? $_GET['emp_id'] : '');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = [];
}

if ($type === 'get_batch_steps') {
    $pc = isset($_GET['product_code']) ? ebmr_escape($conn, $_GET['product_code']) : '';
    $wo = isset($_GET['work_order_no']) ? ebmr_escape($conn, $_GET['work_order_no']) : '';
    $bn = isset($_GET['batch_number']) ? ebmr_escape($conn, $_GET['batch_number']) : '';

    $steps = [];
    $substeps = [];

    $q = "SELECT step_id, substep_id, step_name, payload_json FROM ebmr_proceed_step_snapshot
          WHERE plant_id='" . $plant_id . "' AND product_code='" . $pc . "'
            AND work_order_no='" . $wo . "' AND batch_number='" . $bn . "'";
    $r = $conn->query($q);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $sid = $row['step_id'];
            $sub = $row['substep_id'] === null ? '' : $row['substep_id'];
            $payload = json_decode($row['payload_json'], true);
            if ($sub === '' || $sub === '0') {
                $steps[$sid] = [
                    'data' => $payload,
                    'step_name' => $row['step_name'],
                ];
            } else {
                $substeps[$sid . '__' . $sub] = [
                    'data' => $payload,
                ];
            }
        }
    }

    $batch_approved = 0;
    $approved_at = null;
    $q2 = "SELECT approved, approved_at FROM ebmr_proceed_batch_approval
           WHERE plant_id='" . $plant_id . "' AND product_code='" . $pc . "'
             AND work_order_no='" . $wo . "' AND batch_number='" . $bn . "' LIMIT 1";
    $r2 = $conn->query($q2);
    if ($r2 && $r2->num_rows > 0) {
        $a = $r2->fetch_assoc();
        $batch_approved = (int) $a['approved'];
        $approved_at = $a['approved_at'];
    }

    ebmr_json_out([
        'status' => 'success',
        'steps' => $steps,
        'substeps' => $substeps,
        'batch_approved' => $batch_approved,
        'approved_at' => $approved_at,
    ]);
}

if ($type === 'save_step' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pc = ebmr_escape($conn, isset($input['product_code']) ? $input['product_code'] : '');
    $wo = ebmr_escape($conn, isset($input['work_order_no']) ? $input['work_order_no'] : '');
    $bn = ebmr_escape($conn, isset($input['batch_number']) ? $input['batch_number'] : '');
    $step_id = ebmr_escape($conn, isset($input['step_id']) ? (string) $input['step_id'] : '');
    $step_name = isset($input['step_name']) ? ebmr_escape($conn, $input['step_name']) : '';
    $data = isset($input['data']) ? $input['data'] : null;
    $payload = ebmr_escape($conn, json_encode($data));

    $sql = "INSERT INTO ebmr_proceed_step_snapshot
            (plant_id, product_code, work_order_no, batch_number, step_id, substep_id, step_name, payload_json, emp_id)
            VALUES ('" . $plant_id . "','" . $pc . "','" . $wo . "','" . $bn . "','" . $step_id . "','','" . $step_name . "','" . $payload . "','" . $emp_id . "')
            ON DUPLICATE KEY UPDATE
              step_name=VALUES(step_name),
              payload_json=VALUES(payload_json),
              emp_id=VALUES(emp_id),
              updated_at=CURRENT_TIMESTAMP";
    if ($conn->query($sql)) {
        ebmr_json_out(['status' => 'success']);
    }
    ebmr_json_out(['status' => 'error', 'msg' => $conn->error]);
}

if ($type === 'save_substep' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pc = ebmr_escape($conn, isset($input['product_code']) ? $input['product_code'] : '');
    $wo = ebmr_escape($conn, isset($input['work_order_no']) ? $input['work_order_no'] : '');
    $bn = ebmr_escape($conn, isset($input['batch_number']) ? $input['batch_number'] : '');
    $step_id = ebmr_escape($conn, isset($input['step_id']) ? (string) $input['step_id'] : '');
    $sub_id = ebmr_escape($conn, isset($input['substep_id']) ? (string) $input['substep_id'] : '');
    $data = isset($input['data']) ? $input['data'] : null;
    $payload = ebmr_escape($conn, json_encode($data));

    $sql = "INSERT INTO ebmr_proceed_step_snapshot
            (plant_id, product_code, work_order_no, batch_number, step_id, substep_id, step_name, payload_json, emp_id)
            VALUES ('" . $plant_id . "','" . $pc . "','" . $wo . "','" . $bn . "','" . $step_id . "','" . $sub_id . "',NULL,'" . $payload . "','" . $emp_id . "')
            ON DUPLICATE KEY UPDATE
              payload_json=VALUES(payload_json),
              emp_id=VALUES(emp_id),
              updated_at=CURRENT_TIMESTAMP";
    if ($conn->query($sql)) {
        ebmr_json_out(['status' => 'success']);
    }
    ebmr_json_out(['status' => 'error', 'msg' => $conn->error]);
}

if ($type === 'approve_batch' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pc = ebmr_escape($conn, isset($input['product_code']) ? $input['product_code'] : '');
    $wo = ebmr_escape($conn, isset($input['work_order_no']) ? $input['work_order_no'] : '');
    $bn = ebmr_escape($conn, isset($input['batch_number']) ? $input['batch_number'] : '');
    $now = date('Y-m-d H:i:s');

    $sql = "INSERT INTO ebmr_proceed_batch_approval
            (plant_id, product_code, work_order_no, batch_number, approved, approved_at, approved_by_emp_id)
            VALUES ('" . $plant_id . "','" . $pc . "','" . $wo . "','" . $bn . "',1,'" . ebmr_escape($conn, $now) . "','" . $emp_id . "')
            ON DUPLICATE KEY UPDATE
              approved=1,
              approved_at=VALUES(approved_at),
              approved_by_emp_id=VALUES(approved_by_emp_id)";
    if ($conn->query($sql)) {
        ebmr_json_out(['status' => 'success']);
    }
    ebmr_json_out(['status' => 'error', 'msg' => $conn->error]);
}

ebmr_json_out(['status' => 'error', 'msg' => 'Unknown type']);
