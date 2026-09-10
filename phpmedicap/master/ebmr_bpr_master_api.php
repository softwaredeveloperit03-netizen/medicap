<?php
/**
 * Cyclone eBMR / BPR master API (Process Master independent module).
 *
 * GET  master/ebmr_bpr_master_api.php?type=list_masters&department=production|packing
 * GET  master/ebmr_bpr_master_api.php?type=get_master_tree&product_code=...&department=...
 * GET  master/ebmr_bpr_master_api.php?type=list_products
 * POST master/ebmr_bpr_master_api.php?type=save_master_tree  (JSON body)
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/ebmr_bpr_master_schema.php';

ebmr_bpr_master_bootstrap($conn);

function ebpr_json_out($arr)
{
    echo json_encode($arr);
    exit;
}

function ebpr_esc($conn, $v)
{
    if ($v === null) {
        return '';
    }
    return mysqli_real_escape_string($conn, (string) $v);
}

function ebpr_norm_department($dept)
{
    $d = strtolower(trim((string) $dept));
    if ($d === 'packing' || $d === 'bpr' || $d === 'ebpr') {
        return 'packing';
    }
    return 'production';
}

function ebpr_normalize_stages($stages)
{
    if (!is_array($stages)) {
        return [];
    }
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
    return $stages;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$plant_id = isset($_GET['plant_id']) ? ebpr_esc($conn, $_GET['plant_id']) : '142';
$emp_id = ebpr_esc($conn, isset($_GET['emp_id']) ? $_GET['emp_id'] : '');
$department = ebpr_norm_department(isset($_GET['department']) ? $_GET['department'] : 'production');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = [];
}

if ($type === 'list_products') {
    $out = [];
    $q = "SELECT product_code, product_code1, product_name, dosage_form, status
          FROM product
          WHERE plant_id='" . $plant_id . "'
          ORDER BY product_name ASC LIMIT 2000";
    $r = @$conn->query($q);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $code = trim((string) ($row['product_code'] ?: $row['product_code1']));
            if ($code === '') {
                continue;
            }
            $out[] = [
                'product_code' => $code,
                'product_name' => $row['product_name'],
                'dosage_form' => $row['dosage_form'] ?? '',
                'status' => $row['status'] ?? '',
            ];
        }
    }
    ebpr_json_out(['status' => 'ok', 'products' => $out]);
}

if ($type === 'list_masters') {
    $dept = ebpr_norm_department(isset($_GET['department']) ? $_GET['department'] : '');
    $dept_esc = ebpr_esc($conn, $dept);
    $out = [];
    $q = "SELECT product_code, product_name, department, revision, status, updated_at, updated_by_emp_id
          FROM cyclone_ebmr_bpr_master
          WHERE plant_id='" . $plant_id . "' AND department='" . $dept_esc . "'
          ORDER BY updated_at DESC LIMIT 500";
    $r = $conn->query($q);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $out[] = $row;
        }
    }
    ebpr_json_out(['status' => 'ok', 'department' => $dept, 'masters' => $out]);
}

if ($type === 'get_master_tree') {
    $pc = isset($_GET['product_code']) ? ebpr_esc($conn, trim($_GET['product_code'])) : '';
    $dept = ebpr_norm_department(isset($_GET['department']) ? $_GET['department'] : $department);
    if ($pc === '') {
        ebpr_json_out(['status' => 'error', 'msg' => 'product_code required']);
    }
    $q = "SELECT product_code, product_name, department, master_json, revision, status, updated_at, updated_by_emp_id
          FROM cyclone_ebmr_bpr_master
          WHERE plant_id='" . $plant_id . "' AND product_code='" . $pc . "' AND department='" . ebpr_esc($conn, $dept) . "'
          LIMIT 1";
    $r = $conn->query($q);
    if (!$r || $r->num_rows === 0) {
        ebpr_json_out([
            'status' => 'not_found',
            'msg' => 'No eBMR/BPR master for this product',
            'department' => $dept,
            'Stages' => [],
        ]);
    }
    $row = $r->fetch_assoc();
    $envelope = json_decode($row['master_json'], true);
    if (!is_array($envelope)) {
        $envelope = ['Stages' => []];
    }
    $stages = isset($envelope['Stages']) && is_array($envelope['Stages']) ? $envelope['Stages'] : [];
    ebpr_json_out([
        'status' => 'ok',
        'product_code' => $row['product_code'],
        'product_name' => $row['product_name'],
        'department' => $row['department'],
        'revision' => (int) $row['revision'],
        'status' => $row['status'],
        'updated_at' => $row['updated_at'],
        'updated_by_emp_id' => $row['updated_by_emp_id'],
        'Stages' => $stages,
    ]);
}

if ($type === 'save_master_tree') {
    $pc = isset($input['product_code']) ? trim((string) $input['product_code']) : '';
    $dept = ebpr_norm_department(isset($input['department']) ? $input['department'] : $department);
    if ($pc === '') {
        ebpr_json_out(['status' => 'error', 'msg' => 'product_code required in JSON body']);
    }
    $pc_esc = ebpr_esc($conn, $pc);
    $dept_esc = ebpr_esc($conn, $dept);
    $pname = isset($input['product_name']) ? trim((string) $input['product_name']) : '';
    $pname_esc = $pname === '' ? 'NULL' : "'" . ebpr_esc($conn, $pname) . "'";
    $stages = ebpr_normalize_stages(isset($input['Stages']) ? $input['Stages'] : []);
    $json_esc = "'" . ebpr_esc($conn, json_encode(['Stages' => $stages], JSON_UNESCAPED_UNICODE)) . "'";
    $now = date('Y-m-d H:i:s');
    $now_esc = ebpr_esc($conn, $now);
    $emp_sql = $emp_id === '' ? 'NULL' : "'" . $emp_id . "'";

    $q0 = "SELECT id, revision FROM cyclone_ebmr_bpr_master
           WHERE plant_id='" . $plant_id . "' AND product_code='" . $pc_esc . "' AND department='" . $dept_esc . "' LIMIT 1";
    $r0 = $conn->query($q0);
    $nextRev = 1;
    if ($r0 && $r0->num_rows > 0) {
        $row0 = $r0->fetch_assoc();
        $nextRev = max(1, (int) $row0['revision'] + 1);
        $id = (int) $row0['id'];
        $qUp = "UPDATE cyclone_ebmr_bpr_master SET
                  product_name=" . $pname_esc . ",
                  master_json=" . $json_esc . ",
                  revision=" . (int) $nextRev . ",
                  status='Active',
                  updated_at='" . $now_esc . "',
                  updated_by_emp_id=" . $emp_sql . "
                WHERE id=" . $id . " LIMIT 1";
        if (!$conn->query($qUp)) {
            ebpr_json_out(['status' => 'error', 'msg' => 'Update failed', 'detail' => $conn->error]);
        }
        ebpr_json_out(['status' => 'ok', 'department' => $dept, 'revision' => $nextRev, 'updated_at' => $now]);
    }

    $qIns = "INSERT INTO cyclone_ebmr_bpr_master
        (plant_id, product_code, product_name, department, master_json, revision, status, updated_at, updated_by_emp_id)
        VALUES ('" . $plant_id . "', '" . $pc_esc . "', " . $pname_esc . ", '" . $dept_esc . "', " . $json_esc . ", 1, 'Active', '" . $now_esc . "', " . $emp_sql . ")";
    if (!$conn->query($qIns)) {
        ebpr_json_out(['status' => 'error', 'msg' => 'Insert failed', 'detail' => $conn->error]);
    }
    ebpr_json_out(['status' => 'ok', 'department' => $dept, 'revision' => 1, 'updated_at' => $now]);
}

if ($type === 'seed_demo_masters') {
    $configFile = dirname(__DIR__) . '/config/deploy.local.php';
    $expectedToken = '';
    if (is_readable($configFile)) {
        $deployCfg = include $configFile;
        if (is_array($deployCfg) && !empty($deployCfg['migration_token'])) {
            $expectedToken = (string) $deployCfg['migration_token'];
        }
    }
    $seedToken = isset($_GET['token']) ? (string) $_GET['token'] : '';
    if ($expectedToken === '' || !hash_equals($expectedToken, $seedToken)) {
        ebpr_json_out(['status' => 'error', 'msg' => 'Invalid seed token']);
    }
    require_once dirname(__DIR__) . '/seed-demo-ebmr-bpr.php';
    ebpr_json_out(seed_demo_ebmr_bpr_masters($conn, $plant_id, $emp_id !== '' ? $emp_id : 'seed'));
}

ebpr_json_out(['status' => 'error', 'msg' => 'Unknown type']);
