<?php
/**
 * Marketing PO entry flow (no batch generation):
 *   New (Entered) → Processing (view/send) → Approval (Pending) → Log / Approved
 *
 * Endpoints (type=):
 *   getEnteredPOsForProcessing
 *   getEnteredPODetail&id=
 *   updateProcessingPoStatus  (body/GET: status=Send for Approval|Cancelled|Hold, id=, remark=)
 *   getMarketingPoEntryLog
 */
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/po-entry-flow-error.log');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../token.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

date_default_timezone_set('Asia/Kolkata');
$token = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$_GET['token'] = $token;
$entry_date = date('Y-m-d H:i:s');
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = array();
}

function pef_esc($conn, $v) {
    return $conn->real_escape_string((string)($v ?? ''));
}

function pef_json($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function pef_ensure_columns($conn) {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $cols = array(
        'processing_by' => 'VARCHAR(50) NULL',
        'processing_date' => 'VARCHAR(50) NULL',
        'processing_remark' => 'TEXT NULL',
        'client_type' => 'TEXT NULL',
        'terms' => 'LONGTEXT NULL',
        'products' => 'LONGTEXT NULL',
        'valid_till' => 'TEXT NULL',
        'remark' => 'TEXT NULL',
        'approve_by' => 'VARCHAR(50) NULL',
        'approve_date' => 'VARCHAR(50) NULL',
        'approve_digital_signature' => 'TEXT NULL',
        'approve_digital_signature_date' => 'VARCHAR(50) NULL',
        'reject_digital_signature' => 'TEXT NULL',
        'reject_digital_signature_date' => 'VARCHAR(50) NULL',
        'batches' => 'LONGTEXT NULL',
        'leftover' => 'VARCHAR(50) NULL',
        'excess' => 'VARCHAR(50) NULL',
    );
    foreach ($cols as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM `po_entry` LIKE '" . pef_esc($conn, $col) . "'");
        if ($check && $check->num_rows === 0) {
            @$conn->query("ALTER TABLE `po_entry` ADD COLUMN `{$col}` {$def}");
        }
    }
}

function pef_auth($conn, $token) {
    $tokenEsc = pef_esc($conn, $token);
    if ($tokenEsc === '') {
        pef_json(array('status' => 'error', 'message' => 'Missing session token. Please login again.'), 401);
    }
    $res = $conn->query("SELECT * FROM token WHERE token='{$tokenEsc}'");
    if (!$res || $res->num_rows === 0) {
        pef_json(array('status' => 'error', 'message' => 'Invalid or missing token'), 401);
    }
    $row = $res->fetch_assoc();
    $string = decrypt('decrypt', $tokenEsc, $row['key1'], $row['key2']);
    $parts = explode('$', (string)$string);
    $_GET['emp_id'] = $parts[0] ?? '';
    $_GET['department'] = $parts[1] ?? '';
}

function pef_load_products($conn, array $po) {
    $products = array();
    $orderNo = pef_esc($conn, $po['order_no'] ?? '');
    $poId = (int)($po['id'] ?? 0);
    $sql = "SELECT o.*,
                COALESCE(NULLIF(o.order_qty,''), NULLIF(o.planQty,''), NULLIF(o.plan_qty,'')) AS order_qty,
                COALESCE(NULLIF(o.unit,''), NULLIF(o.planUnit,''), NULLIF(o.packingUnit,'')) AS unit,
                pr.product_name, pr.product_type, pr.grade
            FROM order_materials o
            LEFT JOIN product pr ON o.product_code = pr.product_code
            WHERE ";
    if ($orderNo !== '') {
        $sql .= "o.order_no='{$orderNo}'";
        if ($poId > 0) {
            $sql .= " OR o.po_entry_id='{$poId}'";
        }
    } elseif ($poId > 0) {
        $sql .= "o.po_entry_id='{$poId}'";
    } else {
        return $products;
    }
    $sql .= ' ORDER BY o.id ASC';
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['details']) && is_string($row['details'])) {
                $decoded = json_decode($row['details'], true);
                $row['details'] = is_array($decoded) ? $decoded : array();
            }
            if (!empty($row['pack_size']) && is_string($row['pack_size'])) {
                $decoded = json_decode($row['pack_size'], true);
                $row['pack_size'] = is_array($decoded) ? $decoded : array(
                    'pack_size' => $row['packingStyle'] ?? '',
                    'unit' => $row['packingUnit'] ?? ($row['unit'] ?? ''),
                );
            } else {
                $row['pack_size'] = array(
                    'pack_size' => $row['packingStyle'] ?? '',
                    'unit' => $row['packingUnit'] ?? ($row['unit'] ?? ''),
                );
            }
            $products[] = $row;
        }
    }
    if (count($products) === 0 && !empty($po['products'])) {
        $fromJson = json_decode($po['products'], true);
        if (is_array($fromJson)) {
            $products = $fromJson;
        }
    }
    return $products;
}

