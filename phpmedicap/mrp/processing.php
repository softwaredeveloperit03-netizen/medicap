<?php
/**
 * Medicap MRP Processing /entry — standalone (no _bootstrap.php, no marketing/po.php).
 *
 * After Factory Order Save & Send:
 *   order_materials.reqStatus = Inprocess
 *   split_planning_qty.status = Pending
 * → listed here via getPendingProcessingPOs
 *
 * Frontend: mrp/processing.php?type=...
 *
 * Matches factoryorder.php auth pattern so it works on hosts where mrp/_bootstrap.php is not deployed.
 */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/processing-error.log');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../token.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');

$token = $_GET['token'] ?? '';
$timestamp = time();
$entry_date = date('Y-m-d H:i:s', $timestamp);
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = array();
}

/**
 * Prefer local marketing helper; fall back to sibling phpzuma on shared host.
 * Returns false if helpers are missing (list still works without batch formula).
 */
function mrp_proc_require_helpers($required = true) {
    static $loaded = null;
    if ($loaded === true) {
        return true;
    }
    if ($loaded === false && !$required) {
        return false;
    }

    $local = __DIR__ . '/../marketing/processing_po_helpers.php';
    $candidates = array($local);
    $parent = dirname(__DIR__);
    $candidates[] = $parent . '/../phpzuma/marketing/processing_po_helpers.php';
    $candidates[] = dirname($parent) . '/phpzuma/marketing/processing_po_helpers.php';

    foreach ($candidates as $cand) {
        if (is_file($cand)) {
            require_once $cand;
            $loaded = true;
            return true;
        }
    }

    $loaded = false;
    if ($required) {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'Missing processing_po_helpers.php — upload marketing/processing_po_helpers.php',
        ));
        exit;
    }
    return false;
}

function mrp_proc_esc($conn, $v) {
    return $conn->real_escape_string((string)($v ?? ''));
}

function mrp_proc_table_exists($conn, $table) {
    $t = mrp_proc_esc($conn, $table);
    $r = $conn->query("SHOW TABLES LIKE '{$t}'");
    return $r && $r->num_rows > 0;
}

