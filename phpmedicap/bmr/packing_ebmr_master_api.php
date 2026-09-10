<?php
/**
 * Packing eBMR — stage / step / substep master (formulation / excipients packing plant).
 *
 * Angular:
 *   GET  bmr/packing_ebmr_master_api.php?type=list_masters
 *   GET  bmr/packing_ebmr_master_api.php?type=get_master_tree&product_code=...
 *   POST bmr/packing_ebmr_master_api.php?type=save_master_tree  (JSON body)
 *
 * Body for save: { "product_code": "...", "product_name": "...", "Stages": [ ... ] }
 * Stage shape (aligned with production eBMR JSON): { "stages": "Stage title", "Steps": [ { "id", "step", "Substeps": [ { "id", "substep" } ] } ] }
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');

require '../db.php';
require '../token.php';


function pem_json_out($arr)
{
    echo json_encode($arr);
    exit;
}

function pem_esc($conn, $v)
{
    if ($v === null) {
        return '';
    }
    return mysqli_real_escape_string($conn, (string) $v);
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
$token_esc = pem_esc($conn, $token);
$sqlTok = "SELECT * FROM token WHERE token='" . $token_esc . "' LIMIT 1";
$resTok = $conn->query($sqlTok);
$_GET['emp_id'] = '';
if ($resTok && $resTok->num_rows > 0) {
    $rowT = $resTok->fetch_assoc();
    if (isset($rowT['emp_id']) && $rowT['emp_id'] !== '') {
        $_GET['emp_id'] = $rowT['emp_id'];
    }
} else {
    pem_json_out(['status' => 'error', 'msg' => 'Invalid or missing token']);
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$plant_id = isset($_GET['plant_id']) ? pem_esc($conn, $_GET['plant_id']) : '';
$emp_id = pem_esc($conn, isset($_GET['emp_id']) ? $_GET['emp_id'] : '');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = [];
}

if ($type === 'list_masters') {
    $out = [];
    $q = "SELECT product_code, product_name, revision, updated_at, updated_by_emp_id
          FROM packing_ebmr_master
          WHERE plant_id='" . $plant_id . "'
          ORDER BY updated_at DESC LIMIT 500";
    $r = $conn->query($q);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $out[] = $row;
        }
    }
    pem_json_out(['status' => 'ok', 'masters' => $out]);
}

if ($type === 'get_master_tree') {
    $pc = isset($_GET['product_code']) ? pem_esc($conn, $_GET['product_code']) : '';
    if ($pc === '') {
        pem_json_out(['status' => 'error', 'msg' => 'product_code required']);
    }
    $q = "SELECT product_code, product_name, master_json, revision, updated_at, updated_by_emp_id
          FROM packing_ebmr_master
          WHERE plant_id='" . $plant_id . "' AND product_code='" . $pc . "' LIMIT 1";
    $r = $conn->query($q);
    if (!$r || $r->num_rows === 0) {
        pem_json_out(['status' => 'not_found', 'msg' => 'No packing master for this product', 'Stages' => []]);
    }
    $row = $r->fetch_assoc();
    $envelope = json_decode($row['master_json'], true);
    if (!is_array($envelope)) {
        $envelope = ['Stages' => []];
    }
    $stages = isset($envelope['Stages']) && is_array($envelope['Stages']) ? $envelope['Stages'] : [];
    pem_json_out([
        'status' => 'ok',
        'product_code' => $row['product_code'],
        'product_name' => $row['product_name'],
        'revision' => (int) $row['revision'],
        'updated_at' => $row['updated_at'],
        'updated_by_emp_id' => $row['updated_by_emp_id'],
        'Stages' => $stages,
    ]);
}

if ($type === 'save_master_tree') {
    $pc = isset($input['product_code']) ? trim((string) $input['product_code']) : '';
    if ($pc === '') {
        pem_json_out(['status' => 'error', 'msg' => 'product_code required in JSON body']);
    }
    $pc_esc = pem_esc($conn, $pc);
    $pname = isset($input['product_name']) ? trim((string) $input['product_name']) : '';
    $pname_esc = $pname === '' ? 'NULL' : "'" . pem_esc($conn, $pname) . "'";

    $stages = isset($input['Stages']) && is_array($input['Stages']) ? $input['Stages'] : [];
    foreach ($stages as &$st) {
        if (!is_array($st)) {
            $st = ['stages' => '', 'Steps' => []];
            continue;
        }
        if (!isset($st['stages'])) {
            $st['stages'] = isset($st['stage_name']) ? $st['stage_name'] : '';
        }
        if (!isset($st['Steps']) || !is_array($st['Steps'])) {
            $st['Steps'] = [];
        }
        foreach ($st['Steps'] as &$stp) {
            if (!is_array($stp)) {
                $stp = ['id' => '', 'step' => '', 'Substeps' => []];
                continue;
            }
            if (!isset($stp['Substeps']) || !is_array($stp['Substeps'])) {
                $stp['Substeps'] = [];
            }
        }
        unset($stp);
    }
    unset($st);

    $envelope = ['Stages' => $stages];
    $json_esc = "'" . pem_esc($conn, json_encode($envelope, JSON_UNESCAPED_UNICODE)) . "'";
    $now = date('Y-m-d H:i:s');
    $now_esc = pem_esc($conn, $now);
    $emp_sql = $emp_id === '' ? 'NULL' : "'" . $emp_id . "'";

    $q0 = "SELECT id, revision FROM packing_ebmr_master
           WHERE plant_id='" . $plant_id . "' AND product_code='" . $pc_esc . "' LIMIT 1";
    $r0 = $conn->query($q0);
    $nextRev = 1;
    if ($r0 && $r0->num_rows > 0) {
        $row0 = $r0->fetch_assoc();
        $nextRev = max(1, (int) $row0['revision'] + 1);
        $id = (int) $row0['id'];
        $qUp = "UPDATE packing_ebmr_master SET
                  product_name=" . $pname_esc . ",
                  master_json=" . $json_esc . ",
                  revision=" . (int) $nextRev . ",
                  updated_at='" . $now_esc . "',
                  updated_by_emp_id=" . $emp_sql . "
                WHERE id=" . $id . " LIMIT 1";
        if (!$conn->query($qUp)) {
            pem_json_out(['status' => 'error', 'msg' => 'Update failed', 'detail' => $conn->error]);
        }
        pem_json_out(['status' => 'ok', 'revision' => $nextRev, 'updated_at' => $now]);
    }

    $qIns = "INSERT INTO packing_ebmr_master (plant_id, product_code, product_name, master_json, revision, updated_at, updated_by_emp_id)
             VALUES ('" . $plant_id . "', '" . $pc_esc . "', " . $pname_esc . ", " . $json_esc . ", 1, '" . $now_esc . "', " . $emp_sql . ")";
    if (!$conn->query($qIns)) {
        pem_json_out(['status' => 'error', 'msg' => 'Insert failed', 'detail' => $conn->error]);
    }
    pem_json_out(['status' => 'ok', 'revision' => 1, 'updated_at' => $now]);
}

pem_json_out(['status' => 'error', 'msg' => 'Unknown type']);
