<?php
/**
 * eBMR Proceed — save / load filled step data (new table, no process.php).
 *
 * Deploy next to other bmr/*.php files. Angular calls:
 *   bmr/ebmr_filled_step_api.php?type=get_batch_steps&product_code=...&work_order_no=...&batch_number=...
 *   POST bmr/ebmr_filled_step_api.php?type=save_step
 *   POST bmr/ebmr_filled_step_api.php?type=save_substep
 *   POST bmr/ebmr_filled_step_api.php?type=approve_batch
 *
 * Requires: ../db.php (mysqli $conn). Token table `token` like rest of Cyclone.
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');

require_once dirname(__DIR__) . '/db.php';

function filled_json_out($arr)
{
    echo json_encode($arr);
    exit;
}

function filled_esc($conn, $v)
{
    if ($v === null) {
        return '';
    }
    return mysqli_real_escape_string($conn, (string) $v);
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
$token_esc = filled_esc($conn, $token);
$sqlTok = "SELECT * FROM token WHERE token='" . $token_esc . "' LIMIT 1";
$resTok = $conn->query($sqlTok);
$_GET['emp_id'] = '';
if ($resTok && $resTok->num_rows > 0) {
    $rowT = $resTok->fetch_assoc();
    if (isset($rowT['emp_id']) && $rowT['emp_id'] !== '') {
        $_GET['emp_id'] = $rowT['emp_id'];
    }
} else {
    filled_json_out(['status' => 'error', 'msg' => 'Invalid or missing token']);
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$plant_id = isset($_GET['plant_id']) ? filled_esc($conn, $_GET['plant_id']) : '';
$emp_id = filled_esc($conn, isset($_GET['emp_id']) ? $_GET['emp_id'] : '');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = [];
}

// ---------- GET: all saved fills for one batch ----------
if ($type === 'get_batch_steps') {
    $pc = isset($_GET['product_code']) ? filled_esc($conn, $_GET['product_code']) : '';
    $wo = isset($_GET['work_order_no']) ? filled_esc($conn, $_GET['work_order_no']) : '';
    $bn = isset($_GET['batch_number']) ? filled_esc($conn, $_GET['batch_number']) : '';

    $steps = [];
    $substeps = [];

    $q = "SELECT step_id, substep_id, step_name, fill_json, updated_at FROM ebmr_filled_step
          WHERE plant_id='" . $plant_id . "' AND product_code='" . $pc . "'
            AND work_order_no='" . $wo . "' AND batch_number='" . $bn . "'";
    $r = $conn->query($q);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $sid = $row['step_id'];
            $sub = ($row['substep_id'] === null || $row['substep_id'] === '') ? '' : $row['substep_id'];
            $payload = json_decode($row['fill_json'], true);
            if ($sub === '' || $sub === '0') {
                $steps[$sid] = [
                    'data' => $payload,
                    'step_name' => $row['step_name'],
                    'saved_at' => isset($row['updated_at']) ? $row['updated_at'] : null,
                ];
            } else {
                $substeps[$sid . '__' . $sub] = [
                    'data' => $payload,
                    'saved_at' => isset($row['updated_at']) ? $row['updated_at'] : null,
                ];
            }
        }
    }

    $batch_approved = 0;
    $approved_at = null;
    $approved_by_emp_id = null;
    $q2 = "SELECT approved, approved_at, approved_by_emp_id FROM ebmr_filled_batch_approval
           WHERE plant_id='" . $plant_id . "' AND product_code='" . $pc . "'
             AND work_order_no='" . $wo . "' AND batch_number='" . $bn . "' LIMIT 1";
    $r2 = $conn->query($q2);
    if ($r2 && $r2->num_rows > 0) {
        $a = $r2->fetch_assoc();
        $batch_approved = (int) $a['approved'];
        $approved_at = $a['approved_at'];
        if (isset($a['approved_by_emp_id']) && $a['approved_by_emp_id'] !== null && trim((string) $a['approved_by_emp_id']) !== '') {
            $approved_by_emp_id = $a['approved_by_emp_id'];
        }
    }

    $last_step_emp_id = null;
    $last_step_saved_at = null;
    $q3 = "SELECT emp_id, updated_at FROM ebmr_filled_step
           WHERE plant_id='" . $plant_id . "' AND product_code='" . $pc . "'
             AND work_order_no='" . $wo . "' AND batch_number='" . $bn . "'
             AND emp_id IS NOT NULL AND TRIM(emp_id) <> ''
           ORDER BY updated_at DESC LIMIT 1";
    $r3 = $conn->query($q3);
    if ($r3 && $r3->num_rows > 0) {
        $u = $r3->fetch_assoc();
        $last_step_emp_id = $u['emp_id'];
        $last_step_saved_at = isset($u['updated_at']) ? $u['updated_at'] : null;
    }

    filled_json_out([
        'status' => 'success',
        'steps' => $steps,
        'substeps' => $substeps,
        'batch_approved' => $batch_approved,
        'approved_at' => $approved_at,
        'approved_by_emp_id' => $approved_by_emp_id,
        'last_step_emp_id' => $last_step_emp_id,
        'last_step_saved_at' => $last_step_saved_at,
    ]);
}

// ---------- POST: main step ----------
// ---------- GET: approved batches list (used for packing proceed dropdown) ----------
if ($type === 'get_approved_batches') {
    $pc = isset($_GET['product_code']) ? filled_esc($conn, $_GET['product_code']) : '';
    $wherePc = $pc === '' ? '' : " AND a.product_code='" . $pc . "'";

    $rows = [];
    $q = "SELECT
            a.product_code,
            a.work_order_no,
            a.batch_number,
            a.approved_at,
            a.approved_by_emp_id
          FROM ebmr_filled_batch_approval a
          WHERE a.plant_id='" . $plant_id . "'
            AND a.approved=1" . $wherePc . "
          ORDER BY a.approved_at DESC
          LIMIT 500";
    $r = $conn->query($q);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $pc2 = isset($row['product_code']) ? filled_esc($conn, $row['product_code']) : '';
            $wo2 = isset($row['work_order_no']) ? filled_esc($conn, $row['work_order_no']) : '';
            $bn2 = isset($row['batch_number']) ? filled_esc($conn, $row['batch_number']) : '';

            $lastSavedAt = null;
            $lastEmp = null;
            $q2 = "SELECT emp_id, updated_at FROM ebmr_filled_step
                   WHERE plant_id='" . $plant_id . "'
                     AND product_code='" . $pc2 . "'
                     AND work_order_no='" . $wo2 . "'
                     AND batch_number='" . $bn2 . "'
                   ORDER BY updated_at DESC LIMIT 1";
            $r2 = $conn->query($q2);
            if ($r2 && $r2->num_rows > 0) {
                $u = $r2->fetch_assoc();
                $lastSavedAt = isset($u['updated_at']) ? $u['updated_at'] : null;
                $lastEmp = isset($u['emp_id']) ? $u['emp_id'] : null;
            }

            $rows[] = [
                'product_code' => $row['product_code'],
                'work_order_no' => $row['work_order_no'],
                'batch_number' => $row['batch_number'],
                'approved_at' => $row['approved_at'],
                'approved_by_emp_id' => $row['approved_by_emp_id'],
                'last_step_saved_at' => $lastSavedAt,
                'last_step_emp_id' => $lastEmp,
            ];
        }
    }
    filled_json_out(['status' => 'success', 'batches' => $rows]);
}

// ---------- GET: batches where packing BMR started or completed ----------
if ($type === 'get_started_or_completed_batches') {
    $rowsByKey = [];

    $qStarted = "SELECT
                  s.product_code,
                  s.work_order_no,
                  s.batch_number,
                  MAX(s.updated_at) AS last_saved_at,
                  COUNT(*) AS saved_rows
                FROM ebmr_filled_step s
                WHERE s.plant_id='" . $plant_id . "'
                GROUP BY s.product_code, s.work_order_no, s.batch_number
                ORDER BY MAX(s.updated_at) DESC
                LIMIT 1000";
    $rStarted = $conn->query($qStarted);
    if ($rStarted) {
        while ($row = $rStarted->fetch_assoc()) {
            $k = $row['product_code'] . '||' . $row['work_order_no'] . '||' . $row['batch_number'];
            $rowsByKey[$k] = [
                'product_code' => $row['product_code'],
                'work_order_no' => $row['work_order_no'],
                'batch_number' => $row['batch_number'],
                'status' => 'Started',
                'saved_rows' => (int) $row['saved_rows'],
                'last_saved_at' => isset($row['last_saved_at']) ? $row['last_saved_at'] : null,
                'approved_at' => null,
                'approved_by_emp_id' => null,
            ];
        }
    }

    $qCompleted = "SELECT
                    a.product_code,
                    a.work_order_no,
                    a.batch_number,
                    a.approved_at,
                    a.approved_by_emp_id
                   FROM ebmr_filled_batch_approval a
                   WHERE a.plant_id='" . $plant_id . "'
                     AND a.approved=1
                   ORDER BY a.approved_at DESC
                   LIMIT 1000";
    $rCompleted = $conn->query($qCompleted);
    if ($rCompleted) {
        while ($row = $rCompleted->fetch_assoc()) {
            $k = $row['product_code'] . '||' . $row['work_order_no'] . '||' . $row['batch_number'];
            if (!isset($rowsByKey[$k])) {
                $rowsByKey[$k] = [
                    'product_code' => $row['product_code'],
                    'work_order_no' => $row['work_order_no'],
                    'batch_number' => $row['batch_number'],
                    'status' => 'Completed',
                    'saved_rows' => 0,
                    'last_saved_at' => null,
                    'approved_at' => isset($row['approved_at']) ? $row['approved_at'] : null,
                    'approved_by_emp_id' => isset($row['approved_by_emp_id']) ? $row['approved_by_emp_id'] : null,
                ];
            } else {
                $rowsByKey[$k]['status'] = 'Completed';
                $rowsByKey[$k]['approved_at'] = isset($row['approved_at']) ? $row['approved_at'] : null;
                $rowsByKey[$k]['approved_by_emp_id'] = isset($row['approved_by_emp_id']) ? $row['approved_by_emp_id'] : null;
            }
        }
    }

    $rows = array_values($rowsByKey);
    usort($rows, function ($a, $b) {
        $ta = isset($a['approved_at']) && $a['approved_at'] ? $a['approved_at'] : (isset($a['last_saved_at']) ? $a['last_saved_at'] : '');
        $tb = isset($b['approved_at']) && $b['approved_at'] ? $b['approved_at'] : (isset($b['last_saved_at']) ? $b['last_saved_at'] : '');
        return strcmp((string) $tb, (string) $ta);
    });

    filled_json_out(['status' => 'success', 'batches' => $rows]);
}

// ---------- POST: main step ----------
if ($type === 'save_step' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pc = filled_esc($conn, isset($input['product_code']) ? $input['product_code'] : '');
    $wo = filled_esc($conn, isset($input['work_order_no']) ? $input['work_order_no'] : '');
    $bn = filled_esc($conn, isset($input['batch_number']) ? $input['batch_number'] : '');
    $step_id = filled_esc($conn, isset($input['step_id']) ? (string) $input['step_id'] : '');
    $step_name = isset($input['step_name']) ? filled_esc($conn, $input['step_name']) : '';
    $data = isset($input['data']) ? $input['data'] : null;
    $json = filled_esc($conn, json_encode($data));

    $sql = "INSERT INTO ebmr_filled_step
            (plant_id, product_code, work_order_no, batch_number, step_id, substep_id, step_name, fill_json, emp_id)
            VALUES ('" . $plant_id . "','" . $pc . "','" . $wo . "','" . $bn . "','" . $step_id . "','','" . $step_name . "','" . $json . "','" . $emp_id . "')
            ON DUPLICATE KEY UPDATE
              step_name=VALUES(step_name),
              fill_json=VALUES(fill_json),
              emp_id=VALUES(emp_id),
              updated_at=CURRENT_TIMESTAMP";
    if ($conn->query($sql)) {
        filled_json_out(['status' => 'success']);
    }
    filled_json_out(['status' => 'error', 'msg' => $conn->error]);
}

// ---------- POST: substep ----------
if ($type === 'save_substep' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pc = filled_esc($conn, isset($input['product_code']) ? $input['product_code'] : '');
    $wo = filled_esc($conn, isset($input['work_order_no']) ? $input['work_order_no'] : '');
    $bn = filled_esc($conn, isset($input['batch_number']) ? $input['batch_number'] : '');
    $step_id = filled_esc($conn, isset($input['step_id']) ? (string) $input['step_id'] : '');
    $sub_id = filled_esc($conn, isset($input['substep_id']) ? (string) $input['substep_id'] : '');
    $data = isset($input['data']) ? $input['data'] : null;
    $json = filled_esc($conn, json_encode($data));

    $sql = "INSERT INTO ebmr_filled_step
            (plant_id, product_code, work_order_no, batch_number, step_id, substep_id, step_name, fill_json, emp_id)
            VALUES ('" . $plant_id . "','" . $pc . "','" . $wo . "','" . $bn . "','" . $step_id . "','" . $sub_id . "',NULL,'" . $json . "','" . $emp_id . "')
            ON DUPLICATE KEY UPDATE
              fill_json=VALUES(fill_json),
              emp_id=VALUES(emp_id),
              updated_at=CURRENT_TIMESTAMP";
    if ($conn->query($sql)) {
        filled_json_out(['status' => 'success']);
    }
    filled_json_out(['status' => 'error', 'msg' => $conn->error]);
}

// ---------- POST: approve batch (checking screen / PDF gate) ----------
if ($type === 'approve_batch' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pc = filled_esc($conn, isset($input['product_code']) ? $input['product_code'] : '');
    $wo = filled_esc($conn, isset($input['work_order_no']) ? $input['work_order_no'] : '');
    $bn = filled_esc($conn, isset($input['batch_number']) ? $input['batch_number'] : '');
    $now = date('Y-m-d H:i:s');

    $sql = "INSERT INTO ebmr_filled_batch_approval
            (plant_id, product_code, work_order_no, batch_number, approved, approved_at, approved_by_emp_id)
            VALUES ('" . $plant_id . "','" . $pc . "','" . $wo . "','" . $bn . "',1,'" . filled_esc($conn, $now) . "','" . $emp_id . "')
            ON DUPLICATE KEY UPDATE
              approved=1,
              approved_at=VALUES(approved_at),
              approved_by_emp_id=VALUES(approved_by_emp_id)";
    if ($conn->query($sql)) {
        filled_json_out(['status' => 'success']);
    }
    filled_json_out(['status' => 'error', 'msg' => $conn->error]);
}

filled_json_out(['status' => 'error', 'msg' => 'Unknown type']);