function pef_parse_service_entries($raw) {
    $entries = array();
    if ($raw === null || $raw === '') {
        return $entries;
    }
    if (is_array($raw)) {
        $data = $raw;
    } else {
        $data = json_decode((string)$raw, true);
    }
    if (!is_array($data)) {
        return $entries;
    }
    if (isset($data['savedEntries']) && is_array($data['savedEntries'])) {
        return $data['savedEntries'];
    }
    if (isset($data[0]) && is_array($data[0])) {
        return $data;
    }
    return $entries;
}

function pef_parse_batches_field(array &$row) {
    if (empty($row['batches'])) {
        $row['batches'] = array();
    } elseif (is_string($row['batches'])) {
        $decoded = json_decode($row['batches'], true);
        $row['batches'] = is_array($decoded) ? $decoded : array();
    } elseif (!is_array($row['batches'])) {
        $row['batches'] = array();
    }
    $row['leftover'] = floatval($row['leftover'] ?? 0);
    $row['excess'] = floatval($row['excess'] ?? 0);
}

/**
 * After marketing PO approval: sync line status + Work_order_materials
 * so Planning /Receivepofo can pick up the FO.
 */
function pef_push_approved_po_to_receive($conn, $poId, $empId, $entryDate) {
    $poId = (int)$poId;
    if ($poId <= 0) {
        return array('status' => 'error', 'message' => 'Invalid PO id', 'wo_inserted' => 0);
    }

    $poRes = $conn->query("SELECT * FROM po_entry WHERE id={$poId} LIMIT 1");
    if (!$poRes || $poRes->num_rows === 0) {
        return array('status' => 'error', 'message' => 'PO not found', 'wo_inserted' => 0);
    }
    $po = $poRes->fetch_assoc();
    pef_parse_batches_field($po);

    $orderEsc = pef_esc($conn, $po['order_no'] ?? '');
    $plantEsc = pef_esc($conn, $po['plant_id'] ?? ($_GET['plant_id'] ?? ''));
    $empEsc = pef_esc($conn, $empId);
    $entryEsc = pef_esc($conn, $entryDate);
    $billingEsc = pef_esc($conn, $po['billing_type'] ?? 'Forcast');
    if ($billingEsc === '') {
        $billingEsc = 'Forcast';
        $conn->query("UPDATE po_entry SET billing_type='Forcast' WHERE id={$poId}");
    }

    // Prefer line batches; fall back to header batches from processing
    $lines = array();
    $lineSql = "SELECT * FROM order_materials WHERE po_entry_id={$poId}";
    if ($orderEsc !== '') {
        $lineSql .= " OR order_no='{$orderEsc}'";
    }
    $lineSql .= ' ORDER BY id ASC';
    $lineRes = $conn->query($lineSql);
    if ($lineRes) {
        while ($line = $lineRes->fetch_assoc()) {
            if (!empty($line['batches']) && is_string($line['batches'])) {
                $decoded = json_decode($line['batches'], true);
                $line['batches'] = is_array($decoded) ? $decoded : array();
            }
            if (!is_array($line['batches'] ?? null)) {
                $line['batches'] = array();
            }
            if (empty($line['batches']) && !empty($po['batches'])) {
                $line['batches'] = $po['batches'];
                $batchesJson = pef_esc($conn, json_encode($po['batches']));
                $leftover = pef_esc($conn, $po['leftover'] ?? 0);
                $excess = pef_esc($conn, $po['excess'] ?? 0);
                $docNo = pef_esc($conn, $line['doc_no'] ?? $poId);
                $planMonth = pef_esc($conn, $line['planMonth'] ?? '');
                if ($planMonth === '' && !empty($line['deliveryDate'])) {
                    $ts = strtotime((string)$line['deliveryDate']);
                    if ($ts !== false) {
                        $planMonth = date('m-Y', $ts);
                    }
                }
                $conn->query(
                    "UPDATE order_materials SET
                        batches='{$batchesJson}',
                        leftover='{$leftover}',
                        excess='{$excess}',
                        doc_no=IF(IFNULL(doc_no,'')='', '{$docNo}', doc_no),
                        planMonth=IF(IFNULL(planMonth,'')='', '{$planMonth}', planMonth),
                        status='Work Order Preparation Approved'
                     WHERE id=" . (int)$line['id']
                );
            } else {
                $docNo = pef_esc($conn, $line['doc_no'] ?? $poId);
                $planMonth = pef_esc($conn, $line['planMonth'] ?? '');
                if ($planMonth === '' && !empty($line['deliveryDate'])) {
                    $ts = strtotime((string)$line['deliveryDate']);
                    if ($ts !== false) {
                        $planMonth = date('m-Y', $ts);
                    }
                }
                $conn->query(
                    "UPDATE order_materials SET
                        status='Work Order Preparation Approved',
                        doc_no=IF(IFNULL(doc_no,'')='', '{$docNo}', doc_no),
                        planMonth=IF(IFNULL(planMonth,'')='', '{$planMonth}', planMonth)
                     WHERE id=" . (int)$line['id']
                );
            }
            $lines[] = $line;
        }
    }

    $helper = __DIR__ . '/processing_po_helpers.php';
    if (is_file($helper)) {
        require_once $helper;
    }

    $woInserted = 0;
    foreach ($lines as $line) {
        $batches = $line['batches'] ?? array();
        if (!is_array($batches)) {
            $batches = array();
        }
        // Prefer line batches; fall back to header batches from processing
        if (count($batches) === 0 && !empty($po['batches']) && is_array($po['batches'])) {
            $batches = $po['batches'];
        }
        // If still empty, build a single batch from plan qty so Receive FO/PO can see the line
        if (count($batches) === 0) {
            $planQty = floatval($line['planQty'] ?? ($line['plan_qty'] ?? ($po['plan_qty'] ?? 0)));
            if ($planQty > 0) {
                $unit = $line['planUnit'] ?? ($line['unit'] ?? ($po['planUnit'] ?? ''));
                $batches = array(array(
                    'count' => 1,
                    'size' => $planQty,
                    'unit' => $unit,
                ));
                $batchesJson = pef_esc($conn, json_encode($batches));
                $conn->query(
                    "UPDATE order_materials SET batches='{$batchesJson}', status='Work Order Preparation Approved'
                     WHERE id=" . (int)$line['id']
                );
                if (!empty($po['id']) && (empty($po['batches']) || $po['batches'] === '[]' || $po['batches'] === 'null')) {
                    $conn->query(
                        "UPDATE po_entry SET batches='{$batchesJson}' WHERE id=" . (int)$po['id']
                    );
                }
            }
        }
        if (!is_array($batches) || count($batches) === 0) {
            continue;
        }
        $orderNo = trim((string)($line['order_no'] ?? $po['order_no'] ?? ''));
        $orderNoEsc = pef_esc($conn, $orderNo);
        if ($orderNoEsc === '') {
            continue;
        }
        $existing = $conn->query(
            "SELECT id FROM Work_order_materials
             WHERE order_no='{$orderNoEsc}'
               AND IFNULL(order_no,'') != ''
             LIMIT 1"
        );
        if ($existing && $existing->num_rows > 0) {
            continue;
        }

        $input = array(
            'po_entry_id' => $poId,
            'plant_id' => $line['plant_id'] ?? ($po['plant_id'] ?? ''),
            'workorder_no' => function_exists('po_processing_next_workorder_no')
                ? po_processing_next_workorder_no($conn)
                : ('BO' . str_pad((string)$poId, 3, '0', STR_PAD_LEFT)),
            'mainGroupName' => $line['mainGroupName'] ?? ($po['mainGroupName'] ?? ''),
            'groupcode' => $line['groupcode'] ?? ($po['groupcode'] ?? ''),
            'subClient' => $line['subClient'] ?? ($po['subClient'] ?? ''),
            'order_no' => $orderNo,
            'doc_no' => $line['doc_no'] ?? $poId,
            'Fo_code' => $line['Fo_code'] ?? '',
            'planMonth' => $line['planMonth'] ?? '',
            'product_code' => $line['product_code'] ?? ($po['parent_product_code'] ?? ''),
            'packingStyle' => $line['packingStyle'] ?? '',
            'packingUnit' => $line['packingUnit'] ?? '',
            'planUnit' => $line['planUnit'] ?? ($line['unit'] ?? ''),
            'deliveryDate' => $line['deliveryDate'] ?? '',
            'remark' => $po['remark'] ?? '',
            'parent_product_code' => $line['parent_product_code'] ?? ($po['parent_product_code'] ?? ''),
            'CombiMaster_dtl_qty' => $line['CombiMaster_dtl_qty'] ?? '',
            'billing_type' => $line['billing_type'] ?? ($po['billing_type'] ?? 'Forcast'),
            'excess' => $line['excess'] ?? ($po['excess'] ?? 0),
            'leftover' => $line['leftover'] ?? ($po['leftover'] ?? 0),
            'plan_qty' => $line['planQty'] ?? ($line['plan_qty'] ?? ($po['plan_qty'] ?? 0)),
            'planQty' => $line['planQty'] ?? ($line['plan_qty'] ?? ($po['plan_qty'] ?? 0)),
        );

        $planQty = floatval($input['plan_qty']);
        if (function_exists('po_processing_insert_work_orders')) {
            $before = 0;
            $cntRes = $conn->query("SELECT COUNT(*) AS c FROM Work_order_materials WHERE order_no='{$orderNoEsc}'");
            if ($cntRes && ($cr = $cntRes->fetch_assoc())) {
                $before = (int)$cr['c'];
            }
            po_processing_insert_work_orders(
                $conn,
                $input,
                $batches,
                'Work Order Processed',
                $empId,
                $entryDate,
                $planQty
            );
            $after = $before;
            $cntRes2 = $conn->query("SELECT COUNT(*) AS c FROM Work_order_materials WHERE order_no='{$orderNoEsc}'");
            if ($cntRes2 && ($cr2 = $cntRes2->fetch_assoc())) {
                $after = (int)$cr2['c'];
            }
            $woInserted += max(0, $after - $before);
        }
    }

    return array(
        'status' => 'success',
        'wo_inserted' => $woInserted,
        'lines' => count($lines),
        'message' => 'Ready for Planning Receive FO/PO',
    );
}