function mrp_proc_ensure_split_cols($conn) {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    if (!mrp_proc_table_exists($conn, 'split_planning_qty')) {
        $conn->query("CREATE TABLE IF NOT EXISTS `split_planning_qty` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `plant_id` TEXT NULL,
            `order_no` TEXT NULL,
            `product_code` TEXT NULL,
            `product_name` TEXT NULL,
            `month` TEXT NULL,
            `year` TEXT NULL,
            `date` TEXT NULL,
            `oder_qty` TEXT NULL,
            `balance_qty` TEXT NULL,
            `InQty` TEXT NULL,
            `outQty` TEXT NULL,
            `avbl_stock` TEXT NULL,
            `unit` TEXT NULL,
            `planUnit` TEXT NULL,
            `status` TEXT NULL,
            `batches` LONGTEXT NULL,
            `entry_by` TEXT NULL,
            `entryBy` TEXT NULL,
            `entryOn` TEXT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    $cols = array(
        'unit' => 'TEXT NULL',
        'planUnit' => 'TEXT NULL',
        'plant_id' => 'TEXT NULL',
        'status' => 'TEXT NULL',
        'batches' => 'LONGTEXT NULL',
        'leftover' => 'TEXT NULL',
        'excess' => 'TEXT NULL',
        'work_order_planned_qty' => 'TEXT NULL',
        'remarkText' => 'TEXT NULL',
    );
    foreach ($cols as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM `split_planning_qty` LIKE '" . mrp_proc_esc($conn, $col) . "'");
        if ($check && $check->num_rows === 0) {
            @$conn->query("ALTER TABLE `split_planning_qty` ADD COLUMN `{$col}` {$def}");
        }
    }
}

/**
 * List splits ready for /planning/mrp/Processing/entry
 * (Factory Order sent → order_materials.reqStatus=Inprocess + split Pending).
 */
function mrp_proc_get_pending($conn) {
    mrp_proc_ensure_split_cols($conn);
    mrp_proc_require_helpers(false);

    // Backfill splits for orders already sent from Factory Order
    $conn->query("UPDATE split_planning_qty sp
      INNER JOIN order_materials om
        ON om.order_no = sp.order_no AND om.product_code = sp.product_code
      SET sp.status = 'Pending',
          sp.unit = IF(sp.unit IS NULL OR TRIM(sp.unit)='',
                       COALESCE(NULLIF(TRIM(om.unit),''), NULLIF(TRIM(om.planUnit),''), sp.unit),
                       sp.unit),
          sp.planUnit = IF(sp.planUnit IS NULL OR TRIM(sp.planUnit)='',
                           COALESCE(NULLIF(TRIM(om.planUnit),''), NULLIF(TRIM(om.unit),''), sp.planUnit),
                           sp.planUnit),
          sp.plant_id = IF(sp.plant_id IS NULL OR TRIM(sp.plant_id)='', om.plant_id, sp.plant_id)
      WHERE LOWER(TRIM(IFNULL(om.reqStatus,''))) = 'inprocess'
        AND (sp.status IS NULL OR TRIM(sp.status)='' OR LOWER(TRIM(sp.status))='pending')");

    $woExclude = '';
    if (mrp_proc_table_exists($conn, 'Work_order_materials')) {
        $woExclude = " AND a.id NOT IN (
            SELECT DISTINCT CAST(doc_no AS UNSIGNED) FROM Work_order_materials
            WHERE doc_no IS NOT NULL AND TRIM(doc_no) <> '' AND doc_no <> '0'
              AND CAST(doc_no AS UNSIGNED) > 0
        )";
    }

    $sql = "SELECT a.*,
      COALESCE(NULLIF(TRIM(om.unit),''), NULLIF(TRIM(om.planUnit),''), NULLIF(TRIM(om.packingUnit),'')) AS om_unit,
      om.reqStatus AS order_req_status,
      om.id AS order_material_pid,
      (SELECT c1.LglNm FROM client c1 WHERE c1.client_code = c.client_code LIMIT 1) AS clientName,
      (SELECT c1.LglNm FROM client c1 WHERE c1.client_code = c.client_code LIMIT 1) AS mainGroupName,
      (SELECT c2.LglNm FROM client c2 WHERE c2.client_code = c.conisgnee LIMIT 1) AS conisgneeName,
      p.category,
      p.product_name,
      COALESCE(om.deliveryDate, (
        SELECT o.deliveryDate FROM order_materials o
        WHERE o.order_no=a.order_no AND o.product_code=a.product_code
        ORDER BY o.id DESC LIMIT 1
      )) AS deliveryDate,
      c.po_date,
      c.file,
      c.id AS po_entry_id,
      (SELECT CONCAT(IFNULL(firstname,''),' ',IFNULL(middlename,''),' ',IFNULL(lastname,''))
       FROM employee WHERE emp_id = a.entry_by LIMIT 1) AS emp_name
    FROM split_planning_qty a
    LEFT JOIN product p ON a.product_code = p.product_code
    LEFT JOIN po_entry c ON a.order_no = c.order_no
    LEFT JOIN order_materials om ON om.id = (
      SELECT om2.id FROM order_materials om2
      WHERE om2.order_no = a.order_no AND om2.product_code = a.product_code
      ORDER BY CASE WHEN LOWER(TRIM(IFNULL(om2.reqStatus,'')))='inprocess' THEN 0 ELSE 1 END, om2.id DESC
      LIMIT 1
    )
    WHERE (a.status IS NULL OR TRIM(IFNULL(a.status,''))='' OR LOWER(TRIM(a.status))='pending')
      AND LOWER(TRIM(IFNULL(om.reqStatus,''))) = 'inprocess'
      {$woExclude}";

    if (!empty($_GET['plant_id'])) {
        $plant_id = mrp_proc_esc($conn, $_GET['plant_id']);
        $sql .= " AND (a.plant_id = '{$plant_id}' OR a.plant_id IS NULL OR TRIM(a.plant_id) = ''
                   OR om.plant_id = '{$plant_id}')";
    }

    $sql .= ' ORDER BY a.id ASC';

    $output = array();
    $result = $conn->query($sql);
    if ($result === false) {
        echo json_encode(array('error' => $conn->error, 'items' => array()));
        return;
    }

    while ($row = $result->fetch_assoc()) {
        $row['order_material_id'] = $row['id'];
        $row['doc_no'] = $row['id'];
        $row['plan_qty'] = $row['oder_qty'] ?? ($row['plan_qty'] ?? 0);
        $row['planQty'] = $row['plan_qty'];

        if (!empty($row['planUnit'])) {
            $planUnit = $row['planUnit'];
        } elseif (!empty($row['unit'])) {
            $planUnit = $row['unit'];
        } elseif (!empty($row['om_unit'])) {
            $planUnit = $row['om_unit'];
            $conn->query(
                "UPDATE split_planning_qty SET planUnit='" . mrp_proc_esc($conn, $planUnit) . "',
                    unit='" . mrp_proc_esc($conn, $planUnit) . "' WHERE id='" . (int)$row['id'] . "'"
            );
        } else {
            $planUnit = 'NOS';
        }
        $row['planUnit'] = $planUnit;

        if (function_exists('po_processing_batch_formula_for_product')) {
            $row['batch_formula'] = po_processing_batch_formula_for_product(
                $conn,
                $row['product_code'] ?? '',
                $planUnit
            );
        } else {
            $row['batch_formula'] = array();
        }
        $row['products'] = array();

        if (!empty($row['batches']) && is_string($row['batches'])) {
            $decoded = json_decode($row['batches'], true);
            $row['batches'] = is_array($decoded) ? $decoded : array();
        }

        $output[] = $row;
    }

    echo json_encode($output);
}

$tokenEsc = $conn->real_escape_string((string)$token);
$sql = "SELECT * FROM token WHERE token='" . $tokenEsc . "'";
$result = $conn->query($sql);
$_GET['emp_id'] = $_GET['emp_id'] ?? '';
$_GET['department'] = '';

$auth_ok = false;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET['token'], $row['key1'], $row['key2']);
        $parts = explode('$', $string);
        $_GET['emp_id'] = $parts[0] ?? '';
        $_GET['department'] = $parts[1] ?? '';
        break;
    }
    $auth_ok = true;
    $txt = '{"process":"FRONTEND","token":"' . $token . '","action":"' . ($_GET['type'] ?? '') . '","actiontime":"' . $entry_date . '","department":"' . $_GET['department'] . '","emp_id":"' . $_GET['emp_id'] . '","method":"' . ($_SERVER['REQUEST_METHOD'] ?? '') . '","REMOTE_ADDR":"' . ($_SERVER['REMOTE_ADDR'] ?? '') . '"}';
    @file_put_contents(__DIR__ . '/../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);
}