function pef_enrich_row($conn, array $row) {
    $row['terms'] = json_decode($row['terms'] ?? '[]', true);
    if (!is_array($row['terms'])) {
        $row['terms'] = array();
    }
    $row['products'] = pef_load_products($conn, $row);
    foreach ($row['products'] as &$prod) {
        $packLabel = trim((string)($prod['packSizeLabel'] ?? ''));
        if ($packLabel === '') {
            $packLabel = trim((string)(($prod['packingStyle'] ?? '') . ' ' . ($prod['packingUnit'] ?? '')));
        }
        if ($packLabel === '' && is_array($prod['pack_size'] ?? null)) {
            $packLabel = trim(
                (string)(($prod['pack_size']['pack_size'] ?? '') . ' ' . ($prod['pack_size']['unit'] ?? ''))
            );
        }
        $prod['packSizeLabel'] = $packLabel !== '' ? $packLabel : '—';
        $prod['planQty'] = $prod['planQty'] ?? ($prod['order_qty'] ?? ($prod['plan_qty'] ?? ''));
        $prod['planUnit'] = $prod['planUnit'] ?? ($prod['unit'] ?? ($prod['packingUnit'] ?? ''));
    }
    unset($prod);

    $row['service_entries'] = pef_parse_service_entries($row['serviceDescriptionData'] ?? '');
    if (!is_array($row['service_entries'])) {
        $row['service_entries'] = array();
    }
    $cats = array();
    if (!empty($row['serviceCategory'])) {
        foreach (explode(',', (string)$row['serviceCategory']) as $c) {
            $c = trim($c);
            if ($c !== '') {
                $cats[] = $c;
            }
        }
    }
    if (!$cats && $row['service_entries']) {
        foreach ($row['service_entries'] as $e) {
            if (!empty($e['category'])) {
                $cats[] = $e['category'];
            }
        }
        $cats = array_values(array_unique($cats));
    }
    $row['service_categories'] = $cats;

    $row['plan_type'] = trim((string)($row['client_type'] ?? $row['billing_type'] ?? $row['po_type'] ?? ''));
    $isForecast = stripos((string)($row['billing_type'] ?? ''), 'forcast') !== false
        || stripos((string)($row['billing_type'] ?? ''), 'forecast') !== false
        || strcasecmp($row['plan_type'], 'Forecast') === 0;
    if ($isForecast) {
        $row['is_forecast'] = true;
    } else {
        $row['is_forecast'] = false;
    }
    return $row;
}


pef_auth($conn, $token);
pef_ensure_columns($conn);

$type = $_GET['type'] ?? '';
$plantId = pef_esc($conn, $_GET['plant_id'] ?? '');
$empId = pef_esc($conn, $_GET['emp_id'] ?? '');

if ($type === 'getEnteredPOsForProcessing') {
    $output = array();
    $sql = "SELECT p.id, p.order_no, p.po_no, p.po_date, p.valid_till, p.status, p.po_type, p.file,
                   p.entry_date, p.client_type, p.client_code, p.mainGroupName, p.plan_qty,
                   p.parent_product_code, p.billing_type, p.groupcode, p.subClient,
                   p.serviceCategory, p.serviceDescription, p.serviceDescriptionData,
                   p.batches, p.leftover, p.excess,
                   c.TrdNm, c.LglNm,
                   (SELECT CONCAT(IFNULL(firstname,''),' ',IFNULL(middlename,''),' ',IFNULL(lastname,''))
                    FROM employee WHERE emp_id = p.entry_by LIMIT 1) AS emp_name,
                   (SELECT pr.product_name FROM product pr
                    WHERE pr.product_code = COALESCE(NULLIF(p.parent_product_code,''),
                      (SELECT om.product_code FROM order_materials om WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1)
                    ) LIMIT 1) AS product_name,
                   (SELECT om.product_code FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS product_code,
                   (SELECT om.planMonth FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS planMonth,
                   (SELECT COALESCE(NULLIF(om.planUnit,''), NULLIF(om.packingUnit,''), NULLIF(om.unit,''))
                    FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS planUnit,
                   (SELECT om.deliveryDate FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS deliveryDate
            FROM po_entry p
            LEFT JOIN client c ON p.client_code = c.client_code
            WHERE LOWER(TRIM(IFNULL(p.status,''))) IN ('entered', 'new', 'processing', 'hold')";
    if ($plantId !== '') {
        $sql .= " AND (TRIM(IFNULL(p.plant_id,''))='' OR p.plant_id='{$plantId}')";
    }
    $sql .= ' ORDER BY p.id DESC';
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['plan_type'] = trim((string)($row['client_type'] ?? $row['po_type'] ?? ''));
            $row['fo_entry_type'] = trim((string)($row['client_type'] ?? ''));
            if ($row['fo_entry_type'] === '') {
                $hasProduct = trim((string)($row['product_code'] ?? $row['parent_product_code'] ?? '')) !== ''
                    || (float)($row['plan_qty'] ?? 0) > 0;
                $row['fo_entry_type'] = $hasProduct ? 'FO Product' : 'FO Service';
            }
            pef_parse_batches_field($row);
            $output[] = $row;
        }
    }
    pef_json($output);
}

if ($type === 'getEnteredPODetail') {
    $poId = (int)($_GET['id'] ?? 0);
    if ($poId <= 0) {
        pef_json(null);
    }
    $sql = "SELECT p.*,
                   c.TrdNm, c.LglNm,
                   cons.TrdNm AS consignee_name, cons.LglNm AS consignee_lgl,
                   (SELECT CONCAT(IFNULL(firstname,''),' ',IFNULL(middlename,''),' ',IFNULL(lastname,''))
                    FROM employee WHERE emp_id = p.entry_by LIMIT 1) AS emp_name
            FROM po_entry p
            LEFT JOIN client c ON p.client_code = c.client_code
            LEFT JOIN client cons ON p.conisgnee = cons.client_code
            WHERE p.id={$poId}
              AND LOWER(TRIM(IFNULL(p.status,''))) IN ('entered', 'new', 'processing', 'hold')
            LIMIT 1";
    $res = $conn->query($sql);
    if (!$res || $res->num_rows === 0) {
        pef_json(null);
    }
    pef_json(pef_enrich_row($conn, $res->fetch_assoc()));
}

if ($type === 'saveProcessingBatchPlan') {
    $poId = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if ($poId <= 0) {
        pef_json(array('status' => 'error', 'message' => 'PO ID is required'));
    }

    $batchesRaw = $input['batches'] ?? array();
    if (is_string($batchesRaw)) {
        $decoded = json_decode($batchesRaw, true);
        $batchesRaw = is_array($decoded) ? $decoded : array();
    }
    if (!is_array($batchesRaw)) {
        $batchesRaw = array();
    }

    $batchesJson = pef_esc($conn, json_encode($batchesRaw));
    $leftover = pef_esc($conn, $input['leftover'] ?? 0);
    $excess = pef_esc($conn, $input['excess'] ?? 0);

    $check = $conn->query(
        "SELECT id FROM po_entry WHERE id={$poId}
         AND LOWER(TRIM(IFNULL(status,''))) IN ('entered','new','processing','hold') LIMIT 1"
    );
    if (!$check || $check->num_rows === 0) {
        pef_json(array('status' => 'error', 'message' => 'PO not found in Processing queue'));
    }

    $sql = "UPDATE po_entry SET
                batches='{$batchesJson}',
                leftover='{$leftover}',
                excess='{$excess}'
            WHERE id={$poId}";
    if (!$conn->query($sql)) {
        pef_json(array('status' => 'error', 'message' => $conn->error));
    }

    pef_json(array('status' => 'success'));
}

if ($type === 'updateProcessingPoStatus') {
    $poId = (int)($_GET['id'] ?? $input['id'] ?? 0);
    $statusIn = trim((string)($_GET['status'] ?? $input['status'] ?? ''));
    $remark = pef_esc($conn, $_GET['remark'] ?? $input['remark'] ?? '');

    if ($poId <= 0) {
        pef_json(array('status' => 'error', 'message' => 'PO ID is required'));
    }
    if ($remark === '') {
        pef_json(array('status' => 'error', 'message' => 'Remark is required'));
    }

    $map = array(
        'send for approval' => 'Pending',
        'approve' => 'Pending',
        'approved' => 'Pending',
        'pending' => 'Pending',
        'reject' => 'Cancelled',
        'rejected' => 'Cancelled',
        'cancel' => 'Cancelled',
        'cancelled' => 'Cancelled',
        'canceled' => 'Cancelled',
        'hold' => 'Hold',
        'on hold' => 'Hold',
    );
    $key = strtolower($statusIn);
    if (!isset($map[$key])) {
        pef_json(array('status' => 'error', 'message' => 'Invalid status. Use Send for Approval, Cancelled, or Hold'));
    }
    $newStatus = $map[$key];

    $check = $conn->query(
        "SELECT id, order_no, status FROM po_entry WHERE id={$poId}
         AND LOWER(TRIM(IFNULL(status,''))) IN ('entered','new','processing','hold') LIMIT 1"
    );
    if (!$check || $check->num_rows === 0) {
        pef_json(array('status' => 'error', 'message' => 'PO not found in Processing queue'));
    }
    $po = $check->fetch_assoc();
    $orderEsc = pef_esc($conn, $po['order_no'] ?? '');

    $sql = "UPDATE po_entry SET
                status='{$newStatus}',
                processing_by='{$empId}',
                processing_date='{$entry_date}',
                processing_remark='{$remark}'
            WHERE id={$poId}";
    if (!$conn->query($sql)) {
        pef_json(array('status' => 'error', 'message' => $conn->error));
    }

    // Keep line status in sync for list/log filters
    $lineStatus = $newStatus === 'Pending' ? 'Pending' : $newStatus;
    if ($orderEsc !== '') {
        $conn->query(
            "UPDATE order_materials SET status='{$lineStatus}'
             WHERE order_no='{$orderEsc}' OR po_entry_id={$poId}"
        );
    } else {
        $conn->query("UPDATE order_materials SET status='{$lineStatus}' WHERE po_entry_id={$poId}");
    }

    pef_json(array(
        'status' => 'success',
        'po_id' => $poId,
        'order_no' => $po['order_no'] ?? '',
        'new_status' => $newStatus,
        'message' => $newStatus === 'Pending'
            ? 'Sent to PO Approval'
            : ('Updated to ' . $newStatus),
    ));
}

if ($type === 'getPendingPOsForApproval' || $type === 'getPendingPOs') {
    $poId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($poId > 0) {
        $sql = "SELECT p.*,
                       c.TrdNm, c.LglNm,
                       cons.TrdNm AS consignee_name, cons.LglNm AS consignee_lgl,
                       (SELECT CONCAT(IFNULL(firstname,''),' ',IFNULL(middlename,''),' ',IFNULL(lastname,''))
                        FROM employee WHERE emp_id = p.entry_by LIMIT 1) AS emp_name,
                       (SELECT CONCAT(IFNULL(firstname,''),' ',IFNULL(middlename,''),' ',IFNULL(lastname,''))
                        FROM employee WHERE emp_id = p.processing_by LIMIT 1) AS processing_by_name
                FROM po_entry p
                LEFT JOIN client c ON p.client_code = c.client_code
                LEFT JOIN client cons ON p.conisgnee = cons.client_code
                WHERE p.id={$poId}
                  AND LOWER(TRIM(IFNULL(p.status,'')))='pending'";
        if ($plantId !== '') {
            $sql .= " AND (TRIM(IFNULL(p.plant_id,''))='' OR p.plant_id='{$plantId}')";
        }
        $sql .= ' LIMIT 1';
        $res = $conn->query($sql);
        if (!$res || $res->num_rows === 0) {
            pef_json(null);
        }
        pef_json(pef_enrich_row($conn, $res->fetch_assoc()));
    }

    $output = array();
    $sql = "SELECT p.id, p.id AS doc_no, p.order_no, p.po_no, p.po_date, p.valid_till, p.status, p.po_type, p.file,
                   p.entry_date, p.client_type, p.client_code, p.mainGroupName, p.plan_qty,
                   p.parent_product_code, p.billing_type, p.groupcode, p.subClient,
                   p.serviceCategory, p.serviceDescription, p.serviceDescriptionData,
                   p.processing_by, p.processing_date, p.processing_remark,
                   p.batches, p.leftover, p.excess,
                   c.TrdNm, c.LglNm,
                   (SELECT CONCAT(IFNULL(firstname,''),' ',IFNULL(middlename,''),' ',IFNULL(lastname,''))
                    FROM employee WHERE emp_id = p.entry_by LIMIT 1) AS emp_name,
                   (SELECT CONCAT(IFNULL(firstname,''),' ',IFNULL(middlename,''),' ',IFNULL(lastname,''))
                    FROM employee WHERE emp_id = p.processing_by LIMIT 1) AS processing_by_name,
                   (SELECT pr.product_name FROM product pr
                    WHERE pr.product_code = COALESCE(NULLIF(p.parent_product_code,''),
                      (SELECT om.product_code FROM order_materials om WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1)
                    ) LIMIT 1) AS product_name,
                   (SELECT om.product_code FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS product_code,
                   (SELECT om.planMonth FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS planMonth,
                   (SELECT COALESCE(NULLIF(om.planQty,''), NULLIF(om.order_qty,''))
                    FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS line_plan_qty,
                   (SELECT COALESCE(NULLIF(om.planUnit,''), NULLIF(om.packingUnit,''), NULLIF(om.unit,''))
                    FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS planUnit
            FROM po_entry p
            LEFT JOIN client c ON p.client_code = c.client_code
            WHERE LOWER(TRIM(IFNULL(p.status,'')))='pending'";
    if ($plantId !== '') {
        $sql .= " AND (TRIM(IFNULL(p.plant_id,''))='' OR p.plant_id='{$plantId}')";
    }
    $sql .= ' ORDER BY p.id DESC';
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (empty($row['plan_qty']) && !empty($row['line_plan_qty'])) {
                $row['plan_qty'] = $row['line_plan_qty'];
            }
            $row['fo_entry_type'] = trim((string)($row['client_type'] ?? ''));
            if ($row['fo_entry_type'] === '') {
                $hasProduct = trim((string)($row['product_code'] ?? $row['parent_product_code'] ?? '')) !== ''
                    || (float)($row['plan_qty'] ?? 0) > 0;
                $row['fo_entry_type'] = $hasProduct ? 'FO Product' : 'FO Service';
            }
            $row['proceed_approved_by'] = $row['processing_by_name'] ?? '';
            $row['plan_type'] = trim((string)($row['billing_type'] ?? $row['client_type'] ?? $row['po_type'] ?? ''));
            pef_parse_batches_field($row);
            $output[] = $row;
        }
    }
    pef_json($output);
}

if ($type === 'updatePendingPoApproval' || $type === 'updatePendingPOs') {
    $poId = (int)($_GET['id'] ?? $input['id'] ?? 0);
    $statusIn = trim((string)($_GET['status'] ?? $input['status'] ?? ''));
    $remark = pef_esc($conn, $_GET['remark'] ?? $input['remark'] ?? '');
    $digital = pef_esc($conn, $input['digital_signature'] ?? $_GET['digital_signature'] ?? '');
    if ($digital === '' && $empId !== '') {
        $digital = $empId;
    }

    if ($poId <= 0) {
        pef_json(array('status' => 'error', 'message' => 'PO ID is required'));
    }
    if ($remark === '') {
        pef_json(array('status' => 'error', 'message' => 'Remark is required'));
    }

    $key = strtolower($statusIn);
    if (in_array($key, array('approve', 'approved', 'work order preparation approved'), true)) {
        $newStatus = 'Approved';
    } elseif (in_array($key, array('reject', 'rejected', 'cancel', 'cancelled', 'canceled'), true)) {
        $newStatus = 'Rejected';
    } elseif (in_array($key, array('hold', 'on hold'), true)) {
        $newStatus = 'Hold';
    } else {
        pef_json(array('status' => 'error', 'message' => 'Invalid approval status'));
    }

    $check = $conn->query(
        "SELECT id, order_no, plant_id, status FROM po_entry
         WHERE id={$poId} AND LOWER(TRIM(IFNULL(status,'')))='pending' LIMIT 1"
    );
    if (!$check || $check->num_rows === 0) {
        pef_json(array('status' => 'error', 'message' => 'PO not found in Approval queue'));
    }
    $po = $check->fetch_assoc();
    $orderEsc = pef_esc($conn, $po['order_no'] ?? '');

    if ($newStatus === 'Approved') {
        $sql = "UPDATE po_entry SET
                    status='Approved',
                    approve_by='{$empId}',
                    approve_date='{$entry_date}',
                    remark='{$remark}',
                    approve_digital_signature='{$digital}',
                    approve_digital_signature_date='{$entry_date}'
                WHERE id={$poId}";
    } elseif ($newStatus === 'Hold') {
        $sql = "UPDATE po_entry SET
                    status='Hold',
                    approve_by='{$empId}',
                    approve_date='{$entry_date}',
                    remark='{$remark}'
                WHERE id={$poId}";
    } else {
        $sql = "UPDATE po_entry SET
                    status='Rejected',
                    approve_by='{$empId}',
                    approve_date='{$entry_date}',
                    remark='{$remark}',
                    reject_digital_signature='{$digital}',
                    reject_digital_signature_date='{$entry_date}'
                WHERE id={$poId}";
    }
    if (!$conn->query($sql)) {
        pef_json(array('status' => 'error', 'message' => $conn->error));
    }

    if ($orderEsc !== '') {
        $lineStatus = $newStatus === 'Approved' ? 'Work Order Preparation Approved' : $newStatus;
        $conn->query(
            "UPDATE order_materials SET status='{$lineStatus}'
             WHERE order_no='{$orderEsc}' OR po_entry_id={$poId}"
        );
    } else {
        $lineStatus = $newStatus === 'Approved' ? 'Work Order Preparation Approved' : $newStatus;
        $conn->query("UPDATE order_materials SET status='{$lineStatus}' WHERE po_entry_id={$poId}");
    }

    $receiveSync = array('status' => 'skipped', 'wo_inserted' => 0);
    $foSync = array('status' => 'skipped');
    if ($newStatus === 'Approved') {
        // Ensure order_materials lines exist first (FO helper), then WO rows for Receivepofo
        $foHelper = __DIR__ . '/../mrp/po_to_factoryorder.php';
        if (is_file($foHelper)) {
            require_once $foHelper;
            if (function_exists('mrp_po_fo_push_approved_po')) {
                $foSync = mrp_po_fo_push_approved_po(
                    $conn,
                    $poId,
                    $po['order_no'] ?? '',
                    $_GET['plant_id'] ?? ($po['plant_id'] ?? ''),
                    $empId
                );
            }
        }
        $receiveSync = pef_push_approved_po_to_receive($conn, $poId, $empId, $entry_date);
    }

    $receiveMsg = '';
    if ($newStatus === 'Approved') {
        $receiveMsg = ' — sent to Planning Receive FO/PO';
        if (($receiveSync['wo_inserted'] ?? 0) > 0) {
            $receiveMsg .= ' (' . (int)$receiveSync['wo_inserted'] . ' WO line(s))';
        }
    }

    pef_json(array(
        'status' => 'success',
        'po_id' => $poId,
        'new_status' => $newStatus,
        'receive_fo_po' => $receiveSync,
        'factory_order' => $foSync,
        'message' => $newStatus === 'Approved'
            ? ('Approved' . $receiveMsg)
            : ($newStatus === 'Hold' ? 'Put on Hold' : 'Rejected'),
    ));
}

if ($type === 'getMarketingPoEntryLog' || $type === 'getPendingProcessingPOslog') {
    $output = array();
    $sql = "SELECT p.id, p.id AS doc_no, p.order_no, p.po_no, p.po_date, p.valid_till, p.status, p.po_type, p.file,
                   p.entry_date, p.client_type, p.client_code, p.mainGroupName, p.plan_qty,
                   p.serviceCategory, p.serviceDescription, p.serviceDescriptionData,
                   p.processing_by, p.processing_date, p.processing_remark,
                   p.approve_by, p.approve_date, p.remark AS approval_remark,
                   p.batches, p.leftover, p.excess,
                   c.TrdNm, c.LglNm,
                   (SELECT CONCAT(IFNULL(firstname,''),' ',IFNULL(middlename,''),' ',IFNULL(lastname,''))
                    FROM employee WHERE emp_id = p.entry_by LIMIT 1) AS emp_name,
                   (SELECT CONCAT(IFNULL(firstname,''),' ',IFNULL(middlename,''),' ',IFNULL(lastname,''))
                    FROM employee WHERE emp_id = p.processing_by LIMIT 1) AS processing_by_name,
                   (SELECT CONCAT(IFNULL(firstname,''),' ',IFNULL(middlename,''),' ',IFNULL(lastname,''))
                    FROM employee WHERE emp_id = p.approve_by LIMIT 1) AS approve_by_name,
                   (SELECT pr.product_name FROM product pr
                    WHERE pr.product_code = COALESCE(NULLIF(p.parent_product_code,''),
                      (SELECT om.product_code FROM order_materials om WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1)
                    ) LIMIT 1) AS product_name,
                   (SELECT om.product_code FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS product_code,
                   (SELECT om.planMonth FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS planMonth,
                   (SELECT COALESCE(NULLIF(om.planUnit,''), NULLIF(om.packingUnit,''), NULLIF(om.unit,''))
                    FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS planUnit,
                   (SELECT COALESCE(NULLIF(om.planQty,''), NULLIF(om.order_qty,''))
                    FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS line_plan_qty,
                   (SELECT om.deliveryDate FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS deliveryDate
            FROM po_entry p
            LEFT JOIN client c ON p.client_code = c.client_code
            WHERE LOWER(TRIM(IFNULL(p.status,''))) NOT IN ('entered', 'new', 'processing')";
    if ($plantId !== '') {
        $sql .= " AND (TRIM(IFNULL(p.plant_id,''))='' OR p.plant_id='{$plantId}')";
    }
    $sql .= ' ORDER BY p.id DESC LIMIT 2000';
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (empty($row['plan_qty']) && !empty($row['line_plan_qty'])) {
                $row['plan_qty'] = $row['line_plan_qty'];
            }
            $row['planQty'] = $row['plan_qty'] ?? '';
            $row['fo_entry_type'] = trim((string)($row['client_type'] ?? ''));
            if ($row['fo_entry_type'] === '') {
                $hasProduct = trim((string)($row['product_code'] ?? '')) !== ''
                    || (float)($row['plan_qty'] ?? 0) > 0;
                $row['fo_entry_type'] = $hasProduct ? 'FO Product' : 'FO Service';
            }
            pef_parse_batches_field($row);
            $output[] = $row;
        }
    }
    pef_json($output);
}

if ($type === 'getCancelledProcessingPOs') {
    $output = array();
    $sql = "SELECT p.id, p.id AS order_material_id, p.order_no, p.po_no, p.po_date, p.status,
                   p.entry_date, p.mainGroupName, p.plan_qty, p.file, p.processing_remark,
                   (SELECT om.product_code FROM order_materials om
                    WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1) AS product_code,
                   (SELECT pr.product_name FROM product pr
                    WHERE pr.product_code = COALESCE(NULLIF(p.parent_product_code,''),
                      (SELECT om.product_code FROM order_materials om WHERE om.order_no = p.order_no OR om.po_entry_id = p.id ORDER BY om.id ASC LIMIT 1)
                    ) LIMIT 1) AS product_name
            FROM po_entry p
            WHERE LOWER(TRIM(IFNULL(p.status,''))) IN ('cancelled', 'rejected')";
    if ($plantId !== '') {
        $sql .= " AND (TRIM(IFNULL(p.plant_id,''))='' OR p.plant_id='{$plantId}')";
    }
    $sql .= ' ORDER BY p.id DESC LIMIT 500';
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $output[] = $row;
        }
    }
    pef_json($output);
}

if ($type === 'proceed_cancelled_order') {
    $poId = (int)($input['order_material_id'] ?? $input['id'] ?? 0);
    if ($poId <= 0) {
        pef_json(array('status' => 'error', 'message' => 'PO ID is required'));
    }
    $check = $conn->query(
        "SELECT id, order_no FROM po_entry WHERE id={$poId}
         AND LOWER(TRIM(IFNULL(status,''))) IN ('cancelled','rejected') LIMIT 1"
    );
    if (!$check || $check->num_rows === 0) {
        pef_json(array('status' => 'error', 'message' => 'Cancelled PO not found'));
    }
    $po = $check->fetch_assoc();
    $orderEsc = pef_esc($conn, $po['order_no'] ?? '');
    if (!$conn->query("UPDATE po_entry SET status='Entered', processing_remark='', processing_by='', processing_date='' WHERE id={$poId}")) {
        pef_json(array('status' => 'error', 'message' => $conn->error));
    }
    if ($orderEsc !== '') {
        $conn->query("UPDATE order_materials SET status='Entered' WHERE order_no='{$orderEsc}' OR po_entry_id={$poId}");
    } else {
        $conn->query("UPDATE order_materials SET status='Entered' WHERE po_entry_id={$poId}");
    }
    pef_json(array('status' => 'success', 'message' => 'Moved back to Processing'));
}

pef_json(array('status' => 'error', 'message' => 'Unknown type'), 400);