if (!$auth_ok) {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid or missing token'));
    exit;
}

mrp_proc_ensure_split_cols($conn);

$type = $_GET['type'] ?? '';

if ($type === 'getPendingProcessingPOs') {
    mrp_proc_get_pending($conn);
    exit;
}

if ($type === 'getProcessingBatchFormula') {
    mrp_proc_require_helpers(true);
    $productCode = $_GET['product_code'] ?? '';
    $planUnit = $_GET['planUnit'] ?? ($_GET['plan_unit'] ?? '');
    $fresh = ($_GET['fresh'] ?? '1') === '1' || ($_GET['fresh_from_bom'] ?? '') === '1';
    $batchFormula = po_processing_batch_formula_for_product($conn, $productCode, $planUnit, array(
        'fresh_from_bom' => $fresh,
        'all_units' => $fresh || ($_GET['all_units'] ?? '') === '1',
    ));
    echo json_encode(array(
        'status' => 'success',
        'product_code' => $productCode,
        'planUnit' => $planUnit,
        'batch_formula' => $batchFormula,
    ));
    exit;
}

if ($type === 'getProcessingRemark') {
    $order_no = mrp_proc_esc($conn, $_GET['order_no'] ?? '');
    $product_code = mrp_proc_esc($conn, $_GET['product_code'] ?? '');
    $split_id = mrp_proc_esc($conn, $_GET['split_id'] ?? '');

    if ($split_id !== '') {
        $sql = "SELECT a.id, a.order_no, a.product_code, p.product_name, a.remarkText
                FROM split_planning_qty a
                LEFT JOIN product p ON p.product_code = a.product_code
                WHERE a.id = '{$split_id}' LIMIT 1";
    } elseif ($order_no !== '' && $product_code !== '') {
        $sql = "SELECT a.id, a.order_no, a.product_code, p.product_name, a.remarkText
                FROM split_planning_qty a
                LEFT JOIN product p ON p.product_code = a.product_code
                WHERE a.order_no = '{$order_no}' AND a.product_code = '{$product_code}'
                ORDER BY a.id DESC LIMIT 1";
    } elseif ($order_no !== '') {
        $sql = "SELECT a.id, a.order_no, a.product_code, p.product_name, a.remarkText
                FROM split_planning_qty a
                LEFT JOIN product p ON p.product_code = a.product_code
                WHERE a.order_no = '{$order_no}'
                ORDER BY a.id DESC LIMIT 1";
    } else {
        echo json_encode(array('status' => 'error', 'remark' => ''));
        exit;
    }

    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(array(
            'status' => 'success',
            'remark' => $row['remarkText'] ?? '',
            'split_id' => $row['id'] ?? '',
            'order_no' => $row['order_no'] ?? '',
            'product_code' => $row['product_code'] ?? '',
            'product_name' => $row['product_name'] ?? '',
        ));
    } else {
        echo json_encode(array('status' => 'success', 'remark' => ''));
    }
    exit;
}

if ($type === 'updateRemark') {
    $remark = mrp_proc_esc($conn, $input['remark'] ?? '');
    $split_id = mrp_proc_esc($conn, $input['split_id'] ?? '');
    if ($split_id === '') {
        echo json_encode(array('status' => 'Split id is required'));
        exit;
    }
    $sql = "UPDATE split_planning_qty SET remarkText='{$remark}' WHERE id='{$split_id}'";
    if ($conn->query($sql)) {
        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => $conn->error));
    }
    exit;
}

if ($type === 'updatePendingPOs') {
    mrp_proc_require_helpers(true);
    $status = $_GET['status'] ?? '';
    if ($status === 'Work Order Processed' && empty($input['batches'])) {
        po_processing_json_response(array('status' => 'error', 'message' => 'Please generate batches before approving.'));
    }
    po_processing_update_split($conn, $input, $status, $_GET['emp_id'] ?? '', $entry_date);
    exit;
}

echo json_encode(array('status' => 'error', 'message' => 'Unknown type: ' . $type));
